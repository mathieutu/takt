<?php

namespace App\Http\Controllers;

use App\Actions\CreateDemoData;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;

class AuthController
{
    public function show(Request $request): Response
    {
        return Inertia::render('LoginPage', [
            'users' => ! config('auth.enabled') ? User::all(['id', 'name', 'email']) : [],
            'redirect' => $request->query('redirect'),
        ]);
    }

    public function redirect(Request $request): RedirectResponse
    {
        if ($request->filled('redirect')) {
            session(['url.intended' => $request->input('redirect')]);
        }

        return Socialite::driver('github')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $githubUser = Socialite::driver('github')->user();

        $user = User::where('github_id', $githubUser->getId())->first()
            ?? User::where('email', $githubUser->getEmail())->first();

        if ($user) {
            $user->update([
                'github_id' => $githubUser->getId(),
                'avatar' => $githubUser->getAvatar(),
            ]);
        } else {
            $user = User::create([
                'name' => $githubUser->getName() ?? $githubUser->getNickname(),
                'email' => $githubUser->getEmail(),
                'github_id' => $githubUser->getId(),
                'avatar' => $githubUser->getAvatar(),
            ]);
        }

        Auth::login($user, true);

        return redirect()->intended('/');
    }

    public function disabled(Request $request): RedirectResponse
    {
        abort_unless(app()->isLocal(), 403);

        if ($request->filled('redirect')) {
            session(['url.intended' => $request->input('redirect')]);
        }

        $user = User::findOrFail($request->input('user_id'));

        Auth::login($user);

        return redirect()->intended('/');
    }

    public function demo(CreateDemoData $createDemoAccount): RedirectResponse
    {
        $user = User::where('email', config('auth.demo_email'))->first();

        if (! $user) {
            $user = $createDemoAccount();
        }

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
