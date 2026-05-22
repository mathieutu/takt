<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Support\Facades\Auth;

class AccountController
{
    public function delete()
    {
        Account::authenticated()->delete();

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/')->with([
            'toast' => [
                'type' => 'success',
                'message' => "Ton compte vient d'être supprimé.",
            ],
        ]);
    }
}
