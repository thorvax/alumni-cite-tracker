<?php

use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\Graduate;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A sent announcement with a recipient row for each graduate given.
 *
 * @param  array<string, mixed>  $attributes
 * @param  list<Graduate>  $recipients
 */
function sentAnnouncement(array $attributes = [], array $recipients = []): Announcement
{
    $announcement = Announcement::factory()->sent()->create($attributes);

    foreach ($recipients as $graduate) {
        AnnouncementRecipient::create([
            'announcement_id' => $announcement->id,
            'graduate_id' => $graduate->id,
            'email_status' => 'sent',
        ]);
    }

    return $announcement;
}

it('never shows a selected announcement to other alumni or the public', function () {
    $recipient = Graduate::factory()->invited()->create();
    $selected = sentAnnouncement(['title' => 'Only for the class of 2020'], [$recipient]);

    // The public page does not list or open it.
    $this->get('/announcements')
        ->assertInertia(fn (Assert $page) => $page->component('announcements/index')->where('announcements.total', 0));
    $this->get("/announcements/{$selected->id}")->assertNotFound();

    // Another graduate does not see it either.
    loginGraduate();
    $this->get('/alumni/announcements')
        ->assertInertia(fn (Assert $page) => $page->where('announcements.total', 0));
    $this->get("/alumni/announcements/{$selected->id}")->assertNotFound();
});

it('shows public announcements to guests, with drafts and scheduled ones hidden', function () {
    $public = sentAnnouncement(['title' => 'Homecoming 2026', 'visibility' => 'public']);
    Announcement::factory()->public()->create(['title' => 'Draft']);
    Announcement::factory()->public()->create(['status' => 'scheduled', 'publish_at' => now()->addDay()]);

    $this->get('/announcements')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('announcements/index')
            ->where('announcements.total', 1)
            ->where('announcements.data.0.title', 'Homecoming 2026'));

    $this->get("/announcements/{$public->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('announcements/show')
            ->where('announcement.title', 'Homecoming 2026'));
});

it('needs the alumni login for the alumni page', function () {
    $this->get('/alumni/announcements')->assertRedirect('/alumni/login');
});

it('lists public ones and the ones sent to the graduate, newest first, with unread dots', function () {
    $graduate = loginGraduate();
    $older = sentAnnouncement(['title' => 'Older, for me', 'published_at' => now()->subDays(3)], [$graduate]);
    sentAnnouncement(['title' => 'Public news', 'visibility' => 'public', 'published_at' => now()->subDay()]);
    sentAnnouncement(['title' => 'Not for me'], [Graduate::factory()->invited()->create()]);

    $this->get('/alumni/announcements')
        ->assertInertia(fn (Assert $page) => $page
            ->component('alumni/announcements/index')
            ->where('announcements.total', 2)
            ->where('announcements.data.0.title', 'Public news')
            ->where('announcements.data.0.unread', false) // public, not sent to them
            ->where('announcements.data.1.title', 'Older, for me')
            ->where('announcements.data.1.unread', true)
            ->where('alumni.unread_announcements', 1));

    // Opening it marks it read.
    $this->get("/alumni/announcements/{$older->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('alumni/announcements/show')
            ->where('announcement.title', 'Older, for me')
            ->where('alumni.unread_announcements', 0));

    expect(AnnouncementRecipient::where('graduate_id', $graduate->id)->sole()->read_at)->not->toBeNull();
});

it('keeps the first read time when opened again', function () {
    $graduate = loginGraduate();
    $announcement = sentAnnouncement([], [$graduate]);
    $firstRead = now()->subDay()->startOfSecond();
    AnnouncementRecipient::query()->update(['read_at' => $firstRead]);

    $this->get("/alumni/announcements/{$announcement->id}")->assertOk();

    expect(AnnouncementRecipient::sole()->read_at?->equalTo($firstRead))->toBeTrue();
});

it('hides announcements past "show until", and events after their date unless "show until" is later', function () {
    $graduate = loginGraduate();
    $expired = sentAnnouncement(['title' => 'Expired', 'show_until' => now()->subMinute()], [$graduate]);
    $pastEvent = sentAnnouncement(['title' => 'Past event', 'type' => 'event', 'event_starts_at' => now()->subDays(2)], [$graduate]);
    sentAnnouncement(['title' => 'Past event, kept', 'type' => 'event', 'event_starts_at' => now()->subDays(2), 'show_until' => now()->addWeek()], [$graduate]);
    sentAnnouncement(['title' => 'Event later today', 'type' => 'event', 'event_starts_at' => now()->addHour()], [$graduate]);
    sentAnnouncement(['title' => 'No end date'], [$graduate]);

    $this->get('/alumni/announcements')
        ->assertInertia(fn (Assert $page) => $page
            ->where('announcements.total', 3)
            ->where('announcements.data', fn ($rows) => collect($rows)->pluck('title')->sort()->values()->all() === [
                'Event later today', 'No end date', 'Past event, kept',
            ]));

    $this->get("/alumni/announcements/{$expired->id}")->assertNotFound();
    $this->get("/alumni/announcements/{$pastEvent->id}")->assertNotFound();
});

it('hides expired public announcements from guests too', function () {
    $expired = sentAnnouncement(['visibility' => 'public', 'show_until' => now()->subDay()]);

    $this->get('/announcements')->assertInertia(fn (Assert $page) => $page->where('announcements.total', 0));
    $this->get("/announcements/{$expired->id}")->assertNotFound();
});

it('marks a public announcement read when a logged-in recipient opens it from the email', function () {
    $graduate = loginGraduate();
    $announcement = sentAnnouncement(['visibility' => 'public'], [$graduate]);

    $this->get("/announcements/{$announcement->id}")->assertOk();

    expect(AnnouncementRecipient::sole()->read_at)->not->toBeNull();
});
