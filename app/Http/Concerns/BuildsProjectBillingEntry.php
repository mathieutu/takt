<?php

namespace App\Http\Concerns;

use App\Models\Invoice;
use App\Models\Project;
use Carbon\CarbonPeriod;

trait BuildsProjectBillingEntry
{
    protected function buildProjectBillingEntry(Project $project, ?string $clientNameOverride = null): array
    {
        $invoicesByMonth = $project->invoices
            ->whereNotNull('paid_at')
            ->groupBy(fn (Invoice $i) => $i->paid_at->format('Y-m'));

        $timesheetMonths = $project->timesheetEntries
            ->groupBy(fn ($e) => $e->date->format('Y-m'));

        $period = CarbonPeriod::create(
            $project->created_at->copy()->startOfMonth(),
            '1 month',
            now()->startOfMonth()
        );

        $allMonths = collect($period)
            ->map(fn ($d) => $d->format('Y-m'))
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
                    'paid_at' => $i->paid_at->toDateString(),
                    'notes' => $i->notes,
                ])->values(),
            ];
        });

        $outstanding = $project->invoices
            ->whereNull('paid_at')
            ->map(fn (Invoice $i) => [
                'id' => $i->id,
                'amount' => $i->amount,
                'paid_at' => null,
                'notes' => $i->notes,
            ])->values();

        return [
            'id' => $project->id,
            'name' => $project->name,
            'daily_rate' => $project->daily_rate,
            'max_month_budget' => $project->max_month_budget,
            'client' => ['name' => $clientNameOverride ?? $project->client->name],
            'months' => $months->values(),
            'outstanding' => $outstanding,
        ];
    }
}
