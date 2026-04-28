<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use App\Models\Share;
use App\Models\SharedClient;
use App\Models\SharedProject;
use Auth;

class ShareController
{
    public function apply(Share $share)
    {
        $account_id = Auth::id();
        $shared = match ($share->sharing()->getMorphType()) {
            Project::sharer()->getMorphType() => SharedProject::firstOrCreate([
                'account_id' => $account_id,
                'project_id' => $share->share_id
            ]),
            Client::sharer()->getMorphType() => SharedClient::firstOrCreate([
                'account_id' => $account_id,
                'client_id' => $share->share_id
            ]),
        };

        return $shared;
    }
}
