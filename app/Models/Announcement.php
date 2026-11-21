<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * An event or announcement posted by an admin. "public" ones are shown to
 * everyone on /announcements; "selected" ones only to their recipients.
 *
 * @property int $id
 * @property string $type
 * @property string $title
 * @property string $body
 * @property string $audience_type
 * @property array{student_ids?: list<string>, from?: int, to?: int}|null $audience_filter
 * @property string $visibility
 * @property bool $send_email
 * @property string $status
 * @property CarbonImmutable|null $event_starts_at
 * @property CarbonImmutable|null $show_until
 * @property CarbonImmutable|null $publish_at
 * @property CarbonImmutable|null $published_at
 */
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    public const TYPES = ['announcement', 'event'];

    /** all = every graduate, student_ids = a pasted list, year = one year or a range. */
    public const AUDIENCES = ['all', 'student_ids', 'year'];

    /** public = anyone (no login); selected = only the recipients, inside the alumni area. */
    public const VISIBILITIES = ['public', 'selected'];

    public const STATUSES = ['draft', 'scheduled', 'sent'];

    protected $fillable = [
        'type',
        'title',
        'body',
        'event_starts_at',
        'event_place',
        'event_link',
        'audience_type',
        'audience_filter',
        'visibility',
        'send_email',
        'show_until',
        'publish_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'audience_filter' => 'array',
            'send_email' => 'boolean',
            'event_starts_at' => 'datetime',
            'show_until' => 'datetime',
            'publish_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<AnnouncementRecipient, $this>
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(AnnouncementRecipient::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Sent and still showing: not past "show until"; an event with no
     * "show until" hides after the day of the event.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeShowingNow(Builder $query): Builder
    {
        return $query
            ->where('status', 'sent')
            ->where(fn (Builder $q) => $q
                ->where('show_until', '>=', now())
                ->orWhere(fn (Builder $q) => $q
                    ->whereNull('show_until')
                    ->where(fn (Builder $q) => $q
                        ->where('type', '!=', 'event')
                        ->orWhereNull('event_starts_at')
                        ->orWhere('event_starts_at', '>=', today()))));
    }

    /**
     * What anyone can read on /announcements, without logging in.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->showingNow()->where('visibility', 'public');
    }

    /**
     * What a logged-in graduate can read: public ones, plus the ones sent to them.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleTo(Builder $query, Graduate $graduate): Builder
    {
        return $query->showingNow()->where(fn (Builder $q) => $q
            ->where('visibility', 'public')
            ->orWhereHas('recipients', fn (Builder $r) => $r->where('graduate_id', $graduate->id)));
    }

    public function isEvent(): bool
    {
        return $this->type === 'event';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Opened by a graduate: marks it read for them (only if it was sent to them).
     */
    public function markReadBy(Graduate $graduate): void
    {
        $this->recipients()
            ->where('graduate_id', $graduate->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * What the alumni and public pages show (no audience or email details).
     *
     * @return array{id: int, type: string, title: string, body: string, excerpt: string, date: string|null, event_date: string|null, event_place: string|null, event_link: string|null}
     */
    public function toReaderArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'excerpt' => Str::limit(Str::squish($this->body), 180),
            'date' => $this->published_at?->format('M j, Y'),
            'event_date' => $this->event_starts_at?->format('l, M j, Y \a\t g:i A'),
            'event_place' => $this->event_place,
            'event_link' => $this->event_link,
        ];
    }

    /** "All alumni", "12 Student IDs", "Class of 2020" or "Classes 2019 to 2023". */
    public function audienceLabel(): string
    {
        $filter = $this->audience_filter ?? [];

        return match ($this->audience_type) {
            'student_ids' => count($filter['student_ids'] ?? []).' Student ID'.(count($filter['student_ids'] ?? []) === 1 ? '' : 's'),
            'year' => ($filter['from'] ?? null) === ($filter['to'] ?? null)
                ? 'Class of '.($filter['from'] ?? '?')
                : 'Classes '.min($filter['from'] ?? 0, $filter['to'] ?? 0).' to '.max($filter['from'] ?? 0, $filter['to'] ?? 0),
            default => 'All alumni',
        };
    }

    /**
     * The values the admin form edits (dates in the format of the date inputs).
     *
     * @return array<string, mixed>
     */
    public function toFormData(): array
    {
        $filter = $this->audience_filter ?? [];

        return [
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,
            'event_starts_at' => $this->event_starts_at?->format('Y-m-d\TH:i') ?? '',
            'event_place' => $this->event_place ?? '',
            'event_link' => $this->event_link ?? '',
            'audience_type' => $this->audience_type,
            'student_ids' => implode("\n", $filter['student_ids'] ?? []),
            'year_from' => (string) ($filter['from'] ?? ''),
            'year_to' => (string) ($filter['to'] ?? ''),
            'visibility' => $this->visibility,
            'send_email' => $this->send_email,
            'show_until' => $this->show_until?->format('Y-m-d') ?? '',
            'publish_at' => $this->publish_at?->format('Y-m-d\TH:i') ?? '',
        ];
    }
}
