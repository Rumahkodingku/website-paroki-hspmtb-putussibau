<?php

namespace Database\Seeders;

use App\Models\CommunityCategory;
use Illuminate\Database\Seeder;

/**
 * Seeds the three community categories the module ships with by default:
 * Organisasi/Kategorial, Kelompok Doa dan Pelayanan, Wilayah dan Lingkungan.
 *
 * Master data (not demo): runs on every `migrate:fresh --seed`, including the
 * bare install, so admins always have the default groupings to assign
 * communities to. Re-running is idempotent via firstOrCreate keyed on slug, so
 * it never overwrites an admin's rename, and withTrashed() keeps a soft-deleted
 * category from blocking the unique slot.
 *
 * No description is written: the default text used to say "Contoh kategori
 * komunitas untuk pratinjau.", which is demo copy and must not reach a
 * production installation. See docs/DECISIONS.md D-55 and D-95.
 */
class CommunityCategorySeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, sort_order: int}>
     */
    private const CATEGORIES = [
        'organisasi-kategorial' => ['name' => 'Organisasi/Kategorial', 'sort_order' => 0],
        'kelompok-doa-pelayanan' => ['name' => 'Kelompok Doa dan Pelayanan', 'sort_order' => 1],
        'wilayah-lingkungan' => ['name' => 'Wilayah dan Lingkungan', 'sort_order' => 2],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $slug => $fields) {
            CommunityCategory::withTrashed()->firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $fields['name'],
                    'sort_order' => $fields['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
