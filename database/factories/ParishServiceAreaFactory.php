<?php

namespace Database\Factories;

use App\Models\ParishServiceArea;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParishServiceArea>
 */
class ParishServiceAreaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => '<p>'.fake()->paragraph(4).'</p>',
            'map_media_id' => null,
            'map_url' => null,
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }
}
