<?php

use App\Models\Graduate;
use Database\Seeders\DemoGraduatesSeeder;

it('marks seeded graduates as demo data', function () {
    $this->seed(DemoGraduatesSeeder::class);

    expect(Graduate::where('is_demo', true)->count())->toBe(250);
});

it('deletes only the demo graduates', function () {
    Graduate::factory()->count(3)->create(['is_demo' => true]);
    $real = Graduate::factory()->create(['student_id' => '20244012']);

    $this->artisan('cite:delete-demo')
        ->expectsOutput('Found 3 demo graduate(s). 1 real graduate(s) will be kept.')
        ->expectsConfirmation('Delete the demo graduates?', 'yes')
        ->expectsOutput('Deleted 3 demo graduate(s).')
        ->assertSuccessful();

    expect(Graduate::pluck('id')->all())->toBe([$real->id]);
});

it('deletes nothing when the answer is no', function () {
    Graduate::factory()->count(2)->create(['is_demo' => true]);

    $this->artisan('cite:delete-demo')
        ->expectsConfirmation('Delete the demo graduates?', 'no')
        ->expectsOutput('Nothing was deleted.')
        ->assertSuccessful();

    expect(Graduate::count())->toBe(2);
});

it('can skip the question with --force', function () {
    Graduate::factory()->count(2)->create(['is_demo' => true]);

    $this->artisan('cite:delete-demo --force')->assertSuccessful();

    expect(Graduate::count())->toBe(0);
});

it('says so when there is no demo data', function () {
    $this->artisan('cite:delete-demo')
        ->expectsOutput('There are no demo graduates to delete.')
        ->assertSuccessful();
});
