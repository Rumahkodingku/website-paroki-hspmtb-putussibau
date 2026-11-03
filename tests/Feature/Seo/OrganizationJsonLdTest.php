<?php

use App\Services\SiteSettingsService;

it('renders organization and website schema in the initial HTML', function () {
    app(SiteSettingsService::class)->setMany([
        'parish_name' => 'Paroki Contoh',
        'contact_address' => 'Jl. Contoh No. 1',
        'contact_phone' => '081234567890',
        'contact_email' => 'paroki@example.test',
        'social_facebook' => 'https://facebook.com/paroki',
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('"@type":"Organization"')
        ->toContain('"@type":"WebSite"')
        ->toContain('"name":"Paroki Contoh"')
        ->toContain('"sameAs":["https://facebook.com/paroki"]')
        ->toContain('"inLanguage":"id-ID"');
});

it('does not let a setting close the JSON-LD script element', function () {
    app(SiteSettingsService::class)->set('parish_name', '</script><img src=x>');

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->not->toContain('</script><img');
});
