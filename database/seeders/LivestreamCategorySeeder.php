<?php

namespace Database\Seeders;

use App\Models\LivestreamCategory;
use Illuminate\Database\Seeder;

class LivestreamCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Liturgi', 'slug' => 'liturgi', 'sort_order' => 1],
            ['name' => 'Hari Raya', 'slug' => 'hari_raya', 'sort_order' => 2],
            ['name' => 'Devosi', 'slug' => 'devosi', 'sort_order' => 3],
            ['name' => 'Kegiatan', 'slug' => 'kegiatan', 'sort_order' => 4],
        ];

        foreach ($categories as $category) {
            LivestreamCategory::query()->updateOrCreate(
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
