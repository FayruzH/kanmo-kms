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

