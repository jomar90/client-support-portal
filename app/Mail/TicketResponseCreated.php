<?php

namespace App\Mail;

use App\Models\Response;
use App\Models\Ticket;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TicketResponseCreated extends Mailable
{
    public function __construct(
        public Ticket $ticket,
        public Response $response,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New response to ticket #'.$this->ticket->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-response-created',
        );
    }
}
