<?php

use App\Models\Service;
use App\Services\SiteSettingsService;

/*
|--------------------------------------------------------------------------
| Fallback kontak (AC-SVC-14, roadmap 9.2.2)
|--------------------------------------------------------------------------
|
| @see docs/DECISIONS.md D-54
*/

it('uses the service contact when it has one', function () {
    $service = Service::factory()->active()->create([
        'contact_name' => 'Penanggung Jawab',
        'contact_phone' => '0812-3456-7890',
    ]);

    $this->get(route('pelayanan.show', $service))
        ->assertInertia(fn ($page) => $page
            ->where('service.contact.source', 'service')
            ->where('service.contact.tel', 'tel:+6281234567890')
            ->where('service.contact.whatsapp', 'https://wa.me/6281234567890'));
});

it('falls back to the secretariat setting when the service has no contact', function () {
    app(SiteSettingsService::class)->set('contact_phone', '081200000000');

    $service = Service::factory()->active()->create(['contact_phone' => null]);

    $this->get(route('pelayanan.show', $service))
        ->assertInertia(fn ($page) => $page
            ->where('service.contact.source', 'secretariat')
            ->where('service.contact.tel', 'tel:+6281200000000'));
});

it('exposes no link when neither the service nor the secretariat has a number', function () {
    $service = Service::factory()->active()->create(['contact_phone' => null]);

    $this->get(route('pelayanan.show', $service))
        ->assertInertia(fn ($page) => $page
            ->where('service.contact.source', null)
            ->where('service.contact.tel', null)
            ->where('service.contact.whatsapp', null));
});
