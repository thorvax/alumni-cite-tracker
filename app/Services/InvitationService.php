<?php

namespace App\Services;

use App\Jobs\SendSurveyInvitation;
use App\Models\AppSetting;
use App\Models\Graduate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class InvitationService
{
    /** Seconds between emails, so a large batch does not hit the mail provider's limits. */
    public const STAGGER_SECONDS = 2;

    public function queue(Graduate $graduate, bool $reminder = false, int $delaySeconds = 0): void
    {
        $graduate->forceFill(['invite_status' => 'queued', 'invite_error' => null])->save();

        SendSurveyInvitation::dispatch($graduate->id, $reminder)->delay(now()->addSeconds($delaySeconds));
    }

    /**
     * @param  iterable<Graduate>  $graduates
     */
    public function queueMany(iterable $graduates, bool $reminder = false): int
    {
        $count = 0;

        foreach ($graduates as $graduate) {
            $this->queue($graduate, $reminder, $count * self::STAGGER_SECONDS);
            $count++;
        }

        return $count;
    }

    /**
     * Emailed, not yet answered, and due another reminder under the admin's
     * schedule (every N days, up to a maximum).
     *
     * @return Collection<int, Graduate>
     */
    public function dueForReminder(): Collection
    {
        $cutoff = now()->subDays((int) AppSetting::value('reminder_interval_days', 7));
        $max = (int) AppSetting::value('reminder_max', 3);

        return Graduate::query()
            ->invited()
            ->withEmail()
            ->whereNull('survey_completed_at')
            ->where('invite_status', 'sent')
            ->where('reminder_count', '<', $max)
            ->where(function (Builder $query) use ($cutoff) {
                $query->where('last_reminded_at', '<=', $cutoff)
                    ->orWhere(fn (Builder $q) => $q->whereNull('last_reminded_at')->where('invite_sent_at', '<=', $cutoff));
            })
            ->get();
    }
}
