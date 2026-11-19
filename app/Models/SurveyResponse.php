<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One survey submission (first answer or an update), kept for the update history.
 *
 * @property int $id
 * @property int|null $graduate_id
 * @property array<string, array<string, mixed>> $data Answers keyed by step slug.
 * @property Carbon|null $submitted_at
 */
class SurveyResponse extends Model
{
    protected $fillable = [
        'graduate_id',
        'data',
        'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Graduate, $this>
     */
    public function graduate(): BelongsTo
    {
        return $this->belongsTo(Graduate::class);
    }
}
