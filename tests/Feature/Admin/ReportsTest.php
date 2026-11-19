<?php

use App\Models\Graduate;
use App\Models\User;
use App\Support\Reports\GraduateReport;
use Database\Factories\GraduateFactory;
use Inertia\Testing\AssertableInertia as Assert;

const BSIT = 'Bachelor of Science in Information and Technology';
const ACT = 'Associate in Computer Technology';

beforeEach(function () {
    $this->admin = User::factory()->create();
});

/** A graduate who completed the survey, with chosen answers in the first/current-job step. */
function respondent(string $status, string $course, int $year, array $job = []): Graduate
{
    $survey = GraduateFactory::surveyAnswers($status, $course, $year);

    if ($job !== []) {
        $survey['first-current-job'] = $job;
    }

    return Graduate::factory()->create([
        'employment_status' => $status,
        'course' => $course,
        'year_graduated' => $year,
        'survey' => $survey,
        'survey_completed_at' => now(),
    ]);
}

it('shows "no data" numbers without dividing by zero', function () {
    $report = new GraduateReport;

    expect($report->employmentStatus()['total'])->toBe(0)
        ->and($report->employmentStatus()['rate'])->toBe(['count' => 0, 'percent' => 0])
        ->and($report->employmentByCourse())->toBe([])
        ->and($report->employmentByYear())->toBe([])
        ->and($report->jobRelated()['overall']['total'])->toBe(0)
        ->and($report->timeToLand())->toBe(['total' => 0, 'items' => []])
        ->and($report->responseFunnel()['stages'])->toBe([])
        ->and($report->responseFunnel()['rate']['percent'])->toBe(0);

    $this->actingAs($this->admin)->get('/reports')->assertOk();
});

it('counts employment status with percentages and the employment rate', function () {
    Graduate::factory()->count(2)->create(['employment_status' => 'Employed']);
    Graduate::factory()->create(['employment_status' => 'Self-employed']);
    Graduate::factory()->create(['employment_status' => 'Unemployed']);
    Graduate::factory()->create(['employment_status' => null]); // unknown: not counted

    $status = (new GraduateReport)->employmentStatus();

    expect($status['total'])->toBe(4)
        ->and($status['rate'])->toBe(['count' => 3, 'percent' => 75])
        ->and($status['items'])->toBe([
            ['label' => 'Employed', 'count' => 2, 'percent' => 50],
            ['label' => 'Self-employed', 'count' => 1, 'percent' => 25],
            ['label' => 'Unemployed', 'count' => 1, 'percent' => 25],
            ['label' => 'Further Studies', 'count' => 0, 'percent' => 0],
        ]);
});

it('breaks employment down by course code and by year', function () {
    Graduate::factory()->count(3)->create(['course' => BSIT, 'year_graduated' => 2022, 'employment_status' => 'Employed']);
    Graduate::factory()->create(['course' => BSIT, 'year_graduated' => 2023, 'employment_status' => 'Unemployed']);
    Graduate::factory()->create(['course' => ACT, 'year_graduated' => 2023, 'employment_status' => 'Further Studies']);

    $report = new GraduateReport;
    $bsit = collect($report->employmentByCourse())->firstWhere('label', 'BSIT');

    expect($bsit['total'])->toBe(4)
        ->and(collect($bsit['items'])->firstWhere('label', 'Employed'))->toBe(['label' => 'Employed', 'count' => 3, 'percent' => 75])
        ->and($report->employmentByYear())->toBe([
            ['year' => 2022, 'total' => 3, 'employed' => 3, 'percent' => 100],
            ['year' => 2023, 'total' => 2, 'employed' => 0, 'percent' => 0],
        ]);
});

it('counts job related to the course from the first and the current job', function () {
    respondent('Employed', BSIT, 2023, ['first_related' => 'yes']);
    respondent('Employed', BSIT, 2023, ['first_related' => 'yes']);
    respondent('Employed', BSIT, 2023, ['current_related' => 'no']);
    respondent('Employed', ACT, 2023, ['current_related' => 'yes']);
    // Did not complete the survey: never counted in survey charts.
    Graduate::factory()->create(['course' => BSIT, 'survey' => ['first-current-job' => ['first_related' => 'no']]]);

    $related = (new GraduateReport)->jobRelated();
    $bsit = collect($related['courses'])->firstWhere('label', 'BSIT');

    expect($related['overall']['total'])->toBe(4)
        ->and($related['overall']['items'][0])->toBe(['label' => 'Yes', 'count' => 3, 'percent' => 75])
        ->and($bsit['items'])->toBe([
            ['label' => 'Yes', 'count' => 2, 'percent' => 67],
            ['label' => 'No', 'count' => 1, 'percent' => 33],
        ]);
});

it('counts time to land the first job in survey order with Other grouped', function () {
    respondent('Employed', BSIT, 2023, ['first_time_to_land' => '1 to 6 months']);
    respondent('Employed', BSIT, 2023, ['first_time_to_land' => '1 to 6 months']);
    respondent('Employed', BSIT, 2023, ['first_time_to_land' => 'Less than a month']);
    respondent('Employed', BSIT, 2023, ['first_time_to_land' => 'other', 'first_time_to_land_other' => 'A week']);

    $time = (new GraduateReport)->timeToLand();

    expect($time['total'])->toBe(4)
        ->and($time['items'][0])->toBe(['label' => 'Less than a month', 'count' => 1, 'percent' => 25])
        ->and($time['items'][1])->toBe(['label' => '1 to 6 months', 'count' => 2, 'percent' => 50])
        ->and(collect($time['items'])->last())->toBe(['label' => 'Other', 'count' => 1, 'percent' => 25]);
});

it('builds the survey progress funnel for uploaded graduates', function () {
    Graduate::factory()->count(3)->notAnswered()->create();
    Graduate::factory()->notAnswered()->create(['email' => null, 'invite_sent_at' => null]);
    Graduate::factory()->count(2)->invited()->withSurvey()->create(['course' => BSIT, 'year_graduated' => 2023]);
    Graduate::factory()->create(); // added by hand, not uploaded

    $funnel = (new GraduateReport)->responseFunnel();

    expect($funnel['uploaded'])->toBe(6)
        ->and($funnel['rate'])->toBe(['count' => 2, 'percent' => 33])
        ->and(collect($funnel['stages'])->pluck('count', 'label')->all())->toBe([
            'Uploaded' => 6, 'With email' => 5, 'Email sent' => 5, 'Answered' => 2,
        ])
        ->and($funnel['answeredByCourse'])->toBe([['label' => 'BSIT', 'count' => 2, 'percent' => 100]])
        ->and($funnel['unknown'])->toBe(4);
});

it('applies the course and year filters to every chart', function () {
    respondent('Employed', BSIT, 2023, ['first_related' => 'yes', 'first_time_to_land' => '1 to 6 months']);
    respondent('Unemployed', BSIT, 2022);
    respondent('Employed', ACT, 2023, ['first_related' => 'no', 'first_time_to_land' => 'Less than a month']);

    $this->actingAs($this->admin)
        ->get('/reports?course=BSIT&year=2023')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/reports')
            ->where('filters', ['course' => 'BSIT', 'year' => '2023'])
            ->where('status.total', 1)
            ->where('status.rate.percent', 100)
            ->has('byCourse', 1)
            ->has('byYear', 1)
            ->where('related.overall.total', 1)
            ->where('timeToLand.total', 1)
            ->where('options.courses', ['ACT', 'BLIS', 'BSIT']));
});

it('ignores filter values that are not offered', function () {
    respondent('Employed', BSIT, 2023);

    $this->actingAs($this->admin)
        ->get('/reports?course=NURSING&year=1900')
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters', ['course' => '', 'year' => ''])
            ->where('status.total', 1));
});

it('exports the report numbers as CSV without names', function () {
    respondent('Employed', BSIT, 2023, ['first_related' => 'yes']);
    Graduate::query()->update(['full_name' => 'Secret Person']);

    $response = $this->actingAs($this->admin)->get('/reports/export?course=BSIT');

    $response->assertOk()->assertDownload('cite-report-BSIT.csv');
    $csv = $response->streamedContent();

    expect($csv)->toContain('"Employment status",All,Employed,1,100%')
        ->and($csv)->toContain('"Employment status",All,"Employment rate",1,100%')
        ->and($csv)->toContain('Course: '.BSIT)
        ->and($csv)->not->toContain('Secret Person');
});

it('never sends names to the reports page', function () {
    respondent('Employed', BSIT, 2023);
    Graduate::query()->update(['full_name' => 'Secret Person']);

    $this->actingAs($this->admin)->get('/reports')->assertDontSee('Secret Person');
});

/** A respondent with chosen answers merged into the given survey steps. */
function answered(string $status, array $steps, string $course = BSIT): Graduate
{
    $survey = GraduateFactory::surveyAnswers($status, $course, 2023);

    foreach ($steps as $step => $answers) {
        $survey[$step] = $answers + ($survey[$step] ?? []);
    }

    return Graduate::factory()->create([
        'employment_status' => $status,
        'course' => $course,
        'year_graduated' => 2023,
        'survey' => $survey,
        'survey_completed_at' => now(),
    ]);
}

it('returns empty results for items 6 to 13 when nobody answered', function () {
    $report = new GraduateReport;

    expect($report->sectorAndPlace()['sector'])->toBe(['total' => 0, 'items' => []])
        ->and($report->firstJobEarning())->toBe(['total' => 0, 'items' => []])
        ->and($report->howFound())->toBe(['total' => 0, 'items' => []])
        ->and($report->competencies())->toBe(['total' => 0, 'items' => []])
        ->and($report->curriculum()['suggestions'])->toBe(['total' => 0, 'latest' => []])
        ->and($report->notEmployedReasons())->toBe(['total' => 0, 'items' => []])
        ->and($report->helpNeeded())->toBe(['total' => 0, 'latest' => []])
        ->and($report->courseReasons())->toBe(['total' => 0, 'items' => []]);
});

it('counts sector and local vs abroad with labels', function () {
    answered('Employed', ['employment-data' => ['employment_sector' => 'government', 'place_of_work' => 'local']]);
    answered('Employed', ['employment-data' => ['employment_sector' => 'private', 'place_of_work' => 'local']]);
    answered('Employed', ['employment-data' => ['employment_sector' => 'private', 'place_of_work' => 'abroad']]);
    answered('Employed', ['employment-data' => ['employment_sector' => 'private', 'place_of_work' => 'local']]);

    $result = (new GraduateReport)->sectorAndPlace();

    expect(collect($result['sector']['items'])->pluck('count', 'label')->only(['Government', 'Private'])->all())
        ->toBe(['Government' => 1, 'Private' => 3])
        ->and($result['place']['items'])->toBe([
            ['label' => 'Local', 'count' => 3, 'percent' => 75],
            ['label' => 'Abroad', 'count' => 1, 'percent' => 25],
        ]);
});

it('counts first job earning in range order and how they found the job, most common first', function () {
    $job = fn (string $earning, string $found) => [
        'employment-data' => ['first_job' => 'yes'],
        'first-current-job' => ['first_earning' => $earning, 'first_how_found' => $found],
    ];

    answered('Employed', $job('P25,000 and above', 'As walk-in applicant'));
    answered('Employed', $job('Below P5,000', 'Recommended by someone'));
    answered('Employed', $job('Below P5,000', 'Recommended by someone'));

    $report = new GraduateReport;
    $earning = $report->firstJobEarning();

    expect($earning['total'])->toBe(3)
        ->and($earning['items'][0])->toBe(['label' => 'Below P5,000', 'count' => 2, 'percent' => 67])
        ->and(collect($earning['items'])->last())->toBe(['label' => 'P25,000 and above', 'count' => 1, 'percent' => 33])
        ->and($report->howFound()['items'])->toBe([
            ['label' => 'Recommended by someone', 'count' => 2, 'percent' => 67],
            ['label' => 'As walk-in applicant', 'count' => 1, 'percent' => 33],
        ]);
});

it('ranks tick-all-that-apply answers against the people who answered', function () {
    answered('Employed', ['first-current-job' => ['competencies' => ['it', 'communication']]]);
    answered('Employed', ['first-current-job' => ['competencies' => ['it']]]);
    answered('Unemployed', ['employment-data' => ['employed' => 'no', 'not_employed_reasons' => ['no_opportunity', 'health']]]);
    answered('Unemployed', ['employment-data' => ['employed' => 'never', 'not_employed_reasons' => ['no_opportunity']]]);

    $report = new GraduateReport;

    expect($report->competencies())->toBe([
        'total' => 2,
        'items' => [
            ['label' => 'Information Technology skills', 'count' => 2, 'percent' => 100],
            ['label' => 'Communication skills', 'count' => 1, 'percent' => 50],
        ],
    ])->and($report->notEmployedReasons()['items'][0])->toBe(['label' => 'No job opportunity', 'count' => 2, 'percent' => 100]);
});

it('ranks why they chose the course', function () {
    answered('Employed', ['educational-background' => ['reasons_ug' => ['passion', 'affordable']]]);
    answered('Employed', ['educational-background' => ['reasons_ug' => ['passion']]]);

    expect((new GraduateReport)->courseReasons()['items'][0])
        ->toBe(['label' => 'Strong passion for the profession', 'count' => 2, 'percent' => 100]);
});

it('counts curriculum relevance and lists the latest written answers without names', function () {
    answered('Employed', ['first-current-job' => ['curriculum_relevant' => 'yes', 'curriculum_suggestions' => null]]);
    answered('Employed', ['first-current-job' => ['curriculum_relevant' => 'no', 'curriculum_suggestions' => 'Teach more cloud topics.']]);
    // Unemployed, so the factory gives this person no random job answers.
    answered('Unemployed', ['need-help' => ['help_needed' => 'Interview practice please.']]);

    $report = new GraduateReport;
    $curriculum = $report->curriculum();

    expect($curriculum['relevant']['items'][0])->toBe(['label' => 'Yes', 'count' => 1, 'percent' => 50])
        ->and($curriculum['suggestions'])->toBe(['total' => 1, 'latest' => ['Teach more cloud topics.']])
        ->and($report->helpNeeded()['total'])->toBe(3)
        ->and($report->helpNeeded()['latest'])->toContain('Interview practice please.');
});

it('applies the course filter to items 6 to 13', function () {
    answered('Employed', ['first-current-job' => ['competencies' => ['it']]], BSIT);
    answered('Employed', ['first-current-job' => ['competencies' => ['communication']]], ACT);

    $this->actingAs($this->admin)
        ->get('/reports?course=ACT')
        ->assertInertia(fn (Assert $page) => $page
            ->where('competencies.total', 1)
            ->where('competencies.items.0.label', 'Communication skills')
            ->where('helpNeeded.total', 1));
});

it('exports items 6 to 13 as counts but leaves written answers out', function () {
    answered('Employed', ['first-current-job' => [
        'curriculum_relevant' => 'no',
        'competencies' => ['it'],
        'curriculum_suggestions' => 'My name is Juan, add cloud topics.',
    ]]);

    $csv = $this->actingAs($this->admin)->get('/reports/export')->streamedContent();

    expect($csv)->toContain('Information Technology skills",1,100%')
        ->and($csv)->toContain('"Curriculum relevant to first job",All,No,1,100%')
        ->and($csv)->not->toContain('My name is Juan');
});
