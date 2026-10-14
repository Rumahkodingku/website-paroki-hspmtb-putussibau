<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

/**
 * Seeds the three service categories the six MVP services belong to.
 *
 * Roadmap 6B 3.1/6.1 names them: Sakramen Inisiasi, Sakramen Perkawinan,
 * Pelayanan Orang Sakit. Category is a master table rather than the PRD's ENUM
 * so an admin can rename and reorder it — see D-54.
 *
 * firstOrCreate keyed on slug, not updateOrCreate: roadmap 6B-104 requires the
 * seeder to be idempotent without overwriting an admin's edits. Re-running it
 * leaves a renamed category exactly as the admin left it. withTrashed() is there
 * so a soft-deleted category, whose slug still holds the unique slot, does not
 * make a second run fail on the unique index.
 *
 * @see docs/DECISIONS.md D-54
 */
class ServiceCategorySeeder extends Seeder
{
    /**
     * The three categories from roadmap 6B 6.1.
     *
     * @var list<array{name: string, slug: string, sort_order: int}>
     */
    private const CATEGORIES = [
        ['name' => 'Sakramen Inisiasi', 'slug' => 'inisiasi', 'sort_order' => 1],
        ['name' => 'Sakramen Perkawinan', 'slug' => 'perkawinan', 'sort_order' => 2],
        ['name' => 'Pelayanan Orang Sakit', 'slug' => 'orang-sakit', 'sort_order' => 3],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            ServiceCategory::withTrashed()->firstOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
