<?php

namespace App\Mail;

use App\Models\TestSession;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TestSession $session
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitation à passer votre test technique - ITIC Paris',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.test-invitation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
