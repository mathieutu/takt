<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Client;
use Auth;
use Illuminate\Http\Request;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ClientController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clients = Client::where('user_id', Account::authenticated()->id)->get();

        return view('dashboard.singletons.clients', [
            'clients' => $clients,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $user = Account::authenticated();

        $client = $request->validate([
            'name' => 'required|max:255',
            'daily_rate' => 'required|between:0,100'
        ]);

        Client::create([
            ...$client,
            'user_id' => $user->id
        ]);

        return to_route('dashboard.clients');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'daily_rate' => 'required'
        ]);

        if ($client->user_id !== Account::authenticated()->id) {
            throw new AccessDeniedException();
        }

        $client->update($validated);

        return redirect()->route('dashboard.clients');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        if ($client->user_id !== Account::authenticated()->id) {
            throw new NotFoundHttpException();
        }
        $client->delete();

        return to_route('dashboard.clients');
    }
}
