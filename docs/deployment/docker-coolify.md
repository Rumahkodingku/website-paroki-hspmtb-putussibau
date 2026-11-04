# Deployment Docker & Coolify

Panduan operasi untuk menjalankan Website Paroki HSPMTB Putussibau sebagai container production di Coolify.

> Local development tetap native dan tidak membutuhkan Docker. Lihat `docs/DEVELOPMENT.md`.

---

## 1. Arsitektur

Satu Coolify application resource menjalankan satu container dengan `supervisord`:

```text
Container
├── supervisord (PID 1)
├── php-fpm        :9000 (loopback)
├── nginx          :80
├── queue-default  queue:work database --queue=default
├── queue-backups  queue:work backups --queue=backups
└── scheduler      schedule:work

Volume
└── /var/www/html/storage
```

MySQL 8 adalah resource Coolify terpisah per environment dan tidak memiliki public port.

Alasan satu container: worker backup dan image processing menulis ke `storage/`; web harus membaca file yang sama. Named volume Coolify tidak dibagi otomatis antar-resource.

Konsekuensi:

- Deploy/restart menghentikan web, queue, dan scheduler bersamaan.
- Worker dan scheduler tidak dapat di-scale terpisah.
- Hanya satu replika. Jangan menaikkan replica count.

---

## 2. Prasyarat

- Coolify v4.5.3 atau versi 4.x yang kompatibel.
- Docker pada server Coolify.
- Repository Git berisi `Dockerfile` di root.
- MySQL 8 resource per environment.
- Domain staging dan production.
- Akses ke environment variables Coolify.

---

## 3. Build image

Dari root repository:

```bash
docker build --pull -t hspmtb-paroki:local .
```

Build tidak membutuhkan `.env`.

Yang terjadi di dalam build:

1. Composer install dari `composer.lock` dengan `--no-dev`.
2. `npm ci` dari `package-lock.json` dengan `HUSKY=0`.
3. `npm run build`; Wayfinder memakai SQLite in-memory agar tidak menunggu koneksi MySQL.
4. Asset hasil Vite disalin ke image final.
5. MySQL 8.4 client dipasang untuk fitur backup.
6. Compiler dan dependency build tidak masuk image final.

Build membutuhkan jaringan untuk Composer, npm, plugin Bunny Fonts, dan repository MySQL.

Verifikasi image:

```bash
docker run --rm hspmtb-paroki:local php -m
docker run --rm hspmtb-paroki:local php-fpm -tt
docker run --rm hspmtb-paroki:local nginx -t
docker run --rm hspmtb-paroki:local mysqldump --version
docker run --rm hspmtb-paroki:local mysql --version
docker run --rm hspmtb-paroki:local php -r 'require "vendor/autoload.php";'
```

Pastikan image tidak memuat `.env`:

```bash
docker run --rm --entrypoint sh hspmtb-paroki:local -c 'test ! -f .env'
```

---

## 4. Environment variables

Nilai secret tidak boleh masuk Git, Dockerfile, build arg, atau log.

### 4.1 Aplikasi

| Variable | Nilai |
| --- | --- |
| `APP_NAME` | Nama paroki |
| `APP_ENV` | `production` untuk staging dan production |
| `APP_KEY` | Secret unik per environment |
| `APP_DEBUG` | `false` |
| `APP_URL` | URL HTTPS environment |
| `APP_DISPLAY_TIMEZONE` | `Asia/Pontianak` |
| `APP_LOCALE` | `id` |
| `APP_FALLBACK_LOCALE` | Konfigurasi aktif |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `info` |
| `INERTIA_SSR_ENABLED` | `false` |
| `TELESCOPE_ENABLED` | `false` |
| `HOMEPAGE_ILLUSTRATIONS` | `false` |

### 4.2 Database

| Variable | Nilai |
| --- | --- |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | Internal hostname Coolify |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | Nama database environment |
| `DB_USERNAME` | User database environment |
| `DB_PASSWORD` | Secret environment |
| `DB_COLLATION` | `utf8mb4_0900_ai_ci` |

Jangan set `DB_URL`.

### 4.3 Operasional Laravel

| Variable | Nilai |
| --- | --- |
| `SESSION_DRIVER` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `DB_QUEUE_RETRY_AFTER` | `600` |
| `BACKUPS_QUEUE_CONNECTION` | `backups` |
| `BACKUPS_QUEUE` | `backups` |
| `BACKUPS_QUEUE_RETRY_AFTER` | `7300` |
| `BACKUPS_DISK` | `backups` |
| `BACKUPS_MYSQLDUMP_PATH` | `/usr/bin/mysqldump` |
| `BACKUPS_MYSQL_PATH` | `/usr/bin/mysql` |
| `FILESYSTEM_DISK` | `local` |
| `DOCUMENTS_DISK` | `local` |
| `INSTALLER_ENABLED` | `true` saat instalasi pertama, setelah itu `false` |
| `INSTALLER_WRITE_ENV` | `false` |
| `MAIL_*` | Konfigurasi SMTP environment |

`ADMIN_EMAIL` dan `ADMIN_PASSWORD` tidak diisi; admin dibuat melalui wizard.

---

## 5. Setup MySQL di Coolify

1. Buat resource MySQL 8.4 per environment.
2. Aktifkan persistent volume.
3. Matikan public access.
4. Catat internal hostname, database, user, dan password.
5. Verifikasi kolasi:

   ```sql
   SELECT @@character_set_database, @@collation_database;
   ```

   Target: `utf8mb4` / `utf8mb4_0900_ai_ci`.

6. Staging dan production tidak boleh memakai database yang sama.

---

## 6. Setup application resource

| Field | Nilai |
| --- | --- |
| Environment | staging atau production |
| Source | Public Git Repository |
| Repository | `https://github.com/Rumahkodingku/website-paroki-hspmtb-putussibau` |
| Branch staging | `staging` |
| Branch production | `main` |
| Build Pack | `Dockerfile` |
| Base Directory | `/` |
| Dockerfile Location | `/Dockerfile` |
| Docker Build Stage Target | kosong |
| Ports Exposes | `80` |
| Health Check Path | `/up` |
| Health Check Scheme | `http` |
| Health Check Port | `80` |
| Stop Grace Period | `3600` detik |
| Domain | Domain environment |

### 6.1 Persistent storage

1. Tambah **Volume Mount**.
2. Name: `storage`.
3. Destination: `/var/www/html/storage`.
4. Jangan mount `public/storage`; entrypoint membuat symlink.
5. Gunakan volume berbeda untuk staging dan production.

### 6.2 Health check

Gunakan `/up`.

`/up` adalah liveness, bukan readiness database. Status queue dan scheduler dilihat di `/admin/backup`.

---

## 7. Deploy pertama

1. Isi semua environment variable.
2. Buat `APP_KEY` unik:

   ```bash
   php artisan key:generate --show
   ```

   Jalankan di mesin lokal dengan PHP 8.3 atau container sekali pakai. Jangan menyimpan nilai di Git.

3. Set `INSTALLER_ENABLED=true`.
4. Deploy application.
5. Buka `/install` dan selesaikan wizard.
6. Pastikan kredensial database yang diisi wizard sama dengan environment Coolify. Installer memakai nilai wizard untuk migrasi pada request tersebut; setelah itu aplikasi membaca environment Coolify.
7. Verifikasi status instalasi di volume:

   ```bash
   docker exec <container> cat storage/app/private/installation/status.json
   ```

8. Set `INSTALLER_ENABLED=false`.
9. Redeploy.
10. Verifikasi login admin, halaman publik, media, jadwal, SEO, queue, scheduler, dan backup.

Jangan menyalin database atau private storage staging ke production secara otomatis.

---

## 8. Release dan migrasi

1. Buat backup database.
2. Deploy commit baru.
3. Tunggu health check.
4. Jalankan migrasi manual:

   ```bash
   php artisan migrate --force
   ```

   melalui Coolify terminal atau one-off command.

5. Verifikasi halaman publik dan admin.
6. Periksa log queue dan scheduler.

Jangan pernah menjalankan `migrate:fresh` di staging/production. Tidak ada migrasi otomatis saat container start.

---

## 9. Rollback

1. Redeploy commit sebelumnya.
2. Jika migrasi mengubah skema secara destruktif, restore backup database.
3. Jangan menjalankan `migrate:rollback` tanpa memeriksa reversibilitas dan backup.
4. File media di volume tidak ikut rollback otomatis.

---

## 10. Queue dan scheduler

Container menjalankan:

| Proses | Command |
| --- | --- |
| Queue default | `php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=300 --max-time=3600 --memory=900` |
| Queue backup | `php artisan queue:work backups --queue=backups --sleep=3 --tries=1 --timeout=7200 --max-time=86400 --memory=480` |
| Scheduler | `php artisan schedule:work` |

Ketentuan:

1. `DB_QUEUE_RETRY_AFTER=600` harus lebih besar dari timeout worker default 300.
2. `BACKUPS_QUEUE_RETRY_AFTER=7300` harus lebih besar dari timeout worker backup 7200.
3. Hanya satu scheduler per environment.
4. Jangan menambahkan cron `schedule:run` jika `schedule:work` sudah berjalan.
5. Jangan menaikkan replica count.

Cek status:

```bash
docker exec <container> supervisorctl status
```

---

## 11. Backup dan restore

1. Arsip backup berada di `storage/app/private/backups`.
2. Worker backup memakai koneksi queue `backups`.
3. Backup lokal bukan disaster recovery; salin arsip ke offsite secara berkala.
4. Jangan deploy saat backup atau restore panjang berjalan.
5. Restore dapat berjalan sampai 7200 detik; stop grace Coolify maksimum 3600 detik.

Perintah worker manual untuk pengujian:

```bash
php artisan queue:work backups --queue=backups --tries=1 --timeout=7200
```

---

## 12. Log dan troubleshooting

### 12.1 Log

```bash
docker logs <container>
docker logs -f <container>
```

Semua proses menulis ke stdout/stderr:

- Nginx access/error log.
- PHP-FPM error log.
- Laravel `LOG_CHANNEL=stderr`.
- Supervisor program logs.

### 12.2 Container tidak sehat

1. Cek `docker logs`.
2. Cek `supervisorctl status` di dalam container.
3. Cek `/up`:

   ```bash
   curl -i http://127.0.0.1/up
   ```

4. Cek permission storage:

   ```bash
   docker exec <container> ls -ld storage storage/app/public
   ```

### 12.3 Asset tidak muncul

1. Pastikan `public/build/manifest.json` ada di image.
2. Jalankan `npm run build` atau rebuild image.
3. Jangan mengandalkan `npm run dev` di production.

### 12.4 Upload gagal

1. Cek `client_max_body_size` Nginx.
2. Cek `upload_max_filesize` dan `post_max_size` PHP.
3. Cek `max_execution_time` dan `fastcgi_read_timeout`.
4. Cek ruang disk volume.

### 12.5 Queue tidak berjalan

1. `supervisorctl status`.
2. Cek `DB_QUEUE_RETRY_AFTER` dan `BACKUPS_QUEUE_RETRY_AFTER`.
3. Cek koneksi database.
4. Cek heartbeat di `/admin/backup`.

---

## 13. Keamanan

1. `.env` tidak ada di image dan tidak ditulis installer.
2. `APP_DEBUG=false`.
3. `expose_php=Off`.
4. Nginx hanya mengeksekusi `/index.php`.
5. Dotfiles dan ekstensi sensitif ditolak.
6. `storage/app/private` tidak berada di web root.
7. MySQL tidak punya public port.
8. Jangan menambahkan SSH server ke image.
9. Staging memakai APP_KEY, database, mail, storage, dan secret berbeda.

---

## 14. Batasan

1. Satu container, satu unit restart.
2. Scheduler dan queue tidak dapat di-scale terpisah.
3. `/up` hanya liveness.
4. Deploy saat restore panjang dapat memutus proses.
5. Build membutuhkan jaringan.
6. MySQL APT repository menambah supply chain.
7. Worker image processing memakai 1 GB; container sebaiknya minimal 2 GB.
8. Volume storage harus cukup untuk media, dokumen, dan backup lokal.
9. Tidak ada publish image ke registry.
10. Tidak ada backup offsite otomatis.
