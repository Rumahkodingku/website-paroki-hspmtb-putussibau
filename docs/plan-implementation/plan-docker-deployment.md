# Rencana Implementasi Docker Deployment & Coolify

## Website Paroki HSPMTB Putussibau

| Atribut         | Nilai                                                                                                                                               |
| --------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| Dokumen         | Rencana Implementasi Docker Deployment & Coolify                                                                                                    |
| Produk          | Website Resmi Gereja Katolik Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau                                                               |
| Status          | Final — siap dieksekusi                                                                                                                             |
| Prioritas       | P0                                                                                                                                                  |
| Dokumen sumber  | `docs/specifications/spec-docker-deployment.md`                                                                                                     |
| Branch kerja    | `feat/docker-coolify-deployment`                                                                                                                    |
| Stack           | Laravel 13, PHP 8.3, Inertia v3, React 19, TypeScript, Tailwind CSS v4, MySQL 8                                                                     |
| Target platform | Coolify self-hosted v4.5.3 di Docker pada WSL Ubuntu                                                                                                |
| Cakupan         | Dockerfile production, Nginx, PHP-FPM, queue, scheduler, persistent storage, dokumentasi Coolify                                                    |
| Discovery       | Codebase Memory MCP project `home-riot-projects-rumahkodingku-PROJECTS-website-paroki-hspmtb-putussibau` (status `ready`) + pembacaan sumber aktual |

---

## Daftar Isi

- [1. Ringkasan keputusan](#1-ringkasan-keputusan)
- [2. Temuan audit yang mengikat](#2-temuan-audit-yang-mengikat)
- [3. Ruang lingkup](#3-ruang-lingkup)
- [4. Arsitektur final](#4-arsitektur-final)
- [5. Perubahan kode PHP](#5-perubahan-kode-php)
- [6. File yang dibuat dan diubah](#6-file-yang-dibuat-dan-diubah)
- [7. Dockerfile](#7-dockerfile)
- [8. `.dockerignore`](#8-dockerignore)
- [9. Konfigurasi Nginx](#9-konfigurasi-nginx)
- [10. Konfigurasi PHP dan PHP-FPM](#10-konfigurasi-php-dan-php-fpm)
- [11. Supervisor](#11-supervisor)
- [12. Entrypoint](#12-entrypoint)
- [13. Health check](#13-health-check)
- [14. Environment variables Coolify](#14-environment-variables-coolify)
- [15. Runbook Coolify](#15-runbook-coolify)
- [16. First install, release, dan rollback](#16-first-install-release-dan-rollback)
- [17. Pengujian](#17-pengujian)
- [18. Dokumentasi dan keputusan](#18-dokumentasi-dan-keputusan)
- [19. Urutan eksekusi](#19-urutan-eksekusi)
- [20. Risiko dan batasan](#20-risiko-dan-batasan)
- [21. Acceptance criteria](#21-acceptance-criteria)

---

## 1. Ringkasan keputusan

| Topik              | Keputusan                                                                             |
| ------------------ | ------------------------------------------------------------------------------------- |
| Topologi           | Satu container aplikasi dengan `supervisord`                                          |
| Proses             | Nginx, PHP-FPM, queue default, queue backup, scheduler                                |
| Persistent storage | Satu volume Coolify di `/var/www/html/storage`                                        |
| Database           | MySQL 8 resource Coolify terpisah per environment                                     |
| MySQL client       | `mysql-community-client` 8.4 LTS dari repository APT resmi MySQL                      |
| PHP                | PHP 8.3, patch terbaru yang tersedia saat implementasi                                |
| Node               | Node 22 LTS, memenuhi `vite@8`                                                        |
| Asset build        | Composer + npm dijalankan di builder; Wayfinder memakai SQLite in-memory              |
| Telescope          | Guard `class_exists()` karena package hanya ada di `require-dev`                      |
| Installer `.env`   | `INSTALLER_WRITE_ENV=false` di container; environment Coolify adalah sumber kebenaran |
| `APP_ENV`          | `production` untuk staging dan production                                             |
| Kolasi             | Patch installer agar memakai `DB_COLLATION`; target `utf8mb4_0900_ai_ci`              |
| Queue default      | `queue:work database --queue=default`, timeout 300 detik                              |
| Queue backup       | `queue:work backups --queue=backups`, timeout 7200 detik                              |
| Scheduler          | Satu `schedule:work` di container yang sama                                           |
| Compose            | Tidak dibuat                                                                          |
| CI image publish   | Tidak termasuk                                                                        |

### 1.1 Keputusan yang sudah dikonfirmasi pengguna

1. Topologi: **satu container supervisord**.
2. Telescope: **guard `class_exists()`**.
3. Installer: **flag `INSTALLER_WRITE_ENV`**, diset `false` di Coolify.
4. `APP_ENV`: **production untuk staging dan production**.
5. Kolasi: **patch installer ke `utf8mb4_0900_ai_ci`**.
6. MySQL client: **MySQL 8.4 LTS**.
7. Branch: **`feat/docker-coolify-deployment`**.
8. MySQL resource: **provisioning baru, terpisah per environment**.
9. Compose: **tidak dibuat**.

---

## 2. Temuan audit yang mengikat

Temuan berikut berasal dari source aktual, bukan asumsi template Laravel.

1. **`composer.json` memakai PHP `^8.3`; `composer.lock` mengunci platform PHP `^8.3`.** PHP 8.3 lokal yang diverifikasi: `8.3.6`.
2. **Ekstensi runtime yang terbukti dipakai:**
   - `pdo_mysql` — database.
   - `gd` — `ImageProcessor` memakai `imagecreatefromjpeg`, `imagecreatefrompng`, `imagewebp`.
   - `zip` — `ZipArchive` pada backup, restore, dan validasi dokumen.
   - `exif` — orientasi foto; kode memakai `exif_read_data()` bila tersedia.
   - `fileinfo` — validasi MIME.
   - `mbstring`, `dom`, `libxml`, `xml`, `xmlwriter` — framework dan `symfony/html-sanitizer`.
   - `pcntl` dan `posix` — signal/timeout queue worker.
3. **Tidak ada bukti penggunaan Imagick, ffmpeg, ffprobe, ghostscript, atau intl.** Tidak dipasang.
4. **`DatabaseDumper` menambahkan `--set-gtid-purged=OFF` untuk server MySQL.** `mysqldump` MariaDB menolak opsi ini. Runtime wajib memakai MySQL client, bukan MariaDB client Debian.
5. **`@laravel/vite-plugin-wayfinder` menjalankan `php artisan wayfinder:generate` saat `npm run build`.** Build asset membutuhkan PHP dan `vendor`, bukan hanya Node.
6. **Wayfinder dapat menggantung tanpa database** karena `BindingResolver` mencoba membaca kolom model. Builder harus menyediakan SQLite in-memory agar kegagalan koneksi cepat dan jatuh ke pembacaan docblock.
7. **`/up` terdaftar tanpa middleware dan tanpa listener database.** Endpoint aman dipakai sebelum installer, tetapi hanya liveness, bukan readiness database.
8. **Installer gagal di container tanpa patch.** `InstallationRunner` mewajibkan `EnvironmentWriter::write()`. Penulisan `.env` memakai file sementara dan `rename()` di root project; PHP-FPM berjalan sebagai `www-data`, sedangkan root project milik root.
9. **Image `--no-dev` akan fatal tanpa guard.** `bootstrap/providers.php` selalu mendaftarkan `App\Providers\TelescopeServiceProvider`, yang extends class dari `laravel/telescope` (`require-dev`). Schedule juga memanggil `telescope:prune`.
10. **Installer meng-hard-code `utf8mb4_unicode_ci`.** Tabel yang dibuat wizard tidak akan memakai `utf8mb4_0900_ai_ci` meskipun database dibuat dengan kolasi tersebut.
11. **`APP_ENV=staging` mengubah hardening.** `app()->isProduction()` hanya true untuk `production`; staging akan memakai `Model::shouldBeStrict(true)`, password policy default, dan `DB::prohibitDestructiveCommands(false)`.
12. **Batas upload aktual:**
    - Media: 3 MB (`config/media.php`).
    - Dokumen: 10 MB (`config/documents.php`).
    - Backup eksternal: 256 MB (`config/backups.php`).
13. **GD menerima gambar sampai 8000×8000.** Peak memory image processing dapat melewati 512 MB; worker perlu 1 GB.
14. **Default queue `database` memakai `retry_after` 90 detik.** Jika timeout worker lebih besar, dua worker dapat mengambil job yang sama. Nilai harus dinaikkan.
15. **`npm ci` menjalankan `prepare: husky`.** Build context tanpa `.git` dapat gagal; gunakan `HUSKY=0`.
16. **`storage` adalah satu-satunya jalur yang perlu persisten.** Media publik, dokumen privat, backup, status installer, session/cache/queue file fallback, dan log file semuanya berada di bawah `storage/`.
17. **`public/storage` harus berupa symlink ke `storage/app/public`.** Belum ada di image; dibuat entrypoint.
18. **Tidak ada `.ai/rules/index.md`.** Aturan proyek yang berlaku adalah `AGENTS.md` dan konvensi source.

---

## 3. Ruang lingkup

### 3.1 Termasuk

1. `Dockerfile` multi-stage di root repository.
2. `.dockerignore`.
3. Konfigurasi Nginx, PHP, PHP-FPM, supervisor, entrypoint, healthcheck.
4. Satu image aplikasi dengan proses web, queue default, queue backup, dan scheduler.
5. Persistent storage untuk seluruh `storage/`.
6. MySQL 8 resource terpisah per environment dan dokumentasi koneksi internal.
7. Perbaikan kecil pada kode aplikasi yang diperlukan agar image `--no-dev` dan installer berjalan:
   - Guard Telescope.
   - Flag `INSTALLER_WRITE_ENV`.
   - Kolasi installer.
8. Dokumentasi deployment Coolify.
9. Update `docs/DEVELOPMENT.md`, `docs/DECISIONS.md`, dan `docs/backup-recovery.md`.
10. Build dan smoke test lokal.

### 3.2 Tidak termasuk

1. Migrasi MySQL ke PostgreSQL/MariaDB.
2. Mengaktifkan Inertia SSR.
3. Mengubah versi Laravel, Vite, React, atau dependency lain.
4. Mengubah business logic, route, permission, installer flow, atau backup/restore.
5. Menambah Redis.
6. Membuat domain, DNS, TLS, Cloudflare tunnel, atau backup offsite otomatis.
7. Menulis `.env` production.
8. Push, merge, deploy Coolify, atau migrasi database staging/production.
9. Workflow CI publish image ke registry.
10. `docker-compose.yml` / `compose.yaml`.

---

## 4. Arsitektur final

### 4.1 Bentuk deployment

```txt
Coolify Application (1 resource)
└── Container: hspmtb-paroki
    ├── supervisord (PID 1)
    ├── php-fpm        :9000 (loopback)
    ├── nginx          :80
    ├── queue-default  php artisan queue:work database --queue=default
    ├── queue-backups  php artisan queue:work backups --queue=backups
    └── scheduler      php artisan schedule:work
        │
        └── volume: /var/www/html/storage

Coolify MySQL 8 (1 resource per environment)
└── internal hostname, tanpa public port
```

### 4.2 Alasan topologi satu container

1. Worker backup menulis arsip ke `storage/app/private/backups`; web harus dapat mengunduhnya. Satu container menjamin volume yang sama.
2. Worker image processing menulis variasi media ke `storage/app/public`; web harus dapat menyajikannya. Satu container menghilangkan masalah shared volume Coolify.
3. Coolify memberi prefix nama volume per resource; named volume tidak otomatis dibagi antar-resource.
4. Untuk skala situs paroki, satu unit restart lebih sederhana daripada tiga resource dengan bind mount yang harus dijaga permission-nya.
5. Scheduler dan queue tetap proses terpisah di dalam container, sehingga kegagalan salah satu tidak mematikan yang lain; supervisord melakukan restart otomatis.

### 4.3 Konsekuensi

1. Deploy/restart container menghentikan web, queue, dan scheduler bersamaan.
2. Worker dan scheduler tidak dapat di-scale terpisah.
3. Log tercampur dalam satu container, tetapi setiap program supervisor menulis ke stdout/stderr dengan prefix.
4. Hanya satu replika. Jangan menaikkan replica count karena scheduler dan queue akan ganda.

---

## 5. Perubahan kode PHP

### 5.1 `bootstrap/providers.php`

Tujuan: image `composer install --no-dev` tidak memuat Telescope, sehingga provider tidak boleh didaftarkan tanpa syarat.

```php
$providers = [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
];

if (class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)) {
    $providers[] = TelescopeServiceProvider::class;
}

return $providers;
```

### 5.2 `bootstrap/app.php`

Tujuan: schedule tidak memanggil command yang tidak ada pada image produksi.

```php
if (class_exists(\Laravel\Telescope\Telescope::class)) {
    $schedule->command('telescope:prune --hours=24')->daily();
}
```

Schedule lain tidak berubah:

- `queue:prune-failed --hours=168`
- `posts:publish-scheduled`
- `backups:heartbeat`
- `backups:run-scheduled`
- `BackupWorkerHeartbeatJob`
- `backups:verify`
- `backups:prune`

### 5.3 `config/installer.php`

Tambah flag:

```php
'write_env' => (bool) env('INSTALLER_WRITE_ENV', true),
```

Default `true` menjaga perilaku native development.

### 5.4 `app/Services/Installation/InstallationRunner.php`

Ubah langkah `save_environment`:

```php
if (config('installer.write_env') && ! $this->environment->write($envValues)) {
    throw new InstallationFailure(InstallationError::EnvironmentWriteFailed);
}
```

Jika `INSTALLER_WRITE_ENV=false`, installer tidak menulis `.env` dan tetap menyelesaikan status `completed`.

### 5.5 Kolasi installer

Di dua lokasi:

- `app/Services/Installation/InstallationRunner.php`
- `app/Http/Controllers/Installation/InstallationWizardController.php`

Ganti:

```php
'collation' => 'utf8mb4_unicode_ci',
```

menjadi:

```php
'collation' => (string) config('database.connections.mysql.collation', 'utf8mb4_unicode_ci'),
```

Coolify mengeset `DB_COLLATION=utf8mb4_0900_ai_ci`. Dengan begitu koneksi yang dipakai installer dan migrasi memakai kolasi yang benar.

### 5.6 Test perubahan kode

1. Test bahwa `INSTALLER_WRITE_ENV=false` tidak menggagalkan instalasi saat `EnvironmentWriter` tidak dapat menulis.
2. Test bahwa konfigurasi koneksi installer memakai nilai `database.connections.mysql.collation`.
3. Test instalasi yang sudah ada tetap lulus.
4. Jalankan `vendor/bin/pint --dirty --format agent`.

Catatan: test kolasi harus memakai database sementara atau assertion konfigurasi. Jangan mengandalkan `RefreshDatabase` karena tabel test sudah dibuat sebelum installer berjalan.

---

## 6. File yang dibuat dan diubah

### 6.1 Dibuat

| File                                | Fungsi                        |
| ----------------------------------- | ----------------------------- |
| `Dockerfile`                        | Multi-stage build             |
| `.dockerignore`                     | Konteks build aman            |
| `docker/nginx/default.conf`         | Virtual host Laravel          |
| `docker/php/production.ini`         | Konfigurasi PHP produksi      |
| `docker/php-fpm/www.conf`           | Pool PHP-FPM                  |
| `docker/supervisord.conf`           | Lima program proses           |
| `docker/entrypoint.sh`              | Persiapan runtime dan symlink |
| `docker/healthcheck.sh`             | Health check container        |
| `docs/deployment/docker-coolify.md` | Runbook deployment            |

### 6.2 Diubah

| File                                                                 | Perubahan                                      |
| -------------------------------------------------------------------- | ---------------------------------------------- |
| `bootstrap/providers.php`                                            | Guard Telescope                                |
| `bootstrap/app.php`                                                  | Guard schedule Telescope                       |
| `config/installer.php`                                               | Flag `write_env`                               |
| `app/Services/Installation/InstallationRunner.php`                   | Flag write env + kolasi                        |
| `app/Http/Controllers/Installation/InstallationWizardController.php` | Kolasi                                         |
| `tests/Feature/Installation/InstallationRunTest.php`                 | Test flag dan kolasi                           |
| `docs/DEVELOPMENT.md`                                                | Bedakan local native dan Docker deployment     |
| `docs/DECISIONS.md`                                                  | Tambah D-106                                   |
| `docs/backup-recovery.md`                                            | Koreksi command worker dan scheduler container |

### 6.3 Tidak dibuat

- `compose.yaml`
- `README.md`
- Workflow CI baru
- File `.env`
- Perubahan dependency Composer/npm

---

## 7. Dockerfile

### 7.1 Stage `node`

```dockerfile
FROM node:22-bookworm-slim AS node
```

Versi patch Node dipin pada implementasi. Node 22 memenuhi `vite@8` (`^20.19.0 || >=22.12.0`). Stage ini hanya menyediakan binary Node/npm untuk builder.

### 7.2 Stage `builder`

Base:

```dockerfile
FROM php:8.3-fpm-bookworm AS builder
```

Langkah:

1. Install dependency build:
   - `git`, `unzip`
   - `libonig-dev`, `libxml2-dev`, `libzip-dev`
   - `libpng-dev`, `libjpeg62-turbo-dev`, `libfreetype6-dev`, `libwebp-dev`
   - `sqlite3`, `libsqlite3-dev`
2. Gunakan ekstensi bawaan image resmi untuk Artisan/Wayfinder:
   - `mbstring`
   - `dom`
   - `pdo_sqlite` — hanya builder, sudah tersedia di image resmi.

   `zip` tidak dibutuhkan di builder karena Composer memakai binary `unzip`.
3. Copy Composer dari `composer:2.8`.
4. Copy `composer.json` dan `composer.lock`.
5. Jalankan:

   ```bash
   composer install \
     --no-dev \
     --no-scripts \
     --prefer-dist \
     --no-interaction \
     --no-progress \
     --optimize-autoloader
   ```

6. Copy source aplikasi.
7. Buat direktori runtime yang dibutuhkan Wayfinder:

   ```bash
   mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache
   ```

   `.dockerignore` mengeluarkan `storage`, tetapi `view.compiled` harus ada sebelum Artisan dijalankan.
8. Jalankan:

   ```bash
   composer dump-autoload --optimize --no-dev
   ```

   Script `post-autoload-dump` menjalankan `package:discover`. Guard Telescope membuat langkah ini aman tanpa package dev.
9. Copy `package.json` dan `package-lock.json`.
10. Jalankan:

    ```bash
    HUSKY=0 npm ci
    ```

11. Jalankan build asset:

    ```bash
    DB_CONNECTION=sqlite DB_DATABASE=:memory: npm run build
    ```

    Wayfinder dijalankan oleh plugin Vite. SQLite in-memory membuat pemeriksaan model binding gagal cepat.
12. Verifikasi:

    ```bash
    test -f public/build/manifest.json
    ```

Build membutuhkan jaringan untuk Composer, npm, dan plugin Bunny Fonts. Kegagalan unduh font harus terlihat di log build.

### 7.3 Stage `phpext`

Base PHP yang sama. Compile ekstensi runtime:

- `pdo_mysql`
- `gd` dengan `--with-freetype --with-jpeg --with-webp`
- `zip`
- `exif`
- `mbstring`
- `pcntl`
- `posix`
- `opcache`

Ekstensi `.so` dan file `conf.d` disalin ke runtime. Compiler, header dev, dan `$PHPIZE_DEPS` tidak masuk image final.

Tidak dipasang: `imagick`, `intl`, `bcmath`, `ffmpeg`, `ffprobe`, `ghostscript`.

### 7.4 Stage `runtime`

Base:

```dockerfile
FROM php:8.3-fpm-bookworm AS runtime
```

Install:

- `nginx`
- `supervisor`
- `curl`
- `ca-certificates`
- `tzdata`
- library runtime:
  - `libpng16-16`
  - `libjpeg62-turbo`
  - `libfreetype6`
  - `libwebp7`
  - `libzip4`
  - `libonig5`

MySQL client:

1. Tambah keyring dan sources list MySQL APT. Gunakan key `RPM-GPG-KEY-mysql-2025`; key 2023 sudah kedaluwarsa.
2. Install `mysql-community-client` 8.4.13-1debian12 (versi final diverifikasi saat implementasi).
3. Verifikasi:

   ```bash
   mysqldump --version
   mysql --version
   ```

Copy dari stage sebelumnya:

- `vendor`
- `public/build`
- `bootstrap/cache`
- ekstensi PHP
- konfigurasi Nginx/PHP/PHP-FPM/supervisor
- entrypoint dan healthcheck

Buat direktori:

```text
storage/app/public
storage/app/private/backups
storage/app/private/backup-temp
storage/app/private/installation
storage/framework/cache/data
storage/framework/sessions
storage/framework/views
storage/logs
bootstrap/cache
```

Konfigurasi image:

```dockerfile
WORKDIR /var/www/html
EXPOSE 80
STOPSIGNAL SIGTERM
CMD ["/usr/local/bin/docker-entrypoint.sh","/usr/bin/supervisord","-c","/etc/supervisor/conf.d/app.conf"]
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
  CMD ["/usr/local/bin/healthcheck.sh"]
```

Tidak ada `key:generate`, `migrate`, `db:seed`, `storage:link`, atau wizard installer saat build.

### 7.5 Update policy base image

1. Pin tag PHP 8.3 patch, Node 22 patch, dan Composer 2.8 patch.
2. Perbarui minimal satu kali per bulan atau saat patch keamanan keluar.
3. Setelah update, jalankan build dan smoke test.
4. Catat versi yang dipakai di laporan implementasi.

---

## 8. `.dockerignore`

```text
.git
.github
.ai
.agents
.claude
.cursor
node_modules
vendor
.env
.env.*
!.env.example
.phpunit.cache
.phpunit.result.cache
coverage
playwright-report
test-results
blob-report
public/build
public/hot
public/storage
storage
bootstrap/cache/*.php
resources/js/actions
resources/js/routes
resources/js/wayfinder
tests
docs
AGENTS.md
```

Yang tetap masuk konteks:

- `app/`, `bootstrap/app.php`, `config/`, `database/`, `lang/`, `routes/`
- `resources/` termasuk `resources/js/features/**/pages/*.tsx` yang dibaca `PageChunk`
- `public/` aset statis non-build
- `composer.json`, `composer.lock`
- `package.json`, `package-lock.json`
- `vite.config.ts`, `tsconfig.json`
- `Dockerfile`, `docker/`
- `.env.example`

`storage` dikeluarkan utuh; entrypoint membuat struktur runtime.

---

## 9. Konfigurasi Nginx

`docker/nginx/default.conf`:

```nginx
server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php;

    server_tokens off;
    charset utf-8;

    client_max_body_size 300m;
    client_body_timeout 120s;
    send_timeout 300s;

    access_log /dev/stdout;
    error_log  /dev/stderr warn;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

Aturan wajib:

1. Hanya `location = /index.php` yang mengeksekusi PHP.
2. `location ~ \.php$ { return 404; }` mencegah eksekusi file PHP lain.
3. `location ^~ /storage/` melayani file publik dengan `try_files $uri =404`, tanpa eksekusi PHP.
4. `location ^~ /build/` memakai cache immutable satu tahun.
5. `location = /build/manifest.json` dan `fonts-manifest.json` memakai `Cache-Control: no-cache` agar redeploy tidak menyajikan manifest lama.
6. Tolak dotfiles:

   ```nginx
   location ~ /\.(?!well-known).* { deny all; }
   ```

7. Tolak ekstensi sensitif:

   ```nginx
   location ~* \.(env|log|sql|sqlite|bak|ini|sh|git|htaccess|htpasswd)$ {
       deny all;
   }
   ```

8. FastCGI:
   - `fastcgi_pass 127.0.0.1:9000`
   - `fastcgi_read_timeout 600s`
   - `fastcgi_send_timeout 600s`
   - `fastcgi_buffer_size 32k`
   - `fastcgi_buffers 16 16k`
   - `fastcgi_busy_buffers_size 32k`
   - `fastcgi_param SCRIPT_FILENAME $realpath_root/index.php`
   - `fastcgi_param DOCUMENT_ROOT $realpath_root`
   - `fastcgi_param HTTP_PROXY ""`
9. Teruskan header proxy Coolify:
   - `HTTP_X_FORWARDED_FOR`
   - `HTTP_X_FORWARDED_HOST`
   - `HTTP_X_FORWARDED_PORT`
   - `HTTP_X_FORWARDED_PROTO`
10. Gzip untuk CSS, JS, JSON, SVG.

Nginx tidak menyajikan `storage/app/private` karena root hanya `public/`.

---

## 10. Konfigurasi PHP dan PHP-FPM

### 10.1 `docker/php/production.ini`

```ini
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /proc/self/fd/2
error_reporting = E_ALL

memory_limit = 512M
upload_max_filesize = 256M
post_max_size = 260M
max_file_uploads = 20
max_execution_time = 300
max_input_time = 600
max_input_vars = 5000

date.timezone = UTC
default_charset = UTF-8
cgi.fix_pathinfo = 0

opcache.enable = 1
opcache.enable_cli = 1
opcache.validate_timestamps = 0
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 32
opcache.max_accelerated_files = 20000
opcache.jit = off

realpath_cache_size = 4096K
realpath_cache_ttl = 600
```

### 10.2 `docker/php-fpm/www.conf`

```ini
[www]
user = www-data
group = www-data

listen = 127.0.0.1:9000
listen.allowed_clients = 127.0.0.1

clear_env = no
catch_workers_output = yes
decorate_workers_output = no

request_terminate_timeout = 600s

pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500

access.log = /proc/self/fd/2
security.limit_extensions = .php

php_admin_value[error_log] = /proc/self/fd/2
php_admin_flag[log_errors] = on
```

`clear_env = no` wajib agar environment Coolify sampai ke PHP.

Verifikasi:

```bash
php-fpm -tt
nginx -t
```

---

## 11. Supervisor

`docker/supervisord.conf`:

```ini
[supervisord]
nodaemon = true
user = root
pidfile = /tmp/supervisord.pid
logfile = /dev/null
logfile_maxbytes = 0

[unix_http_server]
file = /var/run/supervisor.sock
chmod = 0700

[rpcinterface:supervisor]
supervisor.rpcinterface_factory = supervisor.rpcinterface:make_main_rpcinterface

[supervisorctl]
serverurl = unix:///var/run/supervisor.sock

[program:php-fpm]
command = /usr/local/sbin/php-fpm -F
priority = 10
autostart = true
autorestart = true
startsecs = 3
stopasgroup = true
killasgroup = true
stdout_logfile = /dev/stdout
stdout_logfile_maxbytes = 0
stderr_logfile = /dev/stderr
stderr_logfile_maxbytes = 0

[program:nginx]
command = /usr/sbin/nginx -g "daemon off;"
priority = 20
autostart = true
autorestart = true
startsecs = 3
stopasgroup = true
killasgroup = true
stdout_logfile = /dev/stdout
stdout_logfile_maxbytes = 0
stderr_logfile = /dev/stderr
stderr_logfile_maxbytes = 0

[program:queue-default]
command = /usr/local/bin/php -d memory_limit=1024M /var/www/html/artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=300 --max-time=3600 --memory=900 --no-interaction
priority = 30
autostart = true
autorestart = true
stopasgroup = true
killasgroup = true
stdout_logfile = /dev/stdout
stdout_logfile_maxbytes = 0
stderr_logfile = /dev/stderr
stderr_logfile_maxbytes = 0

[program:queue-backups]
command = /usr/local/bin/php -d memory_limit=512M /var/www/html/artisan queue:work backups --queue=backups --sleep=3 --tries=1 --timeout=7200 --max-time=86400 --memory=480 --no-interaction
priority = 40
autostart = true
autorestart = true
stopasgroup = true
killasgroup = true
stopwaitsecs = 7300
stdout_logfile = /dev/stdout
stdout_logfile_maxbytes = 0
stderr_logfile = /dev/stderr
stderr_logfile_maxbytes = 0

[program:scheduler]
command = /usr/local/bin/php /var/www/html/artisan schedule:work --no-interaction
priority = 50
autostart = true
autorestart = true
stopasgroup = true
killasgroup = true
stdout_logfile = /dev/stdout
stdout_logfile_maxbytes = 0
stderr_logfile = /dev/stderr
stderr_logfile_maxbytes = 0
```

Ketentuan:

1. `queue-default` memakai koneksi `database`, queue `default`, timeout 300 detik.
2. `queue-backups` memakai koneksi `backups`, queue `backups`, timeout 7200 detik.
3. `DB_QUEUE_RETRY_AFTER=600` harus lebih besar dari timeout 300.
4. `BACKUPS_QUEUE_RETRY_AFTER=7300` harus lebih besar dari timeout 7200.
5. Hanya satu scheduler.
6. Jangan memakai `schedule:run` cron tambahan.

---

## 12. Entrypoint

`docker/entrypoint.sh`:

```bash
#!/bin/sh
set -eu

cd /var/www/html
umask 002

mkdir -p \
    storage/app/public \
    storage/app/private/backups \
    storage/app/private/backup-temp \
    storage/app/private/installation \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if [ ! -f storage/.docker-initialized ] || [ "$(stat -c '%u' storage)" != "33" ]; then
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R ug+rwX storage bootstrap/cache
    touch storage/.docker-initialized
    chown www-data:www-data storage/.docker-initialized
fi

if [ ! -e public/storage ] && [ ! -L public/storage ]; then
    ln -s ../storage/app/public public/storage
fi

exec "$@"
```

Entrypoint tidak:

- Menulis `.env`.
- Menjalankan `key:generate`.
- Menjalankan migrasi/seeding.
- Menjalankan `storage:link` non-idempotent.
- Menghapus data atau backup.
- Mengubah owner seluruh volume pada setiap boot.

---

## 13. Health check

`docker/healthcheck.sh`:

```bash
#!/bin/sh
set -eu

curl -fsS --max-time 5 http://127.0.0.1/up >/dev/null

if supervisorctl status | grep -Eq 'FATAL|BACKOFF|STOPPED|EXITED'; then
    exit 1
fi

exit 0
```

Docker:

```dockerfile
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
  CMD ["/usr/local/bin/healthcheck.sh"]
```

Coolify:

- Health check path: `/up`
- Scheme: `http`
- Port: `80`

Catatan: `/up` adalah liveness, bukan readiness database. Status queue/scheduler dilihat dari `/admin/backup`.

---

## 14. Environment variables Coolify

### 14.1 Aplikasi

| Variable                 | Nilai                                     |
| ------------------------ | ----------------------------------------- |
| `APP_NAME`               | Nama paroki                               |
| `APP_ENV`                | `production` untuk staging dan production |
| `APP_KEY`                | Secret unik per environment               |
| `APP_DEBUG`              | `false`                                   |
| `APP_URL`                | URL HTTPS environment                     |
| `APP_DISPLAY_TIMEZONE`   | `Asia/Pontianak`                          |
| `APP_LOCALE`             | `id`                                      |
| `APP_FALLBACK_LOCALE`    | Konfigurasi aktif                         |
| `LOG_CHANNEL`            | `stderr`                                  |
| `LOG_LEVEL`              | `info`                                    |
| `INERTIA_SSR_ENABLED`    | `false`                                   |
| `TELESCOPE_ENABLED`      | `false`                                   |
| `HOMEPAGE_ILLUSTRATIONS` | `false`                                   |

### 14.2 Database

| Variable        | Nilai                     |
| --------------- | ------------------------- |
| `DB_CONNECTION` | `mysql`                   |
| `DB_HOST`       | Internal hostname Coolify |
| `DB_PORT`       | `3306`                    |
| `DB_DATABASE`   | Nama database environment |
| `DB_USERNAME`   | User database environment |
| `DB_PASSWORD`   | Secret environment        |
| `DB_COLLATION`  | `utf8mb4_0900_ai_ci`      |

Jangan set `DB_URL`.

### 14.3 Operasional Laravel

| Variable                    | Nilai                                              |
| --------------------------- | -------------------------------------------------- |
| `SESSION_DRIVER`            | `database`                                         |
| `SESSION_SECURE_COOKIE`     | `true`                                             |
| `CACHE_STORE`               | `database`                                         |
| `QUEUE_CONNECTION`          | `database`                                         |
| `DB_QUEUE_RETRY_AFTER`      | `600`                                              |
| `BACKUPS_QUEUE_CONNECTION`  | `backups`                                          |
| `BACKUPS_QUEUE`             | `backups`                                          |
| `BACKUPS_QUEUE_RETRY_AFTER` | `7300`                                             |
| `BACKUPS_DISK`              | `backups`                                          |
| `BACKUPS_MYSQLDUMP_PATH`    | `/usr/bin/mysqldump`                               |
| `BACKUPS_MYSQL_PATH`        | `/usr/bin/mysql`                                   |
| `FILESYSTEM_DISK`           | `local`                                            |
| `DOCUMENTS_DISK`            | `local`                                            |
| `INSTALLER_ENABLED`         | `true` saat instalasi pertama, setelah itu `false` |
| `INSTALLER_WRITE_ENV`       | `false`                                            |
| `MAIL_*`                    | Konfigurasi SMTP environment                       |
| `APP_VERSION`               | Opsional                                           |
| `APP_COMMIT`                | Opsional                                           |

`ADMIN_EMAIL` dan `ADMIN_PASSWORD` tidak diisi; admin dibuat melalui wizard.

---

## 15. Runbook Coolify

### 15.1 MySQL resource

1. Buat MySQL 8.4 per environment.
2. Aktifkan persistent volume.
3. Matikan public access.
4. Catat internal hostname, database, user, password.
5. Verifikasi kolasi:

   ```sql
   SELECT @@character_set_database, @@collation_database;
   ```

6. Staging dan production tidak boleh memakai database yang sama.

### 15.2 Application resource

| Field                     | Nilai                                                               |
| ------------------------- | ------------------------------------------------------------------- |
| Environment               | staging atau production                                             |
| Source                    | Public Git Repository                                               |
| Repository                | `https://github.com/Rumahkodingku/website-paroki-hspmtb-putussibau` |
| Branch staging            | `staging`                                                           |
| Branch production         | `main`                                                              |
| Build Pack                | `Dockerfile`                                                        |
| Base Directory            | `/`                                                                 |
| Dockerfile Location       | `/Dockerfile`                                                       |
| Docker Build Stage Target | kosong                                                              |
| Ports Exposes             | `80`                                                                |
| Health Check Path         | `/up`                                                               |
| Stop Grace Period         | `3600` detik                                                        |
| Domain                    | Domain environment                                                  |

### 15.3 Persistent storage

1. Tambah **Volume Mount**.
2. Name: `storage`.
3. Destination: `/var/www/html/storage`.
4. Tidak ada mount ke `public/storage`; symlink dibuat entrypoint.
5. Satu volume per environment.

### 15.4 Deploy pertama

1. Isi semua environment variable.
2. Buat `APP_KEY` unik:

   ```bash
   php artisan key:generate --show
   ```

   Jalankan di mesin lokal dengan PHP 8.3 atau container sekali pakai. Jangan menyimpan nilai di Git.
3. Set `INSTALLER_ENABLED=true`.
4. Deploy.
5. Buka `/install` dan selesaikan wizard.
6. Pastikan kredensial database yang diisi wizard sama dengan environment Coolify.
7. Verifikasi `storage/app/private/installation/status.json` berstatus `completed`.
8. Set `INSTALLER_ENABLED=false`.
9. Redeploy.
10. Verifikasi login admin, halaman publik, media, jadwal, SEO, queue, scheduler, dan backup.

### 15.5 Release dan migrasi

1. Buat backup database.
2. Jalankan:

   ```bash
   php artisan migrate --force
   ```

   melalui Coolify terminal atau one-off command.
3. Jangan pernah `migrate:fresh` di staging/production.
4. Rollback: redeploy commit sebelumnya; restore backup bila migrasi destruktif.
5. Tidak ada migrasi otomatis saat container start.

### 15.6 Operasional

1. Lihat log container di Coolify.
2. Periksa status proses:

   ```bash
   supervisorctl status
   ```

3. Diagnostik:

   ```bash
   php artisan about
   ```

4. Jangan deploy saat backup/restore panjang berjalan.
5. Salin arsip backup ke offsite secara berkala.
6. Monitor ukuran volume storage.

---

## 16. First install, release, dan rollback

### 16.1 First install

1. Database kosong.
2. `APP_KEY` tersedia.
3. `APP_URL` benar.
4. `INSTALLER_ENABLED=true`.
5. `INSTALLER_WRITE_ENV=false`.
6. Buka `/install`.
7. Selesaikan wizard.
8. Verifikasi status file di volume.
9. Set `INSTALLER_ENABLED=false`.

### 16.2 Release

1. Backup database.
2. Deploy commit baru.
3. Tunggu health check.
4. Jalankan migrasi manual.
5. Verifikasi halaman publik dan admin.
6. Periksa log queue/scheduler.

### 16.3 Rollback

1. Redeploy commit sebelumnya.
2. Jika migrasi mengubah skema secara destruktif, restore backup database.
3. Jangan menjalankan `migrate:rollback` tanpa memeriksa reversibilitas dan backup.
4. File media di volume tidak ikut rollback otomatis.

---

## 17. Pengujian

### 17.1 Build

```bash
docker build --pull -t hspmtb-paroki:local .
```

Verifikasi:

- Build sukses tanpa `.env`.
- `composer install` memakai lockfile dan `--no-dev`.
- `npm ci` memakai lockfile.
- `npm run build` sukses.
- `public/build/manifest.json` ada.
- `php -m` memuat ekstensi yang dibutuhkan.
- `php-fpm -tt` sukses.
- `nginx -t` sukses.
- `mysqldump --version` dan `mysql --version` sukses.
- Image tidak memuat `.env`, `node_modules`, test result, atau credential.
- `composer check-platform-reqs --no-dev` sukses.

### 17.2 Runtime smoke test

Gunakan MySQL 8 disposable/host dan volume sementara.

- Container sehat.
- `/up` HTTP 200.
- `/` mengembalikan wizard atau halaman publik sesuai status installer.
- CSS/JS dari `public/build` terlayani.
- Koneksi MySQL berhasil.
- `php artisan about` berjalan.
- File storage tetap ada setelah container dibuat ulang dengan volume yang sama.
- Symlink `public/storage` berfungsi.
- `/.env`, `/vendor/...`, `/storage/app/private/...` tidak dapat diakses.
- Upload media 3 MB dan dokumen 10 MB berhasil.
- Upload backup 256 MB tidak ditolak Nginx/PHP sebelum Laravel.

### 17.3 Proses

- `supervisorctl status` menunjukkan semua program `RUNNING`.
- `queue-default` memproses job.
- `queue-backups` memproses job backup pada database disposable.
- `schedule:work` menjalankan `schedule:list` dan heartbeat.
- SIGTERM menghentikan container dengan graceful.
- Tidak ada zombie process.
- Log tampil di stdout/stderr.

### 17.4 Regression

```bash
composer ci:check
vendor/bin/pint --dirty --format agent
```

Jalankan test installer yang berubah. Setelah itu minta pengguna menjalankan full suite:

```bash
php artisan test --compact
```

Jangan mengklaim test lulus bila test database tidak tersedia.

---

## 18. Dokumentasi dan keputusan

### 18.1 Dokumen baru

`docs/deployment/docker-coolify.md`:

- Arsitektur dan alasan satu container.
- Prasyarat.
- Build image.
- Environment variables.
- Langkah Coolify web/MySQL/volume/health.
- First install.
- Release/migrasi/rollback.
- Backup/restore.
- Logs dan troubleshooting.
- Batasan.

### 18.2 `docs/DEVELOPMENT.md`

Ganti §3.3 agar menyatakan:

1. Local development tetap native.
2. Docker tidak diwajibkan untuk developer.
3. Dockerfile hanya untuk staging/production via Coolify.
4. `composer dev` tetap jalur pengembangan utama.
5. Tautkan `docs/deployment/docker-coolify.md`.

### 18.3 `docs/DECISIONS.md`

Tambah **D-106 — Docker deployment untuk Coolify**:

1. D-15 tetap berlaku untuk local development/CI.
2. Dockerfile hanya untuk deployment.
3. Topologi satu container supervisord.
4. Persistent storage `storage/`.
5. `INSTALLER_WRITE_ENV=false` di container.
6. Guard Telescope.
7. MySQL 8.4 client.
8. Kolasi `utf8mb4_0900_ai_ci`.
9. Satu scheduler per environment.

Jangan menghapus atau menulis ulang D-15.

### 18.4 `docs/backup-recovery.md`

1. Koreksi command worker backup:

   ```bash
   php artisan queue:work backups --queue=backups --tries=1 --timeout=7200
   ```

2. Jelaskan scheduler container memakai `schedule:work`, bukan cron tambahan.
3. Tautkan deployment doc untuk nilai PHP/Nginx container.

### 18.5 Tidak diubah

- `AGENTS.md` tidak diubah kecuali diminta.
- `README.md` tidak dibuat.
- `docs/specifications/spec-docker-deployment.md` tetap sebagai spesifikasi.

---

## 19. Urutan eksekusi

1. Buat branch `feat/docker-coolify-deployment`.
2. Patch `bootstrap/providers.php`.
3. Patch `bootstrap/app.php`.
4. Patch `config/installer.php`.
5. Patch `InstallationRunner` dan controller installer.
6. Tambah test flag dan kolasi.
7. Jalankan test installer terkait dan Pint.
8. Buat `Dockerfile`, `.dockerignore`, dan seluruh file `docker/`.
9. Build image lokal.
10. Perbaiki build sampai lulus.
11. Jalankan smoke test runtime, storage, keamanan, queue, scheduler, dan signal.
12. Tulis `docs/deployment/docker-coolify.md`.
13. Update `docs/DEVELOPMENT.md`, `docs/DECISIONS.md`, dan `docs/backup-recovery.md`.
14. Jalankan `composer ci:check`.
15. Laporkan hasil aktual dan batasan.

---

## 20. Risiko dan batasan

1. **Satu container = satu unit restart.** Web, queue, dan scheduler berhenti bersamaan saat deploy.
2. **Tidak bisa scaling horizontal.** Replica lebih dari satu akan menggandakan scheduler dan queue.
3. **`/up` hanya liveness.** Database, queue, dan scheduler tidak diperiksa endpoint tersebut.
4. **Restore bisa berjalan sampai 7200 detik.** Stop grace Coolify maksimum 3600 detik; deploy saat restore dapat memutus proses. Jangan deploy selama backup/restore.
5. **Build membutuhkan jaringan.** Composer, npm, Bunny Fonts, dan repository MySQL APT.
6. **MySQL APT repository menambah supply chain.** Versi package dipin dan kebijakan update dicatat.
7. **Worker image processing memakai 1 GB.** Container sebaiknya diberi limit memori minimal 2 GB.
8. **Volume storage harus cukup.** Media, dokumen, dan backup lokal berada di volume yang sama.
9. **Backup lokal bukan disaster recovery.** Salinan offsite tetap wajib.
10. **`INSTALLER_WRITE_ENV=false` mengubah cara installer menyimpan konfigurasi.** Semua environment harus benar sebelum instalasi.
11. **Staging memakai `APP_ENV=production`.** Label environment dibedakan lewat resource/domain Coolify, bukan `APP_ENV`.
12. **Tidak ada CI build image atau publish registry.** Di luar scope.
13. **`composer install --no-dev` menghapus Telescope.** Debugging produksi harus mengandalkan log, bukan Telescope.

---

## 21. Acceptance criteria

1. Image dibangun dari root `Dockerfile` tanpa `.env`.
2. Nginx + PHP-FPM melayani aplikasi dengan asset hasil Vite.
3. Semua ekstensi PHP yang terbukti dibutuhkan tersedia.
4. MySQL 8 diakses lewat internal hostname; kolasi `utf8mb4_0900_ai_ci`.
5. Media, dokumen privat, status installer, dan backup bertahan setelah redeploy.
6. Private storage dan backup tidak dapat diakses HTTP.
7. Queue default dan queue backup diproses dengan timeout/retry yang benar.
8. Scheduler berjalan satu kali per environment.
9. Health check Coolify `/up` bekerja.
10. Staging dan production memakai image yang sama dengan branch, secret, database, domain, dan volume masing-masing.
11. Docker tidak menjadi syarat local development; `composer dev` tetap berjalan.
12. Tidak ada secret di Git, image layer, build args, atau log.
13. Tidak ada migrasi/destructive action otomatis saat build atau startup.
14. Perubahan business logic tidak dilakukan; hanya guard deployment dan perbaikan kolasi installer.
