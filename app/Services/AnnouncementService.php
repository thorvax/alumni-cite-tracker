<?php

namespace App\Services;

use App\Jobs\SendAnnouncementEmail;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\Graduate;
use App\Support\Announcements\AudienceResolver;
use Illuminate\Support\Facades\DB;

/**
 * Publishes an announcement: saves who it was sent to (one row per graduate)
 * and queues the emails, 2 seconds apart like InvitationService.
 */
class AnnouncementService
{
    public function __construct(private readonly AudienceResolver $resolver) {}

    /**
     * Sends a draft or scheduled announcement now. Returns how many emails were queued.
     */
    public function publish(Announcement $announcement): int
    {
        $now = now();

        // Claim it in one query, so the scheduler and a "Send now" click at the
        // same moment cannot both send it (only one of them changes the row).
        $claimed = Announcement::query()
            ->whereKey($announcement->id)
            ->where('status', '!=', 'sent')
            ->update(['status' => 'sent', 'published_at' => $now]);

        if ($claimed === 0) {
            return 0;
        }

        $announcement->forceFill(['status' => 'sent', 'published_at' => $now])->syncOriginal();
        $audience = $this->resolver->forAnnouncement($announcement);

        DB::transaction(function () use ($announcement, $audience, $now) {

            // The list of recipients is fixed now: graduates added later do not get it.
            $rows = $audience->graduates->map(fn (Graduate $graduate) => [
                'announcement_id' => $announcement->id,
                'graduate_id' => $graduate->id,
                // No email address (or email turned off): they only see it on the site.
                'email_status' => $announcement->send_email && filled($graduate->email) ? 'pending' : 'skipped',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($rows->chunk(500) as $chunk) {
                AnnouncementRecipient::query()->insertOrIgnore($chunk->values()->all());
            }
        });

        return $this->queueMany($announcement->recipients()->where('email_status', 'pending')->get());
    }

    public function queue(AnnouncementRecipient $recipient, int $delaySeconds = 0): void
    {
        $recipient->forceFill(['email_status' => 'queued', 'email_error' => null])->save();

        SendAnnouncementEmail::dispatch($recipient->id)->delay(now()->addSeconds($delaySeconds));
    }

    /**
     * @param  iterable<AnnouncementRecipient>  $recipients
     */
    public function queueMany(iterable $recipients): int
    {
        $count = 0;

        foreach ($recipients as $recipient) {
            $this->queue($recipient, $count * InvitationService::STAGGER_SECONDS);
            $count++;
        }

        return $count;
    }

    public function resendFailed(Announcement $announcement): int
    {
        return $this->queueMany($announcement->recipients()->where('email_status', 'failed')->get());
    }
}
