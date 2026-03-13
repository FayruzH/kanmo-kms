<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class KepEmployeeSyncService
{
    /**
     * @return array{total:int,created:int,updated:int,skipped:int,admin_skipped:int}
     */
    public function sync(): array
    {
        $rows = $this->fetchEmployees();
        $syncPassword = (bool) config('services.kep.sync_password', false);

        $existingUsers = User::query()
            ->select(['id', 'nip', 'email', 'role', 'name', 'password', 'department', 'entity', 'active'])
            ->get()
            ->keyBy('nip');

        $emailOwners = [];
        foreach ($existingUsers as $existingUser) {
            $emailOwners[Str::lower($existingUser->email)] = $existingUser->id;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $adminSkipped = 0;

        foreach ($rows as $row) {
            $nip = User::normalizeNip((string) ($row['nip'] ?? ''));
            if ($nip === '' || !ctype_digit($nip)) {
                $skipped++;
                continue;
            }

            /** @var User|null $existing */
            $existing = $existingUsers->get($nip);
            if ($existing && $existing->role === 'admin') {
                $adminSkipped++;
                continue;
            }

            $passwordHash = trim((string) ($row['password'] ?? ''));
            $hasValidPasswordHash = $this->looksLikePasswordHash($passwordHash);

            if (!$existing && !$hasValidPasswordHash) {
                $skipped++;
                continue;
            }
            if ($existing && $syncPassword && !$hasValidPasswordHash) {
                $skipped++;
                continue;
            }

            $email = $this->resolveEmail(
                rawEmail: (string) ($row['email_comp'] ?? ''),
                nip: $nip,
                currentUserId: $existing?->id,
                emailOwners: $emailOwners
            );

            $attributes = [
                'name' => $this->cleanText((string) ($row['name'] ?? '')) ?: "Employee {$nip}",
                'email' => $email,
                'role' => 'employee',
                'department' => $this->cleanNullableText($row['departement'] ?? null),
                'entity' => $this->cleanNullableText($row['division'] ?? null),
                'active' => true,
            ];
            if (!$existing || $syncPassword) {
                $attributes['password'] = $passwordHash;
            }

            if ($existing) {
                $oldEmail = Str::lower((string) $existing->email);

                $existing->fill($attributes);
                if ($existing->isDirty()) {
                    $existing->save();
                    $updated++;
                }

                $newEmail = Str::lower((string) $existing->email);
                if ($newEmail !== $oldEmail) {
                    unset($emailOwners[$oldEmail]);
                }

                $emailOwners[$newEmail] = $existing->id;
                $existingUsers->put($nip, $existing);

                continue;
            }

            $user = User::query()->create(array_merge($attributes, [
                'nip' => $nip,
            ]));

            $emailOwners[Str::lower($user->email)] = $user->id;
            $existingUsers->put($nip, $user);
            $created++;
        }

        return [
            'total' => count($rows),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'admin_skipped' => $adminSkipped,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchEmployees(): array
    {
        $endpoint = trim((string) config('services.kep.endpoint'));
        if ($endpoint === '') {
            throw new RuntimeException('KEP API endpoint is empty. Set KEP_API_URL in .env.');
        }

        $timeout = max(10, (int) config('services.kep.timeout', 90));

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->get($endpoint);

        $response->throw();

        $payload = $response->json();
        if (!is_array($payload) || !($payload['success'] ?? false)) {
            throw new RuntimeException('Unexpected KEP API response: success flag missing/false.');
        }

        $rows = $payload['data'] ?? null;
        if (!is_array($rows)) {
            throw new RuntimeException('Unexpected KEP API response: data must be an array.');
        }

        return $rows;
    }

    private function resolveEmail(string $rawEmail, string $nip, ?int $currentUserId, array $emailOwners): string
    {
        $candidate = Str::lower(trim($rawEmail));
        if (! filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
            return $this->fallbackEmail($nip, $currentUserId, $emailOwners);
        }

        $ownerId = $emailOwners[$candidate] ?? null;
        if ($ownerId !== null && $ownerId !== $currentUserId) {
            return $this->fallbackEmail($nip, $currentUserId, $emailOwners);
        }

        return $candidate;
    }

    private function fallbackEmail(string $nip, ?int $currentUserId, array $emailOwners): string
    {
        $base = "kep.{$nip}@kanmo.local";
        $ownerId = $emailOwners[$base] ?? null;
        if ($ownerId === null || $ownerId === $currentUserId) {
            return $base;
        }

        $counter = 2;
        while (true) {
            $candidate = "kep.{$nip}.{$counter}@kanmo.local";
            $candidateOwnerId = $emailOwners[$candidate] ?? null;
            if ($candidateOwnerId === null || $candidateOwnerId === $currentUserId) {
                return $candidate;
            }

            $counter++;
        }
    }

    private function looksLikePasswordHash(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        return Str::startsWith($value, ['$2y$', '$2a$', '$argon2i$', '$argon2id$']);
    }

    private function cleanText(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function cleanNullableText(mixed $value): ?string
    {
        $text = $this->cleanText((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }
}
