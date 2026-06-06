<?php

namespace App\Http\Controllers;

use App\Http\Concerns\BuildsProjectBillingEntry;
use App\Models\Client;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class ShowSharedHandler
{
    use BuildsProjectBillingEntry;

    public function __invoke(string $token): Response
    {
        $client = Client::with('user')->where('share_token', $token)->firstOrFail();

        $projects = $client->projects()->withTrashed()->orderBy('created_at')->get();

        abort_if($projects->isEmpty(), 404);

        $projects->load(['timesheetEntries', 'invoices']);

        return Inertia::render('ProjectBillingPage', [
            'shared_by' => $client->user->name,
            'is_shared' => true,
            'projects' => $projects->map(fn (Project $p) => $this->buildProjectBillingEntry($p, $client->name))->values(),
        ]);
    }
}
