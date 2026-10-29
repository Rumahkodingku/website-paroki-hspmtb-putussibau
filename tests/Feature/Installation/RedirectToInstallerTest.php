<?php

use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    configureInstaller();
});

afterEach(function () {
    resetInstallerStatusFile();
});

test('the homepage redirects to the installer while installation is pending', function () {
    $this->get('/')
        ->assertRedirect(route('install.show'));
});

test('other pages are redirected too', function () {
    $this->get('/berita')
        ->assertRedirect(route('install.show'));

    $this->get('/admin')
        ->assertRedirect(route('install.show'));
});

test('the installer, health check, and assets are not redirected', function () {
    $this->get('/install')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('installation/wizard'));

    $this->get('/up')->assertOk();

    $response = $this->get('/build/nonexistent.js');
    expect($response->getStatusCode())->not->toBe(302);

    $response = $this->get('/storage/nonexistent');
    expect($response->getStatusCode())->not->toBe(302);

    $response = $this->get('/favicon.ico');
    expect($response->getStatusCode())->not->toBe(302);

    $response = $this->get('/robots.txt');
    expect($response->getStatusCode())->not->toBe(302);
});

test('a completed installation shows the normal site', function () {
    setInstallerStatus('completed');

    $this->get('/')->assertOk();
});

test('an installation in progress redirects to the installer', function () {
    setInstallerStatus('installing');

    $this->get('/')->assertRedirect(route('install.show'));
});

test('a failed installation redirects to the installer', function () {
    setInstallerStatus('failed', ['error_code' => 'MIGRATION_FAILED']);

    $this->get('/')->assertRedirect(route('install.show'));
});

test('returns 503 when the installer is disabled mid-installation', function () {
    setInstallerStatus('pending');
    config(['installer.enabled' => false]);

    $this->get('/')->assertStatus(503);
    $this->get('/install')->assertNotFound();
});

test('passes through when the installer is disabled and there is no status file', function () {
    config(['installer.enabled' => false]);

    $this->get('/')->assertOk();
});

test('returns the normal site when the installer is disabled but installation is complete', function () {
    setInstallerStatus('completed');
    config(['installer.enabled' => false]);

    $this->get('/')->assertOk();
});
