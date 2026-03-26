<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = filament()->auth()->user();

        $client = Client::create([
            'name'    => $request->name,
            'user_id' => $user->id,
        ]);

        $color = '#' . substr(md5($client->name . $client->id), 0, 6);

        return response()->json([
            'id'          => (string) $client->id,
            'name'        => $client->name,
            'color'       => $color,
            'contactName' => '',
            'email'       => '',
            'active'      => true,
            'createdAt'   => now()->toDateString(),
        ]);
    }
}
