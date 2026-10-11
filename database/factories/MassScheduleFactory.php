<?php

namespace Database\Factories;

use App\Enums\DayOfWeek;
use App\Models\Location;
use App\Models\MassSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MassSchedule>
 *
 * The default is a Sunday mass at 07.30. That is a *test fixture* — the value
 * the AC-MASS-2 boundary table in roadmap 9.5 is written around — and is not a
 * claim about the parish's actual timetable. Nothing seeds this into a
 * production database.
 *
 * @see docs/DECISIONS.md D-34
 */
class MassScheduleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => Location::factory(),
            'day_of_week' => DayOfWeek::Minggu,
            // 'H:i:s', never 'H:i': MySQL returns the seconds and the resolver
            // reads this string directly.
            'start_time' => '07:30:00',
            'name' => 'Misa '.fake()->randomElement(['Pagi', 'Pukul 07.30', 'I']),
            'notes' => null,
            'is_active' => true,
        ];
    }

    /**
     * A schedule the parish has switched off.
     *
     * MASS-04: it stays in the table and simply stops being published.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    /**
     * A schedule on a given day and time.
     *
     * The building block the AC-MASS-2 boundary tests are written from.
     */
    public function onDay(DayOfWeek $day, string $time = '07:30'): static
    {
        return $this->state(fn (): array => [
            'day_of_week' => $day,
            'start_time' => strlen($time) === 5 ? $time.':00' : $time,
        ]);
    }
}
