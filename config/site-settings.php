<?php

/*
|--------------------------------------------------------------------------
| Site settings
|--------------------------------------------------------------------------
|
| The single source of truth for which site settings exist, what type each one
| is, what it falls back to, and what the admin form will accept.
|
| It lives in one file on purpose. The service, the form request and the React
| form all need this list, and three hand-maintained copies of it would drift.
|
| Keys come from PRD Lampiran B. Group names come from PRD 9.2.
|
| The privacy group was deferred to P11 because privacy_policy_content is rich
| text and PRD D-14 does not allow storing unsanitized HTML. It is enabled now
| that HtmlSanitizer exists. See docs/DECISIONS.md D-23 and D-25.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Key:    one entry holding the whole map. Thirty rows is small enough that
    |         per-group keys would only add invalidation bugs, and PRD XC-C1
    |         treats site settings as a single thing to cache.
    |
    | TTL:    a safety net, not the source of freshness. Invalidation on write
    |         is what actually keeps this correct, so the TTL is generous. If it
    |         ever expires with no write having happened, nothing is wrong, the
    |         values simply get re-read from the database.
    |
    | Consistency: eventual. A write is followed by forget() in the same
    |             request, so the next read is correct. Nothing in the codebase
    |             reads settings inside a transaction that also writes them.
    |
    */

    'cache' => [
        'key' => 'site_settings',
        'ttl' => 86400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Groups and keys
    |--------------------------------------------------------------------------
    |
    | Insertion order is the order the tabs appear in the admin form.
    |
    */

    'groups' => [
        'identitas' => [
            'parish_name',
            'parish_short_name',
            'tagline',
            'short_description',
            'motto_verse',
            'patron_saint',
            'logo',
            'logo_alt',
            'favicon',
        ],
        'kontak' => [
            'contact_address',
            'contact_phone',
            'contact_whatsapp',
            'contact_email',
            'office_hours',
            'maps_link',
        ],
        /*
         * Konten profil yang dulu tinggal di tabel parish_profiles. Modul
         * Informasi Profil dihapus (D-69); field ini dipindah ke sini atas
         * permintaan pemilik proyek meski sebagian belum punya consumer publik.
         */
        'profil' => [
            'description',
            'established_year',
            'cover',
            'cover_alt',
            'video_url',
            'is_published',
        ],
        'sosial' => [
            'social_facebook',
            'social_instagram',
            'social_youtube',
            'social_whatsapp_channel',
            'website',
        ],
        'seo' => [
            'seo_default_title',
            'seo_default_description',
            'seo_default_og_image',
        ],
        'beranda' => [
            'home_hero_title',
            'home_hero_description',
            'home_hero_image',
            'home_hero_image_alt',
            'home_news_limit',
            'home_events_limit',
            'home_gallery_limit',
            'home_show_devotion',
            'home_show_gallery',
            'home_show_services',
            'home_show_contact',
        ],
        'privasi' => [
            'privacy_policy_content',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Types
    |--------------------------------------------------------------------------
    |
    | value is a LONGTEXT column, so the column type tells us nothing. These
    | entries tell the service what to hand back to callers: reading home_show_
    | gallery and getting the string "0" instead of false is the kind of bug
    | that only shows up in a template.
    |
    | Anything not listed here is a string.
    |
    | html is the exception that is not a cast: the value stays a string because
    | that is what it is. It is declared so the form knows to render an editor
    | instead of a textarea, and so a reader can see that this column is expected
    | to hold markup and has been through HtmlSanitizer. PRD D-14, XC-S1.
    |
    */

    'types' => [
        'privacy_policy_content' => 'html',
        'description' => 'html',
        'office_hours' => 'json',
        'logo' => 'media',
        'favicon' => 'media',
        'cover' => 'media',
        'home_hero_image' => 'media',
        'established_year' => 'integer',
        'is_published' => 'boolean',
        'home_news_limit' => 'integer',
        'home_events_limit' => 'integer',
        'home_gallery_limit' => 'integer',
        'home_show_devotion' => 'boolean',
        'home_show_gallery' => 'boolean',
        'home_show_services' => 'boolean',
        'home_show_contact' => 'boolean',
    ],

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    |
    | Returned when no row exists yet. Nothing is seeded into the table, so an
    | unset setting and a deliberately blank setting stay distinguishable.
    |
    | The two ranges below come from PRD Lampiran B: home_news_limit 3-6 and
    | home_events_limit 3-5. The show_* defaults are an assumption, not from the
    | document: a parish is expected to hide the blocks it does not use, so
    | everything starts visible. Recorded in D-23.
    |
    */

    'defaults' => [
        'home_news_limit' => 3,
        'home_events_limit' => 3,
        'home_gallery_limit' => 6,
        'home_show_devotion' => true,
        'home_show_gallery' => true,
        'home_show_services' => true,
        'home_show_contact' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation rules
    |--------------------------------------------------------------------------
    |
    | Only keys listed here get a real rule. Everything else is free text and is
    | validated as such by UpdateSettingsRequest, which refuses any key that is
    | not in the groups list above.
    |
    | privacy_policy_content needs its own rule because the generic one caps every
    | settings.* field at 2048 characters, and a privacy policy is a page of prose
    | in HTML. 50000 is generous for a policy that a parish actually publishes,
    | and it is still far below config('html.max_input_length') so the sanitizer
    | is never the thing that refuses the payload. The length is checked after
    | sanitization, so it measures what is actually stored.
    |
    */

    'rules' => [
        'privacy_policy_content' => 'nullable|string|max:50000',
        'description' => 'nullable|string|max:50000',
        'website' => 'nullable|url|max:255',
        'video_url' => 'nullable|url|max:500',
        'established_year' => 'nullable|integer|between:1000,'.date('Y'),
        'home_hero_title' => 'nullable|string|max:255',
        'home_hero_description' => 'nullable|string|max:1000',
        'home_hero_image_alt' => 'nullable|string|max:255|required_with:settings.home_hero_image',
        'home_news_limit' => 'integer|between:3,6',
        'home_events_limit' => 'integer|between:3,5',
        'home_gallery_limit' => 'integer|between:1,12',
        // maps_link has no string rule here: it needs a host allowlist and the
        // parsing in App\Support\MapsUrl, so UpdateSettingsRequest attaches the
        // GoogleMapsLink rule object instead.
    ],

    /*
    |--------------------------------------------------------------------------
    | Map links
    |--------------------------------------------------------------------------
    |
    | PRD XC-S2 and KON-02 restrict maps to Google Maps. There is one admin
    | field, `maps_link`, holding a link copied from Google Maps' Share button.
    | App\Support\MapsUrl derives both the directions link and the embed iframe
    | from it, so the parish never pastes an embed URL by hand.
    |
    | The allowlist is a host-to-path-prefix map rather than a substring check,
    | because the substring forms are the bypasses: `google.com.evil.test`
    | contains `google.com`, and so does `evil.test/?next=www.google.com/maps`.
    | Path prefixes are checked against the parsed path, never the raw string.
    |
    | Short share links (maps.app.goo.gl) are listed separately. They carry no
    | coordinates, so an embed cannot be built from them without asking Google;
    | they are refused with a message that tells the admin to copy the longer
    | link instead.
    |
    */

    'maps' => [
        'link_hosts' => [
            'www.google.com' => ['/maps'],
            'maps.google.com' => ['/'],
        ],
        'short_hosts' => ['maps.app.goo.gl', 'goo.gl'],
    ],

];
