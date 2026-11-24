<?php

use App\Jobs\SendAnnouncementEmail;
use App\Mail\AnnouncementMail;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\Graduate;
use App\Models\User;
use App\Services\AnnouncementService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function announcementForm(array $overrides = []): array
{
    return [
        'type' => 'announcement',
        'title' => 'Alumni Homecoming',
        'body' => "Line one\nLine two",
        'event_starts_at' => '',
        'event_place' => '',
        'event_link' => '',
        'audience_type' => 'all',
        'student_ids' => '',
        'year_from' => '',
        'year_to' => '',
        'visibility' => 'selected',
        'send_email' => true,
        'show_until' => '',
        'publish_at' => '',
        'action' => 'draft',
        ...$overrides,
    ];
}

it('is for admins only', function () {
    $this->get('/admin/announcements')->assertRedirect('/login');
    $this->post('/admin/announcements', announcementForm())->assertRedirect('/login');

    // A logged-in graduate is not an admin. (Real login, because actingAs(..., 'graduate')
    // would also make "graduate" the default guard.)
    $graduate = Graduate::factory()->invited()->create();
    $graduate->forceFill(['password' => 'Secret123', 'activated_at' => now()])->save();
    $this->post('/alumni/login', ['student_id' => $graduate->student_id, 'password' => 'Secret123']);
    $this->assertAuthenticatedAs($graduate, 'graduate');
    $this->get('/admin/announcements')->assertRedirect('/login');

    expect(Announcement::count())->toBe(0);
});

it('lists the history with email and read counts', function () {
    $announcement = Announcement::factory()->sent()->create(['title' => 'Job fair']);
    $graduates = Graduate::factory()->count(4)->invited()->create();
    foreach (['sent', 'sent', 'failed', 'skipped'] as $i => $status) {
        AnnouncementRecipient::create([
            'announcement_id' => $announcement->id,
            'graduate_id' => $graduates[$i]->id,
            'email_status' => $status,
            'read_at' => $i === 0 ? now() : null,
        ]);
    }
    Announcement::factory()->create(['title' => 'Draft one']);

    $this->actingAs($this->admin)
        ->get('/admin/announcements')
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/announcements/index')
            ->where('announcements.total', 2)
            ->where('announcements.data.0.title', 'Draft one') // newest first
            ->where('announcements.data.1.counts', [
                'recipients' => 4, 'sent' => 2, 'failed' => 1, 'no_email' => 1, 'read' => 1,
            ]));
});

it('saves a draft without sending anything', function () {
    Queue::fake();
    Graduate::factory()->invited()->create();

    $this->actingAs($this->admin)
        ->post('/admin/announcements', announcementForm())
        ->assertRedirect();

    $announcement = Announcement::sole();

    expect($announcement->status)->toBe('draft')
        ->and($announcement->created_by)->toBe($this->admin->id)
        ->and($announcement->recipients()->count())->toBe(0);
    Queue::assertNothingPushed();
});

it('sends now: queues emails 2 seconds apart, skips people without email, never includes demo graduates', function () {
    Queue::fake();
    $this->freezeTime(); // so a slow run cannot shift the measured delays
    $first = Graduate::factory()->invited()->create();
    $second = Graduate::factory()->invited()->create();
    $noEmail = Graduate::factory()->notAnswered()->create(['email' => null]);
    $demo = Graduate::factory()->create(['is_demo' => true]);

    $this->actingAs($this->admin)
        ->post('/admin/announcements', announcementForm(['action' => 'send']))
        ->assertRedirect();

    $announcement = Announcement::sole();
    $statuses = $announcement->recipients()->pluck('email_status', 'graduate_id');

    expect($announcement->status)->toBe('sent')
        ->and($announcement->published_at)->not->toBeNull()
        ->and($statuses->all())->toBe([
            $first->id => 'queued',
            $second->id => 'queued',
            $noEmail->id => 'skipped',
        ])
        ->and($statuses->has($demo->id))->toBeFalse();

    Queue::assertPushed(SendAnnouncementEmail::class, 2);
    $delays = collect(Queue::pushed(SendAnnouncementEmail::class))
        ->map(fn (SendAnnouncementEmail $job) => (int) round(now()->diffInSeconds($job->delay)))
        ->sort()->values()->all();
    expect($delays)->toBe([0, 2]);
});

it('only posts on the site when "also send by email" is off', function () {
    Queue::fake();
    Graduate::factory()->invited()->create();

    $this->actingAs($this->admin)
        ->post('/admin/announcements', announcementForm(['action' => 'send', 'send_email' => false]));

    expect(AnnouncementRecipient::sole()->email_status)->toBe('skipped');
    Queue::assertNothingPushed();
});

it('keeps targeted announcements private and checks the pasted Student IDs', function () {
    $this->actingAs($this->admin)
        ->post('/admin/announcements', announcementForm([
            'audience_type' => 'year', 'year_from' => 2020, 'visibility' => 'public',
        ]))
        ->assertSessionHasErrors('visibility');

    $this->actingAs($this->admin)
        ->post('/admin/announcements', announcementForm([
            'audience_type' => 'student_ids', 'student_ids' => '20244012, 1234, abc',
        ]))
        ->assertSessionHasErrors(['student_ids' => 'Student ID Numbers must be 8 digits. Check: 1234, abc']);

    expect(Announcement::count())->toBe(0);
});

it('needs a date and place for events, and a future time to schedule', function () {
    $this->actingAs($this->admin)
        ->post('/admin/announcements', announcementForm(['type' => 'event']))
        ->assertSessionHasErrors(['event_starts_at', 'event_place']);

    $this->actingAs($this->admin)
        ->post('/admin/announcements', announcementForm(['action' => 'schedule', 'publish_at' => now()->subHour()->format('Y-m-d\TH:i')]))
        ->assertSessionHasErrors('publish_at');
});

it('schedules an announcement without sending it yet', function () {
    Queue::fake();
    Graduate::factory()->invited()->create();
    $when = now()->addDay()->startOfHour();

    $this->actingAs($this->admin)
        ->post('/admin/announcements', announcementForm(['action' => 'schedule', 'publish_at' => $when->format('Y-m-d\TH:i')]));

    $announcement = Announcement::sole();
    expect($announcement->status)->toBe('scheduled')
        ->and($announcement->publish_at?->equalTo($when))->toBeTrue()
        ->and($announcement->recipients()->count())->toBe(0);
    Queue::assertNothingPushed();
});

it('does not send a private announcement that reaches nobody', function () {
    $this->actingAs($this->admin)
        ->post('/admin/announcements', announcementForm([
            'action' => 'send', 'audience_type' => 'student_ids', 'student_ids' => '20990001',
        ]))
        ->assertSessionHasErrors('audience_type');

    expect(Announcement::count())->toBe(0);
});

it('previews who it reaches, unknown IDs and who has not activated', function () {
    $activated = Graduate::factory()->invited()->create(['student_id' => '20244012']);
    $activated->forceFill(['activated_at' => now()])->save();
    Graduate::factory()->notAnswered()->create(['student_id' => '20244013', 'email' => null, 'last_name' => 'Reyes']);

    $this->actingAs($this->admin)
        ->postJson('/admin/announcements/preview', [
            'audience_type' => 'student_ids',
            'student_ids' => "20244012\n20244013, 20249999",
        ])
        ->assertOk()
        ->assertJson([
            'reach' => 2,
            'withoutEmail' => 1,
            'unknownIds' => ['20249999'],
            'notActivated' => 1,
            'notActivatedNames' => [['student_id' => '20244013', 'name' => 'Reyes']],
        ]);
});

it('records sent or failed for each email', function () {
    Mail::fake();
    $graduate = Graduate::factory()->invited()->create();
    $announcement = Announcement::factory()->sent()->create();
    $recipient = AnnouncementRecipient::create([
        'announcement_id' => $announcement->id, 'graduate_id' => $graduate->id, 'email_status' => 'queued',
    ]);

    (new SendAnnouncementEmail($recipient->id))->handle();

    Mail::assertSent(AnnouncementMail::class, fn (AnnouncementMail $mail) => $mail->hasTo($graduate->email));
    expect($recipient->fresh()->email_status)->toBe('sent')
        ->and($recipient->fresh()->emailed_at)->not->toBeNull();

    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP is down'));
    $recipient->update(['email_status' => 'queued']);

    (new SendAnnouncementEmail($recipient->id))->handle();

    expect($recipient->fresh()->email_status)->toBe('failed')
        ->and($recipient->fresh()->email_error)->toBe('SMTP is down');
});

it('resends one email, or all failed ones', function () {
    Queue::fake();
    $announcement = Announcement::factory()->sent()->create();
    $failed = Graduate::factory()->count(2)->invited()->create()->map(fn (Graduate $g) => AnnouncementRecipient::create([
        'announcement_id' => $announcement->id, 'graduate_id' => $g->id, 'email_status' => 'failed', 'email_error' => 'Oops',
    ]));
    $noEmail = AnnouncementRecipient::create([
        'announcement_id' => $announcement->id,
        'graduate_id' => Graduate::factory()->notAnswered()->create(['email' => null])->id,
        'email_status' => 'skipped',
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/announcements/{$announcement->id}/recipients/{$failed[0]->id}/resend")
        ->assertRedirect();
    expect($failed[0]->fresh()->email_status)->toBe('queued')
        ->and($failed[0]->fresh()->email_error)->toBeNull();

    // Nobody to email.
    $this->actingAs($this->admin)
        ->post("/admin/announcements/{$announcement->id}/recipients/{$noEmail->id}/resend")
        ->assertStatus(422);

    $this->actingAs($this->admin)
        ->post("/admin/announcements/{$announcement->id}/resend-failed")
        ->assertSessionHas('status', 'Queued 1 email(s) again.');

    Queue::assertPushed(SendAnnouncementEmail::class, 2);
});

it('does not resend a recipient of another announcement', function () {
    $announcement = Announcement::factory()->sent()->create();
    $other = Announcement::factory()->sent()->create();
    $recipient = AnnouncementRecipient::create([
        'announcement_id' => $other->id,
        'graduate_id' => Graduate::factory()->invited()->create()->id,
        'email_status' => 'failed',
    ]);

    $this->actingAs($this->admin)
        ->post("/admin/announcements/{$announcement->id}/recipients/{$recipient->id}/resend")
        ->assertNotFound();
});

it('only lets a sent announcement fix the title, message and show-until date', function () {
    $announcement = Announcement::factory()->sent()->create(['type' => 'announcement', 'audience_type' => 'all']);

    $this->actingAs($this->admin)
        ->put("/admin/announcements/{$announcement->id}", announcementForm([
            'title' => 'Fixed title',
            'body' => 'Fixed body',
            'show_until' => '2030-01-31',
            'type' => 'event',
            'audience_type' => 'year',
            'action' => 'send',
        ]))
        ->assertSessionHas('status', 'Saved. The email was already sent, so only the site shows the change.');

    $announcement->refresh();
    expect($announcement->title)->toBe('Fixed title')
        ->and($announcement->body)->toBe('Fixed body')
        ->and($announcement->show_until?->format('Y-m-d H:i'))->toBe('2030-01-31 23:59')
        ->and($announcement->type)->toBe('announcement')
        ->and($announcement->audience_type)->toBe('all');
});

it('deletes drafts but never sent announcements', function () {
    $draft = Announcement::factory()->create();
    $sent = Announcement::factory()->sent()->create();

    $this->actingAs($this->admin)->delete("/admin/announcements/{$sent->id}")->assertStatus(422);
    $this->actingAs($this->admin)->delete("/admin/announcements/{$draft->id}")->assertRedirect('/admin/announcements');

    expect(Announcement::pluck('id')->all())->toBe([$sent->id]);
});

it('sends a saved draft from its detail page', function () {
    Queue::fake();
    Graduate::factory()->invited()->create();
    $draft = Announcement::factory()->create();

    $this->actingAs($this->admin)
        ->get("/admin/announcements/{$draft->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/announcements/show')
            ->where('preview.reach', 1));

    $this->actingAs($this->admin)->post("/admin/announcements/{$draft->id}/send")->assertRedirect();

    expect($draft->fresh()->status)->toBe('sent');
    Queue::assertPushed(SendAnnouncementEmail::class, 1);

    // Sending twice is refused.
    $this->actingAs($this->admin)->post("/admin/announcements/{$draft->id}/send")->assertStatus(422);
});

it('escapes the message in the email and links to the right page', function () {
    $graduate = Graduate::factory()->notAnswered()->create();

    $selected = Announcement::factory()->sent()->event()->create([
        'body' => "<script>alert(1)</script>\nSecond line",
    ]);
    $html = (new AnnouncementMail($selected, $graduate))->render();

    expect($html)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;<br />')
        ->not->toContain('<script>alert(1)')
        ->toContain(route('alumni.announcements.show', $selected))
        // Not activated yet: their personal link is included.
        ->toContain(route('survey.invite', ['token' => $graduate->invite_token]))
        ->toContain('UCU Main Campus, CITE Building');

    $public = Announcement::factory()->sent()->public()->create();
    $html = (new AnnouncementMail($public, $graduate))->render();

    expect($html)->toContain(route('announcements.show', $public))
        ->not->toContain(route('survey.invite', ['token' => $graduate->invite_token]));
});

it('publishes only once', function () {
    Queue::fake();
    Graduate::factory()->invited()->create();
    $announcement = Announcement::factory()->create();
    $service = app(AnnouncementService::class);

    expect($service->publish($announcement))->toBe(1)
        ->and($service->publish($announcement->fresh()))->toBe(0)
        ->and($announcement->recipients()->count())->toBe(1);
});
