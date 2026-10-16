<?php

use App\Models\Community;
use App\Models\CommunityCategory;

/*
|--------------------------------------------------------------------------
| Kategori Komunitas
|--------------------------------------------------------------------------
|
| AC-CAT-01..06 and roadmap 5.1.
|
| @see docs/DECISIONS.md D-55
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function communityCategoryPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Organisasi/Kategorial',
        'slug' => 'organisasi-kategorial',
        'description' => 'Komunitas kategorial paroki.',
        'sort_order' => 0,
        'is_active' => true,
    ], $overrides);
}

it('lists categories for a super admin', function () {
    CommunityCategory::factory()->count(2)->create();

    $this->actingAs(superAdmin())
        ->get(route('community-categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/master-data/kategori-komunitas/index')
            ->has('categories.data', 2));
});

it('creates a category', function () {
    $this->actingAs(superAdmin())
        ->post(route('community-categories.store'), communityCategoryPayload())
        ->assertRedirect(route('community-categories.index'));

    expect(CommunityCategory::query()->sole()->name)->toBe('Organisasi/Kategorial');
});

it('rejects invalid input', function () {
    $this->actingAs(superAdmin())
        ->post(route('community-categories.store'), communityCategoryPayload([
            'name' => '',
            'slug' => 'bukan slug!',
        ]))
        ->assertSessionHasErrors(['name', 'slug']);

    expect(CommunityCategory::count())->toBe(0);
});

it('rejects a duplicate slug', function () {
    CommunityCategory::factory()->create(['slug' => 'organisasi-kategorial']);

    $this->actingAs(superAdmin())
        ->post(route('community-categories.store'), communityCategoryPayload())
        ->assertSessionHasErrors('slug');
});

it('updates a category', function () {
    $category = CommunityCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->patch(route('community-categories.update', $category), communityCategoryPayload(['name' => 'Nama Baru']))
        ->assertRedirect(route('community-categories.index'));

    expect($category->fresh()->name)->toBe('Nama Baru');
});

it('soft deletes a category that is not used', function () {
    $category = CommunityCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->delete(route('community-categories.destroy', $category))
        ->assertRedirect(route('community-categories.index'));

    expect(CommunityCategory::count())->toBe(0)
        ->and(CommunityCategory::withTrashed()->count())->toBe(1);
});

it('refuses to delete a category that is still used', function () {
    $category = CommunityCategory::factory()->create();
    Community::factory()->create(['community_category_id' => $category->getKey()]);

    $this->actingAs(superAdmin())
        ->delete(route('community-categories.destroy', $category))
        ->assertRedirect(route('community-categories.index'))
        ->assertSessionHasErrors('category');

    expect(CommunityCategory::count())->toBe(1);
});

it('allows deactivating a category without deleting its communities', function () {
    $category = CommunityCategory::factory()->create();
    $community = Community::factory()->create(['community_category_id' => $category->getKey()]);

    $this->actingAs(superAdmin())
        ->patch(route('community-categories.update', $category), communityCategoryPayload([
            'is_active' => false,
        ]))
        ->assertSessionHasNoErrors();

    expect($category->fresh()->is_active)->toBeFalse()
        ->and($community->fresh()->exists)->toBeTrue();
});
