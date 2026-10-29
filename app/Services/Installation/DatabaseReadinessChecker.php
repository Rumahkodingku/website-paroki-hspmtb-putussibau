<?php

namespace App\Services\Installation;

use App\Enums\InstallationError;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Decides whether a database may be installed into.
 *
 * The rule is deliberately strict: zero tables or nothing. A database with
 * tables is not "probably empty" because the status table is missing; it is
 * somebody's data until an operator says otherwise. Adding tables to it is
 * never something a web request may decide on its own.
 *
 * @see docs/DECISIONS.md D-93
 */
class DatabaseReadinessChecker
{
    /**
     * @param  array<string, mixed>|null  $connectionConfig
     */
    public function __construct(private ?array $connectionConfig = null) {}

    /**
     * @param  array<string, mixed>|null  $connectionConfig
     * @return array{ok: bool, code: string|null, message: string}
     */
    public function check(?array $connectionConfig = null): array
    {
        $config = $connectionConfig ?? $this->connectionConfig;

        $name = $config !== null
            ? $this->configureProbeWithConfig($config)
            : $this->configureProbe();

        try {
            $schema = Schema::connection($name);

            $database = $schema->getConnection()->getDatabaseName();

            if ($database === '') {
                return $this->result(
                    false,
                    InstallationError::DatabaseUnreachable,
                    InstallationError::DatabaseUnreachable->message(),
                );
            }

            $tables = $schema->getTableListing($database);
        } catch (Throwable $exception) {
            Log::warning('installation.database_readiness_failed', [
                'exception' => $exception::class,
                'sql_state' => (string) $exception->getCode(),
            ]);

            return $this->result(
                false,
                InstallationError::DatabaseUnreachable,
                InstallationError::DatabaseUnreachable->message(),
            );
        }

        $count = count($tables);

        if ($count === 0) {
            return $this->result(true, null, 'Database kosong dan siap digunakan.');
        }

        return $this->result(
            false,
            InstallationError::DatabaseNotFresh,
            sprintf(
                'Database target sudah berisi %d tabel. Gunakan database baru atau lakukan pemeriksaan manual.',
                $count,
            ),
        );
    }

    /**
     * Point the probe connection at the provided configuration.
     */
    /**
     * @param  array<string, mixed>  $config
     */
    private function configureProbeWithConfig(array $config): string
    {
        $name = (string) config('installer.database.probe_connection');

        config(["database.connections.{$name}" => $config]);
        DB::purge($name);

        return $name;
    }

    /**
     * Point the probe connection at the deployment's configured database.
     */
    private function configureProbe(): string
    {
        $name = (string) config('installer.database.probe_connection');
        $driver = (string) config('database.default');

        config(["database.connections.{$name}" => (array) config("database.connections.{$driver}", [])]);
        DB::purge($name);

        return $name;
    }

    /**
     * @return array{ok: bool, code: string|null, message: string}
     */
    private function result(bool $ok, ?InstallationError $error, string $message): array
    {
        return [
            'ok' => $ok,
            'code' => $error?->value,
            'message' => $message,
        ];
    }
}
