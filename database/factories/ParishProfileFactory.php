<?php

namespace Database\Factories;

use App\Models\ParishProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParishProfile>
 *
 * Published by default, like the other content factories. Sample text only —
 * never a real parish value (roadmap section 5, PRD "jangan mengarang data").
 */
class ParishProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Paroki '.fake()->city(),
            'short_description' => fake()->sentence(10),
            'description' => '<p>'.fake()->paragraph(3).'</p>',
            'history_description' => '<p>'.fake()->paragraph(4).'</p>',
            'address' => null,
            'phone' => null,
            'email' => null,
            'website' => null,
            'established_year' => null,
            'logo_media_id' => null,
            'logo_alt' => null,
            'cover_media_id' => null,
            'cover_alt' => null,
            'map_url' => null,
            'video_url' => null,
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (): array => ['is_published' => false]);
    }
}
