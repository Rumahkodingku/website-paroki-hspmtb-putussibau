<?php

use App\Models\Area;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Area — schema dan domain
|--------------------------------------------------------------------------
|
| The area module's own schema assertions, moved here when the area module was
| split out of Komunitas (D-57).
|
| @see docs/DECISIONS.md D-55, D-57, D-58
*/

it('creates the areas table', function () {
    expect(Schema::hasTable('areas'))->toBeTrue();
});

it('soft deletes areas', function () {
    expect(Schema::hasColumn('areas', 'deleted_at'))->toBeTrue();
});

it('has no generated active_marker', function () {
    // The posts/events rule, not the locations rule: the public route binds
    // {area:slug}, so a soft-deleted row keeps its slug.
    expect(Schema::hasColumn('areas', 'active_marker'))->toBeFalse();
});

it('keeps the PRD area fields alongside the hierarchy', function () {
    expect(Schema::hasColumn('areas', 'type'))->toBeTrue()
        ->and(Schema::hasColumn('areas', 'parent_id'))->toBeTrue()
        ->and(Schema::hasColumn('areas', 'leader_name'))->toBeTrue()
        ->and(Schema::hasColumn('areas', 'contact_phone'))->toBeTrue()
        ->and(Schema::hasColumn('areas', 'show_contact'))->toBeTrue();
});

it('no longer carries a prayer_schedule column', function () {
    // D-58 dropped it: the parish does not keep a prayer schedule per area, and
    // PRD §9.9 / Lampiran C asked for one. The assertion is inverted on purpose
    // so the column cannot quietly return with the next migration.
    expect(Schema::hasColumn('areas', 'prayer_schedule'))->toBeFalse();
});

it('restricts deleting a wilayah that still has children', function () {
    $wilayah = Area::factory()->wilayah()->create();
    Area::factory()->lingkungan()->create(['parent_id' => $wilayah->getKey()]);

    expect(fn () => $wilayah->forceDelete())->toThrow(QueryException::class);
});

it('orders areas by sort_order then name', function () {
    $second = Area::factory()->create(['name' => 'Zona B', 'slug' => 'zona-b', 'sort_order' => 2]);
    $first = Area::factory()->create(['name' => 'Zona A', 'slug' => 'zona-a', 'sort_order' => 1]);

    expect(Area::ordered()->pluck('id')->all())->toBe([$first->getKey(), $second->getKey()]);
});

it('keeps a soft-deleted area slug in the table', function () {
    $area = Area::factory()->create(['slug' => 'stasi-jaras']);
    $area->delete();

    expect(Area::query()->where('slug', 'stasi-jaras')->exists())->toBeFalse()
        ->and(Area::withTrashed()->where('slug', 'stasi-jaras')->exists())->toBeTrue();
});

it('publishes only active areas', function () {
    $active = Area::factory()->create();
    Area::factory()->inactive()->create();

    expect(Area::active()->pluck('id')->all())->toBe([$active->getKey()]);
});
