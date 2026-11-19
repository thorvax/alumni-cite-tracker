<?php

namespace Database\Seeders;

use App\Models\Graduate;
use Illuminate\Database\Seeder;

class DemoGraduatesSeeder extends Seeder
{
    /**
     * Fills the app with 250 sample graduates (all marked is_demo) so the
     * dashboard and every report chart have data:
     *  - 50 added without a survey (like "Add Graduate"),
     *  - 160 uploaded, emailed and answered (the survey needs the alumni login),
     *  - 40 uploaded who have not answered yet (some without email, some failed).
     *
     * Run with: php artisan db:seed --class=DemoGraduatesSeeder
     * Remove with: php artisan cite:delete-demo
     */
    public function run(): void
    {
        $demo = ['is_demo' => true];

        Graduate::factory()->count(50)->create($demo);
        Graduate::factory()->count(160)->invited()->withSurvey()->create($demo);

        // Uploaded with the optional Course and Year Graduated columns filled in.
        Graduate::factory()->count(28)->notAnswered()
            ->state(fn () => [
                'course' => fake()->randomElement(array_keys(Graduate::COURSE_CODES)),
                'year_graduated' => fake()->numberBetween(2019, 2023),
            ])
            ->create($demo);
        Graduate::factory()->count(6)->notAnswered()->create($demo + ['email' => null, 'invite_status' => 'pending', 'invite_sent_at' => null]);
        Graduate::factory()->count(6)->notAnswered()->create($demo + ['invite_status' => 'failed', 'invite_sent_at' => null]);
    }
}
