<?php

namespace App\Http\Controllers;

use App\Actions\CreateProject;
use App\Actions\UpdateProject;
use App\Actions\UpsertEntries;
use App\Http\Concerns\BuildsProjectsPageProps;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController
{
    use BuildsProjectsPageProps;

    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->projects()->active()->doesntExist()) {
            $hasHidden = $user->projects()->inactive()->exists()
                || $user->clients()->onlyTrashed()->exists();

            if (! $hasHidden) {
                return redirect()->route('projects.create');
            }

            if (! $request->boolean('with_trashed')) {
                return redirect()->route('projects.index', ['with_trashed' => true]);
            }
        }

        return Inertia::render('ProjectsPage', $this->projectsPageProps($request));
    }

    public function create(Request $request): Response
    {
        return Inertia::render('ProjectForm', [
            'page' => $this->projectsPageProps($request),
            'modal' => [
                'project' => null,
                'clients' => $request->user()->clients()->get()->map->export(['id', 'name', 'daily_rate']),
            ],
        ]);
    }

    public function store(Request $request, CreateProject $createProject): RedirectResponse
    {
        $createProject($request->user(), $request->all());

        return redirect()
            ->route('projects.index')
            ->with('success', 'Projet créé avec succès.');
    }

    public function duplicate(Project $project): RedirectResponse
    {
        $newProject = tap($project->replicate(['end_date']))->save();

        return redirect()
            ->route('projects.edit', ['project' => $newProject])
            ->with('success', 'Projet dupliqué avec succès.');
    }

    public function edit(Request $request, Project $project): Response
    {
        // Guarded against self-reference: after a validation error, Inertia reloads this very edit
        // page and the browser's Referer header for that reload is the edit page itself — recording
        // it as the "back" destination would trap every later successful save on this same page.
        $referer = $request->header('Referer');

        if ($referer && $referer !== $request->url()) {
            $request->session()->put(
                "back_url_project_{$project->id}",
                Str::after($referer, $request->getSchemeAndHttpHost()),
            );
        }

        return Inertia::render('ProjectForm', [
            'page' => $this->projectsPageProps($request),
            'modal' => [
                'project' => $project->export([
                    'id',
                    'name',
                    'description',
                    'daily_rate',
                    'daily_rates',
                    'max_month_budget',
                    'monthly_budgets',
                    'max_total_budget',
                    'client_id',
                ])->merge([
                    'start_date' => $project->start_date->toDateString(),
                    'end_date' => $project->end_date?->toDateString(),
                ]),
                'clients' => $request->user()->clients()->get()->map->export(['id', 'name', 'daily_rate']),
            ],
            'back_url' => $request->session()->get("back_url_project_{$project->id}"),
        ]);
    }

    public function update(Request $request, Project $project, UpdateProject $updateProject): RedirectResponse
    {
        $updateProject($project, $request->all());

        $backUrl = $request->session()->pull('back_url_project_'.$project->id, route('projects.index'));

        return redirect()
            ->to($backUrl)
            ->with('success', 'Projet mis à jour avec succès.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        if ($project->timesheetEntries()->exists() || $project->invoices()->exists()) {
            return redirect()
                ->back()
                ->with('error', 'Impossible de supprimer définitivement un projet ayant des entrées.');
        }

        $project->delete();

        return redirect()
            ->back()
            ->with('success', 'Projet supprimé définitivement.');
    }

    public function syncEntries(Request $request, Project $project, UpsertEntries $upsertEntries): RedirectResponse|JsonResponse
    {
        $upsertEntries($project, $request->all());

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Entries synchronized.']);
        }

        return redirect()->back();
    }
}
