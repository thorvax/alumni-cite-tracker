<?php

use App\Mail\LoginCodeMail;
use App\Models\Graduate;
use App\Services\LoginCodeService;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Mail::fake();

    $this->graduate = Graduate::factory()->create([
        'student_id' => '20244012',
        'last_name' => 'Dela Cruz',
        'email' => 'juan@example.com',
        'survey_completed_at' => now(),
    ]);

    $this->graduate->forceFill([
        'password' => 'OldPass123',
        'activated_at' => now(),
        'privacy_consented_at' => now(),
    ])->save();
});

/** Fills in Forgot password and returns the emailed code. */
function requestReset($test): string
{
    $test->post('/alumni/forgot-password', ['student_id' => '20244012', 'last_name' => 'Dela Cruz'])
        ->assertRedirect('/alumni/verify-code');

    $code = null;
    Mail::assertQueued(LoginCodeMail::class, function (LoginCodeMail $mail) use (&$code) {
        $code = $mail->code;

        return $mail->hasTo('juan@example.com') && $mail->purpose === 'reset';
    });

    return (string) $code;
}

it('shows the forgot password page', function () {
    $this->get('/alumni/forgot-password')->assertOk();
});

it('resets the password with the emailed code', function () {
    $code = requestReset($this);

    $this->post('/alumni/verify-code', ['code' => $code])->assertRedirect('/alumni/set-password');

    // They agreed before, so the checkbox is not asked again.
    $this->post('/alumni/set-password', ['password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])
        ->assertRedirect('/alumni/my-survey');

    $this->assertAuthenticatedAs($this->graduate, 'graduate');
    $this->post('/alumni/logout');

    // Old password no longer works, the new one does.
    $this->post('/alumni/login', ['student_id' => '20244012', 'password' => 'OldPass123'])
        ->assertSessionHasErrors('login');
    $this->post('/alumni/login', ['student_id' => '20244012', 'password' => 'NewPass123'])
        ->assertRedirect('/alumni/my-survey');
});

it('does not reveal the email address on a reset', function () {
    requestReset($this);

    $this->get('/alumni/verify-code')
        ->assertInertia(fn (Assert $page) => $page->where('email', null)->where('purpose', 'reset'));
});

it('looks the same but sends nothing when the details do not match', function (string $studentId, string $lastName) {
    $this->post('/alumni/forgot-password', ['student_id' => $studentId, 'last_name' => $lastName])
        ->assertRedirect('/alumni/verify-code')
        ->assertSessionHasNoErrors();

    Mail::assertNothingQueued();

    $this->get('/alumni/verify-code')
        ->assertInertia(fn (Assert $page) => $page->where('email', null)->where('resendIn', 60));

    // Every code is wrong, with the same message as a real wrong code.
    $this->post('/alumni/verify-code', ['code' => '123456'])
        ->assertSessionHasErrors(['code' => LoginCodeService::WRONG]);
    $this->get('/alumni/set-password')->assertRedirect('/');
})->with([
    'wrong last name' => ['20244012', 'Santos'],
    'unknown student ID' => ['20249999', 'Dela Cruz'],
]);

it('does not reset an account that was never activated', function () {
    $this->graduate->forceFill(['activated_at' => null])->save();

    $this->post('/alumni/forgot-password', ['student_id' => '20244012', 'last_name' => 'Dela Cruz'])
        ->assertRedirect('/alumni/verify-code');

    Mail::assertNothingQueued();
});

it('does not accept an activation code for a reset', function () {
    // A leftover activation code must not unlock the reset flow.
    $activationCode = '111111';
    $this->graduate->loginCodes()->create([
        'code_hash' => bcrypt($activationCode),
        'purpose' => 'activate',
        'expires_at' => now()->addMinutes(10),
    ]);

    requestReset($this);

    $this->post('/alumni/verify-code', ['code' => $activationCode])->assertSessionHasErrors('code');
});

it('requires the privacy checkbox on a reset if they never agreed', function () {
    $this->graduate->forceFill(['privacy_consented_at' => null])->save();
    $code = requestReset($this);
    $this->post('/alumni/verify-code', ['code' => $code]);

    $this->post('/alumni/set-password', ['password' => 'NewPass123', 'password_confirmation' => 'NewPass123'])
        ->assertSessionHasErrors('privacy');
});
