<?php

namespace App\Mail;

use App\Models\EvaluationAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EvaluationInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public EvaluationAttempt $attempt
    ) {}

    public function envelope(): Envelope
    {
        $this->attempt->loadMissing('evaluation');

        return new Envelope(
            subject: 'Nouvelle évaluation : ' . $this->attempt->evaluation->title . ' - ITIC Paris',
        );
    }

    public function content(): Content
    {
        $this->attempt->loadMissing('evaluation', 'student');

        return new Content(
            view: 'emails.evaluation-invitation',
            with: [
                'evaluation' => $this->attempt->evaluation,
                'student' => $this->attempt->student,
                'startUrl' => route('eval.start', $this->attempt->token),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
