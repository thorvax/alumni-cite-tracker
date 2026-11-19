<?php

namespace App\Jobs;

use App\Mail\UpdateReminderMail;
use App\Models\Graduate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Emails one graduate a "please update your answers" reminder.
 */
class SendUpdateReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $graduateId) {}

    public function handle(): void
    {
        $graduate = Graduate::find($this->graduateId);

        if ($graduate === null || blank($graduate->email) || $graduate->invite_token === null) {
            return;
        }

        try {
            Mail::to($graduate->email)->send(new UpdateReminderMail($graduate));
        } catch (Throwable $exception) {
            // Logged by id only (no email address in the log).
            Log::warning("Update reminder failed for graduate #{$graduate->id}: {$exception->getMessage()}");
        }
    }
}
