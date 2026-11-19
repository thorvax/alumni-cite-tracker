<?php

use App\Mail\LoginCodeMail;
use App\Models\Graduate;
use App\Models\GraduateLoginCode;
use App\Services\LoginCodeService;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->graduate = Graduate::factory()->create([
        'student_id' => '20244012',
        'last_name' => 'Dela Cruz',
        'full_name' => 'Juan Dela Cruz',
        'email' => 'juan@example.com',
        'invite_token' => str_repeat('a', 48),
        'survey_completed_at' => null,
    ]);
});

/** Opens the email link and passes page 1. Returns the emailed code. */
function startActivation($test): string
{
    $test->get('/survey/start/'.str_repeat('a', 48));
    $test->post('/alumni/activate', ['student_id' => '20244012', 'last_name' => 'dela cruz'])
        ->assertRedirect('/alumni/verify-code');

    $code = null;
    Mail::assertQueued(LoginCodeMail::class, function (LoginCodeMail $mail) use (&$code) {
        $code = $mail->code;

        return $mail->hasTo('juan@example.com');
    });

    return (string) $code;
}

function validPassword(array $extra = []): array
{
    return $extra + [
        'password' => 'Secret123',
        'password_confirmation' => 'Secret123',
        'privacy' => true,
    ];
}

it('sends the email link to the identity page', function () {
    $this->get('/survey/start/'.str_repeat('a', 48))->assertRedirect('/alumni/activate');
    $this->get('/alumni/activate')->assertOk();
});

it('ignores an unknown link', function () {
    $this->get('/survey/start/'.str_repeat('z', 48))->assertRedirect('/');
});

it('rejects a wrong last name with one generic message', function () {
    $this->get('/survey/start/'.str_repeat('a', 48));

    $this->post('/alumni/activate', ['student_id' => '20244012', 'last_name' => 'Santos'])
        ->assertSessionHasErrors(['identity' => 'The details you entered do not match our records.'])
        ->assertSessionDoesntHaveErrors(['student_id', 'last_name']);

    Mail::assertNothingQueued();
});

it('rejects a student ID that belongs to the link of someone else', function () {
    Graduate::factory()->create(['student_id' => '20249999', 'last_name' => 'Santos']);
    $this->get('/survey/start/'.str_repeat('a', 48));

    $this->post('/alumni/activate', ['student_id' => '20249999', 'last_name' => 'Santos'])
        ->assertSessionHasErrors('identity');
});

it('stores the code hashed and expiring in 10 minutes', function () {
    $code = startActivation($this);
    $record = GraduateLoginCode::sole();

    expect($record->code_hash)->not->toBe($code)
        ->and($record->purpose)->toBe('activate')
        ->and((int) round(now()->diffInMinutes($record->expires_at)))->toBe(10);
});

it('rejects a wrong code', function () {
    startActivation($this);

    $this->post('/alumni/verify-code', ['code' => '000000'])->assertSessionHasErrors('code');
    $this->get('/alumni/set-password')->assertRedirect('/alumni/verify-code');
});

it('rejects an expired code', function () {
    $code = startActivation($this);
    $this->travel(11)->minutes();

    $this->post('/alumni/verify-code', ['code' => $code])->assertSessionHasErrors('code');
});

it('does not accept the same code twice', function () {
    $code = startActivation($this);

    $this->post('/alumni/verify-code', ['code' => $code])->assertRedirect('/alumni/set-password');

    expect(app(LoginCodeService::class)->verify($this->graduate, 'activate', $code))
        ->toBe('This code has expired or was already used. Please request a new code.');
});

it('locks the code after 5 wrong attempts', function () {
    $code = startActivation($this);

    foreach (range(1, 5) as $i) {
        $this->post('/alumni/verify-code', ['code' => $code === '111111' ? '222222' : '111111']);
    }

    $this->post('/alumni/verify-code', ['code' => $code])
        ->assertSessionHasErrors(['code' => 'Too many wrong attempts. Please request a new code.']);
});

it('allows a new code only once per 60 seconds', function () {
    startActivation($this);

    $this->post('/alumni/verify-code/resend')->assertSessionHasErrors('code');
    Mail::assertQueuedCount(1);

    $this->travel(61)->seconds();
    $this->post('/alumni/verify-code/resend')->assertSessionHasNoErrors();
    Mail::assertQueuedCount(2);
});

it('requires the privacy checkbox', function () {
    $code = startActivation($this);
    $this->post('/alumni/verify-code', ['code' => $code]);

    $this->post('/alumni/set-password', validPassword(['privacy' => false]))
        ->assertSessionHasErrors('privacy');

    expect($this->graduate->fresh()->activated_at)->toBeNull();
});

it('requires a strong password', function () {
    $code = startActivation($this);
    $this->post('/alumni/verify-code', ['code' => $code]);

    $this->post('/alumni/set-password', validPassword(['password' => 'password', 'password_confirmation' => 'password']))
        ->assertSessionHasErrors('password');
});

it('activates the account, logs in and opens the survey', function () {
    $code = startActivation($this);
    $this->post('/alumni/verify-code', ['code' => $code]);

    $this->post('/alumni/set-password', validPassword())->assertRedirect('/survey');

    $graduate = $this->graduate->fresh();

    expect($graduate->activated_at)->not->toBeNull()
        ->and($graduate->privacy_consented_at)->not->toBeNull()
        ->and(password_verify('Secret123', (string) $graduate->password))->toBeTrue();

    $this->assertAuthenticatedAs($graduate, 'graduate');
    $this->assertGuest('web'); // not logged in as an admin
});

it('sends people who already answered to My Survey', function () {
    $this->graduate->update(['survey_completed_at' => now()]);
    $code = startActivation($this);
    $this->post('/alumni/verify-code', ['code' => $code]);

    $this->post('/alumni/set-password', validPassword())->assertRedirect('/alumni/my-survey');
});

it('makes the link open the login page once the account is active', function () {
    $this->graduate->forceFill(['activated_at' => now(), 'password' => 'Secret123'])->save();

    $this->get('/survey/start/'.str_repeat('a', 48))->assertRedirect('/alumni/login');
    $this->get('/alumni/activate')->assertRedirect('/');
});
