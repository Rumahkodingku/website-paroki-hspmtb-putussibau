<?php

use App\Models\Community;
use App\Services\SiteSettingsService;

/*
|--------------------------------------------------------------------------
| Detail publik Komunitas dan privasi kontak
|--------------------------------------------------------------------------
|
| AC-DETAIL-01..10, XC-E3, XC-P1, and D-55 point 4.
|
| @see docs/DECISIONS.md D-55
*/

it('shows a published community detail', function () {
    $community = Community::factory()->published()->create(['name' => 'OMK Putussibau']);

    $this->get(route('komunitas.show', $community->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/komunitas/show')
            ->where('community.name', 'OMK Putussibau'));
});

it('answers 404 for a community nobody published', function (string $state) {
    $community = match ($state) {
        'forming' => Community::factory()->forming()->create(),
        'inactive' => Community::factory()->inactive()->create(),
        'draft' => Community::factory()->active()->create(['is_published' => false]),
        default => throw new RuntimeException("Unknown state: {$state}"),
    };

    $this->get(route('komunitas.show', $community->slug))->assertNotFound();
})->with(['forming', 'inactive', 'draft']);

it('answers 404 for a soft-deleted community', function () {
    $community = Community::factory()->published()->create();
    $community->delete();

    $this->get(route('komunitas.show', $community->slug))->assertNotFound();
});

it('answers 404 for an unknown slug', function () {
    $this->get('/komunitas/tidak-ada')->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Privasi kontak komunitas (XC-P1, AC-DETAIL-06/07)
|--------------------------------------------------------------------------
*/

it('omits the community contact when show_contact is false', function () {
    $community = Community::factory()->published()->create([
        'contact_name' => 'Nama Rahasia',
        'contact_phone' => '628111111111',
        'show_contact' => false,
    ]);

    $this->get(route('komunitas.show', $community->slug))
        ->assertInertia(fn ($page) => $page->missing('community.contact'));
});

it('sends the community contact when show_contact is true', function () {
    $community = Community::factory()->published()->withContact('628111111111')->create([
        'contact_name' => 'Nama Kontak',
    ]);

    $this->get(route('komunitas.show', $community->slug))
        ->assertInertia(fn ($page) => $page
            ->where('community.contact.name', 'Nama Kontak')
            ->where('community.contact.phone', '628111111111'));
});

/*
|--------------------------------------------------------------------------
| CTA sekretariat (D-55 point 8)
|--------------------------------------------------------------------------
*/

it('sends no secretariat contact when the parish has not set a number', function () {
    $community = Community::factory()->published()->create();

    $this->get(route('komunitas.show', $community->slug))
        ->assertInertia(fn ($page) => $page->where('community.secretariat', null));
});

it('sends the secretariat contact from site settings', function () {
    app(SiteSettingsService::class)->set('contact_phone', '628123456789');
    $community = Community::factory()->published()->create();

    $this->get(route('komunitas.show', $community->slug))
        ->assertInertia(fn ($page) => $page
            ->where('community.secretariat.phone', '628123456789')
            ->has('community.secretariat.tel'));
});

/*
|--------------------------------------------------------------------------
| Tidak ada galeri atau relasi berita/agenda (AC-DETAIL-09/10)
|--------------------------------------------------------------------------
*/

it('sends no gallery or related content props', function () {
    $community = Community::factory()->published()->create();

    $this->get(route('komunitas.show', $community->slug))
        ->assertInertia(fn ($page) => $page
            ->missing('gallery')
            ->missing('relatedPosts')
            ->missing('relatedEvents')
            ->missing('community.photos'));
});

/*
| Social links (D-59).
|
| The contract is about ABSENCE: five null columns must produce no `socials` key
| at all, not an empty array and not five nulls. A page cannot tell "this
| community has no links" from "these links were withheld" if the empty case
| arrives as data, and an empty section heading is worse than no section.
*/

it('sends the social links a community filled in', function () {
    $community = Community::factory()->published()->withSocialLinks()->create();

    $this->get(route('komunitas.show', $community->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('community.socials', 5)
            ->where('community.socials.0.label', 'Facebook')
            ->where('community.socials.0.url', 'https://facebook.com/contoh-komunitas')
            ->where('community.socials.4.label', 'Situs web')
            ->where('community.socials.4.url', 'https://contoh-komunitas.test'));
});

it('omits the socials key entirely when no column is filled', function () {
    $community = Community::factory()->published()->create();

    $this->get(route('komunitas.show', $community->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->missing('community.socials'));
});

it('sends only the links that are filled, in form order', function () {
    $community = Community::factory()->published()->create([
        'tiktok' => 'https://tiktok.com/@contoh',
        'website' => 'https://contoh.test',
    ]);

    $this->get(route('komunitas.show', $community->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('community.socials', 2)
            ->where('community.socials.0.label', 'TikTok')
            ->where('community.socials.1.label', 'Situs web'));
});
