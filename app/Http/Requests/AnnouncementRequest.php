<?php

namespace App\Http\Requests;

use App\Models\Announcement;
use App\Support\Announcements\AudienceResolver;
use App\Support\Import\StudentSheetValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create/edit an announcement. A sent one only accepts title, message and
 * "show until" (typo fixes and a longer display time; the email is not sent again).
 */
class AnnouncementRequest extends FormRequest
{
    /** Most Student ID Numbers one announcement can list. */
    public const MAX_STUDENT_IDS = 2000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $basics = [
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
            'show_until' => ['nullable', 'date_format:Y-m-d'],
        ];

        if ($this->isSentAlready()) {
            return $basics;
        }

        return [
            ...$basics,
            'type' => ['required', Rule::in(Announcement::TYPES)],
            'event_starts_at' => ['nullable', 'required_if:type,event', 'date'],
            'event_place' => ['nullable', 'required_if:type,event', 'string', 'max:200'],
            'event_link' => ['nullable', 'url:http,https', 'max:255'],
            ...self::audienceRules(),
            // Only "All alumni" may be public; a targeted audience stays private to its recipients.
            'visibility' => ['required', Rule::in($this->input('audience_type') === 'all' ? Announcement::VISIBILITIES : ['selected'])],
            'send_email' => ['boolean'],
            'action' => ['required', Rule::in(['draft', 'send', 'schedule'])],
            'publish_at' => ['nullable', 'required_if:action,schedule', 'date', 'after:now'],
        ];
    }

    /**
     * Also used by the "This will reach N alumni" preview.
     *
     * @return array<string, mixed>
     */
    public static function audienceRules(): array
    {
        $years = 'between:'.StudentSheetValidator::FIRST_YEAR.','.date('Y');

        return [
            'audience_type' => ['required', Rule::in(Announcement::AUDIENCES)],
            'student_ids' => ['nullable', 'required_if:audience_type,student_ids', 'string', 'max:40000'],
            'year_from' => ['nullable', 'required_if:audience_type,year', 'integer', $years],
            'year_to' => ['nullable', 'integer', $years],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->isSentAlready() || $this->input('audience_type') !== 'student_ids' || $validator->errors()->has('student_ids')) {
                    return;
                }

                $ids = AudienceResolver::parseStudentIds((string) $this->input('student_ids'));
                $invalid = array_values(array_filter($ids, fn (string $id) => ! preg_match(StudentSheetValidator::ID_PATTERN, $id)));

                if ($ids === []) {
                    $validator->errors()->add('student_ids', 'Paste at least one Student ID Number.');
                } elseif ($invalid !== []) {
                    $validator->errors()->add('student_ids', 'Student ID Numbers must be 8 digits. Check: '.implode(', ', array_slice($invalid, 0, 10)).(count($invalid) > 10 ? ' ...' : ''));
                } elseif (count($ids) > self::MAX_STUDENT_IDS) {
                    $validator->errors()->add('student_ids', 'Paste at most '.self::MAX_STUDENT_IDS.' Student ID Numbers.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'visibility.in' => 'Only an announcement for all alumni can be public. Announcements for chosen Student IDs or years are shown only to those alumni.',
            'event_starts_at.required_if' => 'Enter the date and time of the event.',
            'event_place.required_if' => 'Enter where the event will be held.',
            'event_link.url' => 'The link must start with http:// or https://.',
            'publish_at.required_if' => 'Choose when to send it.',
            'publish_at.after' => 'The scheduled time must be in the future.',
            'student_ids.required_if' => 'Paste at least one Student ID Number.',
            'year_from.required_if' => 'Enter the graduation year.',
            'year_from.between' => 'Year must be from '.StudentSheetValidator::FIRST_YEAR.' to '.date('Y').'.',
            'year_to.between' => 'Year must be from '.StudentSheetValidator::FIRST_YEAR.' to '.date('Y').'.',
        ];
    }

    /**
     * The validated fields as model attributes (audience saved as JSON; event
     * details dropped for plain announcements).
     *
     * @return array<string, mixed>
     */
    public function announcementAttributes(): array
    {
        $data = $this->validated();

        // "Show until" is a date: it stays visible to the end of that day.
        $showUntil = filled($data['show_until'] ?? null) ? Date::parse($data['show_until'])->endOfDay() : null;

        if ($this->isSentAlready()) {
            return ['title' => $data['title'], 'body' => $data['body'], 'show_until' => $showUntil];
        }

        $isEvent = $data['type'] === 'event';

        return [
            'type' => $data['type'],
            'title' => $data['title'],
            'body' => $data['body'],
            'event_starts_at' => $isEvent ? $data['event_starts_at'] : null,
            'event_place' => $isEvent ? $data['event_place'] : null,
            'event_link' => $isEvent ? ($data['event_link'] ?? null) : null,
            'audience_type' => $data['audience_type'],
            'audience_filter' => self::audienceFilter($data),
            'visibility' => $data['visibility'],
            'send_email' => (bool) ($data['send_email'] ?? false),
            'show_until' => $showUntil,
            'publish_at' => $data['action'] === 'schedule' ? $data['publish_at'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{student_ids?: list<string>, from?: int, to?: int}|null
     */
    public static function audienceFilter(array $data): ?array
    {
        return match ($data['audience_type']) {
            'student_ids' => ['student_ids' => AudienceResolver::parseStudentIds((string) ($data['student_ids'] ?? ''))],
            'year' => [
                'from' => (int) $data['year_from'],
                'to' => (int) ($data['year_to'] ?? $data['year_from']),
            ],
            default => null,
        };
    }

    private function isSentAlready(): bool
    {
        $announcement = $this->route('announcement');

        return $announcement instanceof Announcement && $announcement->isSent();
    }
}
