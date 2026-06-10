<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GeneratePortalSsoLink extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'portal-sso:link
        {nip : Employee NIP, 1-10 digits}
        {name : Employee display name}
        {--email= : Employee email address}
        {--base-url= : KMS base URL override}
        {--nonce= : Nonce override for repeatable testing}
        {--timestamp= : Unix timestamp override}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a signed employee portal SSO login URL for testing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $secret = (string) config('portal_sso.secret', '');
        if ($secret === '') {
            $this->error('PORTAL_SSO_SECRET is empty. Set it before generating a signed SSO URL.');

            return self::FAILURE;
        }

        $nip = User::normalizeNip((string) $this->argument('nip'));
        $name = trim((string) $this->argument('name'));
        $email = trim(strtolower((string) ($this->option('email') ?? '')));
        $timestamp = $this->resolveTimestamp();
        $nonce = $this->resolveNonce();

        $payload = implode('|', [$nip, $name, $email, (string) $timestamp, $nonce]);
        $signature = hash_hmac('sha256', $payload, $secret);

        $baseUrl = rtrim((string) ($this->option('base-url') ?: config('app.url')), '/');
        $url = $baseUrl.'/sso/portal-login?'.http_build_query([
            'nip' => $nip,
            'name' => $name,
            'email' => $email,
            'ts' => $timestamp,
            'nonce' => $nonce,
            'sig' => $signature,
        ]);

        $this->line($url);

        return self::SUCCESS;
    }

    private function resolveTimestamp(): int
    {
        $timestamp = $this->option('timestamp');

        return $timestamp !== null && $timestamp !== ''
            ? (int) $timestamp
            : now()->timestamp;
    }

    private function resolveNonce(): string
    {
        $nonce = trim((string) ($this->option('nonce') ?? ''));

        return $nonce !== '' ? $nonce : Str::random(40);
    }
}
