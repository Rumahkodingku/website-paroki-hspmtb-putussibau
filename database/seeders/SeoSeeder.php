<?php

namespace Database\Seeders;

use App\Models\PageSeo;
use App\Services\SiteSettingsService;
use Illuminate\Database\Seeder;

/**
 * The SEO starting point a new installation gets.
 *
 * The page titles and descriptions are the approved defaults in config/seo.php.
 * The homepage is the exception: its copy comes from the parish identity the
 * installation wizard has just saved, so a fresh site does not have an empty
 * homepage title when the parish already entered its name.
 *
 * The Open Graph image is a public file, not a Media row, so it is stored as the
 * global fallback. Page-level og_media_id stays null and resolves through that
 * fallback until an editor picks a page-specific image.
 *
 * @see config/seo.php
 * @see docs/DECISIONS.md D-105
 */
class SeoSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(SiteSettingsService::class);

        $parishName = $this->value($settings->get('parish_name'));
        $shortName = $this->value($settings->get('parish_short_name'));
        $shortDescription = $this->value($settings->get('short_description'));

        $homeTitle = $parishName ?? $shortName;
        $homeDescription = $shortDescription;

        /** @var array<string, array{route: string, label: string, title: string|null, description: string|null}> $pages */
        $pages = (array) config('seo.static_pages', []);

        foreach ($pages as $key => $page) {
            $title = $key === 'home' ? $homeTitle : $page['title'];
            $description = $key === 'home' ? $homeDescription : $page['description'];

            $row = PageSeo::query()->firstOrCreate(
                ['page_key' => $key],
                [
                    'meta_title' => $title,
                    'meta_description' => $description,
                ],
            );

            $changes = [];

            if ($row->meta_title === null && $title !== null) {
                $changes['meta_title'] = $title;
            }

            if ($row->meta_description === null && $description !== null) {
                $changes['meta_description'] = $description;
            }

            if ($changes !== []) {
                $row->update($changes);
            }
        }

        if ($this->value($settings->get('seo_default_og_image')) === null) {
            $settings->set('seo_default_og_image', '/hero-section.png');
        }
    }

    private function value(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
