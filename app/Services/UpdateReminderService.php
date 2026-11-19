<?php

namespace App\Services;

use App\Jobs\SendUpdateReminder;
use App\Models\AppSetting;
use App\Models\Graduate;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Please update your answers" emails, so graduate data stays current.
 *
 * Settings (admin Settings page): update_reminder_enabled (off by default)
 * and update_reminder_months (6 or 12). Sent by `cite:send-reminders` at the
 * same hour as the survey reminders (reminder_hour).
 */
class UpdateReminderService
{
    public const MONTH_OPTIONS = [6, 12];

    public static function months(): int
    {
        $months = (int) AppSetting::value('update_reminder_months', 6);

        return in_array($months, self::MONTH_OPTIONS, true) ? $months : 6;
    }

    /**
     * Answered, has an email and a personal link, and their last answer AND
     * their last update reminder are both older than the chosen months.
     *
     * @return Builder<Graduate>
     */
    public function due(): Builder
    {
        $cutoff = now()->subMonths(self::months());

        return Graduate::query()
            ->where('is_demo', false) // never email the fake demo addresses
            ->whereNotNull('invite_token')
            ->whereNotNull('survey_completed_at')
            ->withEmail()
            ->where(fn (Builder $q) => $q
                ->where('survey_updated_at', '<=', $cutoff)
                ->orWhere(fn (Builder $q) => $q->whereNull('survey_updated_at')->where('survey_completed_at', '<=', $cutoff)))
            ->where(fn (Builder $q) => $q
                ->whereNull('last_update_reminder_at')
                ->orWhere('last_update_reminder_at', '<=', $cutoff));
    }

    /**
     * Queues one email per graduate who is due. The reminder time is saved
     * right away so the next hourly run never queues the same person twice.
     */
    public function queueDue(): int
    {
        $count = 0;

        $this->due()->each(function (Graduate $graduate) use (&$count) {
            $graduate->forceFill(['last_update_reminder_at' => now()])->save();

            SendUpdateReminder::dispatch($graduate->id)
                ->delay(now()->addSeconds($count * InvitationService::STAGGER_SECONDS));

            $count++;
        });

        return $count;
    }
}
