<?php

namespace App\Mail;

use App\Models\Graduate;
use App\Models\GraduateLoginCode;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The 6-digit code for activating an account or resetting a password.
 * ShouldBeEncrypted keeps the plain code encrypted while it waits in the jobs table.
 */
class LoginCodeMail extends Mailable implements ShouldBeEncrypted
{
    public function __construct(
        public Graduate $graduate,
        public string $code,
        public string $purpose,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->purpose === GraduateLoginCode::PURPOSE_RESET
                ? 'Your CITE Tracker password reset code'
                : 'Your CITE Tracker activation code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.login-code',
            text: 'emails.login-code-text',
            with: [
                'name' => $this->graduate->last_name ?? $this->graduate->full_name,
                'code' => $this->code,
                'minutes' => GraduateLoginCode::EXPIRES_MINUTES,
                'reset' => $this->purpose === GraduateLoginCode::PURPOSE_RESET,
            ],
        );
    }
}
