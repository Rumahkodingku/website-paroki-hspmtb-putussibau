<?php

namespace Database\Factories;

use App\Models\GalleryAlbum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GalleryAlbum>
 *
 * The default is a draft album: not public, which is the state an album is
 * actually created in. Every other case is a named state, so a test that says
 * what it means does not have to know the default.
 */
class GalleryAlbumFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // word() is typed as string, unlike words(), whose signature allows
        // array|string depending on its second argument.
        $title = Str::title(
            fake()->unique()->word().' '.fake()->word().' '.fake()->word(),
        );

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'description' => fake()->sentence(12),
            'event_date' => fake()->date(),
            'location' => null,
            'cover_photo_id' => null,
            'is_published' => false,
            'published_at' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    /**
     * A published album: visible on the public site.
     */
    public function published(): static
    {
        return $this->state(fn (): array => [
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    /**
     * An album with no event date, for the ordering tests.
     */
    public function withoutDate(): static
    {
        return $this->state(fn (): array => ['event_date' => null]);
    }
}
