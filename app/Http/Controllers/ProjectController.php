<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Client;
use App\Models\Project;
use App\Models\SharedProject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProjectController
{
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

        $clients = Client::where('user_id', $userId)->get();

        return Inertia::render('ProjectsPage', [
            'storeAction' => route('dashboard.projects.store'),
            'baseAction'  => url('/dashboard/projects'),
            'projects'    => $projects->map(fn($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'description' => $p->description ?? '',
                'daily_rate'  => $p->daily_rate ? (float) $p->daily_rate : null,
                'client_id'   => $p->client_id,
                'client_name' => $p->client->name,
                'created_at'  => $p->created_at?->translatedFormat('j M Y') ?? '',
                'is_owner'    => $p->is_owner,
                'is_shared'   => $p->is_shared,
            ])->values(),
            'clients' => $clients->map(fn($c) => [
                'id'         => $c->id,
                'name'       => $c->name,
                'daily_rate' => (float) $c->daily_rate,
                'is_owner'   => true,
            ])->values(),
            'open' => request()->has('open'),
        ]);
    }

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
            'client_id'   => [
                'required',
                \Illuminate\Validation\Rule::exists('clients', 'id')->where('user_id', $account->id),
            ],
        ]);

        Project::create($project);

        return to_route('dashboard.projects');
    }

    public function update(Request $request, Project $project)
    {
        if ($project->client->user_id !== Account::authenticated()->id) {
            throw new NotFoundHttpException();
        }

        $validated = $request->validate([
            'name'        => 'required|max:255',
            'description' => 'nullable|max:255',
            'daily_rate'  => 'nullable|numeric|min:0',
            'client_id'   => [
                'required',
                \Illuminate\Validation\Rule::exists('clients', 'id')->where('user_id', Account::authenticated()->id),
            ],
        ]);

        $project->update($validated);

        return to_route('dashboard.projects');
    }

    public function destroy(Project $project)
    {
        if ($project->client->user_id !== Account::authenticated()->id) {
            throw new NotFoundHttpException();
        }

        $project->delete();

        return to_route('dashboard.projects');
    }
}
