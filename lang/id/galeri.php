<?php

/*
|--------------------------------------------------------------------------
| Galeri
|--------------------------------------------------------------------------
|
| Interface copy for the Gallery module (Phase 8A): the admin album screens and
| the photo manager, plus the public directory and album detail pages. Same
| reason as lang/id/komunitas.php and lang/id/pelayanan.php: two copies of a
| label drift, and the one that drifts is the one nobody re-reads.
|
| @see docs/DECISIONS.md D-65
*/

return [

    'labels' => [
        'title' => 'Judul Album',
        'slug' => 'Slug',
        'description' => 'Deskripsi',
        'event_date' => 'Tanggal Kegiatan',
        'location' => 'Lokasi',
        'is_published' => 'Publikasi',
        'cover' => 'Sampul Album',
        'caption' => 'Keterangan Foto',
        'alt_text' => 'Teks Alternatif',
        'photos' => 'Foto',
        'photo' => 'Foto',
    ],

    'hints' => [
        'slug' => 'Terbentuk otomatis dari judul. Ubah bila perlu.',
        'description' => 'Ringkasan singkat album. Tampil sebagai deskripsi halaman.',
        'event_date' => 'Tanggal kegiatan, bukan tanggal unggah.',
        'location' => 'Tempat kegiatan, mis. Gereja Paroki HSPMTB.',
        'photos' => 'JPG atau PNG, maksimal 3 MB per foto.',
        'alt_text' => 'Deskripsi singkat untuk pembaca layar.',
        'cover' => 'Pilih satu foto dari album ini sebagai sampul.',
        'sensitive' => 'Pastikan foto anak-anak atau data pribadi sudah mendapat izin sebelum diterbitkan.',
    ],

    'admin' => [
        'heading' => 'Galeri',
        'description' => 'Album foto kegiatan paroki.',
        'add' => 'Tambah Album',
        'edit' => 'Ubah Album',
        'manage_photos' => 'Kelola Foto',
        'delete' => 'Hapus',
        'save' => 'Simpan',
        'save_draft' => 'Simpan Draf',
        'publish' => 'Terbitkan',
        'cancel' => 'Batal',
        'back' => 'Kembali',
        'reset_filter' => 'Atur Ulang',
        'search_placeholder' => 'Cari judul album…',
        'filter_status' => 'Status',
        'filter_year' => 'Tahun',
        'all' => 'Semua',
        'draft' => 'Draf',
        'published' => 'Terbit',
        'draft_hint' => 'Album draf tidak tampil di halaman publik.',
        'photo_count' => ':count foto',
        'no_photos' => 'Belum ada foto di album ini.',
        'confirm_delete_title' => 'Hapus album :name?',
        'confirm_delete_body' => 'Album akan dihapus sehingga tidak tampil lagi, termasuk di halaman publik. Berkas gambar di pustaka media tidak dihapus.',
        'confirm_detach_title' => 'Keluarkan foto ini?',
        'confirm_detach_body' => 'Foto dikeluarkan dari album. Berkas gambar tetap ada di pustaka media dan tidak dihapus.',
        'cover_selected' => 'Sampul album',
        'set_cover' => 'Jadikan sampul',
        'reorder_hint' => 'Geser urutan dengan tombol, lalu simpan.',
        'add_photos' => 'Tambah Foto',
        'saving' => 'Menyimpan…',
        'sections' => [
            'basic' => 'Informasi Album',
            'publication' => 'Publikasi',
        ],
    ],

    'empty' => [
        'albums' => 'Belum ada album galeri.',
        'filtered' => 'Tidak ada album yang cocok dengan filter.',
        'photos' => 'Belum ada foto. Unggah foto pertama untuk album ini.',
    ],

    'public' => [
        'eyebrow' => 'Galeri',
        'title' => 'Galeri Kegiatan',
        'description' => 'Dokumentasi foto kegiatan Paroki HSPMTB Putussibau.',
        'empty' => 'Belum ada album yang dipublikasikan.',
        'empty_filtered' => 'Tidak ada album pada tahun ini.',
        'filter_year' => 'Tahun',
        'all_years' => 'Semua Tahun',
        'photo_count' => ':count foto',
        'detail_link' => 'Lihat Album',
        'back_to_gallery' => 'Kembali ke galeri',
        'album_photos' => 'Foto Album',
        'open_photo' => 'Perbesar foto',
        'close' => 'Tutup',
        'previous' => 'Foto sebelumnya',
        'next' => 'Foto berikutnya',
        'counter' => 'Foto :current dari :total',
        'album_empty' => 'Album ini belum memiliki foto.',
    ],

    'flash' => [
        'album_created' => 'Album berhasil ditambahkan.',
        'album_updated' => 'Album berhasil diperbarui.',
        'album_deleted' => 'Album berhasil dihapus.',
        'photos_added' => 'Foto berhasil ditambahkan ke album.',
        'photo_updated' => 'Keterangan foto berhasil diperbarui.',
        'photo_removed' => 'Foto dikeluarkan dari album.',
        'cover_updated' => 'Sampul album berhasil diperbarui.',
        'photos_reordered' => 'Urutan foto berhasil disimpan.',
    ],
];
