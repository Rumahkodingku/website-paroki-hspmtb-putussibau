# Implementation Plan — PHASE 9A

> **Project:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau\
> **Phase:** 9A — Beranda Publik (Homepage)\
> **Status:** Implemented — engineering complete (branch `feat/phase-9a-homepage`)\
> **Branch (usulan):** `feat/phase-9a-homepage`\
> **Commit dasar (audit):** `main` @ `fe98acc`\
> **Spesifikasi:** `docs/roadmap/phase-9a-homepage.md`\
> **Sumber kebenaran:** `PRD.md` → `ARCHITECTURE.md` → `DESIGN.md` → `AGENTS.md` → `DECISIONS.md`

Dokumen roadmap Phase 9A menjelaskan _apa_ yang harus ada. Dokumen ini menjelaskan
_bagaimana_ perubahannya, dalam urutan apa, file mana yang tersentuh, dan bagaimana
diverifikasinya. Dokumen ini pelengkap roadmap, bukan pengganti.

> Dokumen ini disusun setelah audit codebase terhadap kondisi repository saat ini
> (`main` @ `fe98acc`) dan **empat keputusan yang sudah dijawab pengguna** melalui
> tool `question`. Seluruh keputusan di §2.3 mengikat implementasi. Dokumen ini
> disusun sebelum implementasi; implementasinya dikerjakan pada branch
> `feat/phase-9a-homepage` dan statusnya diperbarui di §10.

---

## Daftar Isi

1. [Kondisi Codebase Saat Ini](#1-kondisi-codebase-saat-ini)
2. [Task Phase 9A yang Akan Dikerjakan](#2-task-phase-9a-yang-akan-dikerjakan)
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

Audit dilakukan pada `main` @ `fe98acc`. `git status` bersih kecuali dua file
untracked: `docs/roadmap/phase-9a-homepage.md` (dokumen fase ini) dan
`public/hero-section.png` (aset hero yang disediakan pengguna). Tidak ada file
kode yang diubah saat audit.

### 1.1 Fondasi yang sudah ada dan dapat dipakai ulang

| Area | Kondisi aktual | File |
| --- | --- | --- |
| Route Beranda | `Route::inertia('/', 'public/beranda')->name('home')`; belum ada controller | `routes/web.php:55` |
| Halaman Beranda | Masih placeholder Phase 01/03: judul `Selamat Datang`, teks `[ISI: ...]`, empat kartu statis, tombol Dashboard/Masuk, dan `HomeContactSection` | `resources/js/features/public/beranda/pages/index.tsx` |
| Nama page | `public/beranda` sudah terdaftar benar di `pageName`, `PageFinder`, `PageChunk`, dan `app.tsx` | `resources/js/lib/page-name.ts`, `app/Support/PageFinder.php`, `app/Support/PageChunk.php` |
| Layout publik | `PublicLayout` + navbar + footer sudah selesai dan dipakai semua halaman publik | `resources/js/layouts/public/` |
| Shared props | `name`, `auth`, `locale`, `displayTimezone`, `sidebarOpen`, `seo`, `identity`, `media`, `documentUploads`, `contact` | `app/Http/Middleware/HandleInertiaRequests.php` |
| Identitas | `identity` berisi `name`, `shortName`, `tagline`, `shortDescription`, `logoUrl`, `logoAlt` | `HandleInertiaRequests::identityDetails()` |
| Kontak | `contact` sudah berisi `address`, `phone`, `whatsapp`, `email`, `officeHours`, `mapsEmbedUrl`, `mapsLink`, `homeShowContact`, `social` | `HandleInertiaRequests::contactDetails()`, D-67 |
| Pengaturan situs | `SiteSettingsService` adalah satu-satunya pembaca/penulis setting; cache satu entry; tipe, default, dan rule dari config | `app/Services/SiteSettingsService.php`, `config/site-settings.php`, D-23 |
| Setting beranda | Sudah ada 7 key: `home_news_limit`, `home_events_limit`, `home_gallery_limit`, `home_show_devotion`, `home_show_gallery`, `home_show_services`, `home_show_contact` | `config/site-settings.php` |
| Form pengaturan | Field beranda sudah otomatis tampil; media picker membaca tipe `media`; form mengirim seluruh key setiap simpan | `resources/js/features/admin/pengaturan/pages/index.tsx`, `SettingsController` |
| Misa berikutnya | `NextMassResolver`, `App\Support\NextMass`, `NextMassResource` sudah ada; sudah menangani `Asia/Pontianak` dan Misa khusus | `app/Services/NextMassResolver.php`, `app/Http/Resources/Public/MassSchedule/NextMassResource.php` |
| Berita | `Post::published()` sudah menangani status, `published_at`, dan soft delete; `PublicPostResource` sudah berbentuk kartu | `app/Models/Post.php`, `app/Http/Resources/Public/News/PublicPostResource.php` |
| Agenda | `Event::published()` sudah ada; `PublicEventResource` membawa status dan label; belum ada scope “mulai hari ini” yang dipakai bersama | `app/Models/Event.php`, `app/Http/Controllers/Agenda/PublicEventController.php` |
| Pelayanan | `Service::active()->ordered()` sudah ada; `PublicServiceCardResource` sudah berbentuk kartu | `app/Models/Service.php`, `app/Http/Resources/Public/Pelayanan/PublicServiceCardResource.php` |
| Galeri | `GalleryAlbum::published()->ordered()` dan `GalleryPhoto::ready()` sudah ada; belum ada resource teaser yang membawa info album | `app/Models/GalleryAlbum.php`, `app/Models/GalleryPhoto.php` |
| Renungan | Kategori `renungan` sudah di-seed; belum ada section yang membacanya | `database/seeders/PostCategorySeeder.php` |
| SEO | `Seo` + fallback Open Graph di Blade; SSR OFF (D-26); belum ada fallback OG khusus homepage | `resources/js/components/seo.tsx`, `resources/views/app.blade.php` |
| Komponen kartu | `ArticleCard`, `EventCard`, `NextMassCard`, `ContactMap` masih berada di folder feature masing-masing | `resources/js/features/public/*/components/` |
| Test rute publik | `/` masih masuk dataset `placeholder pages` | `tests/Feature/Public/PublicRoutesTest.php` |
| Test E2E publik | Masih mengharapkan title `/Selamat Datang/` | `tests/e2e/public.spec.ts` |
| Test kontak beranda | Belum menguji peta/directions | `tests/js/features/public/beranda/home-contact-section.test.tsx` |
| Test homepage | Belum ada Pest/Vitest khusus homepage | — |
| Aset hero | `public/hero-section.png` ada, 1672×941, ±2 MB, tetapi belum ditrack git | `public/hero-section.png` |
| Alat optimasi | `cwebp` tersedia di host | — |

### 1.2 Yang belum ada dan menjadi pekerjaan utama Phase 9A

- Controller agregasi homepage.
- Hero yang mengambil teks/gambar dari Pengaturan Situs.
- Setting `home_hero_title`, `home_hero_description`, `home_hero_image`, `home_hero_image_alt`.
- Quick links.
- Section Misa Berikutnya, Berita Terbaru, Agenda Mendatang, Renungan, Pelayanan, Galeri, dan Kontak di Beranda.
- Resource teaser galeri yang membawa album.
- Scope agenda “mulai hari ini” yang dipakai bersama.
- Peta di blok kontak Beranda.
- Fallback OG image homepage di HTML awal.
- Komponen kartu yang dipakai dua halaman di `components/`.
- Test Pest/Vitest/E2E homepage.
- Dokumentasi keputusan dan status roadmap.

### 1.3 Kondisi data dan implikasi

- Tidak ada tabel `hero_slides` dan `announcements`.
- Tidak ada route admin `/admin/hero` dan `/admin/pengumuman`.
- Tidak ada seeder konten produksi; homepage harus tetap valid saat data kosong.
- E2E memakai database bare (`demo.enabled = false`), sehingga homepage E2E akan
  tampil tanpa berita, agenda, pelayanan, galeri, atau kontak. Itu adalah skenario
  empty state yang memang harus diuji.
- Baseline test belum dijalankan karena audit ini read-only.

---

## 2. Task Phase 9A yang Akan Dikerjakan

### 2.1 Daftar pekerjaan

| ID | Pekerjaan |
| --- | --- |
| PH9A-01 | Audit repository dan baseline |
| PH9A-02 | Konfirmasi kontrak data per section |
| PH9A-03 | Struktur halaman, hero, dan quick links |
| PH9A-04 | Section dinamis dan empty state |
| PH9A-05 | Responsive dan accessibility |
| PH9A-06 | SEO, performance, dan hardening |
| PH9A-07 | Pest, Vitest, Playwright, build, CI |
| PH9A-08 | Dokumentasi dan handoff |

### 2.2 Tetap di luar scope

- Admin Dashboard (Phase 9B).
- Tabel/migrasi `hero_slides` dan `announcements`.
- CRUD admin hero dan pengumuman.
- Announcement bar.
- Seeder konten paroki.
- Structured data Organization/Church yang sudah ditunda (D-67).
- Perubahan sidebar admin yang tidak terkait.
- Refactor besar di luar kebutuhan homepage.

### 2.3 Keputusan yang sudah dikonfirmasi pengguna

1. **Cakupan:** ikuti batas roadmap 9A. Hero memakai setting dan aset statis,
   tanpa `hero_slides`, `announcements`, announcement bar, atau CRUD hero/pengumuman.
2. **Aset hero:** gunakan setting media `home_hero_image` + `home_hero_image_alt`,
   dengan fallback statis yang disediakan pengguna.
3. **Teks hero:** tambahkan `home_hero_title` dan `home_hero_description` agar
   admin dapat mengubah teks hero tanpa mengubah identitas paroki.
4. **Renungan dan galeri:** Renungan = post terbaru kategori `renungan`;
   `home_gallery_limit` = jumlah foto.

Keputusan ini mengikat implementasi dan harus dicatat di `docs/DECISIONS.md`
sebagai keputusan baru (D-70).

---

## 3. Implementation Plan

### Langkah 0 — Tambah setting hero ke Pengaturan Situs

Tambahkan empat key ke grup `beranda` di `config/site-settings.php`, di urutan
paling atas agar tampil lebih dulu di tab Beranda:

```php
'home_hero_title',
'home_hero_description',
'home_hero_image',
'home_hero_image_alt',
```

#### Tipe

- `home_hero_image` → `media`
- `home_hero_title` → string (default)
- `home_hero_description` → string (default)
- `home_hero_image_alt` → string (default)

#### Rule

- `home_hero_title`: `nullable|string|max:255`
- `home_hero_description`: `nullable|string|max:1000`
- `home_hero_image_alt`: `nullable|string|max:255|required_with:settings.home_hero_image`

#### Label dan hint

Perbarui `lang/id/site-settings.php`:

- label keempat key,
- `home_hero_description` masuk `multiline`,
- hint untuk gambar hero dan alt text.

#### Validasi media

Di `app/Http/Requests/Settings/UpdateSettingsRequest.php`:

- tambahkan `settings.home_hero_image` dengan `Rule::exists('media', 'id')`
  dan status `MediaStatus::Ready`,
- tambahkan pesan error media yang belum selesai diproses,
- pastikan `home_hero_image_alt` wajib saat gambar dipilih.

`SettingsController` tidak perlu diubah: `mediaUrls()` membaca semua key bertipe
`media`, dan form admin sudah merender `MediaUploadField` otomatis.

#### Dampak test

`tests/Feature/Settings/SiteSettingsTest.php` saat ini mengunci jumlah key
site settings sebanyak **37**. Setelah empat key baru, jumlahnya menjadi **41**.
Test harus diperbarui, termasuk:

- tipe `home_hero_image` = `media`,
- `meta.media` memuat `home_hero_image`,
- `meta.multiline` memuat `home_hero_description`,
- media pending ditolak,
- media ready diterima,
- alt wajib saat gambar dipilih.

### Langkah 1 — Buat konfigurasi homepage

Buat `config/homepage.php`:

```php
return [
    'fallback_hero_image' => '/hero-section.webp',
    'fallback_hero_og_image' => '/hero-section.png',
    'fallback_hero_image_alt' => 'Foto gereja paroki pada malam hari',
    'devotion_category_slug' => 'renungan',
];
```

Alasan:

- Fallback hero tidak ditulis di dua tempat (PHP Blade dan React).
- OG image memakai PNG agar kompatibel dengan pratinjau tautan.
- Kategori renungan tidak menjadi magic string di controller.

### Langkah 2 — Pindahkan komponen yang dipakai dua halaman

Pindahkan ke `resources/js/components/`:

| Komponen | File baru | Konsumen |
| --- | --- | --- |
| `ArticleCard` | `components/article-card.tsx` | Berita, Beranda |
| `EventCard` | `components/event-card.tsx` | Agenda, Beranda |
| `NextMassCard` + `LocationLink` | `components/next-mass-card.tsx` | Jadwal Misa, Beranda |
| `ContactMap` | `components/contact-map.tsx` | Kontak, Beranda |

Aturan:

- Setiap komponen mendefinisikan tipe props-nya sendiri
  (`ArticleCardData`, `EventCardData`, `NextMassCardData`) agar `components/`
  tidak mengimpor tipe feature.
- Tipe feature yang sudah ada tetap dipertahankan dan kompatibel secara struktural.
- Perbarui semua import di Berita, Agenda, Jadwal Misa, Kontak, dan
  `mass-schedule-card.tsx`.
- Perbarui `tests/js/components/next-mass-card.test.tsx`.
- Tidak ada perubahan markup/perilaku; ini murni pemindahan agar dependency
  direction tetap sah (`ARCHITECTURE.md` Part B §4).

### Langkah 3 — Backend: scope agenda dan controller homepage

#### 3.1 Scope agenda bersama

Tambahkan di `app/Models/Event.php`:

- `scopeUpcomingFromToday()` — `start_at >= awal hari ini` di
  `config('app.display_timezone')`, lalu dikonversi ke UTC.

Gunakan scope tersebut di:

- `PublicEventController::upcoming()`,
- `PublicHomeController`.

Ini menjaga definisi “agenda mendatang” hanya satu. Perilaku `/agenda` tidak
berubah dan tetap dilindungi `PublicEventPageTest`.

#### 3.2 Controller homepage

Buat `app/Http/Controllers/Home/PublicHomeController.php` dengan `__invoke()`.

Inject:

- `NextMassResolver`
- `SiteSettingsService`

Baca:

- `home_news_limit`, `home_events_limit`, `home_gallery_limit`
- `home_show_devotion`, `home_show_services`, `home_show_gallery`
- `home_hero_title`, `home_hero_description`, `home_hero_image`,
  `home_hero_image_alt`, `motto_verse`

Bentuk props:

```ts
type HomepageProps = {
    nextMass: NextMassCardData | null;
    news: ArticleCardData[];
    events: EventCardData[];
    devotion: ArticleCardData | null;
    services: HomeService[];
    galleryPhotos: HomeGalleryPhoto[];
    hero: {
        title: string | null;
        description: string | null;
        image: string;
        imageAlt: string;
        ogImage: string;
        width: number;
        height: number;
        motto: string | null;
    };
    sections: {
        devotion: boolean;
        services: boolean;
        gallery: boolean;
    };
};
```

Aturan query:

| Section | Query | Limit |
| --- | --- | --- |
| Misa berikutnya | `NextMassResolver::resolve()` → `NextMassResource` | 1 |
| Berita | `Post::published()->with('category')->orderByDesc('published_at')->orderByDesc('id')` | `home_news_limit` |
| Agenda | `Event::published()->upcomingFromToday()->with(['category','location'])->orderBy('start_at')->orderBy('id')` | `home_events_limit` |
| Renungan | `Post::published()` + kategori `config('homepage.devotion_category_slug')`, urut terbaru | 1 |
| Pelayanan | `Service::active()->ordered()->with('thumbnailMedia.variants')` | semua aktif |
| Galeri | `GalleryPhoto::ready()` + album `published()`, `with(['media.variants','album'])`, urut terbaru | `home_gallery_limit` |

Aturan lain:

- Non-paginated collection selalu `array_values(Resource::collection(...)->resolve())`
  (D-39).
- Section yang dimatikan tidak di-query; prop-nya `null`/`[]`.
- Jangan mengirim `content`, `deleted_at`, `is_active`, `media_id`, dan field
  internal lain.
- `hero.image`:
  - media `home_hero_image` → URL varian `large`/`medium`,
  - fallback `config('homepage.fallback_hero_image')`.
- `hero.imageAlt`:
  - setting `home_hero_image_alt`,
  - fallback `config('homepage.fallback_hero_image_alt')`.
- `hero.ogImage`:
  - `seo_default_og_image` bila ada,
  - fallback `config('homepage.fallback_hero_og_image')`.
- `hero.width`/`height` dari media, fallback 1672×941.
- `hero.title`/`description` null bila belum diisi; React memakai fallback identitas.
- `hero.motto` dari `motto_verse`.

#### 3.3 Resource teaser galeri

Buat `app/Http/Resources/Public/Galeri/PublicGalleryTeaserResource.php`:

- `id`, `caption`, `alt_text`, `thumb_url`, `url`, `width`, `height`
- `album: { title, slug }`

Tujuannya agar Beranda bisa menautkan foto ke album tanpa membocorkan model
mentah atau kolom internal.

#### 3.4 Route

Di `routes/web.php`:

```php
Route::get('/', PublicHomeController::class)->name('home');
```

Nama route `home` tetap. Wayfinder diregenerasi setelah perubahan route;
file generated tidak diedit manual.

### Langkah 4 — SEO server-side

Ubah `resources/views/app.blade.php`:

- Jika route `home` dan `seo_default_og_image` kosong, gunakan
  `config('homepage.fallback_hero_og_image')`.
- Jika route `home` dan `seo_default_description` kosong, gunakan
  `short_description`.
- Jika `short_description` juga kosong, tag tetap dihilangkan.
- Jangan mengarang deskripsi.

Di React, halaman memakai `Seo`:

- `title={seo.title ?? identity.shortName ?? 'Beranda'}`
- `description={seo.description ?? hero.description ?? identity.shortDescription}`
- `ogImage={seo.ogImage ?? hero.ogImage}`
- `ogType="website"`

### Langkah 5 — Frontend homepage

Ganti `resources/js/features/public/beranda/pages/index.tsx` dan tambahkan
komponen:

- `home-hero.tsx`
- `home-quick-links.tsx`
- `home-next-mass-section.tsx`
- `home-news-section.tsx`
- `home-agenda-section.tsx`
- `home-devotion-section.tsx`
- `home-services-section.tsx`
- `home-gallery-section.tsx`

Urutan section:

1. Hero
2. Quick links
3. Misa Berikutnya
4. Berita Terbaru
5. Agenda Mendatang
6. Renungan (jika aktif dan ada data)
7. Pelayanan Sakramen (jika aktif dan ada data)
8. Galeri Kegiatan (jika aktif dan ada data)
9. Kontak (mengikuti `home_show_contact`)
10. Footer existing

#### 5.1 Hero

- H1: `hero.title ?? identity.name ?? 'Paroki HSPMTB Putussibau'`.
- Eyebrow: `identity.tagline ?? identity.shortName`.
- Deskripsi: `hero.description ?? identity.shortDescription`.
- Kutipan opsional: `hero.motto`.
- CTA utama: “Lihat Jadwal Misa” → `jadwal-misa.index`.
- CTA sekunder: “Sejarah Paroki” → `profil.sejarah`.
- Gambar: `hero.image`, `alt={hero.imageAlt}`, `width`/`height` dari props.
- `fetchpriority="high"`, `loading="eager"`, `decoding="async"`.
- Full-bleed, `overflow-hidden`, `min-h` responsif.
- Overlay gelap tipis hanya untuk keterbacaan.
- `object-position` diatur agar gereja/patung tetap terlihat di mobile.
- Jika gambar gagal dimuat, hero tetap punya latar gelap dan teks terbaca.

#### 5.2 Quick links

- Jadwal Misa, Berita & Artikel, Agenda, Pelayanan.
- Memakai Wayfinder, bukan URL hard-code.
- Target sentuh ≥ 44 px, fokus terlihat.

#### 5.3 Section dinamis

- **Misa Berikutnya:** `NextMassCard`; selalu tampil. Saat `null`, empty state
  dari kartu tetap tampil, ditambah tautan “Lihat seluruh jadwal misa”.
- **Berita:** grid `ArticleCard`; sembunyikan section jika kosong.
- **Agenda:** `EventCard`; pertahankan label `cancelled`/`postponed`;
  sembunyikan section jika kosong.
- **Renungan:** satu kartu editorial dari post terbaru kategori `renungan`;
  sembunyikan jika toggle false atau kosong.
- **Pelayanan:** grid ringkas service aktif; badge darurat bila ada;
  sembunyikan jika toggle false atau kosong.
- **Galeri:** grid/strip foto; setiap foto menaut ke album;
  `alt_text` → `caption` → string kosong; sembunyikan jika toggle false atau kosong.
- **Kontak:** `HomeContactSection` + peta dari `ContactMap`; sembunyikan
  seluruhnya jika `home_show_contact=false` atau tidak ada data.

#### 5.4 Empty state

- Section opsional tanpa data tidak dirender agar tidak ada heading tanpa isi.
- Misa Berikutnya tetap punya empty state informatif.
- Kontak mengembalikan `null` jika tidak ada item dan tidak ada peta.
- Gambar kartu memakai fallback `ParishImage`; hero memakai fallback visual.

#### 5.5 Kontak dan peta

`HomeContactSection` diperluas:

- tetap membaca shared `contact`,
- menampilkan `ContactMap` bila `mapsEmbedUrl` atau `mapsLink` valid,
- `homeShowContact=false` menyembunyikan seluruh blok, termasuk peta,
- tidak menambah query kontak kedua.

### Langkah 6 — Optimasi aset hero

- Commit `public/hero-section.png`.
- Buat `public/hero-section.webp` dengan `cwebp` yang sudah tersedia.
- Gunakan WebP sebagai gambar hero fallback; PNG untuk fallback OG.
- Jika admin mengunggah `home_hero_image`, gambar hero memakai varian media
  (WebP).
- Catat bahwa aset fallback adalah aset statis, bukan upload Media; PNG/WebP
  tidak membawa EXIF.

Perintah yang direncanakan:

```bash
cwebp -q 82 public/hero-section.png -o public/hero-section.webp
```

### Langkah 7 — Test

#### 7.1 Pest

Buat `tests/Feature/Public/PublicHomepageTest.php`:

1. Guest dapat membuka `/`.
2. Component `public/beranda`.
3. `nextMass` berasal dari resolver; `null` saat tidak ada jadwal.
4. Berita hanya published, urut terbaru, limit `home_news_limit`.
5. Draft/scheduled/future/archived/soft-deleted tidak muncul.
6. Agenda hanya published dan `start_at >= awal hari ini WIB`, urut terdekat,
   limit `home_events_limit`.
7. Agenda cancelled/postponed tetap muncul dengan status benar.
8. Pelayanan hanya aktif, urut `sort_order`/`id`.
9. Galeri hanya foto `ready` dari album published, limit `home_gallery_limit`.
10. Renungan hanya dari kategori `renungan`; toggle false → `null`.
11. `home_show_services=false`, `home_show_gallery=false`,
    `home_show_devotion=false` mengosongkan data.
12. `home_show_contact=false` tidak memengaruhi `/kontak`.
13. Data kosong tidak menyebabkan exception.
14. Field internal tidak terkirim (`content`, `deleted_at`, `is_active`, `media_id`).
15. `hero.title`, `hero.description`, `hero.image`, `hero.imageAlt`, `hero.motto`
    sesuai setting/fallback.
16. `home_hero_image` yang sudah ready dipakai; media pending ditolak.
17. Fallback OG image homepage di HTML awal.
18. Fallback meta description dari `short_description`.

Perbarui:

- `tests/Feature/Public/PublicRoutesTest.php`: keluarkan `/` dari
  `placeholder pages`, tetap di `public pages`.
- `tests/Feature/Settings/SiteSettingsTest.php`: key count 41, tipe
  `home_hero_image` media, validasi media, meta form, dan
  `home_hero_image_alt` wajib saat gambar dipilih.
- `tests/Feature/Agenda/PublicEventPageTest.php` bila scope mengubah
  implementasi internal (perilaku tetap).

#### 7.2 Vitest

Tambahkan `tests/js/features/public/beranda/homepage.test.tsx`:

- Hero merender H1, CTA, alt, dan `href` benar.
- Quick links mengarah ke route benar.
- Section berita/agenda/renungan/pelayanan/galeri disembunyikan saat data kosong.
- Toggle section mematikan render.
- Kontak menampilkan peta hanya saat URL valid.
- `home-contact-section.test.tsx` diperluas untuk peta/directions.
- `next-mass-card.test.tsx` diperbarui import path.

#### 7.3 Playwright

Perbarui `tests/e2e/public.spec.ts`:

- Title tidak lagi `/Selamat Datang/`; gunakan `/HSPMTB|Beranda/`.
- H1 terlihat.
- Hero image terlihat dan `naturalWidth > 0`.
- CTA “Lihat Jadwal Misa” → `/jadwal-misa`.
- CTA “Sejarah Paroki” → `/profil/sejarah`.
- Quick links berfungsi.
- Tidak ada `[ISI:` atau “placeholder fondasi”.
- Homepage tetap usable saat data kosong (kondisi E2E bare).
- Tidak ada overflow horizontal di 360 px.
- Jangan mengandalkan data berita/agenda tertentu.

### Langkah 8 — Dokumentasi

- Tambah `docs/DECISIONS.md` D-70:
  - Homepage memakai controller agregasi.
  - `home_hero_title`, `home_hero_description`, `home_hero_image`, dan
    `home_hero_image_alt` adalah setting baru yang disetujui pemilik proyek;
    tidak ada di PRD Lampiran B.
  - Hero fallback statis + WebP; OG fallback PNG.
  - Announcement bar, `hero_slides`, dan `announcements` tidak dibangun di 9A.
  - `home_gallery_limit` = jumlah foto.
  - Renungan = post kategori `renungan`.
  - Section opsional disembunyikan saat kosong.
  - `Event::upcomingFromToday`.
  - Komponen yang dipindahkan ke `components/`.
  - Divergensi dari PRD dan AC yang belum terpenuhi.
- Perbarui `docs/roadmap/README.md` dan status
  `docs/roadmap/phase-9a-homepage.md` setelah semua gate lulus.
- Perbarui tabel komponen di `docs/ARCHITECTURE.md` bila perlu.
- Jangan menandai Phase 9B selesai.

---

## 4. File yang Akan Terpengaruh

### 4.1 Dibuat

**Backend**

- `config/homepage.php`
- `app/Http/Controllers/Home/PublicHomeController.php`
- `app/Http/Resources/Public/Galeri/PublicGalleryTeaserResource.php`
- `tests/Feature/Public/PublicHomepageTest.php`

**Frontend**

- `resources/js/features/public/beranda/types.ts`
- `resources/js/features/public/beranda/components/home-hero.tsx`
- `resources/js/features/public/beranda/components/home-quick-links.tsx`
- `resources/js/features/public/beranda/components/home-next-mass-section.tsx`
- `resources/js/features/public/beranda/components/home-news-section.tsx`
- `resources/js/features/public/beranda/components/home-agenda-section.tsx`
- `resources/js/features/public/beranda/components/home-devotion-section.tsx`
- `resources/js/features/public/beranda/components/home-services-section.tsx`
- `resources/js/features/public/beranda/components/home-gallery-section.tsx`
- `resources/js/components/article-card.tsx` (pindahan)
- `resources/js/components/event-card.tsx` (pindahan)
- `resources/js/components/next-mass-card.tsx` (pindahan)
- `resources/js/components/contact-map.tsx` (pindahan)
- `tests/js/features/public/beranda/homepage.test.tsx`

**Aset**

- `public/hero-section.webp`

### 4.2 Diubah

**Backend**

- `routes/web.php`
- `app/Models/Event.php`
- `app/Http/Controllers/Agenda/PublicEventController.php`
- `config/site-settings.php`
- `lang/id/site-settings.php`
- `app/Http/Requests/Settings/UpdateSettingsRequest.php`
- `resources/views/app.blade.php`

**Frontend**

- `resources/js/features/public/beranda/pages/index.tsx`
- `resources/js/features/public/beranda/components/home-contact-section.tsx`
- `resources/js/features/public/berita/pages/index.tsx`
- `resources/js/features/public/berita/pages/show.tsx`
- `resources/js/features/public/agenda/pages/index.tsx`
- `resources/js/features/public/agenda/pages/show.tsx`
- `resources/js/features/public/jadwal-misa/pages/index.tsx`
- `resources/js/features/public/jadwal-misa/components/mass-schedule-card.tsx`
- `resources/js/features/public/kontak/pages/index.tsx`

**Test**

- `tests/Feature/Public/PublicRoutesTest.php`
- `tests/Feature/Settings/SiteSettingsTest.php`
- `tests/e2e/public.spec.ts`
- `tests/js/components/next-mass-card.test.tsx`
- `tests/js/features/public/beranda/home-contact-section.test.tsx`

**Dokumentasi**

- `docs/DECISIONS.md`
- `docs/roadmap/README.md`
- `docs/roadmap/phase-9a-homepage.md`
- `docs/ARCHITECTURE.md` (tabel komponen, bila diperlukan)
- `AGENTS.md` (ringkasan status Phase 9A, bila diperlukan)

**Aset yang perlu ditambahkan ke git**

- `public/hero-section.png` (saat ini untracked)

### 4.3 Dihapus/dipindahkan

File lama berikut dihapus setelah isinya dipindahkan:

- `resources/js/features/public/berita/components/article-card.tsx`
- `resources/js/features/public/agenda/components/event-card.tsx`
- `resources/js/features/public/jadwal-misa/components/next-mass-card.tsx`
- `resources/js/features/public/kontak/components/contact-map.tsx`

Tidak ada file lain yang dihapus.

---

## 5. Dependency / Configuration Impact

- **Tidak ada dependency Composer/npm baru.**
- **Tidak ada migration, tabel, model, policy, permission, atau CRUD admin baru.**
- **Ada empat setting baru** di grup `beranda`:
  `home_hero_title`, `home_hero_description`, `home_hero_image`,
  `home_hero_image_alt`.
- Total key site settings menjadi **41** (sebelumnya 37).
- `home_hero_image` memakai media pipeline yang sudah ada; admin bisa
  mengunggah/mengganti dari Pengaturan Situs.
- `home_hero_image_alt` wajib saat gambar dipilih; validasi di
  `UpdateSettingsRequest`.
- `config/homepage.php` menyimpan fallback aset dan slug kategori renungan.
- Route `/` berubah dari `Route::inertia` menjadi controller; nama route `home`
  tetap.
- Wayfinder perlu diregenerasi.
- `Event` mendapat scope baru; `PublicEventController` memakainya.
- `resources/views/app.blade.php` mendapat fallback OG/description khusus homepage.
- `public/hero-section.png` harus masuk git; `public/hero-section.webp` dibuat
  dari aset tersebut.
- `public/storage` tetap diperlukan untuk URL media modul lain.
- E2E memakai database kosong; homepage tampil sparse dan itu memang skenario
  yang diuji.
- Announcement bar, `hero_slides`, dan `announcements` **tidak** ditambahkan.

---

## 6. Risiko dan Potensi Konflik

### 6.1 Konflik PRD vs Phase 9A

PRD §7.1 mewajibkan:

- `hero_slides` dan admin `/admin/hero`,
- `announcements` dan admin `/admin/pengumuman`,
- announcement bar (HOME-03),
- hero maksimal 5 slide (HOME-04/HOME-05),
- AC-HOME-2 dan AC-HOME-5.

Roadmap Phase 9A melarang modul CRUD Beranda baru dan tidak mencantumkan
announcement bar. Dengan keputusan yang dipilih:

- **HOME-03, HOME-04, HOME-05, AC-HOME-5 tidak terpenuhi.**
- **AC-HOME-2 terpenuhi sebagian**: teks dan gambar hero bisa diubah dari
  Pengaturan Situs, tetapi pengumuman tidak ada.
- Ini harus dicatat sebagai divergensi sadar di `docs/DECISIONS.md`.

### 6.2 Risiko teknis

1. **Aset hero untracked** — jika tidak di-commit, E2E dan produksi 404.
2. **Ukuran hero** — PNG 2 MB; WebP mengurangi, tetapi LCP tetap harus diukur.
3. **Pemindahan komponen** — import dan `ArchitectureTest` bisa rusak jika ada
   yang terlewat.
4. **`Event::upcomingFromToday`** — batas “awal hari ini WIB” harus persis sama
   dengan `/agenda`.
5. **Eloquent strict mode** — semua relasi harus di-eager-load.
6. **Non-paginated collection** — wajib `array_values(...->resolve())`.
7. **`home_gallery_limit`** — diputuskan sebagai jumlah foto, bukan album.
8. **Renungan** — bergantung pada kategori slug `renungan`; jika slug diubah,
   section hilang.
9. **Meta description** — tidak boleh dikarang; jika setting kosong, tag tidak
   dirender.
10. **`home_hero_image_alt` wajib** — menambah friksi admin, tetapi sesuai PRD
    HOME-05/XC-M5.
11. **`npm run check:fix` tidak aman** — jangan jalankan membabi buta; gunakan
    `npm run check` dan periksa diff.
12. **E2E memakai `public/build`** — wajib `npm run build` sebelum `npm run e2e`.
13. **Port 8000** — cek `ss -ltnp | grep 8000` sebelum E2E.
14. **Pest dan Playwright berbagi database** — gunakan `npm run e2e`, bukan
    `npx playwright test` langsung.
15. **Kontak** — tetap satu shared prop; jangan membuat query kontak kedua.
16. **SEO** — gunakan `Seo`, bukan `Head` saja.

---

## 7. Verification Plan

### 7.1 Regenerate Wayfinder

```bash
php artisan wayfinder:generate --with-form
```

### 7.2 Targeted backend

```bash
php artisan test --compact tests/Feature/Public/PublicHomepageTest.php
php artisan test --compact tests/Feature/Public/PublicRoutesTest.php
php artisan test --compact tests/Feature/Agenda/PublicEventPageTest.php
php artisan test --compact tests/Feature/Settings/SiteSettingsTest.php
php artisan test --compact tests/Feature/MassSchedule/PublicMassSchedulePageTest.php
```

### 7.3 Targeted frontend

```bash
npm run test:unit -- tests/js/features/public/beranda/homepage.test.tsx
npm run test:unit -- tests/js/features/public/beranda/home-contact-section.test.tsx
npm run test:unit -- tests/js/components/next-mass-card.test.tsx
```

### 7.4 Format dan gate penuh

```bash
vendor/bin/pint --dirty --format agent
composer test
composer ci:check
```

`composer test` mencakup Pint check, PHPStan, dan seluruh Pest.
`composer ci:check` mencakup lint/format frontend, typecheck, Vitest, dan Pest.

### 7.5 Build dan E2E

```bash
npm run build
ss -ltnp | grep 8000
npm run e2e
```

`npm run e2e` menjalankan `app:e2e:prepare` lebih dulu. Jika ada server lama di
port 8000, hentikan atau gunakan `E2E_PORT`.

### 7.6 Verifikasi manual

- Viewport 360, 390, 768, 1024, 1440 px.
- Tidak ada horizontal overflow.
- Satu H1, section H2, kartu H3.
- Keyboard: skip link, navbar, CTA, quick links, kartu, peta.
- Dark mode: hero tetap terbaca; section memakai token semantik.
- Empty state: tanpa misa, berita, agenda, renungan, pelayanan, galeri, kontak.
- `home_show_contact=false`: blok kontak hilang, `/kontak` tetap 200.
- `home_show_services=false` dan `home_show_gallery=false`: section hilang.
- HTML awal: `<title>`, meta description (jika data ada), `og:image` fallback.
- Page source tidak memuat `content`, `deleted_at`, `is_active`, `media_id`.
- Admin: unggah `home_hero_image`, isi alt, simpan, dan lihat perubahan di
  homepage.
- Admin: kosongkan `home_hero_image`, simpan, dan pastikan fallback statis muncul.

### 7.7 CI

- Pastikan workflow GitHub pada commit final lulus.
- Laporkan perintah yang benar-benar dijalankan dan hasilnya; jangan mengklaim
  lulus tanpa menjalankan.

---

## 8. Acceptance Criteria yang Akan Terpenuhi

### 8.1 AC Phase 9A

| AC | Status rencana |
| --- | --- |
| AC-01 Beranda publik bukan placeholder | Terpenuhi |
| AC-02 Hero, identitas, CTA, quick links | Terpenuhi |
| AC-03 Data dinamis dari resolver/modul | Terpenuhi |
| AC-04 Limit dan toggle setting | Terpenuhi |
| AC-05 Empty state dan robustness | Terpenuhi |
| AC-06 Responsive dan accessibility | Terpenuhi |
| AC-07 Kualitas teknis | Terpenuhi |
| AC-08 Batas scope | Terpenuhi |
| AC-09 Tidak ada CRUD/tabel homepage baru | Terpenuhi |
| AC-10 Tidak ada Admin Dashboard | Terpenuhi |

### 8.2 AC PRD yang perlu dicatat

| AC PRD | Status |
| --- | --- |
| AC-HOME-1 semua blok aktif tampil | **Sebagian** — announcement bar tidak dibangun |
| AC-HOME-2 perubahan hero/pengumuman dari admin | **Sebagian** — hero title/description/image/alt bisa diubah; pengumuman tidak ada |
| AC-HOME-3 tidak ada overflow 360 px | Terpenuhi |
| AC-HOME-4 title, meta description, OG image | **Sebagian** — title/OG ada; description hanya jika setting diisi |
| AC-HOME-5 announcement bar | **Tidak terpenuhi** — di luar scope |

---

## 9. Pertanyaan / Ambiguity

Tidak ada pertanyaan blocking tersisa. Keputusan yang sudah dikonfirmasi:

1. **Cakupan** — ikuti roadmap 9A; tanpa `hero_slides`, `announcements`,
   announcement bar, atau CRUD hero/pengumuman.
2. **Hero image** — setting media `home_hero_image` + `home_hero_image_alt`,
   dengan fallback statis `public/hero-section.webp` dan OG PNG.
3. **Hero text** — setting baru `home_hero_title` dan `home_hero_description`;
   fallback ke identitas paroki.
4. **Renungan** — post terbaru kategori `renungan`.
5. **Galeri** — `home_gallery_limit` = jumlah foto.

Hal yang perlu disetujui saat implementasi:

- `home_hero_image_alt` wajib diisi saat gambar hero dipilih.
- Fallback OG memakai PNG statis, bukan gambar media yang diunggah.
- Announcement bar tidak dibangun dan akan dicatat sebagai divergensi PRD.

---

## 10. Status

**Status: Implemented — engineering complete (branch `feat/phase-9a-homepage`).**

Rencana ini dieksekusi pada branch `feat/phase-9a-homepage`. Ringkasan hasil,
file yang berubah, dan gate verifikasi dicatat pada laporan akhir pekerjaan dan
pada D-70 di `docs/DECISIONS.md`.
