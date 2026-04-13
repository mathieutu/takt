<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Organization;
use App\Models\User;
use Auth;
use Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController
{
    public function logout()
    {
        Auth::logout();

        Request::session()->invalidate();
        Request::session()->regenerateToken();

        return redirect('/');
    }

    public function login(\Illuminate\Http\Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard.settings'))->with([
                'from' => 'login'
            ]);
        }

        return back()->withErrors([
            '#global' => __('auth.failed')
        ]);
    }

    public function register(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:' . Account::class . ',email',
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->symbols()->numbers()
            ],
            'type' => [
                'required',
                Rule::enum(AccountType::class)
            ]
        ]);

        $account = Account::create($validated);

        $model_info = match ($account->type) {
            AccountType::User => [
                'validation' => [
                    'first_name' => 'required',
                    'last_name' => 'required'
                ],
                'model' => User::class
            ],
            AccountType::Organization => [
                'validation' => [
                    'name' => 'required'
                ],
                'model' => Organization::class
            ]
        };

        $model_validated = $request->validate([
            'model' => $model_info['validation']
        ])['model'];
        $model_info['model']::create($model_validated);

        Auth::login($account);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard.settings'))->with([
            'from' => 'register'
        ]);
    }
}
