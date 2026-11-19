<?php

namespace App\Mail;

use App\Models\Graduate;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class UpdateReminderMail extends Mailable
{
    public function __construct(public Graduate $graduate) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Please update your CITE Graduate Tracer Survey answers',
        );
    }

    public function content(): Content
    {
        $last = $this->graduate->survey_updated_at ?? $this->graduate->survey_completed_at;

        return new Content(
            view: 'emails.update-reminder',
            text: 'emails.update-reminder-text',
            with: [
                'name' => $this->graduate->last_name ?? $this->graduate->full_name,
                // The personal link opens the login (or activation, if not done yet).
                'link' => route('survey.invite', ['token' => $this->graduate->invite_token]),
                'lastUpdated' => $last?->format('F j, Y'),
            ],
        );
    }
}
