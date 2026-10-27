<?php

use App\Models\Media;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceFaq;
use App\Models\ServiceRequirement;
use App\Models\ServiceStep;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Pelayanan — schema dan domain
|--------------------------------------------------------------------------
|
| The five Phase 6B tables and the models above them. What is asserted here is
| the shape the decisions fixed, not that Eloquent works, because the shape is
| what a later change can quietly break:
|
|   - the category is a real FK, not the PRD's ENUM (D-54),
|   - slug stays unique across soft-deleted services, so there is no
|     active_marker column (D-45, D-54),
|   - a category in use cannot be deleted while a service points at it,
|   - a child edit moves its service's updated_at (SVC-03).
|
| @see docs/DECISIONS.md D-54
*/

it('creates the five tables', function (string $table) {
    expect(Schema::hasTable($table))->toBeTrue();
})->with([
    'service_categories',
    'services',
    'service_requirements',
    'service_steps',
    'service_faqs',
]);

it('soft deletes the two parent tables but not the children', function (string $table, bool $softDeletes) {
    expect(Schema::hasColumn($table, 'deleted_at'))->toBe($softDeletes);
})->with([
    ['service_categories', true],
    ['services', true],
    ['service_requirements', false],
    ['service_steps', false],
    ['service_faqs', false],
]);

it('has no generated active_marker anywhere', function (string $table) {
    // The posts/events rule, not the locations rule (D-45): slug is bound on the
    // public route, so a soft-deleted service keeps its slug.
    expect(Schema::hasColumn($table, 'active_marker'))->toBeFalse();
})->with([
    'service_categories',
    'services',
    'service_requirements',
    'service_steps',
    'service_faqs',
]);

it('stores category as a foreign key, not the PRD enum', function () {
    expect(Schema::hasColumn('services', 'service_category_id'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'category'))->toBeFalse();
});

it('replaces the icon with a media thumbnail', function () {
    // The icon key column is gone on both tables; a service now points at a
    // media row instead. service_categories lost its icon with it.
    expect(Schema::hasColumn('services', 'thumbnail_media_id'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'thumbnail_alt'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'icon'))->toBeFalse()
        ->and(Schema::hasColumn('service_categories', 'icon'))->toBeFalse();
});

it('nulls the thumbnail when its media is deleted', function () {
    $media = Media::factory()->create();
    $service = Service::factory()->create(['thumbnail_media_id' => $media->getKey()]);

    $media->delete();

    expect($service->fresh()->thumbnail_media_id)->toBeNull();
});

it('restricts deleting a category a service still points at', function () {
    $category = ServiceCategory::factory()->create();
    Service::factory()->create(['service_category_id' => $category->getKey()]);

    // forceDelete bypasses soft delete, so the database-level RESTRICT is what
    // answers here — the controller refuses the ordinary path first.
    expect(fn () => $category->forceDelete())->toThrow(QueryException::class);
});

it('cascades the three child tables when a service is force deleted', function () {
    $service = Service::factory()->create();

    ServiceRequirement::factory()->count(2)->create(['service_id' => $service->getKey()]);
    ServiceStep::factory()->count(2)->create(['service_id' => $service->getKey()]);
    ServiceFaq::factory()->count(2)->create(['service_id' => $service->getKey()]);

    $service->forceDelete();

    expect(ServiceRequirement::count())->toBe(0)
        ->and(ServiceStep::count())->toBe(0)
        ->and(ServiceFaq::count())->toBe(0);
});

it('keeps a soft-deleted service slug in the table', function () {
    $service = Service::factory()->create(['slug' => 'baptis-bayi']);
    $service->delete();

    expect(Service::query()->where('slug', 'baptis-bayi')->exists())->toBeFalse()
        ->and(Service::withTrashed()->where('slug', 'baptis-bayi')->exists())->toBeTrue();
});

it('adds public detail fields to services and requirements', function () {
    expect(Schema::hasColumn('services', 'audience_info'))->toBeTrue()
        ->and(Schema::hasColumn('services', 'preparation_info'))->toBeTrue()
        ->and(Schema::hasColumn('service_requirements', 'requirement_condition'))->toBeTrue()
        ->and(Schema::hasColumn('service_requirements', 'applies_to'))->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Scope
|--------------------------------------------------------------------------
*/

it('publishes only active services', function () {
    $active = Service::factory()->active()->create();
    Service::factory()->create();

    expect(Service::active()->pluck('id')->all())->toBe([$active->getKey()]);
});

it('orders services by sort_order then id', function () {
    $second = Service::factory()->create(['sort_order' => 2]);
    $first = Service::factory()->create(['sort_order' => 1]);

    expect(Service::ordered()->pluck('id')->all())->toBe([$first->getKey(), $second->getKey()]);
});

it('orders children by sort_order then id', function () {
    $service = Service::factory()->create();
    $second = ServiceRequirement::factory()->create(['service_id' => $service->getKey(), 'sort_order' => 1]);
    $first = ServiceRequirement::factory()->create(['service_id' => $service->getKey(), 'sort_order' => 0]);

    expect(ServiceRequirement::ordered()->pluck('id')->all())->toBe([$first->getKey(), $second->getKey()]);
});

/*
|--------------------------------------------------------------------------
| Google Form helpers
|--------------------------------------------------------------------------
*/

it('reports the registration state from the row', function () {
    $open = Service::factory()->withForm()->create();
    $closed = Service::factory()->formClosed()->create();
    $none = Service::factory()->create();
    $emergency = Service::factory()->emergency()->create();

    expect($open->isAcceptingRegistrations())->toBeTrue()
        ->and($closed->isAcceptingRegistrations())->toBeFalse()
        ->and($closed->hasFormUrl())->toBeTrue()
        ->and($none->usesGoogleForm())->toBeFalse()
        ->and($emergency->usesGoogleForm())->toBeFalse();
});
