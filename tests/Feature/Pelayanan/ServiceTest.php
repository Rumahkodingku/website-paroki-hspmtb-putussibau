<?php

use App\Models\Service;
use App\Models\ServiceCategory;

/*
|--------------------------------------------------------------------------
| Service CRUD, publikasi, dan aturan Google Form opsional
|--------------------------------------------------------------------------
|
| AC-SVC-3..5, 9, 13 and roadmap 6B-042/6B-052..6B-061.
|
| @see docs/DECISIONS.md D-54
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function servicePayload(ServiceCategory $category, array $overrides = []): array
{
    return array_merge([
        'title' => 'Layanan Contoh',
        'slug' => 'layanan-contoh',
        'service_category_id' => $category->getKey(),
        'thumbnail_media_id' => null,
        'thumbnail_alt' => null,
        'summary' => 'Ringkasan singkat.',
        'introduction' => '<p>Pengantar.</p>',
        'schedule_info' => null,
        'fee_info' => null,
        'contact_name' => null,
        'contact_phone' => null,
        'form_url' => null,
        'form_button_label' => 'Daftar Sekarang',
        'is_form_open' => false,
        'form_closed_message' => null,
        'is_emergency' => false,
        'is_active' => false,
        'sort_order' => 0,
    ], $overrides);
}

it('creates a service and lands on its edit page', function () {
    $category = ServiceCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('services.store'), servicePayload($category, ['title' => 'Baptis Bayi', 'slug' => 'baptis-bayi']))
        ->assertRedirect(route('services.edit', Service::query()->sole()));

    $service = Service::query()->sole();

    expect($service->title)->toBe('Baptis Bayi')
        ->and($service->is_active)->toBeFalse()
        ->and($service->form_url)->toBeNull();
});

it('rejects invalid input and persists nothing', function () {
    $category = ServiceCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('services.store'), servicePayload($category, [
            'title' => '',
            'slug' => 'bukan slug!',
        ]))
        ->assertSessionHasErrors(['title', 'slug']);

    expect(Service::count())->toBe(0);
});

it('rejects a duplicate slug', function () {
    $category = ServiceCategory::factory()->create();
    Service::factory()->create(['slug' => 'baptis-bayi']);

    $this->actingAs(superAdmin())
        ->post(route('services.store'), servicePayload($category, ['slug' => 'baptis-bayi']))
        ->assertSessionHasErrors('slug');
});

it('sanitizes the introduction on the way in', function () {
    $category = ServiceCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('services.store'), servicePayload($category, [
            'introduction' => '<p>Baik</p><script>alert(1)</script>',
        ]));

    expect(Service::query()->sole()->introduction)
        ->toContain('<p>Baik</p>')
        ->not->toContain('<script');
});

/*
|--------------------------------------------------------------------------
| Google Form opsional (AC-SVC-3, 4, 5)
|--------------------------------------------------------------------------
*/

it('accepts a service with no Google Form', function () {
    $category = ServiceCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('services.store'), servicePayload($category, ['form_url' => null]))
        ->assertSessionHasNoErrors();

    expect(Service::query()->sole()->form_url)->toBeNull();
});

it('accepts a valid Google Form URL', function (string $url) {
    $category = ServiceCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('services.store'), servicePayload($category, ['form_url' => $url]))
        ->assertSessionHasNoErrors();

    expect(Service::query()->sole()->form_url)->toBe($url);
})->with([
    'docs.google.com' => ['https://docs.google.com/forms/d/e/abc/viewform'],
    'forms.gle' => ['https://forms.gle/AbC123'],
]);

it('rejects a URL that is not a Google Form', function (string $url) {
    $category = ServiceCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('services.store'), servicePayload($category, ['form_url' => $url]))
        ->assertSessionHasErrors('form_url');
})->with([
    'http scheme' => ['http://docs.google.com/forms/d/e/abc/viewform'],
    'suffix host' => ['https://docs.google.com.evil.com/forms/abc'],
    'query bypass' => ['https://evil.com/?next=docs.google.com/forms/abc'],
    'credentials' => ['https://user:pass@docs.google.com/forms/abc'],
    'wrong path' => ['https://docs.google.com/not-forms/abc'],
]);

it('closes registration when there is no form URL', function () {
    $category = ServiceCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('services.store'), servicePayload($category, [
            'form_url' => null,
            'is_form_open' => true,
        ]));

    expect(Service::query()->sole()->is_form_open)->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Emergency (AC-SVC-9, SVC-06)
|--------------------------------------------------------------------------
*/

it('refuses to activate an emergency service without a contact phone', function () {
    $category = ServiceCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('services.store'), servicePayload($category, [
            'is_emergency' => true,
            'is_active' => true,
            'contact_phone' => null,
        ]))
        ->assertSessionHasErrors('contact_phone');
});

it('activates an emergency service that has a contact phone', function () {
    $category = ServiceCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('services.store'), servicePayload($category, [
            'is_emergency' => true,
            'is_active' => true,
            'contact_phone' => '0812-3456-7890',
        ]))
        ->assertSessionHasNoErrors();

    expect(Service::query()->sole()->is_active)->toBeTrue();
});

it('clears the form URL of an emergency service', function () {
    $category = ServiceCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('services.store'), servicePayload($category, [
            'is_emergency' => true,
            'form_url' => 'https://docs.google.com/forms/d/e/abc/viewform',
            'is_form_open' => true,
        ]));

    $service = Service::query()->sole();

    expect($service->form_url)->toBeNull()
        ->and($service->is_form_open)->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| Kategori aktif (AC-SVC-13)
|--------------------------------------------------------------------------
*/

it('refuses a new service in an inactive category', function () {
    $category = ServiceCategory::factory()->inactive()->create();

    $this->actingAs(superAdmin())
        ->post(route('services.store'), servicePayload($category))
        ->assertSessionHasErrors('service_category_id');
});

it('refuses to activate a service whose category is inactive', function () {
    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create([
        'service_category_id' => $category->getKey(),
        'is_active' => false,
    ]);

    $category->update(['is_active' => false]);

    $this->actingAs(superAdmin())
        ->patch(route('services.update', $service), servicePayload($category, [
            'is_active' => true,
        ]))
        ->assertSessionHasErrors('service_category_id');
});

/*
|--------------------------------------------------------------------------
| Update & delete
|--------------------------------------------------------------------------
*/

it('updates a service', function () {
    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create(['service_category_id' => $category->getKey()]);

    $this->actingAs(superAdmin())
        ->patch(route('services.update', $service), servicePayload($category, ['title' => 'Judul Baru']))
        ->assertRedirect(route('services.edit', $service));

    expect($service->fresh()->title)->toBe('Judul Baru');
});

it('soft deletes a service', function () {
    $service = Service::factory()->create();

    $this->actingAs(superAdmin())
        ->delete(route('services.destroy', $service))
        ->assertRedirect(route('services.index'));

    expect(Service::count())->toBe(0)
        ->and(Service::withTrashed()->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Halaman admin
|--------------------------------------------------------------------------
*/

it('renders the service list', function () {
    $category = ServiceCategory::factory()->create();
    Service::factory()->create(['service_category_id' => $category->getKey()]);

    $this->actingAs(superAdmin())
        ->get(route('services.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/pelayanan')
            // Paginated props keep the `data` wrapper — see D-39.
            ->has('services.data', 1)
            ->where('services.data.0.registration_method', 'unset')
            ->has('categories'));
});

it('renders the create page', function () {
    ServiceCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->get(route('services.create'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/pelayanan/tambah')
            ->has('categories'));
});

it('renders the edit page with its children', function () {
    $category = ServiceCategory::factory()->create();
    $service = Service::factory()->create(['service_category_id' => $category->getKey()]);

    $this->actingAs(superAdmin())
        ->get(route('services.edit', $service))
        ->assertInertia(fn ($page) => $page
            ->component('admin/pelayanan/ubah')
            ->where('service.title', $service->title)
            ->has('requirements')
            ->has('steps')
            ->has('faqs'));
});
