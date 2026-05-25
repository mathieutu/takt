<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $projects = $request->user()->projects()->with('timesheetEntries', 'sharer')->get();

        if ($projects->isEmpty()) {
            return redirect()->route('projects.index');
        }

        $entries = $projects->flatMap->timesheetEntries
            ->sortByDesc('date')
            ->map(fn ($e) => [
                'id' => $e->id,
                'projectId' => $e->project_id,
                'date' => $e->date->toDateString(),
                'value' => round($e->coverage / 100, 2),
                'label' => $e->title,
            ])
            ->values();

        $clients = $request->user()->clients()->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'daily_rate' => (float) $c->daily_rate,
            ])
            ->values();

        $projectsData = $projects->map(fn ($p) => [
            'id' => $p->id,
            'clientId' => $p->client_id,
            'name' => $p->name,
            'description' => $p->description ?? '',
            'daily_rate' => $p->daily_rate !== null ? (float) $p->daily_rate : null,
            'is_shared' => $p->sharer !== null,
        ])->values();

        return Inertia::render('DashboardPage', [
            'entries' => $entries,
            'clients' => $clients,
            'projects' => $projectsData,
        ]);
    }
}
