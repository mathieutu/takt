<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimesheetEntry;
use App\Services\HolidayService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowTimesheetHandler
{
    public function __invoke(Request $request, HolidayService $holidays): Response
    {
        $date = $request->date('month', 'Y-m') ?? now()->startOfMonth();
        $projects = $request->user()->projects()
            ->where('projects.start_date', '<=', $date->endOfMonth())
            ->where(fn ($q) => $q->whereNull('projects.end_date')->orWhere('projects.end_date', '>=', $date->startOfMonth()))
            ->with(['timesheetEntries' => fn (HasMany $query) => $query->whereBetween('date', [$date->startOfMonth(), $date->endOfMonth()])])
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
            'urls' => [
                'nextMonth' => action(self::class, ['month' => $date->addMonth()->format('Y-m')], false),
                'prevMonth' => action(self::class, ['month' => $date->subMonth()->format('Y-m')], false),
            ],
            'holidays' => $holidays->forMonth($date),
            'invoices' => $monthInvoices->map(fn (Invoice $i) => [
                'id' => $i->id,
                'amount' => $i->amount,
                'created_at' => $i->created_at->toDateString(),
                'paid_at' => $i->paid_at?->toDateString(),
                'notes' => $i->notes,
                'project_name' => $i->project->name,
                'client_name' => $i->project->client->name,
                'client_id' => $i->project->client_id,
                'daily_rate' => $i->project->daily_rate,
            ])->values()->all(),
            'projects' => $projects->map(fn (Project $p) => $p->export([
                'id',
                'name',
                'client' => ['id', 'name'],
                'daily_rate',
                'isArchived() as is_archived',
            ])->merge([
                'entries' => $p->timesheetEntries
                    ->keyBy(fn (TimesheetEntry $e) => $e->date->toDateString())
                    ->map(fn (TimesheetEntry $e) => $e->export([
                        'coverage',
                        'title',
                        'description',
                    ])),
            ])),
        ]);
    }
}
