<?php

namespace App\Mcp\Tools;

use App\Actions\UpsertEntries as UpsertEntriesAction;
use App\Models\Client;
use App\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[Description('Create or update the timesheet entries of a project, one per date (up to '.UpsertEntries::MAX_ENTRIES.' per call, across as many months as needed). Days missing from the list are left untouched; an empty entry (coverage 0, no title, no description) deletes that day. Every date must fall within the project period (start_date/end_date), otherwise the whole batch is rejected and nothing is written. Use dry_run to preview the creations, updates and deletions before writing. duplicate_billing_days lists the days of the batch where, together with the other projects of the same client, more than one day would be billed (a warning, nothing is blocked): tell the user before insisting.')]
#[IsIdempotent]
class UpsertEntries extends TaktTool
{
    public const int MAX_ENTRIES = 400;

    public function handle(Request $request, UpsertEntriesAction $upsertEntries): Response
    {
        $request->validate(['entries' => ['array', 'max:'.self::MAX_ENTRIES]]);

        $dryRun = (bool) $request->get('dry_run');
        $project = $this->findProject($request, (string) $request->get('project_id'));

        $diff = $upsertEntries($project, $request->all(), $dryRun);
        $overbooked = $this->overbookedDaysAfter($project, $request->get('entries', []));

        return Response::json(['dry_run' => $dryRun, ...$diff, 'duplicate_billing_days' => $overbooked]);
    }

    /**
     * Days of the batch that would exceed one billed day for the client once written, counting its other
     * projects. Computed from the input rather than the database so a dry run reports them too. Only
     * a warning: like in the UI, nothing blocks billing the same day twice.
     *
     * @param  array<int, array<string, mixed>>  $entries
     * @return Collection<int, array{date: string, total_coverage: int, projects: array<string, int>}>
     */
    private function overbookedDaysAfter(Project $project, array $entries): Collection
    {
        $dates = collect($entries)->pluck('date')->map(fn ($date) => CarbonImmutable::parse($date)->toDateString());

        if ($dates->isEmpty()) {
            return collect();
        }

        $existingBillable = $project->timesheetEntries()->whereIn('date', $dates)->pluck('billable', 'date')
            ->mapWithKeys(fn ($billable, $date) => [CarbonImmutable::parse($date)->toDateString() => (bool) $billable]);

        $pending = collect($entries)
            ->map(fn (array $entry) => [
                'date' => CarbonImmutable::parse($entry['date'])->toDateString(),
                'project_id' => $project->id,
                'project' => $project->name,
                'coverage' => (int) ($entry['coverage'] ?? 0),
                'billable' => $entry['billable'] ?? null,
            ])
            ->filter(fn (array $row) => $row['coverage'] > 0 && ($row['billable'] ?? $existingBillable->get($row['date'], true)))
            ->map(fn (array $row) => Arr::except($row, 'billable'));

        $others = $project->client->billableCoverageRows($dates->min(), $dates->max())
            ->reject(fn (array $row) => $row['project_id'] === $project->id)
            ->filter(fn (array $row) => $dates->contains($row['date']));

        return Client::overbookedDays($others->concat($pending))
            ->filter(fn (array $day) => $pending->contains('date', $day['date']))
            ->values();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->string()->description('Project id.')->required(),
            'dry_run' => $schema->boolean()->description('Write nothing, only return the diff.'),
            'entries' => $schema->array()->items($schema->object([
                'date' => $schema->string()->description('YYYY-MM-DD.')->required(),
                'coverage' => $schema->integer()->min(0)->max(100)->description('Percentage of the day (100 = 1 day).')->required(),
                'billable' => $schema->boolean()->description('Billable (true by default).'),
                'title' => $schema->string(),
                'description' => $schema->string(),
            ]))->max(self::MAX_ENTRIES)->required(),
        ];
    }
}
