<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ActivityTime;
use App\Models\Client;
use App\Models\Project;
use App\Models\SharedProject;
use Inertia\Inertia;

class DashboardController
{
    public function index()
    {
        $account = Account::authenticated();

        $clients = Client::where('user_id', $account->id)->get();
        $clientIds = $clients->pluck('id')->all();

        $ownedProjectIds = Project::whereIn('client_id', $clientIds)->pluck('id');
        $sharedProjectIds = SharedProject::where('account_id', $account->id)->pluck('project_id');

        $projects = Project::with('client', 'sharer')
            ->whereIn('id', $ownedProjectIds->merge($sharedProjectIds)->unique())
            ->get()
            ->map(fn($p) => tap($p, function ($p) use ($ownedProjectIds) {
                $p->is_owner = $ownedProjectIds->contains($p->id);
                $p->is_shared = $p->sharer !== null;
            }));

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
            'is_owner'   => true,
        ])->values();

        $projectsData = $projects->map(fn($p) => [
            'id'          => $p->id,
            'clientId'    => $p->client_id,
            'name'        => $p->name,
            'description' => $p->description ?? '',
            'daily_rate'  => $p->daily_rate !== null ? (float) $p->daily_rate : null,
            'is_owner'    => $p->is_owner,
            'is_shared'   => $p->is_shared,
        ])->values();

        return Inertia::render('DashboardPage', [
            'entries'    => $entries,
            'clients'    => $clientsData,
            'projects'   => $projectsData,
            'csrfToken'  => csrf_token(),
        ]);
    }
}
