<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Auth;
use Illuminate\Http\Request;

class AccountController
{
    public function delete()
    {
        (new AuthController)->logout();

        Account::authenticated()->delete();

        return redirect('/')->with([
            'toast' => [
                'type' => 'success',
                'message' => "Ton compte vient d'être supprimé."
            ]
        ]);
    }
}
