<?php

use App\Models\Graduate;
use Inertia\Testing\AssertableInertia as Assert;

it('tells the landing page nobody is logged in (button: Log in to the survey)', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('welcome')
            ->where('alumni', null));
});

it('tells the landing page a logged-in graduate has not answered yet (button: Start the survey)', function () {
    $graduate = Graduate::factory()->invited()->create(['last_name' => 'Dela Cruz', 'survey_completed_at' => null]);
    $graduate->forceFill(['password' => 'Secret123', 'activated_at' => now()])->save();

    $this->post('/alumni/login', ['student_id' => $graduate->student_id, 'password' => 'Secret123']);

    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->where('alumni', ['last_name' => 'Dela Cruz', 'answered' => false, 'unread_announcements' => 0]));
});

it('tells the landing page a logged-in graduate already answered (button: Go to My Survey)', function () {
    $graduate = Graduate::factory()->invited()->create(['last_name' => 'Dela Cruz', 'survey_completed_at' => now()]);
    $graduate->forceFill(['password' => 'Secret123', 'activated_at' => now()])->save();

    $this->post('/alumni/login', ['student_id' => $graduate->student_id, 'password' => 'Secret123']);

    // Only the last name, "answered" and the unread count are shared, no other personal data.
    $this->get('/')
        ->assertInertia(fn (Assert $page) => $page
            ->where('alumni', ['last_name' => 'Dela Cruz', 'answered' => true, 'unread_announcements' => 0]));
});
