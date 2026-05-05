<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $recipientName;

    public function __construct(string $otp, string $recipientName = 'User')
    {
        $this->otp           = $otp;
        $this->recipientName = $recipientName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Password Reset Code — Mister Takoyaki',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.otp',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
