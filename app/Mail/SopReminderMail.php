<?php

namespace App\Mail;

use App\Models\SopDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

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
        $subject = $this->reminderType === 'expired'
            ? 'SOP Expired Reminder: ' . $this->sop->title
            : 'SOP Expiring Reminder: ' . $this->sop->title;

        return $this->subject($subject)
            ->view('emails.sop-reminder');
    }
}
