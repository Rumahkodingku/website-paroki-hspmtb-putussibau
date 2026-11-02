<?php

use App\Models\Document;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\Livestream;
use App\Models\Media;
use App\Models\MediaVariant;
use Database\Seeders\Demo\DemoDocumentSeeder;
use Database\Seeders\Demo\DemoGallerySeeder;
use Database\Seeders\Demo\DemoLivestreamingSeeder;
use Database\Seeders\Demo\DemoMassScheduleSeeder;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Demo Gallery, Document, and Livestreaming seeders
|--------------------------------------------------------------------------
|
| These seeders copy the supplied demo assets into the real media/document
| pipeline. Storage is faked so the tests exercise the same file operations
| without writing into the developer's storage directory.
|
| @see docs/DECISIONS.md D-53
*/

beforeEach(function (): void {
    config(['demo.enabled' => true]);

    Storage::fake('local');
    Storage::fake('public');
});

it('seeds five published albums with ten photos each', function () {
    $this->seed(DemoGallerySeeder::class);

    $albums = GalleryAlbum::query()->published()->withCount('photos')->get();

    expect($albums)->toHaveCount(5)
        ->and($albums->pluck('photos_count')->unique()->all())->toBe([10])
        ->and(GalleryPhoto::query()->count())->toBe(50)
        ->and(Media::query()->where('path', 'like', 'media/originals/demo-galeri/%')->count())->toBe(10)
        ->and(MediaVariant::query()->count())->toBe(30);

    $album = GalleryAlbum::query()
        ->published()
        ->with(['photos.media.variants'])
        ->firstOrFail();

    expect($album->cover_photo_id)->not->toBeNull()
        ->and($album->photos)->toHaveCount(10)
        ->and($album->photos->first()->media->status->value)->toBe('ready')
        ->and($album->photos->first()->media->variants)->toHaveCount(3);
});

it('seeds one published featured document on the private disk', function () {
    $this->seed(DemoDocumentSeeder::class);

    $document = Document::query()->with('category')->sole();

    expect($document->status->value)->toBe('published')
        ->and($document->is_featured)->toBeTrue()
        ->and($document->category->slug)->toBe('lainnya')
        ->and($document->mime_type)->toBe('application/pdf')
        ->and($document->file_size)->toBeGreaterThan(0)
        ->and(Storage::disk($document->file_disk)->exists($document->file_path))->toBeTrue();
});

it('seeds a published live stream for the homepage', function () {
    $this->seed(DemoMassScheduleSeeder::class);
    $this->seed(DemoLivestreamingSeeder::class);

    $live = Livestream::currentLive();

    expect($live)->not->toBeNull()
        ->and($live->youtube_video_id)->toBe('PDDOkUq33Sw')
        ->and($live->status->value)->toBe('published')
        ->and($live->broadcast_status->value)->toBe('live')
        ->and($live->live_slot)->toBe(1)
        ->and($live->location_id)->not->toBeNull();
});

it('does not end an existing live stream when seeding', function () {
    $existing = Livestream::factory()->live()->create();

    $this->seed(DemoLivestreamingSeeder::class);

    expect(Livestream::currentLive()?->getKey())->toBe($existing->getKey())
        ->and(Livestream::query()->where('youtube_video_id', 'PDDOkUq33Sw')->exists())->toBeFalse();
});

it('is idempotent across a second run', function () {
    $this->seed(DemoMassScheduleSeeder::class);
    $this->seed(DemoGallerySeeder::class);
    $this->seed(DemoDocumentSeeder::class);
    $this->seed(DemoLivestreamingSeeder::class);

    $before = [
        GalleryAlbum::query()->count(),
        GalleryPhoto::query()->count(),
        Media::query()->count(),
        MediaVariant::query()->count(),
        Document::query()->count(),
        Livestream::query()->count(),
    ];

    $this->seed(DemoGallerySeeder::class);
    $this->seed(DemoDocumentSeeder::class);
    $this->seed(DemoLivestreamingSeeder::class);

    expect([
        GalleryAlbum::query()->count(),
        GalleryPhoto::query()->count(),
        Media::query()->count(),
        MediaVariant::query()->count(),
        Document::query()->count(),
        Livestream::query()->count(),
    ])->toBe($before);
});
