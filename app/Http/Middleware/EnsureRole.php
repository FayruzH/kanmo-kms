<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle($request, Closure $next, ...$roles)
    {
        if (!in_array(auth()->user()->role, $roles)) {
            abort(403);
        }

        return $next($request);
    }
}
