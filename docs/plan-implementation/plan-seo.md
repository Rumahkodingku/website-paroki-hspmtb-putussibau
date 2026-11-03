# Rencana Implementasi SEO

## Website Paroki HSPMTB Putussibau

| Atribut           | Nilai                                                                                 |
| ----------------- | ------------------------------------------------------------------------------------- |
| Dokumen           | Rencana Implementasi SEO                                                              |
| Produk            | Website Resmi Gereja Katolik Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau |
| Status            | Final — siap dieksekusi                                                               |
| Prioritas         | P1                                                                                    |
| Dokumen sumber    | `docs/specifications/spec-seo.md`                                                     |
| Stack             | Laravel 13, PHP 8.3, Inertia v3, React 19, TypeScript, Tailwind CSS v4, MySQL 8       |
| Branch            | `main`                                                                                |
| Basis commit      | `08105ab`                                                                             |
| Bahasa UI         | Bahasa Indonesia                                                                      |
| Penyimpanan waktu | UTC                                                                                   |
| Zona tampilan     | `Asia/Pontianak`                                                                      |
| Dependency baru   | Tidak ada                                                                             |

---

## Daftar Isi

1. [Ringkasan Keputusan](#1-ringkasan-keputusan)
2. [Prinsip Implementasi](#2-prinsip-implementasi)
3. [Kondisi Codebase Saat Ini](#3-kondisi-codebase-saat-ini)
4. [Arsitektur Target](#4-arsitektur-target)
5. [Model Data](#5-model-data)
6. [Kontrak Backend](#6-kontrak-backend)
7. [Kontrak Frontend](#7-kontrak-frontend)
8. [Sitemap dan robots.txt](#8-sitemap-dan-robotstxt)
9. [Admin SEO Halaman Statis](#9-admin-seo-halaman-statis)
10. [Structured Data JSON-LD](#10-structured-data-json-ld)
11. [Keamanan, Audit, dan Cache](#11-keamanan-audit-dan-cache)
12. [Task Breakdown](#12-task-breakdown)
13. [File yang Akan Terpengaruh](#13-file-yang-akan-terpengaruh)
14. [Test Matrix](#14-test-matrix)
15. [Verification Plan](#15-verification-plan)
16. [Risiko dan Mitigasi](#16-risiko-dan-mitigasi)
17. [Acceptance Criteria](#17-acceptance-criteria)
18. [Yang Sengaja Tidak Dikerjakan](#18-yang-sengaja-tidak-dikerjakan)
19. [Catatan Operasional](#19-catatan-operasional)
20. [Status](#20-status)

---

## 1. Ringkasan Keputusan

Empat belas keputusan berikut sudah dikonfirmasi pemilik proyek dan mengikat seluruh isi dokumen.

| #    | Topik                   | Keputusan                                                                                    |
| ---- | ----------------------- | -------------------------------------------------------------------------------------------- |
| D-1  | Metadata HTML awal      | Resolver server-side + Blade. Inertia SSR tetap off sesuai D-26.                             |
| D-2  | Sitemap XML             | Route `/sitemap.xml` tanpa dependency. `spatie/laravel-sitemap` tidak dipakai.               |
| D-3  | SEO halaman statis      | Tabel `page_seo` dengan `page_key` allowlist, UI admin, validasi, dan audit.                 |
| D-4  | Cakupan halaman statis  | Semua halaman statis/indeks publik (18 halaman). Detail konten memakai data konten.          |
| D-5  | Isi sitemap             | Halaman statis, berita, agenda, pelayanan, komunitas, area, dan galeri.                      |
| D-6  | Agenda lewat            | Semua agenda terbit dimasukkan, termasuk arsip.                                              |
| D-7  | Canonical daftar/filter | Filter dan pencarian ke URL dasar; `?page=N` (N>1) self-canonical; `?q=` mendapat `noindex`. |
| D-8  | Robots per halaman      | Tidak ada kontrol UI. Sistem menetapkan `index`/`noindex`.                                   |
| D-9  | Format judul            | Judul Halaman \| Nama Paroki, nama dari `parish_short_name` lalu `parish_name`.              |
| D-10 | robots.txt              | Route dinamis dari `APP_URL`; `public/robots.txt` dihapus.                                   |
| D-11 | SEO agenda              | Fallback dari judul dan `plain_description`; tidak ada kolom SEO baru.                       |
| D-12 | OG image global         | Tetap string path/URL dengan validasi; tidak diubah menjadi media ID.                        |
| D-13 | Render JSON-LD          | `Organization`/`WebSite` di Blade; `Article`/`Event`/`BreadcrumbList` di React.              |
| D-14 | Izin admin SEO          | Permission baru `seo.view` dan `seo.update`.                                                 |

### 1.1 Alasan utama menolak Spatie

Verifikasi Composer menunjukkan:

- `spatie/laravel-sitemap:8.2.0` mewajibkan PHP `^8.4`, sedangkan proyek memakai PHP 8.3.
- `spatie/laravel-sitemap:8.0.0` mendukung PHP 8.3, tetapi hanya dapat dipasang dengan `-W`.
- Pemasangan tersebut menurunkan `guzzlehttp/guzzle` 8.2.0 → 7.16.1, mengubah 31 package, menghapus 1 package, dan menarik `spatie/crawler` serta `spatie/browsershot` yang tidak diperlukan.
- Sitemap hanya berisi URL dari model dan route, sehingga generator XML sederhana lebih kecil risikonya.

---

## 2. Prinsip Implementasi

1. **Satu resolver server** — title, description, canonical, robots, dan OG image ditentukan di server, bukan dihitung ulang di browser.
2. **Blade dan React memakai nilai yang sama** — tidak ada canonical ganda atau konflik antara HTML awal dan hasil hidrasi.
3. **Global adalah fallback** — metadata spesifik halaman selalu menang atas `seo_default_*`.
4. **Tidak mengarang data** — JSON-LD hanya memakai data yang benar-benar ditampilkan atau tersimpan.
5. **Allowlist** — `page_key` hanya dari konfigurasi; tidak ada input bebas dari browser.
6. **No secrets** — token verifikasi dan data pribadi tidak masuk source code atau JSON-LD.
7. **Noindex bukan proteksi** — admin, preview, dan installer tetap dilindungi autentikasi/middleware.
8. **Tanpa dependency baru** — sitemap dan metadata memakai Laravel, React, dan helper yang sudah ada.
9. **Testable** — setiap aturan canonical, filter, robots, dan sitemap memiliki test regresi.
10. **Konsisten dengan arsitektur** — `SiteSettingsService` tetap satu-satunya akses pengaturan global; model tidak dikirim mentah ke frontend.

---

## 3. Kondisi Codebase Saat Ini

| Area                              | Kondisi aktual                                                                                                        | Implikasi                                                          |
| --------------------------------- | --------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------ |
| `resources/views/app.blade.php`   | Head baseline memakai `rtrim(config('app.url')) . request()->getRequestUri()` untuk canonical dan `og:url`.           | Query string masuk canonical.                                      |
| `resources/js/components/seo.tsx` | Fallback canonical memakai `page.url`; `og:url` diabsolutkan, canonical tidak.                                        | Canonical bisa relatif.                                            |
| Detail berita/agenda              | Memakai `window.location.href` sebagai canonical.                                                                     | Canonical bergantung browser dan membawa query string.             |
| Title                             | `<Seo>` merender `<title>`, banyak halaman juga merender `<Head title>`, dan `app.tsx` menambahkan `- VITE_APP_NAME`. | Dua sumber judul bersaing.                                         |
| Metadata per halaman              | Hanya default global yang ada di HTML awal.                                                                           | Berita/agenda baru punya title/description setelah hidrasi.        |
| Preview admin                     | `noIndex` hanya diberikan React.                                                                                      | HTML awal preview masih `index, follow`.                           |
| Sitemap                           | Belum ada.                                                                                                            | Crawler tidak punya daftar URL publik.                             |
| robots.txt                        | `public/robots.txt` hanya `User-agent: *` dan `Disallow:`.                                                            | Tidak menunjuk sitemap.                                            |
| JSON-LD                           | Hanya `Event`, dirender React, memakai `data-head-key`.                                                               | `head-key` tidak dikenali Inertia v3; schema lain belum ada.       |
| SEO konten                        | `Post` punya `meta_title`/`meta_description` + fallback; `Event` belum punya field SEO; `Service` punya fallback.     | Pertahankan field existing; agenda memakai fallback.               |
| Persistence halaman statis        | Belum ada.                                                                                                            | Perlu tabel `page_seo`.                                            |
| Batasan arsitektur                | SSR off (D-26); `ArchitectureTest` mengunci sinkronisasi `head-key` ↔ `data-inertia`.                                 | Jangan mengaktifkan SSR; jangan menambah key tanpa pasangan Blade. |
| Runtime                           | PHP 8.3; Spatie terbaru butuh PHP 8.4.                                                                                | Sitemap tanpa dependency.                                          |

### 3.1 Bukti file

- `resources/views/app.blade.php` baris 208–209.
- `resources/js/components/seo.tsx` baris 24–29, 92–97.
- `resources/js/app.tsx` baris 14–15, 63.
- `resources/js/features/public/berita/pages/show.tsx` baris 27, 36.
- `resources/js/features/public/agenda/pages/show.tsx` baris 20, 31.
- `app/Http/Middleware/HandleInertiaRequests.php` baris 125, 150–167.
- `app/Models/Post.php` baris 147–153, 213–237.
- `app/Models/Event.php` baris 128–131, 215–224.
- `app/Http/Resources/Public/News/PublicPostDetailResource.php` baris 52–56.
- `app/Http/Resources/Public/Agenda/PublicEventDetailResource.php` baris 29–49.
- `tests/Unit/ArchitectureTest.php` baris 796–822.
- `tests/Feature/Foundation/InfrastructureFoundationTest.php` baris 268–337.

---

## 4. Arsitektur Target

```text
Request publik
│
├─ HandleInertiaRequests::share()
│  └─ SeoMetadataResolver
│     ├─ CanonicalUrlResolver
│     ├─ PageSeo (tabel page_seo)
│     ├─ Post / Event / Service / Community / Area / GalleryAlbum
│     └─ SiteSettingsService (fallback global)
│
├─ Blade resources/views/app.blade.php
│  ├─ title, description, canonical, robots, Open Graph, Twitter
│  └─ JSON-LD Organization + WebSite
│
└─ React
   ├─ Seo membaca shared prop `seo.page`
   ├─ Article JSON-LD
   ├─ Event JSON-LD
   └─ BreadcrumbList JSON-LD

Route terpisah
├─ GET /sitemap.xml → SitemapController
└─ GET /robots.txt  → RobotsController
```

### 4.1 Alur data

1. `HandleInertiaRequests::share()` memanggil `SeoMetadataResolver::resolve($request)`.
2. Resolver memakai route name dan route model binding yang sudah tersedia.
3. Hasilnya menjadi `seo.page` di shared props.
4. Blade membaca `$page['props']['seo']['page']` untuk HTML awal.
5. React membaca `usePage().props.seo.page` untuk hidrasi dan navigasi lunak.
6. Sitemap memakai route dan scope model yang sama, bukan crawling.

---

## 5. Model Data

### 5.1 Migration `page_seo`

Buat migration baru:

```text
database/migrations/2026_11_03_000000_create_page_seo_table.php
```

Skema:

```php
Schema::create('page_seo', function (Blueprint $table): void {
    $table->id();
    $table->string('page_key')->unique();
    $table->string('meta_title')->nullable();
    $table->text('meta_description')->nullable();
    $table->foreignId('og_media_id')
        ->nullable()
        ->constrained('media')
        ->nullOnDelete();
    $table->timestamps();
});
```

Kolom `robots` tidak dibuat karena tidak ada kontrol UI dan nilai robots ditentukan resolver.

### 5.2 Model `PageSeo`

`app/Models/PageSeo.php`:

```php
#[Fillable([
    'page_key',
    'meta_title',
    'meta_description',
    'og_media_id',
])]
class PageSeo extends Model
{
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_media_id');
    }

    public function ogImageUrl(): ?string
    {
        return $this->media?->url('medium');
    }
}
```

Ketentuan:

- Tidak mengirim model mentah ke frontend.
- URL media di-resolve lewat `Media::url('medium')`.
- `page_key` tidak diisi dari input browser.

### 5.3 Allowlist halaman

File baru `config/seo.php`:

```php
return [
    'title_separator' => ' | ',

    'static_pages' => [
        'home' => [
            'route' => 'home',
            'label' => 'Beranda',
            'title' => null,
            'description' => null,
        ],
        // ...
    ],
];
```

Daftar `page_key` dan route:

| page_key                | Route                 | Label                 |
| ----------------------- | --------------------- | --------------------- |
| `home`                  | `home`                | Beranda               |
| `profile`               | `profil.index`        | Profil Paroki         |
| `profile_history`       | `profil.sejarah`      | Sejarah Paroki        |
| `profile_area_coverage` | `profil.wilayah`      | Wilayah Pelayanan     |
| `profile_pastors`       | `profil.pastor`       | Pastor Paroki         |
| `profile_structure`     | `profil.struktur`     | Struktur Kepengurusan |
| `area`                  | `area.index`          | Area Paroki           |
| `mass_schedule`         | `jadwal-misa.index`   | Jadwal Misa           |
| `news`                  | `berita.index`        | Berita & Artikel      |
| `agenda`                | `agenda.index`        | Agenda Kegiatan       |
| `services`              | `pelayanan.index`     | Pelayanan Paroki      |
| `communities`           | `komunitas.index`     | Komunitas             |
| `gallery`               | `galeri.index`        | Galeri                |
| `livestreaming`         | `livestreaming.index` | Livestreaming         |
| `documents`             | `dokumen.index`       | Dokumen               |
| `contact`               | `kontak.index`        | Hubungi Kami          |
| `privacy`               | `legal.privacy`       | Kebijakan Privasi     |
| `terms`                 | `legal.terms`         | Syarat dan Ketentuan  |

Nilai `title`/`description` default diambil dari teks yang sudah ada di komponen React. Halaman yang belum punya deskripsi memakai fallback global; tidak membuat copy editorial baru tanpa persetujuan.

---

## 6. Kontrak Backend

### 6.1 Class baru

| File                                        | Tanggung jawab                                              |
| ------------------------------------------- | ----------------------------------------------------------- |
| `app/Support/Seo/SeoMetadataResolver.php`  | Menyusun metadata halaman dari route, model, dan pengaturan |
| `app/Support/Seo/CanonicalUrlResolver.php` | Menyusun canonical absolut dan aturan pagination            |
| `app/Support/SeoTitle.php`                  | Memformat judul dan nama paroki                             |

### 6.2 Bentuk data resolver

```php
[
    'title' => string,
    'description' => ?string,
    'canonical' => string,
    'ogImage' => ?string,
    'ogType' => 'website'|'article',
    'robots' => 'index, follow'|'noindex, follow',
]
```

### 6.3 Shared prop

`HandleInertiaRequests::seoDefaults()` diperluas:

```php
'seo' => fn (): array => [
    'appUrl' => rtrim((string) config('app.url'), '/'),
    'siteName' => $read('parish_name'),
    'title' => $read('seo_default_title'),
    'description' => $read('seo_default_description'),
    'ogImage' => $read('seo_default_og_image'),
    'page' => app(SeoMetadataResolver::class)->resolve($request),
],
```

Tambahan `abilities`:

```php
'seo.view' => (bool) $request->user()?->can('seo.view'),
'seo.update' => (bool) $request->user()?->can('seo.update'),
```

### 6.4 Aturan title

| Halaman        | Sumber title                                                 |
| -------------- | ------------------------------------------------------------ |
| Beranda        | `seo_default_title` → `parish_short_name` → `parish_name`    |
| Halaman statis | `page_seo.meta_title` → config `title` → `seo_default_title` |
| Berita         | `Post::displayMetaTitle()`                                   |
| Agenda         | `Event.title`                                                |
| Pelayanan      | `Service.title`                                              |
| Komunitas      | `Community.name`                                             |
| Area           | `Area.name`                                                  |
| Galeri         | `GalleryAlbum.title`                                         |
| Dokumen        | Teks halaman `Dokumen`                                       |

Formatter:

```php
public static function format(string $title, ?string $siteName): string
{
    $title = trim($title);

    if ($siteName === null || trim($siteName) === '') {
        return $title;
    }

    if ($title === trim($siteName)) {
        return $title;
    }

    return $title.' | '.trim($siteName);
}
```

Nama situs: `parish_short_name` → `parish_name`. Duplikasi aturan di TypeScript dijaga oleh `resources/js/lib/seo-title.ts` dan test unit.

### 6.5 Aturan description

| Halaman        | Sumber description                                               |
| -------------- | ---------------------------------------------------------------- |
| Berita         | `Post::displayDescription()`                                     |
| Agenda         | `Event::plainDescription()`                                      |
| Pelayanan      | `summary` → plain text `introduction`                            |
| Komunitas      | `description` sebagai plain text                                 |
| Area           | `description` sebagai plain text                                 |
| Galeri         | `description` sebagai plain text                                 |
| Halaman statis | `page_seo.meta_description` → config → `seo_default_description` |
| Beranda        | `seo_default_description` → `short_description`                  |

Description kosong tidak dirender sebagai tag kosong.

### 6.6 Aturan canonical

`CanonicalUrlResolver`:

1. Default `url()->current()` — absolut, tanpa query.
2. `berita.index` dan `agenda.index`: jika `page > 1`, tambahkan `?page=N`.
3. Parameter `q`, `kategori`, `bulan`, `mode`, `sort` dibuang dari canonical.
4. Tidak memakai `window.location.href`.
5. `og:url` sama dengan canonical.
6. Basis `APP_URL`; produksi wajib HTTPS.

Contoh:

| URL diminta                 | Canonical                 |
| --------------------------- | ------------------------- |
| `/berita?q=misa`            | `/berita`                 |
| `/berita?kategori=renungan` | `/berita`                 |
| `/berita?page=2`            | `/berita?page=2`          |
| `/berita/judul-artikel`     | `/berita/judul-artikel`   |
| `/agenda?bulan=2026-11`     | `/agenda`                 |
| `/agenda/kegiatan-paroki`   | `/agenda/kegiatan-paroki` |

### 6.7 Aturan robots

| Kondisi               | Nilai             |
| --------------------- | ----------------- |
| Halaman publik normal | `index, follow`   |
| `/berita?q=...`       | `noindex, follow` |
| `/admin/*`            | `noindex, follow` |
| `/install/*`          | `noindex, follow` |
| Preview berita        | `noindex, follow` |
| Halaman error         | `noindex, follow` |

`noindex` bukan pengganti autentikasi.

### 6.8 Route model binding

Resolver membaca model yang sudah di-bind:

- `berita.show` → `Post`
- `agenda.show` → `Event`
- `pelayanan.show` → `Service`
- `komunitas.show` → `Community`
- `area.show` → `Area`
- `galeri.show` → `GalleryAlbum`

Tidak ada controller baru untuk halaman detail.

### 6.9 Blade

`resources/views/app.blade.php`:

- `$seoPage = $page['props']['seo']['page'] ?? null`.
- Title memakai `SeoTitle::format()`.
- Description, canonical, robots, dan `og:type` memakai `$seoPage`.
- `og:image` dan `twitter:image` memakai URL absolut.
- Hapus `request()->getRequestUri()`.
- Pertahankan semua `data-inertia` yang sudah ada.
- Tambahkan JSON-LD `Organization` dan `WebSite` dengan `JSON_HEX_TAG`.

Contoh:

```php
<title>{{ \App\Support\SeoTitle::format($seoPage['title'] ?? $seoTitle ?? config('app.name'), $seoSiteName) }}</title>

@if($seoPage['description'] ?? $seoDescription)
    <meta data-inertia="description" name="description" content="{{ $seoPage['description'] ?? $seoDescription }}">
@endif

<link data-inertia="canonical" rel="canonical" href="{{ $seoPage['canonical'] ?? url()->current() }}">
```

### 6.10 Query dan cache

- `page_seo` dibaca satu kali per request.
- Tidak ada cache baru.
- Jika nanti di-cache, invalidasi dilakukan saat `page_seo` disimpan.
- Sitemap dinamis tanpa cache; cache dapat ditambahkan bila volume URL meningkat.

---

## 7. Kontrak Frontend

### 7.1 `resources/js/components/seo.tsx`

Perubahan:

- Membaca `seo.page` dari shared props.
- Semua prop menjadi opsional dan menimpa nilai server hanya bila diberikan.
- `canonical` selalu absolut.
- `og:url` memakai canonical yang sama.
- Tidak memakai `page.url` atau `window.location`.
- Tidak merender description kosong.
- Semua `head-key` yang ada dipertahankan.

Bentuk props:

```ts
type SeoProps = {
    title?: string;
    description?: string | null;
    canonical?: string | null;
    ogImage?: string | null;
    ogType?: 'website' | 'article';
    noIndex?: boolean;
};
```

### 7.2 `resources/js/app.tsx`

- Hapus `VITE_APP_NAME` sebagai sumber nama situs.
- Title callback memakai nama dari `page.props.seo.siteName` dan fallback `page.props.name`.
- Format `Judul | Nama Paroki`.

```ts
title: (title, page) =>
    formatSeoTitle(
        title,
        page.props.seo?.siteName ?? page.props.name ?? null,
    ),
```

### 7.3 Halaman publik

Hapus `<Head title=...>` dari semua halaman publik yang memakai `<Seo>`:

- `beranda/pages/index.tsx`
- `berita/pages/index.tsx`, `berita/pages/show.tsx`
- `agenda/pages/index.tsx`, `agenda/pages/show.tsx`
- `profil/pages/index.tsx`, `sejarah.tsx`, `wilayah.tsx`, `pastor.tsx`, `struktur.tsx`
- `profil/pages/area/index.tsx`, `profil/pages/area/show.tsx`
- `jadwal-misa/pages/index.tsx`
- `pelayanan/pages/index.tsx`, `pelayanan/components/sections/hero-section.tsx`
- `komunitas/pages/index.tsx`, `komunitas/pages/show.tsx`
- `galeri/pages/index.tsx`, `galeri/pages/show.tsx`
- `dokumen/pages/index.tsx`
- `kontak/pages/index.tsx`
- `livestreaming/pages/index.tsx`
- `legal/components/legal-page.tsx`

Ganti `<Seo title=... description=... />` menjadi `<Seo />` agar nilai berasal dari resolver dan `page_seo`. Admin preview tetap memakai `<Seo noIndex />`.

### 7.4 Hapus canonical dari browser

- `berita/pages/show.tsx`: hapus `window.location.href` untuk canonical.
- `agenda/pages/show.tsx`: hapus `window.location.href` untuk canonical.
- `ShareActions` boleh tetap memakai URL saat ini karena untuk berbagi, bukan canonical.
- `EventJsonLd` menerima canonical dari `seo.page.canonical`.

### 7.5 Tipe

Perbarui `resources/js/types/index.ts`:

```ts
export type SeoPage = {
    title: string;
    description: string | null;
    canonical: string;
    ogImage: string | null;
    ogType: 'website' | 'article';
    robots: string;
};

export type SeoDefaults = {
    appUrl: string;
    siteName: string | null;
    title: string | null;
    description: string | null;
    ogImage: string | null;
    page: SeoPage;
};
```

---

## 8. Sitemap dan robots.txt

### 8.1 Route

Tambahkan di bagian publik `routes/web.php`:

```php
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');
```

Keduanya sudah dikecualikan dari redirect installer oleh `RedirectToInstaller` (`sitemap*`, `robots.txt`).

### 8.2 `SitemapController`

Sumber URL:

| Sumber    | Scope                        |
| --------- | ---------------------------- |
| Statis    | `config('seo.static_pages')` |
| Berita    | `Post::published()`          |
| Agenda    | `Event::published()`         |
| Pelayanan | `Service::active()`          |
| Komunitas | `Community::published()`     |
| Area      | `Area::active()`             |
| Galeri    | `GalleryAlbum::published()`  |

Aturan:

- URL absolut dari `route()`.
- `lastmod` hanya dari `updated_at` yang bermakna.
- Agenda lewat tetap dimasukkan.
- Tidak memasukkan draft, scheduled, archived, soft-deleted, unpublished, atau inactive.
- Tidak memasukkan `/admin`, `/install`, preview, `.ics`, download dokumen, URL dengan query, atau URL redirect.
- Tidak memakai `priority` dan `changefreq`.
- Respons `application/xml; charset=UTF-8`.
- Tidak memerlukan autentikasi.
- Tidak melakukan crawling route.

View: `resources/views/seo/sitemap.blade.php`.

Bentuk XML:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ $url['loc'] }}</loc>
        @if ($url['lastmod'] !== null)
            <lastmod>{{ $url['lastmod'] }}</lastmod>
        @endif
    </url>
</urlset>
```

### 8.3 `RobotsController`

Respons `text/plain`:

```text
User-agent: *
Allow: /
Disallow: /admin
Disallow: /install
Sitemap: {APP_URL}/sitemap.xml
```

- `APP_URL` dibaca dari config.
- Tidak menebak domain produksi.
- `public/robots.txt` dihapus agar route dipakai.
- Deployment harus memastikan file lama tidak tersisa di server.

---

## 9. Admin SEO Halaman Statis

### 9.1 Permission

Tambahkan ke `database/seeders/PermissionSeeder.php`:

```php
'seo.view',
'seo.update',
```

Tambahkan keduanya ke shared `abilities` di `HandleInertiaRequests`.

### 9.2 Route admin

Di grup admin, dekat route pengaturan:

```php
Route::get('/pengaturan/seo', [PageSeoController::class, 'edit'])
    ->name('seo.edit');
Route::put('/pengaturan/seo', [PageSeoController::class, 'update'])
    ->name('seo.update');
```

### 9.3 Controller dan Form Request

File:

- `app/Http/Controllers/Seo/PageSeoController.php`
- `app/Http/Requests/Seo/UpdatePageSeoRequest.php`

Aturan:

- `edit`: `Gate::authorize('seo.view')`.
- `update`: request authorize `seo.update`.
- Payload `pages` divalidasi terhadap allowlist `config('seo.static_pages')`.
- `page_key` tidak pernah diterima dari browser.
- `meta_title`: `nullable|string|max:255`.
- `meta_description`: `nullable|string|max:500`.
- `og_media_id`: media `ready` yang benar-benar ada.
- Baris yang semua field-nya kosong dihapus.
- Simpan dalam satu transaksi.
- Audit setiap perubahan melalui `AuditLogger::recordModel('page_seo', 'updated', ...)`.
- Tambahkan `page_seo` ke `config/audit.php`:
  - `modules`
  - `fields`: `page_key`, `meta_title`, `meta_description`, `og_media_id`
  - label modul di `lang/id/audit.php`

### 9.4 UI admin

Halaman baru: `resources/js/features/admin/pengaturan/pages/seo.tsx`.

Isi:

- `ListHeader` “SEO Halaman Statis”.
- Daftar semua halaman allowlist.
- Setiap baris:
  - label halaman,
  - tautan “Buka halaman”,
  - input `meta_title`,
  - textarea `meta_description`,
  - pemilih media OG memakai `MediaUploadField`.
- Satu tombol “Simpan SEO halaman”.
- `useForm` mengirim `pages` ke `seo.update`.
- Error validasi tampil per halaman.

Navigasi:

- Tambahkan child “SEO Halaman” di bawah “Pengaturan Situs” pada `app-sidebar-nav.ts`.
- Gunakan ability `seo.view`.

---

## 10. Structured Data JSON-LD

### 10.1 `Organization` dan `WebSite`

Dirender Blade, sitewide, tanpa `data-inertia`.

`Organization`:

- `@type`
- `name`
- `url`
- `logo` jika ada
- `sameAs` dari sosial yang valid
- `address`, `telephone`, `email` hanya jika tersedia

`WebSite`:

- `@type`
- `name`
- `url`
- `inLanguage: id-ID`

Tidak menambah `potentialAction` karena tidak ada halaman pencarian khusus. JSON-LD memakai `json_encode` dengan `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG`.

### 10.2 `Article`

Komponen baru: `resources/js/features/public/berita/components/article-json-ld.tsx`.

Data dari `PublicPostDetail`:

- `headline`
- `description`
- `image` absolut
- `datePublished`
- `dateModified` bila tersedia
- `author` (`Person`, `displayAuthorName`)
- `publisher` (`Organization`, nama paroki + logo)
- `mainEntityOfPage` canonical
- `inLanguage: id-ID`

Gunakan `head-key="article-json-ld"`.

### 10.3 `Event`

Perbaiki `event-json-ld.tsx`:

- Ganti `data-head-key` menjadi `head-key`.
- `url` memakai canonical absolut.
- `image` absolut.
- Pertahankan `eventStatus`, `startDate`, `endDate`, `location`, `about`.
- Jangan mengarang lokasi atau tanggal.
- Tetap memakai `serializeJsonLd()`.

### 10.4 `BreadcrumbList`

Komponen baru: `resources/js/components/breadcrumb-json-ld.tsx`.

Dipakai di halaman yang sudah menampilkan `Breadcrumbs`:

- detail berita
- detail agenda
- detail galeri
- detail komunitas
- detail pelayanan
- detail area

Item memakai `href.url` dari Wayfinder, atau canonical untuk item terakhir. URL dibuat absolut dengan `seo.appUrl`.

### 10.5 Serializer

Pindahkan `serializeJsonLd()` ke `resources/js/lib/json-ld.ts`.

Aturan tetap: escape `<` menjadi `\u003c` agar nilai tidak dapat menutup `<script>`.

Tidak menerima JSON-LD mentah dari admin.

---

## 11. Keamanan, Audit, dan Cache

### 11.1 Keamanan

- `seo.view` dan `seo.update` melindungi halaman admin.
- `page_key` hanya dari allowlist server.
- `og_media_id` harus media `ready`.
- Input `meta_title`/`meta_description` dibatasi panjangnya.
- JSON-LD tidak menerima input mentah dari admin.
- JSON-LD selalu di-escape sebelum masuk `<script>`.
- Sitemap tidak memuat URL privat.
- `robots.txt` tidak memblokir URL yang perlu dibaca crawler untuk melihat `noindex`.
- Admin dan preview tetap dilindungi autentikasi; `noindex` bukan pengganti.
- Token verifikasi Search Console tidak disimpan di source code.

### 11.2 Audit

Modul baru `page_seo` di `config/audit.php`:

```php
'modules' => [
    // ...
    'page_seo',
],

'fields' => [
    // ...
    'page_seo' => ['page_key', 'meta_title', 'meta_description', 'og_media_id'],
],
```

Label `audit.modules.page_seo` ditambahkan di `lang/id/audit.php`.

Perubahan halaman statis dicatat sebagai `updated` dengan snapshot before/after. Perubahan pengaturan global tetap memakai `AuditLogger::settingsUpdated()` yang sudah ada.

### 11.3 Cache

- `SiteSettingsService` sudah menginvalidasi cache saat pengaturan disimpan.
- `page_seo` tidak di-cache pada fase ini.
- Sitemap dinamis tanpa cache.
- Jika cache ditambahkan nanti:
  - `page_seo`: forget saat simpan.
  - sitemap: forget saat berita, agenda, pelayanan, komunitas, area, galeri, atau `page_seo` berubah.

---

## 12. Task Breakdown

### Fase 0 — Persiapan

| ID     | Task                                        | Output                        |
| ------ | ------------------------------------------- | ----------------------------- |
| SEO-00 | Jalankan baseline `composer ci:check`       | Kondisi awal terverifikasi    |
| SEO-01 | Catat keputusan baru di `docs/DECISIONS.md` | Keputusan arsitektur tercatat |

### Fase 1 — Fondasi metadata

| ID     | Task                                                           | Output                                |
| ------ | -------------------------------------------------------------- | ------------------------------------- |
| SEO-02 | Tambahkan `config/seo.php` dengan allowlist 18 halaman         | Konfigurasi halaman statis            |
| SEO-03 | Tambahkan `SeoTitle`                                           | Formatter judul konsisten             |
| SEO-04 | Tambahkan `CanonicalUrlResolver`                               | Canonical absolut + aturan pagination |
| SEO-05 | Tambahkan `SeoMetadataResolver`                                | Metadata per route                    |
| SEO-06 | Perluas shared prop `seo` dan `abilities`                      | Data tersedia untuk Blade dan React   |
| SEO-07 | Ubah `app.blade.php`                                           | HTML awal memuat metadata halaman     |
| SEO-08 | Ubah `seo.tsx`, `app.tsx`, dan tipe                            | React memakai nilai server            |
| SEO-09 | Hapus `<Head title>` dan canonical browser dari halaman publik | Tidak ada sumber ganda                |
| SEO-10 | Tambahkan test metadata dan canonical                          | Regresi terlindungi                   |

### Fase 2 — Sitemap dan robots

| ID     | Task                                           | Output                |
| ------ | ---------------------------------------------- | --------------------- |
| SEO-11 | Tambahkan `SitemapController` dan view sitemap | `/sitemap.xml` valid  |
| SEO-12 | Tambahkan `RobotsController`                   | `/robots.txt` dinamis |
| SEO-13 | Hapus `public/robots.txt`                      | Route dipakai         |
| SEO-14 | Tambahkan test XML, isi sitemap, dan robots    | Regresi terlindungi   |

### Fase 3 — SEO halaman statis

| ID     | Task                                                | Output                            |
| ------ | --------------------------------------------------- | --------------------------------- |
| SEO-15 | Buat migration dan model `PageSeo`                  | Persistence siap                  |
| SEO-16 | Tambahkan permission `seo.view` dan `seo.update`    | Otorisasi siap                    |
| SEO-17 | Tambahkan controller, form request, dan route admin | Endpoint siap                     |
| SEO-18 | Tambahkan modul audit `page_seo`                    | Perubahan tercatat                |
| SEO-19 | Buat UI admin dan navigasi                          | Editor dapat mengelola SEO statis |
| SEO-20 | Tambahkan test CRUD, otorisasi, validasi, dan audit | Regresi terlindungi               |

### Fase 4 — JSON-LD

| ID     | Task                                             | Output                       |
| ------ | ------------------------------------------------ | ---------------------------- |
| SEO-21 | Tambahkan `Organization` dan `WebSite` di Blade  | Schema sitewide di HTML awal |
| SEO-22 | Tambahkan `Article` JSON-LD                      | Schema artikel               |
| SEO-23 | Perbaiki `Event` JSON-LD                         | `head-key` dan URL absolut   |
| SEO-24 | Tambahkan `BreadcrumbList` JSON-LD               | Schema breadcrumb            |
| SEO-25 | Pindahkan serializer ke `lib/json-ld.ts`         | Satu implementasi escape     |
| SEO-26 | Tambahkan test JS dan feature test schema server | Regresi terlindungi          |

### Fase 5 — Verifikasi

| ID     | Task                                                | Output                        |
| ------ | --------------------------------------------------- | ----------------------------- |
| SEO-27 | Jalankan `composer ci:check`                        | Gate penuh lulus              |
| SEO-28 | Periksa HTML awal beberapa halaman tanpa JavaScript | Metadata server terverifikasi |
| SEO-29 | Validasi sitemap dengan parser XML                  | XML valid                     |
| SEO-30 | Verifikasi Search Console setelah deploy            | Operasional selesai           |

---

## 13. File yang Akan Terpengaruh

### 13.1 File baru

```text
config/seo.php
app/Support/Seo/SeoMetadataResolver.php
app/Support/Seo/CanonicalUrlResolver.php
app/Support/SeoTitle.php
app/Models/PageSeo.php
database/migrations/2026_11_03_000000_create_page_seo_table.php
app/Http/Controllers/Seo/PageSeoController.php
app/Http/Requests/Seo/UpdatePageSeoRequest.php
app/Http/Controllers/Public/SitemapController.php
app/Http/Controllers/Public/RobotsController.php
resources/views/seo/sitemap.blade.php
resources/js/features/admin/pengaturan/pages/seo.tsx
resources/js/lib/seo-title.ts
resources/js/lib/json-ld.ts
resources/js/features/public/berita/components/article-json-ld.tsx
resources/js/components/breadcrumb-json-ld.tsx
tests/Feature/Seo/SeoMetadataTest.php
tests/Feature/Seo/PageSeoTest.php
tests/Feature/Seo/SitemapTest.php
tests/Feature/Seo/RobotsTest.php
tests/Feature/Seo/OrganizationJsonLdTest.php
tests/js/lib/seo-title.test.ts
tests/js/lib/json-ld.test.ts
tests/js/features/public/agenda/event-json-ld.test.tsx
tests/js/features/public/berita/article-json-ld.test.tsx
```

### 13.2 File diubah

```text
routes/web.php
app/Http/Middleware/HandleInertiaRequests.php
resources/views/app.blade.php
resources/js/components/seo.tsx
resources/js/app.tsx
resources/js/types/index.ts
resources/js/layouts/app/app-sidebar-nav.ts
database/seeders/PermissionSeeder.php
config/audit.php
lang/id/audit.php
resources/js/features/public/agenda/components/event-json-ld.tsx
docs/DECISIONS.md
```

Halaman publik yang diubah:

```text
resources/js/features/public/beranda/pages/index.tsx
resources/js/features/public/berita/pages/index.tsx
resources/js/features/public/berita/pages/show.tsx
resources/js/features/public/agenda/pages/index.tsx
resources/js/features/public/agenda/pages/show.tsx
resources/js/features/public/profil/pages/index.tsx
resources/js/features/public/profil/pages/sejarah.tsx
resources/js/features/public/profil/pages/wilayah.tsx
resources/js/features/public/profil/pages/pastor.tsx
resources/js/features/public/profil/pages/struktur.tsx
resources/js/features/public/profil/pages/area/index.tsx
resources/js/features/public/profil/pages/area/show.tsx
resources/js/features/public/jadwal-misa/pages/index.tsx
resources/js/features/public/pelayanan/pages/index.tsx
resources/js/features/public/pelayanan/components/sections/hero-section.tsx
resources/js/features/public/komunitas/pages/index.tsx
resources/js/features/public/komunitas/pages/show.tsx
resources/js/features/public/galeri/pages/index.tsx
resources/js/features/public/galeri/pages/show.tsx
resources/js/features/public/dokumen/pages/index.tsx
resources/js/features/public/kontak/pages/index.tsx
resources/js/features/public/livestreaming/pages/index.tsx
resources/js/features/public/legal/components/legal-page.tsx
```

### 13.3 File dihapus

```text
public/robots.txt
```

---

## 14. Test Matrix

### 14.1 Feature test PHP

| File                         | Kasus                                                                                                                                                                                                                  |
| ---------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `SeoMetadataTest.php`        | HTML awal berita/agenda; fallback statis; canonical filter; pagination; `q` noindex; tag tidak ganda; OG absolut; preview/admin noindex                                                                                |
| `PageSeoTest.php`            | Izin `seo.view`/`seo.update`; simpan title/description/OG; page_key invalid; panjang invalid; media belum ready; audit; baris kosong dihapus                                                                           |
| `SitemapTest.php`            | XML valid; halaman statis; berita published; agenda published termasuk lewat; pelayanan/komunitas/area/galeri; exclusion draft/scheduled/archived/unpublished/inactive; URL absolut; tidak ada admin/preview/ics/query |
| `RobotsTest.php`             | 200 `text/plain`; `Sitemap:` memakai `APP_URL`; `Disallow` admin/install; tidak dialihkan installer                                                                                                                    |
| `OrganizationJsonLdTest.php` | `Organization` dan `WebSite` ada; properti kosong tidak dirender; escape `</script>`                                                                                                                                   |

### 14.2 Unit/JS test

| File                       | Kasus                                                               |
| -------------------------- | ------------------------------------------------------------------- |
| `seo-title.test.ts`        | Format title; fallback tanpa site name; title sama dengan site name |
| `json-ld.test.ts`          | Escape `<`; JSON valid                                              |
| `event-json-ld.test.tsx`   | `head-key`; URL absolut; status event; properti opsional            |
| `article-json-ld.test.tsx` | Field artikel; author; publisher; canonical                         |

### 14.3 Regression yang harus lulus

- `tests/Unit/ArchitectureTest.php` — sinkronisasi `head-key` dan `data-inertia`.
- `tests/Feature/Foundation/InfrastructureFoundationTest.php` — baseline head tags.
- `tests/Feature/Public/PublicRoutesTest.php` — tag OG/Twitter di HTML awal.
- `tests/Feature/Foundation/RbacFoundationTest.php` — permission baru mengikuti pola nama.
- `composer ci:check` penuh.

---

## 15. Verification Plan

1. Jalankan baseline `composer ci:check` sebelum perubahan.
2. Setelah Fase 1, jalankan test SEO dan periksa HTML awal dengan `curl` tanpa JavaScript:

   ```bash
   curl -s http://localhost:8000/berita/contoh-artikel | grep -E '<title>|canonical|og:url|robots|description'
   ```

3. Pastikan hanya ada satu title, description, canonical, robots, `og:title`, `og:url`.
4. Pastikan canonical absolut dan tidak memuat `?q=`.
5. Setelah Fase 2, validasi sitemap:

   ```bash
   curl -s http://localhost:8000/sitemap.xml | php -r '$x = simplexml_load_string(stream_get_contents(STDIN)); var_dump($x !== false);'
   ```

6. Periksa `robots.txt` dan pastikan `Sitemap:` memakai `APP_URL` lokal, bukan domain produksi.
7. Setelah Fase 3, uji admin tanpa izin dan dengan izin.
8. Setelah Fase 4, validasi JSON-LD dengan Google Rich Results Test setelah deploy.
9. Setelah deploy:
   - verifikasi domain di Google Search Console,
   - submit `/sitemap.xml`,
   - periksa laporan indexing,
   - pastikan `APP_URL` produksi `https` dan redirect HTTP → HTTPS aktif.

---

## 16. Risiko dan Mitigasi

| Risiko                                                          | Dampak                             | Mitigasi                                                                      |
| --------------------------------------------------------------- | ---------------------------------- | ----------------------------------------------------------------------------- |
| Menghapus `<Head title>` di banyak file melewatkan satu halaman | Dua sumber judul kembali muncul    | Test menghitung satu `<title>`; grep sisa `<Head title>` di folder publik     |
| Aturan title ada di PHP dan TypeScript                          | Format bisa berbeda                | Test unit di kedua sisi dengan contoh yang sama                               |
| JSON-LD artikel/event/breadcrumb dirender React                 | Schema tidak ada di HTML awal      | `Organization`/`WebSite` sudah di HTML awal; perluas resolver bila diperlukan |
| Sitemap dinamis menambah query                                  | Response lebih lambat              | Volume URL kecil; tambahkan cache nanti                                       |
| `page_seo` menambah query per halaman statis                    | Sedikit overhead                   | Satu lookup per request                                                       |
| `public/robots.txt` masih ada di server produksi                | Route tidak dipakai                | Hapus dari repo dan deployment                                                |
| Permission `seo.*` belum diberikan ke role selain super_admin   | Role lain tidak bisa mengelola SEO | Super_admin memakai `Gate::before`; petakan role saat dibuat                  |
| `seo_default_og_image` lama tidak valid                         | Gambar OG tidak tampil             | Validasi saat simpan; resolver menangani path dan URL http(s)                 |
| Nama situs belum diisi                                          | Suffix judul tidak ada             | Fallback ke `parish_name`, lalu `config('app.name')`                          |

---

## 17. Acceptance Criteria

- [ ] HTML awal berita memuat title dan description berita.
- [ ] HTML awal agenda memuat title dan description agenda.
- [ ] Halaman statis memakai `page_seo`, lalu config, lalu fallback global.
- [ ] Canonical absolut dan memakai `APP_URL`.
- [ ] Query `q`, `kategori`, `bulan`, `mode`, dan `sort` tidak masuk canonical.
- [ ] `?page=N` (N>1) self-canonical.
- [ ] `?q=` mendapat `noindex`.
- [ ] `og:url` sama dengan canonical.
- [ ] Gambar Open Graph memakai URL absolut.
- [ ] Tidak ada title/description/canonical/robots/OG ganda.
- [ ] Preview, admin, dan installer memakai `noindex` di HTML awal.
- [ ] `/sitemap.xml` mengembalikan XML valid.
- [ ] Sitemap memuat halaman statis, berita, agenda, pelayanan, komunitas, area, dan galeri yang publik.
- [ ] Sitemap tidak memuat draft, scheduled, archived, unpublished, inactive, admin, preview, `.ics`, atau URL dengan query.
- [ ] `/robots.txt` menunjuk sitemap dari `APP_URL`.
- [ ] `Organization`, `WebSite`, `Article`, `Event`, dan `BreadcrumbList` memakai data nyata.
- [ ] JSON-LD tidak dapat menutup `<script>`.
- [ ] Admin tanpa `seo.view`/`seo.update` tidak dapat mengubah SEO.
- [ ] Perubahan SEO tercatat di audit log.
- [ ] `composer ci:check` lulus.

---

## 18. Yang Sengaja Tidak Dikerjakan

- Verifikasi Google Search Console otomatis.
- Editor `robots.txt` bebas melalui admin.
- Editor JSON-LD mentah untuk admin.
- Pengaktifan Inertia SSR.
- Sitemap untuk URL download dokumen dan `.ics`.
- Halaman keyword otomatis atau konten SEO tipis.
- Kolom SEO baru pada tabel `events`.
- Kontrol `index`/`noindex` per halaman melalui UI.
- Cache sitemap atau cache `page_seo` pada fase ini.

---

## 19. Catatan Operasional

1. `APP_URL` produksi harus berupa domain kanonis dengan HTTPS.
2. Redirect HTTP → HTTPS dan domain alternatif → domain kanonis dilakukan di web server/reverse proxy.
3. Setelah deploy, pastikan `public/robots.txt` lama tidak tersedia.
4. Search Console diverifikasi setelah domain produksi tersedia.
5. Sitemap disubmit setelah verifikasi.
6. Indexing tidak dijamin hanya karena sitemap tersedia; pantau laporan Search Console.
7. Jika nanti `page_seo` di-cache, invalidasi wajib dilakukan saat simpan.
8. Jika nanti sitemap di-cache, invalidasi wajib dilakukan saat konten publik berubah.

---

## 20. Status

| Atribut            | Nilai                                                     |
| ------------------ | --------------------------------------------------------- |
| Status dokumen     | Final — siap dieksekusi                                   |
| Perubahan kode     | Belum ada                                                 |
| Dependency baru    | Tidak ada                                                 |
| Menunggu keputusan | Tidak ada                                                 |
| Langkah berikutnya | Mulai Fase 0, lalu Fase 1 dengan test setelah setiap fase |
