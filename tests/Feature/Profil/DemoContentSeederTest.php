<?php

use App\Http\Resources\Public\Pelayanan\PublicServiceDetailResource;
use App\Models\Event;
use App\Models\MassSchedule;
use App\Models\Media;
use App\Models\ParishBoardPeriod;
use App\Models\ParishHistoryNarrative;
use App\Models\ParishProfile;
use App\Models\PastorAssignment;
use App\Models\Post;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceFaq;
use App\Models\ServiceRequirement;
use App\Models\ServiceStep;
use App\Services\SiteSettingsService;
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

    $settings = app(SiteSettingsService::class);

    $profile = ParishProfile::current();

    expect(ParishHistoryNarrative::query()->count())->toBe(1)
        ->and($settings->get('short_description'))->not->toBeNull()
        ->and($settings->get('logo'))->not->toBeNull()
        ->and($profile->description)->not->toBeNull()
        ->and($profile->established_year)->toBe(1956)
        ->and($profile->cover_media_id)->not->toBeNull()
        ->and(Post::query()->where('status', 'published')->count())->toBeGreaterThan(0)
        ->and(Event::query()->where('is_published', true)->count())->toBeGreaterThan(0)
        ->and(MassSchedule::query()->count())->toBeGreaterThan(0);
});

it('fills and activates the service module for preview', function () {
    $this->seed(DatabaseSeeder::class);

    $services = Service::query()->active()->get();

    // ServiceSeeder creates the six services inactive; the demo seeder activates
    // them so /pelayanan is populated. Production never reaches this branch.
    expect($services)->toHaveCount(7);

    foreach ($services as $service) {
        expect($service->requirements()->count())->toBeGreaterThan(0)
            ->and($service->steps()->count())->toBeGreaterThan(0)
            ->and($service->faqs()->count())->toBeGreaterThan(0);
    }

    // All four CTA states of PRD 7.6 are represented, so each can be reviewed.
    $states = $services
        ->map(fn (Service $service): string => (string) PublicServiceDetailResource::make($service)
            ->toArray(request())['cta']['state'])
        ->unique()
        ->sort()
        ->values()
        ->all();

    expect($states)->toBe(['contact', 'emergency', 'form_closed', 'form_open']);
});

it('gives every category a description and every service a thumbnail', function () {
    $this->seed(DatabaseSeeder::class);

    expect(ServiceCategory::query()->whereNull('description')->count())->toBe(0)
        ->and(Service::query()->whereNull('thumbnail_media_id')->count())->toBe(0);
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
        ParishHistoryNarrative::query()->count(),
        Post::query()->count(),
        Event::query()->count(),
        MassSchedule::query()->count(),
        Media::query()->count(),
        Service::query()->count(),
        ServiceRequirement::query()->count(),
        ServiceStep::query()->count(),
        ServiceFaq::query()->count(),
    ];

    $this->seed(DatabaseSeeder::class);

    expect([
        ParishHistoryNarrative::query()->count(),
        Post::query()->count(),
        Event::query()->count(),
        MassSchedule::query()->count(),
        Media::query()->count(),
        Service::query()->count(),
        ServiceRequirement::query()->count(),
        ServiceStep::query()->count(),
        ServiceFaq::query()->count(),
    ])->toBe($before);
});

it('is skipped when the demo flag is off', function () {
    config(['demo.enabled' => false]);

    $this->seed(DatabaseSeeder::class);

    expect(ParishHistoryNarrative::query()->count())->toBe(0)
        ->and(Post::query()->count())->toBe(0);
});
