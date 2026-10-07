<?php

namespace Database\Factories;

use App\Enums\MediaStatus;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 *
 * Values are invented placeholders. No test should assert on the literal
 * contents, and the seeders never use this factory.
 */
class MediaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'disk' => 'local',
            'path' => 'media/'.fake()->uuid().'.jpg',
            'original_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => fake()->numberBetween(10_000, 2_000_000),
            'width' => 1600,
            'height' => 1200,
            'alt_text' => null,
            'status' => MediaStatus::Pending,
            'uploader_id' => null,
            'error_message' => null,
        ];
    }

    /**
     * An upload that finished processing.
     */
    public function ready(): static
    {
        return $this->state(fn (): array => ['status' => MediaStatus::Ready]);
    }
}
