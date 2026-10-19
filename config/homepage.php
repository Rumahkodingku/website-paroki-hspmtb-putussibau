<?php

/*
|--------------------------------------------------------------------------
| Homepage
|--------------------------------------------------------------------------
|
| Constants specific to the public homepage. These are not site settings: the
| parish edits the hero through /admin/pengaturan, while the values here are
| fallbacks and references the application itself owns.
|
| fallback_hero_image is served from public/. It is used when the parish has not
| uploaded home_hero_image yet, so a fresh install still opens with the approved
| photograph rather than an empty hero.
|
| fallback_hero_og_image is the PNG copy. Link unfurlers are less reliable with
| WebP than with PNG, and the hero already has both files for that reason.
|
| devotion_category_slug names the seeded post category PRD 7.1 uses for the
| reflection block. It lives here so the controller does not carry a magic
| string; renaming the category in the database intentionally hides the block.
|
*/

return [

    'fallback_hero_image' => '/hero-section.webp',

    'fallback_hero_og_image' => '/hero-section.png',

    'fallback_hero_image_alt' => 'Foto gereja paroki pada malam hari',

    'devotion_category_slug' => 'renungan',

    /*
    |--------------------------------------------------------------------------
    | Illustrations
    |--------------------------------------------------------------------------
    |
    | Fallback-only imagery for the homepage. These are licensed stock
    | photographs served from public/illustrations, never parish photographs,
    | and they are used only when a card has no real media.
    |
    | The flag is off by default on purpose: production must show the parish's
    | own uploads, and the end-to-end suite runs against a database with no
    | media. Turn it on locally with HOMEPAGE_ILLUSTRATIONS=true to review the
    | layout with imagery. Every illustration is labelled "Ilustrasi" in the
    | interface and its alt text says it is an illustration, so nobody mistakes
    | it for a photograph of the parish (AGENTS.md, DESIGN.md).
    |
    | Sources are Unsplash photographs served through picsum.photos, resized
    | with cwebp into three widths per image. The photographer credits are
    | recorded in docs/DECISIONS.md D-71.
    |
    */

    'illustrations' => [
        'enabled' => (bool) env('HOMEPAGE_ILLUSTRATIONS', false),

        'alt_prefix' => 'Ilustrasi: ',

        'images' => [
            'activity' => [
                'alt' => 'orang bekerja di depan komputer',
                'width' => 1200,
                'height' => 800,
                'sources' => [
                    '600' => 'illustrations/activity-600.webp',
                    '960' => 'illustrations/activity-960.webp',
                    '1200' => 'illustrations/activity-1200.webp',
                ],
            ],
            'study' => [
                'alt' => 'meja belajar dengan buku dan komputer',
                'width' => 1200,
                'height' => 800,
                'sources' => [
                    '600' => 'illustrations/study-600.webp',
                    '960' => 'illustrations/study-960.webp',
                    '1200' => 'illustrations/study-1200.webp',
                ],
            ],
            'gathering' => [
                'alt' => 'orang-orang berkumpul di tepi pantai',
                'width' => 1200,
                'height' => 800,
                'sources' => [
                    '600' => 'illustrations/gathering-600.webp',
                    '960' => 'illustrations/gathering-960.webp',
                    '1200' => 'illustrations/gathering-1200.webp',
                ],
            ],
            'journey' => [
                'alt' => 'jalan desa berkabut di antara pepohonan',
                'width' => 1200,
                'height' => 800,
                'sources' => [
                    '600' => 'illustrations/journey-600.webp',
                    '960' => 'illustrations/journey-960.webp',
                    '1200' => 'illustrations/journey-1200.webp',
                ],
            ],
            'nature-lake' => [
                'alt' => 'pemandangan danau dan hutan',
                'width' => 1200,
                'height' => 800,
                'sources' => [
                    '600' => 'illustrations/nature-lake-600.webp',
                    '960' => 'illustrations/nature-lake-960.webp',
                    '1200' => 'illustrations/nature-lake-1200.webp',
                ],
            ],
            'nature-detail' => [
                'alt' => 'buah pinus di atas meja kayu',
                'width' => 1200,
                'height' => 800,
                'sources' => [
                    '600' => 'illustrations/nature-detail-600.webp',
                    '960' => 'illustrations/nature-detail-960.webp',
                    '1200' => 'illustrations/nature-detail-1200.webp',
                ],
            ],
            'craft' => [
                'alt' => 'botol kaca di pagar kayu',
                'width' => 1200,
                'height' => 800,
                'sources' => [
                    '600' => 'illustrations/craft-600.webp',
                    '960' => 'illustrations/craft-960.webp',
                    '1200' => 'illustrations/craft-1200.webp',
                ],
            ],
        ],
    ],

];
