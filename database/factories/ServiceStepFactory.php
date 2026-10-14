<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceStep>
 *
 * @see docs/DECISIONS.md D-54
 */
class ServiceStepFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'sort_order' => 0,
        ];
    }
}
