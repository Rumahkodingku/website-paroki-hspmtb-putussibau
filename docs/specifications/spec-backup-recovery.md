# Feature Specification — Backup & Recovery

## Website Gereja Katolik Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau

| Metadata             | Nilai                                                                     |
| -------------------- | ------------------------------------------------------------------------- |
| Prioritas            | P0 — wajib                                                                |
| Status               | Spesifikasi implementasi                                                  |
| Stack yang diketahui | Laravel 13, Inertia.js v3, React 19, TypeScript, Tailwind CSS v4, MySQL 8 |
| Bahasa UI            | Bahasa Indonesia                                                          |
| Zona waktu tampilan  | `Asia/Pontianak`                                                          |
| Penyimpanan waktu    | UTC                                                                       |
| Audit Log            | Sudah tersedia; gunakan implementasi yang ada                             |

> **Instruksi untuk AI agent:** Sebelum coding, periksa `AGENTS.md`, `docs/ARCHITECTURE.md`, `docs/PRD.md`, `docs/DECISIONS.md`, roadmap, deployment, struktur upload, permission, queue, scheduler, dan Audit Log aktual. Dokumen ini mendefinisikan kebutuhan, bukan klaim bahwa path/model tertentu sudah ada. Ikuti konvensi repository. Jangan mengubah scope atau membuat keputusan berdampak besar secara diam-diam. Jika ada ambiguitas yang memengaruhi keamanan, kelayakan, data, atau deployment, berhenti dan ajukan pertanyaan yang spesifik.

---

## 1. Tujuan dan ruang lingkup

Fitur harus memungkinkan administrator berwenang untuk:

1. Membuat backup database secara manual.
2. Membuat backup lengkap database dan file yang dikelola aplikasi.
3. Menjadwalkan backup otomatis.
4. Melihat riwayat, detail, status, ukuran, dan hasil validasi backup.
5. Mengunduh backup dengan authorization.
6. Mengunggah backup eksternal untuk validasi dan kemungkinan pemulihan.
7. Memulihkan database atau backup lengkap dengan prosedur aman.
8. Mengatur jadwal dan retensi.
9. Menghapus backup sesuai permission dan kebijakan retensi.
10. Mencatat operasi melalui Audit Log yang sudah ada.

Fitur ini bukan pengganti backup infrastruktur, version control, atau salinan disaster recovery di lokasi lain. Backup aplikasi tidak mencakup source code, `.env`, application key, credential, token, private key, konfigurasi server, DNS, SSL, atau OS. Backup lokal pada server yang sama tidak melindungi dari kehilangan seluruh server.

## 2. Prinsip non-negotiable

1. **Fail closed:** kegagalan authorization, validasi, checksum, atau prasyarat membatalkan operasi.
2. Status `success` hanya diberikan setelah file final tersedia dan validasi yang diwajibkan lulus.
3. Simpan backup di private storage, bukan public disk/web root.
4. Jangan pernah memasukkan `.env`, credential, secret, token, atau private key ke arsip.
5. Validasi semua arsip sebelum restore atau ekstraksi ke lokasi aktif.
6. Restore produksi wajib memerlukan konfirmasi eksplisit dan safety backup yang berhasil dibuat serta diverifikasi.
7. Cegah dua restore berjalan bersamaan dan konflik antara restore dengan operasi lain yang tidak kompatibel.
8. Jangan menyatakan restore berhasil jika hasilnya parsial atau verifikasi gagal.
9. Gunakan Audit Log yang sudah ada; jangan membuat sistem audit paralel.
10. Jangan mengedit migration yang sudah berjalan; tambahkan migration baru bila diperlukan.
11. Ikuti pola arsitektur repository: controller tipis, Actions untuk operasi multi-langkah, Services untuk kemampuan reusable, Policy/Gate dan Spatie Permission sesuai pola aktual.
12. Jangan membocorkan stack trace, SQL mentah, absolute path, konten backup, credential, atau data pribadi ke UI/log.
13. Jangan menjalankan restore otomatis melalui scheduler.
14. Jangan menjalankan shell command dengan concatenation input user.
15. Jangan menganggap checksum yang disimpan hanya bersama arsip membuktikan perlindungan anti-tamper jika penyerang dapat mengubah keduanya.

## 3. Jenis backup dan format

### 3.1 Backup Database

- Format unduhan: `.sql.gz`.
- Memuat skema dan data database aplikasi aktif sesuai konfigurasi aplikasi.
- Termasuk konten, pengaturan, akun, role/permission, metadata media, dan Audit Log jika berada di database aplikasi.
- Tidak memuat file upload fisik.
- Dump harus kompatibel dengan versi database dan privilege yang tersedia pada deployment.
- File sementara dibersihkan saat proses gagal.
- Nama file yang disarankan: `hspmtb-db-YYYY-MM-DD-HHmmss.sql.gz`.

### 3.2 Backup Lengkap

- Format unduhan: `.zip`.
- Isi minimal:
  - `manifest.json`
  - `database/database.sql` atau dump yang formatnya didefinisikan dan didukung secara eksplisit
  - `files/` berisi file upload aplikasi yang termasuk scope.
- Identifikasi folder file berdasarkan repository dan konfigurasi storage aktual; jangan menebak path.
- Contoh kategori: media berita/artikel, galeri, dan dokumen yang dikelola admin.
- Tidak memuat `vendor/`, `node_modules/`, cache, session, log, temporary files, source code, `.env`, atau konfigurasi rahasia.
- Jangan mengikuti symlink keluar dari cakupan backup.
- Nama file yang disarankan: `hspmtb-full-YYYY-MM-DD-HHmmss.zip`.

### 3.3 Manifest

Manifest wajib berisi:

- `format` dan `format_version`.
- `backup_id`.
- `type`: `database` atau `full`.
- `created_at` dalam ISO 8601 UTC.
- identifier aplikasi non-secret.
- versi aplikasi/commit SHA jika diketahui, selain itu `null`.
- driver database.
- path relatif dump.
- `files_included`, `file_count` dan `total_uncompressed_bytes` jika relevan.
- algoritma checksum `sha256`.
- checksum dump dan komponen payload yang relevan.
- `created_by` berupa ID pengguna atau identifier sistem untuk job otomatis.
- catatan opsional yang tidak boleh memuat secret.

Tolak format version yang tidak didukung. Jangan diam-diam menurunkan validasi untuk arsip lama. Untuk deteksi modifikasi oleh penyerang yang dapat mengubah arsip sekaligus manifest, dibutuhkan checksum/tanda tangan terpisah di storage yang terlindungi; jangan mengklaim checksum internal sebagai anti-tamper.

### 3.4 Cakupan

| Komponen                                | Database backup | Full backup |
| --------------------------------------- | --------------: | ----------: |
| Struktur dan data database aplikasi     |              Ya |          Ya |
| Konten, akun, pengaturan, permission    |              Ya |          Ya |
| Audit Log di database aplikasi          |              Ya |          Ya |
| File upload yang dikelola aplikasi      |           Tidak |          Ya |
| Cache/session/log/temp                  |           Tidak |       Tidak |
| Source code, `vendor/`, `node_modules/` |           Tidak |       Tidak |
| `.env`, secret, credential, key         |           Tidak |       Tidak |
| OS, server config, DNS, SSL             |           Tidak |       Tidak |

Restore database hanya mengubah database; file upload saat ini tidak dipulihkan. Full restore memulihkan database dan file yang termasuk arsip. Jangan menghapus file aktif yang tidak ada di backup secara diam-diam; kebijakan penghapusan harus eksplisit, diuji, dan dikonfirmasi.

---

## 4. Permission dan authorization

Gunakan permission berikut sebagai target, sesuaikan dengan naming/seeding yang benar-benar digunakan repository:

| Permission                | Kapabilitas                  |
| ------------------------- | ---------------------------- |
| `backups.view`            | Melihat daftar/detail/status |
| `backups.create`          | Membuat backup manual        |
| `backups.download`        | Mengunduh backup             |
| `backups.upload`          | Mengunggah arsip eksternal   |
| `backups.restore`         | Memulai restore              |
| `backups.delete`          | Menghapus backup             |
| `backups.manage-settings` | Mengubah jadwal/retensi      |

Aturan:

- Authorization wajib di backend pada setiap route/action; menyembunyikan tombol saja tidak cukup.
- Guest tidak boleh mengakses halaman, metadata sensitif, file, upload, atau restore.
- `upload` tidak otomatis memberi hak `restore`; upload tidak boleh langsung mengubah data.
- `download`, `delete`, `restore`, dan `manage-settings` merupakan permission terpisah.
- Ikuti Super Admin bypass yang sudah ada, jika ada, dan audit operasi tersebut.
- Jangan membuat permission hanya di frontend.
- Uji IDOR pada seluruh endpoint dengan ID backup yang diganti.

## 5. Metadata dan lifecycle

Periksa apakah model/tabel backup yang sesuai sudah ada sebelum membuat baru. Bila belum ada, metadata minimal:

- ID, tipe (`database`/`full`), sumber (`manual`/`scheduled`/`uploaded`/`pre_restore`).
- Status, nama file aman, storage disk dan relative path privat.
- Ukuran byte, SHA-256, format version.
- Waktu mulai/selesai, durasi bila tersedia.
- User ID pembuat atau actor sistem.
- Hasil validasi, waktu validasi, error code/pesan internal aman.
- Catatan opsional dan timestamp.
- Jangan simpan isi backup atau absolute path server jika relative path cukup.

Status backup: `queued`, `running`, `validating`, `success`, `failed`, dan `cancelled` bila pembatalan benar-benar didukung. Restore harus memiliki lifecycle operasi terpisah atau riwayat operasi terpisah, agar kegagalan restore tidak merusak status validitas backup sumber.

Aturan:

- `success` harus menunjuk file yang ada dan valid.
- File hilang harus ditandai unavailable/invalid dan tidak dapat diunduh/restore.
- File orphaned tidak boleh otomatis dianggap backup tepercaya.
- Error teknis dicatat secara aman; UI hanya menampilkan ringkasan tersanitasi.
- Gunakan lock/idempotency untuk retry dan scheduler.
- Simpan timestamp UTC, tampilkan dalam `Asia/Pontianak`.
- Penghapusan file dan metadata harus menangani kegagalan parsial secara konsisten.

## 6. BK-M — Backup manual

Admin dengan `backups.create` dapat memilih `Backup Database` atau `Backup Lengkap`.

Kebutuhan:

- Jelaskan komponen yang disertakan dan dikecualikan sebelum memulai.
- Catatan backup opsional, divalidasi dan dibatasi panjangnya.
- Buat record operasi sebelum pekerjaan berat berjalan.
- Gunakan queue/background job bila didukung. Jangan membuat request HTTP panjang tanpa batas.
- Audit ketersediaan queue worker dan hosting. Jika tidak tersedia, pilih fallback yang aman dan dokumentasikan; jangan menganggap worker aktif.
- Dump menggunakan koneksi aplikasi yang dikonfigurasi, bukan target database dari input user.
- Full backup mengumpulkan hanya file yang telah diidentifikasi dalam scope.
- File temporary disimpan non-public dengan permission minimum.
- Artefak final dipindah ke private storage hanya setelah selesai.
- Hitung checksum dan validasi format/manifest/isi.
- Kegagalan menghasilkan `failed`, cleanup yang aman, dan pesan yang dapat ditindaklanjuti.
- Batasi concurrency; cegah pekerjaan duplikat.
- Tangani storage unavailable, disk penuh, binary tidak tersedia, timeout, dan permission filesystem.
- UI menampilkan status, waktu, ukuran, durasi bila tersedia, dan progres hanya jika benar-benar dapat diukur.
- Catat start/success/failure di Audit Log yang ada.

Acceptance:

- Database menghasilkan `.sql.gz` yang bisa dibaca/dekompresi dan berisi dump valid.
- Full backup menghasilkan `.zip` dengan manifest dan file yang diharapkan.
- Artefak gagal tidak dapat diunduh/restore.
- Backup tidak dapat diakses melalui public URL.
- File temporary dibersihkan pada jalur sukses dan kegagalan yang tertangani.

## 7. BK-S — Backup terjadwal

Default rekomendasi awal: backup database harian pukul 02.00 `Asia/Pontianak`; full backup mingguan bila kapasitas dan durasi memungkinkan. Nilai ini harus dikonfirmasi terhadap hosting dan kapasitas sebelum dianggap final.

Kebutuhan:

- Pengguna `backups.manage-settings` dapat mengaktifkan/menonaktifkan jadwal dan mengubah frekuensi/waktu yang didukung.
- Simpan timezone secara eksplisit.
- Laravel Scheduler harus benar-benar dikonfigurasi pada server. Queue worker juga harus aktif bila digunakan.
- Job idempotent agar pemanggilan scheduler berulang tidak membuat backup duplikat untuk slot jadwal yang sama.
- Kegagalan satu job tidak menghentikan jadwal berikutnya.
- Riwayat menampilkan hasil, waktu terakhir, dan waktu berikutnya jika dapat dihitung.
- Jika scheduler belum dikonfigurasi, UI menyatakan fitur otomatis belum siap, bukan seolah aktif.
- Tidak ada scheduled restore.
- Catat hasil operasi otomatis.

Acceptance:

- Jadwal aktif membuat backup di environment terkonfigurasi.
- Jadwal nonaktif tidak membuat job.
- Timezone benar dan trigger berulang tidak membuat duplikat.
- Kegagalan tercatat.
- Fitur tidak mengklaim otomatis berfungsi jika scheduler server belum aktif.

## 8. BK-L — Daftar dan detail riwayat

Tabel daftar:

- ID/nama, tipe, sumber, status, waktu, ukuran, durasi bila tersedia, status validasi, pembuat/label sistem, aksi sesuai permission.
- Pagination server-side, filter tipe/status/sumber/rentang tanggal, pencarian bila sesuai pola aplikasi.
- Empty/loading/error state dan retry.
- Status tidak dibedakan dengan warna saja.
- Waktu ditampilkan `Asia/Pontianak`.
- Jangan menampilkan absolute path, SQL, stack trace, atau isi backup.

Detail:

- Metadata backup, status, waktu, ukuran, checksum, hasil validasi, ringkasan isi, pembuat, pesan gagal aman, dan aksi sesuai permission.
- Backup sedang berjalan tidak bisa diunduh/restore.
- Backup hilang/invalid tidak menawarkan download/restore.
- UI menyembunyikan aksi tak berizin, tetapi backend tetap memeriksa authorization.

## 9. BK-V — Validasi dan integritas

Saat membuat backup, sebelum status success:

1. Artefak ada dan ukurannya bukan nol.
2. Format cocok dengan tipe.
3. Kompresi bisa dibaca.
4. Dump tidak tampak terpotong menurut pemeriksaan yang didukung.
5. Full backup memiliki manifest valid.
6. Semua path relatif dan aman.
7. Checksum cocok.
8. Artefak final berada di private storage.
9. Metadata sesuai artefak.

Validasi upload:

- Extension/MIME hanya pemeriksaan awal.
- Periksa ukuran, signature/struktur arsip, manifest, version, tipe, checksum, daftar entry, jumlah file, ukuran hasil ekstraksi, dan rasio kompresi.
- Tolak path traversal (`../`, absolute path, drive prefix, null byte, separator yang dapat bypass), symlink/hardlink, special entry, dan path duplikat/konflik.
- Periksa seluruh daftar entry sebelum ekstraksi.
- Gunakan nama internal yang dibuat server; jangan gunakan nama upload sebagai destination path.
- Jangan mengeksekusi konten upload.
- Batasi resource parsing agar ZIP bomb dan disk exhaustion ditangani.
- Validasi ulang sebelum restore untuk mengurangi risiko file berubah setelah preview.
- Validasi ulang tidak mengubah isi backup; hasil gagal memblokir restore.

Acceptance:

- Arsip rusak, checksum salah, manifest hilang/tidak valid, version tidak didukung, path berbahaya, dan arsip melewati batas ditolak.
- Pesan error menyebut alasan aman seperti checksum mismatch atau format tidak didukung.

## 10. BK-D — Download

- Memerlukan `backups.download`.
- Hanya file sukses, valid, dan tersedia dapat diunduh.
- Gunakan private storage dan streaming atau mekanisme privat yang aman.
- Jangan membuat public URL permanen. Temporary signed URL hanya jika singkat masa berlakunya dan sesuai authorization.
- Sanitasi nama file unduhan.
- Set header download yang sesuai dan cegah content sniffing bila relevan.
- Catat download di Audit Log sesuai skema yang tersedia.
- File invalid, hilang, atau terhapus tidak dapat diunduh.
- Storage error ditampilkan secara aman.

## 11. BK-U — Upload backup eksternal

- Memerlukan `backups.upload`.
- Upload **tidak pernah langsung melakukan restore**.
- Format yang didukung hanya `.sql.gz` dan `.zip` dengan format backup aplikasi yang dikenal.
- Validasi arsip sepenuhnya sebelum backup dapat dipilih untuk restore.
- Simpan di private storage dengan nama server-generated.
- Catat uploader, waktu, nama asli tersanitasi, ukuran, checksum, tipe, hasil validasi.
- Jangan menggunakan nama file asli sebagai path; jangan menimpa file berdasarkan nama.
- Terapkan batas ukuran dan resource; dokumentasikan limit PHP/web server/reverse proxy/hosting.
- Upload invalid ditandai dan dibersihkan sesuai kebijakan agar tidak memenuhi disk.
- Catat upload di Audit Log.

## 12. BK-R — Restore / pemulihan

Restore adalah operasi destruktif dan memerlukan `backups.restore`.

### Alur wajib

1. User memilih backup.
2. Backend memeriksa authorization, file, status, format, tipe, manifest, checksum, dan validitas.
3. UI menampilkan nama, tipe, tanggal, ukuran, hasil validasi, serta data yang akan diganti.
4. User memberikan konfirmasi eksplisit. Untuk produksi gunakan konfirmasi kuat, misalnya mengetik frasa yang ditentukan UI.
5. Backend validasi ulang tepat sebelum restore.
6. Ambil exclusive lock; tolak jika restore lain atau operasi yang berkonflik sedang berjalan.
7. Buat safety backup database dan, untuk full restore, file yang terdampak. Verifikasi safety backup. Jika gagal, batalkan restore.
8. Aktifkan maintenance/proteksi write yang sesuai. Audit dampak ke queue, scheduler, dan proses eksternal.
9. Restore database dari format yang didukung.
10. Untuk full restore, ekstrak ke staging yang aman, validasi semua path, lalu terapkan strategi penggantian file yang dapat dipulihkan.
11. Jalankan verifikasi pasca-restore.
12. Lepas lock dan pulihkan maintenance mode pada semua jalur keluar.
13. Catat hasil melalui Audit Log.
14. Tampilkan sukses, gagal, atau parsial sesuai hasil nyata.

### Peringatan UI

- Data setelah waktu backup mungkin hilang.
- Database-only tidak memulihkan file upload.
- Full restore mengganti database dan file yang termasuk scope.
- Mungkin ada gangguan layanan.
- Tampilkan status safety backup sebelum tombol final tersedia.

### Perlindungan database

- Target database/host/user/credential tidak boleh berasal dari input user; gunakan koneksi aplikasi yang dikonfigurasi.
- Jangan menjalankan SQL dump mentah tanpa validasi sumber, format, ukuran, dan isi yang diperlukan.
- Jika memakai binary eksternal, gunakan argumen terpisah, environment aman, timeout, exit-code handling; jangan shell concatenation.
- Cegah dump mengalihkan konteks ke database lain.
- Tentukan penanganan kegagalan parsial secara eksplisit. Jangan menjanjikan atomic rollback bila tidak didukung.
- Jika restore gagal setelah perubahan dimulai, tandai gagal/parsial dan sediakan prosedur recovery melalui safety backup.
- Jangan menjalankan operasi destructive sebelum safety backup sukses dan konfirmasi selesai.

### Perlindungan file

- Validasi path setelah normalisasi canonical path dan pastikan tetap di dalam root tujuan.
- Tolak symlink/hardlink, device file, FIFO, dan entry tak didukung.
- Gunakan staging directory privat.
- Backup/snapshot file saat ini sebelum menggantinya.
- Jangan menimpa file arbitrary di luar direktori upload terkonfigurasi.
- Jangan menghapus file yang tidak ada dalam arsip tanpa kebijakan eksplisit.
- Jika database berhasil tetapi file gagal, laporkan partial failure dan recovery steps; jangan melaporkan full success.

### Verifikasi pasca-restore

- Koneksi database aplikasi dapat digunakan.
- Query pemeriksaan dasar berjalan.
- Tabel inti yang diharapkan tersedia.
- Konfigurasi penting yang dibutuhkan aplikasi dapat dibaca.
- Untuk full restore, direktori file tercakup ada dan dapat dibaca oleh proses aplikasi.
- Verifikasi jumlah/checksum file bila metadata mendukung.
- Status mencerminkan hasil sebenarnya.
- Refresh cache hanya sesuai praktik deployment yang ada.
- Jangan menganggap seluruh aplikasi sehat hanya karena satu query berhasil.

### Acceptance restore

- Tanpa permission, konfirmasi, atau safety backup tervalidasi: restore ditolak.
- Backup invalid/hilang/version tak didukung: restore ditolak.
- Restore concurrent dicegah.
- Path arsip tidak bisa menulis di luar root yang diizinkan.
- Kegagalan parsial tidak disamarkan sebagai sukses.
- Lock dan maintenance mode ditangani pada exception.
- Semua hasil tercatat di Audit Log.

## 13. BK-C — Pengaturan, retensi, dan penghapusan

Pengaturan hanya untuk `backups.manage-settings`:

- Aktif/nonaktif jadwal.
- Jenis backup terjadwal.
- Frekuensi dan waktu.
- Timezone.
- Retensi harian/mingguan.
- Batas upload, jumlah file, dan ukuran hasil ekstraksi.
- Ambang disk rendah jika pengukuran tersedia.
- Storage hanya melalui disk yang dikonfigurasi aman; jangan menerima arbitrary filesystem path dari UI.
- Notifikasi hanya jika mekanismenya telah tersedia atau secara eksplisit ditetapkan.

Default retensi rekomendasi awal:

- Backup otomatis harian: 7 hari.
- Backup otomatis mingguan: 4 minggu.
- Backup manual: jangan dihapus otomatis pada awal implementasi kecuali kebijakan ditetapkan.
- Safety backup restore: dikecualikan dari retensi normal selama operasi/periode perlindungan.
- Upload invalid: dibersihkan sesuai periode singkat yang dikonfigurasi.

Aturan retensi:

- Hapus hanya backup yang eligible dan tidak sedang digunakan restore.
- Jangan menghapus berdasarkan nama file saja; gunakan metadata dan path terverifikasi.
- Hapus file/metadata dengan penanganan kegagalan parsial.
- Catat penghapusan otomatis/manual.
- Penghapusan manual memerlukan `backups.delete` dan konfirmasi.
- Jangan menghapus safety backup yang masih dibutuhkan.
- Jika file gagal dihapus, jangan berpura-pura penghapusan sukses.
- Sediakan mekanisme rekonsiliasi record/file orphaned/missing sesuai pola command repository.

## 14. Status, kegagalan, dan operasional

Status yang perlu dipahami UI: queued, running, validating, success, failed, invalid/unavailable, restore running, restore success, restore failed/partial.

Pesan aman yang bisa ditindaklanjuti:

- Storage tidak tersedia.
- Ruang disk tidak mencukupi.
- Scheduler belum dikonfigurasi.
- Format tidak didukung.
- Checksum tidak cocok.
- Backup tidak ditemukan.
- Operasi restore lain sedang berjalan.
- Permission tidak mencukupi.

Jangan tampilkan stack trace, raw SQL, credential, absolute path, atau konten arsip. Simpan detail teknis minimum yang aman di log. Gunakan timeout dan cleanup. Retry backup harus idempotent; jangan menimpa backup sukses. Jangan otomatis retry restore setelah perubahan destruktif dimulai. Deteksi job macet jika infrastruktur mendukung.

## 15. Audit Log integration

Gunakan Audit Log yang sudah ada. Catat bila skema mendukung:

- Backup manual dimulai/berhasil/gagal.
- Backup otomatis berhasil/gagal.
- Validasi berhasil/gagal.
- Upload berhasil/gagal.
- Download.
- Restore diminta/ditolak/berhasil/gagal/parsial.
- Backup dihapus manual/otomatis.
- Pengaturan diubah.

Metadata: actor user ID atau actor sistem, event key, backup/operation ID, tipe, hasil, timestamp, correlation ID jika tersedia, dan ringkasan aman.

Jangan log isi SQL/file, credential, token, signed URL aktif, raw stack trace, atau data pribadi yang tidak dibutuhkan.

**Peringatan:** bila Audit Log disimpan di database yang sama, restore database dapat mengembalikan Audit Log ke waktu backup dan menghilangkan event setelahnya. Catat event sebelum/sesudah restore sesuai kemampuan yang ada, tetapi jangan klaim audit immutable. Audit eksternal yang tahan rollback berada di luar scope kecuali sudah tersedia.

## 16. Storage, kapasitas, dan hosting

- Gunakan private Laravel disk/storage abstraction yang tersedia.
- Pisahkan temporary workspace dari backup final.
- Jangan simpan backup di public web root, Git, atau frontend build.
- Batasi filesystem permission.
- Periksa ruang sebelum operasi berat bila mungkin dan tangani disk-full di tengah operasi.
- Pertimbangkan ruang untuk temporary dump, arsip final, dan safety backup sekaligus.
- Proses file besar secara streaming bila memungkinkan.
- Arsitektur boleh mendukung external object storage, tetapi jangan menyatakan integrasi selesai sebelum credential, bucket, policy, konektivitas, lifecycle, dan tes diverifikasi.
- Jika hanya storage lokal, tampilkan peringatan bahwa kehilangan server dapat menghilangkan backup.
- Verifikasi ketersediaan `mysqldump`/binary, privilege database, queue worker, scheduler cron, PHP upload limits, web server limits, timeout, dan permission hosting.
- Jangan menjanjikan durasi backup/restore sebelum diuji pada dataset dan hosting aktual.

## 17. Arsitektur implementasi

Ikuti struktur repository aktual. Secara konseptual:

- Controller tipis.
- Actions untuk membuat backup, upload, validasi, restore, delete.
- Services reusable untuk dump, checksum, manifest, archive inspection, path validation, storage.
- Jobs untuk proses berat dan retensi bila queue tersedia.
- Policy/Gate untuk authorization.
- Model/metadata untuk status backup dan operasi restore.
- Scheduler/Console commands sesuai pola Laravel project.
- Adapter ke Audit Log yang sudah ada.

Frontend mengikuti struktur feature admin yang ada, TypeScript, route helpers/Wayfinder bila dipakai, komponen form/dialog/table/toast/pagination yang sudah ada, serta UI Bahasa Indonesia. Backend adalah sumber kebenaran authorization/status.

Jangan menambah package tanpa memeriksa dependency existing, kompatibilitas, lisensi, maintenance, dan hosting. Jangan menetapkan path final sebelum repository diperiksa.

### Halaman konseptual

- Daftar `Backup & Pemulihan`.
- Detail backup.
- Pengaturan backup.
- Dialog buat backup.
- Dialog upload.
- Dialog restore dengan konfirmasi.
- Dialog hapus.

Nama route di atas hanya rancangan konseptual. Gunakan naming dan route conventions aktual. Operasi yang mengubah state tidak boleh memakai GET; gunakan CSRF protection dan middleware admin yang ada.

## 18. UX dan accessibility

Halaman daftar:

- Tombol buat backup/upload sesuai permission.
- Ringkasan backup terakhir dan status scheduler.
- Filter, pagination, empty/loading/error state.
- Peringatan scheduler belum siap dan storage lokal.
- Status disertai teks, bukan warna saja.

Dialog buat backup:

- Pilih jenis.
- Jelaskan isi/pengecualian.
- Catatan opsional.
- Peringatan ukuran/waktu full backup.

Dialog restore:

- Nama, jenis, waktu, ukuran, hasil validasi.
- Komponen yang diganti.
- Peringatan kehilangan perubahan setelah backup.
- Status safety backup.
- Konfirmasi eksplisit yang sulit terpencet.
- Label tombol akhir jelas, misalnya `Konfirmasi Pemulihan`.

Semua aksi harus keyboard-accessible. Dialog memiliki title/description/focus management. Error terhubung ke field. Status proses diumumkan secara aksesibel. Jangan mengandalkan warna saja.

## 19. Threat checklist

Uji dan mitigasi:

1. Unauthorized/IDOR.
2. Public exposure backup.
3. Path traversal.
4. ZIP bomb/archive expansion.
5. Symlink/hardlink/special entry.
6. SQL dump rusak atau berbahaya.
7. Shell injection.
8. Secret/data pribadi bocor di response/log.
9. Race condition antara backup/upload/delete/restore.
10. CSRF pada operasi destruktif.
11. Upload overwrite berdasarkan nama.
12. Restore parsial dan safety recovery.
13. Disk exhaustion.
14. File berubah/hilang setelah validasi.
15. Duplicate jobs/retries.
16. Manifest dan arsip diubah bersamaan.
17. Penulisan file di luar root.
18. Arbitrary storage path dari UI.
19. Maintenance mode/lock tidak dipulihkan.
20. Scheduler/worker tidak tersedia tetapi UI mengklaim aktif.

Jangan menambahkan endpoint SQL bebas, arbitrary database target, atau ekstraksi arsip ke path pilihan user.

## 20. Testing wajib

Gunakan test framework dan konvensi repository (Pest bila masih digunakan), factories/helpers yang ada, fake storage/job, dan fixture terisolasi. Jangan menjalankan dump/restore terhadap database produksi dalam test.

### Unit

- Manifest/version, checksum, path validation, retensi, status, sanitasi nama/error, file hilang/ukuran nol.

### Authorization

- Guest ditolak.
- Tiap permission diuji terpisah.
- Download tidak memberi restore.
- Upload tidak memberi restore.
- Delete/settings terpisah.
- IDOR tidak membocorkan data.

### Backup

- Database backup dan full backup.
- File yang masuk/tidak masuk scope.
- Secret, `.env`, log, cache, source code tidak disertakan.
- Job failure, cleanup, metadata, checksum, private storage, idempotency.

### Upload/validasi

- Arsip valid/invalid.
- Extension palsu.
- Manifest hilang/tidak valid.
- Checksum mismatch.
- Version tak didukung.
- Path traversal, symlink, special entry, duplikasi path.
- Batas ukuran, jumlah file, expanded size, rasio kompresi.
- Upload tidak langsung restore.

### Restore

- Permission dan konfirmasi wajib.
- Invalid/missing backup ditolak.
- Safety backup wajib.
- Lock mencegah concurrent restore.
- Database/full restore menangani komponen yang benar.
- Path tidak keluar root.
- Kegagalan database/file menghasilkan status gagal/parsial.
- Lock/maintenance mode dipulihkan pada exception.
- Audit event tercatat.
- Tidak ada secret dalam response/log.

### Scheduler/retensi

- Jadwal aktif/nonaktif, timezone, duplicate trigger.
- Retensi hanya menghapus backup eligible.
- Backup yang sedang digunakan dan safety backup terlindungi.
- File deletion failure ditangani.

### E2E

Jika Playwright tersedia, uji daftar/empty state, buat backup/status, filter/pagination, upload valid/invalid, dialog restore, permission, loading/error. Jangan bergantung pada dump/restore production atau arsip besar sungguhan tanpa fixture terisolasi.

Jalankan quality gate aktual repository, termasuk `composer ci:check` bila masih menjadi gate aktif, test backend, lint/type-check/build frontend, dan E2E relevan. Jangan menyatakan selesai jika test penting gagal atau tidak dijalankan; laporkan alasan.

## 21. Definition of Done

- [ ] Repository dan deployment telah diperiksa.
- [ ] Backup database `.sql.gz` dapat dibuat dan divalidasi.
- [ ] Full backup `.zip` memuat database, file upload dalam scope, dan manifest.
- [ ] Private storage dan SHA-256 diterapkan.
- [ ] Daftar/detail/status/filter/pagination berfungsi.
- [ ] Download memerlukan authorization.
- [ ] Upload eksternal divalidasi dan tidak langsung restore.
- [ ] Path traversal, symlink, dan archive expansion berlebihan ditolak.
- [ ] Restore database dan full restore mengikuti urutan keselamatan.
- [ ] Safety backup berhasil diverifikasi sebelum restore produksi.
- [ ] Lock/concurrency diterapkan.
- [ ] Verifikasi pasca-restore dan partial failure ditangani.
- [ ] Scheduler/queue sesuai hosting nyata dan status setup terlihat.
- [ ] Retensi/penghapusan mengikuti kebijakan.
- [ ] Semua permission diuji backend.
- [ ] Audit Log yang ada terintegrasi.
- [ ] Tidak ada secret di backup, UI, atau log.
- [ ] Dokumentasi operasional dan keterbatasan hosting tersedia.
- [ ] Test relevan dan quality gate lulus atau kegagalan dilaporkan eksplisit.

## 22. Acceptance criteria

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
- **BK-AC-12:** Safety backup sukses dan valid sebelum restore produksi.
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

## 23. Urutan implementasi

### Phase 0 — Discovery

1. Baca dokumentasi repository.
2. Periksa upload/media, storage, DB config, permission, Audit Log, admin navigation, queue, scheduler, dan deployment.
3. Periksa `mysqldump`, privilege database, storage, cron, worker, batas upload dan timeout hosting.
4. Identifikasi package/migration yang dibutuhkan.
5. Laporkan temuan, risiko, dan keputusan ambigu sebelum perubahan berisiko.

### Phase 1 — Fondasi

1. Metadata/lifecycle.
2. Private storage dan temporary workspace.
3. Format manifest/checksum.
4. Permission dan test dasar.

### Phase 2 — Backup manual

1. Database backup.
2. Full backup berdasarkan cakupan file aktual.
3. Validasi/checksum.
4. Daftar/detail/status.
5. Download privat.
6. Audit Log dan tests.

### Phase 3 — Upload dan restore

1. Upload ke private/quarantine storage.
2. Validasi arsip.
3. Safety backup.
4. Lock dan maintenance strategy.
5. Database restore.
6. Full file restore via staging.
7. Verifikasi dan partial failure.
8. Security/recovery tests.

### Phase 4 — Scheduler dan retensi

1. Jadwal/idempotency.
2. Setup scheduler/worker.
3. Retensi.
4. Pengaturan dan authorization.
5. Test timezone, duplicate job, dan retention.

### Phase 5 — Hardening

1. Audit security.
2. Uji arsip rusak/path traversal/file besar/storage failure.
3. Jalankan test suite dan quality gate.
4. Dokumentasikan setup dan recovery.
5. Laporkan file berubah, test, konfigurasi manual, dan risiko tersisa.

## 24. Hal yang wajib diverifikasi sebelum coding

Agent wajib memastikan:

1. Path/disk upload yang benar untuk berita, galeri, dokumen, dan media.
2. Versi DB dan dukungan `mysqldump`.
3. Ketersediaan queue worker dan Laravel Scheduler pada hosting target.
4. Kapasitas dan permission private storage.
5. Package archive/backup yang sudah ada.
6. Cara permission/seeder dikelola.
7. Skema/event contract Audit Log.
8. Dampak maintenance mode pada queue, scheduler, dan request tulis.
9. Kompatibilitas backup lintas versi aplikasi/database yang diperlukan.
10. Batas upload PHP/web server/proxy/hosting.
11. Ketersediaan external storage.
12. Privilege akun database untuk dump dan restore.
13. Metode pengujian restore yang tidak menyentuh production.

Jika jawaban memengaruhi keamanan, kelayakan, atau scope, agent harus berhenti dan bertanya dengan opsi serta konsekuensinya. Untuk keputusan teknis rutin yang tidak mengubah kebutuhan, ikuti pola repository dan catat asumsi.

## 25. Aturan komunikasi agent

Sebelum coding, sampaikan ringkasan temuan dengan path aktual, komponen existing yang dapat dipakai, komponen yang harus dibuat, risiko hosting, dan pertanyaan keputusan yang belum jelas.

Selama coding:

- Jangan mengubah file tidak terkait.
- Jangan menambah package/migration tanpa alasan.
- Jangan mengedit migration yang sudah berjalan.
- Jangan menyimpan secret di repository.
- Jangan menjalankan restore terhadap production.
- Jangan mengklaim restore terbukti jika hanya mock yang diuji.
- Jangan mengabaikan test gagal atau mengubah acceptance criteria agar tampak lulus.

Setelah coding, laporkan file berubah, flow backup/restore, test dan hasil, konfigurasi server manual, keterbatasan, serta cara operator memverifikasi backup dan melakukan recovery.

## 26. Default dan keputusan yang belum final

| Area                          | Default                                           | Status                             |
| ----------------------------- | ------------------------------------------------- | ---------------------------------- |
| Database backup               | `.sql.gz`                                         | Persyaratan                        |
| Full backup                   | `.zip` + manifest                                 | Persyaratan                        |
| Checksum                      | SHA-256                                           | Persyaratan                        |
| Storage                       | Private                                           | Persyaratan keamanan               |
| Waktu                         | Simpan UTC, tampil `Asia/Pontianak`               | Konvensi aplikasi                  |
| Backup harian                 | 02.00 Asia/Pontianak                              | Rekomendasi, verifikasi deployment |
| Retensi harian                | 7 hari                                            | Rekomendasi awal                   |
| Retensi mingguan              | 4 minggu                                          | Rekomendasi awal                   |
| Full backup otomatis          | Mingguan                                          | Rekomendasi, verifikasi kapasitas  |
| Safety backup sebelum restore | Wajib                                             | Persyaratan                        |
| Upload eksternal              | Dalam scope                                       | Validasi dengan hosting            |
| External object storage       | Siapkan abstraction; jangan anggap terkonfigurasi | Bergantung deployment              |
| Notifikasi                    | Tidak wajib kecuali mekanisme tersedia/ditetapkan | Di luar scope inti                 |
| Audit immutable eksternal     | Tidak termasuk                                    | Butuh sistem terpisah              |

## 27. Kontrak akhir

Fitur P0 memenuhi tujuan jika admin berwenang dapat membuat dan mengunduh backup database `.sql.gz` dan backup lengkap `.zip`, melihat serta memvalidasi riwayat, mengunggah arsip eksternal yang didukung, dan memulihkan data melalui konfirmasi eksplisit, safety backup, locking, validasi path, serta verifikasi pasca-restore. Scheduler dan retensi harus bekerja berdasarkan konfigurasi hosting yang nyata. Semua operasi dilindungi authorization backend, disimpan secara privat, tidak membocorkan secret, terintegrasi dengan Audit Log yang sudah ada, dan dilengkapi test serta dokumentasi operasional.

**Backup yang baik bukan sekadar file yang berhasil dibuat. Backup yang baik memiliki integritas tervalidasi dan prosedur restore yang diuji secara aman.**
