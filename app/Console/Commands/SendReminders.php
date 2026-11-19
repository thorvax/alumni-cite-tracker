<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Services\InvitationService;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'cite:send-reminders {--force : Ignore the on/off switch and the time of day}';

    protected $description = 'Email survey reminders that are due under the admin schedule';

    public function handle(InvitationService $invitations): int
    {
        if (! $this->option('force')) {
            if (! AppSetting::flag('reminder_enabled', false)) {
                return self::SUCCESS;
            }

            // The scheduler runs this hourly; only act in the hour the admin picked.
            if ((int) now()->format('G') !== (int) AppSetting::value('reminder_hour', 8)) {
                return self::SUCCESS;
            }
        }

        $count = $invitations->queueMany($invitations->dueForReminder(), reminder: true);

        $this->info("Queued {$count} reminder(s).");

        return self::SUCCESS;
    }
}
