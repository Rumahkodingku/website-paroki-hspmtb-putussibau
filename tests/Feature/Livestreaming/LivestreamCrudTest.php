<?php

use App\Models\AuditEvent;
use App\Models\Livestream;
use App\Models\Location;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| Livestreaming — CRUD dan validasi URL
|--------------------------------------------------------------------------
|
| The write path is SaveLivestreamAction, so these tests assert both the
| persisted row and the derived fields: youtube_video_id, published_at,
| recorded_at, and the audit columns.
|
| @see docs/DECISIONS.md D-101
*/

afterEach(function (): void {
    Carbon::setTestNow();
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function livestreamPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Misa Minggu Pagi',
        'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'description' => 'Perayaan Ekaristi Minggu pagi dari Gereja Paroki.',
        'livestream_category_id' => null,
        'location_id' => null,
        'scheduled_at' => '2026-11-08T08:00:00+07:00',
        'recorded_at' => null,
        'is_recording_available' => false,
        'status' => 'draft',
    ], $overrides);
}

it('creates a draft and extracts the video id server-side', function () {
    $this->actingAs(superAdmin())
        ->post(route('livestreams.store'), livestreamPayload([
            'youtube_video_id' => 'evilevil123',
        ]))
        ->assertRedirect(route('livestreams.edit', Livestream::query()->sole()));

    $stream = Livestream::query()->sole();

    expect($stream->title)->toBe('Misa Minggu Pagi')
        ->and($stream->youtube_video_id)->toBe('dQw4w9WgXcQ')
        ->and($stream->status->value)->toBe('draft')
        ->and($stream->published_at)->toBeNull()
        ->and($stream->created_by)->not->toBeNull()
        ->and($stream->updated_by)->not->toBeNull();
});

it('stamps published_at on the first publication and keeps it afterwards', function () {
    Carbon::setTestNow('2026-11-01 08:00:00');

    $this->actingAs(superAdmin())
        ->post(route('livestreams.store'), livestreamPayload(['status' => 'published']))
        ->assertSessionHasNoErrors();

    $stream = Livestream::query()->sole();
    $publishedAt = $stream->published_at?->toIso8601String();

    expect($publishedAt)->not->toBeNull();

    Carbon::setTestNow('2026-11-02 08:00:00');

    $this->actingAs(superAdmin())
        ->patch(route('livestreams.update', $stream), livestreamPayload([
            'status' => 'archived',
        ]))
        ->assertSessionHasNoErrors();

    expect($stream->fresh()->published_at?->toIso8601String())->toBe($publishedAt);

    $this->actingAs(superAdmin())
        ->patch(route('livestreams.update', $stream), livestreamPayload([
            'status' => 'published',
        ]))
        ->assertSessionHasNoErrors();

    expect($stream->fresh()->published_at?->toIso8601String())->toBe($publishedAt);
});

it('defaults recorded_at when a replay is marked available', function () {
    $stream = Livestream::factory()->create(['ended_at' => Carbon::parse('2026-11-01 10:00:00')]);

    $this->actingAs(superAdmin())
        ->patch(route('livestreams.update', $stream), livestreamPayload([
            'status' => 'published',
            'is_recording_available' => true,
            'recorded_at' => null,
        ]))
        ->assertSessionHasNoErrors();

    expect($stream->fresh()->is_recording_available)->toBeTrue()
        ->and($stream->fresh()->recorded_at?->toDateTimeString())->toBe('2026-11-01 10:00:00');
});

it('accepts the supported YouTube URL shapes', function (string $url) {
    $this->actingAs(superAdmin())
        ->post(route('livestreams.store'), livestreamPayload(['youtube_url' => $url]))
        ->assertSessionHasNoErrors();

    expect(Livestream::query()->sole()->youtube_video_id)->toBe('dQw4w9WgXcQ');
})->with([
    'watch' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'short' => 'https://youtu.be/dQw4w9WgXcQ',
    'embed' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
    'shorts' => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
    'live' => 'https://www.youtube.com/live/dQw4w9WgXcQ',
]);

it('rejects a non-YouTube URL, a lookalike host and an invalid id', function (string $url) {
    $this->actingAs(superAdmin())
        ->post(route('livestreams.store'), livestreamPayload(['youtube_url' => $url]))
        ->assertSessionHasErrors('youtube_url');

    expect(Livestream::query()->count())->toBe(0);
})->with([
    'other host' => 'https://example.test/watch?v=dQw4w9WgXcQ',
    'lookalike host' => 'https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ',
    'invalid id' => 'https://youtu.be/too-short',
    'raw iframe' => '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>',
]);

it('rejects a title that is too short and leaves nothing behind', function () {
    $this->actingAs(superAdmin())
        ->post(route('livestreams.store'), livestreamPayload(['title' => 'Hi']))
        ->assertSessionHasErrors('title');

    expect(Livestream::query()->count())->toBe(0);
});

it('updates metadata without losing the derived fields', function () {
    $stream = Livestream::factory()->published()->create();
    $location = Location::factory()->create(['name' => 'Kapel Santa Maria']);

    $this->actingAs(superAdmin())
        ->patch(route('livestreams.update', $stream), livestreamPayload([
            'title' => 'Misa Minggu Sore',
            'status' => 'published',
            'livestream_category_id' => null,
            'location_id' => $location->getKey(),
        ]))
        ->assertSessionHasNoErrors();

    $stream->refresh();

    expect($stream->title)->toBe('Misa Minggu Sore')
        ->and($stream->livestream_category_id)->toBeNull()
        ->and($stream->location?->name)->toBe('Kapel Santa Maria')
        ->and($stream->youtube_video_id)->toBe('dQw4w9WgXcQ')
        ->and($stream->published_at)->not->toBeNull();
});

it('accepts an active location and rejects an inactive one', function () {
    $active = Location::factory()->create(['name' => 'Gereja Paroki']);
    $inactive = Location::factory()->create([
        'name' => 'Kapel Lama',
        'is_active' => false,
    ]);

    $this->actingAs(superAdmin())
        ->post(route('livestreams.store'), livestreamPayload([
            'location_id' => $active->getKey(),
        ]))
        ->assertSessionHasNoErrors();

    expect(Livestream::query()->sole()->location_id)->toBe($active->getKey());

    $this->actingAs(superAdmin())
        ->post(route('livestreams.store'), livestreamPayload([
            'title' => 'Misa di Kapel Lama',
            'location_id' => $inactive->getKey(),
        ]))
        ->assertSessionHasErrors('location_id');

    $this->actingAs(superAdmin())
        ->post(route('livestreams.store'), livestreamPayload([
            'title' => 'Misa Tanpa Lokasi',
            'location_id' => 999999,
        ]))
        ->assertSessionHasErrors('location_id');

    expect(Livestream::query()->count())->toBe(1);
});

it('soft deletes a record and audits it', function () {
    $stream = Livestream::factory()->create();

    $this->actingAs(superAdmin())
        ->delete(route('livestreams.destroy', $stream))
        ->assertRedirect(route('livestreams.index'));

    expect(Livestream::query()->count())->toBe(0)
        ->and(Livestream::withTrashed()->count())->toBe(1)
        ->and(AuditEvent::query()
            ->where('module', 'livestreams')
            ->where('action', 'deleted')
            ->exists())->toBeTrue();
});

it('records created and published audit events', function () {
    $this->actingAs(superAdmin())
        ->post(route('livestreams.store'), livestreamPayload(['status' => 'draft']))
        ->assertSessionHasNoErrors();

    expect(AuditEvent::query()->where('module', 'livestreams')->where('action', 'created')->exists())->toBeTrue();

    $stream = Livestream::query()->sole();

    $this->actingAs(superAdmin())
        ->patch(route('livestreams.update', $stream), livestreamPayload(['status' => 'published']))
        ->assertSessionHasNoErrors();

    expect(AuditEvent::query()->where('module', 'livestreams')->where('action', 'published')->exists())->toBeTrue();
});
