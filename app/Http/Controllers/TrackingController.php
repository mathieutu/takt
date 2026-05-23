<?php

namespace App\Http\Controllers;

use App\Models\BillingEntry;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TrackingController
{
    public function index(Request $request): Response
    {
        $clients = $request->user()->clients()->orderBy('name')->get(['id', 'name', 'daily_rate']);

        $selectedClientId = $request->query('client_id');
        $projects = collect();

        if ($selectedClientId) {
            $client = $clients->firstWhere('id', $selectedClientId)
                ?? throw new NotFoundHttpException;

            $projects = Project::where('client_id', $client->id)
                ->with(['timesheetEntries', 'billingEntries'])
                ->orderBy('name')
                ->get()
                ->map(function (Project $project) use ($client) {
                    $rate = (float) ($project->daily_rate ?? $client->daily_rate ?? 0);

                    $billingByMonth = $project->billingEntries
                        ->keyBy(fn ($b) => $b->month->format('Y-m'));

                    $months = $project->timesheetEntries
                        ->groupBy(fn ($e) => $e->date->format('Y-m'))
                        ->map(fn ($entries, $month) => [
                            'month' => $month,
                            'days_worked' => round($entries->sum('coverage') / 100, 2),
                            'billing_entry_id' => $billingByMonth->get($month)?->id,
                            'amount_billed' => (float) ($billingByMonth->get($month)?->amount_billed ?? 0),
                            'payment_date' => $billingByMonth->get($month)?->payment_date?->toDateString(),
                            'notes' => $billingByMonth->get($month)?->notes,
                        ])
                        ->sortKeys()
                        ->values();

                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'daily_rate' => $rate,
                        'max_budget' => $project->max_budget !== null ? (float) $project->max_budget : null,
                        'client_name' => $client->name,
                        'months' => $months,
                    ];
                });
        }

        return Inertia::render('TrackingPage', [
            'clients' => $clients->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'daily_rate' => (float) $c->daily_rate,
            ])->values(),
            'selectedClientId' => $selectedClientId,
            'projects' => $projects->values(),
        ]);
    }

    public function storeBilling(Request $request, Project $project): JsonResponse
    {
        abort_unless($request->user()->can('update', $project), 403);

        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'amount_billed' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $entry = BillingEntry::updateOrCreate(
            ['project_id' => $project->id, 'month' => $validated['month'].'-01'],
            [
                'amount_billed' => $validated['amount_billed'],
                'payment_date' => $validated['payment_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ],
        );

        return response()->json([
            'id' => $entry->id,
            'amount_billed' => (float) $entry->amount_billed,
            'payment_date' => $entry->payment_date?->toDateString(),
            'notes' => $entry->notes,
        ]);
    }

    public function updateBilling(Request $request, BillingEntry $entry): JsonResponse
    {
        abort_unless($request->user()->can('update', $entry->project), 403);

        $validated = $request->validate([
            'amount_billed' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $entry->update($validated);

        return response()->json([
            'id' => $entry->id,
            'amount_billed' => (float) $entry->amount_billed,
            'payment_date' => $entry->payment_date?->toDateString(),
            'notes' => $entry->notes,
        ]);
    }

    public function updateBudget(Request $request, Project $project): JsonResponse
    {
        abort_unless($request->user()->can('update', $project), 403);

        $validated = $request->validate([
            'max_budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        $project->update($validated);

        return response()->json([
            'max_budget' => $project->max_budget !== null ? (float) $project->max_budget : null,
        ]);
    }
}
