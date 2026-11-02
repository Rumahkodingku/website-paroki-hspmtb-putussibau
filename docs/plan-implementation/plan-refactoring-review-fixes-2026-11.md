# Implementation Plan — Perbaikan Temuan Review Refactoring (November 2026)

> **Project:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau\
> **Scope:** 8 temuan review atas refactoring audit codebase November 2026\
> **Status:** Implemented — verifikasi targeted, 2026-11-02\
> **Branch dasar:** `refactor/refactor-code` @ `6ba2314`\
> **Branch (usulan):** satu branch per fase, contoh `refactor/review-installer-fail-closed`, `refactor/review-backup-disk-check`\
> **Sumber kebenaran:** `AGENTS.md` → `PRD.md` → `ARCHITECTURE.md` → `docs/DECISIONS.md` → dokumen ini\
> **Tanggal:** 2026-11-02\
> **Dokumen terkait:** `docs/plan-implementation/plan-refactoring-audit-codebase-2026-11.md`

Dokumen ini menjelaskan **apa yang diperbaiki, di file mana, dalam urutan apa, dan bagaimana
diverifikasi**. Dokumen ini bukan roadmap fitur baru; ini rencana tindak lanjut atas review
terhadap refactoring yang sudah dikerjakan.

> **Penting:** Semua perubahan refactoring saat ini masih belum di-commit. Fase 0 wajib
> memisahkan baseline sebelum perbaikan dimulai. Jangan mengklaim suatu fase lulus tanpa
> output perintah yang benar-benar dijalankan.

---

## Daftar Isi

- [Implementation Plan — Perbaikan Temuan Review Refactoring (November 2026)](#implementation-plan--perbaikan-temuan-review-refactoring-november-2026)
  - [Daftar Isi](#daftar-isi)
  - [0. Instruksi untuk AI Agent](#0-instruksi-untuk-ai-agent)
  - [1. Ringkasan Eksekutif](#1-ringkasan-eksekutif)
    - [1.1 Hasil yang diharapkan](#11-hasil-yang-diharapkan)
    - [1.2 Batasan global](#12-batasan-global)
    - [1.3 Hubungan dengan plan audit sebelumnya](#13-hubungan-dengan-plan-audit-sebelumnya)
  - [2. Keputusan yang Mengikat](#2-keputusan-yang-mengikat)
    - [2.1 Keputusan yang sudah disepakati](#21-keputusan-yang-sudah-disepakati)
    - [2.2 Keputusan teknis](#22-keputusan-teknis)
    - [2.3 Batas yang sengaja diterima](#23-batas-yang-sengaja-diterima)
  - [3. Kondisi Codebase Saat Ini](#3-kondisi-codebase-saat-ini)
    - [3.1 Baseline repository](#31-baseline-repository)
    - [3.2 Delapan temuan dan bukti](#32-delapan-temuan-dan-bukti)
    - [3.3 Shortcut yang bukan temuan](#33-shortcut-yang-bukan-temuan)
  - [4. Prinsip Implementasi](#4-prinsip-implementasi)
  - [5. Rencana per Fase](#5-rencana-per-fase)
    - [Fase 0 — Baseline dan guardrail](#fase-0--baseline-dan-guardrail)
    - [Fase 1A — Installer fail-closed](#fase-1a--installer-fail-closed)
    - [Fase 1B — Cek disk backup menghitung dump](#fase-1b--cek-disk-backup-menghitung-dump)
    - [Fase 2A — Test seluruh cabang referensi media](#fase-2a--test-seluruh-cabang-referensi-media)
    - [Fase 2B — Test `sourceBytes()` dan satukan walk file](#fase-2b--test-sourcebytes-dan-satukan-walk-file)
    - [Fase 3A — Rich-text kosong menyimpan `null`](#fase-3a--rich-text-kosong-menyimpan-null)
    - [Fase 3B — Validasi tanggal melaporkan error bersamaan](#fase-3b--validasi-tanggal-melaporkan-error-bersamaan)
    - [Fase 4 — Dokumentasi dan keputusan](#fase-4--dokumentasi-dan-keputusan)
    - [Fase 5 — Verifikasi akhir](#fase-5--verifikasi-akhir)
  - [6. Urutan Eksekusi dan Dependency](#6-urutan-eksekusi-dan-dependency)
  - [7. Test Matrix](#7-test-matrix)
  - [8. Ringkasan File yang Terdampak](#8-ringkasan-file-yang-terdampak)
    - [8.1 File baru](#81-file-baru)
    - [8.2 File diubah](#82-file-diubah)
    - [8.3 File dokumentasi](#83-file-dokumentasi)
    - [8.4 File yang tidak disentuh](#84-file-yang-tidak-disentuh)
  - [9. Risk Register](#9-risk-register)
  - [10. Verifikasi dan Acceptance](#10-verifikasi-dan-acceptance)
    - [10.1 Definition of Done global](#101-definition-of-done-global)
    - [10.2 Checklist per fase](#102-checklist-per-fase)
    - [10.3 Checklist rilis](#103-checklist-rilis)
  - [11. Out of Scope](#11-out-of-scope)
  - [12. Status Implementasi](#12-status-implementasi)
  - [13. Lampiran](#13-lampiran)
    - [13.1 Cheat sheet perintah](#131-cheat-sheet-perintah)
    - [13.2 Format commit](#132-format-commit)
    - [13.3 Referensi](#133-referensi)

---

## 0. Instruksi untuk AI Agent

Baca bagian ini sebelum menyentuh file apa pun.

1. **Baca `AGENTS.md` terlebih dahulu.** Aturan proyek (generated files, test, Pint,
   PHPStan, MySQL, commit) berlaku penuh.
2. **Kerjakan satu fase per branch/PR.** Jangan menggabungkan dua fase kecuali diminta.
3. **Jangan mengubah keputusan di §2.** Kalau implementasi bertabrakan dengan keputusan,
   berhenti dan tanya pengguna.
4. **Jangan menyentuh file di §11.** Tidak ada migrasi, dependensi baru, atau refactor
   di luar 8 temuan.
5. **Untuk perubahan perilaku, tulis test yang gagal dulu**, lalu perbaiki.
6. **Jangan pernah memakai `--no-verify`.** Pre-commit menjalankan gate penuh.
7. **Jalankan perintah verifikasi di setiap fase.** Jangan mengklaim lulus tanpa output.
8. **Perbarui §12** setelah fase selesai (tanggal, commit, PR, catatan).
9. **Jangan mengedit file generated** (`resources/js/{routes,actions,wayfinder}`,
   `public/build`). Regenerasi dengan `php artisan wayfinder:generate --with-form` bila
   route berubah.
10. **Kalau ragu, tanya.** Jangan mengarang perilaku baru.

---

## 1. Ringkasan Eksekutif

Review menemukan **2 blocker rilis**, **4 perbaikan yang sebaiknya diselesaikan**, dan
**2 pembersihan**. Tidak ada temuan yang memerlukan perubahan skema database, route, atau
UI.

### 1.1 Hasil yang diharapkan

| Hasil               | Ukuran keberhasilan                                                                                |
| ------------------- | -------------------------------------------------------------------------------------------------- |
| Keamanan installer  | Instalasi ditolak saat database aplikasi tidak bisa dihubungi, bukan hanya saat sudah berisi tabel |
| Keselamatan backup  | Cek disk memperhitungkan dump database; backup tidak lolos cek lalu gagal di tengah                |
| Integritas media    | Setiap kolom referensi media punya test yang membuktikan delete ditolak                            |
| Estimasi backup     | `sourceBytes()` terverifikasi dan aturan skip file hanya ditulis sekali                            |
| Kebenaran rich text | Editor kosong menyimpan `null`, bukan `<p></p>`                                                    |
| UX validasi         | Error tanggal dan error field lain tampil bersamaan                                                |
| Stabilitas test     | Test `disk_full` deterministik, tidak bergantung sisa disk mesin                                   |
| Gate                | `composer ci:check` hijau setelah semua fase                                                       |

### 1.2 Batasan global

- **Tidak ada migrasi/skema baru.**
- **Tidak ada dependensi baru.**
- **Tidak ada abstraksi/layer baru** kecuali seam test kecil yang dijelaskan di §2.
- **Tidak ada perubahan UI.** Rich text diperbaiki di backend; komponen publik sudah
  menangani `null`.
- **Tidak ada perubahan Master Data.** Pekerjaan Master Data dari refactoring sebelumnya
  tidak disentuh.
- **Tidak ada refactor di luar 8 temuan.** Temuan baru yang muncul dicatat, bukan
  dikerjakan diam-diam.

### 1.3 Hubungan dengan plan audit sebelumnya

`plan-refactoring-audit-codebase-2026-11.md` mencatat Fase 1–7 sebagian besar selesai,
dengan 6c/6d/6e ditunda. Review atas hasil refactoring tersebut menghasilkan 8 temuan
yang menjadi isi dokumen ini.

Dokumen ini **tidak mengulang** pekerjaan yang sudah selesai. Fokusnya:

1. menutup dua blocker rilis;
2. menambah test untuk guard yang sudah ada;
3. merapikan dua perilaku kecil dan satu duplikasi.

---

## 2. Keputusan yang Mengikat

### 2.1 Keputusan yang sudah disepakati

| Topik                                                 | Keputusan                                                                                     |
| ----------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| Installer saat database aplikasi tidak bisa dihubungi | **Selalu fail-closed** dengan `DATABASE_UNREACHABLE`. Tidak ada flag bypass.                  |
| Ukuran dump database pada cek disk                    | **Cek ulang setelah dump** memakai ukuran dump aktual.                                        |
| Temuan Nice-to-have                                   | **#7 dan #8 dimasukkan** ke plan.                                                             |
| Test `disk_full`                                      | **Suntik nilai free space** lewat seam kecil di `BackupStorage`, bukan bergantung disk mesin. |
| Cakupan test media                                    | **Semua cabang referensi**, bukan hanya 2.                                                    |

### 2.2 Keputusan teknis

**K1 — Installer fail-closed.**

`InstallationRunner::run()` memeriksa database aplikasi yang sedang dikonfigurasi sebelum
mengganti koneksi ke database yang diposting wizard:

```text
readiness.check() tanpa argumen
    ok = false
        code DATABASE_NOT_FRESH  → InstallationFailure(DatabaseNotFresh)
        code DATABASE_UNREACHABLE → InstallationFailure(DatabaseUnreachable)
        code lain                 → InstallationFailure(code tersebut)
    ok = true                     → lanjut
```

- Guard dijalankan **sebelum** `applyDatabaseConfig($database)`.
- Tidak ada `INSTALLER_ALLOW_UNREACHABLE`.
- Instalasi baru yang sah tetap berjalan selama database target sudah dibuat, kosong, dan
  bisa dihubungi.
- Jika database aplikasi sedang down, operator harus memperbaiki `DB_*` lebih dulu.

**K2 — Cek disk backup.**

- `sourceBytes()` dihitung sekali per backup.
- Cek pertama sebelum dump memakai ukuran file sumber.
- Setelah `mysqldump`, cek kedua memakai ukuran dump aktual dari `$dump['size']`.
- Rumus:

  ```text
  required = 2 × (source + dump) + threshold
  ```

  Dump diperlakukan sebagai input yang ikut disalin/di-zip, bukan tambahan satu kali.
- Cek kedua dijalankan sebelum hash, `collectFiles()`, dan `writeZip()`.

**K3 — Seam free space untuk test.**

`BackupStorage` mendapat parameter opsional `?int $freeBytesOverride = null`.
`freeBytes()` mengembalikan nilai itu bila diisi; produksi tetap memakai
`disk_free_space()`. Ini bukan fitur produksi, hanya cara membuat test deterministik.

**K4 — Test media.**

Semua cabang `Media::isReferencedByContent()` diuji:

- 13 cabang kolom (10 model; Post, Event, dan Community punya dua kolom);
- 5 kasus soft-deleted untuk model yang memakai `withTrashed()`.

**K5 — `sourceBytes()` dan walk file.**

- Ekstrak generator privat `scopedFiles()` yang dipakai `sourceBytes()` dan
  `collectFiles()`.
- `sourceBytes()` menjadi public agar bisa diuji langsung; service tetap final dan tidak
  punya interface.
- Dedupe dan aturan skip dotfile pindah ke generator.

**K6 — Rich text kosong.**

`UpdateSettingsRequest::normaliseValue()` memakai `HtmlSanitizer::cleanOrNull()`, bukan
`clean()`. Semua setting bertipe `html` mengikuti aturan ini.

**K7 — Validasi tanggal.**

Callback `after` hanya berhenti jika field tanggal yang relevan punya error:

- Event: `start_at` atau `end_at`;
- Post: `published_at`.

Error field lain tidak boleh lagi menunda validasi tanggal.

### 2.3 Batas yang sengaja diterima

- **Tidak ada estimasi ukuran database sebelum dump.** Dump yang sangat besar masih bisa
  memenuhi disk selama `mysqldump`; cek kedua hanya mencegah kegagalan di fase ZIP.
- **Tidak ada flag installer.** Fail-closed selalu berlaku.
- **Daftar referensi media tetap manual.** `shortcut:` di `Media.php` dipertahankan.
- **Asumsi disk temp = disk backup tetap.** `shortcut:` di `BackupCreator.php`
  dipertahankan dan diperbarui.
- **Filter aktor audit tidak diubah.** Di luar 8 temuan.

---

## 3. Kondisi Codebase Saat Ini

### 3.1 Baseline repository

| Atribut             | Nilai                                                                       |
| ------------------- | --------------------------------------------------------------------------- |
| Branch              | `refactor/refactor-code`                                                    |
| Commit terakhir     | `6ba2314 refactor: polish public and installation UI components`            |
| Status working tree | Banyak perubahan belum di-commit                                            |
| Database            | MySQL 8; dev `website_paroki_hspmtb`, test `website_paroki_hspmtb_test`     |
| Gate penuh          | Belum dijalankan ulang untuk seluruh refactoring                            |
| E2E                 | `master-data.spec.ts` lulus 12/12 dengan `E2E_PORT=8001`; suite penuh belum |

Fase 0 wajib mencatat baseline sebelum perubahan baru. Jangan mencampur commit refactoring
yang sudah ada dengan commit perbaikan review.

### 3.2 Delapan temuan dan bukti

| #   | Temuan                                                          | Bukti                                                                                                                                | Dampak                                                                                     | Prioritas |
| --- | --------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------ | --------- |
| 1   | Cek disk backup tidak menghitung dump database                  | `app/Services/Backup/BackupCreator.php:50-53,243-256,276-301`                                                                        | Backup lolos cek lalu gagal di tengah; disk bisa penuh                                     | Must      |
| 2   | Installer fail-open saat database aplikasi tidak bisa dihubungi | `app/Services/Installation/InstallationRunner.php:75-79`                                                                             | Saat DB down dan status file hilang, wizard bisa diarahkan ke DB kosong dan menimpa `.env` | Must      |
| 3   | Guard media hanya diuji 2 dari 10 jenis                         | `app/Models/Media.php:159-190`, `tests/Feature/Media/MediaTest.php:524-555`                                                          | Kolom referensi yang salah lolos CI; gambar publik bisa rusak                              | Should    |
| 4   | `sourceBytes()` belum diuji                                     | `app/Services/Backup/BackupCreator.php:276-301`, `tests/Unit/Backup/BackupDiskSpaceTest.php:1-11`                                    | Estimasi bisa 0 tanpa terdeteksi; cek disk tidak efektif                                   | Should    |
| 5   | Test `disk_full` bergantung sisa disk mesin                     | `tests/Feature/Backup/BackupCreationTest.php:64-84`                                                                                  | Test gagal di mesin dengan sisa disk >1 TB                                                 | Should    |
| 6   | Rich-text settings masih menyimpan `<p></p>`                    | `app/Http/Requests/Settings/UpdateSettingsRequest.php:150-151`, `resources/js/features/public/legal/components/legal-page.tsx:48-50` | Halaman legal menampilkan area kosong, bukan “Konten belum tersedia.”                      | Should    |
| 7   | Validasi tanggal dilewati saat ada error lain                   | `app/Concerns/EventValidationRules.php:150`, `app/Concerns/PostValidationRules.php:196`                                              | Admin memperbaiki satu error, submit, lalu menemukan error berikutnya                      | Nice      |
| 8   | `sourceBytes()` menduplikasi walk `collectFiles()`              | `app/Services/Backup/BackupCreator.php:276-301,393-475`                                                                              | Aturan skip bisa menyimpang; estimasi salah                                                | Nice      |

### 3.3 Shortcut yang bukan temuan

Tiga `shortcut:` berikut adalah keputusan sadar, bukan temuan review:

1. `app/Models/Media.php:161` — daftar referensi media diperbarui manual.
2. `app/Services/Backup/BackupCreator.php:265` — ZIP diasumsikan tidak lebih besar dari
   input dan disk backup diasumsikan sama dengan disk temp.
3. `app/Http/Controllers/Audit/AuditLogController.php:158` — filter aktor memakai daftar
   user; aktor yang sudah dihapus tidak muncul.

Jangan “memperbaiki” shortcut ini tanpa keputusan baru.

---

## 4. Prinsip Implementasi

1. **Fail closed.** Guard keamanan dan prasyarat backup menolak operasi yang tidak aman.
2. **Smallest complete change.** Tidak menambah layer, opsi, atau konfigurasi yang tidak
   diminta.
3. **Test perilaku, bukan implementasi.** Test harus gagal jika perilaku yang dijaga
   hilang.
4. **Satu sumber aturan.** Aturan skip file backup hanya boleh ada di satu tempat.
5. **Jangan menambah cakupan.** Temuan baru dicatat di PR/issue, bukan dikerjakan di
   branch yang sama.
6. **Ikuti pola repository.** Controller tipis, service untuk kemampuan reusable, trait
   untuk aturan validasi, test Pest.
7. **Jangan mengedit file generated.**
8. **Dokumentasi ikut berubah.** Keputusan installer yang lama (D-90) harus diperbarui
   agar tidak bertentangan dengan kode.

---

## 5. Rencana per Fase

### Fase 0 — Baseline dan guardrail

**Target:** memastikan pekerjaan review dimulai dari baseline yang jelas dan tidak
mencampur perubahan yang belum di-commit.

**Langkah:**

1. Periksa `git status` dan `git diff --stat`.
2. Commit refactoring yang sudah selesai, atau simpan sebagai stash/cabang terpisah.
   Jangan memulai perbaikan review di atas working tree yang bercampur.
3. Pastikan MySQL 8 berjalan.
4. Jalankan baseline:
   - `composer ci:check`
   - bila gagal, catat kegagalan dan tentukan apakah berasal dari refactoring sebelumnya
     atau lingkungan.
5. Buat branch untuk fase pertama.

**Risiko:**

- Baseline penuh bisa gagal karena test `disk_full` bergantung lingkungan. Catat sebagai
  temuan yang memang akan diperbaiki di Fase 1B; jangan “memperbaikinya” dengan mengubah
  threshold secara acak.
- `composer ci:check` menjalankan seluruh test dan bisa memakan waktu; jangan memotongnya
  dengan `--no-verify` saat commit.

**Selesai bila:** baseline tercatat dan branch perbaikan terpisah dari refactoring lama.

---

### Fase 1A — Installer fail-closed

**Target:** installer tidak lagi bisa diarahkan ke database kosong ketika database
aplikasi sedang tidak bisa dihubungi.

**Berkas:**

- `app/Services/Installation/InstallationRunner.php:75-79`
- `tests/Feature/Installation/InstallerLockTest.php`
- `tests/Feature/Installation/InstallationRunTest.php`
- `docs/DECISIONS.md` — D-90
- `docs/DEVELOPMENT.md` — §11.1

**Langkah:**

1. Tulis test baru yang gagal:
   - fake `DatabaseReadinessChecker` mengembalikan `ok=false`,
     `code=DATABASE_UNREACHABLE`;
   - POST `/install/run` dengan session instalasi yang sah;
   - harapkan `status=failed`, `error_code=DATABASE_UNREACHABLE`, dan state `failed`.
2. Ubah `InstallationRunner::run()`:
   - ganti pengecekan `code === DatabaseNotFresh` menjadi `if (! $applicationReadiness['ok'])`;
   - map `code` ke `InstallationError::tryFrom()`;
   - fallback ke `DatabaseUnreachable` bila code tidak dikenal.
3. Pastikan guard tetap sebelum `applyDatabaseConfig($database)`.
4. Pastikan `EnvironmentWriter` tidak dipanggil pada jalur ini. Bila perlu, gunakan fake
   writer yang mencatat pemanggilan.
5. Perbarui D-90:
   - database aplikasi yang sudah berisi tabel **atau** tidak bisa dihubungi ditolak;
   - tidak ada flag bypass.
6. Perbarui §11.1 `docs/DEVELOPMENT.md`:
   - database target harus bisa dihubungi dan kosong;
   - setelah status `failed`, operator memperbaiki `DB_*` lalu menghapus
     `storage/app/private/installation/status.json` untuk mengulang.

**Risiko:**

- Instalasi baru dengan database yang belum dibuat akan ditolak. Ini disengaja; operator
  harus membuat database kosong lebih dulu.
- Status instalasi menjadi `failed`; retry memerlukan penghapusan status file. Prosedur ini
  harus jelas di dokumentasi.
- Pesan error tidak boleh membocorkan kredensial. Gunakan pesan `DatabaseUnreachable`
  yang sudah ada.
- Guard yang fail-closed juga menolak database yang sedang down karena gangguan sementara.
  Ini trade-off keamanan yang disepakati.

**Pengujian:**

```bash
php artisan test --compact tests/Feature/Installation/InstallerLockTest.php
php artisan test --compact tests/Feature/Installation/InstallationRunTest.php
php artisan test --compact tests/Feature/Installation
```

Kasus wajib:

- unreachable → failed, `DATABASE_UNREACHABLE`, tidak menulis `.env`;
- not fresh → failed, `DATABASE_NOT_FRESH`;
- fresh install → completed;
- status file failed tidak membuka retry otomatis.

**Selesai bila:** tidak ada jalur instalasi yang lanjut saat database aplikasi tidak bisa
dihubungi, dan dokumentasi tidak lagi menyatakan sebaliknya.

---

### Fase 1B — Cek disk backup menghitung dump

**Target:** backup database-only dan full backup gagal lebih awal ketika ruang tidak cukup
untuk dump + salinan + ZIP.

**Berkas:**

- `app/Services/Backup/BackupCreator.php:50-53,243-256,276-301`
- `app/Services/Backup/BackupStorage.php:139-147`
- `tests/Unit/Backup/BackupDiskSpaceTest.php`
- `tests/Feature/Backup/BackupCreationTest.php`

**Langkah:**

1. Tulis unit test rumus yang gagal:
   - `requiredBytes(0, threshold)` tetap sama;
   - `requiredBytes(0, threshold, dump)` = `2 × dump + threshold`;
   - `requiredBytes(source, threshold, dump)` = `2 × (source + dump) + threshold`.
2. Tulis feature test deterministik:
   - ganti test threshold 1 TB;
   - inject free space di bawah threshold → `disk_full` sebelum dump;
   - inject free space sedikit di atas threshold tetapi di bawah `threshold + 2 × dump`
     → `disk_full` setelah dump.
3. Tambah seam di `BackupStorage`:

   ```php
   public function __construct(
       private readonly ?int $freeBytesOverride = null,
   ) {}
   ```

   `freeBytes()` mengembalikan override bila tidak null; jika tidak, tetap memakai
   `disk_free_space()`.
4. Ubah `BackupCreator::create()`:
   - hitung `$source` sekali (`sourceBytes()` untuk full, `0` untuk database-only);
   - cek disk sebelum dump;
   - setelah dump, cek disk lagi dengan `$dump['size']`;
   - baru lakukan hash, `collectFiles()`, dan `writeZip()`.
5. Ubah `assertDiskSpace()` menjadi menerima `int $sourceBytes, int $dumpBytes = 0`.
6. Ubah `requiredBytes()` menjadi menerima `int $dumpBytes = 0` dan memakai rumus
   `2 × (source + dump) + threshold`.
7. Perbarui komentar `shortcut:` agar menyebut dump dan batas yang masih berlaku.

**Risiko:**

- Cek kedua berjalan setelah dump; dump raksasa masih bisa memenuhi disk selama
  `mysqldump`. Estimasi awal dari `information_schema` sengaja ditunda.
- Rumus lebih konservatif; backup dengan ruang mepet bisa ditolak meskipun sebenarnya
  cukup. Ini lebih baik daripada gagal di tengah.
- `freeBytesOverride` adalah seam test. Pastikan default `null` dan tidak dipakai di
  produksi.
- `sourceBytes()` dihitung sekali; file yang berubah selama dump bisa membuat estimasi
  usang. Risiko lama, bukan regresi baru.

**Pengujian:**

```bash
php artisan test --compact tests/Unit/Backup/BackupDiskSpaceTest.php
php artisan test --compact tests/Feature/Backup/BackupCreationTest.php
```

Kasus wajib:

- database-only dengan free space di bawah threshold → gagal sebelum dump;
- database-only dengan free space cukup untuk pre-check tetapi tidak untuk dump → gagal
  setelah dump;
- full backup dengan file sumber → sukses;
- backup database → sukses;
- test tidak membaca sisa disk mesin.

**Selesai bila:** cek disk memakai ukuran dump aktual dan test `disk_full` deterministik.

---

### Fase 2A — Test seluruh cabang referensi media

**Target:** setiap kolom yang membuat media “masih dipakai” punya test yang membuktikan
delete ditolak.

**Berkas:**

- `tests/Feature/Media/MediaTest.php:524-555`
- `app/Models/Media.php:159-190` — tidak diubah, hanya diuji

**Langkah:**

1. Tambah dataset di `MediaTest.php` dengan `Media::factory()->create()`.
2. Cakup 13 cabang:

   | #   | Model             | Kolom                                 |
   | --- | ----------------- | ------------------------------------- |
   | 1   | Post              | `media_id`                            |
   | 2   | Post              | `featured_image` (prefix varian)      |
   | 3   | Event             | `media_id`                            |
   | 4   | Event             | `poster_path`                         |
   | 5   | Service           | `thumbnail_media_id`                  |
   | 6   | Community         | `logo_media_id`                       |
   | 7   | Community         | `thumbnail_media_id`                  |
   | 8   | Area              | `photo_media_id`                      |
   | 9   | Pastor            | `photo_media_id`                      |
   | 10  | ParishBoardMember | `photo_media_id`                      |
   | 11  | ParishProfile     | `cover_media_id`                      |
   | 12  | GalleryPhoto      | `media_id`                            |
   | 13  | SiteSetting       | key bertipe `media` (misalnya `logo`) |

3. Tambah 5 kasus soft-deleted untuk model yang memakai `withTrashed()`:
   - Post, Event, Service, Community, Area.
4. Setiap kasus:
   - buat media;
   - buat satu referensi;
   - `isReferencedByContent()` harus `true`;
   - DELETE route media harus redirect dengan error `media`;
   - row media masih ada.
5. Pertahankan test HTTP post/setting yang sudah ada, atau gabungkan ke dataset tanpa
   menghilangkan cakupan wiring controller.

**Risiko:**

- Test bertambah sekitar 18 kasus dan sedikit memperlambat suite media.
- Factory `ParishProfile`, `GalleryPhoto`, dan `ParishBoardMember` membuat relasi tambahan;
  pastikan override kolom media tetap dipakai.
- Jangan memakai `storeMedia()` bila tidak perlu; guard berjalan sebelum file disentuh,
  sehingga `Media::factory()` cukup dan lebih cepat.
- Test soft-deleted harus memastikan model benar-benar terhapus (soft delete), bukan hanya
  `is_published=false`.

**Pengujian:**

```bash
php artisan test --compact tests/Feature/Media/MediaTest.php --filter="refuses to delete media"
php artisan test --compact tests/Feature/Media/MediaTest.php
```

**Selesai bila:** tidak ada cabang referensi media yang lolos tanpa test.

---

### Fase 2B — Test `sourceBytes()` dan satukan walk file

**Target:** estimasi ukuran file sumber terverifikasi, dan aturan skip hanya ditulis di
satu tempat.

**Berkas:**

- `app/Services/Backup/BackupCreator.php:276-301,393-475`
- `tests/Unit/Backup/BackupDiskSpaceTest.php` atau
  `tests/Unit/Backup/BackupSourceBytesTest.php` (baru)

**Langkah:**

1. Tulis test `sourceBytes()` yang gagal:
   - fake disk `local` dan `public`;
   - set config scope media original, media variant, dan documents;
   - buat file dengan ukuran berbeda;
   - tambah dotfile dan file bersarang;
   - assert jumlah byte tepat dan dotfile tidak dihitung.
2. Ekstrak generator privat `scopedFiles()`:
   - iterasi `scopes()`;
   - lewati path kosong;
   - trim slash;
   - lewati dotfile;
   - dedupe berdasarkan `disk + relative`.
3. `sourceBytes()` memakai generator dan menjumlahkan `size()` dengan `try/catch`
   best-effort.
4. `collectFiles()` memakai generator; path guard, symlink check, copy, dan manifest tetap
   di `collectFiles()`.
5. Hapus `$seen` duplikat di `collectFiles()` karena sudah ditangani generator.
6. Jadikan `sourceBytes()` public dengan docblock bahwa ini diekspos untuk test; service
   tetap final dan tidak punya interface.

**Risiko:**

- `sourceBytes()` sekarang melakukan dedupe; hasil untuk scope duplikat menjadi lebih kecil
  dan lebih benar.
- Path guard tetap hanya di `collectFiles()`. Jika ada path tidak aman, `sourceBytes()`
  bisa menghitungnya sebagai overestimate; backup tetap gagal di `collectFiles()`. Ini
  aman.
- API public baru kecil; tidak ada interface yang berubah.
- Generator harus mempertahankan urutan dan aturan skip yang sama dengan `collectFiles()`
  lama.

**Pengujian:**

```bash
php artisan test --compact tests/Unit/Backup
php artisan test --compact tests/Feature/Backup/BackupCreationTest.php
```

Kasus wajib:

- tiga scope dijumlahkan;
- dotfile tidak dihitung;
- file bersarang dihitung;
- full backup yang ada tetap menyalin file yang benar;
- `collectFiles()` masih melewati dotfile dan symlink.

**Selesai bila:** `sourceBytes()` punya test langsung dan tidak ada duplikasi walk.

---

### Fase 3A — Rich-text kosong menyimpan `null`

**Target:** mengosongkan kebijakan privasi atau syarat ketentuan tidak lagi menyimpan
`<p></p>`.

**Berkas:**

- `app/Http/Requests/Settings/UpdateSettingsRequest.php:150-151`
- `tests/Feature/Settings/SiteSettingsTest.php`

**Langkah:**

1. Tulis test yang gagal:
   - kirim `settings.privacy_policy_content = '<p></p>'`;
   - assert kolom `value` null dan `settingsService()->get()` null;
   - ulangi untuk `terms_and_conditions_content`.
2. Ganti `HtmlSanitizer::clean()` menjadi `cleanOrNull()` di `normaliseValue()`.
3. Tambah test Inertia:
   - setelah editor dikosongkan, GET `route('legal.privacy')`;
   - assert komponen `public/legal/privacy` menerima `content` null.
4. Pastikan test lama yang mengirim string kosong tetap lulus.

**Risiko:**

- Semua setting bertipe `html` sekarang menganggap markup kosong sebagai null. Ini
  konsisten dengan Profil, Pelayanan, dan Komunitas.
- `cleanOrNull()` tidak boleh menghapus dokumen yang hanya berisi gambar; helper sudah
  menangani `<p><img ...></p>`.
- Tidak ada perubahan frontend; komponen legal sudah menampilkan empty state.

**Pengujian:**

```bash
php artisan test --compact tests/Feature/Settings/SiteSettingsTest.php
```

Kasus wajib:

- `<p></p>` → null untuk privacy dan terms;
- `<p> </p>` dan `<p><br></p>` → null;
- `<p><img ...></p>` tetap tersimpan;
- sanitasi script/event handler tetap berjalan;
- halaman legal menerima null.

**Selesai bila:** editor kosong tidak lagi menghasilkan konten HTML kosong yang truthy.

---

### Fase 3B — Validasi tanggal melaporkan error bersamaan

**Target:** admin melihat error tanggal dan error field lain dalam satu submit.

**Berkas:**

- `app/Concerns/EventValidationRules.php:150`
- `app/Concerns/PostValidationRules.php:196`
- `tests/Feature/Agenda/EventTest.php`
- `tests/Feature/News/PostTest.php`

**Langkah:**

1. Tulis test yang gagal:
   - Event: all-day + `end_at` beda hari + `title` kosong → error `title` dan `end_at`;
   - Event: `start_at` malformed + `title` kosong → error `title` dan `start_at`,
     tidak 500;
   - Post: scheduled + `published_at` di masa lalu + `title` kosong → error `title` dan
     `published_at`;
   - Post: `published_at` malformed + `title` kosong → error `title` dan `published_at`,
     tidak 500.
2. Ubah Event:
   - ganti `$validator->errors()->isNotEmpty()` dengan
     `has('start_at') || has('end_at')`.
3. Ubah Post:
   - ganti dengan `has('published_at')`.
4. Pertahankan komentar bahwa tanggal malformed tidak boleh di-parse ulang agar 422 tidak
   menjadi 500.

**Risiko:**

- Jika nama field tanggal berubah, pengecekan harus ikut diperbarui. Komentar di kode
  harus menyebut field yang dijaga.
- Error tanggal dari rule lain tetap membuat callback berhenti; itu benar karena parsing
  ulang tidak aman.
- Jangan menghapus early return sepenuhnya; itu bisa mengubah 422 menjadi 500.

**Pengujian:**

```bash
php artisan test --compact tests/Feature/Agenda/EventTest.php
php artisan test --compact tests/Feature/News/PostTest.php
```

**Selesai bila:** error tanggal tidak lagi “menunggu” error lain diperbaiki.

---

### Fase 4 — Dokumentasi dan keputusan

**Target:** dokumentasi tidak bertentangan dengan perilaku baru.

**Berkas:**

- `docs/DECISIONS.md` — D-90, D-93 bila perlu
- `docs/DEVELOPMENT.md` — §11.1
- `docs/plan-implementation/plan-refactoring-audit-codebase-2026-11.md` — §12
- `app/Services/Backup/BackupCreator.php` — komentar rumus/shortcut bila belum

**Langkah:**

1. D-90: installer menolak `DATABASE_NOT_FRESH` **dan** `DATABASE_UNREACHABLE`.
2. DEVELOPMENT §11.1:
   - database target harus bisa dihubungi dan kosong;
   - cara mengulang setelah status `failed`.
3. Plan status §12:
   - tandai temuan 1–8 selesai beserta tanggal/commit;
   - catat temuan yang sengaja ditunda.
4. Komentar backup:
   - jelaskan rumus `2 × (source + dump) + threshold`;
   - sebut asumsi ZIP dan disk yang tersisa.

**Pengujian:**

```bash
rg -n "DATABASE_UNREACHABLE|2 × source|2 \* source|low_disk_threshold" docs app/Services/Backup
git diff --check
```

**Selesai bila:** tidak ada teks dokumentasi yang menyatakan perilaku lama.

---

### Fase 5 — Verifikasi akhir

**Target:** semua perubahan lolos gate penuh dan siap di-commit.

**Langkah:**

1. Jalankan Pint:

   ```bash
   vendor/bin/pint --dirty --format agent
   ```

2. Jalankan gate penuh:

   ```bash
   composer ci:check
   ```

3. Periksa diff:

   ```bash
   git diff --check
   git status --short
   ```

4. Pastikan tidak ada file generated yang berubah.
5. E2E tidak wajib karena tidak ada perubahan UI. Jika ingin smoke test manual:
   - kosongkan kebijakan privasi di admin, buka halaman publik, pastikan muncul
     “Konten belum tersedia.”;
   - jalankan satu backup database dan satu full backup di lingkungan dev.

**Risiko:**

- `composer ci:check` membutuhkan MySQL; pastikan service berjalan.
- Full test suite bisa memakan waktu; jangan memotong dengan `--no-verify`.
- Jika E2E dijalankan saat `composer dev` memakai port 8000, gunakan `E2E_PORT=8001`
  seperti pada verifikasi Master Data sebelumnya.

**Selesai bila:** `composer ci:check` lulus, diff bersih, dan tidak ada file generated
yang berubah.

---

## 6. Urutan Eksekusi dan Dependency

| Urutan | Fase    | Dependency | Catatan                            |
| ------ | ------- | ---------- | ---------------------------------- |
| 1      | Fase 0  | —          | Baseline dan pemisahan branch      |
| 2      | Fase 1A | Fase 0     | Keamanan installer                 |
| 3      | Fase 1B | Fase 0     | Backup disk check                  |
| 4      | Fase 2A | Fase 0     | Test media; independen             |
| 5      | Fase 2B | Fase 1B    | Keduanya menyentuh `BackupCreator` |
| 6      | Fase 3A | Fase 0     | Independen                         |
| 7      | Fase 3B | Fase 0     | Independen                         |
| 8      | Fase 4  | Fase 1–3   | Dokumentasi                        |
| 9      | Fase 5  | Semua      | Gate penuh                         |

Fase 2A dan 3A/3B boleh dikerjakan paralel di branch terpisah, tetapi jangan digabung
dalam satu PR tanpa alasan.

---

## 7. Test Matrix

| Fase | File test                                            | Kasus utama                                              | Perintah                                                                 |
| ---- | ---------------------------------------------------- | -------------------------------------------------------- | ------------------------------------------------------------------------ |
| 1A   | `tests/Feature/Installation/InstallerLockTest.php`   | Unreachable, not fresh, fresh install, failed status     | `php artisan test --compact tests/Feature/Installation`                  |
| 1A   | `tests/Feature/Installation/InstallationRunTest.php` | Run lengkap, tidak menulis `.env` saat gagal             | idem                                                                     |
| 1B   | `tests/Unit/Backup/BackupDiskSpaceTest.php`          | Rumus source + dump + threshold                          | `php artisan test --compact tests/Unit/Backup/BackupDiskSpaceTest.php`   |
| 1B   | `tests/Feature/Backup/BackupCreationTest.php`        | Pre-check, post-dump check, database backup, full backup | `php artisan test --compact tests/Feature/Backup/BackupCreationTest.php` |
| 2A   | `tests/Feature/Media/MediaTest.php`                  | 13 cabang + 5 soft-deleted                               | `php artisan test --compact tests/Feature/Media/MediaTest.php`           |
| 2B   | `tests/Unit/Backup/BackupSourceBytesTest.php`        | Jumlah file, dotfile, nested file                        | `php artisan test --compact tests/Unit/Backup`                           |
| 2B   | `tests/Feature/Backup/BackupCreationTest.php`        | Full backup tetap menyalin file                          | idem                                                                     |
| 3A   | `tests/Feature/Settings/SiteSettingsTest.php`        | `<p></p>` → null, halaman legal                          | `php artisan test --compact tests/Feature/Settings/SiteSettingsTest.php` |
| 3B   | `tests/Feature/Agenda/EventTest.php`                 | All-day + error lain, malformed date                     | `php artisan test --compact tests/Feature/Agenda/EventTest.php`          |
| 3B   | `tests/Feature/News/PostTest.php`                    | Scheduled + error lain, malformed date                   | `php artisan test --compact tests/Feature/News/PostTest.php`             |
| 5    | Seluruh suite                                        | Regression                                               | `composer ci:check`                                                      |

---

## 8. Ringkasan File yang Terdampak

### 8.1 File baru

| File                                          | Isi                                            |
| --------------------------------------------- | ---------------------------------------------- |
| `tests/Unit/Backup/BackupSourceBytesTest.php` | Test langsung `sourceBytes()` dengan fake disk |

### 8.2 File diubah

| File                                                   | Perubahan                                                                     |
| ------------------------------------------------------ | ----------------------------------------------------------------------------- |
| `app/Services/Installation/InstallationRunner.php`     | Guard fail-closed untuk semua hasil readiness yang tidak ok                   |
| `app/Services/Backup/BackupCreator.php`                | Cek disk kedua, rumus dump, generator `scopedFiles()`, `sourceBytes()` public |
| `app/Services/Backup/BackupStorage.php`                | Seam `freeBytesOverride` untuk test                                           |
| `app/Http/Requests/Settings/UpdateSettingsRequest.php` | `cleanOrNull()` untuk rich text                                               |
| `app/Concerns/EventValidationRules.php`                | Early return hanya untuk error `start_at`/`end_at`                            |
| `app/Concerns/PostValidationRules.php`                 | Early return hanya untuk error `published_at`                                 |
| `tests/Feature/Installation/InstallerLockTest.php`     | Test unreachable                                                              |
| `tests/Feature/Installation/InstallationRunTest.php`   | Test jalur gagal tidak menulis `.env`                                         |
| `tests/Unit/Backup/BackupDiskSpaceTest.php`            | Rumus dengan dump                                                             |
| `tests/Feature/Backup/BackupCreationTest.php`          | Test deterministik pre/post check                                             |
| `tests/Feature/Media/MediaTest.php`                    | Dataset 13 cabang + soft-deleted                                              |
| `tests/Feature/Settings/SiteSettingsTest.php`          | Test `<p></p>` dan halaman legal                                              |
| `tests/Feature/Agenda/EventTest.php`                   | Test error bersamaan                                                          |
| `tests/Feature/News/PostTest.php`                      | Test error bersamaan                                                          |

### 8.3 File dokumentasi

| File                                                                  | Perubahan                          |
| --------------------------------------------------------------------- | ---------------------------------- |
| `docs/DECISIONS.md`                                                   | D-90 diperbarui; D-93 bila perlu   |
| `docs/DEVELOPMENT.md`                                                 | §11.1 prasyarat dan prosedur retry |
| `docs/plan-implementation/plan-refactoring-audit-codebase-2026-11.md` | §12 status                         |
| `docs/plan-implementation/plan-refactoring-review-fixes-2026-11.md`   | Dokumen ini                        |

### 8.4 File yang tidak disentuh

- Semua migration dan schema database.
- Route, controller, policy, dan frontend Master Data.
- `resources/js/{routes,actions,wayfinder}` dan `public/build`.
- Dependency `composer.json` / `package.json`.
- `resources/js/features/public/legal/components/legal-page.tsx` — sudah menangani null.
- `app/Models/Media.php` — hanya diuji, tidak diubah.
- Shortcut media, backup disk, dan audit actor — keputusan sadar.

---

## 9. Risk Register

| Risiko                                                    | Kemungkinan   | Dampak | Mitigasi                                                                       |
| --------------------------------------------------------- | ------------- | ------ | ------------------------------------------------------------------------------ |
| Installer memblokir instalasi baru karena DB belum dibuat | Sedang        | Sedang | Dokumentasi jelas: buat DB kosong dan pastikan `DB_*` benar sebelum wizard     |
| Status `failed` membuat retry terkunci                    | Tinggi        | Rendah | Dokumentasikan penghapusan status file setelah DB diperbaiki                   |
| Dump raksasa memenuhi disk sebelum cek kedua              | Rendah–sedang | Tinggi | Cek kedua menangkap kegagalan ZIP; estimasi awal ditunda dan dicatat           |
| Rumus disk terlalu konservatif                            | Sedang        | Rendah | Backup ditolak lebih awal; operator bisa menurunkan threshold sesuai kebijakan |
| Seam `freeBytesOverride` bocor ke produksi                | Rendah        | Rendah | Default null; hanya test yang mengisi; tidak ada route/config                  |
| Test media lambat                                         | Sedang        | Rendah | Gunakan factory, bukan upload; 18 kasus masih wajar                            |
| Factory media membuat relasi tak terduga                  | Rendah        | Rendah | Override kolom media secara eksplisit; assert row media tetap ada              |
| `sourceBytes()` public dianggap API                       | Rendah        | Rendah | Service final, tanpa interface; docblock menjelaskan tujuan test               |
| Dedupe walk mengubah estimasi                             | Rendah        | Rendah | Test jumlah byte; overestimate/underestimate dicek                             |
| Rich text null menghapus konten yang disengaja            | Rendah        | Rendah | Hanya markup kosong; test gambar tetap tersimpan                               |
| Early return validasi salah field                         | Rendah        | Sedang | Test malformed date + error lain; komentar field                               |
| `composer ci:check` gagal karena lingkungan               | Sedang        | Rendah | Pastikan MySQL; catat kegagalan lingkungan, jangan klaim lulus                 |

---

## 10. Verifikasi dan Acceptance

### 10.1 Definition of Done global

- [ ] Setiap bug fix punya test yang gagal sebelum fix dan lulus setelah fix.
- [x] Tidak ada migration, route, atau perubahan UI.
- [x] Semua 8 temuan tertutup atau sengaja ditunda dengan alasan.
- [x] D-90 dan DEVELOPMENT tidak bertentangan dengan kode.
- [ ] `composer ci:check` lulus.
- [x] `git diff --check` bersih.
- [x] Tidak ada file generated yang diedit manual.
- [x] §12 diperbarui dengan tanggal, commit, dan PR.

### 10.2 Checklist per fase

| Fase | Checklist                                                                      |
| ---- | ------------------------------------------------------------------------------ |
| 0    | Baseline tercatat; branch terpisah; MySQL hidup                                |
| 1A   | Test unreachable lulus; not fresh tetap lulus; docs diperbarui                 |
| 1B   | Test pre/post check lulus; test tidak bergantung disk mesin; full backup lulus |
| 2A   | 13 cabang + 5 soft-deleted lulus; test lama tetap lulus                        |
| 2B   | `sourceBytes()` teruji; walk hanya satu; full backup lulus                     |
| 3A   | `<p></p>` → null; halaman legal null; sanitasi tetap                           |
| 3B   | Error tanggal + error lain tampil bersamaan; malformed date tidak 500          |
| 4    | Docs tidak menyebut perilaku lama                                              |
| 5    | `composer ci:check` hijau; diff bersih                                         |

### 10.3 Checklist rilis

- [ ] Branch per fase sudah di-merge atau siap di-merge.
- [ ] Tidak ada perubahan yang belum di-review.
- [ ] Backup manual dan full backup diuji di lingkungan dev.
- [ ] Halaman legal diuji manual setelah editor dikosongkan.
- [ ] Installer diuji pada database kosong yang bisa dihubungi.
- [ ] Tidak ada `--no-verify` yang dipakai.

---

## 11. Out of Scope

1. Estimasi ukuran database dari `information_schema` sebelum dump.
2. Flag `INSTALLER_ALLOW_UNREACHABLE`.
3. Pengecekan schema-driven untuk daftar referensi media.
4. Pemisahan disk temp dan disk backup.
5. Perbaikan filter aktor audit.
6. Refactor Master Data, FormMeta, atau policy trait.
7. Perubahan UI, termasuk komponen legal.
8. Full E2E suite.
9. Perubahan dependency atau lockfile.
10. Migrasi atau perubahan schema.

---

## 12. Status Implementasi

| Fase | Status           | Tanggal    | Commit | PR  | Catatan                                                                 |
| ---- | ---------------- | ---------- | ------ | --- | ----------------------------------------------------------------------- |
| 0    | Partial          | 2026-11-02 | —      | —   | Baseline targeted per fase; `composer ci:check` penuh belum dijalankan |
| 1A   | Done             | 2026-11-02 | —      | —   | Installer fail-closed + test `DATABASE_UNREACHABLE`                     |
| 1B   | Done             | 2026-11-02 | —      | —   | Cek disk + dump + test deterministik                                    |
| 2A   | Done             | 2026-11-02 | —      | —   | 18 kasus media (13 cabang + 5 soft-deleted)                             |
| 2B   | Done             | 2026-11-02 | —      | —   | `sourceBytes()` public + shared walk + test                             |
| 3A   | Done             | 2026-11-02 | —      | —   | `cleanOrNull()` + test halaman legal                                    |
| 3B   | Done             | 2026-11-02 | —      | —   | Error tanggal + error field lain                                        |
| 4    | Done             | 2026-11-02 | —      | —   | D-90, DEVELOPMENT, komentar backup                                      |
| 5    | Partial          | 2026-11-02 | —      | —   | Pint, PHPStan, test targeted; gate penuh ditunda sesuai instruksi user  |

---

## 13. Lampiran

### 13.1 Cheat sheet perintah

```bash
# Baseline / gate penuh (MySQL harus hidup)
composer ci:check

# Test PHP spesifik
php artisan test --compact tests/Feature/Installation
php artisan test --compact tests/Unit/Backup
php artisan test --compact tests/Feature/Backup/BackupCreationTest.php
php artisan test --compact tests/Feature/Media/MediaTest.php
php artisan test --compact tests/Feature/Settings/SiteSettingsTest.php
php artisan test --compact tests/Feature/Agenda/EventTest.php
php artisan test --compact tests/Feature/News/PostTest.php

# Format PHP
vendor/bin/pint --dirty --format agent

# Cek diff
git diff --check
git status --short

# E2E (tidak wajib untuk plan ini)
npm run e2e:prepare
E2E_PORT=8001 npx playwright test tests/e2e/master-data.spec.ts
```

### 13.2 Format commit

```text
<type>(<scope>): <subject ≤ 100 karakter>

Contoh:
fix(installer): fail closed when the application database is unreachable
fix(backup): count the database dump in the disk-space check
test(media): cover every reference branch in the delete guard
refactor(backup): share the scoped file walk and test sourceBytes
fix(settings): store an emptied rich-text setting as null
fix(validation): report date errors alongside unrelated field errors
docs: record the fail-closed installer and backup disk check
```

Gunakan Conventional Commits. Jangan memakai `--no-verify`; CI tetap menjalankan gate.

### 13.3 Referensi

- `AGENTS.md` — commands, tests, generated files, commit.
- `docs/DECISIONS.md` — D-14, D-25, D-90, D-91, D-93, D-94.
- `docs/DEVELOPMENT.md` — §11 Installation Wizard.
- `docs/plan-implementation/plan-refactoring-audit-codebase-2026-11.md` — plan audit sebelumnya.
- `docs/plan-implementation/plan-backup-recovery.md` — perilaku backup dan restore.
- `app/Services/Backup/BackupCreator.php` — implementasi cek disk.
- `app/Services/Installation/InstallationRunner.php` — guard installer.
- `app/Models/Media.php` — guard referensi media.
