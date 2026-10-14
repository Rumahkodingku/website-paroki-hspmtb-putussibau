<?php

use App\Models\Service;
use App\Models\ServiceFaq;

/*
|--------------------------------------------------------------------------
| FAQ per layanan
|--------------------------------------------------------------------------
|
| Recipe 6B-276.
|
| @see docs/DECISIONS.md D-54
*/

it('adds a faq to a service', function () {
    $service = Service::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('service-faqs.store', $service), [
            'question' => 'Apakah ada biaya?',
            'answer' => 'Silakan hubungi sekretariat.',
        ])
        ->assertRedirect(route('services.edit', ['service' => $service, 'tab' => 'faq']));

    expect($service->faqs()->sole()->question)->toBe('Apakah ada biaya?');
});

it('requires both the question and the answer', function () {
    $service = Service::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('service-faqs.store', $service), ['question' => '', 'answer' => ''])
        ->assertSessionHasErrors(['question', 'answer']);
});

it('updates a faq', function () {
    $service = Service::factory()->create();
    $faq = ServiceFaq::factory()->create(['service_id' => $service->getKey()]);

    $this->actingAs(superAdmin())
        ->patch(route('service-faqs.update', [$service, $faq]), [
            'question' => 'Pertanyaan baru',
            'answer' => 'Jawaban baru',
        ])
        ->assertRedirect();

    expect($faq->fresh()->question)->toBe('Pertanyaan baru');
});

it('deletes a faq', function () {
    $service = Service::factory()->create();
    $faq = ServiceFaq::factory()->create(['service_id' => $service->getKey()]);

    $this->actingAs(superAdmin())
        ->delete(route('service-faqs.destroy', [$service, $faq]))
        ->assertRedirect();

    expect(ServiceFaq::count())->toBe(0);
});

it('reorders faqs', function () {
    $service = Service::factory()->create();
    $first = ServiceFaq::factory()->create(['service_id' => $service->getKey(), 'sort_order' => 0]);
    $second = ServiceFaq::factory()->create(['service_id' => $service->getKey(), 'sort_order' => 1]);

    $this->actingAs(superAdmin())
        ->post(route('service-faqs.reorder', $service), ['ids' => [$second->getKey(), $first->getKey()]]);

    expect($first->fresh()->sort_order)->toBe(1)
        ->and($second->fresh()->sort_order)->toBe(0);
});

it('answers 404 for a faq from another service', function () {
    $service = Service::factory()->create();
    $faq = ServiceFaq::factory()->create(['service_id' => Service::factory()->create()->getKey()]);

    $this->actingAs(superAdmin())
        ->delete(route('service-faqs.destroy', [$service, $faq]))
        ->assertNotFound();
});
