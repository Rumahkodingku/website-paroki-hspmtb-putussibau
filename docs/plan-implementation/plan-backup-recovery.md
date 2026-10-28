# Rencana Implementasi Backup & Recovery

## Website Paroki HSPMTB Putussibau

| Atribut           | Nilai                                                                                                                                               |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| Dokumen           | Rencana Implementasi Backup & Recovery                                                                                                              |
| Produk            | Website Resmi Gereja Katolik Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau                                                               |
| Status            | Final — siap dieksekusi                                                                                                                             |
| Prioritas         | P0                                                                                                                                                  |
| Dokumen sumber    | `docs/specifications/spec-backup-recovery.md`                                                                                                       |
| Stack             | Laravel 13, PHP 8.3+, Inertia v3, React 19, TypeScript, Tailwind CSS v4, MySQL 8                                                                    |
| Bahasa UI         | Bahasa Indonesia                                                                                                                                    |
| Penyimpanan waktu | UTC                                                                                                                                                 |
| Zona tampilan     | `Asia/Pontianak`                                                                                                                                    |
| Cakupan           | Backup database, full backup, upload eksternal, restore, jadwal, retensi, pengaturan                                                                |
| Discovery         | Codebase Memory MCP project `home-riot-projects-rumahkodingku-PROJECTS-website-paroki-hspmtb-putussibau` (status `ready`) + pembacaan sumber aktual |

---

## 1. Ringkasan keputusan

| Topik                        | Keputusan                                                                                                                                          |
| ---------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| Eksekusi produksi            | Queue khusus `backups` + worker `--queue=backups,default`; heartbeat worker ditampilkan di UI                                                      |
| Binary database              | `mysqldump` dan `mysql` dengan deteksi executable; argumen terpisah; password melalui environment                                                  |
| Uji restore nyata            | Database uji terpisah `website_paroki_hspmtb_restore_test`; test di-skip dengan alasan jelas bila tidak tersedia                                   |
| Jadwal awal                  | Aktif: database harian 02.00, full mingguan Minggu 02.30, timezone `Asia/Pontianak`                                                                |
| Retensi                      | Harian 14 hari, mingguan 4 minggu, manual tidak dihapus otomatis, upload invalid 7 hari, safety backup 30 hari                                     |
| Full restore file            | Ganti file yang ada di arsip; file lain dipertahankan; UI menyatakan hasil bukan cermin penuh                                                      |
| Batas upload                 | 256 MB (dapat diubah di pengaturan)                                                                                                                |
| Permission pengaturan        | `backups.settings`                                                                                                                                 |
| Tabel dikecualikan dari dump | `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, `backups`, `backup_restores`, `backup_settings` |
| Restore produksi             | Diizinkan dari UI dengan `backups.restore` + frasa `PULIHKAN DATA`; safety backup wajib; lock eksklusif                                            |
| Restore flow                 | Dua tahap: siapkan safety backup (non-destruktif) → konfirmasi final → restore destruktif                                                          |
| Storage                      | Disk privat `backups`; temporary workspace terpisah; tidak ada public URL                                                                          |
| Format                       | Database `.sql.gz`; full `.zip` dengan `manifest.json`                                                                                             |
| Checksum                     | SHA-256 untuk artefak dan komponen payload                                                                                                         |
| Maintenance                  | Flag cache + middleware write-block; bukan `artisan down`                                                                                          |
| Package baru                 | Tidak ada; memakai `ZipArchive`, `zlib`, dan Symfony Process yang sudah tersedia                                                                   |
| Audit                        | Memakai `AuditLogger` existing; allowlist diperluas; batas rollback didokumentasikan                                                               |

### 1.1 Keputusan teknis

1. Tiga tabel baru: `backups`, `backup_restores`, `backup_settings`.
2. `backups.uuid` menjadi `backup_id` di manifest; primary key tetap `bigint`.
3. Metadata backup **tidak memakai foreign key ke `users`** karena tabel `users` ikut di-restore, sedangkan tabel backup dikecualikan dari dump.
4. `backup_restores.backup_id` dan `safety_backup_id` memakai FK ke `backups` karena kedua tabel sama-sama dikecualikan dari dump.
5. Dump database mengecualikan tabel operasional dan tabel backup; `audit_events` tetap disertakan.
6. Manifest database disisipkan sebagai komentar `-- HSPMTB-MANIFEST:<base64>` di awal `.sql.gz`.
7. Manifest full backup adalah `manifest.json` di root ZIP.
8. Restore memakai dua tahap: `preparing` (safety backup) → `ready` → `running` (destruktif) → hasil.
9. Restore worker memakai `Cache::lock('backups:exclusive')`; create/restore tidak boleh berjalan bersamaan.
10. Maintenance write memakai flag cache `backups:restore-in-progress` + middleware; TTL 2 jam.
11. Retensi dan jadwal memakai slot unik di database, bukan nama file.
12. Semua error yang tampil ke UI memakai kode dan pesan aman; stack trace, SQL mentah, dan absolute path tidak pernah dikirim ke browser.
13. Semua route mutasi memakai POST/PUT/DELETE + CSRF.
14. File final hanya berada di disk privat `backups`; download melalui controller streaming.
15. Regenerasi Wayfinder setelah route berubah: `php artisan wayfinder:generate --with-form`.

---

## 2. Prinsip implementasi

1. **Fail closed.** Kegagalan authorization, validasi, checksum, lock, atau prasyarat membatalkan operasi.
2. **Success hanya setelah validasi.** Status `success` diberikan setelah artefak final ada, berada di private storage, dan validasi wajib lulus.
3. **Private by default.** Tidak ada backup di public disk, web root, Git, atau frontend build.
4. **No secrets.** `.env`, credential, token, private key, dan secret tidak pernah masuk arsip, response, atau log aplikasi.
5. **Restore destruktif harus aman.** Permission + konfirmasi eksplisit + safety backup tervalidasi + lock + verifikasi.
6. **Partial failure tidak disamarkan.** Database berhasil tetapi file gagal = `partial`, bukan `success`.
7. **Audit Log existing.** Tidak membuat sistem audit paralel.
8. **Path tidak pernah dari user.** Nama file internal dibuat server; path tujuan berasal dari konfigurasi.
9. **Database target tidak pernah dari user.** Selalu koneksi aplikasi yang dikonfigurasi.
10. **Follow repository architecture.** Controller tipis, Action untuk operasi multi-langkah, Service untuk kemampuan reusable, Policy/Gate untuk authorization.
11. **No new package.** Gunakan ekstensi dan dependency yang sudah ada.
12. **No migration edit.** Migration baru saja.
13. **UI jujur.** Jika scheduler, worker, binary, atau storage belum siap, UI menyatakannya, bukan mengklaim aktif.
14. **Operasional teruji.** Backup bukan hanya terbentuk; integritas dan prosedur restore harus diuji.

---

## 3. Temuan discovery

| Area                   | Fakta aktual                                                                                                          | Implikasi                                          |
| ---------------------- | --------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------- |
| Stack                  | Laravel 13, PHP 8.3, Inertia v3, React 19, MySQL 8.0.46                                                               | Sesuai spesifikasi                                 |
| Ukuran DB pengembangan | ±3,44 MB, 47 tabel                                                                                                    | Backup manual realistis untuk pengujian lokal      |
| Binary lokal           | `/usr/bin/mysqldump` versi 8.0.46 tersedia                                                                            | Produksi harus diverifikasi operator               |
| Privilege lokal        | `root@localhost` dengan privilege luas                                                                                | Cukup untuk dump/restore lokal                     |
| Private storage        | `storage/app/private`                                                                                                 | Tempat yang benar untuk arsip backup               |
| Media asli             | `media/originals` pada disk `local`                                                                                   | Scope full backup                                  |
| Media varian           | `media/variants` pada disk `public`                                                                                   | Scope full backup                                  |
| Dokumen                | `documents` pada disk `local`                                                                                         | Scope full backup                                  |
| Audit Log              | `audit_events`, `App\Services\AuditLogger`, `App\Actions\Audit\RecordAuditEventAction`, allowlist `config/audit.php`  | Perlu diperluas untuk event backup                 |
| Permission             | `PermissionSeeder`; test mewajibkan regex `^[a-z]+\.[a-z]+$`                                                          | `backups.manage-settings` tidak lolos              |
| Super Admin            | `Gate::before` memberi semua ability                                                                                  | Policy tetap diperlukan untuk user non-super-admin |
| Queue                  | `QUEUE_CONNECTION=database`; `retry_after=90`                                                                         | Job panjang berisiko dianggap gagal/retry ganda    |
| Scheduler              | `bootstrap/app.php` + `schedule:work` lokal; cron produksi belum diketahui                                            | Kesiapan harus dideteksi                           |
| Upload lokal           | `upload_max_filesize=2M`, `post_max_size=8M`                                                                          | Upload >2 MB ditolak PHP sebelum Laravel           |
| Ekstensi               | `zip`, `zlib`, `pdo_mysql` tersedia                                                                                   | Tidak perlu package arsip baru                     |
| UI admin               | `features/admin/*/pages`, `DataTable`, `DeleteDialog`, `ConfirmDialog`, `ListParts`, sidebar `ADMINISTRATION_ENTRIES` | Pola UI dapat dipakai ulang                        |
| Test                   | Pest + `RefreshDatabase`; `QUEUE_CONNECTION=sync`; Vitest; Playwright                                                 | Perlu strategi uji restore khusus                  |
| Kode backup existing   | Tidak ada                                                                                                             | Dibangun dari nol                                  |

### 3.1 Konflik requirement

- **PRD NFR-AVAIL** menyebut backup harian database + berkas dan retensi ≥ 14 hari.
- **Spec backup** menyarankan database harian + full mingguan, retensi harian 7 hari.

Keputusan yang diambil: database harian + full mingguan aktif, retensi harian 14 hari dan mingguan 4 minggu. Dengan demikian retensi efektif memenuhi ≥ 14 hari dan full backup mingguan tetap tersedia.

---

## 4. Arsitektur solusi

```text
React Admin (Inertia)
  ├─ Halaman daftar
  ├─ Halaman detail
  ├─ Halaman pengaturan
  └─ Dialog Buat / Upload / Siapkan Restore / Konfirmasi Restore / Hapus
          │
          ▼
FormRequest + Policy/Gate
          │
          ▼
Controller tipis
          │
          ▼
Action (orkestrasi) ──────────────► AuditLogger (existing)
          │
          ├─ CreateBackupJob
          ├─ PrepareRestoreJob
          ├─ RestoreBackupJob
          ├─ BackupWorkerHeartbeatJob
          └─ Command scheduler/retensi/rekonsiliasi
          │
          ▼
Service reusable:
  BackupSettings, BackupStorage, BackupPathGuard,
  BackupManifest, BackupArchiveInspector,
  DatabaseDumper, DatabaseRestorer, CommandRunner,
  BackupRetention, BackupHealth, BackupSafeError
          │
          ▼
Private disk `backups`
  ├─ archives/   arsip final
  ├─ uploads/    karantina upload sementara
  └─ (temp workspace lokal terpisah)
```

### 4.1 Tanggung jawab lapisan

| Lapisan     | Tanggung jawab                                                                          |
| ----------- | --------------------------------------------------------------------------------------- |
| Controller  | Authorize, baca input tervalidasi, panggil satu Action/Service, render Inertia/redirect |
| FormRequest | Aturan validasi, normalisasi input, authorization satu tempat                           |
| Policy      | Ability per operasi dan status backup                                                   |
| Action      | Orkestrasi multi-langkah, transaksi, dispatch job, audit                                |
| Service     | Kemampuan reusable: dump, restore, manifest, arsip, path, storage, retensi, health      |
| Job         | Pekerjaan berat dan status lifecycle                                                    |
| Command     | Jadwal, retensi, verifikasi, rekonsiliasi, unlock                                       |
| Model       | Metadata, cast, scope, relasi, accessor status                                          |
| Resource    | Bentuk data aman untuk frontend                                                         |

---

## 5. Skema database

### 5.1 Migration

File baru:

```text
database/migrations/2026_10_28_000000_create_backup_tables.php
```

#### 5.1.1 `backups`

```php
Schema::create('backups', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();

    $table->string('type', 10);              // database | full
    $table->string('source', 20);            // manual | scheduled | uploaded | pre_restore
    $table->string('status', 20);            // queued | running | validating | success | failed
    $table->string('validation_status', 20)->default('pending');
                                             // pending | passed | failed

    $table->string('disk', 30);
    $table->string('path', 500)->nullable();
    $table->string('original_filename', 255)->nullable();

    $table->unsignedBigInteger('file_size')->nullable();
    $table->string('checksum', 64)->nullable();
    $table->string('format_version', 10)->nullable();
    $table->string('note', 500)->nullable();

    $table->string('failure_code', 50)->nullable();
    $table->string('failure_message', 500)->nullable();

    $table->unsignedBigInteger('created_by')->nullable();
    $table->string('created_by_label', 150)->nullable();

    $table->timestamp('started_at')->nullable();
    $table->timestamp('finished_at')->nullable();
    $table->unsignedInteger('duration_ms')->nullable();
    $table->timestamp('validated_at')->nullable();
    $table->timestamp('file_missing_at')->nullable();
    $table->timestamp('protected_until')->nullable();

    $table->string('scheduled_slot', 100)->nullable()->unique();

    $table->timestamps();

    $table->index('type');
    $table->index('source');
    $table->index('status');
    $table->index('validation_status');
    $table->index('created_at');
    $table->index('created_by');
});
```

**Catatan:** `created_by` sengaja tanpa FK ke `users`. Lihat bagian 5.2.

#### 5.1.2 `backup_restores`

```php
Schema::create('backup_restores', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();

    $table->foreignId('backup_id')
        ->constrained('backups')
        ->cascadeOnDelete();

    $table->foreignId('safety_backup_id')
        ->nullable()
        ->constrained('backups')
        ->nullOnDelete();

    $table->string('status', 20);
    // queued | preparing | ready | running | success | failed | partial | expired

    $table->string('database_result', 20)->nullable(); // success | failed | skipped
    $table->string('files_result', 20)->nullable();    // success | failed | skipped

    $table->unsignedBigInteger('requested_by')->nullable();
    $table->string('requested_by_label', 150)->nullable();

    $table->timestamp('confirmed_at')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamp('started_at')->nullable();
    $table->timestamp('finished_at')->nullable();
    $table->unsignedInteger('duration_ms')->nullable();

    $table->string('failure_code', 50)->nullable();
    $table->string('failure_message', 500)->nullable();
    $table->json('verification')->nullable();

    $table->timestamps();

    $table->index('backup_id');
    $table->index('status');
    $table->index('created_at');
    $table->index('requested_by');
});
```

#### 5.1.3 `backup_settings`

```php
Schema::create('backup_settings', function (Blueprint $table) {
    $table->unsignedTinyInteger('id')->primary(); // selalu 1

    $table->boolean('schedule_enabled')->default(true);
    $table->boolean('database_enabled')->default(true);
    $table->boolean('full_enabled')->default(true);

    $table->char('database_time', 5)->default('02:00');
    $table->unsignedTinyInteger('full_day')->default(0); // 0 = Minggu
    $table->char('full_time', 5)->default('02:30');
    $table->string('timezone', 64)->default('Asia/Pontianak');

    $table->unsignedSmallInteger('retention_daily_days')->default(14);
    $table->unsignedSmallInteger('retention_weekly_weeks')->default(4);
    $table->unsignedSmallInteger('retention_invalid_upload_days')->default(7);
    $table->unsignedSmallInteger('retention_safety_days')->default(30);

    $table->unsignedInteger('max_upload_megabytes')->default(256);
    $table->unsignedInteger('max_expanded_megabytes')->default(4096);
    $table->unsignedInteger('max_files')->default(10000);
    $table->unsignedInteger('max_compression_ratio')->default(100);
    $table->unsignedInteger('low_disk_threshold_megabytes')->default(1024);

    $table->unsignedBigInteger('updated_by')->nullable();

    $table->timestamps();
});
```

### 5.2 Catatan skema

1. **Tidak ada FK ke `users`** pada `backups.created_by`, `backup_restores.requested_by`, dan `backup_settings.updated_by`.
   Alasan: restore database akan men-drop/recreate tabel `users`, sedangkan tabel backup dikecualikan dari dump. FK dari tabel yang tetap ada ke tabel yang di-drop akan menyulitkan restore.
2. `backup_restores.backup_id` memakai `cascadeOnDelete` karena `backups` dan `backup_restores` sama-sama dikecualikan dari dump; riwayat restore boleh ikut terhapus saat backup dihapus manual, sementara Audit Log tetap menyimpan event-nya. `safety_backup_id` memakai `nullOnDelete`.
3. `backups.scheduled_slot` unik; MySQL mengizinkan banyak `NULL`, sehingga backup manual/upload tidak bentrok.
4. `status` dan `validation_status` disimpan sebagai string enum aplikasi, bukan `ENUM` database, mengikuti pola enum aplikasi yang ada.
5. `file_missing_at` menandai backup yang file-nya hilang; status utama tetap `success` agar riwayat tidak berubah, tetapi availability menjadi `unavailable`.
6. `protected_until` melindungi safety backup dari retensi normal.
7. `backup_restores.expires_at` membatasi operasi `ready` yang tidak pernah dikonfirmasi.
8. Tidak ada soft delete pada `backups`; penghapusan manual menghapus file lalu metadata, dengan penanganan kegagalan parsial.

### 5.3 Model

| Model                      | Cast                                                                                                                                                                             | Relasi/Scope                                                                                                                      | Catatan                                                 |
| -------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------- |
| `App\Models\Backup`        | `type` → `BackupType`, `source` → `BackupSource`, `status` → `BackupStatus`, `validation_status` → `BackupValidationStatus`, timestamp → `immutable_datetime`, `file_size` → int | `restores()`, `safetyFor()`, scope `latestFirst()`, scope `available()`, accessor `isAvailable()`, `isRunning()`, `isProtected()` | `$fillable`/`#[Fillable]` mengikuti pola model existing |
| `App\Models\BackupRestore` | `status` → `BackupRestoreStatus`, `database_result`/`files_result` → `BackupComponentResult                                                                                      | null`,`verification` → array, timestamp → `immutable_datetime`                                                                    | `backup()`, `safetyBackup()`, scope `active()`          | Status `queued`/`preparing`/`ready`/`running` dianggap aktif |
| `App\Models\BackupSetting` | boolean, integer                                                                                                                                                                 | —                                                                                                                                 | Singleton; service yang membaca/menulis                 |

Accessor penting:

- `Backup::isAvailable(): bool` — `status === success`, `validation_status === passed`, `path !== null`, `file_missing_at === null`.
- `Backup::isDownloadable(): bool` — `isAvailable()`.
- `Backup::isRestorable(): bool` — `isAvailable()` dan tidak sedang direstore.
- `Backup::isProtected(): bool` — `protected_until !== null && protected_until->isFuture()`.
- `BackupRestore::isActive(): bool` — `in_array(status, [queued, preparing, ready, running])`.

### 5.4 Factory

- `BackupFactory`: default `type=database`, `source=manual`, `status=success`, `validation_status=passed`, `disk=backups`, path unik, ukuran, checksum, `created_by`.
- `BackupRestoreFactory`: default `status=ready`, terhubung ke backup valid.
- `BackupSettingFactory`: default setting sesuai keputusan.

Factory dipakai test; tidak ada data seed produksi.

### 5.5 Nilai awal pengaturan

Tidak menambah seeder baru. `BackupSettingsService::values()` mengembalikan default dari konfigurasi/model bila baris belum ada. Baris `backup_settings` dibuat saat pertama kali pengaturan disimpan atau halaman pengaturan dibuka (`firstOrCreate`).

Default final:

```text
schedule_enabled              true
database_enabled              true
full_enabled                  true
database_time                 02:00
full_day                      0 (Minggu)
full_time                     02:30
timezone                      Asia/Pontianak
retention_daily_days          14
retention_weekly_weeks        4
retention_invalid_upload_days 7
retention_safety_days         30
max_upload_megabytes          256
max_expanded_megabytes        4096
max_files                     10000
max_compression_ratio         100
low_disk_threshold_megabytes  1024
```

---

## 6. Konfigurasi

### 6.1 `config/backups.php`

```php
return [
    'disk' => env('BACKUPS_DISK', 'backups'),
    'queue_connection' => env('BACKUPS_QUEUE_CONNECTION', 'backups'),
    'queue' => env('BACKUPS_QUEUE', 'backups'),

    'archive_path' => 'archives',
    'upload_path' => 'uploads',
    'temp_path' => storage_path('app/private/backup-temp'),

    'mysqldump_path' => env('BACKUPS_MYSQLDUMP_PATH', 'mysqldump'),
    'mysql_path' => env('BACKUPS_MYSQL_PATH', 'mysql'),

    'format' => 'hspmtb-backup',
    'format_version' => '1',

    'app_version' => env('APP_VERSION'),
    'app_commit' => env('APP_COMMIT'),

    'timezones' => [
        'Asia/Pontianak',
        'Asia/Jakarta',
        'Asia/Makassar',
        'Asia/Jayapura',
        'UTC',
    ],

    'limits' => [
        'max_upload_megabytes' => 256,
        'max_expanded_megabytes' => 4096,
        'max_files' => 10000,
        'max_compression_ratio' => 100,
    ],

    'timeouts' => [
        'dump_seconds' => 3600,
        'restore_seconds' => 7200,
        'lock_seconds' => 7200,
    ],

    'heartbeat' => [
        'scheduler_ttl_seconds' => 300,
        'worker_ttl_seconds' => 900,
    ],
];
```

Nilai limit yang dapat diubah admin tetap disimpan di `backup_settings`; konfigurasi ini menjadi fallback dan batas teknis.

### 6.2 Disk privat `backups`

Tambahkan di `config/filesystems.php`:

```php
'backups' => [
    'driver' => 'local',
    'root' => storage_path('app/private/backups'),
    'serve' => false,
    'throw' => true,
    'report' => false,
],
```

- `serve => false` memastikan tidak ada URL publik.
- `throw => true` membuat kegagalan storage terlihat dan dapat ditangani fail closed.

### 6.3 Queue khusus `backups`

Tambahkan di `config/queue.php`:

```php
'backups' => [
    'driver' => 'database',
    'connection' => env('DB_QUEUE_CONNECTION'),
    'table' => env('DB_QUEUE_TABLE', 'jobs'),
    'queue' => env('BACKUPS_QUEUE', 'backups'),
    'retry_after' => (int) env('BACKUPS_QUEUE_RETRY_AFTER', 7300),
    'after_commit' => false,
],
```

Job memakai:

```php
->onConnection(config('backups.queue_connection'))
->onQueue(config('backups.queue'))
```

Dengan begitu E2E dapat memakai `BACKUPS_QUEUE_CONNECTION=sync`, sedangkan produksi memakai koneksi `backups` dengan `retry_after` lebih besar dari timeout job.

### 6.4 `.env.example`

Tambahkan tanpa nilai rahasia:

```dotenv
BACKUPS_DISK=backups
BACKUPS_QUEUE_CONNECTION=backups
BACKUPS_QUEUE=backups
BACKUPS_QUEUE_RETRY_AFTER=7300
BACKUPS_MYSQLDUMP_PATH=mysqldump
BACKUPS_MYSQL_PATH=mysql
APP_VERSION=
APP_COMMIT=
```

### 6.5 Permission seeder

Tambahkan ke `database/seeders/PermissionSeeder.php`:

```text
backups.view
backups.create
backups.download
backups.upload
backups.restore
backups.delete
backups.settings
```

`tests/Feature/Foundation/RbacFoundationTest.php` diperbarui dengan daftar permission baru. Regex `^[a-z]+\.[a-z]+$` tidak dilonggarkan.

### 6.6 Audit Log

`config/audit.php`:

- Modules: tambah `backups`.
- Actions: tambah action backup/restore.
- Fields: `backups` → `type`, `source`, `status`, `validation_status`, `file_size`, `checksum`, `format_version`, `note`, `failure_code`, `scheduled_slot`, serta field pengaturan (`schedule_enabled`, `database_enabled`, `full_enabled`, `database_time`, `full_day`, `full_time`, `timezone`, `retention_daily_days`, `retention_weekly_weeks`, `retention_invalid_upload_days`, `retention_safety_days`, `max_upload_megabytes`, `max_expanded_megabytes`, `max_files`, `max_compression_ratio`, `low_disk_threshold_megabytes`).
- Metadata: `backups` → `backup_id`, `operation_id`, `type`, `source`, `status`, `file_size`, `duration_ms`, `error_code`, `safety_backup_id`, `count`, `ids`, `requested_by`, `setting_keys_changed`.
- Summarize: `backups` → `note`.

`lang/id/audit.php`:

- `modules.backups` = `Backup & Pemulihan`.
- Label untuk setiap action baru.
- `verbs`/`descriptions` untuk action yang dipakai `AuditLogger` agar tidak menampilkan kode mentah.

### 6.7 Shared abilities

`app/Http/Middleware/HandleInertiaRequests.php`:

```php
'abilities' => fn (): array => [
    'auditlogs.view' => (bool) $request->user()?->can('auditlogs.view'),
    'backups.view' => (bool) $request->user()?->can('backups.view'),
    'backups.create' => (bool) $request->user()?->can('backups.create'),
    'backups.upload' => (bool) $request->user()?->can('backups.upload'),
    'backups.download' => (bool) $request->user()?->can('backups.download'),
    'backups.restore' => (bool) $request->user()?->can('backups.restore'),
    'backups.delete' => (bool) $request->user()?->can('backups.delete'),
    'backups.settings' => (bool) $request->user()?->can('backups.settings'),
],
```

### 6.8 Scheduler

Tambahkan di `bootstrap/app.php`:

```php
$schedule->command('backups:heartbeat')
    ->everyMinute()
    ->withoutOverlapping();

$schedule->command('backups:run-scheduled')
    ->everyMinute()
    ->withoutOverlapping();

$schedule->job(
    new BackupWorkerHeartbeatJob,
    config('backups.queue'),
    config('backups.queue_connection'),
)->everyFiveMinutes();

$schedule->command('backups:verify')
    ->dailyAt('03:00')
    ->timezone(config('app.display_timezone'));

$schedule->command('backups:prune')
    ->dailyAt('03:30')
    ->timezone(config('app.display_timezone'));
```

Catatan: `backups:run-scheduled` tetap berjalan setiap menit karena waktu database/full dapat diubah admin kapan saja. Idempotensi dijaga oleh `scheduled_slot` unik.

---

## 7. Format backup, manifest, dan validasi

### 7.1 Backup database — `.sql.gz`

- Nama final: `hspmtb-db-YYYY-MM-DD-HHmmss.sql.gz` (UTC).
- Isi: dump MySQL yang dikompresi gzip.
- Manifest disisipkan di awal dump sebagai komentar:

```sql
-- HSPMTB-MANIFEST:<base64 JSON>
-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
-- ...
-- Dump completed on ...
```

- MySQL mengabaikan komentar `--`, sehingga file tetap dapat dipulihkan.
- Validasi:
  1. Baca baris pertama hasil dekompresi.
  2. Decode base64 dan JSON.
  3. Periksa `format`, `format_version`, `type`, `backup_id`, `created_at`.
  4. Hitung SHA-256 dump **tanpa** baris manifest, cocokkan `checksums.database_sha256`.
  5. Pastikan ada header dump dan footer `-- Dump completed`.
  6. Pastikan gzip dapat dibaca sampai EOF.

### 7.2 Backup lengkap — `.zip`

Struktur:

```text
manifest.json
database/database.sql
files/<disk>/<relative-path>...
```

- Nama final: `hspmtb-full-YYYY-MM-DD-HHmmss.zip` (UTC).
- `manifest.json` berada di root ZIP.
- `database/database.sql` tidak dikompresi di dalam ZIP (ZIP sendiri yang mengompresi).
- File upload disimpan dengan prefix disk agar dua disk tidak bertabrakan:
  - `files/local/media/originals/...`
  - `files/public/media/variants/...`
  - `files/local/documents/...`

### 7.3 Skema manifest

```json
{
  "format": "hspmtb-backup",
  "format_version": "1",
  "backup_id": "0199c1f4-7e2a-7f3b-9a11-4b4f2f3c1a2b",
  "type": "full",
  "created_at": "2026-10-28T02:30:00Z",
  "app": {
    "name": "Website Paroki HSPMTB",
    "version": null,
    "commit": null
  },
  "database": {
    "driver": "mysql",
    "dump_path": "database/database.sql",
    "server_version": "8.0.46"
  },
  "files": {
    "included": [
      {
        "disk": "local",
        "path": "media/originals/01/abc.jpg",
        "size": 123456,
        "sha256": "..."
      }
    ],
    "count": 1,
    "total_uncompressed_bytes": 123456
  },
  "checksums": {
    "algorithm": "sha256",
    "database_sha256": "..."
  },
  "created_by": {
    "type": "system",
    "id": null,
    "label": "Penjadwal"
  },
  "note": null
}
```

Untuk backup database, `type` = `database`, `files.included` = `[]`, `files.count` = `0`, `database.dump_path` = `null` atau `database.sql`.

### 7.4 Scope file full backup

Diambil dari konfigurasi aktual:

```php
[
    [
        'disk' => (string) config('media.original_disk'),
        'path' => trim((string) config('media.paths.original'), '/'),
    ],
    [
        'disk' => (string) config('media.variant_disk'),
        'path' => trim((string) config('media.paths.variants'), '/'),
    ],
    [
        'disk' => (string) config('documents.disk'),
        'path' => trim((string) config('documents.path'), '/'),
    ],
]
```

Tidak termasuk:

- `vendor/`, `node_modules/`
- source code
- `.env`, credential, key, token
- `storage/logs`, `storage/framework`, cache, session
- temporary, build frontend, file system
- symlink yang keluar dari root scope

File yang ditambahkan setelah backup tidak dihapus saat restore (lihat bagian 9.6).

### 7.5 Tabel yang dikecualikan dari dump

```text
sessions
cache
cache_locks
jobs
job_batches
failed_jobs
password_reset_tokens
backups
backup_restores
backup_settings
```

Alasan:

- Cache/session/jobs bukan isi backup dan tidak boleh dipulihkan.
- `password_reset_tokens` mengandung token sensitif.
- Tabel backup harus tetap ada agar riwayat, safety backup, dan operasi restore tidak hilang saat database dipulihkan.
- `audit_events` tetap disertakan; konsekuensinya dicatat di bagian 11.

### 7.6 Aturan validasi artefak

Berlaku untuk backup buatan sendiri dan upload:

1. Artefak ada, dapat dibaca, ukuran > 0.
2. Ekstensi sesuai tipe:
   - database: harus `.sql.gz`, bukan sekadar `.gz`
   - full: harus `.zip`
3. Format kompresi dapat dibaca.
4. Dump tidak terpotong (header + footer).
5. Manifest ada, valid, dan `format_version` didukung (`1`).
6. Tipe di manifest cocok dengan tipe record.
7. Semua path relatif aman dan berada dalam scope.
8. Checksum dump cocok.
9. Untuk full: setiap file yang tercantum ada di ZIP, ukuran cocok, SHA-256 cocok.
10. Tidak ada entry ZIP di luar:
    - `manifest.json`
    - `database/database.sql`
    - `files/...`
11. Tidak ada entry duplikat.
12. Tidak ada symlink/hardlink/special entry.
13. Tidak ada entry terenkripsi.
14. Jumlah file, ukuran ekstraksi, dan rasio kompresi dalam batas.
15. File final berada di private storage.
16. Metadata record sesuai artefak.

### 7.7 Aturan path guard

Tolak path yang:

- kosong
- mengandung null byte/control character
- absolut (`/`, `C:\`, `\\server\share`)
- mengandung backslash
- mengandung segmen `..`
- mengandung segmen `.` setelah normalisasi
- mengandung `//`
- memiliki drive prefix
- keluar dari root setelah `realpath`
- menunjuk symlink, device, FIFO, socket
- duplikat dengan entry lain

Gunakan `BackupPathGuard::assertSafeRelative(string $path): string` yang mengembalikan path ternormalisasi atau melempar `BackupException`.

### 7.8 Batas ZIP

Default dari pengaturan:

```text
max_upload_megabytes          256
max_expanded_megabytes        4096
max_files                     10000
max_compression_ratio         100
```

Pemeriksaan dilakukan dari central directory sebelum ekstraksi:

- `ZipArchive::numFiles`
- `statIndex($i)['size']` (uncompressed)
- `statIndex($i)['comp_size']`
- rasio `size / comp_size`
- external attributes untuk symlink

---

## 8. Enums, model, service, action, job, dan command

### 8.1 Enum

| Enum                               | Case                                                                                 |
| ---------------------------------- | ------------------------------------------------------------------------------------ |
| `App\Enums\BackupType`             | `Database`, `Full`                                                                   |
| `App\Enums\BackupSource`           | `Manual`, `Scheduled`, `Uploaded`, `PreRestore`                                      |
| `App\Enums\BackupStatus`           | `Queued`, `Running`, `Validating`, `Success`, `Failed`                               |
| `App\Enums\BackupValidationStatus` | `Pending`, `Passed`, `Failed`                                                        |
| `App\Enums\BackupRestoreStatus`    | `Queued`, `Preparing`, `Ready`, `Running`, `Success`, `Failed`, `Partial`, `Expired` |
| `App\Enums\BackupComponentResult`  | `Success`, `Failed`, `Skipped`                                                       |

Setiap enum memiliki `label(): string` yang membaca `lang/id/backups.php`, mengikuti pola `DocumentStatus`.

### 8.2 Service

| Service                  | Tanggung jawab                                                            | Method kunci                                                                                                |
| ------------------------ | ------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| `BackupSettingsService`  | Baca/tulis singleton setting, default, validasi nilai                     | `values()`, `model()`, `update(array, User)`                                                                |
| `BackupStorage`          | Disk final, temp, upload, staging, free space, stream                     | `disk()`, `tempPath()`, `archivePath()`, `uploadPath()`, `stagingPath()`, `freeBytes()`, `checksum()`       |
| `BackupPathGuard`        | Normalisasi dan validasi path                                             | `assertSafeRelative()`, `assertInsideRoot()`, `assertAllowedScope()`                                        |
| `BackupManifest`         | Bangun/parse manifest database dan full                                   | `build()`, `encode()`, `decodeSqlComment()`, `parseZip()`, `assertSupported()`                              |
| `BackupArchiveInspector` | Inspeksi ZIP/gzip, limit, checksum, entry berbahaya                       | `inspectSqlGz()`, `inspectZip()`, `assertEntrySafe()`                                                       |
| `CommandRunner`          | Wrapper Symfony Process agar bisa di-fake                                 | `run(array $command, array $env, ?string $input, int $timeout)`                                             |
| `DatabaseDumper`         | Dump koneksi aplikasi ke file                                             | `dump(string $outputPath, ?string $connection = null): DumpResult`                                          |
| `DatabaseRestorer`       | Restore file SQL ke koneksi aplikasi                                      | `restore(string $sqlPath, ?string $connection = null): RestoreResult`                                       |
| `BackupRetention`        | Hitung backup yang eligible dihapus                                       | `eligible(Backup $backup, string $now): bool`, `queryEligible()`                                            |
| `BackupHealth`           | Heartbeat scheduler/worker, binary, disk, batas upload PHP, storage lokal | `schedulerStatus()`, `workerStatus()`, `queueReady()`, `binaryStatus()`, `uploadLimit()`, `storageStatus()` |
| `BackupSafeError`        | Map exception ke kode/pesan aman                                          | `from(Throwable): array{code,message}`                                                                      |

### 8.3 Action

| Action                       | Tanggung jawab                                                                        |
| ---------------------------- | ------------------------------------------------------------------------------------- |
| `CreateBackupAction`         | Validasi input, cek kesiapan, buat record `queued`, dispatch `CreateBackupJob`, audit |
| `UploadBackupAction`         | Simpan upload ke karantina, buat record, validasi, pindahkan atau bersihkan           |
| `ValidateBackupAction`       | Validasi penuh artefak dan update metadata/status validasi                            |
| `PrepareRestoreAction`       | Buat operasi `backup_restores` status `queued`, dispatch `PrepareRestoreJob`, audit   |
| `CreateSafetyBackupAction`   | Buat backup `pre_restore`, validasi, set `protected_until`                            |
| `RestoreBackupAction`        | Validasi final confirmation, revalidate backup, dispatch `RestoreBackupJob`, audit    |
| `DeleteBackupAction`         | Cek status/lock, hapus file, hapus metadata, audit; kegagalan file tidak disamarkan   |
| `UpdateBackupSettingsAction` | Validasi silang, update, audit                                                        |
| `PruneBackupsAction`         | Hapus backup eligible; file gagal dihapus ditandai                                    |
| `VerifyBackupsAction`        | Cek file hilang/berubah, update `file_missing_at`/validasi, audit                     |
| `ReconcileBackupsAction`     | Laporkan orphan/missing; hapus orphan hanya dengan flag eksplisit                     |

### 8.4 Job

| Job                        | Queue     | Timeout | Tries | Tanggung jawab                                                  |
| -------------------------- | --------- | ------- | ----- | --------------------------------------------------------------- |
| `CreateBackupJob`          | `backups` | 3600    | 1     | Dump, manifest, zip/gzip, validasi, pindah final, status, audit |
| `PrepareRestoreJob`        | `backups` | 7200    | 1     | Safety backup + validasi, status `ready`, audit                 |
| `RestoreBackupJob`         | `backups` | 7200    | 1     | Lock, maintenance, restore DB/file, verifikasi, status, audit   |
| `BackupWorkerHeartbeatJob` | `backups` | 60      | 1     | Tulis heartbeat worker                                          |

Aturan job:

- `$tries = 1` untuk backup/restore; tidak ada retry otomatis setelah perubahan destruktif.
- `$failOnTimeout = true`.
- Lock diambil di dalam job, bukan di controller.
- Status dan audit ditulis pada setiap transisi penting.
- `finally` selalu membersihkan temp, lock, dan maintenance flag.

### 8.5 Command

| Command                 | Jadwal       | Tanggung jawab                                |
| ----------------------- | ------------ | --------------------------------------------- |
| `backups:heartbeat`     | setiap menit | Tulis heartbeat scheduler                     |
| `backups:run-scheduled` | setiap menit | Cek setting + slot, dispatch backup           |
| `backups:verify`        | harian 03:00 | Deteksi file hilang/berubah                   |
| `backups:prune`         | harian 03:30 | Retensi dan pembersihan upload invalid        |
| `backups:reconcile`     | manual       | Laporkan orphan/missing; opsi hapus orphan    |
| `backups:unlock`        | manual       | Bersihkan lock/flag macet (dengan konfirmasi) |

### 8.6 Exception dan pesan aman

`App\Exceptions\BackupException` memiliki:

- `code` internal: `storage_unavailable`, `disk_full`, `binary_missing`, `dump_failed`, `archive_invalid`, `checksum_mismatch`, `format_unsupported`, `path_unsafe`, `size_limit`, `file_missing`, `operation_in_progress`, `safety_backup_failed`, `db_restore_failed`, `file_restore_failed`, `verification_failed`, `queue_unavailable`.
- `safeMessage`: dipetakan dari `lang/id/backups.php` dan ditampilkan ke UI.
- Detail teknis hanya dicatat di log dengan kode + class exception; bukan stack trace lengkap ke UI.

---

## 9. Alur operasi

### 9.1 Backup manual

1. `POST /admin/backup`
   - FormRequest authorize `backups.create`.
   - Validasi `type` (`database`/`full`) dan `note` (max 500).
2. `CreateBackupAction`:
   - Cek `BackupHealth` (binary, disk, queue).
   - Cek tidak ada restore aktif.
   - Buat record `queued`, source `manual`, `created_by` user, `uuid`.
   - Audit `backup_started`.
   - Dispatch `CreateBackupJob` ke queue `backups`.
3. `CreateBackupJob`:
   - Ambil lock `backups:exclusive` dengan `block(300)`.
   - Set status `running`, `started_at`.
   - Bersihkan temp workspace.
   - Cek free disk.
   - Jika database: dump ke temp `.sql`, hitung checksum, buat manifest, sisipkan komentar, gzip.
   - Jika full: dump ke temp `database.sql`, enumerasi file scope, hitung checksum per file, buat manifest, zip.
   - Set status `validating`.
   - Validasi penuh (bagian 7.6).
   - Pindahkan artefak final ke `archives/{uuid}/...`.
   - Set status `success`, `file_size`, `checksum`, `validated_at`, `finished_at`, `duration_ms`.
   - Audit `backup_completed`.
   - Hapus temp.
4. Jika gagal:
   - Set status `failed`, `failure_code`, `failure_message` aman.
   - Hapus temp.
   - Audit `backup_failed`.
   - Jangan meninggalkan artefak final parsial.

### 9.2 Backup terjadwal

1. `backups:run-scheduled` membaca setting.
2. Jika `schedule_enabled` false → selesai.
3. Hitung slot:
   - database: `database:YYYY-MM-DD` di timezone setting.
   - full: `full:YYYY-Www` (ISO week) di timezone setting.
4. Cek waktu:
   - database: `H:i` sama dengan `database_time`.
   - full: hari sama dengan `full_day` dan `H:i` sama dengan `full_time`.
5. Jika slot sudah ada → selesai (idempotent).
6. Cek `BackupHealth::queueReady()`. Jika worker heartbeat basi (dan koneksi bukan `sync`), jangan dispatch; tulis log peringatan dan biarkan UI menyatakan worker belum siap. Jangan menumpuk job di antrean.
7. Buat record `queued`, source `scheduled`, `scheduled_slot` unik.
8. Dispatch `CreateBackupJob`.
9. Jika dispatch gagal, record `failed` + audit; slot tetap unik agar tidak spam.
10. Kegagalan satu job tidak menghentikan jadwal berikutnya.

### 9.3 Upload eksternal

1. `POST /admin/backup/unggah`
   - FormRequest authorize `backups.upload`.
   - Validasi nama asli: `.sql.gz` atau `.zip`.
   - Validasi MIME awal: gzip/zip.
   - Validasi ukuran maksimum dari `BackupSettingsService`.
2. Simpan file ke `uploads/{uuid}` dengan nama server-generated.
3. Buat record `uploaded`, status `validating`, `original_filename` tersanitasi.
4. `ValidateBackupAction`:
   - Jalankan validasi penuh.
   - Jika valid: pindahkan ke `archives/{uuid}/...`, status `success`, validation `passed`, audit `backup_uploaded`.
   - Jika invalid: hapus file, status `failed`, validation `failed`, `path` null, `failure_code`, audit `backup_upload_rejected`.
5. Upload **tidak pernah** memanggil restore.
6. File invalid tidak boleh memenuhi disk.

### 9.4 Download

1. `GET /admin/backup/{backup}/unduh`
2. Authorize `backups.download`.
3. Cek `isDownloadable()`: status `success`, validation `passed`, file ada, `file_missing_at` null.
4. Streaming dari disk `backups`:
   - `Content-Type`: `application/gzip` atau `application/zip`.
   - `Content-Disposition: attachment` dengan nama tersanitasi.
   - `X-Content-Type-Options: nosniff`.
   - `Cache-Control: private, no-store`.
5. Audit `backup_downloaded`.
6. Kegagalan storage → pesan aman, bukan path.

### 9.5 Restore dua tahap

#### Tahap 1 — Persiapan safety backup

1. `POST /admin/backup/{backup}/pemulihan/persiapan`
2. Authorize `backups.restore`.
3. Validasi backup: status `success`, validation `passed`, file ada.
4. Tolak jika ada operasi restore aktif untuk backup yang sama atau restore lain yang berjalan.
5. Buat record `backup_restores` status `queued`, `requested_by`, `expires_at = null`.
6. Dispatch `PrepareRestoreJob`.
7. Job:
   - Set status `preparing`.
   - Buat safety backup:
     - database restore → backup database `pre_restore`;
     - full restore → backup full `pre_restore`.
   - Validasi safety backup.
   - Set `safety_backup_id`, `protected_until = now + 30 hari`.
   - Set status `ready`, `confirmed_at = null`, `expires_at = now + 1 jam`.
   - Audit `restore_safety_created` dan `restore_ready`.
8. Jika safety backup gagal:
   - Status `failed`, `failure_code=safety_backup_failed`.
   - Tidak ada perubahan data.
   - Audit `restore_failed`.

#### Tahap 2 — Konfirmasi final dan restore

1. UI menampilkan detail backup, safety backup, peringatan, dan mengaktifkan tombol `Konfirmasi Pemulihan` hanya jika status `ready` dan belum kedaluwarsa.
2. `POST /admin/backup/{backup}/pemulihan`
   - Authorize `backups.restore`.
   - Validasi `confirmation` = `PULIHKAN DATA`.
   - Validasi `restore_id` milik backup ini, status `ready`, belum `expires_at`.
   - Revalidasi artefak backup tepat sebelum restore.
   - Set `confirmed_at`.
   - Dispatch `RestoreBackupJob`.
   - Audit `restore_requested`.
3. `RestoreBackupJob`:
   - Ambil lock `backups:exclusive` tanpa menunggu; jika gagal → `failed`, `operation_in_progress`.
   - Set status `running`, `started_at`.
   - Set flag `backups:restore-in-progress` (TTL 2 jam).
   - Restore database:
     - `.sql.gz` → stream dekompresi ke `mysql`;
     - `.zip` → ekstrak `database/database.sql` ke temp, verifikasi checksum, restore.
   - Set `database_result`.
   - Jika full restore:
     - ekstrak `files/...` ke staging privat;
     - validasi path dan checksum;
     - salin ke disk tujuan; file yang tidak ada di arsip tetap dipertahankan;
     - set `files_result`.
   - Verifikasi pasca-restore (bagian 9.7).
   - Set status `success` jika database dan file (jika ada) sukses dan verifikasi lulus.
   - Set `partial` jika database sukses tetapi file gagal.
   - Set `failed` jika database gagal.
   - Audit `restore_completed` / `restore_partial` / `restore_failed`.
   - `finally`: lepas flag maintenance, lepas lock, bersihkan temp.
4. Tidak ada retry otomatis setelah tahap destruktif dimulai.

### 9.6 Kebijakan file full restore

- File yang ada di arsip: diganti.
- File yang tidak ada di arsip: **dipertahankan**.
- File yang gagal disalin: dicatat; restore menjadi `partial`.
- Tidak ada penghapusan file arbitrary di luar scope.
- Staging privat, validasi path, dan checksum sebelum penyalinan.
- Safety full backup dibuat sebelum perubahan, sehingga file yang diganti dapat dipulihkan.

### 9.7 Verifikasi pasca-restore

Wajib lulus untuk status `success`:

1. Koneksi database aplikasi dapat digunakan (`DB::purge()` lalu `SELECT 1`).
2. Tabel inti tersedia:
   - `users`
   - `roles`
   - `permissions`
   - `site_settings`
   - `media`
   - `posts`
   - `documents`
   - `migrations`
3. `site_settings` dapat dibaca melalui `SiteSettingsService`.
4. Untuk full restore:
   - direktori scope ada dan dapat dibaca;
   - jumlah file yang dipulihkan sesuai manifest;
   - checksum file yang dipulihkan cocok.
5. `migrations` table ada; jika versi backup lebih lama dari kode aplikasi, simpan peringatan di `verification` (tidak auto-migrate).
6. Cache permission Spatie dan cache site settings dibersihkan secara spesifik; tidak `cache:clear` global.

`verification` JSON menyimpan hasil per pemeriksaan tanpa path absolut.

### 9.8 Retensi

`PruneBackupsAction` menghapus:

| Source                   | Syarat                                                               |
| ------------------------ | -------------------------------------------------------------------- |
| `scheduled` + `database` | `created_at` lebih lama dari `retention_daily_days`                  |
| `scheduled` + `full`     | `created_at` lebih lama dari `retention_weekly_weeks`                |
| `uploaded` + `failed`    | `created_at` lebih lama dari `retention_invalid_upload_days`         |
| `pre_restore`            | `protected_until` sudah lewat dan tidak direferensikan restore aktif |

Tidak pernah menghapus:

- `source = manual`
- backup dengan status `queued`/`running`/`validating`
- backup yang direferensikan `backup_restores` aktif (`queued`, `preparing`, `ready`, `running`)
- safety backup yang masih `protected_until`

Penghapusan:

1. Hapus file.
2. Jika file gagal dihapus: update `failure_code=delete_failed`, `failure_message`, audit `backup_delete_failed`; jangan hapus metadata.
3. Jika file tidak ada: lanjut hapus metadata.
4. Hapus baris.
5. Audit `backup_retention_deleted` atau `backup_deleted`.

Sebelum retensi, command yang sama menandai operasi restore `ready` yang melewati `expires_at` sebagai `expired`, menulis audit `restore_expired`, dan melepas proteksi safety backup sesuai kebijakan. Ini mencegah operasi yang tidak pernah dikonfirmasi memblokir restore lain selamanya.

### 9.9 Rekonsiliasi

`backups:reconcile`:

- Scan `archives` pada disk `backups`.
- Bandingkan dengan `backups.path`.
- Laporkan:
  - file tanpa metadata (orphan);
  - metadata tanpa file (missing).
- Default: hanya laporan.
- `--delete-orphans`: hapus file orphan setelah konfirmasi eksplisit.
- Orphan tidak otomatis dianggap backup tepercaya.

### 9.10 File hilang/berubah

- `backups:verify` harian:
  - cek `exists` dan `size`;
  - missing → set `file_missing_at`, audit;
  - file kembali ada dan ukuran cocok → clear `file_missing_at`;
  - size berbeda → `validation_status=failed`, `failure_code=file_changed`, audit.
- Download/restore selalu cek file pada saat akses.
- Backup dengan `file_missing_at` tidak dapat diunduh/direstore sampai verifikasi berikutnya membersihkannya.

---

## 10. HTTP layer

### 10.1 Route

Tambahkan di `routes/web.php` dalam group admin:

```php
Route::get('/backup', [BackupController::class, 'index'])
    ->name('backups.index');

Route::get('/backup/pengaturan', [BackupSettingsController::class, 'edit'])
    ->name('backups.settings.edit');

Route::put('/backup/pengaturan', [BackupSettingsController::class, 'update'])
    ->name('backups.settings.update');

Route::post('/backup', [BackupController::class, 'store'])
    ->name('backups.store');

Route::post('/backup/unggah', [BackupUploadController::class, 'store'])
    ->name('backups.upload');

Route::get('/backup/{backup}', [BackupController::class, 'show'])
    ->whereNumber('backup')
    ->name('backups.show');

Route::get('/backup/{backup}/unduh', [BackupDownloadController::class, 'download'])
    ->whereNumber('backup')
    ->name('backups.download');

Route::post('/backup/{backup}/pemulihan/persiapan', [BackupRestoreController::class, 'prepare'])
    ->whereNumber('backup')
    ->name('backups.restore.prepare');

Route::post('/backup/{backup}/pemulihan', [BackupRestoreController::class, 'store'])
    ->whereNumber('backup')
    ->name('backups.restore');

Route::delete('/backup/{backup}', [BackupController::class, 'destroy'])
    ->whereNumber('backup')
    ->name('backups.destroy');
```

Catatan:

- Route statis (`/backup/pengaturan`, `/backup/unggah`) ditempatkan sebelum `{backup}`.
- `{backup}` dibatasi angka.
- Semua mutasi memakai POST/PUT/DELETE.
- Setelah route berubah, jalankan `php artisan wayfinder:generate --with-form`.

### 10.2 Policy

`App\Policies\BackupPolicy`:

```php
public function viewAny(User $user): bool
public function view(User $user, Backup $backup): bool
public function create(User $user): bool
public function download(User $user, Backup $backup): bool
public function upload(User $user): bool
public function restore(User $user, Backup $backup): bool
public function delete(User $user, Backup $backup): bool
public function settings(User $user): bool
```

Aturan:

- `download` = permission + `isDownloadable()`.
- `restore` = permission + `isRestorable()`.
- `delete` = permission + tidak direferensikan restore aktif.
- `settings` = `backups.settings`.
- Super Admin tetap lolos melalui `Gate::before`, tetapi Policy tetap dipanggil untuk user lain.

### 10.3 FormRequest

| FormRequest                   | Authorize                        | Validasi                                                                       |
| ----------------------------- | -------------------------------- | ------------------------------------------------------------------------------ |
| `StoreBackupRequest`          | `can('create', Backup::class)`   | `type` in `database,full`; `note` nullable string max 500                      |
| `UploadBackupRequest`         | `can('upload', Backup::class)`   | file wajib; nama `.sql.gz`/`.zip`; MIME; max dari setting                      |
| `PrepareRestoreRequest`       | `can('restore', $backup)`        | backup valid; tidak ada restore aktif                                          |
| `RestoreBackupRequest`        | `can('restore', $backup)`        | `restore_id` valid; `confirmation` = `PULIHKAN DATA`                           |
| `UpdateBackupSettingsRequest` | `can('settings', Backup::class)` | boolean, `date_format:H:i`, `timezone`, rentang retensi/limit, validasi silang |

Validasi silang pengaturan:

- Jika `schedule_enabled` true, minimal satu dari `database_enabled`/`full_enabled` true.
- Jika `full_enabled` true, `full_day` 0–6 dan `full_time` valid.
- `max_expanded_megabytes` >= `max_upload_megabytes`.
- `max_compression_ratio` >= 1.
- `retention_*` dalam batas wajar.

### 10.4 Controller

| Controller                 | Method     | Tanggung jawab                                               |
| -------------------------- | ---------- | ------------------------------------------------------------ |
| `BackupController`         | `index`    | Gate viewAny, filter/sort/pagination, ringkasan, readiness   |
|                            | `show`     | Gate view, load restore history, cek availability            |
|                            | `store`    | FormRequest, panggil `CreateBackupAction`, redirect detail   |
|                            | `destroy`  | Gate delete, panggil `DeleteBackupAction`, redirect daftar   |
| `BackupDownloadController` | `download` | Gate download, streaming, audit                              |
| `BackupUploadController`   | `store`    | FormRequest, panggil `UploadBackupAction`, redirect detail   |
| `BackupRestoreController`  | `prepare`  | FormRequest, panggil `PrepareRestoreAction`, redirect detail |
|                            | `store`    | FormRequest, panggil `RestoreBackupAction`, redirect detail  |
| `BackupSettingsController` | `edit`     | Gate settings, kirim setting + readiness                     |
|                            | `update`   | FormRequest, panggil `UpdateBackupSettingsAction`, redirect  |

Controller tidak berisi dump, zip, validasi path, atau restore logic.

### 10.5 Filter daftar

Query parameter:

```text
search    (nama file / catatan)
type      database|full
source    manual|scheduled|uploaded|pre_restore
status    queued|running|validating|success|failed
validasi  pending|passed|failed
dari      YYYY-MM-DD
sampai    YYYY-MM-DD
sort      created_at|file_size|type|status
direction asc|desc
page
per_page  10|15|25|50
```

Gunakan `SortsAdminList` untuk whitelist sort dan `perPage()`.

### 10.6 Resource

`BackupListResource`:

```text
id, uuid, type, type_label, source, source_label,
status, status_label, validation_status, validation_label,
file_size, duration_ms, created_at, validated_at,
created_by_label, scheduled_slot, is_available, file_missing_at,
can: { download, restore, delete }
```

`BackupResource` (detail) menambahkan:

```text
checksum, format_version, note, failure_code, failure_message,
started_at, finished_at, protected_until,
restores: [BackupRestoreResource],
readiness: { scheduler, worker, binary, upload_limit, storage }
```

`BackupRestoreResource`:

```text
id, uuid, status, status_label, database_result, files_result,
safety_backup_id, safety_backup_status, requested_by_label,
created_at, confirmed_at, expires_at, started_at, finished_at,
failure_code, failure_message, verification
```

`BackupSettingsResource`:

```text
settings, timezones, readiness
```

Tidak ada absolute path, SQL, stack trace, atau isi arsip di resource.

### 10.7 Middleware write-block

`App\Http\Middleware\BlockWritesDuringRestore`:

- Cek `Cache::get('backups:restore-in-progress')`.
- Jika aktif dan method bukan `GET`, `HEAD`, `OPTIONS` → `abort(503, 'Pemulihan sedang berlangsung. Coba lagi setelah selesai.')`.
- Ditambahkan ke grup `web` setelah middleware auth/Inertia.
- Tidak memblokir job CLI yang sedang restore.
- Flag selalu dihapus di `finally`; TTL 2 jam mencegah lock permanen bila proses mati.

---

## 11. Integrasi Audit Log

### 11.1 Katalog event

| Action                     | Aktor       | Kapan                                |
| -------------------------- | ----------- | ------------------------------------ |
| `backup_started`           | user/system | Record backup dibuat                 |
| `backup_completed`         | user/system | Backup sukses                        |
| `backup_failed`            | user/system | Backup gagal                         |
| `backup_validated`         | system      | Validasi lulus                       |
| `backup_validation_failed` | system      | Validasi gagal                       |
| `backup_uploaded`          | user        | Upload valid                         |
| `backup_upload_rejected`   | user        | Upload invalid                       |
| `backup_downloaded`        | user        | Download                             |
| `backup_deleted`           | user        | Hapus manual                         |
| `backup_delete_failed`     | user/system | File gagal dihapus                   |
| `backup_retention_deleted` | system      | Retensi                              |
| `backup_settings_updated`  | user        | Pengaturan berubah                   |
| `restore_requested`        | user        | Persiapan restore                    |
| `restore_safety_created`   | system      | Safety backup valid                  |
| `restore_ready`            | system      | Menunggu konfirmasi final            |
| `restore_rejected`         | user        | Konfirmasi/permission/validasi gagal |
| `restore_started`          | system      | Restore destruktif mulai             |
| `restore_completed`        | system      | Restore sukses                       |
| `restore_partial`          | system      | Database sukses, file gagal          |
| `restore_failed`           | system      | Restore gagal                        |
| `restore_expired`          | system      | Operasi `ready` kedaluwarsa          |

### 11.2 Metadata allowlist

Hanya key berikut yang boleh masuk:

```text
backup_id, operation_id, type, source, status, file_size,
duration_ms, error_code, safety_backup_id, count, ids, requested_by
```

Tidak boleh:

- path absolut
- isi SQL/arsip
- checksum penuh? checksum boleh, tetapi tidak wajib
- credential/token
- stack trace
- signed URL

### 11.3 Aktor

- Aksi user: `AuditLogger::recordModel()` dengan model `Backup`/`BackupSetting`.
- Aksi job/scheduler: `AuditLogger::system()` dengan `actor_type=system`.
- Restore setelah database diganti: gunakan `system()` dan metadata `requested_by`, karena user asal mungkin tidak ada di database hasil restore.
- `idempotency_key` untuk event job yang bisa terpicu ulang, misalnya `backup:{uuid}:completed`.

### 11.4 Batas audit saat restore

Karena `audit_events` berada di database yang sama:

- Event sebelum restore dapat hilang saat database dikembalikan.
- Event setelah restore ditulis ke database hasil restore.
- Sebelum restore destruktif, tulis satu baris log aman ke `storage/logs/laravel.log`:
  - `backup.restore.starting`
  - `backup.restore.finished`
- Jangan mengklaim audit immutable.
- Audit eksternal yang tahan rollback tetap di luar scope.

---

## 12. Frontend

### 12.1 Sidebar

Tambahkan di `ADMINISTRATION_ENTRIES`:

```ts
{
    title: 'Backup & Pemulihan',
    href: backupsIndex(),
    icon: DatabaseBackup,
    ability: 'backups.view',
}
```

Ikon `DatabaseBackup` dari `lucide-react`. Pengaturan backup diakses dari tombol di halaman daftar, bukan item sidebar terpisah.

### 12.2 Halaman

| File                                                      | Inertia name              | Isi                                                        |
| --------------------------------------------------------- | ------------------------- | ---------------------------------------------------------- |
| `resources/js/features/admin/backup/pages/index.tsx`      | `admin/backup`            | Ringkasan, kesiapan, filter, tabel, pagination, dialog     |
| `resources/js/features/admin/backup/pages/show.tsx`       | `admin/backup/show`       | Detail, validasi, checksum, riwayat restore, safety backup |
| `resources/js/features/admin/backup/pages/pengaturan.tsx` | `admin/backup/pengaturan` | Form pengaturan + panel kesiapan                           |

### 12.3 Komponen

| File                                    | Fungsi                                                            |
| --------------------------------------- | ----------------------------------------------------------------- |
| `components/create-backup-dialog.tsx`   | Pilih tipe, penjelasan isi/pengecualian, catatan, peringatan      |
| `components/upload-backup-dialog.tsx`   | Upload file, batas ukuran, progress, error                        |
| `components/prepare-restore-dialog.tsx` | Detail backup, peringatan, tombol siapkan safety backup           |
| `components/confirm-restore-dialog.tsx` | Status safety backup, peringatan, checkbox, frasa `PULIHKAN DATA` |
| `components/backup-status-badge.tsx`    | Status teks + ikon, bukan warna saja                              |
| `components/backup-type-badge.tsx`      | Database/Full                                                     |
| `components/backup-summary.tsx`         | Backup terakhir, status jadwal, storage                           |
| `components/backup-filters.tsx`         | Filter tipe/sumber/status/tanggal/pencarian                       |
| `components/backup-settings-form.tsx`   | Form pengaturan                                                   |
| `types.ts`                              | Tipe row/detail/settings/readiness                                |

Reuse existing:

- `DataTable`
- `ListPagination`
- `ListEmpty`
- `ListHeader`
- `ListToolbar`
- `FilterSelect`
- `ResetFilters`
- `RowActions`
- `DeleteDialog`
- `ConfirmDialog`
- `Badge`
- `Button`
- `Text`
- `Dialog`
- `Switch`
- `Input`
- `Select`

### 12.4 Tipe frontend

```ts
type BackupType = 'database' | 'full';
type BackupSource = 'manual' | 'scheduled' | 'uploaded' | 'pre_restore';
type BackupStatus = 'queued' | 'running' | 'validating' | 'success' | 'failed';
type BackupValidationStatus = 'pending' | 'passed' | 'failed';
type RestoreStatus =
    | 'queued'
    | 'preparing'
    | 'ready'
    | 'running'
    | 'success'
    | 'failed'
    | 'partial'
    | 'expired';
```

`BackupRow`, `BackupDetail`, `BackupRestoreRow`, `BackupSettings`, `BackupReadiness`, `BackupFilters` mengikuti resource backend.

### 12.5 Polling dan status

- Jika ada backup `queued/running/validating` atau restore `queued/preparing/ready/running`, halaman melakukan partial reload setiap 5 detik:
  - `router.reload({ only: ['backups', 'summary', 'readiness'] })`
  - atau `only: ['backup', 'restores']` di halaman detail.
- Polling berhenti saat tidak ada status aktif.
- Tombol retry manual tersedia saat error.
- Progress hanya ditampilkan jika statusnya benar-benar terukur; tidak ada progress bar palsu.

### 12.6 UX dan accessibility

- Dialog memiliki title, description, focus management.
- Error terhubung ke field.
- Status memakai teks + ikon, bukan warna saja.
- Semua aksi keyboard-accessible.
- Tombol restore final disabled sampai:
  - status restore `ready`;
  - safety backup valid;
  - frasa `PULIHKAN DATA` diketik benar;
  - checkbox pemahaman dicentang.
- Peringatan restore:
  - data setelah waktu backup dapat hilang;
  - database-only tidak memulihkan file upload;
  - full restore mengganti file yang ada di arsip dan mempertahankan file lain;
  - layanan dapat terganggu sementara.
- Peringatan storage lokal: kehilangan server dapat menghilangkan backup.
- Peringatan scheduler/worker belum siap.
- Peringatan full backup: ukuran dan durasi lebih besar.

### 12.7 Salinan UI Bahasa Indonesia

Contoh label:

```text
Backup & Pemulihan
Backup Database
Backup Lengkap
Buat Backup
Unggah Backup
Unduh
Siapkan Pemulihan
Konfirmasi Pemulihan
Hapus Backup
Menunggu antrean
Sedang dibuat
Memvalidasi
Berhasil
Gagal
Tidak tersedia
Safety backup berhasil dibuat
Pemulihan sedang berlangsung
```

---

## 13. Keamanan dan operasional

### 13.1 Threat checklist

| Ancaman                       | Mitigasi                                                                                         |
| ----------------------------- | ------------------------------------------------------------------------------------------------ |
| Unauthorized/IDOR             | Policy + Gate di setiap route; test tiap permission dan ID                                       |
| Public exposure               | Disk privat; tidak ada URL; download streaming                                                   |
| Path traversal                | `BackupPathGuard`; normalisasi; `realpath`; tolak `..`, absolut, backslash, null byte            |
| ZIP bomb                      | Batas ukuran ekstraksi, jumlah file, rasio kompresi; periksa central directory sebelum ekstraksi |
| Symlink/hardlink              | Tolak external attributes symlink dan special entry                                              |
| SQL rusak/berbahaya           | Validasi header/footer, checksum, hanya binary `mysql` dari koneksi aplikasi                     |
| Shell injection               | Argumen array; tanpa shell concatenation; input user tidak masuk command                         |
| Secret bocor                  | Tidak pernah menyertakan `.env`; log aman; UI hanya pesan kode                                   |
| Race condition                | Lock `backups:exclusive`; status record; FK restrict                                             |
| CSRF                          | Route mutasi POST/DELETE + middleware web                                                        |
| Upload overwrite              | Nama server-generated; tidak memakai nama asli sebagai path                                      |
| Restore parsial               | Status `partial`; safety backup; verifikasi                                                      |
| Disk exhaustion               | Cek free space; batas upload/ekstraksi; cleanup temp                                             |
| File berubah setelah validasi | Revalidasi checksum tepat sebelum restore                                                        |
| Duplicate jobs                | `scheduled_slot` unik; `tries=1`; lock                                                           |
| Manifest diubah bersama arsip | Checksum artefak disimpan di DB; batas anti-tamper didokumentasikan                              |
| Penulisan di luar root        | Path guard + staging + validasi scope                                                            |
| Arbitrary storage path        | Storage hanya dari config; UI tidak menerima path                                                |
| Maintenance tidak pulih       | `finally` + TTL + `backups:unlock`                                                               |
| Scheduler/worker tidak aktif  | Heartbeat + status UI jujur                                                                      |

### 13.2 Batas anti-tamper

- SHA-256 internal membuktikan integritas terhadap kerusakan/modifikasi tidak sengaja.
- Penyerang yang dapat mengubah arsip **dan** database **dan** manifest dapat memalsukan checksum.
- Checksum/tanda tangan terpisah di storage yang terlindungi berada di luar scope.
- Dokumentasi tidak boleh mengklaim checksum internal sebagai anti-tamper penuh.

### 13.3 Storage dan kapasitas

- Disk final: `backups` (privat).
- Temp workspace: `storage/app/private/backup-temp` (privat, lokal).
- Upload quarantine: `uploads/`.
- Staging restore: `backup-temp/staging/`.
- Arsip final: `archives/{uuid}/`.
- Cek free space untuk temp dan final bila disk lokal.
- `low_disk_threshold_megabytes` default 1024 MB.
- Jika disk penuh di tengah operasi: status `failed`, cleanup, pesan aman.
- External object storage belum dikonfigurasi; abstraction siap tetapi tidak diklaim selesai.

### 13.4 Kebutuhan hosting

Operator wajib memverifikasi:

1. `mysqldump` dan `mysql` tersedia dan dapat dieksekusi.
2. Privilege database cukup untuk dump dan restore.
3. Queue worker berjalan untuk `backups` dan `default`.
4. Cron `schedule:run` berjalan setiap menit.
5. `upload_max_filesize`, `post_max_size`, `max_input_time`, `memory_limit` cukup.
6. Batas reverse proxy/web server untuk upload 256 MB.
7. Ruang disk untuk temp + arsip + safety backup.
8. Permission filesystem untuk `storage/app/private`.
9. Timezone server dan `schedule:run` tidak mengubah perhitungan karena command memakai timezone setting.

---

## 14. Rencana test

### 14.1 Unit

- `BackupPathGuardTest`
  - path normal
  - `../`
  - absolute path
  - drive prefix
  - backslash
  - null byte
  - duplicate segment
  - symlink
  - path di luar root
- `BackupManifestTest`
  - manifest database
  - manifest full
  - format/version salah
  - field wajib hilang
  - checksum salah
  - JSON rusak
- `BackupArchiveInspectorTest`
  - gzip valid/rusak
  - zip valid/rusak
  - manifest hilang
  - entry di luar scope
  - entry duplikat
  - symlink
  - entry terenkripsi
  - rasio kompresi melebihi batas
  - jumlah file/ukuran melebihi batas
- `BackupRetentionTest`
  - scheduled database 14 hari
  - scheduled full 4 minggu
  - manual tidak dihapus
  - upload invalid 7 hari
  - safety backup dilindungi
  - backup aktif tidak dihapus
- `BackupStatusTest`
  - transisi status
  - availability
  - file missing
- `BackupSafeErrorTest`
  - mapping exception ke kode/pesan aman
  - tidak mengandung path/stack trace

### 14.2 Feature — authorization

- Guest ditolak.
- Setiap permission diuji terpisah:
  - `backups.view`
  - `backups.create`
  - `backups.download`
  - `backups.upload`
  - `backups.restore`
  - `backups.delete`
  - `backups.settings`
- Download tidak memberi restore.
- Upload tidak memberi restore.
- Delete/settings terpisah.
- IDOR: ID backup diganti; user tanpa permission tidak mendapat data.
- Super Admin lolos sesuai `Gate::before`.

### 14.3 Feature — backup

- Backup database menghasilkan `.sql.gz` yang dapat dibaca.
- Full backup menghasilkan `.zip` dengan manifest, database, dan file scope.
- File di luar scope tidak masuk.
- `.env`, log, cache, session, source code tidak masuk.
- Private disk digunakan.
- Checksum cocok.
- File temp dibersihkan pada sukses dan gagal.
- File gagal tidak dapat diunduh/direstore.
- Job failure menulis status `failed` dan audit.
- Idempotensi scheduler.
- Queue dispatch memakai koneksi `backups`.

### 14.4 Feature — upload/validasi

- Upload valid diterima.
- Upload invalid ditolak dan file dibersihkan.
- Ekstensi palsu ditolak.
- Manifest hilang/rusak ditolak.
- Checksum mismatch ditolak.
- Format version tidak didukung ditolak.
- Path traversal/symlink/special entry/duplikasi ditolak.
- Batas ukuran/jumlah file/expanded size/ratio ditolak.
- Upload tidak langsung restore.
- Upload invalid tidak memenuhi disk.

### 14.5 Feature — restore

- Permission wajib.
- Frasa konfirmasi wajib.
- Backup invalid/hilang ditolak.
- Persiapan safety backup wajib.
- Safety backup gagal → restore dibatalkan.
- Restore tidak berjalan sebelum status `ready`.
- Lock mencegah concurrent restore.
- Database restore mengubah database.
- Full restore mengubah database + file.
- File yang tidak ada di arsip dipertahankan.
- Path entry tidak keluar root.
- Database gagal → `failed`.
- Database sukses + file gagal → `partial`.
- Lock/maintenance flag dilepas pada exception.
- Audit event tercatat.
- Tidak ada secret di response/log.
- Operasi `ready` kedaluwarsa ditolak.

### 14.6 Feature — scheduler/retensi/pengaturan

- Jadwal aktif membuat job pada slot yang benar.
- Jadwal nonaktif tidak membuat job.
- Timezone dihormati.
- Trigger berulang tidak menduplikasi.
- Kegagalan satu job tidak menghentikan jadwal berikutnya.
- Retensi hanya menghapus eligible.
- Backup aktif dan safety backup tidak dihapus.
- File deletion failure tidak disamarkan.
- Pengaturan divalidasi silang.
- Audit perubahan pengaturan.

### 14.7 Integration — restore nyata

File: `tests/Integration/Backup/RestoreIntegrationTest.php`

- Tambahkan `Integration` ke `tests/Pest.php` (`pest()->extend(TestCase::class)->in('Feature', 'Unit', 'Integration')`), **tanpa** `RefreshDatabase` untuk `Integration`.
- Gunakan database `website_paroki_hspmtb_restore_test` dari env `BACKUP_RESTORE_TEST_DATABASE`.
- Jika database tidak dapat dibuat/diakses: `markTestSkipped` dengan alasan jelas.
- Alur:
  1. Buat database uji.
  2. Jalankan migration.
  3. Insert marker row.
  4. Dump dengan `DatabaseDumper`.
  5. Hapus marker/tabel.
  6. Restore dengan `DatabaseRestorer`.
  7. Assert marker row kembali.
  8. Drop database uji.
- Jangan menyentuh database test/dev/produksi.
- Hasil skip dilaporkan, bukan diklaim lulus.

### 14.8 E2E

File: `tests/e2e/backup.spec.ts`

- Empty state.
- Daftar dan filter.
- Buat backup database (dengan `BACKUPS_QUEUE_CONNECTION=sync`).
- Status berubah menjadi berhasil.
- Download.
- Upload invalid ditolak.
- Dialog persiapan restore dan konfirmasi.
- Pengaturan tersimpan.
- Permission menu.
- Loading/error state.

Konfigurasi Playwright:

- Tambahkan `BACKUPS_QUEUE_CONNECTION: 'sync'` pada `webServer.env` agar job berjalan inline di E2E.
- Jangan menguji restore destruktif penuh di E2E tanpa fixture terisolasi.
- Bersihkan backup yang dibuat test melalui UI atau command.

### 14.9 Quality gate

```bash
php artisan wayfinder:generate --with-form
composer ci:check
npm run e2e
```

- `composer ci:check` menjalankan `npm check`, `types:check`, `test:unit`, Pint, PHPStan, dan `php artisan test`.
- MySQL harus berjalan.
- Test integration restore boleh skip jika DB uji tidak tersedia; laporkan.
- Jangan menyatakan selesai jika test penting gagal atau tidak dijalankan.

---

## 15. Urutan implementasi

### Phase 0 — Discovery

- [x] Baca spesifikasi, `AGENTS.md`, arsitektur, PRD, keputusan, roadmap, deployment.
- [x] Periksa storage, upload, DB, permission, audit, queue, scheduler, admin navigation.
- [x] Periksa `mysqldump`, privilege lokal, ekstensi PHP, batas upload.
- [x] Identifikasi package/migration yang dibutuhkan.
- [x] Catat risiko dan keputusan.

### Phase 1 — Fondasi

- [ ] Migration `backups`, `backup_restores`, `backup_settings`.
- [ ] Enum, model, factory.
- [ ] `config/backups.php`, disk `backups`, queue `backups`.
- [ ] Permission seeder + update `RbacFoundationTest`.
- [ ] `BackupPolicy` + route skeleton.
- [ ] `BackupSettingsService`, `BackupStorage`, `BackupPathGuard`.
- [ ] Shared abilities + sidebar entry.
- [ ] Test fondasi + authorization.

**Gate:** migration jalan, permission seeded, guest/tanpa permission ditolak, `composer ci:check` bagian fondasi hijau.

### Phase 2 — Backup manual

- [ ] `CommandRunner` + `DatabaseDumper`.
- [ ] `BackupManifest` + `BackupArchiveInspector`.
- [ ] `CreateBackupAction` + `CreateBackupJob`.
- [ ] Backup database `.sql.gz`.
- [ ] Full backup `.zip` + file scope.
- [ ] Validasi + checksum + private storage.
- [ ] Halaman daftar/detail + download.
- [ ] Audit event.
- [ ] Test backup DB/full, cleanup, idempotency.

**Gate:** DB backup dan full backup terbentuk di private storage, manifest/checksum valid, file gagal tidak bisa diunduh, audit tercatat.

### Phase 3 — Upload dan restore

- [ ] Upload quarantine + validasi penuh.
- [ ] `PrepareRestoreAction` + `PrepareRestoreJob`.
- [ ] `RestoreBackupAction` + `RestoreBackupJob`.
- [ ] Safety backup + lock eksklusif.
- [ ] `BlockWritesDuringRestore`.
- [ ] Restore database + full file restore via staging.
- [ ] Verifikasi pasca-restore + status `partial`.
- [ ] Security/recovery tests.
- [ ] Integration test restore nyata.

**Gate:** restore tanpa permission/konfirmasi/safety backup ditolak; concurrent restore ditolak; path traversal/symlink ditolak; partial failure tidak dilaporkan sukses; lock dan flag maintenance selalu dilepas.

### Phase 4 — Scheduler, retensi, pengaturan

- [ ] Halaman pengaturan + validasi.
- [ ] Heartbeat scheduler/worker.
- [ ] `RunScheduledBackups`.
- [ ] `PruneBackups`, `VerifyBackups`, `ReconcileBackups`, `UnlockBackups`.
- [ ] Retensi dan proteksi safety backup.
- [ ] Test timezone, duplicate slot, retensi.

**Gate:** jadwal aktif membuat backup pada slot yang benar; trigger berulang tidak menduplikasi; jadwal nonaktif tidak membuat job; retensi tidak menghapus backup yang dilindungi.

### Phase 5 — Hardening

- [ ] Audit keamanan menyeluruh.
- [ ] Uji arsip rusak, ZIP bomb, symlink, file besar, disk penuh, storage failure.
- [ ] E2E relevan.
- [ ] `docs/backup-recovery.md`.
- [ ] `docs/DECISIONS.md` D-80+.
- [ ] `docs/DEVELOPMENT.md` worker/cron.
- [ ] Laporan akhir: file berubah, test, konfigurasi manual, risiko.

**Gate:** `composer ci:check` lulus; E2E backup lulus; dokumentasi operasional tersedia; tidak ada secret di arsip/UI/log.

---

## 16. Definition of Done

- [ ] Repository dan deployment telah diperiksa.
- [ ] Backup database `.sql.gz` dapat dibuat dan divalidasi.
- [ ] Full backup `.zip` memuat database, file upload dalam scope, dan manifest.
- [ ] Private storage dan SHA-256 diterapkan.
- [ ] Daftar/detail/status/filter/pagination berfungsi.
- [ ] Download memerlukan authorization.
- [ ] Upload eksternal divalidasi dan tidak langsung restore.
- [ ] Path traversal, symlink, dan archive expansion berlebihan ditolak.
- [ ] Restore database dan full restore mengikuti urutan keselamatan.
- [ ] Safety backup berhasil diverifikasi sebelum restore.
- [ ] Lock/concurrency diterapkan.
- [ ] Verifikasi pasca-restore dan partial failure ditangani.
- [ ] Scheduler/queue sesuai hosting nyata dan status setup terlihat.
- [ ] Retensi/penghapusan mengikuti kebijakan.
- [ ] Semua permission diuji backend.
- [ ] Audit Log existing terintegrasi.
- [ ] Tidak ada secret di backup, UI, atau log.
- [ ] Dokumentasi operasional dan keterbatasan hosting tersedia.
- [ ] Test relevan dan quality gate lulus atau kegagalan dilaporkan eksplisit.

---

## 17. Risiko dan keterbatasan

1. **Produksi belum diverifikasi.** Scheduler, worker, `mysqldump`, privilege DB, cron, dan batas upload harus diverifikasi operator. UI menampilkan status nyata.
2. **Restore database tidak atomic.** MySQL DDL auto-commit. Kegagalan di tengah menghasilkan `failed`/`partial`; recovery melalui safety backup.
3. **Audit Log ikut ter-rollback.** Event sebelum restore dapat hilang. Jangan mengklaim audit immutable.
4. **Backup lokal tidak melindungi dari kehilangan server.** External object storage belum dikonfigurasi.
5. **Metadata backup ada di DB yang sama.** Jika DB hilang total, arsip tetap ada tetapi tidak otomatis dipercaya; operator memakai manifest/runbook.
6. **Restore lintas versi aplikasi.** Migration yang lebih baru dapat hilang dari tabel `migrations`; verifikasi memberi peringatan, tidak auto-migrate.
7. **Upload lokal >2 MB** ditolak PHP sampai `upload_max_filesize`/`post_max_size` dinaikkan.
8. **Anti-tamper terbatas.** Checksum internal tidak melindungi dari penyerang yang dapat mengubah arsip dan database.
9. **Full backup dapat besar.** Durasi dan kebutuhan disk harus diuji pada dataset nyata.
10. **Restore full tidak menghapus file di luar arsip.** Hasil bukan cermin penuh; ini keputusan keamanan yang disengaja.

---

## 18. Konfigurasi manual operator

### 18.1 Setelah deploy

```bash
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder --force
php artisan config:cache
php artisan route:cache
```

Jika route berubah di lingkungan build:

```bash
php artisan wayfinder:generate --with-form
npm run build
```

### 18.2 Worker

```bash
php artisan queue:work --queue=backups,default --tries=1 --timeout=7200
```

Gunakan Supervisor/systemd sesuai hosting. Pastikan `retry_after` (7300) lebih besar dari `timeout` (7200).

### 18.3 Scheduler

```cron
* * * * * cd /path/ke/aplikasi && php artisan schedule:run >> /dev/null 2>&1
```

### 18.4 PHP dan web server

- `upload_max_filesize=256M`
- `post_max_size=260M`
- `max_input_time=600`
- `memory_limit=512M` (minimal untuk inspeksi ZIP)
- Batas body upload reverse proxy minimal 260 MB.
- Timeout web server untuk upload minimal 600 detik.

### 18.5 Database privilege

Dump:

- `SELECT`
- `SHOW VIEW`
- `TRIGGER`
- `EVENT` (jika ada event)
- `LOCK TABLES` (jika tidak memakai `--skip-lock-tables`)

Restore:

- `CREATE`, `DROP`, `ALTER`
- `INSERT`, `UPDATE`, `DELETE`
- `INDEX`, `REFERENCES`
- `CREATE VIEW`, `SHOW VIEW`
- `CREATE ROUTINE`, `ALTER ROUTINE`
- `TRIGGER`

### 18.6 Verifikasi manual

1. Buat backup database.
2. Unduh dan cek file dapat dibuka.
3. Buat full backup.
4. Cek manifest dan isi ZIP.
5. Upload arsip yang sama.
6. Siapkan restore, pastikan safety backup berhasil.
7. Lakukan restore di staging, bukan produksi.
8. Verifikasi aplikasi, login, halaman publik, dan file media.
9. Cek Audit Log.

---

## 19. Inventaris file

### 19.1 Baru

```text
config/backups.php

app/Enums/BackupType.php
app/Enums/BackupSource.php
app/Enums/BackupStatus.php
app/Enums/BackupValidationStatus.php
app/Enums/BackupRestoreStatus.php
app/Enums/BackupComponentResult.php

app/Models/Backup.php
app/Models/BackupRestore.php
app/Models/BackupSetting.php

app/Exceptions/BackupException.php

app/Services/Backup/BackupSettingsService.php
app/Services/Backup/BackupStorage.php
app/Services/Backup/BackupPathGuard.php
app/Services/Backup/BackupManifest.php
app/Services/Backup/BackupArchiveInspector.php
app/Services/Backup/CommandRunner.php
app/Services/Backup/DatabaseDumper.php
app/Services/Backup/DatabaseRestorer.php
app/Services/Backup/BackupRetention.php
app/Services/Backup/BackupHealth.php
app/Services/Backup/BackupSafeError.php

app/Actions/Backup/CreateBackupAction.php
app/Actions/Backup/UploadBackupAction.php
app/Actions/Backup/ValidateBackupAction.php
app/Actions/Backup/PrepareRestoreAction.php
app/Actions/Backup/CreateSafetyBackupAction.php
app/Actions/Backup/RestoreBackupAction.php
app/Actions/Backup/DeleteBackupAction.php
app/Actions/Backup/UpdateBackupSettingsAction.php
app/Actions/Backup/PruneBackupsAction.php
app/Actions/Backup/VerifyBackupsAction.php
app/Actions/Backup/ReconcileBackupsAction.php

app/Jobs/CreateBackupJob.php
app/Jobs/PrepareRestoreJob.php
app/Jobs/RestoreBackupJob.php
app/Jobs/BackupWorkerHeartbeatJob.php

app/Console/Commands/RunScheduledBackups.php
app/Console/Commands/PruneBackups.php
app/Console/Commands/VerifyBackups.php
app/Console/Commands/ReconcileBackups.php
app/Console/Commands/BackupHeartbeat.php
app/Console/Commands/UnlockBackups.php

app/Http/Controllers/Backup/BackupController.php
app/Http/Controllers/Backup/BackupDownloadController.php
app/Http/Controllers/Backup/BackupUploadController.php
app/Http/Controllers/Backup/BackupRestoreController.php
app/Http/Controllers/Backup/BackupSettingsController.php

app/Http/Requests/Backup/StoreBackupRequest.php
app/Http/Requests/Backup/UploadBackupRequest.php
app/Http/Requests/Backup/PrepareRestoreRequest.php
app/Http/Requests/Backup/RestoreBackupRequest.php
app/Http/Requests/Backup/UpdateBackupSettingsRequest.php

app/Http/Resources/Backup/BackupListResource.php
app/Http/Resources/Backup/BackupResource.php
app/Http/Resources/Backup/BackupRestoreResource.php
app/Http/Resources/Backup/BackupSettingsResource.php

app/Policies/BackupPolicy.php
app/Http/Middleware/BlockWritesDuringRestore.php

database/migrations/2026_10_28_000000_create_backup_tables.php
database/factories/BackupFactory.php
database/factories/BackupRestoreFactory.php
database/factories/BackupSettingFactory.php

lang/id/backups.php

resources/js/features/admin/backup/pages/index.tsx
resources/js/features/admin/backup/pages/show.tsx
resources/js/features/admin/backup/pages/pengaturan.tsx
resources/js/features/admin/backup/components/create-backup-dialog.tsx
resources/js/features/admin/backup/components/upload-backup-dialog.tsx
resources/js/features/admin/backup/components/prepare-restore-dialog.tsx
resources/js/features/admin/backup/components/confirm-restore-dialog.tsx
resources/js/features/admin/backup/components/backup-status-badge.tsx
resources/js/features/admin/backup/components/backup-type-badge.tsx
resources/js/features/admin/backup/components/backup-summary.tsx
resources/js/features/admin/backup/components/backup-filters.tsx
resources/js/features/admin/backup/components/backup-settings-form.tsx
resources/js/features/admin/backup/types.ts

tests/Unit/Backup/BackupPathGuardTest.php
tests/Unit/Backup/BackupManifestTest.php
tests/Unit/Backup/BackupArchiveInspectorTest.php
tests/Unit/Backup/BackupRetentionTest.php
tests/Unit/Backup/BackupStatusTest.php
tests/Unit/Backup/BackupSafeErrorTest.php

tests/Feature/Backup/BackupAuthorizationTest.php
tests/Feature/Backup/BackupCreationTest.php
tests/Feature/Backup/BackupUploadTest.php
tests/Feature/Backup/BackupDownloadTest.php
tests/Feature/Backup/BackupRestoreTest.php
tests/Feature/Backup/BackupSettingsTest.php
tests/Feature/Backup/BackupSchedulerTest.php
tests/Feature/Backup/BackupRetentionCommandTest.php

tests/Integration/Backup/RestoreIntegrationTest.php

tests/e2e/backup.spec.ts

docs/backup-recovery.md
```

### 19.2 Dimodifikasi

```text
config/filesystems.php
config/queue.php
config/audit.php
lang/id/audit.php

database/seeders/PermissionSeeder.php

app/Http/Middleware/HandleInertiaRequests.php
app/Providers/AppServiceProvider.php (jika perlu binding service)
routes/web.php
bootstrap/app.php

tests/Pest.php
tests/Feature/Foundation/RbacFoundationTest.php
phpunit.xml (jika perlu env test backup)
playwright.config.ts

resources/js/layouts/app/app-sidebar-nav.ts
resources/js/types/index.ts (bila tipe shared prop perlu diperluas)

.env.example
docs/DECISIONS.md
docs/DEVELOPMENT.md
composer.json (opsional: platform requirement `ext-zip` dan `ext-zlib`)
```

**Tidak diedit manual:** `resources/js/routes/`, `resources/js/actions/`, `resources/js/wayfinder/`, `public/build/`. Jalankan `php artisan wayfinder:generate --with-form` setelah route berubah.

---

## 20. Catatan keputusan yang akan ditambahkan ke `docs/DECISIONS.md`

| Nomor | Judul                                                 | Ringkasan                                                                                                 |
| ----- | ----------------------------------------------------- | --------------------------------------------------------------------------------------------------------- |
| D-80  | Metadata backup dan tabel yang dikecualikan dari dump | Tiga tabel backup; tabel operasional/backup dikecualikan; `audit_events` disertakan dengan batas rollback |
| D-81  | Disk privat `backups` dan temporary workspace         | Arsip final terpisah dari temp; tidak ada public URL                                                      |
| D-82  | Format manifest backup                                | `.sql.gz` memakai komentar manifest; `.zip` memakai `manifest.json`                                       |
| D-83  | Queue khusus `backups` dan heartbeat kesiapan         | `retry_after` 7300; worker `--queue=backups,default`; UI menampilkan heartbeat                            |
| D-84  | Restore dua tahap dengan safety backup                | `preparing` → `ready` → `running`; safety backup wajib; write-block middleware                            |
| D-85  | Kebijakan file full restore                           | Ganti yang ada di arsip; file lain dipertahankan                                                          |
| D-86  | Penamaan permission `backups.settings`                | Mengikuti regex permission repo, bukan `backups.manage-settings`                                          |
| D-87  | Default jadwal dan retensi                            | Database harian 02:00, full Minggu 02:30, retensi 14 hari/4 minggu/7 hari/30 hari                         |
| D-88  | Restore produksi dan batas audit                      | UI restore dengan frasa `PULIHKAN DATA`; audit di DB ikut ter-rollback                                    |
| D-89  | Uji restore nyata dengan database terpisah            | `website_paroki_hspmtb_restore_test`; skip transparan bila tidak tersedia                                 |

---

## 21. Ringkasan acceptance

- **BK-AC-01:** Admin berwenang dapat membuat backup database dan mengunduh `.sql.gz`.
- **BK-AC-02:** Admin berwenang dapat membuat backup lengkap dan mengunduh `.zip`.
- **BK-AC-03:** Full backup memiliki manifest dan checksum valid.
- **BK-AC-04:** Backup hanya sukses setelah artefak final divalidasi.
- **BK-AC-05:** Admin dapat melihat daftar dan detail backup.
- **BK-AC-06:** Backup invalid/hilang tidak dapat di-restore.
- **BK-AC-07:** Backup disimpan di private storage.
- **BK-AC-08:** Download memeriksa authorization backend.
- **BK-AC-09:** Upload memvalidasi format, manifest, checksum, ukuran, dan path.
- **BK-AC-10:** Upload tidak mengubah data produksi.
- **BK-AC-11:** Restore memerlukan permission dan konfirmasi eksplisit.
- **BK-AC-12:** Safety backup sukses dan valid sebelum restore.
- **BK-AC-13:** Restore concurrent dicegah.
- **BK-AC-14:** Arsip tidak dapat menulis di luar root yang diizinkan.
- **BK-AC-15:** Hasil restore diverifikasi; partial failure tidak disamarkan.
- **BK-AC-16:** Backup otomatis hanya dianggap aktif jika scheduler/worker deployment benar-benar dikonfigurasi.
- **BK-AC-17:** Retensi tidak menghapus backup yang sedang digunakan/safety backup terlindungi.
- **BK-AC-18:** Operasi penting dicatat di Audit Log yang ada.
- **BK-AC-19:** Tidak ada secret di arsip, response, atau log.
- **BK-AC-20:** Authorization diuji di backend.
- **BK-AC-21:** Dokumentasi menjelaskan keterbatasan backup lokal dan prosedur recovery.
- **BK-AC-22:** Test relevan dan quality gate lulus atau hasilnya dilaporkan transparan.

---

## 22. Catatan akhir

Plan ini adalah sumber acuan implementasi. Jika ada perubahan scope, requirement, atau keputusan hosting, perbarui dokumen ini dan `docs/DECISIONS.md` sebelum mengubah kode. Jangan mengklaim backup/restore terbukti hanya karena mock test lulus; integration test dengan database uji terpisah adalah bukti minimum untuk restore nyata.
