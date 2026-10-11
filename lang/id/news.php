<?php

/*
|--------------------------------------------------------------------------
| Berita & Artikel — label antarmuka
|--------------------------------------------------------------------------
|
| Setiap entri adalah label, petunjuk, atau pesan yang ditampilkan antarmuka.
| Tidak ada nilai paroki yang hidup di berkas ini.
|
| Kunci `statuses` dan `post_statuses` dibaca oleh App\Enums\PostStatus lewat
| method label()-nya, jadi status baru tidak bisa muncul di database tanpa
| seseorang menuliskan dulu apa namanya.
|
| Kunci label mengikuti nama field yang dipakai form request dan resource,
| supaya form admin dan kartu publik membaca kata yang sama untuk hal yang sama.
|
| @see docs/DECISIONS.md D-43
*/

return [

    /*
     * Dipakai App\Enums\PostStatus::label(). PRD 7.4 menyebutnya Draf,
     * Dijadwalkan, Terbit, Diarsipkan — kata-kata itu dipakai apa adanya.
     */
    'statuses' => [
        'draft' => 'Draf',
        'scheduled' => 'Dijadwalkan',
        'published' => 'Terbit',
        'archived' => 'Diarsipkan',
    ],

    'post_statuses' => [
        'draft' => 'Draf',
        'scheduled' => 'Dijadwalkan',
        'published' => 'Terbit',
        'archived' => 'Diarsipkan',
    ],

    'labels' => [
        'title' => 'Judul artikel',
        'slug' => 'Slug',
        'post_category_id' => 'Kategori',
        'author_name' => 'Nama penulis',
        'excerpt' => 'Ringkasan',
        'content' => 'Isi artikel',
        'featured_image' => 'Gambar utama',
        'image_alt' => 'Teks alternatif gambar',
        'scripture_reference' => 'Referensi ayat',
        'status' => 'Status',
        'published_at' => 'Waktu terbit',
        'is_pinned' => 'Sematkan',
        'view_count' => 'Jumlah tampilan',
        'meta_title' => 'Meta title',
        'meta_description' => 'Meta description',

        'name' => 'Nama kategori',
        'sort_order' => 'Urutan',
    ],

    'hints' => [
        'slug' => 'Dipakai pada tautan artikel. Kosongkan untuk dibuat otomatis dari judul.',
        'author_name' => 'Kosongkan untuk memakai nama akun pembuat.',
        'excerpt' => 'Ditampilkan pada daftar dan saat tautan dibagikan. Kosongkan untuk memakai awal isi.',
        'image_alt' => 'Dibaca layar pembaca. Wajib diisi untuk gambar utama yang informatif.',
        'scripture_reference' => 'Hanya untuk kategori Renungan. Contoh: Yohanes 15.13',
        'published_at' => 'Waktu lokal Asia/Pontianak. Disimpan sebagai UTC.',
        'meta_title' => 'Kosongkan untuk memakai judul artikel.',
        'meta_description' => 'Kosongkan untuk memakai ringkasan.',
        'sort_order' => 'Angka kecil ditampilkan lebih dulu.',
    ],

    'empty' => [
        'posts' => 'Belum ada artikel.',
        'filtered_posts' => 'Tidak ada artikel untuk filter ini.',
        'categories' => 'Belum ada kategori berita.',
        'filtered_categories' => 'Tidak ada kategori untuk pencarian ini.',
        'public_posts' => 'Belum ada berita yang diterbitkan.',
        'public_filtered' => 'Tidak ada berita untuk pencarian ini.',
        'pinned' => 'Belum ada artikel yang disematkan.',
    ],

    'admin' => [
        'title' => 'Berita',
        'description' => 'Tulis, jadwalkan, dan terbitkan artikel paroki beserta gambar utamanya.',
        'create' => 'Tulis artikel',
        'preview' => 'Pratinjau artikel',
        'preview_hint' => 'Halaman ini hanya untuk admin dan tidak akan terindeks mesin pencari.',
    ],

];
