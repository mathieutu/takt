<?php

namespace App\Actions;

use App\Models\Project;
use App\Models\TimesheetEntry;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UpsertEntries
{
    /**
     * Creates or updates the timesheet entries of a project, one per date. An entry left empty
     * (coverage 0, no title, no description) deletes that day instead. Days absent from the input
     * are untouched. Everything is validated first, then written atomically; with `$dryRun` nothing
     * is written and only the resulting diff is returned.
     *
     * @param  array<string, mixed>  $input
     * @return array{created: list<string>, updated: list<string>, deleted: list<string>}
     */
    public function __invoke(Project $project, array $input, bool $dryRun = false): array
    {
        $validated = Validator::make($input, [
            'entries' => ['required', 'array'],
            'entries.*.date' => [
                'required',
                'date',
                function (string $attribute, mixed $value, Closure $fail) use ($project) {
                    if (! $project->isActiveOn($value)) {
                        $fail('Ce projet ne peut pas recevoir de coverage à cette date.');
                    }
                },
            ],
            'entries.*.coverage' => ['required', 'integer', 'between:0,100'],
            'entries.*.billable' => ['sometimes', 'boolean'],
            'entries.*.title' => ['nullable', 'string'],
            'entries.*.description' => ['nullable', 'string'],
        ])->validate();

        $existingDates = $project->timesheetEntries()->pluck('date')->map->toDateString()->all();
        $diff = ['created' => [], 'updated' => [], 'deleted' => []];

        DB::transaction(function () use ($project, $validated, $existingDates, $dryRun, &$diff) {
            foreach ($validated['entries'] as $entry) {
                $date = CarbonImmutable::parse($entry['date'])->toDateString();
                $exists = in_array($date, $existingDates, true);
                $data = Arr::except($entry, 'date');
                $isEmpty = ($data['coverage'] ?? 0) === 0
                    && blank($data['title'] ?? null)
                    && blank($data['description'] ?? null);

                if ($isEmpty) {
                    if ($exists) {
                        $diff['deleted'][] = $date;
                    }

                    if (! $dryRun) {
                        TimesheetEntry::where(['project_id' => $project->id, 'date' => $entry['date']])->delete();
                    }

                    continue;
                }

                $diff[$exists ? 'updated' : 'created'][] = $date;

                if (! $dryRun) {
                    TimesheetEntry::updateOrCreate(
                        ['project_id' => $project->id, 'date' => $entry['date']],
                        $data,
                    );
                }
            }
        });

        return $diff;
    }
}
