<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\StockOrder;

use Illuminate\Contracts\Queue\ShouldQueue;

class StockOrderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $order;
    public $title;
    public $mailMessage;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(StockOrder $order, string $title, string $message)
    {
        $this->order = $order;
        $this->title = $title;
        $this->mailMessage = $message;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->title,
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
            view: 'emails.stock-order',
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
