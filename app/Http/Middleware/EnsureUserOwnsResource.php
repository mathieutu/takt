<?php

namespace App\Http\Middleware;

use App\Contracts\HasUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EnsureUserOwnsResource
{
    public function handle(Request $request, Closure $next): Response
    {
        $isUnauthorized = collect($request->route()->parameters())
            ->contains(fn ($model) => ! $model instanceof HasUser || ! $model->user->is($request->user()));

        if ($isUnauthorized) {
            throw new AccessDeniedHttpException;
        }

        return $next($request);
    }
}
