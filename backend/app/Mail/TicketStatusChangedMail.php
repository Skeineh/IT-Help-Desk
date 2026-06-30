<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $ticketRef,
        public readonly string $ticketTitle,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly string $changedBy,
        public readonly string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[{$this->ticketRef}] Status changed: {$this->oldStatus} → {$this->newStatus}"
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.ticket-status-changed');
    }
}
