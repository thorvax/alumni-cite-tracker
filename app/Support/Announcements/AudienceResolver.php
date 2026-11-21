<?php

namespace App\Support\Announcements;

use App\Models\Announcement;
use App\Models\Graduate;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

/**
 * Turns an announcement's audience (all / Student IDs / graduation year)
 * into the list of graduates it reaches. Demo graduates are always left out.
 * "All" means every graduate on record, activated or not.
 */
class AudienceResolver
{
    public function forAnnouncement(Announcement $announcement): Audience
    {
        return $this->resolve($announcement->audience_type, $announcement->audience_filter ?? []);
    }

    /**
     * @param  array{student_ids?: list<string>, from?: int|string, to?: int|string|null}  $filter
     */
    public function resolve(string $type, array $filter): Audience
    {
        return match ($type) {
            'all' => new Audience($this->graduates()->get()),
            'student_ids' => $this->byStudentIds($filter['student_ids'] ?? []),
            'year' => $this->byYears((int) ($filter['from'] ?? 0), (int) ($filter['to'] ?? $filter['from'] ?? 0)),
            default => throw new InvalidArgumentException("Unknown audience type [{$type}]."),
        };
    }

    /**
     * Splits a pasted list on commas, spaces, semicolons and new lines, and
     * drops empty entries and duplicates (order kept).
     *
     * @return list<string>
     */
    public static function parseStudentIds(string $pasted): array
    {
        $parts = preg_split('/[\s,;]+/', $pasted, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique($parts));
    }

    /**
     * @param  list<string>  $studentIds
     */
    private function byStudentIds(array $studentIds): Audience
    {
        $graduates = $this->graduates()->whereIn('student_id', $studentIds)->get();

        $found = $graduates->pluck('student_id')->all();

        return new Audience(
            $graduates,
            unknownIds: array_values(array_diff($studentIds, $found)),
        );
    }

    /** One year (from = to) or a range; the order of the two years does not matter. */
    private function byYears(int $from, int $to): Audience
    {
        return new Audience(
            $this->graduates()->whereBetween('year_graduated', [min($from, $to), max($from, $to)])->get(),
            withoutYear: $this->graduates()->whereNull('year_graduated')->count(),
        );
    }

    /**
     * @return Builder<Graduate>
     */
    private function graduates(): Builder
    {
        return Graduate::query()->where('is_demo', false)->orderBy('id');
    }
}
