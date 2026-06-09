<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        if (file_exists($manifest = public_path('build/manifest.json'))) {
            return md5_file($manifest);
        }

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
                    'avatar' => $user->avatar,
                ] : null,
                'isDemo' => $user?->email === config('auth.demo_email'),
            ],
            'updatedAt' => Inertia::once(function () {
                $updatedAt = config('app.updated_at');

                return (is_numeric($updatedAt)
                     ? Date::createFromTimestamp($updatedAt, 'Europe/Paris')
                     : Date::parse($updatedAt, 'Europe/Paris'))->toDatetimeString();
            }),
        ];
    }
}
