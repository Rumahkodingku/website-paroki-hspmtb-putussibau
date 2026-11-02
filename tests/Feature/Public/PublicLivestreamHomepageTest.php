<?php

use App\Models\Livestream;
use App\Models\Location;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Livestreaming — section beranda
|--------------------------------------------------------------------------
|
| The homepage carries a live block only when a published stream is live. An
| ended recording is archive content, not a substitute for a live stream, and
| no placeholder is left behind when the answer is "nothing".
|
| @see docs/DECISIONS.md D-101
*/

beforeEach(function (): void {
    $this->withoutVite();
});

it('sends no live stream and hides the section when nothing is live', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('liveStream', null)
            ->where('sections.livestreaming', false));
});

it('sends the published live stream and enables the section', function () {
    $location = Location::factory()->create(['name' => 'Gereja Paroki']);
    $live = Livestream::factory()->live()->create([
        'location_id' => $location->getKey(),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('liveStream.id', $live->getKey())
            ->where('liveStream.title', $live->title)
            ->where('liveStream.video_id', $live->youtube_video_id)
            ->where('liveStream.location', 'Gereja Paroki')
            ->where('sections.livestreaming', true));
});

it('does not show a draft live record', function () {
    Livestream::factory()->live()->create([
        'status' => 'draft',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('liveStream', null)
            ->where('sections.livestreaming', false));
});

it('does not show an ended recording as the live section', function () {
    Livestream::factory()->ended()->recordingAvailable()->create();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('liveStream', null)
            ->where('sections.livestreaming', false));
});

it('does not leak internal fields through the homepage prop', function () {
    Livestream::factory()->live()->create();

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->missing('liveStream.youtube_url')
            ->missing('liveStream.created_by')
            ->missing('liveStream.updated_by')
            ->missing('liveStream.deleted_at')
            ->missing('liveStream.live_slot'));
});
