<?php

namespace App\Jobs;

use App\Actions\Backup\ValidateBackupAction;
use App\Enums\BackupComponentResult;
use App\Enums\BackupRestoreStatus;
use App\Enums\BackupType;
use App\Exceptions\BackupException;
use App\Models\Backup;
use App\Models\BackupRestore;
use App\Services\AuditLogger;
use App\Services\Backup\BackupCreator;
use App\Services\Backup\BackupHealth;
use App\Services\Backup\BackupPathGuard;
use App\Services\Backup\BackupStorage;
use App\Services\Backup\DatabaseReconnector;
use App\Services\Backup\DatabaseRestorer;
use App\Services\SiteSettingsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Throwable;
use ZipArchive;

/**
 * Restores a validated archive through the two-stage flow.
 *
 * The database is restored first, then files for a full backup. A database
 * failure is `failed`; a database success with a file failure is `partial`.
 * The maintenance flag and the lock are released in `finally`.
 */
class RestoreBackupJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 7200;

    public bool $failOnTimeout = true;

    public function __construct(public readonly int $restoreId) {}

    public function handle(
        BackupStorage $storage,
        ValidateBackupAction $validator,
        DatabaseRestorer $restorer,
        DatabaseReconnector $reconnector,
        BackupPathGuard $paths,
        AuditLogger $audit,
        BackupHealth $health,
        BackupCreator $creator,
    ): void {
        $health->markWorkerHeartbeat();

        $restore = BackupRestore::query()->find($this->restoreId);

        if ($restore === null) {
            return;
        }

        $backup = Backup::query()->find($restore->backup_id);

        if ($backup === null) {
            $this->finish($restore, null, BackupComponentResult::Failed, BackupComponentResult::Skipped, [], __('backups.errors.file_missing'), 'file_missing', $audit);

            return;
        }

        $lock = Cache::lock('backups:exclusive', (int) config('backups.timeouts.lock_seconds', 7200));

        if (! $lock->get()) {
            $this->finish($restore, $backup, BackupComponentResult::Failed, BackupComponentResult::Skipped, [], __('backups.errors.operation_in_progress'), 'operation_in_progress', $audit);

            return;
        }

        $storage->cleanup('restore-'.$restore->uuid);
        $temp = $storage->tempPath('restore-'.$restore->uuid);

        $restore->forceFill([
            'status' => BackupRestoreStatus::Running,
            'started_at' => now(),
            'failure_code' => null,
            'failure_message' => null,
        ])->save();

        $databaseResult = BackupComponentResult::Failed;
        $filesResult = BackupComponentResult::Skipped;
        $verification = [];
        $failureCode = 'db_restore_failed';
        $failureMessage = (string) __('backups.errors.db_restore_failed');

        try {
            $artifact = $temp.'/artifact.'.$this->extension($backup->type);
            $storage->localCopy((string) $backup->path, $artifact);

            $inspection = $validator->inspectLocal($backup, $artifact);

            if ($backup->checksum !== null
                && ! hash_equals($backup->checksum, $inspection['artifact_checksum'])) {
                throw BackupException::because('checksum_mismatch');
            }

            $sqlPath = $temp.'/database.sql';

            if ($backup->type === BackupType::Database) {
                $this->decompressSql($artifact, $sqlPath);
            } else {
                $this->extractDatabase($artifact, $sqlPath);
            }

            if (! hash_equals($inspection['database_checksum'], (string) hash_file('sha256', $sqlPath))) {
                throw BackupException::because('checksum_mismatch');
            }

            Cache::put(BackupHealth::RESTORE_FLAG, true, 7200);
            Log::info('backup.restore.starting', [
                'backup_id' => $backup->uuid,
                'operation_id' => $restore->uuid,
                'actor_id' => $restore->requested_by,
            ]);

            $restorer->restore($sqlPath);
            $databaseResult = BackupComponentResult::Success;

            if ($backup->type === BackupType::Full) {
                $filesResult = $this->restoreFiles($artifact, $inspection['manifest'], $paths, $creator, $temp);
            }

            $verification = $this->verify($creator, $reconnector);

            $failedVerification = in_array(false, $verification, true);

            if ($failedVerification) {
                $failureCode = 'verification_failed';
                $failureMessage = (string) __('backups.errors.verification_failed');
            } elseif ($filesResult === BackupComponentResult::Failed) {
                $failureCode = 'file_restore_failed';
                $failureMessage = (string) __('backups.errors.file_restore_failed');
            } else {
                $failureCode = null;
                $failureMessage = null;
            }

            $this->forgetCaches();

            $status = match (true) {
                $failedVerification => BackupRestoreStatus::Failed,
                $filesResult === BackupComponentResult::Failed => BackupRestoreStatus::Partial,
                default => BackupRestoreStatus::Success,
            };

            $this->finish($restore, $backup, $databaseResult, $filesResult, $verification, $failureMessage, $failureCode, $audit, $status);

            Log::info('backup.restore.finished', [
                'backup_id' => $backup->uuid,
                'operation_id' => $restore->uuid,
                'status' => $status->value,
            ]);
        } catch (Throwable $exception) {
            $code = $databaseResult === BackupComponentResult::Success
                ? 'file_restore_failed'
                : 'db_restore_failed';

            $safe = $exception instanceof BackupException
                ? $exception
                : BackupException::because($code, $exception);

            $this->finish($restore, $backup, $databaseResult, $filesResult, $verification, $safe->safeMessage(), $safe->reasonCode, $audit, BackupRestoreStatus::Failed);

            Log::error('backup.restore.failed', [
                'backup_id' => $backup->uuid,
                'operation_id' => $restore->uuid,
                'code' => $safe->reasonCode,
                'exception' => $exception::class,
            ]);

            throw $safe;
        } finally {
            Cache::forget(BackupHealth::RESTORE_FLAG);
            $lock->release();
            $storage->cleanup('restore-'.$restore->uuid);
        }
    }

    private function extension(BackupType $type): string
    {
        return $type === BackupType::Database ? 'sql.gz' : 'zip';
    }

    private function decompressSql(string $artifact, string $target): void
    {
        $handle = gzopen($artifact, 'rb');

        if ($handle === false) {
            throw BackupException::because('archive_invalid');
        }

        $output = fopen($target, 'wb');

        if ($output === false) {
            gzclose($handle);
            throw BackupException::because('storage_unavailable');
        }

        try {
            gzgets($handle);

            while (! gzeof($handle)) {
                $chunk = gzread($handle, 1024 * 1024);

                if ($chunk === false) {
                    throw BackupException::because('archive_invalid');
                }

                if ($chunk !== '') {
                    fwrite($output, $chunk);
                }
            }
        } finally {
            fclose($output);
            gzclose($handle);
        }
    }

    private function extractDatabase(string $artifact, string $target): void
    {
        $zip = new ZipArchive;
        $opened = $zip->open($artifact, ZipArchive::RDONLY);

        if ($opened !== true) {
            throw BackupException::because('archive_invalid');
        }

        try {
            $stream = $zip->getStream('database/database.sql');

            if (! is_resource($stream)) {
                throw BackupException::because('archive_invalid');
            }

            $output = fopen($target, 'wb');

            if ($output === false) {
                fclose($stream);
                throw BackupException::because('storage_unavailable');
            }

            try {
                stream_copy_to_stream($stream, $output);
            } finally {
                fclose($stream);
                fclose($output);
            }
        } finally {
            $zip->close();
        }
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function restoreFiles(
        string $artifact,
        array $manifest,
        BackupPathGuard $paths,
        BackupCreator $creator,
        string $temp,
    ): BackupComponentResult {
        $zip = new ZipArchive;
        $opened = $zip->open($artifact, ZipArchive::RDONLY);

        if ($opened !== true) {
            throw BackupException::because('archive_invalid');
        }

        $failed = 0;
        $included = $manifest['files']['included'] ?? [];

        if (! is_array($included) || $included === []) {
            $zip->close();

            return BackupComponentResult::Skipped;
        }

        try {
            foreach ($included as $index => $file) {
                if (! is_array($file)) {
                    $failed++;

                    continue;
                }

                $disk = (string) ($file['disk'] ?? '');
                $relative = (string) ($file['path'] ?? '');
                try {
                    $relative = $paths->assertAllowedScope($disk, $relative, $creator->scopes());

                    $stream = $zip->getStream('files/'.$disk.'/'.$relative);

                    if (! is_resource($stream)) {
                        throw BackupException::because('archive_invalid');
                    }

                    $staged = $temp.'/staged-'.$index;
                    $output = fopen($staged, 'wb');

                    if ($output === false) {
                        fclose($stream);
                        throw BackupException::because('storage_unavailable');
                    }

                    try {
                        stream_copy_to_stream($stream, $output);
                    } finally {
                        fclose($stream);
                        fclose($output);
                    }

                    if (! hash_equals((string) ($file['sha256'] ?? ''), (string) hash_file('sha256', $staged))) {
                        throw BackupException::because('checksum_mismatch');
                    }

                    $target = Storage::disk($disk);
                    $source = fopen($staged, 'rb');

                    if ($source === false || ! $target->put($relative, $source)) {
                        if (is_resource($source)) {
                            fclose($source);
                        }

                        throw BackupException::because('storage_unavailable');
                    }

                    fclose($source);
                } catch (Throwable $exception) {
                    Log::warning('backup.restore.file_failed', [
                        'index' => $index,
                        'code' => $exception instanceof BackupException ? $exception->reasonCode : 'file_restore_failed',
                    ]);

                    $failed++;
                }
            }
        } finally {
            $zip->close();
        }

        return $failed === 0
            ? BackupComponentResult::Success
            : BackupComponentResult::Failed;
    }

    /**
     * @return array<string, bool>
     */
    private function verify(BackupCreator $creator, DatabaseReconnector $reconnector): array
    {
        $checks = [
            'database_connection' => false,
            'core_tables' => false,
            'settings_readable' => false,
            'file_roots' => false,
        ];

        try {
            $reconnector->refresh();
            DB::select('select 1');
            $checks['database_connection'] = true;
        } catch (Throwable) {
            return $checks;
        }

        $tables = ['users', 'roles', 'permissions', 'site_settings', 'media', 'posts', 'documents', 'migrations'];
        $checks['core_tables'] = true;

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $checks['core_tables'] = false;
                break;
            }
        }

        try {
            app(SiteSettingsService::class)->get('parish_name');
            $checks['settings_readable'] = true;
        } catch (Throwable) {
            $checks['settings_readable'] = false;
        }

        $checks['file_roots'] = true;

        foreach ($creator->scopes() as $scope) {
            if ($scope['path'] === '') {
                continue;
            }

            try {
                Storage::disk($scope['disk'])->allFiles($scope['path']);
            } catch (Throwable) {
                $checks['file_roots'] = false;
                break;
            }
        }

        return $checks;
    }

    private function forgetCaches(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        app(SiteSettingsService::class)->forget();
    }

    /**
     * @param  array<string, bool>  $verification
     */
    private function finish(
        BackupRestore $restore,
        ?Backup $backup,
        BackupComponentResult $databaseResult,
        BackupComponentResult $filesResult,
        array $verification,
        ?string $failureMessage,
        ?string $failureCode,
        AuditLogger $audit,
        ?BackupRestoreStatus $status = null,
    ): void {
        $status ??= BackupRestoreStatus::Failed;
        $finishedAt = now();

        $restore->forceFill([
            'status' => $status,
            'database_result' => $databaseResult,
            'files_result' => $filesResult,
            'verification' => $verification,
            'failure_code' => $failureCode,
            'failure_message' => $failureMessage,
            'finished_at' => $finishedAt,
            'duration_ms' => $restore->started_at === null
                ? null
                : (int) max(0, $finishedAt->diffInMilliseconds($restore->started_at)),
        ])->save();

        if ($backup === null) {
            return;
        }

        $action = match ($status) {
            BackupRestoreStatus::Success => 'restore_completed',
            BackupRestoreStatus::Partial => 'restore_partial',
            default => 'restore_failed',
        };

        $result = $status === BackupRestoreStatus::Success ? 'success' : 'failure';

        $audit->system(
            'backups',
            $action,
            $result,
            (string) __('audit.descriptions.'.$action),
            [
                'backup_id' => $backup->uuid,
                'operation_id' => $restore->uuid,
                'safety_backup_id' => $restore->safety_backup_id,
                'requested_by' => $restore->requested_by,
                'error_code' => $failureCode,
            ],
            Backup::class,
            (string) $backup->getKey(),
            $backup->type->label(),
        );
    }
}
