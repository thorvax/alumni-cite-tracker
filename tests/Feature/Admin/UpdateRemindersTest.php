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

it('emails the personal link that opens the login', function () {
    Mail::fake();
    $graduate = answeredOn(now()->subYear());

    (new SendUpdateReminder($graduate->id))->handle();

    Mail::assertSent(UpdateReminderMail::class, function (UpdateReminderMail $mail) use ($graduate) {
        return $mail->hasTo($graduate->email)
            && str_contains($mail->render(), '/survey/start/'.$graduate->invite_token);
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
        ->get('/settings/system')
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
        ->patch('/settings/system/update-reminders', ['enabled' => true, 'months' => 3])
        ->assertSessionHasErrors(['months' => 'Choose every 6 or every 12 months.']);
});
