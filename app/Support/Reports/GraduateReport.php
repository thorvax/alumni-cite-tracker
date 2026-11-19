<?php

namespace App\Support\Reports;

use App\Models\Graduate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Numbers for the admin Reports page and its CSV export.
 *
 * Every chart uses the same Course / Year filters. Counting is done with
 * database queries (GROUP BY), reading single answers straight out of the
 * graduates.survey JSON, so the table is never loaded into PHP.
 *
 * Shapes returned (so the page can draw any of them):
 *   item      = ['label' => string, 'count' => int, 'percent' => int]
 *   breakdown = ['total' => int, 'items' => item[]]
 *
 * Answer keys come from SurveyController / resources/js/lib/survey-options.ts.
 */
class GraduateReport
{
    /** Order of the "time to land the first job" choices (survey-options.ts `timeToLand`). */
    public const TIME_TO_LAND = [
        'Less than a month',
        '1 to 6 months',
        '7 to 11 months',
        '1 year to less than 2 years',
        '2 years to less than 3 years',
        '3 years to less than 4 years',
    ];

    /**
     * @param  string|null  $course  Full course name (see Graduate::COURSE_CODES), or null for all.
     */
    public function __construct(
        private ?string $course = null,
        private ?int $year = null,
    ) {}

    /**
     * Everyone that matches the filters.
     *
     * @return Builder<Graduate>
     */
    private function graduates(): Builder
    {
        return Graduate::query()
            ->when($this->course !== null, fn (Builder $q) => $q->where('course', $this->course))
            ->when($this->year !== null, fn (Builder $q) => $q->where('year_graduated', $this->year));
    }

    /**
     * Only people who completed the survey (for answer-based charts).
     *
     * @return Builder<Graduate>
     */
    private function respondents(): Builder
    {
        return $this->graduates()->whereNotNull('survey_completed_at')->whereNotNull('survey');
    }

    /**
     * 1. Employment status (donut) and the employment rate.
     *
     * @return array{total: int, rate: array{count: int, percent: int}, items: list<array{label: string, count: int, percent: int}>}
     */
    public function employmentStatus(): array
    {
        $counts = $this->graduates()
            ->whereIn('employment_status', Graduate::STATUSES)
            ->groupBy('employment_status')
            ->selectRaw('employment_status, count(*) as aggregate')
            ->pluck('aggregate', 'employment_status')
            ->map(fn ($count) => (int) $count);

        $total = (int) $counts->sum();
        $employed = (int) $counts->only(Graduate::EMPLOYED_STATUSES)->sum();

        return [
            'total' => $total,
            'rate' => ['count' => $employed, 'percent' => self::percent($employed, $total)],
            'items' => self::items(collect(Graduate::STATUSES)->mapWithKeys(fn ($s) => [$s => $counts->get($s, 0)]), $total),
        ];
    }

    /**
     * 2. Employment status per course (stacked bar).
     *
     * @return list<array{label: string, total: int, items: list<array{label: string, count: int, percent: int}>}>
     */
    public function employmentByCourse(): array
    {
        $rows = $this->graduates()
            ->whereNotNull('course')
            ->whereIn('employment_status', Graduate::STATUSES)
            ->groupBy('course', 'employment_status')
            ->selectRaw('course, employment_status, count(*) as aggregate')
            ->toBase()
            ->get();

        return array_values($rows->groupBy('course')
            ->map(function (Collection $group, string $course) {
                $counts = $group->pluck('aggregate', 'employment_status')->map(fn ($count) => (int) $count);
                $total = (int) $counts->sum();

                return [
                    'label' => Graduate::COURSE_CODES[$course] ?? $course,
                    'total' => $total,
                    'items' => self::items(collect(Graduate::STATUSES)->mapWithKeys(fn ($s) => [$s => $counts->get($s, 0)]), $total),
                ];
            })
            ->sortBy('label')
            ->all());
    }

    /**
     * 3. Employment rate per year graduated (trend).
     *
     * @return list<array{year: int, total: int, employed: int, percent: int}>
     */
    public function employmentByYear(): array
    {
        return array_values($this->graduates()
            ->whereNotNull('year_graduated')
            ->whereIn('employment_status', Graduate::STATUSES)
            ->groupBy('year_graduated')
            ->orderBy('year_graduated')
            ->selectRaw('year_graduated, count(*) as total')
            ->selectRaw('sum(case when employment_status in (?, ?) then 1 else 0 end) as employed', Graduate::EMPLOYED_STATUSES)
            ->toBase()
            ->get()
            ->map(fn ($row) => [
                'year' => (int) $row->year_graduated,
                'total' => (int) $row->total,
                'employed' => (int) $row->employed,
                'percent' => self::percent((int) $row->employed, (int) $row->total),
            ])
            ->all());
    }

    /**
     * 4. Is the job related to the course? Yes / No per course.
     * First job: first_related. Current (not first) job: current_related.
     *
     * @return array{overall: array{total: int, items: list<array{label: string, count: int, percent: int}>}, courses: list<array{label: string, total: int, items: list<array{label: string, count: int, percent: int}>}>}
     */
    public function jobRelated(): array
    {
        $rows = $this->respondents()
            ->select([
                'course',
                self::answer('first-current-job', 'first_related').' as first_related',
                self::answer('first-current-job', 'current_related').' as current_related',
            ])
            ->selectRaw('count(*) as aggregate')
            ->groupBy('course', self::answer('first-current-job', 'first_related'), self::answer('first-current-job', 'current_related'))
            ->toBase()
            ->get()
            ->map(fn ($row) => [
                'course' => Graduate::COURSE_CODES[$row->course] ?? ($row->course ?? 'Unknown'),
                'related' => self::clean($row->first_related) ?? self::clean($row->current_related),
                'count' => (int) $row->aggregate,
            ])
            ->filter(fn (array $row) => in_array($row['related'], ['yes', 'no'], true));

        $yesNo = function (Collection $group): array {
            $counts = $group->groupBy('related')->map(fn (Collection $g) => (int) $g->sum('count'));
            $total = (int) $counts->sum();

            return [
                'total' => $total,
                'items' => self::items(collect(['Yes' => $counts->get('yes', 0), 'No' => $counts->get('no', 0)]), $total),
            ];
        };

        return [
            'overall' => $yesNo($rows),
            'courses' => array_values($rows->groupBy('course')
                ->map(fn (Collection $group, string $course) => ['label' => $course] + $yesNo($group))
                ->sortBy('label')
                ->all()),
        ];
    }

    /**
     * 5. Time to land the first job (only asked when it is their first job).
     *
     * @return array{total: int, items: list<array{label: string, count: int, percent: int}>}
     */
    public function timeToLand(): array
    {
        return $this->inOrder('first-current-job', 'first_time_to_land', self::TIME_TO_LAND);
    }

    /**
     * 6. Sector of the present job, and local vs abroad (employed respondents).
     *
     * @return array{sector: array{total: int, items: list<array{label: string, count: int, percent: int}>}, place: array{total: int, items: list<array{label: string, count: int, percent: int}>}}
     */
    public function sectorAndPlace(): array
    {
        return [
            'sector' => $this->labeled('employment-data', 'employment_sector', SurveyLabels::SECTORS),
            'place' => $this->labeled('employment-data', 'place_of_work', SurveyLabels::PLACE_OF_WORK),
        ];
    }

    /**
     * 7. First job earning range. Grouped counts only, never per person.
     *
     * @return array{total: int, items: list<array{label: string, count: int, percent: int}>}
     */
    public function firstJobEarning(): array
    {
        return $this->inOrder('first-current-job', 'first_earning', SurveyLabels::EARNINGS);
    }

    /**
     * 8. How graduates found their first job (most common first).
     *
     * @return array{total: int, items: list<array{label: string, count: int, percent: int}>}
     */
    public function howFound(): array
    {
        return self::ranked($this->inOrder('first-current-job', 'first_how_found', SurveyLabels::HOW_FOUND));
    }

    /**
     * 9. Competencies used on the job (can pick more than one; most common first).
     *
     * @return array{total: int, items: list<array{label: string, count: int, percent: int}>}
     */
    public function competencies(): array
    {
        return $this->multiChoice('first-current-job', 'competencies', SurveyLabels::COMPETENCIES);
    }

    /**
     * 10. Was the curriculum relevant to the first job? Plus the latest suggestions.
     *
     * @return array{relevant: array{total: int, items: list<array{label: string, count: int, percent: int}>}, suggestions: array{total: int, latest: list<string>}}
     */
    public function curriculum(): array
    {
        return [
            'relevant' => $this->labeled('first-current-job', 'curriculum_relevant', ['yes' => 'Yes', 'no' => 'No']),
            'suggestions' => $this->latestText('first-current-job', 'curriculum_suggestions'),
        ];
    }

    /**
     * 11. Reasons for not being employed (can pick more than one; most common first).
     *
     * @return array{total: int, items: list<array{label: string, count: int, percent: int}>}
     */
    public function notEmployedReasons(): array
    {
        return $this->multiChoice('employment-data', 'not_employed_reasons', SurveyLabels::NOT_EMPLOYED_REASONS);
    }

    /**
     * 12. Help needed in landing a job: how many answered, and the latest answers.
     *
     * @return array{total: int, latest: list<string>}
     */
    public function helpNeeded(): array
    {
        return $this->latestText('need-help', 'help_needed');
    }

    /**
     * 13. Why they chose the course (undergraduate reasons; can pick more than one).
     *
     * @return array{total: int, items: list<array{label: string, count: int, percent: int}>}
     */
    public function courseReasons(): array
    {
        return $this->multiChoice('educational-background', 'reasons_ug', SurveyLabels::COURSE_REASONS);
    }

    /**
     * 14. Survey progress for uploaded graduates: uploaded -> with email -> email sent -> answered.
     *
     * Bulk upload only has Student ID, Last Name and Email, so the course and
     * year of people who have not answered yet are unknown.
     *
     * @return array{uploaded: int, rate: array{count: int, percent: int}, stages: list<array{label: string, count: int, percent: int}>, answeredByCourse: list<array{label: string, count: int, percent: int}>, answeredByYear: list<array{label: string, count: int, percent: int}>, unknown: int}
     */
    public function responseFunnel(): array
    {
        $row = $this->graduates()
            ->whereNotNull('invite_token')
            ->selectRaw('count(*) as uploaded')
            ->selectRaw("sum(case when email is not null and email <> '' then 1 else 0 end) as with_email")
            ->selectRaw('sum(case when invite_sent_at is not null then 1 else 0 end) as sent')
            ->selectRaw('sum(case when survey_completed_at is not null then 1 else 0 end) as answered')
            ->toBase()
            ->first();

        $uploaded = (int) ($row->uploaded ?? 0);
        $answered = (int) ($row->answered ?? 0);

        $stages = collect([
            'Uploaded' => $uploaded,
            'With email' => (int) ($row->with_email ?? 0),
            'Email sent' => (int) ($row->sent ?? 0),
            'Answered' => $answered,
        ]);

        $answeredBy = function (string $column) {
            $counts = $this->graduates()
                ->whereNotNull('invite_token')
                ->whereNotNull('survey_completed_at')
                ->whereNotNull($column)
                ->groupBy($column)
                ->orderBy($column)
                ->select([$column.' as value'])
                ->selectRaw('count(*) as aggregate')
                ->pluck('aggregate', 'value')
                ->mapWithKeys(fn ($count, $value) => [
                    (string) ($column === 'course' ? (Graduate::COURSE_CODES[$value] ?? $value) : $value) => (int) $count,
                ]);

            return self::items($counts, (int) $counts->sum());
        };

        return [
            'uploaded' => $uploaded,
            'rate' => ['count' => $answered, 'percent' => self::percent($answered, $uploaded)],
            'stages' => $uploaded > 0 ? self::items($stages, $uploaded) : [],
            'answeredByCourse' => $answeredBy('course'),
            'answeredByYear' => $answeredBy('year_graduated'),
            'unknown' => $uploaded - $answered,
        ];
    }

    /**
     * How many respondents gave each answer to one single-choice question.
     *
     * @return Collection<string, int>
     */
    private function countAnswer(string $step, string $key): Collection
    {
        $path = self::answer($step, $key);

        return $this->respondents()
            ->select([$path.' as value'])
            ->selectRaw('count(*) as aggregate')
            ->groupBy($path)
            ->toBase()
            ->get()
            ->mapWithKeys(fn ($row) => [(string) self::clean($row->value) => (int) $row->aggregate])
            ->except('');
    }

    /**
     * A single-choice answer counted in the survey's order. Values typed under
     * "Other" (or anything not in the list) are grouped as Other.
     *
     * @param  list<string>  $order
     * @return array{total: int, items: list<array{label: string, count: int, percent: int}>}
     */
    private function inOrder(string $step, string $key, array $order): array
    {
        $counts = $this->countAnswer($step, $key);

        $ordered = collect($order)->mapWithKeys(fn (string $label) => [$label => $counts->get($label, 0)]);
        $other = (int) $counts->except($order)->sum();

        if ($other > 0) {
            $ordered->put('Other', $other);
        }

        $total = (int) $ordered->sum();

        return ['total' => $total, 'items' => $total > 0 ? self::items($ordered, $total) : []];
    }

    /**
     * A single-choice answer saved as a key (e.g. "government"), shown with its label.
     *
     * @param  array<string, string>  $labels  key => label
     * @return array{total: int, items: list<array{label: string, count: int, percent: int}>}
     */
    private function labeled(string $step, string $key, array $labels): array
    {
        $counts = $this->countAnswer($step, $key);

        $named = collect($labels)->mapWithKeys(fn (string $label, string $value) => [$label => $counts->get($value, 0)]);
        $other = (int) $counts->except(array_keys($labels))->sum();

        if ($other > 0) {
            $named->put('Other', (int) $named->get('Other', 0) + $other);
        }

        $total = (int) $named->sum();

        return ['total' => $total, 'items' => $total > 0 ? self::items($named, $total) : []];
    }

    /**
     * A "tick all that apply" answer. The total is the number of respondents who
     * ticked at least one, so percentages can add up to more than 100%.
     * Each choice is one COUNT query using the database's JSON functions.
     *
     * @param  array<string, string>  $labels  key => label
     * @return array{total: int, items: list<array{label: string, count: int, percent: int}>}
     */
    private function multiChoice(string $step, string $key, array $labels): array
    {
        $path = self::answer($step, $key);
        $total = $this->respondents()->whereJsonLength($path, '>', 0)->count();

        if ($total === 0) {
            return ['total' => 0, 'items' => []];
        }

        $counts = collect($labels)
            ->mapWithKeys(fn (string $label, string $value) => [
                $label => $this->respondents()->whereJsonContains($path, $value)->count(),
            ])
            ->filter()
            ->sortDesc();

        return ['total' => $total, 'items' => self::items($counts, $total)];
    }

    /**
     * How many respondents wrote something, and the latest answers (text only,
     * no names or other details about who wrote them).
     *
     * @return array{total: int, latest: list<string>}
     */
    private function latestText(string $step, string $key, int $limit = 10): array
    {
        $path = self::answer($step, $key);

        $answered = fn (): Builder => $this->respondents()
            ->whereNotNull($path)
            ->where($path, '<>', '')
            ->where($path, '<>', 'null'); // MySQL reads a JSON null as the text "null"

        return [
            'total' => $answered()->count(),
            'latest' => array_values($answered()
                ->orderByDesc('survey_completed_at')
                ->limit($limit)
                ->select([$path.' as text'])
                ->toBase()
                ->pluck('text')
                ->map(fn ($text) => trim((string) $text))
                ->all()),
        ];
    }

    /**
     * Most common first (used for "how found").
     *
     * @param  array{total: int, items: list<array{label: string, count: int, percent: int}>}  $breakdown
     * @return array{total: int, items: list<array{label: string, count: int, percent: int}>}
     */
    private static function ranked(array $breakdown): array
    {
        $items = array_filter($breakdown['items'], fn (array $item) => $item['count'] > 0);
        usort($items, fn (array $a, array $b) => $b['count'] <=> $a['count']);

        return ['total' => $breakdown['total'], 'items' => $items];
    }

    /** "survey->first-current-job->first_related": Laravel turns this into a JSON lookup for MySQL and SQLite. */
    private static function answer(string $step, string $key): string
    {
        return "survey->{$step}->{$key}";
    }

    /** MySQL returns the text "null" for a JSON null; treat it (and blanks) as no answer. */
    private static function clean(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 'null') {
            return null;
        }

        return is_scalar($value) ? (string) $value : null;
    }

    /** Never divides by zero. */
    public static function percent(int $count, int $total): int
    {
        return $total > 0 ? (int) round($count / $total * 100) : 0;
    }

    /**
     * @template TKey of array-key
     * @template TCount of int
     *
     * @param  Collection<TKey, TCount>  $counts  label => count
     * @return list<array{label: string, count: int, percent: int}>
     */
    private static function items(Collection $counts, int $total): array
    {
        return array_values($counts
            ->map(fn ($count, $label) => [
                'label' => (string) $label,
                'count' => (int) $count,
                'percent' => self::percent((int) $count, $total),
            ])
            ->all());
    }
}
