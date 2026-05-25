<?php

namespace App\Http\Controllers;

use App\Http\Concerns\BuildsProjectsPageProps;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    use BuildsProjectsPageProps;

    public function edit(Request $request, Client $client): Response
    {
        $this->authorize('update', $client);

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

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->update($request->validated());

        return redirect()->route('projects.index')->with('success', 'Client successfully updated.');
    }

    public function restore(Client $client): RedirectResponse
    {
        $this->authorize('restore', $client);

        $client->restore();

        return redirect()->back()->with('success', 'Client restored successfully.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

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
