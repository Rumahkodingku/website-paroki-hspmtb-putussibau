<?php

/*
|--------------------------------------------------------------------------
| Site settings labels
|--------------------------------------------------------------------------
|
| Labels for the site settings form. The keys mirror config/site-settings.php so
| a setting added there gets its label from here and nothing else has to change.
|
| Every entry is an interface label only. No parish value lives in this file.
|
*/

return [

    'groups' => [
        'identitas' => 'Identitas Paroki',
        'kontak' => 'Kontak',
        'sosial' => 'Media Sosial',
        'seo' => 'SEO Dasar',
        'beranda' => 'Beranda',
        'privasi' => 'Kebijakan Privasi',
        'syarat' => 'Syarat & Ketentuan',
    ],

    'labels' => [
        'parish_name' => 'Nama paroki',
        'parish_short_name' => 'Nama singkat',
        'tagline' => 'Tagline',
        'short_description' => 'Deskripsi singkat',
        'motto_verse' => 'Motto / ayat',
        'patron_saint' => 'Patron Santo',
        'logo' => 'Logo',
        'logo_alt' => 'Teks alternatif logo',
        'favicon' => 'Favicon',

        'contact_address' => 'Alamat',
        'contact_phone' => 'Telepon',
        'contact_whatsapp' => 'WhatsApp',
        'contact_email' => 'Surel',
        'office_hours' => 'Jam Pelayanan Sekretariat',
        'maps_link' => 'Tautan Google Maps',

        'website' => 'Situs web paroki',

        'social_facebook' => 'Facebook',
        'social_instagram' => 'Instagram',
        'social_youtube' => 'YouTube',
        'social_whatsapp_channel' => 'Kanal WhatsApp',

        'seo_default_title' => 'Judul default',
        'seo_default_description' => 'Deskripsi default',
        'seo_default_og_image' => 'Gambar OG default',

        'home_hero_title' => 'Judul hero beranda',
        'home_hero_description' => 'Deskripsi hero beranda',
        'home_hero_image' => 'Gambar hero beranda',
        'home_hero_image_alt' => 'Teks alternatif gambar hero',

        'home_news_limit' => 'Jumlah berita di beranda',
        'home_events_limit' => 'Jumlah agenda di beranda',
        'home_gallery_limit' => 'Jumlah foto di galeri beranda',
        'home_show_devotion' => 'Tampilkan renungan',
        'home_show_gallery' => 'Tampilkan galeri',
        'home_show_services' => 'Tampilkan pelayanan',
        'home_show_contact' => 'Tampilkan kontak',

        'privacy_policy_content' => 'Kebijakan privasi',
        'terms_and_conditions_content' => 'Syarat dan ketentuan',
    ],

    /*
     | Which keys are long enough to deserve a textarea, and which of those
     | accept something other than plain prose.
     */
    'multiline' => [
        'short_description',
        'contact_address',
        'motto_verse',
        'seo_default_description',
        'home_hero_description',
    ],

    /*
     | Keys holding HTML rather than prose. The React form renders these with the
     | Tiptap editor, and UpdateSettingsRequest passes them through
     | HtmlSanitizer before validation, because PRD D-14 does not allow storing
     | unsanitized HTML.
     |
     | The list itself is not here. SettingsController reads it from
     | config('site-settings.types'), so the type declaration that triggers
     | sanitization is the same one that triggers the editor. Adding a key below
     | as well would be a second thing to forget.
     |
     | Helper text shown under a field, where the field needs more explanation
     | than its label can carry.
     */
    'hints' => [
        'privacy_policy_content' => 'Isi kebijakan privasi. HTML yang tidak diizinkan akan dibuang otomatis saat disimpan.',
        'terms_and_conditions_content' => 'Isi syarat dan ketentuan. HTML yang tidak diizinkan akan dibuang otomatis saat disimpan.',
        'maps_link' => 'Tempel link dari Google Maps (buka lokasi, tekan Bagikan, lalu Salin link). Peta tertanam dibuat otomatis dari link ini.',
        'office_hours' => 'Atur hari dan jam operasional pelayanan sekretariat paroki.',
        'logo' => 'Logo dipakai pada header dan footer situs.',
        'favicon' => 'Ikon kecil yang tampil di tab peramban. Unggah gambar persegi (PNG disarankan).',
        'home_hero_title' => 'Kosongkan untuk memakai nama paroki sebagai judul hero.',
        'home_hero_description' => 'Kosongkan untuk memakai deskripsi singkat paroki.',
        'home_hero_image' => 'Gambar utama beranda. Bila kosong, situs memakai foto gereja bawaan. Rasio yang disarankan 16:9.',
        'home_hero_image_alt' => 'Wajib diisi saat gambar hero dipilih. Jelaskan gambar secara singkat untuk pembaca layar.',
        'website' => 'Situs resmi paroki, bila ada. Bukan situs komunitas.',
    ],

    /*
     | Fields that are paths or URLs rather than prose. seo_default_og_image
     | remains a text input; logo, favicon and the home hero image use the media
     | picker and are declared by type instead.
     */
    'urls' => [
        'maps_link',
        'social_facebook',
        'social_instagram',
        'social_youtube',
        'social_whatsapp_channel',
        'seo_default_og_image',
        'website',
    ],

];
