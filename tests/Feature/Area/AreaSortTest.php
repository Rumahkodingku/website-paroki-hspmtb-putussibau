<?php

use App\Models\Area;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Sorting the one area table
|--------------------------------------------------------------------------
|
| The admin list used to be three tables, one per type, grouped in React. It is
| one sortable table now, so the order moved from `->ordered()` in the query into
| `applyAdminSort` — which is what puts it under a whitelist and a tie-breaker.
|
| The default order is worth a test of its own. It is type, then the admin's own
| "Urutan", then name: three instructions in that order, because MySQL keeps
| ORDER BY instructions in the order they were added. Writing the default into
| the query chain and then sorting on top of it is how a header click silently
| does nothing (D-41).
|
| The unknown-key case is the one that fails quietly. `?sort=` arrives from the
| address bar and goes into ORDER BY, where it cannot be bound; a key outside the
| whitelist has to be ignored rather than interpolated, and a mangled link should
| still show a list instead of a 500.
|
| @see docs/DECISIONS.md D-41, D-57
| @see app/Concerns/SortsAdminList.php
*/

/**
 * A type as the factory takes it, written out because these tests are about
 * ordering and a state helper that also creates a parent would change the row
 * count behind the assertions.
 */
function areaOfType(string $type, string $name, int $sortOrder = 0): Area
{
    return Area::factory()->create([
        'type' => $type,
        'name' => $name,
        'slug' => Str::slug($name).'-'.uniqid(),
        'parent_id' => null,
        'sort_order' => $sortOrder,
    ]);
}

it('orders by type, then urutan, then name when no sort is asked for', function () {
    areaOfType('wilayah', 'Zulu', 0);
    areaOfType('stasi', 'Midang', 0);
    // Same type, so only "Urutan" can put Beta above Alfa despite the name.
    areaOfType('lingkungan', 'Alfa', 1);
    areaOfType('lingkungan', 'Beta', 0);

    $this->actingAs(superAdmin())
        ->get(route('areas.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            // A non-paginated list is a plain array, not {data: [...]} (D-39).
            ->missing('areas.data')
            ->where('areas.0.name', 'Beta')
            ->where('areas.1.name', 'Alfa')
            ->where('areas.2.name', 'Midang')
            ->where('areas.3.name', 'Zulu')
        );
});

it('sorts by name in either direction', function () {
    areaOfType('wilayah', 'Zulu');
    areaOfType('wilayah', 'Alfa');
    areaOfType('wilayah', 'Mano');

    $this->actingAs(superAdmin())
        ->get(route('areas.index', ['sort' => 'name', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('areas.0.name', 'Alfa')
            ->where('areas.1.name', 'Mano')
            ->where('areas.2.name', 'Zulu')
        );

    $this->actingAs(superAdmin())
        ->get(route('areas.index', ['sort' => 'name', 'direction' => 'desc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('areas.0.name', 'Zulu')
            ->where('areas.1.name', 'Mano')
            ->where('areas.2.name', 'Alfa')
        );
});

it('sorts by type', function () {
    areaOfType('wilayah', 'Zulu');
    areaOfType('stasi', 'Midang');
    areaOfType('lingkungan', 'Alfa');

    $this->actingAs(superAdmin())
        ->get(route('areas.index', ['sort' => 'type', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('areas.0.type', 'lingkungan')
            ->where('areas.1.type', 'stasi')
            ->where('areas.2.type', 'wilayah')
        );

    $this->actingAs(superAdmin())
        ->get(route('areas.index', ['sort' => 'type', 'direction' => 'desc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('areas.0.type', 'wilayah')
            ->where('areas.1.type', 'stasi')
            ->where('areas.2.type', 'lingkungan')
        );
});

it('sorts by urutan and by status', function () {
    areaOfType('wilayah', 'Zulu', 5);
    areaOfType('wilayah', 'Alfa', 1);
    Area::factory()->create([
        'type' => 'wilayah',
        'name' => 'Mano',
        'slug' => 'mano-nonaktif',
        'parent_id' => null,
        'sort_order' => 0,
        'is_active' => false,
    ]);

    $this->actingAs(superAdmin())
        ->get(route('areas.index', ['sort' => 'sort_order', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('areas.0.name', 'Mano')
            ->where('areas.1.name', 'Alfa')
            ->where('areas.2.name', 'Zulu')
        );

    $this->actingAs(superAdmin())
        ->get(route('areas.index', ['sort' => 'is_active', 'direction' => 'asc']))
        ->assertInertia(fn (Assert $page) => $page
            // Mano is the only inactive one, so it leads. The two actives then
            // tie on is_active and the id tie-breaker decides between them:
            // Zulu was created first, so it comes second overall.
            ->where('areas.0.name', 'Mano')
            ->where('areas.0.is_active', false)
            ->where('areas.1.name', 'Zulu')
            ->where('areas.2.name', 'Alfa')
        );
});

it('ignores an unknown sort key and keeps the default order', function () {
    areaOfType('wilayah', 'Zulu');
    areaOfType('stasi', 'Midang');
    areaOfType('lingkungan', 'Alfa');

    $this->actingAs(superAdmin())
        ->get(route('areas.index', ['sort' => 'parent_id', 'direction' => 'asc']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('areas.0.type', 'lingkungan')
            ->where('areas.1.type', 'stasi')
            ->where('areas.2.type', 'wilayah')
        );
});

it('mirrors the sort and direction back into the filters prop', function () {
    $this->actingAs(superAdmin())
        ->get(route('areas.index', ['sort' => 'name', 'direction' => 'desc']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sort', 'name')
            ->where('filters.direction', 'desc')
        );

    // No sort at all: both stay empty strings rather than becoming null, because
    // the DataTable reads them as '' and compares direction against 'desc'.
    $this->actingAs(superAdmin())
        ->get(route('areas.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sort', '')
            ->where('filters.direction', '')
        );
});
