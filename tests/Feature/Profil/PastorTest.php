<?php

use App\Models\Media;
use App\Models\MediaVariant;
use App\Models\Pastor;
use App\Models\PastorAssignment;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Pastor — identitas
|--------------------------------------------------------------------------
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validPastor(array $overrides = []): array
{
    return array_merge([
        'name' => 'Nama Pastor',
        'title' => 'Pst.',
        'photo_media_id' => null,
        'photo_alt' => '',
        'bio' => '<p>Biografi.</p>',
        'sort_order' => 0,
        'is_published' => true,
    ], $overrides);
}

it('renders the pastor list', function () {
    Pastor::factory()->count(2)->create();

    $this->actingAs(superAdmin())
        ->get(route('pastors.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/profil/pastor/index')
            ->has('pastors.data', 2)
            ->has('positions'));
});

it('includes the photo URL in the list so the photo column can render', function () {
    $media = Media::factory()->ready()->create();
    MediaVariant::factory()->create([
        'media_id' => $media->getKey(),
        'name' => 'medium',
        'disk' => (string) config('media.variant_disk'),
        'path' => 'media/variants/'.$media->getKey().'/medium.webp',
    ]);

    Pastor::factory()->create(['photo_media_id' => $media->getKey()]);

    $this->actingAs(superAdmin())
        ->get(route('pastors.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('pastors.data.0.photo_url', fn ($url) => is_string($url) && str_contains($url, 'storage')));
});

it('creates a pastor and opens its edit page', function () {
    $this->actingAs(superAdmin())
        ->post(route('pastors.store'), validPastor())
        ->assertRedirect();

    expect(Pastor::count())->toBe(1)
        ->and(Pastor::first()->name)->toBe('Nama Pastor');
});

it('requires a name', function () {
    $this->actingAs(superAdmin())
        ->post(route('pastors.store'), validPastor(['name' => '']))
        ->assertSessionHasErrors('name');

    expect(Pastor::count())->toBe(0);
});

it('updates a pastor', function () {
    $pastor = Pastor::factory()->create(['name' => 'Lama']);

    $this->actingAs(superAdmin())
        ->patch(route('pastors.update', $pastor), validPastor(['name' => 'Baru']))
        ->assertRedirect(route('pastors.edit', $pastor));

    expect($pastor->fresh()->name)->toBe('Baru');
});

it('deletes a pastor and cascades the assignments', function () {
    $pastor = Pastor::factory()->current()->create();

    $this->actingAs(superAdmin())
        ->delete(route('pastors.destroy', $pastor))
        ->assertRedirect(route('pastors.index'));

    expect(Pastor::count())->toBe(0)
        ->and(PastorAssignment::count())->toBe(0);
});
