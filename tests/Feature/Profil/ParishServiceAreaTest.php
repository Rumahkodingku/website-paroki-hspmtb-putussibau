<?php

use App\Models\ParishServiceArea;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Wilayah pelayanan — singleton
|--------------------------------------------------------------------------
*/

it('renders the service-area form', function () {
    $this->actingAs(superAdmin())
        ->get(route('parish-service-area.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/profil/wilayah/index')
            ->where('area.id', null)
            ->has('labels'));
});

it('creates the singleton on first save and forwards its readiness to the page', function () {
    $this->actingAs(superAdmin())
        ->put(route('parish-service-area.update'), [
            'content' => '<p>Cakupan wilayah.</p>',
            'map_media_id' => null,
            'is_published' => true,
        ])
        ->assertRedirect(route('parish-service-area.edit'));

    expect(ParishServiceArea::count())->toBe(1);
});

it('sanitizes the narrative', function () {
    $this->actingAs(superAdmin())
        ->put(route('parish-service-area.update'), [
            'content' => '<p>Ok</p><script>alert(1)</script>',
            'map_media_id' => null,
            'is_published' => true,
        ]);

    expect(ParishServiceArea::current()->content)->not->toContain('<script>');
});
