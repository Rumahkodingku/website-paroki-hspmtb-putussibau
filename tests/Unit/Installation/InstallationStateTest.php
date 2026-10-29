<?php

use App\Enums\InstallationError;
use App\Services\Installation\InstallationState;

beforeEach(function () {
    resetInstallerStatusFile();
    config(['installer.status_path' => installerStatusFile()]);
});

afterEach(function () {
    resetInstallerStatusFile();
});

test('a missing file means pending', function () {
    $state = app(InstallationState::class);

    expect($state->status())->toBe(InstallationState::PENDING)
        ->and($state->currentStep())->toBeNull()
        ->and($state->errorCode())->toBeNull();
});

test('an installation moves from pending to installing to completed', function () {
    $state = app(InstallationState::class);

    $state->begin();

    expect($state->status())->toBe(InstallationState::INSTALLING)
        ->and($state->currentStep())->toBe('validate')
        ->and($state->isInstalling())->toBeTrue();

    $state->step('migrate');

    expect($state->currentStep())->toBe('migrate');

    $state->complete();

    expect($state->status())->toBe(InstallationState::COMPLETED)
        ->and($state->isCompleted())->toBeTrue()
        ->and($state->currentStep())->toBe('lock');
});

test('a failure records a safe error code and time', function () {
    $state = app(InstallationState::class);

    $state->begin();
    $state->step('migrate');
    $state->fail(InstallationError::MigrationFailed);

    expect($state->status())->toBe(InstallationState::FAILED)
        ->and($state->isFailed())->toBeTrue()
        ->and($state->errorCode())->toBe(InstallationError::MigrationFailed->value)
        ->and($state->payload()['failed_at'])->not->toBeNull();
});

test('recording a step before the run starts is refused', function () {
    $state = app(InstallationState::class);

    expect(fn () => $state->step('migrate'))
        ->toThrow(RuntimeException::class);
});

test('an unreadable status file fails closed', function () {
    file_put_contents(installerStatusFile(), '{not json');

    $state = app(InstallationState::class);

    expect($state->status())->toBe(InstallationState::FAILED)
        ->and($state->errorCode())->toBe(InstallationError::StatusUnreadable->value);
});

test('a status file with an unknown state fails closed', function () {
    file_put_contents(installerStatusFile(), json_encode([
        'version' => 1,
        'status' => 'something-else',
    ]));

    $state = app(InstallationState::class);

    expect($state->status())->toBe(InstallationState::FAILED)
        ->and($state->errorCode())->toBe(InstallationError::StatusUnreadable->value);
});
