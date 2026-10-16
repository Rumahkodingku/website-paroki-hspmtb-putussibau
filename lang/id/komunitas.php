<?php

/*
|--------------------------------------------------------------------------
| Komunitas
|--------------------------------------------------------------------------
|
| Interface copy for the Communities module (Phase 07): the admin category and
| community screens, and the public directory and detail pages. Same reason as
| lang/id/pelayanan.php: two copies of a label drift, and the one that drifts is
| the one nobody re-reads.
|
| Area copy (stasi/wilayah/lingkungan) lives in lang/id/area.php
| since the area module was split out of Komunitas (D-57).
|
| @see docs/DECISIONS.md D-55, D-57
*/

return [

    'status' => [
        'forming' => 'Dalam Pembentukan',
        'active' => 'Aktif',
        'inactive' => 'Tidak Aktif',
    ],

    'labels' => [
        'name' => 'Nama',
        'slug' => 'Slug',
        'description' => 'Deskripsi',
        'category' => 'Kategori',
        'status' => 'Status Komunitas',
        'is_published' => 'Publikasi',
        'logo' => 'Logo',
        'logo_alt' => 'Teks alternatif logo',
        'thumbnail' => 'Thumbnail',
        'thumbnail_alt' => 'Teks alternatif thumbnail',
        'vision' => 'Visi',
        'mission' => 'Misi',
        'meeting_schedule' => 'Jadwal Pertemuan',
        'meeting_location' => 'Lokasi Pertemuan',
        'eligibility' => 'Persyaratan Bergabung',
        'joining_info' => 'Cara Bergabung',
        'contact_name' => 'Nama Kontak',
        'contact_phone' => 'Nomor Kontak',
        'show_contact' => 'Tampilkan kontak di halaman publik',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'youtube' => 'YouTube',
        'website' => 'Situs Web Komunitas',
        'sort_order' => 'Urutan',
        'is_active' => 'Status',
    ],

    'hints' => [
        'slug' => 'Terbentuk otomatis dari nama. Ubah bila perlu.',
        'logo' => 'JPG atau PNG, maksimal 3 MB.',
        'thumbnail' => 'JPG atau PNG, maksimal 3 MB. Satu foto representatif, bukan galeri.',
        'show_contact' => 'Kontak hanya muncul di halaman publik bila opsi ini aktif.',
        'website' => 'Situs milik komunitas ini, bukan situs web paroki. Kosongkan bila belum ada.',
        'facebook' => 'Tempel alamat lengkap, misalnya https://facebook.com/nama-komunitas.',
    ],

    'admin' => [
        'category_heading' => 'Kategori Komunitas',
        'category_description' => 'Kelompok komunitas, mis. Organisasi/Kategorial atau Kelompok Doa.',
        'community_heading' => 'Komunitas',
        'community_description' => 'Profil komunitas, kontak, dan publikasi.',
        'add' => 'Tambah',
        'edit' => 'Ubah',
        'delete' => 'Hapus',
        'save' => 'Simpan',
        'cancel' => 'Batal',
        'back' => 'Kembali',
        'reset_filter' => 'Atur Ulang',
        'search_placeholder' => 'Cari nama…',
        'all' => 'Semua',
        'confirm_delete_title' => 'Hapus :name?',
        'confirm_delete_body' => 'Data akan dihapus. Tindakan ini tidak dapat dibatalkan dari antarmuka.',
        'in_use' => ':count komunitas menggunakan entri ini.',
        'sections' => [
            'basic' => 'Informasi Dasar',
            'images' => 'Logo dan Thumbnail',
            'profile' => 'Profil Komunitas',
            'schedule' => 'Jadwal dan Lokasi',
            'joining' => 'Informasi Bergabung dan Kontak',
            'social' => 'Media Sosial',
            'publication' => 'Publikasi',
        ],
        'publication_draft' => 'Draf (belum tampil publik)',
        'publication_live' => 'Dipublikasikan',
        'not_published_status' => 'Status :status tidak ditampilkan di halaman publik.',
    ],

    'empty' => [
        'categories' => 'Belum ada kategori komunitas.',
        'communities' => 'Belum ada komunitas.',
        'filtered' => 'Tidak ada komunitas yang cocok dengan filter.',
    ],

    'public' => [
        'eyebrow' => 'Komunitas',
        'title' => 'Komunitas Paroki',
        'description' => 'Temukan kelompok dan kategorial di paroki.',
        'search_label' => 'Cari komunitas',
        'search_placeholder' => 'Cari nama komunitas…',
        'filter_category' => 'Kategori',
        'detail_link' => 'Lihat Detail',
        'empty' => 'Belum ada komunitas yang dipublikasikan.',
        'empty_filtered' => 'Tidak ada komunitas yang cocok dengan pencarian atau filter.',
        'about' => 'Tentang Komunitas',
        'vision_mission' => 'Visi dan Misi',
        'schedule_location' => 'Jadwal dan Lokasi Pertemuan',
        'joining' => 'Cara Bergabung',
        'contact' => 'Kontak Pengurus',
        'contact_secretariat' => 'Hubungi Sekretariat',
        'back_to_directory' => 'Kembali ke daftar komunitas',
    ],

    'flash' => [
        'category_created' => 'Kategori komunitas berhasil ditambahkan.',
        'category_updated' => 'Kategori komunitas berhasil diperbarui.',
        'category_deleted' => 'Kategori komunitas berhasil dihapus.',
        'community_created' => 'Komunitas berhasil ditambahkan.',
        'community_updated' => 'Komunitas berhasil diperbarui.',
        'community_deleted' => 'Komunitas berhasil dihapus.',
    ],
];
