<?php

namespace Database\Seeders;

use App\Models\DocumentCategory;
use Illuminate\Database\Seeder;

/**
 * The five document categories PRD 9.11 names.
 *
 * firstOrCreate keyed on slug: re-running the seeder must not overwrite an
 * admin's rename or reorder.
 */
class DocumentCategorySeeder extends Seeder
{
    /**
     * @var list<array{name: string, slug: string, sort_order: int}>
     */
    private const CATEGORIES = [
        ['name' => 'Formulir Pelayanan', 'slug' => 'formulir-pelayanan', 'sort_order' => 1],
        ['name' => 'Pedoman', 'slug' => 'pedoman', 'sort_order' => 2],
        ['name' => 'Warta Paroki', 'slug' => 'warta-paroki', 'sort_order' => 3],
        ['name' => 'Liturgi', 'slug' => 'liturgi', 'sort_order' => 4],
        ['name' => 'Lainnya', 'slug' => 'lainnya', 'sort_order' => 5],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            DocumentCategory::query()->firstOrCreate(
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
