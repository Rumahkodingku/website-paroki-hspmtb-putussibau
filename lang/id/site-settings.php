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
    ],

    'labels' => [
        'parish_name' => 'Nama paroki',
        'parish_short_name' => 'Nama singkat',
        'tagline' => 'Tagline',
        'short_description' => 'Deskripsi singkat',
        'motto_verse' => 'Motto / ayat',
        'patron_saint' => 'Patron Santo',
        'logo' => 'Lokasi logo',
        'favicon' => 'Lokasi favicon',

        'contact_address' => 'Alamat',
        'contact_phone' => 'Telepon',
        'contact_whatsapp' => 'WhatsApp',
        'contact_email' => 'Surel',
        'office_hours' => 'Jam Layanan',
        'maps_embed_url' => 'URL peta tertanam',
        'maps_link' => 'Tautan peta',

        'social_facebook' => 'Facebook',
        'social_instagram' => 'Instagram',
        'social_youtube' => 'YouTube',
        'social_whatsapp_channel' => 'Kanal WhatsApp',

        'seo_default_title' => 'Judul default',
        'seo_default_description' => 'Deskripsi default',
        'seo_default_og_image' => 'Gambar OG default',

        'home_news_limit' => 'Jumlah berita di beranda',
        'home_events_limit' => 'Jumlah agenda di beranda',
        'home_gallery_limit' => 'Jumlah foto di galeri beranda',
        'home_show_devotion' => 'Tampilkan renungan',
        'home_show_gallery' => 'Tampilkan galeri',
        'home_show_services' => 'Tampilkan pelayanan',
        'home_show_contact' => 'Tampilkan kontak',
    ],

    /*
     | Which keys are long enough to deserve a textarea, and which of those
     | accept something other than plain prose.
     */
    'multiline' => [
        'short_description',
        'contact_address',
        'office_hours',
        'motto_verse',
        'seo_default_description',
    ],

    /*
     | Fields that are paths or URLs rather than prose. Upload widgets arrive
     | with P10, which is the media milestone, so for now they are text.
     */
    'urls' => [
        'maps_embed_url',
        'maps_link',
        'social_facebook',
        'social_instagram',
        'social_youtube',
        'social_whatsapp_channel',
        'logo',
        'favicon',
        'seo_default_og_image',
    ],

];
