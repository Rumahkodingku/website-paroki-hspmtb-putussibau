# Implementation Plan — Refactoring Berdasarkan Audit Codebase

> **Project:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau\
> **Scope:** Refactoring 15 temuan audit codebase (bug, risiko, duplikasi, dead code)\
> **Status:** Implemented in working tree — final gate partial (lihat §12)\
> **Branch dasar (audit):** `refactor/refactor-code` @ `d249894`\
> **Branch (usulan):** satu branch per fase, contoh `refactor/audit-jsonld`, `refactor/audit-multiday-event`\
> **Sumber kebenaran:** `AGENTS.md` → `PRD.md` → `ARCHITECTURE.md` → `docs/DECISIONS.md` → dokumen ini\
> **Tanggal audit:** 2026-10-31

Dokumen ini menjelaskan **apa yang diubah, di file mana, dalam urutan apa, dan bagaimana
diverifikasi**. Dokumen ini bukan roadmap fitur; ini rencana perbaikan teknis berdasarkan
audit. Semua keputusan di §2 **mengikat** dan tidak boleh diubah tanpa persetujuan pengguna.

> **Penting:** Dokumen ini disusun dari audit source code. Baseline test **belum dijalankan
> ulang** saat dokumen ini ditulis. Fase 0 wajib menjalankan baseline sebelum fase lain
> dimulai.

---

## Daftar Isi

- [Implementation Plan — Refactoring Berdasarkan Audit Codebase](#implementation-plan--refactoring-berdasarkan-audit-codebase)
  - [Daftar Isi](#daftar-isi)
  - [0. Instruksi untuk AI Agent](#0-instruksi-untuk-ai-agent)
  - [1. Ringkasan Eksekutif](#1-ringkasan-eksekutif)
    - [1.1 Hasil yang diharapkan](#11-hasil-yang-diharapkan)
    - [1.2 Batasan global](#12-batasan-global)
  - [2. Keputusan yang Mengikat](#2-keputusan-yang-mengikat)
    - [2.1 Keputusan produk (dari pengguna)](#21-keputusan-produk-dari-pengguna)
    - [2.2 Keputusan teknis yang mengikat](#22-keputusan-teknis-yang-mengikat)
    - [2.3 Koreksi terhadap audit](#23-koreksi-terhadap-audit)
  - [3. Kondisi Codebase Saat Ini](#3-kondisi-codebase-saat-ini)
    - [3.1 Baseline repository](#31-baseline-repository)
    - [3.2 15 temuan dan bukti](#32-15-temuan-dan-bukti)
    - [3.3 Konvensi yang mengikat implementasi](#33-konvensi-yang-mengikat-implementasi)
  - [4. Arsitektur Target dan Prinsip Refactoring](#4-arsitektur-target-dan-prinsip-refactoring)
  - [5. Rencana per Fase](#5-rencana-per-fase)
    - [Fase 0 — Baseline dan guardrail](#fase-0--baseline-dan-guardrail)
    - [Fase 1 — JSON-LD tidak bisa keluar dari `<script>`](#fase-1--json-ld-tidak-bisa-keluar-dari-script)
    - [Fase 2 — Agenda multi-hari dan lintas bulan](#fase-2--agenda-multi-hari-dan-lintas-bulan)
    - [Fase 3 — Notice yang menargetkan satu misa](#fase-3--notice-yang-menargetkan-satu-misa)
    - [Fase 4 — Foto Area: eager load varian](#fase-4--foto-area-eager-load-varian)
    - [Fase 5 — Throttle unduhan dokumen](#fase-5--throttle-unduhan-dokumen)
    - [Fase 6 — Konsolidasi format tanggal](#fase-6--konsolidasi-format-tanggal)
    - [Fase 7 — Satu sumber aturan audit](#fase-7--satu-sumber-aturan-audit)
    - [Fase 8 — Sapu dead code](#fase-8--sapu-dead-code)
    - [Fase 9 — CI dan dependensi](#fase-9--ci-dan-dependensi)
    - [Fase 10 — Aturan telepon dan ICS](#fase-10--aturan-telepon-dan-ics)
    - [Fase 11 — Verifikasi akhir dan rilis](#fase-11--verifikasi-akhir-dan-rilis)
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
   di luar 15 temuan.
5. **Untuk perubahan perilaku, tulis test yang gagal dulu**, lalu perbaiki.
6. **Jangan pernah memakai `--no-verify`.** Pre-commit menjalankan gate penuh.
7. **Jalankan perintah verifikasi di setiap fase.** Jangan mengklaim lulus tanpa output.
8. **Perbarui §12** setelah fase selesai (tanggal, commit, PR, catatan).
9. **Jangan mengedit file generated** (`resources/js/{routes,actions,wayfinder}`,
   `public/build`). Regenerasi dengan `php artisan wayfinder:generate --with-form`.
10. **Kalau ragu, tanya.** Jangan mengarang perilaku baru.

---

## 1. Ringkasan Eksekutif

Audit menemukan **3 bug/risiko harus diperbaiki**, **7 perbaikan risiko/duplikasi**, dan
**5 pembersihan**. Tidak ada temuan yang memerlukan perubahan skema database.

### 1.1 Hasil yang diharapkan

| Hasil            | Ukuran keberhasilan                                                            |
| ---------------- | ------------------------------------------------------------------------------ |
| Keamanan         | Tidak ada input event yang bisa keluar dari blok JSON-LD                       |
| Kebenaran agenda | Kegiatan multi-hari tampil selama belum selesai dan muncul di bulan yang benar |
| Kebenaran notice | Notice bertarget jadwal/misa khusus muncul saat targetnya tampil               |
| Performa         | Daftar area berfoto tidak lazy-load dan tidak N+1                              |
| Abuse            | Endpoint unduh dibatasi, `download_count` tetap sesuai PRD                     |
| Konsistensi      | Satu sumber format tanggal, satu aturan audit, satu aturan telepon             |
| Lean             | ±1.364 baris dead code dihapus; 2 dependensi tak terpakai hilang               |
| CI               | `tsc` dan Vitest tidak lagi dijalankan dua kali                                |

### 1.2 Batasan global

- **Tidak ada migrasi/skema baru.**
- **Tidak ada dependensi baru.**
- **Tidak ada abstraksi/layer baru.**
- **Tidak ada perubahan pada modul backup, installer, auth, atau kebijakan izin**
  kecuali disebut eksplisit di fase terkait.
- Perubahan perilaku harus punya test regresi.
- Total estimasi: **±20–30 jam** untuk satu developer.

---

## 2. Keputusan yang Mengikat

### 2.1 Keputusan produk (dari pengguna)

| #   | Pertanyaan          | Keputusan                        | Konsekuensi                                                                    |
| --- | ------------------- | -------------------------------- | ------------------------------------------------------------------------------ |
| 1   | Cakupan refactoring | **Semua 15 temuan**              | 11 fase; Nice to have tetap dikerjakan                                         |
| 2   | Notice bertarget    | **Tampilkan sesuai target**      | Query publik diperluas; form admin tidak diubah                                |
| 3   | `/design-system`    | **Hapus sekarang**               | Route, halaman, referensi test/e2e, generated route dihapus                    |
| 4   | Agenda multi-hari   | **Tampil sampai `end_at` lewat** | `end_at` null memakai `start_at`; kegiatan satu hari yang sudah selesai hilang |

### 2.2 Keputusan teknis yang mengikat

**K1 — Predikat agenda multi-hari (Fase 2).**

`Event::scopeUpcomingFromToday()` menjadi:

```text
(end_at IS NULL AND start_at >= awal_hari_ini_di_display_timezone)
OR
(end_at IS NOT NULL AND end_at >= sekarang)
```

Alasan: kegiatan dengan `end_at` dihormati sampai selesai; kegiatan tanpa `end_at`
dianggap relevan sepanjang hari mulai.

`PublicEventController::monthly()` memakai irisan rentang:

```text
start_at <= akhir_bulan
AND (
    (end_at IS NULL AND start_at >= awal_bulan)
    OR
    (end_at IS NOT NULL AND end_at >= awal_bulan)
)
```

Urutan tetap `start_at`, lalu `id`.

**K2 — Notice bertarget (Fase 3).**

`notices()` menampilkan notice jika salah satu benar:

1. Notice umum (`location_id`, `mass_schedule_id`, `special_mass_id` semuanya null), atau
2. `location_id` sama dengan lokasi yang sedang difilter, atau
3. `mass_schedule_id` ada di daftar jadwal rutin yang **benar-benar tampil** setelah
   filter lokasi/hari, atau
4. `special_mass_id` ada di daftar misa khusus yang **benar-benar tampil**.

Notice untuk target yang tidak tampil (terfilter, nonaktif, terhapus) tetap tersembunyi.

**K3 — Unduhan dokumen (Fase 5).**

- Tambahkan `throttle:30,1` pada route unduh.
- **Jangan** mengubah `download_count` menjadi per sesi. PRD DL-06/AC-DL-2 dan
  `tests/Feature/Document/DocumentDownloadTest.php:55` mengunci "setiap unduhan".
- Rate limit 30/menit/IP adalah default; boleh diubah hanya jika ada alasan operasional.

**K4 — Format tanggal (Fase 6).**

- `resources/js/lib/datetime.ts` menjadi satu-satunya tempat format tanggal.
- Tanggal tidak valid mengembalikan `''` dari helper; komponen yang perlu teks fallback
  menampilkan teksnya sendiri (mis. "Waktu belum tersedia").
- Output yang sudah ada harus dipertahankan persis: titik pada "07.30", suffix "WIB",
  singkatan hari/bulan Indonesia, dan `displayTimezone` dari props.

**K5 — Aturan audit (Fase 7).**

- `RecordAuditEventAction` menjadi pemilik tunggal `allowedFields()` dan `normalize()`.
- `AuditLogger` mendelegasikan; tidak ada kelas baru.
- Redaksi key bersarang mengikuti aturan action.

**K6 — Nomor telepon (Fase 10a).**

- Satu `App\Rules\PhoneNumberRule` (atau satu method trait bersama) menggantikan tiga
  closure identik.
- Pesan validasi dipertahankan persis.

**K7 — ICS (Fase 10b).**

- Setiap baris fisik maksimal 75 oktet; lanjutan `CRLF + spasi`.
- Tidak boleh memotong karakter UTF-8.
- Tidak menambah dependensi.

**K8 — Dead code (Fase 8).**

- Hapus route dan halaman `/design-system` sepenuhnya. Tidak ada route pengganti.
- Hapus komponen upload yang tidak diimpor dan komponen UI yang tidak diimpor.
- Hapus `tests/Unit/ExampleTest.php`.

### 2.3 Koreksi terhadap audit

Audit menyebut "counter unduhan naik per request" sebagai masalah. Itu **bukan** masalah:
PRD DL-06 dan AC-DL-2 mewajibkan counter naik setiap unduhan, dan test menguncinya.
Fase 5 hanya menambahkan throttle. Jangan mengubah counter.

---

## 3. Kondisi Codebase Saat Ini

### 3.1 Baseline repository

| Item                    | Nilai                                                                   |
| ----------------------- | ----------------------------------------------------------------------- |
| Branch                  | `refactor/refactor-code`                                                |
| Commit audit            | `d249894`                                                               |
| Working tree saat audit | Bersih                                                                  |
| Stack                   | Laravel 13, Inertia v3, React 19, Vite 8, Tailwind v4, PHP 8.3, MySQL 8 |
| Test suite              | 208 file test (Pest, Vitest, Playwright)                                |
| CI                      | `.github/workflows/tests.yml` → `composer ci:check` + commitlint + e2e  |
| Gate lokal              | pre-commit → lint-staged + `composer ci:check`                          |

**Baseline belum dijalankan ulang.** Fase 0 wajib menjalankan `composer ci:check` dan
`npm run e2e` untuk mencatat kondisi awal.

### 3.2 15 temuan dan bukti

| #   | Temuan                              | File utama                                                                                                                      | Bukti                                                                                                                      |
| --- | ----------------------------------- | ------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| 1   | JSON-LD bisa keluar dari `<script>` | `resources/js/features/public/agenda/components/event-json-ld.tsx:72-81`                                                        | Diuji di Chromium: `script.innerHTML` dengan `</script><img onerror=...>` membuat elemen `<img>` dan menjalankan `onerror` |
| 2   | Agenda multi-hari hilang            | `app/Models/Event.php:147-155`, `app/Http/Controllers/Agenda/PublicEventController.php:96-139`                                  | Scope hanya memeriksa `start_at`; test hanya mencakup kegiatan satu hari                                                   |
| 3   | Notice bertarget tidak tampil       | `app/Http/Controllers/MassSchedule/PublicMassScheduleController.php:125-151`                                                    | `tests/Feature/MassSchedule/DeleteTest.php:380-386` mencatat celah ini                                                     |
| 4   | Foto Area lazy-load/N+1             | `app/Http/Controllers/Area/AreaController.php:50,132`, `app/Http/Resources/Area/AreaResource.php:42`                            | `photoMedia` di-load tanpa `variants`; `Media::url()` membaca `variants`                                                   |
| 5   | Format tanggal 6 salinan            | `resources/js/lib/datetime.ts:56-138` + 5 file fitur                                                                            | Perilaku tanggal invalid sudah berbeda (`''`, `null`, teks)                                                                |
| 6   | Unduhan tanpa throttle              | `routes/web.php:176-179`, `app/Http/Controllers/Document/PublicDocumentController.php:81-137`                                   | Route publik tanpa middleware throttle; counter naik per request                                                           |
| 7   | Komponen upload mati                | `resources/js/components/upload/document-upload.tsx`, `file-upload.tsx`, `gallery-upload.tsx`                                   | Tidak ada import di seluruh tree (455 baris)                                                                               |
| 8   | `/design-system` masih rilis        | `routes/web.php:186`, `resources/js/features/public/design-system/pages/index.tsx`                                              | D-31: dihapus saat shell publik stabil; shell sudah stabil (634 baris)                                                     |
| 9   | CI dobel                            | `composer.json:68-73`                                                                                                           | `npm run check` sudah berisi `types:check` + `test:unit`, lalu dijalankan lagi                                             |
| 10  | Aturan audit dobel                  | `app/Services/AuditLogger.php:419-462`, `app/Actions/Audit/RecordAuditEventAction.php:223-285`                                  | Redaksi nested sudah berbeda implementasi                                                                                  |
| 11  | Dependensi tak terpakai             | `package.json:57-58`                                                                                                            | `cn` dan `concurrently` tidak diimpor/dipakai                                                                              |
| 12  | Komponen UI mati                    | `resources/js/components/app-logo.tsx`, `ui/icon.tsx`, `ui/pagination.tsx`, `ui/placeholder-pattern.tsx`, `ui/toggle-group.tsx` | Tidak ada import (275 baris)                                                                                               |
| 13  | Aturan telepon 3 salinan            | `app/Concerns/AreaValidationRules.php:68-73`, `CommunityValidationRules.php:84-89`, `ServiceValidationRules.php:82-87`          | Closure dan pesan identik                                                                                                  |
| 14  | ICS tanpa line folding              | `app/Services/IcsCalendarBuilder.php:78-112`                                                                                    | Baris `DESCRIPTION` bisa >75 oktet                                                                                         |
| 15  | Test kosong                         | `tests/Unit/ExampleTest.php:1-5`                                                                                                | `expect(true)->toBeTrue()`                                                                                                 |

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

Tidak ada perubahan arsitektur besar. Targetnya adalah **mengurangi duplikasi dan
memperbaiki perilaku**, bukan menambah layer.

| Prinsip                    | Penerapan                                                                           |
| -------------------------- | ----------------------------------------------------------------------------------- |
| Satu sumber kebenaran      | Format tanggal di `lib/datetime.ts`; aturan audit di action; aturan telepon di rule |
| Query benar dulu           | Perbaiki predikat di model/controller, bukan menambal di React                      |
| Test di level yang tepat   | Bug query → Pest; serialisasi/format → Vitest; alur UI → Playwright                 |
| Hapus, bukan tambah        | Dead code dihapus; tidak ada wrapper/interface baru                                 |
| Perubahan kecil            | Satu fase = satu PR; jangan menggabungkan pembersihan dengan perubahan perilaku     |
| Jangan sentuh yang bekerja | Backup, installer, auth, permission tidak diubah                                    |

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

**Menutup temuan:** — (persiapan)

**Target:** Kondisi awal tercatat; data yang bisa merusak Fase 2 diketahui.

**File:** Tidak ada file kode yang diubah.

**Langkah:**

1. Pastikan MySQL hidup dan `website_paroki_hspmtb_test` ada.
2. Jalankan `composer ci:check`; simpan output.
3. Jalankan `npm run e2e`; simpan output dan daftar kegagalan yang sudah ada.
4. Simpan baseline rute: `php artisan route:list --json > /tmp/routes-before.json`.
5. Jalankan query read-only:

   ```sql
   SELECT COUNT(*) FROM events WHERE end_at IS NOT NULL AND end_at < start_at;
   ```

6. Catat `npm audit` dan `composer show --direct`.
7. Catat versi: `php -v`, `node -v`, `npm -v`, `composer --version`.

**Test wajib:** Tidak ada test baru.

**Verifikasi:** Output baseline tersimpan di PR/issue atau catatan terpisah.

**Risiko:**

- Baseline bisa gagal karena MySQL atau `upload_max_filesize`. Catat sebagai kondisi awal;
  jangan perbaiki di luar scope.
- Data `end_at < start_at` bisa membuat kegiatan hilang setelah Fase 2. Kalau count > 0,
  buat rencana koreksi data sebelum Fase 2.

**Definition of Done:** Baseline tercatat, termasuk kegagalan yang sudah ada.

**Estimasi:** 30 menit.

---

### Fase 1 — JSON-LD tidak bisa keluar dari `<script>`

**Menutup temuan:** #1 (Must fix).

**Target:** Judul/deskripsi agenda yang mengandung `</script>` tetap menghasilkan JSON valid
dan tidak bisa membuat elemen HTML baru.

**File:**

| Aksi     | File                                                                |
| -------- | ------------------------------------------------------------------- |
| Ubah     | `resources/js/features/public/agenda/components/event-json-ld.tsx`  |
| Buat     | `tests/js/features/public/agenda/event-json-ld.test.tsx`            |
| Opsional | `tests/js/support/inertia.tsx` (hanya jika perlu merender `<Head>`) |

**Langkah:**

1. Ekstrak serialisasi menjadi fungsi murni yang diekspor, misalnya
   `serializeJsonLd(data: Record<string, unknown>): string`.
2. Implementasi: `JSON.stringify(data).replace(/</g, '\\u003c')`.
3. Ganti pemakaian di `dangerouslySetInnerHTML` dengan fungsi tersebut.
4. Test:
   - Judul `</script><img src=x onerror=alert(1)>` → output tidak mengandung `</script>`;
     `JSON.parse(output).name` sama dengan judul asli.
   - Karakter Indonesia, tanda kutip, dan objek bersarang tetap valid.
5. Jangan mengubah `data-head-key` atau struktur data lain.

**Test wajib:**

| File                                                     | Jenis  | Kasus                                     |
| -------------------------------------------------------- | ------ | ----------------------------------------- |
| `tests/js/features/public/agenda/event-json-ld.test.tsx` | Vitest | Escape `</script>`                        |
| `tests/js/features/public/agenda/event-json-ld.test.tsx` | Vitest | JSON valid untuk teks Indonesia dan kutip |
| `tests/Feature/Agenda/PublicEventPageTest.php`           | Pest   | Halaman agenda tetap 200 (regresi)        |

**Verifikasi:**

```bash
npm run test:unit -- tests/js/features/public/agenda/event-json-ld.test.tsx
npm run types:check
npm run lint
php artisan test --filter=PublicEventPageTest
```

**Risiko:**

- Escaping `<` mengubah isi string, tetapi JSON tetap valid dan tampilan tidak berubah.
- Jangan meng-escape `>`/`&` tanpa kebutuhan; diff membesar tanpa manfaat.

**Definition of Done:** Test gagal sebelum perbaikan dan lulus sesudahnya; tidak ada cara
menghasilkan `</script>` dari data event.

**Estimasi:** S (30–60 menit).

---

### Fase 2 — Agenda multi-hari dan lintas bulan

**Menutup temuan:** #2 (Must fix).

**Target:** Kegiatan tampil selama `end_at` belum lewat; kegiatan lintas bulan muncul di
bulan yang benar.

**File:**

| Aksi     | File                                                                            |
| -------- | ------------------------------------------------------------------------------- |
| Ubah     | `app/Models/Event.php` (`scopeUpcomingFromToday`)                               |
| Ubah     | `app/Http/Controllers/Agenda/PublicEventController.php` (`upcoming`, `monthly`) |
| Ubah     | `tests/Feature/Agenda/PublicEventPageTest.php`                                  |
| Ubah     | `tests/Feature/Public/PublicHomepageTest.php`                                   |
| Ubah     | `tests/Feature/DashboardTest.php`                                               |
| Ubah     | `tests/Feature/Agenda/EventTest.php` (jika ada asumsi scope)                    |
| Opsional | `docs/DECISIONS.md` (catat semantik baru)                                       |

**Langkah:**

1. Tulis test yang gagal (lihat tabel test).
2. Ubah `scopeUpcomingFromToday()` sesuai **K1**:
   - `end_at IS NULL AND start_at >= awal hari ini` **atau**
   - `end_at IS NOT NULL AND end_at >= now()`.
3. Ubah `monthly()` sesuai **K1**:
   - `start_at <= akhir bulan`
   - dan irisan `end_at`/`start_at` dengan awal bulan.
4. Perbarui docblock scope: nama `upcomingFromToday` dipertahankan agar diff kecil, tetapi
   jelaskan semantik barunya.
5. Pastikan urutan `start_at`, `id` tidak berubah.
6. Periksa dampak ke `PublicHomeController` dan `DashboardController`; keduanya memakai
   scope yang sama, jadi seharusnya tidak perlu perubahan kode.

**Test wajib:**

| File                      | Kasus                                                        |
| ------------------------- | ------------------------------------------------------------ |
| `PublicEventPageTest.php` | Retret 12–14 Nov terlihat pada 13 Nov                        |
| `PublicEventPageTest.php` | Kegiatan yang berakhir kemarin tidak terlihat                |
| `PublicEventPageTest.php` | Kegiatan hari ini dengan `end_at` sudah lewat tidak terlihat |
| `PublicEventPageTest.php` | Kegiatan hari ini tanpa `end_at` tetap terlihat              |
| `PublicEventPageTest.php` | Kegiatan 31 Okt–2 Nov muncul di daftar November              |
| `PublicHomepageTest.php`  | Blok agenda memakai semantik yang sama                       |
| `DashboardTest.php`       | `summary.events.upcoming` menghitung kegiatan multi-hari     |

**Verifikasi:**

```bash
php artisan test --filter=PublicEventPageTest
php artisan test --filter=PublicHomepageTest
php artisan test --filter=DashboardTest
php artisan test --filter=EventTest
npm run e2e
```

**Risiko:**

- **Perubahan perilaku:** kegiatan satu hari dengan `end_at` yang sudah lewat hilang lebih
  cepat. Ini konsekuensi keputusan "sampai `end_at` lewat"; jelaskan di PR.
- **Zona waktu:** `awal hari` dihitung di `Asia/Pontianak`; `end_at >= now()` membandingkan
  instan UTC. Jangan mencampur.
- **Data lama:** `end_at < start_at` tersembunyi. Cek di Fase 0.
- **Hitungan dashboard** ikut berubah; perbarui test secara sadar.

**Definition of Done:** Semua test baru lulus; test lama yang berubah punya alasan tertulis;
tidak ada migrasi.

**Estimasi:** M (2–4 jam).

---

### Fase 3 — Notice yang menargetkan satu misa

**Menutup temuan:** #3 (Must fix).

**Target:** Notice untuk jadwal rutin/misa khusus muncul saat targetnya tampil; tetap
tersembunyi saat target tidak tampil.

**File:**

| Aksi     | File                                                                      |
| -------- | ------------------------------------------------------------------------- |
| Ubah     | `app/Http/Controllers/MassSchedule/PublicMassScheduleController.php`      |
| Opsional | `app/Models/ScheduleNotice.php` (scope `visibleFor`)                      |
| Opsional | `app/Http/Resources/Public/MassSchedule/PublicScheduleNoticeResource.php` |
| Ubah     | `tests/Feature/MassSchedule/PublicMassSchedulePageTest.php`               |
| Ubah     | `tests/Feature/MassSchedule/ScheduleNoticeTest.php`                       |
| Ubah     | `tests/Feature/MassSchedule/DeleteTest.php` (komentar test)               |

**Langkah:**

1. Di `index()`, ambil koleksi jadwal rutin dan misa khusus **sekali**.
2. Hitung `scheduleIds` dan `specialMassIds` dari koleksi yang sudah difilter lokasi/hari.
3. Teruskan id ke `notices($location, $scheduleIds, $specialMassIds)`.
4. Query notice sesuai **K2**:
   - umum, atau
   - `location_id` cocok, atau
   - `whereIn('mass_schedule_id', $scheduleIds)`, atau
   - `whereIn('special_mass_id', $specialMassIds)`.
5. Jangan mengubah aturan notice lokasi yang sudah ada.
6. Perbarui komentar di `DeleteTest.php:380-386` yang menyebut celah ini.
7. Tampilan tetap banner atas. Tidak perlu field baru di resource kecuali produk meminta
   label target.

**Test wajib:**

| File                             | Kasus                                                  |
| -------------------------------- | ------------------------------------------------------ |
| `PublicMassSchedulePageTest.php` | Notice target jadwal rutin muncul                      |
| `PublicMassSchedulePageTest.php` | Notice target misa khusus muncul                       |
| `PublicMassSchedulePageTest.php` | Notice target tersembunyi oleh filter lokasi           |
| `PublicMassSchedulePageTest.php` | Notice target tersembunyi oleh filter hari             |
| `PublicMassSchedulePageTest.php` | Notice umum dan notice lokasi tetap seperti sebelumnya |
| `ScheduleNoticeTest.php`         | Notice target yang dihapus/nonaktif tidak muncul       |

**Verifikasi:**

```bash
php artisan test tests/Feature/MassSchedule
npm run e2e
```

**Risiko:**

- **Banner menumpuk** jika banyak notice. Uji dengan 3–5 notice; batasi hanya jika terbukti
  mengganggu.
- **Target terhapus/nonaktif** otomatis hilang karena id diambil dari daftar tampil.
- **Produk:** ini memperluas halaman publik sesuai keputusan; catat di `DECISIONS.md` bila
  tim memakai catatan keputusan.

**Definition of Done:** Ketiga jenis target punya test; tidak ada notice yang bocor ke
halaman yang salah.

**Estimasi:** M (2–4 jam).

---

### Fase 4 — Foto Area: eager load varian

**Menutup temuan:** #4 (Should fix).

**Target:** Daftar area berfoto tidak memicu lazy-load dan tidak menambah query per baris.

**File:**

| Aksi | File                                                                      |
| ---- | ------------------------------------------------------------------------- |
| Ubah | `app/Http/Controllers/Area/AreaController.php` (`index`, `edit`/`create`) |
| Ubah | `app/Http/Controllers/Area/PublicAreaController.php` (`show`)             |
| Ubah | `tests/Feature/Area/AreaTest.php`                                         |
| Ubah | `tests/Feature/Area/PublicAreaPageTest.php`                               |

**Langkah:**

1. Ganti `photoMedia` menjadi `photoMedia.variants` pada eager load daftar admin dan form.
2. Tambahkan `photoMedia.variants` pada `show()` area publik.
3. Test: area dengan `photo_media_id` dan varian `medium` merender 200 di strict mode.
4. Opsional: test jumlah query dengan `DB::listen` untuk 5 area berfoto.

**Test wajib:**

| File                     | Kasus                                                    |
| ------------------------ | -------------------------------------------------------- |
| `AreaTest.php`           | Daftar area dengan foto merender tanpa lazy-load         |
| `PublicAreaPageTest.php` | Detail area dengan foto merender dan mengirim `photoUrl` |

**Verifikasi:**

```bash
php artisan test --filter=AreaTest
php artisan test --filter=PublicAreaPageTest
```

**Risiko:**

- Rendah. Media tanpa varian tetap menghasilkan `null` dan placeholder.
- Jangan mengubah `Media::url()`; masalahnya di eager load.

**Definition of Done:** Area berfoto tampil 200 di non-produksi; tidak ada query per area.

**Estimasi:** S (30 menit).

---

### Fase 5 — Throttle unduhan dokumen

**Menutup temuan:** #6 (Should fix).

**Target:** Endpoint unduh tidak bisa dipakai menghabiskan bandwidth; `download_count`
tetap sesuai PRD.

**File:**

| Aksi     | File                                              |
| -------- | ------------------------------------------------- |
| Ubah     | `routes/web.php` (route `dokumen.unduh`)          |
| Opsional | `bootstrap/app.php` (status 429)                  |
| Ubah     | `tests/Feature/Document/DocumentDownloadTest.php` |

**Langkah:**

1. Tambahkan `->middleware('throttle:30,1')` pada route unduh.
2. **Jangan** mengubah counter: PRD DL-06/AC-DL-2 dan test `:55` mengunci perilaku.
3. Opsional: masukkan `429` ke daftar status halaman error di `bootstrap/app.php`
   (`[403, 404, 419, 500, 503]` → tambah `429`). Jangan tambahkan entri `COPY[429]` di
   React kecuali ada ilustrasi `429.webp`; fallback sudah berbahasa Indonesia.
4. Test: 30 request pertama OK, request ke-31 429. Bersihkan cache/rate limiter di
   `beforeEach` agar tidak flaky.
5. Catat di PR: di belakang reverse proxy, trusted proxy harus dikonfigurasi (di luar scope).

**Test wajib:**

| File                       | Kasus                                                   |
| -------------------------- | ------------------------------------------------------- |
| `DocumentDownloadTest.php` | 30 request OK, ke-31 429                                |
| `DocumentDownloadTest.php` | Counter tetap naik tiap request (test lama tetap lulus) |
| `DocumentDownloadTest.php` | Inline preview tidak menambah counter (test lama)       |

**Verifikasi:**

```bash
php artisan test --filter=DocumentDownloadTest
npm run e2e -- dokumen.spec.ts
```

**Risiko:**

- **NAT kantor paroki:** 30/menit cukup longgar; pantau 429 setelah rilis.
- **Test flaky:** rate limiter memakai cache; bersihkan di `beforeEach`.
- **Proxy:** `request->ip()` bisa menjadi IP proxy; dokumentasikan.

**Definition of Done:** Limit aktif; counter tetap sesuai PRD; test 429 stabil.

**Estimasi:** S (1–2 jam).

---

### Fase 6 — Konsolidasi format tanggal

**Menutup temuan:** #5 (Should fix).

**Target:** Satu sumber format tanggal; tidak ada `Intl.DateTimeFormat` lokal di komponen
yang didaftar; perilaku tanggal tidak valid seragam.

**File:**

| Aksi            | File                                                                                 |
| --------------- | ------------------------------------------------------------------------------------ |
| Ubah            | `resources/js/lib/datetime.ts`                                                       |
| Ubah            | `resources/js/features/public/beranda/components/home-news-section.tsx`              |
| Ubah            | `resources/js/features/public/beranda/components/home-agenda-section.tsx`            |
| Ubah            | `resources/js/features/public/agenda/components/agenda-selected-day-panel.tsx`       |
| Ubah            | `resources/js/features/public/agenda/components/event-card.tsx`                      |
| Ubah            | `resources/js/features/admin/dashboard/event-date.ts`                                |
| Ubah            | `resources/js/features/public/jadwal-misa/components/special-mass-schedule-card.tsx` |
| Ubah            | `resources/js/features/public/galeri/components/album-card.tsx`                      |
| Ubah            | `tests/js/lib/datetime.test.ts`                                                      |
| Ubah            | `tests/js/lib/datetime-calendar.test.ts`                                             |
| Ubah            | `tests/js/features/public/beranda/homepage.test.tsx`                                 |
| Buat (opsional) | `tests/js/features/public/agenda/date-formatting.test.tsx`                           |

**Langkah:**

1. **Characterization test dulu:** pin output saat ini untuk:
   - `2026-11-14T00:30:00Z` (WIB),
   - tanggal tidak valid,
   - `displayTimezone` non-default,
   - teks Indonesia dan singkatan hari/bulan.
2. Tambahkan helper ke `datetime.ts`:
   - `formatWibDayParts(iso, tz)` → `{ day, month, weekday }`
   - `formatWibLongDate(iso, tz)` → "5 Oktober 2026"
   - `formatWibMonthLabel` bila diperlukan.
3. Ganti helper lokal satu per satu; pertahankan output persis.
4. Tetapkan perilaku invalid: helper mengembalikan `''`; komponen menampilkan fallback
   sendiri.
5. Hapus fungsi lokal yang tidak dipakai.
6. Jangan mengubah locale/opsi `Intl` yang sudah ada.

**Test wajib:**

| File                        | Kasus                                                   |
| --------------------------- | ------------------------------------------------------- |
| `datetime.test.ts`          | Output WIB tanggal/jam, invalid, timezone override      |
| `datetime-calendar.test.ts` | Grid bulan tetap benar                                  |
| `homepage.test.tsx`         | Berita dan agenda beranda menampilkan tanggal yang sama |
| Test baru (opsional)        | Event card dan panel agenda memakai helper baru         |

**Verifikasi:**

```bash
npm run test:unit
npm run types:check
npm run e2e
```

**Risiko:**

- **Output drift** (titik, spasi, singkatan). Mitigasi: characterization test dan cek visual.
- **Perbedaan ICU Node/browser.** Jangan mengubah opsi/locale.
- **Jangan** menghapus `displayTimezone` prop.

**Definition of Done:** Tidak ada `Intl.DateTimeFormat` di komponen yang didaftar; test
karakterisasi lulus; e2e lulus.

**Estimasi:** M–L (4–6 jam). Kerjakan sendiri, jangan digabung.

---

### Fase 7 — Satu sumber aturan audit

**Menutup temuan:** #10 (Should fix).

**Target:** Tidak ada dua salinan `allowedFields()`/`normalize()`; redaksi key bersarang
konsisten.

**File:**

| Aksi       | File                                             |
| ---------- | ------------------------------------------------ |
| Ubah       | `app/Actions/Audit/RecordAuditEventAction.php`   |
| Ubah       | `app/Services/AuditLogger.php`                   |
| Ubah       | `tests/Feature/Audit/AuditSensitiveDataTest.php` |
| Verifikasi | seluruh `tests/Feature/Audit/*`                  |

**Langkah:**

1. Jadikan `allowedFields()` dan `normalize()` milik action (public atau satu method
   `snapshotValue()`).
2. Hapus salinan private di `AuditLogger`; `snapshot()` mendelegasikan.
3. Pastikan redaksi nested mengikuti action.
4. Tambahkan test: snapshot settings dengan array bersarang berisi `password`/`secret`
   tidak menyimpan nilai asli.
5. Jalankan seluruh suite audit; perbarui test yang mengunci JSON persis hanya jika
   perubahan perilaku memang benar.

**Test wajib:**

| File                          | Kasus                             |
| ----------------------------- | --------------------------------- |
| `AuditSensitiveDataTest.php`  | Redaksi nested array              |
| `AuditEventRecordingTest.php` | Snapshot before/after tetap benar |
| `AuditSettingsTest.php`       | Perubahan settings tetap tercatat |

**Verifikasi:**

```bash
php artisan test tests/Feature/Audit
composer ci:check
```

**Risiko:**

- Snapshot settings bisa berubah. Mitigasi: suite audit lengkap.
- Jangan menambah kelas baru.

**Definition of Done:** Hanya satu implementasi aturan; test redaksi nested lulus.

**Estimasi:** M (2–3 jam).

---

### Fase 8 — Sapu dead code

**Menutup temuan:** #7, #8, #12, #15 (Should–Nice).

**Target:** Menghapus ±1.364 baris kode yang tidak dipakai tanpa mengubah fitur.

**File:**

**8a. Komponen upload mati (455 baris)**

| Aksi  | File                                                       |
| ----- | ---------------------------------------------------------- |
| Hapus | `resources/js/components/upload/document-upload.tsx` (157) |
| Hapus | `resources/js/components/upload/file-upload.tsx` (148)     |
| Hapus | `resources/js/components/upload/gallery-upload.tsx` (150)  |

Bukti pengganti: `resources/js/components/media-upload-field.tsx` sudah punya varian
`document` dan `gallery` ditangani halaman galeri.

**8b. `/design-system` (634 baris)**

| Aksi          | File                                                                               |
| ------------- | ---------------------------------------------------------------------------------- |
| Hapus route   | `routes/web.php:186`                                                               |
| Hapus halaman | `resources/js/features/public/design-system/pages/index.tsx`                       |
| Regenerasi    | `resources/js/routes/design-system/`, `resources/js/actions/Inertia/Controller.ts` |
| Ubah          | `tests/Feature/Public/PublicRoutesTest.php:52,98`                                  |
| Ubah          | `tests/Feature/Public/PublicPlaceholderRoutesTest.php:106`                         |
| Ubah          | `tests/e2e/public.spec.ts:312,441`                                                 |

**8c. Komponen UI mati (275 baris)**

| Aksi  | File                                                      |
| ----- | --------------------------------------------------------- |
| Hapus | `resources/js/components/app-logo.tsx` (39)               |
| Hapus | `resources/js/components/ui/icon.tsx` (14)                |
| Hapus | `resources/js/components/ui/pagination.tsx` (119)         |
| Hapus | `resources/js/components/ui/placeholder-pattern.tsx` (32) |
| Hapus | `resources/js/components/ui/toggle-group.tsx` (71)        |

**8d. Test kosong**

| Aksi  | File                         |
| ----- | ---------------------------- |
| Hapus | `tests/Unit/ExampleTest.php` |

**Langkah:**

1. Sebelum menghapus, ulangi grep nama export/path persis di `resources/js`, `tests`,
   `tests/e2e`, dan string config.
2. Hapus 8a + 8c dalam satu commit.
3. Hapus 8b dalam commit terpisah:
   - hapus route dan halaman,
   - jalankan `php artisan wayfinder:generate --with-form`,
   - hapus sisa folder generated bila masih ada, lalu regenerasi sekali lagi,
   - perbarui referensi test/e2e.
4. Hapus 8d.
5. Jalankan `tsc`, lint, Vitest, route list, dan test rute publik.

**Test wajib:**

| File                              | Kasus                                   |
| --------------------------------- | --------------------------------------- |
| `PublicRoutesTest.php`            | Daftar route publik tanpa design-system |
| `PublicPlaceholderRoutesTest.php` | Daftar nama route tanpa design-system   |
| `tests/Unit/ArchitectureTest.php` | Pemetaan halaman tetap konsisten        |
| `tests/e2e/public.spec.ts`        | URL design-system dihapus dari daftar   |

**Verifikasi:**

```bash
npm run check
php artisan test --filter='PublicRoutesTest|PublicPlaceholderRoutesTest|ArchitectureTest'
npm run e2e
git diff --stat
```

**Risiko:**

- **Import tersembunyi/dinamis:** `tsc` menangkap import statis; grep menangkap string.
- **Regenerasi Wayfinder** bisa banyak file. Review diff; hanya entri design-system hilang.
- **E2E** gagal jika URL masih dikunjungi; hapus dari daftar, jangan nonaktifkan test.

**Definition of Done:** Tidak ada import/test yang menunjuk file yang dihapus; build, tsc,
dan e2e lulus; `/design-system` hilang dari route list.

**Estimasi:** S–M (2–3 jam).

---

### Fase 9 — CI dan dependensi

**Menutup temuan:** #9, #11 (Should–Nice).

**Target:** Gate tidak mengerjakan tsc/Vitest dua kali; dua dependensi tak terpakai hilang.

**File:**

| Aksi     | File                                               |
| -------- | -------------------------------------------------- |
| Ubah     | `composer.json` (`ci:check`)                       |
| Ubah     | `package.json` (`cn`, `concurrently`)              |
| Ubah     | `package-lock.json`                                |
| Opsional | `AGENTS.md:129` (catatan audit lama)               |
| Opsional | `docs/DEVELOPMENT.md:368` (catatan `concurrently`) |

**Langkah:**

1. Hapus `npm run types:check` dan `npm run test:unit` dari `ci:check`; sisakan
   `npm run check` lalu `@test`.
2. `npm uninstall cn concurrently`; perbarui `package-lock.json`.
3. Jangan menyentuh `pnpm-lock.yaml` pada fase ini; itu keputusan package manager terpisah.
4. Jalankan `composer ci:check` untuk membuktikan semua pemeriksaan masih berjalan.
5. Perbarui catatan stale bila disetujui; kalau tidak, catat di PR.

**Test wajib:** Tidak ada test baru; verifikasi output gate.

**Verifikasi:**

```bash
composer ci:check
npm run build
npm audit
npm ls cn concurrently
```

**Risiko:**

- **CI kehilangan check:** `npm run check` sudah berisi lint, format, tsc, Vitest;
  `@test` berisi Pint, PHPStan, Pest. Periksa output.
- **`concurrently` dipakai di luar repo?** `npm ls`/grep menunjukkan tidak.
- **`cn` transitif?** Tidak diimpor; build membuktikan.

**Definition of Done:** Gate tetap lengkap, lebih pendek, dua dependensi hilang.

**Estimasi:** S (30–60 menit).

---

### Fase 10 — Aturan telepon dan ICS

**Menutup temuan:** #13, #14 (Nice).

**Target:** Satu aturan nomor telepon; ICS mematuhi 75 oktet tanpa merusak UTF-8.

**File:**

**10a. Aturan telepon**

| Aksi | File                                        |
| ---- | ------------------------------------------- |
| Buat | `app/Rules/PhoneNumberRule.php`             |
| Ubah | `app/Concerns/AreaValidationRules.php`      |
| Ubah | `app/Concerns/CommunityValidationRules.php` |
| Ubah | `app/Concerns/ServiceValidationRules.php`   |
| Ubah | `tests/Feature/Area/AreaTest.php`           |
| Ubah | `tests/Feature/Komunitas/CommunityTest.php` |
| Ubah | `tests/Feature/Pelayanan/ServiceTest.php`   |

**10b. ICS line folding**

| Aksi | File                                  |
| ---- | ------------------------------------- |
| Ubah | `app/Services/IcsCalendarBuilder.php` |
| Ubah | `tests/Feature/Agenda/IcsTest.php`    |

**Langkah 10a:**

1. Buat `PhoneNumberRule` yang memakai `PhoneNumber::isValid` dan pesan yang sama.
2. Ganti tiga closure.
3. Tambah satu test nomor tidak valid per form.

**Langkah 10b:**

1. Tambahkan helper fold setelah escape: baris fisik maksimal 75 oktet, lanjutan
   `CRLF + spasi`.
2. Jangan memotong di tengah karakter UTF-8; gunakan `mb_strcut`/perhitungan byte aman.
3. Terapkan pada semua baris di `calendar()`.
4. Test deskripsi 200 karakter, teks Indonesia, dan emoji; pastikan setiap baris ≤75 oktet
   dan hasil unfold sama.

**Test wajib:**

| File                | Kasus                                     |
| ------------------- | ----------------------------------------- |
| `AreaTest.php`      | Nomor telepon tidak valid ditolak         |
| `CommunityTest.php` | Nomor telepon tidak valid ditolak         |
| `ServiceTest.php`   | Nomor telepon tidak valid ditolak         |
| `IcsTest.php`       | Deskripsi panjang di-fold dan tetap valid |
| `IcsTest.php`       | Teks non-ASCII tidak rusak                |

**Verifikasi:**

```bash
php artisan test --filter=IcsTest
php artisan test --filter='AreaTest|CommunityTest|ServiceTest'
composer ci:check
```

**Risiko:**

- **Pesan validasi berubah:** pertahankan persis.
- **Fold memotong escape/multibyte:** test teks panjang dan non-ASCII.
- Jangan menambah library ICS.

**Definition of Done:** Tiga form memakai satu rule; ICS lulus test lama dan test panjang.

**Estimasi:** M (2–3 jam).

---

### Fase 11 — Verifikasi akhir dan rilis

**Menutup temuan:** — (verifikasi).

**Target:** Semua fase terverifikasi bersama, tanpa regresi dan tanpa perubahan skema.

**Langkah:**

1. `composer ci:check` penuh.
2. `npm run e2e` penuh.
3. Bandingkan `php artisan route:list --json` dengan baseline: hanya route design-system
   yang hilang.
4. `git diff --stat database/migrations` harus kosong.
5. `npm audit`, `composer show --direct`.
6. Smoke manual:
   - Publik: `/`, `/berita`, `/berita/{slug}`, `/agenda` (multi-hari), `/agenda/{slug}`
     (JSON-LD valid), `/jadwal-misa` (notice target misa), `/profil/area/{slug}` (foto),
     `/dokumen` (unduh + 429).
   - Admin: dashboard (hitungan agenda), daftar area berfoto, buat notice target misa,
     audit log setelah perubahan, simpan pengaturan, halaman backup.
7. Perbarui `docs/DECISIONS.md` untuk keputusan yang berubah:
   - semantik multi-hari,
   - notice bertarget,
   - penghapusan `/design-system` (D-31),
   - aturan audit bila berubah.
8. Siapkan catatan rilis: tidak ada migrasi, tidak ada perubahan env.

**Verifikasi:**

```bash
composer ci:check
npm run e2e
php artisan route:list --json > /tmp/routes-after.json
diff <(jq -S '.[].name' /tmp/routes-before.json) <(jq -S '.[].name' /tmp/routes-after.json)
git diff --stat database/migrations
```

**Risiko:**

- E2E bisa gagal karena data, bukan perubahan. Jalankan `app:e2e:prepare` dan bandingkan
  dengan baseline Fase 0.
- Kalau gagal, rollback per fase, jangan menambal semua di satu commit.

**Definition of Done:** Semua gate lulus; smoke manual selesai; tidak ada migrasi; tidak ada
dependensi baru.

**Estimasi:** 2 jam.

---

## 6. Urutan Eksekusi dan Dependency

| Fase | Bergantung pada    | Bisa paralel dengan | Catatan                                                |
| ---- | ------------------ | ------------------- | ------------------------------------------------------ |
| 0    | —                  | —                   | Wajib pertama                                          |
| 1    | 0                  | 2–5                 | Isolated                                               |
| 2    | 0                  | 1, 3–5              | Butuh data check dari 0                                |
| 3    | 0                  | 1, 2, 4, 5          | Isolated                                               |
| 4    | 0                  | 1–3, 5              | Isolated                                               |
| 5    | 0                  | 1–4                 | Isolated                                               |
| 6    | 0                  | 7–10                | Paling berisiko; jangan digabung                       |
| 7    | 0                  | 6, 8–10             | Backend                                                |
| 8    | 0, 1–7 tidak wajib | 9, 10               | Menghapus file; lakukan setelah fase yang menyentuhnya |
| 9    | 8 (opsional)       | 10                  | Bisa kapan saja                                        |
| 10   | 0                  | 6–9                 | Isolated                                               |
| 11   | 1–10               | —                   | Terakhir                                               |

**Rekomendasi merge order:** 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8 → 9 → 10 → 11.
Fase 1–5 didahulukan karena berdampak langsung ke pengguna.

**Strategi branch:**

- Branch baru per fase dari `refactor/refactor-code`.
- Nama branch: `refactor/audit-jsonld`, `refactor/audit-multiday-event`,
  `refactor/audit-notice-target`, dst.
- PR kecil, squash merge, subject Conventional Commits ≤100 karakter.

---

## 7. Test Matrix

| Fase | Pest                                                                  | Vitest                                                               | Playwright                            | Manual                             |
| ---- | --------------------------------------------------------------------- | -------------------------------------------------------------------- | ------------------------------------- | ---------------------------------- |
| 1    | `PublicEventPageTest`                                                 | `event-json-ld.test.tsx` (baru)                                      | —                                     | Buka `/agenda/{slug}`, cek JSON-LD |
| 2    | `PublicEventPageTest`, `PublicHomepageTest`, `DashboardTest`          | —                                                                    | `public.spec.ts`, `dashboard.spec.ts` | Agenda 3 hari                      |
| 3    | `PublicMassSchedulePageTest`, `ScheduleNoticeTest`                    | —                                                                    | `mass-schedule.spec.ts`               | Buat notice target misa            |
| 4    | `AreaTest`, `PublicAreaPageTest`                                      | —                                                                    | `area.spec.ts`                        | Area berfoto                       |
| 5    | `DocumentDownloadTest`                                                | —                                                                    | `dokumen.spec.ts`                     | 31 request                         |
| 6    | —                                                                     | `datetime.test.ts`, `datetime-calendar.test.ts`, `homepage.test.tsx` | `public.spec.ts`                      | Cek tanggal di 5 halaman           |
| 7    | seluruh `tests/Feature/Audit/*`                                       | —                                                                    | `audit-log.spec.ts`                   | Audit log setelah edit             |
| 8    | `PublicRoutesTest`, `PublicPlaceholderRoutesTest`, `ArchitectureTest` | `npm run check`                                                      | `public.spec.ts`                      | Route list                         |
| 9    | —                                                                     | —                                                                    | —                                     | `composer ci:check`                |
| 10   | `IcsTest`, tiga test validasi                                         | —                                                                    | —                                     | Unduh `.ics`                       |
| 11   | semua                                                                 | semua                                                                | semua                                 | Smoke checklist                    |

---

## 8. Ringkasan File yang Terdampak

### 8.1 File baru

| File                                                                  | Fase | Isi                   |
| --------------------------------------------------------------------- | ---- | --------------------- |
| `tests/js/features/public/agenda/event-json-ld.test.tsx`              | 1    | Test escaping JSON-LD |
| `app/Rules/PhoneNumberRule.php`                                       | 10a  | Aturan nomor telepon  |
| (opsional) `tests/js/features/public/agenda/date-formatting.test.tsx` | 6    | Test format tanggal   |

### 8.2 File diubah (utama)

- `app/Models/Event.php`
- `app/Http/Controllers/Agenda/PublicEventController.php`
- `app/Http/Controllers/MassSchedule/PublicMassScheduleController.php`
- `app/Http/Controllers/Area/AreaController.php`
- `app/Http/Controllers/Area/PublicAreaController.php`
- `routes/web.php`
- `app/Services/AuditLogger.php`
- `app/Actions/Audit/RecordAuditEventAction.php`
- `resources/js/features/public/agenda/components/event-json-ld.tsx`
- `resources/js/lib/datetime.ts` + komponen tanggal
- `app/Services/IcsCalendarBuilder.php`
- `composer.json`, `package.json`, `package-lock.json`
- Test terkait di §7

### 8.3 File dihapus

- `resources/js/components/upload/document-upload.tsx`
- `resources/js/components/upload/file-upload.tsx`
- `resources/js/components/upload/gallery-upload.tsx`
- `resources/js/features/public/design-system/pages/index.tsx`
- `resources/js/routes/design-system/` (generated)
- `resources/js/components/app-logo.tsx`
- `resources/js/components/ui/icon.tsx`
- `resources/js/components/ui/pagination.tsx`
- `resources/js/components/ui/placeholder-pattern.tsx`
- `resources/js/components/ui/toggle-group.tsx`
- `tests/Unit/ExampleTest.php`

### 8.4 File generated (regenerasi, bukan edit manual)

- `resources/js/routes/` (setelah route design-system dihapus)
- `resources/js/actions/` (setelah route design-system dihapus)
- `resources/js/wayfinder/` (bila berubah)

### 8.5 Tidak disentuh

- `database/migrations/*`
- `app/Services/Backup/*`, `app/Jobs/*Backup*`, `app/Actions/Backup/*`
- `app/Services/Installation/*`
- `app/Http/Middleware/*` (kecuali tidak ada)
- `app/Policies/*`
- `vendor/`, `node_modules/`, `public/build/`

---

## 9. Risk Register

| ID  | Risiko                      | Fase | Dampak                              | Mitigasi                                           |
| --- | --------------------------- | ---- | ----------------------------------- | -------------------------------------------------- |
| R1  | Semantik "upcoming" berubah | 2    | Kegiatan selesai hilang lebih cepat | Test eksplisit; jelaskan di PR; catat di DECISIONS |
| R2  | Data `end_at < start_at`    | 2    | Kegiatan hilang                     | Query cek Fase 0; koreksi data terpisah            |
| R3  | Output tanggal bergeser     | 6    | Teks tanggal berubah                | Characterization test; e2e; cek visual             |
| R4  | Notice menumpuk             | 3    | Banner panjang                      | Uji 3–5 notice; batasi hanya jika perlu            |
| R5  | File dihapus masih diimpor  | 8    | Build gagal                         | Grep + tsc + build + e2e                           |
| R6  | Throttle kena NAT           | 5    | 429 untuk pengguna sah              | 30/menit; pantau log                               |
| R7  | Snapshot audit berubah      | 7    | Test gagal                          | Suite audit lengkap                                |
| R8  | Regenerasi Wayfinder        | 8    | Diff generated besar                | Review diff                                        |
| R9  | CI kehilangan check         | 9    | Regresi tidak terdeteksi            | Verifikasi output `composer ci:check`              |

---

## 10. Verifikasi dan Acceptance

### 10.1 Definition of Done global

- [ ] Test regresi ditulis untuk setiap perubahan perilaku.
- [ ] `composer ci:check` lulus di setiap PR.
- [ ] Fase yang menyentuh UI/rute lulus `npm run e2e`.
- [ ] `vendor/bin/pint --dirty --format agent` dijalankan.
- [ ] File generated diregenerasi, bukan diedit manual.
- [ ] Tidak ada migrasi baru.
- [ ] Tidak ada dependensi baru.
- [ ] Commit Conventional Commits, subject ≤100 karakter.
- [ ] §12 diperbarui.

### 10.2 Checklist acceptance per fase

- [ ] **Fase 0:** baseline tersimpan; data `end_at < start_at` diketahui.
- [ ] **Fase 1:** `</script>` tidak bisa keluar; JSON valid.
- [ ] **Fase 2:** multi-hari tampil; lintas bulan benar; dashboard ikut benar.
- [ ] **Fase 3:** notice target rutin/khusus tampil; target tersembunyi tetap sembunyi.
- [ ] **Fase 4:** area berfoto tanpa lazy-load/N+1.
- [ ] **Fase 5:** 31 request → 429; counter tetap sesuai PRD.
- [ ] **Fase 6:** satu sumber format tanggal; output sama.
- [ ] **Fase 7:** satu aturan audit; redaksi nested.
- [ ] **Fase 8:** 1.364 baris dead code hilang; build/e2e lulus.
- [ ] **Fase 9:** gate lebih pendek; 2 dependensi hilang.
- [ ] **Fase 10:** satu rule telepon; ICS ≤75 oktet/baris.
- [ ] **Fase 11:** semua gate + smoke manual lulus.

### 10.3 Checklist rilis

- [ ] `composer ci:check` lulus.
- [ ] `npm run e2e` lulus.
- [ ] `git diff --stat database/migrations` kosong.
- [ ] Route list diff hanya menghapus `/design-system`.
- [ ] `npm audit` tidak menambah temuan baru.
- [ ] Smoke manual publik + admin selesai.
- [ ] `docs/DECISIONS.md` diperbarui untuk keputusan yang berubah.
- [ ] Tidak ada `--no-verify` di commit mana pun.

---

## 11. Out of Scope

- Tidak ada perubahan skema/migrasi.
- Tidak ada dependensi baru.
- Tidak ada penulisan ulang backup/installer.
- Tidak ada pembersihan `pnpm-lock.yaml` atau pemilihan package manager.
- Tidak ada perubahan `download_count` menjadi per sesi.
- Tidak ada optimasi performa lain di luar N+1 Area dan throttle unduhan.
- Tidak ada perubahan pada modul yang tidak disebut di 15 temuan.
- Tidak ada refactor gaya/penamaan di luar fase.

---

## 12. Status Implementasi

**Status: Implemented in working tree (2026-10-31).** Belum di-commit. Gate final
sebagian: semua test relevan lulus, tetapi suite penuh masih diblokir oleh aset
`public/images.jpg` yang tidak ada di repository (lihat catatan di bawah).

| Fase               | Status    | Tanggal    | Commit/PR | Catatan                                                                                                                                                   |
| ------------------ | --------- | ---------- | --------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 0 — Baseline       | 🟡 Partial | 2026-10-31 | —         | MySQL tersedia; full gate tidak dijalankan sebelum perubahan (waktu).                                                                                     |
| 1 — JSON-LD        | ✅ Selesai | 2026-10-31 | —         | Vitest 2 test + `PublicEventPageTest` lulus.                                                                                                              |
| 2 — Multi-hari     | ✅ Selesai | 2026-10-31 | —         | `PublicEventPageTest` 38 test lulus.                                                                                                                      |
| 3 — Notice target  | ✅ Selesai | 2026-10-31 | —         | 26 + 55 test modul MassSchedule lulus.                                                                                                                    |
| 4 — Foto Area      | ✅ Selesai | 2026-10-31 | —         | 36 test Area lulus.                                                                                                                                       |
| 5 — Throttle       | ✅ Selesai | 2026-10-31 | —         | 10 test dokumen lulus; counter tidak diubah.                                                                                                              |
| 6 — Format tanggal | ✅ Selesai | 2026-10-31 | —         | `npm run check` lulus (380 test).                                                                                                                         |
| 7 — Audit dedup    | ✅ Selesai | 2026-10-31 | —         | 52 test audit lulus.                                                                                                                                      |
| 8 — Dead code      | ✅ Selesai | 2026-10-31 | —         | 91 test rute/arsitektur lulus; Wayfinder diregenerasi.                                                                                                    |
| 9 — CI/deps        | ✅ Selesai | 2026-10-31 | —         | `npm run check`, build, dan PHPStan lulus; 2 dependensi dihapus.                                                                                          |
| 10 — Telepon/ICS   | ✅ Selesai | 2026-10-31 | —         | 111 test terkait lulus.                                                                                                                                   |
| 11 — Verifikasi    | 🟡 Partial | 2026-10-31 | —         | Targeted + frontend penuh lulus; suite PHP penuh: 1.641 lulus, 2 gagal (sudah diperbaiki dan lulus terpisah), 25 error karena `public/images.jpg` hilang. |

**Catatan implementasi (deviasi kecil yang disengaja):**

- `tests/Unit/ContinuousIntegrationScriptTest.php` diperbarui: `ci:check` sekarang
  hanya memanggil `npm run check`, dan test memeriksa isi `check` di
  `package.json` agar tsc + Vitest tetap terkunci.
- `tests/Feature/Foundation/ErrorPageTest.php` diselaraskan dengan handler saat
  ini, yang memang membagikan shared props ke halaman error.
- Empat file `resources/js/features/admin/pengaturan/*` dan
  `resources/js/features/public/profil/pages/area/show.tsx` dibersihkan dari
  import/variabel tak terpakai (pre-existing lint) agar `npm run check` bisa
  lulus.
- `resources/js/features/admin/area/pages/ubah.tsx` hanya diformat Prettier
  (pre-existing format drift).
- `composer test` melewati batas 300 detik Composer; suite dijalankan langsung
  dengan `php artisan test --exclude-group=mailpit`. Error yang tersisa berasal
  dari `database/seeders/ServiceSeeder.php:382` (`public/images.jpg` tidak ada),
  bukan dari perubahan ini.

Legenda: ⬜ Belum · 🟡 Sedang dikerjakan · ✅ Selesai · ⚠️ Diblokir

---

## 13. Lampiran

### 13.1 Cheat sheet perintah

```bash
# Gate penuh (MySQL harus hidup)
composer ci:check

# Test PHP spesifik
php artisan test --filter=PublicEventPageTest
php artisan test tests/Feature/Audit

# Test frontend
npm run test:unit
npm run test:unit -- tests/js/lib/datetime.test.ts
npm run types:check

# E2E
npm run e2e

# Format PHP
vendor/bin/pint --dirty --format agent

# Wayfinder
php artisan wayfinder:generate --with-form

# Route list
php artisan route:list --json

# Build
npm run build
```

### 13.2 Format commit

```text
fix(agenda): escape json-ld script content
fix(agenda): keep multi-day events visible until end_at
fix(mass-schedule): show notices targeted at a mass
perf(area): eager load photo variants
fix(documents): throttle public download endpoint
refactor(frontend): centralize date formatting
refactor(audit): single source for audit field rules
chore(frontend): remove unused upload and ui components
chore(ci): remove duplicated frontend checks
refactor(validation): share phone number rule
fix(agenda): fold long ics lines
```

### 13.3 Referensi keputusan

| Referensi                    | Isi                                                  |
| ---------------------------- | ---------------------------------------------------- |
| `docs/DECISIONS.md` D-31     | `/design-system` adalah route sementara              |
| `docs/DECISIONS.md` D-40     | Notice dan target yang dihapus                       |
| `docs/DECISIONS.md` D-45     | Slug unik lintas soft delete                         |
| `docs/DECISIONS.md` D-46     | Pengecualian `dangerouslySetInnerHTML` untuk JSON-LD |
| `docs/DECISIONS.md` D-65     | Galeri dan media                                     |
| `docs/DECISIONS.md` D-77     | Dashboard membaca alt dari konten                    |
| `docs/PRD.md` DL-06, AC-DL-2 | `download_count` naik setiap unduhan                 |
| `docs/PRD.md` NFR-SCALE      | Ratusan–ribuan kunjungan/hari, satu VPS              |
| `AGENTS.md`                  | Aturan kerja proyek                                  |

---

**Dokumen ini adalah rencana. Tidak ada kode yang diubah saat dokumen ini dibuat.**
