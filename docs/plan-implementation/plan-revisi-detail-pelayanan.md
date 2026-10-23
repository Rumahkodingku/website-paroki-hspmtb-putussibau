# Implementation Plan — Revisi Detail Pelayanan (Detail Sakramen)

> **Project:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau  
> **Jenis pekerjaan:** Revisi skema data modul Pelayanan — detail sakramen  
> **Status:** Diimplementasikan — quality gate lulus (Pint, PHPStan, npm check, 1461 tests)  
> **Branch acuan:** `style/update-ui` @ `0e15907`  
> **Spesifikasi sumber:** `docs/revision/revision-service-detail-data.md`  
> **Sumber kebenaran:** `PRD.md` → `ARCHITECTURE.md` → `AGENTS.md` → `DECISIONS.md`  
> **Cakupan:** `services`, `service_requirements`, `service_steps`, `service_faqs`  
> **Pengecualian eksplisit:** tabel `service_categories` tidak diubah.

Dokumen ini menjelaskan _bagaimana_ revisi dikerjakan, dalam urutan apa, file mana
yang tersentuh, dan bagaimana diverifikasinya. Disusun setelah audit codebase
terhadap branch `style/update-ui` dan **lima keputusan yang sudah dijawab pengguna**
melalui tool `question` (direkap di §2). Seluruh keputusan di §2 mengikat.

---

## Daftar Isi

1. [Ringkasan Eksekutif](#1-ringkasan-eksekutif)
2. [Keputusan yang Mengikat](#2-keputusan-yang-mengikat)
3. [Kondisi Codebase Saat Ini](#3-kondisi-codebase-saat-ini)
4. [Arsitektur Target](#4-arsitektur-target)
5. [Implementation Plan](#5-implementation-plan)
6. [Migration & Strategi Data](#6-migration--strategi-data)
7. [File yang Akan Terpengaruh](#7-file-yang-akan-terpengaruh)
8. [Rencana Test](#8-rencana-test)
9. [Risiko, Divergensi, dan Follow-up](#9-risiko-divergensi-dan-follow-up)
10. [Verification Plan](#10-verification-plan)
11. [Acceptance Criteria](#11-acceptance-criteria)
12. [Status](#12-status)

---

## 1. Ringkasan Eksekutif

Halaman publik detail pelayanan/sakramen perlu menyajikan informasi yang lebih
jelas, mudah dipindai, aksesibel, dan membantu umat memahami persyaratan serta
tahapan pelayanan. Revisi ini **menambah kolom data** pada tabel yang sudah ada
tanpa membangun ulang modul, tanpa mengubah `service_categories`, dan tanpa
mengubah primary key, slug, foreign key, unique constraint, atau soft-delete.

Perubahan skema:
- `services`: +`audience_info` (TEXT NULL), +`preparation_info` (TEXT NULL).
- `service_requirements`: +`requirement_condition` (VARCHAR(32), default `required`), +`applies_to` (VARCHAR(100) NULL).
- `service_steps` dan `service_faqs`: **tidak ada kolom baru** pada MVP.

Tujuan UX yang didukung:
- Memisahkan sasaran layanan (`audience_info`) dari ringkasan/pengantar.
- Memisahkan persiapan (`preparation_info`) dari jadwal.
- Membedakan persyaratan wajib (`required`), kondisional (`conditional`), dan
  bila diminta (`upon_request`).
- Menyebut pihak yang dikenai persyaratan (`applies_to`) tanpa enum global yang
  terlalu sempit.
- Menjaga satu sumber kebenaran untuk status publikasi (`is_active`), kontak,
  CTA, media, dan dokumen.

---

## 2. Keputusan yang Mengikat

Dijawab pengguna melalui tool `question` pada sesi perencanaan. Semua keputusan
di bawah mengikat implementasi.

| # | Pertanyaan | Jawaban | Implikasi |
|---|------------|---------|-----------|
| Q1 | Batas panjang `audience_info`/`preparation_info` | **5000 karakter** | Konsisten dengan `schedule_info`/`fee_info` (TEXT). |
| Q2 | Implementasi `requirement_condition` di backend | **Backed enum** | Buat `App\Enums\ServiceRequirementCondition`. |
| Q3 | UI `applies_to` di admin form | **ChoiceSelect + saran** | Condition pakai ChoiceSelect; applies_to pakai input teks bebas + datalist saran (bukan enum, sesuai spec). |
| Q4 | Item seeder dengan note "Bila diminta." | **Set `upon_request`** | Set kondisi sesuai note secara eksplisit pada seeder. |
| Q5 | Scope frontend publik | **Implement sekaligus** | Section "Untuk Siapa?", "Persiapan", badge condition, applies_to. |
| Q6 | Isi `audience_info`/`preparation_info` pada seeder | **Turunkan dari introduction/schedule (heuristik)** | Konten diturunkan dari data existing, ditandai menunggu persetujuan paroki. |
| Q7 | Badge condition di frontend publik | **Hanya conditional/upon_request** | `required` tanpa badge; conditional "Jika berlaku"; upon_request "Bila diminta". |
| Q8 | Nama key map label kondisi di `ServiceFormMeta` | **`conditions`** | Key `conditions` di meta, dipakai admin ChoiceSelect. |

---

## 3. Kondisi Codebase Saat Ini

### 3.1 Tabel existing (tidak diubah strukturnya kecuali kolom baru)

- `service_categories`: `name`, `slug`, `description`, `sort_order`, `is_active`,
  timestamps, soft deletes. **Tidak disentuh.**
- `services`: `title`, `slug`, `service_category_id`, `thumbnail_media_id`,
  `thumbnail_alt`, `summary`, `introduction`, `schedule_info`, `fee_info`,
  `contact_name`, `contact_phone`, `form_url`, `form_button_label`,
  `is_form_open`, `form_closed_message`, `is_emergency`, `is_active`,
  `sort_order`, timestamps, soft deletes.
- `service_requirements`: `service_id`, `text`, `note`, `sort_order`,
  timestamps, index `(service_id, sort_order)`.
- `service_steps`: `service_id`, `title`, `description`, `sort_order`,
  timestamps, index `(service_id, sort_order)`.
- `service_faqs`: `service_id`, `question`, `answer`, `sort_order`,
  timestamps, index `(service_id, sort_order)`.

### 3.2 Model & aturan domain

- `Service` memakai `#[Fillable]`, soft deletes, scope `active()`/`ordered()`,
  route binding slug (active only). Aturan Google Form & kontak:
  `hasFormUrl()`, `usesGoogleForm()`, `isAcceptingRegistrations()`,
  `hasOwnContact()`. **Tidak diubah.**
- `ServiceRequirement`, `ServiceStep`, `ServiceFaq` memakai `#[Fillable]`,
  cast integer `sort_order`, scope `ordered()`. Perubahan child memperbarui
  `services.updated_at` lewat controller/action, bukan `$touches`.
- `is_active` adalah satu-satunya publication flag (D-54).

### 3.3 Form Request

- `ServiceValidationRules` (trait) dipakai `StoreServiceRequest` &
  `UpdateServiceRequest`: rules, messages, attributes, prepare input, sanitize
  HTML (`introduction`).
- `Store/UpdateServiceRequirementRequest`: rules `text` (required string max 255),
  `note` (nullable string max 2000), `sort_order` (nullable int min 0).

### 3.4 Resource

- Admin: `ServiceResource` (semua field + category + documents + document_ids +
  can), `ServiceRequirementResource` (`id`, `text`, `note`, `sort_order`).
- Public: `PublicServiceDetailResource` (field publik + cta + contact +
  requirements/steps/faqs/documents + updated_at + seo),
  `PublicServiceRequirementResource` (`id`, `text`, `note`).

### 3.5 Frontend

- Admin: `resources/js/features/admin/pelayanan/` — `service-form.tsx`,
  `tab-informasi-umum.tsx`, `tab-persyaratan.tsx`, types, form instance.
- Public: `resources/js/features/public/pelayanan/pages/show.tsx`, types.
- Admin `ServiceFormData` (TS) mirror `ServiceResource`.
- Public `PublicServiceDetail`/`PublicServiceRequirement` (TS) mirror resource.

### 3.6 Seeder & factory

- `ServiceFactory`: default inactive, tanpa form; states `active()`,
  `withForm()`, `formClosed()`, `emergency()`.
- `ServiceRequirementFactory`: `text`, `note`, `sort_order`.
- `ServiceSeeder`: 7 layanan aktif dengan konten lengkap (baptis, krisma, ekaristi,
  rekonsiliasi, perkawinan, imamat, pengurapan orang sakit); requirement item
  dengan note "Bila diminta." pada baptis.

### 3.7 Test existing

- `ServiceSchemaTest`, `ServiceRequirementTest`, `PublicServicePageTest`,
  `ServiceSeederTest`, `ServiceTest`, `ServiceAuthorizationTest`,
  `ServiceStepTest`, `ServiceFaqTest`, `ServiceContactFallbackTest`,
  `ServiceCategoryTest`.
- Pest auto `RefreshDatabase`; test role wajib `seedRolesAndPermissions()`.

---

## 4. Arsitektur Target

### 4.1 Skema target

```text
services (existing + 2 kolom baru)
  audience_info      TEXT NULL
  preparation_info   TEXT NULL

service_requirements (existing + 2 kolom baru)
  requirement_condition  VARCHAR(32) NOT NULL DEFAULT 'required'
  applies_to             VARCHAR(100) NULL
```

`service_steps` dan `service_faqs` tidak berubah.

### 4.2 Enum target

```text
App\Enums\ServiceRequirementCondition (string-backed)
  Required     = 'required'
  Conditional  = 'conditional'
  UponRequest  = 'upon_request'
```

### 4.3 Kontrak data target (delta)

```ts
// Admin
type ServiceDetail = {
  // ...existing
  audience_info: string | null;
  preparation_info: string | null;
};

type ServiceRequirementRow = {
  // ...existing
  requirement_condition: 'required' | 'conditional' | 'upon_request';
  applies_to: string | null;
};

// Public
type PublicServiceDetail = {
  // ...existing
  audience_info: string | null;
  preparation_info: string | null;
};

type PublicServiceRequirement = {
  // ...existing
  requirement_condition: 'required' | 'conditional' | 'upon_request';
  applies_to: string | null;
};
```

### 4.4 Perilaku UI target

- Publik:
  - Section "Untuk Siapa?" hanya jika `audience_info` tidak null/kosong.
  - Section "Persiapan" hanya jika `preparation_info` tidak null/kosong.
  - Requirement: badge "Jika berlaku"/"Bila diminta" hanya untuk conditional/upon_request;
    `applies_to` ditampilkan sebagai label kecil hanya jika ada; `note` di bawah teks.
  - Steps dengan `description = null` tetap rapi; `fee_info = null` tidak dirender "Gratis".
- Admin:
  - Tab Informasi Umum: 2 textarea (Sasaran Layanan, Persiapan).
  - Dialog persyaratan: ChoiceSelect kondisi (Wajib/Jika berlaku/Bila diminta) +
    input applies_to (teks bebas + datalist saran).
- Validasi backend otoritatif; frontend hanya UX.

---

## 5. Implementation Plan

### 5.1 Urutan eksekusi (dengan checkpoint)

1. Audit migration/model/request/resource/type/factory/seeder/working tree. ✅
2. Pastikan tidak ada kolom duplikat/perubahan lokal. ✅
3. Tambah migration baru hanya pada `services` dan `service_requirements`.
4. Update model, PHPDoc, `#[Fillable]`.
5. Update Form Request + pesan validasi ID.
6. Update admin form + helper text + penyimpanan.
7. Update Resource/controller/Inertia props + TypeScript types.
8. Update halaman publik detail.
9. Update factory + seeder.
10. Tambah coverage migration, request validation, props, conditional UI.
11. Regresi Pelayanan, media, Download, pengaturan situs, halaman publik.
12. Lint, format, type check, build, test (script aktual).
13. Perbarui dokumentasi setelah verifikasi.

Checkpoint: setelah migration+model lulus → admin form; setelah kontrak props stabil → UI publik.

### 5.2 Langkah detail per lapisan

#### A. Database

- Buat migration baru (timestamp berikutnya tersedia: `2026_10_16_000000`).
- `up()`: tambah 4 kolom seperti §4.1.
- `down()`: drop 4 kolom.
- Jangan sentuh `service_categories` di `up()`/`down()`.
- Jangan tambah index tanpa query yang membutuhkan.

#### B. Enum

- Buat `app/Enums/ServiceRequirementCondition.php`.
- Label via lang `pelayanan.requirement_conditions.*`.

#### C. Model

- `Service`: PHPDoc + Fillable tambah `audience_info`, `preparation_info`.
- `ServiceRequirement`: PHPDoc + Fillable tambah `requirement_condition`, `applies_to`.

#### D. Validasi

- `ServiceValidationRules`:
  - Rules: `audience_info` nullable string max 5000; `preparation_info` nullable string max 5000.
  - `prepareServiceInput()`: tambah kedua field ke list trim + empty→null.
  - Messages & attributes ID.
- `Store/UpdateServiceRequirementRequest`:
  - `prepareForValidation()`: default condition `required` jika kosong; trim applies_to (empty→null).
  - Rules: condition `Rule::enum(ServiceRequirementCondition::class)`; applies_to nullable string max 100.
  - Messages ID.

#### E. Resource

- Admin `ServiceResource`: +`audience_info`, `preparation_info`.
- Admin `ServiceRequirementResource`: +`requirement_condition`, `applies_to`.
- Public `PublicServiceDetailResource`: +`audience_info`, `preparation_info`.
- Public `PublicServiceRequirementResource`: +`requirement_condition`, `applies_to`.

#### F. Factory & Seeder

- `ServiceFactory`: default null untuk 2 field baru.
- `ServiceRequirementFactory`: default condition `required`, applies_to null.
- `ServiceSeeder`:
  - Tambah `audience_info` & `preparation_info` per layanan (diturunkan dari
    `introduction`/`schedule_info` existing).
  - Requirement arrays: tambah `requirement_condition` (`upon_request` untuk item
    note "Bila diminta.", `required` lainnya) dan `applies_to` (null default).
  - Loop pembuatan requirement pass field baru.

#### G. Admin frontend

- `types.ts`:
  - `ServiceDetail` +2 field.
  - `ServiceFormData` +2 field.
  - `ServiceRequirementRow` +2 field.
  - `ServiceFormMeta` +`conditions: Record<string,string>`.
- `ServiceFormMeta`: kirim `conditions` dari lang.
- `service-form.tsx`: emptyData/toFormData + field baru; teruskan `conditions` ke
  `TabPersyaratan`.
- `tab-informasi-umum.tsx`: 2 textarea (Sasaran Layanan, Persiapan) dengan
  label/hint/error.
- `tab-persyaratan.tsx`: dialog tambah condition ChoiceSelect + applies_to input
  (teks bebas + datalist saran); kirim field baru pada store/update.

#### H. Public frontend

- `types.ts`:
  - `PublicServiceDetail` +2 field.
  - `PublicServiceRequirement` +2 field + type `RequirementCondition`.
- `show.tsx`:
  - Section "Untuk Siapa?" jika `audience_info` ada.
  - Section "Persiapan" jika `preparation_info` ada.
  - Requirement list: badge condition (hanya conditional/upon_request) + label
    applies_to (jika ada).

#### I. Lang

- `lang/id/pelayanan.php`:
  - labels: `audience_info`, `preparation_info`, `requirement_condition`,
    `applies_to`.
  - hints: `audience_info`, `preparation_info`, `applies_to`.
  - `conditions` map: required/conditional/upon_request → Wajib/Jika berlaku/Bila diminta.
  - saran applies_to (datalist): Orang tua, Wali baptis, Calon baptis dewasa,
    Calon penerima sakramen, Keluarga, Umum.

---

## 6. Migration & Strategi Data

### 6.1 Migration baru (konseptual)

```php
Schema::table('services', function (Blueprint $table) {
    $table->text('audience_info')->nullable();
    $table->text('preparation_info')->nullable();
});

Schema::table('service_requirements', function (Blueprint $table) {
    $table->string('requirement_condition', 32)->default('required');
    $table->string('applies_to', 100)->nullable();
});
```

- `down()`: drop 4 kolom.
- Tidak menyentuh `service_categories`.
- Tidak tambah index.
- Urutan kolom bukan kontrak API; `after()` hanya jika konvensi project.

### 6.2 Backfill

- `requirement_condition` default `required` → data lama valid.
- `applies_to`, `audience_info`, `preparation_info` nullable → tidak menebak.
- Admin melengkapi metadata secara bertahap.
- Tidak mengaktifkan layanan nonaktif.
- Tidak mengubah kategori yang sudah terhubung.

---

## 7. File yang Akan Terpengaruh

### Backend (PHP)

| File | Perubahan |
|------|-----------|
| `database/migrations/2026_10_16_000000_*.php` | Baru |
| `app/Enums/ServiceRequirementCondition.php` | Baru |
| `app/Models/Service.php` | PHPDoc + Fillable |
| `app/Models/ServiceRequirement.php` | PHPDoc + Fillable |
| `app/Concerns/ServiceValidationRules.php` | Rules + prepare + messages/attributes |
| `app/Http/Requests/Pelayanan/StoreServiceRequirementRequest.php` | Default condition + applies_to |
| `app/Http/Requests/Pelayanan/UpdateServiceRequirementRequest.php` | Default condition + applies_to |
| `app/Http/Resources/Pelayanan/ServiceResource.php` | +2 field |
| `app/Http/Resources/Pelayanan/ServiceRequirementResource.php` | +2 field |
| `app/Http/Resources/Public/Pelayanan/PublicServiceDetailResource.php` | +2 field |
| `app/Http/Resources/Public/Pelayanan/PublicServiceRequirementResource.php` | +2 field |
| `database/factories/ServiceFactory.php` | Default null |
| `database/factories/ServiceRequirementFactory.php` | Default condition/applies_to |
| `database/seeders/ServiceSeeder.php` | Field baru + condition |

### Frontend (TS/TSX)

| File | Perubahan |
|------|-----------|
| `resources/js/features/admin/pelayanan/types.ts` | Types + FormMeta |
| `resources/js/features/admin/pelayanan/components/service-form.tsx` | emptyData/toFormData + pass conditions |
| `resources/js/features/admin/pelayanan/components/tab-informasi-umum.tsx` | 2 textarea |
| `resources/js/features/admin/pelayanan/components/tab-persyaratan.tsx` | Condition + applies_to |
| `resources/js/features/public/pelayanan/types.ts` | Types |
| `resources/js/features/public/pelayanan/pages/show.tsx` | Sections + badge |

### Lang

| File | Perubahan |
|------|-----------|
| `lang/id/pelayanan.php` | Labels/hints/conditions/saran |

### Tests

| File | Perubahan |
|------|-----------|
| `tests/Feature/Pelayanan/ServiceSchemaTest.php` | Assertion kolom baru |
| `tests/Feature/Pelayanan/ServiceRequirementTest.php` | Validasi condition/applies_to |
| `tests/Feature/Pelayanan/PublicServicePageTest.php` | Props baru (opsional) |

---

## 8. Rencana Test

### 8.1 Database/migration
- Migration berhasil pada DB MySQL test kosong.
- Upgrade pada DB yang sudah menjalankan migration existing.
- Rollback hanya menghapus kolom yang dibuat migration baru.
- `service_categories` tidak disentuh.
- Foreign key, soft deletes, unique slug, media relation, pivot dokumen tidak berubah.

### 8.2 Backend/validasi
- Service create/update menerima `audience_info`/`preparation_info` nullable.
- Requirement create/update menerima condition & applies_to valid.
- Condition tidak dikenal ditolak server.
- Input kosong/null dinormalisasi konsisten.
- Urutan requirements/steps/FAQs tetap deterministik.
- Perubahan child memperbarui `services.updated_at`.
- Aturan CTA, emergency, publikasi tidak regresi.
- Layanan nonaktif/soft-deleted tidak dapat diakses route publik.

### 8.3 Frontend/admin
- Form admin bisa create/edit/reload field baru.
- Section sasaran/persiapan disembunyikan saat kosong.
- Kondisi ditampilkan dengan label benar.
- applies_to hanya ditampilkan jika ada.
- Steps description null tetap rapi.
- fee_info null & dokumen kosong tidak menghasilkan section kosong.
- CTA terbuka/tertutup/tanpa form/emergency tetap berfungsi.
- Layout desktop/tablet/mobile, keyboard focus, label a11y, print mode, kontras dasar diuji.

### 8.4 Quality gate
- `composer ci:check` (npm check → types:check → test:unit → Pint → PHPStan → `php artisan test`).
- MySQL harus running.

---

## 9. Risiko, Divergensi, dan Follow-up

- **Konten seeder**: audience_info/preparation_info diturunkan dari introduction/schedule_info
  (heuristik yang disetujui pengguna). Konten ini **menunggu persetujuan paroki**
  sebelum dipublikasikan; section publik tetap disembunyikan bila kosong/null.
- **Requirement condition item "Bila diminta."**: diset `upon_request` sesuai note;
  item lain `required`. Admin/paroki tetap perlu verifikasi final.
- **applies_to free text**: validasi panjang/teks saja; tidak enum global. Admin
  bertanggung jawab konsistensi label.
- **TS typecheck**: pastikan `tsconfig.json` mencakup file test yang ditambah.
- **Generated files**: tidak ada perubahan route; Wayfinder tidak perlu regenerate.
- **service_categories**: tidak boleh disentuh; verifikasi di migration & test.

### Di luar scope (tidak diimplementasikan)

- Perubahan apa pun pada `service_categories`.
- Tabel pendaftaran/penyimpanan data calon penerima sakramen.
- Kalender jadwal terstruktur/booking.
- Tabel kontak multipihak.
- URL tindakan pada `service_steps`.
- Kategori/featured flag pada FAQ.
- Field hero image tambahan.
- Kolom `is_published`, `cta_state`, `whatsapp_url`, `tel_url`, `thumbnail_url`,
  atau field turunan lain.
- Perubahan modul Download/media yang tidak diperlukan.
- Perubahan isi teologis/persyaratan/prosedur/biaya/jadwal/kontak/URL formulir
  tanpa validasi paroki (kecuali heuristik yang disetujui).
- Refactor modul di luar kebutuhan integrasi.

---

## 10. Verification Plan

- Jalankan `php artisan migrate` pada DB test kosong.
- Jalankan `php artisan migrate:rollback` (hanya 4 kolom terhapus).
- Jalankan `composer ci:check` setelah implementasi.
- Verifikasi PHPStan level 7 tidak menemukan error baru.
- Verifikasi `npm run types:check` dan `npm run test:unit`.
- Manual: buka admin form create/edit, tambah persyaratan dengan condition &
  applies_to, reload, cek tersimpan; buka halaman publik, cek section/badge.

---

## 11. Acceptance Criteria

Mengacu revisi §11. Ringkas:

- [ ] Migration berhasil upgrade & rollback; `service_categories` tidak berubah.
- [ ] Foreign key, soft deletes, unique slug, media relation, pivot dokumen tidak berubah.
- [ ] Service create/update menerima `audience_info`/`preparation_info` nullable.
- [ ] Requirement create/update menerima condition & applies_to valid; condition
      tidak dikenal ditolak.
- [ ] Input kosong/null dinormalisasi konsisten.
- [ ] Urutan requirements/steps/FAQs deterministik.
- [ ] Perubahan child memperbarui `services.updated_at`.
- [ ] Aturan CTA, emergency, publikasi tidak regresi.
- [ ] Layanan nonaktif/soft-deleted tidak dapat diakses route publik.
- [ ] Form admin bisa create/edit/reload field baru.
- [ ] Section sasaran/persiapan disembunyikan saat kosong.
- [ ] Kondisi ditampilkan dengan label benar; applies_to hanya jika ada.
- [ ] Steps description null tetap rapi; fee_info null & dokumen kosong tidak
      menghasilkan section kosong.
- [ ] CTA terbuka/tertutup/tanpa form/emergency berfungsi.
- [ ] Layout desktop/tablet/mobile, keyboard focus, label a11y, print mode,
      kontras dasar diuji.
- [ ] Lint, format, type check, build, test menggunakan script aktual; hasil
      riil dilaporkan.

---

## 12. Status

**Diimplementasikan.** Semua langkah plan selesai; quality gate lulus.
Hasil implementasi akan dilaporkan dengan format "Service Detail Data Revision
Report" (revisi §14) setelah verifikasi selesai.
