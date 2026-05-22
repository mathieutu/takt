<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreActivityTimeRequest;
use App\Http\Requests\UpdateActivityTimeRequest;
use App\Models\ActivityTime;
use App\Models\Project;
use App\Models\SharedProject;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ActivitiesController extends Controller
{
    public function projectReports(Project $project, Request $request)
    {
        Auth::user()->projects()->findOrFail($project->id);

        $from = $request->query('from');
        $to = $request->query('to');

        return $project->activityTimes()
            ->when($from, fn (Builder $query) => $query->where('start_date', '>=', $from))
            ->when($to, fn (Builder $query) => $query->whereRaw('(start_date + day_coverage * 3600 * 24) <= ?', [$to]))
            ->get();
    }

    public function index(Request $request): Response
    {
        $fromParam = $request->query('from');
        $currentDate = $fromParam ? Carbon::parse($fromParam) : Carbon::now();

        $userId = Auth::id();

        $ownedIds = Project::whereHas('client', fn ($q) => $q->where('user_id', $userId))->pluck('id');
        $sharedIds = SharedProject::where('user_id', $userId)->pluck('project_id');

        $projects = Project::with('client')
            ->whereIn('id', $ownedIds->merge($sharedIds)->unique())
            ->get()
            ->map(fn ($p) => tap($p, fn ($p) => $p->is_owner = $ownedIds->contains($p->id)));

        $reports = ActivityTime::whereIn('project_id', $projects->pluck('id'))->get();

        return Inertia::render('ActivityReportPage', [
            'currentYear' => $currentDate->year,
            'currentMonth' => $currentDate->month,
            'projects' => $projects->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'client_name' => $p->client?->name ?? '',
                'daily_rate' => $p->daily_rate
                    ? (float) $p->daily_rate
                    : ($p->client?->daily_rate ? (float) $p->client->daily_rate : 0),
                'is_owner' => $p->is_owner,
            ])->values(),
            'reports' => $reports->map(fn ($r) => [
                'id' => $r->id,
                'project_id' => $r->project_id,
                'start_date' => $r->start_date->format('Y-m-d'),
                'day_coverage' => $r->day_coverage,
                'label' => $r->label ?? '',
                'comments' => $r->comments ?? '',
            ])->values(),
        ]);
    }

    public function store(StoreActivityTimeRequest $request, Project $project): ActivityTime
    {
        Auth::user()->projects()->findOrFail($project->id);

        return ActivityTime::create(['project_id' => $project->id, ...$request->validated()]);
    }

    public function update(UpdateActivityTimeRequest $request, ActivityTime $report): ActivityTime
    {
        $this->authorize('update', $report);

        $report->update($request->validated());

        return $report;
    }

    public function destroy(ActivityTime $report): ActivityTime
    {
        $this->authorize('delete', $report);

        $report->delete();

        return $report;
    }
}
