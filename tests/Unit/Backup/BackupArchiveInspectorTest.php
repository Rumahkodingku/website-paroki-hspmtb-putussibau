<?php

use App\Enums\BackupType;
use App\Exceptions\BackupException;
use App\Services\Backup\BackupArchiveInspector;
use App\Services\Backup\BackupManifest;
use App\Services\Backup\BackupPathGuard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->directory = sys_get_temp_dir().'/hspmtb-backup-'.Str::uuid();
    File::ensureDirectoryExists($this->directory);
    $this->manifest = new BackupManifest;
    $this->inspector = new BackupArchiveInspector(
        $this->manifest,
        new BackupPathGuard,
    );
    $this->limits = [
        'max_expanded_bytes' => 1024 * 1024,
        'max_files' => 100,
        'max_compression_ratio' => 100,
    ];
});

afterEach(function (): void {
    File::deleteDirectory($this->directory);
});

test('it validates a database gzip with an embedded manifest', function (): void {
    $dump = "-- MySQL dump 10.13\nCREATE TABLE example (id int);\n-- Dump completed on 2026-10-28\n";
    $path = $this->directory.'/backup.sql.gz';
    $manifest = $this->manifest->build(
        BackupType::Database,
        '0199c1f4-7e2a-7f3b-9a11-4b4f2f3c1a2b',
        Carbon::now(),
        'system',
        null,
        'Penjadwal',
        null,
        hash('sha256', $dump),
    );

    $handle = gzopen($path, 'wb9');

    if ($handle === false) {
        throw new RuntimeException('Gzip fixture could not be created.');
    }

    gzwrite($handle, $this->manifest->sqlComment($manifest)."\n".$dump);
    gzclose($handle);

    $result = $this->inspector->inspect(BackupType::Database, $path, [], $this->limits);

    expect($result['manifest']['type'])->toBe('database')
        ->and($result['database_checksum'])->toBe(hash('sha256', $dump))
        ->and($result['artifact_checksum'])->toBe(hash_file('sha256', $path));
});

test('it rejects a database gzip whose checksum does not match', function (): void {
    $dump = "-- MySQL dump 10.13\nSELECT 1;\n-- Dump completed on 2026-10-28\n";
    $path = $this->directory.'/backup.sql.gz';
    $manifest = $this->manifest->build(
        BackupType::Database,
        'id',
        Carbon::now(),
        'system',
        null,
        null,
        null,
        str_repeat('0', 64),
    );

    $handle = gzopen($path, 'wb9');

    if ($handle === false) {
        throw new RuntimeException('Gzip fixture could not be created.');
    }

    gzwrite($handle, $this->manifest->sqlComment($manifest)."\n".$dump);
    gzclose($handle);

    expect(fn () => $this->inspector->inspect(BackupType::Database, $path, [], $this->limits))
        ->toThrow(BackupException::class);
});

test('it validates a full zip and rejects an extra entry', function (): void {
    $database = "CREATE TABLE example (id int);\n";
    $file = 'contoh';
    $relative = 'media/originals/contoh.txt';
    $manifest = $this->manifest->build(
        BackupType::Full,
        '0199c1f4-7e2a-7f3b-9a11-4b4f2f3c1a2b',
        Carbon::now(),
        'system',
        null,
        null,
        null,
        hash('sha256', $database),
        [[
            'disk' => 'local',
            'path' => $relative,
            'size' => strlen($file),
            'sha256' => hash('sha256', $file),
        ]],
    );

    $path = $this->directory.'/backup.zip';
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('manifest.json', $this->manifest->encode($manifest));
    $zip->addFromString('database/database.sql', $database);
    $zip->addFromString('files/local/'.$relative, $file);
    $zip->close();

    $scopes = [['disk' => 'local', 'path' => 'media/originals']];
    $result = $this->inspector->inspect(BackupType::Full, $path, $scopes, $this->limits);

    expect($result['file_count'])->toBe(1)
        ->and($result['database_checksum'])->toBe(hash('sha256', $database));

    $badPath = $this->directory.'/bad.zip';
    $bad = new ZipArchive;
    $bad->open($badPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $bad->addFromString('manifest.json', $this->manifest->encode($manifest));
    $bad->addFromString('database/database.sql', $database);
    $bad->addFromString('files/local/'.$relative, $file);
    $bad->addFromString('files/local/../evil.txt', 'x');
    $bad->close();

    expect(fn () => $this->inspector->inspect(BackupType::Full, $badPath, $scopes, $this->limits))
        ->toThrow(BackupException::class);
});
