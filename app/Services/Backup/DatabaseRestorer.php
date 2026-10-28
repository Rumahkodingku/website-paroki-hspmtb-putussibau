<?php

namespace App\Services\Backup;

use App\Exceptions\BackupException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Feeds an already-validated SQL file into `mysql`.
 *
 * The target is always the configured application connection. The file is
 * passed as stdin so the dump never has to fit in memory.
 */
class DatabaseRestorer
{
    public function __construct(private readonly CommandRunner $runner) {}

    public function restore(string $sqlPath, ?string $connection = null): void
    {
        $connection = $connection ?? (string) config('database.default');
        /** @var array<string, mixed> $config */
        $config = DB::connection($connection)->getConfig();
        $driver = (string) ($config['driver'] ?? '');

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw BackupException::because('format_unsupported');
        }

        $database = (string) ($config['database'] ?? '');

        if ($database === '' || ! is_file($sqlPath)) {
            throw BackupException::because('db_restore_failed');
        }

        $command = [
            (string) config('backups.mysql_path'),
            '--default-character-set=utf8mb4',
            '--binary-mode',
        ];

        $socket = (string) ($config['unix_socket'] ?? '');

        if ($socket !== '') {
            $command[] = '--socket='.$socket;
        } else {
            $command[] = '--host='.(string) ($config['host'] ?? '127.0.0.1');
            $command[] = '--port='.(string) ($config['port'] ?? '3306');
        }

        $command[] = '--user='.(string) ($config['username'] ?? '');
        $command[] = $database;

        $environment = [];

        if (isset($config['password']) && $config['password'] !== '') {
            $environment['MYSQL_PWD'] = (string) $config['password'];
        }

        $result = $this->runner->run(
            $command,
            $environment,
            (int) config('backups.timeouts.restore_seconds', 7200),
            $sqlPath,
        );

        if ($result['exit_code'] !== 0) {
            Log::error('Database restore failed.', [
                'connection' => $connection,
                'exit_code' => $result['exit_code'],
                'reason' => 'db_restore_failed',
            ]);

            throw BackupException::because('db_restore_failed');
        }
    }
}
