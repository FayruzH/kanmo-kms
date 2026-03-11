<?php

namespace App\Services;

use App\Models\SopDocument;
use Carbon\Carbon;

class SopStatusService
{
    public function __construct(private readonly SettingService $settingService)
    {
    }

    public function resolveStatus(SopDocument $sop, ?Carbon $today = null): string
    {
        if ($sop->archived_at !== null || $sop->status === 'archived') {
            return 'archived';
        }

        $today = $today ?: now()->startOfDay();
        $expiry = optional($sop->expiry_date)->copy()?->startOfDay();
        if (!$expiry) {
            return 'active';
        }

        if ($expiry->lt($today)) {
            return 'expired';
        }

        $threshold = $this->settingService->getInt('expiry_threshold_days', 30);
        if ($expiry->lte($today->copy()->addDays($threshold))) {
            return 'expiring_soon';
        }

        return 'active';
    }

    public function syncAll(): int
    {
        $count = 0;
        SopDocument::query()->chunkById(200, function ($docs) use (&$count) {
            foreach ($docs as $doc) {
                $status = $this->resolveStatus($doc);
                if ($status !== $doc->status) {
                    $doc->status = $status;
                    $doc->save();
                    $count++;
                }
            }
        });

        return $count;
    }
}
