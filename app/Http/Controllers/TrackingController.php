<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BillingEntry;
use App\Models\Client;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TrackingController
{
    public function index(Request $request)
    {
        $userId = Account::authenticated()->id;

        $clients = Client::where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name', 'daily_rate']);

        $projects = collect();
        $selectedClientId = $request->query('client_id') ? (int) $request->query('client_id') : null;

        if ($selectedClientId) {
            $client = $clients->firstWhere('id', $selectedClientId);
            if (! $client) {
                throw new NotFoundHttpException;
            }

            $projectsList = Project::where('client_id', $client->id)
                ->with(['activityTimes', 'billingEntries'])
                ->orderBy('name')
                ->get();

            foreach ($projectsList as $project) {
                $rate = (float) ($project->daily_rate ?? $client->daily_rate ?? 0);

                $byMonth = $project->activityTimes
                    ->groupBy(fn ($a) => Carbon::parse($a->start_date)->format('Y-m'));

                $billingByMonth = $project->billingEntries
                    ->keyBy(fn ($b) => Carbon::parse($b->month)->format('Y-m'));

                $months = $byMonth
                    ->map(function ($activities, $month) use ($billingByMonth) {
                        $entry = $billingByMonth->get($month);

                        return [
                            'month' => $month,
                            'days_worked' => round($activities->sum('day_coverage') / 100, 2),
                            'billing_entry_id' => $entry?->id,
                            'amount_billed' => $entry ? (float) $entry->amount_billed : 0.0,
                            'payment_date' => $entry?->payment_date?->format('Y-m-d'),
                            'notes' => $entry?->notes,
                        ];
                    })
                    ->sortKeys()
                    ->values();

                $projects->push([
                    'id' => $project->id,
                    'name' => $project->name,
                    'daily_rate' => $rate,
                    'max_budget' => $project->max_budget !== null ? (float) $project->max_budget : null,
                    'client_name' => $client->name,
                    'months' => $months,
                ]);
            }
        }

        return Inertia::render('TrackingPage', [
            'clients' => $clients->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'daily_rate' => (float) $c->daily_rate,
            ])->values(),
            'selectedClientId' => $selectedClientId,
            'projects' => $projects->values(),
            'billingStoreAction' => url('/dashboard/tracking/billing'),
            'billingBaseAction' => url('/dashboard/tracking/billing'),
            'projectBudgetBase' => url('/dashboard/tracking/projects'),
        ]);
    }

    public function storeBilling(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'month' => 'required|date_format:Y-m',
            'amount_billed' => 'required|numeric|min:0',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $project = Project::with('client')->findOrFail($validated['project_id']);
        if ($project->client->user_id !== Account::authenticated()->id) {
            throw new NotFoundHttpException;
        }

        $entry = BillingEntry::updateOrCreate(
            ['project_id' => $validated['project_id'], 'month' => $validated['month'].'-01'],
            [
                'amount_billed' => $validated['amount_billed'],
                'payment_date' => $validated['payment_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]
        );

        return response()->json([
            'id' => $entry->id,
            'amount_billed' => (float) $entry->amount_billed,
            'payment_date' => $entry->payment_date?->format('Y-m-d'),
            'notes' => $entry->notes,
        ]);
    }

    public function updateBilling(Request $request, BillingEntry $entry)
    {
        if ($entry->project->client->user_id !== Account::authenticated()->id) {
            throw new NotFoundHttpException;
        }

        $validated = $request->validate([
            'amount_billed' => 'required|numeric|min:0',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $entry->update($validated);

        return response()->json([
            'id' => $entry->id,
            'amount_billed' => (float) $entry->amount_billed,
            'payment_date' => $entry->payment_date?->format('Y-m-d'),
            'notes' => $entry->notes,
        ]);
    }

    public function updateProjectBudget(Request $request, Project $project)
    {
        if ($project->client->user_id !== Account::authenticated()->id) {
            throw new NotFoundHttpException;
        }

        $validated = $request->validate([
            'max_budget' => 'nullable|numeric|min:0',
        ]);

        $project->update(['max_budget' => $validated['max_budget']]);

        return response()->json([
            'max_budget' => $project->max_budget !== null ? (float) $project->max_budget : null,
        ]);
    }
}
