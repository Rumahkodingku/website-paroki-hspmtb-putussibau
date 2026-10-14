<?php

use App\Models\Service;
use App\Models\ServiceCategory;

/*
|--------------------------------------------------------------------------
| Kategori Pelayanan (master data)
|--------------------------------------------------------------------------
|
| Roadmap 6B 9.2.1 and AC-SVC-13.
|
| @see docs/DECISIONS.md D-54
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function categoryPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Kategori Contoh',
        'slug' => 'kategori-contoh',
        'description' => 'Deskripsi.',
        'sort_order' => 0,
        'is_active' => true,
    ], $overrides);
}

it('creates a category', function () {
    $this->actingAs(superAdmin())
        ->post(route('service-categories.store'), categoryPayload(['name' => 'Sakramen Inisiasi', 'slug' => 'inisiasi']))
        ->assertRedirect(route('service-categories.index'));

    expect(ServiceCategory::query()->sole()->name)->toBe('Sakramen Inisiasi');
});

it('rejects a duplicate slug', function () {
    ServiceCategory::factory()->create(['slug' => 'inisiasi']);

    $this->actingAs(superAdmin())
        ->post(route('service-categories.store'), categoryPayload(['slug' => 'inisiasi']))
        ->assertSessionHasErrors('slug');
});

it('refuses to delete a category a service still uses', function () {
    $category = ServiceCategory::factory()->create();
    Service::factory()->create(['service_category_id' => $category->getKey()]);

    $this->actingAs(superAdmin())
        ->delete(route('service-categories.destroy', $category))
        ->assertSessionHasErrors('category');

    expect(ServiceCategory::count())->toBe(1);
});

it('refuses to delete a category a soft-deleted service still uses', function () {
    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create(['service_category_id' => $category->getKey()]);
    $service->delete();

    $this->actingAs(superAdmin())
        ->delete(route('service-categories.destroy', $category))
        ->assertSessionHasErrors('category');
});

it('deletes an unused category', function () {
    $category = ServiceCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->delete(route('service-categories.destroy', $category))
        ->assertRedirect(route('service-categories.index'));

    expect(ServiceCategory::count())->toBe(0);
});

it('refuses to deactivate a category with active services', function () {
    $category = ServiceCategory::factory()->create();
    Service::factory()->active()->create(['service_category_id' => $category->getKey()]);

    $this->actingAs(superAdmin())
        ->patch(route('service-categories.update', $category), categoryPayload([
            'slug' => $category->slug,
            'is_active' => false,
        ]))
        ->assertSessionHasErrors('is_active');

    expect($category->fresh()->is_active)->toBeTrue();
});

it('deactivates a category whose services are all inactive', function () {
    $category = ServiceCategory::factory()->create();
    Service::factory()->create(['service_category_id' => $category->getKey(), 'is_active' => false]);

    $this->actingAs(superAdmin())
        ->patch(route('service-categories.update', $category), categoryPayload([
            'slug' => $category->slug,
            'is_active' => false,
        ]))
        ->assertSessionHasNoErrors();

    expect($category->fresh()->is_active)->toBeFalse();
});

it('renders the list with a service count', function () {
    $category = ServiceCategory::factory()->create();
    Service::factory()->count(2)->create(['service_category_id' => $category->getKey()]);

    $this->actingAs(superAdmin())
        ->get(route('service-categories.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/master-data/kategori-pelayanan/index')
            // Paginated props keep the `data` wrapper — see D-39.
            ->where('categories.data.0.services_count', 2));
});
