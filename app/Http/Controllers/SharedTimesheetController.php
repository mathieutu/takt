<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Share;
use App\Models\TimesheetEntry;
use App\Services\HolidayService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SharedTimesheetController
{
    public function __construct(private HolidayService $holidays) {}

    public function __invoke(Share $share, Request $request): Response
    {
        abort_unless($share->share_type === 'projects', 404);

        $project = Project::findOrFail($share->share_id);
        $date = $request->date('month', 'Y-m') ?? now()->startOfMonth();

        $entries = TimesheetEntry::where('project_id', $project->id)
            ->inMonth($date)
            ->get();

        return Inertia::render('SharedTimesheetPage', [
            'month' => $date->format('Y-m'),
            'shareToken' => $share->id,
            'projectName' => $project->name,
            'clientName' => $project->client->name,
            'projectId' => $project->id,
            'entries' => $entries
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
