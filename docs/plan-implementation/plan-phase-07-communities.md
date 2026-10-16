# Implementation Plan — PHASE 07

> **Project:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau\
> **Phase:** 07 — Communities (Modul Komunitas)\
> **Status:** Disusun — menunggu persetujuan implementasi\
> **Branch (usulan):** `feat/phase-07-communities`\
> **Spesifikasi:** `docs/roadmap/phase-07-communities.md`\
> **Sumber kebenaran:** `PRD.md` → `ARCHITECTURE.md` → `DESIGN.md` → `AGENTS.md` → `DECISIONS.md`

Dokumen roadmap Phase 07 menjelaskan _apa_ yang harus ada. Dokumen ini menjelaskan
_bagaimana_ perubahannya, dalam urutan apa, file mana yang tersentuh, dan bagaimana
diverifikasinya. Dokumen ini adalah pelengkap roadmap, bukan pengganti.

> Dokumen ini disusun setelah audit codebase terhadap `main` pada commit
> `578182d` (`Merge pull request #8 from Rumahkodingku/feat/phase-06b-parish-service`)
> dan **10 keputusan yang sudah dijawab pengguna**. Seluruh keputusan di §1
> mengikat implementasi. Belum ada satu baris kode implementasi Phase 07 yang
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

Sepuluh pertanyaan diajukan sebelum penyusunan plan ini. Jawaban berikut mengikat
seluruh isi dokumen dan sudah dikonfirmasi pemilik proyek.

| #    | Pertanyaan                                                                    | Keputusan                                                                                                                                                         | Alasan yang dipakai                                                                                                                                                                                                                                  |
| ---- | ----------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| D-1  | Cara menyimpan logo & thumbnail                                               | **FK `*_media_id` + kolom alt** (bukan `logo_path`/`thumbnail_path`)                                                                                              | Seluruh modul terbaru (6A: `parish_profiles.logo_media_id`, `pastors.photo_media_id`; 6B: `services.thumbnail_media_id`) memakai tabel `media` + `MediaField`. Roadmap §5.2.3 menyebut `*_path`; ini **divergensi sadar** yang dicatat di DECISIONS. |
| D-2  | Cakupan skema `areas`                                                         | **Diperluas:** `type` = `wilayah` \| `lingkungan` \| `stasi` + `leader_name`, `contact_phone`, `show_contact`, `prayer_schedule`                                  | Mengikuti PRD §9.9/Lampiran C. Roadmap §5.3 hanya menyebut dua tipe tanpa field pengurus; divergensi dicatat di DECISIONS.                                                                                                                           |
| D-3  | Aturan hierarki dengan `stasi`                                                | **`lingkungan` hanya boleh berparent `wilayah`**; `wilayah`/`stasi` → `parent_id = null`; maks 2 tingkat                                                          | PRD COM-02 persis. `stasi` setara top-level dan tidak boleh memiliki `lingkungan`. Cegah siklus; lingkungan tidak boleh punya anak.                                                                                                                  |
| D-4  | Penempatan navigasi admin                                                     | **`Komunitas` = link datar** → `/admin/komunitas`; **`Wilayah & Lingkungan` + `Kategori Komunitas`** masuk grup **Master Data**                                   | Roadmap §6.1 mewajibkan menu Komunitas + submenu Kategori Komunitas di Master Data. Wilayah/Lingkungan tidak disebut; menaruhnya di Master Data menempatkan semua tabel referensi bersama.                                                           |
| D-5  | Filter area di direktori publik                                               | **Disertakan** (filter kategori + area)                                                                                                                           | Roadmap §7.1/AC-PUB-04 opsional "jika relevan"; umat menemukan komunitas per wilayah. Query divalidasi & query-string dipertahankan.                                                                                                                 |
| D-6  | Soft delete                                                                   | **Ya** untuk `communities`, `community_categories`, `areas`; slug **tidak** dipakai ulang                                                                         | Konsisten dengan `locations`/`service_categories`. Muncul route publik `{community:slug}`, jadi mengikuti aturan D-44 (slug tetap unik lintas soft delete). Tanpa generated `active_marker`.                                                         |
| D-7  | Strategi seeding                                                              | **Demo saja** — tanpa seeder produksi; `DemoContentSeeder` mengisi contoh (tanpa kontak/nama nyata)                                                               | Roadmap §3.2/§17 melarang mengisi semua jenis komunitas sebagai data aktif otomatis (prinsip D-34). D-53 sudah menyediakan pola demo + guard `demo.enabled`.                                                                                         |
| D-8  | CTA kontak di detail publik                                                   | **Fallback ke `site_settings`** (`contact_phone`/`contact_whatsapp`)                                                                                              | Pola Pelayanan D-54 butir 10: satu sumber kontak global. Tampil hanya bila nilainya ada; tidak ada nomor palsu.                                                                                                                                      |
| D-9  | Semantik `published_at`                                                       | **Set sekali** saat `is_published` false→true dan masih null; tidak diubah saat unpublish/republish                                                               | Pola `SavePostAction` (PRD §9.6). `published_at` **tidak** diterima dari request.                                                                                                                                                                    |
| D-10 | Paparan field personal area (`leader_name`/`contact_phone`/`prayer_schedule`) | **Dirender di halaman detail komunitas** dengan privasi server-side; `leader_name` + `contact_phone` dihilangkan dari payload Inertia bila `show_contact = false` | **Ekstensi sadar** atas roadmap §7 (tidak ada halaman area publik). Menegakkan XC-P1 (kontak pengurus/ketua hanya bila `show_contact = true`). Menambah AC + test privasi area; dicatat di DECISIONS.                                                |

Nomor D-1 … D-10 adalah **nomor keputusan lokal dokumen ini**, bukan nomor `D-xx`
di `docs/DECISIONS.md`. Penomoran resmi Phase 07 dimulai dari **D-55**, karena
D-54 adalah nomor terakhir yang terpakai.

## 1.1 Pilihan minor yang diasumsikan

Diasumsikan sesuai rekomendasi; dapat dibatalkan kapan saja tanpa invalidasi
seluruh plan (lihat §14 untuk detail).

| #    | Pilihan minor            | Nilai                                                                                                                                                                                                                   |
| ---- | ------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| m-1  | Nama route               | `communities.*`, `community-categories.*`, `areas.*`                                                                                                                                                                    |
| m-2  | Nama halaman Inertia     | `admin/komunitas`, `admin/komunitas/{tambah,ubah}`, `admin/master-data/kategori-komunitas/{index,tambah,ubah}`, `admin/master-data/wilayah-lingkungan/{index,tambah,ubah}`, `public/komunitas`, `public/komunitas/show` |
| m-3  | Slug & soft delete       | Slug tetap unik lintas soft delete (D-44); `Rule::unique` **tanpa** `withoutTrashed()`; tanpa `active_marker`                                                                                                           |
| m-4  | Sumber `published_at`    | Dikelola server; field tidak ada di rules/validasi request                                                                                                                                                              |
| m-5  | Representasi status/tipe | Enum PHP backed: `CommunityStatus` (`forming`/`active`/`inactive`), `AreaType` (`wilayah`/`lingkungan`/`stasi`)                                                                                                         |
| m-6  | Field naratif            | Textarea biasa (plain text) untuk `description`/`vision`/`mission`/`eligibility`/`joining_info`; **tidak** menambah field HTML/Tiptap baru, `config/html.php` tidak berubah                                             |
| m-7  | Validasi kontak          | `App\Support\PhoneNumber` yang sudah ada (dipakai Pelayanan)                                                                                                                                                            |
| m-8  | Penggantian gambar       | Form mengirim `*_media_id` baru; baris media lama **tidak** dihapus otomatis (media pipeline dipakai bersama)                                                                                                           |
| m-9  | Hapus komunitas          | Soft delete; pivot `community_area` ikut dibersihkan; file tidak dihapus paksa (baris `media` dipertahankan)                                                                                                            |
| m-10 | Selector area            | Multi-pilih + pencarian; disimpan via `sync()` pada pivot                                                                                                                                                               |
| m-11 | Daftar admin             | TanStack v9, `manualSorting: true`, server-sort via `SortsAdminList`                                                                                                                                                    |
| m-12 | Filter area publik       | Area difilter berdasarkan **slug** (pola `PublicPostController::filteredCategory`)                                                                                                                                      |
| m-13 | SEO detail               | `components/seo.tsx`; judul = "{nama} — {parish_name}"                                                                                                                                                                  |
| m-14 | Permission               | +12 (69 → 81): `communities.*`, `communitycategories.*`, `areas.*`                                                                                                                                                      |
| m-15 | Demo content             | `DemoContentSeeder` mengisi 1–2 kategori, beberapa area (wilayah+lingkungan+stasi), komunitas contoh aktif & published; tidak ada kontak/nama nyata                                                                     |

---

# 2. Kondisi Codebase Saat Ini

Audit terhadap `main` pada commit `578182d`. Phase 1–6B sudah selesai dan hijau.

## 2.1 Fondasi yang sudah ada dan cukup — **diaudit, bukan ditulis ulang**

| Area               | Status aktual                                                                                          | File                                                                          |
| ------------------ | ------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------- |
| Timezone           | `app.timezone = 'UTC'` + `display_timezone = 'Asia/Pontianak'`; datetime dikirim ISO 8601              | `config/app.php`, `HandleInertiaRequests.php`                                 |
| Strict mode        | `Model::shouldBeStrict(! isProduction())` — lazy load melempar                                         | `AppServiceProvider.php`                                                      |
| Gate `/admin`      | `['auth','verified',EnsureAccountIsActive,EnsureAdminAccess]` + `Gate::before` (`null`, bukan `false`) | `routes/web.php:114`, `AppServiceProvider.php`                                |
| Media pipeline     | `MediaField` + `useMediaUpload`; `Media::url($variant)`; original privat, varian publik                | `components/media-field.tsx`, `hooks/use-media-upload.ts`, `config/media.php` |
| Rich text          | Tiptap + `RichText`; sanitasi `HtmlSanitizer` + `config/html.php`                                      | `components/rich-text-editor.tsx`, `rich-text.tsx`                            |
| Permission         | **69** permission; nama `entity.verb` satu kata; `RbacFoundationTest` mengunci daftar                  | `database/seeders/PermissionSeeder.php:41`                                    |
| Sorting list admin | `SortsAdminList::applyAdminSort()` — whitelist, tie-breaker `id` terakhir                              | `app/Concerns/SortsAdminList.php`                                             |
| List prop          | `array_values(Resource::collection(…)->resolve())` untuk list non-paginate (D-39)                      | Controller Berita/Agenda/Profil/Pelayanan                                     |
| Layout resolution  | `app.tsx` memilih layout dari nama komponen; halaman hanya `Component.layout = { breadcrumbs }`        | `resources/js/app.tsx`                                                        |
| SEO                | `components/seo.tsx` + tag OG/`data-inertia` di `app.blade.php`                                        | `resources/js/components/seo.tsx`, `resources/views/app.blade.php`            |
| UI primitives      | `tabs`, `switch`, `collapsible`, `dialog`, `table`, `badge`, `select`, `checkbox`, `dropdown-menu`     | `resources/js/components/ui/`                                                 |
| Kontak             | `SiteSettingsService` + kunci `contact_phone`/`contact_whatsapp` (satu sumber)                         | `config/site-settings.php`, `app/Services/SiteSettingsService.php`            |
| Phone util         | `PhoneNumber::isValid()` / `toTel()` / `toWhatsApp()`                                                  | `app/Support/PhoneNumber.php`                                                 |
| Navigasi publik    | Navbar & footer sudah menautkan `@/routes/komunitas` (nama route harus dipertahankan)                  | `layouts/public/components/{parish-navbar,parish-footer}.tsx`                 |
| Testing            | Pest 4.7 (`seedRolesAndPermissions()`), Vitest (`tests/js/`), Playwright (`tests/e2e/`)                | `tests/Pest.php`, `vite.config.ts`, `playwright.config.ts`                    |

## 2.2 Yang sudah ada tapi harus diganti — pekerjaan utama Phase 07

| Area                     | Kekurangan yang ditemukan                                                                                                                                  |
| ------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Route publik komunitas   | `routes/web.php:78-81` masih `Route::inertia('public/komunitas')` dan **closure** `komunitas.show` yang menjawab 200 untuk slug apa pun (melanggar XC-E3). |
| Halaman publik komunitas | `features/public/komunitas/pages/{index,show}.tsx` placeholder `[ISI: …]` (hanya `SectionHeading`).                                                        |
| Sidebar `Komunitas`      | `app-sidebar.tsx:115` `{ title: 'Komunitas', icon: Users }` **tanpa `href`** → baris nonaktif "belum".                                                     |
| Sidebar `Master Data`    | 4 anak (Lokasi, Kategori Berita, Kategori Agenda, Kategori Pelayanan); belum ada `Kategori Komunitas` maupun `Wilayah & Lingkungan`.                       |
| Test placeholder publik  | `PublicPlaceholderRoutesTest` memuat baris `komunitas` (detail placeholder) yang harus dipindah ke test modul.                                             |
| Test RBAC                | `RbacFoundationTest` punya array `$expected` hardcoded — ditambah 12 permission, test merah sampai diperbarui.                                             |
| Test sidebar & E2E       | `tests/js/components/app-sidebar.test.tsx` (baris ~335: `Komunitas` harus **tetap null** saat ini) dan `tests/e2e/master-data.spec.ts`.                    |

## 2.3 Yang belum ada dan harus dibuat

**4 tabel** · **3 model** · **3 factory** · **3 policy** · **~6 FormRequest** ·
**2 Action** · **2 enum** · **4 controller admin + 1 publik** · **~10 `JsonResource`** ·
**~30 route** · **~11 halaman Inertia** · **~15 komponen frontend** · **1 file `lang/id`** ·
**~9 file test** · **perubahan sidebar, RBAC, DECISIONS, DemoContentSeeder**

**Tidak ada** satu pun tabel/model/migration/route admin komunitas saat ini. Tabel
`locations` (tempat misa) dan `parish_service_areas` (singleton narasi + peta,
D-52) **bukan** pengganti `areas` — tujuan ketiganya berbeda dan tidak boleh
disatukan.

## 2.4 Konvensi yang harus diikuti — bukan landmark baru

| Konvensi  | Bentuk persisnya di repo                                                                                 | Konsekuensi untuk Phase 07                                                                                                           |
| --------- | -------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| Fillable  | Atribut **`#[Fillable([…])]`**, bukan properti (dicek `ArchitectureTest`)                                | Tiga model baru wajib memakai atribut                                                                                                |
| Cast      | Method `protected function casts(): array`                                                               | `status`/`type` → enum; `is_active`/`is_published`/`show_contact` → `boolean`; `published_at` → `datetime`; `sort_order` → `integer` |
| Resource  | `JsonResource` terpisah admin vs publik (`App\Http\Resources\Komunitas\…` dan `…\Public\Komunitas\…`)    | Jangan pernah mengirim model mentah                                                                                                  |
| List prop | **`array_values(Resource::collection(…)->resolve())`** untuk list non-paginate (D-39)                    | Opsi kategori/area, daftar area, section detail                                                                                      |
| Sorting   | `applyAdminSort($query, $request, $whitelist, $default)`; whitelist wajib; tie-breaker `id` terakhir     | Untuk daftar komunitas & kategori & area                                                                                             |
| Otorisasi | `FormRequest::authorize()` untuk `store`/`update`; `Gate::authorize()`/Policy untuk lainnya              | Tidak pernah di dua tempat                                                                                                           |
| Policy    | Satu Policy per model; `super_admin` lewat `Gate::before` → **wajib `can()`**, bukan `hasPermissionTo()` | `CommunityPolicy`, `CommunityCategoryPolicy`, `AreaPolicy`                                                                           |
| Action    | Hanya untuk operasi multi-model/transaksi                                                                | `SaveCommunityAction`, `DeleteCommunityAction`                                                                                       |
| Media     | `MediaField` mengirim `*_media_id`; validasi `Rule::exists('media','id')->where('status', Ready)`        | Logo & thumbnail; eager-load `*.variants` saat mengirim URL                                                                          |
| Slug      | `posts`/`events`/`services` mempertahankan slug saat soft delete (D-44/D-45)                             | `communities` mengikuti aturan yang sama; **tanpa** `active_marker`                                                                  |
| Route     | Eksplisit `Route::get/post/patch/delete` + `->name()` dalam satu `routes/web.php`                        | Route admin di dalam grup `/admin`; nama unik; **tidak ada redirect tanpa nama**                                                     |
| Halaman   | Tipis; `Index.layout = { breadcrumbs }`; form & tabel di `components/`/`pages/…/components`              | Mengikuti `features/admin/pelayanan/`                                                                                                |
| Form      | `<form noValidate>` — **wajib**                                                                          | Server satu-satunya validator; pesan Indonesia dari `lang/id/validation.php`                                                         |
| Copy      | Bahasa Indonesia                                                                                         | Semua label, flash, dan `messages()`; label dari `lang/id/komunitas.php`                                                             |
| Enum      | Backed enum + `values()` (pola `LocationType`)                                                           | `CommunityStatus`, `AreaType`                                                                                                        |

## 2.5 Yang **tidak boleh** diasumsikan ada

1. **Tabel/model `community_types`.** Roadmap mewajibkan `community_categories`. Jangan
   membuat `community_types` maupun enum kategori.
2. **Kolom `community_id` di `posts`/`events`.** Dilarang tegas (roadmap §1.1, D-43).
3. **Halaman/route area publik.** Tidak ada; data personal area dirender **di dalam**
   halaman detail komunitas (D-10).
4. **Tabel galeri/foto komunitas.** Ditunda ke Phase 8; jangan membuat section galeri.
5. **Media library page.** Hanya picker; jangan membangun galeri media.
6. **Parser tanggal JS.** `package.json` tidak punya `date-fns`/`dayjs`/`luxon`;
   pakai `lib/datetime.ts` yang ada.
7. **Seeder produksi komunitas.** Tidak dibuat (D-7).

---

# 3. Kontrak yang Harus Dijaga

Kontrak ini sudah diuji test yang ada. Melanggarnya berarti test hijau berubah merah.

## C-1 — `PublicPlaceholderRoutesTest` harus mengosongkan baris `komunitas`

`tests/Feature/Public/PublicPlaceholderRoutesTest.php:54` memuat
`'komunitas' => ['/komunitas/contoh-komunitas', 'public/komunitas/show', 'Fase 7']`.
Setelah Phase 07, baris itu **dihapus** dan perilaku 404-nya pindah ke
`tests/Feature/Komunitas/PublicCommunityDetailTest.php`. Dataset `placeholderDetailRoutes()`
tinggal `galeri` (Fase 8). Test "answers 404 for a published-module slug" yang
menguji `/berita/...` dan `/agenda/...` **bisa** ditambah entri `komunitas`.

## C-2 — `PublicRoutesTest` tetap memuat `/komunitas`, tetapi keluar dari `placeholder pages`

`tests/Feature/Public/PublicRoutesTest.php:48` memetakan
`'/komunitas' => ['/komunitas', 'public/komunitas']` di dataset `public pages` —
**nama komponen `public/komunitas` dipertahankan**. Namun `/komunitas` **harus
dihapus** dari dataset `placeholder pages` (baris 79), karena halaman itu mulai
mengirim props; kalau tidak, test "sends no page-specific props from a
placeholder" merah.

## C-3 — `RbacFoundationTest` punya daftar permission hardcoded

`tests/Feature/Foundation/RbacFoundationTest.php:63` memakai array `$expected` dan
membandingkannya dengan permission di database. Menambah permission **wajib**
menambah baris di `$expected` **dan** memperbarui
`PermissionSeeder::PERMISSIONS` (atau test merah). Test juga menegaskan setiap
permission cocok `/^[a-z]+\.[a-z]+$/` — nama grup harus satu kata: `communities`,
`communitycategories`, `areas`.

## C-4 — Aturan `ArchitectureTest` yang mengikat

`tests/Unit/ArchitectureTest.php` antara lain:

- setiap nama halaman yang diminta controller **harus** terdaftar di frontend;
- halaman mempertahankan folder di namanya;
- `features/a/x` tidak boleh mengimpor `features/b/y`;
- hanya `rich-text.tsx` dan `event-json-ld.tsx` yang boleh `dangerouslySetInnerHTML`;
- komponen tidak boleh `href="/…"` hard-code (Wayfinder);
- model memakai atribut `#[Fillable]` dan tidak memakai `DB::` facade.

Pelanggaran = build gagal, bukan sekadar peringatan.

## C-5 — Nama komponen Inertia harus cocok path file

`pageName()` membuang `index` di **root feature** dan mempertahankannya satu folder
di bawah. Konsekuensi konkret:

| File                                                            | Nama Inertia                                 |
| --------------------------------------------------------------- | -------------------------------------------- |
| `features/admin/komunitas/pages/index.tsx`                      | `admin/komunitas`                            |
| `features/admin/komunitas/pages/tambah.tsx`                     | `admin/komunitas/tambah`                     |
| `features/admin/komunitas/pages/ubah.tsx`                       | `admin/komunitas/ubah`                       |
| `features/admin/master-data/pages/kategori-komunitas/index.tsx` | `admin/master-data/kategori-komunitas/index` |
| `features/admin/master-data/pages/wilayah-lingkungan/index.tsx` | `admin/master-data/wilayah-lingkungan/index` |
| `features/public/komunitas/pages/index.tsx`                     | `public/komunitas`                           |
| `features/public/komunitas/pages/show.tsx`                      | `public/komunitas/show`                      |

Controller dan frontend menghitung nama ini secara terpisah; `ArchitectureTest`
yang menjembatani keduanya. **Jangan** menulis `admin/komunitas/index` untuk root
feature.

## C-6 — Test sidebar dan E2E harus diperbarui bersamaan

`tests/js/components/app-sidebar.test.tsx`:

- array `MASTER_DATA_SUB_MENU` (baris 34–39) harus bertambah `Kategori Komunitas`
  dan `Wilayah & Lingkungan`;
- blok `AppSidebar — modules with no route yet` (baris 335–347) mengklaim
  `Komunitas` **tidak** punya link — harus diubah menjadi klaim link ke
  `/admin/komunitas`.

`tests/e2e/master-data.spec.ts` mengunci href anak Master Data **dan mengkliknya**
(baris 54–57 dan 79–158) — harus bertambah dua entri baru.

Mengubah menu wajib memperbarui keduanya dalam perubahan yang sama.

## C-7 — `route()` mengembalikan URL absolut di Pest

Test yang membandingkan path harus memakai
`parse_url(route(...), PHP_URL_PATH)`, bukan `toStartWith('/admin/...')`
(AGENTS.md, D-48).

## C-8 — Sort whitelist tidak boleh memuat kolom yang tidak ada

`MassScheduleController` dan semua list `SortsAdminList` memetakan kunci `sort`
langsung ke `ORDER BY`; kunci yatim menghasilkan 500 (bukan fallback default).
Whitelist sort daftar komunitas/kategori/area harus hanya memuat kolom yang
benar-benar ada.

---

# 4. Model Data dan Perilaku

Acuan: PRD §7.7/§9.9/Lampiran C + roadmap §5. Perubahan dari `community_types`/
enum ke master `community_categories`, dan dari `*_path` ke `*_media_id`, dicatat
di `docs/DECISIONS.md` (WS0).

## 4.1 Tabel `community_categories` (master, soft delete)

| Kolom                     | Tipe                   | Aturan                                     |
| ------------------------- | ---------------------- | ------------------------------------------ |
| `id`                      | BIGINT UNSIGNED PK     |                                            |
| `name`                    | VARCHAR(100)           | Wajib                                      |
| `slug`                    | VARCHAR(120)           | Wajib, **unique** (tetap saat soft delete) |
| `description`             | TEXT, nullable         |                                            |
| `sort_order`              | INT UNSIGNED default 0 |                                            |
| `is_active`               | BOOLEAN default true   | Master aktif/nonaktif                      |
| `created_at`/`updated_at` | TIMESTAMP              |                                            |
| `deleted_at`              | TIMESTAMP, nullable    | Soft delete                                |

Index: `is_active`, `sort_order` (dan kolom sort yang dipakai list).

**Tidak ada enum kategori** dan **tidak ada seed produksi** (D-7).

## 4.2 Tabel `areas` (master hierarki, soft delete, D-2/D-3)

| Kolom                     | Tipe                      | Aturan                                            |
| ------------------------- | ------------------------- | ------------------------------------------------- |
| `id`                      | BIGINT UNSIGNED PK        |                                                   |
| `parent_id`               | BIGINT UNSIGNED, nullable | FK self → `areas.id`, **restrictOnDelete**        |
| `name`                    | VARCHAR(150)              | Wajib                                             |
| `slug`                    | VARCHAR(180)              | Wajib, **unique** (tetap saat soft delete)        |
| `type`                    | VARCHAR(20)               | `wilayah` \| `lingkungan` \| `stasi` (`AreaType`) |
| `description`             | TEXT, nullable            |                                                   |
| `leader_name`             | VARCHAR(150), nullable    | Ketua wilayah/lingkungan/stasi                    |
| `contact_phone`           | VARCHAR(50), nullable     | Divalidasi `PhoneNumber`                          |
| `show_contact`            | BOOLEAN default false     | Kontrol privasi kontak area (XC-P1)               |
| `prayer_schedule`         | VARCHAR(255), nullable    | Jadwal doa lingkungan                             |
| `sort_order`              | INT UNSIGNED default 0    |                                                   |
| `is_active`               | BOOLEAN default true      | Area aktif ditawarkan untuk relasi baru           |
| `created_at`/`updated_at` | TIMESTAMP                 |                                                   |
| `deleted_at`              | TIMESTAMP, nullable       | Soft delete                                       |

Index: `parent_id`, `type`, `is_active`, `sort_order`.

Aturan hierarki (D-3), divalidasi di server:

- `wilayah`/`stasi` → `parent_id = null`;
- `lingkungan` → `parent_id` menunjuk area bertipe **`wilayah`** saja;
- `lingkungan` tidak boleh punya anak;
- tolak siklus parent;
- maksimal dua tingkat.

## 4.3 Tabel `communities` (soft delete)

Kolom persis roadmap §5.2, dengan `logo_path`/`thumbnail_path` **diganti** FK media (D-1):

`name(150)`, `slug(180) unique`, `community_category_id` FK→`community_categories`
**restrictOnDelete**, `logo_media_id` FK→`media` nullable **nullOnDelete**,
`logo_alt(255) null`, `thumbnail_media_id` FK→`media` nullable **nullOnDelete**,
`thumbnail_alt(255) null`, `description text null`, `vision text null`,
`mission text null`, `meeting_schedule text null`, `meeting_location(255) null`,
`eligibility text null`, `joining_info text null`, `contact_name(255) null`,
`contact_phone(50) null`, `show_contact bool default false`,
`status string default 'forming'`, `is_published bool default false`,
`published_at timestamp null`, `timestamps`, `softDeletes`.

Index: `community_category_id`, `status`, `is_published`. **Tidak ada**
`active_marker` (m-3).

## 4.4 Tabel `community_area` (pivot)

| Kolom          | Tipe            | Aturan                                     |
| -------------- | --------------- | ------------------------------------------ |
| `community_id` | BIGINT UNSIGNED | FK → `communities.id`, **cascadeOnDelete** |
| `area_id`      | BIGINT UNSIGNED | FK → `areas.id`, **restrictOnDelete**      |

Primary key gabungan `(community_id, area_id)` → mencegah pasangan duplikat.
Index `area_id` untuk query arah sebaliknya.

## 4.5 Enum

- `App\Enums\CommunityStatus` — `Forming = 'forming'`, `Active = 'active'`,
  `Inactive = 'inactive'`; `values()` + `label()` (label dari `lang/id/komunitas.php`).
- `App\Enums\AreaType` — `Wilayah = 'wilayah'`, `Lingkungan = 'lingkungan'`,
  `Stasi = 'stasi'`; `values()` + `label()`.

## 4.6 Perilaku bisnis yang mengikat

1. **Publikasi (roadmap §5.2.2).** Draft = `is_published = false`. Publik =
   `is_published = true` **dan** `status = active`. `forming` boleh disimpan/dikelola
   admin tetapi tidak tampil publik. `inactive` tidak tampil meski `is_published = true`.
2. **`published_at` (D-9).** Diisi server sekali saat `is_published` false→true dan
   masih null; tidak diubah saat unpublish/republish. Tidak ada di rules request.
3. **Slug (D-6, m-3).** Unik termasuk baris soft-deleted; `Rule::unique` tanpa
   `withoutTrashed()`. Route publik `{community:slug}` memakai binding + scope
   `published()` → draft/forming/inactive/soft-deleted = 404.
4. **Kategori (roadmap §5.1).** Kategori aktif dapat dipilih; kategori nonaktif tidak
   untuk penetapan baru. Kategori yang masih dipakai komunitas tidak bisa dihapus
   (pesan arahkan memindah/menonaktifkan). Kategori nonaktif tetap tampil pada
   komunitas lama.
5. **Area (roadmap §5.3).** Hanya area aktif ditawarkan untuk relasi baru; area yang
   masih dipakai `community_area` atau masih punya child tidak dapat dihapus.
   Memindahkan lingkungan ke wilayah lain menjaga relasi komunitas.
6. **Privasi kontak komunitas (XC-P1, roadmap §8.3).** `show_contact = false` →
   `contact_name`/`contact_phone` **tidak** dikirim ke props Inertia publik, bukan
   hanya disembunyikan di React.
7. **Privasi kontak area (D-10).** Area pada detail publik hanya mengirim
   `leader_name`+`contact_phone` bila `show_contact = true`; `name`/`type`/
   `prayer_schedule` selalu tampil.
8. **CTA kontak (D-8).** Fallback kontak global: kontak komunitas/area →
   `site_settings` (`contact_phone`/`contact_whatsapp`) → tanpa CTA bila keduanya
   kosong.
9. **Penghapusan komunitas.** Soft delete + `community_area` dibersihkan; tidak
   pernah menyentuh `posts`/`events` (memang tidak ada relasi).
10. **Gambar (D-1).** Logo & thumbnail diunggah via `MediaField`; field path tidak
    pernah diisi teks oleh admin; validasi `exists media status Ready`.

---

# 5. Ringkasan Task

Dipetakan dari roadmap §11 (Task 0–8) ke workstream WS0 … WS9.

| WS  | Isi                                                                | Task roadmap |
| --- | ------------------------------------------------------------------ | ------------ |
| WS0 | Keputusan schema/domain di `docs/DECISIONS.md`                     | Task 0       |
| WS1 | Migrasi, model, relasi, scope, enum, factory                       | Task 1       |
| WS2 | CRUD Kategori Komunitas + FormRequest/Policy/Resource + permission | Task 2       |
| WS3 | CRUD Wilayah & Lingkungan (hierarki) + validasi                    | Task 3       |
| WS4 | CRUD Komunitas (list/filter/pagination, upload media, pivot area)  | Task 4       |
| WS5 | Admin UI (halaman, form bersection, sidebar, Master Data)          | Task 4 + 7   |
| WS6 | Halaman publik direktori + detail + privasi kontak + SEO           | Task 5 + 6   |
| WS7 | A11y, responsif, empty/loading/error states                        | Task 7       |
| WS8 | Feature/Vitest/E2E + regresi Phase 4/5/6A/6B                       | Task 8       |
| WS9 | Dokumentasi keputusan + gate CI + status roadmap                   | Task 8       |

---

# 6. Urutan Implementasi

> Aturan roadmap §11/§17: kerjakan berurutan agar dependency jelas; verifikasi
> tiap workstream. WS0–WS4 adalah checkpoint backend; jangan lanjut WS5 sebelum
> test backend hijau.

## WS0 — Keputusan & Dokumentasi

1. Tambahkan entri `docs/DECISIONS.md` **D-55+** yang mencatat:
    - `community_categories` menggantikan `community_types` + `group enum` PRD;
    - logo/thumbnail memakai `*_media_id` (D-1), divergensi dari roadmap §5.2.3;
    - `areas` diperluas (D-2) + aturan hierarki `stasi`/`lingkungan` (D-3);
    - navigasi: Komunitas datar + Wilayah & Lingkungan/Kategori Komunitas di Master
      Data (D-4);
    - filter area publik disertakan (D-5);
    - soft delete + slug tidak dipakai ulang (D-6);
    - field personal area dipublikasikan di detail komunitas dengan privasi
      server-side (D-10), ekstensi atas roadmap §7;
    - `published_at` server-managed (D-9), CTA fallback `site_settings` (D-8);
    - tidak ada `community_id` pada `posts`/`events`; galeri ditunda ke Phase 8.
2. Jangan mengedit `docs/PRD.md` maupun `docs/roadmap/phase-07-communities.md`.

## WS1 — Database, Model, Factory

### 1.1 Migrasi (satu file modul, pola Phase 05/6B)

`database/migrations/2026_*_create_community_tables.php` berisi empat tabel:
`community_categories`, `areas`, `communities`, `community_area` (§4). Catatan:
FK `communities.community_category_id` **restrict**; `community_area.community_id`
**cascade**; `community_area.area_id` **restrict**; `areas.parent_id` **restrict**;
`*_media_id` **nullOnDelete**; `softDeletes()` pada tiga tabel master.

### 1.2 Enum, Model, Factory

- `CommunityStatus`, `AreaType` (§4.5).
- `App\Models\CommunityCategory` — `#[Fillable]`, `casts()`, `SoftDeletes`,
  `communities()`, scope `active()`, scope `ordered()`.
- `App\Models\Area` — `#[Fillable]`, `casts()`, `SoftDeletes`, `parent()`,
  `children()`, `communities()` (BelongsToMany), scope `active()`, `ordered()`,
  helper `isWilayah()`/`isLingkungan()`/`isStasi()`.
- `App\Models\Community` — `#[Fillable]`, `casts()`, `SoftDeletes`,
  `communityCategory()`, `logoMedia()`, `thumbnailMedia()`, `areas()`
  (BelongsToMany), scope `published()`, `ordered()`, `resolveRouteBinding` slug
  yang men-scope publik.
- Factory: `CommunityCategoryFactory`, `AreaFactory` (state `wilayah()`,
  `lingkungan()`, `stasi()`), `CommunityFactory` (state `published()`, `forming()`,
  `inactive()`, `withArea()`). Tidak mengarang data paroki nyata.

### 1.3 Gate WS1

`php artisan migrate:fresh` di DB test + `migrate:rollback` +
`migrate`; `CommunitySchemaTest` menguji kolom/FK/index & reversibilitas `down()`.

## WS2 — Backend Kategori Komunitas

### 2.1 Permission (`PermissionSeeder::PERMISSIONS` + `RbacFoundationTest::$expected`)

- `communitycategories.view` / `.create` / `.update` / `.delete`
- `communities.view` / `.create` / `.update` / `.delete`
- `areas.view` / `.create` / `.update` / `.delete`

Total baru: **12** (69 → 81). Perbarui `RbacFoundationTest` dan `docs/RBAC.md` §1.

### 2.2 Policy, Concern, FormRequest, Controller, Resource

- `CommunityCategoryPolicy` (`communitycategories.*`).
- `CommunityCategoryValidationRules` + `CommunityFormMeta` (label/hint dari
  `lang/id/komunitas.php`).
- `StoreCommunityCategoryRequest` / `UpdateCommunityCategoryRequest` —
  `authorize()` `can('create'|'update', …)`; `name` wajib, `slug` unique + ignore
  saat update, `description`, `sort_order`, `is_active`.
- `App\Http\Controllers\MasterData\CommunityCategoryController` — CRUD +
  `withCount('communities')`; `destroy` menolak bila kategori masih dipakai
  (pesan arahkan memindah/menonaktifkan).
- `CommunityCategoryResource` + `CommunityCategoryOptionResource`.

### 2.3 Route

`/admin/master-data/kategori-komunitas` (`community-categories.*`),
mengikuti pola `/admin/master-data/kategori-pelayanan`.

### 2.4 Gate WS2

`php artisan test --compact tests/Feature/Komunitas` (subset kategori),
`./vendor/bin/pint --dirty --format agent`, `wayfinder:generate`, `npm run types:check`.

## WS3 — Backend Wilayah & Lingkungan

- `AreaPolicy` (`areas.*`).
- `AreaValidationRules` — rules + `after()` cross-field untuk aturan hierarki (§4.2);
  `phone` via `PhoneNumber`; `parent_id` hanya area `wilayah` aktif untuk `lingkungan`.
- `StoreAreaRequest` / `UpdateAreaRequest`.
- `App\Http\Controllers\Komunitas\AreaController` — list hierarkis (eager load
  `parent`/`children`), search nama, filter tipe/status, `applyAdminSort`;
  `destroy` menolak bila ada child atau dipakai komunitas (pesan jelas).
- `AreaResource` (admin, termasuk field personal) + `AreaOptionResource` (opsi
  hierarkis untuk selector).
- Route `/admin/master-data/wilayah-lingkungan` (`areas.*`).

## WS4 — Backend Komunitas

- `CommunityPolicy` (`communities.*`).
- `CommunityValidationRules` (trait) + `CommunityFormMeta`:
    - `name` wajib; `slug` unique + ignore saat update;
    - `community_category_id` wajib + kategori aktif untuk penetapan baru;
    - `logo_media_id`/`thumbnail_media_id` `nullable` +
      `Rule::exists('media','id')->where('status', Ready)`; `*_alt` max 255;
    - `status` `Rule::enum(CommunityStatus)`; `is_published`/`show_contact` boolean;
    - `contact_phone` divalidasi `PhoneNumber`;
    - `areas` array of id: `exists` + **aktif** + hierarki valid; cegah duplikat;
    - `description`/`vision`/`mission`/`eligibility`/`joining_info` teks + batas panjang;
    - `published_at` **tidak ada** di rules.
    - `prepareForValidation()`: trim, ubah string kosong → null, normalkan boolean.
- `StoreCommunityRequest` / `UpdateCommunityRequest`.
- `app/Actions/Komunitas/SaveCommunityAction` — `DB::transaction`: resolve slug,
  stamp `published_at` (D-9), simpan, `sync()` pivot `areas`.
- `app/Actions/Komunitas/DeleteCommunityAction` — soft delete + detach pivot.
- `App\Http\Controllers\Komunitas\CommunityController` — index (search nama,
  filter kategori/status/publikasi, `applyAdminSort`, paginate + withQueryString),
  create, store, edit, update, destroy.
- `CommunityListResource`, `CommunityResource`, `CommunityOptionResource`.

## WS5 — Admin UI

### 5.1 Kategori Komunitas (Master Data)

`features/admin/master-data/pages/kategori-komunitas/{index,tambah,ubah}.tsx` +
`components/community-category-form.tsx` + `types/community-category.ts`. Kolom:
nama, slug, deskripsi, jumlah komunitas, status, urutan, aksi.

### 5.2 Wilayah & Lingkungan (Master Data)

`features/admin/master-data/pages/wilayah-lingkungan/{index,tambah,ubah}.tsx` +
`components/area-form.tsx` + `types/area.ts`. List menampilkan kolom Tipe,
Wilayah Induk, Nama, Ketua, Jadwal Doa, Status, Urutan; parent selector hanya
menawarkan `wilayah` aktif.

### 5.3 Daftar Komunitas (`/admin/komunitas`)

`features/admin/komunitas/pages/index.tsx` — tabel TanStack v9 server-sorted
(`manualSorting`): thumbnail kecil, nama, kategori (badge), wilayah pelayanan
(ringkas + `+N`), status, publikasi, diperbarui, aksi (detail/edit/hapus). Filter
pencarian + kategori + status + publikasi + reset. Pagination + jumlah. Empty
state (belum ada vs filter kosong).

### 5.4 Tambah/Edit Komunitas

`pages/tambah.tsx`, `pages/ubah.tsx`, `components/community-form.tsx` bersection:

- **A Informasi dasar:** nama*, slug*, kategori\* (aktif saja; kategori lama
  nonaktif tetap tampil saat edit), status, deskripsi.
- **B Logo & Thumbnail:** dua `MediaField` (preview, ganti, hapus, alt), hint
  format/ukuran, error per field. Input file tidak menerima path manual.
- **C Profil:** visi, misi (textarea).
- **D Jadwal & Lokasi:** jadwal pertemuan, lokasi pertemuan (teks bebas).
- **E Wilayah Pelayanan:** multi-select area aktif (hierarkis, dengan pencarian),
  cegah duplikat, ringkasan terpilih.
- **F Informasi Bergabung & Kontak:** eligibility, cara bergabung, contact_name,
  contact_phone, switch `show_contact` + penjelasan.
- **G Publikasi:** switch draft/published + preview status; `published_at` tidak
  diinput admin.

Semua `<form noValidate>`; tombol simpan/batal; indikator submitting;
konfirmasi hapus.

### 5.5 Sidebar

`app-sidebar.tsx`: `Komunitas` → link datar `/admin/komunitas`; grup `Master Data`
bertambah `Kategori Komunitas` dan `Wilayah & Lingkungan`.

## WS6 — Halaman Publik

- `App\Http\Controllers\Komunitas\PublicCommunityController` (`index`, `show`).
- Ganti dua route placeholder dengan controller; **pertahankan** nama route
  `komunitas.index` dan `komunitas.show`, serta nama komponen `public/komunitas`
  dan `public/komunitas/show`.
- `pages/index.tsx`: hanya `published()`, eager-load kategori/area/thumbnail;
  search nama; filter kategori + area; pagination query-string; card + fallback
  gambar; empty state; tombol reset filter.
- `pages/show.tsx`: breadcrumb → header profil → tentang → visi/misi →
  jadwal/lokasi → wilayah pelayanan (dengan ketua & jadwal doa; kontak area hanya
  bila `show_contact`) → cara bergabung → kontak pengurus (bila `show_contact`) →
  CTA kontak/sekretariat → navigasi kembali. Section kosong disembunyikan.
- Komponen: `community-card.tsx`, `community-filters.tsx`,
  `community-profile-header.tsx`, `area-list.tsx`.
- **Tidak ada** galeri, related news/agenda, atau relasi `community_id`.
- 404 untuk slug tidak ada / draft / forming / inactive.

## WS7 — A11y, Responsif, States

- Label terikat input, focus ring, heading semantik, `aria-label` pada tombol ikon,
  alt text bermakna / alt kosong untuk dekoratif.
- Cek 360/390/768/1024/1440 px; filter & search usable di mobile.
- Loading/empty/error/success states konsisten dengan modul lain.

## WS8 — Testing & Regresi

- Feature: skema, CRUD kategori, CRUD area + hierarki, CRUD komunitas + upload +
  pivot + privasi, publik direktori + detail + privasi area, authz.
- Vitest: `app-sidebar.test.tsx` diperbarui (Master Data + Komunitas link).
- E2E: `tests/e2e/komunitas.spec.ts` (baru) + update `master-data.spec.ts`.
- Regresi: MassSchedule, News, Agenda, Profil, Pelayanan, Public.

## WS9 — Dokumentasi & Gate CI

- Finalkan entri `docs/DECISIONS.md` (WS0) berdasarkan hasil nyata.
- Perbarui `docs/RBAC.md` §1 dan `docs/roadmap/README.md` **hanya setelah**
  task/AC/test terbukti; perbarui checklist `docs/roadmap/phase-07-communities.md`.
- Laporan akhir format roadmap §15.

---

# 7. File yang Akan Terpengaruh

## 7.1 Dibuat — backend

```text
database/migrations/2026_*_create_community_tables.php

database/factories/CommunityCategoryFactory.php
database/factories/AreaFactory.php
database/factories/CommunityFactory.php

app/Enums/CommunityStatus.php
app/Enums/AreaType.php

app/Models/CommunityCategory.php
app/Models/Area.php
app/Models/Community.php

app/Policies/CommunityCategoryPolicy.php
app/Policies/AreaPolicy.php
app/Policies/CommunityPolicy.php

app/Concerns/CommunityCategoryValidationRules.php
app/Concerns/AreaValidationRules.php
app/Concerns/CommunityValidationRules.php
app/Concerns/CommunityFormMeta.php

app/Http/Requests/Komunitas/StoreCommunityCategoryRequest.php
app/Http/Requests/Komunitas/UpdateCommunityCategoryRequest.php
app/Http/Requests/Komunitas/StoreAreaRequest.php
app/Http/Requests/Komunitas/UpdateAreaRequest.php
app/Http/Requests/Komunitas/StoreCommunityRequest.php
app/Http/Requests/Komunitas/UpdateCommunityRequest.php

app/Actions/Komunitas/SaveCommunityAction.php
app/Actions/Komunitas/DeleteCommunityAction.php

app/Http/Controllers/Komunitas/AreaController.php
app/Http/Controllers/Komunitas/CommunityController.php
app/Http/Controllers/Komunitas/PublicCommunityController.php
app/Http/Controllers/MasterData/CommunityCategoryController.php

app/Http/Resources/Komunitas/CommunityListResource.php
app/Http/Resources/Komunitas/CommunityResource.php
app/Http/Resources/Komunitas/CommunityOptionResource.php
app/Http/Resources/Komunitas/CommunityCategoryResource.php
app/Http/Resources/Komunitas/CommunityCategoryOptionResource.php
app/Http/Resources/Komunitas/AreaResource.php
app/Http/Resources/Komunitas/AreaOptionResource.php
app/Http/Resources/Public/Komunitas/PublicCommunityCardResource.php
app/Http/Resources/Public/Komunitas/PublicCommunityDetailResource.php
app/Http/Resources/Public/Komunitas/PublicCommunityCategoryResource.php

lang/id/komunitas.php
```

## 7.2 Dibuat — frontend

```text
resources/js/features/admin/komunitas/pages/index.tsx
resources/js/features/admin/komunitas/pages/tambah.tsx
resources/js/features/admin/komunitas/pages/ubah.tsx
resources/js/features/admin/komunitas/components/community-form.tsx
resources/js/features/admin/komunitas/components/community-columns.tsx
resources/js/features/admin/komunitas/components/area-selector.tsx
resources/js/features/admin/komunitas/components/community-status-badge.tsx
resources/js/features/admin/komunitas/types.ts

resources/js/features/admin/master-data/pages/kategori-komunitas/index.tsx
resources/js/features/admin/master-data/pages/kategori-komunitas/tambah.tsx
resources/js/features/admin/master-data/pages/kategori-komunitas/ubah.tsx
resources/js/features/admin/master-data/pages/kategori-komunitas/components/community-category-form.tsx
resources/js/features/admin/master-data/pages/wilayah-lingkungan/index.tsx
resources/js/features/admin/master-data/pages/wilayah-lingkungan/tambah.tsx
resources/js/features/admin/master-data/pages/wilayah-lingkungan/ubah.tsx
resources/js/features/admin/master-data/pages/wilayah-lingkungan/components/area-form.tsx
resources/js/features/admin/master-data/types/community-category.ts
resources/js/features/admin/master-data/types/area.ts

resources/js/features/public/komunitas/components/community-card.tsx
resources/js/features/public/komunitas/components/community-filters.tsx
resources/js/features/public/komunitas/components/community-profile-header.tsx
resources/js/features/public/komunitas/components/area-list.tsx
```

## 7.3 Diubah — backend

| File                                     | Perubahan                                                                                            |
| ---------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| `routes/web.php`                         | Ganti 2 route placeholder komunitas → controller publik; tambah blok admin komunitas + 2 Master Data |
| `database/seeders/PermissionSeeder.php`  | +12 permission; `expectedCount()` otomatis                                                           |
| `database/seeders/DemoContentSeeder.php` | Tambah `seedCommunities()` (kategori + area + komunitas contoh; dijaga `demo.enabled`)               |
| `docs/DECISIONS.md`                      | Entri D-55+ (WS0/WS9)                                                                                |
| `docs/RBAC.md`                           | §1 daftar permission                                                                                 |
| `docs/roadmap/README.md`                 | Status Phase 07 (setelah verifikasi)                                                                 |
| `docs/roadmap/phase-07-communities.md`   | Checklist Task 0–8 & Definition of Done                                                              |

## 7.4 Diubah — frontend

| File                                                     | Perubahan                                                |
| -------------------------------------------------------- | -------------------------------------------------------- |
| `resources/js/features/public/komunitas/pages/index.tsx` | Placeholder → direktori dinamis                          |
| `resources/js/features/public/komunitas/pages/show.tsx`  | Placeholder → detail dinamis                             |
| `resources/js/layouts/app/components/app-sidebar.tsx`    | `Komunitas` + href; 2 anak baru di `Master Data`         |
| `resources/js/app.tsx`                                   | **Tidak berubah** (resolver otomatis dari nama komponen) |

## 7.5 Diubah — test

| File                                                   | Perubahan                                                                                   |
| ------------------------------------------------------ | ------------------------------------------------------------------------------------------- |
| `tests/Feature/Public/PublicPlaceholderRoutesTest.php` | Hapus baris `komunitas` (pindah ke `tests/Feature/Komunitas/PublicCommunityDetailTest.php`) |
| `tests/Feature/Public/PublicRoutesTest.php`            | Hapus `/komunitas` dari dataset `placeholder pages`; `public pages` tetap                   |
| `tests/Feature/Foundation/RbacFoundationTest.php`      | +12 permission ke `$expected`                                                               |
| `tests/js/components/app-sidebar.test.tsx`             | Tambah 2 anak Master Data; ubah asersi `Komunitas` menjadi link                             |
| `tests/e2e/master-data.spec.ts`                        | Perbarui daftar sub-menu + klik halaman baru                                                |
| `tests/e2e/komunitas.spec.ts`                          | **Baru**                                                                                    |

## 7.6 Dihapus

Tidak ada file dihapus. Dua halaman placeholder komunitas **diisi ulang**, nama
komponen Inertia dan nama route dipertahankan agar navbar/footer/`PublicRoutesTest`
tetap valid.

---

# 8. Dependency / Configuration Impact

## 8.1 Dependency baru: **nol**

Tidak ada composer/npm baru. Media pipeline, sanitizer, TanStack, shadcn, Lucide,
`PhoneNumber`, dan `SiteSettingsService` semuanya sudah ada.

## 8.2 Konfigurasi

| Konfigurasi                             | Perubahan                                                           |
| --------------------------------------- | ------------------------------------------------------------------- |
| `database/seeders/PermissionSeeder.php` | +12 permission; `expectedCount()` mengikuti `count(PERMISSIONS)`    |
| `config/html.php`                       | **Tidak berubah** — field naratif disimpan sebagai plain text (m-6) |
| `config/media.php`                      | **Tidak berubah** — dipakai apa adanya                              |
| `config/site-settings.php`              | **Tidak berubah** — fallback kontak memakai kunci yang sudah ada    |
| `config/demo.php`                       | **Tidak berubah** — dipakai sebagai guard seeder demo               |

## 8.3 Wayfinder

Setiap perubahan route/controller mewajibkan `php artisan wayfinder:generate --with-form`
(Vite plugin juga regenerate saat dev/build). Aturan:

- Jangan buat `Route::redirect()` **tanpa nama** (Wayfinder gagal build; D-49/AGENTS).
- Jangan tinggalkan route lama bernama sama dengan yang baru (dua export → build gagal).
- `/admin/komunitas` adalah halaman (bukan redirect), jadi tidak ada masalah nama.

## 8.4 Publikasi, Media, Timezone

- Fallback kontak **hanya** melalui `SiteSettingsService` (jangan query tabel langsung).
- Setiap resource yang mengirim URL media **wajib** eager-load `*.variants` (strict mode).
- `published_at`/`updated_at` dikirim ISO 8601; diformat di React dengan
  `lib/datetime.ts` (`Asia/Pontianak`, format Indonesia). `published_at` disimpan
  sebagai timestamp UTC biasa (bukan time-only seperti `mass_schedules.start_time`).

---

# 9. Risiko dan Mitigasi

| #    | Risiko                                                       | Dampak                                           | Mitigasi                                                                                              |
| ---- | ------------------------------------------------------------ | ------------------------------------------------ | ----------------------------------------------------------------------------------------------------- |
| R-1  | `*_path` (roadmap) vs `*_media_id` (repo)                    | Dua pola gambar / kebingungan schema             | D-1: pakai `*_media_id`; catat divergensi di DECISIONS; eager-load `*.variants`                       |
| R-2  | PRD `community_types`/`group enum` vs `community_categories` | Dua sumber kategori                              | Satu tabel master + FK; jangan buat enum; catat di DECISIONS                                          |
| R-3  | PRD related news/agenda & `community_id`                     | Melanggar larangan roadmap §1.1                  | Jangan sentuh `posts`/`events`; tidak ada section related; test menegaskan tidak ada props itu        |
| R-4  | Nama komponen Inertia ≠ path file                            | Halaman tak bereaksi (200, komponen tak dikenal) | Ikuti `pageName()` (C-5); `ArchitectureTest` menangkap; jalankan setelah tiap controller baru         |
| R-5  | Permission baru tanpa update `RbacFoundationTest`            | Suite merah                                      | C-3: perbarui `$expected` + `PermissionSeeder` bersamaan                                              |
| R-6  | Test placeholder & sidebar/e2e tidak diperbarui              | Suite merah                                      | C-1/C-2/C-6: perbarui bersamaan; `npm run build` sebelum E2E                                          |
| R-7  | Hierarki area salah (siklus, 3 tingkat, lingkungan di stasi) | Data wilayah rusak                               | D-3: validasi `after()` cross-field + test penolakan (termasuk lingkungan di bawah stasi)             |
| R-8  | Hapus kategori/area yang masih dipakai                       | Komunitas/relasi yatim                           | FK restrict + cek `withTrashed()`/`children`/pivot di controller + pesan jelas                        |
| R-9  | Privasi kontak bocor lewat props publik                      | Pelanggaran XC-P1                                | Hapus field dari Resource saat `show_contact=false` (server), bukan conditional React; test `missing` |
| R-10 | Slug unik + soft delete salah arah                           | Soft-deleted menahan slot salah                  | D-6: pola D-44; `Rule::unique` tanpa `withoutTrashed()`; uji make/delete/re-make slug                 |
| R-11 | N+1 relasi kategori/area/variants                            | Strict mode melempar                             | `with()`/`withCount()`; jangan `select()` setelah `withCount()` (D-41)                                |
| R-12 | D-39: list non-paginasi terbungkus `{data:…}`                | Halaman blank saat `.map`                        | `array_values(Resource::collection(…)->resolve())` untuk opsi/section list                            |
| R-13 | Mixing server-sort & client state TanStack                   | Data salah urut                                  | `manualSorting: true`; header click = visit; ikuti pola `misa-rutin`/`pelayanan`                      |
| R-14 | `npm run check:fix` pernah menghapus komentar/`<Heading>`    | Kehilangan kode tanpa sadar                      | Jalankan, baca `git diff` sebelum menyimpan (AGENTS.md); lebih baik pakai `npm run check`             |
| R-15 | Migrasi tidak reversible                                     | `migrate:fresh` gagal                            | Uji `migrate:fresh` + `migrate:rollback` di DB test                                                   |
| R-16 | `npx playwright test` bare setelah Pest                      | E2E gagal di `signIn`                            | Selalu `npm run e2e`; cek `ss -ltnp \| grep 8000`                                                     |

---

# 10. Verification Plan

## 10.1 Gate per workstream

```bash
# WS1
php artisan migrate:fresh
php artisan test --compact tests/Feature/Komunitas/CommunitySchemaTest.php
php artisan migrate:rollback && php artisan migrate

# WS2–WS4 (setiap workstream)
./vendor/bin/pint --dirty --format agent
php artisan test --compact tests/Feature/Komunitas
php artisan wayfinder:generate --with-form
npm run types:check

# WS5–WS7
npm run check
npm run types:check
php artisan test --compact tests/Feature/Komunitas tests/Feature/Public
npm run build

# WS8
npm run e2e          # JANGAN npx playwright test bare
```

## 10.2 Gate penuh di akhir

```bash
composer test        # config:clear, pint --parallel --test, phpstan analyse, artisan test
composer ci:check    # npm run check + npm run types:check + artisan test
npm run build
npm run e2e
```

> `npm run e2e` menyajikan `public/build`, jadi **`npm run build` dulu**. Jangan
> tinggalkan `php artisan serve` di port 8000 (E2E login gagal; `ss -ltnp | grep 8000`).

## 10.3 Pemeriksaan manual khusus

- Viewport 360, 390, 768, 1024, 1440 px (roadmap §9.3).
- Keyboard-only: form komunitas, selector area, filter publik, tombol hapus.
- Cek `php artisan route:list --path=komunitas` dan `--path=master-data` untuk nama
  route unik & tidak tabrakan.
- Cek detail komunitas: kontak komunitas & kontak area masing-masing hilang saat
  `show_contact=false` (inspeksi page props di Network tab, bukan hanya DOM).
- Pastikan tidak ada section galeri/related yang muncul.

---

# 11. Test Matrix

## 11.1 Feature — skema & otorisasi

- `CommunitySchemaTest` — kolom/FK/index empat tabel; `down()` reversible;
  FK restrict/cascade/nullOnDelete; unique `(community_id, area_id)`.
- `CommunityAuthorizationTest` — guest redirect ke login; non-super-admin 403;
  `super_admin` lolos; `FormRequest::authorize()` menolak `update`.

## 11.2 Feature — kategori

- `CommunityCategoryTest` — CRUD; slug unik; kategori nonaktif tidak dipakai
  komunitas baru; kategori terpakai tidak bisa dihapus (AC-CAT-05/06).

## 11.3 Feature — area & hierarki

- `AreaTest` — wilayah tanpa parent; tolak wilayah dengan parent; lingkungan
  wajib parent wilayah; tolak lingkungan tanpa parent; tolak lingkungan dengan
  parent lingkungan/stasi; tolak wilayah berparent wilayah; tolak siklus;
  pindah lingkungan; tolak hapus wilayah beranak; tolak hapus area berelasi;
  authz (AC-AREA-01…07 + D-3).

## 11.4 Feature — komunitas admin

- `CommunityTest` — CRUD; slug unik; kategori invalid/nonaktif; upload logo &
  thumbnail (`Storage::fake()`, media `Ready`); format/ukuran invalid ditolak;
  replacement memperbarui FK; `published_at` set sekali (D-9); pivot area
  tersinkronisasi & duplikat dicegah; status/publikasi terpisah; filter/search/
  pagination; hapus membersihkan pivot; tidak menyentuh posts/events
  (AC-COM-01…12).

## 11.5 Feature — publik

- `PublicCommunityDirectoryTest` — hanya `active` + `is_published`; draft/forming/
  inactive absen; search; filter kategori; filter area; pagination + query-string;
  card + fallback; empty state; tidak ada data contoh hardcoded (AC-PUB-01…08).
- `PublicCommunityDetailTest` — detail active+published 200; unknown/draft/forming/
  inactive 404; section opsional kosong aman; area valid tampil; kontak komunitas
  hanya saat `show_contact=true`; `show_contact=false` → `missing('community.contact…')`;
  kontak area hanya saat `area.show_contact=true` (D-10); SEO props; tanpa galeri/
  related (AC-DETAIL-01…10).

## 11.6 Frontend (Vitest)

- `app-sidebar.test.tsx` — `Komunitas` link ke `/admin/komunitas`; `Kategori
Komunitas` & `Wilayah & Lingkungan` di Master Data; disclosure tetap.

## 11.7 E2E

- `tests/e2e/komunitas.spec.ts` — buat kategori; buat area (wilayah + lingkungan);
  buat komunitas + upload logo/thumbnail + pilih area + toggle publikasi;
  direktori publik menampilkan hanya yang published; filter kategori/area;
  detail tampil; kontak tersembunyi sesuai `show_contact`.
- `tests/e2e/master-data.spec.ts` — tambah 2 entri anak + klik halamannya.

## 11.8 Regresi

- `tests/Feature/MassSchedule`, `tests/Feature/News`, `tests/Feature/Agenda`,
  `tests/Feature/Profil`, `tests/Feature/Pelayanan`, `tests/Feature/Public` tetap
  hijau (AC-QA-04).

---

# 12. Acceptance Criteria

## 12.1 Roadmap §13.1 — Kategori komunitas

- **AC-CAT-01** daftar kategori · **AC-CAT-02** buat valid · **AC-CAT-03** slug unik ·
  **AC-CAT-04** edit · **AC-CAT-05** tidak bisa hapus saat dipakai ·
  **AC-CAT-06** nonaktif tanpa menghapus komunitas · **AC-CAT-07** guest/tak berwenang ditolak.

## 12.2 Roadmap §13.2 — Wilayah & lingkungan

- **AC-AREA-01** wilayah tanpa parent · **AC-AREA-02** lingkungan wajib parent wilayah ·
  **AC-AREA-03** wilayah tidak bisa jadi child · **AC-AREA-04** lingkungan tanpa child ·
  **AC-AREA-05** tolak parent/type tidak valid · **AC-AREA-06** tidak bisa hapus area
  terpakai tanpa penanganan · **AC-AREA-07** CRUD hanya admin berwenang.

## 12.3 Roadmap §13.3 — Komunitas admin

- **AC-COM-01** buat dengan kategori valid · **AC-COM-02** slug unik ·
  **AC-COM-03** logo via upload · **AC-COM-04** thumbnail via upload ·
  **AC-COM-05** format invalid ditolak · **AC-COM-06** ganti gambar & bersihkan yang lama ·
  **AC-COM-07** edit profil/visi/misi/jadwal/lokasi/bergabung/kontak ·
  **AC-COM-08** hubungkan ke beberapa area · **AC-COM-09** status & publikasi terpisah ·
  **AC-COM-10** search & filter list · **AC-COM-11** hapus dengan konfirmasi, tidak
  menyentuh posts/events · **AC-COM-12** otorisasi backend.

## 12.4 Roadmap §13.4 — Direktori publik

- **AC-PUB-01** hanya active+published · **AC-PUB-02** search nama ·
  **AC-PUB-03** filter kategori · **AC-PUB-04** filter area · **AC-PUB-05** pagination
  mempertahankan query-string · **AC-PUB-06** kartu dengan fallback + nama + kategori +
  ringkasan + area + link detail · **AC-PUB-07** empty state · **AC-PUB-08** tanpa data contoh.

## 12.5 Roadmap §13.5 — Detail & privasi

- **AC-DETAIL-01** slug valid+published 200 · **AC-DETAIL-02** slug tak ada 404 ·
  **AC-DETAIL-03** draft/forming/inactive 404 · **AC-DETAIL-04** section opsional hanya
  bila ada data · **AC-DETAIL-05** area valid tampil · **AC-DETAIL-06** kontak hanya saat
  `show_contact=true` & ada data · **AC-DETAIL-07** `show_contact=false` → nama & nomor
  tidak ada di props · **AC-DETAIL-08** SEO nama+deskripsi · **AC-DETAIL-09** tanpa galeri ·
  **AC-DETAIL-10** tanpa related news/agenda & `community_id`.

## 12.6 Roadmap §13.6 — Kualitas

- **AC-QA-01** test baru lulus · **AC-QA-02** lint/typecheck/build lulus ·
  **AC-QA-03** E2E relevan lulus · **AC-QA-04** tanpa regresi Phase 1–6B ·
  **AC-QA-05** CI relevan lulus · **AC-QA-06** dokumentasi & roadmap diperbarui.

## 12.7 AC tambahan Phase 07 (divergensi terkonfirmasi)

- **AC-COM-13** `lingkungan` di bawah `stasi` ditolak server (D-3).
- **AC-COM-14** detail komunitas menampilkan `leader_name`/`prayer_schedule` area
  bila datanya ada (D-10).
- **AC-COM-15** `contact_phone` area **tidak ada** di props Inertia publik saat
  `area.show_contact=false` (D-10, XC-P1).
- **AC-COM-16** area nonaktif tidak menghilangkan relasi komunitas yang sudah ada.

## 12.8 Roadmap §16 — Definition of Done

Empat tabel inti; kategori dapat dikelola; wilayah/lingkungan hierarki maks 2
tingkat; komunitas CRUD; upload logo & thumbnail; replacement/deletion aman;
relasi multi-area; search/filter/pagination admin; direktori publik hanya
active+published; detail publik by slug; privasi kontak server-side;
draft/forming/inactive tidak bocor; UI mengikuti DESIGN.md; **tidak ada** galeri;
**tidak ada** `community_id` di `posts`/`events`; test lulus; lint/typecheck/build/
E2E dijalankan; tanpa regresi; dokumentasi & roadmap diperbarui; laporan akhir
dengan hasil aktual.

---

# 13. Yang Sengaja Tidak Dikerjakan

- **Galeri foto komunitas / `community_photos` / album / lightbox** — Phase 8.
- **Relasi `community_id` pada `posts`/`events`** — dilarang roadmap §1.1 & D-43.
- **Section related news/agenda** di detail komunitas — di luar scope Phase 7.
- **Modul keanggotaan individual, pendaftaran anggota online, absensi, chat,
  pengurus terstruktur** — roadmap §3.2.
- **Halaman/route area publik** (`/wilayah/{slug}` dll.) — data personal area
  dirender di dalam detail komunitas (D-10), bukan halaman sendiri.
- **Tabel `community_types` dan enum kategori** — dilarang; pakai master kategori.
- **Seeder produksi komunitas/kategori/area** — D-7.
- **Role/permission management UI & role tambahan** — di luar MVP.
- **Media library page** — hanya picker.
- **Cache direktori/jadwal** — bukan AC Phase 7.
- **Refactor modul Phase 1–6B** yang tidak berhubungan.
- **`docs/ADMIN_GUIDE.md`** — tidak diminta pada fase ini.

---

# 14. Keputusan Minor yang Diasumsikan

Berlaku sesuai rekomendasi; dapat dibatalkan tanpa invalidasi plan.

| #    | Keputusan          | Nilai & alasan                                                                                                          |
| ---- | ------------------ | ----------------------------------------------------------------------------------------------------------------------- |
| m-1  | Nama route         | `communities.*`, `community-categories.*`, `areas.*` (satu kata, regex `/^[a-z]+\.[a-z]+$/`).                           |
| m-2  | Nama halaman       | Sesuai C-5; `admin/komunitas` untuk root feature, `wilayah-lingkungan` & `kategori-komunitas` di Master Data.           |
| m-3  | Slug & soft delete | Tetap unik saat soft delete (D-44); tanpa `active_marker`; `Rule::unique` tanpa `withoutTrashed()`.                     |
| m-4  | `published_at`     | Server-managed, tidak ada di rules; set sekali (D-9).                                                                   |
| m-5  | Enum               | `CommunityStatus`, `AreaType` (backed enum + `values()` + `label()`).                                                   |
| m-6  | Field naratif      | Textarea plain text; tidak menambah field HTML/Tiptap; `config/html.php` tidak berubah.                                 |
| m-7  | Validasi kontak    | `App\Support\PhoneNumber` yang sudah ada.                                                                               |
| m-8  | Replacement gambar | Form kirim `*_media_id` baru; baris media lama tidak dihapus otomatis (dipakai bersama).                                |
| m-9  | Hapus komunitas    | Soft delete + detach pivot; tidak menghapus baris `media`.                                                              |
| m-10 | Selector area      | Multi-pilih + pencarian; disimpan via `sync()`.                                                                         |
| m-11 | Daftar admin       | TanStack v9, `manualSorting: true`, server-sort `SortsAdminList`.                                                       |
| m-12 | Filter area publik | Berdasarkan slug; slug tak dikenal = tanpa filter (bukan empty list palsu), pola `filteredCategory`.                    |
| m-13 | SEO detail         | `seo.tsx`; judul "{nama} — {parish_name}".                                                                              |
| m-14 | Permission         | +12 (69 → 81).                                                                                                          |
| m-15 | Demo content       | `DemoContentSeeder::seedCommunities()`; kategori+area+komunitas contoh; tanpa kontak/nama nyata; dijaga `demo.enabled`. |
| m-16 | Route ordering     | Static `/admin/master-data/...` tidak bertabrakan dengan `/admin/komunitas` (root index) maupun `{community}`.          |

---

# 15. Status

| Item               | Nilai                                         |
| ------------------ | --------------------------------------------- |
| Dokumen roadmap    | `docs/roadmap/phase-07-communities.md` ✅ ada |
| Audit codebase     | ✅ selesai (commit `578182d`, `main`)         |
| Keputusan pengguna | ✅ 10 pertanyaan terjawab + m-1…m-16          |
| Kode implementasi  | ⏳ **belum dimulai** — menunggu persetujuan   |
| Verifikasi         | ⏳ belum dijalankan                           |
| Keputusan menunggu | —                                             |

## 15.1 Ringkasan lingkup

- **4 tabel** (2 master + 1 profil + 1 pivot) / 1 migrasi; 3 model; 3 factory;
  2 enum; 3 policy; 2 Action; 4 controller admin + 1 publik; ~10 resource;
  ~30 route; ~11 halaman Inertia; ~15 komponen; 1 file `lang/id`.
- **+12 permission** (69 → 81).
- Admin `/admin/komunitas/*` + `/admin/master-data/{kategori-komunitas,wilayah-lingkungan}/*`.
- 2 halaman publik dinamis + filter kategori/area + privasi kontak komunitas &
  area + CTA fallback `site_settings`.
- Sidebar: `Komunitas` link datar + 2 anak Master Data.
- **Demo only** seeder; `posts`/`events` tidak disentuh; galeri ditunda Phase 8.

> Sesuai roadmap §11/§17, implementasi dikerjakan per checkpoint WS0→WS9 dengan
> verifikasi di setiap workstream. WS0–WS4 adalah checkpoint pertama. Jangan
> menandai phase selesai hanya karena file sudah dibuat; status selesai
> didasarkan pada acceptance criteria, hasil test, dan build yang benar-benar
> diverifikasi.

@see `docs/roadmap/phase-07-communities.md`, `docs/DECISIONS.md`, `docs/RBAC.md`,
`docs/ARCHITECTURE.md`
