<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One graduate an announcement was sent to: its email status and when they read it.
 *
 * @property int $id
 * @property int $announcement_id
 * @property int $graduate_id
 * @property string $email_status
 * @property string|null $email_error
 * @property CarbonImmutable|null $emailed_at
 * @property CarbonImmutable|null $read_at
 */
class AnnouncementRecipient extends Model
{
    /** pending, queued, sent, failed, or skipped (no email address). */
    public const EMAIL_STATUSES = ['pending', 'queued', 'sent', 'failed', 'skipped'];

    protected $fillable = [
        'announcement_id',
        'graduate_id',
        'email_status',
        'email_error',
        'emailed_at',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'emailed_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Announcement, $this>
     */
    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    /**
     * @return BelongsTo<Graduate, $this>
     */
    public function graduate(): BelongsTo
    {
        return $this->belongsTo(Graduate::class);
    }
}
