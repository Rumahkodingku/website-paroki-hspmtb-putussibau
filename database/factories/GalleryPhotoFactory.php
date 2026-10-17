<?php

namespace Database\Factories;

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryPhoto>
 *
 * A photo always belongs to an album and always points at a ready Media row:
 * the server's own rule only accepts `ready` media as a photo, so a factory that
 * could produce anything else would build rows the application refuses.
 */
class GalleryPhotoFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gallery_album_id' => GalleryAlbum::factory(),
            'media_id' => Media::factory()->ready(),
            'caption' => null,
            'alt_text' => fake()->sentence(6),
            'sort_order' => 0,
        ];
    }
}
