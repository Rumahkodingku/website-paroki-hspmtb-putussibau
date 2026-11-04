# SPEC: Dockerization & Coolify Deployment untuk Website Paroki HSPMTB Putussibau

- **Dokumen:** `spec-docker-deployment.md`
- **Repository:** `Rumahkodingku/website-paroki-hspmtb-putussibau`
- **Target branch awal:** branch kerja khusus; jangan langsung mengubah `main`
- **Target platform:** Coolify self-hosted v4.5.3 di Docker pada WSL Ubuntu
- **Tujuan:** memberi instruksi eksekusi kepada AI coding agent untuk menambahkan dukungan container production yang dapat dipakai oleh environment `staging` dan `production`.
- **Status:** Implementation specification; agent wajib memvalidasi ulang repository sebelum mengedit.

---

## 1. Instruksi utama untuk AI agent

Bertindak sebagai senior DevOps / Laravel infrastructure engineer. Implementasikan Docker deployment untuk codebase ini berdasarkan kondisi repository terkini, bukan dengan menyalin template Docker Laravel generik.

**Hasil akhir yang diminta:** aplikasi Laravel berjalan sebagai container web yang production-ready di Coolify menggunakan Dockerfile, dengan Nginx sebagai HTTP server dan PHP-FPM sebagai PHP runtime. Gunakan multi-stage build agar dependency dan tool build tidak ikut ke runtime image bila tidak diperlukan. Sediakan proses queue worker dan scheduler sebagai proses/layanan terpisah yang bisa dijalankan dengan image aplikasi yang sama. Jangan mengubah arsitektur/domain aplikasi di luar kebutuhan deployment.

Agent harus:

1. Membaca `AGENTS.md`, `.ai/rules/index.md` jika tersedia, semua rules yang berlaku pada file yang akan diubah, `docs/DEVELOPMENT.md`, `docs/DECISIONS.md`, `docs/backup-recovery.md`, `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `vite.config.ts`, `phpunit.xml`, `bootstrap/app.php`, konfigurasi Laravel yang relevan, migrations, dan kode upload/media/backup.
2. Memvalidasi fakta versi PHP dari `composer.json` dan `composer.lock`; **baseline yang saat ini tercatat adalah PHP `^8.3`**. Jangan menaikkan versi minimum ke 8.4 atau lebih tinggi tanpa bukti kompatibilitas dan alasan yang terdokumentasi.
3. Memeriksa semua ekstensi PHP yang benar-benar dibutuhkan oleh dependency dan aplikasi. Jangan menebak daftar ekstensi hanya berdasarkan template Laravel.
4. Mengidentifikasi binary sistem yang diperlukan oleh fitur backup/restore. Codebase mengharapkan `mysqldump` dan `mysql` tersedia dan menjalankan keduanya melalui `BACKUPS_MYSQLDUMP_PATH` serta `BACKUPS_MYSQL_PATH`.
5. Mengimplementasikan, membangun, menjalankan, dan menguji Docker image secara lokal sebelum menyatakan selesai.
6. Tidak melakukan push, merge, deploy ke Coolify, mengubah secrets, atau menjalankan migrasi pada database staging/production tanpa instruksi eksplisit user.
7. Tidak membuat `.env`, `APP_KEY`, kredensial database, atau nilai secret lalu menyimpannya ke Git.

Jika ada keputusan yang tidak bisa dibuktikan dari source, cari dokumentasi resmi versi dependency yang terpasang dan catat asumsi serta alasannya di laporan implementasi.

## 2. Hasil analisis codebase saat penyusunan spec

Analisis ini didasarkan pada branch `main` yang diperiksa pada 3 November 2026. Agent harus mengulang pemeriksaan terhadap working tree aktual sebelum implementasi karena source dapat berubah.

### 2.1 Stack yang teridentifikasi

| Area                | Temuan source                                                                                                                                           | Dampak untuk Docker                                                                                                   |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| Backend             | Laravel `^13.17`                                                                                                                                        | PHP runtime harus kompatibel dengan constraint Composer.                                                              |
| PHP                 | `composer.json` menetapkan `^8.3`                                                                                                                       | Gunakan PHP 8.3 sebagai baseline kompatibilitas; versi patch image boleh dipin secara reproducible.                   |
| Frontend            | React 19, Inertia React 3                                                                                                                               | Build frontend dilakukan saat image build.                                                                            |
| Build tooling       | Vite `^8.3.5`, Tailwind CSS v4, Wayfinder                                                                                                               | Node.js dibutuhkan di build stage.                                                                                    |
| Package manager JS  | npm; `package-lock.json` tersedia                                                                                                                       | Gunakan `npm ci`, bukan `npm install`.                                                                                |
| PHP dependency      | `composer.lock` tersedia                                                                                                                                | Gunakan `composer install` dari lockfile.                                                                             |
| Database            | `.env.example` menetapkan `DB_CONNECTION=mysql`; dokumentasi menetapkan MySQL 8                                                                         | Jangan mengganti ke PostgreSQL/MariaDB tanpa perubahan aplikasi terpisah.                                             |
| Charset/collation   | dokumentasi project mensyaratkan `utf8mb4_0900_ai_ci`                                                                                                   | Gunakan MySQL 8.x yang mendukung collation tersebut.                                                                  |
| Cache/session/queue | Default project menggunakan database untuk cache, session, dan queue                                                                                    | Redis tidak wajib untuk deployment awal.                                                                              |
| File storage        | disk `local` berada di `storage/app/private`; disk `public` berada di `storage/app/public`                                                              | Kedua direktori perlu persistent storage.                                                                             |
| Backup storage      | disk `backups` berada di `storage/app/private/backups`; workspace di `storage/app/private/backup-temp`                                                  | Persistensikan `storage` secara aman; backup lokal tetap perlu salinan off-server.                                    |
| Backup tools        | fitur backup menjalankan `mysqldump` dan `mysql`                                                                                                        | Runtime image perlu MySQL client tools yang sesuai/kompatibel.                                                        |
| Upload limit        | `docs/DEVELOPMENT.md` mencatat target upload media 3 MB; `docs/backup-recovery.md` menyebut upload arsip 256 MB                                         | PHP dan Nginx harus memiliki limit dan timeout yang sengaja dikonfigurasi, bukan default yang terlalu kecil.          |
| Health route        | `bootstrap/app.php` mendaftarkan health endpoint `/up`                                                                                                  | Gunakan `/up` sebagai health check HTTP jika responsnya diverifikasi sesuai kebutuhan.                                |
| Timezone            | `config/app.php` mewajibkan timezone aplikasi UTC dan `APP_DISPLAY_TIMEZONE=Asia/Pontianak`                                                             | Jangan mengubah timezone aplikasi ke WIB; atur container OS ke UTC.                                                   |
| Inertia SSR         | `INERTIA_SSR_ENABLED=false` dan keputusan D-26 menonaktifkan SSR                                                                                        | Tidak perlu Node SSR server dalam deployment awal. Jangan menjalankan `npm run build:ssr`.                            |
| Scheduled tasks     | `bootstrap/app.php::withSchedule()` mendaftarkan publikasi artikel, backup heartbeat, backup schedule, verification, prune, dan queue/telescope pruning | Scheduler harus berjalan terus-menerus atau dijalankan oleh cron eksternal setiap menit.                              |
| Queue               | default worker dan queue `backups` khusus                                                                                                               | Sediakan worker untuk `backups,default` dengan timeout panjang sesuai runbook.                                        |
| Installer           | dokumentasi DEVELOPMENT menyebut `/install` sebagai wizard instalasi satu kali; prasyarat database kosong dan `APP_KEY` tersedia                        | Deployment pertama wajib mengikuti runbook installer. Jangan otomatis migrate/seed pada image startup.                |
| Restore             | restore bersifat destruktif; aplikasi memakai lock/maintenance flow khusus                                                                              | Container lifecycle tidak boleh menjalankan cleanup yang menghapus data atau menghentikan worker restore sembarangan. |

### 2.2 Temuan penting dari source

- `composer.json` memakai PHP `^8.3`, Laravel `^13.17`, `inertiajs/inertia-laravel ^3.0`, dan `laravel/wayfinder`.
- `package.json` menyatakan project ESM (`"type": "module"`), menggunakan script `build: vite build`, dan memiliki dependency lint/test yang tidak perlu dipasang ke final runtime image.
- `vite.config.ts` memanggil Laravel Vite plugin, Inertia Vite plugin, React plugin, React Compiler preset, Tailwind Vite plugin, Wayfinder plugin, dan Bunny Fonts. Build stage harus memiliki akses jaringan bila font plugin mengambil font saat build; agent harus memverifikasi hasil build dan dokumentasi plugin.
- `phpunit.xml` memaksa `DB_CONNECTION=mysql` dan test database `website_paroki_hspmtb_test`. Image build tidak boleh menjalankan test yang memerlukan database sebagai bagian dari `docker build` kecuali dibuat pipeline test terpisah yang menyediakan MySQL.
- `config/filesystems.php` mengatur `local`, `public`, dan `backups`. Semua file upload/media dan backup harus tetap tersedia setelah container redeploy.
- `config/backups.php` menetapkan queue default `backups`, `retry_after` khusus 7300 detik melalui queue config, dump timeout 3600 detik, restore timeout 7200 detik, dan batas upload backup 256 MB.
- `docs/backup-recovery.md` mensyaratkan `mysqldump`, `mysql`, worker `php artisan queue:work --queue=backups,default --tries=1 --timeout=7200`, serta scheduler `php artisan schedule:run` setiap menit.
- `bootstrap/app.php` health endpoint ialah `/up`; schedule entries meliputi `posts:publish-scheduled`, `backups:heartbeat`, `backups:run-scheduled`, `BackupWorkerHeartbeatJob`, `backups:verify`, dan `backups:prune`.
- `docs/DEVELOPMENT.md` saat ini menyebut “There is no Docker in this project” dan `docs/DECISIONS.md` D-15 menyatakan Docker untuk **development** dibatalkan agar CI tidak membayar biaya service container MySQL. Ini bukan larangan permanen untuk deployment. Implementasi harus merevisi dokumentasi dengan membedakan native local development dari Docker deployment. Jangan menghapus kemampuan menjalankan project dengan `composer dev` secara native.
- `.gitignore` mengecualikan `.env`, `vendor`, `node_modules`, `public/build`, `public/storage`, dan file runtime installer; jangan mengubah proteksi secret tersebut.

### 2.3 Fakta yang belum boleh diasumsikan

Agent wajib memeriksa source aktual untuk:

- Ekstensi PHP yang diperlukan (termasuk ekstensi image processing seperti GD atau Imagick jika dipakai), dan apakah `intl`, `bcmath`, `exif`, `fileinfo`, `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `curl`, `zip`, `sodium`, `pcntl` benar-benar diperlukan.
- Apakah image-processing menggunakan Imagick, GD, atau keduanya.
- Apakah aplikasi memerlukan `ffmpeg`, `ffprobe`, `ghostscript`, atau binary lain untuk fitur spesifik; jangan memasangnya tanpa bukti.
- Apakah worker default perlu dipisahkan dari backup worker atau dapat memakai satu worker dengan queue priorities yang sesuai.
- Apakah backup commands benar-benar menggunakan `mysqldump`/`mysql` yang kompatibel dengan MySQL 8 dan apakah opsi client perlu dikonfigurasi.
- Apakah ada kebutuhan cron selain `schedule:run`.
- Ukuran upload maksimal aktual pada validation rules seluruh modul, bukan hanya DEVELOPMENT.md.

## 3. Keputusan arsitektur target

### 3.1 Bentuk deployment

Target yang disarankan adalah:

1. **Web application image**: satu image aplikasi berisi Nginx + PHP-FPM, dibangun dari root Dockerfile melalui multi-stage build.
2. **Queue worker process**: menggunakan image aplikasi yang sama dengan command Artisan worker; jalankan sebagai process/resource terpisah di Coolify atau service terpisah bila pendekatan deployment yang dipilih membutuhkannya.
3. **Scheduler process**: menggunakan image aplikasi yang sama dengan `php artisan schedule:work`, atau cron container yang menjalankan `schedule:run` setiap menit. Pilih satu pendekatan, jangan menjalankan scheduler ganda.
4. **MySQL 8**: database resource tersendiri di Coolify, persistent volume, tanpa expose port publik. Gunakan database staging dan production terpisah.
5. **Persistent application storage**: volume persisten untuk data runtime yang harus bertahan di antara redeploy.

Agent harus menentukan implementasi process supervision yang tepat berdasarkan dokumentasi resmi Coolify dan base image yang dipilih. Jangan mengandalkan shell background (`&`) untuk menjalankan banyak daemon. Jika Nginx dan PHP-FPM ada dalam satu container, gunakan process supervisor yang benar atau entrypoint PID 1 yang menangani signal/shutdown/restart dengan benar. Worker dan scheduler sebaiknya resource/proses tersendiri agar restart, scaling, log, dan health status dapat dikelola terpisah.

### 3.2 Runtime image dan build stages

Gunakan multi-stage build dengan tanggung jawab yang jelas:

- **Frontend build stage**: Node.js LTS yang memenuhi kebutuhan Vite 8; verifikasi engines dari lockfile/package metadata. Copy `package.json` dan `package-lock.json` lebih dahulu, jalankan `npm ci`, lalu copy source yang dibutuhkan dan jalankan `npm run build`.
- **Composer dependency stage**: Composer 2 pada PHP image yang kompatibel dengan runtime PHP. Jalankan `composer install` dengan lockfile dan flag production (`--no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader`). Jangan jalankan package discovery sebelum file runtime/config final tersedia kecuali memang aman dan didukung. Tangani script Composer berdasarkan kebutuhan Laravel; jangan menggunakan `--ignore-platform-reqs`.
- **Runtime stage**: PHP-FPM pada versi PHP yang telah diverifikasi, Nginx, ekstensi PHP runtime, MySQL client utilities yang diperlukan fitur backup, timezone data bila perlu, dan CA certificates. Jangan membawa Node/npm, compiler, Composer, dependency dev, atau tool build ke runtime jika tidak dibutuhkan.
- Copy output `public/build` dari frontend stage dan `vendor` dari Composer stage. Salin hanya source aplikasi yang dibutuhkan.

Pin base image ke versi eksplisit dan dokumentasikan update policy. Hindari `latest`. Gunakan image resmi atau image yang memiliki dokumentasi keamanan/patch jelas. Sebisa mungkin pin digest bila workflow project mengelola pembaruan digest; jika tidak, gunakan major/minor/patch tag yang terkontrol dan jadwalkan update.

### 3.3 Web serving

- Nginx melayani HTTP di port internal container yang dipilih dan disetel konsisten dengan konfigurasi Coolify (disarankan port 80).
- PHP-FPM hanya listen pada loopback atau Unix socket internal container; jangan expose port FastCGI ke host/public network.
- Nginx `root` harus menunjuk ke folder `/var/www/html/public` (atau path kerja tunggal yang dipilih).
- `try_files` harus meneruskan request Laravel ke `index.php` dan mendukung asset statis.
- FastCGI params harus mengatur `SCRIPT_FILENAME` dengan benar.
- Jangan mengaktifkan directory listing.
- Jangan expose `.env`, dotfiles, source, `vendor`, atau file backup.
- Nginx harus meneruskan header proxy/HTTPS dengan benar. Aplikasi sudah trust proxy `*` melalui `bootstrap/app.php`; verifikasi perilaku HTTPS dan secure cookies melalui Coolify proxy.
- Konfigurasi harus mendukung request body media dan upload backup sesuai batas yang disetujui. Jangan membuat batas sangat besar tanpa alasan; sinkronkan `client_max_body_size`, `upload_max_filesize`, `post_max_size`, `max_execution_time`, `max_input_time`, `memory_limit`, dan timeout proxy/fastcgi.
- Nginx harus memiliki access/error log ke stdout/stderr agar terlihat di Coolify. PHP-FPM dan Laravel logs juga harus dapat diobservasi melalui log container (Laravel bisa diarahkan ke stderr melalui environment `LOG_CHANNEL=stderr` setelah diverifikasi).

### 3.4 PHP configuration dan extension

- Minimal PHP `8.3` sesuai Composer constraint yang ada.
- Pasang `pdo_mysql` dan extension yang ditemukan dari Composer `ext-*`, kode aplikasi, serta dependency runtime.
- Pasang extension yang diperlukan image/media processing setelah menelusuri implementation dan package usage.
- Pastikan `pcntl` tersedia jika worker atau supervisor membutuhkan signal handling; validasi perintah queue worker.
- Sediakan konfigurasi PHP production: `expose_php=Off`, `display_errors=Off`, `log_errors=On`, timezone OS/PHP UTC (aplikasi sudah menyetel timezone UTC), OPcache aktif, dan memory/upload/time limits sesuai hasil audit.
- Konfigurasi FPM harus berjalan sebagai user non-root bila didukung image. Nginx harus drop privilege dengan benar. Hindari menjalankan seluruh aplikasi sebagai root.
- Permission writable hanya untuk `storage` dan `bootstrap/cache` (serta volume runtime lain yang benar-benar perlu). Jangan `chmod -R 777`.

### 3.5 Composer dan Node dependency

- Wajib menggunakan `composer.lock` dan `package-lock.json`.
- Wajib `composer install`, bukan `composer update`.
- Wajib `npm ci`, bukan `npm install`.
- Jangan mengubah versi dependency hanya untuk membuat build berhasil tanpa persetujuan/justifikasi.
- Project menggunakan npm. Jangan memperkenalkan pnpm/yarn atau lockfile lain.
- `npm run build` adalah frontend build command. Jangan menjalankan `npm run build:ssr` karena SSR sengaja dinonaktifkan (D-26).
- Pertimbangkan `COMPOSER_ALLOW_SUPERUSER=1` hanya untuk build stage yang memang berjalan sebagai root; lebih baik jalankan Composer dengan user non-root bila praktis.

## 4. File yang diharapkan dibuat atau diperbarui

Agent harus menjaga scope minimum. Target awal:

### Wajib dibuat

1. `Dockerfile` di root repository.
2. `.dockerignore` di root repository.
3. Direktori `docker/` hanya bila dibutuhkan, misalnya:
   - `docker/nginx/default.conf` atau template Nginx.
   - `docker/php/production.ini`.
   - `docker/php-fpm/zz-app.conf`.
   - `docker/supervisord.conf` jika diperlukan untuk web process dalam satu container.
   - entrypoint script yang kecil dan idempotent bila diperlukan.
4. `docs/deployment/docker-coolify.md` atau lokasi dokumentasi deployment yang konsisten dengan struktur docs yang sudah ada.

### Diperbarui bila relevan

- `docs/DEVELOPMENT.md`: ubah pernyataan “There is no Docker in this project” agar jelas bahwa local development tetap native (jika keputusan itu dipertahankan), sementara Docker ditambahkan untuk deployment Coolify. Update bagian command/requirements tanpa memaksa developer memakai Docker.
- `docs/DECISIONS.md`: tambahkan keputusan baru untuk Docker production deployment dan beri penjelasan bahwa D-15 tetap berlaku hanya untuk Docker-based local development/CI. Jangan menghapus atau menulis ulang sejarah D-15 secara menyesatkan.
- `README.md` tidak ada di root saat analisis awal; jangan membuat README baru kecuali memang diperlukan dan sesuai struktur dokumentasi.

### Opsional, hanya jika ada alasan

- `docker-compose.yml` / `compose.yaml` untuk local smoke test atau proses worker/scheduler. Jangan buat Compose sekadar karena Docker digunakan; Coolify Dockerfile deployment tidak membutuhkan Compose.
- Workflow CI build image. Jangan menambah workflow publikasi registry tanpa kebutuhan dan secrets yang telah disetujui.

## 5. Kontrak build Dockerfile

Dockerfile harus:

- Build dengan context root repository (`/`).
- Menggunakan build arguments hanya untuk nilai non-secret (misalnya versi Node/PHP atau build mode) dan tidak memasukkan secret saat build.
- Build sukses dengan `docker build -t hspmtb-paroki:local .` tanpa `.env` production.
- Menghasilkan image runtime yang tidak membutuhkan source map/dev server Vite.
- Mempertahankan `public/build/manifest.json` beserta seluruh assets hasil Vite.
- Menghasilkan Composer autoload optimized dan package discovery yang benar.
- Tidak menjalankan `php artisan key:generate`, `migrate`, `migrate:fresh`, `db:seed`, `storage:link` yang tidak idempotent/aman saat build, atau wizard installer ketika image dibangun.
- Tidak menyimpan `.env`, private key, token, `APP_KEY`, password, atau data database di image/layer.
- Memastikan `storage` dan `bootstrap/cache` dapat ditulis oleh user runtime.
- Memastikan volume mount runtime tidak menghapus/menghalangi file aplikasi yang diperlukan.
- Tidak membuat folder `public/storage` sebagai symlink ke target volume yang belum ada tanpa proses idempotent yang aman. `storage:link` sebaiknya dijalankan sebagai setup/release command setelah volume terpasang, atau di entrypoint secara idempotent dengan target yang diverifikasi.
- Menggunakan exec-form CMD/ENTRYPOINT dan meneruskan signal OS dengan benar.
- Menetapkan `WORKDIR` dan path secara konsisten.
- Menyediakan proses `php-fpm` dan Nginx sesuai arsitektur yang dipilih.
- Memiliki health check Docker yang tidak mengandalkan `curl` jika tidak tersedia; gunakan `curl`/`wget` setelah memastikan binary tersedia atau PHP HTTP probe yang aman. Health check harus menguji aplikasi melalui Nginx pada endpoint `/up`.

## 6. Kontrak `.dockerignore`

Minimal pertimbangkan mengecualikan:

```text
.git
.github
.ai
.agents
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
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
storage/app/private/*
bootstrap/cache/*.php
public/hot
```

Agent wajib memeriksa konsekuensi setiap ignore pattern. Jangan mengecualikan file source, lockfiles, migrations, `public/` assets statis, `storage/app/public` yang memang committed (jika ada), file konfigurasi build, atau asset yang diperlukan. Pastikan ignore pattern untuk `storage` tidak membuang direktori/file runtime yang seharusnya masuk image; direktori writable dapat dibuat di runtime.

**Peringatan:** `.dockerignore` harus menyingkirkan `.env` dan data runtime/backup lokal dari build context. Jangan memasukkan database dump/media production ke image.

## 7. Persistent storage dan volume strategy

Container filesystem bersifat ephemeral. Persistent volume minimal harus menjaga:

- `storage/app/public` untuk public media/asset upload yang dilayani melalui `/storage`.
- `storage/app/private` untuk dokumen private, backup archives, backup upload/temp, installer status, dan data private lainnya.
- `storage/logs` hanya bila log file tetap dipakai; pilihan utama adalah log ke stdout/stderr agar volume log tidak membesar.
- `bootstrap/cache` tidak harus persisten; boleh ephemeral jika owner/permission benar.

Strategi yang disarankan adalah satu persistent volume untuk keseluruhan `storage/` path agar subdirektori baru tidak hilang, dengan subdirectory `app/public`, `app/private`, `framework`, dan `logs` dibuat dan diberi permission saat startup secara idempotent. Namun agent wajib memastikan mount ke `storage/` tidak menimpa link/file yang penting dan `public/storage` symlink tetap benar.

Data sensitif:

- Jangan mengekspos `storage/app/private` melalui Nginx.
- Jangan mount backup storage ke web root.
- Backup lokal di volume yang sama bukan strategi disaster recovery; dokumentasikan kebutuhan copy ke object storage/offsite.
- Staging dan production wajib memakai volume berbeda.

## 8. Database MySQL dan environment isolation

- Gunakan resource MySQL terpisah di Coolify untuk staging dan production atau satu MySQL server dengan database/user terpisah hanya bila user secara sadar memilih opsi tersebut. Rekomendasi default: resource terpisah untuk isolasi dan lifecycle lebih sederhana.
- Version: MySQL 8.x yang mendukung `utf8mb4_0900_ai_ci`.
- Charset `utf8mb4`; collation sesuai project (`utf8mb4_0900_ai_ci`) setelah verifikasi migrations dan documented decision.
- Database port tidak boleh dipublikasikan ke internet. Akses hanya melalui internal Docker network Coolify.
- Buat password unik per environment dan simpan di Coolify secrets, bukan dokumentasi/commit.
- Host `DB_HOST` harus memakai service/container name atau internal hostname database yang disediakan Coolify, bukan `127.0.0.1` dari app container.
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_PORT=3306`, `DB_CONNECTION=mysql` disetel melalui Environment Variables.
- Jangan menggunakan `DB_URL` kecuali source configuration secara sengaja mendukungnya; `.env.example` memberi peringatan jangan mengatur `DB_URL` karena Laravel akan menggunakannya sebagai connection URL jika tersedia.
- Database staging tidak boleh menggunakan database production.
- Migrations tidak dijalankan otomatis oleh Docker build. Untuk deployment release, jalankan `php artisan migrate --force` sebagai pre-deploy/release step yang disengaja setelah backup, atau prosedur manual yang terdokumentasi. Jangan gunakan `migrate:fresh` pada staging/production.

## 9. Queue worker dan scheduler

### 9.1 Default queue worker

Periksa job yang ada dan tentukan kebutuhan worker. Minimum worker untuk antrean umum:

```bash
php artisan queue:work database --sleep=3 --tries=3 --timeout=90 --no-interaction
```

Sesuaikan berdasarkan config dan jenis job aktual. Worker harus restart saat image baru dideploy agar memuat kode terbaru (`queue:restart` atau lifecycle process yang menggantikan container).

### 9.2 Backup queue worker

Project runbook menyatakan worker backup:

```bash
php artisan queue:work --queue=backups,default --tries=1 --timeout=7200 --no-interaction
```

Pastikan koneksi queue yang dipakai adalah `backups` (berdasarkan `BACKUPS_QUEUE_CONNECTION=backups`) dan `retry_after` lebih besar daripada timeout worker, sesuai `config/queue.php` dan `config/backups.php`. Verifikasi nilai aktual dari source sebelum menyimpulkan. Jangan biarkan dua worker mengambil backup job yang sama akibat `retry_after` lebih pendek dari timeout.

### 9.3 Scheduler

Pilih salah satu:

- **Dedicated scheduler resource** menggunakan `php artisan schedule:work --no-interaction`, atau
- Cron container/service yang menjalankan `php artisan schedule:run` setiap menit.

Rekomendasi default untuk Coolify: dedicated scheduler resource dengan `schedule:work` jika stabil dan didukung; satu scheduler saja per environment. Jika memilih cron container, gunakan cron foreground/supervisor yang benar dan command `schedule:run` setiap menit. Jangan menambahkan schedule entry yang memanggil dirinya sendiri.

Scheduler wajib menjalankan semua entry dari `bootstrap/app.php`, terutama publikasi artikel dan backup. Jangan mengubah jadwal/domain logic demi Docker.

### 9.4 Heartbeats

Fitur backup memeriksa heartbeat scheduler dan worker. Verifikasi bahwa:

- scheduler process benar-benar menjalankan schedule entries;
- worker backup memproses queue yang benar;
- job heartbeat tidak hanya dijadwalkan tetapi benar-benar dieksekusi;
- status di halaman admin menunjukkan scheduler dan worker terdeteksi.

Jika dipisah menjadi beberapa Coolify resource, setiap resource harus menggunakan image/commit/env yang tepat dan mengakses MySQL serta shared storage bila dibutuhkan.

## 10. Entrypoint, release commands, dan lifecycle

Entrypoint harus minimal, aman, idempotent, serta tidak melakukan operasi database destruktif.

Tanggung jawab yang boleh dilakukan saat startup:

- membuat direktori runtime yang diperlukan;
- memastikan owner/permission runtime tepat;
- membuat `public/storage` symlink secara idempotent jika target belum benar dan tidak menghapus file yang tidak dikenal;
- menjalankan pemeriksaan konfigurasi non-destruktif;
- `exec` proses utama/supervisor.

Tanggung jawab yang **tidak boleh** dilakukan otomatis setiap container start:

- `key:generate`;
- `migrate:fresh`, `db:wipe`, atau seeding data;
- instalasi wizard atau perubahan status installer;
- `storage:link` secara non-idempotent;
- menghapus backup, media, file pengguna, atau volume;
- `php artisan down` / `up` tanpa prosedur maintenance/restore yang dirancang;
- mengubah owner seluruh volume secara rekursif pada setiap boot jika hal itu dapat membuat startup lambat atau merusak izin. Bila chown diperlukan, desain ownership saat pembuatan volume.

Buat dokumentasi urutan first boot, normal redeploy, migration release, dan rollback. APP_KEY harus stabil antardeploy dan berbeda antara staging dan production.

## 11. Environment variables

Dokumentasikan daftar variable (nama dan tujuan, jangan nilainya). Minimum:

### Aplikasi

- `APP_NAME`
- `APP_ENV` (`staging` atau `production` sesuai environment; validasi perilaku Laravel untuk nilai `staging`)
- `APP_KEY` (generated sekali, persistent, secret, unik per environment)
- `APP_DEBUG=false`
- `APP_URL`
- `APP_TIMEZONE` tidak dipakai untuk override config app; timezone aplikasi tetap UTC dari source
- `APP_DISPLAY_TIMEZONE=Asia/Pontianak`
- `APP_LOCALE=id`
- `APP_FALLBACK_LOCALE=id`
- `LOG_CHANNEL=stderr` (hanya setelah Laravel config mendukung dan diverifikasi)
- `LOG_LEVEL=info` atau nilai produksi yang disepakati
- `INERTIA_SSR_ENABLED=false`

### Database

- `DB_CONNECTION=mysql`
- `DB_HOST` internal Coolify database hostname
- `DB_PORT=3306`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- Jangan set `DB_URL` tanpa alasan.

### Operasional Laravel

- `SESSION_DRIVER=database` (atau keputusan eksplisit yang konsisten dengan migration dan cache)
- `CACHE_STORE=database`
- `QUEUE_CONNECTION=database`
- `BACKUPS_QUEUE_CONNECTION=backups`
- `BACKUPS_QUEUE=backups`
- `BACKUPS_MYSQLDUMP_PATH` dan `BACKUPS_MYSQL_PATH` sesuai lokasi binary aktual di image
- `FILESYSTEM_DISK=local`
- `DOCUMENTS_DISK=local`
- `BACKUPS_DISK=backups`
- `INERTIA_SSR_ENABLED=false`
- `TELESCOPE_ENABLED` sesuai keputusan keamanan/observability production; jangan menebak default jika provider config memiliki dampak.
- `INSTALLER_ENABLED=true` hanya selama instalasi pertama; setelah selesai set `false`.

### PHP/Nginx

- Upload/body size dan timeout harus disamakan antara Nginx dan PHP. Dokumentasikan nilai akhir yang dipilih berdasarkan audit fitur.
- Jangan memasukkan password atau APP_KEY dalam Docker build args.

`ADMIN_EMAIL` dan `ADMIN_PASSWORD` adalah data seed/installer yang sensitif. Jangan bake ke image. Ikuti installer dokumentasi yang terbaru; jangan mengaktifkan seed admin tanpa memastikan alur instalasi yang benar.

## 12. Installer dan first deployment

Dokumentasi project menyatakan installer satu kali berjalan di `/install`, membutuhkan database kosong, database dapat dihubungi, `APP_KEY`, `APP_URL`, dan dependensi terpasang. Installer menjalankan migrasi dan seed tertentu sesuai alur yang ada.

Agent harus memeriksa implementasi installer aktual sebelum menuliskan runbook. Runbook first deployment wajib:

1. Buat database MySQL 8 staging yang benar-benar kosong dan user dengan privilege sesuai kebutuhan installer/migrasi.
2. Deploy application image dan set environment variables staging, termasuk `APP_KEY` unik.
3. Set `INSTALLER_ENABLED=true` hanya saat pertama kali instalasi bila source mengharuskan.
4. Buka `/install` melalui domain staging dan selesaikan wizard sesuai prosedur aplikasi.
5. Verifikasi file status instalasi dibuat pada persistent private storage.
6. Set `INSTALLER_ENABLED=false` setelah instalasi selesai.
7. Verifikasi login admin, halaman publik, media, jadwal, SEO, queue, scheduler, dan backup.
8. Jangan pernah menjalankan installer pada database production yang sudah memiliki data.
9. Jangan menyalin database atau private storage staging ke production secara otomatis.

Jangan menggunakan `composer setup` jika DEVELOPMENT.md/installer docs menyatakan command tersebut menjalankan migrasi yang membuat database tidak lagi kosong. Perintah first setup harus sesuai dokumentasi installer terbaru.

## 13. Health checks dan observability

- Gunakan endpoint `/up` sebagai HTTP health check jika pemeriksaan source menunjukkan ia aman sebelum installer selesai; jika installer gate mengalihkan health endpoint, tentukan path health check alternatif atau buat pengecualian minimal yang aman.
- Docker HEALTHCHECK harus memeriksa Nginx HTTP endpoint dalam container.
- Coolify health check harus memakai port/path yang sama.
- Health check web tidak menggantikan health check MySQL, queue worker, dan scheduler.
- Nginx access/error logs dan PHP-FPM errors diarahkan ke stdout/stderr.
- Aplikasi harus tetap menulis log Laravel sesuai kebutuhan; jangan mematikan error logs.
- Berikan instruksi cara melihat logs setiap resource di Coolify.

## 14. Keamanan

- Runtime image tidak memuat `.env` atau secrets.
- `.dockerignore` mengecualikan `.env*` (tetap izinkan `.env.example` bila dibutuhkan dokumentasi/build, tetapi build tidak boleh menggunakannya untuk secret).
- `APP_DEBUG=false`, `expose_php=Off`, Nginx menolak akses dotfiles.
- Database tidak expose public port.
- Container web tidak berjalan sebagai root kecuali proses startup yang sangat terbatas memang membutuhkan; drop privilege sebelum menjalankan aplikasi.
- Nginx hanya melayani `public/`.
- `storage/app/private` tidak dapat diakses HTTP secara langsung.
- Jangan memasukkan admin credential, OAuth tokens, mail credentials, API keys, cloud keys, backup credentials, atau APP_KEY ke image/log.
- Jangan mengekspos backup archives melalui route/static file.
- Jangan menambahkan SSH server ke image.
- Jangan mematikan TLS/proxy security Coolify.
- Staging tidak boleh memakai APP_KEY, DB, mail, storage, atau secret production.
- Pastikan cookie/session domain staging tidak berbenturan dengan production.

## 15. Pengujian wajib

### 15.1 Build-time

- `docker build --pull -t hspmtb-paroki:local .` berhasil dari root repository tanpa `.env`.
- Tidak ada `--ignore-platform-reqs`.
- `composer install` menggunakan lockfile dan tidak memasang dev dependencies di runtime.
- `npm ci` menggunakan lockfile.
- `npm run build` berhasil.
- `public/build/manifest.json` ada di image final.
- `php -m` memperlihatkan seluruh extension runtime yang dipersyaratkan.
- `php-fpm -tt` dan `nginx -t` berhasil.
- `mysqldump --version` dan `mysql --version` berhasil.
- Runtime image tidak berisi `.env`, `node_modules`, test results, atau credentials.

### 15.2 Runtime smoke test

Jalankan container di local Docker dengan env test lokal dan MySQL 8 disposable yang collation-nya sesuai. Uji:

- web container mulai dengan status sehat;
- `/up` menghasilkan HTTP success yang diharapkan;
- `/` mengembalikan respons aplikasi/wizard yang benar;
- asset CSS/JS berhasil dilayani dari `public/build`;
- Laravel dapat membuka koneksi MySQL;
- `php artisan about`/command diagnostik berjalan (jangan tampilkan secrets dalam log);
- storage writable dan file tetap ada setelah container dibuat ulang dengan volume yang sama;
- public storage symlink bekerja;
- Nginx tidak melayani `.env`, `vendor`, private storage, atau backup.

### 15.3 Process smoke test

Uji command container image yang sama untuk:

- worker default;
- worker backup dengan `--queue=backups,default --tries=1 --timeout=7200`;
- scheduler `schedule:work` atau cron strategy terpilih.

Pastikan signal SIGTERM menghentikan proses dengan graceful, log tampil, dan proses tidak fork menjadi zombie.

### 15.4 Regression

- Jalankan check/test yang relevan dengan lingkungan yang tersedia.
- Project test suite menggunakan MySQL (`phpunit.xml`), jadi jangan mengklaim tests lulus bila test database tidak disediakan.
- Minimal jalankan lint/config validation dan smoke test Docker.
- Jangan menjalankan `migrate:fresh` terhadap database non-disposable.

## 16. Coolify setup guide yang harus ditulis agent

Dokumentasi harus memberi langkah UI Coolify dengan field yang jelas untuk versi 4.x (validasi label aktual saat dokumentasi ditulis):

### Web application resource

- Environment: `staging` atau `production`.
- Source: Public Git Repository.
- Repository URL: `https://github.com/Rumahkodingku/website-paroki-hspmtb-putussibau`.
- Branch: `staging` untuk staging setelah branch tersedia; `main` untuk production.
- Build Pack: `Dockerfile`.
- Dockerfile location: `/Dockerfile` atau field default root sesuai UI aktual.
- Base directory/build context: `/`.
- Exposed/internal port: sesuai Nginx (target disarankan 80).
- Health check path: `/up` atau path alternatif hasil validasi installer.
- Domain staging dan production berbeda.
- Environment variables dipasang pada resource dan diisi dari secrets secara manual.
- Persistent storage mount mengarah ke path `storage` yang didesain; staging dan production memakai volume masing-masing.

### MySQL resource

- MySQL 8.x.
- Persistent volume aktif.
- Database/user/password sesuai environment.
- Jangan expose public port.
- Internal network/hostname dipakai oleh aplikasi.

### Worker/scheduler resources

- Menggunakan image/commit yang sama dengan web.
- Command berbeda seperti yang didefinisikan pada bagian 9.
- Environment variables dan DB/network sama per environment.
- Shared storage hanya dipasang jika process tersebut perlu menyimpan/membaca file (backup worker perlu akses volume private yang sama dengan web).
- Worker backup harus mempunyai resource limit/timeouts yang cukup untuk job hingga 7200 detik.

Jangan mengasumsikan Coolify otomatis berbagi volume antar-resource. Jika memakai resource terpisah, dokumentasikan volume shared/network yang eksplisit dan validasi kompatibilitas Coolify.

## 17. Batasan dan non-goals

Di luar scope:

- Migrasi database dari MySQL ke PostgreSQL/MariaDB.
- Mengaktifkan Inertia SSR.
- Mengganti Laravel/Vite/React versions.
- Mengubah business logic, routes, permission, installer, backup/restore implementation.
- Mengubah workflow native development agar mewajibkan Docker.
- Menambahkan Redis tanpa kebutuhan terukur.
- Membuat production deployment, domain/DNS, Cloudflare tunnel, TLS, atau backup offsite secara otomatis.
- Menulis `.env` production.
- Mengubah volume atau data database yang sudah ada.

## 18. Dokumentasi dan keputusan D-15

Karena `docs/DEVELOPMENT.md` menyatakan tidak ada Docker dan `docs/DECISIONS.md` D-15 membatalkan Docker untuk local development/CI, agent harus memperbarui dokumentasi dengan hati-hati:

- Pertahankan alasan historis D-15: service MySQL container dan health check tidak dipakai untuk local dev/CI karena biaya CI dan collation MySQL spesifik.
- Tambahkan keputusan baru (nomor berikutnya yang tersedia, setelah memeriksa file) bahwa Dockerfile ditambahkan khusus untuk production/staging deployment via Coolify.
- Tegaskan native local development dengan `composer dev` tetap valid dan tidak diganti.
- Jelaskan Dockerfile bukan environment developer wajib dan tidak mengubah test strategy.
- Tambahkan dokumentasi Docker deployment dan runbook operasi.

## 19. Deliverables

Implementasi dianggap selesai jika repository memiliki:

- [ ] Root `Dockerfile` multi-stage yang terdokumentasi dan berhasil dibangun.
- [ ] Root `.dockerignore` yang aman dan teruji.
- [ ] Nginx config untuk Laravel public root, FastCGI, upload, proxy, dan security.
- [ ] PHP production ini/FPM config seperlunya.
- [ ] Entrypoint/supervisor config hanya jika diperlukan, idempotent dan signal-safe.
- [ ] Strategi storage persistence yang diuji.
- [ ] Definisi command worker default, backup worker, dan scheduler.
- [ ] Dokumentasi Coolify untuk staging dan production.
- [ ] Dokumentasi first install dan release/migration yang aman.
- [ ] D-15/documentation diperbarui tanpa menghapus keputusan historis.
- [ ] Build + runtime smoke tests lulus atau setiap keterbatasan dijelaskan secara eksplisit.

## 20. Acceptance criteria akhir

1. Docker image dapat dibuat dengan Dockerfile dari root project tanpa `.env` production.
2. Aplikasi berjalan di Nginx + PHP-FPM dan menggunakan asset hasil Vite build.
3. Semua PHP extension yang diperlukan tersedia.
4. MySQL 8 dapat diakses dengan internal hostname dan collation sesuai aplikasi.
5. Uploaded media, private documents, installer state, dan backup files bertahan setelah redeploy.
6. Private storage dan backup tidak dapat diakses publik.
7. Default queue dan backup queue dapat diproses sesuai timeout/retry configuration.
8. Scheduler menjalankan semua scheduled tasks satu kali per environment, tanpa duplikasi scheduler.
9. Health check Coolify bekerja.
10. Staging dan production dapat menggunakan Dockerfile yang sama namun memakai branch, secrets, database, domain, dan persistent volumes masing-masing.
11. Docker bukan syarat local development dan test suite tetap dapat berjalan native seperti sebelumnya.
12. Tidak ada secret yang masuk Git, image layer, build args, atau logs.
13. Tidak ada migrasi database/destructive action otomatis pada image build atau container startup.
14. Semua perubahan dibatasi pada deployment infrastructure/docs dan kebutuhan minimum; tidak ada perubahan business logic yang tidak diminta.

## 21. Laporan akhir AI agent

Setelah implementasi, laporkan:

- Ringkasan arsitektur final dan alasan pilihan process layout.
- Daftar file created/modified.
- PHP version, Node version, extensions dan binary yang dipasang.
- Build command dan hasilnya.
- Smoke tests yang dijalankan dan hasil aktualnya.
- Environment variables yang harus diisi manual di Coolify (nama saja, tanpa secret values).
- Langkah Coolify untuk web, MySQL, worker, scheduler, persistent volume, dan health check.
- First deployment/install steps dan langkah release/migration berikutnya.
- Keterbatasan yang belum diselesaikan.
- Pernyataan eksplisit bahwa tidak ada push/deploy/database migration yang dilakukan jika memang tidak dilakukan.

**Jangan menyatakan deployment siap production hanya karena `docker build` berhasil.** Production readiness mensyaratkan runtime, persistent volumes, database, queue, scheduler, backup/restore, health checks, dan keamanan diuji.
