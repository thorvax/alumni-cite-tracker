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
                // Main button: the alumni login page (then My Survey > Update my answers).
                'link' => route('alumni.login'),
                // Only for graduates who answered before accounts existed and never
                // activated one: they need their personal link to create it first.
                'activateLink' => ! $this->graduate->isActivated() && $this->graduate->invite_token !== null
                    ? route('survey.invite', ['token' => $this->graduate->invite_token])
                    : null,
                'lastUpdated' => $last?->format('F j, Y'),
            ],
        );
    }
}
