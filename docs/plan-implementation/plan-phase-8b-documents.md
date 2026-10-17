# Implementation Plan — PHASE 8B

> **Project:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau\
> **Phase:** 8B — Dokumen (Documents)\
> **Status:** Implemented — branch `feat/phase-8b-documents`\
> **Branch (usulan):** `feat/phase-8b-documents`\
> **Spesifikasi:** `docs/roadmap/phase-8b-documents.md`\
> **Sumber kebenaran:** `PRD.md` → `ARCHITECTURE.md` → `DESIGN.md` → `AGENTS.md` → `DECISIONS.md`

Dokumen roadmap Phase 8B menjelaskan _apa_ yang harus ada. Dokumen ini menjelaskan
_bagaimana_ perubahannya, dalam urutan apa, file mana yang tersentuh, dan bagaimana
diverifikasinya. Dokumen ini adalah pelengkap roadmap, bukan pengganti.

> Dokumen ini disusun setelah audit codebase terhadap kondisi repository saat ini
> dan **13 keputusan yang sudah dijawab pengguna** melalui tool `question`.
> Seluruh keputusan di §2.2 mengikat implementasi. Belum ada satu baris kode
> implementasi Phase 8B yang ditulis.

---

## Daftar Isi

1. [Kondisi Codebase Saat Ini](#1-kondisi-codebase-saat-ini)
2. [Task Phase 8B yang Akan Dikerjakan](#2-task-phase-8b-yang-akan-dikerjakan)
3. [Implementation Plan](#3-implementation-plan)
4. [File yang Akan Terpengaruh](#4-file-yang-akan-terpengaruh)
5. [Dependency / Configuration Impact](#5-dependency--configuration-impact)
6. [Risiko dan Potensi Konflik](#6-risiko-dan-potensi-konflik)
7. [Verification Plan](#7-verification-plan)
8. [Acceptance Criteria yang Akan Terpenuhi](#8-acceptance-criteria-yang-akan-terpenuhi)
9. [Pertanyaan / Ambiguity](#9-pertanyaan--ambiguity)
10. [Status](#10-status)

---

## 1. Kondisi Codebase Saat Ini

Audit dilakukan pada repository saat ini (`git status` bersih; hanya
`docs/roadmap/phase-8b-documents.md` yang belum dilacak). Tidak ada file yang
diubah saat audit.

### 1.1 Fondasi yang sudah ada dan dapat dipakai ulang

| Area | Kondisi aktual | File |
| --- | --- | --- |
| Routing publik | `/download` masih `Route::inertia(...)`; `/download/{id}/unduh` masih closure placeholder | `routes/web.php:102-111` |
| Halaman publik Download | Masih placeholder `[ISI: Download]` | `resources/js/features/public/download/pages/index.tsx` |
| Route admin | Belum ada route Dokumen; `/admin` sudah memakai `auth`, `verified`, `EnsureAccountIsActive`, `EnsureAdminAccess` | `routes/web.php` |
| Pola CRUD admin | Sudah matang: `SortsAdminList`, `DataTable`, `ListToolbar`, `DeleteDialog`, `RowActions`, `Field`, `FormInput`, `FormActions`, toast flash | `app/Concerns/SortsAdminList.php`, `resources/js/components/*` |
| Pola modul terbaru | Galeri/Komunitas/Pelayanan memakai FormRequest, Policy, Resource, Action untuk operasi multi-langkah | `app/Http/Controllers/Galeri/*`, `app/Http/Controllers/Komunitas/*`, `app/Http/Controllers/Pelayanan/*` |
| Media pipeline | Hanya JPEG/PNG, 3 MB, disk `local` untuk original dan `public` untuk varian; **tidak cocok untuk PDF/dokumen** | `config/media.php`, `app/Http/Controllers/Media/MediaController.php` |
| Storage | Disk `local` = `storage/app/private`; file privat hanya dapat diakses lewat signed URL, bukan URL publik langsung | `config/filesystems.php` |
| Upload UI | `components/upload/document-upload.tsx` ada, tetapi `MediaUploadField` varian `document` masih menampilkan “Belum tersedia” | `resources/js/components/media-upload-field.tsx` |
| Permission | 85 permission; belum ada `documents.*` atau `documentcategories.*` | `database/seeders/PermissionSeeder.php`, `docs/RBAC.md` |
| Shared props | `name`, `auth.user`, `locale`, `displayTimezone`, `sidebarOpen`, `seo`, `media` | `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/types/index.ts` |
| SEO publik | `Seo` + tag server-side di Blade; SSR OFF | `resources/js/components/seo.tsx`, `resources/views/app.blade.php` |
| Navigasi | Navbar/footer masih menamai “Download” dan mengarah ke `/download`; sidebar admin “Download” masih nonaktif | `parish-navbar.tsx`, `parish-footer.tsx`, `app-sidebar.tsx` |
| Pelayanan | `Service` belum punya relasi dokumen; `document_service` belum ada; tab Dokumen Terkait belum ada | `app/Models/Service.php`, `database/migrations/2026_10_14_000000_create_service_tables.php`, `service-form.tsx` |

### 1.2 Yang belum ada dan menjadi pekerjaan utama Phase 8B

- Tabel `document_categories`, `documents`, `document_service`.
- Model `Document`, `DocumentCategory`, enum status dokumen.
- Permission, Policy, FormRequest, Action, Service storage dokumen.
- Route admin `/admin/dokumen` dan kategori di Master Data.
- Route publik `/dokumen`, endpoint unduh `/dokumen/{document}/unduh`, pratinjau PDF.
- Halaman admin daftar/form/kategori, halaman publik daftar/filter/pencarian.
- Integrasi dokumen ke Pelayanan.
- Redirect route lama `/download` dan `/admin/download`.
- Seluruh test dan pembaruan dokumentasi.

### 1.3 Fakta yang mengikat desain

- **Eloquent strict mode aktif** di luar production (`Model::shouldBeStrict`): semua relasi yang dirender wajib eager load.
- **`withCount()` lalu `select()` menghilangkan count** (D-41); urutan query harus dijaga.
- **`Route::redirect()` wajib diberi nama** agar Wayfinder tidak menghasilkan dua export.
- **Wayfinder adalah sumber URL frontend**; tidak boleh hard-code URL aplikasi.
- **`public/storage` hanya untuk gambar**; dokumen tidak boleh disimpan di disk `public`.
- **PHP saat ini `upload_max_filesize=2M`, `post_max_size=8M`**, sedangkan PRD meminta 10 MB. Ini prasyarat environment yang harus dinaikkan agar upload 10 MB benar-benar berfungsi.
- **`git status` tidak menunjukkan perubahan kode**; plan ini belum dieksekusi.

---

## 2. Task Phase 8B yang Akan Dikerjakan

### 2.1 Scope yang disetujui

Phase 8B dikerjakan **seluruhnya untuk modul Dokumen**, dengan tambahan integrasi dokumen ke Pelayanan yang memang ditunda dari Phase 6B (D-54).

**Termasuk:**

| Task | Isi |
| --- | --- |
| 8B-01 | Schema `document_categories`, `documents`, `document_service` |
| 8B-02 | Enum status `draft/published/archived`, model, factory, scope publik |
| 8B-03 | Permission `documents.*`, `documentcategories.*`, Policy, FormRequest |
| 8B-04 | Private storage, validasi MIME/ekstensi, upload, replace, cleanup, soft delete + hapus file |
| 8B-05 | Admin CRUD Dokumen: list, search, filter kategori/status, create, edit, archive, delete |
| 8B-06 | Admin kategori Dokumen di `/admin/master-data/kategori-dokumen` |
| 8B-07 | Halaman publik `/dokumen`: daftar, filter kategori, pencarian, unggulan, pagination, empty/error state |
| 8B-08 | Endpoint unduh terkontrol, counter unduhan, pratinjau PDF admin + publik Terbit |
| 8B-09 | Integrasi `document_service`: selector di form Pelayanan + “Dokumen Terkait” di detail layanan |
| 8B-10 | Redirect route lama `/download`, `/download/{id}/unduh`, `/admin/download`, `/admin/kategori-download` |
| 8B-11 | Navigasi, label Dokumen, SEO, aksesibilitas, responsif |
| 8B-12 | Test Pest/Vitest/Playwright, lint/type/build, dokumentasi |

**Tidak termasuk:**

- Galeri (Phase 8A) dan Kontak (Phase 8C).
- Dokumen rahasia internal/berbasis login.
- Versioning historis kompleks, approval workflow, OCR, konversi format, editor PDF.
- Media library generik baru.
- Redesign homepage/navbar/footer/design system.
- Role/permission management UI.

### 2.2 Keputusan produk yang sudah dikonfirmasi

| # | Keputusan |
| --- | --- |
| 1 | Integrasi dokumen↔layanan **dikerjakan**: pivot `document_service`, selector di form Pelayanan, bagian “Dokumen Terkait” di detail layanan |
| 2 | Status memakai **enum `draft`, `published`, `archived`**; `published_at` diisi sekali saat pertama terbit |
| 3 | Format mengikuti **PRD lengkap**: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP; maks **10 MB** |
| 4 | P1 yang masuk: **arsip, versi/tanggal, pencarian publik, redirect lama, pratinjau PDF, dokumen unggulan, peringatan `expires_at`** |
| 5 | Hapus dokumen: **soft delete baris + hapus file fisik**; tidak ada restore UI |
| 6 | Seed **5 kategori PRD**: Formulir Pelayanan, Pedoman, Warta Paroki, Liturgi, Lainnya |
| 7 | Admin di `/admin/dokumen`; kategori di `/admin/master-data/kategori-dokumen` |
| 8 | Nama file unduhan = **slug judul + ekstensi kanonik**; counter hanya bertambah pada unduhan `attachment`, bukan pratinjau inline |
| 9 | Pratinjau: **admin semua status**, publik hanya dokumen Terbit, PDF saja |
| 10 | `expires_at` hanya **peringatan**; dokumen tetap tampil dan dapat diunduh |
| 11 | Unggulan tampil sebagai **seksi terpisah + badge** |
| 12 | Relasi layanan diatur dari **form Pelayanan** |
| 13 | Kategori nonaktif: dokumen Terbit tetap tampil; kategori tidak muncul di filter dan tidak dapat dipilih untuk dokumen baru |

---

## 3. Implementation Plan

Urutan di bawah disusun berdasarkan dependency. Jangan melompati langkah.

### 8B.0 — Keputusan dan dokumentasi awal

1. Catat seluruh keputusan di atas sebagai **D-66** di `docs/DECISIONS.md`.
2. Catat divergensi schema dari PRD §9.11:
   - PRD memakai `is_published`; implementasi memakai enum `status`.
   - Tidak ada kolom `slug` karena tidak ada halaman detail dokumen.
   - File memakai pipeline terpisah dari Media.
3. Perbarui `docs/RBAC.md` setelah permission ditambahkan.
4. Tentukan prasyarat environment: `upload_max_filesize >= 10M`, `post_max_size >= 12M`, `ext-zip` tersedia.

### 8B.1 — Domain dan persistence

1. **Migration** `2026_10_20_000000_create_document_tables.php`
   - `document_categories`
     - `id`
     - `name` string(100)
     - `slug` string(100) unique
     - `description` text nullable
     - `sort_order` unsigned integer default 0
     - `is_active` boolean default true
     - `timestamps`
     - index `sort_order`, `is_active`
   - `documents`
     - `id`
     - `document_category_id` FK `restrictOnDelete`
     - `title` string(180)
     - `description` text nullable
     - `file_disk` string(50)
     - `file_path` string(500)
     - `original_filename` string(255)
     - `mime_type` string(150)
     - `file_size` unsigned big integer
     - `version_label` string(100) nullable
     - `document_date` date nullable
     - `expires_at` date nullable
     - `status` enum `draft/published/archived`, default `draft`
     - `is_featured` boolean default false
     - `download_count` unsigned big integer default 0
     - `published_at` timestamp nullable
     - `uploaded_by` FK `users` nullable `nullOnDelete`
     - `updated_by` FK `users` nullable `nullOnDelete`
     - `timestamps`, `softDeletes`
     - Index: `(status, published_at)`, `(document_category_id, status)`, `is_featured`, `expires_at`
   - `document_service`
     - `document_id` FK `documents` `cascadeOnDelete`
     - `service_id` FK `services` `cascadeOnDelete`
     - composite primary key `(document_id, service_id)`
   - Tidak ada `active_marker`; tidak ada unique slug dokumen; kategori tidak soft delete.
2. **Enum** `App\Enums\DocumentStatus`
   - `Draft`, `Published`, `Archived`
   - `values()`, `label()` dari `lang/id/documents.php`
3. **Model** `Document`
   - `#[Fillable]` untuk metadata; **`download_count` tidak fillable**
   - `SoftDeletes`
   - Cast: `status` enum, `is_featured` boolean, `document_date`/`expires_at` date, `published_at` `immutable_datetime`, `file_size`/`download_count` integer
   - Relasi: `category()`, `uploadedBy()`, `updatedBy()`, `services()` `belongsToMany`
   - Scope: `published()` (`status = published`), `featured()`, `ordered()` deterministik
   - Helper: `isExpired()`, `isPdf()`, `extension()`, `downloadFilename()`
4. **Model** `DocumentCategory`
   - Relasi `documents()`
   - Scope `active()`, `ordered()`
5. **Factory** `DocumentCategoryFactory`, `DocumentFactory`
   - State `draft()`, `published()`, `archived()`, `featured()`, `expired()`
   - Factory tidak menulis file secara default; test yang membutuhkan file membuatnya di `Storage::fake('local')`.
6. **Seeder** `DocumentCategorySeeder`
   - `updateOrCreate`/`firstOrCreate` keyed on slug
   - 5 kategori PRD
   - Daftarkan di `DatabaseSeeder`
7. **Test schema/domain**
   - tabel dan kolom ada
   - FK dan unique constraint
   - scope publik tidak mengembalikan draft/archived
   - `published_at` dan status
   - soft delete
   - kategori tidak dapat dihapus jika masih dipakai

### 8B.2 — Permission, Policy, dan authorization

1. Tambah 8 permission ke `PermissionSeeder`:
   - `documents.view`, `documents.create`, `documents.update`, `documents.delete`
   - `documentcategories.view`, `documentcategories.create`, `documentcategories.update`, `documentcategories.delete`
2. Tidak ada `documents.publish`/`documents.archive`; publikasi dan arsip adalah field `status` pada update, mengikuti pola D-36/D-65.
3. Buat `DocumentPolicy`:
   - `viewAny`, `view`, `create`, `update`, `delete`
4. Buat `DocumentCategoryPolicy`:
   - `viewAny`, `create`, `update`, `delete`
5. Semua route admin tetap di grup middleware yang sudah ada.
6. FormRequest `authorize()` menjadi satu-satunya titik otorisasi untuk `store`/`update`; `Gate::authorize()` untuk `index`, `edit`, `destroy`, `preview`.
7. Test:
   - guest dialihkan ke login
   - user tanpa role ditolak 403
   - mutasi manual ditolak backend
   - permission count dan nama di `RbacFoundationTest`
8. Perbarui `docs/RBAC.md` dari 85 menjadi 93 permission.

### 8B.3 — Storage, validasi file, upload, replace, delete

1. **Config** `config/documents.php`
   - `disk` = `env('DOCUMENTS_DISK', 'local')`
   - `path` = `documents`
   - `max_upload_kilobytes` = 10240
   - `mime_types` = daftar MIME yang diizinkan
   - `extensions` = map MIME kanonik → ekstensi
   - `ooxml` = daftar ekstensi OOXML dan entry ZIP yang wajib ada
   - `preview_mime` = `application/pdf`
2. **Rule** `App\Rules\AllowedDocumentFile`
   - Membaca MIME dari bytes (`getMimeType()`), bukan client MIME
   - Menolak ekstensi yang tidak ada di allowlist
   - Menolak ketidakcocokan ekstensi–MIME
   - PDF: `application/pdf`
   - DOC/XLS/PPT legacy: MIME Office yang sesuai
   - DOCX/XLSX/PPTX: deteksi `application/zip` + validasi isi ZIP:
     - `[Content_Types].xml`
     - `word/document.xml` untuk DOCX
     - `xl/workbook.xml` untuk XLSX
     - `ppt/presentation.xml` untuk PPTX
   - ZIP biasa: `application/zip` atau `application/x-zip-compressed`, ekstensi `.zip`
   - Menolak `.php` yang dinamai `.pdf`, SVG, file kosong, file > 10 MB
   - Pesan error Bahasa Indonesia
3. **Service** `App\Services\DocumentStorage`
   - `store(UploadedFile): array{file_disk,file_path,original_filename,mime_type,file_size}`
   - Nama fisik: UUID + ekstensi dari MIME kanonik, bukan nama klien
   - `delete(string $disk, string $path): void`
   - `exists(Document): bool`
   - `extensionFor(string $mimeType): string`
   - `downloadFilename(Document): string`
   - Tidak membaca `Request`
4. **Action** `SaveDocumentAction`
   - Alur:
     1. Validasi sudah dilakukan FormRequest.
     2. Jika ada file baru, simpan ke private disk lebih dulu.
     3. Buka transaksi database: isi metadata, `uploaded_by`/`updated_by`, set `published_at` sekali saat pertama published.
     4. Jika transaksi gagal, hapus file baru yang belum direferensikan.
     5. Setelah commit, jika replace dan path lama berbeda, hapus file lama hanya jika tidak direferensikan baris lain.
   - Metadata-only update tidak menyentuh file.
5. **Action** `DeleteDocumentAction`
   - Soft delete baris + lepas pivot `document_service` dalam transaksi.
   - Setelah commit, hapus file fisik.
   - Jika file gagal dihapus, log; baris tetap soft-deleted.
6. **FormRequest**
   - `StoreDocumentRequest`, `UpdateDocumentRequest`
   - File: `required` saat create, `nullable` saat edit
   - `max:10240`
   - `AllowedDocumentFile`
   - `document_category_id` wajib, aktif untuk pilihan baru; kategori lama yang nonaktif tetap boleh dipertahankan
   - `title` wajib max 180
   - `version_label` nullable max 100
   - `document_date`, `expires_at` nullable date
   - `expires_at >= document_date` jika keduanya diisi
   - `status` enum
   - `is_featured` boolean
   - `prepareForValidation` trim, null untuk field opsional, cast boolean
   - `published_at` tidak pernah diterima dari request
7. **Test**
   - PDF valid diterima
   - DOCX valid diterima (dibuat dengan `ZipArchive` di test)
   - file > 10 MB ditolak
   - `.php` bernama `.pdf` ditolak
   - SVG ditolak
   - MIME/ukuran tersimpan dari deteksi server
   - replace file memperbarui referensi dan tidak menghapus file lama sebelum commit
   - kegagalan database menghapus file baru
   - delete menghapus file dan melepas pivot
   - file hilang saat unduh ditangani 404 + log

### 8B.4 — Admin Dokumen

1. **Routes admin** di `routes/web.php`:
   - `GET /admin/dokumen` → `documents.index` (langsung daftar, bukan redirect)
   - `GET /admin/dokumen/tambah` → `documents.create`
   - `POST /admin/dokumen` → `documents.store`
   - `GET /admin/dokumen/{document}/ubah` → `documents.edit`
   - `PATCH /admin/dokumen/{document}` → `documents.update`
   - `DELETE /admin/dokumen/{document}` → `documents.destroy`
   - `GET /admin/dokumen/{document}/pratinjau` → `documents.preview`
2. **Controller** `DocumentController`
   - `index`: search judul, filter kategori, filter status, sort whitelist (`title`, `status`, `document_date`, `published_at`, `download_count`, `updated_at`), pagination 15, `withQueryString`
   - `create`: kategori aktif + meta upload
   - `edit`: resource + kategori aktif/current
   - `store`/`update`: panggil `SaveDocumentAction`
   - `destroy`: panggil `DeleteDocumentAction`
   - `preview`: `Gate::authorize('view', $document)`, hanya PDF, `inline`, tanpa counter
3. **Resource admin**
   - `DocumentListResource`: id, title, category, format/extension, file_size, version_label, document_date, status, is_featured, is_expired, download_count, updated_at, `can`
   - `DocumentResource`: field form + metadata file yang aman (tanpa `file_path`/`file_disk`) + `can`
4. **Kategori Dokumen**
   - Route master data:
     - `GET /admin/master-data/kategori-dokumen` → `document-categories.index`
     - `GET .../tambah` → `document-categories.create`
     - `POST ...` → `document-categories.store`
     - `GET .../{document_category}/ubah` → `document-categories.edit`
     - `PATCH ...` → `document-categories.update`
     - `DELETE ...` → `document-categories.destroy`
   - Controller `MasterData\DocumentCategoryController`
   - Tolak delete jika masih dipakai dokumen, termasuk soft-deleted (`withTrashed()->count()`), untuk menghindari FK 500
   - Kategori nonaktif tidak muncul sebagai pilihan baru
5. **Halaman admin** `features/admin/dokumen/pages/`
   - `index.tsx`: `DataTable`, pencarian, filter kategori/status, badge status, badge unggulan, peringatan kedaluwarsa, jumlah unduhan, tombol pratinjau/ubah/hapus
   - `tambah.tsx`, `ubah.tsx`: form metadata + file
   - Empty state dengan CTA “Tambah Dokumen”
   - Dialog hapus dengan konsekuensi “tautan ke layanan akan dilepas”
6. **Form** `document-form.tsx`
   - Field: judul, kategori, deskripsi, file, versi, tanggal dokumen, berlaku sampai, status, unggulan
   - `noValidate`
   - File wajib saat create; saat edit menampilkan nama file lama dan file pengganti opsional
   - Label tombol menyesuaikan status: “Simpan Draf”, “Terbitkan”, “Simpan Perubahan”
   - File picker memakai `MediaUploadField` varian `document` yang diaktifkan, dengan batas dari shared prop `documentUploads`
7. **Halaman kategori** `features/admin/master-data/pages/kategori-dokumen/`
   - `index.tsx`, `tambah.tsx`, `ubah.tsx`, `components/document-category-form.tsx`
   - Mengikuti pola Kategori Berita/Agenda/Pelayanan
8. **Sidebar admin**
   - Ganti item nonaktif “Download” menjadi link “Dokumen” ke `documents.index`
   - Tambah “Kategori Dokumen” di grup Master Data
9. **Test**
   - admin list/create/edit/delete
   - filter status/kategori
   - validasi file
   - update metadata tanpa file
   - replace file
   - kategori tidak dapat dihapus saat dipakai
   - unknown sort key diabaikan
   - `withCount` tidak hilang

### 8B.5 — Halaman publik dan unduhan

1. **Routes publik**
   - `GET /dokumen` → `dokumen.index`
   - `GET /dokumen/{document}/unduh` → `dokumen.unduh`
   - Pratinjau publik memakai route unduh yang sama dengan `?inline=1`
2. **Controller** `PublicDocumentController`
   - `index`:
     - query `Document::published()->with('category')`
     - filter kategori slug; kategori nonaktif tidak muncul di daftar filter, tetapi dokumen yang memakainya tetap tampil
     - pencarian judul/deskripsi dengan `LIKE`/binding
     - unggulan: query `published()->featured()` terpisah, limit 3
     - daftar utama: `ordered()` by `published_at desc`, `document_date desc`, `id desc`
     - pagination 12, `withQueryString`
   - `download`:
     1. load `Document` by id
     2. `abort_unless($document->status === Published, 404)`
     3. cek file ada di disk
     4. tentukan `inline` dari query `inline=1`
     5. jika `inline` dan bukan PDF → 404
     6. nama unduhan = `Str::slug(title)` + ekstensi kanonik; fallback `dokumen-{id}`
     7. increment `download_count` atomik hanya untuk attachment
     8. kirim `Storage::disk(...)->download(...)` atau `response(..., 'inline')`
     9. header `X-Content-Type-Options: nosniff`, `Cache-Control: private, no-store`
     10. file hilang → log + 404
3. **Resource publik**
   - `PublicDocumentCardResource`: id, title, description, category, format/extension, file_size, version_label, document_date, is_featured, is_expired, published_at/updated_at
   - **Tidak pernah** mengirim `file_path`, `file_disk`, absolute path, signed URL, atau `original_filename`
   - `PublicDocumentCategoryResource`: id, name, slug
4. **Halaman publik** `features/public/dokumen/pages/index.tsx`
   - `Seo` title “Dokumen Paroki”
   - heading utama deskriptif
   - deskripsi singkat
   - seksi “Dokumen Unggulan” + badge
   - pencarian dan filter kategori di query string
   - kartu dokumen: ikon/format, judul, kategori, deskripsi, ukuran, tanggal, versi, badge kedaluwarsa
   - tombol “Unduh Dokumen” dengan label yang menyebut judul
   - tombol “Pratinjau PDF” hanya untuk PDF
   - pagination, empty state, loading/error state
   - responsif dan keyboard-operable
5. **Navbar dan footer publik**
   - Ganti label “Download” menjadi “Dokumen”
   - Arahkan ke `/dokumen`
6. **Redirect route lama**
   - `301 /download` → `/dokumen`
   - `301 /download/{id}/unduh` → `/dokumen/{id}/unduh`
   - `301 /admin/download` → `/admin/dokumen`
   - `301 /admin/kategori-download` → `/admin/master-data/kategori-dokumen`
   - Setiap redirect **wajib diberi nama** `legacy.*`
7. **Test**
   - publik hanya melihat dokumen Terbit
   - draft/archived 404 pada daftar, filter, pencarian, dan unduh
   - pencarian dan filter kategori
   - unggulan tampil di seksi terpisah
   - kedaluwarsa tetap tampil dan dapat diunduh
   - props publik tidak memuat field internal
   - counter bertambah pada attachment
   - inline preview tidak menambah counter
   - file hilang → 404
   - header aman
   - redirect lama 301

### 8B.6 — Integrasi dokumen ↔ Pelayanan

1. **Model** `Service`
   - Tambah `documents(): BelongsToMany`
2. **Validasi Pelayanan**
   - Tambah `document_ids` nullable array
   - Setiap id harus ada; untuk **pilihan baru** hanya dokumen `published`
   - Dokumen yang sudah terhubung tetapi kemudian archived boleh tetap terlihat di form dengan badge “Tidak terbit”, tetapi tidak ditampilkan publik
3. **Penyimpanan**
   - Karena menulis `services` + pivot, gunakan `SaveServiceAction` atau transaksi di controller
   - Kontrol akses tetap `services.update`; tidak menambah permission pivot
4. **Admin Pelayanan**
   - Tambah tab “Dokumen Terkait” pada form edit
   - Daftar checkbox/multi-select dokumen Terbit
   - Simpan bersama form Pelayanan
5. **Publik Pelayanan**
   - `PublicServiceController` eager load dokumen `published()`
   - `PublicServiceDetailResource` mengirim `documents` hanya published
   - Halaman detail Pelayanan menampilkan seksi “Dokumen Terkait” dengan tombol unduh
   - Jika tidak ada dokumen, seksi tidak dirender
6. **Test**
   - dokumen Terbit dapat ditautkan
   - dokumen draft/archived tidak dapat ditautkan baru
   - dokumen archived yang sudah tertaut tidak tampil publik
   - detail layanan menampilkan dokumen terkait
   - authorization tetap `services.update`
   - seeder/service tanpa dokumen tetap valid

### 8B.7 — Hardening, test, dan dokumentasi

1. Update seluruh test yang terdampak route/placeholder/nav.
2. Jalankan `wayfinder:generate` dan periksa generated files.
3. Jalankan Pint, PHPStan, Vitest, TypeScript, build, E2E.
4. Perbarui:
   - `docs/DECISIONS.md` D-66
   - `docs/RBAC.md` (93 permission)
   - `docs/roadmap/README.md` (Phase 8B selesai)
   - `AGENTS.md` bagian known gaps/status fase
5. Laporkan hasil perintah secara jujur; jangan menandai selesai jika E2E tidak dijalankan.

---

## 4. File yang Akan Terpengaruh

### 4.1 Dibuat — backend

| File | Alasan |
| --- | --- |
| `database/migrations/2026_10_20_000000_create_document_tables.php` | Tiga tabel Dokumen + pivot |
| `app/Enums/DocumentStatus.php` | Enum draft/published/archived |
| `app/Models/Document.php` | Model dokumen, scope publik, helper file |
| `app/Models/DocumentCategory.php` | Model kategori |
| `database/factories/DocumentFactory.php` | Factory + state status |
| `database/factories/DocumentCategoryFactory.php` | Factory kategori |
| `database/seeders/DocumentCategorySeeder.php` | Seed 5 kategori PRD |
| `app/Policies/DocumentPolicy.php` | Otorisasi dokumen |
| `app/Policies/DocumentCategoryPolicy.php` | Otorisasi kategori |
| `app/Concerns/DocumentValidationRules.php` | Rules/messages/normalisasi dokumen |
| `app/Concerns/DocumentCategoryValidationRules.php` | Rules kategori |
| `app/Concerns/DocumentFormMeta.php` | Label/hint/copy form admin |
| `app/Rules/AllowedDocumentFile.php` | Validasi MIME/ekstensi/OOXML |
| `app/Services/DocumentStorage.php` | Simpan/hapus file private, nama aman |
| `app/Actions/Document/SaveDocumentAction.php` | Transaksi metadata + file + replace |
| `app/Actions/Document/DeleteDocumentAction.php` | Soft delete + lepas pivot + hapus file |
| `app/Http/Requests/Document/StoreDocumentRequest.php` | Validasi create |
| `app/Http/Requests/Document/UpdateDocumentRequest.php` | Validasi update |
| `app/Http/Requests/Document/StoreDocumentCategoryRequest.php` | Validasi kategori |
| `app/Http/Requests/Document/UpdateDocumentCategoryRequest.php` | Validasi kategori |
| `app/Http/Controllers/Document/DocumentController.php` | Admin CRUD + preview |
| `app/Http/Controllers/Document/PublicDocumentController.php` | Publik list + download |
| `app/Http/Controllers/MasterData/DocumentCategoryController.php` | Kategori master data |
| `app/Http/Resources/Document/DocumentListResource.php` | Props list admin |
| `app/Http/Resources/Document/DocumentResource.php` | Props form admin |
| `app/Http/Resources/Document/DocumentCategoryResource.php` | Props kategori admin |
| `app/Http/Resources/Public/Document/PublicDocumentCardResource.php` | Props kartu publik |
| `app/Http/Resources/Public/Document/PublicDocumentCategoryResource.php` | Props filter kategori |
| `config/documents.php` | Disk, path, MIME, limit, ekstensi |
| `lang/id/documents.php` | Label, pesan validasi, flash, copy |
| `app/Actions/Pelayanan/SaveServiceAction.php` (jika belum ada) | Simpan service + pivot dokumen dalam transaksi |

### 4.2 Dibuat — frontend

| File | Alasan |
| --- | --- |
| `resources/js/features/admin/dokumen/pages/index.tsx` | Daftar dokumen admin |
| `resources/js/features/admin/dokumen/pages/tambah.tsx` | Form tambah |
| `resources/js/features/admin/dokumen/pages/ubah.tsx` | Form edit |
| `resources/js/features/admin/dokumen/components/document-form.tsx` | Form bersama create/edit |
| `resources/js/features/admin/dokumen/components/document-status-badge.tsx` | Badge Draf/Terbit/Arsip |
| `resources/js/features/admin/dokumen/types.ts` | Type props/admin |
| `resources/js/features/admin/master-data/pages/kategori-dokumen/index.tsx` | Daftar kategori |
| `resources/js/features/admin/master-data/pages/kategori-dokumen/tambah.tsx` | Tambah kategori |
| `resources/js/features/admin/master-data/pages/kategori-dokumen/ubah.tsx` | Ubah kategori |
| `resources/js/features/admin/master-data/pages/kategori-dokumen/components/document-category-form.tsx` | Form kategori |
| `resources/js/features/admin/master-data/types/document-category.ts` | Type kategori |
| `resources/js/features/admin/pelayanan/components/tab-dokumen-terkait.tsx` | Selector dokumen di form Pelayanan |
| `resources/js/features/public/dokumen/pages/index.tsx` | Halaman `/dokumen` |
| `resources/js/features/public/dokumen/components/document-card.tsx` | Kartu dokumen publik |
| `resources/js/features/public/dokumen/types.ts` | Type props publik |

### 4.3 Dibuat — test

| File | Fokus |
| --- | --- |
| `tests/Feature/Document/DocumentSchemaTest.php` | Tabel, kolom, FK, index, scope, soft delete |
| `tests/Feature/Document/DocumentTest.php` | CRUD admin, status, published_at, filter/sort |
| `tests/Feature/Document/DocumentCategoryTest.php` | CRUD kategori + larangan hapus saat dipakai |
| `tests/Feature/Document/DocumentAuthorizationTest.php` | Guest/user tanpa role/mutasi manual |
| `tests/Feature/Document/DocumentDownloadTest.php` | Hanya published, counter, inline, file hilang, header |
| `tests/Feature/Document/PublicDocumentPageTest.php` | List, search, filter, unggulan, props aman, empty state |
| `tests/Feature/Document/DocumentRedirectTest.php` | Redirect lama 301 + tidak ada route name ganda |
| `tests/Feature/Document/DocumentServiceLinkTest.php` | Pivot, selector, public service section |
| `tests/js/features/admin/dokumen/document-form.test.tsx` | Field wajib, file opsional saat edit, status |
| `tests/js/features/public/dokumen/document-card.test.tsx` | Label unduh, format/ukuran, badge |
| `tests/e2e/dokumen.spec.ts` | Alur admin + publik + unduh + arsip + hapus |

### 4.4 Diubah

| File | Perubahan |
| --- | --- |
| `routes/web.php` | Route admin/publik Dokumen, kategori, redirect legacy; hapus closure placeholder download |
| `config/inertia.php` | Tambah path `admin/dokumen/pages`; ganti path `public/download/pages` → `public/dokumen/pages` |
| `app/Http/Middleware/HandleInertiaRequests.php` | Shared prop `documentUploads` |
| `resources/js/types/index.ts` | Type `DocumentUploadLimits` + `SharedProps` |
| `tests/js/support/inertia.tsx` | Nilai asli `documentUploads` |
| `resources/js/components/media-upload-field.tsx` | Aktifkan varian `document` sebagai file picker terkontrol |
| `resources/js/layouts/app/components/app-sidebar.tsx` | Link Dokumen + Kategori Dokumen |
| `resources/js/layouts/public/components/parish-navbar.tsx` | Label/href Dokumen |
| `resources/js/layouts/public/components/parish-footer.tsx` | Label/href Dokumen |
| `database/seeders/PermissionSeeder.php` | 8 permission baru |
| `database/seeders/DatabaseSeeder.php` | Panggil `DocumentCategorySeeder` |
| `app/Models/Service.php` | Relasi `documents()` |
| `app/Http/Controllers/Pelayanan/ServiceController.php` | Load dokumen + simpan pivot |
| `app/Concerns/ServiceValidationRules.php` | Rule `document_ids` |
| `app/Http/Requests/Pelayanan/StoreServiceRequest.php` | Payload `document_ids` |
| `app/Http/Requests/Pelayanan/UpdateServiceRequest.php` | Payload `document_ids` |
| `app/Http/Resources/Pelayanan/ServiceResource.php` | Field `document_ids`/dokumen terpilih |
| `app/Http/Controllers/Pelayanan/PublicServiceController.php` | Eager load dokumen published |
| `app/Http/Resources/Public/Pelayanan/PublicServiceDetailResource.php` | Props `documents` |
| `resources/js/features/admin/pelayanan/components/service-form.tsx` | Tab `dokumen` + `document_ids` |
| `resources/js/features/admin/pelayanan/types.ts` | `ServiceFormData.document_ids`, `ServiceDetail.documents` |
| `resources/js/features/public/pelayanan/pages/show.tsx` | Seksi “Dokumen Terkait” |
| `resources/js/features/public/pelayanan/types.ts` | Type dokumen terkait |
| `lang/id/pelayanan.php` | Copy tab dokumen |
| `tests/Feature/Public/PublicRoutesTest.php` | `/download` → `/dokumen`; placeholder set |
| `tests/Feature/Public/PublicPlaceholderRoutesTest.php` | Hapus test placeholder download; pin route name baru |
| `tests/Feature/Foundation/RbacFoundationTest.php` | Daftar permission baru |
| `tests/js/components/app-sidebar.test.tsx` | Dokumen link + kategori |
| `tests/js/components/parish-navbar.test.tsx` | Label/href Dokumen |
| `tests/js/components/parish-footer.test.tsx` | Label/href Dokumen |
| `tests/js/components/media-upload-field.test.tsx` | Varian `document` sekarang punya file input |
| `tests/e2e/public.spec.ts` | Menu “Dokumen” |
| `tests/Feature/Pelayanan/*` | Dokumen terkait/tab baru |
| `docs/DECISIONS.md` | D-66 |
| `docs/RBAC.md` | 93 permission |
| `docs/roadmap/README.md` | Status Phase 8B |
| `AGENTS.md` | Known gaps/status |
| `.env.example` | `DOCUMENTS_DISK=local` bila config memakai env |

### 4.5 Dihapus / diganti

| File/Route | Alasan |
| --- | --- |
| `resources/js/features/public/download/pages/index.tsx` | Diganti `features/public/dokumen/pages/index.tsx` |
| Closure `/download/{id}/unduh` di `routes/web.php` | Diganti controller unduh sungguhan |
| `Route::inertia('/download', 'public/download')` | Diganti route `/dokumen` |
| Route name `download.index`/`download.unduh` | Diganti `dokumen.index`/`dokumen.unduh`; legacy redirect memakai `legacy.*` |
| Generated `resources/js/routes/download/*` | Dibuat ulang oleh Wayfinder; jangan diedit tangan |

---

## 5. Dependency / Configuration Impact

### 5.1 Tidak ada dependency baru

- Tidak menambah package Composer/npm.
- Tidak memakai `intervention/image` atau library PDF.
- Upload dokumen memakai `Illuminate\Http\UploadedFile`, `Illuminate\Support\Facades\Storage`, dan `finfo`.
- OOXML memakai `ZipArchive`; **`ext-zip` sudah tersedia di mesin ini** dan perlu dipastikan tersedia di production. Bila deployment belum menjamin, tambahkan `ext-zip` sebagai platform requirement di `composer.json` (bukan instalasi package).

### 5.2 Config baru

`config/documents.php`:

- `disk` default `local` (private)
- `path` `documents`
- `max_upload_kilobytes` `10240`
- `mime_types`
- `extensions`
- `ooxml`
- `preview_mime`

Tidak mengubah `config/media.php`.

### 5.3 Shared prop baru

`documentUploads`:

```text
mimeTypes: string[]
extensions: string[]
maxUploadKilobytes: int
```

Wajib ditambahkan ke:

- `HandleInertiaRequests::share()`
- `resources/js/types/index.ts`
- `tests/js/support/inertia.tsx`

Tanpa pembaruan test helper, semua component test yang memakai `renderWithInertia` akan gagal.

### 5.4 Permission dan RBAC

- 85 → 93 permission.
- `PermissionSeeder::expectedCount()` otomatis menyesuaikan.
- `RbacFoundationTest` daftar eksplisit harus diperbarui.
- Production: `Gate::before` sudah memberi super_admin semua ability; tidak perlu attach permission ke role.

### 5.5 Route dan Wayfinder

- Route name publik: `dokumen.index`, `dokumen.unduh`.
- Route name admin: `documents.*`, `document-categories.*`.
- Legacy: `legacy.download.index`, `legacy.download.unduh`, `legacy.download.admin`, `legacy.download-categories`.
- Setelah perubahan route wajib:
  - `php artisan wayfinder:generate --with-form`
  - periksa generated module tidak memiliki export ganda.
- Jangan hard-code `/dokumen` di komponen; gunakan `@/routes/dokumen` dan `@/routes/documents`.

### 5.6 PHP / server

Prasyarat agar fitur 10 MB benar-benar berfungsi:

- `upload_max_filesize >= 10M`
- `post_max_size >= 12M` (idealnya 16M)
- `max_file_uploads >= 1`
- `fileinfo` aktif
- `zip` aktif
- Nginx/Apache `client_max_body_size`/`LimitRequestBody` bila ada

Saat ini `php -r` menunjukkan **2M / 8M**. Ini harus dinaikkan di `php.ini`/host; `.env` tidak dapat mengubahnya. Bila tidak dinaikkan, upload 10 MB akan ditolak sebelum Laravel sempat memvalidasi.

### 5.7 Storage

- Dokumen: disk `local` (private), path `documents/{uuid}.{ext}`.
- Tidak ada symlink `public/storage` untuk dokumen.
- `storage:link` tetap diperlukan untuk gambar/media.
- Jangan pernah mengirim signed URL dokumen ke props publik.
- Hapus file hanya setelah commit/soft delete berhasil; replace menghapus file lama setelah commit.

### 5.8 Database

- Migration baru harus dijalankan di database dev dan test.
- Test memakai `Storage::fake('local')`; jangan mengandalkan file asli di `storage/app/private`.
- Tidak perlu mengubah migration yang sudah ada.

---

## 6. Risiko dan Potensi Konflik

| # | Risiko/Konflik | Dampak | Mitigasi |
| --- | --- | --- | --- |
| 1 | PRD §9.11 memakai `is_published`; plan memakai enum `status` | Divergensi schema PRD | Catat D-66; PRD §9 mengizinkan penyesuaian tipe selama perilaku terpenuhi. Test memastikan draft/archived tidak publik |
| 2 | PRD DL-01 mengizinkan 8 format; roadmap 8B menyarankan PDF saja | Cakupan validasi lebih besar | Ikuti PRD; validasi OOXML dengan isi ZIP; test DOCX valid dan file palsu |
| 3 | `upload_max_filesize=2M`/`post_max_size=8M` saat ini | Upload 10 MB gagal sebelum Laravel | Prasyarat environment; verifikasi `php -i`; dokumentasikan di DECISIONS/AGENTS |
| 4 | PRD DL-04 nama file = slug judul + ekstensi; roadmap menyebut nama asli | Nama unduhan berbeda dari harapan admin | Ikuti PRD; `original_filename` tetap disimpan untuk audit/tampilan |
| 5 | `document_service` adalah dependency Phase 8 yang menyentuh modul Pelayanan | Perubahan di luar folder Dokumen | Sudah disetujui; batasi perubahan pada relasi, selector, resource, dan seksi publik Pelayanan |
| 6 | Route `/download` dan nama route `download.*` diganti | Navbar/footer/test/E2E/Wayfinder rusak bila tidak diperbarui | Redirect 301 bernama; update semua referensi; `wayfinder:generate`; test redirect |
| 7 | `MediaUploadField` varian `document` sebelumnya sengaja tanpa file input | Test lama justru mengunci perilaku placeholder | Ubah test secara sengaja; dokumentasikan bahwa Phase 8B mengisi seam D-63 |
| 8 | Shared prop baru `documentUploads` | Semua test komponen bisa gagal bila helper tidak diperbarui | Update `tests/js/support/inertia.tsx` pada perubahan yang sama |
| 9 | Rename folder page `public/download` → `public/dokumen` | `config/inertia.php`, PageChunk, dan ArchitectureTest bisa gagal | Update path config, hapus page lama, jalankan ArchitectureTest |
| 10 | Sidebar dan navbar berubah | Test sidebar/navbar/footer/E2E gagal | Update keempat test dan `public.spec.ts` |
| 11 | Eloquent strict mode + relasi kategori/dokumen | Lazy load melempar 500 | Eager load `category`, `documents`, `services`; jangan `select()` setelah `withCount()` |
| 12 | Soft delete dokumen + FK kategori `restrict` | Hapus kategori bisa 500 karena dokumen soft-deleted | Hitung `documents()->withTrashed()` sebelum delete; tolak dengan pesan |
| 13 | File yatim saat replace/delete gagal | Storage menumpuk | Simpan file baru dulu; hapus file lama setelah commit; cleanup file baru bila transaksi gagal |
| 14 | File publik bocor lewat URL langsung | Draf/arsip dapat diakses | Private disk; route unduh memeriksa status; test `missing('file_path')`; test URL storage tidak tersedia |
| 15 | Header injection melalui nama file | Respons tidak aman | Nama unduhan dari `Str::slug(title)` + ekstensi; `Storage::download` memakai `makeDisposition`; test judul aneh |
| 16 | Counter unduhan dianggap analytics | Counter bisa membengkak karena preview/bot | Hitung hanya attachment; dokumentasikan definisi; test inline tidak menambah |
| 17 | Kategori nonaktif | Dokumen bisa “hilang” tanpa disadari | Dokumen tetap tampil; kategori hanya hilang dari filter/pilihan baru; test |
| 18 | `npm run check:fix` pernah menghapus isi file | Kehilangan kode | Jangan pakai `check:fix`; gunakan `npm run check` dan periksa `git diff` |
| 19 | E2E memakai `public/build` | Perubahan UI tidak teruji bila build lama | `npm run build` sebelum `npm run e2e` |
| 20 | Stray `php artisan serve` di port 8000 | Semua login E2E gagal | Cek `ss -ltnp | grep 8000` sebelum E2E; `npm run e2e` |
| 21 | OOXML terdeteksi `application/zip` oleh finfo | DOCX valid bisa ditolak | Rule khusus memeriksa isi ZIP, bukan hanya MIME mentah; test DOCX nyata |
| 22 | `ext-zip` tidak tersedia di production | Validasi OOXML gagal | Verifikasi `php -m`; tambahkan platform requirement bila perlu |
| 23 | Kategori yang sudah nonaktif tetapi dipakai dokumen | Form edit bisa memaksa pindah kategori | Sertakan kategori saat ini sebagai opsi pada edit; test |

---

## 7. Verification Plan

### 7.1 Pemeriksaan sebelum coding

```bash
git status
php -m | grep -i -E 'fileinfo|zip'
php -r 'echo ini_get("upload_max_filesize")." / ".ini_get("post_max_size").PHP_EOL;'
php artisan route:list --path=download
php artisan route:list --path=dokumen
```

Pastikan:

- `php -m` memuat `fileinfo` dan `zip`.
- Upload limit host sudah/akan dinaikkan ke minimal 10M/12M.
- Tidak ada proses `php artisan serve` liar di port 8000.

### 7.2 Setelah migration dan route

```bash
php artisan migrate
php artisan wayfinder:generate --with-form
php artisan route:list --path=dokumen
php artisan route:list --path=admin/dokumen
php artisan route:list --path=download
```

Yang diperiksa:

- Semua route name unik.
- Legacy redirect 301 bernama `legacy.*`.
- Tidak ada route name `download.index`/`download.unduh` yang masih dipakai kode.
- Generated Wayfinder tidak memiliki export ganda.

### 7.3 Backend

```bash
php artisan test --compact tests/Feature/Document
php artisan test --compact tests/Feature/Pelayanan
php artisan test --compact tests/Feature/Public
php artisan test --compact tests/Feature/Foundation/RbacFoundationTest.php
```

Fokus assertion:

- Hanya `published` yang muncul di publik dan endpoint unduh.
- Draft/archived 404.
- Counter hanya bertambah pada attachment.
- Inline preview tidak menambah counter.
- File hilang → 404.
- Header `Content-Disposition`, `Content-Type`, `X-Content-Type-Options`.
- `file_path`/`file_disk` tidak ada di props publik.
- Kategori tidak dapat dihapus saat dipakai.
- Pivot dokumen-layanan dan filter published.
- Redirect lama 301.
- Permission dan policy.

### 7.4 Frontend

```bash
npm run types:check
npm run check
npm run test:unit
```

Jangan memakai `npm run check:fix` tanpa memeriksa `git diff`, karena pernah menghapus isi file.

Test Vitest minimal:

- Form dokumen menampilkan field wajib dan error validasi.
- File pengganti opsional saat edit.
- Status Draf/Terbit/Arsip punya label jelas.
- Dialog hapus dapat dibuka/dibatalkan.
- Kartu publik menampilkan format, ukuran, tanggal, versi, badge kedaluwarsa.
- Tombol unduh punya label yang menyebut judul.
- Empty state dan filter.
- Varian `document` pada `MediaUploadField` merender file input dan mengirim file yang dipilih.

### 7.5 Build dan E2E

```bash
npm run build
npm run e2e
```

Alur E2E `tests/e2e/dokumen.spec.ts`:

1. Login admin.
2. Buka menu Dokumen.
3. Buat kategori.
4. Buat dokumen Draf dengan file PDF.
5. Pastikan dokumen tidak tampil di `/dokumen` dan endpoint unduh 404.
6. Terbitkan dokumen.
7. Buka `/dokumen`, cari/filter, unduh PDF.
8. Pastikan counter unduhan bertambah.
9. Edit metadata tanpa mengganti file.
10. Ganti file.
11. Pratinjau PDF.
12. Arsipkan, pastikan hilang dari publik dan unduh 404.
13. Hapus dokumen, pastikan hilang dari admin dan file tidak tersedia.
14. Tautkan dokumen ke layanan dan pastikan muncul di detail layanan.

Catatan:

- Jalankan `npm run build` lebih dulu karena E2E memakai `public/build`.
- Jalankan `npm run e2e` (bukan `npx playwright test`) agar `app:e2e:prepare` membersihkan database test.
- Pastikan port 8000 tidak dipakai server lama.

### 7.6 Gate penuh

```bash
vendor/bin/pint --dirty --format agent
composer test
```

atau:

```bash
composer ci:check
```

`composer test` mencakup Pint test, PHPStan, dan Pest. `composer ci:check` mencakup lint TS, typecheck, Vitest, dan Pest.

### 7.7 Pemeriksaan keamanan manual

- `curl -I /storage/documents/...` tidak boleh mengembalikan file; path dokumen tidak berada di `public`.
- Buka props Inertia halaman publik dan pastikan tidak ada `file_path`, `file_disk`, `original_filename`, atau signed URL.
- Coba akses `/dokumen/{id}/unduh` untuk dokumen draft/archived → 404.
- Coba akses `/download` dan `/download/{id}/unduh` → 301 ke URL baru.
- Cek file yang dihapus benar-benar hilang dari `storage/app/private/documents`.
- Cek `download_count` tidak berubah saat pratinjau inline.
- Cek keyboard: filter, pencarian, tombol unduh, dialog hapus.
- Cek responsif 360/768/1280 px.

---

## 8. Acceptance Criteria yang Akan Terpenuhi

### 8.1 Roadmap Phase 8B §14

- [ ] Nama fitur dan navigasi menggunakan **Dokumen**.
- [ ] Halaman publik utama `/dokumen`.
- [ ] Route admin dilindungi middleware dan authorization.
- [ ] Admin dapat membuat, melihat, mengubah, dan menghapus metadata dokumen.
- [ ] Admin dapat mengunggah file valid dan mengganti file dengan aman.
- [ ] Kategori dokumen berfungsi.
- [ ] Status Draf/Terbit/Arsip berfungsi.
- [ ] Hanya dokumen Terbit tampil publik.
- [ ] Endpoint unduh memeriksa status dan memakai private storage.
- [ ] Draf/Arsip tidak dapat diakses lewat URL langsung/endpoint publik.
- [ ] Validasi tipe dan ukuran file di server.
- [ ] Metadata storage tidak bocor ke props publik.
- [ ] Pencarian/filter berfungsi.
- [ ] UI memiliki loading, empty, validation, error, success state.
- [ ] Responsif dan keyboard-operable.
- [ ] Tautan lama `/download` ditangani.
- [ ] Test backend/frontend/E2E ditambahkan.
- [ ] Lint/type/build/test dijalankan dan dilaporkan.
- [ ] Tidak ada perubahan Galeri/Kontak.

### 8.2 PRD Download

- [ ] **DL-01**: PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, ZIP; maks 10 MB.
- [ ] **DL-02**: MIME divalidasi server; ekstensi–MIME mismatch ditolak.
- [ ] **DL-03**: file di disk non-publik; unduhan lewat controller; pratinjau inline terkontrol.
- [ ] **DL-04**: nama file unduhan = slug judul + ekstensi asli/kanonik.
- [ ] **DL-05**: dokumen draf 404.
- [ ] **DL-06**: `download_count` bertambah atomik setiap unduhan attachment.
- [ ] **DL-07**: replace memperbarui `file_path`, `file_name`/`original_filename`, `mime_type`, `file_size`, `updated_at`; ID dan URL unduh tetap; file lama dihapus.
- [ ] **DL-08**: `Content-Disposition` aman dan `X-Content-Type-Options: nosniff`.
- [ ] **AC-DL-1**: dokumen dapat diunduh dengan nama benar.
- [ ] **AC-DL-2**: counter bertambah.
- [ ] **AC-DL-3**: tipe tidak diizinkan ditolak dengan pesan jelas.
- [ ] **AC-DL-4**: dokumen terkait tampil di halaman layanan.
- [ ] **AC-DL-5**: `.php` berekstensi `.pdf` ditolak.
- [ ] **AC-DL-6**: URL langsung storage tidak dapat mengakses berkas.

### 8.3 Fitur prioritas roadmap

- **DOC-01** daftar admin
- **DOC-02** pencarian admin
- **DOC-03** filter kategori/status
- **DOC-04** tambah dokumen
- **DOC-05** edit metadata
- **DOC-06** ganti berkas
- **DOC-07** hapus dengan konfirmasi
- **DOC-08** kelola kategori
- **DOC-09** draf/terbit
- **DOC-10** arsip
- **DOC-11** pratinjau
- **DOC-12** validasi tipe/ukuran
- **DOC-13** deteksi metadata file
- **DOC-14** daftar publik
- **DOC-15** filter kategori publik
- **DOC-16** pencarian publik
- **DOC-17** unduh terkontrol
- **DOC-18** format/ukuran
- **DOC-19** jumlah unduhan
- **DOC-20** dokumen unggulan
- **DOC-21** responsif
- **DOC-22** peringatan masa berlaku
- **DOC-23** kontrol akses file
- **DOC-24** validasi status unduhan
- **DOC-25** lifecycle file
- **DOC-26** redirect route lama

### 8.4 Integrasi Phase 6B

- [ ] **6B-071**: relasi many-to-many dokumen-layanan.
- [ ] **6B-072**: hanya dokumen published yang tampil publik.
- [ ] **6B-073**: integrasi tidak lagi ditunda.
- [ ] **6B-074**: layanan tetap valid tanpa dokumen.
- [ ] **AC-SVC-6**: dokumen pendukung dapat diunduh dari halaman layanan.

### 8.5 XC-E3 / NFR-SEC

- [ ] Draft/archived 404 pada list, filter, pencarian, dan endpoint unduh.
- [ ] Tidak ada path storage/absolute path/disk internal di props publik.
- [ ] MIME palsu ditolak.
- [ ] Header keamanan pada respons unduhan.
- [ ] File tidak dapat diakses langsung lewat `public/storage`.

---

## 9. Pertanyaan / Ambiguity

Seluruh keputusan produk utama sudah dikonfirmasi melalui tool `question`:

1. Integrasi dokumen-layanan: **ya, pivot + UI Pelayanan**.
2. Status: **enum draft/published/archived**.
3. Format: **PRD lengkap, 10 MB**.
4. P1: **arsip, versi/tanggal, pencarian publik, redirect lama, pratinjau, unggulan, expires warning**.
5. Hapus: **soft delete baris + hapus file**.
6. Kategori: **seed 5 kategori PRD**.
7. Admin: **`/admin/dokumen` + kategori di Master Data**.
8. Unduh: **slug judul + ekstensi, counter attachment saja**.
9. Pratinjau: **admin semua status + publik Terbit**.
10. Kedaluwarsa: **peringatan saja**.
11. Unggulan: **seksi terpisah + badge**.
12. Selector layanan: **di form Pelayanan**.
13. Kategori nonaktif: **dokumen tetap tampil**.

Asumsi minor yang dipakai dan dapat dikoreksi sebelum coding:

- Nama route publik memakai konvensi Indonesia: **`dokumen.index`** dan **`dokumen.unduh`**, bukan `documents.*`, karena route admin memakai `documents.*`.
- Route name kategori memakai **`document-categories.*`**; permission memakai **`documentcategories.*`** (satu kata, mengikuti regex D-36).
- Tidak ada halaman detail dokumen dan tidak ada kolom `slug`, karena roadmap menyebut slug opsional dan PRD tidak memintanya.
- Dokumen yang sudah tertaut ke layanan lalu diarsipkan tetap terlihat di form Pelayanan dengan badge, tetapi tidak tampil publik; tautan baru hanya boleh ke dokumen published.
- `is_featured` boleh disimpan pada draf, tetapi hanya berpengaruh publik saat status published.
- File dokumen disimpan di `storage/app/private/documents`, bukan di tabel `media`.

Prasyarat environment yang perlu dikonfirmasi sebelum implementasi:

- `upload_max_filesize >= 10M` dan `post_max_size >= 12M`.
- `ext-zip` tersedia di production.
- `public/storage` tetap ada untuk gambar; dokumen tidak memakai symlink tersebut.

---

## 10. Status

**Sudah dieksekusi.** Implementasi mengikuti urutan §3: 8B.0 → 8B.1 → 8B.2 →
8B.3 → 8B.4 → 8B.5 → 8B.6 → 8B.7. Hasil verifikasi dicatat pada laporan akhir
pekerjaan; dokumentasi keputusan ada di `docs/DECISIONS.md` D-66.
