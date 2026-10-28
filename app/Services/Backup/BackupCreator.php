<?php

namespace App\Services\Backup;

use App\Enums\BackupStatus;
use App\Enums\BackupType;
use App\Enums\BackupValidationStatus;
use App\Exceptions\BackupException;
use App\Models\Backup;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

/**
 * The heavy part of creating one backup.
 *
 * It is shared by the manual/scheduled job and by the safety backup that runs
 * before a restore, so both paths produce the same format and validation.
 */
final class BackupCreator
{
    public function __construct(
        private readonly BackupStorage $storage,
        private readonly DatabaseDumper $dumper,
        private readonly BackupManifest $manifest,
        private readonly BackupArchiveInspector $inspector,
        private readonly BackupSafeError $errors,
        private readonly AuditLogger $audit,
        private readonly BackupSettingsService $settings,
        private readonly BackupPathGuard $paths,
    ) {}

    public function create(Backup $backup): void
    {
        $startedAt = now();
        $uuid = $backup->uuid;
        $this->storage->cleanup($uuid);
        $temp = $this->storage->tempPath($uuid);
        $backup->forceFill([
            'status' => BackupStatus::Running,
            'started_at' => $startedAt,
            'failure_code' => null,
            'failure_message' => null,
            'file_missing_at' => null,
        ])->save();

        try {
            $this->assertDiskSpace();

            $dumpPath = $temp.'/database.sql';
            $dump = $this->dumper->dump($dumpPath);
            $databaseChecksum = (string) hash_file('sha256', $dumpPath);
            $createdAt = $backup->created_at ?? now();

            if ($backup->type === BackupType::Database) {
                $filename = $this->storage->suggestedFilename(BackupType::Database, $createdAt);
                $manifest = $this->manifest->build(
                    BackupType::Database,
                    $backup->uuid,
                    $createdAt,
                    $backup->created_by === null ? 'system' : 'user',
                    $backup->created_by,
                    $backup->created_by_label,
                    $backup->note,
                    $databaseChecksum,
                    [],
                    $dump['server_version'],
                );

                $artifact = $temp.'/'.$filename;
                $this->writeGzip($artifact, $manifest, $dumpPath);
            } else {
                $filename = $this->storage->suggestedFilename(BackupType::Full, $createdAt);
                $files = $this->collectFiles($temp);
                $manifest = $this->manifest->build(
                    BackupType::Full,
                    $backup->uuid,
                    $createdAt,
                    $backup->created_by === null ? 'system' : 'user',
                    $backup->created_by,
                    $backup->created_by_label,
                    $backup->note,
                    $databaseChecksum,
                    $files['manifest'],
                    $dump['server_version'],
                );

                $artifact = $temp.'/'.$filename;
                $this->writeZip($artifact, $manifest, $dumpPath, $files['entries']);
            }

            $checksum = (string) hash_file('sha256', $artifact);
            $size = (int) filesize($artifact);

            $backup->forceFill([
                'status' => BackupStatus::Validating,
                'checksum' => $checksum,
                'file_size' => $size,
                'format_version' => (string) config('backups.format_version', '1'),
            ])->save();

            $result = $this->inspector->inspect(
                $backup->type,
                $artifact,
                $this->scopes(),
                $this->limits(),
            );

            if (! hash_equals($checksum, $result['artifact_checksum'])) {
                throw BackupException::because('checksum_mismatch');
            }

            $relative = $this->storage->archivePath($uuid, $filename);
            $this->storage->moveToArchive($artifact, $relative);

            if (! $this->storage->exists($relative)) {
                throw BackupException::because('storage_unavailable');
            }

            $finishedAt = now();

            $backup->forceFill([
                'path' => $relative,
                'status' => BackupStatus::Success,
                'validation_status' => BackupValidationStatus::Passed,
                'validated_at' => $finishedAt,
                'finished_at' => $finishedAt,
                'duration_ms' => (int) max(0, $finishedAt->diffInMilliseconds($startedAt)),
            ])->save();

            $this->audit->system(
                'backups',
                'backup_validated',
                'success',
                __('audit.descriptions.backup_validated'),
                [
                    'backup_id' => $backup->uuid,
                    'type' => $backup->type->value,
                    'file_size' => $backup->file_size,
                ],
                Backup::class,
                (string) $backup->getKey(),
                $this->label($backup),
            );

            $this->audit->system(
                'backups',
                'backup_completed',
                'success',
                __('audit.descriptions.backup_completed'),
                [
                    'backup_id' => $backup->uuid,
                    'type' => $backup->type->value,
                    'source' => $backup->source->value,
                    'file_size' => $backup->file_size,
                    'duration_ms' => $backup->duration_ms,
                ],
                Backup::class,
                (string) $backup->getKey(),
                $this->label($backup),
            );

            Log::info('Backup completed.', [
                'backup_id' => $backup->uuid,
                'type' => $backup->type->value,
                'file_size' => $backup->file_size,
            ]);
        } catch (Throwable $exception) {
            $finishedAt = now();

            $backup->forceFill([
                'status' => BackupStatus::Failed,
                'validation_status' => BackupValidationStatus::Failed,
                'failure_code' => $this->errors->code($exception),
                'failure_message' => $this->errors->message($exception),
                'finished_at' => $finishedAt,
                'duration_ms' => (int) max(0, $finishedAt->diffInMilliseconds($startedAt)),
            ])->save();

            $this->audit->system(
                'backups',
                'backup_failed',
                'failure',
                __('audit.descriptions.backup_failed'),
                [
                    'backup_id' => $backup->uuid,
                    'type' => $backup->type->value,
                    'source' => $backup->source->value,
                    'error_code' => $this->errors->code($exception),
                ],
                Backup::class,
                (string) $backup->getKey(),
                $this->label($backup),
            );

            Log::error('Backup failed.', [
                'backup_id' => $backup->uuid,
                'code' => $this->errors->code($exception),
                'exception' => $exception::class,
            ]);

            throw $exception;
        } finally {
            $this->storage->cleanup($uuid);
        }
    }

    /**
     * @return list<array{disk: string, path: string}>
     */
    public function scopes(): array
    {
        return [
            [
                'disk' => (string) config('media.original_disk'),
                'path' => trim((string) config('media.paths.original'), '/'),
            ],
            [
                'disk' => (string) config('media.variant_disk'),
                'path' => trim((string) config('media.paths.variants'), '/'),
            ],
            [
                'disk' => (string) config('documents.disk'),
                'path' => trim((string) config('documents.path'), '/'),
            ],
        ];
    }

    /**
     * @return array{max_expanded_bytes: int, max_files: int, max_compression_ratio: int}
     */
    public function limits(): array
    {
        return [
            'max_expanded_bytes' => $this->settings->maxExpandedBytes(),
            'max_files' => $this->settings->maxFiles(),
            'max_compression_ratio' => $this->settings->maxCompressionRatio(),
        ];
    }

    private function assertDiskSpace(): void
    {
        $free = $this->storage->freeBytes();

        if ($free === null) {
            return;
        }

        $threshold = (int) $this->settings->values()['low_disk_threshold_megabytes'] * 1024 * 1024;

        if ($free < $threshold) {
            throw BackupException::because('disk_full');
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function writeGzip(string $path, array $manifest, string $dumpPath): void
    {
        $handle = gzopen($path, 'wb9');

        if ($handle === false) {
            throw BackupException::because('storage_unavailable');
        }

        try {
            gzwrite($handle, $this->manifest->sqlComment($manifest)."\n");

            $dump = fopen($dumpPath, 'rb');

            if ($dump === false) {
                throw BackupException::because('storage_unavailable');
            }

            try {
                while (! feof($dump)) {
                    $chunk = fread($dump, 1024 * 1024);

                    if ($chunk === false) {
                        throw BackupException::because('storage_unavailable');
                    }

                    if ($chunk !== '') {
                        gzwrite($handle, $chunk);
                    }
                }
            } finally {
                fclose($dump);
            }
        } finally {
            gzclose($handle);
        }

        if (! is_file($path) || filesize($path) === 0) {
            throw BackupException::because('dump_failed');
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  list<array{entry: string, local: string}>  $entries
     */
    private function writeZip(string $path, array $manifest, string $dumpPath, array $entries): void
    {
        $zip = new ZipArchive;
        $opened = $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            throw BackupException::because('storage_unavailable');
        }

        try {
            if (! $zip->addFromString('manifest.json', $this->manifest->encode($manifest))) {
                throw BackupException::because('storage_unavailable');
            }

            if (! $zip->addFile($dumpPath, 'database/database.sql')) {
                throw BackupException::because('storage_unavailable');
            }

            foreach ($entries as $entry) {
                if (! $zip->addFile($entry['local'], $entry['entry'])) {
                    throw BackupException::because('storage_unavailable');
                }
            }
        } finally {
            if (! $zip->close()) {
                throw BackupException::because('storage_unavailable');
            }
        }

        if (! is_file($path) || filesize($path) === 0) {
            throw BackupException::because('archive_invalid');
        }
    }

    /**
     * @return array{
     *     manifest: list<array{disk: string, path: string, size: int, sha256: string}>,
     *     entries: list<array{entry: string, local: string}>
     * }
     */
    private function collectFiles(string $temp): array
    {
        $manifest = [];
        $entries = [];
        $seen = [];
        $index = 0;

        foreach ($this->scopes() as $scope) {
            if ($scope['path'] === '') {
                continue;
            }

            $adapter = Storage::disk($scope['disk']);

            foreach ($adapter->allFiles($scope['path']) as $relative) {
                $relative = trim((string) $relative, '/');

                if ($relative === '' || str_starts_with(basename($relative), '.')) {
                    continue;
                }

                $this->paths->assertSafeRelative($relative);

                try {
                    $absolute = $adapter->path($relative);
                } catch (Throwable) {
                    $absolute = null;
                }

                if ($absolute !== null && is_link($absolute)) {
                    continue;
                }

                $entry = 'files/'.$scope['disk'].'/'.$relative;

                if (isset($seen[$entry])) {
                    continue;
                }

                $seen[$entry] = true;

                $stream = $adapter->readStream($relative);

                if (! is_resource($stream)) {
                    throw BackupException::because('storage_unavailable');
                }

                $local = $temp.'/file-'.(++$index);

                try {
                    $target = fopen($local, 'wb');

                    if ($target === false) {
                        throw BackupException::because('storage_unavailable');
                    }

                    try {
                        stream_copy_to_stream($stream, $target);
                    } finally {
                        fclose($target);
                    }
                } finally {
                    fclose($stream);
                }

                $size = (int) filesize($local);

                $manifest[] = [
                    'disk' => $scope['disk'],
                    'path' => $relative,
                    'size' => $size,
                    'sha256' => (string) hash_file('sha256', $local),
                ];

                $entries[] = [
                    'entry' => $entry,
                    'local' => $local,
                ];
            }
        }

        return ['manifest' => $manifest, 'entries' => $entries];
    }

    private function label(Backup $backup): string
    {
        return $backup->type->label().' '.$backup->created_at->utc()->format('Y-m-d H:i:s');
    }
}
