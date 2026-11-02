# Implementation Plan — Refactoring Audit Codebase (November 2026)

> **Project:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau\
> **Scope:** Refactoring 20 temuan audit codebase (bug, risiko, performa, duplikasi, dead code)\
> **Status:** Planned — belum diimplementasikan\
> **Branch dasar (audit):** `refactor/refactor-code`\
> **Branch (usulan):** satu branch per fase, contoh `refactor/audit-installer-guard`, `refactor/audit-date-boundary`\
> **Sumber kebenaran:** `AGENTS.md` → `PRD.md` → `ARCHITECTURE.md` → `docs/DECISIONS.md` → dokumen ini\
> **Tanggal audit:** 2026-11-02\
> **Dokumen audit pertama:** `docs/plan-implementation/plan-refactoring-audit-codebase.md` (15 temuan, Oktober 2026)

Dokumen ini menjelaskan **apa yang diubah, di file mana, dalam urutan apa, dan bagaimana
diverifikasi**. Dokumen ini bukan roadmap fitur; ini rencana perbaikan teknis berdasarkan
audit kedua. Semua keputusan di §2 **mengikat** dan tidak boleh diubah tanpa persetujuan
pengguna.

> **Penting:** Baseline test belum dijalankan ulang saat dokumen ini ditulis. Fase 0 wajib
> menjalankan baseline sebelum fase lain dimulai. Jangan mengklaim suatu fase lulus tanpa
> output perintah yang benar-benar dijalankan.

---

## Daftar Isi

- [Implementation Plan — Refactoring Audit Codebase (November 2026)](#implementation-plan--refactoring-audit-codebase-november-2026)
  - [Daftar Isi](#daftar-isi)
  - [0. Instruksi untuk AI Agent](#0-instruksi-untuk-ai-agent)
  - [1. Ringkasan Eksekutif](#1-ringkasan-eksekutif)
    - [1.1 Hasil yang diharapkan](#11-hasil-yang-diharapkan)
    - [1.2 Batasan global](#12-batasan-global)
    - [1.3 Hubungan dengan plan audit pertama](#13-hubungan-dengan-plan-audit-pertama)
  - [2. Keputusan yang Mengikat](#2-keputusan-yang-mengikat)
    - [2.1 Keputusan produk](#21-keputusan-produk)
    - [2.2 Keputusan teknis yang mengikat](#22-keputusan-teknis-yang-mengikat)
    - [2.3 Koreksi terhadap audit](#23-koreksi-terhadap-audit)
  - [3. Kondisi Codebase Saat Ini](#3-kondisi-codebase-saat-ini)
    - [3.1 Baseline repository](#31-baseline-repository)
    - [3.2 20 temuan dan bukti](#32-20-temuan-dan-bukti)
    - [3.3 Konvensi yang mengikat implementasi](#33-konvensi-yang-mengikat-implementasi)
  - [4. Arsitektur Target dan Prinsip Refactoring](#4-arsitektur-target-dan-prinsip-refactoring)
  - [5. Rencana per Fase](#5-rencana-per-fase)
    - [Fase 0 — Baseline dan guardrail](#fase-0--baseline-dan-guardrail)
    - [Fase 1 — Keamanan installer](#fase-1--keamanan-installer)
    - [Fase 2 — Batas input dan tanggal](#fase-2--batas-input-dan-tanggal)
    - [Fase 3 — Performa baca](#fase-3--performa-baca)
    - [Fase 4 — Integritas dan konsistensi data](#fase-4--integritas-dan-konsistensi-data)
      - [4a. Media delete guard](#4a-media-delete-guard)
      - [4b. Rich text kosong](#4b-rich-text-kosong)
      - [4c. Konsistensi galeri dan layanan](#4c-konsistensi-galeri-dan-layanan)
      - [4d. Ruang disk backup](#4d-ruang-disk-backup)
    - [Fase 5 — Deduplikasi mekanis dan dead code](#fase-5--deduplikasi-mekanis-dan-dead-code)
    - [Fase 6 — Master data](#fase-6--master-data)
    - [Fase 7 — Higiene dependensi dan verifikasi akhir](#fase-7--higiene-dependensi-dan-verifikasi-akhir)
  - [6. Urutan Eksekusi dan Dependency](#6-urutan-eksekusi-dan-dependency)
  - [7. Test Matrix](#7-test-matrix)
  - [8. Ringkasan File yang Terdampak](#8-ringkasan-file-yang-terdampak)
    - [8.1 File baru](#81-file-baru)
    - [8.2 File diubah (utama)](#82-file-diubah-utama)
    - [8.3 File dihapus](#83-file-dihapus)
    - [8.4 File generated (regenerasi, bukan edit manual)](#84-file-generated-regenerasi-bukan-edit-manual)
    - [8.5 Tidak disentuh](#85-tidak-disentuh)
  - [9. Risk Register](#9-risk-register)
  - [10. Verifikasi dan Acceptance](#10-verifikasi-dan-acceptance)
    - [10.1 Definition of Done global](#101-definition-of-done-global)
    - [10.2 Checklist acceptance per fase](#102-checklist-acceptance-per-fase)
    - [10.3 Checklist rilis](#103-checklist-rilis)
  - [11. Out of Scope](#11-out-of-scope)
  - [12. Status Implementasi](#12-status-implementasi)
  - [13. Lampiran](#13-lampiran)
    - [13.1 Cheat sheet perintah](#131-cheat-sheet-perintah)
    - [13.2 Format commit](#132-format-commit)
    - [13.3 Referensi keputusan](#133-referensi-keputusan)

---

## 0. Instruksi untuk AI Agent

Baca bagian ini sebelum menyentuh file apa pun.

1. **Baca `AGENTS.md` terlebih dahulu.** Aturan proyek (generated files, test, Pint,
   PHPStan, MySQL, commit) berlaku penuh.
2. **Kerjakan satu fase per branch/PR.** Jangan menggabungkan dua fase kecuali diminta.
3. **Jangan mengubah keputusan di §2.** Kalau implementasi bertabrakan dengan keputusan,
   berhenti dan tanya pengguna.
4. **Jangan menyentuh file di §11.** Tidak ada migrasi, dependensi baru, atau refactor
   di luar 20 temuan.
5. **Untuk perubahan perilaku, tulis test yang gagal dulu**, lalu perbaiki.
6. **Jangan pernah memakai `--no-verify`.** Pre-commit menjalankan gate penuh.
7. **Jalankan perintah verifikasi di setiap fase.** Jangan mengklaim lulus tanpa output.
8. **Perbarui §12** setelah fase selesai (tanggal, commit, PR, catatan).
9. **Jangan mengedit file generated** (`resources/js/{routes,actions,wayfinder}`,
   `public/build`). Regenerasi dengan `php artisan wayfinder:generate --with-form`.
10. **Kalau ragu, tanya.** Jangan mengarang perilaku baru.

---

## 1. Ringkasan Eksekutif

Audit kedua menemukan **4 bug/risiko harus diperbaiki**, **10 perbaikan
risiko/duplikasi/performa**, dan **6 pembersihan**. Tidak ada temuan yang memerlukan
perubahan skema database.

### 1.1 Hasil yang diharapkan

| Hasil           | Ukuran keberhasilan                                                                     |
| --------------- | --------------------------------------------------------------------------------------- |
| Keamanan        | Installer tidak bisa dipakai pada instalasi yang database aplikasinya sudah terisi      |
| Kebenaran input | Tidak ada lagi 500 dari tanggal/bulan yang tidak valid; input buruk menjadi 422         |
| Performa        | Setting dibaca sekali per request; galeri memuat satu foto per album                    |
| Integritas data | Media yang masih dipakai tidak bisa dihapus; rich text kosong tersimpan `null`          |
| Konsistensi     | Jumlah foto galeri sama di kartu dan detail; kartu layanan lain punya thumbnail         |
| Lean            | Satu generator slug, satu pemilih varian, satu helper filter kategori, dead code hilang |
| Dependensi      | `sail` dan `chisel` dihapus; `pao` dipertahankan; hanya satu lockfile JS                |
| Gate            | `composer ci:check` dan E2E hijau setelah setiap fase                                   |

### 1.2 Batasan global

- **Tidak ada migrasi/skema baru.**
- **Tidak ada dependensi baru.**
- **Tidak ada abstraksi/layer baru** kecuali trait/helper yang menggantikan duplikasi
  yang sudah ada.
- **Tidak ada perubahan** pada modul backup/restore selain cek ruang disk, dan tidak ada
  perubahan pada auth/permission.
- Perubahan perilaku harus punya test regresi.
- Bentuk props Inertia tidak berubah kecuali bagian dari bug fix yang diuji.
- Total estimasi: **±8–11 hari kerja** untuk satu developer.

### 1.3 Hubungan dengan plan audit pertama

`plan-refactoring-audit-codebase.md` (Oktober 2026) menangani 15 temuan yang berbeda
(JSON-LD, agenda multi-hari, notice bertarget, foto Area, throttle unduhan, format
tanggal, aturan audit, dead code, CI, telepon, ICS). Dokumen ini **tidak membuka ulang**
keputusan di sana.

Dua titik temu yang perlu dicatat:

1. Plan pertama sengaja **menunda** keputusan `pnpm-lock.yaml` ("keputusan package
   manager terpisah"). Dokumen ini menyelesaikannya di Fase 7.
2. Plan pertama sudah menyapu dead code tertentu (komponen upload, `/design-system`,
   komponen UI). Dead code yang tercantum di sini adalah sisa yang ditemukan audit kedua
   dan **tidak boleh dihapus ulang** jika sudah hilang.

---

## 2. Keputusan yang Mengikat

### 2.1 Keputusan produk

| #   | Pertanyaan                      | Keputusan default                                                                 | Konsekuensi                                                                        |
| --- | ------------------------------- | --------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| 1   | Penulisan `.env` oleh installer | **Pertahankan** penulisan `.env`, tetapi tambahkan guard database aplikasi        | D-90 diperbarui agar sesuai kode; opsi menghapus penulisan `.env` ditunda          |
| 2   | Media yang masih dipakai        | **Tolak penghapusan** dengan pesan jelas                                          | Tidak ada cascade; admin harus melepas referensi dulu                              |
| 3   | Rich text kosong                | Simpan `null`; **tidak ada** pembersihan massal data lama                         | Data lama `<p></p>` tetap sampai diedit ulang; command cleanup bersifat opsional   |
| 4   | Filter aktor audit              | Bangun dari tabel `users` + `system`/`anonymous`                                  | Aktor yang sudah dihapus tidak muncul di dropdown; event tetap bisa difilter by id |
| 5   | Cakupan master data             | Gabungkan policy + list page; controller hanya bagian yang identik; form opsional | Validation rules tetap per modul karena field-nya berbeda                          |
| 6   | `pnpm-lock.yaml`                | Hapus; proyek memakai npm                                                         | Satu lockfile; `docs/DEVELOPMENT.md` sudah menyatakan npm                          |

Jika salah satu keputusan default tidak disetujui, **berhenti sebelum fase terkait** dan
minta keputusan pengguna. Jangan mengubah dokumen ini secara diam-diam.

### 2.2 Keputusan teknis yang mengikat

**K1 — Guard installer (Fase 1).**

`InstallationRunner::run()` memeriksa database **aplikasi yang sedang dikonfigurasi**
sebelum mengganti koneksi ke database yang diposting wizard:

```text
if readiness.check() (tanpa argumen) mengembalikan hasil yang tidak ok
    → lempar InstallationFailure sesuai kode (DATABASE_NOT_FRESH / DATABASE_UNREACHABLE)
```

- `DATABASE_UNREACHABLE` juga ditolak; operator harus memperbaiki koneksi database
  aplikasi lebih dulu. Tidak ada flag bypass.
- Guard dijalankan **sebelum** `applyDatabaseConfig($database)`.
- `.env.example` diubah menjadi `INSTALLER_ENABLED=false`.
- Penulisan `.env` dipertahankan; D-90 diperbarui agar tidak lagi bertentangan dengan
  kode.

**K2 — Konversi waktu lokal (Fase 2).**

Satu trait `App\Concerns\ConvertsLocalDateTime`:

```text
'' → null
string valid → Carbon::parse(value, config('app.display_timezone'))->utc()
string tidak valid → kembalikan nilai asli (biar rule `date` yang menolak)
```

Tidak ada `Carbon::parse` mentah di `prepareForValidation()`.

**K3 — Setting per request (Fase 3).**

- `SiteSettingsService::all()` memoisasi hasil di properti instance.
- Binding memakai `$this->app->scoped(...)`, **bukan** `singleton`, agar worker queue
  tidak menyimpan setting basi antar-job.
- `forget()` mengosongkan properti dan cache.

**K4 — Galeri (Fase 3).**

- Direktori galeri memuat **satu** foto siap per album untuk fallback sampul.
- `photo_count` memakai filter `ready()` yang sama dengan halaman detail.
- Jika `limit()` per-parent tidak berperilaku benar, gunakan relasi `hasOne`
  `firstReadyPhoto`; jangan memuat semua foto.

**K5 — Media delete (Fase 4).**

- `Media::isReferencedByContent()` memeriksa semua kolom `*_media_id`, kolom path
  (`posts.featured_image`, `events.poster_path`), `gallery_photos.media_id`, dan
  `site_settings` bertipe `media`.
- Jika direferensikan: tolak, jangan hapus row/file.
- Jika tidak: perilaku lama dipertahankan.

**K6 — Rich text (Fase 4).**

- Satu helper `HtmlSanitizer::cleanOrNull(?string): ?string`.
- `<p></p>`, `<p><br></p>`, dan whitespace menjadi `null`.
- `<p><img ...></p>` tetap tersimpan.
- Tidak ada UPDATE massal data lama tanpa command dry-run terpisah.

**K7 — Master data (Fase 6).**

- Policy kategori: satu trait `ChecksModulePermission`.
- List page kategori: satu komponen generik dengan props kolom/route/filter.
- Controller kategori: hanya transaksi audit + guard "masih dipakai" yang diekstrak.
- Form kategori: opsional; hanya gabungkan yang benar-benar identik.
- `*ValidationRules` **tidak** digabung.

**K8 — Dependensi (Fase 7).**

- Hapus `laravel/sail` dan `laravel/chisel`.
- **Pertahankan** `laravel/pao`: ia auto-register service provider dan Pest plugin,
  serta memformat output console saat agent terdeteksi. Bukan sekadar library pasif.
- Hapus `pnpm-lock.yaml` dan `pnpm-workspace.yaml`.
- Hapus `.codex/config.toml` (starter artifact berisi path absolut mesin).
- Jangan mengubah `package-lock.json` selain hasil `npm install` yang diperlukan.

**K9 — View counter (Fase 5).**

- Ganti `Cache::has()` + `Cache::put()` dengan `Cache::add()` yang atomik.
- Session key per artikel boleh dipertahankan; jika diubah, harus ada test yang
  membuktikan perilaku "sekali per sesi" tidak berubah.

### 2.3 Koreksi terhadap audit

**Koreksi 1 — Mekanisme takeover installer.**

Audit awal menyebut installer bisa "menimpa database yang sudah ada". Itu **tidak benar**:
`DatabaseReadinessChecker` menolak database target yang berisi tabel
(`app/Services/Installation/DatabaseReadinessChecker.php:32-81`).

Mekanisme yang benar:

1. Wizard menerima konfigurasi database **lain** dari langkah 3
   (`InstallationWizardController::testDatabase`, baris 115-146).
2. `InstallationRunner` mengganti koneksi ke database kosong itu
   (`app/Services/Installation/InstallationRunner.php:73`), menjalankan migrasi, dan
   membuat `super_admin` di sana.
3. Di akhir, `InstallationRunner` menulis `.env` dengan `DB_*`, `MAIL_*`, `APP_URL`, dan
   `APP_ENV` dari sesi (`InstallationRunner.php:135-184`).
4. Situs asli kini menunjuk ke database attacker; attacker punya `super_admin` di sana.

Karena itu guard K1 harus memeriksa **database aplikasi yang sedang dikonfigurasi**,
bukan database yang diposting wizard. Ini juga menutup skenario file status hilang.

**Koreksi 2 — D-90 sudah menyimpang dari kode.**

`docs/DECISIONS.md` D-90 menyatakan "Wizard tidak menulis `.env`", tetapi
`InstallationRunner` menulis `.env` melalui `EnvironmentWriter`. Fase 1 harus
menyelaraskan dokumen dan kode (default: pertahankan kode, perbarui D-90).

**Koreksi 3 — Retensi audit bukan bug.**

`config/audit.php:391` memiliki `retention_months => 12`, tetapi `docs/audit-log.md:115`
menyatakan tidak ada auto-purge. Ini keputusan/limitasi yang didokumentasikan, bukan
temuan bug. Yang tetap menjadi temuan adalah `actorOptions()` yang memindai seluruh tabel
setiap halaman dibuka (Fase 3).

**Koreksi 4 — `expires_at` dokumen bukan bug.**

Dokumen kedaluwarsa tetap tampil; itu keputusan D-66 (PRD 9.x). Jangan mengubah
`Document::scopePublished()`.

---

## 3. Kondisi Codebase Saat Ini

### 3.1 Baseline repository

| Item                    | Nilai                                                                   |
| ----------------------- | ----------------------------------------------------------------------- |
| Branch                  | `refactor/refactor-code`                                                |
| Working tree saat audit | Bersih                                                                  |
| Stack                   | Laravel 13, Inertia v3, React 19, Vite 8, Tailwind v4, PHP 8.3, MySQL 8 |
| Cache default           | `CACHE_STORE=database` (`.env` dan `.env.example`)                      |
| Session default         | `SESSION_DRIVER=database`                                               |
| Queue default           | `QUEUE_CONNECTION=database`                                             |
| Test suite              | Pest, Vitest (`tests/js/**`), Playwright (`tests/e2e/**`)               |
| Gate lokal              | pre-commit → lint-staged + `composer ci:check`                          |

**Baseline belum dijalankan ulang.** Fase 0 wajib menjalankan `composer ci:check` dan
`npm run e2e` untuk mencatat kondisi awal.

### 3.2 20 temuan dan bukti

| #   | Temuan                                                    | File utama                                                                                                                                                                                                                                                       | Bukti / kasus konkret                                                                                | Prioritas | Fase |
| --- | --------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- | --------- | ---- |
| 1   | Installer bisa diarahkan ke DB kosong lalu menulis `.env` | `InstallationRunner.php:52-90,135-184`, `InstallationWizardController.php:115-146`, `.env.example:55`                                                                                                                                                            | Status file hilang + `INSTALLER_ENABLED=true` + DB kosong milik attacker → `.env` situs ditimpa      | Must      | 1    |
| 2   | Tanggal rusak → 500, parser `toUtc` 4 salinan             | `PostValidationRules.php:133,140-148`, `EventValidationRules.php:121-136`, `StoreScheduleNoticeRequest.php:52-53,146-154`, `StoreSpecialMassRequest.php:37-38,93-101`                                                                                            | `published_at=garbage` / `start_at=2026-13-01` → `InvalidFormatException` → 500                      | Must      | 2    |
| 3   | Setting dibaca dari cache DB 21–39 kali per request       | `SiteSettingsService.php:32-47`, `HandleInertiaRequests.php:150-167,182-205,275-325`, `app.blade.php:57,124-146`                                                                                                                                                 | Setiap `get()` = satu `SELECT` ke tabel `cache`; beranda publik ±39 query                            | Must      | 3    |
| 4   | `/agenda?bulan=2026-13` → 500                             | `PublicEventController.php:208-220`                                                                                                                                                                                                                              | Regex lolos, `Carbon::parse('2026-13-01')` melempar; test hanya menguji `bukan-bulan`                | Must      | 2    |
| 5   | Galeri memuat semua foto semua album                      | `PublicGalleryController.php:34-47`                                                                                                                                                                                                                              | 12 album × 200 foto + media + varian dimuat hanya untuk `photos->first()`                            | Should    | 3    |
| 6   | Hapus media meninggalkan gambar rusak                     | `MediaController.php:193-241`, migration `2026_10_11_082807:118,182`, `PublicPostResource.php:44`                                                                                                                                                                | `media_id` nullOnDelete, tetapi `featured_image`/`poster_path` tetap menunjuk file yang dihapus      | Should    | 4    |
| 7   | Filter aktor audit memindai seluruh tabel                 | `AuditLogController.php:155-162`                                                                                                                                                                                                                                 | `DISTINCT actor_* ORDER BY actor_name LIMIT 200` tanpa index pendukung; audit log tidak dipangkas    | Should    | 3    |
| 8   | `<p></p>` tersimpan di luar Komunitas                     | `ProfilValidationRules.php:42-55`, `ServiceValidationRules.php:286-299`, `CommunityValidationRules.php:236-271`                                                                                                                                                  | Tiptap mengirim `<p></p>`; halaman profil merender heading kosong                                    | Should    | 4    |
| 9   | Tiga generator slug                                       | `SavePostAction.php:115-145`, `SaveEventAction.php:92-116`, `DuplicateEventAction.php:86-98`, `GeneratesUniqueSlug.php:34-65`                                                                                                                                    | `DuplicateEventAction` tanpa batas loop dan parameter `$sourceKey` tidak dipakai                     | Should    | 5    |
| 10  | Enam modul kategori salinan dekat                         | `app/Http/Controllers/MasterData/*CategoryController.php` (843 baris), `app/Policies/*CategoryPolicy.php` (244), `master-data/pages/*/index.tsx` (1.326), `*-category-form.tsx` (535)                                                                            | Setiap field baru harus diubah di 6 controller + 6 halaman + 6 form + 6 policy                       | Should    | 6    |
| 11  | Sepuluh trait `*FormMeta` bentuk sama                     | `app/Concerns/*FormMeta.php` (415 baris)                                                                                                                                                                                                                         | Perbedaan utama hanya nama file bahasa dan sumber pilihan                                            | Should    | 6    |
| 12  | Jumlah foto galeri tidak konsisten                        | `PublicGalleryController.php:36` vs `:66`                                                                                                                                                                                                                        | Kartu `withCount('photos')` (semua), detail `loadCount(ready)` (siap)                                | Should    | 4    |
| 13  | Kartu "Layanan Lainnya" tanpa thumbnail                   | `PublicServiceController.php:52-56`, `PublicServiceCardResource.php:29`                                                                                                                                                                                          | `otherServices` tidak eager-load `thumbnailMedia.variants`; resource memakai `whenLoaded`            | Nice      | 4    |
| 14  | Cek disk backup tidak memperhitungkan ukuran              | `BackupCreator.php:243-256,306-338,346-428`                                                                                                                                                                                                                      | Full backup menyalin semua file ke temp lalu zip; sisa 2 GB untuk media 3 GB → gagal di tengah       | Should    | 4    |
| 15  | Dead code                                                 | `BackupStorage.php:41,112,121`; `AuditEvent.php:90,99`; `Media.php:137`; `ImageProcessor.php:114`; `BackupRetention.php:59-77`; `Backup.php:142`; `parish-cta.tsx`; `PublicPostController.php:36`; `PublicEventController.php:37`; `DuplicateEventAction.php:86` | Tidak ada pemanggil di produksi; `BackupRetention::eligible()` menduplikasi aturan `queryEligible()` | Nice      | 5    |
| 16  | `largestVariantName()` duplikat                           | `SavePostAction.php:211-230`, `SaveEventAction.php:145-164`                                                                                                                                                                                                      | 20 baris identik di dua action                                                                       | Nice      | 5    |
| 17  | Helper filter kategori publik duplikat                    | `PublicPostController.php:242-262`, `PublicEventController.php:178-200`, `PublicCommunityController.php:92-111`                                                                                                                                                  | `filteredCategory()` dan `categories()` ditulis ulang per modul                                      | Nice      | 5    |
| 18  | View counter tidak atomik, session tumbuh                 | `RecordPostViewAction.php:68-83`, `PublicPostController.php:111-119`                                                                                                                                                                                             | `Cache::has` + `put` bisa menghitung dua kali; satu key session per artikel                          | Nice      | 5    |
| 19  | Bulan admin bergeser diam-diam                            | `EventController.php:248-259`                                                                                                                                                                                                                                    | `createFromFormat('Y-m-d','2026-13-01')` → `2027-01-01`, bukan error                                 | Nice      | 2    |
| 20  | Dependensi & lockfile ganda                               | `composer.json:14,27,29`, `pnpm-lock.yaml`, `pnpm-workspace.yaml`, `docs/DEVELOPMENT.md:19`                                                                                                                                                                      | `sail` tanpa Docker, `chisel` tidak dipakai; `pao` dipakai untuk output agent; dua lockfile JS       | Nice      | 7    |

### 3.3 Konvensi yang mengikat implementasi

| Konvensi   | Aturan                                                                         |
| ---------- | ------------------------------------------------------------------------------ |
| Routing    | Semua URL dari `@/routes` / `@/actions`; jangan hard-code                      |
| Inertia    | Tidak ada React Router; props plain array untuk list non-paginated             |
| Otorisasi  | Backend selalu memeriksa; FormRequest `authorize()` untuk route dengan request |
| Test       | Pest + `RefreshDatabase` otomatis; `seedRolesAndPermissions()` bila butuh role |
| Vitest     | `tests/js/**`, globals disabled, happy-dom                                     |
| Playwright | `workers: 1`, `APP_DEBUG=false`, jalankan `app:e2e:prepare` dulu               |
| PHPStan    | Level 7; jangan menambah `@phpstan-ignore` tanpa alasan                        |
| Pint       | `vendor/bin/pint --dirty --format agent`                                       |
| Generated  | `php artisan wayfinder:generate --with-form`                                   |
| Commit     | Conventional Commits, subject ≤100 karakter                                    |

---

## 4. Arsitektur Target dan Prinsip Refactoring

Tidak ada perubahan arsitektur besar. Targetnya adalah **mengurangi duplikasi,
memperbaiki perilaku, dan menghapus pemborosan**, bukan menambah layer.

| Prinsip                    | Penerapan                                                                                  |
| -------------------------- | ------------------------------------------------------------------------------------------ |
| Satu sumber kebenaran      | Konversi waktu di satu trait; setting di satu service; slug di satu trait                  |
| Query benar dulu           | Perbaiki query/eager load di backend, bukan menambal di React                              |
| Hapus, bukan tambah        | Dead code dihapus; helper baru hanya jika menggantikan ≥2 salinan                          |
| Perubahan kecil            | Satu fase = satu PR; bug fix dipisah dari deduplikasi                                      |
| Test di level yang tepat   | Bug query → Pest; format/serialisasi → Vitest; alur UI → Playwright                        |
| Jangan sentuh yang bekerja | Auth, permission, backup/restore (selain cek disk), dan modul yang tidak terkait dibiarkan |

---

## 5. Rencana per Fase

Setiap fase memakai format yang sama:

- **Menutup temuan**
- **Target**
- **File**
- **Langkah**
- **Test wajib**
- **Verifikasi**
- **Risiko**
- **Definition of Done**
- **Estimasi**

---

### Fase 0 — Baseline dan guardrail

**Menutup temuan:** — (persiapan).

**Target:** Diketahui kondisi awal yang benar-benar hijau, sehingga setiap regresi setelah
refactor bisa dibedakan dari kegagalan lama.

**File:** tidak ada perubahan kode.

**Langkah:**

1. Pastikan MySQL 8 berjalan dan database `website_paroki_hspmtb_test` ada.
2. Jalankan `composer ci:check`; catat hasil, waktu, dan commit.
3. Jalankan `npm run e2e` sekali; catat spec yang lulus/gagal.
4. Opsional: catat jumlah query halaman utama dan halaman admin dengan `DB::listen`
   sementara di tinker. Jangan commit kode pengukuran.
5. Pastikan working tree bersih dan branch siap.

**Test wajib:** tidak ada test baru; ini baseline.

**Verifikasi:**

```bash
composer ci:check
npm run e2e
git status --short --branch
```

**Risiko:** rendah. Jika baseline sudah merah, perbaiki dulu sebelum refactor.

**Definition of Done:** ada catatan baseline (tanggal, commit, hasil gate, hasil E2E).

**Estimasi:** S (0,5 hari).

---

### Fase 1 — Keamanan installer

**Menutup temuan:** #1, drift D-90.

**Target:**

- Installer tidak bisa dipakai pada instalasi yang database aplikasinya sudah berisi
  tabel, walaupun wizard diarahkan ke database kosong.
- `.env.example` tidak lagi mengaktifkan installer secara default.
- Keputusan penulisan `.env` konsisten dengan dokumentasi.

**File:**

| Aksi     | File                                                                                           |
| -------- | ---------------------------------------------------------------------------------------------- |
| Ubah     | `.env.example:55`                                                                              |
| Ubah     | `app/Services/Installation/InstallationRunner.php:52-90` (guard sebelum `applyDatabaseConfig`) |
| Ubah     | `docs/DECISIONS.md` D-90                                                                       |
| Ubah     | `tests/Feature/Installation/InstallerLockTest.php`                                             |
| Ubah     | `tests/Feature/Installation/InstallationRunTest.php` (bila perlu)                              |
| Opsional | `app/Services/Installation/EnvironmentWriter.php` (hanya jika memilih Opsi A)                  |
| Opsional | `app/Http/Middleware/RedirectToInstaller.php` (bila ingin fail-closed saat status hilang)      |

**Langkah:**

1. Ubah `.env.example` menjadi `INSTALLER_ENABLED=false`; pertahankan komentar yang sudah
   ada. Jangan mengubah `.env` lokal.
2. Di `InstallationRunner::run()`, setelah cek status `PENDING` dan sebelum
   `applyDatabaseConfig($database)`, panggil `$this->readiness->check()` **tanpa argumen**
   (memeriksa koneksi aplikasi yang sedang aktif).
   - Jika hasil `DATABASE_NOT_FRESH` → `throw new InstallationFailure(InstallationError::DatabaseNotFresh)`.
   - Jika hasil `DATABASE_UNREACHABLE` → `throw new InstallationFailure(InstallationError::DatabaseUnreachable)`.
   - Tindak lanjut review 2026-11-02 memilih fail-closed untuk kedua kasus.
3. Pastikan guard berjalan sebelum `state->begin()` atau sesudahnya; jika sebelum, catch
   tetap menulis status `failed` (perilaku `InstallationState::fail()` sudah benar).
4. Putuskan penulisan `.env`:
   - **Opsi B (default):** pertahankan `save_environment` + `EnvironmentWriter`; perbarui
     D-90 agar menyatakan wizard menulis kunci `.env` yang dipilih, dengan guard di atas.
   - **Opsi A (ditunda):** hapus `save_environment` dan `EnvironmentWriter`; operator
     menyiapkan `.env` sendiri. Ini perubahan UX besar dan perlu persetujuan terpisah.
5. Tambahkan test:
   - Instalasi ditolak `DATABASE_NOT_FRESH` ketika database aplikasi sudah berisi tabel,
     meskipun database yang diposting kosong.
   - `EnvironmentWriter` tidak dipanggil saat ditolak (gunakan spy/fake).
   - Instalasi fresh tetap berhasil (test lama dengan fake readiness).
6. Perbarui D-90 dan, bila perlu, D-93/D-96 agar tidak bertentangan.

**Test wajib:**

| File                                                 | Kasus                                                              |
| ---------------------------------------------------- | ------------------------------------------------------------------ |
| `tests/Feature/Installation/InstallerLockTest.php`   | Database aplikasi berisi tabel + database posting kosong → ditolak |
| `tests/Feature/Installation/InstallerLockTest.php`   | `.env` tidak ditulis saat ditolak                                  |
| `tests/Feature/Installation/InstallationRunTest.php` | Instalasi fresh tetap berhasil                                     |
| `tests/Feature/Installation/InstallerAccessTest.php` | Redirect/akses installer tidak berubah                             |

**Verifikasi:**

```bash
php artisan test tests/Feature/Installation
php artisan test tests/Unit/Installation
composer ci:check
```

**Risiko:**

- **False positive:** guard memblokir instalasi sah jika database aplikasi berisi tabel
  tetapi operator memang ingin memakainya. Itu perilaku yang diinginkan (D-93).
- **Test fake readiness:** `InstallationRunTest` mengganti `DatabaseReadinessChecker`
  dengan fake. Test guard baru harus memakai checker asli atau fake yang membedakan
  panggilan tanpa argumen vs dengan argumen.
- **Status terminal:** instalasi yang ditolak menjadi `failed`; operator harus menghapus
  file status untuk mengulang (sesuai D-93). Dokumentasikan di pesan error.
- **Redirect:** jika status file hilang dan installer tetap aktif, `RedirectToInstaller`
  masih mengalihkan semua trafik ke `/install` (gangguan ketersediaan). Guard mencegah
  takeover, bukan redirect. Perbaikan redirect dapat menjadi PR terpisah.

**Definition of Done:** guard dan `.env.example` selesai; test membuktikan takeover
tertutup; D-90 tidak lagi bertentangan; gate hijau.

**Estimasi:** M (0,5–1 hari).

---

### Fase 2 — Batas input dan tanggal

**Menutup temuan:** #2, #4, #19.

**Target:**

- Tidak ada 500 dari input tanggal/bulan yang tidak valid.
- Hanya ada satu implementasi konversi waktu lokal → UTC.
- Filter bulan publik dan admin berperilaku sama terhadap input rusak.

**File:**

| Aksi | File                                                                          |
| ---- | ----------------------------------------------------------------------------- |
| Buat | `app/Concerns/ConvertsLocalDateTime.php`                                      |
| Ubah | `app/Concerns/PostValidationRules.php:133,140-148`                            |
| Ubah | `app/Concerns/EventValidationRules.php:121-136`                               |
| Ubah | `app/Http/Requests/MassSchedule/StoreScheduleNoticeRequest.php:52-53,146-154` |
| Ubah | `app/Http/Requests/MassSchedule/StoreSpecialMassRequest.php:37-38,93-101`     |
| Ubah | `app/Http/Controllers/Agenda/PublicEventController.php:208-220`               |
| Ubah | `app/Http/Controllers/Agenda/EventController.php:248-259`                     |
| Ubah | `tests/Feature/News/PostTest.php`                                             |
| Ubah | `tests/Feature/Agenda/EventTest.php`                                          |
| Ubah | `tests/Feature/Agenda/PublicEventPageTest.php`                                |
| Ubah | `tests/Feature/MassSchedule/ScheduleNoticeTest.php`                           |
| Ubah | `tests/Feature/MassSchedule/SpecialMassTest.php`                              |

**Langkah:**

1. Buat trait `ConvertsLocalDateTime` dengan satu method `toUtc(mixed $value): mixed`:
   - string kosong / `null` → `null`;
   - string valid → `Carbon::parse($value, config('app.display_timezone'))->utc()`;
   - `InvalidFormatException` → kembalikan nilai asli.
2. Ganti empat salinan `toUtc` dengan trait; hapus method lokal.
3. Perbaiki `PublicEventController::filteredMonth()`:
   - parse `YYYY-MM-01`;
   - jika `format('Y-m') !== $value`, kembalikan bulan berjalan.
4. Perbaiki `EventController::monthFilter()`:
   - parse dengan format yang sama;
   - jika `format('Y-m') !== $value`, kembalikan `null` (tanpa filter).
5. Tambahkan test regresi:
   - `published_at=garbage` pada store/update artikel → 422.
   - `start_at=garbage` / `end_at=garbage` pada agenda → 422.
   - `starts_at=garbage` / `ends_at=garbage` pada notice → 422.
   - `start_at=garbage` / `end_at=garbage` pada misa khusus → 422.
   - `/agenda?bulan=2026-13` → 200, jatuh ke bulan berjalan.
   - `/admin/agenda?bulan=2026-13` → 200, bukan Januari 2027.
   - Tanggal valid tetap dikonversi ke UTC dengan benar (test lama).

**Test wajib:**

| File                      | Kasus                                                             |
| ------------------------- | ----------------------------------------------------------------- |
| `PostTest.php`            | Tanggal publikasi tidak valid → 422, bukan 500                    |
| `EventTest.php`           | Tanggal mulai/selesai tidak valid → 422                           |
| `EventTest.php`           | Filter bulan admin tidak valid tidak bergeser ke tahun berikutnya |
| `PublicEventPageTest.php` | `bulan=2026-13` → 200 dan bulan berjalan                          |
| `ScheduleNoticeTest.php`  | Waktu mulai/selesai tidak valid → 422                             |
| `SpecialMassTest.php`     | Waktu mulai/selesai tidak valid → 422                             |

**Verifikasi:**

```bash
php artisan test tests/Feature/News/PostTest.php
php artisan test tests/Feature/Agenda
php artisan test tests/Feature/MassSchedule
composer ci:check
```

**Risiko:**

- **Perbandingan `after_or_equal`:** jika satu field valid dan satu tidak, pesan error
  bisa menumpuk. Uji dua field tidak valid sekaligus.
- **Format sah non-standar:** `2026-11-02T10:00` harus tetap diterima.
- **Zona waktu:** `Asia/Pontianak` tidak punya DST, tetapi tetap uji konversi UTC.
- **AuditLogIndexRequest** sudah memakai `date_format` lebih dulu; jangan diubah.

**Definition of Done:** semua test di atas lulus; tidak ada `Carbon::parse` mentah di
`prepareForValidation()`; tidak ada 500 dari input tanggal.

**Estimasi:** S (0,5 hari).

---

### Fase 3 — Performa baca

**Menutup temuan:** #3, #5, #7.

**Target:**

- Satu request hanya membaca cache setting sekali (bukan 20–40 kali).
- Halaman galeri hanya memuat satu foto per album.
- Filter aktor audit tidak memindai seluruh tabel setiap halaman dibuka.

**File:**

| Aksi     | File                                                            |
| -------- | --------------------------------------------------------------- |
| Ubah     | `app/Services/SiteSettingsService.php:32-47`                    |
| Ubah     | `app/Providers/AppServiceProvider.php` (binding `scoped`)       |
| Ubah     | `app/Http/Controllers/Galeri/PublicGalleryController.php:34-47` |
| Ubah     | `app/Http/Controllers/Audit/AuditLogController.php:155-162`     |
| Buat     | `tests/Unit/SiteSettingsServiceTest.php`                        |
| Ubah     | `tests/Feature/Galeri/PublicGalleryDirectoryTest.php`           |
| Ubah     | `tests/Feature/Audit/AuditLogFilteringTest.php`                 |
| Opsional | `tests/Feature/Public/PublicHomepageTest.php`                   |

**Langkah:**

1. `SiteSettingsService`:
   - tambah `private ?array $values = null`;
   - `all()` → `return $this->values ??= Cache::remember(...)`;
   - `forget()` → `$this->values = null; Cache::forget(...)`.
2. Daftarkan `$this->app->scoped(SiteSettingsService::class)` di `AppServiceProvider::register()`.
   Jangan memakai `singleton` (risiko setting basi di queue worker).
3. Galeri:
   - ganti eager load `photos` menjadi satu foto siap per album, mis.
     `->with(['photos' => fn ($q) => $q->ready()->with('media.variants')->limit(1)])`;
   - verifikasi `HasOneOrMany::limit()` memakai `groupLimit` per parent;
   - jika tidak, tambahkan relasi `hasOne` `firstReadyPhoto` dan pakai itu.
4. Audit:
   - ganti `actorOptions()` agar membaca dari `users` (`id`, `name`) + entri tetap
     `system`/`anonymous`;
   - jika aktor yang sudah dihapus harus tetap muncul, cache hasil distinct 10 menit
     sebagai alternatif.
5. Tambahkan test:
   - unit test service: 20 `get()` → `Cache::remember` sekali; `forget()` membaca ulang.
   - galeri: 2 album × 5 foto; hitung `GalleryPhoto` yang di-retrieve via
     `Event::listen('eloquent.retrieved: ...')`; harus ≤ 2.
   - audit: opsi aktor tetap memuat pengguna; filter aktor lama tetap lulus.

**Test wajib:**

| File                                            | Kasus                                                                |
| ----------------------------------------------- | -------------------------------------------------------------------- |
| `tests/Unit/SiteSettingsServiceTest.php` (baru) | Memoization + reset `forget()`                                       |
| `PublicGalleryDirectoryTest.php`                | Hanya satu foto per album dimuat                                     |
| `PublicGalleryDirectoryTest.php`                | `photo_count` memakai filter `ready()` (bila digabung dengan Fase 4) |
| `AuditLogFilteringTest.php`                     | Filter aktor tetap berfungsi                                         |
| `PublicHomepageTest.php`                        | Beranda tetap memuat setting yang benar                              |

**Verifikasi:**

```bash
php artisan test tests/Unit/SiteSettingsServiceTest.php
php artisan test tests/Feature/Galeri/PublicGalleryDirectoryTest.php
php artisan test tests/Feature/Audit
php artisan test tests/Feature/Public/PublicHomepageTest.php
composer ci:check
```

**Risiko:**

- **`scoped` vs `singleton`:** salah pilih membuat setting basi di worker. Uji dua job
  berurutan yang mengubah setting.
- **Per-parent limit:** query window function; uji di MySQL 8, bukan SQLite.
- **Actor options:** aktor yang sudah dihapus tidak muncul di dropdown. Ini keputusan
  yang dapat diterima; jika tidak, pakai cache distinct.
- **Test cache:** test suite memakai `CACHE_STORE=array`; gunakan `Cache::spy()` atau
  fake repository, bukan query count.

**Definition of Done:** pembacaan cache setting per request turun ke 1–2; galeri tidak
memuat seluruh foto; test retrieval/unit membuktikannya.

**Estimasi:** M (1 hari).

---

### Fase 4 — Integritas dan konsistensi data

Fase ini dipecah menjadi empat PR kecil. Kerjakan berurutan.

**Menutup temuan:** #6, #8, #12, #13, #14.

**Target:**

- Media yang masih dipakai tidak bisa dihapus.
- Rich text kosong tersimpan `null`.
- Jumlah foto di kartu dan detail konsisten.
- Kartu "Layanan Lainnya" menampilkan thumbnail.
- Backup menolak sebelum disk penuh.

#### 4a. Media delete guard

**File:**

| Aksi | File                                                     |
| ---- | -------------------------------------------------------- |
| Ubah | `app/Models/Media.php` (method referensi)                |
| Ubah | `app/Http/Controllers/Media/MediaController.php:193-241` |
| Ubah | `docs/DECISIONS.md` D-24                                 |
| Ubah | `tests/Feature/Media/MediaTest.php`                      |

**Langkah:**

1. Tambahkan `Media::isReferencedByContent(): bool` yang memeriksa:
   - `posts.media_id` dan `posts.featured_image`
   - `events.media_id` dan `events.poster_path`
   - `services.thumbnail_media_id`
   - `communities.logo_media_id`, `thumbnail_media_id`
   - `parish_profiles.logo_media_id`, `cover_media_id`, `map_media_id`
   - `pastors.photo_media_id`, `parish_board_members.photo_media_id`, `areas.photo_media_id`
   - `gallery_photos.media_id`
   - `site_settings` bertipe `media` (nilai = id sebagai string)
2. Di `destroy()`, tolak dengan pesan jelas bila direferensikan; jangan hapus row/file.
3. Test: media yang dipakai artikel → ditolak, row + file tetap ada; media tak terpakai
   → tetap terhapus.
4. Perbarui D-24.

**Test wajib:**

| File            | Kasus                                  |
| --------------- | -------------------------------------- |
| `MediaTest.php` | Media dipakai artikel → delete ditolak |
| `MediaTest.php` | Media dipakai event → delete ditolak   |
| `MediaTest.php` | Media dipakai galeri → delete ditolak  |
| `MediaTest.php` | Media dipakai setting → delete ditolak |
| `MediaTest.php` | Media tak terpakai → tetap terhapus    |

**Risiko:** daftar referensi mudah tertinggal saat ada kolom baru. Mitigasi: satu test
yang membuat referensi di setiap tabel. `site_settings` menyimpan id sebagai string.

#### 4b. Rich text kosong

**File:**

| Aksi     | File                                                   |
| -------- | ------------------------------------------------------ |
| Ubah     | `app/Services/HtmlSanitizer.php` (`cleanOrNull`)       |
| Ubah     | `app/Concerns/ProfilValidationRules.php:42-55`         |
| Ubah     | `app/Concerns/ServiceValidationRules.php:286-299`      |
| Ubah     | `app/Concerns/CommunityValidationRules.php:236-271`    |
| Opsional | `app/Http/Requests/Settings/UpdateSettingsRequest.php` |
| Ubah     | `tests/Feature/Profil/ParishProfileTest.php`           |
| Ubah     | `tests/Feature/Pelayanan/ServiceTest.php`              |
| Ubah     | `tests/Feature/Komunitas/CommunityTest.php`            |

**Langkah:**

1. Tambahkan `HtmlSanitizer::cleanOrNull(?string): ?string` yang memakai
   `isBlankMarkup` (pindahkan dari `CommunityValidationRules`).
2. Ganti `sanitizeHtmlFields` di tiga concern agar memanggil helper tersebut.
3. Test: `<p></p>`, `<p><br></p>`, whitespace → `null`; `<p><img ...></p>` tetap.
4. Opsional: command cleanup data lama dengan dry-run; jangan UPDATE massal tanpa
   persetujuan.

**Test wajib:** tiga test di atas, masing-masing untuk field yang dikosongkan dan untuk
gambar yang tersisa.

**Risiko:** perubahan hanya berlaku untuk tulisan baru; data lama tetap. Jangan
menjanjikan pembersihan retroaktif.

#### 4c. Konsistensi galeri dan layanan

**File:**

| Aksi | File                                                               |
| ---- | ------------------------------------------------------------------ |
| Ubah | `app/Http/Controllers/Galeri/PublicGalleryController.php:36`       |
| Ubah | `app/Http/Controllers/Pelayanan/PublicServiceController.php:52-56` |
| Ubah | `tests/Feature/Galeri/PublicGalleryDirectoryTest.php`              |
| Ubah | `tests/Feature/Pelayanan/PublicServicePageTest.php`                |

**Langkah:**

1. `withCount(['photos' => fn ($q) => $q->ready()])` di direktori galeri.
2. Tambahkan `->with('thumbnailMedia.variants')` pada `otherServices`.
3. Test: album dengan 2 foto siap + 1 gagal → `photo_count` 2 di kartu dan detail;
   `otherServices.0.thumbnail` tidak null.

**Test wajib:** dua test di atas.

**Risiko:** rendah. Pastikan `ready()` tidak mengubah jumlah album yang tampil.

#### 4d. Ruang disk backup

**File:**

| Aksi      | File                                                            |
| --------- | --------------------------------------------------------------- |
| Ubah      | `app/Services/Backup/BackupCreator.php:243-256,306-338,346-428` |
| Buat/Ubah | test unit kalkulasi (file baru di `tests/Unit/Backup/`)         |
| Opsional  | `tests/Feature/Backup/BackupCreationTest.php`                   |

**Langkah:**

1. Hitung total ukuran sumber (file yang akan dibackup) sebelum mulai.
2. `assertDiskSpace()` membandingkan `freeBytes` dengan
   `2 × (sourceBytes + dumpBytes) + threshold`; `dumpBytes` bernilai `0` pada cek awal.
3. Ekstrak perhitungan murni
   `requiredBytes(int $sourceBytes, int $thresholdBytes, int $dumpBytes = 0): int`.
4. Backup database memakai threshold pada cek awal, lalu ukuran dump aktual setelah
   `mysqldump`.
5. Test unit formula + feature test deterministik dengan injeksi free space, bukan
   threshold sangat besar. Tindak lanjut review: lihat
   `plan-refactoring-review-fixes-2026-11.md` §1B.

**Test wajib:**

| File                                               | Kasus                                                        |
| -------------------------------------------------- | ------------------------------------------------------------ |
| `tests/Unit/Backup/BackupDiskSpaceTest.php` (baru) | `requiredBytes` menghitung 2× sumber + threshold             |
| `BackupCreationTest.php`                           | Backup ditolak sebelum artefak dibuat saat ruang tidak cukup |

**Risiko:** `BackupStorage` adalah `final`; jangan memaksakan mock. Uji formula murni dan
picu `disk_full` lewat threshold yang sangat besar.

**Verifikasi Fase 4:**

```bash
php artisan test tests/Feature/Media
php artisan test tests/Feature/Profil
php artisan test tests/Feature/Pelayanan
php artisan test tests/Feature/Komunitas
php artisan test tests/Feature/Galeri
php artisan test tests/Feature/Backup
php artisan test tests/Unit/Backup
composer ci:check
npm run e2e -- tests/e2e/editor-media.spec.ts
```

**Definition of Done:** semua test di atas lulus; tidak ada jalur delete media yang
meninggalkan referensi rusak; halaman publik tidak menampilkan heading kosong setelah
field dikosongkan.

**Estimasi:** M–L (1,5–2 hari).

---

### Fase 5 — Deduplikasi mekanis dan dead code

**Menutup temuan:** #9, #15, #16, #17, #18.

**Target:**

- Satu generator slug untuk semua model.
- Satu pemilih varian gambar.
- Satu helper filter kategori publik.
- Penghitung view atomik.
- Tidak ada method/komponen tanpa pemanggil.

**File:**

| Aksi  | File                                                                             |
| ----- | -------------------------------------------------------------------------------- |
| Ubah  | `app/Actions/News/SavePostAction.php:115-145,211-230`                            |
| Ubah  | `app/Actions/Agenda/SaveEventAction.php:92-116,145-164`                          |
| Ubah  | `app/Actions/Agenda/DuplicateEventAction.php:86-98`                              |
| Ubah  | `app/Concerns/GeneratesUniqueSlug.php` (bila perlu)                              |
| Ubah  | `app/Models/Media.php` (largest variant path)                                    |
| Ubah  | `app/Http/Controllers/News/PublicPostController.php:242-262`                     |
| Ubah  | `app/Http/Controllers/Agenda/PublicEventController.php:178-200`                  |
| Ubah  | `app/Http/Controllers/Komunitas/PublicCommunityController.php:92-111`            |
| Ubah  | `app/Actions/News/RecordPostViewAction.php:68-83`                                |
| Hapus | `app/Services/Backup/BackupStorage.php:41,112,121`                               |
| Hapus | `app/Models/AuditEvent.php:90,99`                                                |
| Hapus | `app/Models/Media.php:137`                                                       |
| Hapus | `app/Services/ImageProcessor.php:114`                                            |
| Hapus | `app/Services/Backup/BackupRetention.php:59-77`                                  |
| Hapus | `app/Models/Backup.php:142` (jika hanya dipakai `eligible`)                      |
| Hapus | `resources/js/components/parish-cta.tsx`                                          |
| Ubah  | `app/Http/Controllers/News/PublicPostController.php:36` (trait tidak dipakai)    |
| Ubah  | `app/Http/Controllers/Agenda/PublicEventController.php:37` (trait tidak dipakai) |
| Ubah  | `app/Actions/Agenda/DuplicateEventAction.php:86` (parameter tidak dipakai)       |
| Ubah  | `tests/Feature/News/PostTest.php`, `tests/Feature/Agenda/EventTest.php`          |
| Ubah  | `tests/Feature/News/ViewCountTest.php`                                           |
| Ubah  | `tests/Feature/Public/*` (filter kategori)                                       |

**Langkah:**

1. Slug:
   - `SavePostAction` dan `SaveEventAction` memakai `resolveUniqueSlug(..., withTrashed: true)`;
   - `DuplicateEventAction` memakai trait yang sama dengan basis `title-copy`;
   - hapus parameter `$sourceKey` yang tidak dipakai.
2. Varian gambar: pindahkan `largestVariantName()` ke `Media`
   (mis. `Media::largestVariantPath()`), panggil dari kedua action.
3. Filter kategori publik: **dibiarkan** sebagai accepted duplication; query tiap modul
   berbeda urutan/scope dan abstraksinya tidak sepadan.
4. View counter: ganti `Cache::has` + `Cache::put` dengan `Cache::add` (atomik).
5. Dead code: hapus setelah `rg` memastikan tidak ada referensi (termasuk test, config,
   string). `ParishCta` dihapus beserta blok test-nya.
6. Test: slug duplicate, varian, view counter.

**Test wajib:**

| File                                                                                    | Kasus                                            |
| --------------------------------------------------------------------------------------- | ------------------------------------------------ |
| `PostTest.php`                                                                          | Slug yang diketik tetap; slug kosong dibuat unik |
| `EventTest.php`                                                                         | Duplikat dua kali → `-copy`, `-copy-2`           |
| `EventTest.php`                                                                         | Tabrakan judul tidak menghasilkan loop           |
| `MediaTest.php` / `PostTest.php` / `EventTest.php`                                      | Varian terlebar dipilih                          |
| `PublicPostPageTest.php`, `PublicEventPageTest.php`, `PublicCommunityDirectoryTest.php` | Filter kategori tetap                            |
| `ViewCountTest.php`                                                                     | Dua panggilan IP/UA sama → satu increment        |

**Verifikasi:**

```bash
php artisan test tests/Feature/News
php artisan test tests/Feature/Agenda
php artisan test tests/Feature/Komunitas
php artisan test tests/Feature/Public
composer ci:check
```

**Risiko:**

- **Output slug harus identik** dengan perilaku lama. Jalankan test slug sebelum dan
  sesudah.
- **`Cache::add`:** pastikan logika "sudah dihitung" tidak terbalik.
- **`ParishCta`:** sudah dihapus atas keputusan pemilik produk; tidak ada risiko produk
  tersisa.
- **`BackupRetention::eligible()`:** hanya hapus jika benar-benar tidak dipakai; jangan
  menghapus `queryEligible()`.

**Definition of Done:** satu implementasi slug, satu pemilih varian, view counter atomik;
`ParishCta` dihapus; helper filter kategori dibiarkan; gate hijau.

**Estimasi:** M (1 hari).

---

### Fase 6 — Master data

> **Scope implementasi (2026-11-02):** hanya **6a + 6b**. 6c (controller helper),
> 6d (list page), dan 6e (form) ditunda.

**Menutup temuan:** #10, #11.

**Target:**

- Enam policy kategori memakai satu trait; sepuluh `*FormMeta` memakai satu helper.
- Tidak ada perubahan perilaku yang terlihat pengguna.
- Validation rules tetap per modul karena field-nya memang berbeda.

**File:**

| Aksi     | File                                                                                |
| -------- | ----------------------------------------------------------------------------------- |
| Buat     | `app/Policies/Concerns/ChecksModulePermission.php` (trait)                          |
| Ubah     | `app/Policies/{Community,Document,Event,Livestream,Post,Service}CategoryPolicy.php` |
| Buat     | `app/Concerns/FormMeta.php` atau helper serupa                                      |
| Ubah     | `app/Concerns/*FormMeta.php` (10 file)                                              |
| Buat     | `app/Http/Controllers/MasterData/Concerns/ManagesCategory.php` (trait)              |
| Ubah     | `app/Http/Controllers/MasterData/*CategoryController.php` (6 file)                  |
| Buat     | `resources/js/features/admin/master-data/components/category-list-page.tsx`         |
| Ubah     | `resources/js/features/admin/master-data/pages/*/index.tsx` (6 file)                |
| Opsional | `resources/js/features/admin/master-data/components/category-form.tsx`              |
| Ubah     | `tests/Feature/{Agenda,News,Document,Komunitas,Pelayanan}/*CategoryTest.php`        |
| Ubah     | `tests/js/features/admin/master-data/**`                                            |
| Ubah     | `tests/e2e/master-data.spec.ts`                                                     |

**Langkah 6a — FormMeta helper (PR 1, rendah):**

1. Buat satu helper `formMeta(string $langKey, array $choices = [])` yang mengembalikan
   `labels`, `hints`, `copy`, `empty`, dan pilihan.
2. Ganti 10 trait agar memanggil helper; pertahankan nama method publik yang dipakai
   controller.
3. Test: semua halaman form yang ada tetap memuat props yang sama.

**Langkah 6b — Policy kategori (PR 2, rendah):**

1. Buat trait `ChecksModulePermission` dengan `permissionPrefix()` abstrak.
2. Setiap policy kategori hanya mendefinisikan prefix dan type-hint model.
3. Test: `*AuthorizationTest` dan test policy yang ada.

**Langkah 6c — Controller kategori (PR 3, sedang):**

1. Ekstrak hanya bagian yang benar-benar sama: transaksi audit create/update/delete,
   guard "kategori masih dipakai", dan redirect/toast.
2. Jangan membuat base controller generik yang mengabstraksi payload/validation.
3. Test: seluruh `*CategoryTest` + E2E master-data.

**Langkah 6d — List page kategori (PR 4, sedang–tinggi):**

1. Buat `CategoryListPage` yang menerima kolom (`DataColumn[]`), route index/create/edit/
   destroy, filter, label, dan handler delete.
2. Ganti enam `index.tsx` menjadi pembungkus tipis.
3. Pertahankan perbedaan: `documents_count` vs `posts_count`, kolom status, filter
   tambahan.
4. Test: JS test komponen generik dengan dua konfigurasi; E2E semua halaman kategori.

**Langkah 6e — Form kategori (opsional, PR 5):**

1. Gabungkan hanya form yang benar-benar identik (agenda, komunitas, livestream,
   pelayanan); berita dan dokumen punya field berbeda.
2. Jika perbedaannya lebih besar dari manfaatnya, lewati dan catat alasannya.

**Test wajib:**

| File                                     | Kasus                                |
| ---------------------------------------- | ------------------------------------ |
| `EventCategoryTest.php`                  | CRUD, validasi, delete guard         |
| `PostCategoryTest.php`                   | CRUD, validasi, delete guard         |
| `DocumentCategoryTest.php`               | CRUD, status/deskripsi, delete guard |
| `CommunityCategoryTest.php`              | CRUD, validasi, delete guard         |
| `ServiceCategoryTest.php`                | CRUD, sort, delete guard             |
| `tests/js/features/admin/master-data/**` | List page generik                    |
| `tests/e2e/master-data.spec.ts`          | Semua halaman kategori               |

**Verifikasi:**

```bash
php artisan test tests/Feature/Agenda/EventCategoryTest.php
php artisan test tests/Feature/News/PostCategoryTest.php
php artisan test tests/Feature/Document/DocumentCategoryTest.php
php artisan test tests/Feature/Komunitas/CommunityCategoryTest.php
php artisan test tests/Feature/Pelayanan/ServiceCategoryTest.php
npm run test:unit -- tests/js/features/admin/master-data
npm run e2e:prepare && npx playwright test tests/e2e/master-data.spec.ts
composer ci:check
```

**Risiko:**

- **Regresi visual/logika paling besar.** Wajib karakterisasi test sebelum refactor.
- **Perbedaan kecil antar-modul mudah hilang** (dokumen punya `description`, layanan punya
  `sort_order`, agenda tidak punya `sort_order`).
- **Bentuk props** tidak boleh berubah; TypeScript menangkap sebagian, E2E sisanya.
- **Jangan gabungkan `*ValidationRules`**; field tiap modul berbeda.

**Definition of Done:** semua test kategori dan E2E master-data lulus; tidak ada
duplikasi policy/list page yang tersisa; perbedaan antar-modul tetap ada dan diuji.

**Estimasi:** L (3–5 hari).

---

### Fase 7 — Higiene dependensi dan verifikasi akhir

**Menutup temuan:** #20.

**Target:**

- Tidak ada dependensi/lockfile yang tidak dipakai.
- Aplikasi boot, ArchitectureTest, dan build Vite lulus.
- Dokumentasi keputusan diperbarui.

**File:**

| Aksi     | File                                                         |
| -------- | ------------------------------------------------------------ |
| Ubah     | `composer.json` (hapus `sail` dan `chisel`; `pao` tetap)      |
| Hapus    | `pnpm-lock.yaml`, `pnpm-workspace.yaml`                      |
| Hapus    | `.codex/config.toml`                                         |
| Ubah     | `docs/DEVELOPMENT.md:151` (Pao bukan penyedia `php artisan dev`) |
| Ubah     | `docs/DECISIONS.md` (catatan `sail`/pnpm)                    |
| Ubah     | `docs/roadmap/phase-01-project-foundation.md` (butir P01)    |

**Langkah:**

1. Hapus `laravel/sail` (require-dev) dan `laravel/chisel` (require) dengan
   `composer remove`. **Jangan** menghapus `laravel/pao`.
2. Hapus `pnpm-lock.yaml` (lokal, ignored) dan `pnpm-workspace.yaml` (tracked).
3. Hapus `.codex/config.toml` (tracked, path absolut mesin).
4. Jalankan `composer validate --strict`, `composer install`, dan `npm ci` di lingkungan
   bersih.
5. Perbarui `docs/DEVELOPMENT.md` (Pao bukan penyedia `php artisan dev`) dan
   `docs/DECISIONS.md`.
6. Verifikasi targeted: boot aplikasi, ArchitectureTest, dan build Vite.

**Test wajib:** tidak ada test baru; verifikasi targeted sesuai keputusan.

**Verifikasi:**

```bash
composer validate --strict
composer install
npm ci
php artisan about
php artisan test tests/Unit/ArchitectureTest.php --compact
npm run build
```

**Risiko:**

- **`laravel/chisel` mungkin dipakai tooling lokal** yang tidak terlihat di `rg`; cek
  `vendor/bin` dan dokumentasi sebelum menghapus.
- **`laravel/pao` jangan dihapus**: auto-register provider dan Pest plugin.
- **`composer remove`** bisa mengubah versi transitive; periksa `git diff` lockfile dan
  pertahankan hanya penghapusan paket yang dimaksud.

**Definition of Done:** `composer.json` bersih dari `sail`/`chisel`; `pao` tetap ada;
hanya satu lockfile JS; aplikasi boot, ArchitectureTest dan build lulus; dokumentasi
konsisten.

**Estimasi:** S (0,5 hari).

---

## 6. Urutan Eksekusi dan Dependency

```text
Fase 0 (baseline)
   │
   ├── Fase 1 (installer)  ── independen
   ├── Fase 2 (tanggal)    ── independen
   └── Fase 3 (performa)   ── independen
            │
            └── Fase 4 (integritas) ── independen, tetapi lebih mudah setelah Fase 2
                     │
                     └── Fase 5 (dedupe mekanis) ── independen
                              │
                              └── Fase 6 (master data) ── paling akhir
                                       │
                                       └── Fase 7 (dependensi + verifikasi)
```

Aturan:

- Fase 1, 2, dan 3 boleh dikerjakan paralel oleh dua orang, tetapi jangan satu PR.
- Fase 6 harus setelah Fase 5 selesai agar baseline stabil dan dead code tidak
  bertabrakan.
- Fase 7 selalu terakhir karena menghapus dependensi dan menjalankan verifikasi penuh.

---

## 7. Test Matrix

| Fase | Perintah utama                                                                                 | Test baru                                      |
| ---- | ---------------------------------------------------------------------------------------------- | ---------------------------------------------- |
| 0    | `composer ci:check`, `npm run e2e`                                                             | —                                              |
| 1    | `php artisan test tests/Feature/Installation`, `tests/Unit/Installation`                       | Guard installer, `.env` tidak ditulis          |
| 2    | `php artisan test tests/Feature/News`, `Agenda`, `MassSchedule`                                | Tanggal/bulan tidak valid                      |
| 3    | `php artisan test tests/Unit/SiteSettingsServiceTest.php`, `Galeri`, `Audit`                   | Memoization, retrieval count, actor options    |
| 4    | `php artisan test tests/Feature/Media`, `Profil`, `Pelayanan`, `Komunitas`, `Galeri`, `Backup` | Media guard, rich text, count, thumbnail, disk |
| 5    | `php artisan test tests/Feature/News`, `Agenda`, `Komunitas`, `Public`                         | Slug, varian, filter, view counter             |
| 6    | `php artisan test` kategori + `npm run test:unit` master-data + E2E                            | Policy, list page, form                        |
| 7    | `composer validate`, `composer install`, `npm ci`, `php artisan about`, `ArchitectureTest`, `npm run build` | —                                    |

Catatan:

- Test suite memakai `CACHE_STORE=array` dan `SESSION_DRIVER=array` di `phpunit.xml`.
  Jangan mengandalkan query count untuk menguji cache setting; gunakan `Cache::spy()`.
- E2E selalu dijalankan setelah `npm run e2e:prepare`.
- Jangan mengklaim lulus tanpa menempelkan output perintah.

---

## 8. Ringkasan File yang Terdampak

### 8.1 File baru

| File                                                                        | Fase |
| --------------------------------------------------------------------------- | ---- |
| `app/Concerns/ConvertsLocalDateTime.php`                                    | 2    |
| `app/Policies/Concerns/ChecksModulePermission.php`                          | 6    |
| `app/Concerns/BuildsFormMeta.php`                                           | 6    |
| `tests/Unit/SiteSettingsServiceTest.php`                                    | 3    |
| `tests/Unit/Backup/BackupDiskSpaceTest.php`                                 | 4    |

### 8.2 File diubah (utama)

| File                                                                                                                                | Fase |
| ----------------------------------------------------------------------------------------------------------------------------------- | ---- |
| `.env.example`                                                                                                                      | 1    |
| `app/Services/Installation/InstallationRunner.php`                                                                                  | 1    |
| `docs/DECISIONS.md`                                                                                                                 | 1, 4 |
| `app/Concerns/PostValidationRules.php`, `EventValidationRules.php`                                                                  | 2    |
| `app/Http/Requests/MassSchedule/StoreScheduleNoticeRequest.php`, `StoreSpecialMassRequest.php`                                      | 2    |
| `app/Http/Controllers/Agenda/PublicEventController.php`, `EventController.php`                                                      | 2    |
| `app/Services/SiteSettingsService.php`, `AppServiceProvider.php`                                                                    | 3    |
| `app/Http/Controllers/Galeri/PublicGalleryController.php`, `AuditLogController.php`                                                 | 3    |
| `app/Http/Controllers/Media/MediaController.php`, `app/Models/Media.php`                                                            | 4    |
| `app/Services/HtmlSanitizer.php`, `ProfilValidationRules.php`, `ServiceValidationRules.php`, `CommunityValidationRules.php`         | 4    |
| `app/Http/Controllers/Pelayanan/PublicServiceController.php`                                                                        | 4    |
| `app/Services/Backup/BackupCreator.php`                                                                                             | 4    |
| `app/Actions/News/SavePostAction.php`, `Agenda/SaveEventAction.php`, `Agenda/DuplicateEventAction.php`                              | 5    |
| `app/Actions/News/RecordPostViewAction.php`                                                                                         | 5    |
| `app/Http/Controllers/News/PublicPostController.php`, `Agenda/PublicEventController.php`, `Komunitas/PublicCommunityController.php` | 5    |
| `app/Policies/*CategoryPolicy.php`, `app/Concerns/*FormMeta.php`                                                                    | 6    |
| `composer.json`, `docs/DEVELOPMENT.md`                                                                                              | 7    |

### 8.3 File dihapus

| File                                     | Fase | Catatan                                                                                                                                                                                            |
| ---------------------------------------- | ---- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `pnpm-lock.yaml`, `pnpm-workspace.yaml`  | 7    | Proyek memakai npm                                                                                                                                                                                 |
| `.codex/config.toml`                     | 7    | Starter artifact berisi path absolut mesin                                                                                                                                                         |
| `resources/js/components/parish-cta.tsx` | 5    | Dihapus; blok test `ParishCta` di `public-primitives.test.tsx` ikut dihapus                                                                                                                         |
| Method dead code (bukan file)            | 5    | `BackupStorage::stagingPath/checksum` (`size` dipakai `VerifyBackupsAction`), `AuditEvent::scopeLatestFirst/scopeOccurredBetween`, `Media::isPending`, `ImageProcessor::measure`, `BackupRetention::eligible`, `Backup::isProtected` |

### 8.4 File generated (regenerasi, bukan edit manual)

- `resources/js/routes/**`, `resources/js/actions/**`, `resources/js/wayfinder/**`
  hanya jika ada perubahan route/controller name. Fase-fase di dokumen ini **tidak
  mengubah route**, jadi regenerasi tidak diharapkan. Jika terjadi, jalankan
  `php artisan wayfinder:generate --with-form` dan review diff.
- `public/build/**` tidak disentuh manual.

### 8.5 Tidak disentuh

- `database/migrations/**` (tidak ada skema baru).
- `app/Http/Controllers/Backup/*` selain `BackupCreator` (Fase 4d).
- `app/Services/Installation/*` selain `InstallationRunner` (Fase 1).
- Auth, Fortify, permission, role.
- `resources/js/components/ui/**` (kecuali komponen yang dihapus di Fase 5).
- Modul backup/restore selain cek disk.

---

## 9. Risk Register

| Risiko                                      | Fase  | Dampak | Mitigasi                                                    |
| ------------------------------------------- | ----- | ------ | ----------------------------------------------------------- |
| Guard installer memblokir instalasi sah     | 1     | Tinggi | Uji dengan DB kosong dan DB berisi; pesan error jelas; D-93 |
| `toUtc` mengubah perilaku tanggal valid     | 2     | Sedang | Test lama + test batas zona waktu                           |
| `scoped` vs `singleton` salah               | 3     | Sedang | Test dua job berurutan; review binding                      |
| Per-parent limit tidak didukung             | 3     | Sedang | Fallback relasi `hasOne`                                    |
| Daftar referensi media tertinggal           | 4     | Tinggi | Test referensi per tabel; review migration                  |
| Data lama `<p></p>` tetap                   | 4     | Rendah | Dokumentasikan; command cleanup opsional                    |
| `Cache::add` membalik logika                | 5     | Sedang | Test view counter                                           |
| Menghapus `ParishCta` yang masih dibutuhkan | 5     | Rendah | Sudah dihapus atas keputusan pemilik produk                 |
| Regresi master data                         | 6     | Tinggi | Karakterisasi test + E2E per PR                             |
| `chisel` dipakai tooling lokal              | 7     | Rendah | Cek `vendor/bin` + grep                                     |
| E2E tidak stabil                            | semua | Sedang | `workers: 1`, `app:e2e:prepare`, ulangi sekali              |

---

## 10. Verifikasi dan Acceptance

### 10.1 Definition of Done global

- [ ] `composer ci:check` hijau (Pint, PHPStan level 7, Pest, ESLint/Prettier/tsc/Vitest).
- [ ] Test baru gagal sebelum perbaikan dan lulus sesudahnya (untuk bug fix).
- [ ] Tidak ada penurunan cakupan test pada modul yang disentuh.
- [ ] E2E dijalankan untuk fase yang menyentuh UI/rute (1, 4, 6, 7).
- [ ] Tidak ada file generated yang diedit manual.
- [ ] `docs/DECISIONS.md` diperbarui bila perilaku atau keputusan berubah.
- [ ] PR description memuat: tujuan, file, risiko, bukti test (output), dan rollback.
- [ ] Conventional Commit, subjek ≤ 100 karakter.

### 10.2 Checklist acceptance per fase

| Fase | Acceptance                                                                   |
| ---- | ---------------------------------------------------------------------------- |
| 0    | Baseline gate + E2E dicatat                                                  |
| 1    | Guard + `.env.example` + test takeover + D-90                                |
| 2    | Trait `toUtc`, 4 salinan hilang, filter bulan publik/admin benar, test 422   |
| 3    | Setting dibaca sekali, galeri satu foto per album, actor options users-based |
| 4    | Media guard, rich text null, count konsisten, thumbnail layanan, disk backup |
| 5    | Slug, varian, filter kategori, view counter, dead code                       |
| 6    | Policy + list page generik, controller shared, E2E master-data               |
| 7    | Dependensi bersih, satu lockfile, boot + ArchitectureTest + build hijau      |

### 10.3 Checklist rilis

- [ ] Semua fase digabung ke branch rilis.
- [ ] `composer ci:check` dijalankan pada commit akhir.
- [ ] `npm run e2e` penuh dijalankan pada commit akhir.
- [ ] `php artisan route:list` tidak berubah tidak sengaja.
- [ ] `git diff --stat` direview; tidak ada file tak terduga.
- [ ] `docs/DECISIONS.md` dan `docs/DEVELOPMENT.md` konsisten.
- [ ] Tidak ada `--no-verify` di commit mana pun.

---

## 11. Out of Scope

- Menambah fitur baru atau mengubah desain UI.
- Upgrade dependency major (Laravel 13, React 19, Vite 8, Tailwind 4 sudah dipakai).
- Mengubah model role/permission atau alur auth.
- Menggabungkan `*ValidationRules` antar-modul (field-nya memang berbeda).
- Mengaktifkan SSR atau mengubah kontrak Inertia.
- Menambah layer repository/service baru tanpa kebutuhan yang terbukti.
- Menulis ulang backup/restore; hanya cek disk space yang disentuh.
- Memperbaiki retensi audit (didokumentasikan sebagai limitasi, bukan bug).
- Mengubah `Document::scopePublished()` untuk menyembunyikan dokumen kedaluwarsa.

---

## 12. Status Implementasi

| Fase | Status    | Tanggal    | Commit | PR  | Catatan                                                                 |
| ---- | --------- | ---------- | ------ | --- | ----------------------------------------------------------------------- |
| 0    | Sebagian  | 2026-11-02 | —      | —   | Test per fase dijalankan; `composer ci:check` dan E2E penuh belum      |
| 1    | Selesai   | 2026-11-02 | —      | —   | Guard DB aplikasi + `.env.example=false` + test `DATABASE_NOT_FRESH`; D-90 diperbarui |
| 2    | Selesai   | 2026-11-02 | —      | —   | Trait `ConvertsLocalDateTime`, filter bulan publik/admin, test 422      |
| 3    | Selesai   | 2026-11-02 | —      | —   | Memoization + `scoped`, galeri `limit(1)`, actor options dari users     |
| 4    | Selesai   | 2026-11-02 | —      | —   | 4a media guard, 4b `cleanOrNull`, 4c count/thumbnail, 4d cek disk `2× + threshold` |
| 5    | Selesai   | 2026-11-02 | —      | —   | Slug, varian, view counter, dead code selesai; `ParishCta` dihapus; helper filter dibiarkan |
| 6    | Selesai (6a+6b) | 2026-11-02 | — | — | FormMeta helper + policy trait; 6c/6d/6e ditunda |
| 7    | Selesai   | 2026-11-02 | —      | —   | `sail`/`chisel` dihapus; `pao` dipertahankan; pnpm + `.codex` dihapus; targeted verification |

Catatan implementasi:

- Fase 4d (cek ruang disk backup) selesai: awalnya `2 × ukuran sumber + threshold`
  dengan feature test `disk_full` lewat threshold yang sangat besar. Tindak lanjut
  review menambahkan ukuran dump dan cek kedua setelah dump; lihat
  `plan-refactoring-review-fixes-2026-11.md` §1B.
- Fase 5 selesai: `ParishCta` dihapus beserta test-nya; helper filter kategori publik
  dibiarkan sebagai accepted duplication karena query tiap modul berbeda urutan/scope.
- Fase 6 selesai untuk **6a + 6b**: `BuildsFormMeta` + `ChecksModulePermission`.
  **6c/6d/6e ditunda**: controller helper, list page, dan form kategori tidak diubah.
  E2E `master-data.spec.ts` lulus (12 test).
- Tindak lanjut review 2026-11-02: 8 temuan ditangani di
  `plan-refactoring-review-fixes-2026-11.md` (installer fail-closed, dump disk,
  test media, `sourceBytes()`, rich text, validasi tanggal).
- Fase 7 selesai dengan koreksi: `laravel/pao` **dipertahankan** karena auto-register
  provider dan Pest plugin; yang dihapus adalah `laravel/sail` dan `laravel/chisel`.
  `symfony/mime` juga diperbarui ke v7.4.22 untuk menutup advisory
  CVE-2026-45070; `composer audit` kini bersih.
- `composer ci:check` penuh dan `npm run e2e` belum dijalankan pada sesi ini; yang
  dijalankan adalah test per fase, PHPStan/Pint, dan verifikasi targeted Fase 7.

---

## 13. Lampiran

### 13.1 Cheat sheet perintah

```bash
# Gate penuh (MySQL harus hidup)
composer ci:check

# Test PHP spesifik
php artisan test tests/Feature/Installation
php artisan test --filter=InstallerLockTest

# Test frontend
npm run test:unit
npm run test:unit -- tests/js/features/admin/master-data

# E2E
npm run e2e:prepare
npx playwright test tests/e2e/master-data.spec.ts

# Format PHP
vendor/bin/pint --dirty --format agent

# Wayfinder (hanya jika route berubah)
php artisan wayfinder:generate --with-form

# Route list
php artisan route:list --except-vendor

# Build
npm run build
```

### 13.2 Format commit

```text
<type>(<scope>): <subject ≤ 100 karakter>

Contoh:
fix(installer): refuse installation when the application database is not fresh
fix(agenda): return 422 for malformed dates instead of 500
perf(settings): memoize site settings per request
fix(media): refuse to delete media that content still references
refactor(master-data): share category permission policy
chore(deps): remove unused sail and chisel packages
```

Gunakan `--no-verify` hanya jika benar-benar terpaksa; CI tetap menjalankan gate.

### 13.3 Referensi keputusan

- `docs/DECISIONS.md` D-16, D-17, D-19, D-20, D-21, D-23, D-24, D-25, D-26, D-30,
  D-39, D-43, D-44, D-45, D-55, D-57, D-60, D-65, D-90, D-91, D-93, D-94, D-96, D-101.
- `docs/audit-log.md` §7 (limitasi retensi).
- `docs/ARCHITECTURE.md` Part A (controller/action/service), Part C (Inertia contract).
- `AGENTS.md` (commands, tests, generated files, commit).
- `docs/plan-implementation/plan-refactoring-audit-codebase.md` (audit pertama).
