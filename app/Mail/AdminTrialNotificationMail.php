<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminTrialNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $businessName,
        public string $contactName,
        public string $email,
        public ?string $phone,
        public string $trialItemName,
        public int $trialDays
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Trial Registration: '.$this->businessName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-trial-notification',
        );
    }
}
