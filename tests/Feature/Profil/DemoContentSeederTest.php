<?php

use App\Models\Event;
use App\Models\MassSchedule;
use App\Models\Media;
use App\Models\ParishBoardPeriod;
use App\Models\ParishProfile;
use App\Models\PastorAssignment;
use App\Models\Post;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Demo content seeder
|--------------------------------------------------------------------------
|
| The sample content is a deliberate divergence from D-14 (D-53), so it gets
| its own tests rather than being folded into the bare-install ones. Those
| tests turn `demo.enabled` off; these turn it on.
|
| Storage is faked so the media pipeline writes into a temporary disk instead of
| the real one; the rows it records are the same either way.
|
*/

beforeEach(function () {
    config(['demo.enabled' => true]);
    Storage::fake('local');
    Storage::fake('public');
});

it('fills every module with published sample content', function () {
    $this->seed(DatabaseSeeder::class);

    expect(ParishProfile::query()->count())->toBe(1)
        ->and(ParishProfile::query()->first()->is_published)->toBeTrue()
        ->and(Post::query()->where('status', 'published')->count())->toBeGreaterThan(0)
        ->and(Event::query()->where('is_published', true)->count())->toBeGreaterThan(0)
        ->and(MassSchedule::query()->count())->toBeGreaterThan(0);
});

it('keeps exactly one pastor in office and one active period', function () {
    $this->seed(DatabaseSeeder::class);

    expect(PastorAssignment::query()->where('is_current', true)->count())->toBe(1)
        ->and(ParishBoardPeriod::query()->where('is_active', true)->count())->toBe(1);
});

it('builds the shared image through the media pipeline', function () {
    $this->seed(DatabaseSeeder::class);

    $media = Media::query()->where('path', 'media/originals/dummy-paroki.jpg')->first();

    expect($media)->not->toBeNull()
        ->and($media->status->value)->toBe('ready')
        ->and($media->variants()->count())->toBeGreaterThan(0);
});

it('is idempotent across a second run', function () {
    $this->seed(DatabaseSeeder::class);

    $before = [
        ParishProfile::query()->count(),
        Post::query()->count(),
        Event::query()->count(),
        MassSchedule::query()->count(),
        Media::query()->count(),
    ];

    $this->seed(DatabaseSeeder::class);

    expect([
        ParishProfile::query()->count(),
        Post::query()->count(),
        Event::query()->count(),
        MassSchedule::query()->count(),
        Media::query()->count(),
    ])->toBe($before);
});

it('is skipped when the demo flag is off', function () {
    config(['demo.enabled' => false]);

    $this->seed(DatabaseSeeder::class);

    expect(ParishProfile::query()->count())->toBe(0)
        ->and(Post::query()->count())->toBe(0);
});
