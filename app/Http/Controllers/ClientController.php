<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Client;
use Auth;
use Illuminate\Http\Request;

class ClientController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clients = Client::all();

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

        return redirect()->route('dashboard.clients');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'daily_rate' => 'required'
        ]);

        $client = Client::findOrFail($id);

        if ($client->user_id !== Account::authenticated()->id) {
            throw new BadRequestException();
        }

        $client->update($validated);

        return redirect()->route('dashboard.clients');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $client = Client::find($id);

        $client->delete();

        return redirect()->route('dashboard.clients');    }
}
