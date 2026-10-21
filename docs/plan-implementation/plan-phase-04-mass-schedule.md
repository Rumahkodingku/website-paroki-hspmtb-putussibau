# Implementation Plan — PHASE 04

> **Project:** Website Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB)
> Putussibau\
> **Phase:** 04 — Mass Schedule (Jadwal Misa)\
> **Status:** Dieksekusi — branch `feat/phase-04-mass-schedule`\
> **Spesifikasi:** `docs/roadmap/phase-04-mass-schedule.md`\
> **Sumber kebenaran:** `PRD.md` → `ARCHITECTURE.md` → `DESIGN.md` → `DECISIONS.md`

Dokumen ini adalah **rencana implementasi terperinci** untuk Phase 04. Dokumen
roadmap Phase 04 menjelaskan _apa_ yang harus ada; dokumen ini menjelaskan _bagaimana_
perubahannya, dalam urutan apa, dan bagaimana diverifikasinya.

> Dokumen ini disusun setelah audit codebase terhadap `main` pada commit `4e398ac`
> dan **8 pertanyaan keputusan yang sudah dijawab pengguna**. Seluruh rencana di
> sini sudah diimplementasikan; lihat §14 untuk hasilnya.

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

Delapan pertanyaan diajukan sebelum penyusunan plan ini. Jawaban berikut mengikat
seluruh isi dokumen.

| #   | Pertanyaan                                      | Keputusan                                                                                                                                                  | Alasan yang dipakai                                                                                                                                                                                                                                                                                                                                                      |
| --- | ----------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| D-1 | Soft delete pada `locations` / `special_masses` | **Tidak ada soft delete.** Hanya `is_active`                                                                                                               | Roadmap Phase 04 §4 menyatakan "jangan menambahkan soft delete jika kebijakan modul sudah cukup menggunakan `is_active`", dan §5.6 melarang endpoint hapus permanen. Tidak ada endpoint hapus sama sekali, jadi `deleted_at` akan jadi kolom mati — antipola yang D-24 kritik sebagai "permission nothing reads yet". Dicatat sebagai divergensi dari PRD §9.5 + ADM-05. |
| D-2 | Bentuk tabel `locations`                        | **`slug` (unique) + `notes`; tanpa `sort_order`**                                                                                                          | PRD §9.5 punya `sort_order` tetapi tidak punya `slug`/`notes`; roadmap §4.1 sebaliknya, dan §5.2 mewajibkan validasi slug. `slug` membuat filter `?lokasi=stasi-x` terbaca manusia dan bisa dibagikan. `sort_order` dihapus karena tidak ada satu pun requirement yang mengurutkan lokasi.                                                                               |
| D-3 | Bentuk tabel `schedule_notices`                 | **Ikuti roadmap §4.4**                                                                                                                                     | PRD §9.5 hanya menyebut `message`, `starts_at` (nullable), `ends_at`, `is_active`. Roadmap §2.1 eksplisit meminta "CRUD pemberitahuan perubahan jadwal dengan rentang tampil" dan §6.5 meminta "target umum atau target lokasi/jadwal/misa khusus". Bentuk PRD §9.5 tidak dapat menyediakan target. Divergensi dicatat.                                                  |
| D-4 | Struktur URL admin                              | **`/admin/jadwal-misa` = ringkasan + daftar Misa Rutin**; `/admin/lokasi`, `/admin/misa-khusus`, `/admin/pemberitahuan-jadwal` = 3 halaman daftar terpisah | PRD §5.2 memetakan 4 URL ke 4 fungsi; roadmap §6.1 meminta satu halaman ringkasan. Opsi ini memenuhi keduanya: URL PRD tetap persis, tidak ada redirect, 13 halaman Inertia dalam satu feature.                                                                                                                                                                          |
| D-5 | Target `schedule_notices`                       | **Saling eksklusif** — 0 (umum) atau tepat 1 dari 3 FK                                                                                                     | Roadmap §4.4: "Validasi harus memastikan referensi yang diisi benar-benar ada dan relasi konsisten." Tiga target yang menumpuk tidak punya semantik; label di banner notice jadi ambigu. Ditegakkan dengan `Rule::prohibitedWith()`.                                                                                                                                     |
| D-6 | Cara aktif/nonaktif entitas                     | **`is_active` sebagai field biasa di form `update`.** Tidak ada endpoint `aktifkan`/`nonaktifkan`                                                          | Roadmap §5.6 menyebut "deactivate/activate" sebagai endpoint, tetapi §6 meminta "perubahan sekecil mungkin". Field biasa memenuhi kebutuhan tanpa menambah 8 endpoint. Peringatan MASS-07 tetap ada di frontend lewat `active_schedules_count` dari `withCount()`.                                                                                                       |
| D-7 | Data contoh untuk pengembangan                  | **Factory + `MassScheduleDemoSeeder` terpisah, tidak dipanggil `DatabaseSeeder`**                                                                          | Roadmap §5.1 melarang menaruh jadwal resmi yang belum dikonfirmasi ke seeder production. D-14 mengosongkan `DatabaseSeeder`. Seeder demo harus dipanggil manual agar tidak pernah ikut `migrate:fresh --seed`.                                                                                                                                                           |
| D-8 | URL Maps dari koordinat                         | **Disusun di server**, di `PublicLocationResource`                                                                                                         | Roadmap §8: "Periksa tidak ada … perhitungan bisnis penting yang hanya terjadi di client." React menerima satu field `mapsUrl` yang sudah jadi.                                                                                                                                                                                                                          |

Tiga pilihan minor diasumsikan sesuai rekomendasi dan dapat dibatalkan kapan saja:

| #   | Pilihan minor              | Nilai                                                             |
| --- | -------------------------- | ----------------------------------------------------------------- |
| m-1 | Nama file halaman          | `tambah.tsx` / `ubah.tsx` (Indonesia, mengikuti URL)              |
| m-2 | Granularitas permission    | 12 permission `resource.action` **satu kata**, tanpa tanda hubung |
| m-3 | Penempatan formatter waktu | `resources/js/lib/datetime.ts`                                    |

Nomor D-1 … D-8 di atas adalah **nomor keputusan lokal dokumen ini**, bukan nomor
`D-xx` di `docs/DECISIONS.md`. Penomoran resmi untuk Phase 04 dimulai dari
**D-34**, karena D-01 … D-33 sudah terpakai.

---

# 2. Kondisi Codebase Saat Ini

Audit dilakukan terhadap `main` pada commit `4e398ac`
(`Merge pull request #3 from Rumahkodingku/feat/text-typography-component`).

## 2.1 Fondasi yang sudah ada dan cukup — **diaudit, bukan ditulis ulang**

| Area                 | Status aktual                                                                                                                                                                                     | File                                               |
| -------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------- |
| Timezone             | `app.timezone = 'UTC'` + `app.display_timezone = 'Asia/Pontianak'`, sudah dikirim sebagai shared prop                                                                                             | `config/app.php`, `HandleInertiaRequests.php:83`   |
| Carbon               | `Date::use(CarbonImmutable::class)` — semua tanggal immutable                                                                                                                                     | `AppServiceProvider.php:120`                       |
| Strict mode          | `Model::shouldBeStrict(! isProduction())` — lazy load = exception                                                                                                                                 | `AppServiceProvider.php:159`                       |
| Gate `/admin`        | `['auth', 'verified', EnsureAccountIsActive, EnsureAdminAccess]` + `Gate::before` untuk `super_admin` (mengembalikan `null`, bukan `false`)                                                       | `routes/web.php:137`, `AppServiceProvider.php:110` |
| Base Controller      | **Kosong.** Tidak ada trait `AuthorizesRequests` → `$this->authorize()` **tidak tersedia**; wajib `Gate::authorize()` atau `FormRequest::authorize()`                                             | `app/Http/Controllers/Controller.php`              |
| Permission           | 15 permission sudah ter-seed: `admin.access`, `dashboard.view`, `settings.{view,update}`, `media.{view,create,delete}`, `users.{view,create,update,disable}`, `posts.{view,create,update,delete}` | `PermissionSeeder.php:41`                          |
| Validasi `id`        | **Lengkap** (111 kunci, D-02), dengan dua test penjaga. `attributes` punya 25 entri                                                                                                               | `lang/id/validation.php`                           |
| Flash                | `Inertia::flash('toast', [...])` + `useFlashToast()` di `ui/sonner.tsx` — **bukan** `->with('success')`                                                                                           | `SettingsController.php:54`                        |
| Form pattern         | `useForm` + `post(Action.update().url)` di pengaturan; `<Form {...action.form()}>` di akun                                                                                                        | `features/admin/pengaturan/pages/index.tsx`        |
| Layout resolution    | `app.tsx` memilih layout dari nama komponen; halaman hanya mengirim `Component.layout = { breadcrumbs }` — **halaman tidak pernah meng-import layout**                                            | `resources/js/app.tsx`, ARCHITECTURE.md Part B §3  |
| Primitive siap pakai | `Table`, `Pagination`, `Dialog`, `Tabs`, `Select`, `Switch`, `Badge`, `Skeleton`, `DropdownMenu`                                                                                                  | `resources/js/components/ui/*`                     |
| Komponen siap pakai  | `ConfirmDialog` (Utama dialog, **nol konsumen**), `EmptyState`, `InputError`, `ParishCard`, `PublicSection`, `SectionHeading`                                                                     | `resources/js/components/*`                        |
| `Text`               | 16 variant token tipografi + `as` untuk elemen; `cn()` sudah terdaftar ke tailwind-merge (D-33)                                                                                                   | `components/text.tsx`                              |
| Timezone di frontend | shared prop `displayTimezone` sudah tersedia di setiap halaman                                                                                                                                    | `types/index.ts`                                   |
| Testing              | Pest 4.7 (Pest.php punya `seedRolesAndPermissions()`, `superAdmin()`, `truecolor()`), Vitest via `vite-plus` di `tests/js/`, Playwright di `tests/e2e/`                                           | `tests/`                                           |

## 2.2 Yang ada tapi belum selesai — pekerjaan utama Phase 04

| Area                                                      | Kekurangan yang ditemukan                                                                                                                                                                     |
| --------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Route publik `/jadwal-misa`                               | `routes/web.php:58` masih `Route::inertia('/jadwal-misa', 'public/jadwal-misa')->name('jadwal-misa.index')` — placeholder tanpa query, props, atau controller                                 |
| Halaman publik                                            | `resources/js/features/public/jadwal-misa/pages/index.tsx` hanya 26 baris `[ISI: …]`. Komponen DESIGN.md §Cards `MassScheduleCard` **belum ada**                                              |
| Sidebar admin                                             | `app-sidebar.tsx:54` — entri `{ title: 'Jadwal Misa', icon: CalendarDays }` **tanpa `href`**, jadi ia render sebagai baris disabled (`aria-disabled="true"` + penanda "belum")                |
| `Paginated<T>`                                            | Ada di `types/pagination.ts` dengan docblock "nothing reads it yet" — **Phase 04 adalah konsumen pertamanya**                                                                                 |
| `ui/table.tsx`, `ui/pagination.tsx`, `confirm-dialog.tsx` | Scaffolding, nol konsumen. Tidak ada satu pun halaman daftar di aplikasi                                                                                                                      |
| `@media print`                                            | **Tidak ada sama sekali** di `resources/css/app.css` (599 baris), padahal PRD XC-B2 mewajibkannya untuk halaman Jadwal Misa                                                                   |
| Permission                                                | `PermissionSeeder` docblock menyatakan permission per modul "added when the module is built" — 12 permission Jadwal Misa belum ada                                                            |
| `lang/id/validation.php` `attributes`                     | Tidak punya `day_of_week`, `start_time`, `end_time`, `language`, `notes`, `maps_url`, `latitude`, `longitude`, `start_at`, `end_at`, `starts_at`, `ends_at`, `message`, `type`, `location_id` |

## 2.3 Yang belum ada dan harus dibuat

4 tabel · 2 enum · 4 model · 4 factory · 4 policy · 11 form request · 1 service
(`NextMassResolver`) · 1 DTO (`NextMass`) · 1 action · **10 `JsonResource`** ·
**6 controller** · 17 route · 13 halaman Inertia admin · 6 komponen publik ·
1 `lib/datetime.ts` · 1 blok `@media print` · 1 file `lang/id/mass-schedule.php` ·
1 demo seeder · 12 file test.

## 2.4 Konvensi yang harus diikuti — bukan landmark baru

| Konvensi                      | Bentuk persisnya di repo                                                                                                                                                                         |
| ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Fillable                      | `#[Fillable(['key', ...])]` **atribut**, bukan properti — `ArchitectureTest.php:119` mengecek atributnya                                                                                         |
| Migration                     | `return new class extends Migration`, `up()`/`down()`, `$table->timestamps()` sebelum indeks, `Schema::dropIfExists` dalam urutan terbalik, komentar inline untuk setiap kolom nullable & indeks |
| Enum → kolom                  | `$table->enum('status', MediaStatus::values())` — enum itu yang menentukan kolom DB (`MediaController`)                                                                                          |
| Model                         | PHPDoc `@property` per kolom, `casts()`, relasi dengan `@return BelongsTo<Location, $this>`                                                                                                      |
| Scope                         | `public function scopeActive(Builder $query): Builder` dengan `@param Builder<Location> $query` (`User::scopeActive`)                                                                            |
| Factory                       | state named (`UserFactory::unverified()`, `::inactive()`)                                                                                                                                        |
| Form Request                  | `authorize()` = satu-satunya titik otorisasi untuk `store`/`update`; `prepareForValidation()` untuk normalisasi; `messages()` untuk teks Indonesia                                               |
| Otorisasi selain store/update | `Gate::authorize('ability', $model)` di controller (`SettingsController::edit()`)                                                                                                                |
| Assertion database            | **Tidak ada `assertDatabaseHas` di repo ini.** Semua pakai query Eloquent ke `expect()`                                                                                                          |
| Assertion Inertia             | `use Inertia\Testing\AssertableInertia as Assert;` + closure `fn (Assert $page)`                                                                                                                 |
| Nama route                    | Wayfinder mengelompokkan per segmen sebelum titik: `mass-schedules.index` → `@/routes/mass-schedules` (export `index`). **Import path dibaca dari output `wayfinder:generate`, bukan ditebak**   |
| Import halaman lain           | `import { index } from '@/routes/mass-schedules'`; hanya **nama route**, bukan `@/actions`                                                                                                    |
| Test Vitest                   | API selalu di-import eksplisit dari `vite-plus/test` (`globals: false`); render lewat `renderWithInertia()` dari `tests/js/support/inertia.tsx`; query berbasis role/label                       |
| Test E2E                      | Nama test **bahasa Indonesia**; `page.goto('/path')`; `resetLoginRateLimit(); await signIn(page)` di `beforeEach`; selector `data-test`                                                          |

## 2.5 Inkonsistensi dokumen yang ditemukan saat audit

Bukan blocker, tapi harus dikoreksi di Tahap E. **Semuanya sudah dikoreksi**;
kolom status menandai mana yang sudah tertutup.

| Temuan                                                                    | Lokasi                                | Status |
| ------------------------------------------------------------------------- | ------------------------------------- | ------ |
| `AGENTS.md` menulis "Current: **Phase 1**"                                | `AGENTS.md:14`                        | ✅ D-39 |
| `AGENTS.md` menulis "role `super_admin` + **12 permissions**", aktual 15  | `AGENTS.md:139`                       | ✅ D-39 |
| `docs/roadmap/README.md` masih `Phase 3 🟡 Current`, `Phase 4 ⚪ Planned` | `roadmap/README.md:48-49`             | ✅ D-39 |
| **`docs/roadmap/phase-04-mass-schedule.md` belum di-commit**              | `git status` → `?? ...phase-04-...md` | ✅ D-39 |
| `docs/roadmap/phase-02-*.md` tidak ada di folder                          | Phase 02 sudah selesai (D-19)         | ⚠️ dibiarkan |

Yang terakhir dibiarkan dengan sengaja: Phase 02 selesai dan tested, tetapi
dokumen roadmap-nya tidak pernah masuk repo. Menulis ulang roadmap Phase 02
dari memori setelah fact adalah cara yang salah untuk membuat sejarah; lebih
baik Phase 05 menulis ulang roadmap-nya sendiri saat mulai.

---

# 3. Kontrak yang Harus Dijaga

Bagian ini adalah daftar hal yang **bukan** pekerjaan Phase 04, melainkan
batasan yang, begitu dilanggar, akan membuat build gagal atau memicu regresi.

## 3.1 Empat hal yang harus sepakat saat menambah page

| Mengetahui                               | Mekanisme                                           | Otomatis?                 |
| ---------------------------------------- | --------------------------------------------------- | ------------------------- |
| `resources/js/app.tsx`                   | `import.meta.glob('./features/*/*/pages/**/*.tsx')` | ✅                        |
| `app/Support/PageFinder.php`             | membaca filesystem                                  | ✅                        |
| `app/Support/PageChunk.php`              | membaca filesystem                                  | ✅                        |
| **`config/inertia.php` → `pages.paths`** | **daftar literal**                                  | ❌ **WAJIB diisi manual** |

13 halaman Inertia baru berarti **satu** entry baru di `config/inertia.php`:
`resource_path('js/features/admin/jadwal-misa/pages')`. Arch test `the inertia page
finder and the blade lookup find the same files` (`ArchitectureTest.php:504`)
menelusuri `PageChunk::all()` dan membandingkannya dengan daftar itu. Tanpa
penambahan, test gagal.

Entry publik `resource_path('js/features/public/jadwal-misa/pages')` **sudah ada**.

## 3.2 Arch test yang akan bereaksi

| Asersi                                                             | Lokasi | Yang dilindungi                                                                                                                                |
| ------------------------------------------------------------------ | ------ | ---------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------- |
| `there is no repository layer`                                     | `:49`  | `app/Repositories`, `app/Domain`, `RepositoryServiceProvider`                                                                                  |
| `services do not depend on the http layer`                         | `:65`  | `App\Services` **dilarang** memakai `Illuminate\Http\Request` → **`NextMassResolver` wajib menerima waktu lewat parameter, bukan `request()`** |
| `services do not depend on controllers`                            | `:74`  | Service tidak memanggil controller                                                                                                             |
| `every model declares its fillable columns explicitly`             | `:119` | `App\Models` harus punya atribut `Illuminate\Database\Eloquent\Attributes\Fillable`                                                            |
| `models do not bypass eloquent with the query builder facade`      | `:134` | `App\Models` **dilarang** memakai `Illuminate\Support\Facades\DB`                                                                              |
| `a feature does not import another feature`                        | `:313` | `features/admin/jadwal-misa` **dilarang** meng-import `features/public/jadwal-misa` atau feature lain                                          |
| `hooks and lib import nothing above them`                          | `:344` | `lib/datetime.ts` tidak boleh meng-import `features/` atau `components/`                                                                       |
| `no frontend directory imports one that does not exist`            | `:359` | 9 direktori yang dikenal                                                                                                                       |
| `the inertia page finder and the blade lookup find the same files` | `:504` | **WAJIB** entry `config/inertia.php`                                                                                                           |
| `no two features claim the same page name`                         | `:462` | nama page membawa audience + feature, jadi Mustahil bentrok secara struktural                                                                  |
| `only the error page renders sanitized html on the frontend`       | `:556` | **hanya** `rich-text.tsx` boleh `dangerouslySetInnerHTML`                                                                                      |
| `navigation never hard-codes an application URL`                   | `:596` | **seluruh** file frontend, regex `href\s\*(=                                                                                                   | :)\s*\{?\s*['"]/`→`href="/admin/jadwal-misa"` **gagal** |
| `no component reaches for a brand colour by hex value`             | `:648` | `bg-[#AB020E]` **gagal**; hanya token semantik                                                                                                 |
| `the seo head keys match the ones the blade template renders`      | `:678` | head key baru harus ada di `seo.tsx` **dan** `app.blade.php`                                                                                   |

## 3.3 Test yang mengunci nilai dan akan gagal

| Test                                  | Yang dikunci                                    | Yang harus dilakukan                                                                              |
| ------------------------------------- | ----------------------------------------------- | ------------------------------------------------------------------------------------------------- |
| `RbacFoundationTest.php:50`           | Daftar permission **persis**                    | tambah 12 nama ke `$expected` (preseden: P10 menambah `media.*`)                                  |
| `RbacFoundationTest.php:80`           | Regex `/^[a-z]+\.[a-z]+$/`                      | nama permission **tidak boleh** mengandung `-` — alasan D-2 / m-2 memilih 12 permission satu kata |
| `RbacFoundationTest.php:189`          | `Permission::count() === expectedCount()`       | otomatis, `expectedCount()` tidak perlu diubah                                                    |
| `AdminAccessTest.php:160`             | `$adminPages` hardcoded                         | tambah 21 route name                                                                              |
| `PublicPlaceholderRoutesTest.php:199` | `'jadwal-misa.index'` ada                       | **nama route tidak boleh berubah**                                                                |
| `PublicRoutesTest.php:45`             | `/jadwal-misa` → komponen `public/jadwal-misa`  | komponen tidak boleh berubah                                                                      |
| `e2e/public.spec.ts:203`              | `/jadwal-misa` tanpa horizontal scroll di 360px | daftar harus berupa kartu di layar kecil                                                          |

## 3.4 Aturan yang paling mudah dilanggar

1. **`MassSchedule` tidak boleh meng-cast `start_time`.** `cast('datetime:H:i')`
   menerapkan konversi timezone; kolom itu adalah **jam dinding WIB** (PRD D-02).
   Nilai harus tetap string `'H:i:s'`.
2. **`config('app.timezone')` tetap `'UTC'`.** D-01 melanggarnya secara global.
   Semua "hari ini" dan "misa berikutnya" memakai
   `->setTimezone(config('app.display_timezone'))`.
3. **Base Controller tidak punya `AuthorizesRequests`.** `$this->authorize()` tidak
   ada; memakainya = fatal error.
4. **Otorisasi satu tempat.** `store`/`update` di `FormRequest::authorize()`;
   `index`/`edit`/endpoint lain di `Gate::authorize()`. Tidak pernah keduanya.
5. **`Gate::before` hanya berlaku untuk Gate-level check.** `$user->hasPermissionTo()`
   melewati Gate, jadi kode otorisasi wajib `can()`.
6. **`shouldBeStrict()` aktif di non-produksi.** Satu `with()` yang lupa =
   exception, bukan halaman lambat. Semua query wajib `with()` / `withCount()`.
7. **PHPStan level 7 tanpa baseline, `tests/` ikut dianalisa.** PHPDoc
   `@return list<…>` / `@param array<string, mixed>` ditulis sejak awal.
8. **Formatter.** 4 spasi, single quote, semicolon, printWidth 80, Tailwind
   auto-sort. Jalankan `npm run check:fix` **sekali di akhir**, lalu review diff.
9. **`npm run check` memakai `denyWarnings: true` + `typeAware: true`.** Setiap prop
   baru wajib bertipe eksplisit; `any` menggagalkan gate.
10. **Inertia SSR tetap OFF** (D-26). Tag SEO harus tetap ada di `app.blade.php`.
11. **`resources/js/components/ui/*` diabaikan lint dan sengaja direstyle.** Tidak
    pernah diedit tangan, tidak pernah di-`shadcn add` ulang (D-22).
12. **`pre-commit` menjalankan `composer ci:check` penuh** (~25 detik). Memerlukan
    database test MySQL hidup. Gunakan `--no-verify` hanya jika memang tidak
    situasi itu (D-28).

---

# 4. Ringkasan Task

| #     | Task                  | Perlu kerja?         | Output                                             |
| ----- | --------------------- | -------------------- | -------------------------------------------------- |
| 04.01 | Migration 4 tabel     | Ya, besar            | 1 migration, 4 tabel, FK, unique MASS-06, 6 indeks |
| 04.02 | Enum                  | Ya, kecil            | `LocationType`, `DayOfWeek`                        |
| 04.03 | Model + scope         | Ya, besar            | 4 model, `#[Fillable]`, 4 scope                    |
| 04.04 | Factory               | Ya, menengah         | 4 factory + 9 state                                |
| 04.05 | Policy                | Ya, kecil            | 4 policy (`viewAny`/`create`/`update`)             |
| 04.06 | Form Request          | **Ya, besar**        | 11 request, validasi Indonesia                     |
| 04.07 | `NextMassResolver`    | **Ya, besar**        | DTO + service + algoritma MASS-08                  |
| 04.08 | Duplikasi jadwal      | Ya, menengah         | Action bertransaksi                                |
| 04.09 | Controller + Route    | **Ya, besar**        | 6 controller, 17 route, 12 permission              |
| 04.10 | `JsonResource`        | Ya, besar            | 10 resource; pemakaian pertama di repo             |
| 04.11 | Admin UI              | **Ya, sangat besar** | 13 halaman, 10 komponen                            |
| 04.12 | Publik `/jadwal-misa` | **Ya, sangat besar** | 6 komponen, 1 halaman ditulis ulang                |
| 04.13 | Print stylesheet      | Ya, kecil            | blok `@media print`                                |
| 04.14 | `lib/datetime.ts`     | Ya, kecil            | 4 formatter                                        |
| 04.15 | Testing               | **Ya, sangat besar** | 12 file test                                       |
| 04.16 | Dokumentasi           | Ya, kecil            | D-34…D-38, `AGENTS.md`, roadmap                    |

---

# 5. Urutan Implementasi

Roadmap §11 menetapkan urutan A→E. Urutan di bawah mengikuti itu.

```text
Tahap A — Audit                     ✅ selesai (bagian 2 dokumen ini)
      ↓
Tahap B — Database & backend
      04.01 migration → 04.02 enum → 04.03 model → 04.04 factory
      → 04.05 policy → 04.06 form request → 04.07 resolver
      → 04.08 action duplikasi → 04.09 controller+route+permission → 04.10 resource
      → jalankan test backend SEBELUM lanjut ke UI
      ↓
Tahap C — Frontend admin
      config/inertia.php → sidebar → 13 halaman + 10 komponen
      → jalankan test frontend admin
      ↓
Tahap D — Frontend publik
      lib/datetime.ts → 6 komponen → tulis ulang pages/index.tsx → print stylesheet
      → jalankan test frontend publik
      ↓
Tahap E — Verifikasi akhir
      test matrix → composer test → ci:check → e2e → build → review manual
      → D-34…D-38 → AGENTS.md → roadmap README
```

**Pencacahan:** `Wayfinder` di-regenerate setelah 04.09 (route berubah) dan sekali
lagi setelah 04.12 jika ada perubahan route.

---

## 5.1 [04.01] Migration

**File:** `database/migrations/2026_10_XX_000000_create_mass_schedule_tables.php`
(nama waktu akan diisi `php artisan make:migration` saat eksekusi).

Mengikuti `2026_10_07_100000_create_media_tables.php`: satu file untuk beberapa
tabel yang saling terkait, `timestamps()` sebelum indeks, `dropIfExists` dalam
urutan terbalik, dan komentar inline untuk setiap kolom nullable serta setiap
indeks (mengapa indeks itu ada).

```php
Schema::create('locations', function (Blueprint $table) {
    $table->id();
    $table->string('name', 150);
    $table->string('slug', 180)->unique();
    $table->enum('type', LocationType::values());
    $table->text('address')->nullable();
    $table->decimal('latitude', 10, 7)->nullable();
    $table->decimal('longitude', 10, 7)->nullable();
    $table->string('maps_url', 2048)->nullable();
    $table->text('notes')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

Schema::create('mass_schedules', function (Blueprint $table) {
    $table->id();
    // ON DELETE RESTRICT: roadmap 4.5 — jangan sampai ada jadwal yatim.
    $table->foreignId('location_id')->constrained()->restrictOnDelete();
    $table->unsignedTinyInteger('day_of_week');   // 0 = Minggu .. 6 = Sabtu
    $table->time('start_time');                  // jam dinding WIB — PRD D-02
    $table->string('name', 150);
    $table->string('language', 50)->nullable();
    $table->text('notes')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    // MASS-06 / PRD D-09 — lapis kedua setelah validasi FormRequest.
    $table->unique(['location_id', 'day_of_week', 'start_time'],
        'mass_schedules_location_day_time_unique');
    // Query publik: kelompokkan per hari, lalu urutkan jam dalam hari itu.
    $table->index(['day_of_week', 'start_time']);
});

Schema::create('special_masses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('location_id')->constrained()->restrictOnDelete();
    $table->string('title', 180);
    $table->text('description')->nullable();
    $table->timestamp('start_at');               // UTC — D-01
    $table->timestamp('end_at')->nullable();
    $table->text('notes')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    // MASS-02 — tab Misa Khusus: aktif, belum lewat, urut menaik.
    $table->index(['start_at', 'is_active']);
    $table->index(['location_id', 'start_at']);
});

Schema::create('schedule_notices', function (Blueprint $table) {
    $table->id();
    $table->string('title', 180);
    $table->text('message');
    // Ketiganya nullable: notice tanpa target berlaku umum (roadmap 4.4).
    // SET NULL supaya notice tetap hidup bila targetnya dihapus.
    $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('mass_schedule_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('special_mass_id')->nullable()->constrained()->nullOnDelete();
    $table->timestamp('starts_at');              // wajib — roadmap 4.4
    $table->timestamp('ends_at')->nullable();    // null = tanpa batas — MASS-05
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    // MASS-05 — tiga kondisi dari rentang tampil + status.
    $table->index(['is_active', 'starts_at', 'ends_at']);
});
```

**Tidak ada `softDeletes()`** (D-1).

## 5.2 [04.02] Enum

`app/Enums/LocationType.php` — `string`:

```php
enum LocationType: string
{
    case GerejaParoki = 'gereja_paroki';
    case Stasi = 'stasi';
    case Kapel = 'kapel';
    case Lainnya = 'lainnya';

    /** @return list<string> */
    public static function values(): array { /* array_column(self::cases(), 'value') */ }

    public function label(): string { /* → lang/id/mass-schedule.php */ }
}
```

`app/Enums/DayOfWeek.php` — `int` (PRD Lampiran C):

```php
enum DayOfWeek: int
{
    case Minggu = 0;
    case Senin = 1;
    /* …Selasa, Rabu, Kamis, Jumat */
    case Sabtu = 6;

    public static function from(int $value): self { /* ValueError di luar 0..6 */ }
    public function label(): string { /* "Minggu" … */ }
}
```

**Catatan yang wajib ditulis di docblock `DayOfWeek`:** nilai enum ini sengaja
sama dengan `Carbon::dayOfWeek` (0 = Minggu). Keduanya dipakai berdampingan di
`NextMassResolver`, dan ketidakcocokan satu angka di sana akan menggeser "misa
berikutnya" satu hari.

## 5.3 [04.03] Model

`app/Models/Location.php`, `MassSchedule.php`, `SpecialMass.php`,
`ScheduleNotice.php`.

Semua memakai `#[Fillable([...])]` **atribut** (ARCHITECTURE.md Part A §6 +
`ArchitectureTest.php:119`), PHPDoc `@property` per kolom, dan relasi bertipe.

| Model            | Relasi                                                                     | Scope                                                                              |
| ---------------- | -------------------------------------------------------------------------- | ---------------------------------------------------------------------------------- |
| `Location`       | `hasMany(MassSchedule)`, `hasMany(SpecialMass)`, `hasMany(ScheduleNotice)` | `scopeActive`                                                                      |
| `MassSchedule`   | `belongsTo(Location)`, `hasMany(ScheduleNotice)`                           | `scopeActive` (`is_active` **+** `whereHas('location', active)` — MASS-03/MASS-07) |
| `SpecialMass`    | `belongsTo(Location)`, `hasMany(ScheduleNotice)`                           | `scopeUpcoming` (`is_active` + `start_at >= now()` — MASS-02)                      |
| `ScheduleNotice` | `belongsTo(Location)`, `belongsTo(MassSchedule)`, `belongsTo(SpecialMass)` | `scopeCurrentlyVisible` (MASS-05)                                                  |

Casts:

```php
// Location
'latitude' => 'decimal:7', 'longitude' => 'decimal:7', 'is_active' => 'boolean',
// MassSchedule
'day_of_week' => DayOfWeek::class, 'is_active' => 'boolean',
//   ⚠️ start_time SENGAJA TIDAK ADA cast-nya — lihat 3.4 butir 1
// SpecialMass / ScheduleNotice
'is_active' => 'boolean', 'start_at' => 'immutable_datetime', 'end_at' => 'immutable_datetime',
```

Scope dipakai **bersama** oleh query publik dan query admin supaya aturan
"lokasi aktif" tidak ditulis ulang di tempat lain:

```php
public function scopeActive(Builder $query): Builder
{
    return $query->where('is_active', true);
}
```

## 5.4 [04.04] Factory

| Factory                 | State                                                                                                                                               |
| ----------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------- |
| `LocationFactory`       | default (`gereja_paroki`, aktif), `inactive()`, `withoutMaps()`, `withCoordinates(float $lat, float $lng)`                                          |
| `MassScheduleFactory`   | default (`Minggu`, `07:30`, aktif), `inactive()`, `onDay(DayOfWeek $day, string $time = '07:30')`                                                   |
| `SpecialMassFactory`    | default (mulai 7 hari ke depan), `upcoming()`, `past()`, `inactive()`                                                                               |
| `ScheduleNoticeFactory` | default (aktif, sedang tampil), `general()` (tanpa target), `scheduled()` (`starts_at` di masa depan), `expired()` (`ends_at` lampau), `inactive()` |

Semua memakai `fake()` dengan `fake_locale = id_ID` (D-02).

## 5.5 [04.05] Policy

`app/Policies/{Location,MassSchedule,SpecialMass,ScheduleNotice}Policy.php`,
mengikuti `ProductPolicy` di ARCHITECTURE.md Part E §2:

```php
final class LocationPolicy
{
    public function viewAny(User $user): bool { return $user->can('locations.view'); }
    public function create(User $user): bool { return $user->can('locations.create'); }
    public function update(User $user, Location $location): bool { return $user->can('locations.update'); }
}
```

`viewAny`/`create` dipanggil sebagai `Gate::authorize('viewAny', Location::class)`.
Tidak ada `delete` — tidak ada endpoint hapus (D-1, D-6).

## 5.6 [04.06] Form Request

`app/Http/Requests/MassSchedule/` — 11 file. `authorize()` adalah **satu-satunya**
titik otorisasi untuk `store`/`update` (ARCHITECTURE.md Part A §8).

| Request                                                      | `authorize()`                                  | Aturan khas                                                                                                                                                                                                                                                                                                                                       |
| ------------------------------------------------------------ | ---------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `StoreLocationRequest`                                       | `can('create', Location::class)`               | `type`→`Rule::enum`; `slug`→`required\|string\|max:180\|unique:locations`; `maps_url`→`nullable\|url:http,https\|max:2048`; `latitude`→`nullable\|numeric\|between:-90,90`; `longitude`→`nullable\|numeric\|between:-180,180`; **closure**: `latitude` XOR `longitude` ditolak                                                                    |
| `UpdateLocationRequest`                                      | `can('update', $this->route('location'))`      | idem + `Rule::unique('locations')->ignore($location)`                                                                                                                                                                                                                                                                                             |
| `StoreMassScheduleRequest`                                   | `can('create', MassSchedule::class)`           | `day_of_week`→`Rule::enum(DayOfWeek)`; `start_time`→`required\|date_format:H:i`; `name`→`required\|string\|max:150`; `location_id`→`required\|integer` + **`Rule::exists('locations','id')->where('is_active', true)`** (MASS-03) + **`Rule::unique('mass_schedules')`** dengan `where` pada `location_id`, `day_of_week`, `start_time` (MASS-06) |
| `UpdateMassScheduleRequest`                                  | `can('update', $this->route('mass_schedule'))` | idem + `Rule::unique('mass_schedules')->ignore($massSchedule, 'location_id')->where(...)`                                                                                                                                                                                                                                                         |
| `DuplicateMassScheduleRequest`                               | `can('create', MassSchedule::class)`           | `location_id` wajib **aktif** + unique check terhadap lokasi tujuan (§5.7)                                                                                                                                                                                                                                                                        |
| `StoreSpecialMassRequest` / `UpdateSpecialMassRequest`       | policy                                         | `end_at`→`nullable\|date\|after_or_equal:start_at`; `start_at`→`required\|date`                                                                                                                                                                                                                                                                   |
| `StoreScheduleNoticeRequest` / `UpdateScheduleNoticeRequest` | policy                                         | `title`→`required\|string\|max:180`; `message`→`required\|string`; **D-5**: `location_id`/`mass_schedule_id`/`special_mass_id` → `nullable\|prohibitedWith:<yang lain>`; `ends_at`→`nullable\|after_or_equal:starts_at`                                                                                                                           |

`prepareForValidation()` dipakai untuk normalisasi yang tidak bisa dilakukan rule:

- `start_time` menerima `"7:30"` dari `<input type="time">` → dinormalisasi `"07:30"`
- `latitude`/`longitude` menerima string dengan koma desimal Indonesia (`"-2,5"`) →
  `" -2.5"` (Faker `id_ID` juga memformatnya begitu)

**Pesan duplikat** (AC-MASS-7, roadmap §5.2 "pesan yang ramah pengguna"):

```php
public function messages(): array
{
    return [
        'location_id.unique' => 'Sudah ada jadwal pada lokasi, hari, dan jam tersebut.',
        'start_at.after_or_equal' => 'Waktu selesai tidak boleh lebih awal dari waktu mulai.',
        'day_of_week.in' => 'Hari yang dipilih tidak valid.',
    ];
}
```

**`lang/id/validation.php` `attributes`** ditambah 15 entri (§2.2). Tanpa ini
`:attribute` tampil sebagai nama kolom Inggris — dan karena `fallback_locale` juga
`id`, dua test penjaga kelengkapan (`ApplicationConfigurationTest`) tetap hijau
tetapi pesannya salah bahasa.

**`lang/id/mass-schedule.php`** (baru) — pola `lang/id/site-settings.php`:
`labels`, `hints`, `days`, `types`, `statuses`, `noticeScopes`, `emptyStates`.

## 5.7 [04.07] `NextMassResolver` + `NextMass`

### `app/Support/NextMass.php`

```php
final readonly class NextMass
{
    public function __construct(
        public MassKind $kind,            // enum: Rutin | Khusus
        public CarbonImmutable $startsAt, // UTC
        public string $name,
        public ?string $language,
        public Location $location,
        public ?int $massScheduleId,
        public ?int $specialMassId,
    ) {}

    public function isRegular(): bool { /* … */ }
}
```

> `app/Support/` hanya berisi "small helpers only" (ARCHITECTURE.md Part A §1).
> `NextMass` adalah nilai yang dioper antar-lapisan (service → resource → props),
> bukan helper, jadi ini tempat yang benar dan tidak butuh folder baru.

`MassKind` enum baru ikut di `app/Enums/` (`Rutin = 'rutin'`, `Khusus = 'khusus'`)
agar union di TypeScript mencerminkan enum, bukan string bebas.

### `app/Services/NextMassResolver.php`

```php
final class NextMassResolver
{
    public function resolve(?int $locationId = null, ?DateTimeInterface $now = null): ?NextMass;
    public function resolveFor(Location $location, ?DateTimeInterface $now = null): NextMass;
}
```

> ⚠️ **Tidak boleh** memakai `Illuminate\Http\Request` — `ArchitectureTest.php:65`
> membuat build gagal. Waktu masuk lewat parameter default, yang membuat
> `Carbon::setTestNow()` **dan** injeksi eksplisit sama-sama bekerja
> (roadmap §5.4 "atau injeksi waktu setara").

Algoritma, roadmap §5.4:

1. `$now = ($now ?? now())->setTimezone(config('app.display_timezone'))`.
2. Ambil `MassSchedule::query()->active()->when($locationId, …)->with('location')->get()`
   — `scopeActive` sudah menyaring lokasi nonaktif (MASS-03/MASS-07).
3. Untuk tiap jadwal rutin:
    ```php
    $daysAhead = ($schedule->day_of_week->value - $now->dayOfWeek + 7) % 7;
    $candidate = $now->startOfDay()->addDays($daysAhead)
                     ->setTimeFromTimeString($schedule->start_time);
    if ($candidate < $now) {
        $candidate = $candidate->addWeek();     // jam hari ini sudah lewat → +7 hari
    }
    ```
    `>= now` dijamin oleh baris `if` tersebut — inilah yang menguji kasus
    "Minggu 07.29 → kandidat 07.30" dan "Minggu 07.31 → bergeser ke Minggu depan".
4. Ambil `SpecialMass::query()->upcoming()->when($locationId, …)->with('location')->get()`
   dan bandingkan **di UTC** (`start_at` sudah UTC, D-01).
5. Bandingkan semua kandidat berdasarkan `startsAt` terkecil.
6. Seri → **misa khusus menang** (MASS-08 langkah 4). PRD §7.3 langkah 4 dan
   roadmap §5.4 langkah 6 mengizinkan kontrak hasil menyertakan kedua kandidat;
   UI hanya menampilkan satu, jadi kontrak _choose-one_ lebih sederhana dan tidak
   kehilangan apa pun.
7. Tidak ada kandidat → `null` → empty state yang sopan di UI.

Kandidat rutin selalu berada dalam 0–7 hari ke depan, jadi tidak perlu pagination.

## 5.8 [04.08] Duplikasi jadwal

`app/Actions/MassSchedule/DuplicateMassScheduleAction.php` — ARCHITECTURE.md
Part A §4 (Action dipakai karena menulis lebih dari satu model / butuh
transaksi):

```php
final class DuplicateMassScheduleAction
{
    /**
     * @param  array{name: string, language: ?string, notes: ?string, is_active: bool}  $attributes
     */
    public function handle(MassSchedule $source, Location $target, array $attributes): MassSchedule
    {
        return DB::transaction(fn (): MassSchedule => $source->replicate([
            'location_id' => $target->getKey(),
            ...$attributes,
        ]));
    }
}
```

`replicate()` menyalin semua kolom kecuali `id` dan `created_at`/`updated_at` —
persis §5.7 "jangan menyalin ID atau timestamps". `DB::transaction()` tetap dipakai
meski satu baris, karena ini operasi bisnis yang harus atomik terhadap penolakan
duplikat dari unique index (§5.2 "dua lapis").

Controller `DuplicateMassScheduleController` (invokable) memanggilnya setelah
`DuplicateMassScheduleRequest` lolos, dan tetap menangkap `QueryException` dengan
kode `23000` → `with('error', …)` untuk kasus request bersamaan.

## 5.9 [04.09] Controller, Route, Permission

### Route publik

`routes/web.php:58` diganti:

```php
Route::get('/jadwal-misa', [PublicMassScheduleController::class, 'index'])
    ->name('jadwal-misa.index');
```

**Nama route tidak berubah** → `PublicPlaceholderRoutesTest` dan
`PublicRoutesTest` tetap hijau.

### Route admin

`routes/mass-schedule.php` (baru), di-`require` dari `routes/web.php`. Meniru
`routes/settings.php` persis: middleware group sendiri, bukan di dalam group
`web.php`, supaya `web.php` tidak tumbuh 17 baris dan daftar middleware tetap
satu blok yang mudah diaudit.

```php
Route::middleware(['auth', 'verified', EnsureAccountIsActive::class, EnsureAdminAccess::class])
    ->prefix('admin')
    ->group(function () { /* … */ });
```

| Method | URI                                                  | Name                       | Komponen Inertia                         |
| ------ | ---------------------------------------------------- | -------------------------- | ---------------------------------------- |
| GET    | `/admin/jadwal-misa`                                 | `mass-schedules.index`     | `admin/jadwal-misa`                      |
| GET    | `/admin/jadwal-misa/tambah`                          | `mass-schedules.create`    | `admin/jadwal-misa/misa-rutin/tambah`    |
| POST   | `/admin/jadwal-misa`                                 | `mass-schedules.store`     | —                                        |
| GET    | `/admin/jadwal-misa/{mass_schedule}/ubah`            | `mass-schedules.edit`      | `admin/jadwal-misa/misa-rutin/ubah`      |
| PATCH  | `/admin/jadwal-misa/{mass_schedule}`                 | `mass-schedules.update`    | —                                        |
| POST   | `/admin/jadwal-misa/{mass_schedule}/duplikat`        | `mass-schedules.duplicate` | —                                        |
| GET    | `/admin/lokasi`                                      | `locations.index`          | `admin/jadwal-misa/lokasi`               |
| GET    | `/admin/lokasi/tambah`                               | `locations.create`         | `admin/jadwal-misa/lokasi/tambah`        |
| POST   | `/admin/lokasi`                                      | `locations.store`          | —                                        |
| GET    | `/admin/lokasi/{location}/ubah`                      | `locations.edit`           | `admin/jadwal-misa/lokasi/ubah`          |
| PATCH  | `/admin/lokasi/{location}`                           | `locations.update`         | —                                        |
| GET    | `/admin/misa-khusus`                                 | `special-masses.index`     | `admin/jadwal-misa/misa-khusus`          |
| GET    | `/admin/misa-khusus/tambah`                          | `special-masses.create`    | `admin/jadwal-misa/misa-khusus/tambah`   |
| POST   | `/admin/misa-khusus`                                 | `special-masses.store`     | —                                        |
| GET    | `/admin/misa-khusus/{special_mass}/ubah`             | `special-masses.edit`      | `admin/jadwal-misa/misa-khusus/ubah`     |
| PATCH  | `/admin/misa-khusus/{special_mass}`                  | `special-masses.update`    | —                                        |
| GET    | `/admin/pemberitahuan-jadwal`                        | `schedule-notices.index`   | `admin/jadwal-misa/pemberitahuan`        |
| GET    | `/admin/pemberitahuan-jadwal/tambah`                 | `schedule-notices.create`  | `admin/jadwal-misa/pemberitahuan/tambah` |
| POST   | `/admin/pemberitahuan-jadwal`                        | `schedule-notices.store`   | —                                        |
| GET    | `/admin/pemberitahuan-jadwal/{schedule_notice}/ubah` | `schedule-notices.edit`    | `admin/jadwal-misa/pemberitahuan/ubah`   |
| PATCH  | `/admin/pemberitahuan-jadwal/{schedule_notice}`      | `schedule-notices.update`  | —                                        |

Route `tambah` didaftarkan **sebelum** route berparameter agar `/admin/lokasi/tambah`
tidak pernah tertangkap sebagai `{location}`. **Tidak ada `destroy`** (D-1, D-6).

### Controller

`app/Http/Controllers/MassSchedule/`:

| Controller                        | Method                                       | Gate                                                        |
| --------------------------------- | -------------------------------------------- | ----------------------------------------------------------- |
| `PublicMassScheduleController`    | `index`                                      | — (publik)                                                  |
| `LocationController`              | `index`, `create`, `store`, `edit`, `update` | `viewAny` / `create` (FormRequest) / `update` (FormRequest) |
| `MassScheduleController`          | `index`, `create`, `store`, `edit`, `update` | idem                                                        |
| `SpecialMassController`           | `index`, `create`, `store`, `edit`, `update` | idem                                                        |
| `ScheduleNoticeController`        | `index`, `create`, `store`, `edit`, `update` | idem                                                        |
| `DuplicateMassScheduleController` | `__invoke`                                   | `create` via FormRequest                                    |

`index()` mengikuti pola `ProductController` (ARCHITECTURE Part E §4):
`paginate(15)->withQueryString()`, `LocationOptionResource` untuk dropdown,
`LocationResource::collection(...)`.

Controller `create`/`edit` mengirim label & opsi dari `lang/id/mass-schedule.php`
supaya React tidak membawa salinan kedua (pola `SettingsController::formMeta()`).

### Query publik — validasi filter

Roadmap §5.5: "Filter `location_id` dan `day` divalidasi; jangan percaya query
parameter mentah." Halaman publik **tidak pernah** mengembalikan 422 karena
query string rusak; parameter tidak valid diabaikan:

```php
$locationId = $request->integer('lokasi') ?: null;
$location = $locationId ? Location::query()->active()->find($locationId) : null;

$day = $request->has('hari') ? DayOfWeek::tryFrom($request->integer('hari')) : null;
```

`DayOfWeek::tryFrom()` mengembalikan `null` untuk `7` atau `-1`, jadi filter rusak
menghasilkan daftar penuh — bukan error dan bukan hasil kosong yang menyesatkan.

### Permission (12)

```php
// PermissionSeeder::PERMISSIONS, ditambahkan setelah blok posts.*
'locations.view', 'locations.create', 'locations.update',
'massschedules.view', 'massschedules.create', 'massschedules.update',
'specialmasses.view', 'specialmasses.create', 'specialmasses.update',
'schedulenotices.view', 'schedulenotices.create', 'schedulenotices.update',
```

Nama **satu kata tanpa tanda hubung** (m-2) demi menjaga regex
`RbacFoundationTest.php:80` (`/^[a-z]+\.[a-z]+$/`) tetap utuh. Total menjadi 27
permission; `$expected` di `RbacFoundationTest` ikut bertambah.

## 5.10 [04.10] `JsonResource`

**Pemakaian pertama di repository ini.** `app/Http/Resources/` dibuat sekarang.

`app/Http/Resources/MassSchedule/` (admin):

| Resource                 | Isi                                                                                       |
| ------------------------ | ----------------------------------------------------------------------------------------- |
| `LocationResource`       | seluruh kolom + `active_schedules_count` (dari `withCount`, untuk dialog MASS-07) + `can` |
| `LocationOptionResource` | `id`, `name`, `type` — untuk `Select` dan filter publik                                   |
| `MassScheduleResource`   | termasuk `location` nested (`whenLoaded`)                                                 |
| `SpecialMassResource`    | `start_at`/`end_at` → ISO 8601                                                            |
| `ScheduleNoticeResource` | termasuk status turunan: `draft`/`terjadwal`/`sedang tampil`/`kedaluwarsa` (§6.5)         |

`app/Http/Resources/Public/MassSchedule/` (publik — bentuk berbeda, jadi dipisah
sesuai ARCHITECTURE.md Part C §2):

| Resource                       | Isi                                                                              |
| ------------------------------ | -------------------------------------------------------------------------------- |
| `PublicLocationResource`       | `id`, `name`, `type`, `address`, **`mapsUrl`** (lihat D-8)                       |
| `PublicMassScheduleResource`   | `day_of_week`, `start_time` (`"07:30"`), `name`, `language`, `notes`, `location` |
| `PublicSpecialMassResource`    | `title`, `description`, `start_at`, `end_at` (ISO 8601), `location`              |
| `PublicScheduleNoticeResource` | `title`, `message`, `scope`                                                      |
| `NextMassResource`             | `kind`, `starts_at`, `name`, `language`, `location`                              |

`mapsUrl` disusun **di server** (D-8):

```php
'mapsUrl' => $this->maps_url ?: (
    $this->latitude !== null && $this->longitude !== null
        ? "https://www.google.com/maps/search/?api=1&query={$this->latitude},{$this->longitude}"
        : null
),
```

Alamat kosong **tidak pernah** dipakai membangun tautan (roadmap §7.2).

## 5.11 [04.11] Frontend Admin

### Prasyarat

1. `config/inertia.php` → tambah `resource_path('js/features/admin/jadwal-misa/pages')`.
2. `resources/js/app.tsx` → **tidak perlu diubah**. `switch (true)` pada case
   `admin/akun/` tidak konflik; semua `admin/jadwal-misa/*` jatuh ke `default: AppLayout`.
3. `resources/js/components/app-sidebar.tsx:54` → `import { index } from '@/routes/mass-schedules'`
   lalu `{ title: 'Jadwal Misa', href: index(), icon: CalendarDays }`. Tipe `Entry`,
   komponen `SidebarEntry`, dan `SidebarGroup` **tidak berubah** — cabangnya sudah
   ada.

### Struktur feature

```text
resources/js/features/admin/jadwal-misa/
├── types.ts
├── pages/
│   ├── index.tsx                     → admin/jadwal-misa  (ringkasan + Misa Rutin)
│   ├── lokasi.tsx                    → admin/jadwal-misa/lokasi
│   ├── lokasi/{tambah,ubah}.tsx
│   ├── misa-rutin/{tambah,ubah}.tsx
│   ├── misa-khusus.tsx
│   ├── misa-khusus/{tambah,ubah}.tsx
│   ├── pemberitahuan.tsx
│   └── pemberitahuan/{tambah,ubah}.tsx
└── components/
    ├── location-form.tsx
    ├── mass-schedule-form.tsx
    ├── special-mass-form.tsx
    ├── schedule-notice-form.tsx
    ├── location-table.tsx
    ├── mass-schedule-table.tsx
    ├── special-mass-table.tsx
    ├── schedule-notice-table.tsx
    └── duplicate-mass-schedule-dialog.tsx
```

Nama komponen Inertia mengikuti aturan ARCHITECTURE.md Part B §1: nama page adalah
`<audience>/<feature>/<path di bawah pages/>`, dengan `index.tsx` mewakili fitur
itu sendiri. **URL dan nama page tidak harus sama** — `/admin/lokasi` →
`admin/jadwal-misa/lokasi` sah, persis seperti `/admin/akun/profile` →
`admin/akun/profile`.

### Kenapa tidak ada sub-nav layout

Roadmap §6.1 menyebut "empat pintasan". Ada dua opsi:

- Layout sub-nav baru → **ditolak**: `ArchitectureTest.php:304` melarang
  `layouts/` meng-import `features/`, jadi array nav harus tinggal di layout
  (menggunakan copy Inggris seperti `layouts/settings/layout.tsx`) atau dikirim
  sebagai layout prop dari setiap halaman. Keduanya lebih besar dari gunanya
  gunanya.
- **Kartu pintasan di `pages/index.tsx`** → dipilih. Roadmap §6 juga meminta
  "perubahan sekecil mungkin" pada navigasi, dan §6.1 hanya menyebut tombol tambah
  sesuai konteks, bukan navigasi bersarang.

### Pola halaman

Semua halaman mengikuti ARCHITECTURE.md Part B §3 — **tanpa import layout**:

```tsx
export default function Index({ massSchedules, locations, stats, filters, can }: Props) { … }

Index.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Jadwal Misa' },
    ],
};
```

### Pola form

Satu komponen per entitas dipakai bersama oleh `tambah` dan `ubah`
(ARCHITECTURE.md Part E §8). Submit lewat `useForm` + `form.submit(Action.store())`;
tombol nonaktif saat `form.processing` (roadmap §6.3 "cegah submit ganda").
`InputError` di sebelah setiap field, `errors` dibaca dari `useForm`.

Tanggal-waktu dikirim dari datetime-local **waktu lokal WIB** dan dikonversi ke
UTC di `prepareForValidation()` — satu-satunya tempat konversi itu terjadi, sesuai
roadmap §6.4.

### Dialog konfirmasi MASS-07

`DuplicateMassScheduleAction` bukan yang perlu dialog; yang perlu adalah
**menonaktifkan lokasi** (MASS-07):

```tsx
// LokasiResource selalu membawa active_schedules_count (withCount).
<ConfirmDialog
    open={pendingDeactivate !== null}
    onOpenChange={setPendingDeactivate}
    title="Nonaktifkan lokasi ini?"
    description={
        `${row.name} masih memiliki ${row.active_schedules_count} jadwal aktif. ` +
        "Jadwal pada lokasi nonaktif tidak akan tampil di halaman publik."
    }
    confirmLabel="Nonaktifkan"
    destructive
    onConfirm={confirmDeactivate}
/>
```

`ConfirmDialog` sudah ada dan **nol konsumen** — ini penggunaannya yang pertama.
`data-test="confirm-dialog-confirm"` / `confirm-dialog-cancel` sudah tersedia
untuk Playwright.

### Daftar & responsif

`ui/table.tsx` (8 subkomponen) dan `ui/pagination.tsx` belum pernah dipakai;
Phase 04 adalah konsumen pertamanya. Pola responsif untuk **AC-MASS-5**:

```tsx
{
    /* < md — kartu */
}
<div className="space-y-3 md:hidden"> … </div>;
{
    /* ≥ md — tabel */
}
<div className="hidden overflow-x-auto md:block">
    <Table> … </Table>
</div>;
```

Pencarian & filter memakai query string (ARCHITECTURE.md Part B §6):

```tsx
router.get(
    index().url,
    { search, lokasi, status },
    { preserveState: true, preserveScroll: true },
);
```

## 5.12 [04.12] Frontend Publik `/jadwal-misa`

`resources/js/features/public/jadwal-misa/pages/index.tsx` **ditulis ulang**
(file yang sama, supaya `PageChunk`, `PageFinder`, dan nama route tidak berubah).

```text
resources/js/features/public/jadwal-misa/
├── types.ts
├── pages/index.tsx                    ← ditulis ulang
└── components/
    ├── next-mass-card.tsx
    ├── mass-schedule-card.tsx         ← nama dari DESIGN.md §Cards & Containers
    ├── schedule-notice-banner.tsx
    ├── schedule-filter.tsx
    ├── location-info.tsx
    └── share-actions.tsx
```

### Susunan halaman (roadmap §7.1)

| Urutan | Bagian                                   | Komponen                           | Sumber data                           |
| ------ | ---------------------------------------- | ---------------------------------- | ------------------------------------- |
| 1      | Hero: eyebrow "Ibadah", judul, deskripsi | `PublicSection` + `SectionHeading` | —                                     |
| 2      | Kartu Misa Berikutnya                    | `next-mass-card.tsx`               | `nextMass: NextMassData \| null`      |
| 3      | Notice aktif                             | `schedule-notice-banner.tsx`       | `notices: PublicScheduleNoticeData[]` |
| 4      | Tab `Misa Rutin` / `Misa Khusus`         | `Tabs`                             | state lokal + `?tab=`                 |
| 5      | Filter lokasi & hari                     | `schedule-filter.tsx`              | query string                          |
| 6      | Daftar rutin, dikelompokkan per hari     | `mass-schedule-card.tsx`           | `regularMasses`                       |
| 7      | Daftar khusus, urut menaik               | kartu                              | `specialMasses`                       |
| 8      | Info lokasi + "Buka di Google Maps"      | `location-info.tsx`                | `locations`                           |
| 9      | Bagikan WhatsApp · Salin tautan · Cetak  | `share-actions.tsx`                | `seo.appUrl`                          |

### Interaksi (roadmap §7.2)

- **Filter** — `router.get(jadwalMisa(), { lokasi, hari }, { preserveState: true,
preserveScroll: true })`. Aksi reset mengembalikan query string kosong.
- **Tab** — `useState` + `?tab=` agar URL bisa dibagikan; tab tetap hidup saat
  Inertia menukar props.
- **Maps** — `<a href={l.mapsUrl} target="_blank" rel="noopener noreferrer">`;
  `mapsUrl === null` → tombol **tidak dirender sama sekali** (§7.3 "Lokasi tidak
  memiliki URL Maps").
- **WhatsApp** — `https://wa.me/?text=${encodeURIComponent(`${title} ${url}`)}` (XC-B1).
- **Salin tautan** — `navigator.clipboard.writeText()` dengan fallback
  `document.execCommand('copy')`, feedback toast sukses/gagal (§7.3 "kegagalan
  copy link").
- **Cetak** — `window.print()` + `print:hidden` pada navbar, filter, dan aksi.
- **Waktu** — `07.30 WIB` untuk jam rutin; `Asia/Pontianak` untuk datetime
  (`lib/datetime.ts`). **Jangan** menghitung jadwal kedua di React; backend
  mengirim `nextMass` yang sudah final (roadmap §7.2).

### SEO

**Tidak menambah head key baru.** `Seo` yang sudah ada mempaginationkan
`title`/`description`/`canonical` dari nama komponen, jadi `app.blade.php` **tidak
perlu disentuh** — dan `ArchitectureTest.php:678` tetap hijau. Kalau nanti ada
kebutuhan menambah key, wajib di dua file sekaligus.

## 5.13 [04.13] Print Stylesheet

PRD XC-B2 (MUST): "halaman Jadwal Misa dan detail Pelayanan memiliki stylesheet
cetak (`@media print`) yang rapi: tanpa navigasi, tanpa banner, tipografi bersih."

`resources/css/app.css` **belum punya satu pun aturan `@media print`** (599 baris).
Tambahan, di akhir file:

```css
@layer base {
    /* XC-B2 — cetak Jadwal Misa: nav, filter, dan aksi ikut disembunyikan lewat
       utilitas `print:hidden` pada elemennya masing-masing. */
    @media print {
        .print-hide {
            display: none !important;
        }
        .print-only {
            display: revert !important;
        }
        body {
            background: #fff;
        }
        a[href^="http"]::after {
            content: " (" attr(href) ")";
            font-size: 0.75em;
        }
    }
}
```

Pemakaian: `print:hidden` (utilitas Tailwind, tanpa CSS baru) pada navbar,
filter, tombol aksi, dan footer — konsisten dengan Aturan Tailwind DESIGN.md.
Kelas `.print-hide` / `.print-only` hanya untuk hal yang tidak bisa dicapai
utilitas.

**Spesifikasi cetak.**roadmap §7.2: "Mode cetak menyembunyikan navigasi, tombol
aksi, dan elemen dekoratif yang tidak dibutuhkan. Jadwal harus tetap terbaca."
Terpilih: cetak **tab Misa Rutin**, bukan tab terakhir yang sedang aktif —
sebabkan tab khusus menambah data yang tidak selalu relevan untuk kertas.

## 5.14 [04.14] `lib/datetime.ts`

`resources/js/lib/datetime.ts` (baru). ARCHITECTURE.md Part B §1 menyebut
`lib/` sebagai "utilities (`cn()`, formatters)", dan dua konsumen ada di fase ini
(publik + admin):

```ts
export function formatWibTime(time: string): string; // "07:30" → "07.30 WIB"
export function formatWibDate(iso: string): string; // "Minggu, 5 Oktober 2026"
export function formatWibDateTime(iso: string): string; // "Minggu, 5 Oktober 2026 · 07.30 WIB"
export function formatDayName(day: number): string; // 0 → "Minggu"
```

Semua memakai `timeZone: 'Asia/Pontianak'` secara **eksplisit**, bukan
`Intl.DateTimeFormat().resolvedOptions().timeZone` — roadmap §8 "jangan
mengandalkan timezone perangkat". `timeZone` dibaca dari shared prop
`displayTimezone` bila diteruskan, dengan `Asia/Pontianak` sebagai default.

> Pengecualian: `formatWibTime('07:30')` **bukan** konversi tanggal. Ia hanya
> mengganti `:` dengan `.` dan menambahkan ` WIB`, karena `start_time` adalah jam
> dinding (D-02). Kalau ia memakai `Intl`, jam 00:00 di UTC akan bergeser satu
> hari — bug yang persis dicegah oleh test MASS-01.

## 5.15 [04.15] Testing

### Backend Unit — `tests/Unit/NextMassResolverTest.php`

`RefreshDatabase` **tidak** otomatis untuk `tests/Unit`, jadi file ini memanggilnya
sendiri lewat `uses(RefreshDatabase::class)` di awal file.

8 kasus, mengikuti roadmap §9.1 + tabel §9.5:

| #   | Kasus                                                               | Cara                                  |
| --- | ------------------------------------------------------------------- | ------------------------------------- |
| 1   | Jadwal rutin berikutnya dipilih dengan benar                        | `Carbon::setTestNow()`, WIB eksplisit |
| 2   | Hari ini + jam **sama persis** dengan `now` tetap memenuhi `>= now` | batas bawah `>=`                      |
| 3   | Jam hari ini sudah lewat → kemunculan berikutnya 7 hari kemudian    | batas atas `<`                        |
| 4   | Misa khusus lebih awal daripada rutin → terpilih                    |                                       |
| 5   | Waktu sama → **misa khusus** diprioritaskan                         | `kind === MassKind::Khusus`           |
| 6   | `location_id` membatasi kandidat                                    |                                       |
| 7   | Tidak ada kandidat → `null`                                         | semua nonaktif / lokasi nonaktif      |
| 8   | Lokasi nonaktif diabaikan                                           |                                       |

Ditambah **satu test D-02** yang tidak ada di roadmap §9.1 tapi wajib:
`start_time` `'07:30:00'` di DB → `resolve()` mengembalikan kandidat whose `startsAt`
memformatted `07.30 WIB`, **bukan** `00.30 WIB`. Tanpa itu, `cast('datetime')`
bisa masuk tanpa terdeteksi.

### Backend Feature — `tests/Feature/MassSchedule/`

| File                             | Cakupan                                                                                                                                                                                                                                            |
| -------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `LocationTest.php`               | index/create/store/edit/update; 403 non-admin; redirect tamu; validasi lengkap; `maps_url` salah skema ditolak; koordinat tanpa pasangan ditolak; `slug` unik; **`active_schedules_count` benar**; update → `is_active = false` reflects di publik |
| `MassScheduleTest.php`           | CRUD; **lokasi nonaktif ditolak** (MASS-03); unique ditolak dengan pesan Indonesia (AC-MASS-7/MASS-06); jam tidak valid ditolak; **`start_time` tersimpan apa adanya** (D-02); jadwal nonaktif tidak muncul di query publik (MASS-04)              |
| `SpecialMassTest.php`            | CRUD; `end_at < start_at` ditolak; **misa lampau tersimpan tapi tidak muncul di props publik** (MASS-02/AC-MASS-6); urut menaik                                                                                                                    |
| `ScheduleNoticeTest.php`         | CRUD; **kedua target ditolak** (D-5); notice inactive/scheduled/expired tidak tampil (MASS-05); `ends_at = null` tampil tanpa batas; notice umum tampil                                                                                            |
| `DuplicateMassScheduleTest.php`  | sukses ke lokasi tujuan valid; ditolak bila tujuan tidak aktif; ditolak bila kombinasi sudah ada; tidak menyalin `id`/`created_at`; `name`/`language`/`notes` bisa ditimpa                                                                         |
| `PublicMassSchedulePageTest.php` | komponen `public/jadwal-misa`; **jadwal nonaktif/lokasi nonaktif tidak bocor**; **perubahan admin tercermin pada request berikutnya** (AC-MASS-3); filter `lokasi`/`hari`; parameter rusak **tidak** menghasilkan 422; notice yang terlihat        |

Semua memakai `$this->actingAs(superAdmin())` (helper `tests/Pest.php`) dan
`expect(Model::query()->…)` untuk assertion database — **`assertDatabaseHas` tidak
dipakai di repo ini**.

### Frontend Vitest — `tests/js/`

| File                                     | Cakupan                                                                               |
| ---------------------------------------- | ------------------------------------------------------------------------------------- |
| `lib/datetime.test.ts`                   | `07:30` → `07.30 WIB`; tanggal & datetime WIB; tidak bergeser oleh timezone perangkat |
| `components/mass-schedule-card.test.tsx` | render nama/jam/lokasi/bahasa; catatan opsional; empty state                          |
| `components/next-mass-card.test.tsx`     | dengan kandidat; `nextMass = null` → pesan ramah                                      |
| `components/schedule-filter.test.tsx`    | ganti lokasi/hari memperbarui `router.get`; state filter tetap terlihat; aksi reset   |

### E2E — `tests/e2e/mass-schedule.spec.ts`

Nama test bahasa Indonesia; `resetLoginRateLimit(); await signIn(page)` di
`beforeEach`; **tidak** hard-code kredensial maupun nama database (D-29 §5).

1. `/jadwal-misa` publik memuat kartu Misa Berikutnya dan tab
2. filter lokasi + hari memperbarui daftar
3. tab Misa Khusus menampilkan hanya yang belum lewat
4. admin melihat 4 pintasan di `/admin/jadwal-misa`
5. membuat jadwal baru, lalu **memverifikasi muncul di halaman publik** (AC-MASS-3)
6. konfirmasi nonaktifkan lokasi muncul saat `active_schedules_count > 0`
7. `/admin/jadwal-misa` menolak tamu → `/admin/login`
8. **tidak ada horizontal scroll di 360px** (sejenis `public.spec.ts:203`)

### Arch assertion — `tests/Unit/ArchitectureTest.php`

**Tidak menambah asersi baru.** Aturan yang relevan sudah ada dan akan aktif dengan
sendirinya (`a feature does not import another feature`, `services do not depend on
the http layer`, `the inertia page finder and the blade lookup find the same
files`). Menambah aturan baru tanpa implementasi yang menyalahkannya adalah
test yang tidak pernah merah.

## 5.16 [04.16] Dokumentasi

### `docs/DECISIONS.md` — D-34 … D-38

| ID       | Judul                                                                                                              |
| -------- | ------------------------------------------------------------------------------------------------------------------ |
| **D-34** | Skema empat tabel: tanpa soft delete; `slug`+`notes` tanpa `sort_order`; `schedule_notices` mengikuti roadmap §4.4 |
| **D-35** | Tidak ada cache untuk jadwal (PRD XC-C1 SHOULD ditunda)                                                            |
| **D-36** | 12 permission `resource.action` satu kata                                                                          |
| **D-37** | `is_active` sebagai field form, bukan endpoint toggle; peringatan MASS-07 via `active_shadules_count`              |
| **D-38** | Empat URL PRD §5.2 dipertahankan; empat pintasan sebagai kartu, tanpa sub-nav layout                               |

Bunyi draft untuk D-34 dan D-35 — keduanya adalah pengakuan terbuka terhadap
PRD, jadi harus ditulis eksplisit:

```text
## D-34 — Skema tabel Jadwal Misa, dan tiga divergensi dari PRD §9.5

**Status:** Accepted · berlaku sejak P04

PRD §9.5 dan roadmap Phase 04 §4 tidak Susai pada tiga titik. Roadmap menang
ketiga kali karena artefak yang lebih spesifik untuk fase ini, dan karena PRD §9.5
sendiri menyatakan "Nama dan tipe bersifat usulan; boleh disesuaikan saat
implementasi selama perilaku di dokumen ini terpenuhi".

### 1. Tidak ada soft delete

PRD §9.5 menandai `locations` (SD) dan `special_masses` (SD); ADM-05 (MUST) menyatakan
operasi hapus memakai soft delete. Roadmap §4 menyatakan sebaliknya — jangan
menambahkan soft delete bila `is_active` cukup — dan §5.6 melarang endpoint hapus
permanen.

Dipilih: hanya `is_active`, tanpa `deleted_at`. Alasannya bukan sekadar perbedaan
dokumen: modul ini tidak punya endpoint hapus sama sekali, jadi kolom soft delete
tidak akan pernah diisi — persis apa yang D-24 kritik pada permission yang belum
punya kode pembaca. Menghapus kolom juga membuat "tidak dihapus permanen" (MASS-04)
benar secara struktural, bukan hanya lewat konvensi.

Konsekuensi yang harus dicatat: sampai suatu modul benar-benar punya endpoint
hapus, ADM-05 belum terpenuhi untuk tabel konten ini.

### 2. `locations`: `slug` + `notes`, tanpa `sort_order`

PRD §9.5 punya `sort_order` dan tidak punya `slug`/`notes`; roadmap §4.1 sebaliknya,
dan roadmap §5.2 mewajibkan validasi slug. `slug` membuat filter lokasi publik
terbaca manusia dan dapat dibagikan; `sort_order` dihapus karena tidak ada satu
pun requirement yang mengurutkan lokasi.

### 3. `schedule_notices` mengikuti roadmap §4.4

PRD §9.5 hanya menyebut `message`, `starts_at` (nullable), `ends_at`, `is_active`.
Roadmap §2.1 meminta CRUD pemberitahuan dengan rentang tampil dan §6.5 meminta
target umum/lokasi/jadwal/misa-khusus. Bentuk PRD §9.5 tidak dapat menyediakan
target. `starts_at` menjadi wajib, dan ketiga foreign key saling eksklusif
(0 atau tepat 1) agar label pada banner notice tidak ambigu.

## D-35 — Jadwal tidak di-cache, dan kenapa XC-C1 ditunda

**Status:** Accepted · berlaku sejak P04

PRD XC-C1 (SHOULD) menyebut cache untuk "data jarang berubah (jadwal misa rutin)"
dengan invalidasi saat admin menyimpan, dan satu syarat yang tegas: cache tidak
boleh membuat "misa berikutnya" salah.

Cache ditunda pada fase ini. Empat tabel ini kecil — jumlah lokasi, jadwal,
misa khusus, dan notice pada sebuah paroki berada di bawah ratusan baris — sehingga
query publik yang sudah ter-indeks lebih murah daripadaKEY biaya invalidasi yang
benar. XC-C1 adalah SHOULD, dan "masa depan punya cache dengan invalidasi" lebih
aman daripada "sekarang punya cache tanpa invalidasi".

Konsekuensi: AC-MASS-3 terpenuhi tanpa kerja invalidasi apa pun. Kalau cache
ditambahkan nanti, aturan berikutnya wajib: hitung ulang `NextMassResolver` per
request dari data yang ter-cache, jangan pernah melihat kandidat dari cache apa pun.
```

### `AGENTS.md`

- `AGENTS.md:14` "Current: **Phase 1**" → "**Phase 4**"
- `AGENTS.md:139` "**12 permissions**" → "**27 permission**"
- Tambah baris **Known gaps** yang jujur:
    - 4 tabel Jadwal Misa sudah ada; `MassScheduleDemoSeeder` adalah data contoh,
      bukan jadwal resmi, dan belum pernah dikonfirmasi sekretariat
    - belum ada cache jadwal (D-35)
    - `AGENTS.md:14` "Current: Phase 1" → "Phase 4" tidak boleh menggantung —
      setiap transisi fase memperbarui baris ini

### `docs/roadmap/README.md`

Fase 3 → ✅ Done · Fase 4 → 🟡 Current (lalu ✅ Done setelah §13 DoD terpenuhi)

### `docs/roadmap/phase-04-mass-schedule.md`

**Phase 04 belum ter-commit.** `git status` menunjukkan
`?? docs/roadmap/phase-04-mass-schedule.md`. Dokumen ini harus masuk di commit
pertama; jika tidak, dokumen acuan fase tidak ikut ke PR.

---

# 6. File yang Akan Terpengaruh

## 6.1 Dibuat — Backend (48)

```text
database/migrations/2026_10_XX_000000_create_mass_schedule_tables.php
app/Enums/{LocationType,DayOfWeek,MassKind}.php
app/Models/{Location,MassSchedule,SpecialMass,ScheduleNotice}.php
database/factories/{Location,MassSchedule,SpecialMass,ScheduleNotice}Factory.php
database/seeders/MassScheduleDemoSeeder.php
app/Policies/{Location,MassSchedule,SpecialMass,ScheduleNotice}Policy.php
app/Http/Requests/MassSchedule/StoreLocationRequest.php
app/Http/Requests/MassSchedule/UpdateLocationRequest.php
app/Http/Requests/MassSchedule/StoreMassScheduleRequest.php
app/Http/Requests/MassSchedule/UpdateMassScheduleRequest.php
app/Http/Requests/MassSchedule/DuplicateMassScheduleRequest.php
app/Http/Requests/MassSchedule/StoreSpecialMassRequest.php
app/Http/Requests/MassSchedule/UpdateSpecialMassRequest.php
app/Http/Requests/MassSchedule/StoreScheduleNoticeRequest.php
app/Http/Requests/MassSchedule/UpdateScheduleNoticeRequest.php
app/Support/NextMass.php
app/Services/NextMassResolver.php
app/Actions/MassSchedule/DuplicateMassScheduleAction.php
app/Http/Resources/MassSchedule/{Location,LocationOption,MassSchedule,SpecialMass,ScheduleNotice}Resource.php
app/Http/Resources/Public/MassSchedule/{PublicLocation,PublicMassSchedule,PublicSpecialMass,PublicScheduleNotice,NextMass}Resource.php
app/Http/Controllers/MassSchedule/{PublicMassSchedule,Location,MassSchedule,SpecialMass,ScheduleNotice,DuplicateMassSchedule}Controller.php
routes/mass-schedule.php
lang/id/mass-schedule.php
```

## 6.2 Dibuat — Frontend (30)

```text
resources/js/lib/datetime.ts
resources/js/features/admin/jadwal-misa/types.ts
resources/js/features/admin/jadwal-misa/pages/index.tsx
resources/js/features/admin/jadwal-misa/pages/lokasi.tsx
resources/js/features/admin/jadwal-misa/pages/lokasi/{tambah,ubah}.tsx
resources/js/features/admin/jadwal-misa/pages/misa-rutin/{tambah,ubah}.tsx
resources/js/features/admin/jadwal-misa/pages/misa-khusus.tsx
resources/js/features/admin/jadwal-misa/pages/misa-khusus/{tambah,ubah}.tsx
resources/js/features/admin/jadwal-misa/pages/pemberitahuan.tsx
resources/js/features/admin/jadwal-misa/pages/pemberitahuan/{tambah,ubah}.tsx
resources/js/features/admin/jadwal-misa/components/{location,mass-schedule,special-mass,schedule-notice}-form.tsx
resources/js/features/admin/jadwal-misa/components/{location,mass-schedule,special-mass,schedule-notice}-table.tsx
resources/js/features/admin/jadwal-misa/components/duplicate-mass-schedule-dialog.tsx
resources/js/features/public/jadwal-misa/types.ts
resources/js/features/public/jadwal-misa/components/{next-mass-card,mass-schedule-card,schedule-notice-banner,schedule-filter,location-info,share-actions}.tsx
```

## 6.3 Dibuat — Test (12)

```text
tests/Unit/NextMassResolverTest.php
tests/Feature/MassSchedule/{Location,MassSchedule,SpecialMass,ScheduleNotice,DuplicateMassSchedule,PublicMassSchedulePage}Test.php
tests/js/lib/datetime.test.ts
tests/js/components/{mass-schedule-card,next-mass-card,schedule-filter}.test.tsx
tests/e2e/mass-schedule.spec.ts
```

## 6.4 Diubah (14)

| File                                                       | Perubahan                                                                              | Alasan                                                                                     |
| ---------------------------------------------------------- | -------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------ |
| `routes/web.php`                                           | `Route::inertia('/jadwal-misa')` → controller; +`require __DIR__.'/mass-schedule.php'` | Roadmap §5.5 "Pertahankan route, ubah dari placeholder"                                    |
| `config/inertia.php`                                       | +1 entry `pages.paths`                                                                 | **Wajib** `ArchitectureTest.php:504`                                                       |
| `database/seeders/PermissionSeeder.php`                    | +12 permission + docblock                                                              | AUTH-R6; docblock sendiri menyatakan permission per modul "added when the module is built" |
| `lang/id/validation.php`                                   | +15 entri `attributes`                                                                 | D-02                                                                                       |
| `resources/css/app.css`                                    | blok `@media print`                                                                    | XC-B2 — belum ada satu pun aturan cetak                                                    |
| `resources/js/components/app-sidebar.tsx`                  | +`href` entri "Jadwal Misa"                                                            | Entri sudah ada, hanya disabled                                                            |
| `resources/js/features/public/jadwal-misa/pages/index.tsx` | ditulis ulang                                                                          | ganti `[ISI: …]` dengan data nyata                                                         |
| `tests/Feature/Foundation/RbacFoundationTest.php`          | +12 nama di `$expected`                                                                | Persis seperti P10 menambah `media.*`                                                      |
| `tests/Feature/Auth/AdminAccessTest.php`                   | +21 route name di `$adminPages`                                                        | Guard tiga lapis middleware                                                                |
| `tests/Feature/Public/PublicPlaceholderRoutesTest.php`     | docblock: `/jadwal-misa` bukan placeholder lagi                                        | Ketepatan dokumentasi test                                                                 |
| `AGENTS.md`                                                | Current Phase 4; 12→27 permission; known-gaps                                          | Akurasi; AGENTS.md adalah dokumen binding                                                  |
| `docs/roadmap/README.md`                                   | Phase 3 Done, Phase 4 Current                                                          | Status board                                                                               |
| `docs/DECISIONS.md`                                        | +D-34…D-38                                                                             | AGENTS.md mewajibkan                                                                       |
| `docs/roadmap/phase-04-mass-schedule.md`                   | **di-commit**                                                                          | Sekarang `??` di `git status`                                                              |

## 6.5 Tidak disentuh — sengaja

| Item                                                            | Alasan                                                                                              |
| --------------------------------------------------------------- | --------------------------------------------------------------------------------------------------- |
| `app/Providers/AppServiceProvider.php`                          | Gate `::before` sudah memberi `super_admin` semua ability; policy baru cukup `can()`                |
| `resources/js/app.tsx`                                          | Resolver layout berbasis nama komponen; `admin/jadwal-misa/*` jatuh ke `default: AppLayout`         |
| `resources/js/layouts/**`                                       | Tidak ada layout baru (lihat 5.11); `ArchitectureTest.php:304`                                      |
| `resources/js/components/ui/*`                                  | Primitif shadcn yang tersedia sudah cukup; D-22 melarang restyle/regenerate                         |
| `resources/views/app.blade.php`                                 | Tidak ada head key SEO baru                                                                         |
| `bootstrap/app.php`                                             | Tidak ada job, command, atau schedule baru di fase ini                                              |
| `config/app.php`, `config/html.php`, `config/site-settings.php` | `display_timezone` sudah ada (D-01); tidak ada rich text di fase ini                                |
| `phpunit.xml`                                                   | D-03 sudah MySQL + `APP_DISPLAY_TIMEZONE`                                                           |
| `phpstan.neon`                                                  | Melonggarkan ignore = menyembunyikan bug (D-27)                                                     |
| `HandleInertiaRequests.php`                                     | Shared prop sudah cukup (`displayTimezone` sudah ada); ARCH Part C §5 melarang shared prop membesar |
| `database/seeders/DatabaseSeeder.php`                           | D-14 — `MassScheduleDemoSeeder` sengaja **tidak** ditambahkan (D-7)                                 |
| `.env` / `.env.example`                                         | Tidak ada env baru                                                                                  |

## 6.6 Dihapus

**Tidak ada.** Tidak ada soft delete yang perlu dilepas, tidak ada placeholder
yang dibuang (file `pages/index.tsx` yang sama ditulis ulang), tidak ada
dependency yang dicabut, tidak ada test yang dihapus.

---

# 7. Dependency / Configuration Impact

## 7.1 Dependency — **nol baru**

Roadmap §3.9: "Jangan menambahkan dependency baru bila kebutuhan dapat dipenuhi
oleh stack yang sudah ada." Seluruh kebutuhan fase ini terpenuhi:

| Kebutuhan         | Yang dipakai                                                                                                                                  | Sudah ada?                |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------- |
| ORM + query       | Eloquent, `Builder::whereHas`, `paginate`, `withCount`                                                                                        | ✅                        |
| Validasi          | `FormRequest`, `Rule::enum`, `Rule::unique`, `Rule::exists`, `Rule::prohibitedWith`, `date_format`                                            | ✅                        |
| Serialisasi props | `Illuminate\Http\Resources\Json\JsonResource`                                                                                                 | ✅ (pertama kali dipakai) |
| Transaction       | `DB::transaction`                                                                                                                             | ✅                        |
| Timezone          | `nesbot/carbon` (melalui `laravel/framework`)                                                                                                 | ✅                        |
| Tabel admin       | `ui/table.tsx`, `ui/pagination.tsx`, `ui/select.tsx`, `ui/switch.tsx`, `ui/tabs.tsx`, `ui/dialog.tsx`, `ui/dropdown-menu.tsx`, `ui/badge.tsx` | ✅                        |
| Dialog konfirmasi | `components/confirm-dialog.tsx`                                                                                                               | ✅ (nol konsumen)         |
| Toast             | `ui/sonner.tsx` + `Inertia::flash`                                                                                                            | ✅                        |
| Format tanggal    | `Intl.DateTimeFormat` (bawaan browser)                                                                                                        | ✅                        |
| Test              | Pest, Vitest (`vite-plus/test`), Playwright                                                                                                   | ✅                        |

## 7.2 Configuration

| Key                                  | Impact                                                               | Wajib? |
| ------------------------------------ | -------------------------------------------------------------------- | ------ |
| `config/inertia.php` → `pages.paths` | **+1 entry**: `resource_path('js/features/admin/jadwal-misa/pages')` | ✅ ya  |
| `config/app.php`                     | tidak berubah — `display_timezone` sudah ada (D-01)                  | —      |
| `config/html.php`                    | tidak berubah — tidak ada rich text di fase ini                      | —      |
| `config/site-settings.php`           | tidak berubah                                                        | —      |
| `config/database.php`                | tidak berubah                                                        | —      |
| `phpunit.xml`                        | tidak berubah — MySQL + `APP_DISPLAY_TIMEZONE` sudah (D-03)          | —      |
| `phpstan.neon`                       | **tidak boleh** menambah `ignoreErrors` (D-27)                       | —      |
| `bootstrap/app.php`                  | tidak berubah — tidak ada job/command/schedule baru                  | —      |
| `vite.config.ts`                     | tidak berubah — `tests/js/**` sudah masuk `include`                  | —      |
| `tsconfig.json`                      | tidak berubah                                                        | —      |
| `package.json`                       | tidak berubah                                                        | —      |
| `composer.json`                      | tidak berubah                                                        | —      |
| `.env` / `.env.example`              | tidak berubah                                                        | —      |

## 7.3 Generated (gitignored, di-regenerate)

```bash
php artisan wayfinder:generate --with-form
```

Dipanggil setelah 04.09 (route berubah) dan sekali lagi di Tahap E bila ada
perubahan route lanjutan. Plugin Vite juga meng-generate otomatis saat
`npm run dev` / `npm run build`.

**Import path harus dibaca dari output `wayfinder:generate`, bukan ditebak** —
`ArchitectureTest.php:65` dan catatan Phase 03 §3.3 butir 1 keduanya menyebutnya.
`mass-schedules.index` → `@/routes/mass-schedules` (export `index`);
`jadwal-misa.index` → `@/routes/jadwal-misa` (export `index`).

---

# 8. Risiko dan Mitigasi

| #    | Risiko                                                                            | Dampak                                                                                | Mitigasi                                                                                                                                     |
| ---- | --------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| R-1  | `ArchitectureTest.php:504` — 13 halaman tanpa entry `config/inertia.php`          | Seluruh test Inertia merah, termasuk yang sudah ada                                   | Entry ditambahkan pada perubahan yang sama; ada di [§3.1](#31-empat-hal-yang-harus-sepakat-saat-menambah-page) dan langkah 5.11              |
| R-2  | `RbacFoundationTest.php:50` mengunci daftar permission secara persis              | 2 test merah                                                                          | `$expected` ditambah 12 nama (preseden: P10 menambah `media.*`)                                                                              |
| R-3  | `RbacFoundationTest.php:80` regex `/^[a-z]+\.[a-z]+$/`                            | test merah                                                                            | Permission **satu kata** tanpa `-` (m-2). Kalau nanti `mass-schedules.*` dipakai, regex harus dilonggarkan **secara sadar**, bukan diam-diam |
| R-4  | `AdminAccessTest.php:160` `$adminPages` hardcoded                                 | Guard tiga-lapis gagal diam-diam                                                      | 21 route name ditambahkan                                                                                                                    |
| R-5  | `shouldBeStrict()` + `preventLazyLoading()`                                       | Satu `with()` lupa = **exception**, bukan halaman lambat                              | Semua query wajib `with()` / `withCount()`; `resolveFor()` dan semua resource `whenLoaded()`                                                 |
| R-6  | PHPStan level 7 tanpa baseline, `tests/` ikut dianalisa                           | Build merah                                                                           | PHPDoc `@return list<…>` / `@param array<string, mixed>` sejak awal; `readonly final class` untuk DTO                                        |
| R-7  | `ArchitectureTest.php:313` — `admin/jadwal-misa` meng-import `public/jadwal-misa` | Test merah                                                                            | Kode bersama naik ke `lib/` (`lib/datetime.ts`), bukan saling import. `types.ts` duplikat tipis per feature, bukan di-share                  |
| R-8  | `ArchitectureTest.php:65` — `NextMassResolver` memakai `Request`                  | Build gagal                                                                           | Resolver menerima `?DateTimeInterface $now = null`; `ArchitectureTest` sudah menjaganya                                                      |
| R-9  | `ArchitectureTest.php:596` — `href="/admin/jadwal-misa"`                          | Test merah                                                                            | Selalu `import { index } from '@/routes/mass-schedules'`                                                                                     |
| R-10 | `ArchitectureTest.php:648` — `bg-[#AB020E]`                                       | Test merah                                                                            | Hanya token semantik; `ui/` dikecualikan                                                                                                     |
| R-11 | **`start_time` ter-cast jadi datetime**                                           | **Melanggar D-02**; kartu "Misa Berikutnya" meleset satu hari pada batas tengah malam | Tanpa cast; test eksplisit `07:30` → `07.30 WIB` di `NextMassResolverTest`                                                                   |
| R-12 | `config('app.timezone')` diubah ke WIB                                            | Melanggar D-01 secara global                                                          | Tidak disentuh; `->setTimezone(config('app.display_timezone'))` di resolver                                                                  |
| R-13 | `DayOfWeek` tidak sama dengan `Carbon::dayOfWeek`                                 | "Misa berikutnya" bergeser satu hari                                                  | Kedua enum 0=Minggu; docblock menyatakannya; kasus batas diuji                                                                               |
| R-14 | `ArchitectureTest.php:678` — head key baru tanpa pasangan                         | 2 `og:description`, tidak ada crawler error                                           | **Tidak menambah head key baru**; kalau ditambah, dua file sekaligus                                                                         |
| R-15 | `e2e/public.spec.ts:203` — horizontal scroll di 360px                             | Suite E2E merah                                                                       | Kartu di `< md` sejak awal, bukan tabela yang disembunyikan                                                                                  |
| R-16 | Halaman publik 422 karena query string rusak                                      | Pengunjung melihat halaman error                                                      | Filter divalidasi lalu **diabaikan**, bukan mengembalikan 422                                                                                |
| R-17 | 13 halaman + 10 resource = diff besar                                             | melanggar roadmap §2.2 "tanpa refactor di luar kebutuhan"                             | Commit dipecah 5 tahap (A→B→C→D→E), tiap commit hijau; tidak menyentuh satu pun file di luar §6                                              |
| R-18 | `npm run check:fix` me-resort `app.css`                                           | Diff CSS tak terduga                                                                  | Jalankan sekali di akhir, review diff (Phase 03 §3.3 butir 5)                                                                                |
| R-19 | Demo seeder terbaca sebagai jadwal resmi                                          | Pelanggaran hard rule AGENTS.md                                                       | Tidak dipanggil `DatabaseSeeder`; docblock "DATA CONTOH — BUKAN JADWAL RESMI PAROKI"; nilai placeholder `[ISI: …]`                           |
| R-20 | `pre-commit` menjalankan `composer ci:check` penuh                                | Commit butuh ~25 detik dan MySQL hidup                                                | MySQL test DB harus hidup sebelum commit; `--no-verify` hanya bila memang tidak situasi itu                                                  |
| R-21 | Database test belum dibuat di mesin ini                                           | `RefreshDatabase` gagal                                                               | `CREATE DATABASE website_paroki_hspmtb_test CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;` sekali per mesin (D-03)                       |
| R-22 | `Eloquent mass assignment` membuang kolom                                         | Data hilang diam-diam                                                                 | `#[Fillable]` eksplisit + test behavioral; `shouldBeStrict` menangkapnya                                                                     |
| R-23 | Data datetime salah zona karena `datetime-local` dibaca sebagai UTC               | Jadwal salah 7 jam                                                                    | Konversi **hanya** di `prepareForValidation()`; test batas tengah malam                                                                      |

---

# 9. Verification Plan

## 0. Prasyarat — test DB harus hidup

`RefreshDatabase` menjalankan `migrate:fresh`, jadi database **terpisah** wajib
ada (D-03):

```bash
CREATE DATABASE website_paroki_hspmtb_test CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
```

## 1. Setelah Tahap B — backend

```bash
php artisan wayfinder:generate --with-form
php artisan test tests/Unit/NextMassResolverTest.php
php artisan test tests/Feature/MassSchedule
composer types:check          # phpstan level 7
vendor/bin/pint --dirty
```

Roadmap §11 Tahap B: "Jalankan backend tests sebelum membangun UI penuh."

## 2. Setelah Tahap C + D — frontend

```bash
npm run check                 # oxlint type-aware + denyWarnings + format check
npm run check:fix             # hanya bila ada failure format; review diff CSS
npm run types:check           # tsc --noEmit ( mencakup tests/js & tests/e2e)
npm run test:unit             # Vitest
```

## 3. Gate penuh = yang CI jalankan

```bash
composer test
# → php artisan config:clear
# → pint --parallel --test
# → phpstan analyse
# → php artisan test

composer ci:check
# → npm run check
# → npm run types:check
# → npm run test:unit
# → php artisan test
```

## 4. Production build

```bash
npm run build
```

Harus lolos tanpa "Unable to locate file in Vite manifest" dan tanpa unresolved
import. SSR tetap off (D-26) — `build:ssr` **tidak** dijalankan.

## 5. E2E (tidak ada di `pre-commit`)

```bash
npm run e2e       # sudah meng-chain: e2e:prepare → playwright test
```

Butuh MySQL hidup dan `tests/e2e/.auth.json` yang dibuat `app:e2e:prepare`.
**Jangan hard-code kredensial maupun nama database di spec** (D-29 §5).

## 6. Acceptance criteria

| AC                    | Cara verifikasi                                                                                                                                                             |
| --------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **AC-MASS-1**         | Manual: Beranda → shortcut/navbar "Jadwal Misa" ≤ 2 klik. + E2E `goto('/')` lalu klik                                                                                       |
| **AC-MASS-2**         | `tests/Unit/NextMassResolverTest.php` — 6 kasus tabel roadmap §9.5 dengan `Carbon::setTestNow()` + WIB eksplisit, + 1 kasus batas tengah malam, + 1 kasus D-02              |
| **AC-MASS-3**         | `PublicMassSchedulePageTest` — mutasi admin, lalu `GET /jadwal-misa` pada request berikutnya; konten berubah. **Tanpa cache** (D-35)                                        |
| **AC-MASS-4**         | Feature test — setiap lokasi dengan `maps_url` atau koordinat valid punya `mapsUrl` non-null `https://`; lokasi tanpa keduanya punya `null` dan React tidak merender tombol |
| **AC-MASS-5**         | E2E viewport 360px, `documentElement.scrollWidth <= clientWidth` (pola `public.spec.ts:203`)                                                                                |
| **AC-MASS-6**         | Feature test — `SpecialMass::past()` tetap ada di DB, tidak muncul di props publik                                                                                          |
| **AC-MASS-7**         | Feature test — POST duplikat → `assertSessionHasErrors(['location_id'])` dengan pesan Indonesia; `QueryException` 23000 tertangani jadi flash error                         |
| Guard otorisasi       | Feature test — tamu → redirect `login`; user non-super-admin → 403, untuk **seluruh** endpoint mutasi                                                                       |
| Guard validasi        | Feature test — 11 form request: input tidak valid ditolak, tidak ada yang persist                                                                                           |
| **MASS-01**           | `NextMassResolverTest` — `start_time` `'07:30:00'` → kandidat `07.30 WIB`, bukan `00.30 WIB`                                                                                |
| **MASS-02/AC-MASS-6** | `SpecialMassTest` — lampau tersimpan, tidak tampil                                                                                                                          |
| **MASS-03**           | `MassScheduleTest` — lokasi nonaktif ditolak untuk jadwal baru **dan** perubahan                                                                                            |
| **MASS-04**           | `MassScheduleTest` — jadwal nonaktif tidak muncul di query publik, record tetap ada                                                                                         |
| **MASS-05**           | `ScheduleNoticeTest` — inactive/scheduled/expired tidak lolos scope; `ends_at = null` tetap tampil                                                                          |
| **MASS-06/AC-MASS-7** | `MassScheduleTest` — unique ditolak dengan pesan Indonesia                                                                                                                  |
| **MASS-07**           | `LocationTest` — `active_schedules_count` benar; E2E — dialog konfirmasi muncul saat > 0; endpoint tetap aman                                                               |
| **MASS-08/AC-MASS-2** | `NextMassResolverTest` — 8 kasus                                                                                                                                            |
| Guard arsitektur      | `ArchitectureTest` — 0 repository, service tidak baca `Request`, 0 import lintas-feature, entry `config/inertia.php` ada                                                    |
| Regression            | `composer test` + `npm run e2e` penuh — **tidak boleh ada test merah baru**                                                                                                 |

## 7. Review manual (wajib, tidak bisa diotomasi)

```text
viewport : 360 390 420 640 768 834 1024 1068 1280 1440
Console  : nol error/warning baru
DevTools : document.documentElement.scrollWidth <= window.innerWidth  (tiap viewport)
Keyboard : Tab-through seluruh form dan dialog; focus ring terlihat; ConfirmDialog
           tidak mengunci focus; Esc menutup
Status   : tidak hanya dibedakan oleh warna (ikon + teks) — roadmap §6.6
Print    : Ctrl+P → navbar, filter, tombol aksi, footer hilang; jadwal tetap terbaca
Maps     : setiap tombol membuka URL https yang valid di tab baru
Share    : WhatsApp → teks ter-encode; Salin tautan → toast sukses dan gagal
Timezone : jam tampil 07.30 WIB; tanggal "Minggu, 5 Oktober 2026"
NotFound : /jadwal-misa?q=<rusak> tidak 422
```

---

# 10. Test Matrix

## 10.1 Backend Unit

| #    | Test                                               | Requirement | Metode                  |
| ---- | -------------------------------------------------- | ----------- | ----------------------- |
| U-1  | Jadwal rutin berikutnya dipilih                    | MASS-08     | resolver + `setTestNow` |
| U-2  | Jam **sama persis** dengan `now` memenuhi `>= now` | MASS-08.3   | batas bawah             |
| U-3  | Jam hari ini lewat → +7 hari                       | MASS-08.2   | batas atas              |
| U-4  | Misa khusus lebih awal → terpilih                  | MASS-08.4   |                         |
| U-5  | Waktu sama → **khusus** diprioritaskan             | MASS-08.4   | `kind`                  |
| U-6  | Filter `location_id` membatasi                     | MASS-08.5   |                         |
| U-7  | Tidak ada kandidat → `null`                        | MASS-08.5   |                         |
| U-8  | Lokasi nonaktif diabaikan                          | MASS-07     |                         |
| U-9  | **`start_time` tidak terkonversi timezone**        | **D-02**    | `07:30` → `07.30 WIB`   |
| U-10 | Pergantian tengah malam                            | AC-MASS-2   | 23:59 → 00:01           |

## 10.2 Backend Feature

| #    | Test                                                        | Requirement        |
| ---- | ----------------------------------------------------------- | ------------------ |
| F-1  | Migration jalan di MySQL test                               | Roadmap §9.2       |
| F-2  | Tamu → redirect; non-admin → 403, semua endpoint mutasi     | Roadmap §9.2       |
| F-3  | Lokasi: CRUD + validasi + slug unik + koordinat berpasangan | Roadmap §5.2       |
| F-4  | Lokasi nonaktif ditolak untuk jadwal baru/perubahan         | MASS-03            |
| F-5  | Jadwal duplikat ditolak, pesan Indonesia                    | MASS-06, AC-MASS-7 |
| F-6  | Jadwal nonaktif tidak muncul di query publik                | MASS-04            |
| F-7  | Misa khusus lampau tersimpan tapi tidak tampil              | MASS-02, AC-MASS-6 |
| F-8  | Notice belum mulai / nonaktif / kedaluwarsa tidak tampil    | MASS-05            |
| F-9  | Notice umum tampil sesuai rentang aktif                     | MASS-05            |
| F-10 | Notice dengan dua target ditolak                            | D-5                |
| F-11 | Duplikasi berhasil ke lokasi tujuan valid                   | Roadmap §5.7       |
| F-12 | Duplikasi ditolak bila tujuan tidak aktif / duplikat        | Roadmap §5.7       |
| F-13 | `end_at < start_at` ditolak                                 | Roadmap §5.2       |
| F-14 | URL Maps/koordinat tidak valid ditangani aman               | Roadmap §9.2       |
| F-15 | `active_schedules_count` benar                              | MASS-07            |
| F-16 | Props publik tidak membocorkan field internal               | ARCH Part C §1     |
| F-17 | Parameter filter rusak tidak menghasilkan 422               | Roadmap §5.5       |
| F-18 | Perubahan admin terlihat di request berikutnya              | AC-MASS-3          |

## 10.3 Frontend Vitest

| #   | Test                                             | Requirement    |
| --- | ------------------------------------------------ | -------------- |
| V-1 | `formatWibTime('07:30')` → `07.30 WIB`           | XC-T2, MASS-01 |
| V-2 | Tanggal & datetime WIB, tidak bergeser           | XC-T2          |
| V-3 | `MassScheduleCard` render nama/jam/lokasi/bahasa | PRD §7.3       |
| V-4 | `MassScheduleCard` empty state                   | XC-E1          |
| V-5 | `NextMassCard` dengan kandidat                   | AC-MASS-2      |
| V-6 | `NextMassCard` `nextMass = null` → pesan ramah   | Roadmap §7.3   |
| V-7 | Filter memperbarui `router.get`                  | PRD §7.3       |
| V-8 | Filter aktif tetap terlihat + aksi reset         | Roadmap §7.2   |
| V-9 | Tombol Maps tidak dirender tanpa `mapsUrl`       | Roadmap §7.3   |

## 10.4 E2E Playwright

| #   | Test                                      | Requirement         |
| --- | ----------------------------------------- | ------------------- |
| E-1 | Publik memuat kartu Misa Berikutnya + tab | PRD §7.3            |
| E-2 | Filter lokasi + hari memperbarui daftar   | PRD §7.3            |
| E-3 | Tab khusus hanya memuat yang belum lewat  | AC-MASS-6           |
| E-4 | Admin melihat 4 pintasan                  | Roadmap §6.1        |
| E-5 | Buat jadwal → terlihat di publik          | AC-MASS-3           |
| E-6 | Dialog nonaktifkan lokasi muncul          | MASS-07             |
| E-7 | Admin menolak tamu                        | AUTH-R1             |
| E-8 | Tidak ada horizontal scroll di 360px      | AC-MASS-5, NFR-RESP |

---

# 11. Acceptance Criteria

## 11.1 Roadmap §10 — AC-MASS-1 … AC-MASS-7

| AC            | Kriteria                                                           | Terpenuhi oleh                                                                                                |
| ------------- | ------------------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------- |
| **AC-MASS-1** | Jadwal Misa Minggu ditemukan ≤ 2 klik dari Beranda                 | Entri "Jadwal Misa" sudah ada di navbar publik **dan** sidebar admin; route `jadwal-misa.index` dipertahankan |
| **AC-MASS-2** | Kartu Misa Berikutnya benar menurut WIB, termasuk seluruh boundary | `NextMassResolver` + U-1…U-10                                                                                 |
| **AC-MASS-3** | Perubahan admin tercermin di publik pada request berikutnya        | Tanpa cache (D-35) + F-18, E-5                                                                                |
| **AC-MASS-4** | Setiap lokasi dengan informasi Maps valid punya tombol berfungsi   | `PublicLocationResource::mapsUrl` (D-8) + F-14, V-9                                                           |
| **AC-MASS-5** | Halaman terbaca di ponsel tanpa tabel terpotong                    | Kartu `< md` / tabel `≥ md` + E-8                                                                             |
| **AC-MASS-6** | Misa khusus lewat tidak tampil di tab, tetapi tetap tersimpan      | `scopeUpcoming()` + F-7                                                                                       |
| **AC-MASS-7** | Jadwal duplikat ditolak dengan pesan jelas                         | Unique index + validasi + penanganan `QueryException` + F-5                                                   |
| Guard         | Semua route mutasi terlindungi authorization + validasi server     | 4 Policy + 11 FormRequest + `EnsureAdminAccess` + F-2                                                         |
| Guard         | Seluruh test modul lulus, tanpa kegagalan baru                     | `composer test` + `npm run e2e` penuh                                                                         |

## 11.2 Roadmap §13 — Definition of Done

| Item DoD                                                                | Status                                               |
| ----------------------------------------------------------------------- | ---------------------------------------------------- |
| Keempat tabel dan relasi diterapkan melalui migration                   | ✅ 04.01, 04.03                                      |
| Seluruh CRUD admin berfungsi dengan authorization dan validasi          | ✅ 04.05, 04.06, 04.09, 04.11                        |
| Duplikasi jadwal bekerja serta menolak kombinasi duplikat               | ✅ 04.08, F-11, F-12                                 |
| Halaman publik menampilkan data aktual dan seluruh fungsi utama         | ✅ 04.12                                             |
| Perhitungan Misa Berikutnya lolos tes waktu dan timezone                | ✅ U-1…U-10                                          |
| Jam rutin tetap jam dinding WIB; datetime UTC, tampil `Asia/Pontianak`  | ✅ U-9, casts tanpa `start_time`                     |
| Responsif, empty/error/loading state, Maps/share/copy/print, a11y dasar | ✅ 04.12, 04.13, V-4…V-9, review manual              |
| Test backend, frontend, typecheck/lint/build lulus                      | ✅ §9                                                |
| Tidak ada regression pada halaman/layout/modul lain                     | ✅ `composer test` + `npm run e2e`                   |
| Tidak ada jadwal resmi atau statistik yang direkayasa                   | ✅ D-7, demo seeder tidak dipanggil `DatabaseSeeder` |

## 11.3 Traceability PRD

| Requirement                                               | Ditenuhi di mana                                                           |
| --------------------------------------------------------- | -------------------------------------------------------------------------- |
| `MASS-01` jam 24 jam, tampil lokal                        | `start_time` tanpa cast + `formatWibTime` + U-9                            |
| `MASS-02` misa khusus lewat tidak tampil, tetap tersimpan | `scopeUpcoming()` + F-7                                                    |
| `MASS-03` jadwal wajib terhubung lokasi **aktif**         | `Rule::exists()->where('is_active', true)` + F-4                           |
| `MASS-04` jadwal nonaktif tidak tampil, tidak dihapus     | scope query + tidak ada route `destroy` + F-6                              |
| `MASS-05` notice berhenti setelah `ends_at`               | `scopeCurrentlyVisible()` + F-8, F-9                                       |
| `MASS-06` cegah duplikasi                                 | unique index + validasi + F-5                                              |
| `MASS-07` peringatan nonaktifkan lokasi                   | `active_shadules_count` + `ConfirmDialog` + F-15, E-6                      |
| `MASS-08` algoritma Misa Berikutnya                       | `NextMassResolver` + U-1…U-8                                               |
| `XC-T1` D-01 / D-02                                       | casts UTC + `start_time` jam dinding                                       |
| `XC-T2` format lokal Indonesia                            | `lang/id/mass-schedule.php` + `lib/datetime.ts` + V-1, V-2                 |
| `XC-T3` "misa berikutnya" dievaluasi terhadap WIB         | `setTimezone(display_timezone)`                                            |
| `XC-E1` empty state                                       | `EmptyState` untuk 4 kondisi kosong                                        |
| `XC-E3` 404 untuk konten belum terbit                     | ⚠️ **N/A** untuk fase ini — semua datamasschedule admin-curated; lihat §12 |
| `XC-B1` bagikan WhatsApp + salin tautan                   | `share-actions.tsx`                                                        |
| `XC-B2` stylesheet cetak                                  | `@media print` + `print:hidden`                                            |
| `XC-C1` cache data jarang berubah                         | **Ditunda** — D-35                                                         |
| `NFR-RESP` 360px, 44px, nol scroll horizontal             | E-8 + review manual                                                        |
| `NFR-A11Y` kontras, keyboard, ARIA                        | `ConfirmDialog`, `Tabs`, label form                                        |
| `NFR-SEO` title + description + OG                        | `Seo` yang ada; **tanpa head key baru**                                    |
| `ADM-01` admin responsif                                  | kartu di `< md`                                                            |
| `ADM-02` pesan validasi Bahasa Indonesia                  | `messages()` + `lang/id/validation.php` + `lang/id/mass-schedule.php`      |
| `ADM-05` soft delete                                      | ✅ **Terpenuhi** — D-40 (ditambahkan setelah plan ini selesai)                   |
| `AUTH-R1` guard `/admin/*`                                | 4 Policy + FormRequest + `EnsureAdminAccess` + F-2                         |
| `AUTH-R6` penamaan permission                             | D-36                                                                       |

---

# 12. Yang Sengaja Tidak Dikerjakan

Roadmap §2.2 "Tidak termasuk" — dicatat agar tidak hilang:

| Item                                                 | Alasan                                                                                                                                                                                          | Pemilik                  |
| ---------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------ |
| Modul agenda, berita, komunitas, profil paroki       | Roadmap §2.2                                                                                                                                                                                    | Fase 5–7                 |
| Notifikasi push/email/SMS                            | Roadmap §2.2                                                                                                                                                                                    | —                        |
| Sinkronisasi Google Calendar                         | Roadmap §2.2                                                                                                                                                                                    | —                        |
| Booking/pendaftaran misa                             | Roadmap §2.2                                                                                                                                                                                    | —                        |
| **Notifikasi email** saat admin menyimpan jadwal     | Roadmap §2.2 "Notifikasi push/email/SMS" — dan `Notification` belum jadi pola di repo                                                                                                           | —                        |
| **Cache jadwal**                                     | **Ditunda** — D-35. XC-C1 SHOULD                                                                                                                                                                | Fase lanjutan bila perlu |
| **Endpoint hapus**                                   | D-1, D-6. Admin "nonaktifkan, tidak hapus"                                                                                                                                                      | —                        |
| **Sub-nav admin**                                    | Ditemukan saat implementasi; lihat §5.11                                                                                                                                                        | —                        |
| **`sitemap.xml` + `robots.txt` otomatis**            | PRD §13 assigns ke Fase 4 roadmap; butuh seluruh modul selesai                                                                                                                                  | Fase 10–11               |
| **Structured data `Event` untuk misa**               | Butuh data domain lengkap                                                                                                                                                                       | Fase 10                  |
| **Image picker untuk foto lokasi**                   | D-24 — belum ada media library UI                                                                                                                                                               | —                        |
| **Halaman detail lokasi**                            | Tidak ada di PRD §5.1; filter lokasi memakai query string                                                                                                                                       | —                        |
| **`XC-E3` (404 untuk konten belum terbit)**          | Semua entri Jadwal Misa dikelola admin dan tidak punya konsep "draf" yang perlu disamarkan dari publik; `is_active` sudah menjadi filter, dan URL publikya adalah daftar, bukan entitas tunggal | —                        |
| **Media picker di Tiptap**                           | D-24                                                                                                                                                                                            | —                        |
| **`composer.json` / `package.json` dependency baru** | Roadmap §3.9                                                                                                                                                                                    | —                        |

---

# 13. Keputusan yang Masih Menunggu Konfirmasi

## 13.1 Pengakuan terbuka yang harus masuk `docs/DECISIONS.md`

Empat keputusan Phase 04 **menyimpang dari PRD** dan wajib dicatat eksplisit
(AGENTS.md: "If you find a requirement ambiguous → record in DECISIONS.md"). Draft
bunyinya ada di [§5.16](#516-0416-dokumentasi).

| ID       | Divergensi                                                                                                          | Dampak                                                                      |
| -------- | ------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------- |
| **D-34** | Tanpa soft delete (PRD §9.5 + ADM-05); `slug`+`notes` tanpa `sort_order`; `schedule_notices` mengikuti roadmap §4.4 | Bagian 1 **digantikan D-40**; bagian 2 dan 3 tetap berlaku |
| **D-40** | Soft delete untuk keempat tabel, empat `Route::delete`, empat permission `*.delete`, generated column pada dua unique index | Roadmap §5.6 dilanggar eksplisit; ADM-05 kini terpenuhi |
| **D-35** | XC-C1 SHOULD ditunda                                                                                                | Tidak ada invalidasi cache yang perlu ditulis — dan tidak bisa salah        |
| **D-36** | 12 permission satu kata                                                                                             | Mengunci `RbacFoundationTest.php:80` pada pola nama tanpa `-`               |
| **D-37** | `is_active` field form, bukan endpoint                                                                              | `AdminAccessTest.$adminPages` tidak bertambah untuk route toggle            |
| **D-38** | 4 pintasan sebagai kartu, tanpa sub-nav layout                                                                      | Tidak ada layout admin kedua                                                |

## 13.2 Konfirmasi konten dari paroki

Tidak menghalangi implementasi — factory dan form kosong keduanya valid — tetapi
`MassScheduleDemoSeeder` **tidak boleh** berisi nama lokasi atau jam yang terlihat
seperti data resmi:

| Kebutuhan                                           | Pemilik              | Temporer                    |
| --------------------------------------------------- | -------------------- | --------------------------- |
| Nama dan jenis lokasi (gereja paroki, stasi, kapel) | Sekretariat / Pastor | `[ISI: nama lokasi contoh]` |
| Jadwal misa rutin per lokasi                        | Sekretariat / Pastor | **Kosong**                  |
| Koordinat / tautan Google Maps                      | Sekretariat          | **Kosong**                  |
| Teks notifikasi contoh                              | Sekretariat          | **Kosong**                  |

Rekomendasi: demo seeder membuat **satu** lokasi contoh tanpa koordinat dan
**tanpa** jadwal, supaya halaman publik bisa dilihat terstruktur (empty state,
tab, filter) tanpa pernah menampilkan jadwal yang bisa disalahartikan.

## 13.3 Yang belum diputuskan dan belum masuk dokumen ini

| Item                                         | Mengapa belum                                                          |
| -------------------------------------------- | ---------------------------------------------------------------------- |
| Nama file `tambah.tsx` vs `create.tsx` (m-1) | Asumsikan rekomendasi; mudah dibalik tanpa menyentuh backend           |
| Halaman detail lokasi                        | Tidak ada di PRD §5.1; diasumsikan tidak perlu                         |
| Jumlah baris per halaman admin               | Diasumsikan 15 (`paginate(15)`), mengikuti default ARCHITECTURE Part C |

---

# 14. Status

```text
Phase:
PHASE 04 — Mass Schedule (Jadwal Misa)

Status:
[x] Audit codebase (main @ 4e398ac)
[x] Keputusan diambil (8 pertanyaan)
[x] Plan disusun
[x] Branch feat/phase-04-mass-schedule
[x] Tahap B — Database & backend
[x] Tahap C — Frontend admin
[x] Tahap D — Frontend publik & integrasi
[x] Tahap E — Verifikasi akhir

Keputusan yang sudah diambil:
D-1  Tanpa soft delete, hanya is_active
D-2  locations: slug + notes, tanpa sort_order
D-3  schedule_notices mengikuti roadmap §4.4
D-4  /admin/jadwal-misa = ringkasan + list Misa Rutin
D-5  Target notice saling eksklusif (0 atau tepat 1)
D-6  is_active = field form, bukan endpoint toggle
D-7  Factory + MassScheduleDemoSeeder terpisah (tidak di DatabaseSeeder)
D-8  URL Maps koordinat disusun di server (PublicLocationResource)

Pilihan minor:
m-1  Nama file halaman: tambah.tsx / ubah.tsx
m-2  12 permission resource.action satu kata
m-3  Formatter waktu di resources/js/lib/datetime.ts

Keputusan yang menunggu:
(nihil untuk implementasi — D-34…D-38 sudah memiliki bunyi di §5.16 dan siap ditulis
 ke docs/DECISIONS.md pada Tahap E)

Ringkasan:
// Dibuat   : 48 backend + 30 frontend + 13 test = 91 file baru
// Diubah   : 17 file
// Dihapus  : 0
// Dependency baru : 0
// Perubahan config : 1 (config/inertia.php pages.paths)

Hasil verifikasi:
// php artisan test       528 passed, 0 failed
// npm run test:unit      212 passed, 11 files
// composer types:check   phpstan level 7, 0 errors
// npm run check          oxlint type-aware, 0 warnings
// npm run types:check    tsc --noEmit, 0 errors
// npm run build          Vite production build sukses
// npm run e2e            39 passed (12 mass-schedule + 27 lainnya)
```

> `npm run e2e` menjalankan `app:e2e:prepare` lebih dulu. Suite Pest dan
> Playwright memakai database yang sama, jadi `npx playwright test` telanjang
> setelah `php artisan test` akan gagal seluruhnya di `signIn`.

### Yang berbeda dari rencana

Tiga tempat di mana implementasi melampaui apa yang dokumen ini prediksi, dan
ketiganya tercatat di tempat kodenya berada:

| Rencana | Kenyataan |
| --- | --- |
| "9 `JsonResource`" | Butuh satu trait `ResolvesNestedResources`: `whenLoaded()` mengembalikan `null` untuk relasi yang dimuat tetapi kosong, dan me-resolve `null` itu adalah error, bukan field yang absen |
| "`has('regularMasses', 0)` counts an empty list" | A `JsonResource` serialises as `{"data": [...]}`, so an empty list counted as **one** element. Two assertions were passing without checking anything |
| "2 enum" | Three: `MassKind` became necessary once the tie-break in MASS-08 step 4 had to be observable by a test |

## 4.5 Lanjutan setelah Phase 04 — sub-menu, tabel, dan sorting (D-41)

Tiga perubahan UI yang berpasangan: sidebar mendapat sub-menu, keempat daftar
memakai `components/ui/table.tsx` di atas `@tanstack/react-table`, dan sorting
dipindah ke server.

Yang harus diketahui siapa pun yang menyentuh skema atau navigasi:

- **Divergensi PRD §5.2.** `/admin/jadwal-misa` kini hanya ringkasan; daftar
  Misa Rutin pindah ke `/admin/jadwal-misa/misa-rutin`. Ini membatalkan bagian
  D-38 yang menahan URL lama, dan tercatat di D-41.
- **Empat kartu pintasan dihapus** — sub-menu menggantikannya.
- **Redirection setelah simpan** ke `mass-schedules.list`, bukan ringkasan.
- **v9 TanStack bukan v8.** `useTable` + `tableFeatures`, dan
  `<FlexRender cell={…} />`, bukan `flexRender(cell)`.
- **Halaman mengecil** dari 348–451 baris menjadi 50–265; kolom, kartu, dan
  aksi baris pindah ke komponen sendiri.

## 4.4 Lanjutan setelah Phase 04 — soft delete (D-40)

Rencana ini selesai tanpa endpoint hapus, dan ADM-05 tercatat sebagai
divergensi di D-34 §1. Soft delete kemudian ditambahkan sebagai pekerjaan
susulan: `deleted_at` di keempat tabel, empat `Route::delete`, empat
permission `*.delete`, satu dialog konfirmasi bersama, dan
`tests/Feature/MassSchedule/DeleteTest.php` (32 test).

Bagian yang tidak boleh dilupakan siapa pun yang menyentuh skema:

- **Generated column pada dua unique index.** `locations.slug` dan
  `mass_schedules(location_id, day_of_week, start_time)` gained
  `active_marker = IF(deleted_at IS NULL, 1, NULL)`. Menambahkan `deleted_at`
  ke index akan lebih buruk — MySQL menganggap NULL berbeda, jadi MASS-06
  berhenti berlaku.
- **`Rule::unique()` perlu `->withoutTrashed()`** di dua tempat.
- **`replicate()` menyalin kolom generated** dan MySQL menolaknya dengan error
  3105; `DuplicateMassScheduleAction` harus melewati `active_marker`.
- **`withCount()` pada relasi soft-delete** perlu
  `->withoutGlobalScope(SoftDeletingScope::class)`, kalau tidak hitungan
  "Target terhapus" selalu nol.

Dan tiga bug yang **tidak** ada di rencana ini, yang tidak bisa ditangkap test
Pest mana pun dan baru terlihat di browser. Semuanya sudah diperbaiki dan
dicatat di `docs/DECISIONS.md` D-39:

1. Inertia membungkus prop non-paginasikan sebagai `{data: [...]}` — seluruh
   halaman admin modul ini render jadi layar kosong di browser, sementara 528
   test Pest hijau.
2. Empat `<form>` tanpa `noValidate`: Chromium memvalidasi sendiri, submit
   tidak pernah jalan, dan pesan bahasa Indonesia tidak pernah muncul.
3. `->has('locations', 1)` **lolos** pada `{data: [...]}` karena menghitung
   satu kunci. Dua assertion yang menjaga prop ini tidak melihat apa pun.

> **Dokumen ini adalah rencana; implementasinya ada di branch
> `feat/phase-04-mass-schedule`.**
>
> Catatan yang paling sering berguna nanti ada di §14 dan di `docs/DECISIONS.md`
> D-34 … D-38 — khususnya pengakuan bahwa ADM-05 (soft delete) **belum**
> terpenuhi untuk modul ini, dan bahwa isi jadwal paroki belum ada.
