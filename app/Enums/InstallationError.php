<?php

namespace App\Enums;

/**
 * Safe, stable error codes for the installation wizard.
 *
 * The code is what the status file and the JSON response carry; the message is
 * the only thing a browser is allowed to read. Exception details stay in the
 * server log.
 *
 * @see docs/DECISIONS.md D-91
 */
enum InstallationError: string
{
    case RequirementsFailed = 'REQUIREMENTS_FAILED';
    case StatusUnreadable = 'STATUS_UNREADABLE';
    case DatabaseUnsupported = 'DATABASE_UNSUPPORTED';
    case DatabaseUnreachable = 'DATABASE_UNREACHABLE';
    case DatabaseNotFresh = 'DATABASE_NOT_FRESH';
    case InstallLocked = 'INSTALL_LOCKED';
    case SessionExpired = 'SESSION_EXPIRED';
    case MigrationFailed = 'MIGRATION_FAILED';
    case SeederFailed = 'SEEDER_FAILED';
    case AdminEmailTaken = 'ADMIN_EMAIL_TAKEN';
    case AdminCreateFailed = 'ADMIN_CREATE_FAILED';
    case SettingsFailed = 'SETTINGS_FAILED';
    case VerificationFailed = 'VERIFICATION_FAILED';
    case EnvironmentWriteFailed = 'ENVIRONMENT_WRITE_FAILED';
    case InstallFailed = 'INSTALL_FAILED';

    public function message(): string
    {
        return match ($this) {
            self::RequirementsFailed => 'Persyaratan server belum terpenuhi. Perbaiki item yang ditandai lalu periksa ulang.',
            self::StatusUnreadable => 'Status instalasi tidak dapat dibaca. Periksa file status melalui server sebelum mencoba lagi.',
            self::DatabaseUnsupported => 'Driver database tidak didukung installer.',
            self::DatabaseUnreachable => 'Database tidak dapat dijangkau atau kredensial ditolak. Periksa konfigurasi pada .env.',
            self::DatabaseNotFresh => 'Database target sudah berisi tabel. Gunakan database baru atau lakukan pemeriksaan manual.',
            self::InstallLocked => 'Proses instalasi lain sedang berjalan. Periksa status dan log server.',
            self::SessionExpired => 'Sesi instalasi berakhir. Isi kembali data yang diperlukan.',
            self::MigrationFailed => 'Migrasi database gagal. Periksa log server dan status migrasi sebelum mencoba lagi.',
            self::SeederFailed => 'Penyiapan data awal gagal. Periksa log server; jangan mengulang tanpa memeriksa database.',
            self::AdminEmailTaken => 'Email tersebut sudah digunakan akun lain.',
            self::AdminCreateFailed => 'Akun Super Admin gagal dibuat. Instalasi tidak diselesaikan.',
            self::SettingsFailed => 'Identitas paroki gagal disimpan. Instalasi tidak diselesaikan.',
            self::VerificationFailed => 'Verifikasi instalasi gagal. Jangan gunakan aplikasi sebelum diperiksa operator.',
            self::EnvironmentWriteFailed => 'Konfigurasi environment gagal ditulis. Periksa permission file .env.',
            self::InstallFailed => 'Instalasi gagal. Periksa log server sebelum mencoba lagi.',
        };
    }
}
