<?php

use App\Models\Service;
use App\Models\ServiceRequirement;

/*
|--------------------------------------------------------------------------
| Persyaratan dokumen per layanan
|--------------------------------------------------------------------------
|
| Recipe 6B-274 and AC-SVC-10; ordering, ownership, and SVC-03.
|
| @see docs/DECISIONS.md D-54
*/

it('adds a requirement to a service', function () {
    $service = Service::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('service-requirements.store', $service), ['text' => 'Fotokopi Kartu Keluarga'])
        ->assertRedirect(route('services.edit', ['service' => $service, 'tab' => 'persyaratan']));

    expect($service->requirements()->sole()->text)->toBe('Fotokopi Kartu Keluarga');
});

it('appends a new requirement after the existing ones', function () {
    $service = Service::factory()->create();
    ServiceRequirement::factory()->create(['service_id' => $service->getKey(), 'sort_order' => 4]);

    $this->actingAs(superAdmin())
        ->post(route('service-requirements.store', $service), ['text' => 'Berikutnya']);

    expect($service->requirements()->orderByDesc('sort_order')->first()->sort_order)->toBe(5);
});

it('updates a requirement', function () {
    $service = Service::factory()->create();
    $requirement = ServiceRequirement::factory()->create(['service_id' => $service->getKey()]);

    $this->actingAs(superAdmin())
        ->patch(route('service-requirements.update', [$service, $requirement]), ['text' => 'Diubah'])
        ->assertRedirect();

    expect($requirement->fresh()->text)->toBe('Diubah');
});

it('deletes a requirement', function () {
    $service = Service::factory()->create();
    $requirement = ServiceRequirement::factory()->create(['service_id' => $service->getKey()]);

    $this->actingAs(superAdmin())
        ->delete(route('service-requirements.destroy', [$service, $requirement]))
        ->assertRedirect();

    expect(ServiceRequirement::count())->toBe(0);
});

it('answers 404 for a requirement from another service', function () {
    $service = Service::factory()->create();
    $other = Service::factory()->create();
    $requirement = ServiceRequirement::factory()->create(['service_id' => $other->getKey()]);

    $this->actingAs(superAdmin())
        ->patch(route('service-requirements.update', [$service, $requirement]), ['text' => 'Diubah'])
        ->assertNotFound();
});

it('reorders requirements from a submitted order', function () {
    $service = Service::factory()->create();
    $first = ServiceRequirement::factory()->create(['service_id' => $service->getKey(), 'sort_order' => 0]);
    $second = ServiceRequirement::factory()->create(['service_id' => $service->getKey(), 'sort_order' => 1]);

    $this->actingAs(superAdmin())
        ->post(route('service-requirements.reorder', $service), ['ids' => [$second->getKey(), $first->getKey()]])
        ->assertRedirect();

    expect($first->fresh()->sort_order)->toBe(1)
        ->and($second->fresh()->sort_order)->toBe(0);
});

it('rejects a reorder containing an id from another service', function () {
    $service = Service::factory()->create();
    $mine = ServiceRequirement::factory()->create(['service_id' => $service->getKey()]);
    $foreign = ServiceRequirement::factory()->create(['service_id' => Service::factory()->create()->getKey()]);

    $this->actingAs(superAdmin())
        ->post(route('service-requirements.reorder', $service), ['ids' => [$mine->getKey(), $foreign->getKey()]])
        ->assertSessionHasErrors('ids');

    expect($mine->fresh()->sort_order)->toBe(0);
});

it('touches the service when a requirement changes', function () {
    $service = Service::factory()->create();
    $before = $service->updated_at;

    $this->travel(1)->minutes();

    $this->actingAs(superAdmin())
        ->post(route('service-requirements.store', $service), ['text' => 'Berkas baru']);

    expect($service->fresh()->updated_at->greaterThan($before))->toBeTrue();
});

it('stores requirement condition and applies_to', function () {
    $service = Service::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('service-requirements.store', $service), [
            'text' => 'Fotokopi KK',
            'requirement_condition' => 'conditional',
            'applies_to' => 'Wali baptis',
        ])
        ->assertRedirect();

    $requirement = $service->requirements()->sole();

    expect($requirement->requirement_condition)->toBe('conditional')
        ->and($requirement->applies_to)->toBe('Wali baptis');
});

it('rejects an unknown requirement condition', function () {
    $service = Service::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('service-requirements.store', $service), [
            'text' => 'Fotokopi KK',
            'requirement_condition' => 'maybe',
        ])
        ->assertSessionHasErrors('requirement_condition');
});

it('defaults requirement condition to required when omitted', function () {
    $service = Service::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('service-requirements.store', $service), ['text' => 'Fotokopi KK'])
        ->assertRedirect();

    expect($service->requirements()->sole()->requirement_condition)->toBe('required');
});

it('rejects applies_to longer than 100 characters', function () {
    $service = Service::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('service-requirements.store', $service), [
            'text' => 'Fotokopi KK',
            'applies_to' => str_repeat('a', 101),
        ])
        ->assertSessionHasErrors('applies_to');
});
