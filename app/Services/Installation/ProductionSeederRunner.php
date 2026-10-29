<?php

namespace App\Services\Installation;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;

/**
 * Runs only the seeders an installation is allowed to run.
 *
 * The list comes from config/installer.php; nothing here may reach for
 * DatabaseSeeder, the demo content, ServiceSeeder, SiteSettingsSeeder or the
 * environment-driven SuperAdminSeeder.
 *
 * @see docs/DECISIONS.md D-90
 */
final class ProductionSeederRunner
{
    public function runPermissions(): void
    {
        $this->runGroup('permissions');
    }

    public function runCategories(): void
    {
        $this->runGroup('categories');
    }

    private function runGroup(string $group): void
    {
        $seeders = (array) config("installer.seeders.{$group}", []);

        foreach ($seeders as $seeder) {
            if (! is_string($seeder) || ! class_exists($seeder)) {
                throw new RuntimeException("Seeder instalasi tidak dikenali pada grup {$group}.");
            }

            $exitCode = Artisan::call('db:seed', [
                '--class' => $seeder,
                '--force' => true,
            ]);

            if ($exitCode !== 0) {
                throw new RuntimeException("Seeder {$seeder} gagal dijalankan.");
            }
        }
    }
}
