<?php

use App\Models\Media;
use App\Models\MediaVariant;
use App\Models\ParishBoardMember;
use App\Models\ParishBoardPeriod;
use App\Models\ParishHistoryItem;
use App\Models\ParishProfile;
use App\Models\ParishServiceArea;
use App\Models\Pastor;
use App\Models\PastorAssignment;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Halaman publik Profil Paroki
|--------------------------------------------------------------------------
|
| Setiap halaman memakai scope published(); baris draf tidak boleh sampai ke
| pengunjung meski URL-nya benar.
*/

it('renders the profile page and hides an unpublished profile', function () {
    ParishProfile::factory()->unpublished()->create();

    $this->get('/profil')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/profil')
            ->where('profile', null)
            ->where('sections.sejarah', false));
});

it('shows a published profile with its sections', function () {
    ParishProfile::factory()->create(['name' => 'Paroki HSPMTB']);
    ParishHistoryItem::factory()->create();
    ParishServiceArea::factory()->create();
    Pastor::factory()->current()->create();
    ParishBoardPeriod::factory()->create();

    $this->get('/profil')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.name', 'Paroki HSPMTB')
            ->where('sections.sejarah', true)
            ->where('sections.pastor', true));
});

it('resolves an attached cover image to its variant URL', function () {
    // Same lazy-load guard as the admin side, on the public resource: the
    // controller eager loads coverMedia.variants, and Media::url() reads it.
    $media = Media::factory()->ready()->create();
    MediaVariant::factory()->create([
        'media_id' => $media->getKey(),
        'name' => 'large',
        'disk' => (string) config('media.variant_disk'),
        'path' => 'media/variants/'.$media->getKey().'/large.webp',
    ]);

    ParishProfile::factory()->create(['cover_media_id' => $media->getKey()]);

    $this->get('/profil')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.cover', fn ($url) => is_string($url) && str_contains($url, 'storage')));
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
