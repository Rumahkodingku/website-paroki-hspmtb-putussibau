# Implementation Plan — PHASE 9B

> **Project:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau\
> **Phase:** 9B — Admin Dashboard\
> **Status:** Implemented — engineering complete (branch `feat/phase-9b-admin-dashboard`)\
> **Branch (usulan):** `feat/phase-9b-admin-dashboard`\
> **Commit dasar (audit):** `main` @ `f4229fb` (merge Phase 9A)\
> **Spesifikasi:** `docs/roadmap/phase-9b-admin-dashboard.md`\
> **Sumber kebenaran:** `PRD.md` → `ARCHITECTURE.md` → `DESIGN-v2.md` → `AGENTS.md` → `DECISIONS.md`

Dokumen roadmap Phase 9B menjelaskan _apa_ yang harus ada. Dokumen ini menjelaskan
_bagaimana_ perubahannya, dalam urutan apa, file mana yang tersentuh, dan bagaimana
diverifikasinya. Dokumen ini pelengkap roadmap, bukan pengganti.

> Dokumen ini disusun setelah audit codebase terhadap kondisi repository saat ini
> (`main` @ `f4229fb`) dan **empat keputusan yang sudah dijawab pengguna** melalui
> tool `question`. Seluruh keputusan di §9 mengikat implementasi. Dokumen ini
> disusun sebelum implementasi; statusnya diperbarui di §10 setelah gate lulus.

---

## Daftar Isi

- [Implementation Plan — PHASE 9B](#implementation-plan--phase-9b)
  - [Daftar Isi](#daftar-isi)
  - [1. Kondisi Codebase Saat Ini](#1-kondisi-codebase-saat-ini)
    - [1.1 Dashboard saat ini](#11-dashboard-saat-ini)
    - [1.2 Sumber data — terverifikasi](#12-sumber-data--terverifikasi)
    - [1.3 Fondasi frontend](#13-fondasi-frontend)
  - [2. Task Phase 9B yang Akan Dikerjakan](#2-task-phase-9b-yang-akan-dikerjakan)
  - [3. Implementation Plan](#3-implementation-plan)
    - [3.1 Definisi kontrak metrik (PH9B-02) — MENGIKAT](#31-definisi-kontrak-metrik-ph9b-02--mengikat)
    - [3.2 Fase A — Backend (PH9B-03)](#32-fase-a--backend-ph9b-03)
    - [3.3 Fase B — Frontend (PH9B-04, PH9B-05)](#33-fase-b--frontend-ph9b-04-ph9b-05)
    - [3.4 Fase C — Test (PH9B-06)](#34-fase-c--test-ph9b-06)
    - [3.5 Fase D — Dokumentasi (PH9B-07)](#35-fase-d--dokumentasi-ph9b-07)
  - [4. File yang Akan Terpengaruh](#4-file-yang-akan-terpengaruh)
    - [4.1 Baru](#41-baru)
    - [4.2 Diubah](#42-diubah)
    - [4.3 Tidak diubah](#43-tidak-diubah)
  - [5. Dependency / Configuration Impact](#5-dependency--configuration-impact)
  - [6. Risiko dan Potensi Konflik](#6-risiko-dan-potensi-konflik)
  - [7. Verification Plan](#7-verification-plan)
  - [8. Acceptance Criteria yang Akan Terpenuhi](#8-acceptance-criteria-yang-akan-terpenuhi)
    - [8.1 AC Phase 9B](#81-ac-phase-9b)
    - [8.2 AC PRD](#82-ac-prd)
  - [9. Pertanyaan / Ambiguity](#9-pertanyaan--ambiguity)
  - [10. Status](#10-status)

---

## 1. Kondisi Codebase Saat Ini

Audit dilakukan pada `main` @ `f4229fb`. `git status` bersih kecuali satu file
untracked: `docs/roadmap/phase-9b-admin-dashboard.md` (dokumen fase ini). Tidak ada
file kode yang diubah saat audit.

### 1.1 Dashboard saat ini

| Area | Kondisi aktual | File |
| --- | --- | --- |
| Route Dashboard | `Route::inertia('/', 'admin/dashboard')->name('dashboard')` — belum ada controller | `routes/web.php:128` |
| Middleware | Grup `/admin`: `auth`, `verified`, `EnsureAccountIsActive`, `EnsureAdminAccess` | `routes/web.php:120` |
| Otorisasi | `EnsureAdminAccess` → `Gate::authorize('admin.access')`; `super_admin` lolos via `Gate::before` | `app/Http/Middleware/EnsureAdminAccess.php:32`, `app/Providers/AppServiceProvider.php:108` |
| Halaman | Masih `PlaceholderPattern` (36 baris), breadcrumb `[{ title: 'Dashboard', href: dashboard() }]` | `resources/js/features/admin/dashboard/pages/index.tsx` |
| Test | Hanya guest redirect + super admin 200 | `tests/Feature/DashboardTest.php` |
| Helper test | `superAdmin()` tersedia (seed role + permission) | `tests/Pest.php:114` |

> Catatan: `plan-design-v2-migration.md` M5.2 menyebut "Dashboard admin" selesai —
> itu **restyle token placeholder**, bukan dashboard berisi. Phase 9B mengisi kontennya.

### 1.2 Sumber data — terverifikasi

| Modul | Sumber | Status & scope aktual |
| --- | --- | --- |
| Berita | `posts` | `PostStatus draft/scheduled/published/archived` (native enum) + `published_at`. `Post::scopePublished()` = `status=published AND published_at IS NOT NULL AND published_at <= now()` (`app/Models/Post.php:147`). SoftDeletes. |
| Agenda | `events` | `is_published` boolean **terpisah** dari `EventStatus scheduled/cancelled/postponed`. `Event::scopePublished()` = `is_published=true` (`app/Models/Event.php:128`); `scopeUpcomingFromToday()` = `start_at >=` awal hari di `Asia/Pontianak` (`app/Models/Event.php:147`). SoftDeletes. |
| Dokumen | `documents` | `DocumentStatus draft/published/archived`; `scopePublished()` = `status=published AND published_at IS NOT NULL` (tidak membandingkan `now()`); `expires_at` date nullable + `isExpired()` (`app/Models/Document.php:127,156`, false bila null). SoftDeletes. |
| Jadwal Misa rutin | `mass_schedules` | `is_active`; `scopeActive()` = active + lokasi aktif (`app/Models/MassSchedule.php:93`). `start_time` wall-clock, **tidak di-cast**. SoftDeletes + generated `active_marker`. |
| Misa Khusus | `special_masses` | `is_active` + `scopeUpcoming()` = active, `start_at >= now()`, lokasi aktif (`app/Models/SpecialMass.php:92`). SoftDeletes. |
| Pemberitahuan | `schedule_notices` | `scopeCurrentlyVisible()` = `is_active AND starts_at<=now AND (ends_at null OR >=now)` (`app/Models/ScheduleNotice.php:132`). SoftDeletes. |
| Pelayanan | `services` | `is_active` (default **false**), kolom `form_url` (bukan `google_form_url`). `hasFormUrl()`, `usesGoogleForm()` = hasFormUrl && !is_emergency, `isAcceptingRegistrations()`. **`hasGoogleForm()` tidak ada**; **tidak ada aturan "form wajib"**. SoftDeletes. |
| Media | `media` | `MediaStatus pending/processing/ready/failed`; `alt_text` nullable; **tanpa SoftDeletes, tanpa scope, tanpa Policy, tanpa halaman pustaka/index media**, tanpa tombol retry. Direferensikan 10+ tabel via `*_media_id`. |
| Galeri | `gallery_albums` | `is_published` boolean + `published_at`. SoftDeletes. |

### 1.3 Fondasi frontend

| Area | Kondisi aktual | File |
| --- | --- | --- |
| Preseden aggregation page | `PublicHomeController` — controller dengan private query methods, `array_values(Resource::collection()->resolve())`, reuse resolver | `app/Http/Controllers/Home/PublicHomeController.php` |
| Layout | `app.tsx` memetakan `admin/*` → `AppLayout`; page mengisi `.layout.breadcrumbs` | `resources/js/app.tsx:62`, `resources/js/layouts/app-layout.tsx` |
| Komponen reusable | `Heading`, `Text`, `EmptyState`, `ListHeader`/`ListEmpty`/`StatusBadge`, `ParishImage` (fallback `onError` → `ImageOff`), `ui/card`, `ui/badge` | `resources/js/components/` |
| Formatter | `formatWibDate`, `formatWibDateTime`, `DISPLAY_TIMEZONE` | `resources/js/lib/datetime.ts` |
| StatCard/metric | **Belum ada** — harus dibuat baru | — |
| Badge status | Ada **per-feature** (`berita`, `agenda`, `dokumen`); dilarang diimpor lintas feature | `tests/Unit/ArchitectureTest.php:313` |
| Tipe | `SharedProps`, `Paginated<T>` | `resources/js/types/index.ts`, `resources/js/types/pagination.ts` |
| Test frontend | `vp test` (Vitest) di `tests/js/`, Playwright di `tests/e2e/`, helper `signIn`/`resetLoginRateLimit` | `tests/js/support/inertia.tsx`, `tests/e2e/support/` |

---

## 2. Task Phase 9B yang Akan Dikerjakan

Mengganti placeholder Dashboard menjadi **agregator read-only**, mengikuti
`PH9B-01` s/d `PH9B-07` dan `AC-01`..`AC-05`:

1. Controller tipis `DashboardController` + props terstruktur (PH9B-03).
2. Lima kartu ringkasan: Berita, Agenda, Dokumen, Jadwal Misa, Pelayanan.
3. Panel Aksi Cepat (4 pintasan) + Agenda Terdekat.
4. Panel Berita Terbaru + Perlu Perhatian.
5. Panel Konten Terbaru (gabungan).
6. Test Pest (+ Vitest/Playwright bila perlu) dan update roadmap/DECISIONS setelah gate.

**Tidak termasuk (roadmap §13):** analytics/trafik, pengunjung unik, tren bulanan,
audit log admin, approval workflow, notifikasi baru, CRUD/tabel statistik Dashboard,
kartu rinci kategori/komunitas/area/pastor/pengurus/total akun, dependency baru.

---

## 3. Implementation Plan

### 3.1 Definisi kontrak metrik (PH9B-02) — MENGIKAT

Semua definisi memakai scope/model aktual; tidak ada query paralel. "Publik"
selalu berarti definisi scope publik modul masing-masing.

**Kartu 1 — Berita (`posts`)**

| Angka | Definisi |
| --- | --- |
| Total | Semua post non-deleted |
| Draf | `status = draft` |
| Terjadwal | `status = scheduled` |
| Terbit | `Post::published()` (`status=published AND published_at IS NOT NULL AND published_at <= now()`) |
| Diarsipkan | `status = archived` |

> Scheduled **tidak** dihitung sebagai publik. `published_at` di masa depan bukan "Terbit".

**Kartu 2 — Agenda (`events`)**

| Angka | Definisi |
| --- | --- |
| Terbit & mendatang | `Event::published()->upcomingFromToday()` |
| Draf | `is_published = false` |
| Terjadwal | `is_published = true AND status = scheduled` |
| Dibatalkan | `is_published = true AND status = cancelled` |
| Ditunda | `is_published = true AND status = postponed` |

> Publikasi (`is_published`) **dipisah** dari status kegiatan. Agenda lewat tidak masuk
> "mendatang"; cancelled/postponed tetap dilabeli, bukan disamarkan sebagai normal.

**Kartu 3 — Dokumen (`documents`)**

| Angka | Definisi |
| --- | --- |
| Total | Semua dokumen non-deleted |
| Draf / Terbit / Arsip | Berdasarkan `DocumentStatus` |
| Kedaluwarsa | `expires_at IS NOT NULL AND expires_at < today(display_timezone)` (indikator perhatian; `null` diabaikan) |

> "Terbit" memakai `Document::published()` (`status=published AND published_at NOT NULL`).
> Kedaluwarsa adalah fakta tanggal, bukan status publikasi — keduanya dibedakan.

**Kartu 4 — Jadwal Misa (tiga hitungan terpisah, tidak dicampur)**

| Angka | Definisi |
| --- | --- |
| Misa rutin aktif | `MassSchedule::active()->count()` |
| Misa khusus mendatang | `SpecialMass::upcoming()->count()` |
| Pemberitahuan berlaku | `ScheduleNotice::currentlyVisible()->count()` |

**Kartu 5 — Pelayanan (`services`)**

| Angka | Definisi |
| --- | --- |
| Aktif | `is_active = true` |
| Nonaktif | `is_active = false` |

> Tidak ada hitungan "form belum diisi" — tidak ada aturan "form wajib" (lihat §9).

**Panel — Agenda Terdekat (3–5 item)**
`Event::published()->upcomingFromToday()->with(['category','location'])->orderBy('start_at')->orderBy('id')->limit(5)`.
Field: `title`, `status` (value + label), `start_at`/`end_at` (ISO), `is_all_day`, `location_name`, `edit_url`.

**Panel — Berita Terbaru (3 item)**
`Post::published()->with(['category','media.variants'])->orderByDesc('published_at')->orderByDesc('id')->limit(3)`.
Field: `title`, `status` (value + label), `published_at`, `image`/`image_alt` (null bila tidak ada), `edit_url`.

**Panel — Perlu Perhatian (hanya yang lolos keputusan §9)**

1. **Dokumen kedaluwarsa** — `Document` non-deleted, `expires_at NOT NULL AND expires_at < today(display_timezone)`; link ke `documents.index`.
2. **Media tanpa alt text (scoped)** — hasilkan **daftar konten** (bukan hitungan media mentah) yang gambarnya tidak punya `alt_text`, dibatasi modul ber-edit-route tunggal:
   - `posts.media_id` → hanya post `published`
   - `events.media_id` → hanya event `is_published`
   - `services.thumbnail_media_id` → hanya service `is_active`

   Setiap item = `{ type, title, edit_url }` (cap 5, plus total count). Media dengan
   `status != ready` dikecualikan. Orphan media dan tabel lain (galeri, komunitas,
   pastor, pengurus, sejarah, wilayah, hero setting) **tidak** dihitung di fase ini —
   gap dicatat di `docs/DECISIONS.md`.

**Panel — Konten Terbaru (5–8 item, gabungan)**
Tiga query terbatas (posts/events/documents, eager load yang perlu) → merge urut
`updated_at` desc → slice 5–8. Field: `type`, `title`, `status_label`, `updated_at`,
`edit_url`. `updated_at` = "Terakhir diperbarui", **bukan** pelaku perubahan; bukan audit log.

### 3.2 Fase A — Backend (PH9B-03)

1. Ganti `routes/web.php:128`:

   ```php
   Route::get('/', DashboardController::class)->name('dashboard');
   ```

   Nama route tetap `dashboard` agar `dashboard()` (breadcrumb/sidebar) dan Wayfinder tidak berubah.
2. Buat `app/Http/Controllers/Dashboard/DashboardController.php` (`__invoke`), mengikuti
   preseden `PublicHomeController`: query agregasi di private methods, tanpa service
   baru (abstraksi hanya bila benar-benar perlu). `Gate::authorize('admin.access')`
   opsional karena middleware sudah menjaganya; tidak ada izin per-model.
3. Buat resource di `app/Http/Resources/Dashboard/`:
   - `RecentPostResource`
   - `UpcomingEventResource`
   - Konten Terbaru dibangun sebagai **array terkontrol** di controller (gabungan 3 model).
4. Semua agregasi pakai `count()`/conditional aggregate; **jangan** memuat seluruh tabel
   lalu menghitung di React. Semua list dibatasi di query, eager load relasi yang dirender
   (anti N+1, Eloquent strict mode aktif).
5. Hormati soft delete (scope bawaan), timezone `display_timezone`, dan jangan kirim raw
   model/field sensitif (`->missing()` di test).
6. Props akhir (mirror TS):

   ```php
   return Inertia::render('admin/dashboard', [
       'summary' => [
           'posts' => [...], 'events' => [...], 'documents' => [...],
           'massSchedules' => [...], 'services' => [...],
       ],
       'upcomingEvents' => array_values(UpcomingEventResource::collection($events)->resolve()),
       'recentPosts'    => array_values(RecentPostResource::collection($posts)->resolve()),
       'attentionItems' => [...],
       'recentContent'  => [...],
   ]);
   ```

7. Jalankan `php artisan wayfinder:generate --with-form` setelah route berubah.

### 3.3 Fase B — Frontend (PH9B-04, PH9B-05)

1. `resources/js/features/admin/dashboard/types.ts` — `DashboardProps` + tipe item.
2. Komponen di `resources/js/features/admin/dashboard/components/` (satu konsumen →
   feature-scoped, **bukan** `components/`):
   - `dashboard-header.tsx` — judul + deskripsi singkat + tombol "Lihat Website" → `home()`
   - `summary-cards.tsx` — 5 kartu (angka + label + sub-rincian; status tekstual)
   - `quick-actions.tsx` — 4 tombol Wayfinder: `posts.create`, `events.create`, `mass-schedules.list`, `settings.edit`
   - `upcoming-events-panel.tsx` — status + tanggal WIB + lokasi + link edit + empty state + link modul Agenda
   - `recent-posts-panel.tsx` — judul + status + tanggal; thumbnail via `ParishImage` (fallback `onError`, **tanpa URL dummy**)
   - `attention-panel.tsx` — kondisional; hanya item yang disetujui
   - `recent-content-panel.tsx` — jenis, judul, status, "Terakhir diperbarui", link
   - `status-text.tsx` — label status lokal ATAU pakai `status_label` dari backend (menghindari impor badge lintas-feature)
3. `index.tsx` — compose section sesuai roadmap §5; `.layout.breadcrumbs` tetap;
    semua panel punya empty state; data kosong → 0 dan panel kosong rapi.
4. Responsif 360/390/768/1024/1440; panel bertumpuk di mobile dengan urutan masuk akal;
    tanpa horizontal overflow; status tidak hanya warna; heading hierarchy; keyboard & focus state.

### 3.4 Fase C — Test (PH9B-06)

 1. Perluas `tests/Feature/DashboardTest.php` (jangan hapus assertion lama) + test agregasi:
    - draft/scheduled tidak dihitung sebagai publik (post);
    - agenda lewat/cancelled/postponed tidak dihitung sebagai agenda normal;
    - dokumen kedaluwarsa benar dan `expires_at = null` diabaikan;
    - rutin/khusus/notice dihitung dengan definisi masing-masing;
    - limit list; DB kosong tidak exception; batas waktu pakai timezone paroki;
    - props tidak membocorkan field sensitif;
    - user terverifikasi tanpa `admin.access` → 403.
 2. Vitest opsional `tests/js/features/admin/dashboard/dashboard-index.test.tsx`
    (render, empty state, quick link) — pola `kategori-dokumen-index.test.tsx`.
 3. E2E `tests/e2e/dashboard.spec.ts` — sign in, ringkasan tampil, aksi cepat
    navigasi nyata (klik + cek URL/target), mobile viewport, data kosong aman.

### 3.5 Fase D — Dokumentasi (PH9B-07)

 1. `docs/roadmap/README.md` — tandai 9B **hanya setelah gate lulus**.
 2. `docs/DECISIONS.md` — definisi statistik, metrik yang dikeluarkan (media failed,
    Google Form), dan cakupan `Media tanpa alt text` yang dibatasi 3 modul.

---

## 4. File yang Akan Terpengaruh

### 4.1 Baru

| File | Alasan |
| --- | --- |
| `app/Http/Controllers/Dashboard/DashboardController.php` | Agregasi Dashboard; `Route::inertia` tidak cukup untuk props |
| `app/Http/Resources/Dashboard/RecentPostResource.php` | Shape kontrak Inertia untuk Berita Terbaru |
| `app/Http/Resources/Dashboard/UpcomingEventResource.php` | Shape kontrak Inertia untuk Agenda Terdekat |
| `resources/js/features/admin/dashboard/types.ts` | `DashboardProps` + tipe item (mirror props PHP) |
| `resources/js/features/admin/dashboard/components/*.tsx` | 7–8 komponen feature-scoped (belum ada StatCard) |
| `tests/js/features/admin/dashboard/dashboard-index.test.tsx` | (opsional) perilaku komponen kunci |
| `tests/e2e/dashboard.spec.ts` | E2E admin Dashboard |

### 4.2 Diubah

| File | Perubahan |
| --- | --- |
| `routes/web.php:128` | `Route::inertia` → `Route::get('/', DashboardController::class)` (nama tetap `dashboard`) |
| `resources/js/features/admin/dashboard/pages/index.tsx` | Placeholder → Dashboard dinamis |
| `tests/Feature/DashboardTest.php` | Tambah test agregasi/otorisasi (tanpa menghapus yang lama) |
| `docs/roadmap/README.md` | Tandai Phase 9B (setelah gate) |
| `docs/DECISIONS.md` | Keputusan & definisi metrik baru |
| `resources/js/routes`, `resources/js/actions` | **Generated** Wayfinder — regenerasi, jangan diedit tangan |

### 4.3 Tidak diubah

- Model/migration — **tidak ada** perubahan schema.
- `HandleInertiaRequests` shared props — props dashboard adalah page props.
- `tests/js/support/inertia.tsx` — hanya berubah bila `SharedProps` berubah (tidak berubah).
- Badge status feature — dilarang diimpor dashboard (ArchitectureTest).

---

## 5. Dependency / Configuration Impact

- **Tidak ada dependency baru** (roadmap §34/§392 melarang).
- **Route:** satu-satunya perubahan registrasi; nama `dashboard` dipertahankan → semua
  breadcrumb `dashboard()` dan item sidebar tetap valid.
- **Wayfinder:** wajib regen setelah route berubah (otomatis saat `npm run dev`/`build`).
- **Timezone:** `config/app.php:display_timezone` = `Asia/Pontianak` dipakai untuk
  "agenda mendatang" dan "dokumen kedaluwarsa".
- **Tanpa cache, tanpa job/queue, tanpa seeder produksi.** Data kosong = 0, bukan diisi seed.
- **Gate:** `composer ci:check` (lint, typecheck, Pest, Vitest) + `npm run build` + `npm run e2e`.

---

## 6. Risiko dan Potensi Konflik

1. **Cross-feature import (ArchitectureTest:313)** — Dashboard dilarang mengimpor badge
   `berita`/`agenda`/`dokumen`. Mitigasi: label status dari backend (`status_label`) atau
   komponen lokal `status-text.tsx`.
2. **`components/` vs `features/` (AGENTS.md)** — StatCard hanya dipakai Dashboard → wajib
   di `features/admin/dashboard/components/`, bukan `components/`.
3. **Indikator "Perlu Perhatian" tanpa jalur perbaikan** — media tanpa alt text & media
   failed: tidak ada halaman pustaka media dan tidak ada retry. Mitigasi: hanya 2 indikator
   yang lolos (§9), media alt text dibatasi ke daftar **konten** dengan link edit.
4. **Google Form** — tidak ada aturan "form wajib"; `usesGoogleForm()` hanya "punya URL" →
   dikeluarkan agar tidak menyesatkan (ADM-06 SHOULD).
5. **`Document::scopePublished()`** tidak membandingkan `now()`; definisi "Terbit" dokumen
   dieksplisitkan agar tidak keliru.
6. **Event published vs status** — wajib dipisah; dilarang satu filter `published` generik.
7. **`start_time` wall clock** — jangan di-cast (D-02); jangan pakai datetime cast untuk
   jadwal rutin.
8. **Desain superseding** — `docs/DESIGN.md` (v1.1) **superseded**; yang mengikat adalah
   `docs/DESIGN-v2.md` (AGENTS.md + D-72). Plan mengikuti DESIGN-v2.
9. **E2E DB bare** — Dashboard harus lulus dengan semua tabel kosong; jangan andalkan seeder.
10. **PHPStan level 7 + analisis `tests/`** — resource & array shape harus ber-PHPDoc; hindari `any` baru.
11. **`Route::inertia` → controller** — pastikan tidak ada referensi lain yang mengasumsikan
    Inertia route tanpa controller (test `component('admin/dashboard')` tetap valid).

---

## 7. Verification Plan

Jalankan setelah implementasi (laporkan pass/fail apa adanya):

```bash
./vendor/bin/pint --dirty                    # format PHP
php artisan test --filter=Dashboard          # test terfokus
php artisan test                             # suite penuh
npm run types:check                          # tsc --noEmit
npm run check                                # oxlint type-aware + format
npm run build                                # WAJIB sebelum e2e (e2e memakai public/build)
npm run e2e                                  # termasuk app:e2e:prepare
composer ci:check                            # gate lengkap
```

Tambahan manual:

- Viewport 360, 390, 768, 1024, 1440 — tanpa horizontal overflow.
- Keyboard navigation, focus state, label, heading hierarchy, status tekstual.
- Semua tabel kosong: Dashboard menampilkan 0 + empty state, tanpa exception.
- Page source tidak memuat `deleted_at`, `is_active`, `uploaded_by`, `error_message`, dan
  field sensitif lain.
- Aksi cepat membuka route yang benar (klik + cek URL dan konten target).
- Catat baseline failure yang sudah ada sebelum perubahan.

---

## 8. Acceptance Criteria yang Akan Terpenuhi

### 8.1 AC Phase 9B

| AC | Status rencana |
| --- | --- |
| AC-01 Dashboard dinamis, route terlindungi, tetap jalan saat tabel kosong | Terpenuhi |
| AC-02 Statistik mengikuti enum/scope aktual, tanpa hitung dari daftar frontend | Terpenuhi |
| AC-03 Aksi cepat/panel navigasi valid, limit teratur, link edit berfungsi | Terpenuhi |
| AC-04 Setiap indikator punya query teruji + tindakan lanjutan | Terpenuhi (2 indikator, §9) |
| AC-05 UI bersih/responsif/aksesibel, tanpa N+1/props berlebih/data sensitif, gate lulus | Terpenuhi |

### 8.2 AC PRD

| AC PRD | Status |
| --- | --- |
| Dasbor: jumlah berita, agenda mendatang, dokumen, aktivitas terbaru + pintasan | **Terpenuhi** |
| ADM-01 responsif ponsel | Terpenuhi |
| ADM-06 (SHOULD) indikator "belum ada alt text" & "Google Form belum diisi" | **Sebagian** — alt text (scoped) ada; Google Form dikeluarkan (tanpa aturan wajib) dan dicatat |

---

## 9. Pertanyaan / Ambiguity

Tidak ada pertanyaan blocking tersisa. Keputusan yang sudah dikonfirmasi pengguna:

1. **Acuan UI** — bangun dari spesifikasi struktur roadmap §5 + `docs/DESIGN-v2.md`
   (tanpa pixel mockup; mockup tidak tersedia di repository).
2. **Perlu Perhatian** — hanya **Dokumen kedaluwarsa** dan **Media tanpa alt text (scoped)**.
   Media `failed` dan "Pelayanan Google Form belum diisi" **dikeluarkan** (tidak ada jalur
   perbaikan / aturan wajib) dan dicatat di `docs/DECISIONS.md`.
3. **Kartu Jadwal Misa** — tiga hitungan sumber terpisah (rutin aktif, khusus mendatang,
   pemberitahuan berlaku), tidak dicampur.
4. **Konten Terbaru** — satu daftar gabungan 5–8 item (roadmap §6.10 mengizinkan).

Hal yang akan dicatat sebagai gap saat implementasi:

- Announcement bar `hero_slides` tidak relevan di fase ini (sudah D-70); tidak terbawa.
- Cakupan "Media tanpa alt text" dibatasi 3 modul (posts/events/services); tabel lain
  (galeri, komunitas, pastor, pengurus, sejarah, wilayah, hero setting) tidak dihitung.
- Media `failed` dan Google Form tidak ditampilkan.

---

## 10. Status

**Status: Implemented — engineering complete (branch `feat/phase-9b-admin-dashboard`).**

Rencana ini dieksekusi pada branch `feat/phase-9b-admin-dashboard`. Ringkasan
hasil:

- **Backend:** `DashboardController` + `RecentPostResource`/`UpcomingEventResource`;
  route `dashboard` menjadi controller (nama & middleware tetap). Tanpa schema,
  dependency, cache, atau seeder baru.
- **Frontend:** `features/admin/dashboard/{types.ts,content-target.ts,components/*,pages/index.tsx}`
  — tujuh komponen feature-scoped. Tidak ada impor lintas-feature.
- **Koreksi sadar (D-77):** indikator alt text membaca kolom konten
  (`image_alt`/`poster_alt`/`thumbnail_alt`), bukan `media.alt_text`, karena itu
  yang dirender halaman publik. Cakupan dibatasi posts/events/services.
- **Gate verifikasi:** `pint` ✅, `phpstan` ✅ (0 error), `npm run check` ✅,
  `npm run types:check` ✅, Vitest 382 ✅, Pest 1456 ✅, `npm run build` ✅,
  Playwright 111 ✅ (termasuk `dashboard.spec.ts`), `composer ci:check` ✅.
- **Dokumentasi:** `docs/DECISIONS.md` D-76 & D-77; `docs/roadmap/README.md`
  menandai Phase 9B selesai. PHASE 10 tetap terpisah.

