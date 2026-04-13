<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ActivityTime;
use App\Models\Project;
use DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Exists;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ActivitiesController
{
    public function find(Request $request)
    {
        $account = Account::authenticated();
        $project = Project::find($request->query('project_id'));

        if (!$project || $project->client->user_id !== $account->id)
            throw new NotFoundHttpException('You must provide a valid project id.');

        $from = $request->query('from', -INF);
        $to = $request->query('to', INF);

        return
            ActivityTime::where('start_date', '>=', $from)
                ->where(DB::raw('(start_date + day_coverage * 3600 * 24)'), '<=', $to)
                ->where('project_id', $project)
                ->get();
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
        return view('dashboard.activity_reports.index', [
            'reports' => $this->find($request)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $account = Account::authenticated();

        $validated = $request->validate([
            'project_id' => [
                'required',
                (new Exists((new Project)->getTable()))
                    ->where('client.user_id', $account->id)
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
                'min:0',
                'max:100'
            ],
            'comments' => [
                'nullable',
                'string'
            ]
        ]);

        return Project::create($validated);
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
                'min:0',
                'max:100'
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
