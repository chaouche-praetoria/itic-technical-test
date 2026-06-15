<?php

namespace App\Mail;

use App\Models\ClassInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClassInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ClassInvitation $invitation
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitation à rejoindre une classe - ITIC Paris',
        );
    }

    public function content(): Content
    {
        $this->invitation->loadMissing('classroom.academicLevel', 'classroom.teacher');

        return new Content(
            view: 'emails.class-invitation',
            with: [
                'classroom' => $this->invitation->classroom,
                'joinUrl' => route('class.join', $this->invitation->token),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
