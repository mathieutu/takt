<?php

namespace App\Http\Controllers;

use App\Models\ActivityTime;
use App\Models\Project;
use App\Models\Share;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShareController
{
    public function generate(Project $project): JsonResponse
    {
        abort_unless($project->client->user_id === Auth::id(), 403);

        $share = $project->share();

        return response()->json(['url' => $share->url()]);
    }

    public function revoke(Project $project): JsonResponse
    {
        if ($project->client->user_id !== Auth::id()) {
            abort(403);
        }

        $project->sharer()->delete();

        return response()->json(['success' => true]);
    }

    public function apply(Share $share, Request $request)
    {
        if ($share->share_type !== 'projects') {
            throw new NotFoundHttpException;
        }

        $project = Project::with('client')->find($share->share_id);

        if (! $project) {
            throw new NotFoundHttpException;
        }

        $fromParam = $request->query('from');
        $currentDate = $fromParam ? Carbon::parse($fromParam) : Carbon::now();

        $reports = ActivityTime::where('project_id', $project->id)->get();

        return Inertia::render('SharedActivityReportPage', [
            'projectName' => $project->name,
            'clientName' => $project->client?->name ?? '',
            'projectId' => $project->id,
            'currentYear' => $currentDate->year,
            'currentMonth' => $currentDate->month,
            'reports' => $reports->map(fn ($r) => [
                'id' => $r->id,
                'project_id' => $r->project_id,
                'start_date' => $r->start_date->format('Y-m-d'),
                'day_coverage' => $r->day_coverage ?? 0,
                'label' => $r->label ?? '',
                'comments' => $r->comments ?? '',
            ])->values(),
        ]);
    }
}
