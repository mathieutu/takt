<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserController
{
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('ProfilePage', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'github_id' => $user->github_id,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
        ]);

        $request->user()->update($validated);

        return redirect()
            ->route('profile')
            ->with('success', 'Votre profil a été mis à jour.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->user()->delete();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('success', 'Votre compte a été supprimé.');
    }
}
