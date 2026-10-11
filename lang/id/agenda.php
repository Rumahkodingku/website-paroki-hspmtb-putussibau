<?php

/*
|--------------------------------------------------------------------------
| Agenda — label antarmuka
|--------------------------------------------------------------------------
|
| Setiap entri adalah label, petunjuk, atau pesan yang ditampilkan antarmuka.
| Tidak ada nilai paroki yang hidup di berkas ini — tidak ada kegiatan contoh,
| tidak ada tanggal contoh. D-34 menetapkan prinsip yang sama untuk Jadwal Misa.
|
| Kunci `statuses` dibaca oleh App\Enums\EventStatus lewat method label()-nya.
| AC-EVT-4 menuntut label "Dibatalkan" tidak pernah ambigu, dan label yang
| ditulis di dua tempat adalah label yang pada akhirnya berbeda antara daftar
| dan halaman detail.
|
| @see docs/DECISIONS.md D-44
*/

return [

    /*
     * Dipakai App\Enums\EventStatus::label().
     */
    'statuses' => [
        'scheduled' => 'Terjadwal',
        'cancelled' => 'Dibatalkan',
        'postponed' => 'Ditunda',
    ],

    'labels' => [
        'title' => 'Judul kegiatan',
        'slug' => 'Slug',
        'event_category_id' => 'Kategori',
        'description' => 'Deskripsi',
        'start_at' => 'Waktu mulai',
        'end_at' => 'Waktu selesai',
        'is_all_day' => 'Sepanjang hari',
        'location_name' => 'Nama lokasi',
        'location_id' => 'Lokasi',
        'poster_path' => 'Poster',
        'poster_alt' => 'Teks alternatif poster',
        'status' => 'Status kegiatan',
        'is_published' => 'Tampilkan di situs',

        'name' => 'Nama kategori',
        'color' => 'Warna penanda',
    ],

    'hints' => [
        'slug' => 'Dipakai pada tautan kegiatan. Kosongkan untuk dibuat otomatis dari judul.',
        'start_at' => 'Waktu lokal Asia/Pontianak. Disimpan sebagai UTC.',
        'end_at' => 'Kosongkan bila tidak ada waktu selesai.',
        'is_all_day' => 'Kegiatan tanpa jam tertentu. Jam tidak perlu diisi.',
        'location_id' => 'Pilih lokasi master bila ada. Kosongkan bila belum terdaftar sebagai lokasi master.',
        'location_name' => 'Dipakai bila lokasi belum terdaftar sebagai lokasi master.',
        'poster_alt' => 'Dibaca layar pembaca. Wajib diisi untuk poster yang informatif.',
        'color' => 'Kode warna hex, contoh #2E7D32. Warna adalah petunjuk, bukan satu-satunya penanda.',
    ],

    'empty' => [
        'events' => 'Belum ada agenda.',
        'filtered_events' => 'Tidak ada agenda untuk filter ini.',
        'categories' => 'Belum ada kategori agenda.',
        'public_events' => 'Belum ada agenda yang diumumkan.',
        'public_filtered' => 'Tidak ada agenda untuk filter ini.',
        'month' => 'Belum ada agenda pada bulan ini.',
        'location' => 'Lokasi menyusul',
    ],

    'public' => [
        'upcoming' => 'Agenda mendatang',
        'list_mode' => 'Daftar',
        'calendar_mode' => 'Kalender',
        'this_month' => 'Bulan ini',
        'today' => 'Hari Ini',
        'previous_month' => 'Bulan sebelumnya',
        'next_month' => 'Bulan berikutnya',
        'all_day' => 'Sepanjang hari',
        'add_to_calendar' => 'Tambahkan ke Google Calendar',
        'download_ics' => 'Unduh kalender (.ics)',
        'cancelled_label' => 'Dibatalkan',
        'postponed_label' => 'Ditunda',
    ],

    'admin' => [
        'title' => 'Agenda',
        'description' => 'Kelola kegiatan paroki beserta waktu, lokasi, dan posternya.',
        'duplicate' => 'Gandakan kegiatan',
    ],

];
