<?php

use App\Actions\Backup\PruneBackupsAction;
use App\Enums\BackupRestoreStatus;
use App\Enums\BackupSource;
use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Enums\BackupValidationStatus;
use App\Models\Backup;
use App\Models\BackupRestore;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Storage::fake('backups');
});

/**
 * @param  array<string, mixed>  $attributes
 */
function retainedBackup(array $attributes): Backup
{
    $backup = Backup::factory()->create(array_merge([
        'uuid' => (string) Str::uuid(),
        'disk' => 'backups',
        'status' => BackupStatus::Success,
        'validation_status' => BackupValidationStatus::Passed,
        'path' => 'archives/'.Str::uuid().'/backup.sql.gz',
        'created_at' => now()->subDays(20),
    ], $attributes));

    Storage::disk('backups')->put((string) $backup->path, 'artifact');

    return $backup;
}

test('retention removes only eligible backups and protects safety backups', function (): void {
    $manual = retainedBackup([
        'source' => BackupSource::Manual,
        'type' => BackupType::Database,
    ]);
    $daily = retainedBackup([
        'source' => BackupSource::Scheduled,
        'type' => BackupType::Database,
        'scheduled_slot' => 'database:old',
    ]);
    $weekly = retainedBackup([
        'source' => BackupSource::Scheduled,
        'type' => BackupType::Full,
        'scheduled_slot' => 'full:old',
        'created_at' => now()->subWeeks(5),
    ]);
    $uploaded = retainedBackup([
        'source' => BackupSource::Uploaded,
        'type' => BackupType::Database,
        'status' => BackupStatus::Failed,
        'validation_status' => BackupValidationStatus::Failed,
        'created_at' => now()->subDays(10),
    ]);
    $safety = retainedBackup([
        'source' => BackupSource::PreRestore,
        'type' => BackupType::Database,
        'protected_until' => now()->addDays(10),
    ]);

    $protected = retainedBackup([
        'source' => BackupSource::Scheduled,
        'type' => BackupType::Database,
        'scheduled_slot' => 'database:active',
    ]);
    BackupRestore::factory()->create([
        'backup_id' => $protected->getKey(),
        'status' => BackupRestoreStatus::Ready,
        'expires_at' => now()->addHour(),
    ]);

    $result = app(PruneBackupsAction::class)->handle();

    expect($result['deleted'])->toBe(3)
        ->and(Backup::query()->whereKey($manual->getKey())->exists())->toBeTrue()
        ->and(Backup::query()->whereKey($safety->getKey())->exists())->toBeTrue()
        ->and(Backup::query()->whereKey($protected->getKey())->exists())->toBeTrue()
        ->and(Backup::query()->whereKey($daily->getKey())->exists())->toBeFalse()
        ->and(Backup::query()->whereKey($weekly->getKey())->exists())->toBeFalse()
        ->and(Backup::query()->whereKey($uploaded->getKey())->exists())->toBeFalse();
});

test('expired ready restore operations are closed', function (): void {
    $backup = retainedBackup([
        'source' => BackupSource::Manual,
        'type' => BackupType::Database,
    ]);
    $restore = BackupRestore::factory()->create([
        'backup_id' => $backup->getKey(),
        'status' => BackupRestoreStatus::Ready,
        'expires_at' => now()->subMinute(),
    ]);

    $result = app(PruneBackupsAction::class)->handle();

    expect($result['expired'])->toBe(1)
        ->and($restore->fresh()->status)->toBe(BackupRestoreStatus::Expired);
});
