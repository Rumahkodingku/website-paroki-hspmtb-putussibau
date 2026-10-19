# Beranda: Ilustrasi Fallback + Perbaikan UI Gambar

Written against: branch `feat/phase-9a-homepage`, commit dasar `main` @ `fe98acc`
(Phase 9A belum di-commit; perubahan ada di working tree).

Plan ini adalah satu paket perubahan untuk surface Beranda publik `/`:
lapisan ilustrasi fallback untuk slot gambar kosong, plus tiga perbaikan
konformansi UI yang sudah ditemukan pada audit sebelumnya.

## Evidence chain

- **Surface:** route `/` → `App\Http\Controllers\Home\PublicHomeController` →
  `resources/js/features/public/beranda/pages/index.tsx` dan komponen di
  `resources/js/features/public/beranda/components/`. Komponen bersama yang ikut
  terpengaruh: `resources/js/components/article-card.tsx`,
  `resources/js/components/parish-image.tsx`, `resources/js/components/ui/button.tsx`
  (tidak diubah).
- **Problem:**
  1. Slot gambar berita, pelayanan, dan galeri tampil kosong saat paroki belum
     mengunggah media, sehingga Beranda terasa tidak hidup di lokal/demo.
  2. Heading hero tidak pernah turun ke langkah kecil terdokumentasi di ponsel.
  3. Gambar galeri tidak mengirim `srcset`/`sizes`.
  4. Link "Baca renungan" memakai emas, bukan warna link on-dark yang
     terdokumentasi.
- **Design evidence:**
  - `DESIGN.md` → Photography Geometry: "Use responsive `srcset`, `sizes` …";
    Image Behavior: "Gallery imagery uses responsive sizes and lazy loading",
    "Hero photography uses responsive `srcset` …".
  - `DESIGN.md` → Responsive Behavior: "Hero typography: 56px → 40px → 34px → 28px";
    tabel: Phone 420–640px hero type 34px; Small phone ≤419px compact hero.
  - `DESIGN.md` → Buttons: "`text-link-on-dark` — Light red/pink or white link on
    navy surfaces"; Gold hanya untuk "small highlights, icons, decorative rules,
    badges, and selected emphasis".
  - `DESIGN.md` → Do's and Don'ts: "Do not use generic stock photography when
    approved parish photography is available"; "Do not invent parish history …".
  - `AGENTS.md`: jangan mengarang data/konten paroki; placeholder harus jelas.
  - `PRD.md` XC-M2/M3/M4/M5: MIME/server-side, EXIF dibuang, `srcset`/`sizes`,
    alt gambar kunci.
  - `docs/DECISIONS.md` D-70: hero memakai aset resmi
    `public/hero-section.webp` / `.png`.
  - `docs/DECISIONS.md` D-24: pipeline media resmi.
  - `docs/DECISIONS.md` D-53 / `config/demo.php`: `DEMO_SEED` mematikan demo
    di E2E; flag ilustrasi wajib terpisah.
- **Owner:** `config/homepage.php` (satu sumber fallback homepage),
  `App\Support\HomepageIllustration` (pemilih deterministik),
  `PublicHomeController` (perakit props).
- **Scope and affected surfaces:** Beranda `/` saja. `/berita`, `/pelayanan`,
  `/galeri` tetap memakai resource publik apa adanya.
- **Uncertainty:** pilihan akhir foto Unsplash dan fotografernya, serta subjek
  gambar. Harus tidak menyamar sebagai foto Paroki HSPMTB. Tidak boleh ada
  bangunan gereja yang bisa disalahartikan sebagai gereja paroki.

## Keputusan pengguna (mengikat)

1. Ilustrasi internet hanya untuk **lokal/demo**; flag default `false`.
2. Slot ilustrasi: **berita + pelayanan + galeri**.
3. Sumber: **WebP lokal yang di-commit** di `public/illustrations/`; bukan hotlink.
4. Wajib **badge "Ilustrasi" + `alt` jujur**.
5. **Hero tetap** foto resmi `public/hero-section.webp`/`.png`.
6. Tiga temuan UI sebelumnya **dikerjakan sekalian**: heading hero responsive,
   `srcSet`/`sizes` galeri, warna link renungan.

## Design decision

Tambahkan satu lapisan ilustrasi milik Beranda yang bersifat **fallback-only**
dan **off secara default**:

- Aktif hanya bila `HOMEPAGE_ILLUSTRATIONS=true` (env).
- Tidak pernah menimpa media resmi; hanya mengisi slot yang kosong.
- Selalu berlabel "Ilustrasi" dan `alt` berawalan "Ilustrasi: ".
- Disimpan lokal sebagai WebP multi-lebar agar offline, deterministik, dan bisa
  `srcset`.
- Hero tidak disentuh.
- Galeri kosong **tidak** diisi ke `galleryPhotos`; dikirim sebagai prop terpisah
  `galleryIllustrations` dan dirender sebagai strip berlabel, tanpa tautan album.

## Reuse

- `App\Support\HomepageIllustration` (baru) — helper kecil tanpa HTTP/DB/Request;
  `for(string $seed, string $variant = 'card'): ?array{src, srcSet, alt, width, height}`;
  deterministik lewat `crc32($seed) % count`.
- `config/homepage.php` — satu file konfigurasi homepage; tambahkan blok
  `illustrations`.
- `resources/js/components/parish-image.tsx` — sudah menerima `srcSet`, `sizes`,
  `aspectRatio`, `priority`, dan fallback `onError`; jangan buat komponen gambar baru.
- `resources/js/components/ui/button.tsx` — tidak diubah.
- `config/demo.php` — pola flag env; ilustrasi memakai flag sendiri, bukan `demo.enabled`.
- `PublicPostResource`, `PublicServiceCardResource`, `PublicGalleryTeaserResource`
  tidak diubah; ilustrasi hanya dirajut di `PublicHomeController`.

## Changes

### 1. `config/homepage.php` — blok ilustrasi

Tambahkan:

```php
'illustrations' => [
    'enabled' => (bool) env('HOMEPAGE_ILLUSTRATIONS', false),
    'alt_prefix' => 'Ilustrasi: ',
    'images' => [
        'community' => [
            'alt' => 'umat berkumpul dalam kegiatan paroki',
            'width' => 1200,
            'height' => 800,
            'sources' => [
                '600' => 'illustrations/community-600.webp',
                '960' => 'illustrations/community-960.webp',
                '1200' => 'illustrations/community-1200.webp',
            ],
        ],
        // 6–8 entri: lilin, Alkitab terbuka, rosario, paduan suara,
        // tangan berdoa, alam sekitar. TIDAK memakai bangunan gereja.
    ],
],
```

Preserve: `fallback_hero_image`, `fallback_hero_og_image`,
`fallback_hero_image_alt`, `devotion_category_slug`.

Verify: `php artisan config:show homepage` menampilkan blok `illustrations`.

### 2. `public/illustrations/*.webp` — aset

- 6–8 foto Unsplash berlisensi, tiap foto 3 lebar (600, 960, 1200).
- Konversi dengan `cwebp` yang sudah tersedia di host; kualitas ~82.
- Target ukuran: tiap file < 250 KB.
- Tidak ada `storage:link`; disajikan langsung dari `public/`.
- Jangan pakai gambar yang menampilkan gereja/bangunan yang bisa dibaca sebagai
  Paroki HSPMTB.
- Kumpulkan daftar URL Unsplash + nama fotografer untuk D-71.

Verify:

```bash
file public/illustrations/*.webp
ls -lh public/illustrations/
```

### 3. `app/Support/HomepageIllustration.php` — pemilih ilustrasi

API:

```php
final class HomepageIllustration
{
    public static function enabled(): bool;

    /**
     * @return array{src: string, srcSet: string, alt: string, width: int, height: int}|null
     */
    public static function for(string $seed, string $variant = 'card'): ?array;
}
```

- `enabled()` membaca `config('homepage.illustrations.enabled')`.
- `for()` mengembalikan `null` saat flag off atau daftar kosong.
- Entri dipilih deterministik: `crc32($seed) % count($images)`.
- `srcSet` dibangun dari `sources` + `sizes`:
  - `card` (berita): `(min-width: 768px) 33vw, (min-width: 640px) 50vw, 100vw`
  - `thumb` (pelayanan): `64px`
  - `gallery`: `(min-width: 768px) 33vw, 50vw`
- Tidak ada HTTP, DB, `Request`, atau cache.

### 4. `app/Http/Controllers/Home/PublicHomeController.php`

- Setelah resolusi `news`, untuk tiap item dengan `image === null` saat flag on:
  - `image` ← `HomepageIllustration::for((string) $id, 'card')['src']`
  - `image_alt` ← alt ilustrasi
  - `image_is_illustration` ← `true`
- Setelah resolusi `services`, untuk tiap item dengan `thumbnail === null` saat
  flag on:
  - `thumbnail` ← `HomepageIllustration::for((string) $id, 'thumb')['src']`
  - `thumbnail_alt` ← alt ilustrasi
  - `thumbnail_is_illustration` ← `true`
- Galeri: bila `galleryPhotos` kosong **dan** flag on, kirim prop baru
  `galleryIllustrations` (daftar `HomeIllustration`) dengan 3–6 entri galeri.
  Jangan mengisi `galleryPhotos` dengan data palsu.
- Item yang sudah punya media resmi **tidak diubah**.
- Resource publik tidak berubah, sehingga `/berita`, `/pelayanan`, dan `/galeri`
  tetap identik.

### 5. Kontrak TypeScript

`resources/js/features/public/beranda/types.ts`:

```ts
export type HomeIllustration = {
    id: string;
    src: string;
    srcSet: string;
    alt: string;
    width: number;
    height: number;
};
```

- `HomeService` tambah `thumbnail_is_illustration?: boolean`.
- `HomepageProps` tambah `galleryIllustrations: HomeIllustration[]`.
- Berita memakai `ArticleCardData` + `image_is_illustration?: boolean`.

`resources/js/components/article-card.tsx`:

- Tambah `image_is_illustration?: boolean` pada tipe dan props.
- Saat true, render badge kecil "Ilustrasi" di atas gambar.
- Badge memakai token desain yang sudah ada (`bg-gold-light`/`text-navy-dark`
  atau `bg-canvas`/`text-muted-foreground`); pilih satu dan jangan hard-code hex.

### 6. Komponen Beranda

- `home-services-section.tsx`
  - Bila `service.thumbnail` ada (asli atau ilustrasi), render sebagai
    thumbnail persegi kecil di kiri menggantikan ikon `HeartHandshake`.
  - Bila `thumbnail_is_illustration`, tampilkan badge "Ilustrasi" kecil.
  - Pertahankan struktur `<ul>` terbagi; jangan ubah menjadi grid kartu.
- `home-gallery-section.tsx`
  - Foto asli: teruskan `srcSet` dan `sizes` pada `ParishImage`, dibangun dari
    `photo.thumb_url` (400w) dan `photo.url` (960w).
  - Bila `galleryPhotos` kosong dan `galleryIllustrations` terisi, render strip
    ilustrasi terpisah berjudul "Ilustrasi" dengan badge di tiap tile, **tanpa
    link album** dan tanpa menyamar sebagai album paroki.
  - Bila keduanya kosong, section tetap tersembunyi.
- `home-hero.tsx`
  - Ganti override heading dari `text-display-lg md:text-hero-display` menjadi
    `text-display-md sm:text-display-lg md:text-hero-display`.
  - Hero tetap memakai `hero.image` dari aset resmi.
- `home-devotion-section.tsx`
  - Link "Baca renungan": ubah `text-gold` menjadi `text-red-on-dark`.
  - Eyebrow "Renungan" dan label kategori tetap `text-gold`.

### 7. Environment dan E2E

- `.env.example` tambah:

  ```dotenv
  # Ilustrasi fallback homepage (lokal/demo saja). Biarkan false di produksi.
  HOMEPAGE_ILLUSTRATIONS=false
  ```

- `playwright.config.ts` pada `webServer.env` tambah
  `HOMEPAGE_ILLUSTRATIONS: 'false'` agar E2E tidak pernah memuat ilustrasi dan
  tetap deterministik.
- E2E yang ada tidak diubah; 105 test harus tetap lulus.

### 8. Test

`tests/Feature/Public/PublicHomepageTest.php` tambah:

- flag off → `news.0.image` null, `galleryIllustrations` kosong.
- flag on + berita tanpa gambar → `image_is_illustration` true dan `image`
  menunjuk `illustrations/`.
- flag on + berita bergambar → gambar asli menang, `image_is_illustration` absen/false.
- flag on + pelayanan tanpa thumbnail → `thumbnail_is_illustration` true.
- flag on + galeri kosong → `galleryIllustrations` terisi, `galleryPhotos` tetap `[]`.
- determinisme: seed yang sama menghasilkan entri yang sama.
- flag off di halaman lain → `/berita`, `/pelayanan`, `/galeri` tidak berubah.

`tests/js/features/public/beranda/homepage.test.tsx` tambah:

- badge "Ilustrasi" muncul saat `image_is_illustration`.
- badge tidak muncul saat gambar asli.
- strip ilustrasi galeri muncul saat `galleryIllustrations` diisi dan
  `galleryPhotos` kosong.
- pelayanan merender thumbnail ilustrasi sebagai pengganti ikon.

### 9. Dokumentasi

`docs/DECISIONS.md` D-71 (baru) mencatat:

- Sumber foto Unsplash + fotografer + URL (untuk atribusi).
- Tujuan: demo lokal, flag default off, tidak pernah menggantikan media paroki.
- Divergensi sadar dari DESIGN "hindari foto stok", dimitigasi badge + alt.
- Hero tetap foto resmi.
- Galeri ilustrasi terpisah dari album nyata.

Tidak ada perubahan pada `docs/DESIGN.md`.

## Scope

- **Inherit:** Beranda `/`, `ArticleCard`, `ParishImage`, `config/homepage.php`.
- **Verify:** `/berita`, `/pelayanan`, `/galeri`, `/`, E2E publik, pengaturan situs.
- **Exclude:** hero dari stok; album galeri palsu; Unsplash API; CSP; pipeline
  `media`; perubahan halaman publik lain; perubahan admin.

## Validation

- **Product:** di lokal dengan `HOMEPAGE_ILLUSTRATIONS=true`, berita/pelayanan/galeri
  tampil berilustrasi dan berlabel; dengan flag off, tampilan identik dengan
  sebelum perubahan.
- **Interface:** 360/390/768/1024/1440; heading hero turun ke 34px di ponsel;
  galeri memakai `srcSet`; link renungan memakai token on-dark red; badge
  "Ilustrasi" terbaca; alt jujur; tidak ada overflow horizontal.
- **System:** satu resolver; resource publik tidak berubah; tidak ada komponen
  gambar kedua; tidak ada request jaringan eksternal.
- **Repository:**
  - `php artisan test --compact tests/Feature/Public/PublicHomepageTest.php` → PASS
  - `npm run test:unit -- tests/js/features/public/beranda/homepage.test.tsx` → PASS
  - `npm run check` / `npm run types:check` → PASS
  - `vendor/bin/pint --dirty --format agent` → PASS
  - `composer types:check` → PASS
  - `npm run build` → PASS
  - `npm run e2e` → 105 PASS
  - `composer ci:check` → PASS

## Stop conditions

- Stop if ilustrasi diminta tetap tampil ketika media resmi sudah ada.
- Stop if galeri kosong diminta diisi ilustrasi tanpa label "Ilustrasi".
- Stop if hero diminta diganti foto internet.
- Stop if sumber diubah menjadi hotlink Unsplash/Picsum untuk produksi tanpa
  persetujuan risiko E2E, privasi, dan lisensi.
- Stop if `public/hero-section.*` hilang atau berubah.

## Design documentation

Setelah diterima dan divalidasi: catat di `docs/DECISIONS.md` sebagai D-71 —
"Ilustrasi Beranda: fallback demo dari aset WebP lokal berlisensi, flag default
off, selalu berlabel, tidak pernah menggantikan media paroki." Tidak ada
perubahan pada `docs/DESIGN.md`.
