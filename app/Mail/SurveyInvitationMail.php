<?php

namespace App\Mail;

use App\Models\Graduate;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class SurveyInvitationMail extends Mailable
{
    public function __construct(
        public Graduate $graduate,
        public bool $reminder = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->reminder ? 'Reminder: ' : '').'Please answer the CITE Graduate Tracer Survey',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.survey-invitation',
            text: 'emails.survey-invitation-text',
            with: [
                'name' => $this->graduate->last_name ?? $this->graduate->full_name,
                'link' => route('survey.invite', ['token' => $this->graduate->invite_token]),
                'reminder' => $this->reminder,
            ],
        );
    }
}
