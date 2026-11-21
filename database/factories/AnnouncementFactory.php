<?php

namespace Database\Factories;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    /**
     * A draft announcement for everyone, shown only to its recipients.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 'announcement',
            'title' => fake()->sentence(5),
            'body' => fake()->paragraph(),
            'audience_type' => 'all',
            'audience_filter' => null,
            'visibility' => 'selected',
            'send_email' => true,
            'status' => 'draft',
        ];
    }

    public function event(): static
    {
        return $this->state(fn () => [
            'type' => 'event',
            'event_starts_at' => now()->addWeeks(2),
            'event_place' => 'UCU Main Campus, CITE Building',
        ]);
    }

    public function public(): static
    {
        return $this->state(fn () => ['visibility' => 'public']);
    }

    public function sent(): static
    {
        return $this->state(fn () => ['status' => 'sent', 'published_at' => now()]);
    }

    /**
     * @param  list<string>  $studentIds
     */
    public function forStudentIds(array $studentIds): static
    {
        return $this->state(fn () => [
            'audience_type' => 'student_ids',
            'audience_filter' => ['student_ids' => $studentIds],
        ]);
    }

    public function forYears(int $from, ?int $to = null): static
    {
        return $this->state(fn () => [
            'audience_type' => 'year',
            'audience_filter' => ['from' => $from, 'to' => $to ?? $from],
        ]);
    }
}
