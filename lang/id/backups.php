<?php

return [

    'types' => [
        'database' => 'Database',
        'full' => 'Lengkap',
    ],

    'sources' => [
        'manual' => 'Manual',
        'scheduled' => 'Terjadwal',
        'uploaded' => 'Unggahan',
        'pre_restore' => 'Safety backup',
    ],

    'statuses' => [
        'queued' => 'Menunggu antrean',
        'running' => 'Sedang dibuat',
        'validating' => 'Memvalidasi',
        'success' => 'Berhasil',
        'failed' => 'Gagal',
    ],

    'validation' => [
        'pending' => 'Belum divalidasi',
        'passed' => 'Lolos validasi',
        'failed' => 'Tidak valid',
    ],

    'restore_statuses' => [
        'queued' => 'Menunggu persiapan',
        'preparing' => 'Menyiapkan safety backup',
        'ready' => 'Siap dikonfirmasi',
        'running' => 'Pemulihan berjalan',
        'success' => 'Pemulihan berhasil',
        'failed' => 'Pemulihan gagal',
        'partial' => 'Berhasil sebagian',
        'expired' => 'Kedaluwarsa',
    ],

    'components' => [
        'success' => 'Berhasil',
        'failed' => 'Gagal',
        'skipped' => 'Tidak diterapkan',
    ],

    'availability' => [
        'available' => 'Tersedia',
        'missing' => 'Tidak tersedia',
        'invalid' => 'Tidak valid',
    ],

    'errors' => [
        'backup_failed' => 'Backup gagal diproses.',
        'storage_unavailable' => 'Penyimpanan backup tidak dapat diakses.',
        'disk_full' => 'Ruang penyimpanan tidak mencukupi.',
        'binary_missing' => 'Perangkat dump/restore database tidak tersedia di server.',
        'dump_failed' => 'Dump database gagal dibuat.',
        'archive_invalid' => 'Arsip backup tidak valid atau rusak.',
        'checksum_mismatch' => 'Checksum arsip tidak cocok.',
        'format_unsupported' => 'Format atau versi backup tidak didukung.',
        'path_unsafe' => 'Arsip memuat path yang tidak diizinkan.',
        'size_limit' => 'Arsip melebihi batas ukuran atau jumlah file.',
        'file_missing' => 'File backup tidak ditemukan.',
        'operation_in_progress' => 'Operasi backup atau pemulihan lain sedang berjalan.',
        'safety_backup_failed' => 'Safety backup gagal dibuat; pemulihan dibatalkan.',
        'db_restore_failed' => 'Pemulihan database gagal.',
        'file_restore_failed' => 'Pemulihan file gagal.',
        'verification_failed' => 'Verifikasi setelah pemulihan gagal.',
        'queue_unavailable' => 'Worker antrean belum siap; operasi tidak dapat dijalankan.',
        'delete_failed' => 'File backup gagal dihapus.',
        'file_changed' => 'File backup berubah setelah validasi.',
        'ready_expired' => 'Sesi persiapan pemulihan sudah kedaluwarsa.',
        'confirmation_invalid' => 'Frasa konfirmasi pemulihan tidak sesuai.',
    ],

    'messages' => [
        'created' => 'Backup mulai diproses.',
        'uploaded' => 'Backup berhasil diunggah dan divalidasi.',
        'upload_rejected' => 'Backup ditolak karena tidak valid.',
        'deleted' => 'Backup berhasil dihapus.',
        'settings_updated' => 'Pengaturan backup berhasil disimpan.',
        'restore_preparing' => 'Safety backup sedang disiapkan.',
        'restore_started' => 'Pemulihan mulai dijalankan.',
        'restore_completed' => 'Pemulihan berhasil.',
        'restore_partial' => 'Pemulihan database berhasil, tetapi sebagian file gagal dipulihkan.',
        'restore_failed' => 'Pemulihan gagal.',
        'file_missing' => 'File backup tidak ditemukan di penyimpanan.',
    ],

    'settings' => [
        'title' => 'Pengaturan Backup',
        'description' => 'Atur jadwal, retensi, dan batas operasi backup.',
        'schedule_enabled' => 'Aktifkan backup otomatis',
        'database_enabled' => 'Backup database harian',
        'full_enabled' => 'Backup lengkap mingguan',
        'database_time' => 'Waktu backup database',
        'full_day' => 'Hari backup lengkap',
        'full_time' => 'Waktu backup lengkap',
        'timezone' => 'Zona waktu jadwal',
        'retention_daily_days' => 'Retensi backup database (hari)',
        'retention_weekly_weeks' => 'Retensi backup lengkap (minggu)',
        'retention_invalid_upload_days' => 'Retensi metadata unggahan gagal (hari)',
        'retention_safety_days' => 'Perlindungan safety backup (hari)',
        'max_upload_megabytes' => 'Batas ukuran unggahan (MB)',
        'max_expanded_megabytes' => 'Batas ukuran hasil ekstraksi (MB)',
        'max_files' => 'Batas jumlah file dalam arsip',
        'max_compression_ratio' => 'Batas rasio kompresi',
        'low_disk_threshold_megabytes' => 'Ambang peringatan disk (MB)',
    ],

    'days' => [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
    ],

    'readiness' => [
        'scheduler' => 'Scheduler',
        'worker' => 'Worker antrean',
        'binary' => 'Perangkat database',
        'storage' => 'Penyimpanan',
        'upload_limit' => 'Batas unggah PHP',
        'ready' => 'Siap',
        'not_ready' => 'Belum terdeteksi',
        'local_warning' => 'Backup disimpan di server yang sama. Kehilangan server juga menghilangkan backup.',
    ],

];
