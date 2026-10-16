<?php

/*
|--------------------------------------------------------------------------
| Area
|--------------------------------------------------------------------------
|
| Interface copy for the area module (Phase 07, D-57): the admin area screens
| and the public directory and detail pages. Split out of lang/id/komunitas.php
| when the module stopped being part of Komunitas.
|
| The area type labels are read from here too, so a value sent to React as
| `lingkungan` has exactly one place its Indonesian word lives.
|
| @see docs/DECISIONS.md D-55, D-57
*/

return [

    'area_types' => [
        'wilayah' => 'Wilayah',
        'lingkungan' => 'Lingkungan',
        'stasi' => 'Stasi',
    ],

    'labels' => [
        'name' => 'Nama',
        'slug' => 'Slug',
        'description' => 'Deskripsi',
        'address' => 'Alamat',
        'latitude' => 'Lintang',
        'longitude' => 'Bujur',
        'maps_url' => 'Tautan Google Maps',
        'location_heading' => 'Lokasi dan Peta',
        'location_description' => 'Alamat dan titik peta area ini. Lintang dan bujur harus diisi bersama, atau dikosongkan bersama.',
        'sort_order' => 'Urutan',
        'is_active' => 'Status',
        'type' => 'Tipe Area',
        'parent' => 'Wilayah Induk (opsional)',
        'leader_name' => 'Nama Ketua',
        'contact_phone' => 'Nomor Kontak Ketua',
        'show_contact' => 'Tampilkan kontak ketua di halaman publik',
    ],

    'hints' => [
        'slug' => 'Terbentuk otomatis dari nama. Ubah bila perlu.',
        'parent' => 'Opsional. Isi hanya bila lingkungan ini berada di bawah sebuah wilayah.',
        'show_contact' => 'Kontak ketua hanya muncul di halaman publik bila opsi ini aktif.',
        'address' => 'Alamat lengkap area, boleh beberapa baris.',
        'latitude' => 'Boleh dikosongkan, tetapi harus diisi bersama bujur.',
        'longitude' => 'Boleh dikosongkan, tetapi harus diisi bersama lintang.',
        'maps_url' => 'Kosongkan bila hanya koordinat yang diisi; tautan dibuat dari lintang dan bujur.',
    ],

    'admin' => [
        'area_description' => 'Hierarki stasi, wilayah, dan lingkungan serta pengurusnya.',
    ],

    'empty' => [
        'areas' => 'Belum ada stasi, wilayah, atau lingkungan.',
        'filtered' => 'Tidak ada area yang cocok dengan filter.',
    ],

    'flash' => [
        'area_created' => 'Data area berhasil ditambahkan.',
        'area_updated' => 'Data area berhasil diperbarui.',
        'area_deleted' => 'Data area berhasil dihapus.',
    ],

    /*
     * Only the section headings live here. The rest of the public copy is typed
     * in the React pages themselves, and the duplicate strings that sat here were
     * never read by anything - a lang file that describes copy no screen shows
     * is worse than no file at all.
     *
     * PublicAreaController reads section_{type} for the directory groups.
     */
    'public' => [
        'section_stasi' => 'Stasi',
        'section_lingkungan' => 'Lingkungan',
        'section_wilayah' => 'Wilayah',
    ],
];
