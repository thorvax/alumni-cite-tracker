<?php

use App\Models\Graduate;
use App\Models\SurveyResponse;
use Inertia\Testing\AssertableInertia;

it('sends guests from the survey to the alumni login', function (string $url) {
    $this->get($url)->assertRedirect('/alumni/login');
})->with(['/survey', '/survey/general-information', '/survey/referral-submit']);

it('does not accept survey answers from a guest', function () {
    $this->post('/survey/general-information', ['first_name' => 'Juan'])
        ->assertRedirect('/alumni/login');

    $this->withSession(['survey' => surveyAnswers(['employed' => 'no', 'not_employed_reasons' => ['did_not_look']])])
        ->post('/survey/referral-submit', ['classmates' => null, 'certify' => true])
        ->assertRedirect('/alumni/login');

    expect(Graduate::count())->toBe(0)
        ->and(SurveyResponse::count())->toBe(0);
});

it('keeps the email link public so people can activate their account', function () {
    $graduate = Graduate::factory()->invited()->create();

    $this->get('/survey/start/'.$graduate->invite_token)->assertRedirect('/alumni/activate');
});

it('returns to the survey after logging in', function () {
    $graduate = Graduate::factory()->invited()->create(['student_id' => '20244012', 'survey_completed_at' => null]);
    $graduate->forceFill(['password' => 'Secret123', 'activated_at' => now()])->save();

    $this->get('/survey/general-information')->assertRedirect('/alumni/login');

    $this->post('/alumni/login', ['student_id' => '20244012', 'password' => 'Secret123'])
        ->assertRedirect('/survey/general-information');
});

it('never adds a second record when the same graduate submits twice', function () {
    $graduate = loginGraduate();
    $answers = surveyAnswers(['employed' => 'no', 'not_employed_reasons' => ['did_not_look']]);

    foreach ([1, 2] as $time) {
        $this->withSession(['survey' => $answers])
            ->post('/survey/referral-submit', ['classmates' => null, 'certify' => true])
            ->assertRedirect('/alumni/my-survey');
    }

    expect(Graduate::count())->toBe(1)
        ->and($graduate->fresh()->survey_updated_at)->not->toBeNull();
});

it('removes unfinished answers when the graduate logs out', function () {
    loginGraduate();

    $this->withSession(['survey' => ['general-information' => ['first_name' => 'Juan']]])
        ->post('/alumni/logout');

    expect(session('survey'))->toBeNull();
});

it('shows the letter to the graduate before step A', function () {
    loginGraduate();

    $this->get('/survey')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('survey/intro'));
});
