<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Share;
use App\Models\TimesheetEntry;
use App\Services\HolidayService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class SharedController
{
    public function __construct(private readonly HolidayService $holidays) {}

    public function __invoke(Share $share, Request $request): Response
    {
        $date = $request->date('month', 'Y-m') ?? now()->startOfMonth();

        [$sharedBy, $projects] = match ($share->shareable_type) {
            'projects' => $this->projectData($share, $date, $request),
            'clients' => $this->clientData($share, $date, $request),
            default => abort(404),
        };

        return Inertia::render('TimesheetPage', [
            'shared_by' => $sharedBy,
            'current' => ['year' => $date->year, 'month' => $date->month],
            'urls' => [
                'prevMonth' => route('shares.show', ['share' => $share, 'month' => $date->subMonthNoOverflow()->format('Y-m')], false),
                'nextMonth' => route('shares.show', ['share' => $share, 'month' => $date->addMonthNoOverflow()->format('Y-m')], false),
            ],
            'projects' => $projects,
            'holidays' => $this->holidays->forMonth($date),
        ]);
    }

    /** @return array{string, array} */
    private function projectData(Share $share, Carbon $date, Request $request): array
    {
        /** @var Project $project */
        $project = Project::with('client.user')->findOrFail($share->shareable_id);

        $this->recordReceivedShare($share, $project->client->user_id, $request);

        return [
            $project->client->user->name,
            [[
                'id' => $project->id,
                'name' => $project->name,
                'client' => ['name' => $project->client->name],
                'daily_rate' => $project->daily_rate,
                'entries' => $this->mapEntries(
                    TimesheetEntry::where('project_id', $project->id)->inMonth($date)->get()
                ),
            ]],
        ];
    }

    /** @return array{string, Collection} */
    private function clientData(Share $share, Carbon $date, Request $request): array
    {
        /** @var Client $client */
        $client = Client::with('user')->findOrFail($share->shareable_id);

        $this->recordReceivedShare($share, $client->user_id, $request);

        $projects = $client->projects()->orderBy('created_at', 'desc')->get();
        $entries = TimesheetEntry::whereIn('project_id', $projects->pluck('id'))
            ->inMonth($date)
            ->get()
            ->groupBy('project_id');

        return [
            $client->user->name,
            $projects->map(fn (Project $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'client' => ['name' => $client->name],
                'daily_rate' => $p->daily_rate,
                'entries' => $this->mapEntries($entries[$p->id] ?? collect()),
            ]),
        ];
    }

    private function mapEntries(Collection $entries): Collection
    {
        return $entries
            ->keyBy(fn (TimesheetEntry $e) => $e->date->toDateString())
            ->map(fn (TimesheetEntry $e) => [
                'coverage' => $e->coverage,
                'title' => $e->title ?? '',
                'description' => $e->description ?? '',
            ]);
    }

    private function recordReceivedShare(Share $share, string $ownerId, Request $request): void
    {
        $user = $request->user();

        if ($user && $user->id !== $ownerId) {
            $user->receivedShares()->firstOrCreate(['share_id' => $share->id]);
        }
    }
}
