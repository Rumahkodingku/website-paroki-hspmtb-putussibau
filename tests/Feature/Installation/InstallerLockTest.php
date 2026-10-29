<?php

beforeEach(function () {
    configureInstaller();
});

afterEach(function () {
    resetInstallerStatusFile();
});

test('a run is refused while another installation is in progress', function () {
    setInstallerStatus('installing');

    $this->postJson('/install/run', ['confirmed' => true])
        ->assertStatus(409)
        ->assertJson([
            'status' => 'installing',
            'error_code' => 'INSTALL_LOCKED',
        ]);
});

test('a failed installation does not expose a retry endpoint', function () {
    setInstallerStatus('failed', ['error_code' => 'MIGRATION_FAILED']);

    $this->postJson('/install/run', ['confirmed' => true])
        ->assertStatus(409)
        ->assertJson([
            'status' => 'failed',
            'error_code' => 'MIGRATION_FAILED',
        ]);
});

test('a completed installation refuses every mutating installer endpoint', function () {
    setInstallerStatus('completed');

    $this->postJson('/install/run', ['confirmed' => true])->assertStatus(409);
    $this->post('/install/database/test')->assertStatus(409);
    $this->post('/install/super-admin/validate', [])->assertStatus(409);
    $this->post('/install/application/validate', [])->assertStatus(409);
});

test('the progress endpoint reports the backend state without secrets', function () {
    setInstallerStatus('installing', ['current_step' => 'migrate']);

    $this->getJson('/install/progress')
        ->assertOk()
        ->assertJson([
            'status' => 'installing',
            'current_step' => 'migrate',
        ])
        ->assertJsonMissingPath('admin')
        ->assertJsonMissingPath('password');
});

test('the installer refuses to run when it is disabled', function () {
    config(['installer.enabled' => false]);

    $this->postJson('/install/run', ['confirmed' => true])->assertNotFound();
});
