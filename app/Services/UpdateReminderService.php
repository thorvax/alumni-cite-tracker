<?php

namespace App\Services;

use App\Jobs\SendUpdateReminder;
use App\Models\AppSetting;
use App\Models\Graduate;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Please update your answers" emails, so graduate data stays current.
 *
 * Settings (admin Settings page):
 *  - update_reminder_enabled: automatic reminders on/off (off by default)
 *  - update_reminder_months: every 6 or 12 months
 *  - update_reminder_target: "stale" = only alumni who have not updated in that
 *    time, "all" = every alumni who answered, once per period
 * Sent by `cite:send-reminders` at the same hour as the survey reminders.
 */
class UpdateReminderService
{
    public const MONTH_OPTIONS = [6, 12];

    public const TARGET_STALE = 'stale';

    public const TARGET_ALL = 'all';

    public const TARGETS = [self::TARGET_STALE, self::TARGET_ALL];

    public static function months(): int
    {
        $months = (int) AppSetting::value('update_reminder_months', 6);

        return in_array($months, self::MONTH_OPTIONS, true) ? $months : 6;
    }

    public static function target(): string
    {
        $target = AppSetting::value('update_reminder_target', self::TARGET_STALE);

        return in_array($target, self::TARGETS, true) ? $target : self::TARGET_STALE;
    }

    /**
     * Who gets a reminder now: can be reminded, was not reminded within the
     * period, and (for "stale") has not updated within the period either.
     *
     * @return Builder<Graduate>
     */
    public function due(): Builder
    {
        $cutoff = now()->subMonths(self::months());

        $query = $this->remindable()
            ->where(fn (Builder $q) => $q
                ->where('last_update_reminder_at', '<=', $cutoff)
                // Never reminded: wait one period after they first answered.
                ->orWhere(fn (Builder $q) => $q->whereNull('last_update_reminder_at')->where('survey_completed_at', '<=', $cutoff)));

        return self::target() === self::TARGET_ALL ? $query : self::needsUpdate($query);
    }

    /**
     * Answered, has an email and a personal link, and is not demo data.
     *
     * @return Builder<Graduate>
     */
    public function remindable(): Builder
    {
        return Graduate::query()
            ->where('is_demo', false) // never email the fake demo addresses
            ->whereNotNull('invite_token')
            ->whereNotNull('survey_completed_at')
            ->withEmail();
    }

    /**
     * Last answer (update, or first submit) is older than the period.
     *
     * @param  Builder<Graduate>  $query
     * @return Builder<Graduate>
     */
    public static function needsUpdate(Builder $query): Builder
    {
        $cutoff = now()->subMonths(self::months());

        return $query->where(fn (Builder $q) => $q
            ->where('survey_updated_at', '<=', $cutoff)
            ->orWhere(fn (Builder $q) => $q->whereNull('survey_updated_at')->where('survey_completed_at', '<=', $cutoff)));
    }

    /**
     * Last answer is within the period.
     *
     * @param  Builder<Graduate>  $query
     * @return Builder<Graduate>
     */
    public static function upToDate(Builder $query): Builder
    {
        $cutoff = now()->subMonths(self::months());

        return $query->where(fn (Builder $q) => $q
            ->where('survey_updated_at', '>', $cutoff)
            ->orWhere(fn (Builder $q) => $q->whereNull('survey_updated_at')->where('survey_completed_at', '>', $cutoff)));
    }

    /**
     * Queues one email per graduate who is due. The reminder time is saved
     * right away so the next hourly run never queues the same person twice.
     */
    public function queueDue(): int
    {
        $count = 0;

        $this->due()->each(function (Graduate $graduate) use (&$count) {
            $this->queue($graduate, $count * InvitationService::STAGGER_SECONDS);
            $count++;
        });

        return $count;
    }

    /**
     * Can this graduate be sent an update reminder at all (by hand or on schedule)?
     */
    public function canRemind(Graduate $graduate): bool
    {
        return ! $graduate->is_demo
            && $graduate->survey_completed_at !== null
            && $graduate->invite_token !== null
            && filled($graduate->email);
    }

    /**
     * Queues one update reminder now and records the time, so the automatic
     * schedule waits a full period before reminding them again.
     */
    public function queue(Graduate $graduate, int $delaySeconds = 0): void
    {
        $graduate->forceFill(['last_update_reminder_at' => now()])->save();

        SendUpdateReminder::dispatch($graduate->id)->delay(now()->addSeconds($delaySeconds));
    }
}
