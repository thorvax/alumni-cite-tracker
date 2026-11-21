<?php

namespace App\Jobs;

use App\Mail\AnnouncementMail;
use App\Models\AnnouncementRecipient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Emails one announcement to one graduate and records sent or failed,
 * so the announcement's detail page can show it.
 */
class SendAnnouncementEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $recipientId) {}

    public function handle(): void
    {
        $recipient = AnnouncementRecipient::with(['announcement', 'graduate'])->find($this->recipientId);

        if ($recipient === null) {
            return;
        }

        $graduate = $recipient->graduate;

        // Email removed in the meantime, or a demo row: nothing to send.
        if ($graduate === null || $graduate->is_demo || blank($graduate->email)) {
            $recipient->forceFill(['email_status' => 'skipped', 'email_error' => null])->save();

            return;
        }

        try {
            Mail::to($graduate->email)->send(new AnnouncementMail($recipient->announcement, $graduate));
        } catch (Throwable $exception) {
            $this->markFailed($recipient, $exception);

            return;
        }

        $recipient->forceFill(['email_status' => 'sent', 'email_error' => null, 'emailed_at' => now()])->save();
    }

    /**
     * Runs if the job itself crashes (for example the worker is stopped mid-send).
     */
    public function failed(Throwable $exception): void
    {
        $recipient = AnnouncementRecipient::find($this->recipientId);

        if ($recipient !== null) {
            $this->markFailed($recipient, $exception);
        }
    }

    private function markFailed(AnnouncementRecipient $recipient, Throwable $exception): void
    {
        $recipient->forceFill([
            'email_status' => 'failed',
            'email_error' => Str::limit($exception->getMessage(), 240),
        ])->save();
    }
}
