<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('LoginPage', [
            'users' => ! config('auth.enabled') ? User::all(['id', 'name', 'email']) : [],
        ]);
    }

    public function redirect(): RedirectResponse
    {
        return Socialite::driver('github')->redirect();
    }

    public function callback(): RedirectResponse
    {
        $githubUser = Socialite::driver('github')->user();

        $user = User::where('github_id', $githubUser->getId())->first()
            ?? User::where('email', $githubUser->getEmail())->first();

        if ($user) {
            $user->update(['github_id' => $githubUser->getId()]);
        } else {
            $user = User::create([
                'name' => $githubUser->getName() ?? $githubUser->getNickname(),
                'email' => $githubUser->getEmail(),
                'github_id' => $githubUser->getId(),
            ]);
        }

        Auth::login($user);

        return redirect()->intended('/');
    }

    public function disabled(Request $request): RedirectResponse
    {
        abort_unless(app()->isLocal(), 403);

        $user = User::findOrFail($request->input('user_id'));

        Auth::login($user);

        return redirect()->intended('/');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
