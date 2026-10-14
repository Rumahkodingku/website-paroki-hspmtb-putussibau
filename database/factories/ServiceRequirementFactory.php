<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceRequirement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequirement>
 *
 * @see docs/DECISIONS.md D-54
 */
class ServiceRequirementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'text' => fake()->sentence(5),
            'note' => null,
            'sort_order' => 0,
        ];
    }
}
