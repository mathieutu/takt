<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(): Response
    {
        $userId = Auth::id();

        $projects = Project::with('client')->withExists('sharer')
            ->whereHas('client', fn ($q) => $q->where('user_id', $userId))
            ->get();

        $clients = Client::where('user_id', $userId)->get();

        return Inertia::render('ProjectsPage', [
            'projects' => $projects->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description ?? '',
                'daily_rate' => $p->daily_rate ? (float) $p->daily_rate : null,
                'client_id' => $p->client_id,
                'client_name' => $p->client->name,
                'created_at' => $p->created_at?->translatedFormat('j M Y') ?? '',
                'is_shared' => $p->sharer_exists,
            ])->values(),
            'clients' => $clients->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'daily_rate' => (float) $c->daily_rate,
            ])->values(),
            'open' => request()->has('open'),
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $userId = Auth::id();
        $data = $request->validated();

        if ($request->client_id === 'new') {
            $client = Client::create([
                'name' => $data['client_name'],
                'daily_rate' => $data['client_rate'],
                'user_id' => $userId,
            ]);
            $data['client_id'] = $client->id;
        }

        Project::create($data);

        return to_route('projects.index');
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return redirect()->back();
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->back();
    }
}
