<?php

namespace Database\Factories;

use App\Models\ParishBoardPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParishBoardPeriod>
 */
class ParishBoardPeriodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = (int) fake()->numberBetween(1990, (int) date('Y') - 4);

        return [
            'name' => 'Periode '.$start.'–'.($start + 3),
            'start_year' => $start,
            'end_year' => $start + 3,
            'description' => null,
            'is_active' => false,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['is_active' => true]);
    }
}
