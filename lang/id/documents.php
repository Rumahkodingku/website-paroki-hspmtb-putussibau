<?php

return [
    'statuses' => [
        'draft' => 'Draf',
        'published' => 'Terbit',
        'archived' => 'Arsip',
    ],

    'labels' => [
        'title' => 'Judul dokumen',
        'category' => 'Kategori',
        'description' => 'Deskripsi',
        'file' => 'Berkas dokumen',
        'version' => 'Versi',
        'document_date' => 'Tanggal dokumen',
        'expires_at' => 'Berlaku sampai',
        'status' => 'Status publikasi',
        'is_featured' => 'Dokumen unggulan',
    ],

    'hints' => [
        'title' => 'Nama dokumen yang akan dilihat pengunjung.',
        'category' => 'Kategori dipakai untuk filter di halaman publik.',
        'description' => 'Ringkasan singkat isi dokumen. Opsional.',
        'file' => 'PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, atau ZIP. Maksimal 10 MB.',
        'version' => 'Contoh: 1.0 atau Revisi Oktober 2026.',
        'document_date' => 'Tanggal dokumen, bukan tanggal unggah.',
        'expires_at' => 'Dokumen yang lewat tanggal ini diberi tanda, tetapi tetap dapat diunduh.',
        'status' => 'Draf dan Arsip tidak tampil di halaman publik.',
        'is_featured' => 'Dokumen unggulan tampil di bagian khusus halaman Dokumen.',
    ],

    'admin' => [
        'title' => 'Dokumen',
        'description' => 'Kelola berkas resmi dan informasi yang dibagikan paroki.',
        'create_title' => 'Tambah Dokumen',
        'create_description' => 'Unggah berkas dan lengkapi informasinya.',
        'edit_title' => 'Ubah Dokumen',
        'edit_description' => 'Perbarui metadata atau ganti berkas dokumen.',
        'save_draft' => 'Simpan Draf',
        'publish' => 'Terbitkan',
        'save_changes' => 'Simpan Perubahan',
        'back' => 'Kembali',
        'delete_title' => 'Hapus dokumen',
        'delete_description' => 'Dokumen akan dihapus dari daftar dan halaman publik. Tautan ke layanan ikut dilepas.',
        'file_current' => 'Berkas saat ini',
        'file_replace' => 'Ganti berkas (opsional)',
        'file_required' => 'Pilih berkas dokumen.',
        'no_file' => 'Belum ada berkas.',
        'expired' => 'Kedaluwarsa',
        'featured' => 'Unggulan',
        'downloads' => 'Unduhan',
    ],

    'empty' => [
        'documents' => 'Belum ada dokumen.',
        'filtered' => 'Tidak ada dokumen yang cocok dengan filter.',
        'categories' => 'Belum ada kategori dokumen.',
        'category_filtered' => 'Tidak ada kategori yang cocok.',
    ],

    'category' => [
        'title' => 'Kategori Dokumen',
        'description' => 'Kelompokkan dokumen agar mudah difilter pengunjung.',
        'create_title' => 'Tambah Kategori Dokumen',
        'edit_title' => 'Ubah Kategori Dokumen',
        'name' => 'Nama kategori',
        'slug' => 'Slug',
        'description_field' => 'Deskripsi',
        'sort_order' => 'Urutan',
        'is_active' => 'Kategori aktif',
        'save' => 'Simpan kategori',
        'save_changes' => 'Simpan perubahan',
        'back' => 'Kembali',
    ],

    'flash' => [
        'created' => 'Dokumen berhasil disimpan.',
        'updated' => 'Dokumen berhasil diperbarui.',
        'deleted' => 'Dokumen berhasil dihapus.',
        'category_created' => 'Kategori dokumen berhasil ditambahkan.',
        'category_updated' => 'Kategori dokumen berhasil diperbarui.',
        'category_deleted' => 'Kategori dokumen berhasil dihapus.',
        'category_in_use' => 'Kategori tidak dapat dihapus karena masih memiliki :count dokumen. Pindahkan dokumennya terlebih dahulu.',
    ],
];
