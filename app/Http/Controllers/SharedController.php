<?php

namespace App\Http\Controllers;

use App\Http\Concerns\BuildsProjectBillingEntry;
use App\Models\Client;
use App\Models\Project;
use App\Models\Share;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SharedController
{
    use BuildsProjectBillingEntry;

    public function __invoke(Share $share, Request $request): Response
    {
        [$sharedBy, $projectData] = match ($share->shareable_type) {
            'projects' => $this->projectData($share, $request),
            'clients' => $this->clientData($share, $request),
            default => abort(404),
        };

        return Inertia::render('ProjectBillingPage', [
            'shared_by' => $sharedBy,
            'is_shared' => true,
            ...$projectData,
        ]);
    }

    /** @return array{string, array} */
    private function projectData(Share $share, Request $request): array
    {
        /** @var Project $project */
        $project = Project::with(['client.user', 'timesheetEntries', 'invoices'])->findOrFail($share->shareable_id);

        $this->recordReceivedShare($share, $project->client->user_id, $request);

        return [
            $project->client->user->name,
            ['projects' => [$this->buildProjectBillingEntry($project)]],
        ];
    }

    /** @return array{string, array} */
    private function clientData(Share $share, Request $request): array
    {
        /** @var Client $client */
        $client = Client::with('user')->findOrFail($share->shareable_id);

        $this->recordReceivedShare($share, $client->user_id, $request);

        $projects = $client->projects()->orderBy('created_at')->get();

        abort_if($projects->isEmpty(), 404);

        $projects->load(['timesheetEntries', 'invoices']);

        return [
            $client->user->name,
            ['projects' => $projects->map(fn (Project $p) => $this->buildProjectBillingEntry($p, $client->name))->values()],
        ];
    }

    private function recordReceivedShare(Share $share, string $ownerId, Request $request): void
    {
        $user = $request->user();

        if ($user && $user->id !== $ownerId) {
            $user->receivedShares()->firstOrCreate(['share_id' => $share->id]);
        }
    }
}
