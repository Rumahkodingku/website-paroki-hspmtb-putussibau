<?php

namespace Database\Factories;

use App\Models\ParishProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParishProfile>
 */
class ParishProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'description' => '<p>'.fake()->paragraph(4).'</p>',
            'established_year' => 1956,
            'cover_media_id' => null,
            'cover_alt' => null,
            'video_url' => null,
        ];
    }
}
