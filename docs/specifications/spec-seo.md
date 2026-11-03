# SEO Specification — Website Paroki HSPMTB Putussibau

- **Status:** Proposed specification
- **Repository:** `Rumahkodingku/website-paroki-hspmtb-putussibau`
- **Stack:** Laravel 13, Inertia.js 3, React 19, TypeScript
- **Bahasa website:** Indonesia
- **Zona waktu tampilan:** `Asia/Pontianak`
- **Tujuan:** memperkuat crawling, indexing, metadata, dan pengelolaan SEO tanpa mengganti arsitektur aplikasi yang sudah ada.

> Dokumen ini disusun berdasarkan pemeriksaan codebase pada branch default repository yang tersedia saat audit. Ini adalah spesifikasi implementasi, bukan klaim bahwa semua rekomendasi sudah diterapkan.

---

## 1. Ringkasan Keputusan

Implementasikan SEO sebagai peningkatan bertahap pada sistem yang sudah ada, bukan membangun ulang seluruh mekanisme.

1. **Pertahankan** `resources/js/components/seo.tsx` sebagai komponen metadata React.
2. **Pertahankan** `App\Services\SiteSettingsService` dan konfigurasi `config/site-settings.php` sebagai sumber pengaturan global.
3. **Pertahankan** field `meta_title` dan `meta_description` pada model `Post` beserta fallback `displayMetaTitle()` dan `displayDescription()`.
4. **Gunakan Spatie Laravel Sitemap** untuk membantu menghasilkan sitemap XML, dengan pemilihan URL publik tetap dikendalikan oleh aplikasi.
5. **Perbaiki canonical URL** agar absolut, konsisten, bebas query string yang tidak relevan, dan sama antara HTML awal, React, serta sitemap.
6. **Pastikan metadata spesifik halaman tersedia pada HTML awal.** Evaluasi SSR Inertia atau pendekatan server-side metadata yang kompatibel dengan deployment; jangan mengandalkan perubahan head setelah hydration saja.
7. **Tambahkan pengaturan SEO halaman statis** di Admin Panel dengan tabel `page_seo` atau desain persistence setara yang disetujui setelah audit migrasi.
8. **Tambahkan SEO untuk agenda** dengan fallback dari data agenda yang ada.
9. **Tambahkan JSON-LD** untuk `Organization`/`WebSite`, `Article` atau `NewsArticle`, dan `BreadcrumbList` jika sesuai; pertahankan JSON-LD `Event` yang sudah ada.
10. **Validasi hasil melalui test otomatis dan Google Search Console.**

SEO tidak menjamin halaman akan diindeks atau mendapat peringkat tertentu. Tujuan implementasi ini adalah memastikan mesin pencari dapat menemukan dan memahami halaman publik dengan sinyal yang konsisten.

---

## 2. Kondisi Codebase Saat Ini

### 2.1 Komponen dan sistem yang sudah tersedia

| Area                    | Kondisi hasil audit                                                                                                                              | Implikasi                                                                                       |
| ----------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------------- |
| Komponen metadata React | `resources/js/components/seo.tsx` tersedia; mendukung title, description, canonical, OG, Twitter Card, dan `noIndex`                             | Pertahankan dan perbaiki perilakunya                                                            |
| HTML awal               | `resources/views/app.blade.php` menghasilkan head baseline server-side; komentar codebase menyatakan SSR tidak aktif                             | Baseline tersedia, tetapi metadata spesifik halaman belum dijamin ada pada respons awal         |
| Pengaturan global       | Grup `seo` di `config/site-settings.php` berisi `seo_default_title`, `seo_default_description`, `seo_default_og_image`                           | Tidak perlu membuat sistem pengaturan global duplikat                                           |
| Service pengaturan      | `App\Services\SiteSettingsService` menyediakan pembacaan, penulisan, cache, dan invalidasi                                                       | Semua akses pengaturan harus melewati service ini                                               |
| Admin pengaturan        | `SettingsController` menampilkan grup konfigurasi di `/admin/pengaturan`                                                                         | Pengaturan global SEO sudah punya tempat integrasi                                              |
| SEO berita              | `Post` mempunyai `meta_title` dan `meta_description`; `PublicPostDetailResource` mengekspos fallback SEO                                         | Pertahankan field dan fallback yang sudah ada                                                   |
| JSON-LD agenda          | `resources/js/features/public/agenda/components/event-json-ld.tsx` menghasilkan schema `Event`                                                   | Pertahankan dan tingkatkan hanya bila data relevan tersedia                                     |
| Robots                  | `public/robots.txt` saat audit berisi `User-agent: *` dan `Disallow:`                                                                            | Crawling tidak diblokir, tetapi belum mencantumkan sitemap                                      |
| Sitemap                 | Belum ditemukan implementasi sitemap pada file yang diperiksa; `spatie/laravel-sitemap` tidak tercantum di `composer.json`                       | Perlu implementasi dan dependency baru jika memilih Spatie                                      |
| SEO halaman statis      | Tidak ditemukan mekanisme persistence SEO per halaman statis pada file yang diperiksa                                                            | Perlu dirancang dan diintegrasikan                                                              |
| SEO agenda              | `Event` mempunyai judul, deskripsi, poster, tanggal, lokasi, dan status, tetapi belum terlihat field meta title/description pada resource detail | Gunakan fallback sekarang; field khusus dapat ditambahkan jika kebutuhan editor mengharuskannya |
| Schema artikel          | Belum ditemukan komponen JSON-LD artikel pada file yang diperiksa                                                                                | Perlu ditambahkan                                                                               |
| Breadcrumb visual       | Komponen `Breadcrumbs` sudah digunakan pada halaman detail berita dan agenda                                                                     | Dapat digunakan sebagai sumber data konsisten untuk `BreadcrumbList`                            |

### 2.2 Masalah teknis yang perlu diperbaiki

#### Canonical pada Blade

`resources/views/app.blade.php` saat ini membentuk `og:url` dan canonical menggunakan:

```php
rtrim(config('app.url'), '/') . request()->getRequestUri()
```

`getRequestUri()` dapat menyertakan query string. Ini berisiko menghasilkan canonical seperti `/berita?q=misa`, meskipun URL yang seharusnya menjadi canonical adalah `/berita`.

Perbaiki dengan resolver canonical terpusat yang menggunakan URL publik kanonis dan aturan per-route. Jangan mengganti secara membabi buta dengan `request()->url()` untuk semua kasus tanpa memeriksa route, locale, slug, dan kebutuhan parameter yang memang menjadi bagian URL.

#### Canonical pada React

`resources/js/components/seo.tsx` saat ini dapat menerima `canonical` dari halaman, tetapi nilai fallback dapat berupa `page.url` yang relatif. Halaman detail berita dan agenda juga menggunakan `window.location.href`, yang tidak tersedia saat render server dan dapat menyertakan query string.

Gunakan URL canonical absolut yang diberikan server/resolver. Hindari membangun canonical utama dari `window.location.href`.

#### Metadata per halaman pada HTML awal

Blade sudah mengeluarkan default title, description, Open Graph, Twitter Card, robots, dan canonical. Namun, title dan description di sana berasal dari pengaturan global; title/deskripsi spesifik berita atau agenda baru diberikan oleh komponen React pada halaman.

Target implementasi adalah metadata penting halaman tersedia sejak HTML awal. Evaluasi pendekatan server-side metadata atau Inertia SSR dengan mempertimbangkan versi, hosting, build, dan pemeliharaan. Jangan mengaktifkan SSR tanpa pengujian deployment.

---

## 3. Ruang Lingkup

### Termasuk

- SEO Settings global yang memakai sistem pengaturan yang ada.
- SEO per halaman statis.
- SEO berita dan agenda.
- Resolver metadata dan canonical URL.
- XML sitemap.
- `robots.txt`.
- JSON-LD yang sesuai dengan konten.
- Validasi, otorisasi, audit log, dan cache invalidation.
- Test otomatis serta panduan verifikasi Search Console.

### Tidak termasuk dalam fase ini

- Jaminan ranking atau indexing Google.
- Sistem SEO pihak ketiga berbayar.
- Pengelolaan `robots.txt` bebas melalui textarea admin.
- Editor JSON-LD mentah untuk admin.
- Pembuatan halaman keyword otomatis atau konten SEO tipis.
- Pengaktifan SSR tanpa audit kompatibilitas deployment.

---

## 4. Arsitektur yang Diusulkan

```text
Admin Panel
├── /admin/pengaturan
│   └── SEO global (pengaturan yang sudah ada)
├── SEO halaman statis
│   └── title, description, OG image, robots terbatas
├── Manajemen Berita
│   └── meta_title, meta_description (field existing)
└── Manajemen Agenda
    └── metadata dengan fallback dari data agenda

Public SEO Infrastructure
├── SiteSettingsService
├── Page SEO Repository / Resolver
├── Seo Metadata Resolver
├── Canonical URL Resolver
├── HTML initial-head integration
├── React Seo component
├── Structured Data components
├── Sitemap generator (Spatie)
└── public/robots.txt

Monitoring
└── Google Search Console
```

### Prinsip arsitektur

- Gunakan `SiteSettingsService` untuk semua baca/tulis pengaturan situs.
- Jangan query `site_settings` langsung dari komponen React atau controller baru.
- Jangan mengirim model Eloquent mentah ke frontend; ikuti pola resource/props yang sudah ada.
- Metadata global menjadi fallback, bukan pengganti metadata spesifik halaman.
- Data schema harus berasal dari data yang benar-benar ditampilkan di halaman.
- Sitemap, canonical, dan metadata harus menggunakan domain kanonis yang sama.

---

## 5. Spesifikasi Admin Panel

### 5.1 SEO global

Lokasi yang disarankan: grup **SEO** yang sudah tersedia pada `/admin/pengaturan`.

Field existing:

| Key                       | Fungsi                    | Keputusan                                                |
| ------------------------- | ------------------------- | -------------------------------------------------------- |
| `seo_default_title`       | Judul default             | Pertahankan                                              |
| `seo_default_description` | Deskripsi default         | Pertahankan                                              |
| `seo_default_og_image`    | Gambar Open Graph default | Pertahankan dan validasi cara penyimpanan/resolusi media |

Tambahan opsional setelah audit kebutuhan:

| Field                      | Fungsi                                      | Ketentuan                                                                                            |
| -------------------------- | ------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| `google_site_verification` | Token verifikasi HTML Search Console        | String tervalidasi; hanya role berwenang yang dapat mengubah                                         |
| `organization_same_as`     | Tautan resmi sosial/organisasi untuk schema | Lebih baik gunakan tautan sosial yang sudah disimpan pada pengaturan situs, bukan menyimpan duplikat |
| `organization_logo`        | Logo untuk schema                           | Gunakan identitas/logo yang sudah ada jika memenuhi kebutuhan; hindari field duplikat                |

Jangan menyimpan domain canonical sebagai setting admin jika domain sudah dikelola oleh `APP_URL` dan konfigurasi deployment. Gunakan `APP_URL` sebagai sumber URL dasar setelah domain produksi dipastikan.

### 5.2 SEO halaman statis

Tambahkan persistence untuk halaman statis. Rekomendasi awal: tabel `page_seo`, dengan unique key untuk setiap halaman yang diizinkan.

Kolom yang disarankan:

| Kolom              | Tipe konseptual               | Catatan                                                                                                  |
| ------------------ | ----------------------------- | -------------------------------------------------------------------------------------------------------- |
| `id`               | bigint                        | Primary key                                                                                              |
| `page_key`         | string, unique                | Kunci stabil seperti `home`, `profile`, `mass_schedule`, `services`, `communities`, `gallery`, `contact` |
| `meta_title`       | nullable string               | Judul khusus halaman                                                                                     |
| `meta_description` | nullable text                 | Deskripsi khusus halaman                                                                                 |
| `og_media_id`      | nullable foreign key          | Opsional; gunakan sistem media yang ada                                                                  |
| `robots`           | nullable enum/string terbatas | Hanya pilihan yang diizinkan; default dikelola sistem                                                    |
| timestamps         | timestamps                    | Audit perubahan dasar                                                                                    |

Ketentuan:

- Jangan izinkan admin menentukan `page_key` bebas.
- Daftar halaman yang dapat dikelola harus berupa allowlist di aplikasi.
- Canonical URL tidak disimpan bebas untuk penggunaan normal; resolver membentuknya dari route publik.
- Jika implementasi media memakai ID media, resolusikan URL melalui mekanisme `Media` yang sudah ada.
- Periksa apakah proyek memiliki pola migration, model, policy/gate, request, dan audit log yang harus diikuti sebelum membuat tabel.

### 5.3 Berita

Model `Post` sudah memiliki `meta_title` dan `meta_description`. Gunakan field existing ini.

Perilaku:

1. `meta_title` kosong → gunakan `displayMetaTitle()`/judul berita sesuai aturan model.
2. `meta_description` kosong → gunakan `displayDescription()` (fallback excerpt/konten) yang sudah tersedia.
3. Gunakan gambar berita sebagai OG image bila tersedia; jika tidak, fallback ke OG image global.
4. Hanya berita yang lolos scope publikasi yang boleh masuk sitemap dan schema artikel.
5. Draft, scheduled yang belum waktunya, archived, soft-deleted, dan preview tidak boleh dianggap halaman publik yang dapat diindeks.

Jangan menambah kolom duplikat untuk field yang sudah ada.

### 5.4 Agenda

Data agenda yang tersedia mencakup judul, slug, deskripsi, waktu mulai/selesai, lokasi, poster, dan status. Pertahankan struktur ini.

Perilaku metadata:

1. Title fallback dari judul agenda + nama situs.
2. Description fallback dari `plain_description`.
3. OG image fallback dari poster, lalu gambar OG global.
4. Canonical berasal dari route detail agenda, bukan `window.location.href`.
5. Sitemap hanya memasukkan agenda publik yang memang layak ditemukan dan diindeks. Agenda yang sudah lewat tidak otomatis harus dikeluarkan; keputusan mengikuti apakah halaman tersebut masih berguna sebagai arsip publik.
6. Jangan menambah field SEO khusus agenda kecuali kebutuhan editorial terbukti; jika ditambahkan, gunakan migration dan form request sesuai pola proyek.

---

## 6. Aturan Metadata

### 6.1 Title dan description

- Setiap halaman publik penting harus memiliki title unik dan deskripsi yang sesuai isi halaman.
- Gunakan Bahasa Indonesia yang alami dan nama “Putussibau” bila relevan secara editorial.
- Hindari keyword stuffing.
- Fallback global hanya digunakan jika metadata spesifik tidak tersedia.
- Jangan menampilkan title/description kosong.
- Jangan membuat title dan description dari query pencarian pengguna.

Contoh pola, bukan teks wajib:

- Beranda: `Gereja Katolik HSPMTB Putussibau`
- Profil: `Profil Paroki HSPMTB Putussibau`
- Jadwal misa: `Jadwal Misa Paroki HSPMTB Putussibau`
- Berita: `[Judul Berita] | HSPMTB Putussibau`
- Agenda: `[Nama Kegiatan] | HSPMTB Putussibau`

### 6.2 Open Graph dan Twitter

Pertahankan dukungan yang sudah ada di `Seo`:

- `og:title`
- `og:description`
- `og:type`
- `og:site_name`
- `og:url`
- `og:image`
- `twitter:card`
- `twitter:title`/`twitter:description`/`twitter:image` sesuai kebutuhan

Pastikan gambar sosial menggunakan URL absolut yang dapat diakses publik. Hindari URL media privat atau URL sementara.

### 6.3 Robots meta

- Halaman publik normal: `index, follow`.
- Draft/preview/private/admin: tidak boleh bocor sebagai konten publik; gunakan autentikasi dan status HTTP yang tepat. Jangan mengandalkan `noindex` sebagai pengganti proteksi akses.
- `noindex` hanya untuk halaman publik yang memang tidak dimaksudkan masuk indeks.
- Jangan mengatur `noindex` global dari UI yang mudah terklik tanpa konfirmasi/otorisasi.
- Jika crawler harus membaca `noindex`, jangan memblokir URL itu di `robots.txt`.

---

## 7. Canonical URL dan Normalisasi URL

Buat satu resolver server-side untuk menghasilkan canonical URL absolut.

### Ketentuan

1. Gunakan `APP_URL`/konfigurasi domain kanonis untuk host.
2. Pastikan scheme HTTPS di produksi.
3. Jangan memasukkan query string tracking, pencarian, pagination, sorting, atau filter ke canonical kecuali strategi SEO menyatakan URL tersebut memang halaman kanonis yang berbeda.
4. Gunakan slug dan route name yang resmi untuk halaman detail.
5. Canonical HTML awal, canonical React, `og:url`, dan URL sitemap harus cocok.
6. URL alternatif domain/HTTP diarahkan ke domain kanonis menggunakan redirect permanen pada infrastruktur yang sesuai.
7. Hindari menggunakan `window.location.href` sebagai sumber canonical.
8. Untuk daftar berita/agenda dengan parameter filter, tetapkan strategi eksplisit: umumnya canonical ke halaman indeks dasar atau URL filter yang memang sengaja ingin diindeks.

### Acceptance criteria

- URL `/berita?q=misa` tidak otomatis menghasilkan canonical `/berita?q=misa`.
- URL detail berita canonical ke slug berita tersebut.
- URL detail agenda canonical ke slug agenda tersebut.
- Tidak ada URL canonical relatif.
- Tidak ada canonical ganda atau konflik antara server dan React.

---

## 8. HTML Awal dan Inertia

`resources/views/app.blade.php` sudah mengeluarkan metadata default melalui `<x-inertia::head>`, dan komponen React `Seo` menggunakan `<Head>` dengan key yang sepadan. Pertahankan mekanisme key yang konsisten.

Namun, metadata baseline saat ini tidak menjamin nilai khusus per halaman ada di respons HTML awal.

### Persyaratan

- HTML awal halaman berita menampilkan title dan description berita yang benar.
- HTML awal halaman agenda menampilkan title dan description agenda yang benar.
- Halaman statis menggunakan Page SEO jika tersedia, lalu fallback ke pengaturan global.
- Hindari title, description, canonical, robots, atau OG tags ganda.
- Nilai yang diberikan React harus sama dengan nilai yang dirender server.

### Implementasi yang perlu dipilih

Pilih salah satu pendekatan setelah audit teknis:

1. **Server-side metadata props:** controller/resolver menyiapkan metadata halaman untuk template awal dan React memakai data yang sama; atau
2. **Inertia SSR:** jika versi dan konfigurasi deployment mendukungnya dengan stabil.

Jangan menganggap SEO selesai hanya karena tag muncul setelah hydration. Tambahkan pengujian terhadap HTML respons awal untuk memastikan perilaku sebenarnya.

---

## 9. XML Sitemap dengan Spatie

Tambahkan dependency `spatie/laravel-sitemap` jika kompatibel dengan Laravel 13 dan dependency lockfile yang digunakan. Pastikan versi package yang dipilih diverifikasi sebelum instalasi.

### Endpoint

- `/sitemap.xml`
- HTTP `200` saat sukses.
- Content-Type XML yang sesuai.
- URL dalam sitemap berupa URL absolut kanonis.
- Tidak memerlukan autentikasi.
- Tidak menampilkan URL internal/admin.

### Sumber URL

1. Halaman statis publik yang diizinkan.
2. Berita yang dipublikasikan dan layak diindeks melalui scope `Post::published()`.
3. Agenda publik melalui scope publikasi yang berlaku.
4. Halaman publik lain hanya jika berguna dan tidak menimbulkan duplikasi.

### Aturan

- Jangan melakukan crawling semua route secara membabi buta.
- Jangan memasukkan draft, preview, halaman login/admin, error page, URL privat, atau URL dengan `noindex`.
- Jangan memasukkan URL redirect atau URL non-kanonis.
- `lastmod` hanya diisi jika tersedia timestamp perubahan konten yang bermakna dan benar.
- Jika sitemap di-cache/dihasilkan ke file, invalidasi atau regenerasi saat konten publik berubah; jika dinamis, pastikan query dan response tetap efisien.
- Pastikan sitemap tidak mengandung URL staging/local.

### robots.txt

Perbarui `public/robots.txt` agar mengizinkan crawling yang memang diinginkan dan menambahkan:

```text
Sitemap: https://DOMAIN-PRODUKSI/sitemap.xml
```

Ganti `DOMAIN-PRODUKSI` dengan domain kanonis aktual. Jangan menebak domain produksi dari nama repository.

---

## 10. Structured Data JSON-LD

### Schema yang direkomendasikan

| Schema                       | Lokasi                                                    | Sumber data                                                         |
| ---------------------------- | --------------------------------------------------------- | ------------------------------------------------------------------- |
| `Organization`               | Halaman utama atau seluruh halaman publik jika dibutuhkan | Identitas paroki, URL, logo, alamat/kontak yang sudah dikonfigurasi |
| `WebSite`                    | Website utama                                             | Nama situs dan URL kanonis                                          |
| `Article` atau `NewsArticle` | Detail berita                                             | `Post`, penulis, tanggal publikasi, gambar, title, deskripsi        |
| `Event`                      | Detail agenda                                             | Schema `Event` yang sudah ada                                       |
| `BreadcrumbList`             | Halaman bertingkat                                        | Data breadcrumb halaman yang sama dengan UI                         |

### Aturan keamanan dan akurasi

- Pertahankan `serializeJsonLd()` yang meng-escape `<` sebelum JSON-LD dimasukkan ke script.
- Jangan menerima JSON-LD mentah dari admin.
- Jangan mengarang alamat, penulis, tanggal, gambar, atau properti yang tidak tersedia.
- `Event` harus mencerminkan tanggal, status, lokasi, dan informasi yang terlihat di halaman.
- Jangan mengubah jadwal misa berulang menjadi `Event` berulang tanpa model data dan kebutuhan yang jelas.
- Schema tidak menjamin rich results.
- Uji hasil melalui validator dan Google Rich Results Test ketika tipe schema relevan didukung.

---

## 11. Performa, konten, dan SEO lokal

### Performa

- Audit Core Web Vitals untuk halaman publik utama.
- Optimalkan gambar hero, poster agenda, dan gambar berita; gunakan varian media yang sesuai.
- Pastikan gambar penting mempunyai dimensi/rasio yang stabil untuk mengurangi layout shift.
- Hindari menambah package SEO besar yang tidak diperlukan.
- Jangan mengaktifkan SSR tanpa mengukur dampak operasional dan performanya.

### Konten dan internal links

- Pastikan informasi alamat, jadwal misa, pelayanan sakramen, kontak, dan pengumuman akurat serta mudah ditemukan.
- Hubungkan berita terkait, agenda, profil, jadwal misa, dan pelayanan menggunakan link HTML yang dapat dirayapi.
- Gunakan heading semantik dan satu heading utama yang jelas per halaman.
- Gunakan alt text deskriptif untuk gambar informatif; gunakan alt kosong hanya untuk gambar dekoratif.
- Jangan membuat konten hanya untuk menargetkan keyword tanpa manfaat bagi umat.

---

## 12. Keamanan, Akses, dan Audit

- Ikuti gate/policy dan pola otorisasi yang sudah digunakan Admin Panel.
- Perubahan pengaturan SEO harus melewati `UpdateSettingsRequest` atau Form Request khusus yang sesuai.
- Validasi URL gambar dan media melalui sistem media yang ada.
- Gunakan audit logger yang sudah ada untuk perubahan pengaturan sensitif.
- Jangan menyimpan token rahasia atau kredensial Search Console di source code.
- Verification token bukan password, tetapi tetap hanya boleh diubah oleh role yang berwenang.
- Pastikan halaman admin dan konten privat tidak menjadi publik melalui sitemap atau JSON-LD.
- Periksa cache invalidation `SiteSettingsService` setelah perubahan pengaturan.

---

## 13. Pengujian dan Acceptance Criteria

### Pengaturan admin

- [ ] SEO global dapat dibaca dan disimpan melalui sistem pengaturan yang ada.
- [ ] Perubahan pengaturan langsung tercermin setelah cache diinvalidasi.
- [ ] Halaman statis dapat menyimpan dan membaca SEO berdasarkan `page_key`.
- [ ] Admin tanpa izin tidak dapat mengubah SEO.
- [ ] Validasi menolak input yang tidak sesuai.
- [ ] Metadata berita memakai field existing, tanpa kolom duplikat.

### Metadata dan canonical

- [ ] Setiap halaman publik penting memiliki title dan description yang sesuai.
- [ ] HTML respons awal memiliki metadata yang sesuai dengan halaman yang diminta.
- [ ] Tidak ada duplicate title/description/canonical/robots/Open Graph tags.
- [ ] Canonical absolut dan memakai domain kanonis.
- [ ] Query string yang tidak relevan tidak masuk canonical.
- [ ] `og:url` sama dengan canonical.
- [ ] Gambar Open Graph menggunakan URL absolut yang bisa diakses publik.
- [ ] Fallback bekerja saat metadata spesifik kosong.

### Sitemap dan robots

- [ ] `/sitemap.xml` mengembalikan XML valid.
- [ ] Sitemap memuat halaman statis yang disetujui.
- [ ] Sitemap memuat hanya berita publik yang memenuhi aturan.
- [ ] Sitemap memuat agenda publik sesuai kebijakan indexing.
- [ ] Sitemap tidak memuat draft, admin, preview, URL privat, URL redirect, atau URL `noindex`.
- [ ] URL sitemap absolut dan kanonis.
- [ ] `robots.txt` dapat diakses dan menunjuk ke sitemap produksi.
- [ ] Test tidak mengasumsikan domain produksi tertentu pada environment lokal.

### Structured data

- [ ] JSON-LD artikel menggunakan data artikel yang benar.
- [ ] JSON-LD Event sesuai dengan detail agenda.
- [ ] JSON-LD tidak berisi data rekaan atau properti kosong yang tidak perlu.
- [ ] Serializer JSON-LD tetap meng-escape karakter berbahaya.
- [ ] Breadcrumb schema sesuai dengan breadcrumb yang terlihat.

### Operasional

- [ ] Test feature untuk sitemap, metadata, canonical, dan otorisasi tersedia.
- [ ] Build/type checks dan test suite proyek lulus.
- [ ] Search Console telah diverifikasi setelah deployment.
- [ ] Sitemap disubmit dan laporan indexing diperiksa.

---

## 14. Urutan Implementasi

### Phase 1 — Audit dan perbaikan fondasi

1. Audit `app.blade.php`, komponen `Seo`, route publik, dan perilaku Inertia Head.
2. Buat canonical URL resolver server-side.
3. Hapus penggunaan `window.location.href` sebagai sumber canonical.
4. Pastikan metadata halaman detail tersedia pada HTML awal.
5. Tambahkan test regression untuk canonical dan metadata.

### Phase 2 — Sitemap

1. Verifikasi kompatibilitas dan pasang Spatie Laravel Sitemap.
2. Buat sitemap berdasarkan allowlist halaman dan scope publik model.
3. Tambahkan endpoint `/sitemap.xml`.
4. Perbarui `robots.txt`.
5. Tambahkan test XML dan filtering konten.

### Phase 3 — Admin SEO

1. Pertahankan SEO global yang sudah ada pada `/admin/pengaturan`.
2. Implementasikan SEO halaman statis dengan persistence unik per halaman.
3. Integrasikan SEO berita dengan field existing.
4. Tambahkan metadata agenda bila dibutuhkan tanpa menggandakan data.
5. Terapkan otorisasi, validasi, preview, dan audit log.

### Phase 4 — Structured data dan optimasi konten

1. Tambahkan `Organization` dan `WebSite`.
2. Tambahkan `Article`/`NewsArticle`.
3. Pertahankan dan validasi `Event`.
4. Tambahkan `BreadcrumbList` jika konsisten dengan UI.
5. Perbaiki internal linking, alt text, dan performa gambar.

### Phase 5 — Validasi produksi

1. Jalankan seluruh test dan build.
2. Periksa HTML awal dan metadata di halaman publik.
3. Validasi sitemap, robots, canonical, dan JSON-LD.
4. Verifikasi domain di Google Search Console.
5. Submit sitemap dan pantau laporan indexing.

---

## 15. Referensi

- Repository: <https://github.com/Rumahkodingku/website-paroki-hspmtb-putussibau>
- Spatie Laravel Sitemap: <https://github.com/spatie/laravel-sitemap>
- Google — JavaScript SEO basics: <https://developers.google.com/search/docs/crawling-indexing/javascript/javascript-seo-basics>
- Google — Consolidate duplicate URLs: <https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls>
- Google — Build and submit a sitemap: <https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap>
- Google Search Console: <https://search.google.com/search-console/about>
- Google Rich Results Test: <https://search.google.com/test/rich-results>
- PageSpeed Insights: <https://pagespeed.web.dev/>

---

## 16. Catatan untuk Implementer

Sebelum mulai coding:

1. Periksa branch default dan status kerja lokal.
2. Baca `AGENTS.md`, `PRD.md`, `ARCHITECTURE.md`, `docs/DECISIONS.md`, serta aturan modul terkait yang benar-benar ada di repository.
3. Periksa migration, pola Form Request, authorization, audit logging, media, dan test existing.
4. Jangan membuat tabel, field, route, atau service duplikat sebelum memastikan tidak ada implementasi yang sudah tersedia.
5. Implementasikan per fase dengan perubahan kecil dan test regresi.
6. Jangan mengklaim indexing berhasil hanya karena sitemap berhasil dibuat; indexing diverifikasi melalui Google Search Console setelah deployment.
