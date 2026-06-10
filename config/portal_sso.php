<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Portal SSO Toggle
    |--------------------------------------------------------------------------
    |
    | Enable this when KMS is launched from the company employee portal.
    | Requests are expected to be signed with HMAC SHA-256.
    |
    */
    'enabled' => env('PORTAL_SSO_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Employee Route Enforcement
    |--------------------------------------------------------------------------
    |
    | Keep this false while the employee portal is still public. When the
    | portal integration is ready, set it to true so employee pages require
    | an authenticated SSO session.
    |
    */
    'enforce_employee_auth' => env('PORTAL_SSO_ENFORCE_EMPLOYEE_AUTH', false),

    /*
    |--------------------------------------------------------------------------
    | Employee Portal Entry URL
    |--------------------------------------------------------------------------
    |
    | Used when employee auth enforcement is enabled and a guest opens an
    | employee page directly. This should point to the employee portal page
    | that launches KMS through the signed /sso/portal-login URL.
    |
    */
    'entry_url' => env('PORTAL_SSO_ENTRY_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Shared Secret
    |--------------------------------------------------------------------------
    |
    | Must match the secret used by the employee portal when generating
    | signatures for /sso/portal-login requests.
    |
    */
    'secret' => env('PORTAL_SSO_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Token Freshness
    |--------------------------------------------------------------------------
    |
    | Maximum allowed age in seconds between the portal timestamp and KMS
    | server time.
    |
    */
    'max_age_seconds' => (int) env('PORTAL_SSO_MAX_AGE_SECONDS', 90),

    /*
    |--------------------------------------------------------------------------
    | Replay Protection
    |--------------------------------------------------------------------------
    |
    | When enabled, each nonce can only be used once during token lifetime.
    |
    */
    'require_nonce' => (bool) env('PORTAL_SSO_REQUIRE_NONCE', true),
];
