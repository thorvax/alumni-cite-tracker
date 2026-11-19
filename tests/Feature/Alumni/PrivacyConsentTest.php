<?php

use App\Models\Graduate;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the public Data Privacy Notice page', function () {
    $this->get('/privacy')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('privacy'));
});

it('requires the Data Privacy Notice checkbox on the letter page before the survey', function () {
    $graduate = Graduate::factory()->invited()->create();
    $this->actingAs($graduate, 'graduate');

    // The steps stay closed until they agree.
    $this->get('/survey/general-information')->assertRedirect('/survey');
    $this->post('/survey/general-information', ['first_name' => 'Juan'])->assertRedirect('/survey');

    $this->post('/survey', ['privacy' => false])
        ->assertSessionHasErrors(['privacy' => 'Please confirm that you have read and agree to the Data Privacy Notice.']);

    $this->post('/survey', ['privacy' => true])->assertRedirect('/survey/general-information');
    $this->get('/survey/general-information')->assertOk();
});

it('no longer asks for the checkbox on step A', function () {
    loginGraduate();

    $this->post('/survey/general-information', [
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan@example.com',
        'civil_status' => 'Single',
        'sex' => 'male',
        'birth_date' => '2000-01-15',
        'region' => 'Region I',
        'province' => 'Pangasinan',
        'city' => 'Urdaneta City',
        'barangay' => 'San Vicente',
        'mobile_primary' => '09171234567',
    ])->assertSessionHasNoErrors()->assertRedirect('/survey/educational-background');
});

it('stores the time they ticked the checkbox when the survey is submitted', function () {
    loginGraduate();
    $answers = surveyAnswers(['employed' => 'no', 'not_employed_reasons' => ['advance_study']]);
    $answers['consented_at'] = now()->subMinutes(20)->toIso8601String();

    $this->withSession(['survey' => $answers])
        ->post('/survey/referral-submit', ['classmates' => null, 'certify' => true])
        ->assertRedirect('/alumni/my-survey');

    // The time of the tick (20 minutes ago), not the time of submitting.
    expect(Graduate::sole()->privacy_consented_at->diffInMinutes(now()))->toBeGreaterThanOrEqual(19.0);

    // Updating later asks for the agreement again.
    expect(session('survey.consented_at'))->toBeNull();
});
