<?php

namespace Database\Factories;

use App\Enums\PastorPosition;
use App\Models\Pastor;
use App\Models\PastorAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pastor>
 *
 * Sample names only. A pastor's name is the single easiest real value to invent
 * by accident, so the factory uses Faker's name and never a plausible-looking
 * parish figure (roadmap section 5).
 */
class PastorFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'title' => 'Pst.',
            'photo_media_id' => null,
            'photo_alt' => null,
            'bio' => '<p>'.fake()->paragraph(3).'</p>',
            'sort_order' => 0,
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }

    /**
     * A pastor with one current assignment, which is the shape the public
     * "pastor yang bertugas" section reads.
     */
    public function current(PastorPosition $position = PastorPosition::PastorParoki): static
    {
        return $this->afterCreating(function (Pastor $pastor) use ($position): void {
            PastorAssignment::factory()->for($pastor)->current()->create([
                'position' => $position,
            ]);
        });
    }
}
