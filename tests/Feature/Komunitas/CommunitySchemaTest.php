<?php

use App\Models\Community;
use App\Models\CommunityCategory;
use App\Models\Media;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Komunitas — schema dan domain
|--------------------------------------------------------------------------
|
| The Community tables and the models above them. What is asserted here is the
| shape the decisions fixed, not that Eloquent works, because the shape is what
| a later change can quietly break:
|
|   - category is a real FK, not the PRD's `community_types` (D-55 point 1),
|   - logo/thumbnail are media FKs, not path columns (D-55 point 2),
|   - slug stays unique across soft-deleted rows, so there is no active_marker.
|
| The area tables moved to their own module (D-57); their schema is asserted in
| tests/Feature/Area/AreaSchemaTest.php.
|
| @see docs/DECISIONS.md D-55, D-57, D-59
*/

it('creates the community tables', function (string $table) {
    expect(Schema::hasTable($table))->toBeTrue();
})->with([
    'community_categories',
    'communities',
]);

it('has a nullable column for each of the five social links', function (string $column) {
    expect(Schema::hasColumn('communities', $column))->toBeTrue();
})->with(['facebook', 'instagram', 'tiktok', 'youtube', 'website']);

it('no longer has the community_area pivot', function () {
    // The community↔area relation was removed in D-57.
    expect(Schema::hasTable('community_area'))->toBeFalse();
});

it('soft deletes the community tables', function (string $table) {
    expect(Schema::hasColumn($table, 'deleted_at'))->toBeTrue();
})->with([
    'community_categories',
    'communities',
]);

it('has no generated active_marker anywhere', function (string $table) {
    // The posts/events/services rule, not the locations rule (D-44/D-45): the
    // public route binds {community:slug}, so a soft-deleted row keeps its slug.
    expect(Schema::hasColumn($table, 'active_marker'))->toBeFalse();
})->with([
    'community_categories',
    'communities',
]);

it('stores the category as a foreign key, not the PRD enum', function () {
    expect(Schema::hasColumn('communities', 'community_category_id'))->toBeTrue()
        ->and(Schema::hasColumn('communities', 'community_type_id'))->toBeFalse()
        ->and(Schema::hasColumn('communities', 'group'))->toBeFalse();
});

it('replaces the logo and thumbnail paths with media foreign keys', function () {
    expect(Schema::hasColumn('communities', 'logo_media_id'))->toBeTrue()
        ->and(Schema::hasColumn('communities', 'logo_alt'))->toBeTrue()
        ->and(Schema::hasColumn('communities', 'thumbnail_media_id'))->toBeTrue()
        ->and(Schema::hasColumn('communities', 'thumbnail_alt'))->toBeTrue()
        ->and(Schema::hasColumn('communities', 'logo_path'))->toBeFalse()
        ->and(Schema::hasColumn('communities', 'thumbnail_path'))->toBeFalse();
});

it('nulls the logo or thumbnail when its media is deleted', function () {
    $logo = Media::factory()->create();
    $thumbnail = Media::factory()->create();
    $community = Community::factory()->create([
        'logo_media_id' => $logo->getKey(),
        'thumbnail_media_id' => $thumbnail->getKey(),
    ]);

    $logo->delete();
    $thumbnail->delete();

    expect($community->fresh()->logo_media_id)->toBeNull()
        ->and($community->fresh()->thumbnail_media_id)->toBeNull();
});

it('restricts deleting a category a community still points at', function () {
    $category = CommunityCategory::factory()->create();
    Community::factory()->create(['community_category_id' => $category->getKey()]);

    expect(fn () => $category->forceDelete())->toThrow(QueryException::class);
});

/*
|--------------------------------------------------------------------------
| Scope
|--------------------------------------------------------------------------
*/

it('publishes only active and published communities', function () {
    $published = Community::factory()->published()->create();
    Community::factory()->forming()->create();
    Community::factory()->inactive()->create();
    Community::factory()->active()->create(); // active but not published

    expect(Community::published()->pluck('id')->all())->toBe([$published->getKey()]);
});

it('orders communities by name then id', function () {
    $second = Community::factory()->create(['name' => 'B Komunitas', 'slug' => 'b-komunitas']);
    $first = Community::factory()->create(['name' => 'A Komunitas', 'slug' => 'a-komunitas']);

    expect(Community::ordered()->pluck('id')->all())->toBe([$first->getKey(), $second->getKey()]);
});

it('keeps a soft-deleted community slug in the table', function () {
    $community = Community::factory()->create(['slug' => 'omk-putussibau']);
    $community->delete();

    expect(Community::query()->where('slug', 'omk-putussibau')->exists())->toBeFalse()
        ->and(Community::withTrashed()->where('slug', 'omk-putussibau')->exists())->toBeTrue();
});
