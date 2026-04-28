<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Client;
use App\Models\Project;
use App\Models\SharedClient;
use App\Models\SharedProject;
use Illuminate\Http\Request;

class ProjectController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = Account::authenticated()->id;

        $ownedIds = Project::whereHas('client', fn($q) => $q->where('user_id', $userId))->pluck('id');
        $sharedIds = SharedProject::where('account_id', $userId)->pluck('project_id');

        $projects = Project::with('client', 'sharer')
            ->whereIn('id', $ownedIds->merge($sharedIds)->unique())
            ->get()
            ->map(fn($p) => tap($p, function ($p) use ($ownedIds) {
                $p->is_owner = $ownedIds->contains($p->id);
                $p->is_shared = $p->sharer !== null;
            }));

        $ownedClientIds = Client::where('user_id', $userId)->pluck('id');
        $sharedClientIds = SharedClient::where('account_id', $userId)->pluck('client_id');

        $clients = Client::with('sharer')
            ->whereIn('id', $ownedClientIds->merge($sharedClientIds)->unique())
            ->get()
            ->map(fn($c) => tap($c, function ($c) use ($ownedClientIds) {
                $c->is_owner = $ownedClientIds->contains($c->id);
                $c->is_shared = $c->sharer !== null;
            }));

        return view('dashboard.singletons.projects', [
            'projects' => $projects,
            'clients' => $clients,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $account = Account::authenticated();

        if ($request->client_id === 'new') {
            $clientData = $request->validate([
                'client_name' => 'required|max:255',
                'client_rate' => 'required|numeric|min:0',
            ]);

            $client = Client::create([
                'name'       => $clientData['client_name'],
                'daily_rate' => $clientData['client_rate'],
                'user_id'    => $account->id,
            ]);

            $request->merge(['client_id' => $client->id]);
        }

        $project = $request->validate([
            'name'        => 'required|max:255',
            'description' => 'nullable|max:255',
            'daily_rate'  => 'nullable|numeric|min:0',
            'client_id'   => 'required|exists:clients,id',
        ]);

        Project::create($project);

        return to_route('dashboard.projects');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable|max:255',
            'daily_rate' => 'nullable|numeric|min:0',
            'client_id' => 'required',
        ]);

        $project->update($validated);

        return to_route('dashboard.projects');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $project->delete();

        return to_route('dashboard.projects');
    }
}
