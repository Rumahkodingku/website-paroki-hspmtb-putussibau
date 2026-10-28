<?php

use App\Jobs\CreateBackupJob;
use App\Models\Backup;
use App\Models\BackupSetting;
use App\Services\Backup\BackupSettingsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

beforeEach(function (): void {
    config(['backups.queue_connection' => 'sync']);
    Queue::fake();
});

test('the scheduled command creates one database backup per slot', function (): void {
    $this->travelTo(Carbon::parse('2026-10-28 02:00:00', 'Asia/Pontianak'));

    $this->artisan('backups:run-scheduled')->assertSuccessful();

    expect(Backup::query()->where('scheduled_slot', 'database:2026-10-28')->count())->toBe(1);

    $this->artisan('backups:run-scheduled')->assertSuccessful();

    expect(Backup::query()->where('scheduled_slot', 'database:2026-10-28')->count())->toBe(1);

    Queue::assertPushed(CreateBackupJob::class, 1);
});

test('the scheduled command creates the weekly full backup on Sunday', function (): void {
    $this->travelTo(Carbon::parse('2026-11-01 02:30:00', 'Asia/Pontianak'));

    $this->artisan('backups:run-scheduled')->assertSuccessful();

    $backup = Backup::query()->where('scheduled_slot', 'full:2026-W44')->first();

    expect($backup)->not->toBeNull()
        ->and($backup?->type->value)->toBe('full');

    Queue::assertPushed(CreateBackupJob::class, 1);
});

test('the scheduled command does nothing when the schedule is disabled', function (): void {
    BackupSetting::query()->forceCreate(array_merge(
        app(BackupSettingsService::class)->defaults(),
        ['id' => 1, 'schedule_enabled' => false],
    ));
    $this->travelTo(Carbon::parse('2026-10-28 02:00:00', 'Asia/Pontianak'));

    $this->artisan('backups:run-scheduled')->assertSuccessful();

    expect(Backup::query()->count())->toBe(0);
});

test('the scheduled command skips when no worker is detected', function (): void {
    config(['backups.queue_connection' => 'backups']);
    $this->travelTo(Carbon::parse('2026-10-28 02:00:00', 'Asia/Pontianak'));

    $this->artisan('backups:run-scheduled')->assertSuccessful();

    expect(Backup::query()->count())->toBe(0);
    Queue::assertNothingPushed();
});
