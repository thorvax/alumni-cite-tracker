<?php

use App\Models\Announcement;
use App\Models\Graduate;
use App\Support\Announcements\Audience;
use App\Support\Announcements\AudienceResolver;

function resolveAudience(string $type, array $filter = []): Audience
{
    return app(AudienceResolver::class)->resolve($type, $filter);
}

it('reaches every graduate for "all", activated or not, but never demo graduates', function () {
    $activated = Graduate::factory()->invited()->create();
    $activated->forceFill(['activated_at' => now()])->save();
    $notActivated = Graduate::factory()->notAnswered()->create();
    $addedByHand = Graduate::factory()->create(); // no Student ID, still on record
    Graduate::factory()->create(['is_demo' => true]);

    $audience = resolveAudience('all');

    expect($audience->graduates->pluck('id')->all())
        ->toEqualCanonicalizing([$activated->id, $notActivated->id, $addedByHand->id])
        ->and($audience->notActivated()->pluck('id')->all())
        ->toEqualCanonicalizing([$notActivated->id, $addedByHand->id]);
});

it('reaches pasted Student ID Numbers and lists the ones not found', function () {
    $first = Graduate::factory()->invited()->create(['student_id' => '20244012']);
    $second = Graduate::factory()->invited()->create(['student_id' => '20244013']);
    Graduate::factory()->invited()->create(['student_id' => '20244014']); // not in the list
    Graduate::factory()->invited()->create(['student_id' => '20240001', 'is_demo' => true]);

    $ids = AudienceResolver::parseStudentIds("20244012, 20244013\n20244013 99999999;20240001");
    $audience = resolveAudience('student_ids', ['student_ids' => $ids]);

    expect($ids)->toBe(['20244012', '20244013', '99999999', '20240001'])
        ->and($audience->graduates->pluck('id')->all())->toBe([$first->id, $second->id])
        // A demo graduate counts as not found: it is never reached.
        ->and($audience->unknownIds)->toBe(['99999999', '20240001']);
});

it('reaches one graduation year or a range, and counts those with no year', function () {
    $in2020 = Graduate::factory()->create(['year_graduated' => 2020]);
    $in2022 = Graduate::factory()->create(['year_graduated' => 2022]);
    Graduate::factory()->create(['year_graduated' => 2024]);
    Graduate::factory()->notAnswered()->create(); // no year on record
    Graduate::factory()->create(['year_graduated' => 2021, 'is_demo' => true]);

    expect(resolveAudience('year', ['from' => 2022, 'to' => 2022])->graduates->pluck('id')->all())
        ->toBe([$in2022->id]);

    // The range works whichever year is typed first.
    $range = resolveAudience('year', ['from' => 2023, 'to' => 2019]);

    expect($range->graduates->pluck('id')->all())->toBe([$in2020->id, $in2022->id])
        ->and($range->withoutYear)->toBe(1);
});

it('counts graduates without an email, who will only see it on the site', function () {
    Graduate::factory()->invited()->create();
    $noEmail = Graduate::factory()->notAnswered()->create(['email' => null]);
    $emptyEmail = Graduate::factory()->notAnswered()->create(['email' => '']);

    $audience = resolveAudience('all');

    expect($audience->count())->toBe(3)
        ->and($audience->withoutEmail()->pluck('id')->all())->toBe([$noEmail->id, $emptyEmail->id]);
});

it('resolves the audience saved on an announcement', function () {
    $graduate = Graduate::factory()->invited()->create(['student_id' => '20231111']);
    Graduate::factory()->invited()->create();

    $announcement = Announcement::factory()->forStudentIds(['20231111', '20230000'])->create();
    $audience = app(AudienceResolver::class)->forAnnouncement($announcement);

    expect($audience->graduates->pluck('id')->all())->toBe([$graduate->id])
        ->and($audience->unknownIds)->toBe(['20230000']);
});
