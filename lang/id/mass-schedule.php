<?php

/*
|--------------------------------------------------------------------------
| Mass schedule labels
|--------------------------------------------------------------------------
|
| Interface copy for the Jadwal Misa module. Every entry is a label, a hint or a
| message the interface shows — no parish value lives in this file.
|
| The days and types keys mirror App\Enums\DayOfWeek and App\Enums\LocationType:
| the enums ask this file for their label by their own value, so a new day or a
| new kind of location appears here once and nowhere else.
|
| The label keys mirror the field names used by the form requests and the
| resources, so the admin form and the public card read the same word for the
| same thing.
|
*/

return [

    'days' => [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ],

    'types' => [
        'gereja_paroki' => 'Gereja Paroki',
        'stasi' => 'Stasi',
        'kapel' => 'Kapel',
        'lainnya' => 'Lainnya',
    ],

    'statuses' => [
        'active' => 'Aktif',
        'inactive' => 'Nonaktif',
    ],

    'notice_statuses' => [
        'draft' => 'Nonaktif',
        'scheduled' => 'Terjadwal',
        'visible' => 'Sedang tampil',
        'expired' => 'Kedaluwarsa',
    ],

    'notice_scopes' => [
        'general' => 'Umum (semua pengunjung)',
        'location' => 'Lokasi tertentu',
        'mass_schedule' => 'Jadwal rutin tertentu',
        'special_mass' => 'Misa khusus tertentu',
    ],

    'labels' => [
        'name' => 'Nama lokasi',
        'slug' => 'Slug',
        'type' => 'Jenis lokasi',
        'address' => 'Alamat',
        'latitude' => 'Lintang',
        'longitude' => 'Bujur',
        'maps_url' => 'URL Google Maps',
        'notes' => 'Catatan',
        'is_active' => 'Status aktif',

        'location_id' => 'Lokasi',
        'day_of_week' => 'Hari',
        'start_time' => 'Jam mulai',
        'name_mass' => 'Nama misa',
        'language' => 'Bahasa',

        'title' => 'Judul',
        'description' => 'Deskripsi',
        'start_at' => 'Waktu mulai',
        'end_at' => 'Waktu selesai',

        'message' => 'Pesan',
        'mass_schedule_id' => 'Jadwal rutin',
        'special_mass_id' => 'Misa khusus',
        'starts_at' => 'Mulai tampil',
        'ends_at' => 'Akhir tampil',
    ],

    'hints' => [
        'slug' => 'Dipakai pada tautan filter lokasi. Contoh: stasi-tanah-empang.',
        'type' => 'Menentukan bagaimana lokasi ini ditampilkan pada filter halaman publik.',
        'latitude' => 'Koordinat desimal. isi bersama bujur, atau kosongkan keduanya.',
        'longitude' => 'Koordinat desimal. Isi bersama lintang, atau kosongkan keduanya.',
        'maps_url' => 'Tautan Google Maps langsung. Jika kosong, tautan dibuat dari koordinat.',
        'start_time' => 'Jam dinding WIB, bukan tanggal. Contoh: 07.30.',
        'language' => 'Kosongkan bila misa bahasa Indonesia.',
        'start_at' => 'Waktu lokal Asia/Pontianak. Disimpan sebagai UTC.',
        'end_at' => 'Kosongkan bila tidak ada waktu selesai.',
        'starts_at' => 'Notice belum tampil sebelum waktu ini.',
        'ends_at' => 'Kosongkan bila notice tidak punya batas akhir.',
        'note_general' => 'Notice tanpa target berlaku untuk semua pengunjung.',
        'note_exclusive' => 'Isi paling banyak satu target. Kosongkan untuk notice umum.',
    ],

    'next_mass' => [
        'title' => 'Misa Berikutnya',
        'empty_title' => 'Belum ada misa terjadwal',
        'empty_description' => 'Jadwal misa berikutnya belum tersedia. Silakan kembali lagi nanti.',
        'regular' => 'Misa rutin',
        'special' => 'Misa khusus',
    ],

    'notices' => [
        'title' => 'Pemberitahuan Perubahan Jadwal',
    ],

    'empty' => [
        'next_mass' => 'Belum ada misa berikutnya yang dapat ditampilkan.',
        'regular' => 'Belum ada jadwal misa rutin.',
        'special' => 'Belum ada misa khusus yang akan datang.',
        'notices' => 'Belum ada pemberitahuan perubahan jadwal.',
        'locations' => 'Belum ada lokasi.',
        'filtered_regular' => 'Tidak ada jadwal untuk filter ini.',
        'filtered_special' => 'Tidak ada misa khusus untuk filter ini.',
    ],

    'share' => [
        'whatsapp' => 'Bagikan lewat WhatsApp',
        'copy' => 'Salin tautan',
        'print' => 'Cetak jadwal',
        'copied' => 'Tautan halaman disalin.',
        'copy_failed' => 'Tautan tidak dapat disalin. Salin dari bilah alamat.',
        'maps' => 'Buka di Google Maps',
    ],

    'admin' => [
        'module_title' => 'Jadwal Misa',
        'module_description' => 'Kelola lokasi, jadwal misa rutin, misa khusus, dan pemberitahuan perubahan jadwal.',
        'shortcut_locations' => 'Lokasi',
        'shortcut_regular' => 'Misa Rutin',
        'shortcut_special' => 'Misa Khusus',
        'shortcut_notices' => 'Pemberitahuan',
        'stat_regular' => 'Jadwal rutin aktif',
        'stat_locations' => 'Lokasi aktif',
        'stat_special' => 'Misa khusus mendatang',
        'stat_notices' => 'Notice sedang tampil',
        'deactivate_warning' => 'Lokasi ini masih memiliki :count jadwal aktif. Jadwal pada lokasi nonaktif tidak akan tampil di halaman publik.',
        'duplicate_title' => 'Duplikasi jadwal',
        'duplicate_description' => 'Salin jadwal ini ke lokasi lain. Hari, jam, nama, bahasa, dan catatan ikut tersalin.',
    ],

];
