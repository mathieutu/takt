<?php

namespace App\Http\Controllers;

use App\Http\Concerns\BuildsProjectBillingEntry;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    use BuildsProjectBillingEntry;

    public function show(Request $request, Project $project): Response
    {
        $this->authorize('update', $project);

        $project->load(['timesheetEntries', 'invoices']);

        return Inertia::render('ProjectBillingPage', [
            'projects' => [$this->buildProjectBillingEntry($project)],
            'is_shared' => false,
        ]);
    }

    public function showClient(Request $request, Client $client): Response
    {
        $this->authorize('update', $client);

        $projects = $client->projects()->orderBy('created_at')->get();

        abort_if($projects->isEmpty(), 404);

        $projects->load(['timesheetEntries', 'invoices']);

        return Inertia::render('ProjectBillingPage', [
            'projects' => $projects->map(fn (Project $p) => $this->buildProjectBillingEntry($p, $client->name))->values(),
            'is_shared' => false,
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $project->invoices()->create($validated);

        return redirect()->route('projects.billing.show', $project);
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice->project);

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $invoice->update($validated);

        return redirect()->route('projects.billing.show', $invoice->project);
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {

        $this->authorize('update', $invoice->project);

        $invoice->delete();

        return redirect()->back();
    }
}
