<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * No fabricated data. The starter kit seeded a "Test User", which is
     * invented data and therefore not allowed in this project.
     *
     * The Super Admin account is only created when ADMIN_NAME, ADMIN_EMAIL and
     * ADMIN_PASSWORD are present in the environment, so `migrate:fresh --seed`
     * works on a machine that has not been configured yet.
     *
     * @see docs/DECISIONS.md D-12, D-14, D-17
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            SiteSettingsSeeder::class,
            SuperAdminSeeder::class,
        ]);
    }
}
