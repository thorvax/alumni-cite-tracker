<?php

use App\Models\AppSetting;
use App\Models\Graduate;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

it('redirects guests from every admin page to the login page', function (string $url) {
    $this->get($url)->assertRedirect('/login');
})->with([
    '/dashboard', '/graduates', '/graduates/create', '/graduates/import', '/graduates/invitations',
    '/reminders/survey', '/reminders/update', '/graduates/reminders', '/graduates/updates',
    '/search', '/reports', '/settings/system',
]);

it('opens every page in the admin menu', function (string $url, string $component) {
    $this->actingAs($this->admin)
        ->get($url)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    ['/graduates/import', 'admin/graduates/import'],
    ['/graduates/invitations', 'admin/graduates/invitations'],
    ['/reminders/survey', 'admin/reminders/survey'],
    ['/reminders/update', 'admin/reminders/update'],
    ['/graduates/reminders', 'admin/graduates/reminders'],
    ['/graduates/updates', 'admin/graduates/updates'],
    ['/settings/system', 'admin/settings'],
]);

it('sends /reminders to the Survey reminders tab', function () {
    $this->actingAs($this->admin)->get('/reminders')->assertRedirect('/reminders/survey');
});

it('keeps each page to one job', function () {
    $this->actingAs($this->admin)
        ->get('/graduates/import')
        ->assertInertia(fn (Assert $page) => $page
            ->has('preview')
            ->missing('recipients')
            ->missing('reminders'));

    $this->actingAs($this->admin)
        ->get('/settings/system')
        ->assertInertia(fn (Assert $page) => $page
            ->has('account')
            ->has('system')
            ->missing('updateReminders'));
});

it('shows how many people are waiting for a survey reminder', function () {
    Graduate::factory()->notAnswered()->create(['invite_status' => 'sent']);
    Graduate::factory()->notAnswered()->create(['invite_status' => 'sent', 'is_demo' => true]); // never emailed

    $this->actingAs($this->admin)
        ->get('/reminders/survey')
        ->assertInertia(fn (Assert $page) => $page->where('reminders.waiting', 1));
});

it('shows dashboard totals', function () {
    Graduate::factory()->create(['employment_status' => 'Employed']);
    Graduate::factory()->create(['employment_status' => 'Self-employed']);
    Graduate::factory()->create(['employment_status' => 'Unemployed']);
    Graduate::factory()->create(['employment_status' => 'Further Studies']);

    $this->actingAs($this->admin)
        ->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->where('stats.total', 4)
            ->where('stats.employed.count', 2)
            ->where('stats.unemployed.count', 1)
            ->where('stats.further.count', 1));
});

it('lists, paginates and searches graduates', function () {
    Graduate::factory()->count(10)->create();
    Graduate::factory()->create(['full_name' => 'Juan Dela Cruz']);

    $this->actingAs($this->admin)
        ->get('/graduates')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/graduates/index')
            ->where('graduates.total', 11)
            ->has('graduates.data', 10)
            ->where('filters.per_page', 10));

    $this->actingAs($this->admin)
        ->get('/graduates?q=Dela+Cruz')
        ->assertInertia(fn (Assert $page) => $page
            ->where('graduates.total', 1)
            ->where('graduates.data.0.name', 'Juan Dela Cruz'));
});

it('shows 5, 10, 25, 50 or 100 graduates per page', function (int $perPage, int $shown) {
    Graduate::factory()->count(30)->create();

    $this->actingAs($this->admin)
        ->get("/graduates?per_page={$perPage}")
        ->assertInertia(fn (Assert $page) => $page
            ->has('graduates.data', $shown)
            ->where('filters.per_page', $perPage)
            ->where('perPageOptions', [5, 10, 25, 50, 100]));
})->with([
    [5, 5],
    [25, 25],
    [100, 30],
]);

it('falls back to 10 per page for a size that is not offered', function () {
    Graduate::factory()->count(30)->create();

    $this->actingAs($this->admin)
        ->get('/graduates?per_page=100000')
        ->assertInertia(fn (Assert $page) => $page
            ->has('graduates.data', 10)
            ->where('filters.per_page', 10));
});

it('opens a typed page number and keeps the search and page size', function () {
    Graduate::factory()->count(12)->create();

    $this->actingAs($this->admin)
        ->get('/graduates?per_page=5&page=3')
        ->assertInertia(fn (Assert $page) => $page
            ->where('graduates.current_page', 3)
            ->has('graduates.data', 2));
});

it('sends a page number past the end to the last page', function () {
    Graduate::factory()->count(12)->create(['full_name' => 'Juan Santos']);

    $this->actingAs($this->admin)
        ->get('/graduates?q=Santos&per_page=5&page=99')
        ->assertRedirect('/graduates?q=Santos&per_page=5&page=3');
});

it('finds graduates by course code and filters by year and status', function () {
    Graduate::factory()->create([
        'course' => 'Bachelor of Science in Information and Technology',
        'year_graduated' => 2023,
        'employment_status' => 'Employed',
    ]);
    Graduate::factory()->create([
        'course' => 'Bachelor of Library in Information Science',
        'year_graduated' => 2023,
        'employment_status' => 'Employed',
    ]);

    $this->actingAs($this->admin)
        ->get('/search?q=BSIT&year=2023&status=Employed')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/search')
            ->where('results.total', 1)
            ->where('results.data.0.course', 'BSIT'));
});

it('adds a graduate', function () {
    $this->actingAs($this->admin)
        ->post('/graduates', [
            'full_name' => 'Maria Santos',
            'birth_date' => '2001-01-15',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'email' => 'maria@example.com',
            'region' => 'Region I (Ilocos Region)',
            'province' => 'Pangasinan',
            'city' => 'City of Urdaneta',
        ])
        ->assertRedirect();

    expect(Graduate::where('full_name', 'Maria Santos')->exists())->toBeTrue();
});

it('validates the add graduate form', function () {
    $this->actingAs($this->admin)
        ->post('/graduates', [])
        ->assertSessionHasErrors(['full_name', 'birth_date', 'gender', 'civil_status', 'region', 'province', 'city']);
});

it('updates a graduate', function () {
    $graduate = Graduate::factory()->create();

    $this->actingAs($this->admin)
        ->patch("/graduates/{$graduate->id}", [
            'full_name' => 'Updated Name',
            'birth_date' => '2000-02-02',
            'gender' => 'Male',
            'civil_status' => 'Married',
            'region' => 'Region I (Ilocos Region)',
            'province' => 'Pangasinan',
            'city' => 'City of Urdaneta',
        ])
        ->assertRedirect("/graduates/{$graduate->id}");

    expect($graduate->refresh()->full_name)->toBe('Updated Name');
});

it('shows a graduate with all profile sections', function () {
    $graduate = Graduate::factory()->create();

    $this->actingAs($this->admin)
        ->get("/graduates/{$graduate->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/graduates/show')
            ->has('sections', 7)
            ->where('sections.0.key', 'personal'));
});

it('sends the details the printed record needs', function () {
    $graduate = Graduate::factory()->invited()->create([
        'student_id' => '20244012',
        'course' => 'Bachelor of Science in Information and Technology',
        'survey_completed_at' => '2026-03-01 10:00:00',
    ]);

    $this->actingAs($this->admin)
        ->get("/graduates/{$graduate->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('graduate.student_id', '20244012')
            ->where('graduate.course_name', 'Bachelor of Science in Information and Technology')
            ->where('graduate.answered_at', 'March 1, 2026')
            ->where('graduate.updated_at', null));
});

it('builds report numbers for a year', function () {
    Graduate::factory()->count(3)->create(['year_graduated' => 2023, 'employment_status' => 'Employed']);
    Graduate::factory()->create(['year_graduated' => 2022, 'employment_status' => 'Unemployed']);

    $this->actingAs($this->admin)
        ->get('/reports?year=2023')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/reports')
            ->where('status.total', 3)
            ->where('status.rate.count', 3));
});

it('updates the account and the system settings', function () {
    $this->actingAs($this->admin)
        ->patch('/settings/system/account', [
            'name' => 'New Name',
            'email' => 'new@ucu.edu.ph',
            'position' => 'Dean',
        ])
        ->assertRedirect();

    expect($this->admin->refresh()->name)->toBe('New Name');

    $this->actingAs($this->admin)
        ->patch('/settings/system', [
            'allow_registration' => false,
            'email_notifications' => true,
            'activity_logs' => true,
        ])
        ->assertRedirect();

    expect(AppSetting::flag('allow_registration'))->toBeFalse();
});
