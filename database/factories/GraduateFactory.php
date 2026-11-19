<?php

namespace Database\Factories;

use App\Models\Graduate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Graduate>
 */
class GraduateFactory extends Factory
{
    protected $model = Graduate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roll = fake()->numberBetween(1, 100);

        // About 72% employed (a few of them self-employed), 14% unemployed, 14% further studies.
        $status = match (true) {
            $roll <= 65 => 'Employed',
            $roll <= 72 => 'Self-employed',
            $roll <= 86 => 'Unemployed',
            default => 'Further Studies',
        };

        return [
            'full_name' => fake()->firstName().' '.fake()->lastName(),
            'birth_date' => fake()->dateTimeBetween('-30 years', '-21 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['Male', 'Female']),
            'civil_status' => fake()->randomElement(['Single', 'Single', 'Married']),
            'email' => fake()->unique()->safeEmail(),
            'contact_number' => '09'.fake()->numerify('#########'),
            'current_address' => 'Urdaneta City, Pangasinan',
            'region' => 'Region I (Ilocos Region)',
            'province' => 'Pangasinan',
            'city' => 'City of Urdaneta',
            'course' => fake()->randomElement(array_keys(Graduate::COURSE_CODES)),
            'year_graduated' => fake()->numberBetween(2019, 2023),
            'employment_status' => $status,
        ];
    }

    /**
     * Uploaded through Bulk Upload (has a Student ID and a personal link) and emailed.
     */
    public function invited(): static
    {
        return $this->state(fn () => [
            'student_id' => fake()->unique()->numerify('19######'),
            'last_name' => fake()->lastName(),
            'invite_token' => Str::random(48),
            'invite_status' => 'sent',
            'invite_sent_at' => now()->subDays(fake()->numberBetween(1, 60)),
        ]);
    }

    /**
     * Uploaded but has not answered yet: only Student ID, Last Name and Email are known.
     */
    public function notAnswered(): static
    {
        return $this->invited()->state(fn (array $attributes) => [
            'full_name' => $attributes['last_name'],
            'course' => null,
            'year_graduated' => null,
            'employment_status' => null,
            'birth_date' => null,
            'gender' => null,
            'civil_status' => null,
            'contact_number' => null,
            'current_address' => null,
            'region' => null,
            'province' => null,
            'city' => null,
        ]);
    }

    /**
     * Completed the survey: realistic answers that match the graduate's course,
     * year and employment status. Keys and values are the ones SurveyController accepts.
     */
    public function withSurvey(): static
    {
        return $this->state(fn (array $attributes) => [
            'survey' => self::surveyAnswers(
                (string) $attributes['employment_status'],
                (string) $attributes['course'],
                (int) $attributes['year_graduated'],
            ),
            'survey_completed_at' => now()->subDays(fake()->numberBetween(0, 90)),
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function surveyAnswers(string $status, string $course, int $year): array
    {
        $pick = fn (array $list, int $min = 1, int $max = 2) => fake()->randomElements($list, fake()->numberBetween($min, $max));

        $answers = [
            'general-information' => [
                'first_name' => fake()->firstName(),
                'middle_name' => null,
                'last_name' => fake()->lastName(),
                'email' => fake()->safeEmail(),
                'civil_status' => 'Single',
                'sex' => fake()->randomElement(['male', 'female']),
                'birth_date' => fake()->dateTimeBetween('-30 years', '-21 years')->format('Y-m-d'),
                'region' => 'Region I (Ilocos Region)',
                'province' => 'Pangasinan',
                'city' => 'City of Urdaneta',
                'barangay' => 'San Vicente',
                'house_no' => null,
                'mobile_primary' => '09'.fake()->numerify('#########'),
                'mobile_secondary' => null,
                'privacy' => true,
            ],
            'educational-background' => [
                'course' => $course,
                'year_graduated' => $year,
                'reasons_ug' => $pick([
                    'high_grades_course', 'good_grades_hs', 'parents_relatives', 'peer_influence',
                    'role_model', 'passion', 'immediate_employment', 'prestige',
                    'course_availability', 'career_advancement', 'affordable', 'compensation',
                    'abroad', 'no_choice',
                ], 1, 3),
                'reasons_grad' => [],
            ],
            'trainings' => [],
        ];

        $answers['employment-data'] = match ($status) {
            'Employed', 'Self-employed' => self::employedAnswers($status === 'Self-employed'),
            'Further Studies' => [
                'employed' => 'no',
                'not_employed_reasons' => ['advance_study', ...$pick(['family_concern', 'no_opportunity'], 0, 1)],
            ],
            default => [
                'employed' => fake()->randomElement(['no', 'never']),
                'not_employed_reasons' => $pick(['family_concern', 'health', 'no_experience', 'no_opportunity', 'did_not_look']),
            ],
        };

        if (($answers['employment-data']['employed'] ?? null) === 'yes') {
            $answers['first-current-job'] = self::jobAnswers($answers['employment-data']);
        }

        $answers['need-help'] = ['help_needed' => fake()->randomElement([
            'Job fair announcements from the college would help.',
            'Tips for technical interviews and coding tests.',
            'Help building a portfolio and resume.',
            'More information about government job openings.',
            'Connections with companies hiring fresh graduates.',
        ])];
        $answers['referral-submit'] = ['classmates' => null, 'certify' => true];

        return $answers;
    }

    /**
     * @return array<string, mixed>
     */
    private static function employedAnswers(bool $selfEmployed): array
    {
        return [
            'employed' => 'yes',
            'employment_status' => $selfEmployed ? 'self_employed' : fake()->randomElement(['regular', 'regular', 'temporary', 'casual', 'contractual']),
            'employment_sector' => $selfEmployed ? 'entrepreneurial' : fake()->randomElement(['private', 'private', 'government']),
            'self_employed_skills' => $selfEmployed ? 'Web development' : null,
            'present_occupation' => fake()->randomElement(['Web Developer', 'IT Support', 'Librarian', 'Data Encoder', 'Programmer']),
            'business_line' => fake()->randomElement(['Information Technology', 'Education', 'Financial Intermediation', 'Public Administration and Defense; Compulsory, Social Security', 'Wholesale and Retail Trade, repair of motor vehicles, motorcycles and personal and household goods']),
            'work_line' => fake()->randomElement(['Software Developer', 'Web Developer', 'Help Desk/Support Specialist', 'Librarian', 'Data Analyst']),
            'place_of_work' => fake()->boolean(85) ? 'local' : 'abroad',
            'first_job' => fake()->boolean(70) ? 'yes' : 'no',
        ];
    }

    /**
     * @param  array<string, mixed>  $employment
     * @return array<string, mixed>
     */
    private static function jobAnswers(array $employment): array
    {
        $related = fake()->boolean(72) ? 'yes' : 'no';

        if ($employment['first_job'] === 'no') {
            return [
                'current_related' => $related,
                'current_reasons' => [fake()->randomElement(['salaries', 'career_challenge', 'special_skills', 'proximity'])],
                'current_stay' => fake()->randomElement(['Less than 6 months', '6 months to less than 1 year', '1 year to less than 2 years']),
                'current_level' => fake()->randomElement(['Rank or Clerical', 'Professional, Technical or Supervisory']),
                'current_sector' => $employment['employment_sector'],
                'current_earning' => fake()->randomElement(['P10,000 to less than P15,000', 'P15,000 to less than P20,000', 'P20,000 to less than P25,000']),
            ];
        }

        return [
            'first_related' => $related,
            'first_reasons' => [fake()->randomElement(['salaries', 'career_challenge', 'special_skills', 'proximity'])],
            'first_time_to_land' => fake()->randomElement([
                'Less than a month', '1 to 6 months', '1 to 6 months', '1 to 6 months',
                '7 to 11 months', '1 year to less than 2 years', '2 years to less than 3 years',
            ]),
            'first_level' => fake()->randomElement(['Rank or Clerical', 'Professional, Technical or Supervisory']),
            'first_sector' => $employment['employment_sector'],
            'first_how_found' => fake()->randomElement([
                'Response to an advertisement', 'As walk-in applicant', 'Recommended by someone',
                'Information from friends', 'Job Fair or Public Employment Service Office (PESO)',
            ]),
            'first_earning' => fake()->randomElement([
                'P5,000 to less than P10,000', 'P10,000 to less than P15,000',
                'P15,000 to less than P20,000', 'P20,000 to less than P25,000', 'P25,000 and above',
            ]),
            'curriculum_relevant' => fake()->boolean(80) ? 'yes' : 'no',
            'competencies' => $related === 'yes'
                ? fake()->randomElements(['communication', 'human_relations', 'entrepreneurial', 'it', 'problem_solving', 'critical_thinking'], fake()->numberBetween(1, 3))
                : [],
            'curriculum_suggestions' => $related === 'no'
                ? fake()->randomElement(['More hands-on projects with real clients.', 'Add cloud and DevOps topics.', 'More internship hours.'])
                : null,
        ];
    }
}
