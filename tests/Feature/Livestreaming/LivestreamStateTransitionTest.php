<?php

use App\Models\AuditEvent;
use App\Models\Livestream;

/*
|--------------------------------------------------------------------------
| Livestreaming — transisi status siaran
|--------------------------------------------------------------------------
|
| The operational state is changed only through the start/end/unavailable
| endpoints. These tests pin the one-live invariant, the timestamps, the
| editorial guard, and the audit trail.
|
| @see docs/DECISIONS.md D-101
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function livestreamStatePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Misa Minggu Pagi',
        'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'description' => 'Perayaan Ekaristi Minggu pagi.',
        'livestream_category_id' => null,
        'location' => 'Gereja Paroki HSPMTB',
        'scheduled_at' => '2026-11-08T08:00:00+07:00',
        'recorded_at' => null,
        'is_recording_available' => false,
        'status' => 'published',
    ], $overrides);
}

it('starts a published scheduled stream', function () {
    $stream = Livestream::factory()->published()->create();

    $this->actingAs(superAdmin())
        ->post(route('livestreams.start', $stream))
        ->assertRedirect();

    $stream->refresh();

    expect($stream->broadcast_status->value)->toBe('live')
        ->and($stream->live_slot)->toBe(1)
        ->and($stream->started_at)->not->toBeNull()
        ->and($stream->ended_at)->toBeNull()
        ->and(AuditEvent::query()
            ->where('module', 'livestreams')
            ->where('action', 'broadcast_started')
            ->exists())->toBeTrue();
});

it('ends the previous live stream when another starts', function () {
    $first = Livestream::factory()->live()->create();
    $second = Livestream::factory()->published()->create();

    $this->actingAs(superAdmin())
        ->post(route('livestreams.start', $second))
        ->assertRedirect();

    $first->refresh();
    $second->refresh();

    expect($first->broadcast_status->value)->toBe('ended')
        ->and($first->live_slot)->toBeNull()
        ->and($first->ended_at)->not->toBeNull()
        ->and($first->recorded_at)->not->toBeNull()
        ->and($second->broadcast_status->value)->toBe('live')
        ->and($second->live_slot)->toBe(1)
        ->and(Livestream::query()->whereNotNull('live_slot')->count())->toBe(1);

    $ended = AuditEvent::query()
        ->where('module', 'livestreams')
        ->where('action', 'broadcast_ended')
        ->where('subject_id', (string) $first->getKey())
        ->first();

    expect($ended)->not->toBeNull()
        ->and($ended->metadata['replaced_by'] ?? null)->toBe($second->getKey());

    $started = AuditEvent::query()
        ->where('module', 'livestreams')
        ->where('action', 'broadcast_started')
        ->where('subject_id', (string) $second->getKey())
        ->first();

    expect($started)->not->toBeNull()
        ->and($started->metadata['previous_live_id'] ?? null)->toBe($first->getKey());
});

it('ends a live stream without assuming a replay exists', function () {
    $stream = Livestream::factory()->live()->create();

    $this->actingAs(superAdmin())
        ->post(route('livestreams.end', $stream))
        ->assertRedirect();

    $stream->refresh();

    expect($stream->broadcast_status->value)->toBe('ended')
        ->and($stream->live_slot)->toBeNull()
        ->and($stream->ended_at)->not->toBeNull()
        ->and($stream->recorded_at)->not->toBeNull()
        ->and($stream->is_recording_available)->toBeFalse()
        ->and(AuditEvent::query()
            ->where('module', 'livestreams')
            ->where('action', 'broadcast_ended')
            ->exists())->toBeTrue();
});

it('refuses to end a stream that is not live', function () {
    $stream = Livestream::factory()->published()->create();

    $this->actingAs(superAdmin())
        ->post(route('livestreams.end', $stream))
        ->assertSessionHasErrors('broadcast_status');

    expect($stream->fresh()->broadcast_status->value)->toBe('scheduled');
});

it('refuses to mark a live stream unavailable', function () {
    $stream = Livestream::factory()->live()->create();

    $this->actingAs(superAdmin())
        ->post(route('livestreams.unavailable', $stream))
        ->assertSessionHasErrors('broadcast_status');

    expect($stream->fresh()->broadcast_status->value)->toBe('live')
        ->and($stream->fresh()->live_slot)->toBe(1);
});

it('marks a scheduled stream unavailable and clears its slot', function () {
    $stream = Livestream::factory()->published()->create();

    $this->actingAs(superAdmin())
        ->post(route('livestreams.unavailable', $stream))
        ->assertRedirect();

    expect($stream->fresh()->broadcast_status->value)->toBe('unavailable')
        ->and($stream->fresh()->live_slot)->toBeNull()
        ->and(AuditEvent::query()
            ->where('module', 'livestreams')
            ->where('action', 'broadcast_unavailable')
            ->exists())->toBeTrue();
});

it('refuses to unpublish or archive a live stream through the form', function () {
    $stream = Livestream::factory()->live()->create();

    foreach (['draft', 'archived'] as $status) {
        $this->actingAs(superAdmin())
            ->patch(route('livestreams.update', $stream), livestreamStatePayload([
                'status' => $status,
            ]))
            ->assertSessionHasErrors('status');

        expect($stream->fresh()->status->value)->toBe('published')
            ->and($stream->fresh()->broadcast_status->value)->toBe('live')
            ->and($stream->fresh()->live_slot)->toBe(1);
    }
});

it('refuses to start a draft or an ended stream', function () {
    $draft = Livestream::factory()->create();
    $ended = Livestream::factory()->ended()->create();

    $this->actingAs(superAdmin())
        ->post(route('livestreams.start', $draft))
        ->assertSessionHasErrors('broadcast_status');

    $this->actingAs(superAdmin())
        ->post(route('livestreams.start', $ended))
        ->assertSessionHasErrors('broadcast_status');

    expect($draft->fresh()->broadcast_status->value)->toBe('scheduled')
        ->and($ended->fresh()->broadcast_status->value)->toBe('ended');
});

it('does not record a successful start when the start is refused', function () {
    $draft = Livestream::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('livestreams.start', $draft))
        ->assertSessionHasErrors('broadcast_status');

    expect(AuditEvent::query()
        ->where('module', 'livestreams')
        ->where('action', 'broadcast_started')
        ->count())->toBe(0);
});

it('clears the slot when a live record is deleted', function () {
    $live = Livestream::factory()->live()->create();

    $this->actingAs(superAdmin())
        ->delete(route('livestreams.destroy', $live))
        ->assertRedirect(route('livestreams.index'));

    expect(
        Livestream::withTrashed()->whereKey($live->getKey())->first()?->live_slot,
    )->toBeNull();

    $next = Livestream::factory()->published()->create();

    $this->actingAs(superAdmin())
        ->post(route('livestreams.start', $next))
        ->assertRedirect();

    expect($next->fresh()->broadcast_status->value)->toBe('live')
        ->and(Livestream::query()->whereNotNull('live_slot')->count())->toBe(1);
});
