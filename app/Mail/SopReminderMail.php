<?php

namespace App\Mail;

use App\Models\SopDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SopReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SopDocument $sop,
        public readonly string $reminderType
    ) {
    }

    public function build(): self
    {
        $this->sop->loadMissing(['category', 'department', 'sourceApp', 'pic']);

        $isExpired = $this->reminderType === 'expired';
        $subject = $isExpired
            ? 'SOP Expired Reminder: '.$this->sop->title
            : 'SOP Expiring Reminder: '.$this->sop->title;

        $today = now()->startOfDay();
        $expiryDate = $this->sop->expiry_date ? Carbon::parse($this->sop->expiry_date)->startOfDay() : null;

        $dayNote = '-';
        if ($expiryDate) {
            $diffDays = $today->diffInDays($expiryDate, false);
            $dayNote = $diffDays < 0
                ? abs($diffDays).' day(s) overdue'
                : $diffDays.' day(s) remaining';
        }

        return $this->subject($subject)
            ->view('emails.sop-reminder')
            ->text('emails.sop-reminder-text')
            ->with([
                'statusLabel' => $isExpired ? 'Expired' : 'Expiring Soon',
                'statusBgColor' => $isExpired ? '#FDECEC' : '#FFF6E5',
                'statusTextColor' => $isExpired ? '#C81E1E' : '#B45309',
                'sopCode' => 'SOP-'.str_pad((string) $this->sop->id, 3, '0', STR_PAD_LEFT),
                'expiryDateLabel' => $expiryDate?->format('Y-m-d') ?? '-',
                'dayNote' => $dayNote,
                'sopUrl' => route('employee.sop.show', $this->sop),
                'appName' => config('app.name', 'Kanmo KMS'),
            ]);
    }
}
