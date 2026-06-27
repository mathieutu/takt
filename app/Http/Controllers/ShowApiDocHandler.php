<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowApiDocHandler
{
    public function __invoke(Request $request): Response
    {
        $projects = $request->user()->projects()->get();

        return Inertia::render('ApiDocPage', [
            'base_url' => url('/'),
            'api_token' => $request->user()->api_token,
            'projects' => $projects->map->export(['id', 'name', 'client.name as client_name']),
        ]);
    }
}
