# Implementation Plan — Revisi Konsolidasi Identitas Paroki & Pengaturan Situs

> **Project:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau\
> **Jenis pekerjaan:** Revisi — hapus modul Informasi Profil, konsolidasi data identitas/kontak ke Pengaturan Situs, perbaikan SSOT\
> **Status:** Belum diimplementasikan (belum ada satu baris kode ditulis)\
> **Branch (usulan):** `feat/revisi-konsolidasi-identitas-paroki`\
> **Commit dasar (audit):** `main` @ `cb2befd`\
> **Spesifikasi sumber:** `docs/revision/revision-data-duplication-profil-pengaturan.md`\
> **Sumber kebenaran:** `PRD.md` → `ARCHITECTURE.md` → `DESIGN.md` → `AGENTS.md` → `DECISIONS.md`

Dokumen ini menjelaskan _bagaimana_ revisi dikerjakan, dalam urutan apa, file mana
yang tersentuh, dan bagaimana diverifikasinya. Disusun setelah audit codebase
terhadap `main` @ `cb2befd` dan **sembilan keputusan yang sudah dijawab pengguna**
melalui tool `question` (direkap di §2). Seluruh keputusan di §2 mengikat.

> **Peringatan cakupan.** Keputusan pengguna memperluas revisi jauh melebihi
> "hapus field duplikat": **seluruh modul Informasi Profil dihapus** (admin + publik),
> tabel `parish_profiles` di-drop, dan sebagian besar field-nya dipindah ke
> `site_settings` sebagai key baru — sebagian **tanpa consumer**. Divergensi ini
> dicatat sebagai **D-69** di §9 dan wajib ditulis ke `docs/DECISIONS.md`.

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

Dua permukaan admin menyediakan data identitas paroki yang tumpang tindih:
`/admin/pengaturan` (site settings) dan `/admin/profil/informasi` (singleton
`parish_profiles`). Sebagian data benar-benar duplikat (nama, kontak, deskripsi
singkat), sebagian hanya terlihat mirip tapi berbeda consumer.

Revisi ini **menghapus modul Informasi Profil sepenuhnya** dan menjadikan
`site_settings` (melalui `SiteSettingsService`) sebagai satu-satunya tempat
mengelola identitas & kontak global. Konten narasi sejarah dipindah ke modul
Sejarah melalui tabel singleton baru. Halaman publik `/profil` (ringkasan)
dihapus; tautan "Profil" diarahkan ke `/profil/sejarah`.

Hasil akhir:

- Satu tempat edit identitas global: **Pengaturan Situs → Identitas Paroki**.
- Satu tempat edit kontak resmi: **Pengaturan Situs → Kontak**.
- Narasi sejarah dikelola di **admin Sejarah**, bukan di form profil.
- Tidak ada lagi tabel/model/route/form `ParishProfile`.
- Navbar & footer publik membaca identitas dari settings canonical.

---

## 2. Keputusan yang Mengikat

Sembilan keputusan dari pengguna. Implementasi **tidak boleh** menyimpang tanpa
persetujuan ulang.

| # | Pertanyaan | Keputusan | Dampak |
| --- | --- | --- | --- |
| 1 | Alternatif A vs B untuk Informasi Profil | **Hapus total modul Informasi Profil** (admin + publik) | Model, tabel, controller, request, resource, policy, factory, halaman, form, sidebar, route, types, tests dihapus |
| 2 | Pemilik `short_description` | **`site_settings.short_description` canonical** | `/profil` (dihapus) tak lagi jadi sumber; footer membaca settings |
| 3 | Kolom duplikat | **Drop lewat migration baru** | Backfill deterministik sebelum drop; migration lama tidak diedit |
| 4 | Field yang dihapus dari form profil | **Semua**: `name`, `short_description`, `address`, `phone`, `email`, `map_url`, `logo_media_id`, `logo_alt` | Form dihapus seluruhnya; field identitas/kontak hanya di settings |
| 5 | Scope navbar/footer | **Ya — sambungkan** navbar (nama+logo) & deskripsi footer ke settings canonical | `parish-navbar.tsx` & `parish-footer.tsx` kehilangan hardcode |
| 6 | Sumber identitas di `/profil` | **Prop `identity` eksplisit** dari `SiteSettingsService` | Karena `/profil` dihapus, prop dipakai navbar/footer (shared) & halaman publik yang butuh |
| 7 | Ringkasan read-only di Informasi Profil | **Ya** (semula) | **Gugur** — halaman dihapus, tidak ada permukaan. Tidak ada CTA ke settings di halaman yang tak ada |
| 8 | Nasib `/profil` publik | **Hapus**; tautan "Profil" → `/profil/sejarah`; item "Ringkasan" dihapus | `profil.index` dihapus; `ProfileSubNav` 4 item |
| 9 | Storage narasi sejarah | **Tabel singleton baru `parish_history_narratives`** | Model + migration + route/controller/request baru; dikelola di halaman admin Sejarah |
| 10 | Field sisa ParishProfile | **Pindah semua ke settings** (`logo_alt`, `website`, `established_year`, `description`, `cover`+`cover_alt`, `video_url`, `is_published`) meski **tanpa consumer** | Key settings baru; `description` bertipe `html`; `is_published` jadi boolean |
| 11 | Format media di settings | **Media picker** (`media_id` + `_alt`) untuk `logo` & `cover` | `logo` berubah dari string URL → media; `cover` media baru; `favicon`/`seo_default_og_image` **tetap** URL (follow-up) |
| 12 | Aturan backfill | **Settings menang**; copy profil→settings hanya bila settings kosong | `name`/`address`/`phone`/`email`/`map_url` tidak ditimpa; nilai `address` profil dibuang |

---

## 3. Kondisi Codebase Saat Ini

Audit pada `main` @ `cb2befd`. `git status` bersih kecuali `docs/revision/`
(untracked). Tidak ada file kode diubah saat audit.

### 3.1 Dua sumber data dan consumer aktual

**`site_settings` (via `SiteSettingsService`, `config/site-settings.php`):**

| Key | Consumer nyata |
| --- | --- |
| `parish_name` | `HandleInertiaRequests::seoDefaults()` → `seo.siteName`; footer; `app.blade.php` og:site_name; JSON-LD agenda |
| `parish_short_name` | **tidak ada** (test saja) |
| `tagline`, `motto_verse`, `patron_saint` | **tidak ada** |
| `short_description` | **tidak ada** |
| `logo`, `favicon` | **tidak ada** (navbar/header hardcode `/logo.svg`; blade hardcode favicon) |
| `contact_address/phone/whatsapp/email` | `HandleInertiaRequests::contactDetails()` → `contact` shared prop → footer, `home-contact-section`, `contact-details` |
| `office_hours` | `contact.officeHours` → kontak & beranda |
| `maps_link` | `contact.mapsEmbedUrl`/`mapsLink` → kontak & footer |

**`parish_profiles` (singleton `ParishProfile`):**

| Field | Consumer publik aktual |
| --- | --- |
| `name` | `/profil` (heading + cover alt) |
| `short_description` | `/profil` (heading description + meta) |
| `description` | `/profil` (RichText) |
| `history_description` | `/profil/sejarah` (RichText) |
| `cover_media_id`/`cover_alt` | `/profil` (gambar sampul) |
| `address`, `phone`, `email`, `website`, `established_year`, `logo_media_id`, `logo_alt`, `map_url`, `video_url`, `is_published` | **dikirim ke publik tetapi tidak dirender di mana pun** |

**Kesimpulan:** hanya `name`, `short_description`, `description`, `history_description`,
dan `cover` yang benar-benar tampil. Sisanya data mati. Contact resmi publik
**sudah** dari settings.

### 3.2 Data aktual (dev DB, `main`)

- `parish_profiles` id `1`: `name` (sama dengan `parish_name`), `short_description`
  terisi, `address` = "Putussibau…", `phone`/`email`/`website`/`map_url`/`video_url`
  null, `established_year` `1956`, `logo_media_id`/`cover_media_id` `1`, `is_published` 1.
- `site_settings`: `contact_address` = "Pontianak…" (berbeda dari profil),
  `contact_phone`/`contact_whatsapp`/`contact_email`/`maps_link` terisi,
  `parish_name`/`parish_short_name` terisi, `short_description`/`tagline`/
  `motto_verse`/`patron_saint`/`logo`/`favicon` null.
- Ada baris legacy `site_settings.maps_embed_url` (tidak dikenal config) — di luar
  konfigurasi sehingga diabaikan `setMany()`; dapat dibersihkan sebagai follow-up.

### 3.3 Fakta yang mengikat desain

- **Wayfinder** adalah sumber URL frontend; tidak boleh hard-code URL. Regenerasi
  setelah perubahan route.
- **`config/inertia.php`**: `resource_path('js/features/admin/profil/pages')`
  terdaftar sebagai page finder. Folder tetap dipakai 4 sub-modul; **jangan** hapus
  registrasi.
- **ArchitectureTest** menegakkan kecocokan page finder ↔ page component, allowlist
  `dangerouslySetInnerHTML` (tepat 2 file), dan `components/` vs `features/`.
- **Eloquent strict mode** aktif di luar production (`Model::shouldBeStrict`).
- **Shared props harus kecil**; `identity` dibenarkan karena navbar/footer tampil
  di semua halaman publik (pola `seo`/`contact`).
- **Settings `logo` saat ini string** dan dirender `type="url"`; mengubahnya jadi
  media picker berarti menambah `FieldKind 'media'` + meta baru.
- **Pest & Playwright berbagi DB test**; jangan jalankan Playwright bare setelah Pest.
- **`npm run e2e` menyajikan `public/build`** → wajib `npm run build` dulu.

---

## 4. Arsitektur Target

### 4.1 Ownership final

| Konsep | Pemilik kanonis | Consumer setelah revisi |
| --- | --- | --- |
| Nama resmi, nama pendek, tagline, short_description, motto, patron, logo+alt | `site_settings` (`identitas`) | navbar, footer, SEO, `/profil/sejarah` (jika dipakai) |
| Alamat, telepon, whatsapp, email, jam pelayanan, maps | `site_settings` (`kontak`) | footer, `/kontak`, beranda |
| Narasi sejarah | **`parish_history_narratives`** (singleton baru) | admin Sejarah, `/profil/sejarah` |
| `description`, `established_year`, `cover`+`cover_alt`, `video_url`, `website`, `is_published` | `site_settings` (grup baru `profil`) | **belum ada consumer** (data tersimpan) |
| Sejarah timeline, wilayah, pastor, struktur | tabel masing-masing | publik + admin |

### 4.2 Key `site_settings` akhir

**Grup `identitas`** (perubahan): `logo` berubah tipe → `media`, tambah `logo_alt`.

```
parish_name, parish_short_name, tagline, short_description,
motto_verse, patron_saint, logo (media), logo_alt, favicon
```

**Grup `kontak`** (tidak berubah):

```
contact_address, contact_phone, contact_whatsapp, contact_email, office_hours, maps_link
```

**Grup baru `profil`** (semua baru):

```
description (html), established_year (integer), cover (media), cover_alt,
video_url, website, is_published (boolean)
```

Urutan tab admin: `identitas, kontak, profil, sosial, seo, beranda, privasi`.

### 4.3 Shape shared prop `identity`

Dibangun di `HandleInertiaRequests` dari `SiteSettingsService` + resolusi Media:

```
identity: {
  name: string|null,            // parish_name
  shortName: string|null,       // parish_short_name
  tagline: string|null,
  shortDescription: string|null,
  logoUrl: string|null,         // resolusi media `logo`
  logoAlt: string|null,
}
```

`seo.siteName` tetap ada (sudah dipakai SEO/JSON-LD); `identity.name` bersumber
dari key yang sama.

---

## 5. Implementation Plan

Urutan disusun berdasarkan dependency. **Jangan melompati langkah.**

### Fase 0 — Persiapan branch

1. Konfirmasi `git status`; buat branch `feat/revisi-konsolidasi-identitas-paroki`
   dari `main` terbaru.
2. Sertakan `docs/revision/revision-data-duplication-profil-pengaturan.md`
   (untracked) sebagai bagian pekerjaan; jangan ditimpa.
3. Baseline: `php artisan test --compact`, `npm run check`, `npm run types:check`.

### Fase 1 — Backend data: narasi sejarah singleton

1. **Model** `app/Models/ParishHistoryNarrative.php`
   - Singleton pola `ParishProfile::current()`: `current()` → `firstOrNew([])`.
   - `#[Fillable(['narrative'])]`; docblock singkat (sanitized HTML).
2. **Migration** create `parish_history_narratives`
   - `id`, `narrative` LONGTEXT nullable, timestamps.
   - Letakkan di file migration konsolidasi (Fase 1.4) atau terpisah — pilih satu
     file agar urutan create→copy→drop atomik.
3. **Request** `app/Http/Requests/Profil/UpdateParishHistoryNarrativeRequest.php`
   - `authorize()`: `$this->user()->can('parishhistories.update')` (konsisten
     `ParishHistoryItemController`). _Catatan: jika policy sudah ada, gunakan
     `Gate::authorize` di controller sebagai gantinya — ikuti pola existing._
   - `prepareForValidation()`: sanitasi `narrative` (pakai `ProfilValidationRules::sanitizeHtmlFields`).
   - Rule: `narrative => ['nullable','string','max:500000']`; pesan Indonesia.
4. **Controller** `ParishHistoryItemController`
   - `index()`: ganti `$profile = ParishProfile::current()` + `ParishProfileResource`
     → muat `ParishHistoryNarrative::current()`; kirim prop `narrative` (string|null).
   - Tambah `updateNarrative(UpdateParishHistoryNarrativeRequest $request)`:
     `ParishHistoryNarrative::current()->fill(['narrative' => ...])->save()`;
     redirect `parish-histories.index` dengan flash sukses.
5. **Route admin**: `PUT /admin/profil/sejarah/narasi` →
   `ParishHistoryItemController@updateNarrative`, name `parish-histories.narrative.update`.

### Fase 2 — Backend data: konsolidasi settings

1. **`config/site-settings.php`**
   - Grup `identitas`: tetap `logo` (tipe berubah), tambah `logo_alt`.
   - Grup baru `profil` (setelah `kontak`): `description`, `established_year`,
     `cover`, `cover_alt`, `video_url`, `website`, `is_published`.
   - `types`: `logo`/`cover` => `media`; `description` => `html`;
     `established_year` => `integer`; `is_published` => `boolean`.
   - `rules`: `established_year` (`integer|between:1000,<tahun berjalan>`);
     `website`/`video_url` (`nullable|url|max:255`/`max:500`);
     `description` (`nullable|string|max:50000`).
   - `defaults`: tidak menambah (semua kosong).
2. **`app/Http/Requests/Settings/UpdateSettingsRequest.php`**
   - Validasi `settings.logo` & `settings.cover`: `nullable|string` +
     `Rule::exists('media','id')->where('status', MediaStatus::Ready->value)`.
     (Rule object ditambahkan eksplisit seperti `GoogleMapsLink`, karena
     `config('site-settings.rules')` hanya menerima string.)
   - `logo_alt`/`cover_alt`: `nullable|string|max:255`.
   - `prepareForValidation` sanitasi `description` sudah otomatis (tipe `html`).
3. **`app/Http/Controllers/Settings/SettingsController.php`**
   - `formMeta()`: tambah `'media' => ['logo','cover']` (disarankan: derive dari
     `config('site-settings.types') === 'media'`, bukan daftar hardcode).
   - Tambah `'mediaUrls' => ['logo' => <url|null>, 'cover' => <url|null>]`
     (resolve via Media + variants) agar picker menampilkan gambar tersimpan.
   - `numericRanges` tambah `established_year` (mis. `[1000, <tahun>]`).
   - `urls` untuk `website`/`video_url` berasal dari `lang/id/site-settings.php`.
4. **Lang** `lang/id/site-settings.php`
   - Tambah `groups.profil` => "Profil Paroki".
   - Tambah `labels`: `logo_alt`, `description`, `established_year`, `cover`,
     `cover_alt`, `video_url`, `website`, `is_published`.
   - Keluarkan `logo` dari daftar `urls`; tambah `website`/`video_url` ke `urls`.
   - Tambah `hints` (mis. "Identitas umum paroki…", "Narasi/prosa opsional").
5. **Test** — perluas `tests/Feature/Settings/SiteSettingsTest.php`:
   - Key baru tersimpan (grup `profil`, `logo_alt`).
   - `logo`/`cover` menolak media id non-`ready`; menerima yang `ready`.
   - Sanitasi `description`.
   - Cache invalidation tetap benar setelah save.

### Fase 3 — Backend: hapus modul & rewire

1. **Route** `routes/web.php`
   - Hapus import `ParishProfileController`.
   - Admin root `/admin/profil` redirect → `to_route('parish-histories.index')`
     (ganti `to_route('parish-profile.edit')`).
   - Hapus `parish-profile.edit` & `parish-profile.update`.
   - Hapus `profil.index` (publik); sisakan sejarah/wilayah/pastor/struktur.
   - Tambah route narasi (Fase 1.5).
2. **`PublicParishProfileController`**
   - Hapus `index()`.
   - `sejarah()`: buang `PublicParishProfileResource`; kirim `narrative` dari
     `ParishHistoryNarrative::current()` + `items`.
3. **Hapus file** (lihat §7.3): `ParishProfile` model, `ParishProfileController`,
   `ParishProfileResource`, `PublicParishProfileResource`,
   `UpdateParishProfileRequest`, `ParishProfilePolicy`, `ParishProfileFactory`.
   - **Jangan** hapus `ProfilFormMeta` (7 controller), `ProfilValidationRules`
     (8 request), `ResolvesMediaUrl` (dipakai resource sibling).
4. **Permissions**: hapus `ParishProfilePolicy` saja. **Pertahankan**
   `parishprofiles.view`/`parishprofiles.update` di `PermissionSeeder` karena
   masih dipakai `ParishServiceAreaPolicy` (Wilayah). `RbacFoundationTest` tidak
   berubah.
5. **`HandleInertiaRequests`**: tambah `identity` shared prop (lihat §4.3) +
   perbarui docblock shape `share()`.

### Fase 4 — Frontend

1. **Hapus**: `features/admin/profil/pages/informasi/**`,
   `features/public/profil/pages/index.tsx`,
   `features/public/profil/components/profile-nav-card.tsx`.
2. **Sidebar** `layouts/app/components/app-sidebar.tsx`: hapus anak "Informasi
   Profil" & import `editParishProfile`; parent "Profil Paroki" href → sejarah.
3. **Public navbar** `layouts/public/components/parish-navbar.tsx`:
   - `ParishLogo` baca `identity.shortName`/`identity.logoUrl`/`identity.logoAlt`
     (fallback aman bila null), bukan hardcode + `/logo.svg`.
   - Entry nav "Profil" → `sejarah()` (hapus import `index as profil`).
4. **Footer** `parish-footer.tsx`: deskripsi `[ISI: …]` → `identity.shortDescription`
   (sembunyikan bila null); link "Profil" → `sejarah()`.
5. **`ProfileSubNav`**: hapus item "Ringkasan" (`profilIndex`).
6. **Public `sejarah.tsx`**: prop `narrative` (bukan `profile`); render RichText.
7. **Admin `features/admin/profil/pages/sejarah/index.tsx`**: buang blok
   "Narasi dikelola pada halaman profil" + link `editProfile`; tambah
   `RichTextEditor` narasi + tombol simpan via Wayfinder
   `parish-histories.narrative.update` (route action, bukan URL hardcode).
8. **Admin types** `features/admin/profil/types.ts`: hapus `ParishProfileRow`,
   `ParishProfileFormData`; tambah tipe narasi.
9. **Settings admin** `features/admin/pengaturan/`
   - `types.ts`: tambah `'profil'` ke `SettingsGroupName`; tambah
     `media: string[]` dan `mediaUrls: Record<string,string|null>` ke `SettingsMeta`.
   - `pages/index.tsx`: tambah `FieldKind 'media'` + render `MediaUploadField`
     (variant `logo` untuk `logo`, `image` untuk `cover`); `GROUP_ORDER`
     diganti derive dari `Object.keys(groups)` agar tidak drift saat grup baru.
   - `logo_alt`/`cover_alt` dirender sebagai input teks (atau via `altValue`/
     `onAltChange` pada `MediaUploadField`).
10. **`resources/js/types/index.ts`**: tambah `IdentityDetails` + `identity` di
    `SharedProps`.
11. **Wayfinder**: `php artisan wayfinder:generate --with-form`. Jangan edit file
    generated manual.

### Fase 5 — Seeder

`database/seeders/DemoContentSeeder.php`:

- Ganti `seedParishProfile()` → isi `site_settings` (identitas + kontak + key
  grup `profil` baru) dan `parish_history_narratives` dengan data contoh yang
  jelas sample.
- Pertahankan guard `config('demo.enabled')`; `app:e2e:prepare` tetap mematikan demo.
- Jangan mengarang data nyata (nama pastor, telepon, koordinat). Nilai contoh
  diberi tanda jelas sebagai sample.

### Fase 6 — Test

Lihat §8.

### Fase 7 — Dokumentasi

- `docs/DECISIONS.md`: tambah **D-69** (lihat §9) + perbarui referensi D-52.
- `docs/RBAC.md`: perbarui baris policy profil (`parishprofiles.*` kini hanya
  menggating Wilayah).
- `AGENTS.md` (Known gaps) & `docs/roadmap/README.md`: catat modul Informasi
  Profil dihapus.

---

## 6. Migration & Strategi Data

Satu migration baru (jangan edit migration lama): mis.
`database/migrations/2026_10_18_000000_consolidate_parish_identity_into_settings.php`.

Urutan di dalam `up()`:

1. **Create** `parish_history_narratives`.
2. **Backfill narasi**: `DB::table('parish_profiles')->value('history_description')`
   → insert satu baris bila tabel narasi kosong dan nilainya tidak null.
   Gunakan query builder (bukan model `ParishProfile` yang akan dihapus).
3. **Backfill settings hanya bila key belum ada/kosong** (pakai `DB::table('site_settings')`):

   | Key settings | Sumber profil | Aturan |
   | --- | --- | --- |
   | `short_description` | `short_description` | hanya bila settings kosong |
   | `description` | `description` | hanya bila settings kosong |
   | `established_year` | `established_year` | hanya bila settings kosong |
   | `video_url` | `video_url` | hanya bila settings kosong |
   | `website` | `website` | hanya bila settings kosong |
   | `logo` | `logo_media_id` | hanya bila settings kosong |
   | `logo_alt` | `logo_alt` | hanya bila settings kosong |
   | `cover` | `cover_media_id` | hanya bila settings kosong |
   | `cover_alt` | `cover_alt` | hanya bila settings kosong |
   | `is_published` | `is_published` | hanya bila settings kosong |

   Grup key mengikuti `config('site-settings.groups')` (insert dengan `group`
   yang benar).
4. **Tidak menimpa** `parish_name`, `contact_address`, `contact_phone`,
   `contact_email`, `maps_link` — settings menang. Nilai `address` profil
   ("Putussibau…") dibuang karena tidak pernah dirender.
5. **Drop** `parish_profiles` (`Schema::dropIfExists`).
6. (Opsional) hapus baris legacy `site_settings` dengan key `maps_embed_url`.

`down()`: buat ulang tabel `parish_profiles` (schema minimal) — atau tandai
migration irreversible dengan komentar + `throw` bila tidak ada skema lama yang
masuk akal. Rekomendasi: sediakan `down()` yang membuat ulang tabel agar
`migrate:rollback` tidak error, dengan catatan data tidak dipulihkan.

Guard untuk DB kosong: semua langkah backfill memakai `if` existence check.

---

## 7. File yang Akan Terpengaruh

### 7.1 Dibuat

| File | Isi |
| --- | --- |
| `database/migrations/2026_10_18_000000_consolidate_parish_identity_into_settings.php` | create narasi + backfill + drop parish_profiles |
| `app/Models/ParishHistoryNarrative.php` | singleton narasi sejarah |
| `app/Http/Requests/Profil/UpdateParishHistoryNarrativeRequest.php` | validasi + sanitasi narasi |
| `tests/Feature/Profil/ParishHistoryNarrativeTest.php` | test narasi (CRUD + sanitasi) |

### 7.2 Diubah

| File | Perubahan |
| --- | --- |
| `config/site-settings.php` | grup `profil`, tipe media/html/integer/boolean, rules |
| `app/Http/Requests/Settings/UpdateSettingsRequest.php` | rule media untuk `logo`/`cover` |
| `app/Http/Controllers/Settings/SettingsController.php` | meta `media`, `mediaUrls`, `numericRanges` |
| `lang/id/site-settings.php` | label/grup/hint baru; `logo` keluar dari `urls` |
| `app/Http/Middleware/HandleInertiaRequests.php` | shared prop `identity` |
| `app/Http/Controllers/Profil/PublicParishProfileController.php` | hapus `index()`, rewire `sejarah()` |
| `app/Http/Controllers/Profil/ParishHistoryItemController.php` | `index()` pakai narasi; tambah `updateNarrative()` |
| `routes/web.php` | hapus route profil/BE, redirect root admin, route narasi |
| `database/seeders/DemoContentSeeder.php` | seed settings + narasi, hapus profil |
| `resources/js/layouts/app/components/app-sidebar.tsx` | hapus anak Informasi Profil; href → sejarah |
| `resources/js/layouts/public/components/parish-navbar.tsx` | `identity` + link Profil → sejarah |
| `resources/js/layouts/public/components/parish-footer.tsx` | deskripsi + link Profil → sejarah |
| `resources/js/features/public/profil/components/profile-subnav.tsx` | hapus item "Ringkasan" |
| `resources/js/features/public/profil/pages/sejarah.tsx` | prop `narrative` |
| `resources/js/features/public/profil/types.ts` | buang `PublicParishProfile`, tambah narasi |
| `resources/js/features/admin/profil/pages/sejarah/index.tsx` | editor narasi inline |
| `resources/js/features/admin/profil/types.ts` | buang tipe profil, tambah narasi |
| `resources/js/features/admin/pengaturan/types.ts` | grup `profil`, meta `media`/`mediaUrls` |
| `resources/js/features/admin/pengaturan/pages/index.tsx` | `FieldKind 'media'`, derive `GROUP_ORDER` |
| `resources/js/types/index.ts` | `identity` di `SharedProps` |
| `tests/js/support/inertia.tsx` | `identity` di `publicPageProps()` |
| `docs/DECISIONS.md`, `docs/RBAC.md`, `AGENTS.md`, `docs/roadmap/README.md` | dokumentasi |

### 7.3 Dihapus

| File |
| --- |
| `app/Models/ParishProfile.php` |
| `app/Http/Controllers/Profil/ParishProfileController.php` |
| `app/Http/Requests/Profil/UpdateParishProfileRequest.php` |
| `app/Http/Resources/Profil/ParishProfileResource.php` |
| `app/Http/Resources/Public/Profil/PublicParishProfileResource.php` |
| `app/Policies/ParishProfilePolicy.php` |
| `database/factories/ParishProfileFactory.php` |
| `resources/js/features/admin/profil/pages/informasi/**` (index + form) |
| `resources/js/features/public/profil/pages/index.tsx` |
| `resources/js/features/public/profil/components/profile-nav-card.tsx` |
| `tests/Feature/Profil/ParishProfileTest.php` |
| `tests/Feature/Profil/ParishProfileSchemaTest.php` |
| `resources/js/routes/parish-profile/**`, `resources/js/actions/**/ParishProfileController.ts` (regenerate) |

### 7.4 Test yang diperbarui

`PublicParishProfilePageTest.php`, `DemoContentSeederTest.php`,
`ProfilAuthorizationTest.php`, `ParishHistoryItemTest.php` (component prop),
`PublicRoutesTest.php`, `PublicPlaceholderRoutesTest.php`,
`tests/js/components/app-sidebar.test.tsx`, `parish-navbar.test.tsx`,
`parish-footer.test.tsx`, `tests/e2e/profil-paroki.spec.ts`,
`tests/e2e/public.spec.ts`, `tests/e2e/settings.spec.ts`.

---

## 8. Rencana Test

### 8.1 Feature (Pest)

- **Settings**: key baru tersimpan; media non-ready ditolak; `description`
  tersanitasi; key tak dikenal tetap ditolak; cache invalidation benar.
- **Narasi sejarah**: `index` mengirim narasi; `updateNarrative` menyimpan &
  menyaring HTML; otorisasi sesuai `parishhistories.update`.
- **Public**: `/profil/sejarah` menampilkan narasi + item; `/profil` **404**;
  route `profil.index` tidak ada.
- **Regression**: tidak ada row `site_settings` ganda; save settings tidak
  membuat/menulis `parish_profiles` (tabel sudah tidak ada).
- **Hapus**: `ParishProfileTest`, `ParishProfileSchemaTest`.

### 8.2 Unit/Component (Vitest via `vp test`)

- `tests/js/support/inertia.tsx`: `publicPageProps()` + `SharedProps` harus punya
  `identity`, atau semua render halaman publik gagal.
- `app-sidebar.test.tsx`: 4 anak Profil Paroki, tanpa Informasi Profil.
- `parish-navbar.test.tsx`: entry "Profil" → `/profil/sejarah`; identitas dari
  `identity` (fallback aman).
- `parish-footer.test.tsx`: deskripsi dari `identity.shortDescription`; link
  Profil → sejarah; tidak ada link `/profil`.
- `profile-subnav` (bila ada test): 4 item, tanpa "Ringkasan".

### 8.3 E2E (Playwright)

- `profil-paroki.spec.ts`: hapus submenu "Informasi Profil" & tes simpan profil;
  daftar halaman publik tanpa `/profil`; sidebar Profil Paroki 4 anak.
- `public.spec.ts`: navigasi navbar/footer "Profil" mendarat di `/profil/sejarah`.
- `settings.spec.ts`: tab "Profil Paroki" tampil; field media picker berfungsi;
  simpan + reload mempertahankan nilai.
- Wajib `npm run build` sebelum `npm run e2e`; `beforeEach` tetap
  `resetLoginRateLimit()`.

### 8.4 Architecture

`tests/Unit/ArchitectureTest.php` harus tetap hijau: page finder ↔ component
setelah `features/admin/profil/pages/informasi` dihapus; allowlist
`dangerouslySetInnerHTML` tidak berubah (tetap 2 file).

---

## 9. Risiko, Divergensi, dan Follow-up

### 9.1 Divergensi yang wajib dicatat (D-69)

1. **Modul Informasi Profil dihapus total** — menyimpang dari D-52 (yang
   menetapkan `parish_profiles` sebagai singleton profil, bukan site settings).
2. **Konten editorial panjang dipindah ke settings** (`description`, `cover`,
   `established_year`, `video_url`, `website`, `is_published`) **tanpa consumer** —
   menyimpang dari revisi §5/§7.2 ("jangan menyalin konten editorial panjang ke
   settings"; hindari key mati). Dilakukan atas permintaan pengguna; ditandai
   sebagai risiko.
3. **`is_published` kehilangan makna** setelah `/profil` dihapus.
4. **Kehilangan tampilan publik**: prosa profil & gambar sampul tidak lagi
   ditampilkan.
5. **Nilai `address` profil dibuang** (settings menang) — bukan overwrite senyap;
   didokumentasikan.
6. **`favicon`/`seo_default_og_image` tetap URL** — inkonsisten dengan `logo`/`cover`
   yang menjadi media picker.

### 9.2 Risiko teknis

| Risiko | Mitigasi |
| --- | --- |
| Migration destruktif di lingkungan berisi data | Backfill deterministik + guard existence; `down()` membuat ulang tabel |
| Media picker di settings menambah kompleksitas `SettingsController` | Meta `media`/`mediaUrls` eksplisit + test |
| Shared prop `identity` bocor data tak perlu | Hanya kirim identitas publik (tanpa kontak; kontak sudah ada di `contact`) |
| `GROUP_ORDER` hardcode drift | Derive dari `Object.keys(groups)` |
| E2E gagal karena build basi | `npm run build` dulu |

### 9.3 Follow-up (di luar scope revisi ini)

- Konversi `favicon`/`seo_default_og_image` ke media picker agar konsisten.
- Pertimbangkan consumer nyata untuk `description`/`cover`/`established_year`
  (mis. halaman "Tentang" atau beranda) atau hapus kembali.
- Bersihkan baris legacy `site_settings.maps_embed_url`.

---

## 10. Verification Plan

Jalankan, catat hasil faktual (jangan klaim lulus tanpa menjalankan):

| Langkah | Command |
| --- | --- |
| Format PHP | `./vendor/bin/pint --dirty --format agent` |
| Test backend selektif | `php artisan test --compact tests/Feature/Profil tests/Feature/Settings tests/Feature/Public` |
| Lint + format TS/CSS | `npm run check` |
| Typecheck | `npm run types:check` |
| Regenerate Wayfinder | `php artisan wayfinder:generate --with-form` |
| Gate penuh | `composer test` |
| Build + E2E (opsional, mahal) | `npm run build && npm run e2e` |

Review `git diff` akhir: fokus, tidak ada perubahan di luar scope.

---

## 11. Acceptance Criteria

Mengacu ID di `docs/revision/revision-data-duplication-profil-pengaturan.md`.

- [ ] **DATA-SSOT-01** Identitas global hanya dapat diedit dari Pengaturan Situs.
- [ ] **DATA-SSOT-02** Kontak resmi hanya dapat diedit dari Pengaturan Situs.
- [ ] **DATA-SSOT-03** Tidak ada endpoint tulis kedua untuk field global
      (modul profil & tabelnya hilang).
- [ ] **DATA-SSOT-04** Narasi sejarah tetap berfungsi (pindah ke modul Sejarah).
- [ ] **DATA-SSOT-05** Consumer publik (navbar/footer) membaca settings canonical.
- [ ] **DATA-SSOT-06** Cache settings tidak valid setelah save.
- [ ] **DATA-SSOT-07** Tidak ada data produksi hilang tanpa aturan backfill;
      migration lama tidak diedit.
- [ ] **DATA-SSOT-08** Admin memahami identitas global dikelola di Pengaturan Situs
      (sidebar + tab baru).
- [ ] **DATA-SSOT-09** Test feature/component/E2E terdampak diperbarui & lulus.
- [ ] **DATA-SSOT-10** Tidak ada refactor di luar scope.
- [ ] Divergensi dicatat di `docs/DECISIONS.md` (D-69).

---

## 12. Status

| Item | Status |
| --- | --- |
| Audit codebase | Selesai (`main` @ `cb2befd`) |
| Keputusan pengguna | Selesai (12 butir, §2) |
| Plan implementasi | Selesai (dokumen ini) |
| Implementasi | Belum dimulai |
| Verifikasi | Belum dijalankan |

> Langkah pertama saat implementasi: buat branch, lalu kerjakan **Fase 1 → Fase 7**
> berurutan. Jangan hapus apa pun sebelum migration backfill ada.
