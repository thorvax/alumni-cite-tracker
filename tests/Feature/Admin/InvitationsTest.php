<?php

use App\Models\Graduate;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->create();
});

it('counts each email status with the same rules as the status badges', function () {
    Graduate::factory()->count(2)->notAnswered()->create(['invite_status' => 'sent']);
    Graduate::factory()->notAnswered()->create(['invite_status' => 'failed']);
    Graduate::factory()->notAnswered()->create(['invite_status' => 'queued']);
    Graduate::factory()->notAnswered()->create(['invite_status' => 'pending', 'invite_sent_at' => null]);
    Graduate::factory()->notAnswered()->create(['invite_status' => null, 'invite_sent_at' => null]); // pending too
    Graduate::factory()->notAnswered()->create(['email' => null]); // no email
    Graduate::factory()->invited()->create(['survey_completed_at' => now()]); // answered
    Graduate::factory()->create(); // added by hand without a link: not listed

    $graduates = Graduate::query()->invited()->get();

    $this->actingAs($this->admin)
        ->get('/graduates/invitations')
        ->assertInertia(fn (Assert $page) => $page
            ->where('counts', [
                'total' => 8,
                'pending' => 2,
                'queued' => 1,
                'sent' => 2,
                'failed' => 1,
                'answered' => 1,
                'no_email' => 1,
            ])
            // Same answer as the badge on each row.
            ->where('counts.sent', $graduates->filter(fn (Graduate $g) => $g->invitationStatus() === 'sent')->count())
            ->where('recipients.total', 7)
            ->where('noEmail.total', 1));
});

it('pages the email status list and keeps the status filter', function () {
    Graduate::factory()->count(12)->notAnswered()->create(['invite_status' => 'sent']);
    Graduate::factory()->count(3)->notAnswered()->create(['invite_status' => 'failed']);

    $this->actingAs($this->admin)
        ->get('/graduates/invitations?per_page=5&page=3')
        ->assertInertia(fn (Assert $page) => $page
            ->where('perPage', 5)
            ->where('recipients.total', 15)
            ->where('recipients.current_page', 3)
            ->has('recipients.data', 5));

    $this->actingAs($this->admin)
        ->get('/graduates/invitations?status=sent&per_page=10&page=2')
        ->assertInertia(fn (Assert $page) => $page
            ->where('filter', 'sent')
            ->where('recipients.total', 12)
            ->has('recipients.data', 2)
            ->where('recipients.data.0.status', 'sent'));
});

it('pages the No email list separately', function () {
    Graduate::factory()->count(7)->notAnswered()->create(['email' => null]);
    Graduate::factory()->count(3)->notAnswered()->create(['invite_status' => 'sent']);

    $this->actingAs($this->admin)
        ->get('/graduates/invitations?per_page=5&no_email_page=2')
        ->assertInertia(fn (Assert $page) => $page
            ->where('noEmail.total', 7)
            ->where('noEmail.current_page', 2)
            ->has('noEmail.data', 2)
            // The email status list stays on its first page.
            ->where('recipients.current_page', 1));
});

it('sends a page number past the end to the last page', function () {
    Graduate::factory()->count(6)->notAnswered()->create(['invite_status' => 'sent']);

    $this->actingAs($this->admin)
        ->get('/graduates/invitations?per_page=5&page=9')
        ->assertRedirect('/graduates/invitations?per_page=5&page=2&no_email_page=1');
});

it('falls back to 10 per page for a size that is not offered', function () {
    Graduate::factory()->count(12)->notAnswered()->create(['invite_status' => 'sent']);

    $this->actingAs($this->admin)
        ->get('/graduates/invitations?per_page=1000')
        ->assertInertia(fn (Assert $page) => $page
            ->where('perPage', 10)
            ->has('recipients.data', 10));
});
