<?php

namespace App\Http\Controllers;

use App\Http\Concerns\BuildsProjectsPageProps;
use App\Models\Project;
use App\Models\TimesheetEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController
{
    use BuildsProjectsPageProps;

    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->projects()->active()->doesntExist()) {
            $hasArchived = $user->projects()->archived()->exists()
                || $user->clients()->onlyTrashed()->exists();

            if (! $hasArchived) {
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

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isNewClient = ! $request->input('client_id');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'daily_rate' => ['integer', 'min:0'],
            'max_month_budget' => ['nullable', 'integer', 'min:1'],
            'max_total_budget' => ['nullable', 'integer', 'min:1'],
            ...(! $isNewClient ? [
                'client_id' => ['required', 'uuid', Rule::exists('clients', 'id')->where('user_id', $user->id)],
            ] : [
                // Used when creating a new client inline
                'client_name' => ['required', 'string', 'max:255'],
                'client_rate' => ['required', 'numeric', 'min:0'],
            ]),
        ]);

        if ($isNewClient) {
            $client = $user->clients()->create([
                'name' => $data['client_name'],
                'daily_rate' => $data['client_rate'],
            ]);

            $data['client_id'] = $client->id;
        }

        Project::create([
            'name' => $data['name'],
            'description' => $data['description'],
            'daily_rate' => $data['daily_rate'],
            'max_month_budget' => $data['max_month_budget'],
            'max_total_budget' => $data['max_total_budget'],
            'client_id' => $data['client_id'],
            'start_date' => today(),
        ]);

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
        $request->session()->put("back_url_project_{$project->id}", $request->header('Referer'));

        return Inertia::render('ProjectForm', [
            'page' => $this->projectsPageProps($request),
            'modal' => [
                'project' => $project->export([
                    'id',
                    'name',
                    'description',
                    'daily_rate',
                    'max_month_budget',
                    'max_total_budget',
                    'client_id',
                ])->merge([
                    'start_date' => $project->start_date->toDateString(),
                    'end_date' => $project->end_date?->toDateString(),
                ]),
                'clients' => $request->user()->clients()->get()->map->export(['id', 'name', 'daily_rate']),
            ],
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'daily_rate' => ['integer', 'min:0'],
            'max_month_budget' => ['nullable', 'integer', 'min:1'],
            'max_total_budget' => ['nullable', 'integer', 'min:1'],
            'client_id' => ['required', Rule::exists('clients', 'id')->where('user_id', $request->user()->id)],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
        ]);

        $project->update($data);

        $backUrl = $request->session()->pull('back_url_project_'.$project->id, route('projects.index'));

        return redirect()
            ->to($backUrl)
            ->with('success', 'Projet mis à jour avec succès.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        if ($project->isArchived()) {
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

        $project->update(['end_date' => today()]);

        return redirect()
            ->back()
            ->with('success', 'Projet archivé avec succès.');
    }

    public function restore(Project $project): RedirectResponse
    {
        $project->update(['end_date' => null]);
        $project->client->restore();

        return redirect()
            ->back()
            ->with('success', 'Projet restauré avec succès.');
    }

    public function syncEntries(Request $request, Project $project): RedirectResponse|JsonResponse
    {
        if ($project->isArchived()) {
            return response()->json(['message' => 'This project is archived and can\'t be updated.'], 422);
        }

        $validated = $request->validate([
            'entries' => ['required', 'array'],
            'entries.*.date' => ['required', 'date'],
            'entries.*.coverage' => ['required', 'integer', 'between:0,100'],
            'entries.*.title' => ['nullable', 'string'],
            'entries.*.description' => ['nullable', 'string'],
        ]);

        foreach ($validated['entries'] as $entry) {
            $data = Arr::except($entry, 'date');
            $isEmpty = ($data['coverage'] ?? 0) === 0
                && blank($data['title'] ?? null)
                && blank($data['description'] ?? null);

            if ($isEmpty) {
                TimesheetEntry::where(['project_id' => $project->id, 'date' => $entry['date']])->delete();

                continue;
            }

            TimesheetEntry::updateOrCreate(
                ['project_id' => $project->id, 'date' => $entry['date']],
                $data,
            );
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Entries synchronized.']);
        }

        return redirect()->back();
    }
}
