<?php

namespace Database\Seeders;

use App\Models\EventCategory;
use Illuminate\Database\Seeder;

/**
 * Seeds the five activity categories PRD 9.7 names.
 *
 * No activities are seeded. Same reason as PostCategorySeeder: an agenda entry
 * with a made-up date is the easiest thing in this module to invent by accident.
 *
 * @see docs/DECISIONS.md D-44
 */
class EventCategorySeeder extends Seeder
{
    /**
     * Exactly PRD 9.7.
     *
     * @var list<array{name: string, slug: string}>
     */
    private const CATEGORIES = [
        ['name' => 'Liturgi', 'slug' => 'liturgi'],
        ['name' => 'Pembinaan', 'slug' => 'pembinaan'],
        ['name' => 'Sosial', 'slug' => 'sosial'],
        ['name' => 'Rapat', 'slug' => 'rapat'],
        ['name' => 'Lainnya', 'slug' => 'lainnya'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            EventCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name']],
            );
        }
    }
}
