<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TimesheetEntry;
use App\Services\HolidayService;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ShowHomeHandler
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if (! auth()->check()) {
            return Inertia::render('LandingPage');
        }

        $now = CarbonImmutable::now();

        $to = $request->query('to')
            ? CarbonImmutable::parse($request->query('to'))->startOfMonth()
            : $now->startOfMonth();

        $from = $request->query('from')
            ? CarbonImmutable::parse($request->query('from'))->startOfMonth()
            : $to->subMonths(11);

        $isCurrentOrFuturePeriod = $to->gte($now->startOfMonth());
        $windowEnd = $isCurrentOrFuturePeriod ? $now : $to->endOfMonth();

        $currentMonthStart = $to;
        $rollingYearStart = $from;
        $periodMonths = $from->diffInMonths($to) + 1;
        $prevYearStart = $from->subMonths($periodMonths);

        $userClientIds = $request->user()->clients()->withTrashed()->pluck('id');

        $minDate = TimesheetEntry::whereIn('project_id', Project::query()->whereIn('client_id', $userClientIds)->pluck('id'))->min('date');
        $firstEntryMonth = $minDate ? CarbonImmutable::parse($minDate)->format('Y-m') : null;

        $projects = Project::query()
            ->whereIn('client_id', $userClientIds)
            ->where(function ($q) use ($rollingYearStart, $windowEnd) {
                $q->whereNull('end_date')
                    ->orWhereHas('timesheetEntries', fn ($q) => $q->whereBetween('date', [$rollingYearStart, $windowEnd]));
            })
            ->with([
                'client' => fn ($q) => $q->withTrashed(),
                'timesheetEntries',
                'invoices',
            ])
            ->get();

        if ($projects->isEmpty()) {
            return redirect()->route('projects.index');
        }

        $allEntries = $projects->flatMap->timesheetEntries;
        $allInvoices = $projects->flatMap->invoices;

        $holidayService = app(HolidayService::class);
        $holidays = $holidayService->forMonth($to);
        $workingDaysInMonth = $this->countWorkingDays($currentMonthStart, $to->endOfMonth(), $holidays);
        $periodHolidays = $holidayService->forPeriod($rollingYearStart, $windowEnd);
        $periodWorkingDays = $this->countWorkingDays($rollingYearStart, $windowEnd, $periodHolidays);
        $workingDaysPassed = $isCurrentOrFuturePeriod
            ? $this->countWorkingDays($currentMonthStart, $now, $holidays)
            : $workingDaysInMonth;
        $monthAdvancement = $workingDaysInMonth > 0
            ? round($workingDaysPassed / $workingDaysInMonth, 4)
            : 0;

        $monthDays = round(
            $allEntries->filter(fn ($e) => $e->date->year === $to->year && $e->date->month === $to->month)
                ->sum('coverage') / 100,
            2
        );

        // monthRevenue/periodRevenue/projectedRevenue are derived from TimesheetEntry (worked time),
        // never from Invoice::amount — they are not affected by invoice discounts.
        $monthRevenue = $this->revenueForMonth($projects, $to);
        $periodRevenue = $this->revenueInRange($projects, $rollingYearStart, $windowEnd);
        $prevPeriodRevenue = $this->revenueInRange($projects, $prevYearStart, $rollingYearStart->subDay());

        // periodNetInvoiced mirrors the chart's Facturé line (net amount grouped by invoice issue
        // month), summed over the whole selected period, for the "Sur la période" KPI card.
        $periodInvoices = $allInvoices
            ->filter(fn ($i) => $i->created_at->gte($rollingYearStart) && $i->created_at->lte($windowEnd));
        $periodNetInvoiced = $periodInvoices->sum(fn ($i) => $i->netAmount());
        $periodGrossInvoiced = $periodInvoices->sum('amount');

        // periodPaid = net amount actually received within the period, grouped by payment date
        // (unlike periodNetInvoiced/chart.net, which group by invoice issue date).
        $periodPaid = $allInvoices
            ->filter(fn ($i) => $i->paid_at !== null && $i->paid_at->gte($rollingYearStart) && $i->paid_at->lte($windowEnd))
            ->sum(fn ($i) => $i->netAmount());

        $projectedRevenue = $monthAdvancement > 0
            ? (int) round($monthRevenue / $monthAdvancement)
            : 0;

        $trendPeriod = $prevPeriodRevenue > 0 ? (int) round(($periodRevenue - $prevPeriodRevenue) / $prevPeriodRevenue * 100) : 0;

        $outstanding = $allInvoices->whereNull('paid_at');
        $outstandingAmount = $outstanding->sum(fn ($i) => $i->netAmount());
        $outstandingDiscount = $outstanding->sum('discount_amount');

        $outstandingInvoices = $projects->flatMap(fn ($p) => $p->invoices
            ->filter(fn ($i) => $i->paid_at === null)
            ->map(fn ($i) => [
                'clientId' => $p->client_id,
                'clientName' => $p->client->name,
                'amount' => $i->amount,
                'discountAmount' => $i->discount_amount,
                'discountPercent' => $i->discountPercentForDisplay(),
                'daysWaiting' => (int) $i->created_at->diffInDays($now),
            ])
        )->sortByDesc('daysWaiting')->values()->all();

        $periodRevenueByClient = $projects
            ->groupBy(fn ($p) => $p->client->name)
            ->map(fn ($clientProjects, $clientName) => [
                'clientName' => $clientName,
                'revenue' => $this->revenueInRange($clientProjects, $rollingYearStart, $windowEnd),
            ])
            ->filter(fn ($item) => $item['revenue'] > 0)
            ->sortByDesc('revenue')
            ->values()
            ->all();

        $periodInvoicedByClient = $projects
            ->groupBy(fn ($p) => $p->client->name)
            ->map(fn ($clientProjects, $clientName) => [
                'clientName' => $clientName,
                'amount' => $clientProjects->flatMap->invoices
                    ->filter(fn ($i) => $i->created_at->gte($rollingYearStart) && $i->created_at->lte($windowEnd))
                    ->sum(fn ($i) => $i->netAmount()),
            ])
            ->filter(fn ($item) => $item['amount'] > 0)
            ->sortByDesc('amount')
            ->values()
            ->all();

        $periodPaidByClient = $projects
            ->groupBy(fn ($p) => $p->client->name)
            ->map(fn ($clientProjects, $clientName) => [
                'clientName' => $clientName,
                'amount' => $clientProjects->flatMap->invoices
                    ->filter(fn ($i) => $i->paid_at !== null && $i->paid_at->gte($rollingYearStart) && $i->paid_at->lte($windowEnd))
                    ->sum(fn ($i) => $i->netAmount()),
            ])
            ->filter(fn ($item) => $item['amount'] > 0)
            ->sortByDesc('amount')
            ->values()
            ->all();

        $months = collect(range($periodMonths - 1, 0))->map(fn ($i) => $to->subMonths($i)->startOfMonth());
        $chartLabels = $months->map(fn ($m) => $m->format('M'))->all();

        $chartProjects = $projects->map(fn ($p) => [
            'name' => $p->name,
            'data' => $months->map(fn ($m) => $p->timesheetEntries
                ->filter(fn ($e) => $e->date->year === $m->year && $e->date->month === $m->month)
                ->sum('coverage')
            )->values()->all(),
        ])->values()->all();

        // Facturé = net amount (after discount) grouped by invoice issue month.
        $chartNet = $months->map(fn ($m) => $allInvoices
            ->filter(fn ($i) => $i->created_at->year === $m->year && $i->created_at->month === $m->month)
            ->sum(fn ($i) => $i->netAmount())
        )->values()->all();

        $chartWorkingDays = $months->map(fn ($m) => $this->countWorkingDays(
            $m,
            $m->endOfMonth(),
            $holidayService->forMonth($m),
        ))->values()->all();

        $projectsData = $projects
            ->filter(fn ($p) => $p->timesheetEntries
                ->contains(fn ($e) => $e->date->gte($rollingYearStart) && $e->date->lte($windowEnd))
            )
            ->map(function ($p) use ($to, $currentMonthStart, $rollingYearStart, $windowEnd) {
                $workedDaysCount = round($p->timesheetEntries->sum('coverage') / 100, 2);
                $periodDaysCount = round(
                    $p->timesheetEntries
                        ->filter(fn ($e) => $e->date->gte($rollingYearStart) && $e->date->lte($windowEnd))
                        ->sum('coverage') / 100,
                    2
                );
                $monthDaysCount = round(
                    $p->timesheetEntries
                        ->filter(fn ($e) => $e->date->year === $to->year && $e->date->month === $to->month)
                        ->sum('coverage') / 100,
                    2
                );
                $totalWorkedAmount = $p->timesheetEntries
                    ->sum(fn ($e) => (int) round($e->coverage / 100 * $p->daily_rate));
                $firstEntry = $p->timesheetEntries->sortBy('date')->first();
                $projectStart = $firstEntry ? $firstEntry->date : $p->start_date;
                $monthsElapsed = max(1, $projectStart->startOfMonth()->diffInMonths($currentMonthStart) + 1);
                $theoreticalBudget = match (true) {
                    $p->max_total_budget !== null => $p->max_total_budget,
                    $p->max_month_budget !== null => $p->max_month_budget * $monthsElapsed,
                    default => 0,
                };

                return [
                    'id' => $p->id,
                    'clientId' => $p->client_id,
                    'name' => $p->name,
                    'clientName' => $p->client->name,
                    'dailyRate' => $p->daily_rate,
                    'maxMonthBudget' => $p->max_month_budget,
                    'maxTotalBudget' => $p->max_total_budget,
                    'theoreticalBudget' => $theoreticalBudget,
                    'workedDaysCount' => $workedDaysCount,
                    'periodDaysCount' => $periodDaysCount,
                    'monthDaysCount' => $monthDaysCount,
                    'isInactive' => $p->isInactive(),
                    'lastActivity' => $p->timesheetEntries->sortByDesc('date')->first()?->date->toDateString(),
                    'unbilled' => max(0, $totalWorkedAmount - $p->invoices->sum('amount')),
                ];
            })->values()->all();

        // Worked but not yet invoiced, across all projects — gross, mirroring to_invoice's
        // deliberate gross-amount design (see projects[].unbilled, same underlying figure).
        $unbilledAmount = collect($projectsData)->sum('unbilled');

        $unbilledByClient = collect($projectsData)
            ->groupBy('clientName')
            ->map(function ($clientProjects, $clientName) use ($projects, $now) {
                $clientId = $clientProjects->first()['clientId'];
                $lastInvoiceDate = $projects
                    ->where('client_id', $clientId)
                    ->flatMap->invoices
                    ->sortByDesc('created_at')
                    ->first()?->created_at;

                return [
                    'clientId' => $clientId,
                    'clientName' => $clientName,
                    'amount' => $clientProjects->sum('unbilled'),
                    'daysSinceLastInvoice' => $lastInvoiceDate ? (int) $lastInvoiceDate->diffInDays($now) : null,
                ];
            })
            ->filter(fn ($item) => $item['amount'] > 0)
            ->sortByDesc('amount')
            ->values()
            ->all();

        return Inertia::render('DashboardPage', [
            'from' => $from->format('Y-m'),
            'to' => $to->format('Y-m'),
            'firstEntryMonth' => $firstEntryMonth,
            'kpis' => [
                'monthDays' => $monthDays,
                'monthRevenue' => $monthRevenue,
                'projectedRevenue' => $projectedRevenue,
                'periodRevenue' => $periodRevenue,
                'periodNetInvoiced' => $periodNetInvoiced,
                'periodGrossInvoiced' => $periodGrossInvoiced,
                'periodPaid' => $periodPaid,
                'outstandingAmount' => $outstandingAmount,
                'outstandingDiscount' => $outstandingDiscount,
                'unbilledAmount' => $unbilledAmount,
                'periodWorkingDays' => $periodWorkingDays,
                'trendPeriod' => $trendPeriod,
                'prevPeriodRevenue' => $prevPeriodRevenue,
            ],
            'chart' => [
                'labels' => $chartLabels,
                'projects' => $chartProjects,
                'net' => $chartNet,
                'workingDays' => $chartWorkingDays,
            ],
            'projects' => $projectsData,
            'monthAdvancement' => $monthAdvancement,
            'outstandingInvoices' => $outstandingInvoices,
            'unbilledByClient' => $unbilledByClient,
            'periodRevenueByClient' => $periodRevenueByClient,
            'periodInvoicedByClient' => $periodInvoicedByClient,
            'periodPaidByClient' => $periodPaidByClient,
        ]);
    }

    private function countWorkingDays(CarbonImmutable $from, CarbonImmutable $to, array $holidays): int
    {
        return collect(CarbonPeriod::create($from, $to))
            ->filter(fn ($day) => ! $day->isWeekend() && ! array_key_exists($day->toDateString(), $holidays))
            ->count();
    }

    private function revenueForMonth(Collection $projects, CarbonImmutable $month): int
    {
        return $projects->sum(fn ($p) => $p->timesheetEntries
            ->filter(fn ($e) => $e->date->year === $month->year && $e->date->month === $month->month)
            ->sum(fn ($e) => (int) round($e->coverage / 100 * $p->daily_rate))
        );
    }

    private function revenueInRange(Collection $projects, CarbonImmutable $from, CarbonImmutable $to): int
    {
        return $projects->sum(fn ($p) => $p->timesheetEntries
            ->filter(fn ($e) => $e->date->gte($from) && $e->date->lte($to))
            ->sum(fn ($e) => (int) round($e->coverage / 100 * $p->daily_rate))
        );
    }
}
