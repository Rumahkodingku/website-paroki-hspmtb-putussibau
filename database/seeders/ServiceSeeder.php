<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;

/**
 * Seeds the six MVP services roadmap 6B 3.1 requires, and nothing else.
 *
 * Every service is inactive, has no Google Form URL and carries no requirements,
 * steps or FAQs. That is deliberate on both counts:
 *
 * - SVC-01: the requirements and procedure are pastoral policy the parish must
 *   validate before a service is published, so a seeded service starts hidden.
 * - 6B-038/D-34: the Lampiran A "contoh" text must not be presented as official
 *   parish content, so no child rows are seeded at all. The parish writes them.
 *
 * firstOrCreate keyed on slug (6B-104): re-running never overwrites what an admin
 * has changed. The category is resolved by slug, or created if the category
 * seeder has not run.
 *
 * @see docs/DECISIONS.md D-54
 */
class ServiceSeeder extends Seeder
{
    /**
     * Roadmap 6B 3.1: name, slug, category slug, emergency, sort order.
     *
     * @var list<array{title: string, slug: string, category: string, emergency: bool, sort_order: int}>
     */
    private const SERVICES = [
        ['title' => 'Baptis Bayi', 'slug' => 'baptis-bayi', 'category' => 'inisiasi', 'emergency' => false, 'sort_order' => 1],
        ['title' => 'Baptis Dewasa', 'slug' => 'baptis-dewasa', 'category' => 'inisiasi', 'emergency' => false, 'sort_order' => 2],
        ['title' => 'Komuni Pertama', 'slug' => 'komuni-pertama', 'category' => 'inisiasi', 'emergency' => false, 'sort_order' => 3],
        ['title' => 'Krisma', 'slug' => 'krisma', 'category' => 'inisiasi', 'emergency' => false, 'sort_order' => 4],
        ['title' => 'Pernikahan', 'slug' => 'pernikahan', 'category' => 'perkawinan', 'emergency' => false, 'sort_order' => 5],
        ['title' => 'Pelayanan Perminyakan Orang Sakit', 'slug' => 'perminyakan-orang-sakit', 'category' => 'orang-sakit', 'emergency' => true, 'sort_order' => 6],
    ];

    public function run(): void
    {
        foreach (self::SERVICES as $service) {
            $category = $this->categoryFor($service['category']);

            Service::withTrashed()->firstOrCreate(
                ['slug' => $service['slug']],
                [
                    'title' => $service['title'],
                    'service_category_id' => $category->id,
                    'is_emergency' => $service['emergency'],
                    // SVC-01: never public until the parish activates it.
                    'is_active' => false,
                    'form_url' => null,
                    'is_form_open' => false,
                    'form_button_label' => 'Daftar Sekarang',
                    'sort_order' => $service['sort_order'],
                ],
            );
        }
    }

    /**
     * Resolve a seeded category by slug, tolerating a soft-deleted row, and
     * create it if for some reason the category seeder did not run.
     */
    private function categoryFor(string $slug): ServiceCategory
    {
        $existing = ServiceCategory::withTrashed()->where('slug', $slug)->first();

        if ($existing !== null) {
            return $existing;
        }

        return ServiceCategory::query()->create([
            'name' => str($slug)->replace('-', ' ')->title()->toString(),
            'slug' => $slug,
            'is_active' => true,
        ]);
    }
}
