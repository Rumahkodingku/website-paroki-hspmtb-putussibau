<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Seeds the two site settings PRD Lampiran D names.
 *
 * They are the parish's own legal name, quoted from the document rather than
 * invented, so this does not conflict with the standing rule against fabricated
 * parish data. The PRD still marks them [CONFIRM], which is why nothing else is
 * seeded: the Super Admin fills the rest in through /admin/pengaturan.
 *
 * The insert is skipped when a row already exists, so running the seeder twice
 * does not undo anything an admin has since edited.
 *
 * @see docs/DECISIONS.md D-12, D-23
 */
class SiteSettingsSeeder extends Seeder
{
    /**
     * The initial values, exactly as PRD Lampiran D states them.
     *
     * @var array<string, string>
     */
    private const INITIAL_VALUES = [
        'parish_name' => 'Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau',
        'parish_short_name' => 'HSPMTB',
    ];

    public function run(): void
    {
        foreach (self::INITIAL_VALUES as $key => $value) {
            SiteSetting::query()->firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => 'identitas'],
            );
        }
    }
}
