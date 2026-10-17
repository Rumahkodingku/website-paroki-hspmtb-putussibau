<?php

use App\Models\Document;
use App\Models\Service;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function serviceDocumentPayload(Service $service, array $overrides = []): array
{
    return array_merge([
        'title' => $service->title,
        'slug' => $service->slug,
        'service_category_id' => $service->service_category_id,
        'summary' => $service->summary,
        'introduction' => $service->introduction,
        'form_button_label' => $service->form_button_label,
        'is_form_open' => false,
        'is_emergency' => false,
        'is_active' => true,
        'sort_order' => 0,
        'document_ids' => [],
    ], $overrides);
}

it('links published documents from the service form', function () {
    $service = Service::factory()->create();
    $document = Document::factory()->published()->create();

    $this->actingAs(superAdmin())
        ->patch(route('services.update', $service), serviceDocumentPayload($service, [
            'document_ids' => [$document->getKey()],
        ]))
        ->assertSessionHasNoErrors();

    expect($service->fresh()->documents->pluck('id')->all())
        ->toBe([$document->getKey()]);
});

it('refuses to link a draft document', function () {
    $service = Service::factory()->create();
    $draft = Document::factory()->create();

    $this->actingAs(superAdmin())
        ->patch(route('services.update', $service), serviceDocumentPayload($service, [
            'document_ids' => [$draft->getKey()],
        ]))
        ->assertSessionHasErrors('document_ids');

    expect($service->fresh()->documents()->count())->toBe(0);
});

it('does not show an archived document on the public service page', function () {
    $service = Service::factory()->active()->create();
    $published = Document::factory()->published()->create();
    $archived = Document::factory()->archived()->create();

    $service->documents()->attach([$published->getKey(), $archived->getKey()]);

    $this->get(route('pelayanan.show', $service))
        ->assertInertia(fn ($page) => $page
            ->has('service.documents', 1)
            ->where('service.documents.0.id', $published->getKey()));
});

it('keeps a service valid without documents', function () {
    $service = Service::factory()->active()->create();

    $this->get(route('pelayanan.show', $service))
        ->assertInertia(fn ($page) => $page->has('service.documents', 0));
});

it('detaches a document when the service is saved without it', function () {
    $service = Service::factory()->create();
    $document = Document::factory()->published()->create();
    $service->documents()->attach($document);

    $this->actingAs(superAdmin())
        ->patch(route('services.update', $service), serviceDocumentPayload($service))
        ->assertSessionHasNoErrors();

    expect($service->fresh()->documents()->count())->toBe(0);
});
