<?php

use App\Models\Graduate;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $answers = surveyAnswers(['employed' => 'no', 'not_employed_reasons' => ['advance_study']]);
    $answers['general-information']['privacy'] = true;
    $answers['referral-submit'] = ['classmates' => null, 'certify' => true];

    $this->graduate = Graduate::factory()->create([
        'student_id' => '20244012',
        'last_name' => 'Dela Cruz',
        'survey' => $answers,
        'employment_status' => 'Further Studies',
        'survey_completed_at' => now()->subYear(),
    ]);

    $this->graduate->forceFill(['password' => 'Secret123', 'activated_at' => now()->subYear()])->save();
});

it('shows the Update my answers button on My Survey', function () {
    $this->actingAs($this->graduate, 'graduate')
        ->get('/alumni/my-survey')
        ->assertInertia(fn (Assert $page) => $page
            ->component('alumni/my-survey')
            ->where('graduate.answered', true)
            ->where('graduate.can_edit', true));
});

it('opens the survey filled in with the saved answers', function () {
    $this->actingAs($this->graduate, 'graduate')
        ->post('/alumni/my-survey/edit')
        ->assertRedirect('/survey/general-information');

    $this->get('/survey/general-information')
        ->assertInertia(fn (Assert $page) => $page
            ->where('draft.first_name', 'Juan')
            ->missing('draft.privacy')); // must be ticked again

    $this->get('/survey/referral-submit')
        ->assertInertia(fn (Assert $page) => $page->missing('draft.certify'));
});

it('updates the same record and keeps the first answer date', function () {
    $firstAnswered = $this->graduate->survey_completed_at;

    $this->actingAs($this->graduate, 'graduate')->post('/alumni/my-survey/edit');

    // They found a job since last year.
    $answers = session('survey');
    $answers['employment-data'] = ['employed' => 'yes', 'employment_status' => 'regular', 'first_job' => 'yes'];
    $answers['first-current-job'] = ['first_related' => 'yes'];

    $this->withSession(['survey' => $answers])
        ->post('/survey/referral-submit', ['classmates' => null, 'certify' => true])
        ->assertRedirect('/alumni/my-survey')
        ->assertSessionHas('status');

    $graduate = $this->graduate->fresh();

    expect(Graduate::count())->toBe(1)
        ->and($graduate->employment_status)->toBe('Employed')
        ->and($graduate->survey_completed_at->toDateString())->toBe($firstAnswered->toDateString())
        ->and($graduate->survey_updated_at)->not->toBeNull()
        ->and($graduate->privacy_consented_at)->not->toBeNull();
});

it('sends someone without saved answers to the survey', function () {
    $this->graduate->update(['survey' => null, 'survey_completed_at' => null]);

    $this->actingAs($this->graduate, 'graduate')
        ->post('/alumni/my-survey/edit')
        ->assertRedirect('/survey');
});

it('needs the alumni login to update answers', function () {
    $this->post('/alumni/my-survey/edit')->assertRedirect('/alumni/login');
});
