# Backup & Recovery — Panduan Operasional

Dokumen ini adalah runbook untuk operator website Paroki HSPMTB Putussibau.
Spesifikasi produk ada di `docs/specifications/spec-backup-recovery.md` dan
rencana implementasinya di `docs/plan-implementation/plan-backup-recovery.md`.

## 1. Apa yang dicakup

| Komponen | Backup database | Backup lengkap |
| --- | ---: | ---: |
| Struktur dan data database aplikasi | Ya | Ya |
| Akun, role/permission, pengaturan, Audit Log | Ya | Ya |
| Media asli (`media/originals`) | Tidak | Ya |
| Varian gambar (`media/variants`) | Tidak | Ya |
| Dokumen (`documents`) | Tidak | Ya |
| Cache, session, queue, temporary | Tidak | Tidak |
| Source code, `.env`, credential, key | Tidak | Tidak |
| OS, konfigurasi server, DNS, SSL | Tidak | Tidak |

Backup disimpan di disk privat `backups` (`storage/app/private/backups`).
Backup lokal **bukan** pengganti backup infrastruktur atau salinan di lokasi
lain. Jika server hilang, backup di server yang sama juga hilang.

## 2. Format dan integritas

- Database: `hspmtb-db-YYYY-MM-DD-HHmmss.sql.gz`.
- Lengkap: `hspmtb-full-YYYY-MM-DD-HHmmss.zip`, berisi `manifest.json`,
  `database/database.sql`, dan `files/<disk>/<path>`.
- Setiap artefak memiliki SHA-256 yang disimpan di database.
- Manifest database disisipkan sebagai komentar `-- HSPMTB-MANIFEST:` di awal
  dump; MySQL mengabaikannya saat restore.
- Checksum internal membuktikan integritas terhadap kerusakan atau perubahan
  tidak sengaja. Ini bukan perlindungan anti-tamper penuh: pihak yang dapat
  mengubah arsip, manifest, dan database dapat memalsukannya.

## 3. Konfigurasi server

### 3.1 Binary database

Pastikan tersedia dan dapat dieksekusi:

```bash
which mysqldump
which mysql
mysqldump --version
mysql --version
```

Sesuaikan bila path berbeda:

```dotenv
BACKUPS_MYSQLDUMP_PATH=/usr/bin/mysqldump
BACKUPS_MYSQL_PATH=/usr/bin/mysql
```

### 3.2 Worker antrean

Backup dan restore memakai koneksi antrean `backups` agar `retry_after` lebih
panjang dari timeout job.

```bash
php artisan queue:work --queue=backups,default --tries=1 --timeout=7200
```

Jalankan di bawah Supervisor/systemd. Jangan memakai `--tries` lebih dari 1
untuk job restore.

### 3.3 Scheduler

```cron
* * * * * cd /path/ke/aplikasi && php artisan schedule:run >> /dev/null 2>&1
```

Command yang dijadwalkan:

| Command | Jadwal | Fungsi |
| --- | --- | --- |
| `backups:heartbeat` | setiap menit | Menulis heartbeat scheduler |
| `backups:run-scheduled` | setiap menit | Menjalankan slot database/full yang jatuh tempo |
| `backups:verify` | 03:00 | Menandai file hilang/berubah |
| `backups:prune` | 03:30 | Retensi dan penutupan operasi restore kedaluwarsa |

Status kesiapan scheduler/worker terlihat di `/admin/backup`. Jadwal yang
tersimpan tidak dianggap aktif bila heartbeat tidak terdeteksi.

### 3.4 Batas PHP dan web server

Untuk upload arsip eksternal 256 MB:

```ini
upload_max_filesize = 256M
post_max_size = 260M
max_input_time = 600
memory_limit = 512M
```

Sesuaikan juga batas body/timeout reverse proxy. Nilai lokal pengembangan saat
ini lebih kecil (2M/8M), jadi upload besar akan ditolak PHP sebelum Laravel.
Ubah `php.ini` lokal sebelum menguji upload besar.

### 3.5 Permission database

Dump membutuhkan `SELECT`, `SHOW VIEW`, `TRIGGER`, dan (bila ada) `EVENT`.
Restore membutuhkan `CREATE`, `DROP`, `ALTER`, `INSERT`, `UPDATE`, `DELETE`,
`INDEX`, `REFERENCES`, `CREATE VIEW`, `SHOW VIEW`, `CREATE ROUTINE`,
`ALTER ROUTINE`, dan `TRIGGER`.

Target database selalu berasal dari koneksi aplikasi; tidak ada input target
dari UI.

## 4. Jadwal dan retensi awal

- Backup database harian pukul 02:00 `Asia/Pontianak`.
- Backup lengkap mingguan hari Minggu pukul 02:30 `Asia/Pontianak`.
- Retensi database 14 hari.
- Retensi lengkap 4 minggu.
- Backup manual tidak dihapus otomatis.
- Unggahan gagal dibersihkan setelah 7 hari.
- Safety backup restore dilindungi 30 hari.

Semua dapat diubah di `/admin/backup/pengaturan` oleh pemegang
`backups.settings`.

## 5. Prosedur restore

Restore adalah operasi destruktif. Lakukan di staging terlebih dahulu.

1. Buka `/admin/backup`, pilih backup yang valid.
2. Klik **Siapkan Pemulihan**. Sistem membuat safety backup dan memvalidasinya.
3. Tunggu status menjadi **Siap dikonfirmasi**. Tombol konfirmasi final belum
   aktif sebelum safety backup berhasil.
4. Buka dialog konfirmasi, centang pemahaman, dan ketik `PULIHKAN DATA`.
5. Klik **Konfirmasi Pemulihan**.
6. Pantau status. Hasil akhir:
   - `success`: database dan file (untuk full) berhasil diverifikasi;
   - `partial`: database berhasil, sebagian file gagal;
   - `failed`: restore gagal; gunakan safety backup untuk recovery.
7. Setelah selesai, periksa login, halaman publik, media, dan Audit Log.

Selama restore destruktif berjalan, request non-GET ditolak dengan 503. Ini
adalah pengganti `php artisan down`; `down` akan menghentikan worker yang
sedang menjalankan restore.

### 5.1 Jika restore gagal

- Jangan jalankan restore kedua sebelum membaca status dan log.
- Gunakan safety backup yang tercatat pada operasi restore.
- Jika database berhasil tetapi file gagal (`partial`), file yang ada di arsip
  sudah diganti; file di luar arsip dipertahankan.
- Perbaiki penyebab (disk penuh, permission, binary) sebelum mencoba lagi.

### 5.2 Lock macet

Jika worker berhenti di tengah operasi:

```bash
php artisan backups:unlock --force
```

Perintah ini membersihkan lock `backups:exclusive` dan flag maintenance. Gunakan
hanya setelah memastikan tidak ada worker yang masih berjalan.

## 6. Upload arsip eksternal

- Hanya `.sql.gz` dan `.zip` dengan format backup aplikasi ini.
- Upload tidak langsung memulihkan data.
- Arsip divalidasi: format, manifest, versi, checksum, daftar entry, path,
  symlink, jumlah file, ukuran ekstraksi, dan rasio kompresi.
- Arsip invalid dihapus dan dicatat sebagai gagal.

## 7. Verifikasi dan rekonsiliasi

```bash
php artisan backups:verify
php artisan backups:reconcile
```

`backups:verify` memeriksa file yang hilang atau berubah ukuran.
`backups:reconcile` melaporkan file tanpa metadata (orphan) dan metadata tanpa
file. Tambahkan `--delete-orphans` hanya setelah yakin file tersebut tidak
diperlukan.

## 8. Audit Log

Operasi backup/restore dicatat di modul `backups` pada Audit Log yang sudah ada.
Karena `audit_events` berada di database yang sama, restore database juga
mengembalikan Audit Log ke waktu backup. Event sebelum restore dapat hilang.
Sebelum restore destruktif, aplikasi juga menulis baris aman
`backup.restore.starting` dan `backup.restore.finished` ke log aplikasi.

## 9. Batasan yang harus diketahui

1. Restore database tidak atomic; MySQL DDL auto-commit.
2. Backup metadata berada di database yang sama. Jika database hilang total,
   arsip tetap ada tetapi tidak otomatis dianggap tepercaya; gunakan manifest
   dan prosedur manual.
3. Restore lintas versi aplikasi dapat meninggalkan tabel `migrations` yang
   lebih lama. Verifikasi memberi peringatan; aplikasi tidak auto-migrate.
4. Checksum internal bukan anti-tamper penuh.
5. Backup lokal tidak melindungi dari kehilangan server.
6. Upload besar membutuhkan konfigurasi PHP/web server yang sesuai.

## 10. Checklist go-live

- [ ] `mysqldump` dan `mysql` tersedia.
- [ ] Worker `--queue=backups,default` berjalan.
- [ ] Cron `schedule:run` berjalan.
- [ ] Heartbeat scheduler dan worker terlihat di `/admin/backup`.
- [ ] Migration dan `PermissionSeeder` dijalankan.
- [ ] Backup database manual berhasil dan dapat diunduh.
- [ ] Full backup manual berhasil dan manifest valid.
- [ ] Restore diuji di staging.
- [ ] Audit Log mencatat operasi.
- [ ] Operator mengetahui batas backup lokal.
