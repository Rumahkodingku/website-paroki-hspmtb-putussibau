<?php

/*
|--------------------------------------------------------------------------
| Pelayanan — label antarmuka
|--------------------------------------------------------------------------
|
| Setiap entri adalah label, petunjuk, atau pesan yang ditampilkan antarmuka.
| Tidak ada nilai paroki resmi yang hidup di berkas ini: teks tentang syarat,
| prosedur, biaya, dan jadwal berasal dari data yang diisi admin.
|
| Empat grup dipakai oleh layar admin dan dikirim lewat ServiceFormMeta:
|
|   labels      nama field
|   hints       petunjuk di bawah field
|   methods     label metode pendaftaran
|   admin       salinan antarmuka lain (judul, tombol, dialog, tab, placeholder)
|   empty       teks keadaan kosong
|
| Grup `admin`, `empty`, dan `flash` adalah sumber tunggal untuk teks itu, supaya
| komponen React tidak menyimpan salinan yang bisa melenceng (PRD 3.3). Istilah
| diseragamkan: "Pelayanan" untuk modul, "Layanan" untuk satu record.
|
| @see docs/DECISIONS.md D-54
*/

return [

    /*
     * Label metode pendaftaran, dibaca ServiceListResource dan badge admin.
     */
    'methods' => [
        'google_form' => 'Google Form',
        'direct_contact' => 'Kontak Langsung',
        'unset' => 'Tidak Diatur',
    ],

    'labels' => [
        // Informasi umum.
        'title' => 'Nama Layanan',
        'slug' => 'Slug',
        'service_category_id' => 'Kategori',
        'thumbnail_media_id' => 'Thumbnail',
        'thumbnail_alt' => 'Teks alternatif thumbnail',
        'summary' => 'Ringkasan Singkat',
        'introduction' => 'Pengantar Layanan',
        'sort_order' => 'Urutan Tampilan',
        'is_active' => 'Aktifkan layanan ini',

        // Persyaratan.
        'requirement_text' => 'Persyaratan',
        'requirement_note' => 'Catatan tambahan',

        // Prosedur.
        'step_title' => 'Judul langkah',
        'step_description' => 'Penjelasan langkah',

        // Jadwal & biaya.
        'schedule_info' => 'Jadwal & Persiapan',
        'fee_info' => 'Biaya/Persembahan',

        // Kontak & pendaftaran.
        'form_url' => 'Link Google Form',
        'form_button_label' => 'Label Tombol Pendaftaran',
        'is_form_open' => 'Buka pendaftaran di website',
        'form_closed_message' => 'Pesan ketika pendaftaran ditutup',
        'contact_name' => 'Nama penanggung jawab',
        'contact_phone' => 'Nomor telepon',
        'is_emergency' => 'Ini adalah layanan darurat',

        // FAQ.
        'faq_question' => 'Pertanyaan',
        'faq_answer' => 'Jawaban',

        // Kategori.
        'category_name' => 'Nama Kategori',
        'category_slug' => 'Slug',
        'category_description' => 'Deskripsi',
        'category_is_active' => 'Kategori aktif',
    ],

    'hints' => [
        'slug' => 'Dibuat dari nama layanan. Dipakai pada alamat halaman publik dan sebaiknya tidak diubah setelah terbit.',
        'thumbnail_media_id' => 'Gambar yang tampil pada kartu layanan di website. Opsional.',
        'thumbnail_alt' => 'Dibaca layar pembaca. Isi dengan singkat bila gambar diunggah.',
        'summary' => 'Muncul pada kartu daftar layanan di website.',
        'sort_order' => 'Angka kecil ditampilkan lebih dulu.',
        'is_active' => 'Hanya layanan aktif yang tampil di website. Pastikan isinya sudah diverifikasi pihak paroki.',
        'form_url' => 'Kosongkan link jika layanan ini tidak menggunakan Google Form.',
        'form_button_label' => 'Label ini tampil pada tombol pendaftaran di halaman publik.',
        'is_form_open' => 'Selaraskan status ini dengan pengaturan "Terima Jawaban" pada Google Form secara manual.',
        'form_closed_message' => 'Ditampilkan ketika pendaftaran ditutup.',
        'contact_name' => 'Nama penanggung jawab layanan, bila ada.',
        'contact_phone' => 'Dipakai untuk tombol telepon/WhatsApp di halaman publik.',
        'is_emergency' => 'Layanan darurat menggunakan kontak langsung dan tidak memakai Google Form.',
        'requirement_note' => 'Catatan opsional, misalnya keterangan tambahan untuk dokumen ini.',
        'fee_info' => 'Biarkan kosong bila biaya belum dikonfirmasi paroki.',
    ],

    /*
     * Salinan antarmuka lain untuk seluruh layar admin Pelayanan.
     * Dikirim sebagai prop `copy`.
     */
    'admin' => [
        // Halaman daftar.
        'list_title' => 'Daftar Pelayanan',
        'list_description' => 'Kelola informasi setiap layanan sakramen dan pastoral yang ditampilkan di website.',
        'add_service' => 'Tambah Layanan',
        'search_placeholder' => 'Cari nama layanan',
        'filter_all_status' => 'Semua status',
        'filter_active' => 'Aktif',
        'filter_inactive' => 'Nonaktif',
        'filter_all_categories' => 'Semua kategori',
        'preview_site' => 'Lihat di website',
        'edit_action' => 'Ubah',

        // Kolom tabel daftar.
        'col_no' => 'No',
        'col_name' => 'Nama Layanan',
        'col_category' => 'Kategori',
        'col_registration' => 'Pendaftaran',
        'col_status' => 'Status',
        'col_order' => 'Urutan',
        'col_updated' => 'Terakhir Diperbarui',
        'col_actions' => 'Aksi',
        'emergency_badge' => 'Layanan Darurat',
        'needs_validation' => 'Perlu Validasi',

        // Form tambah/edit.
        'create_title' => 'Tambah Layanan',
        'create_description' => 'Buat kerangka layanan baru. Simpan dahulu sebelum mengisi persyaratan, prosedur, dan FAQ.',
        'edit_title' => 'Edit Layanan',
        'edit_description' => 'Perbarui informasi layanan yang dikelola.',
        'back' => 'Kembali',
        'save' => 'Simpan',
        'save_changes' => 'Simpan Perubahan',
        'cancel' => 'Batal',
        'add' => 'Tambah',
        'delete' => 'Hapus',
        'confirm_delete_notice' => 'Data ini akan dihapus dan tidak dapat dikembalikan.',
        'create_tabs_note' => 'Simpan layanan terlebih dahulu untuk mengisi persyaratan, prosedur, dan FAQ.',
        'validation_callout' => 'Layanan baru sebaiknya tetap nonaktif sampai syarat dan prosedurnya divalidasi Pastor Paroki/sekretariat.',

        // Tab.
        'tab_general' => 'Informasi Umum',
        'tab_requirements' => 'Persyaratan',
        'tab_steps' => 'Prosedur',
        'tab_schedule' => 'Jadwal & Biaya',
        'tab_contact' => 'Kontak & Pendaftaran',
        'tab_faq' => 'FAQ',

        // Persyaratan.
        'requirement_title' => 'Persyaratan Dokumen',
        'requirement_description' => 'Daftar dokumen yang perlu disiapkan umat sebelum mendaftar.',
        'requirement_dialog_description' => 'Satu baris untuk satu dokumen.',
        'add_requirement' => 'Tambah Persyaratan',
        'edit_requirement' => 'Ubah Persyaratan',
        'delete_requirement' => 'Hapus Persyaratan',
        'order_hint' => 'Urutkan sesuai alur paling mudah dipahami umat. Gunakan catatan bila perlu.',

        // Prosedur.
        'step_title' => 'Prosedur',
        'step_description' => 'Langkah-langkah yang dilalui umat dari pendaftaran sampai selesai.',
        'step_dialog_description' => 'Nomor langkah ditentukan dari urutannya.',
        'add_step' => 'Tambah Langkah',
        'edit_step' => 'Ubah Langkah',
        'delete_step' => 'Hapus Langkah',

        // Jadwal & biaya.
        'schedule_title' => 'Jadwal & Biaya',
        'schedule_description' => 'Informasi jadwal, persiapan, dan biaya/persembahan.',

        // Kontak & pendaftaran.
        'contact_title' => 'Kontak & Pendaftaran',
        'contact_description' => 'Cara umat mendaftar dan menghubungi penanggung jawab layanan.',
        'registration_title' => 'Pendaftaran (Opsional)',
        'registration_description' => 'Gunakan Google Form bila tersedia, atau arahkan umat ke kontak penanggung jawab.',
        'open_link' => 'Buka Tautan',
        'not_using_form' => 'Tidak menggunakan Google Form.',
        'invalid_url_notice' => 'Tautan belum valid.',
        'contact_person_title' => 'Kontak Penanggung Jawab',
        'contact_person_description' => 'Ditampilkan sebagai tombol telepon/WhatsApp bila diisi.',
        'emergency_title' => 'Layanan Darurat',
        'emergency_description' => 'Layanan darurat menggunakan kontak langsung dan tidak memakai Google Form.',
        'emergency_no_url_notice' => 'Link Google Form tidak dipakai dan tidak akan ditampilkan kepada pengunjung. Nomor telepon wajib diisi sebelum layanan diaktifkan.',
        'notes_title' => 'Catatan Penting',
        'note_form_optional' => 'Link Google Form bersifat opsional.',
        'note_test_link' => 'Gunakan tombol Buka Tautan untuk menguji URL yang dimasukkan.',
        'note_manual_sync' => 'Selaraskan status pendaftaran dengan pengaturan "Terima Jawaban" di Google Form.',
        'note_contact_consent' => 'Kontak hanya ditampilkan bila tersedia dan disetujui untuk publikasi.',

        // FAQ.
        'faq_title' => 'Pertanyaan yang Sering Diajukan',
        'faq_description' => 'Pertanyaan dan jawaban yang sering ditanyakan umat mengenai layanan ini.',
        'faq_dialog_description' => 'Satu pertanyaan dengan jawabannya.',
        'add_faq' => 'Tambah FAQ',
        'edit_faq' => 'Ubah FAQ',
        'delete_faq' => 'Hapus FAQ',

        // Urutan.
        'move_up' => 'Naikkan',
        'move_down' => 'Turunkan',

        // Placeholder.
        'placeholder_name' => 'Misalnya: Baptis Bayi',
        'placeholder_slug' => 'Misalnya: baptis-bayi',
        'placeholder_category' => 'Pilih kategori',
        'placeholder_summary' => 'Satu atau dua kalimat untuk kartu daftar layanan.',
        'placeholder_phone' => 'Misalnya: 0812xxxxxxx',
        'placeholder_requirement' => 'Misalnya: Fotokopi Kartu Keluarga',
        'placeholder_step' => 'Misalnya: Mengisi formulir pendaftaran',
        'empty_thumbnail' => 'Thumbnail bersifat opsional.',
    ],

    'categories' => [
        'title' => 'Kategori Pelayanan',
        'description' => 'Kelola kategori yang mengelompokkan layanan pada halaman publik.',
        'create_title' => 'Tambah Kategori',
        'edit_title' => 'Edit Kategori',
        'services_count' => 'Jumlah layanan',
    ],

    'public' => [
        'eyebrow' => 'Pelayanan',
        'index_title' => 'Pelayanan Sakramen & Pastoral',
        'index_description' => 'Informasi syarat, prosedur, dan cara mendaftar untuk pelayanan sakramen dan pastoral di paroki.',
        'detail_back' => 'Kembali ke daftar pelayanan',
        'register_now' => 'Daftar Sekarang',
        'registration_closed' => 'Pendaftaran Ditutup',
        'contact_secretariat' => 'Silakan hubungi sekretariat paroki untuk informasi lebih lanjut.',
        'contact_now' => 'Hubungi Sekarang',
        'call' => 'Telepon',
        'whatsapp' => 'WhatsApp',
        'requirements_title' => 'Syarat Dokumen',
        'steps_title' => 'Prosedur Pendaftaran',
        'schedule_title' => 'Jadwal & Persiapan',
        'fee_title' => 'Biaya/Persembahan',
        'fee_unknown' => 'Hubungi sekretariat untuk informasi biaya.',
        'contact_person_title' => 'Kontak Penanggung Jawab',
        'faq_title' => 'Pertanyaan yang Sering Diajukan',
        'other_services' => 'Layanan Lainnya',
        'updated_notice' => 'Terakhir diperbarui',
        'print' => 'Cetak',
        'flow_title' => 'Alur Pendaftaran',
        'flow' => [
            ['title' => 'Baca syarat', 'description' => 'Pahami syarat dan persiapan layanan.'],
            ['title' => 'Siapkan dokumen', 'description' => 'Lengkapi berkas yang dibutuhkan.'],
            ['title' => 'Isi Google Form', 'description' => 'Daftar melalui tautan pendaftaran yang tersedia.'],
            ['title' => 'Tunggu konfirmasi', 'description' => 'Sekretariat akan menindaklanjuti pendaftaran Anda.'],
        ],
    ],

    /*
     * Teks keadaan kosong. Kunci admin dipakai layar admin; kunci publik
     * disimpan untuk halaman publik.
     */
    'empty' => [
        'list' => 'Belum ada layanan.',
        'list_description' => 'Tambahkan layanan pertama untuk mulai.',
        'list_filtered' => 'Tidak ada layanan untuk filter ini.',
        'list_filtered_description' => 'Coba reset filter untuk melihat seluruh layanan.',
        'requirements' => 'Belum ada persyaratan. Tambahkan yang pertama.',
        'steps' => 'Belum ada prosedur. Tambahkan langkah pertama.',
        'faqs' => 'Belum ada FAQ. Tambahkan yang pertama.',

        'services' => 'Belum ada layanan yang dipublikasikan.',
        'categories' => 'Belum ada kategori pelayanan.',
        'service_not_found' => 'Layanan yang Anda cari tidak ditemukan.',
    ],

    /*
     * Pesan sukses setelah operasi tulis, dibaca controller lewat __().
     */
    'flash' => [
        'service_created' => 'Layanan berhasil ditambahkan. Lengkapi persyaratan dan prosedurnya.',
        'service_updated' => 'Layanan berhasil diperbarui.',
        'service_deleted' => 'Layanan berhasil dihapus.',

        'requirement_created' => 'Persyaratan berhasil ditambahkan.',
        'requirement_updated' => 'Persyaratan berhasil diperbarui.',
        'requirement_deleted' => 'Persyaratan berhasil dihapus.',
        'requirement_reordered' => 'Urutan persyaratan berhasil diperbarui.',

        'step_created' => 'Langkah prosedur berhasil ditambahkan.',
        'step_updated' => 'Langkah prosedur berhasil diperbarui.',
        'step_deleted' => 'Langkah prosedur berhasil dihapus.',
        'step_reordered' => 'Urutan prosedur berhasil diperbarui.',

        'faq_created' => 'FAQ berhasil ditambahkan.',
        'faq_updated' => 'FAQ berhasil diperbarui.',
        'faq_deleted' => 'FAQ berhasil dihapus.',
        'faq_reordered' => 'Urutan FAQ berhasil diperbarui.',
    ],

];
