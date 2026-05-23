<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TimesheetEntry;
use App\Services\HolidayService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimesheetHandler
{
    public function __invoke(Request $request, HolidayService $holidays): Response
    {
        $date = $request->date('month', 'Y-m') ?? now()->startOfMonth();
        $projects = $request->user()->projects()
            ->with(['timesheetEntries' => fn (HasMany $query) => $query->whereMonth('date', $date)])
            ->get();

        return Inertia::render('TimesheetPage', [
            'current' => ['year' => $date->year, 'month' => $date->month],
            'urls' => [
                'nextMonth' => action(self::class, ['month' => $date->addMonth()->format('Y-m')], false),
                'prevMonth' => action(self::class, ['month' => $date->subMonth()->format('Y-m')], false),
            ],
            'holidays' => $holidays->forMonth($date),
            'projects' => $projects->map(fn (Project $p) => $p->export([
                'id',
                'name',
                'client' => ['name'],
                'daily_rate',
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
