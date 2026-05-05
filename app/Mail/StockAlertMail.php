<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StockAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $reportData;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($reportData)
    {
        $this->reportData = $reportData;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Daily Enterprise Stock Alert: Expiration & Low Inventory Warnings',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.stock-alert',
            with: [
                'lowStocks' => $this->reportData['lowStocks'] ?? [],
                'expiringBatches' => $this->reportData['expiringBatches'] ?? [],
                'expiredBatches' => $this->reportData['expiredBatches'] ?? [],
                'dateReported' => now()->format('Y-m-d H:i:s'),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
