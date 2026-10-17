# Implementation Plan — PHASE 8A

> **Project:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau\
> **Phase:** 8A — Galleries (Modul Galeri)\
> **Status:** Disusun — menunggu persetujuan implementasi\
> **Branch (usulan):** `feat/phase-8a-galleries`\
> **Spesifikasi:** `docs/roadmap/phase-8A-galleries.md`\
> **Sumber kebenaran:** `PRD.md` → `ARCHITECTURE.md` → `DESIGN.md` → `AGENTS.md` → `DECISIONS.md`

Dokumen roadmap Phase 8A menjelaskan _apa_ yang harus ada. Dokumen ini menjelaskan
_bagaimana_ perubahannya, dalam urutan apa, file mana yang tersentuh, dan bagaimana
diverifikasinya. Dokumen ini adalah pelengkap roadmap, bukan pengganti.

> Dokumen ini disusun setelah audit codebase terhadap `main` pada commit
> `cf18db4` (`feat(media): satu picker unggah bervariant, MediaField dihapus`)
> dan **7 keputusan yang sudah dijawab pengguna**. Seluruh keputusan di §1
> mengikat implementasi. Belum ada satu baris kode implementasi Phase 8A yang
> ditulis.

---

## Daftar Isi

1. [Keputusan yang Sudah Diambil](#1-keputusan-yang-sudah-diambil)
2. [Kondisi Codebase Saat Ini](#2-kondisi-codebase-saat-ini)
3. [Kontrak yang Harus Dijaga](#3-kontrak-yang-harus-dijaga)
4. [Model Data dan Perilaku](#4-model-data-dan-perilaku)
5. [Ringkasan Task](#5-ringkasan-task)
6. [Urutan Implementasi](#6-urutan-implementasi)
7. [File yang Akan Terpengaruh](#7-file-yang-akan-terpengaruh)
8. [Dependency / Configuration Impact](#8-dependency--configuration-impact)
9. [Risiko dan Mitigasi](#9-risiko-dan-mitigasi)
10. [Verification Plan](#10-verification-plan)
11. [Test Matrix](#11-test-matrix)
12. [Acceptance Criteria](#12-acceptance-criteria)
13. [Yang Sengaja Tidak Dikerjakan](#13-yang-sengaja-tidak-dikerjakan)
14. [Keputusan Minor yang Diasumsikan](#14-keputusan-minor-yang-diasumsikan)
15. [Status](#15-status)

---

# 1. Keputusan yang Sudah Diambil

Tujuh pertanyaan diajukan sebelum penyusunan plan ini. Jawaban berikut mengikat
seluruh isi dokumen dan sudah dikonfirmasi pemilik proyek.

| #   | Pertanyaan                                                                       | Keputusan                                                                                                                                                                 | Alasan yang dipakai                                                                                                                                                                                               |
| --- | -------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| D-1 | Limit & format upload: PRD 5 MB/JPG/PNG/WebP vs `config/media.php` 3 MB/JPEG+PNG | **Pakai pipeline apa adanya**: 3 MB, `image/jpeg` + `image/png`, **tanpa WebP**. `config/media.php` **tidak diubah**.                                                     | Roadmap 8A §10 menyuruh menyelaraskan frontend/backend dengan `config/media.php`. Mengubah limit berdampak ke **semua** modul + shared prop + test Media. Divergensi vs PRD GAL-01/AC-GAL-6 dicatat di DECISIONS. |
| D-2 | Tautan album ke berita/agenda/komunitas (PRD D-04/§9.10)                         | **Tidak ada** `post_id`/`event_id`/`community_id` di 8A. Dicatat sebagai follow-up (kandidat Phase 9).                                                                    | Roadmap §6.1 (artefak yang lebih spesifik dari PRD) tidak mencantumkannya; Beranda/Phase 9 belum ada. Menambah kolom tanpa konsumen melanggar prinsip D-24.                                                       |
| D-3 | Model status album: enum vs boolean                                              | **`is_published` boolean + `published_at`**. Tanpa enum, tanpa status Arsip.                                                                                              | PRD §9.10 hanya menyebut `is_published bool default false`. Hanya dua keadaan, jadi enum dua-kasus hanya menambah lapisan. Sejalan dengan axis publikasi `communities`.                                           |
| D-4 | Referensi sampul                                                                 | **`cover_photo_id` → `gallery_photos`**, `nullOnDelete`, fallback foto pertama saat null.                                                                                 | PRD §9.10 dan GAL-07 (sampul bawaan = foto pertama). Menjamin "sampul tidak menunjuk item yang sudah dilepas" karena sampul adalah baris foto di album.                                                           |
| D-5 | Penempatan UI kelola foto                                                        | **Halaman khusus** `/admin/galeri/{gallery}/foto`.                                                                                                                        | Form album tetap ringkas; pengelola foto (batch upload, reorder, cover, caption/alt) punya ruang. Ditautkan dari daftar & form album.                                                                             |
| D-6 | Granularitas permission                                                          | **`galleries.view/create/update/delete`** saja. Publikasi **dan** semua operasi foto menumpang `galleries.update`.                                                        | D-36 (`/^[a-z]+\.[a-z]+$/`) dan pola modul lain: tidak ada `posts.publish`/`events.publish`; operasi anak (FAQ/persyaratan, area komunitas) menumpang `*.update` (D-54, D-55 point 11).                           |
| D-7 | (a) penempatan uploader multi-file, (b) deskripsi album                          | **(a) uploader di `features/admin/galeri/components/`**; **(b) deskripsi plain text**. Variant `gallery` di `MediaUploadField` **dibiarkan sebagai seam** (tidak diubah). | ARCHITECTURE §1.1: satu konsumen → milik feature. Deskripsi plain text aman untuk meta description, tanpa HtmlSanitizer/Tiptap.                                                                                   |

Nomor D-1 … D-7 adalah **nomor keputusan lokal dokumen ini**, bukan nomor `D-xx`
di `docs/DECISIONS.md`. Penomoran resmi Phase 8A dimulai dari **D-65**, karena
D-64 adalah nomor terakhir yang terpakai.

## 1.1 Pilihan minor yang diasumsikan

Diasumsikan sesuai rekomendasi; dapat dibatalkan kapan saja tanpa invalidasi
seluruh plan (lihat §14 untuk detail).

| #    | Pilihan minor         | Nilai                                                                                                                                             |
| ---- | --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| m-1  | Nama route            | `galleries.*` (URL `/admin/galeri`), `gallery-photos.*`, `galeri.index`/`galeri.show` (publik, tidak berubah)                                     |
| m-2  | Nama halaman Inertia  | `admin/galeri`, `admin/galeri/{tambah,ubah,foto}`, `public/galeri`, `public/galeri/show`                                                          |
| m-3  | Nama tabel & model    | `gallery_albums` + `gallery_photos` (PRD §9.10); model `GalleryAlbum` + `GalleryPhoto`                                                            |
| m-4  | Slug & soft delete    | Slug album unik lintas soft delete (D-44); `Rule::unique` **tanpa** `withoutTrashed()`; tanpa `active_marker`                                     |
| m-5  | `published_at`        | Dikelola server; **tidak** ada di rules/validasi request                                                                                          |
| m-6  | Deskripsi album       | Plain text (`text null`), bukan HTML tersanitasi                                                                                                  |
| m-7  | Jumlah per halaman    | Admin 15; publik 12 (pola Komunitas)                                                                                                              |
| m-8  | Filter publik         | **Tahun** dari `event_date` (PRD §7.8), bukan kategori                                                                                            |
| m-9  | Urutan publik         | Deterministik: `event_date` terbaru dahulu, lalu `published_at`, tie-breaker `id`                                                                 |
| m-10 | Penggantian/pelepasan | Melepas foto menghapus baris `gallery_photos` saja; baris & berkas `media` **tidak** dihapus                                                      |
| m-11 | Daftar admin          | TanStack v9, `manualSorting: true`, server-sort via `SortsAdminList`                                                                              |
| m-12 | SEO                   | `components/seo.tsx`; detail: judul = judul album, deskripsi = deskripsi album / fallback                                                         |
| m-13 | Permission            | +4 (81 → 85): `galleries.view`, `galleries.create`, `galleries.update`, `galleries.delete`                                                        |
| m-14 | Demo content          | `DemoContentSeeder` mengisi 1 album contoh (tanpa data paroki nyata; hanya saat `demo.enabled`) — opsional                                        |
| m-15 | Uploader              | Memperluas blok registry `components/upload/gallery-upload.tsx` untuk menerima prop `accept`/`type`/limit/teks Indonesia (diizinkan D-63 point 5) |
| m-16 | Lightbox              | Dibangun dari primitif yang ada (`dialog`/`sheet` + tombol); **tidak** menambah dependency lightbox                                               |

---

# 2. Kondisi Codebase Saat Ini

Audit terhadap `main` pada commit `cf18db4`. Phase 1–7 sudah selesai dan hijau.

## 2.1 Fondasi yang sudah ada dan cukup — **diaudit, bukan ditulis ulang**

| Area                | Status aktual                                                                                                                                                             | File                                                                                  |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------- |
| Media pipeline      | `POST/GET/DELETE /admin/media`; model `Media` + `MediaVariant`; job `GenerateImageVariants`; `MediaStatus` (pending/processing/ready/failed)                              | `app/Http/Controllers/Media/MediaController.php`, `app/Models/Media.php`              |
| Media config        | Disk original `local` (privat), variant `public`; MIME **hanya `image/jpeg`+`image/png`**; `max_upload_kilobytes=3072`; varian `thumb 400/medium 960/large 1600` WebP q82 | `config/media.php`                                                                    |
| EXIF/GPS            | `ImageProcessor::encodeWebp()` memakai GD yang tak pernah menulis EXIF → XC-M2/AC-GAL-5 sudah terpenuhi                                                                   | `app/Services/ImageProcessor.php`                                                     |
| Validasi upload     | `IsProcessableImage` membaca magic bytes + `getimagesize` + batas dimensi                                                                                                 | `app/Rules/IsProcessableImage.php`, `app/Http/Requests/Media/StoreMediaRequest.php`   |
| Klien media         | `postMediaFile` + `waitForMedia` (polling 1,5 s) + adapter uploadcn; hanya menerima baris `ready`                                                                         | `resources/js/lib/media-client.ts`, `resources/js/lib/media-upload-adapter.ts`        |
| Blok upload         | `components/upload/{image,avatar,logo,gallery,document,file}-upload.tsx`; facade `MediaUploadField`                                                                       | `resources/js/components/media-upload-field.tsx`                                      |
| Media shared prop   | Limit dari `config/media.php` dikirim ke browser via `media`                                                                                                              | `app/Http/Middleware/HandleInertiaRequests.php`, `resources/js/types/index.ts`        |
| Route & page publik | `/galeri` dan `/galeri/{slug}` **masih placeholder**; page `public/galeri` + `public/galeri/show`                                                                         | `routes/web.php:93-96`, `resources/js/features/public/galeri/pages/`                  |
| Navbar publik       | Sudah menautkan `@/routes/galeri` (nama route harus dipertahankan)                                                                                                        | `resources/js/layouts/public/components/parish-navbar.tsx`                            |
| Pola admin terdekat | Komunitas: `SortsAdminList`, `Save/DeleteAction`, concern validasi & meta, resource admin/publik, `published()` + `resolveRouteBinding` slug                              | `app/Http/Controllers/Komunitas/`, `app/Http/Resources/{Komunitas,Public/Komunitas}/` |
| Strict mode         | `Model::shouldBeStrict(! isProduction())` — lazy load melempar                                                                                                            | `app/Providers/AppServiceProvider.php`                                                |
| Gate `/admin`       | `['auth','verified',EnsureAccountIsActive,EnsureAdminAccess]` + `Gate::before` (`null`, bukan `false`)                                                                    | `routes/web.php:124`, `AppServiceProvider.php`                                        |
| Permission          | **81** permission; nama `entity.verb` satu kata; `RbacFoundationTest` mengunci pola `/^[a-z]+\.[a-z]+$/`                                                                  | `database/seeders/PermissionSeeder.php`                                               |
| Sorting list admin  | `SortsAdminList::applyAdminSort()` — whitelist, tie-breaker `id` terakhir                                                                                                 | `app/Concerns/SortsAdminList.php`                                                     |
| Layout resolution   | `app.tsx` memilih layout dari nama komponen (`public/*` → `PublicLayout`); halaman hanya `Component.layout = { breadcrumbs }`                                             | `resources/js/app.tsx`                                                                |
| SEO                 | `components/seo.tsx` + tag OG/`data-inertia` di `app.blade.php` (SSR OFF, D-26)                                                                                           | `resources/js/components/seo.tsx`, `resources/views/app.blade.php`                    |
| Primitive UI        | `dialog`, `sheet`, `table`, `badge`, `select`, `checkbox`, `dropdown-menu`, `tabs`, `sonner`                                                                              | `resources/js/components/ui/`                                                         |
| Halaman admin lain  | `gallery` variant `MediaUploadField` sengaja tak punya `input[type=file]`; `onMediaChangeMany` sudah disiapkan sebagai seam                                               | `media-upload-field.tsx`, `tests/js/components/media-upload-field.test.tsx`           |
| Testing             | Pest 4.7 (`seedRolesAndPermissions()`), Vitest (`tests/js/`), Playwright (`tests/e2e/`)                                                                                   | `tests/Pest.php`, `vite.config.ts`, `playwright.config.ts`                            |

## 2.2 Yang belum ada dan menjadi pekerjaan utama Phase 8A

| Area                   | Kekurangan yang ditemukan                                                                                                                                       |
| ---------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Tabel & model Galeri   | Tidak ada `gallery_albums`/`gallery_photos`, model, factory, policy, controller, resource, permission.                                                          |
| Route admin Galeri     | Belum ada satu pun route `/admin/galeri`.                                                                                                                       |
| Route publik Galeri    | `routes/web.php:93` `Route::inertia('/galeri','public/galeri')`; `:94` closure yang menjawab **200 untuk slug apa pun** (melanggar XC-E3).                      |
| Halaman publik Galeri  | `features/public/galeri/pages/{index,show}.tsx` masih `[ISI: …]` (hanya `SectionHeading`).                                                                      |
| Sidebar `Galeri`       | `app-sidebar.tsx` `{ title: 'Galeri', icon: Images }` **tanpa `href`** → baris nonaktif "belum".                                                                |
| Uploader multi-file    | `gallery` variant `MediaUploadField` merender panel "Belum tersedia" (D-63); `gallery-upload.tsx` masih hard-code `image/*` + output **WebP** (ditolak server). |
| Placeholder route test | `tests/Feature/Public/PublicPlaceholderRoutesTest.php` mengunci `galeri` sebagai placeholder detail yang **wajib** diganti.                                     |

## 2.3 Fakta yang mengikat desain

- **`config/media.php` menerima JPEG/PNG saja, 3 MB.** Menyimpang dari PRD GAL-01 (WebP, 5 MB) — diputuskan untuk memakai pipeline apa adanya (D-1).
- **`MediaController::destroy()` menghapus berkas lalu baris `media`.** FK dari `gallery_photos.media_id` harus eksplisit; `restrictOnDelete` akan membuat penghapusan gambar yang dipakai album menjadi 500 (lihat §9).
- **Eloquent strict mode aktif.** Setiap relasi yang dirender (cover, foto, media, varian) wajib eager load.
- **`withCount()` diikuti `select()` menjatuhkan kolom count** (D-41) — jangan `select()` setelah `withCount()`.
- **`Route::redirect()` tanpa nama merusak generate Wayfinder** — setiap redirect harus bernama.
- **`npm run check:fix` pernah menghapus isi file** — setelah menjalankannya, periksa `git diff` (AGENTS).

---

# 3. Kontrak yang Harus Dijaga

| Kontrak                                     | Aturan yang mengikat                                                                                                                          |
| ------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| URL publik tidak berubah                    | Pertahankan `/galeri` dan `/galeri/{slug}`; route name `galeri.index`/`galeri.show` tetap.                                                    |
| Hanya album terbit yang publik (XC-E3)      | `scope published()` dipakai di list, detail, **dan** `resolveRouteBinding` slug. Draf/soft-deleted → 404, tanpa `abort_if` yang bisa terlupa. |
| Sumber kebenaran data                       | Laravel memvalidasi & mengotorisasi; React presentasi saja. Tidak ada API internal/React Router.                                              |
| Bentuk props Inertia                        | `JsonResource`; list item vs detail dipisah; TS `Props` disinkronkan di perubahan yang sama.                                                  |
| Non-paginated list                          | `array_values(Resource::collection(...)->resolve())` (D-39). Paginated tetap `Resource::collection($paginator)`.                              |
| Media hanya dari pipeline                   | Simpan `media_id`, **jangan** binary/kolom path. Hanya `MediaStatus::Ready` boleh dijadikan sampul/publik.                                    |
| Media dipakai bersama                       | Melepas/menghapus foto album **tidak** menghapus baris/berkas `media`.                                                                        |
| Otorisasi sekali per route                  | FormRequest `authorize()` untuk `store`/`update`; `Gate::authorize()` untuk `index`/`show`/`edit`/`destroy`/custom.                           |
| Semua route admin dalam middleware existing | `['auth','verified',EnsureAccountIsActive,EnsureAdminAccess]` di grup `/admin`.                                                               |
| URL dibuat (Wayfinder)                      | Komponen import dari `@/routes/*`/`@/actions/*`; tidak ada URL app yang di-hard-code.                                                         |
| Nama halaman = jalur file                   | `admin/galeri`, `admin/galeri/{tambah,ubah,foto}`, `public/galeri`, `public/galeri/show`; dijaga `ArchitectureTest`.                          |
| Teks Indonesia                              | UI & validasi Bahasa Indonesia; nama tabel/kolom snake_case Inggris.                                                                          |
| Tanpa dependency baru                       | Tidak menambah library gambar/lightbox.                                                                                                       |
| `noValidate` pada form Inertia              | Server satu-satunya validator; `required` tetap untuk screen reader.                                                                          |

---

# 4. Model Data dan Perilaku

## 4.1 `gallery_albums`

| Kolom                       | Tipe                                     | Aturan                                                                                                 |
| --------------------------- | ---------------------------------------- | ------------------------------------------------------------------------------------------------------ |
| `id`                        | bigint PK                                | —                                                                                                      |
| `title`                     | string(150)                              | Wajib                                                                                                  |
| `slug`                      | string(180) **unique**                   | Dibuat dari judul bila kosong; unik lintas soft delete (tanpa `withoutTrashed`, tanpa `active_marker`) |
| `description`               | text null                                | Plain text; sumber meta description (dengan fallback aman)                                             |
| `event_date`                | date null                                | Tanggal kegiatan; wall-clock, **tidak** di-cast timezone                                               |
| `location`                  | string(255) null                         | Opsional                                                                                               |
| `is_published`              | boolean default false                    | Status Draf/Terbit                                                                                     |
| `published_at`              | timestamp null                           | Dikelola server; diset sekali saat pertama terbit                                                      |
| `created_by` / `updated_by` | FK `users` null, `nullOnDelete`          | Audit                                                                                                  |
| `created_at`/`updated_at`   | timestamps                               | —                                                                                                      |
| `deleted_at`                | timestamp null (soft delete)             | Konten utama → soft delete                                                                             |
| `cover_photo_id`            | FK `gallery_photos` null, `nullOnDelete` | Ditambahkan via `Schema::table` **setelah** `gallery_photos` dibuat (referensi sirkular)               |

Indeks: `slug` unique; `(is_published, event_date)` untuk query publik.

## 4.2 `gallery_photos`

| Kolom                     | Tipe                                    | Aturan                                              |
| ------------------------- | --------------------------------------- | --------------------------------------------------- |
| `id`                      | bigint PK                               | —                                                   |
| `gallery_album_id`        | FK `gallery_albums` **cascadeOnDelete** | Menghapus album (force delete) menghapus baris foto |
| `media_id`                | FK `media` **cascadeOnDelete**          | **Menghapus baris foto**, bukan berkas/Media lain   |
| `caption`                 | string(255) null                        | Opsional                                            |
| `alt_text`                | string(255) null                        | Aksesibilitas                                       |
| `sort_order`              | unsignedInteger default 0               | Wajib untuk urutan deterministik                    |
| `created_at`/`updated_at` | timestamps                              | —                                                   |

Indeks: **unique `(gallery_album_id, media_id)`** (cegah foto ganda di album yang sama); index `(gallery_album_id, sort_order)`.

> **Catatan ON DELETE:** `gallery_photos.media_id` memakai `cascadeOnDelete` pada **baris foto**. Ini tidak menghapus `media` milik modul lain — arah cascade-nya dari Media ke baris join, bukan sebaliknya. Alternatif `restrictOnDelete` ditolak karena `MediaController::destroy()` akan menjadi 500 saat gambar yang dipakai album dihapus.

## 4.3 Model & perilaku

- `GalleryAlbum`: `#[Fillable]`, `SoftDeletes`, cast `is_published` boolean + `published_at` `immutable_datetime`; relasi `photos()` (`hasMany`, `orderBy('sort_order')`), `coverPhoto()` (`belongsTo`), `createdBy()`/`updatedBy()`; scope `published()` (`is_published = true`), scope `ordered()` (deterministik); `resolveRouteBinding($value,'slug')` → hanya `published()` (pola `Community`).
- `GalleryPhoto`: relasi `album()`, `media()`; cast `sort_order` integer.
- **Tidak** ada enum status, **tidak** ada `is_featured`, **tidak** ada `sort_order` album, **tidak** ada kolom kategori.
- Sampul: `cover_photo_id` null → resource memakai foto pertama (berdasarkan `sort_order`) sebagai sampul.

## 4.4 Lifecycle

- **Terbit**: `published_at` diset sekali saat `is_published` false→true dan masih null; tidak diubah saat unpublish/republish; tidak diterima dari request.
- **Soft delete album**: slug tidak dilepas; `cover_photo_id` & foto tetap ada (DB cascade tidak jalan saat soft delete) → restore mengembalikan album utuh. Publik 404 otomatis via `published()` + SoftDeletes.
- **Hapus foto (detach)**: hapus baris `gallery_photos`; bila baris itu adalah `cover_photo_id`, kosongkan sampul (fallback foto pertama); **jangan** menghapus `media` atau berkasnya.
- **Ganti sampul**: `cover_photo_id` harus menunjuk foto **milik album itu** (validasi keanggotaan).
- **Reorder**: renumber `sort_order` album terkait secara transaksional; tolak id yang bukan milik album.

---

# 5. Ringkasan Task

| Tahap | Ringkasan                                                                                                                                           |
| ----- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| 8A.1  | Migration `gallery_albums`/`gallery_photos`, model, factory, permission, demo seed opsional, test domain/schema.                                    |
| 8A.2  | Route admin, policy, Form Request, Action, controller, resource admin, test backend.                                                                |
| 8A.3  | Halaman admin (daftar/form/kelola foto), uploader multi-file, pilih sampul, reorder, dialog konfirmasi, sidebar, lang, test Vitest.                 |
| 8A.4  | Route & controller publik, resource publik, halaman list/detail, grid, lightbox aksesibel, filter tahun, SEO, test publik & Vitest.                 |
| 8A.5  | Hardening (authorization, status, validasi upload, EXIF/GPS, orphan file, N+1, responsif), regression Media, lint/typecheck/build/E2E, dokumentasi. |

---

# 6. Urutan Implementasi

Urutan mengikuti dependency. Setiap langkah hijau sebelum lanjut.

## 8A.1 — Domain & persistence

1. Migration `2026_10_19_000000_create_gallery_tables.php`: buat `gallery_albums` (tanpa FK cover) → buat `gallery_photos` → `Schema::table('gallery_albums')` tambah FK `cover_photo_id`.
2. Model `GalleryAlbum`, `GalleryPhoto` (+ casts, relasi, scope, `resolveRouteBinding`).
3. Factory `GalleryAlbumFactory`, `GalleryPhotoFactory`.
4. `PermissionSeeder`: +4 permission; `docs/RBAC.md` baris Galeri (81 → 85).
5. (Opsional) `DemoContentSeeder` mengisi 1 album contoh + beberapa foto, hanya saat `demo.enabled`.
6. Test: `GallerySchemaTest` (FK, unique, soft delete tidak melepas slug, `published()` menyembunyikan draf).

## 8A.2 — Backend admin

1. `GalleryAlbumPolicy` (`viewAny/create/update/delete` → `galleries.*`).
2. Concern `GalleryValidationRules` + `GalleryFormMeta`.
3. Form Request: `StoreGalleryAlbumRequest`, `UpdateGalleryAlbumRequest`, `AttachGalleryPhotosRequest`, `ReorderGalleryPhotosRequest`, `UpdateGalleryPhotoRequest`, `SetGalleryCoverRequest`.
4. Action: `SaveGalleryAlbumAction` (slug + `published_at` + konsistensi cover), `AttachGalleryPhotosAction` (transaksi, `sort_order` lanjut, cegah duplikat, hanya media `ready`), `ReorderGalleryPhotosAction` (transaksi + cek keanggotaan).
5. Controller `GalleryController` (index server-paginated/sorted + `withCount('photos')` + eager `coverPhoto.media.variants`; create/store/edit/update/destroy) dan `GalleryPhotoController` (store batch, update caption/alt, reorder, destroy, cover).
6. Route admin di grup `/admin` (`galleries.*`, `gallery-photos.*`); regenerate Wayfinder.
7. Resource `GalleryAlbumListResource`, `GalleryAlbumResource`, `GalleryPhotoResource`.
8. Test backend (lihat §11).

## 8A.3 — UI admin

 1. `lang/id/galeri.php` + `GalleryFormMeta` dipakai controller.
 2. Halaman `features/admin/galeri/pages/{index,tambah,ubah}.tsx`, `types.ts`, komponen `gallery-form.tsx`, `gallery-status-badge.tsx`, `gallery-columns.tsx`.
 3. Halaman `pages/foto.tsx` + `gallery-photos-manager.tsx`, `gallery-photo-grid.tsx`, `gallery-photo-upload.tsx`, `gallery-cover-picker.tsx`, `gallery-photo-dialog.tsx`.
 4. Perluas `components/upload/gallery-upload.tsx` untuk prop `accept`/`type`/limit/teks Indonesia (JPEG, bukan WebP).
 5. Sidebar: `Galeri` menjadi link (`@/routes/galleries`); perbarui `tests/js/components/app-sidebar.test.tsx`.
 6. Test Vitest `/admin/galeri`.

## 8A.4 — Halaman publik

 1. `PublicGalleryController@index/show`; ganti route placeholder (pertahankan nama route & constraint slug).
 2. Resource `PublicGalleryCardResource`, `PublicGalleryDetailResource`, `PublicGalleryPhotoResource`.
 3. Halaman `features/public/galeri/pages/{index,show}.tsx` + `album-card.tsx`, `photo-grid.tsx`, `lightbox.tsx`, `types.ts`; filter tahun, pagination, empty state, SEO.
 4. Test publik + Vitest lightbox/grid.

## 8A.5 — Hardening & regression

 1. Jalankan verifikasi §10; regresi modul pemakai Media.
 2. Perbarui `docs/DECISIONS.md` (D-65+), `docs/RBAC.md`, status Phase 8A di `docs/roadmap/README.md`, checklist §16 roadmap.
 3. Jalankan `composer ci:check` + `composer test`.

---

# 7. File yang Akan Terpengaruh

## 7.1 Dibuat — backend

```text
database/migrations/2026_10_19_000000_create_gallery_tables.php
database/factories/GalleryAlbumFactory.php
database/factories/GalleryPhotoFactory.php
app/Models/GalleryAlbum.php
app/Models/GalleryPhoto.php
app/Policies/GalleryAlbumPolicy.php
app/Concerns/GalleryValidationRules.php
app/Concerns/GalleryFormMeta.php
app/Actions/Galeri/SaveGalleryAlbumAction.php
app/Actions/Galeri/AttachGalleryPhotosAction.php
app/Actions/Galeri/ReorderGalleryPhotosAction.php
app/Http/Controllers/Galeri/GalleryController.php
app/Http/Controllers/Galeri/GalleryPhotoController.php
app/Http/Controllers/Galeri/PublicGalleryController.php
app/Http/Requests/Galeri/StoreGalleryAlbumRequest.php
app/Http/Requests/Galeri/UpdateGalleryAlbumRequest.php
app/Http/Requests/Galeri/AttachGalleryPhotosRequest.php
app/Http/Requests/Galeri/ReorderGalleryPhotosRequest.php
app/Http/Requests/Galeri/UpdateGalleryPhotoRequest.php
app/Http/Requests/Galeri/SetGalleryCoverRequest.php
app/Http/Resources/Galeri/GalleryAlbumListResource.php
app/Http/Resources/Galeri/GalleryAlbumResource.php
app/Http/Resources/Galeri/GalleryPhotoResource.php
app/Http/Resources/Public/Galeri/PublicGalleryCardResource.php
app/Http/Resources/Public/Galeri/PublicGalleryDetailResource.php
app/Http/Resources/Public/Galeri/PublicGalleryPhotoResource.php
lang/id/galeri.php
```

## 7.2 Dibuat — frontend

```text
resources/js/features/admin/galeri/pages/index.tsx
resources/js/features/admin/galeri/pages/tambah.tsx
resources/js/features/admin/galeri/pages/ubah.tsx
resources/js/features/admin/galeri/pages/foto.tsx
resources/js/features/admin/galeri/components/gallery-form.tsx
resources/js/features/admin/galeri/components/gallery-form-panel.tsx
resources/js/features/admin/galeri/components/gallery-form-instance.ts
resources/js/features/admin/galeri/components/gallery-status-badge.tsx
resources/js/features/admin/galeri/components/gallery-columns.tsx
resources/js/features/admin/galeri/components/gallery-photos-manager.tsx
resources/js/features/admin/galeri/components/gallery-photo-grid.tsx
resources/js/features/admin/galeri/components/gallery-photo-upload.tsx
resources/js/features/admin/galeri/components/gallery-cover-picker.tsx
resources/js/features/admin/galeri/components/gallery-photo-dialog.tsx
resources/js/features/admin/galeri/types.ts
resources/js/features/public/galeri/components/album-card.tsx
resources/js/features/public/galeri/components/photo-grid.tsx
resources/js/features/public/galeri/components/lightbox.tsx
resources/js/features/public/galeri/types.ts
```

## 7.3 Dibuat — test

```text
tests/Feature/Galeri/GallerySchemaTest.php
tests/Feature/Galeri/GalleryAlbumTest.php
tests/Feature/Galeri/GalleryPhotoTest.php
tests/Feature/Galeri/GalleryAuthorizationTest.php
tests/Feature/Galeri/PublicGalleryDirectoryTest.php
tests/Feature/Galeri/PublicGalleryDetailTest.php
tests/js/features/admin/galeri/gallery-form.test.tsx
tests/js/features/admin/galeri/gallery-photos-manager.test.tsx
tests/js/components/gallery-lightbox.test.tsx
tests/e2e/galeri.spec.ts
```

## 7.4 Diubah

```text
routes/web.php
database/seeders/PermissionSeeder.php
database/seeders/DemoContentSeeder.php            # opsional
resources/js/components/upload/gallery-upload.tsx
resources/js/layouts/app/components/app-sidebar.tsx
resources/js/features/public/galeri/pages/index.tsx   # placeholder → halaman nyata
resources/js/features/public/galeri/pages/show.tsx    # placeholder → halaman nyata
docs/RBAC.md
docs/DECISIONS.md
docs/roadmap/README.md
tests/Feature/Public/PublicPlaceholderRoutesTest.php
tests/js/components/app-sidebar.test.tsx
```

## 7.5 Dihapus

Tidak ada file yang dihapus. Route placeholder lama **digantikan**, bukan dihapus tanpa pengganti (D-31).

---

# 8. Dependency / Configuration Impact

| Item                       | Dampak                                                                                                                         |
| -------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| Dependency PHP/JS          | **Tidak ada penambahan.** Lightbox dari primitif yang ada; gambar tetap memakai GD.                                            |
| `config/media.php`         | **Tidak diubah** (D-1). Limit 3 MB & MIME JPEG/PNG berlaku untuk Galeri.                                                       |
| Shared prop `media`        | Tidak berubah; uploader Galeri membacanya.                                                                                     |
| Permission & RBAC          | +4 (`galleries.*`); `PermissionSeeder::expectedCount()` otomatis; `RbacFoundationTest` tetap hijau. `docs/RBAC.md` diperbarui. |
| Wayfinder                  | Wajib regenerate: `php artisan wayfinder:generate --with-form` untuk `@/routes/galleries` & `@/actions/.../GalleryController`. |
| Queue                      | Memakai job/queue existing; tidak ada job baru.                                                                                |
| `config/html.php`          | Tidak berubah (deskripsi plain text — D-7).                                                                                    |
| Database                   | Dua tabel baru + FK sirkular; migration-only, tanpa mengubah tabel lama.                                                       |
| Sidebar test & placeholder | Dua test existing diperbarui (sidebar & `PublicPlaceholderRoutesTest`).                                                        |
| Dokumentasi                | `DECISIONS.md` (D-65+) mencatat divergensi; `roadmap/README.md` menandai 8A.                                                   |

---

# 9. Risiko dan Mitigasi

| #    | Risiko                                                                | Mitigasi                                                                                                                                    |
| ---- | --------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| R-1  | Konflik limit/format PRD (5 MB/WebP) vs pipeline (3 MB/JPEG+PNG)      | D-1: pakai pipeline apa adanya; catat divergensi di `DECISIONS.md`; jangan ubah `config/media.php` agar tidak menyentuh semua modul.        |
| R-2  | FK sirkular `cover_photo_id` ↔ `gallery_album_id` gagal saat migrate  | Buat `gallery_albums` tanpa FK cover → `gallery_photos` → `Schema::table` tambah FK cover. Uji `migrate:fresh`.                             |
| R-3  | `media_id` `restrictOnDelete` membuat hapus gambar → 500              | `cascadeOnDelete` pada baris foto; uji menghapus Media yang dipakai album.                                                                  |
| R-4  | `gallery-upload.tsx` bawaan mengubah ke WebP → 422                    | Perluas blok dengan prop `type`/`accept` dari limit (JPEG); uji upload nyata di Vitest/E2E.                                                 |
| R-5  | Soft delete + slug: `active_marker` keliru dipakai (pola locations)   | Slug album unik lintas soft delete (D-44); tanpa `active_marker`; `Rule::unique` tanpa `withoutTrashed()`; uji binding publik.              |
| R-6  | N+1 / strict mode (cover, foto, media, varian)                        | Eager load `coverPhoto.media.variants`, `photos.media.variants`; `withCount('photos')`; **jangan** `select()` setelah `withCount()` (D-41). |
| R-7  | `ArchitectureTest` gagal karena nama halaman/URL hard-code            | Gunakan nama halaman yang terdaftar; tautan via Wayfinder; tidak `href="/..."`.                                                             |
| R-8  | `MediaUploadField` test pecah bila variant `gallery` diaktifkan       | D-7: uploader di feature, `MediaUploadField` dibiarkan; test existing tetap valid.                                                          |
| R-9  | E2E gagal karena `public/build` basi / rate limit login               | `npm run build` sebelum `npm run e2e`; spec login memanggil `resetLoginRateLimit()`.                                                        |
| R-10 | `npm run check:fix` menghapus isi file                                | Jalankan `npm run check` (bukan `:fix`) dulu; bila memakai `:fix`, periksa `git diff`.                                                      |
| R-11 | Kolom `is_featured`/`sort_order`/tautan tanpa konsumen melanggar D-24 | Dihapus dari skema; dicatat sebagai follow-up di `DECISIONS.md`.                                                                            |
| R-12 | Foto draf dapat diakses via URL varian publik (path UUID)             | Mengikuti kebijakan storage existing (semua varian publik, path UUID tak terenumerasi); dicatat sebagai keterbatasan di `DECISIONS.md`.     |
| R-13 | Reorder/attach tidak konsisten (duplikat, urutan bolong)              | Unique `(gallery_album_id, media_id)`; transaksi; cek keanggotaan id; uji reload.                                                           |

---

# 10. Verification Plan

## 10.1 Backend

```bash
./vendor/bin/pint --dirty
php artisan test tests/Feature/Galeri --compact         # targeted
php artisan test tests/Feature/Media --compact          # regresi Media
php artisan test tests/Feature/Komunitas tests/Feature/News tests/Feature/Agenda tests/Feature/Pelayanan tests/Feature/Profil --compact
php artisan test --compact                              # full (PHPStan via composer test)
composer test                                           # pint --test + phpstan + test
```

## 10.2 Frontend

```bash
npm run check
npm run types:check
php artisan wayfinder:generate --with-form
# vitest dijalankan via vp test (tests/js):
npx vp test tests/js/features/admin/galeri tests/js/components/gallery-lightbox.test.tsx
```

## 10.3 E2E

```bash
npm run build
npm run e2e            # menjalankan app:e2e:prepare lebih dulu
```

`tests/e2e/galeri.spec.ts`: login → buat album draf → unggah beberapa foto → tunggu `ready` → pilih sampul → atur urutan → terbitkan → buka `/galeri` & detail sebagai pengunjung → lightbox → pastikan draf 404 → hapus dengan dialog konfirmasi.

## 10.4 Gate penuh

```bash
composer ci:check      # npm run check + npm run types:check + artisan test
composer test
```

---

# 11. Test Matrix

## 11.1 Pest (backend)

| Test                                 | Cakupan                                                                                                                                            |
| ------------------------------------ | -------------------------------------------------------------------------------------------------------------------------------------------------- |
| `GallerySchemaTest`                  | FK, unique `(gallery_album_id, media_id)`, soft delete tidak melepas slug, `published()` scope.                                                    |
| `GalleryAlbumTest`                   | Create/update/delete; slug otomatis & unik (collision → suffix deterministik); `published_at` set sekali; menolak judul kosong; urutan admin.      |
| `GalleryPhotoTest`                   | Attach batch (hanya media `ready`), duplikat ditolak, reorder persisten, update caption/alt, detach tidak menghapus Media, cover hanya foto album. |
| `GalleryAuthorizationTest`           | Guest & user tak berwenang ditolak; Super Admin diizinkan; publik tak bisa mutasi.                                                                 |
| `PublicGalleryDirectoryTest`         | Hanya album terbit; pagination; filter tahun; draf/arsip/soft-deleted tidak muncul di props.                                                       |
| `PublicGalleryDetailTest`            | Hanya album terbit; slug draf/hilang → 404 (XC-E3); foto urut `sort_order`; media belum `ready` tak tampil.                                        |
| `PublicPlaceholderRoutesTest` (ubah) | `galeri` keluar dari daftar placeholder; `/galeri/{slug}` dari dataset 404.                                                                        |

## 11.2 Vitest (frontend)

| Test                              | Cakupan                                                                                                                                    |
| --------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| `gallery-form.test.tsx`           | Error server di field yang benar; label/hint dari props.                                                                                   |
| `gallery-photos-manager.test.tsx` | Status per file, retry file gagal tanpa mengulang yang sukses, cover hanya foto album, dialog konfirmasi mencegah hapus tanpa persetujuan. |
| `gallery-lightbox.test.tsx`       | Buka/tutup (Esc), navigasi panah, fokus terkelola, empty state.                                                                            |
| `app-sidebar.test.tsx` (ubah)     | `Galeri` punya href dan mengarah ke `/admin/galeri`.                                                                                       |

## 11.3 Playwright (E2E)

Satu spec `galeri.spec.ts` untuk skenario end-to-end §10.3.

---

# 12. Acceptance Criteria

## 12.1 Admin album (roadmap §13.1)

- [ ] Super Admin membuka daftar album Galeri.
- [ ] Daftar menampilkan judul, sampul, tanggal kegiatan, jumlah foto, status, waktu pembaruan.
- [ ] Super Admin membuat album dengan judul wajib.
- [ ] Slug aman & tidak duplikat.
- [ ] Edit album tanpa kehilangan foto terhubung.
- [ ] Album dapat disimpan sebagai draf.
- [ ] Album muncul publik hanya setelah diterbitkan.
- [ ] Hapus album lewat konfirmasi.
- [ ] Hapus album tidak menghapus Media yang dipakai modul lain.

## 12.2 Foto album (roadmap §13.2)

- [ ] Unggah beberapa foto sekaligus.
- [ ] UI menampilkan status upload/pemrosesan per file.
- [ ] Kegagalan satu file tidak membuang yang berhasil.
- [ ] Foto hanya dipakai setelah media `ready`.
- [ ] Pilih sampul dari foto album tersebut.
- [ ] Ubah urutan & persisten setelah reload.
- [ ] Ubah caption/alt.
- [ ] Keluarkan foto dari album dengan konfirmasi.
- [ ] Media tidak dihapus dari storage bila masih direferensikan di tempat lain.

## 12.3 Halaman publik (roadmap §13.3)

- [ ] `/galeri` hanya album terbit.
- [ ] Kartu album informatif tanpa placeholder kosong membingungkan.
- [ ] `/galeri/{slug}` hanya menampilkan album terbit.
- [ ] URL draf/arsip/hilang → respons aman & konsisten (404).
- [ ] Foto memakai varian yang tepat; media gagal tidak tampil.
- [ ] Lightbox mouse, keyboard, sentuh.
- [ ] Responsif mobile/tablet/desktop.
- [ ] SEO mengikuti pola publik.

## 12.4 Keamanan & kualitas (roadmap §13.4)

- [ ] Pengunjung tidak bisa menjalankan route mutasi admin.
- [ ] Semua mutasi divalidasi backend.
- [ ] Format & ukuran file divalidasi server-side (JPEG/PNG, ≤3 MB).
- [ ] Tidak ada path file dari nama upload.
- [ ] EXIF/GPS tidak terekspos (dijamin `ImageProcessor` GD).
- [ ] Tanpa N+1 jelas.
- [ ] UI punya state loading/error/empty/success/disabled.
- [ ] Tanpa regresi modul Media.

## 12.5 AC terdampak (PRD/ID)

AC-GAL-1 (batch; jumlah per batch mengikuti kapasitas klien, server per-file), AC-GAL-2 (grid), AC-GAL-3 (lightbox), AC-GAL-4 (thumbnail + lazy), AC-GAL-5 (EXIF GPS hilang), AC-GAL-6 (non-gambar/oversize ditolak) — **dengan penyesuaian**: format JPEG/PNG & batas 3 MB (bukan WebP/5 MB), lihat D-1. GAL-07 (fallback sampul), GAL-08 (batch independen), XC-E3, XC-M1/M2/M3, XC-P1, NFR-A11Y, NFR-SEO.

---

# 13. Yang Sengaja Tidak Dikerjakan

- Modul Dokumen/PDF/download & route `/dokumen` (Phase 8B).
- Integrasi/redesign halaman Kontak `/kontak` (Phase 8C).
- Video, streaming, embed YouTube, media non-gambar.
- Komentar, like, akun publik, upload pengunjung, approval workflow.
- Kategori album (PRD §6.3) — tidak ada tabel kategori; filter publik = tahun.
- Tautan album ke berita/agenda/komunitas (D-2, follow-up).
- Integrasi beranda/`is_featured`/`home_gallery_limit` (Phase 9; D-2).
- Status Arsip.
- Media library generik baru; UI role/permission.
- Redesign homepage/navbar/footer/design system.
- Perubahan `config/media.php` (D-1).

---

# 14. Keputusan Minor yang Diasumsikan

| #    | Keputusan minor       | Nilai & justifikasi                                                                                                  |
| ---- | --------------------- | -------------------------------------------------------------------------------------------------------------------- |
| m-1  | Nama route            | `galleries.*`, `gallery-photos.*`; `galeri.*` publik tak berubah.                                                    |
| m-2  | Nama halaman          | `admin/galeri`, `admin/galeri/{tambah,ubah,foto}`, `public/galeri`, `public/galeri/show`.                            |
| m-3  | Tabel & model         | `gallery_albums`/`gallery_photos`, `GalleryAlbum`/`GalleryPhoto` (PRD §9.10).                                        |
| m-4  | Slug & soft delete    | Unik lintas soft delete (D-44), tanpa `active_marker`.                                                               |
| m-5  | `published_at`        | Dikelola server, bukan field request.                                                                                |
| m-6  | Deskripsi             | Plain text.                                                                                                          |
| m-7  | Pagination            | Admin 15, publik 12.                                                                                                 |
| m-8  | Filter publik         | Tahun (PRD §7.8).                                                                                                    |
| m-9  | Urutan publik         | `event_date` desc → `published_at` desc → `id` desc.                                                                 |
| m-10 | Pelepasan foto        | Hapus baris `gallery_photos`; Media & berkas tetap.                                                                  |
| m-11 | Daftar admin          | TanStack v9 + `manualSorting` + `SortsAdminList`.                                                                    |
| m-12 | SEO detail            | `components/seo.tsx`.                                                                                                |
| m-13 | Permission            | +4 (81 → 85).                                                                                                        |
| m-14 | Demo content          | Opsional, hanya `demo.enabled`, tanpa data nyata.                                                                    |
| m-15 | Perluasan blok upload | `gallery-upload.tsx` menerima prop; default registry dipertahankan; pemanggil mengirim limit nyata & teks Indonesia. |
| m-16 | Lightbox              | Primitif yang ada; tanpa dependency baru.                                                                            |

Semua m-x dapat dibalik tanpa membatalkan plan.

---

# 15. Status

**Disusun — menunggu persetujuan implementasi.**

Belum ada baris kode implementasi Phase 8A yang ditulis. Setelah disetujui:

1. Kerjakan 8A.1 → 8A.5 berurutan.
2. Regenerate Wayfinder setelah route/controller berubah.
3. Jalankan verifikasi §10 dan laporkan perintah serta hasil aktual.
4. Catat keputusan resmi Phase 8A sebagai D-65 dan seterusnya di `docs/DECISIONS.md`, termasuk:
   - `media_id` menggantikan kolom path PRD §9.10;
   - penamaan tabel/model;
   - `is_published` boolean (tanpa enum & Arsip);
   - tanpa kategori/tahun-filter; tanpa tautan album; peniadaan `is_featured`/`sort_order` album;
   - `cover_photo_id` → `gallery_photos`;
   - divergensi limit 3 MB/JPEG+PNG (tanpa WebP) vs PRD GAL-01;
   - keterbatasan URL varian publik untuk media draf.

**Konfirmasi eksplisit:** Phase 8B (Dokumen) dan Phase 8C (Kontak) tetap di luar scope.
