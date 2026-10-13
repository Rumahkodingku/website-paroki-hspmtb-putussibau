# Implementation Plan — PHASE 06A

> **Project:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau\
> **Phase:** 06A — Parish Profile (Profil Paroki)\
> **Status:** Disusun — menunggu persetujuan implementasi\
> **Branch (usulan):** `feat/phase-06a-parish-profile`\
> **Spesifikasi:** `docs/roadmap/phase-6A-parish-profile.md`\
> **Sumber kebenaran:** `PRD.md` → `ARCHITECTURE.md` → `DESIGN.md` → `AGENTS.md` → `DECISIONS.md`

Dokumen roadmap Phase 6A menjelaskan _apa_ yang harus ada. Dokumen ini menjelaskan
_bagaimana_ perubahannya, dalam urutan apa, file mana yang tersentuh, dan
bagaimana diverifikasinya.

> Dokumen ini disusun setelah audit codebase terhadap `main` pada commit
> `d5d810f` (`Merge pull request #6 from Rumahkodingku/fix/media-preview-rich-text-editor`)
> dan **13 keputusan yang sudah dijawab pengguna**. Seluruh keputusan di §1
> mengikat implementasi. Belum ada satu baris kode implementasi yang ditulis.

---

## Daftar Isi

1. [Keputusan yang Sudah Diambil](#1-keputusan-yang-sudah-diambil)
2. [Kondisi Codebase Saat Ini](#2-kondisi-codebase-saat-ini)
3. [Kontrak yang Harus Dijaga](#3-kontrak-yang-harus-dijaga)
4. [Ringkasan Task](#4-ringkasan-task)
5. [Urutan Implementasi](#5-urutan-implementasi)
6. [File yang Akan Terpengaruh](#6-file-yang-akan-terpengaruh)
7. [Dependency / Configuration Impact](#7-dependency--configuration-impact)
8. [Risiko dan Mitigasi](#8-risiko-dan-mitigasi)
9. [Verification Plan](#9-verification-plan)
10. [Test Matrix](#10-test-matrix)
11. [Acceptance Criteria](#11-acceptance-criteria)
12. [Yang Sengaja Tidak Dikerjakan](#12-yang-sengaja-tidak-dikerjakan)
13. [Keputusan yang Masih Menunggu Konfirmasi](#13-keputusan-yang-masih-menunggu-konfirmasi)
14. [Status](#14-status)

---

# 1. Keputusan yang Sudah Diambil

Tiga belas pertanyaan diajukan sebelum penyusunan plan ini. Jawaban berikut
mengikat seluruh isi dokumen.

| #    | Pertanyaan                                          | Keputusan                                                                                                   | Alasan yang dipakai                                                                                                                                                                                                            |
| ---- | --------------------------------------------------- | ----------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| D-1  | Visi & Misi (dilarang 6A, tetapi dibangun Phase 03) | **Hapus total**: route `profil.visiMisi`, `visi-misi.tsx`, link footer, entri dataset test                  | Roadmap §3.2: "tidak membuat route, halaman, submenu, field, tabel, migration, seeder, placeholder, atau tautan untuk Visi & Misi"; §6A-88 menyuruh menghilangkan tautannya dari UI dalam scope; §6A-139 menguji ketiadaannya. |
| D-2  | Model profil utama                                  | **Tabel singleton `parish_profiles`**                                                                       | Roadmap §6.1. Satu profil aktif, desain terkecil. Menyimpan narasi sejarah pada kolom sendiri menghindari tabel key-value generik dan menjauhkan kembali pintu visi/misi.                                                      |
| D-3  | Model pastor                                        | **Dua tabel `pastors` + `pastor_assignments`**                                                              | Roadmap §6.3: "Jangan langsung mengasumsikan satu tabel cukup." Satu orang dapat punya beberapa periode penugasan; riwayat tidak terhapus saat penugasan berubah.                                                              |
| D-4  | Representasi periode                                | **Tahun `unsignedSmallInteger`**                                                                            | PRD §9.4 memakai `smallint`; roadmap §6.3/§6.4 melarang mengarang tanggal yang tidak diketahui. Tanggal penuh tidak dibutuhkan oleh acceptance criteria.                                                                       |
| D-5  | Wilayah pelayanan                                   | **Tabel singleton `parish_service_areas` (satu narasi)**                                                    | Pengguna memilih tabel, tetapi PRD menegaskan wilayah/stasi/lingkungan dikelola modul Komunitas (Phase 7). Satu narasi + peta menghindari duplikasi domain Phase 7 dan memenuhi §6A-63/§6A-100.                                |
| D-6  | Kolom status                                        | **`is_current`/`is_active` (operasional) + `is_published` (visibilitas publik)**                            | Roadmap §7 membedakan dua arti itu secara eksplisit. `is_published` adalah divergensi dari PRD §9.4 dan dicatat di DECISIONS.md.                                                                                               |
| D-7  | Kontak pengurus                                     | **Hilangkan `phone`/`show_contact`**                                                                        | Roadmap §6A-124: "Tidak ada data pribadi yang dipublikasikan tanpa persetujuan." Divergensi dari PRD PROF-03, dicatat.                                                                                                         |
| D-8  | Pengelompokan permission                            | **Lima grup entity**                                                                                        | Roadmap §8.4/§8.5 + RBAC.md. Nama wajib cocok `/^[a-z]+\.[a-z]+$/` (D-36).                                                                                                                                                     |
| D-9  | Halaman overview admin                              | **Tidak dibuat.** Root `/admin/profil` = redirect ke Informasi Profil; sidebar menampilkan submenu langsung | Pengguna: "Saya tidak memerlukan halaman overview." Divergensi dari §6A-44…49 dan §9.2, dicatat. Module root tetap ada karena PRD §5.2 menamainya dan breadcrumb menunjuk ke sana (pola D-42).                                 |
| D-10 | FK periode → anggota                                | **`cascadeOnDelete`**                                                                                       | Menghapus periode menghapus anggotanya; dialog konfirmasi menyebut jumlah anggota (roadmap §6.5, §6A-76). Divergensi dari PRD §9.4 yang menulis `cascade` juga, jadi ini sebenarnya selaras PRD.                               |
| D-11 | Status Phase 5 & README                             | **Perbarui `docs/roadmap/README.md` lebih dulu (C0)**                                                       | README masih menandai Phase 5 "Planned" padahal kode & test sudah ada; roadmap harus menyebut Phase 6A sebagai fase aktif.                                                                                                     |
| D-12 | Sidebar `Profil` (datang tanpa `href`)              | **Ganti menjadi group collapsible "Profil Paroki"** dengan 5 anak                                           | Mengikuti pola disclosure Jadwal Misa/Master Data. Five children: Informasi Profil, Sejarah, Wilayah, Pastor, Struktur.                                                                                                        |
| D-13 | Pemisahan narasi sejarah vs linimasa                | **Narasi pada `parish_profiles.history_description`; linimasa pada `parish_history_items`**                 | Roadmap §6.2: "Pisahkan narasi utama sejarah dari daftar linimasa jika keduanya perlu dikelola secara independen." Keduanya dikelola di halaman Sejarah yang sama. Tambahkan ``history_items` untuk daftar.                    |

## 1.1 Pilihan minor yang diasumsikan

Diasumsikan sesuai rekomendasi; dapat dibatalkan kapan saja tanpa invalidasi
seluruh plan.

| #   | Pilihan minor           | Nilai                                                                                                   | Alasan                                                                                                                                   |
| --- | ----------------------- | ------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| m-1 | Nama route admin        | Prefix `parish-*` / `pastors.*` / `board-*` — **tidak** boleh menabrak `profil.*` publik yang sudah ada | Route publik `profil.*` sudah terpakai (`routes/web.php:36-41`). Nama duplikat membuat Wayfinder gagal build (D-48 §3, D-49).            |
| m-2 | Middleware admin        | Empat lapis: `auth`, `verified`, `EnsureAccountIsActive`, `EnsureAdminAccess`                           | AGENTS.md "Auth layout". Semua route `/admin/*` baru berada dalam grup yang sama.                                                        |
| m-3 | Sanitasi HTML           | `HtmlSanitizer` dipanggil di `prepareForValidation()` setiap FormRequest rich text                      | Mengikuti `PostValidationRules::normalisePostInput()` dan `UpdateSettingsRequest::normaliseValue()`.                                     |
| m-4 | Media                   | Nullable `*_media_id` FK `nullOnDelete` + kolom path varian + `*_alt`, divalidasi `status = Ready`      | Pola `Post`/`Event` yang sudah mapan. Frontend memakai `MediaField` + `useMediaUpload` apa adanya.                                       |
| m-5 | Penegakan singleton     | Level aplikasi (`firstOrCreate` + method `current()`) + test; bukan trik kolom                          | Desain terkecil; roadmap §16 minta "desain terkecil yang memenuhi kebutuhan".                                                            |
| m-6 | Nama file halaman admin | Folder `index.tsx` / `tambah.tsx` / `ubah.tsx`                                                          | Mengikuti `features/admin/master-data/pages/lokasi/`. Catat aturan `pageName()` (index dipertahankan satu folder di bawah root feature). |
| m-7 | Data contoh             | **Tidak ada seeder konten.** Hanya struktur/profil kosong; seeder idempotent                            | Roadmap §6.1, §6A-16; prinsip D-34.                                                                                                      |
| m-8 | Posisi pastor           | Enum `PastorPosition` (`pastor_paroki`, `pastor_rekan`, `lainnya`)                                      | PRD §9.4 `clergy.position`; enum memberi validasi + label terpusat.                                                                      |
| m-9 | Ikon sidebar            | `Users` (atau `Church`) untuk group Profil Paroki                                                       | Menyesuaikan set ikon yang sudah dipakai `app-sidebar.tsx`.                                                                              |

Nomor D-1 … D-13 adalah **nomor keputusan lokal dokumen ini**, bukan nomor
`D-xx` di `docs/DECISIONS.md`. Penomoran resmi Phase 6A dimulai dari **D-52**,
karena D-51 adalah nomor terakhir yang terpakai.

---

# 2. Kondisi Codebase Saat Ini

Audit terhadap `main` pada commit `d5d810f`.

## 2.1 Fondasi yang sudah ada dan cukup — **diaudit, bukan ditulis ulang**

| Area               | Status aktual                                                                                            | File                                                                   |
| ------------------ | -------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------- |
| Timezone           | `app.timezone = 'UTC'` + `display_timezone = 'Asia/Pontianak'`, dikirim sebagai shared prop              | `config/app.php:71,87`, `HandleInertiaRequests.php:83`                 |
| Strict mode        | `Model::shouldBeStrict(! isProduction())`                                                                | `AppServiceProvider.php`                                               |
| Gate `/admin`      | `['auth','verified',EnsureAccountIsActive,EnsureAdminAccess]` + `Gate::before` (`null`, bukan `false`)   | `routes/web.php:101`, `AppServiceProvider.php:108`                     |
| Sanitasi HTML      | `HtmlSanitizer` (singleton) backed `symfony/html-sanitizer`; allowlist di `config/html.php`              | `app/Services/HtmlSanitizer.php`, `config/html.php`                    |
| Media pipeline     | `MediaController` + `StoreMediaRequest` + `GenerateImageVariants`; varian `thumb/medium/large`           | `app/Http/Controllers/Media/`, `config/media.php`                      |
| Media picker       | `MediaField` + `useMediaUpload` (upload + poll + preview) sudah ada dan dipakai Berita/Agenda            | `resources/js/components/media-field.tsx`, `hooks/use-media-upload.ts` |
| Rich text          | Editor Tiptap penuh di `components/` + render `RichText` (satu-satunya `dangerouslySetInnerHTML` sah)    | `resources/js/components/rich-text-editor.tsx`, `rich-text.tsx`        |
| Permission         | **43 permission** ter-seed; nama `entity.verb` satu kata                                                 | `database/seeders/PermissionSeeder.php:41`                             |
| Sorting list admin | `SortsAdminList::applyAdminSort()` — whitelist, tie-breaker `id` terakhir, default lewat argumen         | `app/Concerns/SortsAdminList.php`                                      |
| List prop          | `array_values(Resource::collection(…)->resolve())` untuk list non-paginate (D-39)                        | Controller Berita/Agenda                                               |
| Layout resolution  | `app.tsx` memilih layout dari nama komponen; halaman hanya mengirim `Component.layout = { breadcrumbs }` | `resources/js/app.tsx`                                                 |
| SEO                | `components/seo.tsx` (ogType, canonical, ogImage, noIndex) + tags OG/JSON-LD di `app.blade.php`          | `resources/js/components/seo.tsx`, `resources/views/app.blade.php`     |
| Design tokens      | `Text`, `Heading`, `SectionHeading`, `PublicSection`, `PublicContainer`, `ProfileSubNav` (belum ada)     | `resources/js/components/`                                             |
| Testing            | Pest 4.7 (`seedRolesAndPermissions()`), Vitest via `vite-plus` (`tests/js/`), Playwright (`tests/e2e/`)  | `tests/`                                                               |

## 2.2 Yang sudah ada tapi harus diganti — pekerjaan utama Phase 6A

| Area                    | Kekurangan yang ditemukan                                                                                                                                 |
| ----------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Route publik profil     | `routes/web.php:36-41` masih enam `Route::inertia()` tanpa query. Termasuk `profil.visiMisi` yang harus dihapus.                                          |
| Halaman publik profil   | `features/public/profil/pages/{index,sejarah,wilayah,pastor,struktur,visi-misi}.tsx` semua placeholder `[ISI: …]` berisi satu `SectionHeading`.           |
| Footer publik           | `parish-footer.tsx:16-23,46-55` mengimpor dan menautkan `visiMisi` (harus dihapus) dan sub-halaman profil lain (dipertahankan).                           |
| Entri sidebar `Profil`  | `app-sidebar.tsx:63` `{ title: 'Profil', icon: Users }` tanpa `href` → baris disabled "belum".                                                            |
| Test placeholder publik | `PublicRoutesTest` (dataset `public pages` & `placeholder pages`) dan `PublicPlaceholderRoutesTest:139` memuat `/profil/visi-misi` dan `profil.visiMisi`. |
| Test RBAC               | `RbacFoundationTest.php:62+` punya array permission **hardcoded** — ditambah permission baru, test merah sampai array diperbarui.                         |
| Test sidebar & E2E      | `tests/js/components/app-sidebar.test.tsx` dan `tests/e2e/master-data.spec.ts` mengandalkan daftar group saat ini.                                        |

## 2.3 Yang belum ada dan harus dibuat

**7 tabel** · **1 enum** · **7 model** · **7 factory** · **7 policy** ·
**~10 FormRequest** · **3 Action** · **7 controller admin + 1 publik** ·
**~20 `JsonResource`** · **~30 route** · **~12 halaman Inertia admin** ·
**5 halaman Inertia publik (di-rewrite)** · **~6 komponen publik** ·
**2 file `lang/id`** · **~14 file test** · **1 komponen `ProfileSubNav`** ·
**perubahan sidebar, footer, README, RBAC, DECISIONS**

## 2.4 Konvensi yang harus diikuti — bukan landmark baru

| Konvensi  | Bentuk persisnya di repo                                                                                   | Konsekuensi untuk Phase 6A                                                   |
| --------- | ---------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------- |
| Fillable  | Atribut **`#[Fillable(['key', …])]`**, bukan properti (dicek `ArchitectureTest`)                           | Tujuh model baru wajib memakai atribut                                       |
| Cast      | Method `protected function casts(): array`                                                                 | `is_published`/`is_active`/`is_current` → `boolean`, `year` → `integer`      |
| Resource  | `JsonResource` terpisah admin vs publik; `ResolvesNestedResources` untuk nested                            | Jangan pernah mengirim model mentah                                          |
| List prop | **`array_values(Resource::collection(…)->resolve())`** untuk list non-paginate (D-39)                      | Berlaku untuk linimasa, anggota, pilihan periode                             |
| Sorting   | `applyAdminSort($query, $request, $whitelist, $default)`; default lewat argumen, tie-breaker `id` terakhir | Untuk linimasa, pastor, periode, anggota                                     |
| Otorisasi | `FormRequest::authorize()` untuk `store`/`update`; `Gate::authorize()` untuk lainnya                       | Tidak pernah di dua tempat                                                   |
| Policy    | Satu Policy per model; `super_admin` lewat `Gate::before` → wajib `can()`                                  | `ParishProfilePolicy`, `PastorPolicy`, dst.                                  |
| Action    | Hanya untuk operasi multi-model/transaksi                                                                  | `SetCurrentPastorAction`, `ActivateBoardPeriodAction`                        |
| Slug      | `posts`/`events` mempertahankan slug saat soft delete (D-44); tidak relevan untuk tabel tanpa slug         | Tabel profil tidak memakai slug                                              |
| Route     | Eksplisit `Route::get/post/patch/delete` + `->name()` dalam `routes/web.php` (D-49 satu file)              | Route admin di dalam grup `/admin`; nama unik                                |
| Halaman   | Tipis; `Index.layout = { breadcrumbs }`; form & tabel di `components/`/`pages/…/components`                | Mengikuti `features/admin/master-data/pages/lokasi/`                         |
| Form      | `<form noValidate>` — **wajib**                                                                            | Server satu-satunya validator; pesan Indonesia dari `lang/id/validation.php` |
| Select    | `ChoiceSelect` (Radix, sentinel `__empty__`) untuk form; `<select>` native untuk filter                    | Untuk enum `PastorPosition`, pilihan periode                                 |
| Copy      | Bahasa Indonesia                                                                                           | Semua label, flash, dan `messages()`                                         |

## 2.5 Yang **tidak boleh** diasumsikan ada

1. **Media library page.** Hanya picker (`MediaField`) dan endpoint media; tabel
   `media` mengisi seiring upload. Tidak ada halaman pustaka.
2. **`ProfileSubNav`/`ParishProfileSidebar`/`OrganizationChart`.** Disebut
   DESIGN.md tetapi belum ada; dibangun di Phase 6A.
3. **Parser tanggal.** `package.json` tidak punya `date-fns`/`dayjs`/`luxon`.
   Tahun/linimasa memakai aritmetika sederhana dan `Intl` bila perlu.
4. **Sitemap.** Tidak ada; bukan AC Phase 6A.

---

# 3. Kontrak yang Harus Dijaga

Kontrak ini sudah diuji test yang ada. Melanggarnya berarti test hijau berubah merah.

## C-1 — `PublicPlaceholderRoutesTest` harus mengosongkan baris `profil.visiMisi`

`tests/Feature/Public/PublicPlaceholderRoutesTest.php:128-167` punya array
`$expected` yang memuat `profil.visiMisi`. Setelah D-1, baris itu **dihapus**.
File docblock-nya menyatakan perubahan seperti ini memang bagian dari pekerjaan
modul, bukan sesudahnya.

## C-2 — `PublicRoutesTest` harus menghapus `/profil/visi-misi` dari dua dataset

`tests/Feature/Public/PublicRoutesTest.php:39-44` (dataset `public pages`) dan
`:76-81` (dataset `placeholder pages`) memuat `/profil/visi-misi`. Keduanya
dihapus. Halaman profil yang tetap ada dipindah secara sadar dari `placeholder
pages` ketika controllernya hidup.

## C-3 — `RbacFoundationTest` punya daftar permission hardcoded

`tests/Feature/Foundation/RbacFoundationTest.php:62+` memakai array `$expected`
dan `Expect::toBe(app(PermissionSeeder::class)->expectedCount())`. Menambah
permission **wajib** menambah baris di `$expected` dan memperbarui
`PermissionSeeder::PERMISSIONS` + `expectedCount()`, atau test merah.

## C-4 — Aturan `ArchitectureTest` yang mengikat

`tests/Unit/ArchitectureTest.php` memuat antara lain:

- setiap nama halaman yang diminta controller harus terdaftar di frontend;
- halaman mempertahankan folder di namanya (`admin/profil/sejarah/index`);
- `features/a/x` tidak boleh mengimpor `features/b/y`;
- hanya `rich-text.tsx` dan `event-json-ld.tsx` yang boleh `dangerouslySetInnerHTML`;
- sidebar mengimpor dari `@/routes/*`, bukan feature.

Pelanggaran = build gagal, bukan sekadar peringatan.

## C-5 — Test sidebar dan E2E harus diperbarui bersamaan

`tests/js/components/app-sidebar.test.tsx` (array `JADWAL_MISA_SUB_MENU`,
`MASTER_DATA_SUB_MENU`, asersi disclosure) dan `tests/e2e/master-data.spec.ts`
(href **dan** klik) mengunci group saat ini. Menambah group Profil Paroki
mewajibkan keduanya ditambah kasus serupa untuk group baru.

## C-6 — Nama komponen Inertia harus cocok path file

`pageName()` (`resources/js/lib/page-name.ts`) membuang `index` di root feature
dan mempertahankannya satu folder di bawah. Controller dan frontend menghitung
nama itu secara terpisah; `ArchitectureTest` yang menjembatani keduanya.

---

# 4. Ringkasan Task

Dipetakan dari roadmap §8–§13 ke enam area (A-01 … A-06) dan urutan §15.

| Tahap | Isi                                                                | Task roadmap (contoh)      |
| ----- | ------------------------------------------------------------------ | -------------------------- |
| A     | Migrasi, enum, model, factory, seeder, penegakan singleton         | 6A-08…6A-17                |
| B     | Permission, Policy, FormRequest dasar, kerangka route + sidebar    | 6A-40, 6A-41               |
| C     | Profil & Sejarah (backend + admin UI)                              | 6A-18…6A-25, 6A-50…6A-60   |
| D     | Wilayah (backend + admin UI)                                       | 6A-38, 6A-39, 6A-61…6A-64  |
| E     | Pastor (backend + admin UI)                                        | 6A-26…6A-31, 6A-65…6A-70   |
| F     | Struktur Kepengurusan (backend + admin UI)                         | 6A-32…6A-37, 6A-71…6A-77   |
| G     | Halaman publik + `ProfileSubNav` + SEO                             | 6A-85…6A-115               |
| H     | Hapus Visi & Misi, sidebar, permission, test, dokumentasi, regresi | 6A-01…6A-07, 6A-126…6A-149 |

---

# 5. Urutan Implementasi

> Aturan roadmap §15: selesaikan satu kelompok domain, verifikasi, lanjut. Jangan
> satu perubahan besar tanpa checkpoint.

## Tahap A — Database, enum, model, factory, seeder

### A.1 Migrasi (3 file, pola satu file banyak tabel seperti Phase 05)

1. `2026_*_create_parish_profile_tables.php`
   - **`parish_profiles`** (singleton): `id`; `name` string(150); `short_description` text null;
     `description` longText null (HTML tersanitasi); `history_description` longText null (HTML);
     `address` text null; `phone` string(50) null; `email` string(150) null; `website` string(255) null;
     `established_year` unsignedSmallInteger null; `logo_media_id` foreignId null →
     `media` `nullOnDelete`; `logo_alt` string(255) null; `cover_media_id` foreignId null →
     `media` `nullOnDelete`; `cover_alt` string(255) null; `map_url` string(500) null;
     `video_url` string(500) null; `is_published` boolean default false; `timestamps`.
   - **`parish_history_items`**: `id`; `title` string(200); `year` unsignedSmallInteger null;
     `date` date null; `description` longText null (HTML); `media_id` foreignId null → `media`
     `nullOnDelete`; `image_alt` string(255) null; `sort_order` integer default 0;
     `is_published` boolean default false; `timestamps`; index `['is_published','sort_order','year']`.
   - **`parish_service_areas`** (singleton): `id`; `content` longText null (HTML);
     `map_media_id` foreignId null → `media` `nullOnDelete`; `map_url` string(500) null;
     `is_published` boolean default false; `timestamps`.

2. `2026_*_create_pastor_tables.php`
   - **`pastors`**: `id`; `name` string(150); `title` string(50) null; `photo_media_id` foreignId null →
     `media` `nullOnDelete`; `photo_alt` string(255) null; `bio` longText null (HTML);
     `sort_order` integer default 0; `is_published` boolean default false; `timestamps`.
   - **`pastor_assignments`**: `id`; `pastor_id` foreignId → `pastors` **`cascadeOnDelete`**;
     `position` string(50); `start_year` unsignedSmallInteger null; `end_year` unsignedSmallInteger null;
     `is_current` boolean default false; `description` text null; `timestamps`;
     index `['pastor_id','is_current']`.

3. `2026_*_create_parish_board_tables.php`
   - **`parish_board_periods`**: `id`; `name` string(150); `start_year` unsignedSmallInteger null;
     `end_year` unsignedSmallInteger null; `description` text null; `is_active` boolean default false;
     `is_published` boolean default false; `sort_order` integer default 0; `timestamps`.
   - **`parish_board_members`**: `id`; `parish_board_period_id` foreignId → `parish_board_periods`
     **`cascadeOnDelete`**; `name` string(150); `position` string(100); `photo_media_id` foreignId null →
     `media` `nullOnDelete`; `photo_alt` string(255) null; `description` text null; `sort_order` integer
     default 0; `is_published` boolean default false; `timestamps`;
     index `['parish_board_period_id','sort_order']`.

Catatan migrasi: tidak ada kolom `active_marker`/generated column (tidak ada unique slot
yang perlu dirilis). Tidak ada slug. Jangan mengedit migrasi lama.

### A.2 Enum

- `app/Enums/PastorPosition.php` — string-backed: `PastorParoki='pastor_paroki'`,
  `PastorRekan='pastor_rekan'`, `Lainnya='lainnya'`; method `values(): array` dan `label()` yang
  membaca `lang/id/profil.php`. Menambah case = `lang` entry wajib.

### A.3 Model

- `App\Models\ParishProfile` — `#[Fillable([…])]`, `casts()` boolean, relasi `logoMedia()`,
  `coverMedia()`, scope `published()`, static `current(): self` (`firstOrCreate`).
- `App\Models\ParishHistoryItem` — relasi `media()`, `casts()` (`year` integer, `date` date,
  `is_published` boolean), scope `published()`, scope `ordered()` (`sort_order`, `year`, `id`).
- `App\Models\ParishServiceArea` — static `current(): self`, scope `published()`.
- `App\Models\Pastor` — relasi `assignments()` (`HasMany`), `currentAssignment()`, `photoMedia()`,
  scope `published()`, `ordered()`.
- `App\Models\PastorAssignment` — relasi `pastor()`, `casts()` (`start_year`/`end_year` integer,
  `is_current` boolean), scope `current()`.
- `App\Models\ParishBoardPeriod` — relasi `members()` (`HasMany`), scope `published()`,
  `active()`, `ordered()`.
- `App\Models\ParishBoardMember` — relasi `period()`, `photoMedia()`, scope `published()`, `ordered()`.

Semua relasi media memakai `BelongsTo(Media::class, '…_media_id')`.

### A.4 Factory & seeder

- Tujuh factory: `ParishProfileFactory`, `ParishHistoryItemFactory`, `ParishServiceAreaFactory`,
  `PastorFactory`, `PastorAssignmentFactory`, `ParishBoardPeriodFactory`, `ParishBoardMemberFactory`.
  Semua memakai Faker generik; **tidak** mengarang nama pastor/jabatan/tahun nyata.
- Seeder idempotent `ParishProfileSeeder` (opsional, hanya membuat profil kosong bila belum ada)
  dan/atau `DatabaseSeeder` tidak memanggil konten rekaan. Tandai sebagai sample bila diisi.

### A.5 Gate Tahap A

`php artisan migrate:fresh` di DB test, `migrate:rollback` bersih, `php artisan test --compact
--filter=Profil` belum ada (kosong) → minimal `tests/Feature/Profil/ParishProfileSchemaTest.php`
yang menguji kolom/FK/index ada dan `down()` reversible.

## Tahap B — Permission, Policy, FormRequest dasar, kerangka route + sidebar

### B.1 Permission (`database/seeders/PermissionSeeder.php`)

Tambahkan ke `PERMISSIONS` (lihat §13 untuk status grup keenam):

- `parishprofiles.view`, `parishprofiles.update`
- `parishhistories.view`, `parishhistories.create`, `parishhistories.update`, `parishhistories.delete`
- `pastors.view`, `pastors.create`, `pastors.update`, `pastors.delete`
- `boardperiods.view`, `boardperiods.create`, `boardperiods.update`, `boardperiods.delete`
- `boardmembers.view`, `boardmembers.create`, `boardmembers.update`, `boardmembers.delete`

Perbarui `RbacFoundationTest.php:62+` (`$expected`) dan `expectedCount()` otomatis. Perbarui
`docs/RBAC.md` §1.

### B.2 Policy

`ParishProfilePolicy`, `ParishHistoryItemPolicy`, `ParishServiceAreaPolicy`, `PastorPolicy`,
`PastorAssignmentPolicy`, `ParishBoardPeriodPolicy`, `ParishBoardMemberPolicy` — masing-masing
`viewAny/create/update/delete` memakai `$user->can('<permission>')` (bukan `hasPermissionTo`).
Untuk model singleton, `create`/`delete` tidak dipakai (tidak ada endpoint).

### B.3 FormRequest

Dibuat bertahap di tahap domainnya (C–F), dengan `authorize()` sebagai satu-satunya titik
otorisasi `store`/`update`.

### B.4 Kerangka route + sidebar

- Tambah blok `/admin/profil/*` di `routes/web.php` (dalam grup admin).
- Ganti `app-sidebar.tsx:63` menjadi group **Profil Paroki** (D-12) dengan import Wayfinder dari
  `@/routes/parish-profile`, `@/routes/parish-histories`, dst. Jalankan
  `php artisan wayfinder:generate --with-form`.

## Tahap C — Profil & Sejarah

### C.1 Backend

- `app/Concerns/ParishProfileValidationRules.php` (jika dipakai bersama) atau langsung di request.
- `UpdateParishProfileRequest` — `authorize()` `can('update', ParishProfile::class)`; `rules()`:
  `name` required max 150; `description`/`history_description` nullable string max 500000;
  `email` nullable email; `website`/`map_url`/`video_url` nullable url max 500;
  `established_year` nullable integer between (mis. 1000–`now.year`); `logo_media_id`/`cover_media_id`
  nullable exists `media` where `status = Ready`; `logo_alt`/`cover_alt` nullable max 255;
  `is_published` boolean. `prepareForValidation()` menyanitasi dua kolom HTML.
- `StoreParishHistoryItemRequest` / `UpdateParishHistoryItemRequest` — rule `year`/`date` koheren
  (boleh salah satu kosong, tapi `date` bila ada menang), `sort_order` integer, `media_id` exists Ready.
- `ParishProfileController` (`App\Http\Controllers\Profil`): `edit()` (`Gate::authorize('viewAny',
  ParishProfile::class)`, render `admin/profil/informasi/index`), `update()`.
- `ParishHistoryItemController`: `index` (list + sortir), `create`, `store`, `edit`, `update`, `destroy`.
- Resource admin: `ParishProfileResource`, `ParishHistoryItemResource` (+ `can` block).
- Resource publik: `PublicParishProfileResource`, `PublicParishHistoryItemResource`.

### C.2 Frontend admin

- `features/admin/profil/pages/informasi/index.tsx` — form informasi profil (nama, deskripsi singkat,
  deskripsi, kontak, website, tahun berdiri, logo/cover via `MediaField`, `map_url`, `video_url`,
  `is_published`), `noValidate`, `ChoiceSelect`, preview.
- `features/admin/profil/pages/sejarah/{index,tambah,ubah}.tsx` — list linimasa (Tanggal/Tahun, status,
  urutan) + form item + form narasi sejarah (bagian atas halaman).
- `features/admin/profil/components/*` — form profil, form item sejarah.
- `types.ts` — mirror resource.

## Tahap D — Wilayah

### D.1 Backend

- `UpdateParishServiceAreaRequest` — `content` nullable string max 500000 (HTML), `map_media_id`
  nullable exists Ready, `map_url` nullable url, `is_published` boolean. Sanitasi `content`.
- `ParishServiceAreaController`: `edit()`, `update()`. Otorisasi `parishprofiles.view/update`
  (lihat §13).
- `ParishServiceAreaResource` + `PublicParishServiceAreaResource`.

### D.2 Frontend admin

- `features/admin/profil/pages/wilayah/index.tsx` — form narasi + peta; catatan editorial bahwa
  data rinci komunitas/stasi/lingkungan dikelola modul Komunitas (Phase 7), sesuai 6A-64.

## Tahap E — Pastor

### E.1 Backend

- `StorePastorRequest` / `UpdatePastorRequest` — `name` required max 150, `title` nullable max 50,
  `bio` nullable string, `photo_media_id` nullable exists Ready, `photo_alt`, `sort_order`,
  `is_published`; sanitasi `bio`.
- `StorePastorAssignmentRequest` / `UpdatePastorAssignmentRequest` — `position` `Rule::enum(PastorPosition::class)`,
  `start_year`/`end_year` nullable integer + `after_or_equal`/koheren, `is_current` boolean,
  `description` nullable.
- `SavePastorAction` — simpan pastor + (opsional) assignment baru; `DB::transaction`.
- `SetCurrentPastorAction` — dalam transaksi: `PastorAssignment::query()->update(['is_current' => false])`
  lalu set satu assignment `is_current = true`; memenuhi roadmap §6.3/6A-29.
- `PastorController` (CRUD) + action method `activate` (POST) + assignment CRUD (`storeAssignment`,
  `updateAssignment`, `destroyAssignment`).
- Resource: `PastorResource` (+ nested `assignments`), `PastorAssignmentResource`,
  `PublicPastorResource` (dipisah aktif vs riwayat).

### E.2 Frontend admin

- `features/admin/profil/pages/pastor/{index,tambah,ubah}.tsx` — daftar pastor + status penugasan &
  publikasi; form data pastor + foto; pengelolaan riwayat penugasan; aksi "Tetapkan aktif"
  dengan konfirmasi bila mengubah assignment (6A-68/69).

## Tahap F — Struktur Kepengurusan

### F.1 Backend

- `StoreParishBoardPeriodRequest` / `UpdateParishBoardPeriodRequest` — `name` required,
  `start_year`/`end_year` nullable integer, `end_year >= start_year` bila keduanya ada,
  `is_active`, `is_published`, `sort_order`, `description`.
- `StoreParishBoardMemberRequest` / `UpdateParishBoardMemberRequest` — `parish_board_period_id`
  required exists, `name` required, `position` required, `photo_media_id` nullable exists Ready,
  `photo_alt`, `description`, `sort_order`, `is_published`.
- `ActivateBoardPeriodAction` — transaksi: nonaktifkan semua periode, aktifkan satu
  (PRD PROF-01 / AC-PROF-5).
- `ParishBoardPeriodController` (CRUD + `activate` POST) + `ParishBoardMemberController` (CRUD
  anggota, terikat periode).
- Resource: `ParishBoardPeriodResource` (+ nested `members`), `ParishBoardMemberResource`,
  `PublicBoardPeriodResource`, `PublicBoardMemberResource`.

### F.2 Frontend admin

- `features/admin/profil/pages/struktur/{index,periode-tambah,periode-ubah}.tsx` — daftar periode
  dengan indikator aktif/publikasi; form periode; daftar anggota per periode terpilih; form anggota;
  urutan; tampilan arsip periode lama; dialog hapus periode menyebut jumlah anggota (cascade).

## Tahap G — Halaman Publik + `ProfileSubNav` + SEO

### G.1 Controller publik

- `App\Http\Controllers\Profil\PublicParishProfileController` dengan method `index`, `sejarah`,
  `wilayah`, `pastor`, `struktur`. Setiap query memakai scope `published()` + `with()` relasi media;
  hanya kolom yang dibutuhkan. Ganti enam `Route::inertia` (kecuali `profil.visiMisi` yang dihapus).

### G.2 Halaman publik

- Rewrite `features/public/profil/pages/{index,sejarah,wilayah,pastor,struktur}.tsx` menjadi halaman
  dinamis: `PublicSection`/`SectionHeading`, kartu navigasi (tanpa Visi & Misi), empty state yang
  disengaja, `RichText` untuk kolom HTML.
- Komponen baru: `features/public/profil/components/profile-subnav.tsx` (DESIGN.md
  `ProfileSubNav`/`ParishProfileSidebar`), `pastor-card.tsx`, `organization-chart.tsx` (HTML
  relationship, bukan diagram), `history-timeline.tsx`, `profile-nav-card.tsx`.
- `Head`/`seo.tsx` per halaman untuk title/description/canonical; sinkron head-key dengan
  `app.blade.php`.

### G.3 Gate Tahap G

Test `tests/Feature/Profil/PublicParishProfilePageTest.php`: setiap halaman 200 + komponen benar;
record draft tidak tampil (XC-E3 analog); empty state; HTML tersanitasi.

## Tahap H — Hapus Visi & Misi, sidebar, test, dokumentasi, regresi

### H.1 Hapus Visi & Misi (D-1)

- Hapus `Route::inertia('/profil/visi-misi', …)` di `routes/web.php:38`.
- Hapus `resources/js/features/public/profil/pages/visi-misi.tsx`.
- `parish-footer.tsx`: hapus import `visiMisi` + entri link "Visi & Misi".
- `PublicRoutesTest`: hapus baris `/profil/visi-misi` dari `public pages` dan `placeholder pages`;
  pindahkan halaman profil yang lain dari `placeholder pages` secara sadar saat controllernya hidup.
- `PublicPlaceholderRoutesTest`: hapus `profil.visiMisi` dari `$expected`.
- Regenerasi Wayfinder.

### H.2 Sidebar final

- Group **Profil Paroki** collapsible, 5 anak: Informasi Profil (`parish-profile.edit`),
  Sejarah Paroki (`parish-histories.index`), Wilayah Pelayanan (`parish-service-area.edit`),
  Pastor Paroki (`pastors.index`), Struktur Kepengurusan (`board-periods.index`).
- Perbarui `tests/js/components/app-sidebar.test.tsx` dan `tests/e2e/master-data.spec.ts`
  (+ spec E2E baru untuk Profil Paroki).

### H.3 Tests

Lihat §10.

### H.4 Dokumentasi

- `docs/roadmap/README.md` (Phase 5 Done + Phase 6A).
- `docs/DECISIONS.md` D-52+ (semua divergensi §1).
- `docs/RBAC.md` §1.
- Laporan akhir format roadmap §17.

---

# 6. File yang Akan Terpengaruh

## 6.1 Dibuat — backend

```text
database/migrations/2026_*_create_parish_profile_tables.php
database/migrations/2026_*_create_pastor_tables.php
database/migrations/2026_*_create_parish_board_tables.php

app/Enums/PastorPosition.php

app/Models/ParishProfile.php
app/Models/ParishHistoryItem.php
app/Models/ParishServiceArea.php
app/Models/Pastor.php
app/Models/PastorAssignment.php
app/Models/ParishBoardPeriod.php
app/Models/ParishBoardMember.php

database/factories/ParishProfileFactory.php
database/factories/ParishHistoryItemFactory.php
database/factories/ParishServiceAreaFactory.php
database/factories/PastorFactory.php
database/factories/PastorAssignmentFactory.php
database/factories/ParishBoardPeriodFactory.php
database/factories/ParishBoardMemberFactory.php
database/seeders/ParishProfileSeeder.php        # opsional, idempotent, kosong

app/Policies/ParishProfilePolicy.php
app/Policies/ParishHistoryItemPolicy.php
app/Policies/ParishServiceAreaPolicy.php
app/Policies/PastorPolicy.php
app/Policies/PastorAssignmentPolicy.php
app/Policies/ParishBoardPeriodPolicy.php
app/Policies/ParishBoardMemberPolicy.php

app/Http/Requests/Profil/UpdateParishProfileRequest.php
app/Http/Requests/Profil/StoreParishHistoryItemRequest.php
app/Http/Requests/Profil/UpdateParishHistoryItemRequest.php
app/Http/Requests/Profil/UpdateParishServiceAreaRequest.php
app/Http/Requests/Profil/StorePastorRequest.php
app/Http/Requests/Profil/UpdatePastorRequest.php
app/Http/Requests/Profil/StorePastorAssignmentRequest.php
app/Http/Requests/Profil/UpdatePastorAssignmentRequest.php
app/Http/Requests/Profil/StoreParishBoardPeriodRequest.php
app/Http/Requests/Profil/UpdateParishBoardPeriodRequest.php
app/Http/Requests/Profil/StoreParishBoardMemberRequest.php
app/Http/Requests/Profil/UpdateParishBoardMemberRequest.php

app/Actions/Profil/SavePastorAction.php
app/Actions/Profil/SetCurrentPastorAction.php
app/Actions/Profil/ActivateBoardPeriodAction.php

app/Http/Controllers/Profil/ParishProfileController.php
app/Http/Controllers/Profil/ParishHistoryItemController.php
app/Http/Controllers/Profil/ParishServiceAreaController.php
app/Http/Controllers/Profil/PastorController.php
app/Http/Controllers/Profil/PastorAssignmentController.php
app/Http/Controllers/Profil/ParishBoardPeriodController.php
app/Http/Controllers/Profil/ParishBoardMemberController.php
app/Http/Controllers/Profil/PublicParishProfileController.php

app/Http/Resources/Profil/ParishProfileResource.php
app/Http/Resources/Profil/ParishHistoryItemResource.php
app/Http/Resources/Profil/ParishServiceAreaResource.php
app/Http/Resources/Profil/PastorResource.php
app/Http/Resources/Profil/PastorAssignmentResource.php
app/Http/Resources/Profil/ParishBoardPeriodResource.php
app/Http/Resources/Profil/ParishBoardMemberResource.php
app/Http/Resources/Public/Profil/PublicParishProfileResource.php
app/Http/Resources/Public/Profil/PublicParishHistoryItemResource.php
app/Http/Resources/Public/Profil/PublicParishServiceAreaResource.php
app/Http/Resources/Public/Profil/PublicPastorResource.php
app/Http/Resources/Public/Profil/PublicBoardPeriodResource.php
app/Http/Resources/Public/Profil/PublicBoardMemberResource.php

lang/id/profil.php
```

## 6.2 Dibuat — frontend

```text
resources/js/features/admin/profil/pages/informasi/index.tsx
resources/js/features/admin/profil/pages/sejarah/{index,tambah,ubah}.tsx
resources/js/features/admin/profil/pages/wilayah/index.tsx
resources/js/features/admin/profil/pages/pastor/{index,tambah,ubah}.tsx
resources/js/features/admin/profil/pages/struktur/{index,periode-tambah,periode-ubah}.tsx
resources/js/features/admin/profil/components/parish-profile-form.tsx
resources/js/features/admin/profil/components/history-item-form.tsx
resources/js/features/admin/profil/components/service-area-form.tsx
resources/js/features/admin/profil/components/pastor-form.tsx
resources/js/features/admin/profil/components/pastor-assignment-form.tsx
resources/js/features/admin/profil/components/board-period-form.tsx
resources/js/features/admin/profil/components/board-member-form.tsx
resources/js/features/admin/profil/types.ts

resources/js/features/public/profil/components/profile-subnav.tsx
resources/js/features/public/profil/components/profile-nav-card.tsx
resources/js/features/public/profil/components/history-timeline.tsx
resources/js/features/public/profil/components/pastor-card.tsx
resources/js/features/public/profil/components/organization-chart.tsx
resources/js/features/public/profil/types.ts

tests/Feature/Profil/ParishProfileSchemaTest.php
tests/Feature/Profil/ParishProfileTest.php
tests/Feature/Profil/ParishHistoryItemTest.php
tests/Feature/Profil/ParishServiceAreaTest.php
tests/Feature/Profil/PastorTest.php
tests/Feature/Profil/PastorAssignmentTest.php
tests/Feature/Profil/ParishBoardPeriodTest.php
tests/Feature/Profil/ParishBoardMemberTest.php
tests/Feature/Profil/PublicParishProfilePageTest.php
tests/Feature/Profil/ProfilAuthorizationTest.php
tests/js/features/admin/profil-parish-profile-form.test.tsx
tests/js/components/profile-nav.test.tsx
tests/e2e/profil-paroki.spec.ts
```

## 6.3 Diubah — backend

| File                                                          | Perubahan                                                                         |
| ------------------------------------------------------------- | --------------------------------------------------------------------------------- |
| `routes/web.php`                                              | Route publik profil → controller; hapus `profil.visiMisi`; blok `/admin/profil/*` |
| `database/seeders/PermissionSeeder.php`                       | +17 permission; `expectedCount()` otomatis                                        |
| `docs/RBAC.md`, `docs/DECISIONS.md`, `docs/roadmap/README.md` | Dokumentasi (Tahap H)                                                             |

## 6.4 Diubah — frontend

| File                                                       | Perubahan                                                 |
| ---------------------------------------------------------- | --------------------------------------------------------- |
| `resources/js/layouts/app/components/app-sidebar.tsx`      | Entry `Profil` → group collapsible Profil Paroki (5 anak) |
| `resources/js/layouts/public/components/parish-footer.tsx` | Hapus impor + link `visiMisi`                             |
| `resources/js/features/public/profil/pages/*.tsx`          | Placeholder → halaman dinamis                             |
| `resources/js/app.tsx`                                     | **Tidak berubah** (resolver otomatis dari nama komponen)  |

## 6.5 Diubah — test

| File                                                   | Perubahan                                                                                                    |
| ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------ |
| `tests/Feature/Public/PublicRoutesTest.php`            | Hapus `/profil/visi-misi` dari dua dataset; pindah halaman profil keluar dari `placeholder pages` saat hidup |
| `tests/Feature/Public/PublicPlaceholderRoutesTest.php` | Hapus `profil.visiMisi` dari `$expected`                                                                     |
| `tests/Feature/Foundation/RbacFoundationTest.php`      | Tambah 17 permission ke `$expected`                                                                          |
| `tests/js/components/app-sidebar.test.tsx`             | Tambah `PROFIL_PAROKI_SUB_MENU` + asersi disclosure                                                          |
| `tests/e2e/master-data.spec.ts`                        | Tidak berubah logikanya; tambah spec Profil Paroki baru                                                      |

## 6.6 Dihapus

```text
resources/js/features/public/profil/pages/visi-misi.tsx
```

---

# 7. Dependency / Configuration Impact

## 7.1 Dependency baru: **nol**

Tidak ada composer/npm baru. Media, sanitizer, Tiptap, TanStack, shadcn semuanya sudah ada.
Tidak menambah `date-fns`; tahun/linimasa tidak butuh parser.

## 7.2 Konfigurasi yang berubah

| Konfigurasi                             | Perubahan                                                            |
| --------------------------------------- | -------------------------------------------------------------------- |
| `database/seeders/PermissionSeeder.php` | +17 permission; `expectedCount()` mengikuti `count(PERMISSIONS)`     |
| `config/html.php`                       | **Tidak berubah** — allowlist sudah cukup untuk narasi/biografi/peta |
| `config/site-settings.php`              | **Tidak berubah** — profil tidak disimpan di settings (D-2)          |
| `config/media.php`                      | **Tidak berubah**                                                    |
| `resources/js/app.tsx`                  | **Tidak berubah**                                                    |

## 7.3 Wayfinder

Setiap perubahan route/controller mewajibkan `php artisan wayfinder:generate --with-form`
(Vite plugin juga regenerate saat dev/build). Aturan:

- Jangan buat `Route::redirect()` **tanpa nama** (Wayfinder gagal build; D-49).
- Jangan tinggalkan route lama bernama sama dengan yang baru (dua export → build gagal).
- `/admin/profil` redirect **dinamai** (mis. `parish-profile.index`).

## 7.4 Media & singleton

- Media bersifat opsional; `*_media_id` `nullOnDelete`. Jangan hapus file media bersama konten
  (roadmap §6.7). Validasi server: `Rule::exists('media','id')->where('status', MediaStatus::Ready->value)`.
- Singleton `parish_profiles`/`parish_service_areas` ditegakkan aplikasi + test; tidak ada trik DB.

## 7.5 Inertia shared props

`HandleInertiaRequests::share()` mungkin perlu mengekspos ringkasan profil publik (nama + logo)
untuk navbar/footer. Bila ditambahkan: jaga tetap kecil (ARCHITECTURE Part C §5) dan ingat
D-31 §9 (halaman error dirender tanpa shared props — komponen shell harus tahan props kosong).

---

# 8. Risiko dan Mitigasi

| #    | Risiko                                                    | Dampak                                                | Mitigasi                                                                                             |
| ---- | --------------------------------------------------------- | ----------------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| R-1  | Tabrakan nama route `profil.*` publik vs admin            | Wayfinder gagal build / link salah arah               | Prefix `parish-*`/`pastors.*`/`board-*` (m-1); cek `php artisan route:list` + `LocationRedirectTest` |
| R-2  | Nama komponen Inertia ≠ file (bug Master Data)            | Halaman tak bereaksi, 200 dengan komponen tak dikenal | Ikuti `pageName()`; `ArchitectureTest` menangkap; jalankan setelah tiap controller baru              |
| R-3  | Hapus `/profil/visi-misi` memecahkan test hijau           | Suite merah                                           | C-1/C-2/H.1: update `PublicRoutesTest` & `PublicPlaceholderRoutesTest` dalam perubahan yang sama     |
| R-4  | Permission baru tanpa update `RbacFoundationTest`         | Suite merah                                           | C-3: perbarui `$expected` + `expectedRows` bersamaan; `seedRolesAndPermissions()` di test            |
| R-5  | Dua record singleton (profil/wilayah)                     | Data ganda di publik                                  | `firstOrCreate` + test `assertDatabaseCount(...,1)`                                                  |
| R-6  | Lebih dari satu pastor/periode aktif                      | Melanggar PRD PROF-01 / AC-PROF-5                     | Action transaksional + test (set aktif → yang lain nonaktif)                                         |
| R-7  | N+1 relasi media/anggota                                  | Eloquent strict mode melempar di luar produksi        | `with()`/`withCount()`; jangan `select()` setelah `withCount()` (D-41)                               |
| R-8  | HTML rich text tak tersanitasi                            | XSS                                                   | Sanitasi di `prepareForValidation()`; render hanya via `RichText`                                    |
| R-9  | Data pribadi/pastor/jabatan rekaan                        | Melanggar roadmap §5/§10 & PRD larangan mengarang     | Seeder kosong; placeholder `[ISI: …]` di UI; kontak pengurus dihapus (D-7)                           |
| R-10 | Duplikasi domain Komunitas (Phase 7) di Wilayah           | Pekerjaan ganda / konflik skema                       | Wilayah = satu narasi + peta (D-5); catatan editorial 6A-64                                          |
| R-11 | Sidebar group baru merusak test JS/E2E                    | Suite merah                                           | C-5: update test bersamaan; `npm run build` sebelum E2E                                              |
| R-12 | `npm run check:fix` pernah menghapus komentar/`<Heading>` | Kehilangan kode tanpa sadar                           | Jalankan, baca `git diff` sebelum menyimpan (AGENTS.md)                                              |
| R-13 | Migrasi tidak reversible                                  | `migrate:fresh` gagal                                 | Uji `migrate:fresh` + `migrate:rollback` di DB test                                                  |

---

# 9. Verification Plan

## 9.1 Gate per tahap

```bash
# Tahap A
php artisan migrate:fresh --env=testing
php artisan test --compact tests/Feature/Profil/ParishProfileSchemaTest.php
php artisan migrate:rollback --env=testing && php artisan migrate --env=testing

# Tahap B–G (setiap tahap, ganti filter sesuai file)
./vendor/bin/pint --dirty --format agent
php artisan test --compact tests/Feature/Profil
npm run check
npm run types:check
php artisan wayfinder:generate --with-form

# Tahap H
php artisan test --compact tests/Feature/Public tests/Feature/Foundation
npm run build
npm run e2e          # JANGAN npx playwright test bare
```

## 9.2 Gate penuh di akhir

```bash
composer test        # config:clear, pint --parallel --test, phpstan analyse, artisan test
composer ci:check    # npm run check + npm run types:check + artisan test
npm run build
npm run e2e
```

> `npm run e2e` menyajikan `public/build`, jadi **`npm run build` dulu**. Jangan tinggalkan
> `php artisan serve` di port 8000 (E2E login gagal; `ss -ltnp | grep 8000`).

## 9.3 Pemeriksaan manual khusus

- Viewport 360, 390, 768, 1024, 1440 px (6A-114).
- Keyboard-only untuk form & subnav (6A-81); status tidak hanya dibedakan warna (6A-82).
- Pastikan tidak ada link "Visi & Misi" di navbar/footer/halaman profil (6A-148).
- Cek `php artisan route:list --path=profil` untuk nama route unik.

---

# 10. Test Matrix

## 10.1 Feature — skema & otorisasi

- `ParishProfileSchemaTest` — kolom/FK/index tujuh tabel; `down()` reversible.
- `ProfilAuthorizationTest` — guest redirect ke login; user tanpa permission 403;
  `super_admin` lolos; `FormRequest::authorize()` menolak `update`.

## 10.2 Feature — profil & sejarah

- `ParishProfileTest` — `update` menyimpan + sanitasi HTML; email/url invalid ditolak;
  `established_year` di luar rentang ditolak; singleton tetap satu; `logo_media_id` non-Ready ditolak.
- `ParishHistoryItemTest` — CRUD; tahun/tanggal; `sort_order`; publik tidak melihat draft;
  media FK berperilaku.
- `PublicParishProfilePageTest (profil & sejarah)` — 200 + komponen; draft tidak tampil (XC-E3 analog);
  empty state; HTML aman.

## 10.3 Feature — wilayah

- `ParishServiceAreaTest` — simpan narasi + peta; sanitasi; singleton; publik hanya `is_published`.

## 10.4 Feature — pastor

- `PastorTest` — CRUD; media; publikasi; urutan.
- `PastorAssignmentTest` — rentang tahun; `position` enum; **tepat satu `is_current`**;
  halaman publik memisah aktif vs riwayat; assignment lama tidak terhapus (AC-PROF-2).

## 10.5 Feature — struktur

- `ParishBoardPeriodTest` — CRUD; `end_year >= start_year`; **tepat satu `is_active`** (AC-PROF-5);
  arsip tetap ada.
- `ParishBoardMemberTest` — anggota terikat periode; urutan `sort_order`; hapus periode
  cascade + dialog menghitung anggota.

## 10.6 Frontend (Vitest)

- Form profil/sejarah menampilkan pesan validasi & status submit (6A-140).
- `app-sidebar.test.tsx` — group Profil Paroki: href tiap anak, auto-open, disclosure button (6A-141).
- Komponen `profile-subnav` — active state (6A-141).

## 10.7 E2E

- `tests/e2e/profil-paroki.spec.ts` — admin membuka submenu & berpindah 5 halaman (6A-144);
  simpan profil → muncul di publik setelah dipublikasikan (6A-145); kelola sejarah/pastor/periode/
  anggota alur utama (6A-146); pengunjung navigasi 5 halaman publik (6A-147); tidak ada link Visi &
  Misi (6A-148).

## 10.8 Regresi

- `tests/Feature/MassSchedule`, `tests/Feature/News`, `tests/Feature/Agenda` tetap hijau (6A-149).

---

# 11. Acceptance Criteria

## 11.1 PRD §7.2

- **AC-PROF-1** semua sub-halaman publik dapat diakses & mobile-friendly → Tahap G + viewport test.
- **AC-PROF-2** ganti pastor/periode tanpa menghapus data lama → D-3 + action + `PastorAssignmentTest`.
- **AC-PROF-3** struktur tampil sesuai `sort_order` admin → `ParishBoardMemberTest`.
- **AC-PROF-4** tiap sub-halaman punya URL dibagikan + metadata SEO → Tahap G.
- **AC-PROF-5** mengaktifkan periode B mengarsipkan A → `ActivateBoardPeriodAction` + test.

## 11.2 Roadmap §13 (Definition of Done)

- Semua route publik dalam scope memakai data dinamis.
- Sidebar memiliki submenu Profil Paroki (5 anak) — tanpa overview (D-9).
- Semua write tervalidasi + diotorisasi backend.
- Status publikasi membatasi data publik.
- Tidak ada Visi & Misi di route/menu/field/migration/seeder/halaman (D-1).
- Data sejarah/pastor/wilayah/kepengurusan tidak direkayasa.
- Media memakai infrastruktur yang ada; tidak ada duplikasi domain Komunitas.
- Tidak ada regresi Phase 4–5.
- Test, typecheck, lint/format, E2E, build lulus.
- Tampilan diperiksa mobile→desktop; dokumentasi & roadmap diperbarui.

## 11.3 Roadmap §11 (Keamanan)

- 6A-116…6A-125 tercakup oleh FormRequest + Policy + sanitasi + validasi media/URL + query
  `published()` + FK eksplisit + transaksi + tanpa log data sensitif.

---

# 12. Yang Sengaja Tidak Dikerjakan

- **Visi & Misi** dalam bentuk apa pun (D-1).
- **Halaman overview admin** (D-9).
- **Tabel/hierarki wilayah/stasi/lingkungan** — milik Komunitas Phase 7 (D-5).
- **CRUD komunitas/jenis komunitas** — Phase 7. Phase 6A hanya menyiapkan kontrak tampilan.
- **Media library page** — hanya picker yang sudah ada.
- **REST API terpisah** — data via Inertia.
- **Homepage aggregation / dashboard analytics** (roadmap §6A-49).
- **Visi/misi pada `parish_profiles`** dan kolom apa pun yang tidak dipakai AC.
- **Sitemap/robots** — bukan AC Phase 6A.
- **Kontak pengurus (`phone`/`show_contact`)** (D-7).
- **Seeder konten nyata** (m-7).

---

# 13. Keputusan yang Masih Menunggu Konfirmasi

Keputusan berikut **tidak menghambat** Tahap A, tetapi perlu dikonfirmasi sebelum
Tahap B (permission) dan Tahap D (wilayah).

### m-10 — Grup permission untuk `parish_service_areas`

Keputusan D-8 menyebut **lima grup entity** (`parishprofiles`, `parishhistories`,
`pastors`, `boardperiods`, `boardmembers`). Tabel `parish_service_areas` adalah
entity keenam. Usulan: **gabungkan ke `parishprofiles.*`** (wilayah adalah bagian
halaman profil; hanya `view`/`update`) sehingga tetap lima grup. Alternatif: grup
keenam `parishserviceareas.*`. **Rekomendasi: gabung ke `parishprofiles.*`.**

### m-11 — Permission `parishprofiles` pada singleton

Karena singleton, hanya `parishprofiles.view` dan `parishprofiles.update` yang
dibuat (tidak ada `create`/`delete` endpoint) — sesuai RBAC.md "tidak ada permission
untuk endpoint yang tidak ada". Mohon konfirmasi.

### m-12 — Penempatan narasi sejarah

D-13 menaruh narasi pada `parish_profiles.history_description`. Alternatif: record
`parish_profile_sections` terpisah. **Rekomendasi: kolom pada profil** (sudah dipilih).

### m-13 — Aksi "Tetapkan pastor aktif" dan "Aktifkan periode"

Route `POST` (bukan `GET`), mengikuti `events.duplicate` dan roadmap §9 (hindari
mutasi via GET). Nama usulan: `pastors.assignments.activate`, `board-periods.activate`.
Mohon konfirmasi penamaan.

### m-14 — Waktu muat awal `parish_profile`

Bila nanti ditambahkan cache, `NextMassResolver`-style tidak berlaku; profil tidak
boros. Tidak ada cache untuk Phase 6A.

---

# 14. Status

| Item                         | Nilai                                                       |
| ---------------------------- | ----------------------------------------------------------- |
| Dokumen roadmap              | `docs/roadmap/phase-6A-parish-profile.md` ✅ ada            |
| Audit codebase               | ✅ selesai (commit `d5d810f`)                                |
| Keputusan pengguna           | ✅ 13 pertanyaan terjawab                                    |
| Kode implementasi            | ✅ **selesai** di branch `feat/phase-06a-parish-profile`     |
| Verifikasi                   | ✅ `composer test` + `composer ci:check` + `npm run build` + `npm run e2e` hijau |
| Keputusan menunggu           | — (m-10…m-13 diselesaikan sesuai rekomendasi)                |

**Hasil implementasi (ringkas):**

- 7 tabel lintas 3 migrasi; enum `PastorPosition`; 7 model + 7 factory; schema test.
- 18 permission baru (`parishprofiles`, `parishhistories`, `pastors`, `boardperiods`,
  `boardmembers`); 7 Policy.
- Admin `/admin/profil/*` (Informasi, Sejarah, Wilayah, Pastor + penugasan, Struktur)
  dengan FormRequest, Resource, dan 2 Action transaksional.
- 5 halaman publik dinamis + `ProfileSubNav` + SEO.
- Sidebar group **Profil Paroki** collapsible; Visi & Misi dihapus total.
- Test: 997 Pest + 266 Vitest + 64 Playwright lulus.

> Sesuai roadmap §15, implementasi dikerjakan per checkpoint Tahap A→H, dengan
> verifikasi di setiap tahap. Jangan menandai phase selesai hanya karena file
> sudah dibuat; status selesai didasarkan pada acceptance criteria, hasil test,
> dan build yang benar-benar diverifikasi (roadmap §17).
