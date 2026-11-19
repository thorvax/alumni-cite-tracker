<?php

namespace Database\Factories;

use App\Models\Graduate;
use Illuminate\Database\Eloquent\Factories\Factory;

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
}
