<?php

namespace Database\Factories;

use App\Enums\PastorPosition;
use App\Models\Pastor;
use App\Models\PastorAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PastorAssignment>
 */
class PastorAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = (int) fake()->numberBetween(1990, (int) date('Y'));

        return [
            'pastor_id' => Pastor::factory(),
            'position' => PastorPosition::PastorRekan,
            'start_year' => $start,
            'end_year' => $start + 3,
            'is_current' => false,
            'description' => null,
        ];
    }

    /**
     * An open, current assignment: no end year, is_current true.
     */
    public function current(): static
    {
        return $this->state(fn (): array => [
            'end_year' => null,
            'is_current' => true,
        ]);
    }

    public function position(PastorPosition $position): static
    {
        return $this->state(fn (): array => ['position' => $position]);
    }
}
