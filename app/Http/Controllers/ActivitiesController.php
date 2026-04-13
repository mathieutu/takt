<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ActivityTime;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ActivitiesController
{
    public function find(Request $request)
    {
        $project = $this->getProject($request->query('project_id'));

        if (!$project)
            throw new NotFoundHttpException('You must provide a valid project id.');

        $from = $request->query('from');
        $to = $request->query('to');

        return
            $project->activityTimes()
                ->when($from, fn(Builder $query) => (
                    $query->where('start_date', '>=', $from)
                ))
                ->when($to, fn(Builder $query) => (
                    $query->where(
                        '(start_date + day_coverage * 3600 * 24)',
                        '<=',
                        $to
                    )
                ))
                ->get();
    }

    public function getProject(?string $id): ?Project
    {
        return Project::whereRelation('client', 'user_id', Account::authenticated()->id)->find($id);
    }

    public function get(string $id): ActivityTime
    {
        $found = ActivityTime::whereRelation('project.client', 'user_id', Account::authenticated()->id)->find($id);
        if (!$found)
            throw new NotFoundHttpException("$id not found");

        return $found;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $project = $this->getProject($request->query('project_id'));

        return view('dashboard.singletons.activity-reports', [
            'reports' => $project ? $this->find($request) : null,
            'project' => $project,
            'projects' => Project::whereRelation('client', 'user_id', Account::authenticated()->id)->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => [
                'required',
                Rule::exists('projects', 'id')
                    ->whereIn(
                        'id',
                        Project::whereRelation('client', 'user_id', Account::authenticated()->id)->get('id')
                    )
            ],
            'label' => [
                'nullable',
                'string'
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
                'string'
            ]
        ]);

        return ActivityTime::create($validated);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'label' => [
                'nullable',
                'string'
            ],
            'start_date' => [
                'nullable',
                'date'
            ],
            'day_coverage' => [
                'nullable',
                'integer',
                'between:0,100',
            ],
            'comments' => [
                'nullable',
                'string'
            ]
        ]);

        $model = $this->get($id);
        $model->update($validated);

        return $model;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $model = $this->get($id);

        if (!$model->delete()) {
            throw new GoneHttpException("Unable to delete the report #$id");
        }

        return $model;
    }
}
