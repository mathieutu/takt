<?php

namespace App\Http\Controllers;

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
        $projects = Project::all();
        $clients = Client::all();

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
        $projet = $request->validate([
            'name' => 'required|max:255',
            'description' => 'required|max:255',
            'daily_rate' => 'numeric|min:0',
            'client_id' => 'required',
        ]);

        Project::create($projet);

        return to_route('dashboard.projects');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'description' => 'required|max:255',
            'daily_rate' => 'numeric|min:0',
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
