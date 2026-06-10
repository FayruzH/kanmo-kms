<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PortalSsoController extends Controller
{
    public function authenticate(Request $request): RedirectResponse
    {
        if (!config('portal_sso.enabled', false)) {
            abort(404);
        }

        $secret = (string) config('portal_sso.secret', '');
        if ($secret === '') {
            Log::error('Portal SSO is enabled but PORTAL_SSO_SECRET is empty.');
            abort(503, 'Portal SSO is not configured.');
        }

        $validated = $request->validate([
            'nip' => ['required', 'string', 'regex:/^\d{1,10}$/'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'ts' => ['required', 'integer'],
            'nonce' => ['required', 'string', 'min:12', 'max:120'],
            'sig' => ['required', 'string', 'size:64', 'regex:/^[a-fA-F0-9]{64}$/'],
        ]);

        $nip = User::normalizeNip((string) $validated['nip']);
        $name = trim((string) $validated['name']);
        $email = trim(strtolower((string) ($validated['email'] ?? '')));
        $timestamp = (int) $validated['ts'];
        $nonce = trim((string) $validated['nonce']);
        $signature = strtolower((string) $validated['sig']);
        $maxAgeSeconds = max(10, (int) config('portal_sso.max_age_seconds', 90));

        if (abs(now()->timestamp - $timestamp) > $maxAgeSeconds) {
            throw ValidationException::withMessages([
                'sso' => 'SSO request expired.',
            ]);
        }

        $payload = $this->buildPayload($nip, $name, $email, $timestamp, $nonce);
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expectedSignature, $signature)) {
            throw ValidationException::withMessages([
                'sso' => 'Invalid SSO signature.',
            ]);
        }

        if ((bool) config('portal_sso.require_nonce', true)) {
            $nonceKey = 'portal_sso_nonce:' . $nonce;
            if (!Cache::add($nonceKey, 1, now()->addSeconds($maxAgeSeconds + 30))) {
                throw ValidationException::withMessages([
                    'sso' => 'SSO token has already been used.',
                ]);
            }
        }

        $user = User::query()->where('nip', $nip)->first();
        if ($user && $user->role === 'admin') {
            abort(403, 'Admin account must sign in through /admin/login.');
        }

        $resolvedEmail = $this->resolveEmailForUser($nip, $email, $user?->id);

        if (!$user) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $resolvedEmail,
                'nip' => $nip,
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'role' => 'employee',
                'active' => true,
            ]);
        } else {
            $user->fill([
                'name' => $name,
                'email' => $resolvedEmail,
                'active' => true,
            ]);
            $user->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('employee.dashboard'));
    }

    private function buildPayload(string $nip, string $name, string $email, int $timestamp, string $nonce): string
    {
        return implode('|', [$nip, $name, $email, (string) $timestamp, $nonce]);
    }

    private function resolveEmailForUser(string $nip, string $email, ?int $ignoreUserId = null): string
    {
        $candidate = filter_var($email, FILTER_VALIDATE_EMAIL)
            ? strtolower($email)
            : "portal-{$nip}@kms.local";

        if (!$this->emailExists($candidate, $ignoreUserId)) {
            return $candidate;
        }

        $suffix = 1;
        do {
            $candidate = "portal-{$nip}-{$suffix}@kms.local";
            $suffix++;
        } while ($this->emailExists($candidate, $ignoreUserId));

        return $candidate;
    }

    private function emailExists(string $email, ?int $ignoreUserId = null): bool
    {
        return User::query()
            ->when($ignoreUserId !== null, fn ($query) => $query->where('id', '!=', $ignoreUserId))
            ->where('email', $email)
            ->exists();
    }
}
