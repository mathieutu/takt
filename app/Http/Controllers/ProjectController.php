<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = Account::authenticated()->id;

        $projects = Project::whereHas('client', fn($q) => $q->where('user_id', $userId))->get();
        $clients = Client::where('user_id', $userId)->get();

        return view('dashboard.singletons.projects', [
            'projects' => $projects,
            'clients' => $clients
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
