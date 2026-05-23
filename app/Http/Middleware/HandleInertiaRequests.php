<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = Auth::user();

        Inertia::flash(array_filter([
            'success' => $request->session()->get('success'),
            'info' => $request->session()->get('info'),
            'warn' => $request->session()->get('warn'),
            'error' => $request->session()->get('error'),
            'message' => $request->session()->get('message'),
        ]));

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'name' => $user->name,
                    'email' => $user->email,
                ] : null,
            ],
            'csrfToken' => csrf_token(),
        ];
    }
}
