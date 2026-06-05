<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShareController
{
    public function storeClient(Request $request, Client $client): RedirectResponse
    {
        abort_unless($request->user()->can('update', $client), 403);

        if (! $client->share_token) {
            $client->update(['share_token' => (string) Str::uuid()]);
        }

        return back();
    }

    public function destroyClient(Request $request, Client $client): RedirectResponse
    {
        abort_unless($request->user()->can('update', $client), 403);

        $client->update(['share_token' => null]);

        return back();
    }
}
