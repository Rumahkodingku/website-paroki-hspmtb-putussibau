<?php

it('serves a crawl policy that points at the configured sitemap', function () {
    $response = $this->get('/robots.txt')->assertOk();

    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    expect($response->getContent())
        ->toContain('User-agent: *')
        ->toContain('Allow: /')
        ->toContain('Disallow: /admin')
        ->toContain('Disallow: /install')
        ->toContain('Sitemap: http://localhost/sitemap.xml');
});

it('stays reachable while the installer is pending', function () {
    configureInstaller();

    $this->get('/robots.txt')->assertOk();
});
