<?php

use App\Models\Service;
use App\Models\ServiceStep;

/*
|--------------------------------------------------------------------------
| Prosedur per layanan
|--------------------------------------------------------------------------
|
| Recipe 6B-275; ordering and ownership.
|
| @see docs/DECISIONS.md D-54
*/

it('adds a step to a service', function () {
    $service = Service::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('service-steps.store', $service), ['title' => 'Mengisi formulir'])
        ->assertRedirect(route('services.edit', ['service' => $service, 'tab' => 'prosedur']));

    expect($service->steps()->sole()->title)->toBe('Mengisi formulir');
});

it('updates a step', function () {
    $service = Service::factory()->create();
    $step = ServiceStep::factory()->create(['service_id' => $service->getKey()]);

    $this->actingAs(superAdmin())
        ->patch(route('service-steps.update', [$service, $step]), ['title' => 'Langkah baru'])
        ->assertRedirect();

    expect($step->fresh()->title)->toBe('Langkah baru');
});

it('deletes a step', function () {
    $service = Service::factory()->create();
    $step = ServiceStep::factory()->create(['service_id' => $service->getKey()]);

    $this->actingAs(superAdmin())
        ->delete(route('service-steps.destroy', [$service, $step]))
        ->assertRedirect();

    expect(ServiceStep::count())->toBe(0);
});

it('reorders steps', function () {
    $service = Service::factory()->create();
    $first = ServiceStep::factory()->create(['service_id' => $service->getKey(), 'sort_order' => 0]);
    $second = ServiceStep::factory()->create(['service_id' => $service->getKey(), 'sort_order' => 1]);

    $this->actingAs(superAdmin())
        ->post(route('service-steps.reorder', $service), ['ids' => [$second->getKey(), $first->getKey()]]);

    expect($first->fresh()->sort_order)->toBe(1)
        ->and($second->fresh()->sort_order)->toBe(0);
});

it('answers 404 for a step from another service', function () {
    $service = Service::factory()->create();
    $step = ServiceStep::factory()->create(['service_id' => Service::factory()->create()->getKey()]);

    $this->actingAs(superAdmin())
        ->patch(route('service-steps.update', [$service, $step]), ['title' => 'Diubah'])
        ->assertNotFound();
});
