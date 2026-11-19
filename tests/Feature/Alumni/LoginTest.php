<?php

use App\Models\Graduate;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->graduate = Graduate::factory()->create([
        'student_id' => '20244012',
        'last_name' => 'Dela Cruz',
        'email' => 'juan@example.com',
        'survey_completed_at' => null,
    ]);

    $this->graduate->forceFill(['password' => 'Secret123', 'activated_at' => now()])->save();
});

it('shows the alumni login page', function () {
    $this->get('/alumni/login')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('alumni/login'));
});

it('logs in with Student ID Number and password and opens the survey', function () {
    $this->post('/alumni/login', ['student_id' => '20244012', 'password' => 'Secret123'])
        ->assertRedirect('/survey');

    $this->assertAuthenticatedAs($this->graduate, 'graduate');
    $this->assertGuest('web');
});

it('sends people who already answered to My Survey', function () {
    $this->graduate->update(['survey_completed_at' => now()]);

    $this->post('/alumni/login', ['student_id' => '20244012', 'password' => 'Secret123'])
        ->assertRedirect('/alumni/my-survey');
});

it('shows one generic message for a wrong password or unknown Student ID', function () {
    $message = 'The Student ID Number or password is incorrect.';

    $this->post('/alumni/login', ['student_id' => '20244012', 'password' => 'Wrong123'])
        ->assertSessionHasErrors(['login' => $message]);

    $this->post('/alumni/login', ['student_id' => '20240000', 'password' => 'Secret123'])
        ->assertSessionHasErrors(['login' => $message]);

    $this->assertGuest('graduate');
});

it('does not let an account that was never activated log in', function () {
    $this->graduate->forceFill(['activated_at' => null])->save();

    $this->post('/alumni/login', ['student_id' => '20244012', 'password' => 'Secret123'])
        ->assertSessionHasErrors('login');

    $this->assertGuest('graduate');
});

it('blocks logins after 5 failed attempts per minute', function () {
    foreach (range(1, 5) as $i) {
        $this->post('/alumni/login', ['student_id' => '20244012', 'password' => 'Wrong123']);
    }

    // Even the right password is refused while blocked.
    $this->post('/alumni/login', ['student_id' => '20244012', 'password' => 'Secret123'])
        ->assertSessionHasErrors('login');
    $this->assertGuest('graduate');

    $this->travel(61)->seconds();

    $this->post('/alumni/login', ['student_id' => '20244012', 'password' => 'Secret123'])
        ->assertRedirect('/survey');
});

it('logs out', function () {
    $this->actingAs($this->graduate, 'graduate')
        ->post('/alumni/logout')
        ->assertRedirect('/alumni/login');

    $this->assertGuest('graduate');
});

it('keeps an admin logged in when a graduate logs out on the same browser', function () {
    $admin = User::factory()->create();

    $this->actingAs($admin)
        ->actingAs($this->graduate, 'graduate')
        ->post('/alumni/logout');

    $this->assertAuthenticatedAs($admin, 'web');
});

it('sends guests from alumni pages to the alumni login', function () {
    $this->get('/alumni/my-survey')->assertRedirect('/alumni/login');
});

it('does not let a graduate into the admin pages', function () {
    // Log in through the real form: actingAs(..., 'graduate') would also make
    // "graduate" the default guard for the rest of the test.
    $this->post('/alumni/login', ['student_id' => '20244012', 'password' => 'Secret123']);
    $this->assertAuthenticatedAs($this->graduate, 'graduate');

    $this->get('/dashboard')->assertRedirect('/login');
    $this->get('/graduates')->assertRedirect('/login');
});

it('never shares the graduate as the admin user on alumni pages', function () {
    // Regression: auth:graduate made the graduate appear as auth.user, and the
    // admin user menu crashed (blank My Survey page).
    $this->post('/alumni/login', ['student_id' => '20244012', 'password' => 'Secret123']);

    $this->get('/alumni/my-survey')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('alumni/my-survey')
            ->where('auth.user', null)
            ->where('alumni.last_name', 'Dela Cruz'));
});

it('opens My Survey from the login page when already logged in', function () {
    $this->actingAs($this->graduate, 'graduate')
        ->get('/alumni/login')
        ->assertRedirect('/alumni/my-survey');
});
