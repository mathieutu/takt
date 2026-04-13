<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\Account;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class SettingsController
{
    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        return view('dashboard.singletons.settings', [
            'account' => Account::authenticated()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $auth = Account::authenticated();

        $extra_entries = match ($auth->type) {
            AccountType::User => [
                'user' => [
                    'first_name' => 'required',
                    'last_name' => 'required'
                ]
            ],
            AccountType::Organization => [
                'organization' => [
                    'name' => 'required'
                ]
            ],

            default => []
        };

        $entries = $request->validate([
            'email' => 'required|email',
            'password' => [
                'nullable',
                'confirmed',
                // Password::min(8)->letters()->numbers()->symbols()
            ],
            'password_confirmation' => [
                'required_with:password',
                'same:password'
            ],
            ...$extra_entries
        ]);

        $auth->update($entries);

        match ($auth->type) {
            AccountType::User => $auth->user()->updateOrCreate(['id' => $auth->id], $entries['user']),
            AccountType::Organization => $auth->organization()->updateOrCreate(['id' => $auth->id], $entries['organization']),
            default => null,
        };

        return view('dashboard.singletons.settings', [
            'account' => $auth->fresh(['user', 'organization']),
            'success' => true
        ]);
    }
}
