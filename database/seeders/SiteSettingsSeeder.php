<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

/**
 * Seeds the initial identity values and the homepage list limits.
 *
 * The identity values are the parish's own legal name, quoted from PRD
 * Lampiran D rather than invented. The three homepage limits are initial
 * values within the ranges config/site-settings.php already validates; a fresh
 * installation starts with the layout the parish asked for instead of the
 * narrower fallback.
 *
 * `logo` is deliberately absent. It is a Media id uploaded by an admin, not a
 * value with a safe default, and creating a blank row would make an unset logo
 * look configured. The same reasoning applies to contact, SEO and privacy
 * settings: they are for the admin to fill in.
 *
 * The insert is skipped when a row already exists, so running the seeder twice
 * does not undo anything an admin has since edited — including the logo.
 *
 * @see docs/DECISIONS.md D-12, D-23
 */
class SiteSettingsSeeder extends Seeder
{
    /**
     * The initial values and the group each key belongs to.
     *
     * @var array<string, array{value: string, group: string}>
     */
    private const INITIAL_VALUES = [
        'parish_name' => [
            'value' => 'Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau',
            'group' => 'identitas',
        ],
        'parish_short_name' => [
            'value' => 'HSPMTB',
            'group' => 'identitas',
        ],
        'home_news_limit' => [
            'value' => '4',
            'group' => 'beranda',
        ],
        'home_events_limit' => [
            'value' => '4',
            'group' => 'beranda',
        ],
        'home_gallery_limit' => [
            'value' => '6',
            'group' => 'beranda',
        ],
    ];

    public function run(): void
    {
        foreach (self::INITIAL_VALUES as $key => $setting) {
            SiteSetting::query()->firstOrCreate(
                ['key' => $key],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                ],
            );
        }
    }
}
