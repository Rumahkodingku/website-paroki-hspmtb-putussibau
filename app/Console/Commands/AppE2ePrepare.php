<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds the database the Playwright suite runs against and hands the
 * resulting credentials to the specs.
 *
 * migrate:fresh drops every table it finds, so this command never takes its
 * target from the environment. It reads config('e2e.database') and repoints the
 * connection at it first, which means `npm run e2e` behaves identically whether
 * .env points at a development database or at nothing at all, and the database
 * named in .env is never the one that gets dropped. Only E2E_DATABASE can move
 * the target, so the destructive step follows the suite's own configuration
 * instead of a working directory someone happened to be in.
 *
 * Migrating is additionally blocked outright in production by
 * DB::prohibitDestructiveCommands() in AppServiceProvider.
 *
 * @see docs/DECISIONS.md D-29
 */
#[Signature('app:e2e:prepare')]
#[Description('Rebuild the E2E test database and write the credentials Playwright needs')]
class AppE2ePrepare extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $target = config('e2e.database');

        if (! is_string($target) || blank($target)) {
            $this->components->error(
                'e2e.database is not configured, so there is no safe database to rebuild.',
            );

            return self::FAILURE;
        }

        $connection = config('database.default');
        $before = config("database.connections.{$connection}.database");

        // The whole safety argument: the connection is moved to the suite's own
        // database before anything destructive runs.
        config(["database.connections.{$connection}.database" => $target]);

        // A pooled connection would otherwise keep using the database it opened
        // before the config changed.
        DB::purge($connection);

        $this->components->info("Rebuilding \"{$target}\".");

        // Seeded before migrating so that DatabaseSeeder's SuperAdminSeeder
        // finds the credentials in config rather than skipping, which is the
        // same path a fresh installation takes (see D-17).
        config([
            'admin.name' => config('e2e.admin.name'),
            'admin.email' => config('e2e.admin.email'),
            'admin.password' => config('e2e.admin.password'),
        ]);

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $this->writeAuthFile();

        $this->components->info(sprintf(
            'Seeded "%s" and wrote %s.',
            config('e2e.admin.email'),
            $this->relative(config('e2e.auth_file')),
        ));

        if ($before !== $target) {
            $this->components->twoColumnDetail('Ignored .env database', (string) $before);
        }

        return self::SUCCESS;
    }

    /**
     * Publish the seeded credentials and database for Playwright to read.
     *
     * The database has to travel in the same file. The specs run against a
     * `php artisan serve` that reads its connection from the environment, and
     * that environment has to point at the database this command just rebuilt.
     * Handing Playwright the name is what keeps the two in step; if the server
     * were left on the database named in .env, every login would silently fail
     * against an account that does not exist there.
     */
    private function writeAuthFile(): void
    {
        file_put_contents(
            config('e2e.auth_file'),
            json_encode([
                'email' => config('e2e.admin.email'),
                'password' => config('e2e.admin.password'),
                'database' => config('e2e.database'),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );
    }

    /**
     * Render an absolute path relative to the project root for display.
     */
    private function relative(string $path): string
    {
        return str_starts_with($path, base_path())
            ? ltrim(substr($path, strlen(base_path())), '/')
            : $path;
    }
}
