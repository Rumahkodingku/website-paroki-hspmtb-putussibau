<?php

use App\Enums\BackupSource;
use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Enums\BackupValidationStatus;
use App\Jobs\CreateBackupJob;
use App\Models\Backup;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Storage::fake('backups');
    Storage::fake('local');
    Storage::fake('public');
    config(['backups.queue_connection' => 'sync']);
});

function runBackupJob(Backup $backup): void
{
    $job = new CreateBackupJob($backup->getKey());
    app()->call([$job, 'handle']);
}

test('it creates and validates a database backup', function (): void {
    $backup = Backup::query()->create([
        'uuid' => (string) Str::uuid(),
        'type' => BackupType::Database,
        'source' => BackupSource::Manual,
        'status' => BackupStatus::Queued,
        'validation_status' => BackupValidationStatus::Pending,
        'disk' => 'backups',
        'created_by_label' => 'Super Admin',
    ]);

    runBackupJob($backup);

    $backup->refresh();

    expect($backup->status)->toBe(BackupStatus::Success)
        ->and($backup->validation_status)->toBe(BackupValidationStatus::Passed)
        ->and($backup->path)->not->toBeNull()
        ->and($backup->checksum)->not->toBeNull()
        ->and(Storage::disk('backups')->exists((string) $backup->path))->toBeTrue();

    $local = Storage::disk('backups')->path((string) $backup->path);
    $handle = gzopen($local, 'rb');

    if ($handle === false) {
        throw new RuntimeException('Gzip fixture could not be opened.');
    }

    $manifestLine = gzgets($handle);
    $header = gzgets($handle);

    expect($manifestLine)->toStartWith('-- HSPMTB-MANIFEST:')
        ->and($header)->toStartWith('-- MySQL dump');

    gzclose($handle);
});

test('it creates a full backup with the configured file scopes', function (): void {
    Storage::disk('local')->put('media/originals/contoh.txt', 'media-asli');
    Storage::disk('public')->put('media/variants/contoh.webp', 'varian');
    Storage::disk('local')->put('documents/contoh.pdf', 'dokumen');
    Storage::disk('local')->put('media/originals/.gitkeep', '');

    $backup = Backup::query()->create([
        'uuid' => (string) Str::uuid(),
        'type' => BackupType::Full,
        'source' => BackupSource::Manual,
        'status' => BackupStatus::Queued,
        'validation_status' => BackupValidationStatus::Pending,
        'disk' => 'backups',
        'created_by_label' => 'Super Admin',
    ]);

    runBackupJob($backup);

    $backup->refresh();

    expect($backup->status)->toBe(BackupStatus::Success)
        ->and($backup->path)->not->toBeNull();

    $local = Storage::disk('backups')->path((string) $backup->path);
    $zip = new ZipArchive;
    expect($zip->open($local))->toBeTrue();

    expect($zip->locateName('manifest.json'))->not->toBeFalse()
        ->and($zip->locateName('database/database.sql'))->not->toBeFalse()
        ->and($zip->locateName('files/local/media/originals/contoh.txt'))->not->toBeFalse()
        ->and($zip->locateName('files/public/media/variants/contoh.webp'))->not->toBeFalse()
        ->and($zip->locateName('files/local/documents/contoh.pdf'))->not->toBeFalse()
        ->and($zip->locateName('files/local/media/originals/.gitkeep'))->toBeFalse();

    $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);

    expect($manifest['type'])->toBe('full')
        ->and($manifest['files']['count'])->toBe(3);

    $zip->close();
});
