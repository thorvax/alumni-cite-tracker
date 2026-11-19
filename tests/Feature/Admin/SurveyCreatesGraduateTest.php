<?php

use App\Models\Graduate;
use App\Models\SurveyResponse;

/**
 * @return array<string, array<string, mixed>>
 */
function surveyAnswers(array $employment): array
{
    return [
        'general-information' => [
            'first_name' => 'Juan',
            'middle_name' => null,
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'civil_status' => 'Single',
            'sex' => 'male',
            'birth_date' => '2001-01-15',
            'region' => 'Region I - Ilocos Region',
            'province' => 'Pangasinan',
            'city' => 'Urdaneta City',
            'barangay' => 'Nancamaliran',
            'house_no' => null,
            'mobile_primary' => '09123456789',
            'mobile_secondary' => null,
        ],
        'educational-background' => [
            'course' => 'Bachelor of Science in Information and Technology',
            'year_graduated' => 2023,
        ],
        'trainings' => [],
        'employment-data' => $employment,
        'need-help' => ['help_needed' => 'Interview tips please.'],
    ];
}

it('creates a graduate record when an employed person submits the survey', function () {
    $answers = surveyAnswers([
        'employed' => 'yes',
        'employment_status' => 'regular',
        'employment_sector' => 'private',
        'first_job' => 'yes',
    ]) + ['first-current-job' => ['first_related' => 'yes']];

    $this->withSession(['survey' => $answers])
        ->post('/survey/referral-submit', ['classmates' => null, 'certify' => true])
        ->assertRedirect('/');

    $graduate = Graduate::firstOrFail();

    expect($graduate->full_name)->toBe('Juan Dela Cruz')
        ->and($graduate->gender)->toBe('Male')
        ->and($graduate->employment_status)->toBe('Employed')
        ->and($graduate->year_graduated)->toBe(2023)
        ->and(SurveyResponse::count())->toBe(1);
});

it('lets people who are not employed skip the job form', function () {
    $answers = surveyAnswers([
        'employed' => 'no',
        'not_employed_reasons' => ['advance_study'],
    ]);

    $this->withSession(['survey' => $answers])
        ->post('/survey/referral-submit', ['classmates' => null, 'certify' => true])
        ->assertRedirect('/');

    expect(Graduate::firstOrFail()->employment_status)->toBe('Further Studies');
});

it('sends people who are not employed from Employment Data straight to the help form', function () {
    $this->post('/survey/employment-data', [
        'employed' => 'never',
        'not_employed_reasons' => ['no_opportunity'],
    ])->assertRedirect('/survey/need-help');
});

it('sends employed people to the job form', function () {
    $this->post('/survey/employment-data', [
        'employed' => 'yes',
        'employment_status' => 'regular',
        'employment_sector' => 'private',
        'self_employed_skills' => 'N/A',
        'present_occupation' => 'Software Developer',
        'business_line' => 'Information Technology',
        'work_line' => 'Web Developer',
        'place_of_work' => 'local',
        'first_job' => 'yes',
    ])->assertRedirect('/survey/first-current-job');
});

it('requires the reasons when someone is not employed', function () {
    $this->post('/survey/employment-data', ['employed' => 'no'])
        ->assertSessionHasErrors('not_employed_reasons');
});

it('requires the first job questions based on the earlier answer', function () {
    $this->withSession(['survey' => ['employment-data' => ['employed' => 'yes', 'first_job' => 'yes']]])
        ->post('/survey/first-current-job', ['first_related' => 'yes'])
        ->assertSessionHasErrors(['competencies', 'first_time_to_land', 'first_level', 'first_sector', 'first_how_found', 'first_earning', 'curriculum_relevant']);

    $this->withSession(['survey' => ['employment-data' => ['employed' => 'yes', 'first_job' => 'yes']]])
        ->post('/survey/first-current-job', ['first_related' => 'no'])
        ->assertSessionHasErrors('curriculum_suggestions');
});

it('does not open the job form for people who are not employed', function () {
    $this->withSession(['survey' => ['employment-data' => ['employed' => 'no']]])
        ->get('/survey/first-current-job')
        ->assertRedirect('/survey/need-help');
});

it('requires an answer on the help form', function () {
    $this->post('/survey/need-help', [])->assertSessionHasErrors('help_needed');
});

it('derives the employment status from the survey answers', function (array $employment, string $expected) {
    expect(Graduate::statusFromSurvey(['employment-data' => $employment]))->toBe($expected);
})->with([
    'employed' => [['employed' => 'yes', 'employment_status' => 'regular'], 'Employed'],
    'self-employed' => [['employed' => 'yes', 'employment_status' => 'self_employed'], 'Self-employed'],
    'further studies' => [['employed' => 'no', 'not_employed_reasons' => ['advance_study']], 'Further Studies'],
    'unemployed' => [['employed' => 'never', 'not_employed_reasons' => ['no_opportunity']], 'Unemployed'],
]);
