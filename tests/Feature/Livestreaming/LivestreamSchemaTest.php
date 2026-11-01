<?php

use App\Enums\LivestreamBroadcastStatus;
use App\Enums\LivestreamCategory;
use App\Enums\LivestreamEditorialStatus;
use App\Models\Livestream;
use App\Models\Location;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Livestreaming — skema, cast, factory, dan invariant live tunggal
|--------------------------------------------------------------------------
|
| The database is the last line of defence for "one live stream". These tests
| prove the unique live_slot actually refuses a second one, rather than trusting
| only the action's transaction.
|
| @see docs/DECISIONS.md D-101
*/

it('creates a draft scheduled record by default', function () {
    $stream = Livestream::factory()->create();

    expect($stream->status)->toBe(LivestreamEditorialStatus::Draft)
        ->and($stream->broadcast_status)->toBe(LivestreamBroadcastStatus::Scheduled)
        ->and($stream->category)->toBeNull()
        ->and($stream->live_slot)->toBeNull()
        ->and($stream->is_recording_available)->toBeFalse()
        ->and($stream->published_at)->toBeNull()
        ->and($stream->youtube_video_id)->toHaveLength(11);
});

it('has the columns the archive and homepage depend on', function () {
    expect(Schema::hasColumns('livestreams', [
        'title',
        'youtube_video_id',
        'youtube_url',
        'location_id',
        'category',
        'status',
        'broadcast_status',
        'live_slot',
        'scheduled_at',
        'started_at',
        'ended_at',
        'recorded_at',
        'is_recording_available',
        'published_at',
        'created_by',
        'updated_by',
        'deleted_at',
    ]))->toBeTrue();
});

it('casts the enums and datetimes', function () {
    $stream = Livestream::factory()->create([
        'category' => LivestreamCategory::Liturgi,
        'status' => LivestreamEditorialStatus::Published,
        'broadcast_status' => LivestreamBroadcastStatus::Ended,
        'scheduled_at' => now(),
    ]);

    expect($stream->category)->toBe(LivestreamCategory::Liturgi)
        ->and($stream->status)->toBe(LivestreamEditorialStatus::Published)
        ->and($stream->broadcast_status)->toBe(LivestreamBroadcastStatus::Ended)
        ->and($stream->scheduled_at)->not->toBeNull();
});

it('belongs to an optional master location', function () {
    $location = Location::factory()->create();

    $withLocation = Livestream::factory()->forLocation($location)->create();
    $withoutLocation = Livestream::factory()->create(['location_id' => null]);

    expect($withLocation->location?->is($location))->toBeTrue()
        ->and($withoutLocation->location)->toBeNull();
});

it('builds the published, live and archive factory states', function () {
    $published = Livestream::factory()->published()->create();
    expect($published->status)->toBe(LivestreamEditorialStatus::Published)
        ->and($published->broadcast_status)->toBe(LivestreamBroadcastStatus::Scheduled)
        ->and($published->published_at)->not->toBeNull();

    $live = Livestream::factory()->live()->create();
    expect($live->broadcast_status)->toBe(LivestreamBroadcastStatus::Live)
        ->and($live->live_slot)->toBe(1)
        ->and($live->started_at)->not->toBeNull();

    $ended = Livestream::factory()->ended()->create();
    expect($ended->broadcast_status)->toBe(LivestreamBroadcastStatus::Ended)
        ->and($ended->recorded_at)->not->toBeNull()
        ->and($ended->is_recording_available)->toBeFalse();

    $available = Livestream::factory()->ended()->recordingAvailable()->create();
    expect($available->is_recording_available)->toBeTrue()
        ->and($available->recorded_at)->not->toBeNull();

    $unavailable = Livestream::factory()->unavailable()->create();
    expect($unavailable->broadcast_status)->toBe(LivestreamBroadcastStatus::Unavailable);
});

it('refuses a second live row at the database level', function () {
    Livestream::factory()->live()->create();

    expect(fn () => Livestream::factory()->live()->create())
        ->toThrow(QueryException::class);

    expect(Livestream::query()->whereNotNull('live_slot')->count())->toBe(1);
});

it('keeps exactly one current live stream through the model helper', function () {
    Livestream::factory()->ended()->recordingAvailable()->create();
    $live = Livestream::factory()->live()->create();

    expect(Livestream::currentLive()?->getKey())->toBe($live->getKey());
});

it('returns no current live when only drafts or recordings exist', function () {
    Livestream::factory()->published()->create();
    Livestream::factory()->ended()->recordingAvailable()->create();

    expect(Livestream::currentLive())->toBeNull();
});

it('exposes only published ended recordings through archiveReady', function () {
    $public = Livestream::factory()->ended()->recordingAvailable()->create();
    Livestream::factory()->ended()->create(); // no replay
    Livestream::factory()->create([
        'status' => LivestreamEditorialStatus::Draft,
        'broadcast_status' => LivestreamBroadcastStatus::Ended,
        'is_recording_available' => true,
        'recorded_at' => now(),
    ]);

    $ids = Livestream::query()->published()->archiveReady()->pluck('id')->all();

    expect($ids)->toBe([$public->getKey()]);
});

it('soft deletes a record without removing it from the audit store', function () {
    $stream = Livestream::factory()->create();
    $stream->delete();

    expect(Livestream::query()->count())->toBe(0)
        ->and(Livestream::withTrashed()->count())->toBe(1);
});
