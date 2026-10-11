# Implementation Plan — PHASE 05

> **Project:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau\
> **Phase:** 05 — News & Agenda (Berita & Artikel, Agenda Kegiatan)\
> **Status:** Disusun — menunggu persetujuan implementasi\
> **Branch:** `feat/phase-05-news-agenda`\
> **Spesifikasi:** `docs/roadmap/phase-05-news-&-agenda.md`\
> **Sumber kebenaran:** `PRD.md` → `ARCHITECTURE.md` → `DESIGN.md` → `AGENTS.md` → `DECISIONS.md`

Dokumen roadmap Phase 05 menjelaskan _apa_ yang harus ada. Dokumen ini menjelaskan
_bagaimana_ perubahannya, dalam urutan apa, file mana yang tersentuh, dan
bagaimana diverifikasinya.

> Dokumen ini disusun setelah audit codebase terhadap `main` pada commit `f40d25b`
> dan **6 pertanyaan keputusan yang sudah dijawab pengguna**. Seluruh keputusan di
> §1 mengikat implementasi. Belum ada satu baris kode implementasi yang ditulis.

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

Enam pertanyaan diajukan sebelum penyusunan plan ini. Jawaban berikut mengikat
seluruh isi dokumen. Semua pilihan adalah opsi yang direkomendasikan.

| #   | Pertanyaan                                    | Keputusan                                                                                                                 | Alasan yang dipakai                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                |
| --- | --------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| D-1 | Letak menu **Lokasi** di sidebar admin        | **Pindah ke group baru "Master Data"**, bersama Kategori Berita dan Kategori Agenda. Sub-menu Jadwal Misa jadi **3 anak** | Roadmap §9.3 menampilkan pohon yang memuat Lokasi di Master Data, dan §4.1 eksplisit: "Jangan menduplikasi menu Lokasi di beberapa group. **Pindahkan**/daftarkan item Lokasi yang sudah ada ke group Master Data dan pertahankan route serta perilaku existing-nya." Konsekuensinya `AGENTS.md` dan dua test harus diperbarui (§6.5). Route `locations.index` dan URL `/admin/jadwal-misa/lokasi` **tidak berubah**.                                                                                              |
| D-2 | Konflik `community_id` antara PRD dan roadmap | **Catat di `docs/DECISIONS.md` saja**; PRD tidak diedit                                                                   | Roadmap §5 menutup dengan: "Jika `PRD.md` versi lama masih menyebut `community_id`, keputusan khusus Phase 05 ini yang digunakan untuk implementasi fitur tersebut dan **konflik dokumentasi harus dilaporkan untuk pembaruan PRD secara terpisah**." Mengedit Source of Truth di tengah fase Risks memicu review ulang D-03 yang justru dibutuhkan Phase 07.                                                                                                                                                      |
| D-3 | Unique `slug` pada tabel soft-delete          | **`Rule::unique()` biasa, tanpa `->withoutTrashed()`**                                                                    | `posts` dan `events` punya route model binding publik berbasis slug. Jika slug boleh dipakai ulang, dua baris (satu aktif, satu terhapus) memegang slug yang sama dan `where('slug', …)->first()` menjadi ambigu — bisa mengembalikan yang terhapus. `locations.slug` boleh memakai `withoutTrashed()` justru karena **tidak ada** binding publik berbasis slug.                                                                                                                                                   |
| D-4 | Lokasi `RichTextEditor` Tiptap                | **Pindahkan ke `resources/js/components/rich-text-editor.tsx`**                                                           | ARCHITECTURE.md Part B §1.1: satu konsumen → feature, ≥2 konsumen → `components/`. Setelah Berita memakainya ada dua konsumen. Wrapper yang re-export dari feature akan membuat `components/` bergantung pada `features/` — persis yang `ArchitectureTest` larang. Duplikasi berarti dua editor Tiptap yang pasti berbeda.                                                                                                                                                                                         |
| D-5 | Structured data `Event` (EVT-08) di mana      | **JSON-LD di `<Head>` halaman detail React**                                                                              | SSR mati (D-26), jadi `<Head>` tidak pernah sampai ke crawler — masalah yang sama dengan OG tag. Tapi JSON-LD bersifat per-agenda dan tidak bisa menjadi default statis di Blade. Opsi B butuh mekanisme Blade baru di `app.blade.php` + `HandleInertiaRequests`, yaitu bagian yang D-26 bilangkan "biarkan ke fase website publik". Opsi A tetap punya batasan yang sama dan dicatat sebagai divergensi.                                                                                                          |
| D-6 | Mekanisme unggah gambar utama / poster        | **Pakai tabel `media` + kolom FK nullable `media_id`**                                                                    | Roadmap §7.6/§8.6 meminta varian + WebP "sesuai mekanisme media proyek". `MediaController::store()` mengembalikan `back()`, sehingga form tidak pernah tahu ID media yang baru diunggah — picker harus dibangun. `D-24` sudah menyisakan tempat: _"the browsing UI arrives with the first module that needs it"_. Berita adalah modul itu. `ImageProcessor` + `GenerateImageVariants` + retry XC-M1 dipakai apa adanya. Kolom `media_id` adalah **divergensi dari roadmap §5.2/§5.4** dan dicatat di DECISIONS.md. |

## 1.1 Pilihan minor yang diasumsikan

Diasumsikan sesuai rekomendasi; dapat dibatalkan kapan saja tanpa_invalidasi
seluruh plan.

| #   | Pilihan minor                         | Nilai                                                                                              | Alasan                                                                                                                                                                                                                                                               |
| --- | ------------------------------------- | -------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| m-1 | Penempatan schedule artikel terjadwal | `bootstrap/app.php::withSchedule()`, **bukan** `routes/console.php`                                | Roadmap §12.1 menyebut `routes/console.php`, tetapi `AGENTS.md` menyatakan schedule ada di `withSchedule()` dan itulah yang benar-benar dipakai (`queue:prune-failed` sudah di sana). Docblock `routes/console.php` diperbarui karena alasannya sudah tidak berlaku. |
| m-2 | Granularitas permission               | 12 permission `resource.action` **satu kata**: `postcategories.*`, `events.*`, `eventcategories.*` | `RbacFoundationTest.php:103` memakai regex `/^[a-z]+\.[a-z]+$/`. `post-categories.view` akan gagal (D-36).                                                                                                                                                           |
| m-3 | Nama file halaman                     | `tambah.tsx` / `ubah.tsx`                                                                          | Mengikuti URL dan Phase 04.                                                                                                                                                                                                                                          |
| m-4 | Nama route                            | `posts.*`, `post-categories.*`, `events.*`, `event-categories.*`                                   | Mengikuti `mass-schedules.*` dan `locations.*`. Route publik `berita.*`, `agenda.*`, `agenda.ics` **dipertahankan** agar navbar, footer, dan `PublicRoutesTest` tidak putus.                                                                                         |
| m-5 | Duplikasi agenda                      | `POST /admin/agenda/{event}/duplicate`                                                             | Roadmap §9: "Hindari endpoint mutasi melalui GET." Mengikuti `DuplicateMassScheduleAction`.                                                                                                                                                                          |
| m-6 | Data contoh                           | **Tidak ada seeder konten.** Hanya seeder kategori.                                                | Roadmap §4.2 melarang "mengarang identitas, alamat, jadwal, atau konten nyata paroki untuk seed produksi". D-34 menetapkan prinsip yang sama untuk Phase 04.                                                                                                         |
| m-7 | Nama model agenda                     | `App\Models\Event` (nama tabel default `events` benar)                                             | Roadmap §6.1 menyebut `Event`. Ber.tabrakan dengan facade `Illuminate\Support\Facades\Event`; file yang butuh facade meng-`alias()`-nya. Dicatat di DECISIONS.md.                                                                                                    |

Nomor D-1 … D-6 di atas adalah **nomor keputusan lokal dokumen ini**, bukan nomor
`D-xx` di `docs/DECISIONS.md`. Penomoran resmi untuk Phase 05 dimulai dari
**D-43**, karena D-01 … D-42 sudah terpakai.

---

# 2. Kondisi Codebase Saat Ini

Audit dilakukan terhadap `main` pada commit `f40d25b`
(`Merge pull request #4 from Rumahkodingku/feat/phase-04-mass-schedule`).

## 2.1 Fondasi yang sudah ada dan cukup — **diaudit, bukan ditulis ulang**

| Area                | Status aktual                                                                                                                                                 | File                                                                     |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------ |
| Timezone            | `app.timezone = 'UTC'` + `app.display_timezone = 'Asia/Pontianak'`, dikirim sebagai shared prop                                                               | `config/app.php`, `HandleInertiaRequests.php:83`                         |
| Carbon              | `Date::use(CarbonImmutable::class)`                                                                                                                           | `AppServiceProvider.php:120`                                             |
| Strict mode         | `Model::shouldBeStrict(! isProduction())` — lazy load / discarded / missing = exception                                                                       | `AppServiceProvider.php:159`                                             |
| Gate `/admin`       | `['auth', 'verified', EnsureAccountIsActive, EnsureAdminAccess]` + `Gate::before` (`null`, bukan `false`)                                                     | `routes/web.php:144`, `AppServiceProvider.php:110`                       |
| Sanitasi HTML       | `HtmlSanitizer` (singleton) backed `symfony/html-sanitizer`; allowlist 22 elemen di `config/html.php`; ada `toPlainText()`                                    | `app/Services/HtmlSanitizer.php`                                         |
| Media pipeline      | `MediaController` + `StoreMediaRequest` + `IsProcessableImage` + `ImageProcessor` (GD, EXIF-orient, WebP) + `Job\GenerateImageVariants`                       | `app/{Http,Rules,Services,Jobs}/`                                        |
| Batas upload        | `max_upload_kilobytes = 3072` (3 MB), MIME `jpeg`/`png`, varian `thumb 400 / medium 960 / large 1600`, `webp_quality 82`                                      | `config/media.php`                                                       |
| Permission          | **31 permission** ter-seed, termasuk `posts.{view,create,update,delete}`. `events.*`, `postcategories.*`, `eventcategories.*` **belum ada**                   | `database/seeders/PermissionSeeder.php:41`                               |
| Gate super admin    | `Gate::before` mengulang **semua** ability, hanya berlaku untuk cek Gate-level — jadi otorisasi wajib pakai `can()`, bukan `hasPermissionTo()`                | `AppServiceProvider.php:110`                                             |
| Sorting list admin  | `SortsAdminList::applyAdminSort()` — whitelist, tie-breaker `id` terakhir, default lewat argumen                                                              | `app/Concerns/SortsAdminList.php`                                        |
| Label ke React      | `MassScheduleFormMeta` mengirim `lang/id/mass-schedule.php` ke React                                                                                          | `app/Concerns/MassScheduleFormMeta.php`                                  |
| Rule bersama        | `ValidatesActiveLocationReference` (aktif), `LocationValidationRules`, `MassScheduleValidationRules`                                                          | `app/Concerns/`                                                          |
| Konversi WIB→UTC    | `StoreSpecialMassRequest::toUtc()` di `prepareForValidation()`                                                                                                | `app/Http/Requests/MassSchedule/StoreSpecialMassRequest.php:93`          |
| Duplikasi           | `DuplicateMassScheduleAction` — `replicate(['active_marker','deleted_at'])` + transaksi                                                                       | `app/Actions/MassSchedule/DuplicateMassScheduleAction.php`               |
| Sanitasi di request | `UpdateSettingsRequest::prepareForValidation()` memanggil `HtmlSanitizer::clean()`                                                                            | `app/Http/Requests/Settings/UpdateSettingsRequest.php:108`               |
| Layout resolution   | `app.tsx` memilih layout dari nama komponen; halaman hanya mengirim `Component.layout = { breadcrumbs }`                                                      | `resources/js/app.tsx`                                                   |
| SEO                 | `components/seo.tsx` sudah mendukung `ogType="article"`, `canonical`, `ogImage`, `noIndex`; shared prop `seo` sudah dikirim                                   | `resources/js/components/seo.tsx`                                        |
| OG di respons awal  | Tags OG + canonical sudah di-render di Blade dengan `data-inertia`,joined head-key yang sama                                                                  | `resources/views/app.blade.php:97-147`                                   |
| Waktu di frontend   | `formatWibDate`, `formatWibDateTime`, `toIsoWithOffset`, `toDateTimeLocalValue` — semua dengan `timeZone` eksplisit                                           | `resources/js/lib/datetime.ts`                                           |
| Tiptap              | `@tiptap/react` + `@tiptap/starter-kit`, heading level 2–4, `code/codeBlock/strike/horizontalRule` dimatikan                                                  | `resources/js/features/admin/pengaturan/components/rich-text-editor.tsx` |
| Primitif UI         | 32 komponen shadcn termasuk `alert, badge, card, dialog, select, sidebar, table, textarea, tabs, toggle-group, switch, pagination`                            | `resources/js/components/ui/`                                            |
| Testing             | Pest 4.7 (`tests/Pest.php` punya `seedRolesAndPermissions()`, `superAdmin()`, `truecolor()`), Vitest via `vite-plus` (`tests/js/`), Playwright (`tests/e2e/`) | `tests/`                                                                 |

## 2.2 Yang sudah ada tapi harus diganti — pekerjaan utama Phase 05

| Area                           | Kekurangan yang ditemukan                                                                                                                                                                                               |
| ------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Route publik berita/agenda     | `routes/web.php:67-75` masih **empat closure `Route::inertia`/arrow-fn** yang tidak query apa pun. `/berita/{slug}` menerima slug apa pun yang cocok `[a-z0-9-]+` dan selalu 200 — **pelanggaran XC-E3 yang tercatat**  |
| Endpoint `.ics`                | `routes/web.php:94-105` mengembalikan `VCALENDAR` valid tanpa `VEVENT` dan badan `[ISI: …]`                                                                                                                             |
| Halaman publik berita & agenda | `features/public/berita/pages/{index,show}.tsx` dan `features/public/agenda/pages/{index,show}.tsx` sudah ada tetapi **9 baris JSX** berisi teks `[ISI: …]`                                                             |
| Sidebar admin                  | `app-sidebar.tsx` — entry `{ title: 'Berita', icon: Newspaper }` dan `{ title: 'Agenda', icon: CalendarDays }` **tanpa `href`**, jadi render sebagai baris disabled dengan penanda "belum". Tidak ada group Master Data |
| Komponen `RichText`            | `components/rich-text.tsx` adalah satu-satunya `dangerouslySetInnerHTML` di repo, dan **0 konsumen**                                                                                                                    |
| Komponen `RichTextEditor`      | Hanya 1 konsumen (halaman pengaturan) — melanggar ambang `components/`                                                                                                                                                  |
| List prop non-paginate         | Belum pernah dipakai di modul publik; D-39 (`array_values(...resolve())`) baru akan relevan                                                                                                                             |
| Permission                     | 12 permission modul ini belum ada                                                                                                                                                                                       |
| `RbacFoundationTest`           | `tests/Feature/Foundation/RbacFoundationTest.php:62-92` punya array `$expected` **hardcoded** 31 nama dengan `toBe()` — ditambah 12 permission, test ini **wajib** diperbarui atau merah                                |

## 2.3 Yang belum ada dan harus dibuat

**4 tabel** · **2 enum** · **4 model** · **4 factory** · **4 policy** ·
**8 form request** · **4 concern** · **1 service** (`IcsCalendarBuilder`) ·
**3 action** · **1 command** · **12 `JsonResource`** · **8 controller** ·
**26 route** · **13 halaman Inertia admin** · **2 halaman Inertia publik (di-rewrite)** ·
**~14 komponen** · **2 file `lang/id`** · **12 file test** · **3 helper di
`lib/datetime.ts`** · **1 perubahan pada `MediaController`**

## 2.4 Konvensi yang harus diikuti — bukan landmark baru

| Konvensi                 | Bentuk persisnya di repo                                                                                               | Konsekuensi untuk Phase 05                                                                         |
| ------------------------ | ---------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------- |
| Fillable                 | Atribut **`#[Fillable(['key', …])]`**, bukan properti — `ArchitectureTest.php:119` mengecek atributnya                 | Empat model baru wajib pakai atribut                                                               |
| Cast                     | Method `protected function casts(): array`, bukan properti                                                             | `published_at` / `start_at` / `end_at` → `'immutable_datetime'` (mengikuti `SpecialMass`)          |
| Resource                 | `#[Fillable]` + trait `ResolvesNestedResources` untuk relasi nested                                                    | Ikut dipakai untuk `category`, `author`, `location`                                                |
| List prop                | **`array_values(Resource::collection(…)->resolve())`** untuk list non-paginate (D-39)                                  | Berlaku untuk kategori, agenda, agenda mendatang, related posts, pilihan lokasi                    |
| Otorisasi                | `FormRequest::authorize()` untuk `store`/`update`; `Gate::authorize()` untuk `index`/`show`/`edit`/`destroy`/aksi lain | Tidak pernah di dua tempat                                                                         |
| Pencarian URL            | Bahasa Indonesia: `?lokasi=`, `?hari=` di halaman publik Jadwal Misa                                                   | Berita/agenda publik memakai `?kategori=`, `?q=`, `?bulan=`, `?mode=`                              |
| Filter di luar whitelist | Diabaikan **senyap**, bukan 422                                                                                        | `applyAdminSort` dan filter publik memakai pola sama                                               |
| Pesan                    | Flash `->with('success', …)` di modul Jadwal Misa                                                                      | Diikuti konsisten                                                                                  |
| Label                    | `lang/id/<modul>.php` dikirim lewat concern `*FormMeta`                                                                | `lang/id/news.php`, `lang/id/agenda.php`                                                           |
| Validasi                 | `Rule::enum()` untuk enum, `Rule::unique()->ignore()` pada update, `after_or_equal` untuk rentang                      | `PostStatus`, `EventStatus`                                                                        |
| Route                    | `Route::get/post/patch/delete` eksplisit + `->name()` dalam file route per modul                                       | `routes/news.php`, `routes/agenda.php`                                                             |
| Halaman                  | Tipis; `Index.layout = { breadcrumbs: [...] }`; form & tabel di `components/`                                          | Mengikuti `pages/lokasi/index.tsx`                                                                 |
| Form                     | `<form noValidate>` — **wajib**                                                                                        | Tanpa ini browser memvalidasi dulu, submit tidak pernah jalan, pesan Indonesia tidak pernah muncul |
| Tab URL                  | `router.get(url, params, { preserveState, preserveScroll, replace })`                                                  | Filter dan pencarian                                                                               |
| Select                   | `ChoiceSelect` (Radix, sentinel `'__empty__'`) untuk form; `<select>` native untuk filter                              | Mengikuti                                                                                          |
| Copy                     | Bahasa Indonesia                                                                                                       | Semua label, pesan flash, dan `messages()`                                                         |

## 2.5 Yang **tidak boleh** diasumsikan ada

Tiga hal yang gelāt Saja dalam dokumen tapi tidak ada di repository:

1. **Media picker.** `D-24` menyatakan "tidak ada halaman pustaka media maupun
   image picker di mana pun". Roadmap Phase 05 tidak menyebut membangunnya; ia
   hanya menyebut mengunggah. Konstruksi picker adalah pekerjaan baru yang
   Ordered oleh keputusan D-6.
2. **Pustaka tanggal.** `package.json` tidak punya `date-fns`, `dayjs`, maupun
   `luxon`. Kalender bulanan harus dibangun dari `Intl.DateTimeFormat` +
   aritmetika string.
3. **Sitemap.** `rg sitemap` mengembalikan nihil di luar `docs/`. `NFR-SEO`
   mewajibkannya, tetapi roadmap §15 tidak meletakkannya sebagai AC Phase 05.

---

# 3. Kontrak yang Harus Dijaga

Enam kontrak ini sudah diuji oleh test yang ada. Melanggar salah satunya tidak
"merusak fitur" — ia **merusak test yang selama ini hijau**, dan itu memang
regresi.

## C-1 — `PublicPlaceholderRoutesTest` harus dihapus barisnya, bukan diperbaiki

`tests/Feature/Public/PublicPlaceholderRoutesTest.php:41-97` mengunci lima
placeholder dan menyatakan sendiri:

> "When a module lands, its entry here becomes a route with model binding, at
> which point a missing slug 404s by itself and XC-E3 is satisfied for that
> module. **Removing the row is part of that work, not after it.**"

Empat test ikut hilang bersama dua baris itu:

| Test                                   | Alasan hilang                                                   |
| -------------------------------------- | --------------------------------------------------------------- |
| `answers 200 for any well-formed slug` | Bertentangan dengan POST-09 secara langsung                     |
| `passes the slug through to the page`  | `slug` tidak lagi jadi prop; route mengembalikan model          |
| `describe('the .ics endpoint')` × 4    | Digantikan test ICS nyata di `tests/Feature/Agenda/IcsTest.php` |
| baris `berita` + `agenda` di dataset   | Rute sudah punya controller dengan model binding                |

Daftar route di `it('names every placeholder route in the route table')`
**tetap** dan tetap hijau — nama route tidak berubah.

## C-2 — `PublicRoutesTest` harus mengosongkan `/berita` dan `/agenda` dari dataset `placeholder pages`

`tests/Feature/Public/PublicRoutesTest.php:71-89` menguji bahwa halaman placeholder
**tidak mengirim props**. Setelah Phase 05 keduanya mengirim props, jadi test itu
akan gagal. Berkasnya sendiri memberi tahu caranya:

> "When another module lands, move its URL out of here deliberately. That edit is
> the moment to notice the placeholder set shrinking."

Dataset `public pages` (`:30-53`) **tidak berubah** — nama komponen tetap
`public/berita` dan `public/agenda`.

## C-3 — `RbacFoundationTest` punya daftar permission hardcoded

`tests/Feature/Foundation/RbacFoundationTest.php:62-92` membandingkan
`Permission::pluck('name')` dengan array literal menggunakan `toBe()`. Menambah
12 permission **wajib** menambahkan 12 baris di sana, atau test merah. Hitungan
Jumlah di `:212` sudah aman karena memakai `expectedCount()`.

## C-4 — Enam aturan `ArchitectureTest` yang mengikat

| Aturan                                                             | Yang harus dilakukan                                                                                                                           |
| ------------------------------------------------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| `html is sanitized in the request layer`                           | `App\Http\Requests` harus meng-`use` `App\Services\HtmlSanitizer` — sudah terpenuhi, tetapi **pemakaian per-field** tetap wajib oleh AC-POST-6 |
| `services do not depend on the http layer`                         | `IcsCalendarBuilder` tidak boleh menerima `Illuminate\Http\Request`                                                                            |
| `services do not depend on controllers`                            | Idem                                                                                                                                           |
| `layouts do not import pages or features`                          | Sidebar import **Wayfinder route**, bukan feature — `import { index as … } from '@/routes/…'`                                                  |
| `a feature does not import another feature`                        | Admin berita/agenda **tidak boleh** saling import; shared type masuk `types/` atau `lib/`                                                      |
| `the inertia page finder and the blade lookup find the same files` | `config/inertia.php` `pages.paths` **wajib** ditambah 4 direktori baru                                                                         |

Plus dua aturan tambahan yang berlaku: `navigation never hard-codes an application URL`
(tidak ada `href="/admin/…"` di TSX) dan `no component reaches for a brand colour by
hex value` (tidak ada `[#AB020E]` — termasuk **kategori agenda yang menyimpan hex**,
yang harus dirender lewat `style` inline atau token, bukan utility arbitrary).

## C-5 — `config/inertia.php` harus sinkron dengan `PageChunk::all()`

Empat direktori baru wajib masuk `pages.paths`:

```
resources/js/features/admin/berita/pages
resources/js/features/admin/kategori-berita/pages
resources/js/features/admin/agenda/pages
resources/js/features/admin/kategori-agenda/pages
```

`features/public/berita/pages` dan `features/public/agenda/pages` **sudah ada**
di sana.

## C-6 — Nama komponen Inertia harus cocok dengan path file

Empat hal harus sepakat: `app.tsx` (`import.meta.glob`), `app/Support/PageFinder.php`,
`app/Support/PageChunk.php`, dan `resources/views/app.blade.php`. Aturan nama:

| Nama komponen                         | File                                                     |
| ------------------------------------- | -------------------------------------------------------- |
| `admin/berita`                        | `features/admin/berita/pages/index.tsx`                  |
| `admin/berita/tambah`                 | `features/admin/berita/pages/tambah.tsx`                 |
| `admin/berita/ubah`                   | `features/admin/berita/pages/ubah.tsx`                   |
| `admin/berita/preview`                | `features/admin/berita/pages/preview.tsx`                |
| `admin/kategori-berita`               | `features/admin/kategori-berita/pages/index.tsx`         |
| `admin/agenda`                        | `features/admin/agenda/pages/index.tsx`                  |
| `admin/agenda/tambah`                 | `features/admin/agenda/pages/tambah.tsx`                 |
| `admin/agenda/ubah`                   | `features/admin/agenda/pages/ubah.tsx`                   |
| `admin/kategori-agenda`               | `features/admin/kategori-agenda/pages/index.tsx`         |
| `public/berita`                       | `features/public/berita/pages/index.tsx` (**sudah ada**) |
| `public/berita/show`                  | `features/public/berita/pages/show.tsx` (**sudah ada**)  |
| `public/agenda`, `public/agenda/show` | `features/public/agenda/pages/*` (**sudah ada**)         |

Semua halaman `admin/*` jatuh ke `default` → `AppLayout`; semua `public/*` →
`PublicLayout`. **Tidak ada edit `app.tsx`.**

## C-7 — Test sidebar harus diperbarui (konsekuensi D-1)

| File                                               | Perubahan                                                                                                   |
| -------------------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| `tests/js/components/app-sidebar.test.tsx:27-32`   | `SUB_MENU` kehilangan `'Lokasi'` → 3 anak                                                                   |
| `tests/js/components/app-sidebar.test.tsx:131-141` | Test `does not link a module that has no route yet` berubah: **Berita kini punya link**, Galeri tetap belum |
| `tests/e2e/mass-schedule.spec.ts:255-263`          | Daftar sub-menu kehilangan `['Lokasi', …]`                                                                  |

---

# 4. Ringkasan Task

| Tahap | Isi                                                                                                                                      | Output                                                 |
| ----- | ---------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------ |
| **A** | 4 migration · 3 enum · 4 model · 4 factory · 2 seeder · 4 policy                                                                         | Skema + domain yang bisa diuji                         |
| **B** | CRUD kategori berita + kategori agenda (controller, request, policy, resource, halaman, test)                                            | Kedua master siap dipakai form konten                  |
| **C** | Admin Berita: list + filter + sort, form 6 bagian, Tiptap, media, slug, status, jadwal, pinned, preview, soft delete, sanitasi           | Alur pengelolaan artikel lengkap                       |
| **D** | Publik Berita: `/berita`, `/berita/{slug}`, pinned, cari, filter, related, share, SEO/OG, view count                                     | Berita dapat dibaca dan dibagikan publik               |
| **E** | `PublishScheduledPosts` + schedule tiap menit + idempoten                                                                                | Artikel terjadwal terbit otomatis                      |
| **F** | Admin Agenda: list + filter, form 5 bagian, all-day, lokasi, poster, status, duplikasi, soft delete                                      | Alur pengelolaan agenda lengkap                        |
| **G** | Publik Agenda: daftar + kalender, navigasi bulan, filter, detail, Google Calendar, `.ics`, JSON-LD                                       | Pengunjung menemukan, membaca, menambahkan ke kalender |
| **H** | Sidebar Master Data · `config/inertia.php` · permission · test yang harus berubah · `AGENTS.md` · `docs/DECISIONS.md` · `README` roadmap | Siap direview dan digabung                             |

Tujuh dari delapan tahap mengembalikan Feature ke kondisi hijau tanpa menyentuh
modul lain. Tahap H satu-satunya yang menyentuh Phase 03/04, dan tiga file di
situlah adalah **konsekuensi keputusan D-1 dan D-4**, bukan refactor.

---

# 5. Urutan Implementasi

## Tahap A — Database, enum, model, seeder

### A.1 Migration

Empat migration baru. **Tidak ada migration lama yang diedit** (ARCHITECTURE Part A §13).

**`create_post_categories_table`**

```php
$table->id();
$table->string('name', 150);
$table->string('slug', 180)->unique();
$table->integer('sort_order')->default(0);
$table->timestamps();
$table->index('sort_order');          // daftar kategori mengurut berdasarkan sort_order
```

**`create_posts_table`** — kolom persis roadmap §5.2, **tanpa `community_id`**

| Kolom                 | Definisi                                                          | Catatan                                                    |
| --------------------- | ----------------------------------------------------------------- | ---------------------------------------------------------- |
| `post_category_id`    | `foreignId()->constrained('post_categories')->restrictOnDelete()` | Kategori yang dipakai tidak boleh dihapus (roadmap §5.5)   |
| `author_id`           | `foreignId()->constrained('users')->restrictOnDelete()`           | Diisi dari `$request->user()`, **tidak pernah dari input** |
| `author_name`         | `string(255)->nullable()`                                         | Fallback ke `users.name` saat kosong (roadmap §5.2)        |
| `title`               | `string(255)`                                                     |                                                            |
| `slug`                | `string(255)->unique()`                                           | **Tanpa `withoutTrashed()`** — keputusan D-3               |
| `excerpt`             | `text()->nullable()`                                              |                                                            |
| `content`             | `longText()`                                                      | HTML Tiptap, sudah disanitasi                              |
| `featured_image`      | `string(255)->nullable()`                                         | Path varian `large` di disk public                         |
| `image_alt`           | `string(255)->nullable()`                                         |                                                            |
| `scripture_reference` | `string(255)->nullable()`                                         | Untuk kategori Renungan                                    |
| `status`              | `enum(PostStatus::values())->default('draft')`                    | Enum = tipe kolom (pola `locations.type`)                  |
| `published_at`        | `datetime()->nullable()`                                          | UTC                                                        |
| `is_pinned`           | `boolean()->default(false)`                                       |                                                            |
| `view_count`          | `unsignedInteger()->default(0)`                                   |                                                            |
| `meta_title`          | `string(255)->nullable()`                                         |                                                            |
| `meta_description`    | `text()->nullable()`                                              |                                                            |
| `media_id`            | `foreignId()->nullable()->constrained('media')->nullOnDelete()`   | **Divergensi — keputusan D-6**                             |
| `deleted_at`          | `softDeletes()`                                                   |                                                            |

Index: `unique(slug)`, `index(['status','published_at'])` (roadmap §5.2),
`index('is_pinned')`, dan — **keputusan yang belum diputuskan** — lihat §13.

> **Kandidat `content_text`.** PRD D-11 dan roadmap §7.4 meminta pencarian pada
> **isi** artikel, bukan hanya judul dan ringkasan. `LIKE '%…%'` pada kolom HTML
> mencocokkan nama tag (`<p`, `<strong`) sehingga hasil pencarian berisi kata yang tidak
> pernah ditulis. PRD §9.6 menyatakan `content_text` **MAY** ditambah; roadmap §5.2
> berkata "Jangan menambahkan `content_text` tanpa kebutuhan yang terukur dan
> **keputusan eksplisit**". Rekomendasi: tambahkan `content_text text nullable`,
> diisi di `SavePostAction` dengan `HtmlSanitizer::toPlainText()`, dan tambahkan
> **dua test** yang membuktikan bahwa kolom itu perlu (satu: bahwa pencarian
> "gathering" tidak mengembalikan artikel yang hanya memuat kata itu di dalam
> `<strong>`, dan satu bahwa pencarian pada teks isi benar-benar bekerja).
> Alternatif yang lebih murah: **`LIKE` hanya pada `title` + `excerpt`**, dan ini
> **mendiviasi AC-POST-4** yang eksplisit menyebut "pencarian judul dan isi".
> → **§13, m-8**

**`create_event_categories_table`**

```php
$table->id();
$table->string('name', 150);
$table->string('slug', 180)->unique();
$table->string('color', 7);      // "#2E7D32"
$table->timestamps();
```

**`create_events_table`** — kolom persis roadmap §5.4, **tanpa `community_id`**

| Kolom               | Definisi                                                           |
| ------------------- | ------------------------------------------------------------------ |
| `event_category_id` | `foreignId()->constrained('event_categories')->restrictOnDelete()` |
| `title`             | `string(255)`                                                      |
| `slug`              | `string(255)->unique()` — **tanpa `withoutTrashed()`** (D-3)       |
| `description`       | `longText()->nullable()` — disanitasi bila rich text dipakai       |
| `start_at`          | `datetime()` — UTC                                                 |
| `end_at`            | `datetime()->nullable()`                                           |
| `is_all_day`        | `boolean()->default(false)`                                        |
| `location_name`     | `string(255)->nullable()`                                          |
| `location_id`       | `foreignId('locations')->nullable()->nullOnDelete()`               |
| `poster_path`       | `string(255)->nullable()`                                          |
| `poster_alt`        | `string(255)->nullable()`                                          |
| `status`            | `enum(EventStatus::values())->default('scheduled')`                |
| `is_published`      | `boolean()->default(false)`                                        |
| `media_id`          | `foreignId()->nullable()->constrained('media')->nullOnDelete()`    |
| `deleted_at`        | `softDeletes()`                                                    |

Index: `unique(slug)`, `index('start_at')`, `index(['is_published','start_at'])`.

**Tidak ada generated column.** Berbeda dengan `mass_schedules` (D-40 §2) yang
memerlukan `active_marker` untuk MEMBATAL soft delete memenuhi unique constraint,
keputusan D-3 membuat baris terhapus tetap memegang slug-nya, jadi
`replicate()` tidak punya masalah error 3105 di sini.

### A.2 Enum

`app/Enums/PostStatus.php` — `Draft`/`Scheduled`/`Published`/`Archived`
`app/Enums/EventStatus.php` — `Scheduled`/`Cancelled`/`Postponed`

Keduanya mengikuti `LocationType` persis: `values(): array` untuk tipe kolom,
`label(): string` yang membaca `lang/id/<modul>.php`. Method `label()` inilah yang
mencegah enum baru muncul di database tanpa label.

### A.3 Model

| Model           | Relasi                                                                         | Cast                                                                                            | Scope                     |
| --------------- | ------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------- | ------------------------- |
| `PostCategory`  | `hasMany(Post)`                                                                | —                                                                                               | —                         |
| `Post`          | `belongsTo(PostCategory)`, `belongsTo(User,'author')`, `belongsTo(Media)`      | `status` → enum, `is_pinned` → bool, `published_at` → `immutable_datetime`, `view_count` → int  | `published()`, `pinned()` |
| `EventCategory` | `hasMany(Event)`                                                               | —                                                                                               | —                         |
| `Event`         | `belongsTo(EventCategory)`, `belongsTo(Location)` nullable, `belongsTo(Media)` | `status` → enum, `is_published`/`is_all_day` → bool, `start_at`/`end_at` → `immutable_datetime` | `published()`             |

`Post::published()` adalah **satu-satunya definisi** "publik" untuk artikel:

```php
public function scopePublished(Builder $query): Builder
{
    return $query
        ->where('status', PostStatus::Published)
        ->whereNotNull('published_at')
        ->where('published_at', '<=', now());
}
```

`Event::published()` hanya `where('is_published', true)` — tanpa syarat
`start_at`, karena kegiatan lampau tetap harus tampil di kalender bulan lampau
(EVT-02).

Route model binding publik memakai scope ini:

```php
Route::get('/berita/{post}', [PublicPostController::class, 'show'])->name('berita.show');
```

Laravel 13 mengikat `{post}` lewat `resolveRouteBinding`, dan scope membatasi
query yang di-binding. **Draf, terjadwal, arsip, dan soft-deleted 404 secara
alami** (POST-09) — tanpa `abort_if` manual yang bisa lupa di satu tempat.

### A.4 Factory & seeder

Empat factory dengan state: `Post::factory()->draft()/published()/scheduled()`,
`Event::factory()->published()/cancelled()/allDay()`. Semuanya menghasilkan
`author_id` dari `User::factory()`.

`PostCategorySeeder` (Berita 1, Kegiatan 2, Renungan 3, Pengumuman 4) dan
`EventCategorySeeder` (Liturgi, Pembinaan, Sosial, Rapat, Lainnya). Keduanya
`updateOrCreate` berdasarkan slug → idempotent. `DatabaseSeeder` memanggil
keduanya. **Tidak ada seeder konten** (keputusan m-6).

### A.5 Gate Tahap A

```bash
php artisan migrate:fresh --seed
php artisan migrate:rollback --step=4 && php artisan migrate --seed
php artisan test tests/Feature/Foundation/DatabaseFoundationTest.php
php artisan test tests/Feature/News tests/Feature/Agenda
```

---

## Tahap B — Admin kategori

### B.1 Backend

- `PostCategoryPolicy` / `EventCategoryPolicy` — empat method, persis bentuk
  `MassSchedulePolicy`, memanggil `$user->can('postcategories.view')` dst.
- `PostCategoryValidationRules` / `EventCategoryValidationRules` — trait agar
  Store dan Update tidak bisa berbeda.
    - `name`: `required|string|max:150`
    - `slug`: `required|string|alpha_dash:ascii,lower|max:180` + `Rule::unique(…, 'slug')->ignore($ignore)` **(tanpa `withoutTrashed()`, D-3)**
    - `sort_order`: `required|integer|min:0`
    - `color` (kategori agenda): `required|string|size:7|regex:/^#[0-9A-Fa-f]{6}$/`
- `PostCategoryController` / `EventCategoryController` — `index`, `create`, `store`,
  `edit`, `update`, `destroy`.
- `destroy()` memakai **pola hitung-jumlah `LocationController::destroy()`**:

    ```php
    Gate::authorize('delete', $postCategory);

    if ($postCategory->posts()->exists()) {
        return to_route('post-categories.index')
            ->withErrors(['post_category' => __('Kategori berita tidak dapat dihapus karena masih dipakai artikel.')]);
    }
    ```

    `RESTRICT` di level database adalah lapisan kedua, bukan yang pertama — karena
    ia memicu `QueryException` dan 500, sedangkan yang ini menghasilkan kalimat.

- Resource: `PostCategoryResource`, `EventCategoryResource` (baris admin dengan
  `can: { update, delete }` dan `posts_count`/`events_count` via `whenCounted()`).

### B.2 Frontend

`features/admin/kategori-berita/` dan `features/admin/kategori-agenda/`, masing-masing
`types.ts` + `components/{category-table,category-form}.tsx` +
`pages/{index,tambah,ubah}.tsx`. Komponen yang dipakai ulang
(`DataTable`, `ListToolbar`, `ListEmpty`, `RowActions`, `DeleteDialog`,
`TablePagination`, `Field`, `FormInput`, `FormActions`, `ChoiceSelect`) **tidak
disalin** — di-`import` dari feature jadwal-misa dalam batas aturan
`a feature does not import another feature`.

> **Jebakan ArchitectureTest C-4.** Aturan itu melarang `features/a` meng-import
> `features/b`. Sementara ini `DataTable`, `ListToolbar`, `Field`, dan
> `FormInput` **masih** tinggal di `features/admin/jadwal-misa/components/`.
> Kategori Berita yang butuh `DataTable` **akan** menyentuhnya.
>
> **Solusi: pindahkan bersama komponen generik itu ke `resources/js/components/`**
> pada Tahap B — dengan begitu hanya komponen yang **benar-benar** spesifik
> Jadwal Misa (`columns.tsx`, `mass-schedule-cards.tsx`, `misa-rutin/`) tetap di
> feature-nya. Setelah dipindah, jumlah konsumen: `DataTable` (2+), `ListToolbar`
> (2+), `Field`/`FormInput`/`FormActions`/`FormTextarea`/`ChoiceSelect` (2+),
> `RowActions` (2+), `DeleteDialog` (2+), `TablePagination` (2+),
> `list-parts.tsx` (2+), `form-fields.tsx` (2+), `data-table.tsx` (2+) —
> semuanya ≥2, jadi **berpindah dengan benar**. Thirap yang benar adalah
> mengimpor `features/admin/jadwal-misa/...` dari feature lain, yang pasti gagal.
> Komponen yang tersisa di `jadwal-misa/components/`: `columns.tsx`,
> `duplicate-mass-schedule-dialog.tsx`, `form-fields.tsx` bagian khusus Jadwal Misa.

### B.3 Gate Tahap B

```bash
php artisan wayfinder:generate --with-form
php artisan test tests/Feature/News tests/Feature/Agenda tests/Unit/ArchitectureTest.php
npm run check && npm run types:check && npm run test:unit
```

---

## Tahap C — Admin Berita

### C.1 Validasi — `PostValidationRules` + dua FormRequest

`prepareForValidation()` melakukan **dua hal**, keduanya di satu tempat
(`ArchitectureTest::'html is sanitized in the request layer'` hanya memeriksa
import, bukan call site — jadi AC-POST-6 dijamin **test perilaku**):

```php
protected function prepareForValidation(): void
{
    $this->merge([
        'content'    => app(HtmlSanitizer::class)->clean($this->input('content')),
        'published_at' => $this->toUtc($this->input('published_at')),
    ]);
}
```

Aturan inti:

| Field                 | Aturan                                                                                    |
| --------------------- | ----------------------------------------------------------------------------------------- | -------------------------------------------------------------------------- | ----------------------------------------------------- | --------------------------------------------------------------- |
| `title`               | `required                                                                                 | string                                                                     | max:255`                                              |
| `slug`                | `nullable                                                                                 | string                                                                     | alpha_dash:ascii,lower                                | max:255`+ unique. Kosong → diisi server dari`Str::slug($title)` |
| `post_category_id`    | `required                                                                                 | integer`+`Rule::exists('post_categories','id')`                            |
| `excerpt`             | `nullable                                                                                 | string                                                                     | max:1000`                                             |
| `content`             | `required                                                                                 | string                                                                     | max:500000`(batas sama dengan`html.max_input_length`) |
| `featured_image`      | `nullable                                                                                 | string                                                                     | max:255`                                              |
| `media_id`            | `nullable                                                                                 | integer`+`Rule::exists('media','id')->where('status', MediaStatus::Ready)` |
| `image_alt`           | `nullable                                                                                 | string                                                                     | max:255`                                              |
| `scripture_reference` | `nullable                                                                                 | string                                                                     | max:255`                                              |
| `status`              | `required` + `Rule::enum(PostStatus::class)`                                              |
| `published_at`        | `nullable                                                                                 | date`                                                                      |
| `is_pinned`           | `required                                                                                 | boolean`                                                                   |
| `view_count`          | **tidak pernah di Rules** — AGENTS.md: "Jangan menerima `view_count` dari input pengguna" |
| `meta_title`          | `nullable                                                                                 | string                                                                     | max:255`                                              |
| `meta_description`    | `nullable                                                                                 | string                                                                     | max:500`                                              |

Tiga aturan lintas-field, di `withValidator()`:

1. **`status = scheduled` ⇒ `published_at` wajib dan di masa depan.**
   `'published_at.after_or_equal:now'` dalam group bersyarat.
2. **`status = published` + `published_at` kosong ⇒ diisi `now()`.** Dikenakan di
   `SavePostAction`, bukan di request — aturan ini memiliki efek samping.
3. **Batas dua pinned.** Hitung `Post::published()->where('is_pinned', true)` dengan
   mengecualikan dirinya sendiri pada update; menolak dengan pesan Indonesia
   "Maksimal dua artikel aktif dapat disematkan."

> **Soal race condition (§7.3 roadmap).** Hitung-lalu-tulis adalah check-then-act
> dan tidak dapat dijamin sempurna terhadap dua request bersamaan — tidak ada
> indeks yang bisa menegakkan "maksimal dua". Yang diminta roadmap adalah
> "penanganan harus konsisten dan diuji", dan itulah yang diimplementasikan:
> validasi menolak dengan pesan yang sama, apa pun urutan kedua request-nya.
> Dicatat sebagai keterbatasan yang diketahui, bukan sebagai jaminan.

### C.2 `SavePostAction`

Diekstrak karena satu operasi menulis banyak hal: menyalin `author_id`, mengisi
slug bila kosong, menetapkan `published_at` bila kosong, menyimpan, dan menghapus
media lama bila diganti. `DB::transaction()` di dalamnya.

```php
final class SavePostAction
{
    public function handle(Post $post, array $data, User $author): Post
    {
        return DB::transaction(function () use ($post, $data, $author): Post {
            $post->fill([
                ...$data,
                'author_id'   => $author->getKey(),
                'author_name' => $data['author_name'] ?: $author->name,
                'slug'        => $data['slug'] ?: $this->uniqueSlug($data['title'], $post),
                'published_at' => $data['published_at']
                    ?? ($data['status'] === PostStatus::Published->value ? now() : null),
            ]);

            $post->save();

            return $post;
        });
    }
}
```

**Perilaku slug (POST-01).** Slug hanya dihitung ulang bila field kosong.
Untuk artikel yang sudah terbit, mengedit judul **tidak** mengubah slug —
`$data['slug']` akan berisi nilai lama yang dikirim form. Suffix bentrok
deterministik: `slug-2`, `slug-3`, … dihitung dengan query
`where('slug', 'like', $base.'-%')`.

### C.3 Sortir & list

`SortsAdminList` dengan whitelist:

```php
[
    'title'        => 'posts.title',
    'status'       => 'posts.status',
    'published_at' => 'posts.published_at',
    'created_at'   => 'posts.created_at',
    'is_pinned'    => 'posts.is_pinned',
]
```

Default: `[['posts.published_at', 'desc']]`. Kunci di luar whitelist **diabaikan
senyap** (AGENTS.md). Perhatikan D-42: bila suatu kolom dihapus di masa depan,
entri whitelist-nya **harus ikut hilang** atau `?sort=` yang di-bookmark menjadi 500.

Query list menyertakan `leftJoin('post_categories', …)` untuk mengurutkan kolom
kategori, dengan **`select('posts.*')` di atas `withCount()`** (D-41).

### C.4 Halaman

`pages/{index,tambah,ubah,preview}.tsx`; komponen `post-form.tsx`,
`post-table.tsx`, `post-cards.tsx` (mobile), `media-field.tsx`, `post-status-badge.tsx`.

Form **enam bagian** sesuai roadmap §4.1: informasi utama · isi (Tiptap) · media ·
pengaturan publikasi · SEO · referensi ayat. Semua `<form>` memakai **`noValidate`**.

`preview` adalah halaman Inertia `admin/berita/preview` yang merender artikel
dengan komponen baca publik, dilindungi `Gate::authorize('viewAny', Post::class)`,
dan memakai `<Seo noIndex>`.

### C.5 Media field (keputusan D-6)

Komponen `media-field.tsx` untuk featured image (dan `poster-field` untuk agenda):

1. Tombol "Pilih gambar" → `fetch`/XHR ke `POST /admin/media` dengan
   `FormData` (`file`, `alt_text`), header `X-Requested-With: XMLHttpRequest`
   supaya `expectsJson()` true.
2. `MediaController::store()` **diubah** untuk mengembalikan `JsonResponse`
   berisi `{ id, status }` saat `$request->expectsJson()`, dan tetap
   `back()` + toast Inerta untuk jalur non-JSON (agar tidak merusak apa pun).
3. Komponen melakukan polling `GET /admin/media/{media}` setiap 1,5 s sampai
   `status = ready` (varian tersedia) atau `failed`.
4. Opsi choose dari `media/variants` tampil sebagai kartu mini; `media_id`
   disimpan di form.
5. `MediaResource` baru (JSON) dikirim pada list awal supaya gambar yang sudah
   pernah diunggah bisa dipilih ulang.

`featured_image` diisi **di server** oleh `SavePostAction` dari varian `large`
(`config('media.variants')['large']`), bukan dari input klien — sehingga path
yang tersimpan selalu milik disk public yang sudah diproses.

**Tidak ada penghapusan file dari form post/event.** Media dihapus hanya lewat
`MediaController::destroy`, yang sudah membaca relasinya. Menghapus file dari
form berisiko menghapus file yang masih dirujuk (roadmap §7.6 "Jangan menghapus
file yang masih dipakai konten lain").

### C.6 Gate Tahap C

```bash
php artisan test tests/Feature/News
php artisan test tests/Feature/Media
npm run check && npm run types:check
```

---

## Tahap D — Publik Berita

### D.1 `PublicPostController`

**`index()`** — props: `posts` (paginasi), `pinned` (array), `categories` (array),
`filters`, `seo`.

Blok pinned **query terpisah** dari daftar. Kalau bukan, pagination menghitung
artikel yang sedang ditampilkan fitur dan halaman pertama hanya berisi dua
artikel. Query pinned dibatasi `->limit(2)` di server sehingga batas POST-07 ditegakkan
**meski** data di database menyimpang.

**`show(Post $post)`** — binding berbasis scope `published()` (lihat A.3).
Props: `post` (detail), `related` (array), `previous`/`next`, `categories`,
`seo`.

`RecordPostViewAction::handle(Post $post, string $userAgent)`:

```php
if (in_array(strtolower(trim($userAgent)), self::BOT_USER_AGENTS, true)) {
    return;
}

if ($request->session()->pull("viewed-post.{$post->getKey()}") !== null) {
    return;      // sudah dihitung sesi ini
}

$post->increment('view_count');
```

Controller membaca `$request->userAgent()` lalu meneruskanya — **Action bukan
Service**, karena `ArchitectureTest::'services do not depend on the http layer'`
melarang Service menerima `Illuminate\Http\Request`. Daftar bot adalah konstanta
privat di Action (D-12).

### D.2 Resource publik

`PublicPostResource` (kartu daftar: `id, title, slug, excerpt, image, image_alt,
category, published_at`) dan `PublicPostDetailResource` (tambah `content` yang
sudah disanitasi, `author_name`, `scripture_reference`, `meta_title`,
`meta_description`). **`content` hanya ada di detail.** Excerpt daftar dihitung
server dari `HtmlSanitizer::toPlainText()` bila `excerpt` kosong.

Related posts: `PostCategory` yang sama, exclude diri, Published, limit 3, dengan
`array_values(...resolve())`.

### D.3 Halaman & komponen

`features/public/berita/` — `types.ts`, `components/{article-card,pinned-strip,share-actions,related-posts,article-pagination,news-filters}.tsx`,
`pages/{index,show}.tsx` (di-rewrite dari placeholder 9 baris).

`<Seo ogType="article" ogImage={...} canonical={…} />` memakai head key yang
**sudah ada** — `ArchitectureTest::'the seo head keys match'` tetap hijau tanpa
edit Blade.

`article-pagination` untuk prev/next berdasarkan `published_at`.

### D.4 Gate Tahap D

```bash
php artisan test tests/Feature/News/PublicPostPageTest.php tests/Feature/News/ViewCountTest.php
php artisan test tests/Feature/Public
```

---

## Tahap E — Publikasi terjadwal

### E.1 Command

`app/Console/Commands/PublishScheduledPosts.php`, signature `posts:publish-scheduled`:

```php
public function handle(): int
{
    $published = 0;

    Post::query()
        ->where('status', PostStatus::Scheduled->value)
        ->whereNotNull('published_at')
        ->where('published_at', '<=', now())
        ->update(['status' => PostStatus::Published->value]);

    // ...
}
```

**Idempoten secara struktural**: `UPDATE … WHERE status = 'scheduled'` dijalankan
dua kali menghasilkan perubahan kedua yang Affects 0 baris. Tidak ada read-then-write,
jadi tidak ada race yang bisa menerbitkan dua kali.

### E.2 Schedule

Di `bootstrap/app.php::withSchedule()`, berdampingan dengan `queue:prune-failed`:

```php
$schedule->command('posts:publish-scheduled')->everyMinute()->withoutOverlapping();
```

Docblock `routes/console.php` diperbarui — klaim bahwa berkas itu "reserves itself
for PublishScheduledPosts" sudah tidak berlaku (keputusan m-1).

### E.3 Gate Tahap E

```bash
php artisan test --filter=ScheduledPublishingTest
```

---

## Tahap F — Admin Agenda

### F.1 Validasi

`EventValidationRules` + `Store/UpdateEventRequest`. `prepareForValidation()`
menjalankan dua hal lagi: konversi `start_at`/`end_at` WIB→UTC (pola
`StoreSpecialMassRequest::toUtc()`) dan sanitasi `description`.

| Field                      | Aturan                                                            |
| -------------------------- | ----------------------------------------------------------------- | ------------------------------------------------ | ----------------------------------------------------- |
| `event_category_id`        | `required                                                         | integer`+`Rule::exists('event_categories','id')` |
| `title`                    | `required                                                         | string                                           | max:255`                                              |
| `slug`                     | `nullable                                                         | alpha_dash:ascii,lower                           | max:255`+ unique **(tanpa`withoutTrashed()`, D-3)\*\* |
| `description`              | `nullable                                                         | string                                           | max:50000`                                            |
| `start_at`                 | `required                                                         | date`                                            |
| `end_at`                   | `nullable                                                         | date                                             | after_or_equal:start_at` (EVT-01)                     |
| `is_all_day`               | `required                                                         | boolean`                                         |
| `location_id`              | `nullable                                                         | integer`+`Rule::exists('locations','id')`        |
| `location_name`            | `nullable                                                         | string                                           | max:255`                                              |
| `poster_path`/`poster_alt` | `nullable                                                         | string                                           | max:255`                                              |
| `media_id`                 | `nullable` + `Rule::exists('media','id')->where('status', Ready)` |
| `status`                   | `required` + `Rule::enum(EventStatus::class)`                     |
| `is_published`             | `required                                                         | boolean`                                         |

> **Perbedaan penting dari Jadwal Misa.** `location_id` **tidak** memakai
> `ValidatesActiveLocationReference` (yang mensyaratkan `is_active = true`).
> Menghapus `is_active` dari sebuah agenda akan membuatnya hilang tanpa penjelasan
> — dan `ON DELETE SET NULL` sudahastatic_JANGAN mengembalikan lokasi yang dihapus.
> Trade-off dicatat di DECISIONS.md.

### F.2 `DuplicateEventAction`

```php
$copy = $source->replicate(['deleted_at']);   // tidak ada generated column di events
$copy->slug = null;                          // biarkan SaveEventAction membuat slug baru
$copy->is_published = false;                 // duplikat tidak tayang sampai disetujui
```

Menyalin `poster_path` dan `media_id` **diizinkan** (bukan file yatim — keduanya
hanya referensi baris, bukan file). `replicate(['deleted_at'])` sudah cukup;
error 3105 yang D-40 dokumentasikan **tidak berlaku di sini** karena `events` tidak
punya generated column.

### F.3 Halaman

`pages/{index,tambah,ubah}.tsx`; komponen `event-form.tsx`, `event-table.tsx`,
`event-cards.tsx`, `poster-field.tsx`, `event-status-badge.tsx`. Form **lima bagian**
sesuai roadmap §4.1.

Filter list: `search`, `category_id`, `status` (`scheduled`/`cancelled`/`postponed`),
`is_published`, filter periode (`bulan` = `YYYY-MM`).

Duplikasi: `POST /admin/agenda/{event}/duplicate` (keputusan m-5).

---

## Tahap G — Publik Agenda

### G.1 `PublicEventController`

**`index()`** — query params `?mode=list|calendar`, `?bulan=YYYY-MM`,
`?kategori=<slug>`.

`mode` default `list` di **semua viewport** (AC-EVT-2: "di ponsel tampilan daftar
jadi bawaan"). Jangan lewat CSS `@media` untuk memilih mode — mode adalah pilihan
pengguna, dan CSS yang berbeda menghasilkan URL berbeda dengan isi berbeda.

Query berbeda per mode:

- `list` → kegiatan mendatang (`start_at >= awal hari WIB`) + semua kegiatan pada
  tanggal yang sedang ditintest
- `calendar` → satu bulan penuh, dikelompokkan per tanggal dengan `groupBy`

Filter di luar whitelist **diabaikan senyap**, mengikuti `PublicMassScheduleController::filteredDay()`.

### G.2 Kalender tanpa dependency baru

Grid bulan dihitung dari string `YYYY-MM` + `Intl.DateTimeFormat` dengan
`timeZone: 'Asia/Pontianak'`. Helper baru di `lib/datetime.ts`:

| Helper                 | Fungsi                                                            |
| ---------------------- | ----------------------------------------------------------------- |
| `wibDateKey(iso)`      | `Date` ISO → `'YYYY-MM-DD'` di WIB                                |
| `wibMonthGrid(month)`  | `['YYYY-MM'` → `{ weeks, leadingBlanks, days }` untuk render grid |
| `wibMonthLabel(month)` | `'2026-10'` → `'Oktober 2026'`                                    |

WIB (UTC+7) **tidak punya DST**, jadi aritmetika tanggal pada `'YYYY-MM-DD'` aman
tanpa `date-fns-tz`. Lihat §7 R-13.

Kalender **tidak** memakai primitive shadcn `Calendar` (yang tidak terpasang dan
padanya adalah date-picker, bukan month-grid). Roadmap §4.2 mewajibkan mode daftar
sebagai tampilan utama di ponsel dan kalender tidak boleh memaksa grid terpotong;
grid 7 kolom di 360 px achieved dengan `text-xs` dan sel `aspect-square`.

Navigasi: bulan sebelumnya, bulan berikutnya, tombol "Hari Ini" (§8.3).

### G.3 Detail + Google Calendar + ICS

`PublicEventController::show(Event $event)` — binding atas `published()`.

Google Calendar URL dibangun **di klien** dari data yang sudah tervalidasi (EVT-03
dan D-07 PRD):

```
https://calendar.google.com/calendar/render?action=TEMPLATE
  &text=…&dates=…&details=…&location=…&ctz=Asia/Pontianak
```

dengan `URLSearchParams` (meng-encode dengan benar secara default).

**`IcsCalendarBuilder`** — Service (`App\Services\IcsCalendarBuilder`), karena ia
tidak membaca Request dan boleh dipakai dari controller mana pun:

| Property ICS  | Sumber                                                        |
| ------------- | ------------------------------------------------------------- |
| `UID`         | `{slug}@{app.url}` — **stabil**, bukan UUID baru tiap unduhan |
| `DTSTAMP`     | `now()` dalam UTC, format `Ymd\THis\Z`                        |
| `DTSTART`     | `start_at` UTC → `Ymd\THis\Z`; all-day → `VALUE=DATE:Ymd`     |
| `DTEND`       | `end_at` bila ada; all-day → `start_at + 1 hari`              |
| `SUMMARY`     | `title`                                                       |
| `LOCATION`    | `location` master atau `location_name` (EVT-06)               |
| `DESCRIPTION` | `HtmlSanitizer::toPlainText($description)`                    |
| `STATUS`      | `CANCELLED` bila `status = cancelled` (EVT-07)                |
| `URL`         | `route('agenda.show', $event)`                                |

**Escaping (roadmap §8.5, "Hindari CRLF injection").** RFC 5545 §3.3.11:
nilai teks harus meng-escape `\` → `\\`, `;` → `\;`, `,` → `\,`, baris baru →
`\n`, dan `\r\n` → harus dihapus/di-escape lebih dulu:

```php
private function escape(string $value): string
{
    return str_replace(
        ['\\', "\r\n", "\n", "\r", ';', ','],
        ['\\\\', '\\n', '\\n', '\\n', '\\;', '\\,'],
        $value
    );
}
```

Tanpa langkah `\r\n` **lebih dulu**, input `X\r\nBEGIN:VEVENT` akan menjadi
`X\\nBEGIN:VEVENT` yang tetap menyisipkan baris kedua. Urutan penggantian di atas
menangani itu.

Header respons: `Content-Type: text/calendar; charset=utf-8`,
`Content-Disposition: attachment; filename="{slug}.ics"`. Line ending **CRLF**.

Route publik `agenda.ics` memakai binding atas `published()` → agenda nonpublik
dan soft-deleted 404 (roadmap §8.5).

### G.4 Structured data `Event` (keputusan D-5)

Di `<Head>` pada `features/public/agenda/pages/show.tsx`:

```tsx
<Head>
    <script
        type="application/ld+json"
        head-key="json-ld"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(eventSchema) }}
    />
</Head>
```

> **Ini menambah satu `dangerouslySetInnerHTML` di repo**, dan
> `ArchitectureTest::'only the error page renders sanitized html on the frontend'`
> memindai **seluruh** `resources/js` untuk pola itu, exempted hanya file bernama
> `rich-text.tsx`. Test itu akan **merah** dan harus diperbarui untuk mengecualikan
> file JSON-LD dengan nama yang jelas — lihat §13 m-9 untuk opsi dan alasannya.
>
> → **§13, m-9**

Head key `json-ld` harus ada di **kedua** sisi (`seo.tsx` dan `app.blade.php`)
agar `ArchitectureTest::'the seo head keys match'` tetap hijau.

Payload `Event` schema.org berisi `name`, `startDate` (ISO dengan offset WIB),
`endDate`, `eventStatus`, `eventAttendanceMode`, `location`, `description`,
`image`, `url`.

---

## Tahap H — Integrasi, sidebar, dan dokumen

### H.1 Sidebar

`resources/js/layouts/app/components/app-sidebar.tsx`:

```tsx
import { index as postCategoriesIndex } from "@/routes/post-categories";
import { index as eventCategoriesIndex } from "@/routes/event-categories";

const MASTER_DATA_ENTRIES: Entry[] = [
    { title: "Lokasi", href: locationsIndex(), icon: MapPin },
    { title: "Kategori Berita", href: postCategoriesIndex(), icon: Tags },
    { title: "Kategori Agenda", href: eventCategoriesIndex(), icon: Tags },
];
```

- `CONTENT_ENTRIES` kehilangan anak `Lokasi` dan **memperoleh `href`** pada
  `Berita` dan `Agenda`.
- `<SidebarGroup label="Master Data" entries={MASTER_DATA_ENTRIES} />` disisipkan
  **antara** `Konten` dan `Akun`, sesuai urutan roadmap §9.3.
- Import **Wayfinder route**, bukan feature — `ArchitectureTest::'layouts do not
import pages or features'`.

> **Dua jebakan yang sudah terdokumentasi di `AGENTS.md`** harus dijaga:
>
> 1. Baris **judul grup adalah disclosure `<button>` dengan `aria-expanded`**, bukan
>    link. Master Data tidak punya anak (Lokasi/Kategori Berita/Kategori Agenda
>    adalah sibling, bukan hierarki), jadi ia dirender sebagai tiga tautan biasa
>    dan **tidak** memakai pola disclosure.
> 2. Auto-open memeriksa **semua** URL grup. Master Data tidak punya `children`,
>    jadi pemeriksaan otomatisnya otomatisExcerpt menjadi tidak relevan.

### H.2 Permission

`PermissionSeeder` + 12 nama, satu kata (keputusan m-2):

```
postcategories.view   postcategories.create   postcategories.update   postcategories.delete
events.view           events.create           events.update           events.delete
eventcategories.view  eventcategories.create  eventcategories.update  eventcategories.delete
```

Total 31 → **43**. `RbacFoundationTest.php:62-92` **wajib** ditambah 12 baris
(kontrak C-3). `docs/RBAC.md` tabel permission diperbarui.

### H.3 Konfigurasi

- `config/inertia.php` `pages.paths` + 4 direktori (kontrak C-5)
- `bootstrap/app.php` + 1 schedule entry (Tahap E)
- `routes/web.php` — ganti 4 closure + stub ICS dengan controller; tambah
  `require __DIR__.'/news.php';` dan `require __DIR__.'/agenda.php';`
- `routes/console.php` — docblock
- `.env`, `phpunit.xml`, `composer.json`, `package.json` **tidak berubah**

### H.4 Dokumentasi

- `docs/DECISIONS.md` — D-43 … D-48 (§12, 12)
- `AGENTS.md` — diperbarui pada dua titik yang memang berubah: sidebar (tiga anak
    - Master Data) dan scheduler
- `docs/roadmap/README.md` — Current Focus → Phase 05; tabel status
- `docs/plan-implementation/plan-phase-05-news-agenda.md` — §14 diisi dengan hasil
  verifikasi aktual

---

# 6. File yang Akan Terpengaruh

## 6.1 Dibuat — backend

```
app/Enums/PostStatus.php
app/Enums/EventStatus.php
app/Models/Post.php
app/Models/PostCategory.php
app/Models/Event.php
app/Models/EventCategory.php
app/Policies/PostPolicy.php
app/Policies/PostCategoryPolicy.php
app/Policies/EventPolicy.php
app/Policies/EventCategoryPolicy.php
app/Concerns/PostValidationRules.php
app/Concerns/PostFormMeta.php
app/Concerns/EventValidationRules.php
app/Concerns/EventFormMeta.php
app/Services/IcsCalendarBuilder.php
app/Actions/News/SavePostAction.php
app/Actions/News/RecordPostViewAction.php
app/Actions/Agenda/SaveEventAction.php
app/Actions/Agenda/DuplicateEventAction.php
app/Console/Commands/PublishScheduledPosts.php
app/Http/Controllers/News/PostController.php
app/Http/Controllers/News/PostPreviewController.php
app/Http/Controllers/News/PostCategoryController.php
app/Http/Controllers/News/PublicPostController.php
app/Http/Controllers/Agenda/EventController.php
app/Http/Controllers/Agenda/EventCategoryController.php
app/Http/Controllers/Agenda/PublicEventController.php
app/Http/Controllers/Agenda/EventIcsController.php
app/Http/Requests/News/StorePostRequest.php
app/Http/Requests/News/UpdatePostRequest.php
app/Http/Requests/News/StorePostCategoryRequest.php
app/Http/Requests/News/UpdatePostCategoryRequest.php
app/Http/Requests/Agenda/StoreEventRequest.php
app/Http/Requests/Agenda/UpdateEventRequest.php
app/Http/Requests/Agenda/StoreEventCategoryRequest.php
app/Http/Requests/Agenda/UpdateEventCategoryRequest.php
app/Http/Resources/News/PostResource.php
app/Http/Resources/News/PostOptionResource.php
app/Http/Resources/News/PostCategoryResource.php
app/Http/Resources/News/MediaResource.php
app/Http/Resources/Public/News/PublicPostResource.php
app/Http/Resources/Public/News/PublicPostDetailResource.php
app/Http/Resources/Public/News/PublicPostCategoryResource.php
app/Http/Resources/Agenda/EventResource.php
app/Http/Resources/Agenda/EventOptionResource.php
app/Http/Resources/Agenda/EventCategoryResource.php
app/Http/Resources/Public/Agenda/PublicEventResource.php
app/Http/Resources/Public/Agenda/PublicEventDetailResource.php
app/Http/Resources/Public/Agenda/PublicEventCategoryResource.php
database/migrations/2026_10_XX_create_post_categories_table.php
database/migrations/2026_10_XX_create_posts_table.php
database/migrations/2026_10_XX_create_event_categories_table.php
database/migrations/2026_10_XX_create_events_table.php
database/factories/PostFactory.php
database/factories/PostCategoryFactory.php
database/factories/EventFactory.php
database/factories/EventCategoryFactory.php
database/seeders/PostCategorySeeder.php
database/seeders/EventCategorySeeder.php
lang/id/news.php
lang/id/agenda.php
routes/news.php
routes/agenda.php
```

## 6.2 Dibuat — frontend

```
resources/js/components/rich-text-editor.tsx          # dipindah dari pengaturan (D-4)

resources/js/features/admin/berita/types.ts
resources/js/features/admin/berita/components/{post-form,post-table,post-cards,media-field,post-status-badge}.tsx
resources/js/features/admin/berita/pages/{index,tambah,ubah,preview}.tsx
resources/js/features/admin/kategori-berita/types.ts
resources/js/features/admin/kategori-berita/components/{category-table,category-form}.tsx
resources/js/features/admin/kategori-berita/pages/{index,tambah,ubah}.tsx
resources/js/features/admin/agenda/types.ts
resources/js/features/admin/agenda/components/{event-form,event-table,event-cards,poster-field,event-status-badge}.tsx
resources/js/features/admin/agenda/pages/{index,tambah,ubah}.tsx
resources/js/features/admin/kategori-agenda/types.ts
resources/js/features/admin/kategori-agenda/components/{category-table,category-form}.tsx
resources/js/features/admin/kategori-agenda/pages/{index,tambah,ubah}.tsx

resources/js/features/public/berita/types.ts
resources/js/features/public/berita/components/{article-card,pinned-strip,share-actions,related-posts,article-pagination,news-filters}.tsx
resources/js/features/public/agenda/types.ts
resources/js/features/public/agenda/components/{agenda-card,agenda-calendar,agenda-filters,agenda-list,calendar-actions}.tsx

tests/Feature/News/PostTest.php
tests/Feature/News/PostCategoryTest.php
tests/Feature/News/PublicPostPageTest.php
tests/Feature/News/ScheduledPublishingTest.php
tests/Feature/News/ViewCountTest.php
tests/Feature/News/PostPreviewTest.php
tests/Feature/Agenda/EventTest.php
tests/Feature/Agenda/EventCategoryTest.php
tests/Feature/Agenda/PublicEventPageTest.php
tests/Feature/Agenda/IcsTest.php
tests/Feature/Agenda/DuplicateEventTest.php
tests/e2e/news-agenda.spec.ts
```

## 6.3 Diubah — backend

| File                                             | Perubahan                                                                                   | Alasan                                                                       |
| ------------------------------------------------ | ------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------- |
| `routes/web.php`                                 | Ganti 4 closure berita/agenda + stub ICS dengan controller; tambah `require` dua file route | Kontrak C-1. **Nama route `berita.*`/`agenda.*`/`agenda.ics` dipertahankan** |
| `bootstrap/app.php`                              | + 1 schedule entry                                                                          | Tahap E                                                                      |
| `routes/console.php`                             | Docblock                                                                                    | Keputusan m-1                                                                |
| `database/seeders/PermissionSeeder.php`          | + 12 permission                                                                             | Tahap H, keputusan m-2                                                       |
| `database/seeders/DatabaseSeeder.php`            | + 2 seeder                                                                                  | Tahap A                                                                      |
| `config/inertia.php`                             | + 4 `pages.paths`                                                                           | Kontrak C-5 — arch test                                                      |
| `app/Http/Controllers/Media/MediaController.php` | `store()` mengembalikan JSON saat `expectsJson()`                                           | Keputusan D-6                                                                |
| `app/Services/HtmlSanitizer.php`                 | **Tidak berubah**                                                                           | Allowlist sudah memuat `img[src,alt]`, `figure`, `figcaption`                |

## 6.4 Diubah — frontend

| File                                                                     | Perubahan                                                            |
| ------------------------------------------------------------------------ | -------------------------------------------------------------------- |
| `resources/js/features/admin/pengaturan/components/rich-text-editor.tsx` | **Dihapus** (dipindah)                                               |
| `resources/js/features/admin/pengaturan/pages/index.tsx`                 | Import disesuaikan                                                   |
| `resources/js/features/public/berita/pages/{index,show}.tsx`             | Di-rewrite                                                           |
| `resources/js/features/public/agenda/pages/{index,show}.tsx`             | Di-rewrite                                                           |
| `resources/js/layouts/app/components/app-sidebar.tsx`                    | Group Master Data; `href` Berita/Agenda; hapus Lokasi dari sub-menu  |
| `resources/js/lib/datetime.ts`                                           | + `wibDateKey`, `wibMonthGrid`, `wibMonthLabel`, `googleCalendarUrl` |
| `resources/js/components/seo.tsx`                                        | + head key `json-ld` (keputusan D-5)                                 |
| `resources/views/app.blade.php`                                          | + tag `data-inertia="json-ld"` yang kosong (agar kedua sisi sinkron) |
| `resources/js/features/admin/jadwal-misa/components/*`                   | Komponen generik **dipindah** ke `components/` (§B.2)                |

## 6.5 Diubah — test dan dokumentasi

| File                                                   | Perubahan                                                                                               |
| ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------- |
| `tests/Feature/Public/PublicPlaceholderRoutesTest.php` | **Hapus 2 baris** dataset + **hapus 3 test**; tulis ulang `describe('the .ics endpoint')` — kontrak C-1 |
| `tests/Feature/Public/PublicRoutesTest.php`            | `/berita` + `/agenda` keluar dari dataset `placeholder pages` — kontrak C-2                             |
| `tests/Feature/Foundation/RbacFoundationTest.php`      | + 12 baris di array `$expected` — kontrak C-3                                                           |
| `tests/js/components/app-sidebar.test.tsx`             | `SUB_MENU` 4→3; test "does not link a module that has no route yet" ditulis ulang — kontrak C-7         |
| `tests/e2e/mass-schedule.spec.ts`                      | Sub-menu kehilangan `['Lokasi', …]` — kontrak C-7                                                       |
| `docs/RBAC.md`                                         | Tabel permission                                                                                        |
| `AGENTS.md`                                            | Poin sidebar + poin scheduler                                                                           |
| `docs/DECISIONS.md`                                    | + D-43 …                                                                                                |
| `docs/roadmap/README.md`                               | Status Phase 5                                                                                          |

## 6.6 Dihapus

Tidak ada file yang dihapus, kecuali
`resources/js/features/admin/pengaturan/components/rich-text-editor.tsx` yang
dipindahkan (D-4).

---

# 7. Dependency / Configuration Impact

## 7.1 Dependency baru: **nol**

| Kebutuhan                         | Yang tersedia                                                                         | Kenapa tidak perlu dependency baru                                                                                          |
| --------------------------------- | ------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| Sanitasi HTML                     | `symfony/html-sanitizer` via `HtmlSanitizer`                                          | Sudah dipakai `privacy_policy_content`; allowlist sudah mencakup elemen artikel                                             |
| Emoji/pemformatan                 | Tiptap 3.31 + StarterKit                                                              | Sudah terpasang                                                                                                             |
| Tabel daftar + sortir server-side | `@tanstack/react-table` v9 + `SortsAdminList`                                         | Dipakai Phase 04                                                                                                            |
| Ikon                              | `lucide-react`                                                                        | Sudah dipakai                                                                                                               |
| Kalender bulanan + tanggal WIB    | `Intl.DateTimeFormat` + aritmetika string                                             | **Tidak ada** date-fns/dayjs/luxon di `package.json`. WIB tidak punya DST, jadi aritmetika `'YYYY-MM-DD'` aman (lihat §7.2) |
| Grafik kalender                   | Grid 7 kolom + `aspect-square`                                                        | shadcn `Calendar` **tidak terpasang** dan itu date-picker, bukan month-grid                                                 |
| ICS RFC 5545                      | Penulis teks 60 baris + `array` + `implode("\r\n")`                                   | Adding `spatie/icalendar-generator` untuk satu `VEVENT` adalah over-engineering                                             |
| Unggah gambar                     | `MediaController` + `ImageProcessor` + `GenerateImageVariants` + `IsProcessableImage` | Semua sudah ada (D-24)                                                                                                      |

Roadmap §2.1: _"Jangan menambahkan dependency baru bila kemampuan yang diperlukan
sudah tersedia."_ DESIGN.md §7: _"Do not use a second component library just to
solve one isolated UI problem."_

## 7.2 Apakah kalender tanpa library tanggal benar-benar aman?

Ya, dan alasannya spesifik:

1. **WIB = UTC+7 konstan.** `Asia/Pontianak` tidak pernahvegere observes DST sejak
   2018 (sebelumnya ada zona GMT+8 di sebagian wilayah), dan Indonesia tidak
   mengembalikannya. Jadi `offset = 7` setiap saat dalam setiap tahun.
2. Calendar grid bekerja pada **`'YYYY-MM-DD'` sebagai string**, bukan pada objek
   `Date`. `new Date('2026-10-05')` diparse sebagai **UTC** tengah malam, dan
   `setDate()`/\__ hari pada zona WIB menggesernya mundur satu hari — jebakan
   klasik yang membuat kalender selalu bergeser. Bekerja pada string,String
   `new Date(y, m-1, d)` membaca sebagai waktu **lokal** perangkat, yang
   \_justru_ benar untuk hari kalender: hari kalender adalah label hari, bukan
   instan.
3. Konversi **instant** (kegiatan) tetap lewat `formatToParts` dengan `timeZone`
   eksplisit, persis pola `lib/datetime.ts` yang sudah ada.
   4.mois DST di lain tempat tidak berpengaruh karena grid tidak pernah menyentuh
   zona selain WIB.

Kalau keputusan ini ditinjau ulang dan ternyatakalender perlu mendukung zona
multi, barulah `date-fns` + `@date-fns/tz` masuk akal.

## 7.3 Konfigurasi yang berubah

| Config                                 | Perubahan                                                                                                                                                                                                         | Wajib?                 |
| -------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------- |
| `config/inertia.php`                   | + 4 `pages.paths`                                                                                                                                                                                                 | **Ya** — arch test C-5 |
| `PermissionSeeder`                     | + 12 permission (31 → 43)                                                                                                                                                                                         | **Ya** — test C-3      |
| `bootstrap/app.php`                    | + 1 schedule entry                                                                                                                                                                                                | Ya                     |
| `routes/web.php`, `routes/console.php` | route + docblock                                                                                                                                                                                                  | Ya                     |
| `config/media.php`                     | **Tidak berubah** — 3072 KB sudah cocok dengan T20/POST-04, MIME & varian sudah adequate                                                                                                                          | Tidak                  |
| `config/html.php`                      | **Tidak berubah** — `img[src,alt]`, `figure`, `figcaption` sudah ada. **Jangan** tambahkan `class`/`style`/`id`                                                                                                   | Tidak                  |
| `lang/id/validation.php`               | Kemungkinan 0 — semua pesan lewat `messages()` per-request, seperti `MassScheduleValidationRules`. Jika ada rule baru yang jatuh ke default, dua test penjaga di `ApplicationConfigurationTest` akan memberi tahu | Pantau                 |
| `.env`, `phpunit.xml`                  | **Tidak berubah**                                                                                                                                                                                                 | Tidak                  |
| Wayfinder                              | `php artisan wayfinder:generate --with-form` setelah route berubah                                                                                                                                                | Ya                     |

## 7.4 `media_id` — divergensi yang disengaja

Roadmap §5.2 dan §5.4 tidak menyebut `media_id`; keduanya menyebut
`featured_image VARCHAR(255)` dan `poster_path VARCHAR(255)` sebagai path. Plan ini
**menambah satu kolom nullable FK** ke `media` di kedua tabel, selain kolom path
yang tetap ada.

- **Alasan:** `MediaController::store()` mengembalikan `back()`, jadi form tidak
  pernah tahu ID media yang baru diunggah. Tanpa FK, picker tidak bisa menampilkan
  status `pending`/`ready`/`failed` maupun URL varian — danXC-M1 minta status itu
  ditampilkan di UI.
- **Yang tetap dijaga:** `featured_image`/`poster_path` tetap ditulis server dari
  varian `large`, jadi tidak ada path yang bisa_forged klien.
- **Pencatatan:** DECISIONS.md.

---

# 8. Risiko dan Mitigasi

| #    | Risiko                                                                                                                                             | Dampak                                                                                                                       | Mitigasi                                                                                                                                                                                |
| ---- | -------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| R-1  | **PRD §9.6/§9.7 masih menyebut `community_id`** (D-03/D-05), §7.4/§7.5 menyebut filter/field komunitas                                             | Pembaca PRD bisa menambahkan kolom yang dilarang                                                                             | Migration tidak punya kolom itu; entri DECISIONS.md menandai §9.6/§9.7/D-03/D-05 sebagai **menunggu sinkronisasi** (keputusan D-2)                                                      |
| R-2  | Roadmap §9.3 vs **AGENTS.md** "sub-menu Jadwal Misa memiliki empat anak" (keputusan D-1)                                                           | Dokumentasi aktif jadi salah                                                                                                 | `AGENTS.md` diperbarui di Tahap H; test diperbarui (kontrak C-7)                                                                                                                        |
| R-3  | Roadmap §12.1 bilang scheduler di `routes/console.php`; **`AGENTS.md` bilang `bootstrap/app.php`**                                                 | Command tidak pernah jalan                                                                                                   | Mengikuti praktik yang berjalan (keputusan m-1); docblock `routes/console.php` diperbarui                                                                                               |
| R-4  | `ArchitectureTest::'html is sanitized in the request layer'` hanya cek **import**, bukan call site                                                 | `HtmlSanitizer::clean()` terhapus diam-diam dan test tetap hijau                                                             | AC-POST-6 dijamin **test perilaku** di `PostTest` yang mem-post `<script>`/`onerror=` lalu memeriksa kolom                                                                              |
| R-5  | **D-39** — list prop non-paginate jadi `{data: [...]}`                                                                                             | `.map` dipanggil pada objek → **layar kosong**, sementara seluruh test Pest hijau                                            | Semua list prop memakai `array_values(Resource::collection(…)->resolve())`; assertion memakai `->where('x.0.id', …)` + `->missing('x.data')`, bukan `has('x', 1)` yang menghitung kunci |
| R-6  | **`Event` bentrok dengan facade `Illuminate\Support\Facades\Event`**                                                                               | `Event::dispatch()` diam-diam memanggil model                                                                                | Model tetap `Event`; file yang butuh facade meng-`alias()`-nya; dicatat di DECISIONS.md                                                                                                 |
| R-7  | **`select()` di bawah `withCount()`** diam-diam menghapus hitungan (D-41)                                                                          | `posts_count` selalu nol                                                                                                     | `select('posts.*')` **selalu di atas** `withCount()`                                                                                                                                    |
| R-8  | Filter publik menerima nilai di luar whitelist                                                                                                     | 500 dari query                                                                                                               | Diabaikan **senyap**, mengikuti `PublicMassScheduleController::filteredDay()`                                                                                                           |
| R-9  | Whitelist sortir menyisakan kolom yang sudah dihapus (D-42)                                                                                        | `?sort=language` yang di-bookmark menjadi 500                                                                                | Setiap penghapusan kolom **wajib** menghapus entri whitelist-nya                                                                                                                        |
| R-10 | Gambar diganti → file yatim, atau lebih buruk file terhapus padahal masih dirujuk                                                                  | Konten rusak                                                                                                                 | Tidak ada penghapusan file dari form; `MediaController::destroy` sudah membaca relasi                                                                                                   |
| R-11 | File **terlalu cepat** ditulis `media_id` padahal varian belum ada                                                                                 | Gambar rusak tampil di publik                                                                                                | Validasi `Rule::exists('media','id')->where('status', Ready)`; picker hanya menampilkan yang `ready`                                                                                    |
| R-12 | **JSON-LD butuh `dangerouslySetInnerHTML`** (keputusan D-5)                                                                                        | `ArchitectureTest::'only the error page renders sanitized html'` **merah**                                                   | → **§13, m-9**                                                                                                                                                                          |
| R-13 | Kalender bergeser satu hari karena `new Date('YYYY-MM-DD')` diparse sebagai UTC                                                                    | Tanggal salah di kalender                                                                                                    | Grid bekerja pada string `'YYYY-MM-DD'`; instan dikonversi lewat `formatToParts` dengan `timeZone` eksplisit (§7.2)                                                                     |
| R-14 | **CRLF injection** pada data yang masuk ke file `.ics`                                                                                             | VEVENT palsu bisa disisipkan ke kalender parishioner                                                                         | Escaping RFC 5545 dengan urutan penggantian yang menangani `\r\n` **lebih dulu**; test khusus (§10)                                                                                     |
| R-15 | Batas 2 pinned tidak dapat dijamin terhadap dua request bersamaan                                                                                  | Tiga artikel aktif sempat tersemat                                                                                           | Roadmap §7.3 sendiri meminta "penanganan harus konsisten dan diuji", bukan jaminan keras; validasi menolak dengan pesan sama apa pun urutan request                                     |
| R-16 | **Scope publik bocor** lewat related posts, sitemap, metadata, atau ICS                                                                            | Konten privat terindeks                                                                                                      | Related posts memakai scope `published()`; ICS memakai binding atas scope; tidak ada sitemap di Fase ini (§12)                                                                          |
| R-17 | `<form>` tanpa `noValidate`                                                                                                                        | Browser memvalidasi dulu, submit tidak pernah jalan, pesan Indonesia tidak pernah muncul, test E2E gagal di langkah terakhir | `noValidate` di setiap form; test E2E mengetik field kosong lalu menyimpan                                                                                                              |
| R-18 | `npm run check:fix` pernah **menghapus isi file**                                                                                                  | Konten hilang tanpa jejak                                                                                                    | Dihindari; bila dipakai, `git diff` langsung dibaca                                                                                                                                     |
| R-19 | Pre-commit menjalankan `composer ci:check` penuh (~25 detik, butuh MySQL test)                                                                     | Commit lambat atau gagal                                                                                                     | Dijalankan per tahap; `--no-verify` hanya bila database tidak tersedia                                                                                                                  |
| R-20 | Fase ini besar (±110 file baru)                                                                                                                    | Commit besar sulit direview                                                                                                  | Dipecah 8 commit (Tahap A–H), masing-masing melewati `composer ci:check`                                                                                                                |
| R-21 | **Kategori agenda menyimpan hex**, dan `ArchitectureTest::'no component reaches for a brand colour by hex value'` memindai pola `[#RRGGBB]` di TSX | Test merah                                                                                                                   | Warna dirender lewat `style={{ borderColor: category.color }}` (atribut style inline, **bukan** utility arbitrary Tailwind), jadi pola `[#…]` tidak muncul di TSX                       |
| R-22 | Memindahkan komponen generik keluar dari `jadwal-misa/components/` (B.2)                                                                           | Menyentuh Phase 04                                                                                                           | Dipicu oleh aturan yang sudah ditegakkan `ArchitectureTest`; dipindahkan **hanya** komponen dengan ≥2 konsumen; test Jadwal Misa wajib hijau                                            |
| R-23 | Garis warna kategori agenda tidakEME memenuhi kontras                                                                                              | Kategori tidak terbaca                                                                                                       | Warna adalah **petunjuk visual saja** (roadmap §5.3); nama kategori selalu ikut, jadi tidak bergantung pada warna                                                                       |

---

# 9. Verification Plan

## 9.1 Gate per tahap

```bash
# Tahap A
php artisan migrate:fresh --seed
php artisan migrate:rollback --step=4 && php artisan migrate --seed
php artisan test tests/Feature/Foundation

# Tahap B–G (setiap tahap)
php artisan wayfinder:generate --with-form
php artisan test tests/Feature/News tests/Feature/Agenda
php artisan test tests/Unit/ArchitectureTest.php
npm run check && npm run types:check && npm run test:unit

# Tahap H
composer ci:check
```

## 9.2 Gate penuh di akhir

```bash
./vendor/bin/pint --dirty --format agent   # WAJIB: aturan pint/core
composer test                               # config:clear + pint --test + phpstan + artisan test
composer ci:check                           # npm check + types:check + test:unit + artisan test
npm run check                               # oxlint type-aware, denyWarnings
npm run types:check                         # tsc --noEmit
npm run test:unit                           # Vitest via vite-plus
npm run build                               # vp build
npm run e2e                                 # app:e2e:prepare + Playwright
```

> **`npm run e2e` wajib, `npx playwright test` telanjang tidak boleh.** Suite Pest
> dan Playwright memakai database yang sama (`website_paroki_hspmtb_test`), dan
> `RefreshDatabase` mengosongkannya. Menjalankan Playwright setelah `php artisan
test` membuat seluruh test admin gagal di `signIn` dengan redirect ke
> `/admin/login`.

## 9.3 Pemeriksaan manual khusus

| #   | Pemeriksaan                                                                                                                                      | Mengapa test tidak cukup                                                                                      |
| --- | ------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------- |
| 1   | Buka `/berita/{slug-draf}`, `/agenda/{slug-unpublished}`, `/agenda/{slug-terhapus}` langsung di browser → **404** dengan halaman error Indonesia | POST-09. Binding berbasis scope adalah 404 alami; ini membuktikannya di browser, bukan hanya di response test |
| 2   | Salin URL detail artikel ke WhatsApp → preview menampilkan judul, deskripsi, gambar                                                              | AC-POST-3 bergantung pada scraper pihak ketiga                                                                |
| 3   | Unduh `.ics` untuk agenda all-day / cancelled / tanpa `end_at` → buka di Google Calendar dan kalender desktop                                    | Kompatibilitas klien kalender tidak bisa disimulasikan                                                        |
| 4   | Agenda 23.30–01.00 WIB → tampil di tanggal **awal** di kalender, dan `.ics` punya `DTEND` di hari berikutnya                                     | AC-EVT-1, kasus lintas tengah malam                                                                           |
| 5   | Buka `/admin/jadwal-misa/lokasi` → group Jadwal Misa terbuka sendiri, Lokasi terlihat di Master Data, tidak ada duplikat                         | Kontrak D-1                                                                                                   |
| 6   | 360 px: daftar agenda adalah default dan tidak terpotong; 768 px dan 1440 px: kalender utuh                                                      | AC-EVT-2                                                                                                      |
| 7   | Sematkan 3 artikel aktif → request ketiga ditolak dengan pesan Indonesia; dua pertama tetap tampil                                               | POST-07                                                                                                       |
| 8   | Post artikel dengan `<script>alert(1)</script>` dan `<img src=x onerror=alert(1)>` → kolom `content` bersih                                      | AC-POST-6                                                                                                     |
| 9   | Buat agenda dengan judul berisi `\r\nBEGIN:VEVENT` → file `.ics` tetap punya **satu** `VEVENT`                                                   | §14.4 "CRLF injection"                                                                                        |
| 10  | Buka artikel yang sama dua kali dalam satu sesi → `view_count` naik **1**                                                                        | D-12                                                                                                          |
| 11  | Post artikel ter-MIME-ganti (PNG berisi HTML) → ditolak                                                                                          | XC-M3                                                                                                         |
| 12  | Ganti featured image → gambar lama masih tampil di tempat lain yang merujuk, file tidak hilang diam-diam                                         | Roadmap §7.6                                                                                                  |
| 13  | Unggah gambar, ubah halaman sebelum varian selesai → status `processing` terlihat, tidak ada gambar rusak                                        | XC-M1                                                                                                         |
| 14  | `npm run build` menghasilkan chunk untuk `admin/berita`, `public/agenda`, dan seterusnya                                                         | Membuktikan `PageChunk::source()` + preload Blade bekerja (ARCHITECTURE Part B §1)                            |

---

# 10. Test Matrix

## 10.1 `tests/Feature/News/PostTest.php` — CRUD + otorisasi + validasi

| Test                                                                                       | AC / aturan            |
| ------------------------------------------------------------------------------------------ | ---------------------- |
| `rejects a guest from creating a post`                                                     | §11.2                  |
| `rejects an authenticated user without the posts.create ability`                           | §11.2                  |
| `lets a super admin create a draft post with a category, title, slug, excerpt and content` | §15.1 butir 1          |
| `stores the sanitized content, dropping script elements and event handlers`                | AC-POST-6, POST-05     |
| `rejects an invalid slug`                                                                  | §14.2                  |
| `rejects a duplicate slug`                                                                 | POST-01                |
| `generates a slug from the title when the field is left empty`                             | POST-01                |
| `keeps the slug when the title of a published post is edited`                              | POST-01                |
| `derives a unique slug with a numeric suffix when the generated slug collides`             | POST-01                |
| `requires a future published_at when the status is scheduled`                              | PRD §9.6               |
| `rejects a scheduled post whose published_at is in the past`                               | PRD §9.6               |
| `stamps published_at with now when a post is published without one`                        | PRD §9.6               |
| `rejects a pinned post when two are already pinned`                                        | POST-07                |
| `allows unpinning the post that is holding a slot`                                         | POST-07                |
| `rejects a media reference that is not ready`                                              | R-11                   |
| `soft deletes a post and hides it from the admin list`                                     | §15.1 butir 12         |
| `updates a post and redirects to the list`                                                 | ARCHITECTURE Part E §4 |
| `asserts the admin list carries id, title, status, category, is_pinned and can`            | Part C §2              |

## 10.2 `tests/Feature/News/PostCategoryTest.php`

`rejects a guest` · `rejects a duplicate slug` · `rejects an invalid hex colour` (kategori
agenda) · `rejects deleting a category that still has posts` · `deletes an unused
category` · `orders the list by sort_order` · `sorts by name, title, created_at` ·
`ignores an unknown sort key instead of failing` · `sends posts_count on the list`
· `the status field defaults to draft`

## 10.3 `tests/Feature/News/PublicPostPageTest.php`

| Test                                                                                | AC                 |
| ----------------------------------------------------------------------------------- | ------------------ |
| `lists published posts and hides drafts, scheduled, archived and soft-deleted ones` | POST-02, POST-09   |
| `shows a published post whose published_at is not in the future`                    | POST-02            |
| `answers 404 for a draft post even when the slug is known`                          | POST-09, AC-POST-5 |
| `answers 404 for a scheduled post before its published_at`                          | POST-09            |
| `answers 404 for an archived post`                                                  | POST-09            |
| `answers 404 for a soft-deleted post`                                               | POST-09            |
| `pins at most two posts in the featured area`                                       | POST-07            |
| `filters by category`                                                               | AC-POST-4          |
| `searches the title`                                                                | AC-POST-4          |
| `searches the body text and does not match tag names`                               | AC-POST-4, m-8     |
| `sends the Open Graph title, description and image`                                 | POST-10, AC-POST-3 |
| `sends a fallback description from the excerpt when meta_description is empty`      | §7.7               |
| `sends the scripture reference above the body for a Renungan article`               | §3.2               |
| `lists related posts from the same category and excludes the post itself`           | §3.2               |
| `does not leak a draft through related posts, search or metadata`                   | R-16               |

## 10.4 `tests/Feature/News/ScheduledPublishingTest.php`

`publishes a scheduled post once its published_at arrives` (dengan `Carbon::setTestNow()`)
· `leaves a scheduled post alone before its published_at` · `is idempotent when run twice`
· `does not publish a draft` · `does not publish an archived post` · `is registered in the
schedule` · `produces the same result whether the time zone is UTC or WIB`

## 10.5 `tests/Feature/News/ViewCountTest.php`

`increments the view count on the detail page` · `increments once per session for the same
article` · `ignores a bot user agent` · `does not accept view_count from the request body`

## 10.6 `tests/Feature/News/PostPreviewTest.php`

`lets an authorized admin preview a draft` · `rejects a guest from the preview route` ·
`sends noindex on the preview page` · `does not change the post`

## 10.7 `tests/Feature/Agenda/EventTest.php` · `DuplicateEventTest.php` · `EventCategoryTest.php`

`rejects end_at before start_at` (EVT-01, AC-EVT-6) · `accepts end_at equal to start_at` ·
`accepts an event with no end_at` · `requires no clock for an all-day event` ·
`accepts a free-text location name` · `prefers location_id over location_name` (EVT-06) ·
`allows an event to reference a deactivated location` · `rejects deleting a category that
still has events` · `rejects an invalid hex colour` · `duplicates an event with a new slug` ·
`leaves the duplicate unpublished` · `soft deletes an event` · `keeps a cancelled and a
postponed event visible` · `converts a WIB wall clock to UTC at the boundary` ·
`does not shift a 23.30 WIB event to the wrong day`

## 10.8 `tests/Feature/Agenda/PublicEventPageTest.php`

`lists only published events` · `hides an unpublished event` · `hides a soft-deleted one` ·
`lists an upcoming event in list mode` · `shows past events when browsing a past month`
(EVT-02) · `marks dates that have events` · `filters by category in both modes` (AC-EVT-5) ·
`keeps cancelled events visible with a Dibatalkan label` (EVT-04, AC-EVT-4) · `keeps
postponed events visible with a Ditunda label` · `renders an all-day event without a clock`
· `renders an event with no poster` · `shows an event whose location was soft-deleted as
the free-text name` · `shows an event that crosses midnight on its starting date` (AC-EVT-1)
· `sends an Event structured data payload` (EVT-08) · `answers 404 for a non-public event`
· `ignores an out-of-range month instead of failing`

## 10.9 `tests/Feature/Agenda/IcsTest.php`

`responds with a calendar content type and an attachment filename` · `uses CRLF line
endings` · `carries a stable UID across two downloads` · `emits DTSTART in UTC with a Z
suffix` · `emits DTEND only when the event has one` · `uses VALUE=DATE for an all-day
event` · `sets DTEND to the following day for an all-day event` · `sets STATUS:CANCELLED
for a cancelled event` · `sets SUMMARY from the title` · `sets LOCATION from the master
location when present and the free text otherwise` · `sets DESCRIPTION as plain text with
the markup removed` · `escapes a newline and a semicolon in the title` ·
`cannot be made to inject a second VEVENT through the title` · `answers 404 for an
unpublished event` · `answers 404 for a soft-deleted event` · `does not accept a slug that
is not one`

## 10.10 `tests/Feature/MassSchedule` (regresi)

Seluruh suite Phase 04 **wajib tetap hijau** setelah pemindahan komponen §B.2 dan
perubahan sidebar §H.1.

## 10.11 `tests/e2e/news-agenda.spec.ts`

Menulis artikel dari awal sampai terbit · melihatnya di `/berita` · membukanya ·
mengubahnya · menghapusnya dengan dialog konfirmasi · membuat agenda dengan
`is_all_day` · mempublikasikannya · menemukannya di kalender · mengunduh `.ics` ·
menjalankan filter kategori di kedua mode

---

# 11. Acceptance Criteria

## 11.1 Berita — §15.1

| Butir                                                                                  | Dijamin oleh |
| -------------------------------------------------------------------------------------- | ------------ |
| Super Admin dapat membuat artikel dengan kategori, judul, slug, ringkasan, isi, gambar | §10.1        |
| Artikel dapat disimpan sebagai draft dan dipratinjau                                   | §10.1, §10.6 |
| Artikel dapat diterbitkan langsung atau dijadwalkan                                    | §10.1        |
| Artikel scheduled terbit otomatis                                                      | §10.4        |
| Pinned dibatasi maksimal dua                                                           | §10.1, §10.3 |
| Pencarian, filter kategori, status admin, pagination                                   | §10.1, §10.3 |
| Draft/scheduled/archived tidak bisa dibaca publik                                      | §10.3        |
| HTML berbahaya dibersihkan di server                                                   | §10.1        |
| Gambar utama divalidasi dan alt text dikelola                                          | §10.1        |
| Metadata Open Graph pada detail benar                                                  | §10.3        |
| Soft delete berfungsi dan konten terhapus tidak tampil                                 | §10.1, §10.3 |
| Kategori dikelola dan kategori terpakai terlindungi                                    | §10.2        |

## 11.2 Agenda — §15.2

| Butir                                                                                                | Dijamin oleh |
| ---------------------------------------------------------------------------------------------------- | ------------ |
| Super Admin dapat membuat, mengubah, menggandakan, memublikasikan, membatalkan, menunda, soft delete | §10.7        |
| Kategori agenda dapat dikelola                                                                       | §10.7        |
| Validasi waktu kegiatan berjalan benar                                                               | §10.7        |
| Agenda dapat memakai master lokasi atau nama bebas                                                   | §10.7        |
| Daftar dan kalender publik menampilkan agenda yang dipublikasikan                                    | §10.8        |
| Agenda cancelled/postponed tetap tampil dengan label benar                                           | §10.8        |
| Pengunjung dapat membuka detail agenda                                                               | §10.8        |
| Google Calendar link bekerja                                                                         | §9.3 #2, E2E |
| Unduhan `.ics` valid dan hanya untuk agenda publik                                                   | §10.9        |
| Agenda nonpublik dan soft-deleted tidak muncul di publik                                             | §10.8, §10.9 |
| Kategori yang masih dipakai tidak dapat dihapus                                                      | §10.7        |

## 11.3 Teknis dan desain — §15.3

| Butir                                                        | Dijamin oleh                                                                     |
| ------------------------------------------------------------ | -------------------------------------------------------------------------------- |
| Tidak ada `community_id` pada `posts` dan `events`           | Migrasi + test yang mengecek `Schema::hasColumn('posts','community_id')` → false |
| Tidak ada relasi `Post`/`Event` ke `Community`               | Model tidak punya relasi itu; tidak ada model `Community` di repo sama sekali    |
| Sidebar punya group Master Data dengan 3 menu                | Kontrak C-7                                                                      |
| Menu Lokasi memakai route existing tanpa duplikasi           | §H.1                                                                             |
| Seluruh mutasi dilindungi autentikasi dan otorisasi          | §10.1, §10.7                                                                     |
| UI admin dan publik mengikuti `DESIGN.md`                    | Review visual; token semantik, bukan hex (R-21)                                  |
| Halaman responsif dan pesan validasi Bahasa Indonesia        | E2E + §9.3 #6                                                                    |
| Test, typecheck, lint, build dijalankan dan hasilnya dicatat | §14                                                                              |
| Tidak ada perubahan di luar cakupan tanpa alasan             | Review diff per tahap                                                            |

## 11.4 Definition of Done — roadmap §19

1. Semua kriteria §15 terpenuhi
2. Tidak ada relasi `posts`/`events` ke `communities`
3. CRUD admin, halaman publik, kategori, scheduler, dan ICS berfungsi
4. Otorisasi diterapkan di backend
5. Sanitasi HTML dan validasi upload teruji
6. Data nonpublik tidak dapat diakses dari halaman publik atau ICS
7. UI responsif dan konsisten dengan `DESIGN.md`
8. Test, lint, typecheck, static analysis, build sudah dijalankan; hasil aktual dicatat
9. Tidak ada regresi yang diketahui pada Phase 04
10. Dokumentasi yang terdampak diperbarui

---

# 12. Yang Sengaja Tidak Dikerjakan

Roadmap §17. Daftar ini eksplisit agar tidak ada yang "dilengkapi sambil jalan".

| Tidak dikerjakan                                                | Alasan                                                                                                                      |
| --------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------- |
| Relasi berita/agenda ke `communities`                           | Roadmap §5. decisively melarang                                                                                             |
| Pengelolaan komunitas, jenis, wilayah, lingkungan               | Phase 07                                                                                                                    |
| RSVP / pendaftaran peserta agenda                               | Roadmap §17                                                                                                                 |
| Kegiatan berulang otomatis                                      | EVT-05                                                                                                                      |
| Komentar, like, forum                                           | Roadmap §17                                                                                                                 |
| Workflow persetujuan editor atau role selain `super_admin`      | POST-06                                                                                                                     |
| UI manajemen role/permission                                    | Roadmap §17; `ArchitectureTest::'there is no role or permission management controller'`                                     |
| Newsletter / notifikasi email atau WhatsApp                     | Roadmap §17                                                                                                                 |
| Pencarian global lintas modul                                   | Roadmap §17                                                                                                                 |
| Integrasi kalender pihak ketiga selain Google Calendar + `.ics` | Roadmap §17                                                                                                                 |
| REST API terpisah                                               | Roadmap §1.2                                                                                                                |
| Refactor besar pada layout atau fitur lain                      | Roadmap §17                                                                                                                 |
| Pengisian konten produksi                                       | Roadmap §17; `AGENTS.md`                                                                                                    |
| **`sitemap.xml`**                                               | NFR-SEO mewajibkannya tetapi §15 tidak meletakkannya sebagai AC, dan tidak ada endpoint maupun package-nya. **→ §13, m-10** |
| **Pustaka media penuh**                                         | `D-24` menandainya sebagai pekerjaan tersendiri; keputusan D-6 hanya membangun picker untuk satu field                      |
| **`posts.content_text`**                                        | Roadmap §5.2 melarang tanpa keputusan eksplisit → §13, m-8                                                                  |
| **Cache untuk jadwal artikel**                                  | XC-C1 sudah ditunda di D-35; `NextMassResolver` tetap dihitung per request                                                  |
| **Tambah kolom `language` ke `posts`**                          | D-42 menghapus `mass_schedules.language`; tidak ada kebutuhannya di PRD §9.6                                                |

---

# 13. Keputusan yang Masih Menunggu Konfirmasi

Enam keputusan §1 sudah terjawab. Yang berikut **tidak** muncul di dokumen mana pun
dan tidak dapat diputuskan dari sana. Semuanya punya rekomendasi; implementasi
dapat berjalan tanpa menjawabnya, tapi §13 memengaruhi Tahap A dan G.

### m-8 — `content_text` untuk pencarian isi artikel

**Masalah.** PRD D-11 dan roadmap §7.4/AC-POST-4 meminta pencarian pada **isi**
artikel. `LIKE '%…%'` pada kolom HTML cocok dengan nama tag (`<p>`, `<strong>`,
`class=`), sehingga pencarian "kelas" mengembalikan artikel yang tidak pernah
menggunakan kata itu.

| Opsi                                                                                                                                               | Konsekuensi                                                                                                                                                                                                                                                                 |
| -------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **A. Tambah kolom `content_text text nullable` + `HtmlSanitizer::toPlainText()`, lalu `LIKE` pada `title`/`excerpt`/`content_text` (Rekomendasi)** | Memenuhi AC-POST-4 persis. Butuh dua test yang membuktikan bahwa kolom itu perlu (satu: kata yang hanya ada di dalam tag tidak cocok; dua: pencarian pada teks isi benar-benar bekerja), sesuai syarat roadmap §5.2 "keputusan eksplisit". Kolom bertambah ±8% ukuran tabel |
| B. `LIKE` hanya pada `title` + `excerpt`                                                                                                           | Lebih murah, **mendiviasi AC-POST-4** yang eksplisit menyebut "pencarian judul dan isi"                                                                                                                                                                                     |
| C. FULLTEXT MySQL pada `(title, excerpt, content)` seperti PRD §9.6 menyebut opsional                                                              | Butuh indeks FULLTEXT yang Ukurannya sama dengan tabelnya dan tidak cocok dengan pola `LIKE '%…%'`; kata <4 karakter tidak dicocokkan                                                                                                                                       |

**Rekomendasi: A.** Roadmap mengizinkan, selama ada keputusan eksplisit — dan dua
test tersebut persis "kebutuhan yang terukur".

### m-9 — JSON-LD vs `ArchitectureTest::'only the error page renders sanitized html'`

**Masalah.** Keputusan D-5 necessitates `dangerouslySetInnerHTML` untuk
`<script type="application/ld+json">`. Test itu memindai **seluruh** `resources/js`
dan mengecualikan hanya file bernama `rich-text.tsx`, jadi ia akan merah.

| Opsi                                                                    | Konsekuensi                                                                                                                     |
| ----------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| **A. Perbarui test agar mengecualikan satu file JSON-LD** (Rekomendasi) | Nama file `json-ld.tsx` di-`str_contains` seperti `rich-text.tsx`. Satu baris perubahan, eksplisit                              |
| B. Render JSON-LD sebagai teks biasa melalui `dangerouslySetInnerHTML`  | -area yang sama                                                                                                                 |
| C. JSON-LD dikirim server dan di-render lewat `<script>` tanpa React    | Menghapus interactivity React sepenuhnya; React tidak punya cara lain menaruh konten `<script>` tanpa `dangerouslySetInnerHTML` |
| D. Tunda structured data ke Phase 10                                    | Konsisten dengan D-26; §15 tidak menetapkan structured data sebagai AC, jadi tidak ada yang gagal terpenuhi                     |

**Rekomendasi: A**, dengan nama file yang jelas (`components/json-ld.tsx`) supaya
pengecualian itu terbaca sebagai keputusan, bukan sebagai pelonggaran. Kalau Anda
memilih C atau D, keputusan D-5 berubah dan `resources/views/app.blade.php` tidak
perlu disentuh sama sekali.

### m-10 — Sitemap

PRD NFR-SEO: "`sitemap.xml` + `robots.txt` otomatis". Roadmap §13.1: "Sitemap hanya
memuat artikel yang publik dan agenda yang dipublikasikan" — kalimat yang mengasumsikan
sudah ada. Reality: tidak ada sitemap maupun robots route di repository, dan
`robots.txt` pun tidak ada (yang ada hanya `<meta name="robots">`).

| Opsi                                                     | Konsekuensi                                                                  |
| -------------------------------------------------------- | ---------------------------------------------------------------------------- |
| **A. Tunda ke Phase 09/10 (Rekomendasi)**                | Tidak ada dependency baru. NFR-SEO tetap tercatat sebagai gap yang diketahui |
| B. Controller `.ics`-like tanpa package (`/sitemap.xml`) | Sekitar 80 baris, tanpa dependency. Hanya memuat URL yang publik             |
| C. Tambah `spatie/laravel-sitemap`                       | Melanggar §2.1 roadmap dan DESIGN.md §7 untuk satu route                     |

**Rekomendasi: A.** Yang penting untuk Phase 05 adalah **tidak membocorkan konten
nonpublik**, dan itu sudah dijamin oleh scope. Sitemap yang benar adalah pekerjaan
SEO tersendiri. Dicatat sebagai gap yang diketahui di `docs/DECISIONS.md`.

---

# 14. Status

```
Keputusan (D-1 … D-6):
  D-1 Lokasi pindah ke group Master Data              ✓ dijawab
  D-2 Konflik community_id → DECISIONS.md saja       ✓ dijawab
  D-3 Unique slug tanpa withoutTrashed()              ✓ dijawab
  D-4 RichTextEditor pindah ke components/            ✓ dijawab
  D-5 JSON-LD di <Head>                               ✓ dijawab
  D-6 Media lewat tabel media + kolom media_id        ✓ dijawab

Pilihan minor (m-1 … m-7):
  m-1 Schedule di bootstrap/app.php                   ✓ diasumsikan
  m-2 12 permission satu kata                          ✓ diasumsikan
  m-3 Nama file halaman tambah/ubah                   ✓ diasumsikan
  m-4 Nama route                                       ✓ diasumsikan
  m-5 Duplikasi agenda via POST                        ✓ diasumsikan
  m-6 Tidak ada seeder konten                          ✓ diasumsikan
  m-7 Model bernama Event                               ✓ diasumsikan

Menunggu konfirmasi (tidak menghalangi):
  m-8  posts.content_text untuk pencarian isi          → rekomendasi A
  m-9  Pengecualian ArchitectureTest untuk JSON-LD      → rekomendasi A
  m-10 Sitemap ditunda ke Phase 09/10                   → rekomendasi A

Branch:
  feat/phase-05-news-agenda  (dari main @ f40d25b)

Ringkasan:
  // Dibuat   : 63 backend + 16 frontend + 12 test = 91 file baru
  // Diubah   : 26 entri (±28 file; dua baris pageholders public ditulis ulang)
  // Dipindah : 1 (rich-text-editor.tsx → components/)
  // Dihapus  : 0
  // Dependency baru : 0
  // Perubahan config wajib : 2 (config/inertia.php, bootstrap/app.php)

Status implementasi:
  BELUM ADA KODE YANG DITULIS.
  Menunggu persetujuan untuk memulai Tahap A.
```

---

> **Dokumen ini adalah rencana; implementasinya ada di branch
> `feat/phase-05-news-agenda`.**
>
> Catatan yang paling sering berguna nanti ada di §13 (tiga hal yang belum diputuskan)
> dan di `docs/DECISIONS.md` D-43 … — khususnya pengakuan bahwa **isi berita dan
> agenda paroki belum ada** (keputusan m-6, sejalan dengan D-34 untuk Jadwal Misa),
> bahwa **ADR-05 §9.6/§9.7 masih menyebut `community_id`** yang sengaja tidak
> diimplementasikan (keputusan D-2), dan bahwa **sitemap belum ada** (§13 m-10).
