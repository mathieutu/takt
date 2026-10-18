<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimesheetEntry;
use App\Services\HolidayService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ShowTimesheetHandler
{
    public function __invoke(Request $request, HolidayService $holidays): Response
    {
        $date = $request->date('month', 'Y-m') ?? now()->startOfMonth();
        $previousMonthStart = $date->clone()->subMonth()->startOfMonth();
        $previousMonthEnd = $date->clone()->subMonth()->endOfMonth();

        $projects = $request->user()->projects()
            ->where('projects.start_date', '<=', $date->endOfMonth())
            ->where(fn ($q) => $q->whereNull('projects.end_date')->orWhere('projects.end_date', '>=', $date->startOfMonth()))
            ->withExists(['timesheetEntries as has_previous_month_entries' => fn ($q) => $q->whereBetween('date', [$previousMonthStart->toDateString(), $previousMonthEnd->toDateString()])])
            ->with(['timesheetEntries' => fn (HasMany $query) => $query->whereBetween('date', [$date->startOfMonth()->toDateString(), $date->endOfMonth()->toDateString()])])
            ->orderBy('projects.name')
            ->get();

        $monthInvoices = Invoice::whereRelation('user', 'users.id', $request->user()->id)
            ->where(fn (Builder $q) => $q
                ->whereBetween('created_at', [$date->startOfMonth(), $date->endOfMonth()])
                ->orWhereBetween('paid_at', [$date->startOfMonth(), $date->endOfMonth()])
            )
            ->with(['project' => fn (BelongsTo $q) => $q->with(['client' => fn (BelongsTo $q) => $q->withTrashed()])])
            ->get();

        return Inertia::render('TimesheetPage', [
            'current' => ['year' => $date->year, 'month' => $date->month],
            'day' => $request->query('day'),
            'urls' => [
                'nextMonth' => action(self::class, ['month' => $date->addMonth()->format('Y-m')], false),
                'prevMonth' => action(self::class, ['month' => $date->subMonth()->format('Y-m')], false),
            ],
            'holidays' => $holidays->forMonth($date),
            'duplicate_billing_days' => $this->duplicateBillingDays($projects),
            'invoices' => $monthInvoices->map(fn (Invoice $i) => [
                'id' => $i->id,
                'amount' => $i->amount,
                'created_at' => $i->created_at->toDateString(),
                'paid_at' => $i->paid_at?->toDateString(),
                'notes' => $i->notes,
                'project_name' => $i->project->name,
                'client_name' => $i->project->client->name,
                'client_id' => $i->project->client_id,
                'daily_rate' => $i->project->getDailyRateForDate($i->created_at),
            ])->values()->all(),
            'projects' => $projects->map(fn (Project $p) => $p->export([
                'id',
                'name',
                'client' => ['id', 'name'],
                'isInactive() as is_inactive',
            ])->merge([
                'daily_rate' => $p->getDailyRateForDate($date),
                'start_date' => $p->start_date->toDateString(),
                'end_date' => $p->end_date?->toDateString(),
                'has_previous_month_entries' => (bool) $p->has_previous_month_entries,
                'entries' => $p->timesheetEntries
                    ->keyBy(fn (TimesheetEntry $e) => $e->date->toDateString())
                    ->map(fn (TimesheetEntry $e) => $e->export([
                        'coverage',
                        'billable',
                        'title',
                        'description',
                    ])),
            ])),
        ]);
    }

    /**
     * Days of the displayed month where more than one day is billed to the same client across its
     * projects, computed from the already loaded entries (see Client::overbookedDays()).
     *
     * @param  Collection<int, Project>  $projects  With the month's timesheetEntries loaded
     * @return Collection<int, array<string, mixed>>
     */
    private function duplicateBillingDays(Collection $projects): Collection
    {
        return $projects
            ->flatMap(fn (Project $p) => $p->timesheetEntries
                ->filter(fn (TimesheetEntry $e) => $e->billable && $e->coverage > 0)
                ->map(fn (TimesheetEntry $e) => [
                    'date' => $e->date->toDateString(),
                    'client_id' => $p->client_id,
                    'client_name' => $p->client?->name,
                    'project_id' => $p->id,
                    'project' => $p->name,
                    'coverage' => $e->coverage,
                ]))
            ->groupBy('client_id')
            ->flatMap(fn (Collection $rows) => Client::overbookedDays($rows)->map(fn (array $day) => [
                ...$day,
                'client_id' => $rows->first()['client_id'],
                'client_name' => $rows->first()['client_name'],
            ]))
            ->sortBy('date')
            ->values();
    }
}
