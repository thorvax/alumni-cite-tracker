<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\GraduateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Graduates log in with the "graduate" guard (Student ID Number + password),
 * separate from admins in the users table. password, activated_at and
 * privacy_consented_at are set with forceFill(), never mass assignment.
 *
 * @property Carbon|null $birth_date
 * @property Carbon|null $invite_sent_at
 * @property Carbon|null $last_reminded_at
 * @property Carbon|null $survey_completed_at
 * @property CarbonImmutable|null $survey_updated_at
 * @property CarbonImmutable|null $activated_at
 * @property CarbonImmutable|null $privacy_consented_at
 * @property CarbonImmutable|null $last_update_reminder_at
 * @property string|null $password
 * @property array<string, array<string, mixed>>|null $survey Answers keyed by step slug.
 */
class Graduate extends Authenticatable
{
    /** @use HasFactory<GraduateFactory> */
    use HasFactory;

    public const STATUSES = ['Employed', 'Self-employed', 'Unemployed', 'Further Studies'];

    /** Statuses that count as "employed" on the dashboard and reports. */
    public const EMPLOYED_STATUSES = ['Employed', 'Self-employed'];

    public const COURSE_CODES = [
        'Associate in Computer Technology' => 'ACT',
        'Bachelor of Library in Information Science' => 'BLIS',
        'Bachelor of Science in Information and Technology' => 'BSIT',
    ];

    protected $fillable = [
        'student_id',
        'full_name',
        'last_name',
        'birth_date',
        'gender',
        'civil_status',
        'email',
        'contact_number',
        'current_address',
        'region',
        'province',
        'city',
        'course',
        'year_graduated',
        'employment_status',
        'survey',
        'survey_response_id',
        'invite_token',
        'invite_status',
        'invite_sent_at',
        'invite_error',
        'reminder_count',
        'last_reminded_at',
        'survey_completed_at',
        'survey_updated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'invite_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'survey' => 'array',
            'invite_sent_at' => 'datetime',
            'last_reminded_at' => 'datetime',
            'survey_completed_at' => 'datetime',
            'survey_updated_at' => 'datetime',
            'activated_at' => 'datetime',
            'privacy_consented_at' => 'datetime',
            'password' => 'hashed',
            'is_demo' => 'boolean',
            'last_update_reminder_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<GraduateLoginCode, $this>
     */
    public function loginCodes(): HasMany
    {
        return $this->hasMany(GraduateLoginCode::class);
    }

    public function isActivated(): bool
    {
        return $this->activated_at !== null;
    }

    /** "dela cruz" matches "Dela  Cruz" (case and extra spaces are ignored). */
    public function hasLastName(string $typed): bool
    {
        $normalize = fn (string $name) => Str::lower(Str::squish($name));

        return $this->last_name !== null && $normalize($typed) === $normalize($this->last_name);
    }

    /**
     * @return BelongsTo<SurveyResponse, $this>
     */
    public function surveyResponse(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class);
    }

    public function courseCode(): ?string
    {
        if ($this->course === null) {
            return null;
        }

        return self::COURSE_CODES[$this->course] ?? $this->course;
    }

    /**
     * @return array{id: int, name: string, course: string|null, year: int|null, status: string|null}
     */
    public function toRow(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->full_name,
            'course' => $this->courseCode(),
            'year' => $this->year_graduated,
            // People who were invited but have not answered the survey yet.
            'status' => $this->employment_status ?? ($this->invite_token !== null ? 'Awaiting survey' : null),
        ];
    }

    /**
     * answered, no_email, or the email's state: pending, queued, sent, failed.
     */
    public function invitationStatus(): string
    {
        if ($this->survey_completed_at !== null) {
            return 'answered';
        }

        if (blank($this->email)) {
            return 'no_email';
        }

        return $this->invite_status ?: 'pending';
    }

    /**
     * @return array<string, mixed>
     */
    public function toInvitationRow(): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'last_name' => $this->last_name ?? $this->full_name,
            'email' => $this->email,
            'status' => $this->invitationStatus(),
            'error' => $this->invite_error,
            'sent_at' => $this->invite_sent_at?->format('M j, Y g:i A'),
            'reminders' => $this->reminder_count,
        ];
    }

    /**
     * People who were uploaded and sent (or will be sent) a survey link.
     *
     * @param  Builder<Graduate>  $query
     * @return Builder<Graduate>
     */
    public function scopeInvited(Builder $query): Builder
    {
        return $query->whereNotNull('invite_token');
    }

    /**
     * @param  Builder<Graduate>  $query
     * @return Builder<Graduate>
     */
    public function scopeWithEmail(Builder $query): Builder
    {
        return $query->whereNotNull('email')->where('email', '!=', '');
    }

    /**
     * @param  Builder<Graduate>  $query
     * @return Builder<Graduate>
     */
    public function scopeWithoutEmail(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('email')->orWhere('email', ''));
    }

    /**
     * Matches the name, course (full name or code like BSIT), year or status.
     *
     * @param  Builder<Graduate>  $query
     * @return Builder<Graduate>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        $courses = collect(self::COURSE_CODES)
            ->filter(fn (string $code) => str_contains(strtolower($code), strtolower($term)))
            ->keys()
            ->all();

        return $query->where(function (Builder $q) use ($like, $courses) {
            $q->where('full_name', 'like', $like)
                ->orWhere('course', 'like', $like)
                ->orWhere('employment_status', 'like', $like)
                ->orWhere('year_graduated', 'like', $like)
                ->orWhereIn('course', $courses);
        });
    }

    /**
     * Builds a graduate record from a completed survey.
     *
     * @param  array<string, array<string, mixed>>  $answers  Answers keyed by step slug.
     */
    public static function fromSurvey(array $answers, SurveyResponse $response, ?self $existing = null): self
    {
        $general = $answers['general-information'] ?? [];
        $education = $answers['educational-background'] ?? [];

        $name = collect([$general['first_name'] ?? null, $general['middle_name'] ?? null, $general['last_name'] ?? null])
            ->filter()
            ->implode(' ');

        $address = collect([$general['house_no'] ?? null, $general['barangay'] ?? null, $general['city'] ?? null, $general['province'] ?? null])
            ->filter()
            ->implode(', ');

        $attributes = [
            'full_name' => $name,
            'last_name' => $general['last_name'] ?? null,
            'email' => $general['email'] ?? null,
            'birth_date' => $general['birth_date'] ?? null,
            'gender' => isset($general['sex']) ? ucfirst((string) $general['sex']) : null,
            'civil_status' => $general['civil_status'] ?? null,
            'contact_number' => $general['mobile_primary'] ?? null,
            'current_address' => $address !== '' ? $address : null,
            'region' => $general['region'] ?? null,
            'province' => $general['province'] ?? null,
            'city' => $general['city'] ?? null,
            'course' => $education['course'] ?? null,
            'year_graduated' => $education['year_graduated'] ?? null,
            'employment_status' => self::statusFromSurvey($answers),
            'survey' => $answers,
            'survey_response_id' => $response->id,
            'survey_completed_at' => now(),
        ];

        // A logged-in graduate updates the record the upload created. When they
        // answered before, keep the first date and record this as an update.
        if ($existing !== null) {
            if ($existing->survey_completed_at !== null) {
                $attributes['survey_completed_at'] = $existing->survey_completed_at;
                $attributes['survey_updated_at'] = now();
            }

            $existing->update($attributes);

            return $existing;
        }

        return self::create($attributes);
    }

    /**
     * @param  array<string, array<string, mixed>>  $answers
     */
    public static function statusFromSurvey(array $answers): string
    {
        $employment = $answers['employment-data'] ?? [];

        if (($employment['employed'] ?? null) === 'yes') {
            $selfEmployed = ($employment['employment_status'] ?? null) === 'self_employed'
                || ($employment['employment_sector'] ?? null) === 'entrepreneurial';

            return $selfEmployed ? 'Self-employed' : 'Employed';
        }

        $reasons = (array) ($employment['not_employed_reasons'] ?? []);

        return in_array('advance_study', $reasons, true) ? 'Further Studies' : 'Unemployed';
    }
}
