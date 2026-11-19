<?php

namespace App\Jobs;

use App\Mail\SurveyInvitationMail;
use App\Models\Graduate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Emails one graduate their personal survey link (or a reminder) and records
 * whether it went through, so the upload page can show sent or failed.
 */
class SendSurveyInvitation implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public int $graduateId,
        public bool $reminder = false,
    ) {}

    public function handle(): void
    {
        $graduate = Graduate::find($this->graduateId);

        // They answered in the meantime, or there is nobody to email.
        if ($graduate === null || $graduate->survey_completed_at !== null || blank($graduate->email)) {
            return;
        }

        try {
            Mail::to($graduate->email)->send(new SurveyInvitationMail($graduate, $this->reminder));
        } catch (Throwable $exception) {
            $this->markFailed($graduate, $exception);

            return;
        }

        $graduate->forceFill([
            'invite_status' => 'sent',
            'invite_error' => null,
            // The first email sets the sent time; reminders only count up.
            'invite_sent_at' => $this->reminder ? $graduate->invite_sent_at : now(),
            'reminder_count' => $this->reminder ? $graduate->reminder_count + 1 : $graduate->reminder_count,
            'last_reminded_at' => $this->reminder ? now() : $graduate->last_reminded_at,
        ])->save();
    }

    /**
     * Runs if the job itself crashes (for example the worker is stopped mid-send).
     */
    public function failed(Throwable $exception): void
    {
        $graduate = Graduate::find($this->graduateId);

        if ($graduate !== null) {
            $this->markFailed($graduate, $exception);
        }
    }

    private function markFailed(Graduate $graduate, Throwable $exception): void
    {
        $graduate->forceFill([
            'invite_status' => 'failed',
            'invite_error' => Str::limit($exception->getMessage(), 240),
        ])->save();
    }
}
