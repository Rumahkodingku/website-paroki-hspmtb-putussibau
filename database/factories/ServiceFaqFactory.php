<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceFaq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceFaq>
 *
 * @see docs/DECISIONS.md D-54
 */
class ServiceFaqFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'question' => rtrim(fake()->sentence(), '.').'?',
            'answer' => fake()->paragraph(2),
            'sort_order' => 0,
        ];
    }
}
