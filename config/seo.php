<?php

/*
|--------------------------------------------------------------------------
| SEO
|--------------------------------------------------------------------------
|
| The allowlist of public pages that may carry their own metadata, plus the
| defaults used when an editor has not written any. Keys are stable because
| page_seo.page_key stores them; the labels are what the admin sees.
|
| A page is here only when it is a public index or a static page. Detail pages
| derive their metadata from the content they show, so they are not listed.
|
*/

return [

    'static_pages' => [
        'home' => [
            'route' => 'home',
            'label' => 'Beranda',
            'title' => null,
            'description' => null,
        ],
        'profile' => [
            'route' => 'profil.index',
            'label' => 'Profil Paroki',
            'title' => 'Profil Paroki',
            'description' => 'Mengenal Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau: sejarah, wilayah pelayanan, pastor, dan struktur kepengurusan.',
        ],
        'profile_history' => [
            'route' => 'profil.sejarah',
            'label' => 'Sejarah Paroki',
            'title' => 'Sejarah Paroki',
            'description' => 'Narasi dan linimasa sejarah Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau.',
        ],
        'profile_area_coverage' => [
            'route' => 'profil.wilayah',
            'label' => 'Wilayah Pelayanan',
            'title' => 'Wilayah Pelayanan',
            'description' => 'Cakupan wilayah pelayanan Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau.',
        ],
        'profile_pastors' => [
            'route' => 'profil.pastor',
            'label' => 'Pastor Paroki',
            'title' => 'Pastor Paroki',
            'description' => 'Pastor yang bertugas di Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau beserta riwayat penugasannya.',
        ],
        'profile_structure' => [
            'route' => 'profil.struktur',
            'label' => 'Struktur Kepengurusan',
            'title' => 'Struktur Kepengurusan',
            'description' => 'Susunan kepengurusan Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau per periode.',
        ],
        'area' => [
            'route' => 'area.index',
            'label' => 'Area Paroki',
            'title' => 'Area',
            'description' => 'Kenali stasi dan lingkungan yang menjadi bagian dari kehidupan Gereja Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau.',
        ],
        'mass_schedule' => [
            'route' => 'jadwal-misa.index',
            'label' => 'Jadwal Misa',
            'title' => 'Jadwal Misa',
            'description' => 'Jadwal misa rutin, misa khusus, dan pemberitahuan perubahan jadwal di Paroki HSPMTB Putussibau.',
        ],
        'news' => [
            'route' => 'berita.index',
            'label' => 'Berita & Artikel',
            'title' => 'Berita & Artikel',
            'description' => 'Berita kegiatan, laporan acara, renungan, dan pengumuman resmi Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau.',
        ],
        'agenda' => [
            'route' => 'agenda.index',
            'label' => 'Agenda Kegiatan',
            'title' => 'Agenda Kegiatan',
            'description' => 'Agenda synodal, rapat, dan kegiatan lainnya di Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau.',
        ],
        'services' => [
            'route' => 'pelayanan.index',
            'label' => 'Pelayanan Paroki',
            'title' => 'Pelayanan',
            'description' => 'Informasi syarat, prosedur, dan cara mendaftar pelayanan sakramen dan pastoral di Paroki HSPMTB Putussibau.',
        ],
        'communities' => [
            'route' => 'komunitas.index',
            'label' => 'Komunitas',
            'title' => 'Komunitas',
            'description' => 'Direktori komunitas dan kelompok kategorial Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau.',
        ],
        'gallery' => [
            'route' => 'galeri.index',
            'label' => 'Galeri',
            'title' => 'Galeri',
            'description' => 'Dokumentasi foto kegiatan Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau.',
        ],
        'livestreaming' => [
            'route' => 'livestreaming.index',
            'label' => 'Livestreaming',
            'title' => 'Livestreaming',
            'description' => 'Saksikan siaran langsung dan rekaman kegiatan Paroki HSPMTB Putussibau melalui YouTube.',
        ],
        'documents' => [
            'route' => 'dokumen.index',
            'label' => 'Dokumen',
            'title' => 'Dokumen Paroki',
            'description' => 'Dokumen resmi, formulir, pedoman, dan informasi yang dibagikan Paroki HSPMTB Putussibau.',
        ],
        'contact' => [
            'route' => 'kontak.index',
            'label' => 'Hubungi Kami',
            'title' => 'Hubungi Kami',
            'description' => 'Alamat, telepon, WhatsApp, surel, jam layanan, dan peta lokasi Paroki HSPMTB Putussibau.',
        ],
        'privacy' => [
            'route' => 'legal.privacy',
            'label' => 'Kebijakan Privasi',
            'title' => 'Kebijakan Privasi',
            'description' => null,
        ],
        'terms' => [
            'route' => 'legal.terms',
            'label' => 'Syarat dan Ketentuan',
            'title' => 'Syarat dan Ketentuan',
            'description' => null,
        ],
    ],

];
