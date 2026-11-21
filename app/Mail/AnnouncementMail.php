<?php

namespace App\Mail;

use App\Models\Announcement;
use App\Models\Graduate;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AnnouncementMail extends Mailable
{
    public function __construct(
        public Announcement $announcement,
        public Graduate $graduate,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->announcement->isEvent() ? 'CITE Event: ' : 'CITE Announcement: ').$this->announcement->title,
        );
    }

    public function content(): Content
    {
        $announcement = $this->announcement;
        $public = $announcement->visibility === 'public';

        return new Content(
            view: 'emails.announcement',
            text: 'emails.announcement-text',
            with: [
                'name' => $this->graduate->last_name ?? $this->graduate->full_name,
                'title' => $announcement->title,
                // Plain text; the views escape it and keep the line breaks.
                'body' => $announcement->body,
                'isEvent' => $announcement->isEvent(),
                'eventDate' => $announcement->event_starts_at?->format('l, F j, Y \a\t g:i A'),
                'eventPlace' => $announcement->event_place,
                'eventLink' => $announcement->event_link,
                // Public ones open without a login. Selected ones go through the
                // alumni login, which then returns to the announcement.
                'link' => route($public ? 'announcements.show' : 'alumni.announcements.show', $announcement),
                'needsLogin' => ! $public,
                // Not activated yet: their personal link creates the account first.
                'activateLink' => ! $public && ! $this->graduate->isActivated() && $this->graduate->invite_token !== null
                    ? route('survey.invite', ['token' => $this->graduate->invite_token])
                    : null,
            ],
        );
    }
}
