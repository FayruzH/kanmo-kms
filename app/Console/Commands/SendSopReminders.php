<?php

namespace App\Console\Commands;

use App\Mail\SopReminderMail;
use App\Models\ReminderJob;
use App\Models\SopDocument;
use App\Services\SettingService;
use App\Services\SopStatusService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendSopReminders extends Command
{
    protected $signature = 'sop:send-reminders';
    protected $description = 'Sync SOP status and send reminder emails to SOP PIC';

    public function handle(SopStatusService $statusService, SettingService $settingService): int
    {
        $statusUpdated = $statusService->syncAll();
        $this->info("Status updated: {$statusUpdated}");

        $today = now()->startOfDay();
        $expiringDays = $settingService->getArray('reminder_expiring_days', [30, 14, 7, 1]);
        $expiredInterval = $settingService->getInt('reminder_expired_interval_days', 7);

        $expiringSops = SopDocument::query()
            ->with('pic')
            ->where('status', 'expiring_soon')
            ->get()
            ->filter(function (SopDocument $sop) use ($today, $expiringDays) {
                if (!$sop->expiry_date) {
                    return false;
                }
                $daysLeft = $today->diffInDays($sop->expiry_date, false);
                return in_array($daysLeft, $expiringDays, true);
            });

        $expiredSops = SopDocument::query()
            ->with('pic')
            ->where('status', 'expired')
            ->get()
            ->filter(function (SopDocument $sop) use ($today, $expiredInterval) {
                if (!$sop->expiry_date) {
                    return false;
                }
                $daysAfter = $sop->expiry_date->diffInDays($today, false);
                return $daysAfter >= 0 && $daysAfter % $expiredInterval === 0;
            });

        $sent = 0;
        foreach ($expiringSops as $sop) {
            $sent += $this->sendReminder($sop, 'expiring');
        }
        foreach ($expiredSops as $sop) {
            $sent += $this->sendReminder($sop, 'expired');
        }

        $this->info("Reminders sent: {$sent}");

        return self::SUCCESS;
    }

    private function sendReminder(SopDocument $sop, string $type): int
    {
        if (!$sop->pic || !$sop->pic->email) {
            return 0;
        }

        $job = ReminderJob::query()->create([
            'sop_id' => $sop->id,
            'pic_user_id' => $sop->pic_user_id,
            'reminder_type' => $type,
            'status' => 'pending',
            'meta_json' => ['trigger' => 'scheduler'],
        ]);

        try {
            Mail::to($sop->pic->email)->send(new SopReminderMail($sop, $type));
            $job->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return 1;
        } catch (\Throwable $e) {
            $job->update([
                'status' => 'failed',
                'meta_json' => array_merge($job->meta_json ?? [], ['error' => $e->getMessage()]),
            ]);
            return 0;
        }
    }
}
