<?php

use App\Models\Graduate;
use Inertia\Testing\AssertableInertia as Assert;

it('shows the public Privacy Policy page', function () {
    $this->get('/privacy')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('privacy'));
});

it('requires the privacy checkbox on survey step A', function () {
    loginGraduate();

    $this->post('/survey/general-information', ['privacy' => false])
        ->assertSessionHasErrors(['privacy' => 'Please confirm that you have read and agree to the Privacy Policy.']);
});

it('accepts step A when the privacy checkbox is ticked', function () {
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
        'privacy' => true,
    ])->assertSessionHasNoErrors()->assertRedirect('/survey/educational-background');
});

it('stores the time of consent when the survey is submitted', function () {
    loginGraduate();
    $answers = surveyAnswers(['employed' => 'no', 'not_employed_reasons' => ['advance_study']]);

    $this->withSession(['survey' => $answers])
        ->post('/survey/referral-submit', ['classmates' => null, 'certify' => true])
        ->assertRedirect('/alumni/my-survey');

    expect(Graduate::sole()->privacy_consented_at)->not->toBeNull();
});
