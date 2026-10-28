<?php

use App\Enums\BackupRestoreStatus;
use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Enums\BackupValidationStatus;
use App\Jobs\RestoreBackupJob;
use App\Models\Backup;
use App\Models\BackupRestore;
use App\Services\Backup\BackupHealth;
use App\Services\Backup\BackupManifest;
use App\Services\Backup\DatabaseReconnector;
use App\Services\Backup\DatabaseRestorer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Storage::fake('backups');
    Storage::fake('local');
    Storage::fake('public');
});

/**
 * @return array{0: Backup, 1: string}
 */
function makeRestoreDatabaseArtifact(): array
{
    $dump = "-- MySQL dump 10.13\nCREATE TABLE contoh (id int);\n-- Dump completed on 2026-10-28\n";
    $uuid = (string) Str::uuid();
    $manifest = app(BackupManifest::class)->build(
        BackupType::Database,
        $uuid,
        Carbon::now(),
        'system',
        null,
        'Penguji',
        null,
        hash('sha256', $dump),
    );
    $relative = 'archives/'.$uuid.'/backup.sql.gz';
    $path = Storage::disk('backups')->path($relative);
    @mkdir(dirname($path), 0755, true);
    $handle = gzopen($path, 'wb9');

    if ($handle === false) {
        throw new RuntimeException('Gzip fixture could not be created.');
    }

    gzwrite($handle, app(BackupManifest::class)->sqlComment($manifest)."\n".$dump);
    gzclose($handle);

    $backup = Backup::factory()->create([
        'type' => BackupType::Database,
        'status' => BackupStatus::Success,
        'validation_status' => BackupValidationStatus::Passed,
        'disk' => 'backups',
        'path' => $relative,
        'checksum' => hash_file('sha256', $path),
        'file_size' => filesize($path),
    ]);

    return [$backup, $path];
}

test('a validated restore runs through the job and clears maintenance state', function (): void {
    [$backup] = makeRestoreDatabaseArtifact();
    $restore = BackupRestore::factory()->create([
        'backup_id' => $backup->getKey(),
        'status' => BackupRestoreStatus::Ready,
        'expires_at' => now()->addHour(),
    ]);

    $this->mock(DatabaseRestorer::class)
        ->shouldReceive('restore')
        ->once();

    $this->mock(DatabaseReconnector::class)
        ->shouldReceive('refresh')
        ->once();

    app()->call([new RestoreBackupJob($restore->getKey()), 'handle']);

    $restore->refresh();

    expect($restore->status)->toBe(BackupRestoreStatus::Success)
        ->and($restore->database_result->value)->toBe('success')
        ->and($restore->files_result->value)->toBe('skipped')
        ->and(Cache::get(BackupHealth::RESTORE_FLAG))->toBeNull();
});

test('a concurrent restore is refused', function (): void {
    [$backup] = makeRestoreDatabaseArtifact();
    $restore = BackupRestore::factory()->create([
        'backup_id' => $backup->getKey(),
        'status' => BackupRestoreStatus::Ready,
        'expires_at' => now()->addHour(),
    ]);

    $lock = Cache::lock('backups:exclusive', 7200);
    expect($lock->get())->toBeTrue();

    try {
        app()->call([new RestoreBackupJob($restore->getKey()), 'handle']);

        $restore->refresh();

        expect($restore->status)->toBe(BackupRestoreStatus::Failed)
            ->and($restore->failure_code)->toBe('operation_in_progress');
    } finally {
        $lock->release();
    }
});

test('a full restore replaces files present in the archive and preserves others', function (): void {
    Storage::disk('local')->put('media/originals/existing.txt', 'lama');

    $database = "CREATE TABLE contoh (id int);\n";
    $file = 'baru';
    $relative = 'media/originals/new.txt';
    $uuid = (string) Str::uuid();
    $manifest = app(BackupManifest::class)->build(
        BackupType::Full,
        $uuid,
        Carbon::now(),
        'system',
        null,
        'Penguji',
        null,
        hash('sha256', $database),
        [[
            'disk' => 'local',
            'path' => $relative,
            'size' => strlen($file),
            'sha256' => hash('sha256', $file),
        ]],
    );

    $archive = 'archives/'.$uuid.'/backup.zip';
    $path = Storage::disk('backups')->path($archive);
    @mkdir(dirname($path), 0755, true);
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('manifest.json', app(BackupManifest::class)->encode($manifest));
    $zip->addFromString('database/database.sql', $database);
    $zip->addFromString('files/local/'.$relative, $file);
    $zip->close();

    $backup = Backup::factory()->full()->create([
        'status' => BackupStatus::Success,
        'validation_status' => BackupValidationStatus::Passed,
        'disk' => 'backups',
        'path' => $archive,
        'checksum' => hash_file('sha256', $path),
        'file_size' => filesize($path),
    ]);

    $restore = BackupRestore::factory()->create([
        'backup_id' => $backup->getKey(),
        'status' => BackupRestoreStatus::Ready,
        'expires_at' => now()->addHour(),
    ]);

    $this->mock(DatabaseRestorer::class)
        ->shouldReceive('restore')
        ->once();

    $this->mock(DatabaseReconnector::class)
        ->shouldReceive('refresh')
        ->once();

    app()->call([new RestoreBackupJob($restore->getKey()), 'handle']);

    $restore->refresh();

    expect($restore->status)->toBe(BackupRestoreStatus::Success)
        ->and($restore->files_result->value)->toBe('success')
        ->and(Storage::disk('local')->get($relative))->toBe('baru')
        ->and(Storage::disk('local')->get('media/originals/existing.txt'))->toBe('lama');
});
