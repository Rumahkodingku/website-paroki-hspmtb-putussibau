<?php

namespace App\Actions\Backup;

use App\Enums\BackupStatus;
use App\Enums\BackupValidationStatus;
use App\Exceptions\BackupException;
use App\Models\Backup;
use App\Services\Backup\BackupArchiveInspector;
use App\Services\Backup\BackupCreator;
use Illuminate\Support\Facades\File;

/**
 * Validates an already-stored artifact and records the result.
 *
 * Upload uses this after the file is in quarantine. Restore re-runs the same
 * inspection immediately before destructive work starts.
 */
final class ValidateBackupAction
{
    public function __construct(
        private readonly BackupArchiveInspector $inspector,
        private readonly BackupCreator $creator,
    ) {}

    /**
     * @return array{
     *     manifest: array<string, mixed>,
     *     artifact_checksum: string,
     *     database_checksum: string,
     *     file_count: int,
     *     expanded_bytes: int
     * }
     */
    public function inspectLocal(Backup $backup, string $absolutePath): array
    {
        return $this->inspector->inspect(
            $backup->type,
            $absolutePath,
            $this->creator->scopes(),
            $this->creator->limits(),
        );
    }

    public function markPassed(Backup $backup, string $checksum, int $size, string $formatVersion): void
    {
        $backup->forceFill([
            'status' => BackupStatus::Success,
            'validation_status' => BackupValidationStatus::Passed,
            'checksum' => $checksum,
            'file_size' => $size,
            'format_version' => $formatVersion,
            'validated_at' => now(),
            'finished_at' => now(),
            'failure_code' => null,
            'failure_message' => null,
        ])->save();
    }

    public function markFailed(Backup $backup, BackupException $exception): void
    {
        $backup->forceFill([
            'status' => BackupStatus::Failed,
            'validation_status' => BackupValidationStatus::Failed,
            'path' => null,
            'failure_code' => $exception->reasonCode,
            'failure_message' => $exception->safeMessage(),
            'finished_at' => now(),
        ])->save();
    }

    public function removeLocal(string $path): void
    {
        if (is_file($path)) {
            File::delete($path);
        }
    }
}
