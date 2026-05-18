<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController
{
    public function edit()
    {
        $account = Account::authenticated()->load(['user', 'organization']);

        return Inertia::render('SettingsPage', [
            'account' => $this->serializeAccount($account),
        ]);
    }

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

        return redirect()->route('dashboard.settings')->with('success', true);
    }

    private function serializeAccount(Account $account): array
    {
        return [
            'email'        => $account->email,
            'type'         => $account->type?->value,
            'user'         => $account->user ? [
                'first_name' => $account->user->first_name,
                'last_name'  => $account->user->last_name,
            ] : null,
            'organization' => $account->organization ? [
                'name' => $account->organization->name,
            ] : null,
        ];
    }
}
