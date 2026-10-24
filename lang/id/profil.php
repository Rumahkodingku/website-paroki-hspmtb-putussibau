<?php

/*
|--------------------------------------------------------------------------
| Profil Paroki — label antarmuka
|--------------------------------------------------------------------------
|
| Setiap entri adalah label, petunjuk, atau pesan yang ditampilkan antarmuka.
| Tidak ada nilai paroki yang hidup di berkas ini.
|
| Kunci `positions` dibaca oleh App\Enums\PastorPosition lewat method label(),
| jadi jabatan baru tidak bisa muncul di database tanpa seseorang menuliskan
| dulu apa namanya.
|
| Kunci label mengikuti nama field yang dipakai form request dan resource,
| supaya form admin dan halaman publik membaca kata yang sama untuk hal yang
| sama.
|
| @see docs/DECISIONS.md D-52
*/

return [

    /*
     * Dipakai App\Enums\PastorPosition::label().
     */
    'positions' => [
        'pastor_paroki' => 'Pastor Paroki',
        'pastor_rekan' => 'Pastor Rekan',
        'lainnya' => 'Lainnya',
    ],

    'labels' => [
        // Profil utama.
        'name' => 'Nama paroki',
        'short_description' => 'Deskripsi singkat',
        'description' => 'Deskripsi lengkap',
        'history_description' => 'Narasi sejarah',
        'address' => 'Alamat',
        'phone' => 'Telepon',
        'email' => 'Surel',
        'website' => 'Situs web',
        'established_year' => 'Tahun berdiri',
        'logo_media_id' => 'Logo',
        'logo_alt' => 'Teks alternatif logo',
        'cover_media_id' => 'Gambar sampul',
        'cover_alt' => 'Teks alternatif sampul',
        'map_url' => 'Tautan peta',
        'video_url' => 'Tautan video',
        'is_published' => 'Terbitkan',

        // Linimasa sejarah.
        'title' => 'Judul peristiwa',
        'year' => 'Tahun',
        'date' => 'Tanggal',
        'media_id' => 'Gambar',
        'image_alt' => 'Teks alternatif gambar',
        'sort_order' => 'Urutan',

        // Wilayah pelayanan.
        'content' => 'Narasi wilayah pelayanan',
        'map_media_id' => 'Peta',
        'map_url' => 'Tautan peta',

        // Pastor.
        'pastor_name' => 'Nama pastor',
        'pastor_title' => 'Gelar',
        'bio' => 'Biografi',
        'photo_media_id' => 'Foto',
        'photo_alt' => 'Teks alternatif foto',
        'position' => 'Jabatan',
        'start_year' => 'Tahun mulai',
        'end_year' => 'Tahun selesai',
        'is_current' => 'Sedang bertugas',
        'assignment_description' => 'Keterangan penugasan',

        // Kepengurusan.
        'period_name' => 'Nama periode',
        'is_active' => 'Periode aktif',
        'member_name' => 'Nama pengurus',
        'member_position' => 'Jabatan',
    ],

    'hints' => [
        'short_description' => 'Satu atau dua kalimat yang muncul pada kartu dan berbagi tautan.',
        'description' => 'Prosa profil paroki yang tampil pada halaman /profil. HTML yang tidak diizinkan akan dibuang otomatis saat disimpan.',
        'cover_media_id' => 'Gambar utama halaman /profil. Rasio yang disarankan 16:9.',
        'cover_alt' => 'Wajib diisi saat gambar sampul dipilih. Jelaskan gambar secara singkat untuk pembaca layar.',
        'video_url' => 'Hanya tautan YouTube yang diterima; video akan ditanam di halaman /profil.',
        'history_description' => 'Narasi utama sejarah. Linimasa peristiwa dikelola pada daftar di bawahnya.',
        'established_year' => 'Isi hanya bila tahun berdiri memang diketahui.',
        'is_published' => 'Konten yang belum disetujui jangan diterbitkan.',
        'year' => 'Kosongkan bila hanya tanggal yang diketahui. Isi bila hanya tahunnya yang diketahui.',
        'date' => 'Isi bila tanggal tepat diketahui. Tanggal menang atas tahun saat keduanya terisi.',
        'sort_order' => 'Angka kecil ditampilkan lebih dulu.',
        'is_current' => 'Hanya satu pastor yang boleh berstatus sedang bertugas.',
        'is_active' => 'Menetapkan periode ini aktif otomatis mengarsipkan periode sebelumnya.',
        'map_url' => 'Tautan peta opsional. Hanya URL http/https yang diterima.',
        'member_position' => 'Contoh: Ketua, Sekretaris, Bendahara.',
    ],

    'empty' => [
        'profile' => 'Profil paroki belum diisi.',
        'history' => 'Belum ada peristiwa sejarah.',
        'service_area' => 'Narasi wilayah pelayanan belum diisi.',
        'pastors' => 'Belum ada data pastor.',
        'assignments' => 'Belum ada riwayat penugasan.',
        'periods' => 'Belum ada periode kepengurusan.',
        'members' => 'Belum ada anggota pada periode ini.',
        'public_profile' => 'Profil paroki belum dipublikasikan.',
        'public_history' => 'Sejarah paroki belum dipublikasikan.',
        'public_service_area' => 'Informasi wilayah pelayanan belum dipublikasikan.',
        'public_pastors' => 'Data pastor belum dipublikasikan.',
        'public_periods' => 'Struktur kepengurusan belum dipublikasikan.',
    ],

    'admin' => [
        'information_title' => 'Informasi Profil',
        'information_description' => 'Identitas dan deskripsi utama paroki.',
        'history_title' => 'Sejarah Paroki',
        'history_description' => 'Narasi sejarah dan linimasa peristiwa.',
        'service_area_title' => 'Wilayah Pelayanan',
        'service_area_description' => 'Narasi cakupan wilayah pelayanan paroki.',
        'pastors_title' => 'Pastor Paroki',
        'pastors_description' => 'Data pastor dan riwayat penugasan.',
        'structure_title' => 'Struktur Kepengurusan',
        'structure_description' => 'Periode dan anggota kepengurusan paroki.',
        'service_area_note' => 'Data rinci stasi, wilayah, dan lingkungan dikelola pada modul Komunitas dan hanya ditampilkan di sini.',
        'no_overview' => 'Pilih salah satu bagian Profil Paroki pada menu.',
    ],

    'public' => [
        'eyebrow' => 'Profil',
        'nav' => [
            'index' => 'Ringkasan',
            'sejarah' => 'Sejarah',
            'wilayah' => 'Wilayah Pelayanan',
            'pastor' => 'Pastor',
            'struktur' => 'Struktur Kepengurusan',
        ],
        'current_pastor' => 'Pastor yang bertugas',
        'pastor_history' => 'Riwayat pastor paroki',
        'active_period' => 'Periode aktif',
        'period_archive' => 'Arsip periode',
        'updated_notice' => 'Terakhir diperbarui',
    ],

];
