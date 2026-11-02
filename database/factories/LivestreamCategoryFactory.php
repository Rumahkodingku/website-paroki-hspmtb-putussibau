<?php

namespace Database\Factories;

use App\Models\LivestreamCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LivestreamCategory>
 */
class LivestreamCategoryFactory extends Factory
{
    protected $model = LivestreamCategory::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => null,
            'sort_order' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
        ];
    }
}
