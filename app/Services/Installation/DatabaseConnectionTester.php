<?php

namespace App\Services\Installation;

use App\Enums\InstallationError;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Opens the provided database configuration once, without keeping anything.
 *
 * The probe connection is a copy of the supplied configuration under another
 * name, so the application's real connection is never left pointed at a
 * half-tested configuration. Nothing is migrated and no query is run beyond
 * opening the PDO handle.
 *
 * @see docs/DECISIONS.md D-90
 */
final class DatabaseConnectionTester
{
    /**
     * @param  array<string, mixed>  $connectionConfig  Laravel connection config (must include 'driver').
     * @return array{ok: bool, code: string|null, message: string}
     */
    public function test(array $connectionConfig): array
    {
        $driver = (string) ($connectionConfig['driver'] ?? '');

        if (! in_array($driver, (array) config('installer.database.supported_drivers', []), true)) {
            return $this->failure(InstallationError::DatabaseUnsupported);
        }

        $name = (string) config('installer.database.probe_connection');

        config(["database.connections.{$name}" => $connectionConfig]);
        DB::purge($name);

        try {
            DB::connection($name)->getPdo();

            return [
                'ok' => true,
                'code' => null,
                'message' => 'Koneksi database berhasil.',
            ];
        } catch (Throwable $exception) {
            Log::warning('installation.database_test_failed', [
                'exception' => $exception::class,
                'sql_state' => (string) $exception->getCode(),
            ]);

            return $this->failure(InstallationError::DatabaseUnreachable);
        } finally {
            DB::purge($name);
        }
    }

    /**
     * @return array{ok: bool, code: string|null, message: string}
     */
    private function failure(InstallationError $error): array
    {
        return [
            'ok' => false,
            'code' => $error->value,
            'message' => $error->message(),
        ];
    }
}
