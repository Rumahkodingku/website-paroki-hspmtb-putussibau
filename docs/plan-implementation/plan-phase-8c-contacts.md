# Implementation Plan — PHASE 8C

> **Project:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau\
> **Phase:** 8C — Kontak (Contacts)\
> **Status:** Implemented — branch `feat/phase-8c-contacts`\
> **Branch (usulan):** `feat/phase-8c-contacts`\
> **Commit dasar (audit):** `main` @ `21e5549`\
> **Spesifikasi:** `docs/roadmap/phase-8c-contacts.md`\
> **Sumber kebenaran:** `PRD.md` → `ARCHITECTURE.md` → `DESIGN.md` → `AGENTS.md` → `DECISIONS.md`

Dokumen roadmap Phase 8C menjelaskan _apa_ yang harus ada. Dokumen ini menjelaskan
_bagaimana_ perubahannya, dalam urutan apa, file mana yang tersentuh, dan bagaimana
diverifikasinya. Dokumen ini pelengkap roadmap, bukan pengganti.

> Dokumen ini disusun setelah audit codebase terhadap kondisi repository saat ini
> (`main` @ `21e5549`) dan **enam keputusan yang sudah dijawab pengguna** melalui
> tool `question`. Seluruh keputusan di §2.2 mengikat implementasi. Belum ada satu
> baris kode implementasi Phase 8C yang ditulis.

---

## Daftar Isi

1. [Kondisi Codebase Saat Ini](#1-kondisi-codebase-saat-ini)
2. [Task Phase 8C yang Akan Dikerjakan](#2-task-phase-8c-yang-akan-dikerjakan)
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

Audit dilakukan pada `main` @ `21e5549`. `git status` bersih kecuali satu file
untracked: `docs/roadmap/phase-8c-contacts.md` (dokumen fase ini sendiri — jangan
ditimpa). Tidak ada file kode yang diubah saat audit.

### 1.1 Fondasi yang sudah ada dan dapat dipakai ulang

| Area                   | Kondisi aktual                                                                                                                                                                                        | File                                                                          |
| ---------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------- | ------------------------------------------------------ |
| Route publik Kontak    | `Route::inertia('/kontak', 'public/kontak')->name('kontak.index')`; tanpa controller                                                                                                                  | `routes/web.php:106`                                                          |
| Halaman Kontak         | Masih placeholder Phase 03: `PublicSection` + `SectionHeading` dengan `[ISI: Kontak]`; tanpa props, tanpa `Seo`, tanpa komponen fitur                                                                 | `resources/js/features/public/kontak/pages/index.tsx`                         |
| Nama page              | `public/kontak` sudah terdaftar benar (`pageName`, `PageFinder`, `PageChunk`, `app.tsx`); tidak ada masalah page-name/ArchitectureTest                                                                | `resources/js/lib/page-name.ts`, `app/Support/PageFinder.php`                 |
| Konfigurasi kontak     | Grup `kontak` (7 key) + `sosial` (4 key) + `beranda.home_show_contact` (boolean, default `true`) sudah ada                                                                                            | `config/site-settings.php:69-97`                                              |
| Tipe setting           | Hanya `home_*`/`privacy_policy_content`; key kontak/sosial bertipe string                                                                                                                             | `config/site-settings.php:122-131`                                            |
| Default setting        | Hanya key `home_*`; `home_show_contact` default `true`                                                                                                                                                | `config/site-settings.php:148-156`                                            |
| Rule setting           | Hanya `privacy_policy_content`, `home_news_limit`, `home_events_limit`, `home_gallery_limit`; **tidak ada rule untuk `maps_*`/`contact_*`**                                                           | `config/site-settings.php:176-181`                                            |
| Sumber data tunggal    | `SiteSettingsService` (cache satu entry, `get()`/`getGroup()`/`setMany()`/`forget()`)                                                                                                                 | `app/Services/SiteSettingsService.php`, D-23                                  |
| Helper nomor           | `normalize()`, `isValid()`, `toTel()`, `toWhatsApp()`; sudah dipakai Pelayanan & Komunitas                                                                                                            | `app/Support/PhoneNumber.php`                                                 |
| Preseden validasi host | `GoogleFormUrl`: parse_url + wajib https + tolak kredensial + allowlist host/path                                                                                                                     | `app/Rules/GoogleFormUrl.php`                                                 |
| Validasi settings      | Rule dari config (`explode('                                                                                                                                                                          | ', ...)`) + closure tolak key tak dikenal + sanitasi hanya key bertipe `html` | `app/Http/Requests/Settings/UpdateSettingsRequest.php` |
| Form settings          | `type="url"` untuk key di `meta.urls`; `fieldKind()` menentukan tipe field                                                                                                                            | `resources/js/features/admin/pengaturan/pages/index.tsx:83-106`               |
| Metadata settings form | `urls` dan `multiline` dari lang; `SettingsController::formMeta()` mengirimnya                                                                                                                        | `lang/id/site-settings.php:68-108`, `SettingsController.php:73-88`            |
| Shared props           | `name`, `auth`, `locale`, `displayTimezone`, `sidebarOpen`, `seo`, `media`, `documentUploads`; **tidak ada `contact`**                                                                                | `app/Http/Middleware/HandleInertiaRequests.php:76-96`                         |
| Tipe shared            | `SharedProps`, `SeoDefaults`, `MediaLimits`, `DocumentUploadLimits`                                                                                                                                   | `resources/js/types/index.ts`                                                 |
| Dukungan test shared   | `publicPageProps()` mencerminkan shared props                                                                                                                                                         | `tests/js/support/inertia.tsx`                                                |
| Footer                 | Hardcode `[ISI: alamat lengkap paroki]`, `[ISI: nomor sekretariat]`, `[ISI: alamat surel paroki]`, `MEDIA_SOCIAL` placeholder, `[ISI: tautan peta lokasi]`; link `Halaman kontak` sudah ke `kontak()` | `resources/js/layouts/public/components/parish-footer.tsx`                    |
| Beranda                | Placeholder Phase 01/03: empat `ParishCard` `[ISI:]`; **tidak ada blok kontak**; `home_show_contact` tidak punya konsumen                                                                             | `resources/js/features/public/beranda/pages/index.tsx`                        |
| SEO publik             | Komponen `Seo` dipakai hampir semua halaman publik; SSR OFF; `dangerouslySetInnerHTML` allowlist tepat 2 file                                                                                         | `resources/js/components/seo.tsx`, `tests/Unit/ArchitectureTest.php:660-690`  |
| Ikon                   | Lucide React (mis. `Phone`, `Mail`, `MapPin`, `MessageCircle`, `Church`)                                                                                                                              | `parish-footer.tsx`                                                           |
| Test footer            | Meng-assert placeholder `[ISI: …]` **wajib tampil** dan social bukan link                                                                                                                             | `tests/js/components/parish-footer.test.tsx`                                  |
| Test settings          | Komprehensif: read/write/cache/otorisasi/sanitasi/range                                                                                                                                               | `tests/Feature/Settings/SiteSettingsTest.php`                                 |
| Test E2E publik        | `/kontak` sudah dicek 200 + tanpa overflow; footer → `/kontak`; mode gelap lewat link footer                                                                                                          | `tests/e2e/public.spec.ts`                                                    |

### 1.2 Yang belum ada dan menjadi pekerjaan utama Phase 8C

- Validasi `maps_embed_url` (host allowlist) dan `maps_link`.
- Ekstraksi `src` dari HTML iframe yang ditempel admin.
- Shared prop `contact` (satu sumber untuk halaman Kontak, footer, beranda).
- Halaman `/kontak` yang sesungguhnya.
- Integrasi data kontak di footer (menggantikan `[ISI: …]`).
- Blok kontak beranda yang dikendalikan `home_show_contact`.
- Aksi Salin alamat/nomor.
- Test Pest/Vitest/E2E khusus Kontak dan dokumentasi keputusan.

### 1.3 Fakta yang mengikat desain

- **Eloquent strict mode aktif** di luar production (`Model::shouldBeStrict`) — relevan bila ada relasi, namun Phase 8C tidak menambah model.
- **Wayfinder adalah sumber URL frontend**; tidak boleh hard-code URL. Route Kontak sudah ada (`kontak.index`).
- **Shared props harus kecil dan disengaja.** Menambah `contact` dibenarkan karena footer tampil di semua halaman publik (pola `seo`), bukan hanya di `/kontak`.
- **`type="url"` bawaan browser** pada form settings dapat memblokir submit sebelum rule server berjalan.
- **`dangerouslySetInnerHTML` allowlist tepat 2 file**; KON-13 + keputusan #4 menunda structured data agar allowlist tidak berubah.
- **`maps_link` tidak boleh dipakai sebagai embed** (KON-07); iframe hanya dari `maps_embed_url` tervalidasi.
- **Tanpa migration/model/tabel baru** (KON-01, scope §3).
- **`npm run e2e` menyajikan `public/build`** → wajib `npm run build` lebih dulu.
- **Pest dan Playwright berbagi satu database test** → jangan jalankan Playwright bare setelah Pest.

---

## 2. Task Phase 8C yang Akan Dikerjakan

### 2.1 Scope yang disetujui

Phase 8C dikerjakan **seluruhnya untuk modul Kontak**, termasuk integrasi kontak ke
footer dan blok kontak beranda.

**Termasuk (sesuai `docs/roadmap/phase-8c-contacts.md` §3):**

| Task | Isi                                                                                                                             |
| ---- | ------------------------------------------------------------------------------------------------------------------------------- |
| 8C.1 | Kontrak data/backend: validasi peta, ekstraksi iframe, shared prop `contact`, test backend                                      |
| 8C.2 | Halaman publik `/kontak`: heading, detail kontak, peta, petunjuk arah, sosial, aksi salin, empty state, metadata, test frontend |
| 8C.3 | Footer & beranda: buang hardcode, satu sumber data, `home_show_contact`, test komponen                                          |
| 8C.4 | QA akhir: Pest, Vitest, lint, typecheck, PHPStan, build, E2E, dokumentasi                                                       |

**Tidak termasuk (roadmap §3):**

- Formulir kirim pesan, inbox, dashboard pesan, SMTP, notifikasi.
- Live chat, chatbot, CRM, booking, appointment.
- Tabel/model database kontak baru (`contact_persons` tidak dipakai).
- Menu admin Kontak terpisah (Pengaturan Situs sudah mencukupi).
- Redesign besar beranda/footer/navbar/design system.
- Perubahan modul Galeri (8A) atau Dokumen (8B).
- Structured data Organization/Church (ditunda, lihat §2.2 #4).
- Dependency baru (Composer/npm).

### 2.2 Keputusan produk yang sudah dikonfirmasi

| #   | Keputusan                                                                                 | Dampak                                                                                   |
| --- | ----------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| 1   | **Blok kontak beranda dibangun sekarang**, digate `home_show_contact`                     | Langkah 8C.3 aktif; beranda tetap placeholder untuk blok lain                            |
| 2   | **`maps_embed_url` menerima URL ATAU HTML iframe**; sistem mengekstrak `src` (PRD KON-02) | Butuh `MapsUrl::extract()` + normalisasi saat simpan + test nilai tersimpan              |
| 3   | **`maps_link` host apa pun yang valid** (skema http/https, seperti Lokasi/Area)           | Rule `nullable\|string\|url:http,https\|max:2048`                                        |
| 4   | **Structured data Organization/Church ditunda**                                           | Tidak menambah entri ketiga `dangerouslySetInnerHTML`; dicatat sebagai follow-up di D-67 |
| 5   | **Aksi Salin disertakan**                                                                 | Tombol salin alamat/nomor dengan aksesibilitas                                           |
| 6   | **Shared prop `contact`** (tetap `Route::inertia`, tanpa controller publik)               | Footer site-wide terlayani; kontrak TS + test helper ikut diperbarui                     |

### 2.3 Task per sub-bagian (checklist roadmap §9)

**8C.0 — Audit dan persiapan** (selesai): commit dasar `21e5549`, working tree bersih,
audit konfigurasi/service/helper/halaman/footer/beranda/shared props/validasi URL/test,
susun TODO + acceptance criteria.

**8C.1 — Kontrak data/backend**

- [ ] Tentukan kontrak props (`contact`) sesuai pola `seo`.
- [ ] Data dibaca melalui `SiteSettingsService`.
- [ ] `PhoneNumber` untuk `tel:` dan WhatsApp.
- [ ] Validasi email sebelum `mailto:`.
- [ ] Validasi + allowlist host peta; ekstraksi `src` dari iframe.
- [ ] Null/kosong ditangani konsisten; tanpa query/service duplikat.
- [ ] Test backend; jalankan test terkait.

**8C.2 — Halaman publik**

- [ ] Heading + pengantar; alamat; telepon; WhatsApp; email; jam layanan.
- [ ] Peta hanya bila embed valid; petunjuk arah bila valid; tautan sosial valid.
- [ ] Aksi salin; empty state; mobile-first; tanpa overflow; label aksesibel.
- [ ] Metadata mengikuti pola `Seo`; test frontend; typecheck.

**8C.3 — Footer dan beranda**

- [ ] Buang hardcode kontak dalam scope; satu sumber data.
- [ ] Link footer ke `kontak.index`; aksi telepon/WA memakai helper server.
- [ ] Blok kontak beranda sesuai `home_show_contact`; `false` menyembunyikan beranda saja.
- [ ] Data kosong tidak membuat link rusak; test komponen + regression.

**8C.4 — QA akhir**

- [ ] Jalankan Pest, Vitest, lint/check, typecheck, PHPStan, build, E2E yang relevan.
- [ ] Periksa diff; buang perubahan tidak terkait; tanpa dependency tak perlu.
- [ ] Tanpa informasi kontak palsu/hardcode.
- [ ] Perbarui dokumentasi dan roadmap; catat hasil verifikasi aktual.

---

## 3. Implementation Plan

Urutan di bawah disusun berdasarkan dependency. Jangan melompati langkah.

### 8C.0 — Persiapan branch

1. Konfirmasi `git status`; buat branch `feat/phase-8c-contacts` dari `main` terbaru.
2. Sertakan `docs/roadmap/phase-8c-contacts.md` (untracked) sebagai bagian pekerjaan,
   jangan ditimpa.
3. Catat baseline: `php artisan test --compact` (subset kontak/settings) +
   `npm run check` + `npm run types:check`.

### 8C.1 — Validasi URL peta dan ekstraksi iframe

1. **Config** `config/site-settings.php`
    - Tambah blok `maps`:
        - `embed_hosts` = `['www.google.com', 'maps.google.com']`
        - `embed_paths` = prefix path yang diizinkan untuk `www.google.com` (mis.
          `/maps/embed`) dan untuk `maps.google.com` (mis. `/maps`)
    - Tambah rule `maps_link` di `rules`: `nullable|string|url:http,https|max:2048`.
    - (Opsional) pindahkan daftar allowlist ke `config/maps.php` bila dianggap lebih
      rapi; keputusan dicatat di D-67. Rekomendasi: tetap di `config/site-settings.php`
      agar sejalan dengan filosofi "satu file pengaturan".

2. **Support** `app/Support/MapsUrl.php`
    - `extract(?string $value): ?string`
        - `trim()`; nilai kosong → `null`.
        - Jika nilai mengandung `<iframe` (setelah di-trim, case-insensitive), parse dengan
          `DOMDocument` dan ambil atribut `src` pada elemen `iframe` pertama. Bukan
          `str_contains` sederhana (KON-07).
        - Selain itu, perlakukan nilai sebagai URL apa adanya.
    - `embed(?string $value): ?string`
        - `extract()` dulu, lalu validasi: parse_url sukses, ada `scheme`+`host`, skema
          wajib `https`, tolak `user`/`pass`, host sesuai `embed_hosts`, path sesuai
          `embed_paths`. Kembalikan URL tervalidasi atau `null`.
    - `directions(?string $value): ?string`
        - parse_url valid + skema `http`/`https` + tolak kredensial; kembalikan URL atau
          `null`.
    - Semua pesan/penjelasan dalam docblock (Bahasa Indonesia untuk pesan error di Rule).

3. **Rule** `app/Rules/GoogleMapsEmbedUrl.php`
    - Mengimplementasikan `ValidationRule`, mendelegasikan ke `MapsUrl::embed()`.
    - Pesan Indonesia: "Kode/URL peta harus berupa alamat https Google Maps (`/maps/embed`)."

4. **Request** `app/Http/Requests/Settings/UpdateSettingsRequest.php`
    - `prepareForValidation()` menormalkan `settings.maps_embed_url`:
        - Terima URL mentah atau HTML iframe → simpan **URL tervalidasi** hasil
          `MapsUrl::extract()`. Ini **perubahan perilaku sadar** (roadmap §7.8) dan
          didokumentasikan (D-67).
    - Lampirkan `new GoogleMapsEmbedUrl` pada `settings.maps_embed_url` (satu-satunya
      tempat object Rule dipasang, karena `config('site-settings.rules')` hanya menerima
      string rule).
    - Tambah pesan error Indonesia; rule `maps_link` dari config otomatis terpasang.
    - Pastikan `privacy_policy_content` dan normalisasi boolean tetap tidak berubah.

5. **Lang** `lang/id/site-settings.php`
    - **Keluarkan `maps_embed_url` dari `urls`** agar dirender sebagai teks/textarea,
      bukan `type="url"` (iframes tidak valid sebagai URL input).
    - Tambah `maps_embed_url` ke `multiline` agar admin dapat menempel iframe panjang.
    - Tambah hint: "Tempel URL embed atau kode `<iframe>` Google Maps. Sistem hanya
      menyimpan URL tervalidasi."

6. **Lang** `lang/id/validation.php`
    - Tambah atribut `maps_embed_url` dan `maps_link` (mis. "URL peta tertanam",
      "Tautan peta") agar pesan validasi menyebut istilah yang dipahami pengelola.

7. **Test backend (8C.1)**
    - `tests/Feature/Settings/MapsEmbedValidationTest.php` (atau perluas
      `SiteSettingsTest.php`):
        - URL embed Google valid diterima; nilai tersimpan = URL.
        - HTML iframe valid diterima; **nilai tersimpan = `src` hasil ekstraksi** (bukan HTML).
        - `javascript:`, `data:`, host non-Google, URL berkredensial ditolak.
        - `maps_link` skema tidak valid (`ftp://`, `javascript:`) ditolak.
        - `maps_link` kosong → `null`, bukan error.

### 8C.2 — Shared prop `contact`

1. **`HandleInertiaRequests::share()`**
    - Tambah `'contact' => fn (): array => $this->contactDetails()`.
    - Method `contactDetails()` membaca via `SiteSettingsService` (`getGroup('kontak')`
        - `getGroup('sosial')`) dan membentuk:

        ```text
        contact: {
          address: string|null,          // trim, null bila kosong
          phone:   { display: string, tel: string } | null,      // hanya bila PhoneNumber valid
          whatsapp:{ display: string|null, url: string } | null,  // hanya bila PhoneNumber valid
          email:   string|null,          // hanya bila FILTER_VALIDATE_EMAIL lolos
          officeHours: string|null,
          mapsEmbedUrl: string|null,     // hanya bila MapsUrl::embed() lolos
          mapsLink: string|null,         // hanya bila MapsUrl::directions() lolos
          social: {                      // hanya URL http/https valid; null bila tak ada
            facebook: string|null,
            instagram: string|null,
            youtube: string|null,
            whatsappChannel: string|null,
          }
        }
        ```

    - `tel` = `PhoneNumber::toTel(contact_phone)`; `whatsapp.url` =
      `PhoneNumber::toWhatsApp(contact_whatsapp)`, fallback ke `contact_phone` bila
      `contact_whatsapp` kosong (pola `PublicServiceDetailResource`).
    - **Validasi ulang di jalur baca** (embed/directions) agar nilai lama yang tidak valid
      pernah tidak dirender sebagai iframe.
    - Perbarui docblock shape `share()` (kontrak tertulis; AGENTS).

2. **Validasi runtime (defensif)**
    - `contactDetails()` tidak boleh menambah query per request di luar caches
      `SiteSettingsService` (D-23).

### 8C.3 — Tipe & dukungan test

1. **`resources/js/types/index.ts`**
    - Tambah tipe `ContactDetails` dan key `contact` pada `SharedProps`.

2. **`tests/js/support/inertia.tsx`**
    - Tambah `contact` ke `publicPageProps()` dengan bentuk lengkap (semua `null` atau
      nilai contoh) agar component test tidak gagal.

### 8C.4 — Halaman publik `/kontak`

1. **`resources/js/features/public/kontak/pages/index.tsx`**
    - Ganti placeholder dengan halaman sesungguhnya.
    - `<Seo title="Hubungi Kami" description={…} />` mengikuti pola halaman publik lain.
    - Komposisi:
        1. Heading/hero ringkas: judul "Hubungi Kami" + pengantar tanpa janji waktu respons.
        2. Informasi kontak: alamat, telepon, WhatsApp, email, jam layanan — kondisional.
        3. Lokasi: iframe peta (bila `mapsEmbedUrl`) + tombol petunjuk arah (bila `mapsLink`).
        4. Media sosial: tampilkan hanya tautan valid.
    - Aksi Salin pada alamat dan nomor (keputusan #5).
    - Empty state informatif bila seluruh data kosong (KON-10), tanpa `[ISI: …]` palsu.

2. **Komponen fitur** `resources/js/features/public/kontak/components/`
    - `contact-details.tsx` — daftar item kontak kondisional.
    - `contact-map.tsx` — iframe + tombol petunjuk arah.
    - `contact-social.tsx` — tautan sosial valid.
    - `copy-button.tsx` — tombol salin dengan `navigator.clipboard`, `aria-label`,
      feedback singkat; fallback aman bila API tidak tersedia.
    - `types.ts` — bila komponen butuh tipe lokal.

3. **Prinsip visual (DESIGN.md)**
    - Gunakan `PublicSection`, `PublicContainer`, `SectionHeading`, `ParishCard`,
      `ParishCardContent`, `Text`, `Button`, ikon Lucide.
    - Token semantik (`bg-primary`, `text-muted-foreground`, `border-hairline`); dilarang
      warna hex.
    - Ikon dekoratif `aria-hidden`; tombol/link punya accessible name.
    - Iframe: `title` bermakna, `loading="lazy"`, `referrerPolicy="no-referrer-when-downgrade"`,
      `allowFullScreen` bila sesuai; tidak ada sandbox yang merusak peta Google.
    - Mobile-first; kartu satu kolom di layar kecil; peta tidak menyebabkan overflow.

4. **Route**
    - Tetap `Route::inertia('/kontak', 'public/kontak')` (keputusan #6). Tidak ada
      controller; data dari shared prop.

### 8C.5 — Footer

1. **`resources/js/layouts/public/components/parish-footer.tsx`**
    - Baca `contact` dari `usePage()`.
    - Ganti `[ISI: alamat lengkap paroki]`, `[ISI: nomor sekretariat]`,
      `[ISI: alamat surel paroki]`, `[ISI: tautan peta lokasi]`, dan `MEDIA_SOCIAL`
      placeholder dengan data valid dari shared prop.
    - Baris yang datanya kosong **disembunyikan** (KON-11/KON-10).
    - Telepon memakai `tel` hasil server; WhatsApp memakai `url` hasil server (tanpa
      normalisasi kedua) dengan `target="_blank" rel="noopener noreferrer"`.
    - Pertahankan `FOOTER_LINK`, target sentuh 44px, grup nav, dan link
      `Halaman kontak` → `kontak.index`. Tidak redesign total.

2. **Update test** `tests/js/components/parish-footer.test.tsx`
    - Placeholder `[ISI: …]` tidak lagi wajib tampil; ganti dengan skenario:
        - data lengkap → semua tautan tampil dengan href benar;
        - data kosong → baris disembunyikan, tanpa link mati;
        - social kosong → bukan link.

### 8C.6 — Beranda

1. **`resources/js/features/public/beranda/pages/index.tsx`** (atau komponen
   `features/public/beranda/components/home-contact-section.tsx`)
    - Tambah satu section kontak ringkas memakai shared `contact` yang sama.
    - Dirender hanya bila `contact` (dari shared prop) menunjukkan `home_show_contact`.
      Karena `home_show_contact` bukan bagian `contact`, perlu salah satu:
        - tambahkan `homeShowContact: boolean` ke dalam shared prop `contact`; **atau**
        - baca dari `seo`/shared prop lain. Rekomendasi: **sertakan `homeShowContact`
          di dalam `contact`** (masih satu konsep "kontak"), sehingga tidak menambah prop baru.
    - `home_show_contact=false` → section tidak dirender; `/kontak` tetap aktif.
    - Ikuti pola beranda saat ini; jangan redesign blok lain.

2. **Test frontend beranda**
    - `home_show_contact=true` → section tampil; `false` → tidak tampil.

### 8C.7 — Test lengkap

1. **Pest**
    - `tests/Feature/Contact/ContactPageTest.php`:
        - guest dapat membuka `/kontak` (200, component `public/kontak`).
        - props `contact` benar saat data lengkap.
        - field kosong → `null`; nomor invalid → `phone`/`whatsapp` `null`.
        - nomor `0812…` → `tel:+62812…` dan `wa.me/62812…` (AC-KON-5).
        - maps embed valid diterima; `javascript:`/`data:`/host non-Google/kredensial ditolak.
        - `maps_link` invalid → `null`.
        - social kosong → `null`.
    - `tests/Feature/Settings/MapsEmbedValidationTest.php` (8C.1) — validasi + ekstraksi.
    - `tests/Unit/PhoneNumberTest.php` (opsional, memperkuat roadmap §8.1).
    - Perluas test yang terdampak bila ada perubahan perilaku `UpdateSettingsRequest`.

2. **Vitest**
    - `tests/js/features/public/kontak/contact-page.test.tsx`:
        - data tampil saat tersedia; field kosong disembunyikan.
        - href `tel:`/`https://wa.me/...`/`mailto:`/petunjuk arah benar.
        - peta hanya tampil bila URL valid tersedia.
        - link sosial kosong tidak dirender.
        - tombol salin punya accessible name dan memanggil clipboard (mock).
    - Update `tests/js/components/parish-footer.test.tsx`.
    - Tambah test blok kontak beranda mengikuti `home_show_contact`.

3. **Playwright** `tests/e2e/kontak.spec.ts` + helper `tests/e2e/support/contact.ts`
    - Helper menanam site settings lewat `php artisan tinker --execute` dengan
      `DB_DATABASE` dari `e2eEnvironment()` (pola `tests/e2e/support/gallery.ts`).
    - Skenario:
        1. Pengunjung tanpa login membuka `/kontak`.
        2. Data fixture valid tampil.
        3. Link telepon, WhatsApp, email, petunjuk arah punya target benar.
        4. Peta tampil hanya saat URL embed valid tersedia.
        5. Data kosong tidak menghasilkan tombol rusak.
        6. Footer menuju halaman kontak dan data konsisten.
        7. Blok kontak beranda mengikuti `home_show_contact`.
        8. Perubahan pengaturan admin terlihat di halaman publik setelah disimpan.
        9. Tidak ada horizontal overflow pada viewport mobile (360px).
        10. Tidak ada error console/request akibat perubahan ini.
    - Gunakan fixture test, bukan data pribadi/produksi.

### 8C.8 — Dokumentasi

1. `docs/DECISIONS.md`: tambah **D-67** berisi:
    - kontrak shared prop `contact` (dan `homeShowContact`);
    - kebijakan validasi + ekstraksi `maps_embed_url` (perubahan perilaku simpan);
    - `maps_link` host apa pun http/https;
    - perubahan perilaku footer (placeholder → sembunyikan kosong) yang menyimpang dari
      D-6 Phase 03;
    - structured data ditunda.
2. `docs/roadmap/README.md`: status Phase 8C.
3. `AGENTS.md`: known gaps/status (halaman Kontak, shared prop `contact`).

---

## 4. File yang Akan Terpengaruh

### 4.1 Dibuat — backend

| File                               | Alasan                                                   |
| ---------------------------------- | -------------------------------------------------------- |
| `app/Support/MapsUrl.php`          | Ekstraksi `src` iframe + validasi URL peta (satu sumber) |
| `app/Rules/GoogleMapsEmbedUrl.php` | Rule host allowlist peta (pola `GoogleFormUrl`)          |

### 4.2 Dibuat — frontend

| File                                                                       | Alasan                                              |
| -------------------------------------------------------------------------- | --------------------------------------------------- |
| `resources/js/features/public/kontak/components/contact-details.tsx`       | Daftar item kontak kondisional                      |
| `resources/js/features/public/kontak/components/contact-map.tsx`           | Peta embed + petunjuk arah                          |
| `resources/js/features/public/kontak/components/contact-social.tsx`        | Tautan sosial valid                                 |
| `resources/js/features/public/kontak/components/copy-button.tsx`           | Aksi Salin alamat/nomor                             |
| `resources/js/features/public/beranda/components/home-contact-section.tsx` | Blok kontak beranda (opsional bila ditaruh di page) |

### 4.3 Dibuat — test

| File                                                    | Fokus                                                             |
| ------------------------------------------------------- | ----------------------------------------------------------------- |
| `tests/Feature/Contact/ContactPageTest.php`             | Props `contact`, guest, validasi/link, social                     |
| `tests/Feature/Settings/MapsEmbedValidationTest.php`    | Validasi + ekstraksi peta (atau perluasan `SiteSettingsTest.php`) |
| `tests/Unit/PhoneNumberTest.php`                        | Unit helper nomor (opsional)                                      |
| `tests/js/features/public/kontak/contact-page.test.tsx` | Render, href, peta, sosial, salin                                 |
| `tests/e2e/kontak.spec.ts`                              | Alur publik + footer + beranda + admin→publik                     |
| `tests/e2e/support/contact.ts`                          | Fixture site settings untuk E2E                                   |

### 4.4 Diubah

| File                                                       | Perubahan                                                           |
| ---------------------------------------------------------- | ------------------------------------------------------------------- |
| `app/Http/Middleware/HandleInertiaRequests.php`            | Shared prop `contact` + docblock shape                              |
| `app/Http/Requests/Settings/UpdateSettingsRequest.php`     | Normalisasi + rule `maps_embed_url`/`maps_link`                     |
| `config/site-settings.php`                                 | Blok `maps` (host/path allowlist) + rule `maps_link`                |
| `lang/id/site-settings.php`                                | `maps_embed_url` keluar dari `urls`, masuk `multiline`, tambah hint |
| `lang/id/validation.php`                                   | Atribut `maps_embed_url`, `maps_link`                               |
| `resources/js/types/index.ts`                              | Tipe `ContactDetails` + `SharedProps.contact`                       |
| `tests/js/support/inertia.tsx`                             | `contact` di `publicPageProps()`                                    |
| `resources/js/features/public/kontak/pages/index.tsx`      | Halaman Kontak sesungguhnya                                         |
| `resources/js/layouts/public/components/parish-footer.tsx` | Kontak/sosial dari shared prop                                      |
| `resources/js/features/public/beranda/pages/index.tsx`     | Blok kontak `home_show_contact`                                     |
| `tests/js/components/parish-footer.test.tsx`               | Placeholder → data/empty-state                                      |
| `tests/Feature/Settings/SiteSettingsTest.php`              | Bila rule baru perlu diuji di sini                                  |
| `docs/DECISIONS.md`                                        | D-67                                                                |
| `docs/roadmap/README.md`                                   | Status Phase 8C                                                     |
| `AGENTS.md`                                                | Known gaps/status                                                   |

### 4.5 Dihapus / diganti

| File/Isi                                                          | Alasan                                |
| ----------------------------------------------------------------- | ------------------------------------- |
| Isi placeholder `features/public/kontak/pages/index.tsx`          | Digantikan halaman sesungguhnya       |
| Hardcode kontak/sosial di `parish-footer.tsx`                     | Digantikan shared prop `contact`      |
| `maps_embed_url` dari daftar `lang/id/site-settings.php` → `urls` | Agar dirender textarea (iframe paste) |

Tidak ada route, tabel, atau file test yang dihapus.

---

## 5. Dependency / Configuration Impact

### 5.1 Tidak ada dependency baru

- Tidak menambah package Composer/npm.
- Ekstraksi iframe memakai ekstensi `DOM`/`libxml` bawaan PHP (sudah tersedia); tidak
  ada HTML parser pihak ketiga.

### 5.2 Config baru

- Blok `maps` di `config/site-settings.php` (host + path allowlist).
- Rule `maps_link` di `config/site-settings.php`.
- Tidak mengubah `config/media.php`, `config/documents.php`, atau `config/html.php`.

### 5.3 Shared prop baru

`contact` (dan `homeShowContact` di dalamnya):

```text
contact: {
  address, phone{display,tel}, whatsapp{display,url}, email, officeHours,
  mapsEmbedUrl, mapsLink, homeShowContact,
  social{facebook,instagram,youtube,whatsappChannel}
}
```

Wajib ditambahkan ke:

- `HandleInertiaRequests::share()`
- `resources/js/types/index.ts`
- `tests/js/support/inertia.tsx`

Tanpa pembaruan test helper, semua component test yang memakai `renderWithInertia`
akan gagal (AGENTS).

### 5.4 Route dan Wayfinder

- Route tidak berubah (`kontak.index` sudah ada).
- Tidak ada route baru, sehingga tidak wajib `wayfinder:generate`; namun tetap
  jalankan `php artisan wayfinder:generate --with-form` bila ada perubahan lain yang
  menyentuh route.
- Tetap gunakan `@/routes/kontak`, bukan URL hardcode.

### 5.5 Settings form

- `maps_embed_url` dirender ulang sebagai textarea (bukan `type="url"`).
- `maps_link` tetap `type="url"`; browser tetap memvalidasi format URL dasar, server
  memvalidasi skema + panjang.
- Keputusan `noValidate` pada form settings dicatat di D-67; **rekomendasi**: tidak
  menambahkan `noValidate` global agar validasi native field URL lain (social, logo,
  dsb.) tidak melemah. Validasi `maps_embed_url` dijalankan server karena field-nya
  bukan lagi `type="url"`.

### 5.6 Cache

- `contact` membaca lewat `SiteSettingsService` (cache satu entry, D-23) sehingga tidak
  menambah query per request di luar cache yang sudah ada.
- Invalidasi tetap terjadi pada `setMany()`.

### 5.7 Tidak ada perubahan database

- Tanpa migration, model, tabel, atau seeder baru (KON-01, scope §3).

---

## 6. Risiko dan Potensi Konflik

| #   | Risiko/Konflik                                                    | Dampak                                          | Mitigasi                                                                                              |
| --- | ----------------------------------------------------------------- | ----------------------------------------------- | ----------------------------------------------------------------------------------------------------- | ---------------------- |
| 1   | Konflik scope beranda (KON-12 vs Phase 9)                         | Beranda placeholder bisa tampak "setengah jadi" | Bangun **satu** section kontak saja (keputusan #1); blok lain tetap placeholder; catat di D-67        |
| 2   | Perubahan perilaku footer (placeholder → sembunyikan kosong)      | Menyimpang dari D-6 Phase 03; test lama gagal   | Perbarui `parish-footer.test.tsx`; catat di D-67; placeholder bukan data nyata                        |
| 3   | Structured data KON-06                                            | PRD meminta Organization/Church                 | Ditunda (keputusan #4); dicatat sebagai follow-up; tidak mengubah allowlist `dangerouslySetInnerHTML` |
| 4   | Ekstraksi iframe mengubah nilai tersimpan                         | Admin bisa kaget nilai berubah jadi URL         | Dokumentasikan (roadmap §7.8) + test nilai tersimpan; hint di form                                    |
| 5   | `type="url"` bawaan browser                                       | Submit terblokir sebelum validasi server        | `maps_embed_url` tidak lagi `type="url"`; `maps_link` tetap URL                                       |
| 6   | Shared prop `contact` diperluas ke semua halaman                  | Semua component test bisa gagal                 | Update `tests/js/support/inertia.tsx` + `types/index.ts` dalam perubahan yang sama                    |
| 7   | Validasi baru menolak nilai lama                                  | Tidak ada baris `maps_*` saat ini → aman        | Tetap validasi ulang di jalur baca agar defensif                                                      |
| 8   | `npm run e2e` menyajikan `public/build`                           | UI lama yang teruji                             | `npm run build` sebelum `npm run e2e`                                                                 |
| 9   | Pest & Playwright berbagi database test                           | E2E gagal di `signIn`                           | Jalankan `npm run e2e`, bukan `npx playwright test`                                                   |
| 10  | `php artisan serve` liar di port 8000                             | Semua login E2E gagal                           | Cek `ss -ltnp                                                                                         | grep 8000` sebelum E2E |
| 11  | `npm run check:fix` pernah menghapus isi file                     | Kehilangan kode                                 | Gunakan `npm run check`; periksa `git diff`                                                           |
| 12  | Iframe peta + responsivitas 360px                                 | Horizontal overflow                             | Bungkus peta dengan kontainer `aspect-ratio`/`w-full`; uji E2E overflow                               |
| 13  | `navigator.clipboard` tidak tersedia/denied                       | Error console                                   | Fallback aman + tombol tetap aksesibel; uji E2E tidak ada error console                               |
| 14  | Eloquent strict mode                                              | Tidak relevan (tanpa model baru)                | —                                                                                                     |
| 15  | Dua layanan membaca `contact_phone` (Service/Komunitas resources) | Inkonsistensi fallback                          | `contactDetails()` meniru pola fallback `PublicServiceDetailResource`; test                           |
| 16  | PHPStan menganalisis `tests/` tanpa baseline                      | Kegagalan statis                                | Ikuti pola test yang ada; gunakan tipe Pest yang benar                                                |

---

## 7. Verification Plan

### 7.1 Pemeriksaan sebelum coding

```bash
git status
git branch --show-current
php artisan route:list --path=kontak
php artisan test --compact tests/Feature/Settings
npm run types:check
```

Pastikan working tree bersih (kecuali file roadmap untracked), branch dasar benar, dan
tidak ada proses `php artisan serve` liar di port 8000.

### 7.2 Backend

```bash
php artisan test --compact tests/Feature/Contact
php artisan test --compact tests/Feature/Settings
php artisan test --compact tests/Unit/PhoneNumberTest.php
php artisan test --compact tests/Unit/ArchitectureTest.php
```

Fokus assertion:

- `/kontak` 200 untuk guest dan component-nya `public/kontak`.
- Bentuk props `contact` lengkap dan field kosong `null`.
- `tel:`/`wa.me` hanya untuk nomor valid; `0812…` → `wa.me/62812…`.
- `mailto:` hanya untuk email valid.
- `maps_embed_url` valid diterima; non-Google/`javascript:`/`data:`/kredensial ditolak.
- Nilai tersimpan untuk iframe = URL hasil ekstraksi (bukan HTML).
- `maps_link` skema salah ditolak; kosong → `null`.
- Tidak ada query langsung ke tabel `site_settings` di luar service.

### 7.3 Frontend

```bash
npm run types:check
npm run check
npm run test:unit
```

Test Vitest minimal:

- Halaman Kontak menampilkan tiap bagian hanya saat data ada.
- Empty state ketika semua data kosong.
- href telepon/WhatsApp/email/petunjuk arah benar.
- Peta hanya dirender saat `mapsEmbedUrl` ada; iframe punya `title`.
- Social kosong tidak dirender.
- Tombol salin punya accessible name + memanggil clipboard (mock).
- Footer: data lengkap vs kosong; tidak ada link mati.
- Blok beranda mengikuti `home_show_contact`.

### 7.4 Build dan E2E

```bash
npm run build
npm run e2e
```

Catatan:

- `npm run build` lebih dulu karena E2E memakai `public/build`.
- `npm run e2e` (bukan `npx playwright test`) agar `app:e2e:prepare` membersihkan
  database test.
- Helper `tests/e2e/support/contact.ts` menanam fixture via
  `php artisan tinker --execute` dengan `DB_DATABASE` dari `e2eEnvironment()`.

### 7.5 Gate penuh

```bash
vendor/bin/pint --dirty --format agent
composer test
```

atau:

```bash
composer ci:check
```

`composer test` mencakup Pint test, PHPStan, dan Pest. `composer ci:check` mencakup
lint TS, typecheck, Vitest, dan Pest.

### 7.6 Pemeriksaan manual

- Buka `/kontak` sebagai guest; periksa setiap aksi di viewport 360/768/1280.
- Periksa props Inertia: `contact` tidak memuat raw HTML iframe atau `javascript:`.
- Simpan `maps_embed_url` berisi `<iframe src="https://www.google.com/maps/embed?...">`
  dari admin; pastikan yang tersimpan adalah URL.
- Simpan URL embed non-Google; pastikan ditolak dengan pesan Indonesia.
- Set `home_show_contact=false`; pastikan blok beranda hilang, `/kontak` tetap ada.
- Periksa keyboard: tombol salin, tautan, tombol petunjuk arah.
- Periksa tidak ada error console di `/kontak`.

---

## 8. Acceptance Criteria yang Akan Terpenuhi

### 8.1 Roadmap Phase 8C §5 (KON-01..KON-13)

- [ ] **KON-01** sumber data tunggal via `SiteSettingsService` + shared prop; tanpa hardcode/tabel duplikat.
- [ ] **KON-02** alamat tampil bila bermakna; line break dipertahankan; tanpa placeholder palsu.
- [ ] **KON-03** telepon + `tel:` hanya bila valid (`PhoneNumber::toTel`); mudah dibaca.
- [ ] **KON-04** WhatsApp hanya bila valid (`PhoneNumber::toWhatsApp`); tab baru dengan `rel` aman.
- [ ] **KON-05** email + `mailto:` hanya bila valid.
- [ ] **KON-06** jam layanan tampil bila ada; disembunyikan bila kosong.
- [ ] **KON-07** peta hanya bila `maps_embed_url` tervalidasi (https + allowlist host; tolak `javascript:`/`data:`/kredensial); iframe `title` + `loading="lazy"`.
- [ ] **KON-08** petunjuk arah memakai `maps_link` valid; tanpa koordinat karangan.
- [ ] **KON-09** Facebook/Instagram/YouTube/kanal WhatsApp hanya bila URL valid; label aksesibel.
- [ ] **KON-10** tiap bagian berdasarkan data valid; tanpa `href=""`/URL `undefined`/iframe kosong; empty state informatif.
- [ ] **KON-11** footer memakai sumber & helper sama; link ke `kontak.index`; invalid disembunyikan; tanpa redesign total.
- [ ] **KON-12** `home_show_contact=true` menampilkan blok beranda; `false` menyembunyikan blok beranda saja.
- [ ] **KON-13** metadata mengikuti pola `Seo`; tanpa sistem SEO baru/klaim baru.

### 8.2 PRD §7.9 (AC-KON-1..5)

- [ ] **AC-KON-1** tombol telepon, WhatsApp, email, petunjuk arah berfungsi di ponsel.
- [ ] **AC-KON-2** peta tampil dengan benar dan dimuat lazy.
- [ ] **AC-KON-3** perubahan kontak di admin terlihat di Kontak, footer, Beranda.
- [ ] **AC-KON-4** embed domain non-Google ditolak.
- [ ] **AC-KON-5** nomor `0812…` dinormalisasi menjadi `wa.me/62812…`.

### 8.3 Roadmap Phase 8C §10 (Definition of Done)

- [ ] `/kontak` memakai data Pengaturan Situs.
- [ ] Telepon, WhatsApp, email, petunjuk arah, peta hanya muncul bila valid.
- [ ] Data kosong tidak menghasilkan tombol/link/iframe rusak.
- [ ] Footer dan beranda memakai sumber data konsisten.
- [ ] `home_show_contact` berfungsi sesuai definisi.
- [ ] Tidak ada tabel/pengaturan kontak duplikat.
- [ ] Validasi URL peta aman dan diuji.
- [ ] UI responsif, aksesibel, sesuai design system.
- [ ] Test backend/frontend, typecheck, lint, build, E2E lulus.
- [ ] Tidak ada regresi fase sebelumnya.
- [ ] Dokumentasi relevan diperbarui.
- [ ] Semua TODO selesai dicentang; pekerjaan yang belum selesai dijelaskan.
- [ ] Laporan akhir hanya menyebut test yang benar-benar dijalankan dan hasil aktual.

### 8.4 Requirement turunan

- [ ] **PRD XC-S2** hanya domain Google Maps untuk embed.
- [ ] **PRD KON-04** satu sumber untuk halaman Kontak, footer, Beranda.
- [ ] **DESIGN.md** token/komponen/ikon yang ada; tanpa warna hardcode; target sentuh 44px.
- [ ] **AGENTS.md** setiap form Inertia tetap validasi server; shared props tersinkron dengan `types/index.ts` dan `tests/js/support/inertia.tsx`.

---

## 9. Pertanyaan / Ambiguity

Seluruh keputusan produk utama sudah dikonfirmasi melalui tool `question`:

1. **Blok kontak beranda** → dibangun sekarang, digate `home_show_contact`.
2. **`maps_embed_url`** → menerima URL atau HTML iframe; ekstraksi `src`.
3. **`maps_link`** → host apa pun dengan skema http/https.
4. **Structured data Organization/Church** → ditunda.
5. **Aksi Salin** → disertakan.
6. **Kontrak data** → shared prop `contact`; `Route::inertia` dipertahankan.

Asumsi minor yang dipakai dan dapat dikoreksi sebelum coding:

- Allowlist host embed: `www.google.com` (path `/maps/embed...`) dan `maps.google.com`
  (path `/maps...`). Bila parish memakai bentuk lain (mis. Embed API `maps/embed/v1`),
  allowlist path dapat ditambahkan.
- `homeShowContact` diletakkan di dalam shared prop `contact` agar tidak menambah prop
  baru; alternatifnya prop terpisah dapat dipertimbangkan.
- `maps_embed_url` dirender sebagai textarea (masuk `multiline`); `maps_link` tetap
  `type="url"`.
- Formalitas agar pesan validasi peta berbahasa Indonesia.
- Tidak menambahkan `noValidate` global pada form pengaturan (lihat §5.5) untuk menjaga
  validasi native field URL lain.

---

## 10. Status

**Implemented pada branch `feat/phase-8c-contacts`.** Rencana dikerjakan dalam
urutan §3: 8C.0 → 8C.1 → 8C.2 → 8C.3 → 8C.4 → 8C.5 → 8C.6 → 8C.7 → 8C.8.
Verifikasi yang benar-benar dijalankan: `composer test` (Pint, PHPStan, 1408 Pest
test lulus), `npm run check`, `npm run types:check`, `npm run test:unit`
(348 Vitest test lulus), `npm run build`, dan `npm run e2e` (99 Playwright test
lulus). Keputusan produk dan divergensi dicatat di `docs/DECISIONS.md` D-67.
Konten produksi (alamat, telepon, email, jam, URL peta) tetap harus divalidasi
pihak paroki sebelum publikasi.

> **Amendemen §11 (satu link peta):** setelah ini, verifikasi diulang dan lulus
> dengan **1417 Pest test**, 348 Vitest test, dan 99 Playwright test.

---

## 11. Amendemen — Satu Link Peta, Iframe Diturunkan Otomatis

**Status:** Implemented · branch `feat/phase-8c-contacts`

Setelah Phase 8C selesai, pemilik proyek meminta field "URL Peta tertanam"
hilang: admin cukup menempel satu link Google Maps, dan iframe dibuatkan
otomatis. Rencana di §3 (8C.1) diamandemen sebagai berikut.

**Keputusan.**

1. Endpoint embed: `https://www.google.com/maps?q=<query>&z=<zoom>&output=embed`
   (publik, tanpa API key, tanpa permintaan jaringan dari server).
2. Kunci `maps_embed_url` **dihapus** dari `config/site-settings.php` (30 → 29).
3. Link pendek `maps.app.goo.gl` / `goo.gl` **ditolak** dengan pesan yang
   menyuruh menyalin link panjang.
4. Zoom mengikuti link (`@lat,lng,<z>z` atau `?z=`), dijepit 1–21, default 16.

**Perubahan terhadap §3/§4.**

| Semula | Menjadi |
| --- | --- |
| `MapsUrl::embed()` memvalidasi `/maps/embed` dari `embed_hosts` | `MapsUrl::embed()` membangun `?output=embed` dari `link_hosts` |
| `MapsUrl::directions()` menerima host apa pun | `directions()` hanya host Google |
| Rule `GoogleMapsEmbedUrl` | Rule `GoogleMapsLink` (pesan khusus link pendek) |
| `UpdateSettingsRequest` menormalkan `maps_embed_url` | menormalkan `maps_link` |
| `contact.mapsEmbedUrl` dari setting `maps_embed_url` | diturunkan dari `maps_link` |
| Label + hint `maps_embed_url` di form | dihapus; `maps_link` jadi "Tautan Google Maps" |
| `MapsEmbedValidationTest` | `MapsLinkValidationTest` + `MapsUrlTest` (unit) |

Bentuk kontrak `contact` (dan tipe TS `ContactDetails`) **tidak berubah**, jadi
komponen dan test Vitest tidak perlu diubah selain fixture. Keputusan lengkap
ada di `docs/DECISIONS.md` D-67.
