<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class CommandBarController
{
    public function __invoke()
    {
        $user = Auth::user();

        return [
            'clients' => $user->clients()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
            'projects' => $user->projects()
                ->with('client:id,name')
                ->select(['projects.id', 'projects.name', 'projects.client_id'])
                ->orderBy('projects.name')
                ->get(),
        ];
    }
}
