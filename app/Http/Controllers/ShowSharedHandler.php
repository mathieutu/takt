<?php

namespace App\Http\Controllers;

use App\Http\Concerns\BuildsProjectBillingEntry;
use App\Models\Client;
use App\Models\Project;
use App\Services\HolidayService;
use Inertia\Inertia;
use Inertia\Response;

class ShowSharedHandler
{
    use BuildsProjectBillingEntry;

    public function __invoke(string $token, HolidayService $holidays): Response
    {
        $client = Client::findByShareTokenOrFail($token);

        $projects = $client->projects()->orderedByEndDateThenName()->get();

        abort_if($projects->isEmpty(), 404);

        $projects->load(['timesheetEntries', 'invoices']);

        $builtProjects = $projects->map(fn (Project $p) => $this->buildProjectBillingEntry($p, $client->name))->values();

        return Inertia::render('ProjectBillingPage', [
            'shared_by' => $client->user->name,
            'is_shared' => true,
            'projects' => $builtProjects,
            'holidays' => $this->buildHolidaysForPeriod($holidays, $builtProjects),
            'token' => $token,
        ]);
    }
}
