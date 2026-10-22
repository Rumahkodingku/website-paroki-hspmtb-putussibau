<?php

namespace Database\Seeders;

use App\Enums\DayOfWeek;
use App\Enums\LocationType;
use App\Models\Location;
use App\Models\MassSchedule;
use App\Models\ScheduleNotice;
use App\Models\SpecialMass;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoMassScheduleSeeder extends Seeder
{
    private const MASS_LOCATION_SLUG = 'gereja-paroki';

    public function run(): void
    {
        $paroki = Location::query()->updateOrCreate(
            ['slug' => self::MASS_LOCATION_SLUG],
            [
                'name' => 'Gereja Paroki Hati Santa Perawan Maria Tak Bernoda',
                'type' => LocationType::GerejaParoki,
                'address' => 'Putussibau, Kabupaten Kapuas Hulu',
                'latitude' => null,
                'longitude' => null,
                'maps_url' => null,
                'notes' => null,
                'is_active' => true,
            ],
        );

        $kapel = Location::query()->updateOrCreate(
            ['slug' => 'kapel-santo-paulus'],
            [
                'name' => 'Kapel Santo Paulus',
                'type' => LocationType::Kapel,
                'address' => null,
                'latitude' => null,
                'longitude' => null,
                'maps_url' => null,
                'notes' => null,
                'is_active' => true,
            ],
        );

        $stasi = Location::query()->updateOrCreate(
            ['slug' => 'stasi-santo-yosef'],
            [
                'name' => 'Stasi Santo Yosef',
                'type' => LocationType::Stasi,
                'address' => null,
                'latitude' => null,
                'longitude' => null,
                'maps_url' => null,
                'notes' => null,
                'is_active' => true,
            ],
        );

        /** @var list<array{0: Location, 1: DayOfWeek, 2: string, 3: string}> $schedules */
        $schedules = [
            [$paroki, DayOfWeek::Minggu, '06:30:00', 'Misa Minggu Pagi'],
            [$paroki, DayOfWeek::Minggu, '09:00:00', 'Misa Minggu Kedua'],
            [$paroki, DayOfWeek::Minggu, '17:30:00', 'Misa Minggu Sore'],
            [$paroki, DayOfWeek::Sabtu, '17:30:00', 'Misa Sabtu Sore'],
            [$paroki, DayOfWeek::Senin, '05:30:00', 'Misa Harian'],
            [$paroki, DayOfWeek::Rabu, '05:30:00', 'Misa Harian'],
            [$paroki, DayOfWeek::Jumat, '05:30:00', 'Misa Harian'],
            [$kapel, DayOfWeek::Rabu, '18:00:00', 'Misa Lingkungan'],
            [$stasi, DayOfWeek::Jumat, '18:30:00', 'Misa Stasi'],
        ];

        foreach ($schedules as [$location, $day, $time, $name]) {
            MassSchedule::query()->updateOrCreate(
                [
                    'location_id' => $location->getKey(),
                    'day_of_week' => $day,
                    'start_time' => $time,
                ],
                ['name' => $name, 'notes' => null, 'is_active' => true],
            );
        }

        $specialStart = Carbon::now((string) config('app.display_timezone'))->addDays(20)->setTime(9, 0);

        SpecialMass::query()->updateOrCreate(
            ['title' => 'Misa Syukur Pesta Pelindung Paroki'],
            [
                'location_id' => $paroki->getKey(),
                'description' => 'Misa syukur tahunan pesta pelindung paroki, diikuti ramah tamah umat.',
                'start_at' => $specialStart->utc(),
                'end_at' => $specialStart->addHours(2)->utc(),
                'notes' => null,
                'is_active' => true,
            ],
        );

        ScheduleNotice::query()->updateOrCreate(
            ['title' => 'Penyesuaian jadwal misa harian'],
            [
                'message' => 'Misa harian pekan ini dimajukan menjadi pukul 05.30 WIB.',
                'location_id' => $paroki->getKey(),
                'mass_schedule_id' => null,
                'special_mass_id' => null,
                'starts_at' => Carbon::now((string) config('app.display_timezone'))->subDay()->utc(),
                'ends_at' => Carbon::now((string) config('app.display_timezone'))->addDays(14)->utc(),
                'is_active' => true,
            ],
        );
    }
}
