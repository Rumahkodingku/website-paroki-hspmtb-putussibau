<?php

use App\Models\Community;
use App\Models\CommunityCategory;

/*
|--------------------------------------------------------------------------
| Direktori publik Komunitas
|--------------------------------------------------------------------------
|
| AC-PUB-01..08; roadmap 7.1/7.2.
|
| @see docs/DECISIONS.md D-55
*/

it('shows only active and published communities', function () {
    Community::factory()->published()->create();
    Community::factory()->forming()->create();
    Community::factory()->inactive()->create();
    Community::factory()->active()->create(); // active but a draft

    $this->get(route('komunitas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/komunitas')
            ->has('communities.data', 1));
});

it('searches by name', function () {
    Community::factory()->published()->create(['name' => 'Alpha Komunitas', 'slug' => 'alpha']);
    Community::factory()->published()->create(['name' => 'Beta Komunitas', 'slug' => 'beta']);

    $this->get(route('komunitas.index', ['q' => 'Alpha']))
        ->assertInertia(fn ($page) => $page
            ->has('communities.data', 1)
            ->where('communities.data.0.name', 'Alpha Komunitas')
            ->where('filters.q', 'Alpha'));
});

it('filters by category', function () {
    $category = CommunityCategory::factory()->create(['slug' => 'kategorial']);
    Community::factory()->published()->create(['community_category_id' => $category->getKey()]);
    Community::factory()->published()->create();

    $this->get(route('komunitas.index', ['kategori' => 'kategorial']))
        ->assertInertia(fn ($page) => $page->has('communities.data', 1));
});

it('ignores a category slug nobody has', function () {
    Community::factory()->published()->create();

    $this->get(route('komunitas.index', ['kategori' => 'tidak-ada']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('communities.data', 1));
});

it('paginates and keeps the query string', function () {
    Community::factory()->published()->count(13)->create(['name' => 'Komunitas Contoh']);

    $this->get(route('komunitas.index', ['q' => 'Komunitas', 'page' => 2]))
        ->assertInertia(fn ($page) => $page
            ->where('communities.meta.current_page', 2)
            ->where('filters.q', 'Komunitas')
            ->has('communities.data', 1));
});

it('offers only categories that hold a published community', function () {
    $used = CommunityCategory::factory()->create(['name' => 'Dipakai']);
    CommunityCategory::factory()->create(['name' => 'Kosong']);
    Community::factory()->published()->create(['community_category_id' => $used->getKey()]);

    $this->get(route('komunitas.index'))
        ->assertInertia(fn ($page) => $page
            ->has('categories', 1)
            ->where('categories.0.name', 'Dipakai'));
});

it('renders an empty directory when nothing is published', function () {
    Community::factory()->forming()->create();

    $this->get(route('komunitas.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('communities.data', 0));
});
