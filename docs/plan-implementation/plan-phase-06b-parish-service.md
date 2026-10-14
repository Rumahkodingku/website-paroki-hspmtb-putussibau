# Implementation Plan — PHASE 06B

> **Project:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau\
> **Phase:** 06B — Parish Service (Modul Pelayanan)\
> **Status:** Disusun — menunggu persetujuan implementasi\
> **Branch (usulan):** `feat/phase-06b-parish-service`\
> **Spesifikasi:** `docs/roadmap/phase-6B-parish-service.md`\
> **Sumber kebenaran:** `PRD.md` → `ARCHITECTURE.md` → `DESIGN.md` → `AGENTS.md` → `DECISIONS.md`

Dokumen roadmap Phase 6B menjelaskan _apa_ yang harus ada. Dokumen ini menjelaskan
_bagaimana_ perubahannya, dalam urutan apa, file mana yang tersentuh, dan bagaimana
diverifikasinya. Dokumen ini adalah pelengkap roadmap, bukan pengganti.

> Dokumen ini disusun setelah audit codebase terhadap `main` pada commit
> `6b98aed` (`Merge pull request #7 from Rumahkodingku/feat/phase-06a-parish-profile`)
> dan **9 keputusan yang sudah dijawab pengguna**. Seluruh keputusan di §1
> mengikat implementasi. Belum ada satu baris kode implementasi Phase 6B yang
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

Sembilan pertanyaan diajukan sebelum penyusunan plan ini. Jawaban berikut mengikat
seluruh isi dokumen dan sudah dikonfirmasi pemilik proyek.

| #   | Pertanyaan                                | Keputusan                                                                                       | Alasan yang dipakai                                                                                                                                                            |
| --- | ----------------------------------------- | ----------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| D-1 | Flag publikasi layanan                    | **`is_active` saja** (tanpa `is_published`)                                                     | PRD §9.8 dan roadmap 6B §6.2/§7.1: `is_active` adalah satu-satunya flag publikasi; default `false`. Modul 6A memakai `is_published`, tetapi dokumen fase 6B yang mengikat.     |
| D-2 | Bentuk route admin root                   | **`/admin/pelayanan` merender Daftar Pelayanan langsung**; nama route `services.index`          | Roadmap 6B-041: route harus merender Daftar dan menjadi tujuan langsung sidebar. Tidak ada halaman overview, jadi tidak perlu redirect pola D-42.                              |
| D-3 | Permission anak (requirements/steps/faqs) | **Ikut `services.*`** — otorisasi via `ServicePolicy`                                           | Anak tidak punya halaman daftar mandiri; roadmap §5.2 & RBAC.md §7 melarang permission untuk endpoint yang tidak perlu. Mengikuti pola penugasan pastor yang ikut `pastors.*`. |
| D-4 | Tab "Dokumen Terkait" (Phase 8 belum ada) | **Hilangkan tab**; `document_service` ditunda ke Phase 8 dan dicatat sebagai dependency         | Roadmap 6B §6.6/6B-073: tanpa model dokumen, integrasi ditunda. Tidak membuat migrasi pivot yang menunjuk tabel belum ada.                                                     |
| D-5 | Konten contoh anak 6 layanan              | **Hanya struktur 6 layanan** di `ServiceSeeder` (tanpa persyaratan/prosedur/FAQ contoh). **Diperbarui:** konten contoh penuh ditambahkan di **`DemoContentSeeder`** (demo-only), bukan di `ServiceSeeder`. | Prinsip D-34; roadmap §6.8/6B-038: jangan menampilkan syarat Lampiran A seolah konten resmi di seed struktural. Data contoh Lapiran A hanya hidup saat `demo.enabled` (perpanjangan D-53); E2E & seed produksi tetap bersih. |
| D-6 | Dokumentasi admin                         | **Ditunda** — `docs/ADMIN_GUIDE.md` tidak dibuat pada Phase 6B                                  | Keputusan pengguna. Catatan keputusan (`docs/DECISIONS.md`) tetap wajib; hanya panduan admin yang ditunda.                                                                     |
| D-7 | Normalisasi nomor telepon/WhatsApp        | **Util bersama `app/Support/PhoneNumber.php`** (`toTel()`, `toWhatsApp()`)                      | Dipakai 6B sekarang dan halaman Kontak Phase 8 nanti; satu sumber normalisasi (PRD KON-01).                                                                                    |
| D-8 | Allowlist ikon                            | **`config/service-icons.php`** (allowlist PHP) + **`resources/js/components/service-icon.tsx`** | Backend tahu daftar valid; render Lucide di satu komponen app-level (dipakai admin picker + halaman publik).                                                                   |
| D-9 | Cakupan eksekusi                          | **Bertahap: WS0–WS4 (backend) dulu, verifikasi, baru WS5+**                                     | Roadmap §17: jangan satu perubahan besar tanpa checkpoint.                                                                                                                     |

Nomor D-1 … D-9 adalah **nomor keputusan lokal dokumen ini**, bukan nomor `D-xx`
di `docs/DECISIONS.md`. Penomoran resmi Phase 6B dimulai dari **D-54**, karena
D-53 adalah nomor terakhir yang terpakai.

## 1.1 Pilihan minor yang diasumsikan

Diasumsikan sesuai rekomendasi; dapat dibatalkan kapan saja tanpa invalidasi
seluruh plan (lihat §14 untuk detail).

| #    | Pilihan minor          | Nilai                                                                                               |
| ---- | ---------------------- | --------------------------------------------------------------------------------------------------- |
| m-1  | Kategori `orang sakit` | Slug kategori **`orang-sakit`** (hyphen, sesuai roadmap §6.1); bukan enum PHP                       |
| m-2  | Slug + soft delete     | Slug **tetap** saat soft delete (pola `posts`/`events`, D-44/D-45); **tanpa** `active_marker`       |
| m-3  | Soft delete scope      | `services` + `service_categories` soft delete; tiga tabel anak **hard delete** (`cascade`)          |
| m-4  | "Terakhir diperbarui"  | `$touches = ['service']` pada model anak; `services.updated_at` ikut berubah (SVC-03)               |
| m-5  | Reorder                | Tombol naik/turun (tanpa library baru) + Action transaksional; nomor dari posisi                    |
| m-6  | Accordion FAQ          | Native `<details>`/`<summary>` atau `collapsible` (tidak ada primitif `accordion`)                  |
| m-7  | State tab edit         | Query string `?tab=…`, bukan state lokal; tiap tab memakai endpoint sendiri                         |
| m-8  | Nama route             | `services.*`, `service-requirements.*`, `service-steps.*`, `service-faqs.*`, `service-categories.*` |
| m-9  | Nama halaman admin     | `admin/pelayanan`, `admin/pelayanan/{tambah,ubah}`, `admin/master-data/kategori-pelayanan/*`        |
| m-10 | Urutan fallback kontak | Kontak khusus layanan → `site_settings` → pesan teks sopan                                          |
| m-11 | Ikon tak dikenal       | Render ikon netral (fallback), tidak error                                                          |
| m-12 | Print                  | `print:` utility Tailwind + satu blok `@media print` bila perlu di `app.css`                        |

---

# 2. Kondisi Codebase Saat Ini

Audit terhadap `main` pada commit `6b98aed`. Phase 1–6A sudah selesai dan hijau.

## 2.1 Fondasi yang sudah ada dan cukup — **diaudit, bukan ditulis ulang**

| Area               | Status aktual                                                                                          | File                                                               |
| ------------------ | ------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------ |
| Timezone           | `app.timezone = 'UTC'` + `display_timezone = 'Asia/Pontianak'`; datetime dikirim ISO 8601              | `config/app.php`, `HandleInertiaRequests.php`                      |
| Strict mode        | `Model::shouldBeStrict(! isProduction())` — lazy load melempar                                         | `AppServiceProvider.php:157`                                       |
| Gate `/admin`      | `['auth','verified',EnsureAccountIsActive,EnsureAdminAccess]` + `Gate::before` (`null`, bukan `false`) | `routes/web.php:108`, `AppServiceProvider.php:108`                 |
| Sanitasi HTML      | `HtmlSanitizer` (singleton) backed `symfony/html-sanitizer`; allowlist di `config/html.php`            | `app/Services/HtmlSanitizer.php`, `config/html.php`                |
| Rich text          | Editor Tiptap penuh + `RichText` (satu-satunya `dangerouslySetInnerHTML` sah bersama JSON-LD)          | `components/rich-text-editor.tsx`, `rich-text.tsx`                 |
| Media              | `MediaField` + `useMediaUpload`; **tidak dibutuhkan 6B** (tidak ada kolom gambar layanan)              | `components/media-field.tsx`, `hooks/use-media-upload.ts`          |
| Permission         | **61** permission; nama `entity.verb` satu kata; `RbacFoundationTest` mengunci daftar                  | `database/seeders/PermissionSeeder.php:41`                         |
| Sorting list admin | `SortsAdminList::applyAdminSort()` — whitelist, tie-breaker `id` terakhir                              | `app/Concerns/SortsAdminList.php`                                  |
| List prop          | `array_values(Resource::collection(…)->resolve())` untuk list non-paginate (D-39)                      | Controller Berita/Agenda/Profil                                    |
| Layout resolution  | `app.tsx` memilih layout dari nama komponen; halaman hanya `Component.layout = { breadcrumbs }`        | `resources/js/app.tsx`                                             |
| SEO                | `components/seo.tsx` + tag OG/`data-inertia` di `app.blade.php`                                        | `resources/js/components/seo.tsx`, `resources/views/app.blade.php` |
| UI primitives      | `tabs`, `switch`, `collapsible`, `dialog`, `table`, `badge`, `select`, `checkbox`, `dropdown-menu`     | `resources/js/components/ui/`                                      |
| Navigasi publik    | Navbar & footer sudah menautkan `@/routes/pelayanan` (nama route harus dipertahankan)                  | `layouts/public/components/{parish-navbar,parish-footer}.tsx`      |
| Testing            | Pest 4.7 (`seedRolesAndPermissions()`), Vitest (`tests/js/`), Playwright (`tests/e2e/`)                | `tests/Pest.php`, `vite.config.ts`, `playwright.config.ts`         |

## 2.2 Yang sudah ada tapi harus diganti — pekerjaan utama Phase 6B

| Area                     | Kekurangan yang ditemukan                                                                                                                              |
| ------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Route publik pelayanan   | `routes/web.php:67-70` masih `Route::inertia('public/pelayanan')` dan closure `pelayanan.show` yang menjawab 200 untuk slug apa pun (melanggar XC-E3). |
| Halaman publik pelayanan | `features/public/pelayanan/pages/{index,show}.tsx` placeholder `[ISI: …]` (hanya `SectionHeading`).                                                    |
| Sidebar `Pelayanan`      | `app-sidebar.tsx:110` `{ title: 'Pelayanan', icon: Sparkles }` **tanpa `href`** → baris nonaktif "belum".                                              |
| Sidebar `Master Data`    | 3 anak (Lokasi, Kategori Berita, Kategori Agenda); belum ada `Kategori Pelayanan`.                                                                     |
| Test placeholder publik  | `PublicPlaceholderRoutesTest` memuat baris `pelayanan` (detail placeholder) yang harus dipindah ke test modul.                                         |
| Test RBAC                | `RbacFoundationTest` punya array permission hardcoded — ditambah permission baru, test merah sampai diperbarui.                                        |
| Test sidebar & E2E       | `tests/js/components/app-sidebar.test.tsx` (baris ~330: `queryByRole('link', { name: 'Pelayanan' })` harus null) dan `tests/e2e/master-data.spec.ts`.  |

## 2.3 Yang belum ada dan harus dibuat

**5 tabel** (3 induk/master + 3 anak) · **5 model** · **5 factory** · **2 policy** ·
**~14 FormRequest** · **1 Action** · **1 Rule** · **6 controller admin + 1 publik** ·
**~9 `JsonResource`** · **~35 route** · **~14 halaman Inertia** (3 admin layanan +
3 master data + 2 publik di-rewrite + komponen) · **~14 komponen frontend** ·
**1 config** · **1 util PHP** · **1 file `lang/id`** · **~14 file test** ·
**perubahan sidebar, RBAC, DECISIONS**

## 2.4 Konvensi yang harus diikuti — bukan landmark baru

| Konvensi  | Bentuk persisnya di repo                                                                                 | Konsekuensi untuk Phase 6B                                                       |
| --------- | -------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------- |
| Fillable  | Atribut **`#[Fillable([…])]`**, bukan properti (dicek `ArchitectureTest`)                                | Lima model baru wajib memakai atribut                                            |
| Cast      | Method `protected function casts(): array`                                                               | `is_active`/`is_form_open`/`is_emergency` → `boolean`; `sort_order` → `integer`  |
| Resource  | `JsonResource` terpisah admin vs publik; `ResolvesMediaUrl` tidak dipakai (tanpa media)                  | Jangan pernah mengirim model mentah                                              |
| List prop | **`array_values(Resource::collection(…)->resolve())`** untuk list non-paginate (D-39)                    | Berlaku untuk anak layanan, opsi kategori, layanan lain                          |
| Sorting   | `applyAdminSort($query, $request, $whitelist, $default)`; whitelist wajib; tie-breaker `id` terakhir     | Untuk daftar layanan & kategori                                                  |
| Otorisasi | `FormRequest::authorize()` untuk `store`/`update`; `Gate::authorize()`/Policy untuk lainnya              | Tidak pernah di dua tempat                                                       |
| Policy    | Satu Policy per model; `super_admin` lewat `Gate::before` → **wajib `can()`**, bukan `hasPermissionTo()` | `ServicePolicy`, `ServiceCategoryPolicy`                                         |
| Action    | Hanya untuk operasi multi-model/transaksi                                                                | `ReorderServiceItemsAction`                                                      |
| Slug      | `posts`/`events` mempertahankan slug saat soft delete (D-45); `Rule::unique` tanpa `withoutTrashed()`    | `services` mengikuti aturan yang sama                                            |
| Route     | Eksplisit `Route::get/post/patch/delete` + `->name()` dalam satu `routes/web.php`                        | Route admin di dalam grup `/admin`; nama unik; **tidak ada redirect tanpa nama** |
| Halaman   | Tipis; `Index.layout = { breadcrumbs }`; form & tabel di `components/`/`pages/…/components`              | Mengikuti `features/admin/master-data/pages/lokasi/`                             |
| Form      | `<form noValidate>` — **wajib**                                                                          | Server satu-satunya validator; pesan Indonesia dari `lang/id/validation.php`     |
| Select    | `ChoiceSelect` (Radix, sentinel `__empty__`) untuk form; `<select>` native untuk filter                  | Untuk kategori, status, ikon                                                     |
| Copy      | Bahasa Indonesia                                                                                         | Semua label, flash, dan `messages()`                                             |

## 2.5 Yang **tidak boleh** diasumsikan ada

1. **Tabel/model `documents` dan `document_categories`.** Tidak ada; Phase 8. Tidak
   ada pivot `document_service`.
2. **Halaman/route Kontak.** `/kontak` masih placeholder; jangan membuat halaman
   kontak kedua. Fallback kontak hanya membaca `SiteSettingsService`.
3. **Normalisasi nomor telepon PHP.** Belum ada; dibuat `app/Support/PhoneNumber.php`.
4. **Primitif `accordion`.** `@radix-ui/react-accordion` tidak terpasang. Jangan
   menambah dependency untuk ini.
5. **Media library page.** Hanya picker; 6B tidak memakai media sama sekali.
6. **Parser tanggal JS.** `package.json` tidak punya `date-fns`/`dayjs`/`luxon`;
   pakai `lib/datetime.ts` yang ada.

---

# 3. Kontrak yang Harus Dijaga

Kontrak ini sudah diuji test yang ada. Melanggarnya berarti test hijau berubah merah.

## C-1 — `PublicPlaceholderRoutesTest` harus mengosongkan baris `pelayanan`

`tests/Feature/Public/PublicPlaceholderRoutesTest.php` memuat
`'pelayanan' => ['/pelayanan/contoh-pelayanan', 'public/pelayanan/show', 'Fase 6']`.
Setelah Phase 6B, baris itu **dihapus** dan perilaku 404-nya pindah ke
`tests/Feature/Pelayanan/PublicServicePageTest.php`. Docblock file itu menyatakan
perubahan seperti ini memang bagian dari pekerjaan modul, bukan sesudahnya.

## C-2 — `PublicRoutesTest` tetap memuat `/pelayanan`

`tests/Feature/Public/PublicRoutesTest.php` baris 47 & 75 memetakan
`'/pelayanan' => ['/pelayanan', 'public/pelayanan']`. **Nama komponen `public/pelayanan`
tetap dipakai** (file `pages/index.tsx` di root feature), sehingga baris ini tidak perlu
diubah selama nama halaman dipertahankan.

## C-3 — `RbacFoundationTest` punya daftar permission hardcoded

`tests/Feature/Foundation/RbacFoundationTest.php` memakai array `$expected` dan
`expect(...)->toBe(app(PermissionSeeder::class)->expectedCount())`. Menambah permission
**wajib** menambah baris di `$expected` dan memperbarui `PermissionSeeder::PERMISSIONS`
(atau test merah). Test juga menegaskan setiap permission cocok `/^[a-z]+\.[a-z]+$/`.

## C-4 — Aturan `ArchitectureTest` yang mengikat

`tests/Unit/ArchitectureTest.php` antara lain:

- setiap nama halaman yang diminta controller **harus** terdaftar di frontend;
- halaman mempertahankan folder di namanya (mis. `admin/pelayanan/ubah`);
- `features/a/x` tidak boleh mengimpor `features/b/y`;
- hanya `rich-text.tsx` dan `event-json-ld.tsx` yang boleh `dangerouslySetInnerHTML`;
- komponen tidak boleh `href="/…"` hard-code; sidebar mengimpor dari `@/routes/*`;
- model memakai atribut `#[Fillable]` dan tidak memakai `DB::` facade.

Pelanggaran = build gagal, bukan sekadar peringatan.

## C-5 — Nama komponen Inertia harus cocok path file

`pageName()` (`resources/js/lib/page-name.ts`) membuang `index` di **root feature** dan
mempertahankannya satu folder di bawah. Konsekuensi konkret:

| File                                                            | Nama Inertia                                 |
| --------------------------------------------------------------- | -------------------------------------------- |
| `features/admin/pelayanan/pages/index.tsx`                      | `admin/pelayanan`                            |
| `features/admin/pelayanan/pages/tambah.tsx`                     | `admin/pelayanan/tambah`                     |
| `features/admin/pelayanan/pages/ubah.tsx`                       | `admin/pelayanan/ubah`                       |
| `features/admin/master-data/pages/kategori-pelayanan/index.tsx` | `admin/master-data/kategori-pelayanan/index` |
| `features/public/pelayanan/pages/index.tsx`                     | `public/pelayanan`                           |
| `features/public/pelayanan/pages/show.tsx`                      | `public/pelayanan/show`                      |

Controller dan frontend menghitung nama ini secara terpisah; `ArchitectureTest` yang
menjembatani keduanya. **Jangan** menulis `admin/pelayanan/index` untuk root feature.

## C-6 — Test sidebar dan E2E harus diperbarui bersamaan

`tests/js/components/app-sidebar.test.tsx` (array `MASTER_DATA_SUB_MENU`, asersi
disclosure, dan asersi bahwa `Pelayanan` bukan link) serta `tests/e2e/master-data.spec.ts`
(href **dan** klik) mengunci sidebar saat ini. Mengubah menu `Pelayanan` + menambah anak
`Kategori Pelayanan` mewajibkan keduanya diperbarui dalam perubahan yang sama.

## C-7 — `language`/kolom yang dihapus tidak boleh menyisakan sort-whitelist

`MassScheduleController` memetakan kunci `sort` langsung ke `ORDER BY`; kunci yatim
menghasilkan 500 (bukan fallback default). Setiap whitelist sort untuk daftar 6B harus
hanya memuat kolom yang benar-benar ada.

---

# 4. Model Data dan Perilaku

Acuan: PRD §9.8 + roadmap 6B §6. Perubahan dari enum ke master kategori dicatat di
`docs/DECISIONS.md` (WS0).

## 4.1 Tabel `service_categories` (master, soft delete)

| Kolom                     | Tipe                   | Aturan                                     |
| ------------------------- | ---------------------- | ------------------------------------------ |
| `id`                      | BIGINT UNSIGNED PK     |                                            |
| `name`                    | VARCHAR(100)           | Wajib                                      |
| `slug`                    | VARCHAR(100)           | Wajib, **unique** (tetap saat soft delete) |
| `description`             | TEXT, nullable         |                                            |
| `icon`                    | VARCHAR(100), nullable | Key dari allowlist `service-icons`         |
| `sort_order`              | INT UNSIGNED default 0 |                                            |
| `is_active`               | BOOLEAN default true   | D-1: dipakai sebagai kontrol aktif         |
| `created_at`/`updated_at` | TIMESTAMP              |                                            |
| `deleted_at`              | TIMESTAMP, nullable    | Soft delete                                |

Seed: `inisiasi` (Sakramen Inisiasi), `perkawinan` (Sakramen Perkawinan),
`orang-sakit` (Pelayanan Orang Sakit).

## 4.2 Tabel `services` (soft delete)

Kolom persis PRD §9.8, dengan `category` enum **diganti** `service_category_id` FK:

`title(255)`, `slug(255) unique`, `service_category_id` FK→`service_categories`
**restrictOnDelete**, `icon(100) null`, `summary text null`, `introduction longText null`
(HTML tersanitasi), `schedule_info text null`, `fee_info text null`, `contact_name(255) null`,
`contact_phone(50) null`, `form_url(2048) null`, `form_button_label(100) default 'Daftar Sekarang'`,
`is_form_open bool default false`, `form_closed_message text null`, `is_emergency bool default false`,
`is_active bool default false` (D-1), `sort_order int default 0`, `timestamps`, `softDeletes`.

Index: `service_category_id`, `sort_order`, `is_active`. **Tidak ada** `active_marker`
(m-2).

## 4.3 Tabel anak (hard delete, `cascadeOnDelete`)

- `service_requirements`: `service_id` FK cascade, `text(255)`, `note text null`,
  `sort_order int default 0`, `timestamps`; index `(service_id, sort_order)`.
- `service_steps`: `service_id` FK cascade, `title(255)`, `description text null`,
  `sort_order`, `timestamps`; index sama.
- `service_faqs`: `service_id` FK cascade, `question(255)`, `answer text`, `sort_order`,
  `timestamps`; index sama.

## 4.4 Perilaku bisnis yang mengikat

1. **Publikasi (SVC-01/D-1).** Layanan baru selalu `is_active = false`. Publik = `is_active = true`
    - `deleted_at IS NULL`. Aktivasi adalah aksi eksplisit; UI menampilkan badge "Perlu Validasi"
      untuk layanan nonaktif.
2. **Kategori aktif (roadmap §6.1).** Layanan tidak dapat dibuat/diaktifkan dengan kategori
   nonaktif; kategori yang masih dipakai layanan tidak dapat dihapus (pesan arahkan memindah/
   menonaktifkan).
3. **Slug (D-44/D-45).** Slug layanan tetap unik termasuk baris soft-deleted; `Rule::unique`
   **tanpa** `withoutTrashed()`. Route publik `{service:slug}` memakai binding + scope aktif →
   nonaktif/soft-deleted = 404.
4. **"Terakhir diperbarui" (SVC-03, m-4).** Model anak memakai `$touches = ['service']` sehingga
   `services.updated_at` berubah saat anak dibuat/diubah/dihapus/diurutkan.
5. **Google Form opsional (SVC-05, roadmap §7.4).** `form_url` nullable, tidak `required`.
   `is_form_open` hanya bermakna bila URL ada dan bukan emergency. Normalisasi di backend:
   URL kosong → `is_form_open = false`; `is_emergency = true` → `form_url = null` &
   `is_form_open = false`.
6. **Validasi URL Google Form (SVC-05).** Parse URL: skema `https`; host `docs.google.com`
   dengan path diawali `/forms/`, atau host `forms.gle`; tolak kredensial. Pesan Bahasa Indonesia.
7. **Layanan darurat (SVC-06).** `contact_phone` wajib sebelum `is_active = true` bila
   `is_emergency = true`. Tidak ada tombol daftar/langkah untuk emergency; tampilkan `tel:`/
   `wa.me`.
8. **Empat kondisi CTA (PRD §7.6).** Aktif (form terbuka), tombol nonaktif (ditutup),
   tanpa Google Form (arahkan kontak), emergency (kontak cepat).
9. **Fail-safe kontak (roadmap §9.2.2).** Fallback: kontak khusus → `site_settings`
   (`contact_phone`/`contact_whatsapp`) → pesan teks sopan. Tidak ada tombol palsu.
10. **Kategori "orang sakit".** Slug `orang-sakit` (m-1), dicatat sebagai divergensi dari
    enum PRD `orang_sakit`.

---

# 5. Ringkasan Task

Dipetakan dari roadmap §6–§14 ke workstream WS0 … WS9 sesuai urutan §17.

| WS  | Isi                                                                   | Task roadmap (contoh)        |
| --- | --------------------------------------------------------------------- | ---------------------------- |
| WS0 | Keputusan schema/domain di `docs/DECISIONS.md`                        | 6B-001…6B-015, 6B-329        |
| WS1 | Migrasi, model, relasi, scope, factory, seeder 3 kategori + 6 layanan | 6B-016…6B-039                |
| WS2 | CRUD service + kategori, FormRequest, Policy, Resource, permission    | 6B-040…6B-061                |
| WS3 | CRUD anak (requirements/steps/faqs) + reorder transaksional           | 6B-043…6B-045, 6B-145…6B-162 |
| WS4 | Rule URL Google Form, emergency, normalisasi kontak, sanitasi         | 6B-052…6B-067, 6B-252…6B-259 |
| WS5 | Admin UI: Daftar, form bertab, Kategori di Master Data, sidebar       | 6B-075…6B-144, 6B-086…6B-111 |
| WS6 | Halaman publik indeks + detail + CTA + kontak + FAQ                   | 6B-208…6B-241                |
| WS7 | Print, aksesibilitas, SEO, responsif                                  | 6B-195…6B-207, 6B-242…6B-251 |
| WS8 | Feature/JS/E2E test + regresi Phase 4/5/6A                            | 6B-265…6B-311                |
| WS9 | Dokumentasi keputusan + gate CI + status roadmap                      | 6B-312…6B-331                |

---

# 6. Urutan Implementasi

> Aturan roadmap §17: selesaikan satu workstream, verifikasi, baru lanjut. WS0–WS4
> adalah checkpoint pertama; jangan lanjut WS5 sebelum test backend hijau.

## WS0 — Keputusan & Dokumentasi

1. Tambahkan entri `docs/DECISIONS.md` **D-54+** (satu atau beberapa) yang mencatat:
    - normalisasi `services.category` enum → master `service_categories` + FK, dan
      penyelarasan PRD §9.8/§Lampiran C (tanpa mengedit PRD);
    - `is_active` sebagai satu-satunya flag publikasi (D-1);
    - slug tetap saat soft delete (m-2) mengikuti D-45;
    - `document_service` ditunda ke Phase 8 (D-4);
    - permission `services.*` + `servicecategories.*`, anak ikut `services.*` (D-3);
    - slug kategori `orang-sakit` (m-1);
    - util `PhoneNumber` (D-7) dan allowlist ikon `config/service-icons.php` (D-8).
2. Jangan mengedit `docs/PRD.md` maupun `docs/roadmap/phase-6B-parish-service.md`.

## WS1 — Database, Model, Factory, Seeder

### 1.1 Migrasi (satu file, pola "satu modul satu file" seperti Phase 05)

`database/migrations/2026_*_create_service_tables.php` berisi lima tabel: `service_categories`,
`services`, `service_requirements`, `service_steps`, `service_faqs` (spesifikasi §4).
Catatan: FK `services.service_category_id` **restrictOnDelete**; FK anak
**cascadeOnDelete**; index eksplisit; `softDeletes()` pada dua tabel induk saja.

### 1.2 Model

- `App\Models\ServiceCategory` — `#[Fillable]`, `casts()`, `SoftDeletes`, relasi
  `services()`, scope `active()`, scope `ordered()`.
- `App\Models\Service` — `#[Fillable]`, `casts()`, `SoftDeletes`, relasi
  `serviceCategory()` (BelongsTo), `requirements()`, `steps()`, `faqs()` (HasMany),
  scope `active()`, scope `ordered()` (`sort_order`, `id`).
- `App\Models\ServiceRequirement`, `ServiceStep`, `ServiceFaq` — `#[Fillable]`, `casts()`,
  relasi `service()`, scope `ordered()`, **`protected $touches = ['service'];`** (m-4).

### 1.3 Factory

Lima factory generik (Faker), dengan state `emergency()`, `active()`,
`withChildren()` untuk pemakaian test. Tidak mengarang data paroki nyata.

### 1.4 Seeder

- `ServiceCategorySeeder` — `firstOrCreate` berkunci `slug` (jangan menimpa editan admin);
  3 kategori bawaan.
- `ServiceSeeder` — `firstOrCreate` berkunci `slug`; 6 layanan, `is_active = false`,
  `form_url = null`, `is_form_open = false`; `perminyakan-orang-sakit` `is_emergency = true`;
  kategori di-resolve by slug. **Tidak ada** anak contoh (D-5).
- Keduanya dipanggil dari `database/seeders/DatabaseSeeder.php`.

### 1.5 Gate WS1

`php artisan migrate:fresh` di DB test + `migrate:rollback`; `ParishProfileSchemaTest`-style
`ServiceSchemaTest` menguji kolom/FK/index & reversibilitas `down()`.

## WS2 — Backend CRUD Service & Kategori

### 2.1 Permission (`PermissionSeeder::PERMISSIONS`)

- `services.view`, `services.create`, `services.update`, `services.delete`
- `servicecategories.view`, `servicecategories.create`, `servicecategories.update`,
  `servicecategories.delete`

Total baru: **8** (61 → 69). Perbarui `RbacFoundationTest::$expected` + `docs/RBAC.md` §1.

### 2.2 Policy

`ServicePolicy` (viewAny/create/update/delete → `services.*`), `ServiceCategoryPolicy`
(`servicecategories.*`). Semua memakai `$user->can(...)`.

### 2.3 Concern

`ServiceValidationRules`, `ServiceCategoryValidationRules`, `ServiceFormMeta`
(mengirim `labels`, `hints`, kategori options, dan allowlist ikon ke React — jangan
hard-code label di komponen).

### 2.4 FormRequest

- `StoreServiceRequest` / `UpdateServiceRequest` — `authorize()` `can('create'|'update', …)`;
  aturan `title`, `slug` (unique + ignore saat update), `service_category_id` (exists +
  kategori **aktif** saat `is_active`), `icon` (`Rule::in` allowlist), `summary` max,
  `introduction` max (HTML), `schedule_info`, `fee_info`, `contact_name`, `contact_phone`,
  `form_url` (`nullable` + `GoogleFormUrl`), `form_button_label`, `is_form_open`,
  `form_closed_message`, `is_emergency`, `is_active` (validasi emergency→phone,
  kategori aktif), `sort_order`. `prepareForValidation()`: sanitasi `introduction`,
  normalisasi `form_url`/`is_form_open`/emergency.
- `StoreServiceCategoryRequest` / `UpdateServiceCategoryRequest` — `name`, `slug` unique,
  `description`, `icon`, `sort_order`, `is_active`.

### 2.5 Controller & Resource

- `App\Http\Controllers\Pelayanan\ServiceController`:
  `index` (list + filter/pencarian/kategori/status + `applyAdminSort`),
  `create`, `store`, `edit` (memuat seluruh anak), `update`, `destroy` (soft delete).
- `App\Http\Controllers\MasterData\ServiceCategoryController`: CRUD; `destroy` menolak
  bila kategori masih dipakai layanan (`services()->withTrashed()->exists()`), dengan pesan
  yang mengarahkan memindah/menonaktifkan.
- Resource: `ServiceListResource`, `ServiceResource`, `ServiceCategoryResource`,
  `ServiceOptionResource`; publik: `PublicServiceCardResource`, `PublicServiceDetailResource`.

### 2.6 Gate WS2

`php artisan test --compact tests/Feature/Pelayanan`; `./vendor/bin/pint --dirty --format agent`;
`php artisan wayfinder:generate --with-form`; `npm run types:check`.

## WS3 — CRUD Anak & Reorder

### 3.1 Controller

- `ServiceRequirementController` / `ServiceStepController` / `ServiceFaqController`,
  masing-masing `store`, `update`, `destroy`, `reorder` (nested di bawah `{service}`).

### 3.2 FormRequest & Action

- `StoreServiceRequirementRequest`, `UpdateServiceRequirementRequest`,
  `StoreServiceStepRequest`, `UpdateServiceStepRequest`, `StoreServiceFaqRequest`,
  `UpdateServiceFaqRequest` — `authorize()` `can('update', $this->route('service'))` (D-3).
- `ReorderServiceItemsRequest` — validasi array id milik service.
- `ReorderServiceItemsAction` — `DB::transaction`, menetapkan ulang `sort_order` 0..n.

### 3.3 Gate WS3

CRUD/ordering test hijau; pastikan nomor urut berasal dari posisi, bukan `id`.

## WS4 — Aturan Validasi Khusus

- `App\Rules\GoogleFormUrl` (SVC-05) + pesan Indonesia; 5 kasus bypass ditolak.
- Validasi aktivasi emergency (`contact_phone`) dan aktivasi dengan kategori nonaktif.
- `app/Support/PhoneNumber.php` (`toTel()`, `toWhatsApp()`, normalisasi `0812…` → `62812…`).
- Normalisasi `form_url`/`is_form_open`/emergency di FormRequest.

## WS5 — Admin UI

### 5.1 Daftar Pelayanan (`/admin/pelayanan`)

- `features/admin/pelayanan/pages/index.tsx` — tabel TanStack v9 server-sorted
  (`manualSorting`), kolom `#`, Nama+slug, Kategori (badge), Pendaftaran
  (`Google Form`/`Kontak Langsung`/`Tidak Diatur`), Status, Urutan, Terakhir Diperbarui,
  Aksi (preview bila aktif, edit). Filter pencarian + kategori (dari `service_categories`
  aktif) + status. Pagination + footer jumlah. Empty/loading/error state.

### 5.2 Tambah/Edit bershell tab

- `pages/tambah.tsx` + `pages/ubah.tsx` + `components/service-form-tabs.tsx`.
- Tab: `Informasi Umum | Persyaratan | Prosedur | Jadwal & Biaya | Kontak & Pendaftaran | FAQ`
  (D-4: **tanpa** Dokumen Terkait). State tab via `?tab=` (m-7).
- `Informasi Umum`: nama*, slug*, kategori\* (aktif saja), ikon (picker allowlist), ringkasan,
  pengantar (Tiptap), panel kanan urutan + switch aktif + callout "perlu validasi".
- Tab anak: daftar kartu + dialog tambah/edit + tombol naik/turun + konfirmasi hapus;
  nomor urut dari posisi.
- `Jadwal & Biaya`: dua textarea; biaya boleh kosong, jangan tampilkan "Rp0/Gratis".
- `Kontak & Pendaftaran`: panel Pendaftaran opsional + tombol "Buka Tautan"
  (`rel="noopener noreferrer"`, hanya saat valid) + label tombol + switch `is_form_open`
    - pesan tutup; panel Kontak Penanggung Jawab; panel Layanan Darurat.
- Semua `<form noValidate>`.

### 5.3 Kategori Pelayanan (Master Data)

- `features/admin/master-data/pages/kategori-pelayanan/{index,tambah,ubah}.tsx` +
  `components/service-category-form.tsx`; kolom nama, slug, deskripsi, jumlah layanan,
  status, urutan.

### 5.4 Ikon & Sidebar

- `config/service-icons.php` (allowlist) + `resources/js/components/service-icon.tsx`
  (render Lucide; fallback netral).
- `app-sidebar.tsx`: beri `href` pada `Pelayanan` → `servicesIndex()`; tambah anak
  `Kategori Pelayanan` di `Master Data` → `serviceCategoriesIndex()`.

## WS6 — Halaman Publik

- `App\Http\Controllers\Pelayanan\PublicServiceController` (`index`, `show`).
- Ganti route placeholder dengan controller; pertahankan nama route `pelayanan.index`
  dan `pelayanan.show`.
- `pages/index.tsx`: layanan aktif + `serviceCategory`, dikelompokkan per
  `service_categories.sort_order`; kartu (ikon, nama, ringkasan, tautan detail).
- `pages/show.tsx`: susunan PRD §7.6 (pengantar → CTA atas → 4 langkah (non-darurat,
  hanya bila pakai form) → syarat → prosedur → jadwal → biaya → kontak → FAQ →
  CTA bawah → navigasi layanan lain → "Terakhir diperbarui").
- Komponen: `service-card.tsx`, `service-cta.tsx`, `contact-actions.tsx`, `faq-list.tsx`.
- **Empat kondisi CTA** persis tabel PRD §7.6; tombol nonaktif benar-benar inert.
- Emergency tanpa tombol daftar/langkah; `tel:`/`wa.me` bila nomor valid.
- 404 untuk nonaktif/soft-deleted.

## WS7 — Print, A11y, SEO, Responsif

- `print:` untuk detail; sembunyikan nav/CTA; syarat & langkah utuh (6B-242…245).
- A11y: label, focus ring, heading semantik, `aria-label` pada tombol ikon,
  FAQ bisa keyboard, tab tidak trap fokus (6B-195…202, 6B-246…250).
- SEO via `components/seo.tsx` + `Head`; sinkron `head-key` dengan `app.blade.php`.
- Cek 360/390/768/1024/1440 px; tanpa scroll horizontal.

## WS8 — Testing & Regresi

- Feature: skema, CRUD service/kategori, CRUD/order anak, URL form, emergency,
  publikasi/404, fallback kontak, last-updated, sanitasi, otorisasi, seeder.
- JS: `app-sidebar.test.tsx` diperbarui; test CTA/FAQ.
- E2E: `tests/e2e/pelayanan.spec.ts` + update `master-data.spec.ts`.
- Regresi: MassSchedule, News, Agenda, Profil, Public.

## WS9 — Dokumentasi & Gate CI

- Finalkan entri `docs/DECISIONS.md` (WS0) berdasarkan hasil nyata.
- Perbarui `docs/RBAC.md` §1 dan `docs/roadmap/README.md` **hanya setelah** task/AC/test
  terbukti.
- Laporan akhir format roadmap §18.
- (D-6) `docs/ADMIN_GUIDE.md` **tidak** dibuat pada fase ini.

---

# 7. File yang Akan Terpengaruh

## 7.1 Dibuat — backend

```text
database/migrations/2026_*_create_service_tables.php

database/factories/ServiceCategoryFactory.php
database/factories/ServiceFactory.php
database/factories/ServiceRequirementFactory.php
database/factories/ServiceStepFactory.php
database/factories/ServiceFaqFactory.php

database/seeders/ServiceCategorySeeder.php
database/seeders/ServiceSeeder.php

app/Models/ServiceCategory.php
app/Models/Service.php
app/Models/ServiceRequirement.php
app/Models/ServiceStep.php
app/Models/ServiceFaq.php

app/Policies/ServicePolicy.php
app/Policies/ServiceCategoryPolicy.php

app/Concerns/ServiceValidationRules.php
app/Concerns/ServiceCategoryValidationRules.php
app/Concerns/ServiceFormMeta.php

app/Http/Requests/Pelayanan/StoreServiceRequest.php
app/Http/Requests/Pelayanan/UpdateServiceRequest.php
app/Http/Requests/Pelayanan/StoreServiceCategoryRequest.php
app/Http/Requests/Pelayanan/UpdateServiceCategoryRequest.php
app/Http/Requests/Pelayanan/StoreServiceRequirementRequest.php
app/Http/Requests/Pelayanan/UpdateServiceRequirementRequest.php
app/Http/Requests/Pelayanan/StoreServiceStepRequest.php
app/Http/Requests/Pelayanan/UpdateServiceStepRequest.php
app/Http/Requests/Pelayanan/StoreServiceFaqRequest.php
app/Http/Requests/Pelayanan/UpdateServiceFaqRequest.php
app/Http/Requests/Pelayanan/ReorderServiceItemsRequest.php

app/Actions/Pelayanan/ReorderServiceItemsAction.php

app/Rules/GoogleFormUrl.php
app/Support/PhoneNumber.php

app/Http/Controllers/Pelayanan/ServiceController.php
app/Http/Controllers/Pelayanan/ServiceRequirementController.php
app/Http/Controllers/Pelayanan/ServiceStepController.php
app/Http/Controllers/Pelayanan/ServiceFaqController.php
app/Http/Controllers/Pelayanan/PublicServiceController.php
app/Http/Controllers/MasterData/ServiceCategoryController.php

app/Http/Resources/Pelayanan/ServiceListResource.php
app/Http/Resources/Pelayanan/ServiceResource.php
app/Http/Resources/Pelayanan/ServiceCategoryResource.php
app/Http/Resources/Pelayanan/ServiceOptionResource.php
app/Http/Resources/Public/Pelayanan/PublicServiceCardResource.php
app/Http/Resources/Public/Pelayanan/PublicServiceDetailResource.php

config/service-icons.php
lang/id/pelayanan.php
```

## 7.2 Dibuat — frontend

```text
resources/js/features/admin/pelayanan/pages/index.tsx
resources/js/features/admin/pelayanan/pages/tambah.tsx
resources/js/features/admin/pelayanan/pages/ubah.tsx
resources/js/features/admin/pelayanan/components/service-form-tabs.tsx
resources/js/features/admin/pelayanan/components/tab-informasi-umum.tsx
resources/js/features/admin/pelayanan/components/tab-persyaratan.tsx
resources/js/features/admin/pelayanan/components/tab-prosedur.tsx
resources/js/features/admin/pelayanan/components/tab-jadwal-biaya.tsx
resources/js/features/admin/pelayanan/components/tab-kontak-pendaftaran.tsx
resources/js/features/admin/pelayanan/components/tab-faq.tsx
resources/js/features/admin/pelayanan/components/requirement-dialog.tsx
resources/js/features/admin/pelayanan/components/step-dialog.tsx
resources/js/features/admin/pelayanan/components/faq-dialog.tsx
resources/js/features/admin/pelayanan/components/columns.tsx
resources/js/features/admin/pelayanan/components/service-status-badge.tsx
resources/js/features/admin/pelayanan/components/registration-method-badge.tsx
resources/js/features/admin/pelayanan/components/icon-picker.tsx
resources/js/features/admin/pelayanan/types.ts

resources/js/features/admin/master-data/pages/kategori-pelayanan/index.tsx
resources/js/features/admin/master-data/pages/kategori-pelayanan/tambah.tsx
resources/js/features/admin/master-data/pages/kategori-pelayanan/ubah.tsx
resources/js/features/admin/master-data/pages/kategori-pelayanan/components/service-category-form.tsx
resources/js/features/admin/master-data/types/service-category.ts

resources/js/features/public/pelayanan/components/service-card.tsx
resources/js/features/public/pelayanan/components/service-cta.tsx
resources/js/features/public/pelayanan/components/contact-actions.tsx
resources/js/features/public/pelayanan/components/faq-list.tsx

resources/js/components/service-icon.tsx
```

## 7.3 Diubah — backend

| File                                    | Perubahan                                                                                     |
| --------------------------------------- | --------------------------------------------------------------------------------------------- |
| `routes/web.php`                        | Ganti 2 route placeholder pelayanan → controller publik; tambah blok admin service + kategori |
| `database/seeders/PermissionSeeder.php` | +8 permission; `expectedCount()` otomatis                                                     |
| `database/seeders/DatabaseSeeder.php`   | Tambah `ServiceCategorySeeder`, `ServiceSeeder`                                               |
| `docs/DECISIONS.md`                     | Entri D-54+ (WS0/WS9)                                                                         |
| `docs/RBAC.md`                          | §1 daftar permission                                                                          |
| `docs/roadmap/README.md`                | Status Phase 6B (setelah verifikasi)                                                          |
| `config/site-settings.php`              | **Tidak berubah** — dipakai apa adanya sebagai fallback kontak                                |

## 7.4 Diubah — frontend

| File                                                     | Perubahan                                                    |
| -------------------------------------------------------- | ------------------------------------------------------------ |
| `resources/js/features/public/pelayanan/pages/index.tsx` | Placeholder → indeks dinamis                                 |
| `resources/js/features/public/pelayanan/pages/show.tsx`  | Placeholder → detail dinamis                                 |
| `resources/js/layouts/app/components/app-sidebar.tsx`    | `Pelayanan` + href; anak `Kategori Pelayanan` di Master Data |
| `resources/js/app.tsx`                                   | **Tidak berubah** (resolver otomatis dari nama komponen)     |

## 7.5 Diubah — test

| File                                                   | Perubahan                                                                               |
| ------------------------------------------------------ | --------------------------------------------------------------------------------------- |
| `tests/Feature/Public/PublicPlaceholderRoutesTest.php` | Hapus baris `pelayanan` (pindah ke `tests/Feature/Pelayanan/PublicServicePageTest.php`) |
| `tests/Feature/Foundation/RbacFoundationTest.php`      | Tambah 8 permission ke `$expected`                                                      |
| `tests/js/components/app-sidebar.test.tsx`             | Tambah `Kategori Pelayanan`; ubah asersi `Pelayanan` menjadi link                       |
| `tests/e2e/master-data.spec.ts`                        | Perbarui daftar sub-menu Master Data                                                    |
| `tests/e2e/pelayanan.spec.ts`                          | **Baru**                                                                                |

## 7.6 Dihapus

Tidak ada file dihapus. Dua halaman placeholder pelayanan **diisi ulang**, nama
komponen Inertia dan nama route dipertahankan agar navbar/footer/`PublicRoutesTest`
tetap valid.

---

# 8. Dependency / Configuration Impact

## 8.1 Dependency baru: **nol**

Tidak ada composer/npm baru. Sanitizer, Tiptap, TanStack, shadcn, Lucide semuanya
sudah ada. FAQ memakai `<details>`/`collapsible` — **jangan** menambah
`@radix-ui/react-accordion` (m-6).

## 8.2 Konfigurasi

| Konfigurasi                             | Perubahan                                                        |
| --------------------------------------- | ---------------------------------------------------------------- |
| `database/seeders/PermissionSeeder.php` | +8 permission; `expectedCount()` mengikuti `count(PERMISSIONS)`  |
| `config/service-icons.php`              | **Baru** — allowlist key ikon                                    |
| `config/html.php`                       | **Tidak berubah** — allowlist cukup untuk `introduction`         |
| `config/site-settings.php`              | **Tidak berubah** — fallback kontak memakai kunci yang sudah ada |
| `config/demo.php`                       | **Tidak berubah**                                                |

## 8.3 Wayfinder

Setiap perubahan route/controller mewajibkan `php artisan wayfinder:generate --with-form`
(Vite plugin juga regenerate saat dev/build). Aturan:

- Jangan buat `Route::redirect()` **tanpa nama** (Wayfinder gagal build; D-49/AGENTS).
- Jangan tinggalkan route lama bernama sama dengan yang baru (dua export → build gagal).
- `/admin/pelayanan` adalah halaman (bukan redirect), jadi tidak ada masalah nama.

## 8.4 Publikasi Kontak & Timezone

- Fallback kontak **hanya** melalui `SiteSettingsService` (jangan query tabel langsung).
- Zona waktu: tidak ada datetime media di 6B; `updated_at` dikirim ISO 8601 dan
  diformat di React dengan `lib/datetime.ts` (`Asia/Pontianak`, format Indonesia).

---

# 9. Risiko dan Mitigasi

| #    | Risiko                                                    | Dampak                                           | Mitigasi                                                                                      |
| ---- | --------------------------------------------------------- | ------------------------------------------------ | --------------------------------------------------------------------------------------------- | ---------- |
| R-1  | Konflik PRD enum vs master `service_categories`           | Dua sumber data kategori / kebingungan schema    | WS0 catat di DECISIONS; satu sumber FK; jangan edit PRD; uji FK restrict                      |
| R-2  | Nama komponen Inertia ≠ path file                         | Halaman tak bereaksi (200, komponen tak dikenal) | Ikuti `pageName()` (C-5); `ArchitectureTest` menangkap; jalankan setelah tiap controller baru |
| R-3  | Permission baru tanpa update `RbacFoundationTest`         | Suite merah                                      | C-3: perbarui `$expected` + `PermissionSeeder` bersamaan                                      |
| R-4  | Slug unik + soft delete salah arah (`active_marker`)      | Soft-deleted menahan/melepas slot salah          | m-2: pola D-45; `Rule::unique` tanpa `withoutTrashed()`; uji make/delete/re-make slug         |
| R-5  | Kategori terhapus saat masih dipakai                      | Layanan yatim                                    | FK restrict + `destroy` mengecek `services()->withTrashed()->exists()` + pesan                |
| R-6  | `is_form_open`/`form_url`/emergency kontradiktif          | CTA salah tampil                                 | Normalisasi backend (WS4) + uji 4 kondisi CTA                                                 |
| R-7  | Emergency aktif tanpa `contact_phone`                     | Pelanggaran SVC-06/AC-SVC-9                      | Validasi FormRequest + uji penolakan                                                          |
| R-8  | N+1 relasi kategori/anak                                  | Eloquent strict mode melempar                    | `with()`/`withCount()`; jangan `select()` setelah `withCount()` (D-41)                        |
| R-9  | HTML `introduction` tak tersanitasi                       | XSS                                              | Sanitasi di `prepareForValidation()`; render hanya via `RichText`                             |
| R-10 | `$touches` lupa/tak jalan                                 | "Terakhir diperbarui" tidak berubah              | Uji SVC-03 (ubah anak → `updated_at` parent berubah)                                          |
| R-11 | Sidebar berubah merusak test JS/E2E                       | Suite merah                                      | C-6: update test bersamaan; `npm run build` sebelum E2E                                       |
| R-12 | Mixing server-sort & client state TanStack                | Data salah urut                                  | `manualSorting: true`; header click = visit; ikuti pola `misa-rutin`                          |
| R-13 | `npm run check:fix` pernah menghapus komentar/`<Heading>` | Kehilangan kode tanpa sadar                      | Jalankan, baca `git diff` sebelum menyimpan (AGENTS.md)                                       |
| R-14 | Migrasi tidak reversible                                  | `migrate:fresh` gagal                            | Uji `migrate:fresh` + `migrate:rollback` di DB test                                           |
| R-15 | `npx playwright test` bare setelah Pest                   | E2E gagal di `signIn`                            | Selalu `npm run e2e`; cek `ss -ltnp                                                           | grep 8000` |

---

# 10. Verification Plan

## 10.1 Gate per workstream

```bash
# WS1
php artisan migrate:fresh
php artisan test --compact tests/Feature/Pelayanan/ServiceSchemaTest.php
php artisan migrate:rollback && php artisan migrate

# WS2–WS4 (setiap workstream)
./vendor/bin/pint --dirty --format agent
php artisan test --compact tests/Feature/Pelayanan
php artisan wayfinder:generate --with-form
npm run types:check

# WS5–WS7
npm run check
npm run types:check
php artisan test --compact tests/Feature/Pelayanan tests/Feature/Public
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

- Viewport 360, 390, 768, 1024, 1440 px (6B-207/6B-246).
- Print preview halaman detail: syarat & langkah utuh, nav/CTA hilang (6B-242…245).
- Keyboard-only: tab form, accordion FAQ, tombol reorder (6B-201/6B-250).
- Cek `php artisan route:list --path=pelayanan` dan `--path=master-data` untuk nama
  route unik & tidak tabrakan.
- Cek tidak ada link "kategori" publik yang mengarah ke route tak ada.

---

# 11. Test Matrix

## 11.1 Feature — skema & otorisasi

- `ServiceSchemaTest` — kolom/FK/index lima tabel; `down()` reversible; FK restrict/cascade.
- `ServiceAuthorizationTest` — guest redirect ke login; non-super-admin 403;
  `super_admin` lolos; `FormRequest::authorize()` menolak `update`.

## 11.2 Feature — CRUD & kategori

- `ServiceTest` — CRUD; slug unik; nonaktif default; aktivasi eksplisit; soft delete;
  404 publik untuk nonaktif/soft-deleted (6B-267/279/280).
- `ServiceCategoryTest` — CRUD; slug unik; kategori nonaktif tidak dapat dipakai
  layanan baru/aktivasi; kategori terpakai tidak bisa dihapus (6B-268, AC-SVC-13).

## 11.3 Feature — anak & ordering

- `ServiceRequirementTest`, `ServiceStepTest`, `ServiceFaqTest` — CRUD + urutan;
  nomor dari posisi; anak terikat parent (6B-274/275/276).
- `ServiceReorderTest` — transaksional; `sort_order` berurutan; id milik service lain ditolak.

## 11.4 Feature — aturan 6B

- `GoogleFormUrlTest` — valid (`docs.google.com/forms/…`, `forms.gle/…`);
  invalid (`http://`, host lain, `docs.google.com.evil.com`, path bukan `/forms/`,
  kredensial, `evil.com/?next=…`) (6B-271/272, AC-SVC-4).
- `ServiceFormOptionalTest` — `null`/kosong diterima; `is_form_open` tanpa URL → CTA nonaktif
  (6B-270/273, AC-SVC-5).
- `EmergencyServiceTest` — tidak aktif tanpa `contact_phone`; tidak memakai `form_url`
  publik; CTA kontak (6B-277/278, AC-SVC-9).
- `ServiceContactFallbackTest` — kontak khusus → `site_settings` → pesan sopan; tidak ada
  route kontak baru (6B-269/283, AC-SVC-14).
- `ServiceUpdatedAtTest` — `updated_at` berubah saat anak berubah (6B-282, SVC-03).
- `ServiceSanitizationTest` — HTML berbahaya dibersihkan (6B-284).
- `ServiceSeederTest` — 6 layanan, nonaktif, tanpa `form_url`; idempoten (6B-265/266/287).

## 11.5 Feature — publik

- `PublicServicePageTest` — indeks hanya layanan aktif; detail 200 + komponen;
  nonaktif/soft-deleted 404; pengelompokan kategori; empty state (6B-208…228).

## 11.6 Frontend (Vitest)

- `app-sidebar.test.tsx` — `Pelayanan` link langsung ke `/admin/pelayanan`;
  `Kategori Pelayanan` di bawah Master Data; disclosure Master Data tetap (6B-288/290).
- `service-cta` — empat kondisi CTA (terbuka/tertutup/tanpa form/emergency) (6B-292).

## 11.7 E2E

- `tests/e2e/pelayanan.spec.ts` — super admin buka Daftar dari sidebar; edit informasi umum;
  kelola persyaratan/prosedur/FAQ + urutan; simpan tanpa form URL; masukkan URL valid &
  uji tautan; pengunjung hanya melihat layanan aktif; emergency tanpa CTA pendaftaran
  (6B-297…307).

## 11.8 Regresi

- `tests/Feature/MassSchedule`, `tests/Feature/News`, `tests/Feature/Agenda`,
  `tests/Feature/Profil` tetap hijau (6B-308…311).

---

# 12. Acceptance Criteria

## 12.1 PRD §7.6 (AC-SVC-1 … AC-SVC-9)

- **AC-SVC-1** keenam layanan punya struktur data (konten menunggu validasi paroki) → WS1 seeder nonaktif.
- **AC-SVC-2** tombol form tampil hanya saat URL valid & `is_form_open` → WS6 + `ServiceCta` test.
- **AC-SVC-3** ubah URL & buka/tutup tanpa deploy; URL opsional → WS2/WS4.
- **AC-SVC-4** URL non-allowlist ditolak termasuk bypass → `GoogleFormUrlTest`.
- **AC-SVC-5** saat ditutup tombol inert + pesan → WS6 + test.
- **AC-SVC-6** dokumen terkait → **deferred ke Phase 8** (D-4), dicatat dependency.
- **AC-SVC-7** tombol `tel:`/`wa.me` benar & tervalidasi → `PhoneNumber` + test.
- **AC-SVC-8** halaman detail bisa dicetak rapi → WS7.
- **AC-SVC-9** emergency tidak aktif tanpa telepon → `EmergencyServiceTest`.

## 12.2 Roadmap §15 (AC-SVC-10 … AC-SVC-14)

- **AC-SVC-10** kelola persyaratan individual → WS3.
- **AC-SVC-11** Pelayanan link langsung; Kategori di Master Data; kontak global di
  Pengaturan Situs → WS5 + test sidebar.
- **AC-SVC-12** tidak menyimpan data pendaftar → tidak ada endpoint submission.
- **AC-SVC-13** kategori konsisten; kategori terpakai tidak terhapus → `ServiceCategoryTest`.
- **AC-SVC-14** `site_settings` fallback tunggal → `ServiceContactFallbackTest`.

## 12.3 Roadmap §16 (Definition of Done)

- Tabel/model/relasi tersedia tanpa duplikasi schema.
- Enam layanan di-seed idempoten & nonaktif.
- Admin dapat mengelola data umum, persyaratan, prosedur, jadwal/biaya, kontak, opsi
  Google Form, FAQ, dan urutan.
- Sidebar: `Pelayanan` link langsung; `Kategori Pelayanan` di Master Data.
- Halaman publik indeks/detail dinamis.
- Empat kondisi CTA ditangani.
- Emergency tidak aktif tanpa nomor kontak & tanpa form CTA.
- Nonaktif/soft-deleted → 404.
- Persyaratan/prosedur/FAQ/last-updated berfungsi.
- Validasi HTML/URL/nomor & otorisasi di backend.
- Tidak ada penyimpanan data pendaftar.
- Integrasi Download memakai Phase 8 (bukan modul duplikat).
- Test, E2E, TypeScript, lint/format, build, CI lulus.
- Tidak ada regresi Phase 4/5/6A.
- Responsif, aksesibilitas dasar, mode cetak terverifikasi.

**Content/Go-live Ready** membutuhkan validasi paroki (syarat/prosedur, biaya, jadwal, nomor
darurat, URL Google Form). **Jangan** mengklaim ini selesai.

---

# 13. Yang Sengaja Tidak Dikerjakan

- **Form pendaftaran native / penyimpanan data pendaftar** (PRD §12, SVC-04).
- **Sinkronisasi Google Form/Sheets, notifikasi, otomasi Workspace** (PRD §12).
- **Pembayaran/persembahan online** (PRD §12).
- **Modul Download / pivot `document_service`** — Phase 8 (D-4).
- **CRUD Komunitas/Stasi/Lingkungan** — Phase 7.
- **Halaman/route/tabel Kontak kedua** — kontak global hanya `site_settings`.
- **UI Role/Permission management & role tambahan** (PRD §12).
- **Media library page / gambar layanan** — tidak ada kolom gambar di PRD §9.8.
- **Cache jadwal/data** (bukan AC 6B).
- **Dokumentasi `docs/ADMIN_GUIDE.md`** — ditunda (D-6).
- **Konten Lampiran A sebagai konten resmi** (D-5).

---

# 14. Keputusan Minor yang Diasumsikan

Berlaku sesuai rekomendasi; dapat dibatalkan tanpa invalidasi plan.

| #    | Keputusan                   | Nilai & alasan                                                                                                     |
| ---- | --------------------------- | ------------------------------------------------------------------------------------------------------------------ |
| m-1  | Slug kategori `orang sakit` | `orang-sakit` (roadmap §6.1). Divergensi dari enum PRD dicatat; kategori adalah data, bukan enum kode.             |
| m-2  | Slug & soft delete          | Tetap unik saat soft delete (D-45); tanpa `active_marker`; `Rule::unique` tanpa `withoutTrashed()`.                |
| m-3  | Soft delete                 | Hanya `services` + `service_categories`; anak hard delete cascade.                                                 |
| m-4  | Last updated                | `$touches = ['service']` pada tiga model anak.                                                                     |
| m-5  | Reorder                     | Tombol naik/turun + `ReorderServiceItemsAction` transaksional. Tanpa dependency drag-and-drop.                     |
| m-6  | FAQ accordion               | `<details>`/`<summary>` atau `collapsible`; tidak menambah dependency.                                             |
| m-7  | Tab state                   | `?tab=` query string; tiap tab endpoint sendiri; tidak ada state lintas-tab yang hilang.                           |
| m-8  | Nama route                  | `services.*`, `service-requirements.*`, `service-steps.*`, `service-faqs.*`, `service-categories.*`.               |
| m-9  | Nama halaman                | `admin/pelayanan`, `admin/pelayanan/{tambah,ubah}`, `admin/master-data/kategori-pelayanan/{index,tambah,ubah}`.    |
| m-10 | Fallback kontak             | Kontak khusus → `site_settings` → pesan sopan.                                                                     |
| m-11 | Ikon tak dikenal            | Fallback ikon netral; backend menolak key di luar allowlist.                                                       |
| m-12 | Print                       | `print:` utility + blok `@media print` hanya bila perlu.                                                           |
| m-13 | Route kategori ordering     | Static `/admin/master-data/kategori-pelayanan/...` tidak bertabrakan dengan `{service}` di `/admin/pelayanan/...`. |
| m-14 | `form_url` di UI edit       | Tombol "Buka Tautan" muncul hanya saat URL lolos `GoogleFormUrl`; nonaktif saat tidak valid.                       |

---

# 15. Status

| Item               | Nilai                                                                                         |
| ------------------ | --------------------------------------------------------------------------------------------- |
| Dokumen roadmap    | `docs/roadmap/phase-6B-parish-service.md` ✅ ada                                               |
| Audit codebase     | ✅ selesai (commit `6b98aed`)                                                                  |
| Keputusan pengguna | ✅ 9 pertanyaan terjawab + m-1…m-14                                                            |
| Kode implementasi  | ✅ **selesai** di branch `feat/phase-06b-parish-service`                                       |
| Verifikasi         | ✅ `composer test` (1101 Pest) + `composer ci:check` (268 Vitest) + `npm run build` + `npm run e2e` (72 Playwright) hijau |
| Keputusan menunggu | —                                                                                             |

## 15.1 Ringkasan lingkup

- **5 tabel** (3 induk + 3 anak, sesuai §4) / 1 migrasi; 5 model; 5 factory;
  2 seeder (3 kategori + 6 layanan, idempoten, nonaktif).
- Test: 97 Pelayanan Pest (bagian dari 1101), 268 Vitest, 72 Playwright.

## 15.2 Data contoh (demo-only)

Ditambahkan atas permintaan pemilik proyek di `DemoContentSeeder::seedServices()`
(dijaga `config('demo.enabled')`, perpanjangan D-53): mengisi deskripsi + ikon
ketiga kategori, keenam layanan (aktif hanya di demo), dan ketiga tabel anak
(persyaratan/prosedur/FAQ) dengan konten turunan PRD Lampiran A. Keempat kondisi
CTA PRD 7.6 diwakili. `ServiceSeeder` tetap struktural dan `ServiceSeederTest`
tidak berubah.
- **+8 permission** (61 → 69); 2 Policy.
- Admin `/admin/pelayanan/*` (Daftar, Tambah/Edit bertab, anak CRUD + urutan) dan
  `/admin/master-data/kategori-pelayanan/*`.
- 2 halaman publik dinamis + 4 kondisi CTA + fallback kontak + FAQ + last-updated + print.
- Sidebar: `Pelayanan` link langsung + anak `Kategori Pelayanan` di Master Data.
- `document_service` ditunda ke Phase 8; `docs/ADMIN_GUIDE.md` ditunda (D-6).

> Sesuai roadmap §17, implementasi dikerjakan per checkpoint WS0→WS9 dengan
> verifikasi di setiap workstream. WS0–WS4 adalah checkpoint pertama. Jangan
> menandai phase selesai hanya karena file sudah dibuat; status selesai
> didasarkan pada acceptance criteria, hasil test, dan build yang benar-benar
> diverifikasi.

@see `docs/roadmap/phase-6B-parish-service.md`, `docs/DECISIONS.md`, `docs/RBAC.md`
