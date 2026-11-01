<?php

use App\Enums\LivestreamEditorialStatus;
use App\Models\Livestream;
use App\Models\Location;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Livestreaming — halaman arsip publik
|--------------------------------------------------------------------------
|
| Only published recordings with an ended broadcast and a verified replay may
| reach /livestreaming. The active stream is a separate prop and never appears
| in the grid.
|
| @see docs/DECISIONS.md D-101
*/

beforeEach(function (): void {
    // The page chunk is new; CI does not build assets before Pest.
    $this->withoutVite();
});

it('renders the archive for a guest', function () {
    $this->get('/livestreaming')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/livestreaming')
            ->where('activeStream', null)
            ->has('archives.data', 0)
            ->has('years')
            ->has('categories'));
});

it('lists only published ended recordings with a replay', function () {
    $public = Livestream::factory()->ended()->recordingAvailable()->create();
    $live = Livestream::factory()->live()->create();
    Livestream::factory()->published()->create(); // scheduled
    Livestream::factory()->ended()->create(); // no replay
    Livestream::factory()->unavailable()->create();
    Livestream::factory()->ended()->recordingAvailable()->create([
        'status' => LivestreamEditorialStatus::Draft,
    ]);
    Livestream::factory()->ended()->recordingAvailable()->create([
        'status' => LivestreamEditorialStatus::Archived,
    ]);
    $deleted = Livestream::factory()->ended()->recordingAvailable()->create();
    $deleted->delete();

    $this->get('/livestreaming')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('activeStream.id', $live->getKey())
            ->has('archives.data', 1)
            ->where('archives.data.0.id', $public->getKey()));
});

it('orders recordings newest first with a stable tie-breaker', function () {
    $old = Livestream::factory()->ended()->recordingAvailable()->create([
        'recorded_at' => '2024-01-01 10:00:00',
    ]);
    $new = Livestream::factory()->ended()->recordingAvailable()->create([
        'recorded_at' => '2025-01-01 10:00:00',
    ]);

    $this->get('/livestreaming')
        ->assertInertia(fn (Assert $page) => $page
            ->where('archives.data.0.id', $new->getKey())
            ->where('archives.data.1.id', $old->getKey()));
});

it('searches title and description', function () {
    $titleMatch = Livestream::factory()->ended()->recordingAvailable()->create([
        'title' => 'Misa Paskah',
        'description' => 'Perayaan malam Paskah.',
    ]);
    $descriptionMatch = Livestream::factory()->ended()->recordingAvailable()->create([
        'title' => 'Devosi Rosario',
        'description' => 'Doa bersama menyambut Paskah.',
    ]);
    Livestream::factory()->ended()->recordingAvailable()->create([
        'title' => 'Misa Mingguan',
        'description' => 'Minggu biasa.',
    ]);

    $this->get('/livestreaming?search=Paskah')
        ->assertInertia(fn (Assert $page) => $page
            ->has('archives.data', 2)
            ->where('archives.data.0.id', $descriptionMatch->getKey())
            ->where('archives.data.1.id', $titleMatch->getKey()));
});

it('filters by category and year and ignores invalid values', function () {
    $liturgi = Livestream::factory()->ended()->recordingAvailable()->create([
        'category' => 'liturgi',
        'recorded_at' => '2025-01-01 10:00:00',
    ]);
    Livestream::factory()->ended()->recordingAvailable()->create([
        'category' => 'devosi',
        'recorded_at' => '2024-01-01 10:00:00',
    ]);

    $this->get('/livestreaming?kategori=liturgi')
        ->assertInertia(fn (Assert $page) => $page->has('archives.data', 1)->where('archives.data.0.id', $liturgi->getKey()));

    $this->get('/livestreaming?tahun=2024')
        ->assertInertia(fn (Assert $page) => $page->has('archives.data', 1));

    $this->get('/livestreaming?kategori=nope&tahun=abc')
        ->assertInertia(fn (Assert $page) => $page->has('archives.data', 2));
});

it('paginates twelve recordings per page and keeps the filters in the links', function () {
    foreach (range(1, 13) as $index) {
        Livestream::factory()->ended()->recordingAvailable()->create([
            'title' => "Misa Rekaman {$index}",
            'recorded_at' => now()->subDays($index),
        ]);
    }

    $this->get('/livestreaming?search=Rekaman')
        ->assertInertia(fn (Assert $page) => $page
            ->has('archives.data', 12)
            ->where('archives.meta.per_page', 12)
            ->where('archives.meta.total', 13)
            ->where('archives.links.next', fn (string $url): bool => str_contains($url, 'search=Rekaman')));

    $this->get('/livestreaming?search=Rekaman&page=2')
        ->assertInertia(fn (Assert $page) => $page
            ->has('archives.data', 1)
            ->where('archives.meta.current_page', 2));
});

it('offers only the years that have a public recording', function () {
    Livestream::factory()->ended()->recordingAvailable()->create([
        'recorded_at' => '2025-05-01 10:00:00',
    ]);
    Livestream::factory()->ended()->recordingAvailable()->create([
        'recorded_at' => '2024-05-01 10:00:00',
    ]);
    Livestream::factory()->ended()->recordingAvailable()->create([
        'status' => LivestreamEditorialStatus::Draft,
        'recorded_at' => '2023-05-01 10:00:00',
    ]);

    $this->get('/livestreaming')
        ->assertInertia(fn (Assert $page) => $page
            ->where('years', [2025, 2024]));
});

it('shows the location name from the master-data relation', function () {
    $location = Location::factory()->create(['name' => 'Gereja Paroki']);
    $stream = Livestream::factory()->ended()->recordingAvailable()->create([
        'location_id' => $location->getKey(),
    ]);

    $this->get('/livestreaming')
        ->assertInertia(fn (Assert $page) => $page
            ->where('archives.data.0.id', $stream->getKey())
            ->where('archives.data.0.location', 'Gereja Paroki')
            ->missing('archives.data.0.location_id'));
});

it('never leaks internal fields into the public props', function () {
    $stream = Livestream::factory()->ended()->recordingAvailable()->create();
    Livestream::factory()->live()->create();

    $this->get('/livestreaming')
        ->assertInertia(fn (Assert $page) => $page
            ->missing('archives.data.0.youtube_url')
            ->missing('archives.data.0.created_by')
            ->missing('archives.data.0.updated_by')
            ->missing('archives.data.0.deleted_at')
            ->missing('archives.data.0.live_slot')
            ->missing('activeStream.youtube_url')
            ->missing('activeStream.live_slot'));

    expect($stream->fresh()->youtube_url)->toContain('youtube.com');
});
