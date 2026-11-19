<?php

use App\Models\AppSetting;
use App\Models\Graduate;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->create();
    AppSetting::set('update_reminder_months', 6);
    AppSetting::set('update_reminder_target', 'stale');
    AppSetting::set('update_reminder_stale_months', 6);
});

/** An uploaded graduate who answered on the given date. */
function remindable(DateTimeInterface $answered, array $attributes = []): Graduate
{
    return Graduate::factory()->invited()->create($attributes + [
        'survey' => ['need-help' => ['help_needed' => 'Tips']],
        'survey_completed_at' => $answered,
    ]);
}

it('lists who the next automatic run will remind', function () {
    $due = remindable(now()->subYear(), ['full_name' => 'Ana Due']);
    remindable(now()->subMonth()); // updated recently: not due
    remindable(now()->subYear(), ['last_update_reminder_at' => now()->subMonth()]); // reminded recently
    remindable(now()->subYear(), ['is_demo' => true]); // demo: never emailed

    $this->actingAs($this->admin)
        ->get('/graduates/reminders')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/graduates/reminders')
            ->where('filters.list', 'due')
            ->where('graduates.total', 1)
            ->where('graduates.data.0.id', $due->id)
            ->where('graduates.data.0.name', 'Ana Due')
            ->where('graduates.data.0.activated', false)
            ->where('counts.due', 1)
            ->where('counts.sent', 1));
});

it('previews who "Send now" would remind for a chosen group', function () {
    remindable(now()->subYear());
    remindable(now()->subMonths(4));
    remindable(now()->subWeek());

    $this->actingAs($this->admin)
        ->get('/graduates/reminders?list=manual&target=stale&stale_months=3')
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.target', 'stale')
            ->where('filters.stale_months', 3)
            ->where('graduates.total', 2));

    $this->actingAs($this->admin)
        ->get('/graduates/reminders?list=manual&target=all')
        ->assertInertia(fn (Assert $page) => $page->where('graduates.total', 3));
});

it('lists everyone already reminded, newest first', function () {
    $older = remindable(now()->subYear(), ['last_update_reminder_at' => now()->subMonths(2)]);
    $newer = remindable(now()->subYear(), ['last_update_reminder_at' => now()->subDay()]);
    remindable(now()->subYear()); // never reminded

    $this->actingAs($this->admin)
        ->get('/graduates/reminders?list=sent')
        ->assertInertia(fn (Assert $page) => $page
            ->where('graduates.total', 2)
            ->where('graduates.data.0.id', $newer->id)
            ->where('graduates.data.1.id', $older->id));
});

it('paginates the reminder list', function () {
    foreach (range(1, 7) as $i) {
        remindable(now()->subYear());
    }

    $this->actingAs($this->admin)
        ->get('/graduates/reminders?list=due&per_page=5&page=2')
        ->assertInertia(fn (Assert $page) => $page
            ->where('graduates.current_page', 2)
            ->has('graduates.data', 2));

    $this->actingAs($this->admin)
        ->get('/graduates/reminders?list=due&per_page=5&page=9')
        ->assertRedirect('/graduates/reminders?list=due&per_page=5&page=2');
});

it('keeps the reminder list behind the admin login', function () {
    $this->get('/graduates/reminders')->assertRedirect('/login');
});
