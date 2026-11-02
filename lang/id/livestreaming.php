<?php

/*
|--------------------------------------------------------------------------
| Livestreaming
|--------------------------------------------------------------------------
|
| Interface copy for the Livestreaming module: the admin list/form and the
| status/category labels read by App\Enums\Livestream*. The public page keeps
| its copy in the page itself, the same way the public Galeri page does.
|
| @see docs/DECISIONS.md D-101
*/

return [

    'statuses' => [
        'draft' => 'Draf',
        'published' => 'Terbit',
        'archived' => 'Diarsipkan',
    ],

    'broadcast_statuses' => [
        'scheduled' => 'Dijadwalkan',
        'live' => 'Sedang Live',
        'ended' => 'Selesai',
        'unavailable' => 'Tidak Tersedia',
    ],

    'categories' => [
        'liturgi' => 'Liturgi',
        'hari_raya' => 'Hari Raya',
        'devosi' => 'Devosi',
        'kegiatan' => 'Kegiatan',
    ],

    'labels' => [
        'title' => 'Judul Livestreaming',
        'youtube_url' => 'URL YouTube',
        'description' => 'Deskripsi',
        'category' => 'Kategori',
        'location' => 'Lokasi',
        'scheduled_at' => 'Jadwal Siaran',
        'status' => 'Status Publikasi',
        'broadcast_status' => 'Status Siaran',
        'recorded_at' => 'Waktu Rekaman',
        'is_recording_available' => 'Rekaman Tersedia',
        'thumbnail' => 'Thumbnail',
        'updated_at' => 'Terakhir Diperbarui',
    ],

    'hints' => [
        'youtube_url' => 'Tempel tautan video YouTube (watch, youtu.be, shorts, atau live).',
        'description' => 'Ringkasan singkat, maksimal 1.000 karakter.',
        'category' => 'Dipakai sebagai chip dan filter di halaman arsip.',
        'location' => 'Pilih dari master data lokasi. Hanya lokasi aktif yang tersedia.',
        'scheduled_at' => 'Jadwal tayang dalam WIB. Boleh dikosongkan.',
        'recorded_at' => 'Waktu rekaman, dipakai untuk urutan dan filter tahun arsip.',
        'is_recording_available' => 'Centang setelah replay benar-benar tersedia di YouTube.',
        'status' => 'Draf dan Diarsipkan tidak tampil di situs publik.',
    ],

    'admin' => [
        'heading' => 'Livestreaming',
        'description' => 'Kelola siaran langsung dan arsip rekaman YouTube paroki.',
        'add' => 'Tambah Livestreaming',
        'edit' => 'Ubah Livestreaming',
        'save' => 'Simpan',
        'cancel' => 'Batal',
        'back' => 'Kembali',
        'delete' => 'Hapus',
        'search_placeholder' => 'Cari judul livestreaming…',
        'filter_status' => 'Status publikasi',
        'filter_broadcast_status' => 'Status siaran',
        'filter_category' => 'Kategori',
        'all' => 'Semua',
        'start' => 'Mulai Siaran',
        'end' => 'Akhiri Siaran',
        'unavailable' => 'Tandai Tidak Tersedia',
        'start_title' => 'Mulai siaran?',
        'start_description' => 'Siaran akan tampil di beranda dan halaman Livestreaming sampai diakhiri.',
        'start_replace_description' => 'Siaran ":title" sedang live dan akan diakhiri. Lanjutkan memulai siaran ini?',
        'end_title' => 'Akhiri siaran?',
        'end_description' => 'Siaran akan hilang dari beranda. Tandai rekaman tersedia di form ubah setelah replay dipastikan ada.',
        'unavailable_title' => 'Tandai tidak tersedia?',
        'unavailable_description' => 'Video tidak akan tampil sebagai siaran langsung atau rekaman.',
        'live_notice' => 'Siaran sedang live. Akhiri siaran sebelum mengubah status publikasi.',
        'started_at' => 'Mulai',
        'ended_at' => 'Selesai',
        'schedule' => 'Jadwal',
        'recording' => 'Rekaman',
    ],

    'empty' => [
        'items' => 'Belum ada livestreaming.',
        'filtered' => 'Tidak ada livestreaming yang cocok dengan filter.',
    ],

    'flash' => [
        'created' => 'Livestreaming berhasil dibuat.',
        'updated' => 'Livestreaming berhasil diperbarui.',
        'deleted' => 'Livestreaming berhasil dihapus.',
        'started' => 'Siaran ditandai sedang live.',
        'ended' => 'Siaran telah diakhiri.',
        'unavailable' => 'Siaran ditandai tidak tersedia.',
    ],

];
