<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A one-time code emailed to a graduate.
 *
 * @property int $id
 * @property int $graduate_id
 * @property string $code_hash
 * @property string $purpose
 * @property CarbonImmutable $expires_at
 * @property int $attempts
 * @property CarbonImmutable|null $used_at
 * @property CarbonImmutable|null $created_at
 */
class GraduateLoginCode extends Model
{
    public const PURPOSE_ACTIVATE = 'activate';

    public const PURPOSE_RESET = 'reset';

    /** How long a code works, in minutes. */
    public const EXPIRES_MINUTES = 10;

    /** Wrong guesses allowed before the code stops working. */
    public const MAX_ATTEMPTS = 5;

    /** Seconds to wait before asking for a new code. */
    public const RESEND_SECONDS = 60;

    protected $fillable = [
        'graduate_id',
        'code_hash',
        'purpose',
        'expires_at',
        'attempts',
        'used_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Graduate, $this>
     */
    public function graduate(): BelongsTo
    {
        return $this->belongsTo(Graduate::class);
    }

    /** Not used yet, not expired, and not locked by too many wrong guesses. */
    public function isUsable(): bool
    {
        return $this->used_at === null
            && $this->expires_at->isFuture()
            && $this->attempts < self::MAX_ATTEMPTS;
    }
}
