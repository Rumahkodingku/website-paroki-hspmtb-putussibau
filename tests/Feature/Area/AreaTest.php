<?php

use App\Enums\AreaType;
use App\Models\Area;

/*
|--------------------------------------------------------------------------
| Area — hierarki
|--------------------------------------------------------------------------
|
| AC-AREA-01..06, PRD COM-02, and D-55/D-57. The area module stands alone since
| D-57: a lingkungan may be top-level (this parish has no wilayah), or sit under
| a wilayah. Stasi stays top-level.
|
| @see docs/DECISIONS.md D-55, D-57
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function areaPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Wilayah Utara',
        'slug' => 'wilayah-utara',
        'type' => 'wilayah',
        'parent_id' => null,
        'description' => null,
        'leader_name' => null,
        'contact_phone' => null,
        'show_contact' => false,
        'sort_order' => 0,
        'is_active' => true,
    ], $overrides);
}

it('lists areas for a super admin', function () {
    Area::factory()->count(2)->create();

    $this->actingAs(superAdmin())
        ->get(route('areas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/area')
            // A non-paginated list is a plain array, not {data: [...]} (D-39).
            ->has('areas', 2)
            ->missing('areas.data')
            // The table is sortable, so the header state has to arrive even
            // when nothing was sorted: DataTable reads '' as "no sort".
            ->where('filters.sort', '')
            ->where('filters.direction', ''));
});

it('creates a wilayah without a parent', function () {
    $this->actingAs(superAdmin())
        ->post(route('areas.store'), areaPayload())
        ->assertSessionHasNoErrors();

    expect(Area::query()->sole()->type->value)->toBe('wilayah')
        ->and(Area::query()->sole()->parent_id)->toBeNull();
});

it('rejects a wilayah with a parent', function () {
    $parent = Area::factory()->wilayah()->create();

    $this->actingAs(superAdmin())
        ->post(route('areas.store'), areaPayload(['parent_id' => $parent->getKey()]))
        ->assertSessionHasErrors('parent_id');
});

it('creates a lingkungan with no parent', function () {
    // D-57: this parish has no wilayah, so its centre lingkungan are top-level.
    $this->actingAs(superAdmin())
        ->post(route('areas.store'), areaPayload([
            'name' => 'Lingkungan Melati',
            'slug' => 'lingkungan-melati',
            'type' => 'lingkungan',
            'parent_id' => null,
        ]))
        ->assertSessionHasNoErrors();

    $area = Area::query()->where('slug', 'lingkungan-melati')->sole();

    expect($area->type->value)->toBe('lingkungan')
        ->and($area->parent_id)->toBeNull();
});

it('creates a lingkungan under a wilayah', function () {
    $wilayah = Area::factory()->wilayah()->create();

    $this->actingAs(superAdmin())
        ->post(route('areas.store'), areaPayload([
            'name' => 'Lingkungan Melati',
            'slug' => 'lingkungan-melati',
            'type' => 'lingkungan',
            'parent_id' => $wilayah->getKey(),
        ]))
        ->assertSessionHasNoErrors();

    expect(Area::query()->where('slug', 'lingkungan-melati')->sole()->parent_id)
        ->toBe($wilayah->getKey());
});

it('rejects a lingkungan whose parent is a lingkungan', function () {
    $lingkungan = Area::factory()->lingkungan()->create();

    $this->actingAs(superAdmin())
        ->post(route('areas.store'), areaPayload([
            'type' => 'lingkungan',
            'parent_id' => $lingkungan->getKey(),
        ]))
        ->assertSessionHasErrors('parent_id');
});

it('rejects a lingkungan whose parent is a stasi', function () {
    // stasi is top-level, so it is not a legal parent for a lingkungan even
    // though it is also a top-level area.
    $stasi = Area::factory()->stasi()->create();

    $this->actingAs(superAdmin())
        ->post(route('areas.store'), areaPayload([
            'type' => 'lingkungan',
            'parent_id' => $stasi->getKey(),
        ]))
        ->assertSessionHasErrors('parent_id');
});

it('rejects a stasi with a parent', function () {
    $wilayah = Area::factory()->wilayah()->create();

    $this->actingAs(superAdmin())
        ->post(route('areas.store'), areaPayload([
            'type' => 'stasi',
            'parent_id' => $wilayah->getKey(),
        ]))
        ->assertSessionHasErrors('parent_id');
});

it('moves a lingkungan to another wilayah', function () {
    $from = Area::factory()->wilayah()->create();
    $to = Area::factory()->wilayah()->create();
    $lingkungan = Area::factory()->lingkungan()->create(['parent_id' => $from->getKey()]);

    $this->actingAs(superAdmin())
        ->patch(route('areas.update', $lingkungan), areaPayload([
            'name' => $lingkungan->name,
            'slug' => $lingkungan->slug,
            'type' => 'lingkungan',
            'parent_id' => $to->getKey(),
        ]))
        ->assertSessionHasNoErrors();

    expect($lingkungan->fresh()->parent_id)->toBe($to->getKey());
});

it('refuses to delete a wilayah that still has children', function () {
    $wilayah = Area::factory()->wilayah()->create();
    Area::factory()->lingkungan()->create(['parent_id' => $wilayah->getKey()]);

    $this->actingAs(superAdmin())
        ->delete(route('areas.destroy', $wilayah))
        ->assertSessionHasErrors('area');

    expect(Area::count())->toBe(2);
});

it('soft deletes an unused area', function () {
    $area = Area::factory()->create();

    $this->actingAs(superAdmin())
        ->delete(route('areas.destroy', $area))
        ->assertRedirect(route('areas.index'));

    expect(Area::count())->toBe(0)
        ->and(Area::withTrashed()->count())->toBe(1);
});

it('rejects a phone that is not a phone number', function () {
    $this->actingAs(superAdmin())
        ->post(route('areas.store'), areaPayload([
            'contact_phone' => 'bukan-nomor',
        ]))
        ->assertSessionHasErrors('contact_phone');
});

it('labels every type in Indonesian rather than shipping the raw key', function () {
    /*
     * D-57 moved the area copy from `komunitas.area_types.*` to
     * `area.area_types.*`. A translation key that no longer
     * exists does not throw: __() returns the key, so `type_label` would reach
     * React as the literal string "lingkungan" and the public page would label
     * an area with its own slug. Asserted against the key as well as the word,
     * because "Wilayah" only differs from "wilayah" by a capital letter.
     */
    foreach (AreaType::cases() as $type) {
        expect($type->label())
            ->toBe(__('area.area_types.'.$type->value))
            ->not->toBe($type->value)
            ->not->toContain('area_types');
    }

    expect(AreaType::Wilayah->label())->toBe('Wilayah')
        ->and(AreaType::Lingkungan->label())->toBe('Lingkungan')
        ->and(AreaType::Stasi->label())->toBe('Stasi');
});

/*
| Where the area is (D-61).
|
| Four columns copied from the locations module, so the tests are the question a
| second module asks too: is a coordinate range-checked, does a half-filled pair
| get rejected, and does an untouched box become null rather than 0,0?
*/

it('stores an address and a coordinate pair', function () {
    $this->actingAs(superAdmin())
        ->post(route('areas.store'), areaPayload([
            'address' => 'Gereja Stasi Contoh, Jalan Contoh No. 1',
            'latitude' => '-0.4312900',
            'longitude' => '111.4935200',
        ]))
        ->assertSessionHasNoErrors();

    $area = Area::query()->sole();

    expect($area->address)->toBe('Gereja Stasi Contoh, Jalan Contoh No. 1')
        // decimal, not float: the seventh place has to survive the round trip
        ->and($area->latitude)->toBe('-0.4312900')
        ->and($area->longitude)->toBe('111.4935200');
});

it('rejects a coordinate pair that is only half filled', function (array $pair) {
    $this->actingAs(superAdmin())
        ->post(route('areas.store'), areaPayload($pair))
        ->assertSessionHasErrors('longitude');

    expect(Area::query()->count())->toBe(0);
})->with([
    'latitude only' => [['latitude' => '-0.4312900']],
    'longitude only' => [['longitude' => '111.4935200']],
]);

it('rejects coordinates outside the range of the world', function (string $field, string $value) {
    // A valid pair first, then the value under test: a single array literal with
    // a duplicate key keeps the last one, which would have quietly replaced the
    // bad coordinate with a good one and made this test pass for the wrong reason.
    $pair = ['latitude' => '-0.4312900', 'longitude' => '111.4935200'];
    $pair[$field] = $value;

    $this->actingAs(superAdmin())
        ->post(route('areas.store'), areaPayload($pair))
        ->assertSessionHasErrors($field);
})->with([
    ['latitude', '91'],
    ['latitude', '-90.5'],
    ['longitude', '181'],
]);

it('rejects a maps url that is not http', function () {
    $this->actingAs(superAdmin())
        ->post(route('areas.store'), areaPayload([
            'maps_url' => 'ftp://contoh.test/peta',
        ]))
        ->assertSessionHasErrors('maps_url');
});

it('accepts an area with no location at all, and stores nothing as zero', function () {
    $this->actingAs(superAdmin())
        ->post(route('areas.store'), areaPayload([
            'address' => '',
            'latitude' => '',
            'longitude' => '',
            'maps_url' => '',
        ]))
        ->assertSessionHasNoErrors();

    $area = Area::query()->sole();

    // 0,0 is a real point in the Gulf of Guinea and must not be what an
    // untouched box turns into.
    expect($area->address)->toBeNull()
        ->and($area->latitude)->toBeNull()
        ->and($area->longitude)->toBeNull()
        ->and($area->maps_url)->toBeNull()
        ->and($area->mapsUrl())->toBeNull();
});

it('builds a map link from the coordinates when no url was stored', function () {
    $this->actingAs(superAdmin())
        ->post(route('areas.store'), areaPayload([
            'latitude' => '-0.4312900',
            'longitude' => '111.4935200',
        ]))
        ->assertSessionHasNoErrors();

    expect(Area::query()->sole()->mapsUrl())->toBe(
        'https://www.google.com/maps/search/?api=1&query=-0.4312900,111.4935200'
    );
});

it('prefers a stored maps url over the coordinates', function () {
    $this->actingAs(superAdmin())
        ->post(route('areas.store'), areaPayload([
            'maps_url' => 'https://www.google.com/maps/search/?api=1&query=-0.4312900,111.4935200',
            'latitude' => '-0.4312900',
            'longitude' => '111.4935200',
        ]))
        ->assertSessionHasNoErrors();

    expect(Area::query()->sole()->mapsUrl())->toBe('https://www.google.com/maps/search/?api=1&query=-0.4312900,111.4935200');
});
