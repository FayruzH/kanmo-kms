<?php

namespace App\Services;

use App\Models\SopActivityLog;

class SopActivityService
{
    public function log(int $sopId, int $userId, string $eventType, ?string $device = null): void
    {
        SopActivityLog::query()->create([
            'sop_id' => $sopId,
            'user_id' => $userId,
            'event_type' => $eventType,
            'device' => $device,
        ]);
    }
}
