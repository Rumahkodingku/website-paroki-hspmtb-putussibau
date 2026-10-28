<?php

namespace App\Services\Backup;

use App\Exceptions\BackupException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Dumps only the configured application connection.
 *
 * The database name, host, user and password come from `config/database.php`;
 * nothing here accepts a target from a request.
 */
final class DatabaseDumper
{
    public function __construct(private readonly CommandRunner $runner) {}

    /**
     * @return array{path: string, size: int, server_version: string}
     */
    public function dump(string $outputPath, ?string $connection = null): array
    {
        $connection = $connection ?? (string) config('database.default');
        /** @var array<string, mixed> $config */
        $config = DB::connection($connection)->getConfig();
        $driver = (string) ($config['driver'] ?? '');

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw BackupException::because('format_unsupported');
        }

        $database = (string) ($config['database'] ?? '');

        if ($database === '') {
            throw BackupException::because('dump_failed');
        }

        $serverVersion = $this->serverVersion($connection);

        $directory = dirname($outputPath);

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw BackupException::because('storage_unavailable');
        }

        $command = [
            (string) config('backups.mysqldump_path'),
            '--default-character-set=utf8mb4',
            '--single-transaction',
            '--quick',
            '--skip-lock-tables',
            '--no-tablespaces',
            '--routines',
            '--triggers',
            '--hex-blob',
            '--result-file='.$outputPath,
        ];

        if (! str_contains(strtoupper($serverVersion), 'MARIADB')) {
            $command[] = '--set-gtid-purged=OFF';
        }

        $this->appendConnectionArguments($command, $config);

        foreach ((array) config('backups.excluded_tables', []) as $table) {
            if (is_string($table) && Schema::connection($connection)->hasTable($table)) {
                $command[] = '--ignore-table='.$database.'.'.$table;
            }
        }

        $command[] = $database;

        $environment = [];

        if (isset($config['password']) && $config['password'] !== '') {
            $environment['MYSQL_PWD'] = (string) $config['password'];
        }

        $result = $this->runner->run(
            $command,
            $environment,
            (int) config('backups.timeouts.dump_seconds', 3600),
        );

        $size = is_file($outputPath) ? (int) filesize($outputPath) : 0;

        if ($result['exit_code'] !== 0 || $size === 0) {
            Log::error('Database dump failed.', [
                'connection' => $connection,
                'exit_code' => $result['exit_code'],
                'reason' => 'dump_failed',
            ]);

            throw BackupException::because('dump_failed');
        }

        return [
            'path' => $outputPath,
            'size' => $size,
            'server_version' => $serverVersion,
        ];
    }

    /**
     * @param  list<string>  $command
     * @param  array<string, mixed>  $config
     */
    private function appendConnectionArguments(array &$command, array $config): void
    {
        $socket = (string) ($config['unix_socket'] ?? '');

        if ($socket !== '') {
            $command[] = '--socket='.$socket;
        } else {
            $command[] = '--host='.(string) ($config['host'] ?? '127.0.0.1');
            $command[] = '--port='.(string) ($config['port'] ?? '3306');
        }

        $command[] = '--user='.(string) ($config['username'] ?? '');
    }

    private function serverVersion(string $connection): string
    {
        try {
            $version = DB::connection($connection)->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);

            return is_string($version) ? $version : (string) $version;
        } catch (\Throwable) {
            return '';
        }
    }
}
