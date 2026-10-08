<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\MediaVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaVariant>
 */
class MediaVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'media_id' => Media::factory(),
            'name' => fake()->randomElement(array_keys((array) config('media.variants'))),
            'disk' => 'public',
            'path' => 'media/'.fake()->uuid().'.webp',
            'width' => 400,
            'height' => 300,
            'file_size' => fake()->numberBetween(1_000, 100_000),
            'mime_type' => 'image/webp',
        ];
    }
}
