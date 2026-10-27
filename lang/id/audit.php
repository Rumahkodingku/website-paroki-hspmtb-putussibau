<?php

/*
|--------------------------------------------------------------------------
| Audit Log
|--------------------------------------------------------------------------
|
| Codes live in config/audit.php. This file is the single source for the
| Indonesian labels the admin sees. A test asserts every code has a label, so
| adding a module without a translation fails the suite rather than showing a
| raw code in the interface.
|
*/

return [

    'modules' => [
        'news' => 'Berita',
        'agenda' => 'Agenda',
        'mass_schedules' => 'Jadwal Misa Rutin',
        'special_masses' => 'Misa Khusus',
        'schedule_notices' => 'Pemberitahuan Jadwal',
        'parish_profile' => 'Profil Paroki',
        'parish_histories' => 'Sejarah Paroki',
        'pastors' => 'Pastor',
        'pastor_assignments' => 'Penugasan Pastor',
        'parish_boards' => 'Periode Kepengurusan',
        'parish_board_members' => 'Anggota Kepengurusan',
        'parish_service_area' => 'Wilayah Pelayanan',
        'services' => 'Pelayanan',
        'service_requirements' => 'Persyaratan Pelayanan',
        'service_steps' => 'Prosedur Pelayanan',
        'service_faqs' => 'FAQ Pelayanan',
        'settings' => 'Pengaturan Situs',
        'users' => 'Akun',
        'media' => 'Media',
        'documents' => 'Dokumen',
        'document_categories' => 'Kategori Dokumen',
        'post_categories' => 'Kategori Berita',
        'event_categories' => 'Kategori Agenda',
        'service_categories' => 'Kategori Pelayanan',
        'community_categories' => 'Kategori Komunitas',
        'communities' => 'Komunitas',
        'areas' => 'Area',
        'locations' => 'Lokasi',
        'galleries' => 'Galeri',
        'gallery_photos' => 'Foto Galeri',
    ],

    'actions' => [
        'created' => 'Dibuat',
        'updated' => 'Diperbarui',
        'settings_updated' => 'Pengaturan diperbarui',
        'published' => 'Diterbitkan',
        'unpublished' => 'Publikasi dibatalkan',
        'scheduled' => 'Dijadwalkan',
        'archived' => 'Diarsipkan',
        'cancelled' => 'Dibatalkan',
        'postponed' => 'Ditunda',
        'deleted' => 'Dihapus',
        'restored' => 'Dipulihkan',
        'activated' => 'Diaktifkan',
        'deactivated' => 'Dinonaktifkan',
        'reordered' => 'Diurutkan ulang',
        'attached' => 'Ditambahkan',
        'duplicated' => 'Digandakan',
        'upload_accepted' => 'Unggahan diterima',
        'media_processing_completed' => 'Pemrosesan gambar selesai',
        'media_processing_failed' => 'Pemrosesan gambar gagal',
        'storage_delete_failed' => 'Penghapusan berkas gagal',
        'login_succeeded' => 'Login berhasil',
        'login_failed' => 'Login gagal',
        'logout' => 'Logout',
        'profile_updated' => 'Profil diperbarui',
        'email_changed' => 'Email diubah',
        'password_changed' => 'Kata sandi diubah',
    ],

    'results' => [
        'success' => 'Berhasil',
        'failure' => 'Gagal',
        'denied' => 'Ditolak',
    ],

    'actors' => [
        'system' => 'Sistem',
        'anonymous' => 'Anonim',
    ],

    /*
    |--------------------------------------------------------------------------
    | Verbs
    |--------------------------------------------------------------------------
    |
    | Used to compose "Berita "Judul" diperbarui." for ordinary events.
    |
    */

    'verbs' => [
        'created' => 'dibuat',
        'updated' => 'diperbarui',
        'settings_updated' => 'diperbarui',
        'published' => 'diterbitkan',
        'unpublished' => 'publikasinya dibatalkan',
        'scheduled' => 'dijadwalkan',
        'archived' => 'diarsipkan',
        'cancelled' => 'dibatalkan',
        'postponed' => 'ditunda',
        'deleted' => 'dihapus',
        'restored' => 'dipulihkan',
        'activated' => 'diaktifkan',
        'deactivated' => 'dinonaktifkan',
        'reordered' => 'diurutkan ulang',
        'attached' => 'ditambahkan',
        'duplicated' => 'digandakan',
        'upload_accepted' => 'diterima',
        'media_processing_completed' => 'selesai diproses',
        'media_processing_failed' => 'gagal diproses',
        'storage_delete_failed' => 'gagal dihapus',
        'login_succeeded' => 'berhasil login',
        'login_failed' => 'gagal login',
        'logout' => 'logout',
        'profile_updated' => 'diperbarui',
        'email_changed' => 'diubah',
        'password_changed' => 'diubah',
    ],

    /*
    |--------------------------------------------------------------------------
    | Fixed descriptions
    |--------------------------------------------------------------------------
    |
    | Actions that are not about a named model use these. Everything else is
    | composed from the module label, the subject label and the verb above.
    |
    */

    'descriptions' => [
        'settings_updated' => 'Pengaturan situs diperbarui (:count pengaturan berubah).',
        'login_succeeded' => 'Login berhasil.',
        'login_failed' => 'Percobaan login gagal.',
        'logout' => 'Logout.',
        'profile_updated' => 'Profil akun diperbarui.',
        'email_changed' => 'Email akun diubah.',
        'password_changed' => 'Kata sandi akun diubah.',
        'media_processing_completed' => 'Pemrosesan gambar selesai.',
        'media_processing_failed' => 'Pemrosesan gambar gagal.',
        'storage_delete_failed' => 'Berkas tidak berhasil dihapus.',
    ],

];
