<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ShareController
{
    public function storeClient(Client $client): RedirectResponse
    {
        if (! $client->share_token) {
            $client->update(['share_token' => (string) Str::uuid()]);
        }

        return back();
    }

    public function destroyClient(Client $client): RedirectResponse
    {
        $client->update(['share_token' => null]);

        return back();
    }
}
