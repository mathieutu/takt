<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function edit(): Response
    {
        $user = Auth::user();

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

        Auth::user()->update($validated);

        return redirect()
            ->route('profile')
            ->with('success', 'Your profile has been updated.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::user()->delete();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Your account has been deleted.');
    }
}
