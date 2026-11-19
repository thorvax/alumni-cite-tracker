<?php

use App\Jobs\SendSurveyInvitation;
use App\Jobs\SendUpdateReminder;
use App\Mail\UpdateReminderMail;
use App\Models\AppSetting;
use App\Models\Graduate;
use App\Models\User;
use App\Services\UpdateReminderService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

/** An uploaded graduate who answered the survey on the given date. */
function answeredOn(DateTimeInterface $date, array $attributes = []): Graduate
{
    return Graduate::factory()->invited()->create($attributes + [
        'survey' => ['need-help' => ['help_needed' => 'Tips']],
        'survey_completed_at' => $date,
    ]);
}

beforeEach(function () {
    AppSetting::set('update_reminder_months', 6);
});

it('finds alumni whose last answer is older than the chosen months', function () {
    $old = answeredOn(now()->subMonths(7));
    answeredOn(now()->subMonths(2)); // too recent
    answeredOn(now()->subYear(), ['survey_updated_at' => now()->subMonth()]); // updated recently
    answeredOn(now()->subYear(), ['last_update_reminder_at' => now()->subMonth()]); // reminded recently
    answeredOn(now()->subYear(), ['email' => null]); // nobody to email
    answeredOn(now()->subYear(), ['is_demo' => true]); // fake demo address
    Graduate::factory()->notAnswered()->create(['invite_sent_at' => now()->subYear()]); // never answered

    expect((new UpdateReminderService)->due()->pluck('id')->all())->toBe([$old->id]);
});

it('uses 12 months when the admin picks it', function () {
    AppSetting::set('update_reminder_months', 12);
    answeredOn(now()->subMonths(7));
    $old = answeredOn(now()->subMonths(13));

    expect((new UpdateReminderService)->due()->pluck('id')->all())->toBe([$old->id]);
});

it('reminds again one period after the last reminder if they did not update', function () {
    answeredOn(now()->subYears(2), ['last_update_reminder_at' => now()->subMonths(7)]);

    expect((new UpdateReminderService)->due()->count())->toBe(1);
});

it('queues each due reminder once and records the time', function () {
    Queue::fake();
    $graduate = answeredOn(now()->subMonths(7));

    $this->artisan('cite:send-reminders', ['--force' => true])->assertSuccessful();
    $this->artisan('cite:send-reminders', ['--force' => true])->assertSuccessful();

    Queue::assertPushed(SendUpdateReminder::class, 1);
    expect($graduate->fresh()->last_update_reminder_at)->not->toBeNull();
});

it('does nothing while update reminders are switched off', function () {
    Queue::fake();
    AppSetting::set('reminder_hour', (int) now()->format('G'));
    AppSetting::set('update_reminder_enabled', false);
    answeredOn(now()->subYear());

    $this->artisan('cite:send-reminders')->assertSuccessful();

    Queue::assertNotPushed(SendUpdateReminder::class);
});

it('sends at the chosen hour when switched on', function () {
    Queue::fake();
    AppSetting::set('reminder_hour', (int) now()->format('G'));
    AppSetting::set('update_reminder_enabled', true);
    answeredOn(now()->subYear());

    $this->artisan('cite:send-reminders')->assertSuccessful();

    Queue::assertPushed(SendUpdateReminder::class, 1);
});

it('emails a link to the alumni login page, not account activation', function () {
    Mail::fake();
    $graduate = answeredOn(now()->subYear());
    $graduate->forceFill(['activated_at' => now()->subYear(), 'password' => 'Secret123'])->save();

    (new SendUpdateReminder($graduate->id))->handle();

    Mail::assertSent(UpdateReminderMail::class, function (UpdateReminderMail $mail) use ($graduate) {
        $html = $mail->render();

        return $mail->hasTo($graduate->email)
            && str_contains($html, route('alumni.login'))
            && str_contains($html, 'Log in to update my answers')
            && ! str_contains($html, '/survey/start/'); // no activation link for activated accounts
    });
});

it('also gives the activation link to someone who never activated their account', function () {
    Mail::fake();
    $graduate = answeredOn(now()->subYear()); // answered before accounts existed

    (new SendUpdateReminder($graduate->id))->handle();

    Mail::assertSent(UpdateReminderMail::class, function (UpdateReminderMail $mail) use ($graduate) {
        $html = $mail->render();

        return str_contains($html, route('alumni.login'))
            && str_contains($html, "Haven't activated your account yet?")
            && str_contains($html, '/survey/start/'.$graduate->invite_token);
    });
});

it('never sends survey reminders to demo graduates', function () {
    Queue::fake();
    Graduate::factory()->notAnswered()->create(['is_demo' => true, 'invite_sent_at' => now()->subMonth()]);

    $this->artisan('cite:send-reminders', ['--force' => true])->assertSuccessful();

    Queue::assertNotPushed(SendSurveyInvitation::class);
});

it('saves the update reminder settings from the admin Settings page', function () {
    $admin = User::factory()->create();
    answeredOn(now()->subYear());

    $this->actingAs($admin)
        ->get('/reminders/update')
        ->assertInertia(fn (Assert $page) => $page
            ->where('updateReminders.enabled', false)
            ->where('updateReminders.months', 6)
            ->where('updateReminders.due', 1));

    $this->actingAs($admin)
        ->patch('/settings/system/update-reminders', ['enabled' => true, 'months' => 12])
        ->assertRedirect();

    expect(AppSetting::flag('update_reminder_enabled', false))->toBeTrue()
        ->and(UpdateReminderService::months())->toBe(12);

    $this->actingAs($admin)
        ->patch('/settings/system/update-reminders', ['enabled' => true, 'months' => 5])
        ->assertSessionHasErrors(['months' => 'Choose every 3, 6, 9 or 12 months.']);
});

it('sends update reminders now to alumni who have not updated, even when automatic reminders are off', function () {
    Queue::fake();
    AppSetting::set('update_reminder_enabled', false);
    AppSetting::set('reminder_hour', ((int) now()->format('G') + 5) % 24); // not the chosen hour
    answeredOn(now()->subYear());
    answeredOn(now()->subYear());
    answeredOn(now()->subMonth()); // updated within 6 months: not included

    $this->actingAs(User::factory()->create())
        ->post('/settings/system/update-reminders/send', ['target' => 'stale', 'stale_months' => 6])
        ->assertRedirect()
        ->assertSessionHas('status', 'Queued 2 update reminder(s).');

    Queue::assertPushed(SendUpdateReminder::class, 2);
});

it('sends one graduate an update reminder from Graduate Details', function () {
    Queue::fake();
    $admin = User::factory()->create();
    $graduate = answeredOn(now()->subMonth()); // reminded by hand even if not due yet

    $this->actingAs($admin)
        ->get("/graduates/{$graduate->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('updateReminder.can', true)
            ->where('updateReminder.last', null));

    $this->actingAs($admin)
        ->post("/graduates/{$graduate->id}/update-reminder")
        ->assertSessionHas('status', 'Update reminder queued for this graduate.');

    Queue::assertPushed(SendUpdateReminder::class, fn (SendUpdateReminder $job) => $job->graduateId === $graduate->id);

    // Recorded, so the automatic schedule waits a full period.
    expect($graduate->fresh()->last_update_reminder_at)->not->toBeNull()
        ->and((new UpdateReminderService)->due()->count())->toBe(0);
});

it('does not send an update reminder to someone who cannot get one', function (array $attributes) {
    Queue::fake();
    $graduate = Graduate::factory()->invited()->create($attributes);

    $this->actingAs(User::factory()->create())
        ->post("/graduates/{$graduate->id}/update-reminder")
        ->assertSessionHas('status');

    Queue::assertNothingPushed();
})->with([
    'not answered yet' => [['survey_completed_at' => null]],
    'no email' => [['survey_completed_at' => now(), 'email' => null]],
    'demo data' => [['survey_completed_at' => now(), 'is_demo' => true]],
]);

it('keeps the manual send buttons behind the admin login', function () {
    $graduate = answeredOn(now()->subYear());

    $this->post('/settings/system/update-reminders/send')->assertRedirect('/login');
    $this->post("/graduates/{$graduate->id}/update-reminder")->assertRedirect('/login');
});
