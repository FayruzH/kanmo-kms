# Employee Portal SSO Readiness

KMS already supports a lightweight employee portal SSO handshake through a signed URL. Keep employee pages public for now, then enable enforcement when the employee portal is ready to launch users into KMS.

## Current Mode

- `PORTAL_SSO_ENABLED=true` enables the signed SSO callback endpoint.
- `PORTAL_SSO_ENFORCE_EMPLOYEE_AUTH=false` keeps employee pages public while integration is being prepared.
- Admin users still sign in through `/admin/login`.
- Employee SSO users are matched by normalized `nip`; missing employees are created as `role=employee`.

## Environment

```env
PORTAL_SSO_ENABLED=true
PORTAL_SSO_ENFORCE_EMPLOYEE_AUTH=false
PORTAL_SSO_ENTRY_URL=
PORTAL_SSO_SECRET=
PORTAL_SSO_MAX_AGE_SECONDS=90
PORTAL_SSO_REQUIRE_NONCE=true
```

When the employee portal is ready, set:

```env
PORTAL_SSO_ENFORCE_EMPLOYEE_AUTH=true
PORTAL_SSO_ENTRY_URL=https://kanmoemployeeportal.com/path-that-launches-kms
```

`PORTAL_SSO_SECRET` must be the same secret on KMS and the employee portal. Never commit the real value.

## SSO Callback

Employee portal redirects users to:

```text
GET https://kms.example.com/sso/portal-login
```

Required query parameters:

| Parameter | Description |
| --- | --- |
| `nip` | Employee NIP, normalized to 10 digits before signing |
| `name` | Employee display name |
| `email` | Employee email, optional but recommended |
| `ts` | Current Unix timestamp |
| `nonce` | Random string, 12-120 characters |
| `sig` | Lowercase hex HMAC SHA-256 signature |

Signature payload:

```text
{normalized_nip}|{trimmed_name}|{lowercase_email}|{unix_timestamp}|{nonce}
```

PHP example for the employee portal:

```php
$nip = str_pad(trim($employee['nip']), 10, '0', STR_PAD_LEFT);
$name = trim($employee['name']);
$email = strtolower(trim($employee['email'] ?? ''));
$ts = time();
$nonce = bin2hex(random_bytes(20));

$payload = implode('|', [$nip, $name, $email, (string) $ts, $nonce]);
$sig = hash_hmac('sha256', $payload, $sharedSecret);

$url = 'https://kms.example.com/sso/portal-login?'.http_build_query([
    'nip' => $nip,
    'name' => $name,
    'email' => $email,
    'ts' => $ts,
    'nonce' => $nonce,
    'sig' => $sig,
]);
```

## Local Test Helper

After setting `PORTAL_SSO_SECRET`, generate a signed test link:

```bash
php artisan portal-sso:link 21619 "Test Employee" --email=test.employee@kanmo.co.id
```

Use `--base-url=https://kms.example.com` when generating a production-style URL from a local environment.

## Go-Live Checklist

- Confirm both systems use HTTPS.
- Set the same `PORTAL_SSO_SECRET` in KMS and employee portal.
- Keep `PORTAL_SSO_ENABLED=true` before enabling route enforcement.
- Ensure server clocks are synchronized because `ts` expires quickly.
- Keep `PORTAL_SSO_REQUIRE_NONCE=true` to prevent replay.
- Set `PORTAL_SSO_ENTRY_URL` to the employee portal launcher page.
- Flip `PORTAL_SSO_ENFORCE_EMPLOYEE_AUTH=true` only after portal redirect testing succeeds.
