<?php

namespace Database\Seeders;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Database\Seeder;

/**
 * A single placeholder location, so a developer can see the page populated.
 *
 * This seeder is deliberately NOT called from DatabaseSeeder. That is the whole
 * point of it:
 *
 *   - D-14 emptied DatabaseSeeder of fabricated data on purpose, and AGENTS.md
 *     forbids inventing parish data. A seeder that ran on every `migrate:fresh
 *     --seed` would put invented names in a developer's database without anyone
 *     asking for them.
 *
 *   - Roadmap Phase 04 section 5.1 says no unconfirmed schedule may reach a
 *     production seeder. This one creates no schedules at all, and the location
 *     it does create says plainly that it is a placeholder.
 *
 * Run it by hand when you want to look at /jadwal-misa without going through the
 * admin form:
 *
 *     php artisan db:seed --class=MassScheduleDemoSeeder
 *
 * What it does NOT do, deliberately: seed masses, times, coordinates or notices.
 * Those are exactly the values a parish confirms with its secretariat, and a
 * plausible-looking 07.30 mass is the single easiest thing on this page to
 * invent by accident.
 *
 * @see docs/DECISIONS.md D-34
 */
class MassScheduleDemoSeeder extends Seeder
{
    public function run(): void
    {
        Location::query()->updateOrCreate(
            ['slug' => 'contoh-lokasi'],
            [
                'name' => '[ISI: nama lokasi contoh]',
                'type' => LocationType::GerejaParoki,
                'address' => null,
                'latitude' => null,
                'longitude' => null,
                'maps_url' => null,
                'notes' => 'DATA CONTOH — BUKAN DATA PAROKI. Hapus lewat /admin/lokasi.',
                'is_active' => true,
            ],
        );

        $this->command->info(
            'Dibuat satu lokasi contoh tanpa jadwal. Isi jadwal lewat /admin/jadwal-misa.'
        );
        $this->command->warn(
            'Nama, jam, dan lokasi di situs ini adalah contoh — bukan data paroki yang telah dikonfirmasi.'
        );
    }
}
