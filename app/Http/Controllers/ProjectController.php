<?php

namespace App\Http\Controllers;

use App\Http\Concerns\BuildsProjectBillingEntry;
use App\Http\Concerns\BuildsProjectsPageProps;
use App\Models\Project;
use App\Models\TimesheetEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController
{
    use BuildsProjectBillingEntry, BuildsProjectsPageProps;

    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->projects()->doesntExist()) {
            $hasArchived = $user->projects()->onlyTrashed()->exists()
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
            'daily_rate' => ['integer', 'min:1'],
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
        ]);

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project created successfully.');
    }

    public function duplicate(Project $project): RedirectResponse
    {
        $newProject = tap($project->replicate(['deleted_at']))->save();

        return redirect()
            ->route('projects.edit', ['project' => $newProject])
            ->with('success', 'Project duplicated successfully.');
    }

    public function edit(Request $request, Project $project): Response
    {
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
                    'created_at' => $project->created_at?->toDateString(),
                    'deleted_at' => $project->deleted_at?->toDateString(),
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
            'created_at' => ['required', 'date'],
            'deleted_at' => ['nullable', 'date'],
        ]);

        $project->update($data);

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project, Request $request): RedirectResponse
    {
        if ($project->deleted_at) {
            if ($project->timesheetEntries()->exists() || $project->invoices()->exists()) {
                return redirect()
                    ->back()
                    ->with('error', 'Cannot permanently delete a project with existing entries.');
            }

            $project->forceDelete();

            return redirect()
                ->back()
                ->with('success', 'Project permanently deleted successfully.');
        }

        $project->delete();

        return redirect()
            ->back()
            ->with('success', 'Project archived successfully.');
    }

    public function restore(Project $project): RedirectResponse
    {
        $project->restore();
        $project->client->restore();

        return redirect()
            ->back()
            ->with('success', 'Project restored successfully.');
    }

    public function showBilling(Request $request, Project $project): Response
    {
        $project->load(['timesheetEntries', 'invoices']);

        return Inertia::render('ProjectBillingPage', [
            'projects' => [$this->buildProjectBillingEntry($project)],
            'is_shared' => false,
        ]);
    }

    public function syncEntries(Request $request, Project $project): RedirectResponse
    {
        $entries = $request->validate([
            '*.coverage' => ['required', 'integer', 'between:0,100'],
            '*.title' => ['nullable', 'string'],
            '*.description' => ['nullable', 'string'],
        ]);

        foreach ($entries as $date => $data) {
            TimesheetEntry::updateOrCreate(
                ['project_id' => $project->id, 'date' => $date],
                $data,
            );
        }

        return redirect()->back();
    }
}
