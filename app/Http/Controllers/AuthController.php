<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AuthController
{
    public function showLogin()
    {
        return Inertia::render('LoginPage');
    }

    public function showRegister()
    {
        return Inertia::render('RegisterPage');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard.settings'))->with([
                'from' => 'login',
            ]);
        }

        return back()->withErrors([
            '#global' => __('auth.failed'),
        ]);
    }

    public function register(Request $request)
    {
        $model_info = match ($request->enum('type', AccountType::class)) {
            AccountType::User => [
                'validation' => ['first_name' => 'required', 'last_name' => 'required'],
                'model' => User::class,
            ],
            AccountType::Organization => [
                'validation' => ['name' => 'required'],
                'model' => Organization::class,
            ],
            default => null,
        };

        $validated = $request->validate([
            'email' => 'required|email|unique:'.Account::class.',email',
            'password' => ['required', 'confirmed', Password::min(8)->letters()->symbols()->numbers()],
            'type' => ['required', Rule::enum(AccountType::class)],
            'model' => $model_info ? $model_info['validation'] : ['required'],
        ]);

        $account = DB::transaction(function () use ($validated, $model_info) {
            $account = Account::create($validated);

            $modelData = new $model_info['model']($validated['model']);
            $modelData->id = $account->id;
            $modelData->save();

            return $account;
        });

        Auth::login($account);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard.settings'))->with(['from' => 'register']);
    }
}
