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
})->with(['/dashboard', '/graduates', '/graduates/create', '/search', '/reports', '/settings/system']);

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
            ->has('graduates.data', 8));

    $this->actingAs($this->admin)
        ->get('/graduates?q=Dela+Cruz')
        ->assertInertia(fn (Assert $page) => $page
            ->where('graduates.total', 1)
            ->where('graduates.data.0.name', 'Juan Dela Cruz'));
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

it('builds report numbers for a year', function () {
    Graduate::factory()->count(3)->create(['year_graduated' => 2023, 'employment_status' => 'Employed']);
    Graduate::factory()->create(['year_graduated' => 2022, 'employment_status' => 'Unemployed']);

    $this->actingAs($this->admin)
        ->get('/reports?year=2023')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/reports')
            ->where('employment.total', 3)
            ->where('employment.employed.count', 3));
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
