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
        $prevMonthStart = $to->subMonth();

        $userClientIds = $request->user()->clients()->withTrashed()->pluck('id');

        $minDate = TimesheetEntry::whereIn('project_id', Project::withTrashed()->whereIn('client_id', $userClientIds)->pluck('id'))->min('date');
        $firstEntryMonth = $minDate ? CarbonImmutable::parse($minDate)->format('Y-m') : null;

        $projects = Project::withTrashed()
            ->whereIn('client_id', $userClientIds)
            ->where(function ($q) use ($rollingYearStart, $windowEnd) {
                $q->whereNull('deleted_at')
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
        $prevMonthDays = round(
            $allEntries->filter(fn ($e) => $e->date->year === $prevMonthStart->year && $e->date->month === $prevMonthStart->month)
                ->sum('coverage') / 100,
            2
        );

        $monthRevenue = $this->revenueForMonth($projects, $to);
        $prevMonthRevenue = $this->revenueForMonth($projects, $prevMonthStart);
        $periodRevenue = $this->revenueInRange($projects, $rollingYearStart, $windowEnd);
        $prevPeriodRevenue = $this->revenueInRange($projects, $prevYearStart, $rollingYearStart->subDay());

        $projectedRevenue = $monthAdvancement > 0
            ? (int) round($monthRevenue / $monthAdvancement)
            : 0;

        $trendDays = $prevMonthDays > 0 ? (int) round(($monthDays - $prevMonthDays) / $prevMonthDays * 100) : 0;
        $trendRevenue = $prevMonthRevenue > 0 ? (int) round(($projectedRevenue - $prevMonthRevenue) / $prevMonthRevenue * 100) : 0;
        $trendPeriod = $prevPeriodRevenue > 0 ? (int) round(($periodRevenue - $prevPeriodRevenue) / $prevPeriodRevenue * 100) : 0;

        $outstanding = $allInvoices->whereNull('paid_at');
        $outstandingAmount = $outstanding->sum('amount');
        $outstandingCount = $outstanding->count();
        $overdueCount = $outstanding->filter(fn ($i) => $i->created_at->lt($now->subDays(30)))->count();

        $outstandingInvoices = $projects->flatMap(fn ($p) => $p->invoices
            ->filter(fn ($i) => $i->paid_at === null)
            ->map(fn ($i) => [
                'clientName' => $p->client->name,
                'amount' => $i->amount,
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

        $months = collect(range($periodMonths - 1, 0))->map(fn ($i) => $to->subMonths($i)->startOfMonth());
        $chartLabels = $months->map(fn ($m) => $m->format('M'))->all();

        $chartProjects = $projects->map(fn ($p) => [
            'name' => $p->name,
            'data' => $months->map(fn ($m) => $p->timesheetEntries
                ->filter(fn ($e) => $e->date->year === $m->year && $e->date->month === $m->month)
                ->sum('coverage')
            )->values()->all(),
        ])->values()->all();

        $chartBilled = $months->map(fn ($m) => $allInvoices
            ->filter(fn ($i) => $i->created_at->year === $m->year && $i->created_at->month === $m->month)
            ->sum('amount')
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
                $projectStart = $firstEntry ? $firstEntry->date : $p->created_at;
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
                    'deletedAt' => $p->deleted_at?->toDateTimeString(),
                    'lastActivity' => $p->timesheetEntries->sortByDesc('date')->first()?->date->toDateString(),
                    'unbilled' => max(0, $totalWorkedAmount - $p->invoices->sum('amount')),
                ];
            })->values()->all();

        return Inertia::render('DashboardPage', [
            'from' => $from->format('Y-m'),
            'to' => $to->format('Y-m'),
            'firstEntryMonth' => $firstEntryMonth,
            'kpis' => [
                'monthDays' => $monthDays,
                'workingDays' => $workingDaysInMonth,
                'fillRate' => $workingDaysInMonth > 0 ? (int) round($monthDays / $workingDaysInMonth * 100) : 0,
                'monthRevenue' => $monthRevenue,
                'projectedRevenue' => $projectedRevenue,
                'periodRevenue' => $periodRevenue,
                'outstandingAmount' => $outstandingAmount,
                'outstandingCount' => $outstandingCount,
                'overdueCount' => $overdueCount,
                'periodWorkingDays' => $periodWorkingDays,
                'trendDays' => $trendDays,
                'trendRevenue' => $trendRevenue,
                'trendPeriod' => $trendPeriod,
                'prevPeriodRevenue' => $prevPeriodRevenue,
                'prevMonthRevenue' => $prevMonthRevenue,
            ],
            'chart' => [
                'labels' => $chartLabels,
                'projects' => $chartProjects,
                'billed' => $chartBilled,
                'workingDays' => $chartWorkingDays,
            ],
            'projects' => $projectsData,
            'monthAdvancement' => $monthAdvancement,
            'outstandingInvoices' => $outstandingInvoices,
            'periodRevenueByClient' => $periodRevenueByClient,
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
