<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $recipientName;
    public string $purpose;

    public function __construct(
        string $otp,
        string $recipientName = 'User',
        string $purpose = 'forgot_password'
    ) {
        $this->otp = $otp;
        $this->recipientName = $recipientName;
        $this->purpose = $purpose;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->purpose === 'google_signin'
                ? 'Your Google Sign-In Verification Code - Mister Takoyaki'
                : 'Your Password Reset Code - Mister Takoyaki',
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