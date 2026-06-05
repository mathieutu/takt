<?php

namespace App\Http\Controllers;

use App\Services\HolidayService;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $projects = $request->user()->projects()
            ->with(['client', 'timesheetEntries', 'invoices'])
            ->get();

        if ($projects->isEmpty()) {
            return redirect()->route('projects.index');
        }

        $now = CarbonImmutable::now();
        $currentMonthStart = $now->startOfMonth();
        $rollingYearStart = $now->subMonths(12)->startOfMonth();
        $prevYearStart = $now->subMonths(24)->startOfMonth();
        $prevMonthStart = $currentMonthStart->subMonth();

        $allEntries = $projects->flatMap->timesheetEntries;
        $allInvoices = $projects->flatMap->invoices;

        $holidays = app(HolidayService::class)->forMonth($now);
        $workingDaysInMonth = $this->countWorkingDays($currentMonthStart, $now->endOfMonth(), $holidays);
        $workingDaysPassed = $this->countWorkingDays($currentMonthStart, $now, $holidays);
        $monthAdvancement = $workingDaysInMonth > 0
            ? round($workingDaysPassed / $workingDaysInMonth, 4)
            : 0;

        $monthDays = round(
            $allEntries->filter(fn ($e) => $e->date->year === $now->year && $e->date->month === $now->month)
                ->sum('coverage') / 100,
            2
        );
        $prevMonthDays = round(
            $allEntries->filter(fn ($e) => $e->date->year === $prevMonthStart->year && $e->date->month === $prevMonthStart->month)
                ->sum('coverage') / 100,
            2
        );

        $monthRevenue = $this->revenueForMonth($projects, $now);
        $prevMonthRevenue = $this->revenueForMonth($projects, $prevMonthStart);
        $yearRevenue = $this->revenueInRange($projects, $rollingYearStart, $now);
        $prevYearRevenue = $this->revenueInRange($projects, $prevYearStart, $rollingYearStart->subDay());

        $projectedRevenue = $monthAdvancement > 0
            ? (int) round($monthRevenue / $monthAdvancement)
            : 0;

        $trendDays = $prevMonthDays > 0 ? (int) round(($monthDays - $prevMonthDays) / $prevMonthDays * 100) : 0;
        $trendRevenue = $prevMonthRevenue > 0 ? (int) round(($monthRevenue - $prevMonthRevenue) / $prevMonthRevenue * 100) : 0;
        $trendYear = $prevYearRevenue > 0 ? (int) round(($yearRevenue - $prevYearRevenue) / $prevYearRevenue * 100) : 0;

        $outstanding = $allInvoices->whereNull('paid_at');
        $outstandingAmount = $outstanding->sum('amount');
        $outstandingCount = $outstanding->count();
        $overdueCount = $outstanding->filter(fn ($i) => $i->created_at->lt($now->subDays(30)))->count();

        [$totalDays12m, $weightedSum] = $projects->reduce(function ($carry, $p) use ($rollingYearStart) {
            $days = $p->timesheetEntries
                ->filter(fn ($e) => $e->date->gte($rollingYearStart))
                ->sum('coverage') / 100;

            return [$carry[0] + $days, $carry[1] + $days * $p->daily_rate];
        }, [0, 0]);
        $weightedRate = $totalDays12m > 0 ? (int) round($weightedSum / $totalDays12m) : 0;

        $months = collect(range(11, 0))->map(fn ($i) => $now->subMonths($i)->startOfMonth());
        $chartLabels = $months->map(fn ($m) => $m->format('M'))->all();

        $chartProjects = $projects->map(fn ($p) => [
            'name' => $p->name,
            'data' => $months->map(fn ($m) => $p->timesheetEntries
                ->filter(fn ($e) => $e->date->year === $m->year && $e->date->month === $m->month)
                ->sum(fn ($e) => (int) round($e->coverage / 100 * $p->daily_rate))
            )->values()->all(),
        ])->values()->all();

        $chartBilled = $months->map(fn ($m) => $allInvoices
            ->filter(fn ($i) => $i->created_at->year === $m->year && $i->created_at->month === $m->month)
            ->sum('amount')
        )->values()->all();

        $projectsData = $projects->map(function ($p) use ($now, $currentMonthStart) {
            $cumulativeWorked = $p->timesheetEntries
                ->sum(fn ($e) => (int) round($e->coverage / 100 * $p->daily_rate));
            $thisMonthWorked = $p->timesheetEntries
                ->filter(fn ($e) => $e->date->year === $now->year && $e->date->month === $now->month)
                ->sum(fn ($e) => (int) round($e->coverage / 100 * $p->daily_rate));
            $monthsElapsed = max(1, $currentMonthStart->diffInMonths($p->created_at->startOfMonth()) + 1);
            $theoreticalBudget = $p->max_month_budget !== null ? $p->max_month_budget * $monthsElapsed : 0;

            return [
                'id' => $p->id,
                'clientId' => $p->client_id,
                'name' => $p->name,
                'clientName' => $p->client->name,
                'dailyRate' => $p->daily_rate,
                'maxMonthBudget' => $p->max_month_budget,
                'theoreticalBudget' => $theoreticalBudget,
                'cumulativeWorked' => $cumulativeWorked,
                'thisMonthWorked' => $thisMonthWorked,
                'lastActivity' => $p->timesheetEntries->sortByDesc('date')->first()?->date->toDateString(),
                'unbilled' => max(0, $cumulativeWorked - $p->invoices->sum('amount')),
            ];
        })->values()->all();

        return Inertia::render('DashboardPage', [
            'kpis' => [
                'monthDays' => $monthDays,
                'workingDays' => $workingDaysInMonth,
                'fillRate' => $workingDaysInMonth > 0 ? (int) round($monthDays / $workingDaysInMonth * 100) : 0,
                'monthRevenue' => $monthRevenue,
                'projectedRevenue' => $projectedRevenue,
                'yearRevenue' => $yearRevenue,
                'outstandingAmount' => $outstandingAmount,
                'outstandingCount' => $outstandingCount,
                'overdueCount' => $overdueCount,
                'weightedRate' => $weightedRate,
                'trendDays' => $trendDays,
                'trendRevenue' => $trendRevenue,
                'trendYear' => $trendYear,
            ],
            'chart' => [
                'labels' => $chartLabels,
                'projects' => $chartProjects,
                'billed' => $chartBilled,
            ],
            'projects' => $projectsData,
            'monthAdvancement' => $monthAdvancement,
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
