<?php

use App\Jobs\SendSurveyInvitation;
use App\Mail\LoginCodeMail;
use App\Models\Graduate;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

/** A valid Add Graduate form, plus the given school records. */
function graduateForm(array $records = []): array
{
    return $records + [
        'full_name' => 'Maria Dela Cruz',
        'birth_date' => '2001-01-15',
        'gender' => 'Female',
        'civil_status' => 'Single',
        'email' => 'maria@example.com',
        'region' => 'Region I (Ilocos Region)',
        'province' => 'Pangasinan',
        'city' => 'City of Urdaneta',
    ];
}

it('saves course, year and status from the Add Graduate form', function () {
    $this->actingAs($this->admin)->post('/graduates', graduateForm([
        'course' => 'Bachelor of Science in Information and Technology',
        'year_graduated' => '2024',
        'employment_status' => 'Self-employed',
    ]))->assertRedirect();

    $graduate = Graduate::sole();

    expect($graduate->courseCode())->toBe('BSIT')
        ->and($graduate->year_graduated)->toBe(2024)
        ->and($graduate->employment_status)->toBe('Self-employed')
        ->and($graduate->invite_token)->toBeNull(); // no Student ID: no survey link
});

it('gives a graduate with a Student ID a survey link and emails it', function () {
    Queue::fake();

    $this->actingAs($this->admin)->post('/graduates', graduateForm([
        'student_id' => '20244012',
        'last_name' => 'Dela Cruz',
        'send_invite' => true,
    ]))->assertRedirect();

    $graduate = Graduate::sole();

    expect($graduate->invite_token)->toHaveLength(48)
        ->and($graduate->invitationStatus())->toBe('queued');

    Queue::assertPushed(SendSurveyInvitation::class, 1);
});

it('does not email the link when "Email the survey link now" is unticked', function () {
    Queue::fake();

    $this->actingAs($this->admin)->post('/graduates', graduateForm([
        'student_id' => '20244012',
        'last_name' => 'Dela Cruz',
        'send_invite' => false,
    ]));

    expect(Graduate::sole()->invitationStatus())->toBe('pending');
    Queue::assertNothingPushed();
});

it('lists a graduate without an email under "No email" on the Invitations page', function () {
    Queue::fake();

    $this->actingAs($this->admin)->post('/graduates', graduateForm([
        'email' => '',
        'student_id' => '20244012',
        'last_name' => 'Dela Cruz',
        'send_invite' => true,
    ]));

    Queue::assertNothingPushed();

    $this->actingAs($this->admin)
        ->get('/graduates/invitations')
        ->assertInertia(fn (Assert $page) => $page
            ->has('noEmail', 1)
            ->where('noEmail.0.student_id', '20244012'));
});

it('checks the Student ID, Last Name, course and year', function () {
    Graduate::factory()->create(['student_id' => '20244012']);

    $this->actingAs($this->admin)
        ->post('/graduates', graduateForm(['student_id' => '2024', 'course' => 'Nursing', 'year_graduated' => '1900']))
        ->assertSessionHasErrors(['student_id', 'last_name', 'course', 'year_graduated']);

    $this->actingAs($this->admin)
        ->post('/graduates', graduateForm(['student_id' => '20244012', 'last_name' => 'Cruz']))
        ->assertSessionHasErrors(['student_id' => 'Another graduate already has this Student ID Number.']);
});

it('gives an older graduate a survey link when a Student ID is added on edit', function () {
    $graduate = Graduate::factory()->create(['student_id' => null]);

    $this->actingAs($this->admin)
        ->get("/graduates/{$graduate->id}/edit")
        ->assertInertia(fn (Assert $page) => $page->where('hasLink', false));

    $this->actingAs($this->admin)
        ->patch("/graduates/{$graduate->id}", graduateForm(['student_id' => '20244012', 'last_name' => 'Dela Cruz']))
        ->assertRedirect("/graduates/{$graduate->id}");

    expect($graduate->refresh()->invite_token)->not->toBeNull()
        ->and($graduate->invitationStatus())->toBe('pending');
});

it('keeps the same Student ID on edit without a "taken" error', function () {
    $graduate = Graduate::factory()->invited()->create(['student_id' => '20244012', 'last_name' => 'Dela Cruz']);
    $token = $graduate->invite_token;

    $this->actingAs($this->admin)
        ->patch("/graduates/{$graduate->id}", graduateForm(['student_id' => '20244012', 'last_name' => 'Dela Cruz']))
        ->assertSessionHasNoErrors();

    expect($graduate->refresh()->invite_token)->toBe($token); // the link does not change
});

it('lets a graduate added by hand activate their account from the link', function () {
    Queue::fake();
    Mail::fake();

    $this->actingAs($this->admin)->post('/graduates', graduateForm([
        'student_id' => '20244012',
        'last_name' => 'Dela Cruz',
    ]));
    $this->post('/logout'); // the admin leaves; the graduate opens their link

    $graduate = Graduate::sole();

    $this->get('/survey/start/'.$graduate->invite_token)->assertRedirect('/alumni/activate');
    $this->post('/alumni/activate', ['student_id' => '20244012', 'last_name' => 'dela cruz'])
        ->assertRedirect('/alumni/verify-code');

    Mail::assertQueued(LoginCodeMail::class, fn (LoginCodeMail $mail) => $mail->hasTo('maria@example.com'));
});
