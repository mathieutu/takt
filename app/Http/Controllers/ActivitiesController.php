<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ActivityTime;
use App\Models\Project;
use App\Models\SharedProject;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ActivitiesController
{
    public function get(string|ActivityTime $report): ActivityTime
    {
        $account_id = Account::authenticated()->id;

        if ($report instanceof ActivityTime) {
            if ($report->project->client->user_id != $account_id) {
                $report = null;
            }
        } else {
            $report = ActivityTime::whereRelation('project.client', 'user_id', $account_id)->find($report);
        }

        if (! $report) {
            throw new NotFoundHttpException;
        }

        return $report;
    }

    public function find(Request $request)
    {
        $project =
            Account::authenticated()
                ->user
                ->projects()
                ->find($request->query('project_id'));

        if (! $project) {
            throw new NotFoundHttpException('You must provide a valid project id.');
        }

        $from = $request->query('from');
        $to = $request->query('to');

        return
            $project->activityTimes()
                ->when($from, fn (Builder $query) => (
                    $query->where('start_date', '>=', $from)
                ))
                ->when($to, fn (Builder $query) => (
                    $query->whereRaw('(start_date + day_coverage * 3600 * 24) <= ?', [$to])
                ))
                ->get();
    }

    public function index(Request $request)
    {
        $fromParam = $request->query('from');
        $currentDate = $fromParam ? Carbon::parse($fromParam) : Carbon::now();

        $auth = Account::authenticated();
        $userId = $auth->id;

        $ownedIds = Project::whereHas('client', fn ($q) => $q->where('user_id', $userId))->pluck('id');
        $sharedIds = SharedProject::where('account_id', $userId)->pluck('project_id');

        $projects = Project::with('client')
            ->whereIn('id', $ownedIds->merge($sharedIds)->unique())
            ->get()
            ->map(fn ($p) => tap($p, fn ($p) => $p->is_owner = $ownedIds->contains($p->id)));

        $reports = ActivityTime::whereIn('project_id', $projects->pluck('id'))->get();

        return Inertia::render('ActivityReportPage', [
            'storeUrl' => url('/dashboard/reports'),
            'baseUrl' => url('/dashboard/reports'),
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
                'day_coverage' => $r->day_coverage ?? 0,
                'label' => $r->label ?? '',
                'comments' => $r->comments ?? '',
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => [
                'required',
                Rule::exists('projects', 'id')
                    ->whereIn(
                        'id',
                        Project::whereRelation('client', 'user_id', Account::authenticated()->id)->get('id')
                    ),
            ],
            'label' => [
                'nullable',
                'string',
            ],
            'start_date' => [
                'required',
                'date',
            ],
            'day_coverage' => [
                'nullable',
                'integer',
                'between:0,100',
            ],
            'comments' => [
                'nullable',
                'string',
            ],
        ]);

        return ActivityTime::create($validated);
    }

    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'label' => [
                'nullable',
                'string',
            ],
            'start_date' => [
                'nullable',
                'date',
            ],
            'day_coverage' => [
                'nullable',
                'integer',
                'between:0,100',
            ],
            'comments' => [
                'nullable',
                'string',
            ],
        ]);

        $model = $this->get($id);
        $model->update($validated);

        return $model;
    }

    public function destroy(string $id)
    {
        $model = $this->get($id);

        if (! $model->delete()) {
            throw new GoneHttpException("Unable to delete the report #$id");
        }

        return $model;
    }
}
