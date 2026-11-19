<?php

use App\Models\Graduate;
use App\Models\SurveyResponse;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('keeps every submission and shows what changed, newest first', function () {
    $graduate = loginGraduate(['survey_completed_at' => null]);

    // First answer: not employed.
    $answers = surveyAnswers(['employed' => 'no', 'not_employed_reasons' => ['no_opportunity']]);
    $this->withSession(['survey' => $answers])->post('/survey/referral-submit', ['classmates' => null, 'certify' => true]);

    // Update: found a job and moved.
    $this->travel(1)->day();
    $answers['employment-data'] = ['employed' => 'yes', 'employment_status' => 'regular', 'employment_sector' => 'private', 'present_occupation' => 'Web Developer', 'first_job' => 'yes'];
    $answers['first-current-job'] = ['first_related' => 'yes'];
    $answers['general-information']['city'] = 'Dagupan City';
    $this->withSession(['survey' => $answers])->post('/survey/referral-submit', ['classmates' => null, 'certify' => true]);

    expect(SurveyResponse::where('graduate_id', $graduate->id)->count())->toBe(2);

    $this->actingAs(User::factory()->create())
        ->get("/graduates/{$graduate->id}/history")
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/graduates/history')
            ->where('versions.total', 2)
            // Newest first.
            ->where('versions.data.0.number', 2)
            ->where('versions.data.0.kind', 'Update')
            ->where('versions.data.1.kind', 'First answer')
            ->where('versions.data.1.changes', [])
            // Readable before/after values.
            ->where('versions.data.0.changes', fn ($changes) => collect($changes)->contains(fn ($c) => $c['label'] === 'Present Occupation' && $c['before'] === '—' && $c['after'] === 'Web Developer')
                && collect($changes)->contains(fn ($c) => $c['label'] === 'Employment Status' && $c['before'] === 'Unemployed' && $c['after'] === 'Employed')
                && collect($changes)->contains(fn ($c) => $c['label'] === 'City/Municipality' && $c['after'] === 'Dagupan City'))
            ->has('versions.data.0.sections', 7));
});

it('says when an update had no changes', function () {
    $graduate = loginGraduate(['survey_completed_at' => null]);
    $answers = surveyAnswers(['employed' => 'no', 'not_employed_reasons' => ['did_not_look']]);

    $this->withSession(['survey' => $answers])->post('/survey/referral-submit', ['classmates' => null, 'certify' => true]);
    $this->withSession(['survey' => $answers])->post('/survey/referral-submit', ['classmates' => null, 'certify' => true]);

    $this->actingAs(User::factory()->create())
        ->get("/graduates/{$graduate->id}/history")
        ->assertInertia(fn (Assert $page) => $page
            ->where('versions.data.0.kind', 'Update')
            ->where('versions.data.0.changes', []));
});

it('shows an empty history for a graduate with no submissions', function () {
    $graduate = Graduate::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get("/graduates/{$graduate->id}/history")
        ->assertInertia(fn (Assert $page) => $page->where('versions.total', 0));
});

it('paginates a long history', function () {
    $graduate = Graduate::factory()->create();

    foreach (range(1, 12) as $i) {
        SurveyResponse::create([
            'graduate_id' => $graduate->id,
            'data' => surveyAnswers(['employed' => 'no', 'not_employed_reasons' => ['did_not_look']]),
            'submitted_at' => now()->subDays(20 - $i),
        ]);
    }

    $this->actingAs(User::factory()->create())
        ->get("/graduates/{$graduate->id}/history?page=2")
        ->assertInertia(fn (Assert $page) => $page
            ->where('versions.total', 12)
            ->where('versions.current_page', 2)
            ->has('versions.data', 2)
            ->where('versions.data.1.number', 1));
});

it('keeps the update history behind the admin login', function () {
    $graduate = Graduate::factory()->create();

    $this->get("/graduates/{$graduate->id}/history")->assertRedirect('/login');
});
