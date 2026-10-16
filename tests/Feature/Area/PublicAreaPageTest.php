<?php

use App\Models\Area;

/*
|--------------------------------------------------------------------------
| Halaman publik Area
|--------------------------------------------------------------------------
|
| XC-E3 (only active areas reach the public), XC-P1 (contact only with
| consent), and the directory/detail contract of D-57.
|
| @see docs/DECISIONS.md D-57
*/

it('lists only active areas, grouped by type', function () {
    Area::factory()->stasi()->create(['name' => 'Stasi Jaras']);
    Area::factory()->create(['name' => 'Lingkungan Melati', 'type' => 'lingkungan', 'parent_id' => null]);
    Area::factory()->inactive()->create(['name' => 'Area Nonaktif']);

    $this->get(route('area.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/area')
            ->where('hasAreas', true)
            ->has('sections', 2)
            ->where('sections.0.type', 'stasi')
            ->where('sections.1.type', 'lingkungan'));
});

it('renders an empty directory when nothing is active', function () {
    Area::factory()->inactive()->create();

    $this->get(route('area.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/area')
            ->where('hasAreas', false)
            ->has('sections', 0));
});

it('shows an active area detail', function () {
    $area = Area::factory()->create(['name' => 'Stasi Jaras', 'slug' => 'stasi-jaras']);

    $this->get(route('area.show', $area->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/area/show')
            ->where('area.name', 'Stasi Jaras')
            ->has('area.children'));
});

it('answers 404 for an inactive area', function () {
    $area = Area::factory()->inactive()->create(['slug' => 'stasi-nonaktif']);

    $this->get(route('area.show', $area->slug))->assertNotFound();
});

it('answers 404 for an unknown slug', function () {
    $this->get('/area/tidak-ada')->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Privasi kontak (XC-P1)
|--------------------------------------------------------------------------
*/

it('omits the leader and phone when show_contact is false', function () {
    $area = Area::factory()->create([
        'slug' => 'stasi-privat',
        'leader_name' => 'Ketua Rahasia',
        'contact_phone' => '628222222222',
        'show_contact' => false,
    ]);

    $this->get(route('area.show', $area->slug))
        ->assertInertia(fn ($page) => $page
            ->missing('area.leader_name')
            ->missing('area.contact_phone'));
});

it('sends the leader and phone when show_contact is true', function () {
    $area = Area::factory()->withContact('628222222222')->create([
        'slug' => 'stasi-terbuka',
    ]);

    $this->get(route('area.show', $area->slug))
        ->assertInertia(fn ($page) => $page
            ->where('area.contact_phone', '628222222222')
            ->has('area.leader_name'));
});

it('lists a wilayah children with their own contact privacy', function () {
    $wilayah = Area::factory()->wilayah()->create(['slug' => 'wilayah-utara']);
    /*
     * The names and the urutan numbers are pinned because the children come back
     * ordered by (sort_order, name) and the factory's name is a random faker
     * word. Left to itself, this assertion passed or failed on the faker
     * sequence: two children at sort_order 0 are ordered by whatever words were
     * drawn that run.
     */
    Area::factory()->withContact('628333333333')->create([
        'slug' => 'lingkungan-terbuka',
        'name' => 'Lingkungan Terbuka',
        'parent_id' => $wilayah->getKey(),
        'sort_order' => 0,
    ]);
    Area::factory()->create([
        'slug' => 'lingkungan-tertutup',
        'name' => 'Lingkungan Tertutup',
        'parent_id' => $wilayah->getKey(),
        'sort_order' => 1,
        'show_contact' => false,
        'leader_name' => 'Ketua Tertutup',
        'contact_phone' => '628444444444',
    ]);

    $this->get(route('area.show', $wilayah->slug))
        ->assertInertia(fn ($page) => $page
            ->has('area.children', 2)
            ->where('area.children.0.contact_phone', '628333333333')
            ->where('area.children.1.contact_phone', null)
            ->where('area.children.1.leader_name', null));
});

/*
| Address and map link (D-61).
|
| The page needs the finished link and the address, never the coordinate pair:
| Area::mapsUrl() decides the fallback on the server so React does not have to
| know whether a pair exists.
*/

it('sends the address and a map link built from the coordinates', function () {
    $area = Area::factory()->withLocation()->create([
        'slug' => 'stasi-beralamat',
    ]);

    $this->get(route('area.show', $area->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('area.address', 'Gereja Stasi Contoh, Jalan Contoh No. 1, Putussibau')
            ->where('area.mapsUrl', 'https://www.google.com/maps/search/?api=1&query=-0.4312900,111.4935200')
            // The raw pair is not something the page needs.
            ->missing('area.latitude')
            ->missing('area.longitude'));
});

it('prefers a stored map url over the coordinates', function () {
    $area = Area::factory()->withLocation()->create([
        'slug' => 'stasi-tautan',
        'maps_url' => 'https://maps.app.goo.gl/contoh',
    ]);

    $this->get(route('area.show', $area->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('area.mapsUrl', 'https://maps.app.goo.gl/contoh'));
});

it('sends a null address and no map link for an area that has neither', function () {
    $area = Area::factory()->create(['slug' => 'stasi-tanpa-peta']);

    $this->get(route('area.show', $area->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('area.address', null)
            ->where('area.mapsUrl', null));
});
