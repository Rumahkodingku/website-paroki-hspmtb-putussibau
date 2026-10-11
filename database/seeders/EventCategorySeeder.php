<?php

namespace Database\Seeders;

use App\Models\EventCategory;
use Illuminate\Database\Seeder;

/**
 * Seeds the five activity categories PRD 9.7 names, with the example colours
 * it gives.
 *
 * The colours are PRD's own examples, not a palette invented here — they are
 * the values roadmap 5.3 quotes, and they are stored so the parish can change
 * them from the admin interface rather than in a stylesheet.
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
     * @var list<array{name: string, slug: string, color: string}>
     */
    private const CATEGORIES = [
        ['name' => 'Liturgi', 'slug' => 'liturgi', 'color' => '#2E7D32'],
        ['name' => 'Pembinaan', 'slug' => 'pembinaan', 'color' => '#1976D2'],
        ['name' => 'Sosial', 'slug' => 'sosial', 'color' => '#F57C00'],
        ['name' => 'Rapat', 'slug' => 'rapat', 'color' => '#7B1FA2'],
        ['name' => 'Lainnya', 'slug' => 'lainnya', 'color' => '#757575'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            EventCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name'], 'color' => $category['color']],
            );
        }
    }
}
