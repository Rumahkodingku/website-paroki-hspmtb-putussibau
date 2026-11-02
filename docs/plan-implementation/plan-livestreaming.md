# Implementation Plan — LIVESTREAMING

> **Project:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau\
> **Fitur:** Livestreaming (siaran langsung & arsip YouTube)\
> **Status:** Disusun — menunggu persetujuan implementasi\
> **Branch:** `refactor/refactor-code`\
> **Basis commit:** `cfc77f2` (1 November 2026)\
> **Spesifikasi:** `docs/specifications/spec-livestreaming.md`\
> **Sumber kebenaran:** `AGENTS.md` → `docs/ARCHITECTURE.md` → `docs/DECISIONS.md` → spesifikasi fitur

Dokumen ini menjelaskan _bagaimana_ fitur Livestreaming dibangun: keputusan yang
sudah dikunci, skema data, kontrak backend/frontend, urutan pekerjaan, file yang
tersentuh, risiko, dan cara memverifikasinya. Dokumen ini adalah pelengkap
spesifikasi, bukan pengganti.

> Plan disusun setelah audit langsung terhadap branch `refactor/refactor-code`
> pada commit `cfc77f2`. Seluruh keputusan produk di §1 sudah dikonfirmasi
> pemilik proyek. Belum ada satu baris kode implementasi Livestreaming yang
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

Tujuh pertanyaan diajukan sebelum plan ini disusun. Jawaban berikut mengikat
seluruh isi dokumen dan sudah dikonfirmasi pemilik proyek.

| #   | Pertanyaan                                                | Keputusan                                                                                                                                               | Alasan yang dipakai                                                                                                                  |
| --- | --------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| D-1 | Bagaimana pengunjung memutar rekaman di `/livestreaming`? | **Modal player + link YouTube.** Tidak ada halaman detail dan tidak ada kolom `slug`. Live aktif tetap inline di atas grid.                             | Rekomendasi spec §19 no. 4; surface publik lebih kecil, tidak perlu route `show`, SEO per video ditunda.                             |
| D-2 | Apakah kategori masuk MVP?                                | **Enum 4 kategori**: `liturgi`, `hari_raya`, `devosi`, `kegiatan`. Chip di kartu + filter kategori.                                                     | Spec §5.3 dan §7.3 meminta chip/filter; enum lebih kecil daripada master data dan tidak menambah modul CRUD.                         |
| D-3 | Dari mana thumbnail diambil?                              | **Thumbnail YouTube otomatis** (`i.ytimg.com/vi/{id}/hqdefault.jpg`). Tanpa upload, tanpa `thumbnail_media_id`.                                         | Spec §19 no. 6 merekomendasikan YouTube default; menghindari hak pakai, cache lokal, dan pipeline Media untuk gambar remote.         |
| D-4 | Bagaimana status editorial dan status siaran dikelola?    | **Status editorial = field biasa di form** (`draft/published/archived`). **Status siaran hanya lewat aksi POST**: mulai, akhiri, tandai tidak tersedia. | Konsisten dengan D-65/D-36: publikasi adalah field update, bukan permission terpisah. `broadcast_status` tidak boleh bebas di PATCH. |
| D-5 | Kapan `is_recording_available` diisi `true`?              | **Di form edit** setelah admin memastikan replay tersedia. Aksi akhiri hanya mengisi `ended_at` dan default `recorded_at`.                              | Spec §8.1 langkah 7; replay YouTube sering baru tersedia setelah siaran berakhir.                                                    |
| D-6 | Apakah query siaran aktif di beranda di-cache?            | **Tidak ada cache.** Satu query terindeks per request.                                                                                                  | Menghindari live lama tetap tampil setelah admin mengakhiri; deviasi sadar dari spec §15.                                            |
| D-7 | Apakah panel live menampilkan lokasi?                     | **Tambah `location_id` nullable** yang menunjuk master `locations`; form memakai select lokasi aktif.                                                     | Spec §5.2 meminta lokasi di panel, tetapi tabel §7.1 tidak memilikinya. Master data menjaga konsistensi nama dan mencegah input bebas. |

Nomor D-1 … D-7 adalah **nomor keputusan lokal dokumen ini**, bukan nomor
`D-xx` di `docs/DECISIONS.md`. Keputusan resmi akan dicatat sebagai **D-101**
karena D-100 adalah nomor terakhir yang terpakai.

## 1.1 Pilihan minor yang diasumsikan

Diasumsikan sesuai rekomendasi; dapat dibatalkan tanpa invalidasi seluruh plan
(lihat §14).

| #    | Pilihan minor               | Nilai                                                                                                   |
| ---- | --------------------------- | ------------------------------------------------------------------------------------------------------- |
| m-1  | Nama route publik           | `/livestreaming`, `livestreaming.index`                                                                 |
| m-2  | Nama route admin            | `livestreams.index/create/store/edit/update/destroy/start/end/unavailable`                              |
| m-3  | Nama halaman Inertia        | `public/livestreaming`, `admin/livestreaming`, `admin/livestreaming/tambah`, `admin/livestreaming/ubah` |
| m-4  | Nama tabel & model          | `livestreams`, `App\Models\Livestream`                                                                  |
| m-5  | Pagination publik           | 12 per halaman (pola Galeri)                                                                            |
| m-6  | Pagination admin            | 10 default; opsi 10/15/25/50 lewat `perPage()`                                                          |
| m-7  | Urutan arsip                | `recorded_at DESC`, tie-breaker `id DESC`; `recorded_at` null di akhir                                  |
| m-8  | Urutan admin default        | `updated_at DESC` + tie-breaker `id`                                                                    |
| m-9  | Thumbnail                   | `hqdefault.jpg` (paling konsisten tersedia); crop 16:9 di UI                                            |
| m-10 | Media/queue                 | Tidak menyentuh pipeline Media, tidak ada job/queue baru                                                |
| m-11 | Catatan admin               | Tidak dibuat (tidak ada di tabel spec)                                                                  |
| m-12 | Preview video di form       | Tidak dibuat (spec hanya “boleh”; menghindari parser YouTube duplikat di React)                         |
| m-13 | Structured data VideoObject | Tidak dibuat (durasi/upload date tidak tersedia)                                                        |
| m-14 | Field lokasi                | `location_id` FK nullable; select hanya lokasi aktif                       |

---

# 2. Kondisi Codebase Saat Ini

Audit langsung pada branch `refactor/refactor-code`, commit `cfc77f2`.

## 2.1 Fondasi yang sudah ada dan dipakai ulang — **diaudit, bukan ditulis ulang**

| Area                   | Kondisi aktual                                                                                                                        | File                                                                            |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------- |
| Route publik           | Route per domain di `routes/web.php`, named route, `Route::inertia` hanya untuk halaman statis                                        | `routes/web.php`                                                                |
| Route admin            | Grup `/admin` dengan `auth`, `verified`, `EnsureAccountIsActive`, `EnsureAdminAccess`                                                 | `routes/web.php:198-623`                                                        |
| Pola modul acuan       | Galeri: list server-side, form tambah/ubah, policy, action, resource admin/publik, scope `published()`                                | `app/Http/Controllers/Galeri/`, `app/Http/Resources/Galeri/`                    |
| Homepage aggregator    | `PublicHomeController` menyusun props dari modul pemilik; section tanpa data/setting tidak dikirim                                    | `app/Http/Controllers/Home/PublicHomeController.php`                            |
| YouTube                | `YoutubeUrl` memvalidasi host allowlist; `YoutubeEmbed::id()` menerima `watch`, `youtu.be`, `embed`, `shorts`, `live`; ID 11 karakter | `app/Rules/YoutubeUrl.php`, `app/Support/YoutubeEmbed.php`                      |
| Permission & policy    | Spatie; pola `entity.verb` satu kata; `Gate::before` untuk `super_admin`; `RbacFoundationTest` mengunci `/^[a-z]+\.[a-z]+$/`          | `database/seeders/PermissionSeeder.php`, `app/Providers/AppServiceProvider.php` |
| Audit log              | `AuditLogger` + allowlist `config/audit.php`; mutasi + event sukses dalam transaksi                                                   | `app/Services/AuditLogger.php`, `config/audit.php`                              |
| Sorting list admin     | `SortsAdminList` — whitelist kolom, tie-breaker `id`, default order dipertahankan                                                     | `app/Concerns/SortsAdminList.php`                                               |
| Resource & strict mode | `JsonResource`; `Model::shouldBeStrict()` aktif di non-production; lazy load melempar                                                 | `app/Http/Resources/`, `app/Providers/AppServiceProvider.php`                   |
| Shared props           | `HandleInertiaRequests` mengirim `auth`, `abilities`, `displayTimezone`, `seo`, `identity`, `contact`                                 | `app/Http/Middleware/HandleInertiaRequests.php`                                 |
| Navbar/footer publik   | `navigation.ts` + `footer-data.ts`; semua URL dari Wayfinder                                                                          | `resources/js/layouts/public/navigation.ts`, `.../footer/footer-data.ts`        |
| Admin sidebar          | `app-sidebar-nav.ts`; `ability` hanya dipakai Audit Log/Backup                                                                        | `resources/js/layouts/app/app-sidebar-nav.ts`                                   |
| Form admin             | `useForm`, `Field`, `FormInput`, `FormTextarea`, `ChoiceSelect`, `ActiveSwitch`, `DateTimePicker`; konversi waktu di `lib/datetime`   | `resources/js/components/form-fields.tsx`, `resources/js/lib/datetime.ts`       |
| UI primitif            | `Dialog`, `ConfirmDialog`, `DeleteDialog`, `DataTable`, `ListToolbar`, `FilterSelect`, `ListPagination`, `Image`                      | `resources/js/components/`                                                      |
| Test                   | Pest 4, Vitest 4, Playwright; `seedRolesAndPermissions()`; E2E prepare via artisan                                                    | `tests/Pest.php`, `vite.config.ts`, `playwright.config.ts`                      |

## 2.2 Yang belum ada dan menjadi pekerjaan utama

| Area                  | Kekurangan yang ditemukan                                                                            |
| --------------------- | ---------------------------------------------------------------------------------------------------- |
| Tabel & model         | Tidak ada tabel/model/factory/policy `livestreams`.                                                  |
| Route admin & publik  | Tidak ada `/livestreaming` maupun `/admin/livestreaming`.                                            |
| Halaman React         | Tidak ada `features/public/livestreaming` atau `features/admin/livestreaming`.                       |
| Homepage live section | `PublicHomeController` belum mengirim `liveStream`; `HomeSections` belum punya flag `livestreaming`. |
| Navigasi              | Belum ada menu Livestreaming di navbar, footer, atau sidebar admin.                                  |
| Permission & audit    | Belum ada `livestreams.*`, module/action audit Livestreaming, label di `lang/id/audit.php`.          |
| Thumbnail helper      | `YoutubeEmbed` baru punya `id()` dan `url()`; belum ada `embedUrl`, `watchUrl`, `thumbnailUrl`.      |

## 2.3 Fakta yang mengikat desain

- **Status live bersifat manual.** Tidak ada YouTube API; backend tidak bisa memverifikasi status live secara otoritatif.
- **Satu live aktif harus dijaga di database.** Transaksi + row lock saja tidak menutup race saat dua request mulai dari kondisi kosong; unique `live_slot` menjadi penjamin terakhir.
- **Eloquent strict mode aktif.** Jangan memuat relasi yang tidak di-eager-load; Livestream tidak punya relasi yang dirender, jadi tidak ada N+1.
- **`YoutubeEmbed` adalah satu-satunya parser YouTube.** Jangan menulis regex/parser kedua di backend atau frontend.
- **`Field`/`FormRequest` adalah validator utama.** `required` di React hanya untuk screen reader, bukan pengganti validasi server.
- **Wayfinder wajib di-regenerate** setelah route/controller berubah.

---

# 3. Kontrak yang Harus Dijaga

| Kontrak                              | Aturan yang mengikat                                                                                                                      |
| ------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------- |
| URL publik stabil                    | `/livestreaming` selalu 200 tanpa login, termasuk saat tidak ada live.                                                                    |
| Hanya konten publik yang terkirim    | Draft, archived, unavailable, scheduled, ended tanpa rekaman, dan soft-deleted tidak pernah masuk props publik.                           |
| Satu live aktif                      | Maksimum satu record `published + live`; dijaga transaksi + `lockForUpdate()` + unique `live_slot`.                                       |
| Sumber embed                         | `youtube_video_id` diekstrak server-side dari `youtube_url`; embed URL dibentuk `YoutubeEmbed`; tidak ada iframe mentah dari input admin. |
| Privacy-enhanced                     | Embed memakai `youtube-nocookie.com`, tanpa autoplay bersuara.                                                                            |
| Semua route admin di middleware lama | `['auth','verified',EnsureAccountIsActive,EnsureAdminAccess]`.                                                                            |
| Otorisasi backend                    | `Gate::authorize()`/FormRequest `authorize()` untuk setiap aksi; menyembunyikan tombol di React bukan otorisasi.                          |
| Bentuk props Inertia                 | `JsonResource`; paginated memakai `data/links/meta`; non-paginated memakai `array_values(...->resolve())` bila ada.                       |
| Wayfinder                            | Semua URL aplikasi dari `@/routes/*` atau `@/actions/*`; tidak ada URL hard-code; tidak ada React Router.                                 |
| Waktu                                | Database UTC, tampilan `Asia/Pontianak`; konversi input lewat `toIsoWithOffset()`/`toDateTimeLocalValue()`.                               |
| Publikasi                            | `published_at` diset server sekali saat pertama terbit; tidak pernah diterima dari request.                                               |
| Soft delete                          | Record utama memakai `SoftDeletes`; `live_slot` dikosongkan sebelum delete agar unique index tidak menyandera live berikutnya.            |
| Audit                                | Mutasi + event sukses dalam transaksi yang sama; hanya field/aksi di allowlist `config/audit.php`.                                        |
| Teks Indonesia                       | UI dan pesan validasi Bahasa Indonesia; nama tabel/kolom snake_case Inggris.                                                              |
| Tanpa dependency baru                | Tidak menambah library player/YouTube/lightbox.                                                                                           |

---

# 4. Model Data dan Perilaku

## 4.1 Tabel `livestreams`

Migration awal: `database/migrations/2026_11_01_000000_create_livestreams_table.php`.
Revisi lokasi: `database/migrations/2026_11_01_000001_add_location_id_to_livestreams_table.php`
(mengganti kolom teks `location` dengan `location_id`).

| Kolom                     | Tipe                                    |  Null | Aturan / keterangan                                    |
| ------------------------- | --------------------------------------- | ----: | ------------------------------------------------------ |
| `id`                      | bigint unsigned PK                      | tidak | —                                                      |
| `title`                   | varchar(150)                            | tidak | 3–150 karakter                                         |
| `description`             | text                                    |    ya | Plain text, maks 1.000 karakter                        |
| `youtube_video_id`        | varchar(11)                             | tidak | Hasil ekstraksi server-side                            |
| `youtube_url`             | varchar(500)                            | tidak | URL sumber/kanonis, bukan embed                        |
| `location_id`            | FK `locations` nullable                   |    ya | Lokasi siaran dari master data (D-7)                   |
| `category`                | enum 4 nilai                            |    ya | `livestream_categories` di `lang/id/livestreaming.php` |
| `status`                  | enum `draft/published/archived`         | tidak | Default `draft`                                        |
| `broadcast_status`        | enum `scheduled/live/ended/unavailable` | tidak | Default `scheduled`                                    |
| `live_slot`               | tinyint unsigned **unique**             |    ya | `1` hanya saat `published + live`; selain itu `NULL`   |
| `scheduled_at`            | timestamp                               |    ya | UTC, jadwal tayang                                     |
| `started_at`              | timestamp                               |    ya | Diisi aksi mulai                                       |
| `ended_at`                | timestamp                               |    ya | Diisi aksi akhir                                       |
| `recorded_at`             | timestamp                               |    ya | Dasar urutan/tahun arsip                               |
| `is_recording_available`  | boolean                                 | tidak | Default false                                          |
| `published_at`            | timestamp                               |    ya | Diset sekali saat pertama terbit                       |
| `created_by`              | FK `users`                              |    ya | `nullOnDelete`                                         |
| `updated_by`              | FK `users`                              |    ya | `nullOnDelete`                                         |
| `created_at`/`updated_at` | timestamps                              | tidak | —                                                      |
| `deleted_at`              | timestamp                               |    ya | Soft delete                                            |

Index:

- `status`
- `broadcast_status`
- `scheduled_at`
- `recorded_at`
- composite `(status, broadcast_status, is_recording_available, recorded_at)` — query arsip
- **unique** `live_slot`

> **Catatan `live_slot`:** MySQL 8 mengizinkan banyak `NULL` pada unique index.
> Nilai `1` hanya boleh dimiliki satu baris. Ini penjamin konkurensi nyata;
> aplikasi tetap mengosongkan slot saat akhiri, tandai tidak tersedia, dan hapus.

## 4.2 Model `App\Models\Livestream`

- `#[Fillable([...])]` untuk semua field form + `youtube_video_id`, `published_at`, `created_by`, `updated_by`.
- `#[Hidden(['live_slot'])]`.
- `SoftDeletes`.
- Casts:
  - `status` → `LivestreamEditorialStatus`
  - `broadcast_status` → `LivestreamBroadcastStatus`
  - `category` → `LivestreamCategory`
  - `is_recording_available` → `boolean`
  - `scheduled_at`, `started_at`, `ended_at`, `recorded_at`, `published_at` → `immutable_datetime`
- Relasi `createdBy()`, `updatedBy()`.
- Scope:
  - `published()` → `where('status', Published)`
  - `live()` → `where('broadcast_status', Live)`
  - `archiveReady()` → `where('broadcast_status', Ended)->where('is_recording_available', true)`
  - `orderedForArchive()` → `orderByRaw('recorded_at is null')->orderByDesc('recorded_at')->orderByDesc('id')`
- `public static function currentLive(): ?self` → `published()->live()->first()`.
- Tidak ada `resolveRouteBinding` slug.

## 4.3 Enum

`app/Enums/LivestreamEditorialStatus.php`

```text
Draft       = draft
Published   = published
Archived    = archived
```

`app/Enums/LivestreamBroadcastStatus.php`

```text
Scheduled   = scheduled
Live        = live
Ended       = ended
Unavailable = unavailable
```

`app/Enums/LivestreamCategory.php`

```text
Liturgi     = liturgi
HariRaya    = hari_raya
Devosi      = devosi
Kegiatan    = kegiatan
```

Ketiga enum menyediakan:

- `values(): array` — untuk validasi/migration.
- `label(): string` — dari `lang/id/livestreaming.php`.
- `options(): array<{value,label}>` — untuk form/filter.

Label Indonesia:

| Enum          | Label          |
| ------------- | -------------- |
| `draft`       | Draf           |
| `published`   | Terbit         |
| `archived`    | Diarsipkan     |
| `scheduled`   | Dijadwalkan    |
| `live`        | Sedang Live    |
| `ended`       | Selesai        |
| `unavailable` | Tidak Tersedia |
| `liturgi`     | Liturgi        |
| `hari_raya`   | Hari Raya      |
| `devosi`      | Devosi         |
| `kegiatan`    | Kegiatan       |

## 4.4 State machine

### Editorial (`status`)

```text
draft ──► published ──► archived
  ▲           │             │
  └───────────┘             └──► published (boleh kembali)
```

- `published_at` diset sekali saat pertama menjadi `published`; tidak dibersihkan saat archived/draft.
- Selama `broadcast_status = live`, perubahan `status` ke `draft`/`archived` **ditolak** dengan pesan “Akhiri siaran terlebih dahulu.”

### Broadcast (`broadcast_status`)

```text
scheduled ──► live ──► ended
     │                    │
     └──► unavailable ◄───┘
              │
              └──► live (hanya bila belum pernah ended dan URL sudah diperbaiki)
```

Aturan:

- `start`: hanya dari `scheduled`, atau `unavailable` yang belum pernah `ended`.
- `end`: hanya dari `live`.
- `unavailable`: hanya bila tidak `live` (akhiri dulu).
- Mulai siaran baru saat ada live lain: live lama diakhiri dalam transaksi yang sama.
- `ended` tidak bisa kembali ke `live`; buat record baru untuk siaran baru.

## 4.5 Lifecycle admin

1. Admin membuat draft dengan URL YouTube.
2. Backend memvalidasi URL dan mengekstrak `youtube_video_id`.
3. Admin melengkapi metadata dan menerbitkan (`status = published`).
4. Admin menekan **Mulai Siaran** saat stream benar-benar berjalan di YouTube.
5. Beranda menampilkan section live; `/livestreaming` menampilkan player aktif.
6. Admin menekan **Akhiri Siaran** setelah live berakhir.
7. Admin membuka form edit dan mencentang **Rekaman tersedia** setelah replay dipastikan ada.
8. Rekaman muncul di arsip publik.

## 4.6 `YoutubeEmbed` tambahan

`app/Support/YoutubeEmbed.php` tetap menjadi satu-satunya tempat pengetahuan YouTube.

```php
public static function embedUrl(?string $videoId): ?string;
public static function watchUrl(?string $videoId): ?string;
public static function thumbnailUrl(?string $videoId): ?string;
public static function url(?string $url): ?string; // delegasi ke embedUrl(id($url))
```

- `embedUrl` → `https://www.youtube-nocookie.com/embed/{id}`
- `watchUrl` → `https://www.youtube.com/watch?v={id}`
- `thumbnailUrl` → `https://i.ytimg.com/vi/{id}/hqdefault.jpg`
- Semua mengembalikan `null` untuk ID yang bukan 11 karakter `[A-Za-z0-9_-]`.

---

# 5. Ringkasan Task

| Tahap | Ringkasan                                                                                                                                      |
| ----- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| LS.1  | Migration, model, enum, factory, policy, permission, audit allowlist, test domain/schema.                                                      |
| LS.2  | Validation rules, form meta, actions (save/start/end/unavailable), controller admin, request, resource, route, Wayfinder, test backend admin.  |
| LS.3  | Halaman admin (daftar/form), badge, dialog konfirmasi, sidebar, lang, test Vitest.                                                             |
| LS.4  | Controller publik, resource publik, halaman `/livestreaming`, card, filter, pagination, modal player, SEO/aksesibilitas, test publik & Vitest. |
| LS.5  | Integrasi homepage (`PublicHomeController`, `HomeLivestreamSection`), navbar/footer, test homepage.                                            |
| LS.6  | E2E targeted, hardening, dokumentasi (`DECISIONS.md`, `RBAC.md`), `composer ci:check`, smoke test manual.                                      |

---

# 6. Urutan Implementasi

Urutan mengikuti dependency. Setiap langkah harus hijau sebelum lanjut.

## LS.1 — Domain & persistence

1. Buat migration `2026_11_01_000000_create_livestreams_table.php`.
2. Buat enum `LivestreamEditorialStatus`, `LivestreamBroadcastStatus`, `LivestreamCategory`.
3. Buat model `Livestream` (fillable, hidden, casts, relasi, scope, `currentLive()`).
4. Buat `LivestreamFactory` dengan state:
   - default: `draft` + `scheduled`
   - `published()`
   - `live()` → published + live + `started_at` + `live_slot = 1`
   - `ended()` → published + ended + `started_at`/`ended_at`/`recorded_at`
   - `recordingAvailable()` → ended + `is_recording_available = true`
   - `unavailable()`
5. Buat `LivestreamPolicy`.
6. Tambah 4 permission ke `PermissionSeeder` (101 → 105).
7. Tambah module/action/field/metadata/summarize ke `config/audit.php`.
8. Tambah label module/verb ke `lang/id/audit.php`.
9. Test schema/factory/unique `live_slot`.

## LS.2 — Backend admin

1. Buat `LivestreamValidationRules` + `LivestreamFormMeta`.
2. Buat `StoreLivestreamRequest`, `UpdateLivestreamRequest`.
3. Buat actions:
   - `SaveLivestreamAction`
   - `StartLivestreamAction`
   - `EndLivestreamAction`
   - `MarkLivestreamUnavailableAction`
4. Buat `LivestreamController` (index/create/store/edit/update/destroy/start/end/unavailable).
5. Buat `LivestreamResource`.
6. Tambah route admin di grup `/admin`.
7. Regenerate Wayfinder.
8. Test CRUD, validasi URL, otorisasi, transisi status, audit.

## LS.3 — UI admin

1. Buat `lang/id/livestreaming.php`.
2. Buat halaman `admin/livestreaming/pages/{index,tambah,ubah}.tsx`.
3. Buat komponen:
   - `livestream-form.tsx`
   - `livestream-status-badge.tsx`
   - `livestream-row-actions.tsx`
   - `livestream-form-instance.ts` (bila diperlukan)
4. Tambah menu sidebar admin + shared ability `livestreams.view`.
5. Test Vitest form dan aksi status.

## LS.4 — Halaman publik

1. Buat `PublicLivestreamController@index`.
2. Buat `PublicLivestreamResource`.
3. Tambah route publik `/livestreaming`.
4. Buat halaman `features/public/livestreaming/pages/index.tsx`.
5. Buat komponen:
   - `components/livestream-player.tsx` (shared)
   - `features/public/livestreaming/components/livestream-archive-card.tsx`
   - `features/public/livestreaming/types.ts`
6. Tambah menu navbar + footer.
7. Test publik + Vitest.

## LS.5 — Homepage

1. `PublicHomeController`: query `Livestream::currentLive()` dan kirim `liveStream` + `sections.livestreaming`.
2. `HomepageProps`/`HomeSections` di `features/public/beranda/types.ts`.
3. `HomeLivestreamSection` di `features/public/beranda/components/`.
4. Render setelah `HomeQuickLinks`, sebelum `HomeNextMassSection`.
5. Test homepage.

## LS.6 — Verifikasi & dokumentasi

1. Jalankan seluruh verifikasi §10.
2. Perbarui `docs/RBAC.md`.
3. Tambah **D-101** di `docs/DECISIONS.md`.
4. Smoke test manual sesuai §10.5.
5. Laporkan perintah dan hasil aktual (jangan klaim tanpa eksekusi).

---

# 7. File yang Akan Terpengaruh

## 7.1 Dibuat — backend

```text
database/migrations/2026_11_01_000000_create_livestreams_table.php
database/migrations/2026_11_01_000001_add_location_id_to_livestreams_table.php
database/factories/LivestreamFactory.php
app/Enums/LivestreamEditorialStatus.php
app/Enums/LivestreamBroadcastStatus.php
app/Enums/LivestreamCategory.php
app/Models/Livestream.php
app/Policies/LivestreamPolicy.php
app/Concerns/LivestreamValidationRules.php
app/Concerns/LivestreamFormMeta.php
app/Actions/Livestream/SaveLivestreamAction.php
app/Actions/Livestream/StartLivestreamAction.php
app/Actions/Livestream/EndLivestreamAction.php
app/Actions/Livestream/MarkLivestreamUnavailableAction.php
app/Http/Controllers/Livestream/LivestreamController.php
app/Http/Controllers/Livestream/PublicLivestreamController.php
app/Http/Requests/Livestream/StoreLivestreamRequest.php
app/Http/Requests/Livestream/UpdateLivestreamRequest.php
app/Http/Resources/Livestream/LivestreamResource.php
app/Http/Resources/Public/Livestream/PublicLivestreamResource.php
lang/id/livestreaming.php
```

## 7.2 Dibuat — frontend

```text
resources/js/components/livestream-player.tsx
resources/js/features/public/livestreaming/pages/index.tsx
resources/js/features/public/livestreaming/components/livestream-archive-card.tsx
resources/js/features/public/livestreaming/types.ts
resources/js/features/public/beranda/components/home-livestream-section.tsx
resources/js/features/admin/livestreaming/pages/index.tsx
resources/js/features/admin/livestreaming/pages/tambah.tsx
resources/js/features/admin/livestreaming/pages/ubah.tsx
resources/js/features/admin/livestreaming/components/livestream-form.tsx
resources/js/features/admin/livestreaming/components/livestream-status-badge.tsx
resources/js/features/admin/livestreaming/components/livestream-row-actions.tsx
resources/js/features/admin/livestreaming/types.ts
```

## 7.3 Dibuat — test

```text
tests/Feature/Livestreaming/LivestreamSchemaTest.php
tests/Feature/Livestreaming/LivestreamCrudTest.php
tests/Feature/Livestreaming/LivestreamAuthorizationTest.php
tests/Feature/Livestreaming/LivestreamStateTransitionTest.php
tests/Feature/Livestreaming/PublicLivestreamArchiveTest.php
tests/Feature/Public/PublicLivestreamHomepageTest.php
tests/js/features/public/livestreaming/livestream-player.test.tsx
tests/js/features/public/livestreaming/livestream-archive.test.tsx
tests/js/features/public/beranda/home-livestream-section.test.tsx
tests/js/features/admin/livestreaming/livestream-form.test.tsx
tests/js/features/admin/livestreaming/livestream-state-actions.test.tsx
tests/js/features/admin/livestreaming/livestream-start-confirmation.test.tsx
tests/e2e/livestreaming.spec.ts
tests/e2e/support/livestreaming.ts
```

## 7.4 Diubah

```text
routes/web.php
app/Http/Controllers/Home/PublicHomeController.php
app/Support/YoutubeEmbed.php
app/Models/Location.php
app/Http/Controllers/MasterData/LocationController.php
app/Http/Middleware/HandleInertiaRequests.php
database/seeders/PermissionSeeder.php
config/audit.php
lang/id/audit.php
resources/js/layouts/public/navigation.ts
resources/js/layouts/public/components/footer/footer-data.ts
resources/js/layouts/public/components/navbar/desktop-nav.tsx
resources/js/layouts/public/components/parish-navbar.tsx
resources/js/layouts/app/app-sidebar-nav.ts
resources/js/features/public/beranda/pages/index.tsx
resources/js/features/public/beranda/types.ts
docs/RBAC.md
docs/DECISIONS.md
tests/Unit/YoutubeEmbedTest.php                    # tambah kasus method baru
tests/Feature/Foundation/RbacFoundationTest.php    # daftar permission
tests/js/components/parish-navbar.test.tsx         # entri Livestreaming
tests/js/components/parish-footer.test.tsx         # entri Livestreaming
tests/js/features/public/beranda/homepage.test.tsx # props liveStream/sections
tests/e2e/support/admin-forms.ts                   # pilih() dengan exact=false
```

## 7.5 Generated — jangan diedit manual

```text
resources/js/routes/livestreaming/**
resources/js/routes/livestreams/**
resources/js/actions/App/Http/Controllers/Livestream/**
resources/js/wayfinder/**
```

Regenerate:

```bash
php artisan wayfinder:generate --with-form
```

## 7.6 Dihapus

Tidak ada file yang dihapus.

---

# 8. Dependency / Configuration Impact

| Item                    | Dampak                                                                                                                            |
| ----------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| Dependency PHP/JS       | **Tidak ada penambahan.** Player memakai iframe YouTube native; tidak ada library baru.                                           |
| `config/media.php`      | **Tidak diubah.** Tidak ada upload/thumbnail lokal.                                                                               |
| Queue                   | Tidak ada job/queue baru.                                                                                                         |
| Shared prop `media`     | Tidak berubah.                                                                                                                    |
| Shared prop `abilities` | +1 key: `livestreams.view`.                                                                                                       |
| Permission & RBAC       | +4 (`livestreams.view/create/update/delete`), 101 → 105. `PermissionSeeder::expectedCount()` otomatis. `docs/RBAC.md` diperbarui. |
| Wayfinder               | Wajib regenerate untuk `@/routes/livestreaming`, `@/routes/livestreams`, dan action controller.                                   |
| Database                | Satu tabel baru + satu unique index; migration-only, tidak mengubah tabel lama.                                                   |
| Cache                   | Tidak ada cache baru dan tidak ada invalidasi cache.                                                                              |
| Dokumentasi             | `DECISIONS.md` (D-101), `RBAC.md`. `docs/roadmap/README.md` **tidak diubah** karena fitur ini di luar phase roadmap.              |

---

# 9. Risiko dan Mitigasi

| #    | Risiko                                                 | Mitigasi                                                                                                                                                      |
| ---- | ------------------------------------------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| R-1  | Status `live` manual bisa terlambat diperbarui         | Admin wajib menekan **Akhiri Siaran**; badge live menonjol di admin; `started_at` ditampilkan; dokumentasi operasional di `DECISIONS.md`.                     |
| R-2  | Dua admin menekan **Mulai** bersamaan                  | `DB::transaction` + `lockForUpdate()` + unique `live_slot`; duplicate-key diubah menjadi validation error yang ramah. Uji dua live langsung ditolak database. |
| R-3  | Replay YouTube tidak selalu tersedia                   | `is_recording_available` default false; hanya admin yang mencentang setelah memastikan replay ada. Arsip tidak pernah mengasumsikan replay otomatis.          |
| R-4  | Video menjadi private/deleted/age-restricted           | Admin dapat menandai `unavailable` atau mengarsipkan; kartu menyediakan link “Buka di YouTube”. Pemeriksaan API ditunda.                                      |
| R-5  | Thumbnail YouTube gagal dimuat                         | Komponen `Image` yang sudah ada menampilkan fallback UI; tidak ada broken image.                                                                              |
| R-6  | Live lama tetap tampil karena cache                    | Tidak memakai cache (D-6); query langsung ke database setiap request.                                                                                         |
| R-7  | `live_slot` menyandera live baru setelah soft delete   | `destroy` mengosongkan `live_slot` sebelum soft delete; `end`/`unavailable` juga mengosongkan. Uji hapus record live.                                         |
| R-8  | Perubahan status editorial saat live merusak invariant | `SaveLivestreamAction` menolak `draft`/`archived` selama live; UI menonaktifkan select status dan menampilkan pesan.                                          |
| R-9  | Navbar publik terlalu padat di 1024px                  | Verifikasi visual 1024/1280px. Bila overflow, ubah breakpoint `DesktopNav` dari `lg` ke `xl` (perubahan kecil yang dilaporkan).                               |
| R-10 | Timezone bergeser (UTC vs WIB)                         | Simpan UTC; form memakai `toIsoWithOffset`/`toDateTimeLocalValue`; tampilan memakai `formatWib*` dengan `displayTimezone`. Uji batas tengah malam.            |
| R-11 | URL YouTube tiruan lolos                               | Hanya `Rule\YoutubeUrl` + `YoutubeEmbed`; uji `youtube.com.evil.test`, scheme salah, ID invalid, URL iframe mentah.                                           |
| R-12 | N+1 / lazy load di strict mode                         | Livestream tidak punya relasi yang dirender; query memakai kolom/scope eksplisit. Tidak ada eager load yang perlu ditambahkan.                                |
| R-13 | `ArchitectureTest` gagal karena URL hard-code          | Semua tautan dari Wayfinder; halaman mengikuti `features/<audience>/<feature>/pages/`.                                                                        |
| R-14 | E2E bergantung pada jaringan YouTube                   | E2E tidak mengklik play/iframe; hanya assert tombol, judul, dan link `watch_url`.                                                                             |
| R-15 | `composer ci:check` gagal karena MySQL tidak berjalan  | Pastikan MySQL 8 aktif sebelum commit; DB test `website_paroki_hspmtb_test` sudah dibuat.                                                                     |

---

# 10. Verification Plan

## 10.1 Backend

```bash
vendor/bin/pint --dirty --format agent
php artisan test tests/Feature/Livestreaming --compact
php artisan test tests/Feature/Public/PublicLivestreamHomepageTest.php --compact
php artisan test tests/Unit/YoutubeEmbedTest.php --compact
```

## 10.2 Frontend

```bash
npm run test:unit -- tests/js/features/public/livestreaming tests/js/features/public/beranda/home-livestream-section.test.tsx tests/js/features/admin/livestreaming
npm run types:check
npm run lint
npm run format:check
php artisan wayfinder:generate --with-form
```

## 10.3 E2E

```bash
npm run build
npm run e2e:prepare
npx playwright test tests/e2e/livestreaming.spec.ts
```

Skenario E2E:

1. Admin login → tambah draft → publish.
2. Admin mulai live → homepage menampilkan section live.
3. Pengunjung membuka `/livestreaming` → player aktif terlihat.
4. Admin akhiri live → section homepage hilang.
5. Admin edit → centang rekaman tersedia → kartu arsip muncul.
6. Filter tahun/kategori.
7. User tanpa permission → 403/redirect.

## 10.4 Gate penuh

```bash
composer ci:check
```

`composer ci:check` menjalankan `npm run check` (lint, format, typecheck, Vitest) lalu `composer test` (config clear, Pint `--test`, PHPStan, Pest).

## 10.5 Smoke test manual

1. Login sebagai Super Admin.
2. Buat draft dengan `https://youtu.be/dQw4w9WgXcQ`; publish.
3. Klik **Mulai Siaran**.
4. Buka `/`:
   - section “Ikuti Misa Secara Online” tampil setelah quick links dan sebelum “Misa Berikutnya”;
   - badge `SEDANG LIVE` terlihat;
   - player hanya memuat iframe setelah tombol play diklik;
   - tombol “Tonton di YouTube” membuka tab baru.
5. Buka `/livestreaming`:
   - player live tampil di atas grid;
   - arsip kosong menampilkan empty state, bukan player kosong.
6. Mulai siaran kedua:
   - siaran pertama berakhir otomatis;
   - hanya satu badge/live row di admin.
7. Akhiri siaran:
   - section homepage hilang tanpa menyisakan ruang kosong;
   - `/livestreaming` tetap 200.
8. Edit record → centang “Rekaman tersedia”:
   - kartu arsip muncul;
   - filter search/kategori/tahun dan pagination bekerja;
   - query filter dipertahankan saat pindah halaman.
9. Coba URL `https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ` → ditolak.
10. Akses `/admin/livestreaming` sebagai user tanpa permission → 403.
11. Cek layout 1280px, 1024px, 768px, 375px; tidak ada horizontal overflow.

---

# 11. Test Matrix

## 11.1 Pest — domain & schema

| Test                            | Cakupan                                                                                                                                                                                                                                                                               |
| ------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `LivestreamSchemaTest`          | Default `draft/scheduled`; cast enum; factory state; unique `live_slot` menolak dua live; soft delete; kolom/index ada.                                                                                                                                                               |
| `LivestreamCrudTest`            | Create/update/delete; ekstraksi ID dari `watch`/`youtu.be`/`embed`/`shorts`/`live`; tolak host tiruan/ID invalid; `published_at` sekali; `recorded_at` default; audit created/updated/published/archived.                                                                             |
| `LivestreamAuthorizationTest`   | Guest redirect; user non-admin 403; permission view/create/update/delete; start/end/unavailable butuh `update`.                                                                                                                                                                       |
| `LivestreamStateTransitionTest` | Start mengisi `started_at`/`live_slot`; start kedua mengakhiri yang lama; end mengisi `ended_at`/`recorded_at`; unavailable menolak saat live; unpublish/archive saat live ditolak; start dari ended ditolak; audit sukses/gagal.                                                     |
| `PublicLivestreamArchiveTest`   | Hanya `published + ended + recording available`; draft/archived/unavailable/scheduled/ended tanpa rekaman/soft-deleted tidak muncul; urutan; filter search/kategori/tahun; pagination 12; `withQueryString`; `years` hanya publik; `activeStream` terpisah; tidak ada field internal. |
| `PublicLivestreamHomepageTest`  | Section tampil hanya `published + live`; `liveStream = null` + `sections.livestreaming = false` saat kosong; arsip tidak menggantikan live; props tidak bocor.                                                                                                                        |
| `YoutubeEmbedTest` (ubah)       | `embedUrl`, `watchUrl`, `thumbnailUrl`, ID invalid, host allowlist.                                                                                                                                                                                                                   |

## 11.2 Vitest — frontend

| Test                                | Cakupan                                                                                                                               |
| ----------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| `livestream-player.test.tsx`        | Iframe tidak ada sebelum klik; setelah klik `src` memakai `youtube-nocookie.com`; `title` iframe benar; tombol punya accessible name. |
| `livestream-archive.test.tsx`       | Kartu merender judul/tanggal/kategori; empty state; filter mengirim query yang benar.                                                 |
| `home-livestream-section.test.tsx`  | Tidak render saat `stream = null`/flag false; render saat ada live; link YouTube benar.                                               |
| `livestream-form.test.tsx`          | Field wajib, status editorial, tanggal, submit payload; error server tampil di field yang benar.                                      |
| `livestream-state-actions.test.tsx` | Dialog mulai/akhiri memanggil endpoint yang benar; konfirmasi live lain muncul; tombol disabled saat proses.                          |
| `livestream-start-confirmation.test.tsx` | Dialog menyebut judul live yang akan diakhiri saat memulai stream kedua.                                                         |

## 11.3 Playwright — E2E

Satu spec `tests/e2e/livestreaming.spec.ts` untuk skenario §10.3. Gunakan
`data-test` pada tombol status, badge live, kartu arsip, dan filter agar stabil.
Jangan mengklik iframe YouTube.

---

# 12. Acceptance Criteria

## 12.1 Admin

- [ ] Pengguna tanpa permission tidak dapat mengakses daftar/form/aksi, termasuk lewat URL langsung.
- [ ] Admin dapat membuat draft dengan URL YouTube valid.
- [ ] URL host non-YouTube, host tiruan, dan ID invalid ditolak dengan pesan validasi.
- [ ] Video ID diekstrak server-side; `youtube_video_id` kiriman browser diabaikan.
- [ ] Admin dapat mengubah metadata dan menyimpan perubahan.
- [ ] Admin dapat menerbitkan dan mengarsipkan lewat field `status` di form.
- [ ] Tidak ada lebih dari satu siaran aktif, termasuk saat dua request mulai bersamaan.
- [ ] Admin mendapat konfirmasi saat memulai live baru sementara live lain aktif.
- [ ] Aksi akhiri menghapus live dari homepage.
- [ ] Rekaman hanya muncul di arsip setelah `is_recording_available` dicentang dan `status = published`.
- [ ] Perubahan penting tercatat di Audit Log.

## 12.2 Homepage

- [ ] Section live berada setelah hero/quick links dan sebelum Jadwal Liturgi.
- [ ] Section tampil hanya jika ada record `published + live` yang valid.
- [ ] Jika tidak ada live, section tidak dirender dan tidak menyisakan whitespace.
- [ ] Menu Livestreaming tetap terlihat walau tidak ada live.
- [ ] Homepage tidak menampilkan arsip sebagai pengganti live.
- [ ] Props tidak memuat field internal.

## 12.3 Halaman arsip

- [ ] `/livestreaming` dapat diakses tanpa login.
- [ ] Halaman mengikuti shell dan pola visual Galeri.
- [ ] Siaran aktif, jika ada, tampil sebelum grid arsip.
- [ ] Hanya rekaman publik yang tampil di grid.
- [ ] Urutan terbaru ke terlama konsisten.
- [ ] Search/filter kategori/tahun bekerja dan query dipertahankan saat pagination.
- [ ] Pagination 12 item per halaman.
- [ ] Empty state tampil saat tidak ada data atau hasil filter kosong.
- [ ] Video dapat diputar lewat embed privacy-enhanced atau dibuka di YouTube.
- [ ] Layout responsif desktop/tablet/mobile tanpa overflow.

## 12.4 Keamanan & kualitas

- [ ] Route admin memakai middleware yang sama dengan modul admin lain.
- [ ] Semua aksi dilindungi Policy/Gate di backend.
- [ ] Semua field input divalidasi server-side.
- [ ] Tidak ada API secret/token di client bundle atau props.
- [ ] Draft/archived/unavailable/soft-deleted tidak dapat diakses publik.
- [ ] Satu live aktif dijaga transaksional + unique index.
- [ ] Tidak ada N+1 pada daftar arsip.
- [ ] Lint, typecheck, Pest, Vitest, dan E2E targeted lulus.
- [ ] Wayfinder sudah digenerate ulang.

---

# 13. Yang Sengaja Tidak Dikerjakan

- Integrasi YouTube Data API/OAuth, sinkronisasi channel/playlist, verifikasi live otomatis.
- Upload, transcoding, atau penyimpanan file video di server.
- Upload thumbnail kustom / `thumbnail_media_id` / cache thumbnail lokal.
- Halaman detail video dan kolom `slug`.
- Menampilkan `scheduled` di homepage atau arsip.
- CRUD kategori livestreaming (master data).
- Cache query live dan invalidasi cache.
- Kolom catatan admin.
- Live chat, komentar, donasi, polling, analytics penonton.
- Notifikasi email/push dan pembuatan jadwal otomatis dari jadwal misa.
- Structured data `VideoObject` (durasi/upload date belum tersedia).
- Perubahan `config/media.php`, pipeline Media, atau queue.
- Redesign navbar/footer/design system di luar penambahan menu.

---

# 14. Keputusan Minor yang Diasumsikan

| #    | Keputusan minor   | Nilai & justifikasi                                                                 |
| ---- | ----------------- | ----------------------------------------------------------------------------------- |
| m-1  | Nama route        | `livestreaming.index`; `livestreams.*` untuk admin.                                 |
| m-2  | Nama halaman      | `public/livestreaming`, `admin/livestreaming`, `admin/livestreaming/{tambah,ubah}`. |
| m-3  | Tabel & model     | `livestreams`, `App\Models\Livestream`.                                             |
| m-4  | Pagination        | Publik 12; admin default 10 dengan opsi 10/15/25/50.                                |
| m-5  | Urutan publik     | `recorded_at DESC`, `id DESC`; null di akhir.                                       |
| m-6  | Urutan admin      | `updated_at DESC`, tie-breaker `id`.                                                |
| m-7  | Thumbnail         | `hqdefault.jpg`, crop 16:9 di UI, fallback `Image`.                                 |
| m-8  | Status editorial  | Field form; tanpa permission/endpoint publish terpisah.                             |
| m-9  | Status siaran     | Hanya lewat aksi POST; tidak ada select bebas di form.                              |
| m-10 | Replay            | Dicentang di form edit, bukan dialog akhir.                                         |
| m-11 | `live_slot`       | Kolom nullable unique; dikosongkan pada end/unavailable/delete.                     |
| m-12 | `location_id`     | FK nullable ke master `locations`; select hanya lokasi aktif.                       |
| m-13 | Preview video     | Tidak ada; menghindari parser YouTube duplikat.                                     |
| m-14 | Structured data   | Tidak ada; metadata durasi/upload date belum tersedia.                              |
| m-15 | Navbar breakpoint | Dinaikkan dari `lg` ke `xl` setelah verifikasi 1024px menunjukkan overflow. |

Semua m-x dapat dibalik tanpa membatalkan plan.

---

# 15. Status

**Implemented — 1 November 2026.**

Seluruh task LS.1–LS.6 selesai pada branch `refactor/refactor-code`. Tidak ada
commit, push, merge, atau Pull Request. Keputusan resmi dicatat sebagai
**D-101** di `docs/DECISIONS.md`; empat permission baru dicatat di
`docs/RBAC.md`.

Hasil verifikasi aktual ada di §16. Acceptance criteria §12 terpenuhi, dengan
satu catatan responsif: navbar publik memakai drawer sampai breakpoint `xl`
karena delapan item tidak muat pada 1024px.

---

# 16. Implementation Record

## 16.1 Hasil verifikasi

| Perintah                                                              | Hasil                                                          |
| --------------------------------------------------------------------- | -------------------------------------------------------------- |
| `php artisan test --compact --exclude-group=mailpit`                   | **1725 tests, 10419 assertions, passed** (≈15m 50s; sebelum revisi lokasi) |
| `php artisan test tests/Feature/MassSchedule tests/Feature/Public tests/Feature/Livestreaming tests/Feature/MasterData tests/Unit/ArchitectureTest.php` | **369 tests, 2804 assertions, passed** (revisi lokasi) |
| `php artisan test tests/Feature/Audit tests/Feature/Foundation`        | **154 tests, 2644 assertions, passed**                         |
| `npm run test:unit`                                                   | **43 files, 400 tests, passed**                                |
| `npm run types:check`                                                 | passed                                                         |
| `npm run lint`                                                        | passed                                                         |
| `npm run format:check`                                                | passed                                                         |
| `npm run build`                                                       | passed                                                         |
| `vendor/bin/phpstan analyse --no-progress`                            | passed, 0 errors                                               |
| `vendor/bin/pint --dirty --format agent`                              | passed (memperbaiki urutan import `PublicHomeController`)      |
| `composer ci:check`                                                   | dihentikan setelah `npm run check`, Pint, dan PHPStan lulus; full Pest dijalankan terpisah dan lulus. |
| `E2E_PORT=8002 npx playwright test tests/e2e/livestreaming.spec.ts`   | **2 tests passed**                                             |
| Pemeriksaan viewport 1024px/1280px                                    | drawer pada 1024px, desktop nav pada 1280px, tanpa overflow    |

## 16.2 Catatan implementasi

- Migration menggunakan nama `2026_11_01_000000_create_livestreams_table.php`.
- `live_slot` adalah kolom nullable unique; diuji dengan dua record live
  langsung untuk memastikan database menolak yang kedua.
- Lokasi livestreaming memakai `location_id` FK ke master `locations`; select
  admin hanya menampilkan lokasi aktif, lokasi tetap opsional, dan hapus lokasi
  diblokir bila masih dipakai. Resource publik tetap mengirim nama lokasi
  sebagai string.
- Migration revisi `2026_11_01_000001_add_location_id_to_livestreams_table.php`
  menambahkan FK, backfill nama yang cocok, lalu menghapus kolom teks `location`.
- `YoutubeEmbed` sekarang menyediakan `embedUrl`, `watchUrl`, dan
  `thumbnailUrl`; tidak ada parser YouTube kedua di React.
- Navbar publik dinaikkan dari `lg` ke `xl` setelah verifikasi 1024px
  menunjukkan empat item terakhir terpotong. Tablet memakai drawer.
- E2E memakai viewport 1280×1000 khusus spec Livestreaming agar opsi Radix
  Select di dekat fold tetap berada di dalam viewport. E2E tidak mengklik
  iframe YouTube.
- E2E final dijalankan dengan `E2E_PORT=8002` karena `composer dev` sedang
  memakai port 8000 dengan database pengembangan.
- Tidak ada cache query live, sesuai D-101.
- `tests/e2e/.auth.json` dibuat ulang oleh `npm run e2e:prepare`; file itu
  di-gitignore.

## 16.3 Batasan yang tersisa

- Status live tetap manual; admin harus menekan **Akhiri Siaran**.
- Replay tidak otomatis dianggap tersedia.
- `unavailable` setelah `ended` tidak punya jalur balik; buat record baru.
- Backfill lokasi hanya mencocokkan nama persis (case-insensitive); teks bebas
  yang tidak cocok akan hilang saat kolom lama di-drop.
- Lokasi nonaktif tetap tampil pada record lama, tetapi form edit meminta lokasi
  aktif atau kosong.
- Thumbnail YouTube bergantung pada ketersediaan gambar dari YouTube.
- Tabel admin dapat digulir horizontal pada viewport sempit; kolom aksi
  dijangkau dengan scroll, mengikuti `Table` yang sudah ada.
- `docs/specifications/spec-livestreaming.md` dan plan ini belum di-commit;
  keduanya masih untracked sampai ada instruksi commit.

**Konfirmasi eksplisit:** fitur ini tidak mengubah pipeline Media, tidak
menambah dependency, dan tidak menyentuh Phase 10/11 roadmap.
