<?php

namespace Database\Seeders;

use App\Models\PostCategory;
use Illuminate\Database\Seeder;

/**
 * Seeds the four article categories PRD 9.6 names, in the order it lists them.
 *
 * Nothing else is seeded. D-34's rule applies unchanged: the parish's actual
 * articles are content the parish writes, and a `migrate:fresh --seed` that
 * leaves a plausible-looking article behind is how invented parish data gets
 * shipped.
 *
 * updateOrCreate keyed on slug rather than firstOrCreate keyed on name, so
 * running the seeder twice cannot undo an admin's rename — and so a renamed
 * category keeps its id, which is what its articles point at.
 *
 * @see docs/DECISIONS.md D-43
 */
class PostCategorySeeder extends Seeder
{
    /**
     * Exactly PRD 9.6: Berita, Kegiatan, Renungan, Pengumuman.
     *
     * @var list<array{name: string, slug: string, sort_order: int}>
     */
    private const CATEGORIES = [
        ['name' => 'Berita', 'slug' => 'berita', 'sort_order' => 1],
        ['name' => 'Kegiatan', 'slug' => 'kegiatan', 'sort_order' => 2],
        ['name' => 'Renungan', 'slug' => 'renungan', 'sort_order' => 3],
        ['name' => 'Pengumuman', 'slug' => 'pengumuman', 'sort_order' => 4],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            PostCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name'], 'sort_order' => $category['sort_order']],
            );
        }
    }
}
