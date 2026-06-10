<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeeSsoAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('portal_sso.enforce_employee_auth', false)) {
            return $next($request);
        }

        if (!config('portal_sso.enabled', false)) {
            abort(503, 'Employee SSO enforcement is enabled but PORTAL_SSO_ENABLED is false.');
        }

        $user = Auth::user();
        if ($user && $user->role === 'employee' && (bool) $user->active) {
            return $next($request);
        }

        if ($user && $user->role === 'admin' && Route::has('admin.dashboard')) {
            return redirect()->route('admin.dashboard');
        }

        $request->session()->put('url.intended', $request->fullUrl());

        $entryUrl = trim((string) config('portal_sso.entry_url', ''));
        if ($entryUrl !== '') {
            return redirect()->away($entryUrl);
        }

        abort(503, 'Employee SSO is enabled but PORTAL_SSO_ENTRY_URL is not configured.');
    }
}
