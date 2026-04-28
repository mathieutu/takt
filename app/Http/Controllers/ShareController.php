<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Share;
use App\Models\SharedClient;
use App\Models\SharedProject;
use Auth;
use Illuminate\Http\JsonResponse;

class ShareController
{
    public function generate(Project $project): JsonResponse
    {
        $share = $project->share();

        return response()->json(['url' => $share->url()]);
    }

    public function generateForClient(Client $client): JsonResponse
    {
        $share = $client->share();

        return response()->json(['url' => $share->url()]);
    }

    public function apply(Share $share)
    {
        $account_id = Auth::id();

        $shared = match ($share->share_type) {
            'projects' => SharedProject::firstOrCreate([
                'account_id' => $account_id,
                'project_id' => $share->share_id
            ]),
            'clients' => SharedClient::firstOrCreate([
                'account_id' => $account_id,
                'client_id' => $share->share_id
            ]),
        };

        return to_route('dashboard.projects');
    }
}
