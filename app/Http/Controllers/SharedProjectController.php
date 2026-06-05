<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Share;
use App\Models\TimesheetEntry;
use App\Services\HolidayService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SharedProjectController
{
    public function __construct(private HolidayService $holidays) {}

    public function __invoke(Share $share, Project $project, Request $request): Response
    {
        abort_unless($share->shareable_type === 'clients', 404);
        abort_unless($project->client_id === $share->shareable_id, 404);

        $date = $request->date('month', 'Y-m') ?? now()->startOfMonth();

        return Inertia::render('SharedPage', [
            'type' => 'project',
            'shareToken' => $share->id,
            'projectName' => $project->name,
            'clientName' => $project->client->name,
            'projectId' => $project->id,
            'month' => $date->format('Y-m'),
            'prevUrl' => route('share.project', [$share, $project]).'?month='.$date->subMonthNoOverflow()->format('Y-m'),
            'nextUrl' => route('share.project', [$share, $project]).'?month='.$date->addMonthNoOverflow()->format('Y-m'),
            'backUrl' => route('share.apply', $share),
            'entries' => TimesheetEntry::where('project_id', $project->id)
                ->inMonth($date)
                ->get()
                ->keyBy(fn (TimesheetEntry $e) => $e->date->toDateString())
                ->map(fn (TimesheetEntry $e) => [
                    'coverage' => $e->coverage,
                    'title' => $e->title ?? '',
                    'description' => $e->description ?? '',
                ]),
            'holidays' => $this->holidays->forMonth($date),
        ]);
    }
}
