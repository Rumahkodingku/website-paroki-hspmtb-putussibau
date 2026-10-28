<?php

use App\Enums\BackupRestoreStatus;
use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Enums\BackupValidationStatus;
use App\Jobs\PrepareRestoreJob;
use App\Models\Backup;
use App\Models\BackupRestore;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Storage::fake('backups');
    Storage::fake('local');
    Storage::fake('public');
});

test('preparing a restore creates a validated safety backup before confirmation', function (): void {
    $backup = Backup::factory()->create([
        'type' => BackupType::Database,
        'status' => BackupStatus::Success,
        'validation_status' => BackupValidationStatus::Passed,
        'disk' => 'backups',
        'path' => 'archives/'.Str::uuid().'/source.sql.gz',
    ]);

    Storage::disk('backups')->put((string) $backup->path, 'source-artifact');

    $restore = BackupRestore::factory()->create([
        'backup_id' => $backup->getKey(),
        'status' => BackupRestoreStatus::Queued,
    ]);

    app()->call([new PrepareRestoreJob($restore->getKey()), 'handle']);

    $restore->refresh();

    expect($restore->status)->toBe(BackupRestoreStatus::Ready)
        ->and($restore->safety_backup_id)->not->toBeNull()
        ->and($restore->expires_at)->not->toBeNull();

    $safety = Backup::query()->findOrFail($restore->safety_backup_id);

    expect($safety->status)->toBe(BackupStatus::Success)
        ->and($safety->validation_status)->toBe(BackupValidationStatus::Passed)
        ->and($safety->protected_until)->not->toBeNull()
        ->and(Storage::disk('backups')->exists((string) $safety->path))->toBeTrue();
});
