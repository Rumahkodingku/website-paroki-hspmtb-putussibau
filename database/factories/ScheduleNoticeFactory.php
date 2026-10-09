<?php

namespace Database\Factories;

use App\Models\MassSchedule;
use App\Models\ScheduleNotice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScheduleNotice>
 *
 * The default is a notice that is active and inside its display window right
 * now, which is the case the public banner actually shows. The other states
 * exist so MASS-05's three rejections are each one call away.
 *
 * @see docs/DECISIONS.md D-34
 */
class ScheduleNoticeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Perubahan Jadwal Misa',
            'message' => 'Jadwal misa minggu ini disesuaikan. '.fake()->sentence(),
            // No target: applies to everyone, which is roadmap 4.4's "semua
            // foreign key nullable dapat berlaku umum".
            'location_id' => null,
            'mass_schedule_id' => null,
            'special_mass_id' => null,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
            'is_active' => true,
        ];
    }

    /**
     * A notice about one recurring schedule.
     */
    public function forSchedule(MassSchedule $schedule): static
    {
        return $this->state(fn (): array => ['mass_schedule_id' => $schedule->getKey()]);
    }

    /**
     * A notice whose display window has not opened yet.
     */
    public function scheduled(): static
    {
        return $this->state(fn (): array => [
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    /**
     * A notice whose display window has closed.
     */
    public function expired(): static
    {
        return $this->state(fn (): array => [
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subWeek(),
        ]);
    }

    /**
     * A notice with no end date, which MASS-05 treats as having no upper bound.
     */
    public function openEnded(): static
    {
        return $this->state(fn (): array => ['ends_at' => null]);
    }

    /**
     * A notice the parish has switched off.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
