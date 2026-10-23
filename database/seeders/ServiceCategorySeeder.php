<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

/**
 * Seeds the three sacrament categories the seven MVP services belong to.
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
     * The three categories from the parish's sacrament grouping.
     *
     * @var list<array{name: string, slug: string, description: string, sort_order: int}>
     */
    private const CATEGORIES = [
        [
            'name' => 'Sakramen Inisiasi Kristiani',
            'slug' => 'inisiasi-kristiani',
            'description' => 'Sakramen-sakramen yang memulai dan memperkuat kehidupan Kristiani: Baptis, Krisma (Penguatan), dan Ekaristi. Ketiganya merupakan satu kesatuan inisiasi yang mempersatukan umat dengan Kristus dan Gereja-Nya.',
            'sort_order' => 1,
        ],
        [
            'name' => 'Sakramen Penyembuhan',
            'slug' => 'penyembuhan',
            'description' => 'Sakramen penyembuhan bagi umat yang membutuhkan rekonsiliasi dengan Tuhan dan Gereja serta penghiburan dalam sakit: Tobat dan Rekonsiliasi serta Pengurapan Orang Sakit.',
            'sort_order' => 2,
        ],
        [
            'name' => 'Sakramen Pelayanan Persekutuan',
            'slug' => 'pelayanan-persekutuan',
            'description' => 'Sakramen yang mengabdi pada persekutuan dan misi Gereja: Tahbisan Suci bagi mereka yang dipanggil melayani, dan Perkawinan bagi pasangan yang membangun keluarga Kristiani.',
            'sort_order' => 3,
        ],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            ServiceCategory::withTrashed()->firstOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
