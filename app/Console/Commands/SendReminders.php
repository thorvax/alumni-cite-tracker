<?php

namespace App\Console\Commands;

use App\Models\AppSetting;
use App\Services\InvitationService;
use App\Services\UpdateReminderService;
use Illuminate\Console\Command;

/**
 * Runs hourly (routes/console.php). At the admin's chosen hour it emails:
 *  - survey reminders to people who have not answered yet (Bulk Upload page),
 *  - "please update your answers" reminders every 6 or 12 months (Settings page).
 */
class SendReminders extends Command
{
    protected $signature = 'cite:send-reminders {--force : Ignore the on/off switches and the time of day}';

    protected $description = 'Email survey reminders and update reminders that are due';

    public function handle(InvitationService $invitations, UpdateReminderService $updates): int
    {
        $force = (bool) $this->option('force');

        // The scheduler runs this hourly; only act in the hour the admin picked.
        if (! $force && (int) now()->format('G') !== (int) AppSetting::value('reminder_hour', 8)) {
            return self::SUCCESS;
        }

        if ($force || AppSetting::flag('reminder_enabled', false)) {
            $count = $invitations->queueMany($invitations->dueForReminder(), reminder: true);
            $this->info("Queued {$count} survey reminder(s).");
        }

        if ($force || AppSetting::flag('update_reminder_enabled', false)) {
            $count = $updates->queueDue();
            $this->info("Queued {$count} update reminder(s).");
        }

        return self::SUCCESS;
    }
}
