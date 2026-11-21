<?php

use App\Jobs\SendAnnouncementEmail;
use App\Models\Announcement;
use App\Models\Graduate;
use App\Services\AnnouncementService;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

it('sends scheduled announcements whose time has come, and leaves the rest alone', function () {
    Queue::fake();
    Graduate::factory()->count(2)->invited()->create();

    $due = Announcement::factory()->create(['status' => 'scheduled', 'publish_at' => now()->subMinute()]);
    $later = Announcement::factory()->create(['status' => 'scheduled', 'publish_at' => now()->addHour()]);
    $draft = Announcement::factory()->create(['publish_at' => null]);

    $this->artisan('cite:publish-announcements')
        ->expectsOutputToContain("Sent \"{$due->title}\" (queued 2 email(s)).")
        ->assertSuccessful();

    expect($due->fresh()->status)->toBe('sent')
        ->and($due->fresh()->published_at)->not->toBeNull()
        ->and($due->recipients()->count())->toBe(2)
        ->and($later->fresh()->status)->toBe('scheduled')
        ->and($draft->fresh()->status)->toBe('draft');
    Queue::assertPushed(SendAnnouncementEmail::class, 2);

    // Running again sends nothing twice.
    $this->artisan('cite:publish-announcements')
        ->expectsOutput('No scheduled announcements are due.')
        ->assertSuccessful();
    Queue::assertPushed(SendAnnouncementEmail::class, 2);
});

it('shows a scheduled announcement to alumni only after it is sent', function () {
    Queue::fake();
    $graduate = loginGraduate();
    $scheduled = Announcement::factory()->create([
        'title' => 'Reunion',
        'status' => 'scheduled',
        'publish_at' => now()->addMinutes(10),
    ]);

    $this->get("/alumni/announcements/{$scheduled->id}")->assertNotFound();

    $this->travel(11)->minutes();
    $this->artisan('cite:publish-announcements')->assertSuccessful();

    $this->get('/alumni/announcements')
        ->assertInertia(fn (Assert $page) => $page
            ->where('announcements.data.0.title', 'Reunion')
            ->where('announcements.data.0.unread', true));
    expect($scheduled->recipients()->where('graduate_id', $graduate->id)->exists())->toBeTrue();
});

it('cannot send the same announcement twice, even from an old copy', function () {
    Queue::fake();
    Graduate::factory()->invited()->create();
    $announcement = Announcement::factory()->create(['status' => 'scheduled', 'publish_at' => now()->subMinute()]);
    $staleCopy = Announcement::find($announcement->id);

    $service = app(AnnouncementService::class);

    expect($service->publish($announcement))->toBe(1)
        // e.g. "Send now" clicked while the scheduler was already sending it.
        ->and($service->publish($staleCopy))->toBe(0)
        ->and($announcement->recipients()->count())->toBe(1);
    Queue::assertPushed(SendAnnouncementEmail::class, 1);
});

it('runs every 15 minutes', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn (Event $event) => str_contains((string) $event->command, 'cite:publish-announcements'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('*/15 * * * *');
});
