<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ActivityTime;
use App\Models\Client;
use App\Models\Project;

class DashboardController
{
    public function index()
    {
        $account = Account::authenticated();

        $clients = Client::where('user_id', $account->id)->get();
        $clientIds = $clients->pluck('id')->all();

        $projects = Project::whereIn('client_id', $clientIds)->get();
        $projectIds = $projects->pluck('id')->all();

        $activityTimes = ActivityTime::whereIn('project_id', $projectIds)
            ->orderBy('start_date', 'desc')
            ->get();

        $entries = $activityTimes->map(fn($e) => [
            'id'        => $e->id,
            'projectId' => $e->project_id,
            'date'      => $e->start_date->format('Y-m-d'),
            'value'     => round(($e->day_coverage ?? 0) / 100, 2),
            'label'     => $e->label,
        ])->values();

        $clientsData = $clients->map(fn($c) => [
            'id'         => $c->id,
            'name'       => $c->name,
            'daily_rate' => (float) $c->daily_rate,
        ])->values();

        $projectsData = $projects->map(fn($p) => [
            'id'         => $p->id,
            'clientId'   => $p->client_id,
            'name'       => $p->name,
            'daily_rate' => $p->daily_rate !== null ? (float) $p->daily_rate : null,
        ])->values();

        return view('dashboard.index', [
            'entries'  => $entries,
            'clients'  => $clientsData,
            'projects' => $projectsData,
        ]);
    }
}
