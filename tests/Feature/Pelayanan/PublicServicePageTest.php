<?php

use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceFaq;
use App\Models\ServiceRequirement;
use App\Models\ServiceStep;

/*
|--------------------------------------------------------------------------
| Halaman publik Pelayanan
|--------------------------------------------------------------------------
|
| AC-SVC-1, 2, 5; PRD 7.6; XC-E3.
|
| @see docs/DECISIONS.md D-54
*/

it('groups active services by category and hides empty categories', function () {
    $visible = ServiceCategory::factory()->create(['name' => 'Sakramen Inisiasi']);
    $empty = ServiceCategory::factory()->create(['name' => 'Kategori Kosong']);

    Service::factory()->active()->count(2)->create(['service_category_id' => $visible->getKey()]);
    Service::factory()->create(['service_category_id' => $visible->getKey()]);

    $this->get(route('pelayanan.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/pelayanan')
            ->has('groups', 1)
            ->where('groups.0.name', 'Sakramen Inisiasi')
            ->has('groups.0.services', 2)
            ->where('hasServices', true));
});

it('renders an empty index when nothing is published', function () {
    Service::factory()->create();

    $this->get(route('pelayanan.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/pelayanan')
            ->has('groups', 0)
            ->where('hasServices', false));
});

it('shows an active service detail', function () {
    $service = Service::factory()->active()->create(['title' => 'Baptis Bayi']);

    $this->get(route('pelayanan.show', $service))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('public/pelayanan/show')
            ->where('service.title', 'Baptis Bayi'));
});

it('answers 404 for an inactive service', function () {
    $service = Service::factory()->create(['is_active' => false]);

    $this->get(route('pelayanan.show', $service))->assertNotFound();
});

it('answers 404 for a soft-deleted service', function () {
    $service = Service::factory()->active()->create();
    $service->delete();

    $this->get(route('pelayanan.show', $service))->assertNotFound();
});

it('answers 404 for an unknown slug', function () {
    $this->get('/pelayanan/tidak-ada')->assertNotFound();
});

it('orders requirements, steps and faqs by position', function () {
    $service = Service::factory()->active()->create();

    ServiceRequirement::factory()->create(['service_id' => $service->getKey(), 'text' => 'Kedua', 'sort_order' => 1]);
    ServiceRequirement::factory()->create(['service_id' => $service->getKey(), 'text' => 'Pertama', 'sort_order' => 0]);
    ServiceStep::factory()->create(['service_id' => $service->getKey(), 'title' => 'Langkah Dua', 'sort_order' => 1]);
    ServiceStep::factory()->create(['service_id' => $service->getKey(), 'title' => 'Langkah Satu', 'sort_order' => 0]);
    ServiceFaq::factory()->create(['service_id' => $service->getKey(), 'question' => 'Q2', 'sort_order' => 1]);
    ServiceFaq::factory()->create(['service_id' => $service->getKey(), 'question' => 'Q1', 'sort_order' => 0]);

    $this->get(route('pelayanan.show', $service))
        ->assertInertia(fn ($page) => $page
            ->where('service.requirements.0.text', 'Pertama')
            ->where('service.steps.0.title', 'Langkah Satu')
            ->where('service.faqs.0.question', 'Q1'));
});

/*
|--------------------------------------------------------------------------
| Empat kondisi CTA (AC-SVC-2, 5)
|--------------------------------------------------------------------------
*/

it('offers a live registration button when the form is open', function () {
    $service = Service::factory()->active()->withForm()->create();

    $this->get(route('pelayanan.show', $service))
        ->assertInertia(fn ($page) => $page
            ->where('service.cta.state', 'form_open')
            ->where('service.cta.url', $service->form_url));
});

it('offers a closed registration state when the form is closed', function () {
    $service = Service::factory()->active()->formClosed()->create();

    $this->get(route('pelayanan.show', $service))
        ->assertInertia(fn ($page) => $page
            ->where('service.cta.state', 'form_closed')
            ->where('service.cta.url', null)
            ->where('service.cta.message', 'Pendaftaran sedang ditutup.'));
});

it('points to the secretariat when there is no form', function () {
    $service = Service::factory()->active()->create();

    $this->get(route('pelayanan.show', $service))
        ->assertInertia(fn ($page) => $page->where('service.cta.state', 'contact'));
});

it('uses the direct contact state for an emergency service', function () {
    $service = Service::factory()->active()->emergency()->create();

    $this->get(route('pelayanan.show', $service))
        ->assertInertia(fn ($page) => $page
            ->where('service.cta.state', 'emergency')
            ->where('service.cta.url', null));
});
