<?php

namespace App\Mail;

use App\Models\SopDocument;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SopGroupedReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param Collection<int, SopDocument> $sops
     */
    public function __construct(
        public readonly User $pic,
        public readonly Collection $sops,
        public readonly string $batchId
    ) {
    }

    public function build(): self
    {
        $normalizedSops = $this->sops->values();
        $normalizedSops->loadMissing(['category', 'department']);

        $today = now()->startOfDay();
        $sopRows = $normalizedSops->map(static function (SopDocument $sop) use ($today): array {
            $expiryDate = $sop->expiry_date ? Carbon::parse($sop->expiry_date)->startOfDay() : null;
            $diffDays = $expiryDate ? $today->diffInDays($expiryDate, false) : null;
            $timeline = $diffDays === null
                ? '-'
                : ($diffDays < 0 ? abs($diffDays) . ' day(s) overdue' : $diffDays . ' day(s) remaining');

            return [
                'id' => $sop->id,
                'title' => (string) $sop->title,
                'status' => (string) $sop->status,
                'status_label' => $sop->status === 'expired' ? 'Expired' : 'Expiring Soon',
                'status_bg_color' => $sop->status === 'expired' ? '#FDECEC' : '#FFF6E5',
                'status_text_color' => $sop->status === 'expired' ? '#C81E1E' : '#B45309',
                'expiry_date_label' => $expiryDate?->format('Y-m-d') ?? '-',
                'timeline' => $timeline,
                'department' => (string) ($sop->department?->name ?? '-'),
                'division' => (string) ($sop->category?->name ?? '-'),
                'sop_url' => route('employee.sop.show', $sop),
            ];
        })->values();

        $expiredCount = $sopRows->where('status', 'expired')->count();
        $expiringCount = $sopRows->where('status', 'expiring_soon')->count();
        $totalCount = $sopRows->count();

        return $this->subject("SOP Reminder - Action Required ({$totalCount} SOPs)")
            ->view('emails.sop-reminder-grouped')
            ->text('emails.sop-reminder-grouped-text')
            ->with([
                'appName' => config('app.name', 'Kanmo KMS'),
                'picName' => (string) $this->pic->name,
                'batchId' => $this->batchId,
                'generatedAt' => now()->format('Y-m-d H:i'),
                'totalCount' => $totalCount,
                'expiredCount' => $expiredCount,
                'expiringCount' => $expiringCount,
                'sopRows' => $sopRows,
            ]);
    }
}
