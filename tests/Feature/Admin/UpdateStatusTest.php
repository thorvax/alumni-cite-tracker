<?php

use App\Models\AppSetting;
use App\Models\Graduate;
use App\Models\User;
use App\Services\UpdateReminderService;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->create();
    AppSetting::set('update_reminder_months', 6);
});

/** An uploaded graduate who answered, with chosen dates/changes. */
function respondentOn(DateTimeInterface $answered, array $attributes = []): Graduate
{
    return Graduate::factory()->invited()->create($attributes + [
        'survey' => ['need-help' => ['help_needed' => 'Tips']],
        'survey_completed_at' => $answered,
    ]);
}

it('finds which survey parts changed, ignoring things that are not real changes', function () {
    $old = [
        'general-information' => ['city' => 'Urdaneta', 'privacy' => true],
        'educational-background' => ['year_graduated' => 2023, 'reasons_ug' => ['passion', 'affordable']],
        'employment-data' => ['employed' => 'no'],
        'referral-submit' => ['certify' => true, 'classmates' => null],
    ];
    $new = [
        'general-information' => ['city' => 'Urdaneta', 'mobile_secondary' => ''], // blank added
        'educational-background' => ['year_graduated' => '2023', 'reasons_ug' => ['affordable', 'passion']], // "2023" and order
        'employment-data' => ['employed' => 'yes'], // real change
        'referral-submit' => ['certify' => false],
    ];

    expect(Graduate::changedParts($old, $new))->toBe(['Employment Data'])
        ->and(Graduate::changedParts($old, $old))->toBe([]);
});

it('records the changed parts when a graduate updates, and nothing on the first answer', function () {
    $graduate = loginGraduate(['survey_completed_at' => null]);
    $answers = surveyAnswers(['employed' => 'no', 'not_employed_reasons' => ['did_not_look']]);

    $this->withSession(['survey' => $answers])->post('/survey/referral-submit', ['classmates' => null, 'certify' => true]);
    expect($graduate->fresh()->survey_changes)->toBeNull();

    // Updates: found a job.
    $answers['employment-data'] = ['employed' => 'yes', 'employment_status' => 'regular', 'first_job' => 'yes'];
    $answers['first-current-job'] = ['first_related' => 'yes'];
    $this->withSession(['survey' => $answers])->post('/survey/referral-submit', ['classmates' => null, 'certify' => true]);
    expect($graduate->fresh()->survey_changes)->toBe(['Employment Data', 'First / Current Job']);

    // Updates again without changing anything.
    $this->withSession(['survey' => $answers])->post('/survey/referral-submit', ['classmates' => null, 'certify' => true]);
    expect($graduate->fresh()->survey_changes)->toBe([]);
});

it('reminds only alumni who have not updated, or everyone who answered', function () {
    $stale = respondentOn(now()->subYear());
    $recent = respondentOn(now()->subYear(), ['survey_updated_at' => now()->subMonth()]);
    respondentOn(now()->subMonth()); // answered recently: first reminder waits one period

    AppSetting::set('update_reminder_target', 'stale');
    expect((new UpdateReminderService)->due()->pluck('id')->all())->toBe([$stale->id]);

    AppSetting::set('update_reminder_target', 'all');
    expect((new UpdateReminderService)->due()->pluck('id')->sort()->values()->all())->toBe([$stale->id, $recent->id]);
});

it('saves who to remind from the Settings page', function () {
    $this->actingAs($this->admin)
        ->patch('/settings/system/update-reminders', ['enabled' => true, 'months' => 12, 'target' => 'all'])
        ->assertSessionHasNoErrors();

    expect(UpdateReminderService::target())->toBe('all');

    $this->actingAs($this->admin)
        ->get('/settings/system')
        ->assertInertia(fn (Assert $page) => $page->where('updateReminders.target', 'all'));

    $this->actingAs($this->admin)
        ->patch('/settings/system/update-reminders', ['enabled' => true, 'months' => 12, 'target' => 'everyone'])
        ->assertSessionHasErrors(['target' => 'Choose who to remind.']);
});

it('shows the update status of everyone who answered, with counts', function () {
    respondentOn(now()->subYear()); // needs update, never updated
    respondentOn(now()->subYear(), ['survey_updated_at' => now()->subWeek(), 'survey_changes' => ['Employment Data']]);
    respondentOn(now()->subYear(), ['survey_updated_at' => now()->subWeek(), 'survey_changes' => []]);
    Graduate::factory()->notAnswered()->create(); // not answered: not listed

    $this->actingAs($this->admin)
        ->get('/graduates/updates')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/graduates/updates')
            ->where('graduates.total', 3)
            ->where('counts', [
                'all' => 3,
                'up_to_date' => 2,
                'needs_update' => 1,
                'changed' => 1,
                'no_changes' => 1,
                'never_updated' => 1,
            ])
            // Most recently updated first.
            ->where('graduates.data.0.status', 'up_to_date')
            ->where('graduates.data.2.status', 'needs_update')
            ->where('graduates.data.2.changes', null));
});

it('filters the update status list', function (string $filter, int $expected) {
    respondentOn(now()->subYear());
    respondentOn(now()->subYear(), ['survey_updated_at' => now()->subWeek(), 'survey_changes' => ['Personal Information']]);

    $this->actingAs($this->admin)
        ->get("/graduates/updates?status={$filter}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.status', $filter)
            ->where('graduates.total', $expected));
})->with([
    ['up_to_date', 1],
    ['needs_update', 1],
    ['changed', 1],
    ['no_changes', 0],
    ['never_updated', 1],
]);

it('paginates the update status list', function () {
    foreach (range(1, 12) as $i) {
        respondentOn(now()->subDays($i));
    }

    $this->actingAs($this->admin)
        ->get('/graduates/updates?per_page=5&page=3')
        ->assertInertia(fn (Assert $page) => $page
            ->where('graduates.current_page', 3)
            ->where('graduates.last_page', 3)
            ->has('graduates.data', 2)
            ->where('filters.per_page', 5));

    $this->actingAs($this->admin)
        ->get('/graduates/updates?per_page=5&page=99')
        ->assertRedirect('/graduates/updates?per_page=5&page=3');
});

it('keeps the update status page behind the admin login', function () {
    $this->get('/graduates/updates')->assertRedirect('/login');
});
