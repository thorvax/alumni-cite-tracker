<?php

namespace App\Services;

use App\Jobs\SendUpdateReminder;
use App\Models\AppSetting;
use App\Models\Graduate;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Please update your answers" emails, so graduate data stays current.
 *
 * Automatic (admin Settings, sent by `cite:send-reminders` at the survey
 * reminder hour):
 *  - update_reminder_enabled: on/off (off by default)
 *  - update_reminder_months: remind every 3, 6, 9 or 12 months
 *  - update_reminder_target: "all" = everyone who answered (even recently),
 *    "stale" = only alumni who have not updated in the past N months
 *  - update_reminder_stale_months: that N (3, 6, 9 or 12)
 *
 * Manual ("Send update reminders now"): the same two "who" choices, sent at once.
 */
class UpdateReminderService
{
    public const MONTH_OPTIONS = [3, 6, 9, 12];

    public const TARGET_STALE = 'stale';

    public const TARGET_ALL = 'all';

    public const TARGETS = [self::TARGET_STALE, self::TARGET_ALL];

    /** Automatic: remind every N months. */
    public static function months(): int
    {
        return self::option((int) AppSetting::value('update_reminder_months', 6), 6);
    }

    /** Automatic: who to remind. */
    public static function target(): string
    {
        $target = AppSetting::value('update_reminder_target', self::TARGET_STALE);

        return in_array($target, self::TARGETS, true) ? $target : self::TARGET_STALE;
    }

    /**
     * "Not updated in the past N months" (automatic setting). Also decides
     * "Up to date" / "Needs update" on the Update Status page.
     */
    public static function staleMonths(): int
    {
        return self::option((int) AppSetting::value('update_reminder_stale_months', self::months()), self::months());
    }

    private static function option(int $months, int $fallback): int
    {
        return in_array($months, self::MONTH_OPTIONS, true) ? $months : $fallback;
    }

    /**
     * Who a reminder is for: everyone who can be reminded ("all", even if they
     * answered recently), or only those who have not updated in $staleMonths.
     *
     * @return Builder<Graduate>
     */
    public function audience(string $target, int $staleMonths): Builder
    {
        $query = $this->remindable();

        return $target === self::TARGET_ALL ? $query : self::needsUpdate($query, $staleMonths);
    }

    /**
     * Automatic reminders due now: the chosen audience, minus anyone already
     * reminded within the "remind every" period.
     *
     * @return Builder<Graduate>
     */
    public function due(): Builder
    {
        $cutoff = now()->subMonths(self::months());

        return $this->audience(self::target(), self::staleMonths())
            ->where(fn (Builder $q) => $q
                ->whereNull('last_update_reminder_at')
                ->orWhere('last_update_reminder_at', '<=', $cutoff));
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
     * Last answer (update, or first submit) is older than $months (default: staleMonths()).
     *
     * @param  Builder<Graduate>  $query
     * @return Builder<Graduate>
     */
    public static function needsUpdate(Builder $query, ?int $months = null): Builder
    {
        $cutoff = now()->subMonths($months ?? self::staleMonths());

        return $query->where(fn (Builder $q) => $q
            ->where('survey_updated_at', '<=', $cutoff)
            ->orWhere(fn (Builder $q) => $q->whereNull('survey_updated_at')->where('survey_completed_at', '<=', $cutoff)));
    }

    /**
     * Last answer is within $months (default: staleMonths()).
     *
     * @param  Builder<Graduate>  $query
     * @return Builder<Graduate>
     */
    public static function upToDate(Builder $query, ?int $months = null): Builder
    {
        $cutoff = now()->subMonths($months ?? self::staleMonths());

        return $query->where(fn (Builder $q) => $q
            ->where('survey_updated_at', '>', $cutoff)
            ->orWhere(fn (Builder $q) => $q->whereNull('survey_updated_at')->where('survey_completed_at', '>', $cutoff)));
    }

    /** Automatic: queues everyone due now. */
    public function queueDue(): int
    {
        return $this->queueAll($this->due());
    }

    /**
     * Queues one email per graduate in the group, a few seconds apart. The
     * reminder time is saved right away, so nobody is queued twice.
     *
     * @param  Builder<Graduate>  $group
     */
    public function queueAll(Builder $group): int
    {
        $count = 0;

        $group->each(function (Graduate $graduate) use (&$count) {
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
