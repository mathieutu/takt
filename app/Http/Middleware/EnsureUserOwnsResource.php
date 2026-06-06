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
        foreach ($request->route()->parameters() as $model) {
            if (! $model instanceof HasUser || ! $model->user->is($request->user())) {
                throw new AccessDeniedHttpException;
            }
        }

        return $next($request);
    }
}
