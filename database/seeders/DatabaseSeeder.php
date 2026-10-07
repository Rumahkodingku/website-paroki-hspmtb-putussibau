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
     * Deliberately empty for now. The starter kit seeded a "Test User", which
     * is fabricated data and therefore not allowed in this project.
     *
     * Seeder calls are added as each one exists:
     *
     *   - P05  SuperAdminSeeder   - creates the Super Admin account from
     *                              ADMIN_NAME / ADMIN_EMAIL / ADMIN_PASSWORD
     *   - P09  SiteSettingsSeeder - seeds the canonical keys from PRD Lampiran B
     *
     * Until P05 there is deliberately no account that can log in.
     */
    public function run(): void
    {
        //
    }
}
