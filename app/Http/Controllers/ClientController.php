<?php

namespace App\Http\Controllers;

use App\Http\Concerns\BuildsProjectsPageProps;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController
{
    use BuildsProjectsPageProps;

    public function edit(Request $request, Client $client): Response
    {
        return Inertia::render('ClientForm', [
            'page' => $this->projectsPageProps($request),
            'modal' => [
                'client' => $client->export([
                    'id',
                    'name',
                    'daily_rate',
                ]),
            ],
        ]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'daily_rate' => ['required', 'integer', 'min:0'],
        ]);

        $client->update($data);

        return redirect()->route('projects.index')->with('success', 'Client successfully updated.');
    }

    public function restore(Client $client): RedirectResponse
    {
        $client->restore();

        return redirect()->back()->with('success', 'Client restored successfully.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        if (! $client->deleted_at) {
            $client->projects()->delete();
            $client->delete();

            return redirect()->back()->with('success', 'Client and its projects successfully archived.');
        }

        if ($client->projects()->withTrashed()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete a client with existing projects.');
        }

        $client->forceDelete();

        return redirect()->back()->with('success', 'Client successfully deleted.');
    }
}
