<?php

namespace App\Http\Concerns;

use App\Models\Invoice;
use App\Models\Project;
use Carbon\CarbonImmutable;

trait BuildsProjectBillingEntry
{
    protected function buildProjectBillingEntry(Project $project, ?string $clientNameOverride = null): array
    {
        $invoicesByMonth = $project->invoices
            ->groupBy(fn (Invoice $i) => $i->created_at->format('Y-m'));

        $timesheetMonths = $project->timesheetEntries
            ->groupBy(fn ($e) => $e->date->format('Y-m'));

        $allMonths = $timesheetMonths->keys()
            ->merge($invoicesByMonth->keys())
            ->unique()
            ->sort()
            ->values();

        $months = $allMonths->map(function (string $month) use ($timesheetMonths, $invoicesByMonth) {
            $entries = $timesheetMonths->get($month, collect());

            return [
                'month' => $month,
                'days_worked' => round($entries->sum('coverage') / 100, 2),
                'entries' => $entries
                    ->keyBy(fn ($e) => $e->date->toDateString())
                    ->map(fn ($e) => [
                        'coverage' => $e->coverage,
                        'title' => $e->title ?? '',
                        'description' => $e->description ?? '',
                    ]),
                'invoices' => $invoicesByMonth->get($month, collect())->map(fn (Invoice $i) => [
                    'id' => $i->id,
                    'amount' => $i->amount,
                    'paid_at' => $i->paid_at?->toDateString(),
                    'created_at' => $i->created_at->toDateString(),
                    'notes' => $i->notes,
                ])->values(),
            ];
        });

        $now = CarbonImmutable::now();
        $firstEntry = $project->timesheetEntries->sortBy('date')->first();
        $projectStart = $firstEntry ? $firstEntry->date : $project->start_date;
        $monthsElapsed = max(1, $projectStart->startOfMonth()->diffInMonths($now->startOfMonth()) + 1);

        return [
            'id' => $project->id,
            'name' => $project->name,
            'daily_rate' => $project->daily_rate,
            'max_month_budget' => $project->max_month_budget,
            'max_total_budget' => $project->max_total_budget,
            'is_inactive' => $project->isInactive(),
            'client' => ['id' => $project->client_id, 'name' => $clientNameOverride ?? $project->client->name],
            'months' => $months->values(),
            'months_elapsed' => $monthsElapsed,
            'months_with_entries_count' => $timesheetMonths->count(),
        ];
    }
}
