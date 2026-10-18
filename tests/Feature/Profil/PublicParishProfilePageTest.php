<?php

use App\Models\ParishBoardMember;
use App\Models\ParishBoardPeriod;
use App\Models\ParishHistoryItem;
use App\Models\ParishHistoryNarrative;
use App\Models\ParishServiceArea;
use App\Models\Pastor;
use App\Models\PastorAssignment;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Halaman publik Profil Paroki
|--------------------------------------------------------------------------
|
| The Informasi Profil summary page (/profil) was removed in D-69. What
| remains are the four section pages. Every published row reaches a visitor
| only through a published() scope.
*/

it('renders the history page with its narrative and published items', function () {
    ParishHistoryNarrative::factory()->create(['narrative' => '<p>Narasi sejarah.</p>']);
    ParishHistoryItem::factory()->create(['title' => 'Terbit']);

    $this->get('/profil/sejarah')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/profil/sejarah')
            ->where('narrative', '<p>Narasi sejarah.</p>')
            ->has('items', 1)
            ->where('items.0.title', 'Terbit'));
});

it('sends a null narrative when none has been written', function () {
    $this->get('/profil/sejarah')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('narrative', null));
});

it('does not serve the removed summary page', function () {
    $this->get('/profil')->assertNotFound();
});

it('shows only published history items', function () {
    ParishHistoryItem::factory()->create(['title' => 'Terbit']);
    ParishHistoryItem::factory()->unpublished()->create(['title' => 'Draf']);

    $this->get('/profil/sejarah')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/profil/sejarah')
            ->has('items', 1)
            ->where('items.0.title', 'Terbit'));
});

it('shows the service area only when published', function () {
    ParishServiceArea::factory()->unpublished()->create();

    $this->get('/profil/wilayah')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('area', null));

    ParishServiceArea::query()->update(['is_published' => true]);

    $this->get('/profil/wilayah')
        ->assertInertia(fn (Assert $page) => $page->has('area'));
});

it('separates the current pastor from the history', function () {
    $current = Pastor::factory()->create(['name' => 'Pastor Aktif']);
    PastorAssignment::factory()->for($current)->current()->create();

    $past = Pastor::factory()->create(['name' => 'Pastor Lama']);
    PastorAssignment::factory()->for($past)->create(['end_year' => 2019]);

    $this->get('/profil/pastor')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/profil/pastor')
            ->has('current', 1)
            ->where('current.0.name', 'Pastor Aktif')
            ->has('history', 1)
            ->where('history.0.name', 'Pastor Lama'));
});

it('shows only published periods and members', function () {
    $period = ParishBoardPeriod::factory()->create(['name' => 'Aktif']);
    ParishBoardPeriod::factory()->unpublished()->create(['name' => 'Draf']);
    ParishBoardMember::factory()->create(['parish_board_period_id' => $period->getKey(), 'name' => 'Terbit']);
    ParishBoardMember::factory()->unpublished()->create(['parish_board_period_id' => $period->getKey(), 'name' => 'Draf Anggota']);

    $this->get('/profil/struktur')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/profil/struktur')
            ->has('periods', 1)
            ->where('periods.0.name', 'Aktif')
            ->has('periods.0.members', 1)
            ->where('periods.0.members.0.name', 'Terbit'));
});
