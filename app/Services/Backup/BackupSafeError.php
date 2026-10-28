<?php

namespace App\Services\Backup;

use App\Exceptions\BackupException;
use Throwable;

/**
 * Converts any failure into a code and a message safe for the UI.
 *
 * Unknown exceptions deliberately lose their message: a PDO error or a
 * filesystem exception can contain an absolute path or SQL.
 */
final class BackupSafeError
{
    public function code(Throwable $exception): string
    {
        return $exception instanceof BackupException
            ? $exception->reasonCode
            : 'backup_failed';
    }

    public function message(Throwable $exception): string
    {
        return $exception instanceof BackupException
            ? $exception->safeMessage()
            : (string) __('backups.errors.backup_failed');
    }
}
