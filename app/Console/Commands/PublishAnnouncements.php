<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Console\Command;

/**
 * Runs every 15 minutes (routes/console.php): sends scheduled announcements
 * whose time has come, the same way as "Send now".
 */
class PublishAnnouncements extends Command
{
    protected $signature = 'cite:publish-announcements';

    protected $description = 'Send scheduled announcements whose time has come';

    public function handle(AnnouncementService $announcements): int
    {
        $due = Announcement::query()
            ->where('status', 'scheduled')
            ->where('publish_at', '<=', now())
            ->orderBy('publish_at')
            ->get();

        foreach ($due as $announcement) {
            $emails = $announcements->publish($announcement);
            $this->info("Sent \"{$announcement->title}\" (queued {$emails} email(s)).");
        }

        if ($due->isEmpty()) {
            $this->info('No scheduled announcements are due.');
        }

        return self::SUCCESS;
    }
}
