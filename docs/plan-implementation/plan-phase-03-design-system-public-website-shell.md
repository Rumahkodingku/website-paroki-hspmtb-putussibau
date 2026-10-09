# Implementation Plan — PHASE 03

> **Project:** Website Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB)
> Putussibau\
> **Phase:** 03 — Design System & Public Website Shell\
> **Status:** Dieksekusi — hasil di `docs/roadmap/phase-03-design-system-&-public-website-shell.md` §26 dan `docs/DECISIONS.md` D-31\
> **Spesifikasi:** `docs/roadmap/phase-03-design-system-&-public-website-shell.md`\
> **Sumber kebenaran:** `PRD.md` → `ARCHITECTURE.md` → `DESIGN.md` → `DECISIONS.md`

Dokumen ini adalah **rencana implementasi terperinci** untuk Phase 03. Dokumen
roadmap Phase 03 menjelaskan _apa_ yang harus ada; dokumen ini menjelaskan
_bagaimana_ berubahnya, dalam urutan apa, dan bagaimana diverifikasinya.

> Dokumen ini disusun setelah audit codebase dan 9 pertanyaan keputusan yang
> sudah dijawab pengguna. Seluruh rencana di sini sudah diimplementasikan pada
> branch `feat/phase-03-design-system-public-website-shell`.

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

---

# 1. Keputusan yang Sudah Diambil

Sembilan pertanyaan diajukan sebelum penyusunan plan ini. Jawaban berikut
mengikat seluruh isi dokumen.

| #   | Pertanyaan                                               | Keputusan                                                                 | Alasan yang dipakai                                                                                                                                                                                                                                                                                             |
| --- | -------------------------------------------------------- | ------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| D-1 | Penempatan showcase page (Task 03.12)                    | **Route `/design-system`** (`public/design-system`), `noindex,follow`     | Satu-satunya opsi yang memenuhi DoD §3 "public shell dapat diuji" dan §17 (E2E) tanpa melanggar PRD. Roadmap §16 menyatakan ini bukan Homepage final, dan `/` sudah dipakai `public/beranda`.                                                                                                                   |
| D-2 | Cakupan route placeholder                                | **Semua 23 route** dari PRD §5.1, termasuk route dinamis `{slug}`         | Diminta eksplisit. Konsekuensinya dicatat di [§8 R-1](#8-risiko-dan-mitigasi) dan D-31.                                                                                                                                                                                                                         |
| D-3 | Perilaku `/agenda/{slug}/ics` dan `/download/{id}/unduh` | **Respons placeholder, status 200**                                       | Bentuk respons (status + content-type) benar dan bisa diuji, tanpa tanggal/WIB/counter/nama berkas asli — jadi nol business logic sesuai §20. `abort(501)` akan menghasilkan halaman error dan bertabrakan dengan AC §10.                                                                                       |
| D-4 | Struktur halaman detail                                  | **Satu `show.tsx` per feature** (5 file)                                  | ARCHITECTURE.md Part B §4 melarang `features/a` mengimpor `features/b`; tiap fase berikutnya menghapus file miliknya sendiri tanpa menyentuh yang lain.                                                                                                                                                         |
| D-5 | Dampak XC-E3 dari route `{slug}` wildcard                | **Catat di D-31 + pin dengan test**                                       | `PublicPlaceholderRoutesTest.php` mengunci daftar route placeholder agar tidak bisa hilang diam-diam.                                                                                                                                                                                                           |
| D-6 | Sumber data kontak & sosial di footer                    | **Tetap `[ISI: …]`; hanya `parish_name` dari shared prop `seo.siteName`** | Hanya `parish_name`/`parish_short_name` punya baris (D-23/D-12). Membaca `contact_*` merender string kosong. Tidak memperluas `HandleInertiaRequests`.                                                                                                                                                          |
| D-7 | Urutan & label menu publik                               | **Ikuti DESIGN.md**, catat penyimpangan di D-31                           | DESIGN.md §576: `… Galeri, Download, Kontak` + label "Berita & Artikel" — sama dengan implementasi existing, jadi diff kecil. PRD §5.3 berbeda dan dicatat sebagai divergensi.                                                                                                                                  |
| D-8 | Lokasi shared public component                           | **`resources/js/components/` datar**                                      | `parish-navbar.tsx` dan `parish-footer.tsx` — komponen publik multi-konsumen — sudah ada di sana. Folder baru tidak menyelesaikan masalah nyata.                                                                                                                                                                |
| D-9 | Cakupan test untuk item styling-only                     | **Test kontrak saja**                                                     | Dua aturan repo bertentangan: "setiap AC harus punya test" vs "pure styling changes do not require tests". Dipilih: test untuk kontrak (route, head-key, active state, a11y, overflow, 44px, no-hardcoded-URL); pilihan visual lewat `vp check` + `tsc` + review manual, alasannya ditulis di §26 phase record. |

Nomor D-1 … D-9 di atas adalah **nomor keputusan lokal dokumen ini**, bukan
nomor `D-xx` di `docs/DECISIONS.md`. Penomoran resmi untuk Phase 03 dimulai dari
**D-31**, karena D-01 … D-30 sudah terpakai.

---

# 2. Kondisi Codebase Saat Ini

Audit dilakukan terhadap `main` pada commit `588097f` sebelum Phase 03 dimulai.

## 2.1 Yang sudah ada dan cukup — **diaudit, bukan ditulis ulang**

Roadmap §21.1 melarang menghapus lalu membuat ulang komponen yang sudah ada.
Fase 03 memakai pendekatan ini: **Audit → Refine → Complete → Integrate → Test →
Validate**, bukan rewrite.

| Task                  | Status aktual                                                                                                                                                                                                                  | File                                                                       |
| --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------- |
| 03.01 Design token    | **~95% lengkap.** 17 token tipografi, 7 token radius, palet brand/surface/text/border, mapping semantik shadcn, tema light + dark (derived dari ramp navy), layer `.rich-text`. **Tidak ada satu pun token shadow/elevation.** | `resources/css/app.css`                                                    |
| 03.02 PublicLayout    | **Lengkap.** Skip-link + `<ParishNavbar/>` + `<main id="main">` + `<ParishFooter/>`.                                                                                                                                           | `resources/js/layouts/public-layout.tsx`                                   |
| 03.05 EmptyState      | **Lengkap & generik.** Tidak ada konten feature-specific.                                                                                                                                                                      | `resources/js/components/empty-state.tsx`                                  |
| 03.05 Breadcrumbs     | **Ada**, tetapi memakai tipe admin `BreadcrumbItem[]`, `aria-label="breadcrumb"` berbahasa Inggris, dan tanpa perlakuan anti-overflow.                                                                                         | `resources/js/components/breadcrumbs.tsx`                                  |
| 03.05 Button variants | **Sudah memenuhi.** `default`(primary) · `outline`(secondary) · `secondary`(navy) · `gold` · `ghost` · `destructive` · `link` · `size=icon` (44px). Memetakan seluruh kebutuhan §9.8.                                          | `resources/js/components/ui/button.tsx`                                    |
| 03.05 Skeleton        | **Primitif tersedia.**                                                                                                                                                                                                         | `resources/js/components/ui/skeleton.tsx`                                  |
| 03.07 SEO             | **Lengkap & production-ready.** title/description/canonical/og:_/twitter:_ + fallback + `absolute()`. Head-key soulsing dengan blade. **Kurang: `robots` dan `og:locale`.**                                                    | `resources/js/components/seo.tsx` + `resources/views/app.blade.php`        |
| 03.11 Error states    | **Selesai.** 403/404/419/500/503 + fallback + CTA "Kembali ke Beranda" + tombol retry + tanpa stack trace (exception tidak pernah menyeberang ke props).                                                                       | `resources/js/features/public/error/pages/index.tsx` + `bootstrap/app.php` |

## 2.2 Yang ada tapi belum selesai — pekerjaan utama Phase 03

| Task                      | Kekurangan yang ditemukan                                                                                                                                                                                                                                                                                                                                                                       |
| ------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 03.03 `parish-navbar.tsx` | 10 menu terdaftar, tapi **9 di antaranya `<span aria-disabled>` tanpa href** karena route-nya belum ada. `Beranda` memakai `href: '/'` **hard-coded** — melanggar aturan Wayfinder. Tidak ada active state, tidak ada CTA pintasan "Jadwal Misa", `onNavigate` sudah ada sebagai parameter tapi tak pernah dipasang, logo masih `[ISI: logo paroki]` padahal `public/logo.svg` sudah di-commit. |
| 03.04 `parish-footer.tsx` | Hanya 3 grup link. **Tidak ada grup Media Sosial, tidak ada grup Kontak, tidak ada map link.** `Galeri` dan `Download` masih `href: '#'`. `Beranda` hard-coded `href: '/'`. Label legal (`Kebijakan Privasi`, `Syarat & Ketentuan`, `Kontak`) dirender sebagai `<span>` mati.                                                                                                                   |
| 03.06 `routes/web.php`    | **Hanya `/`** (`Route::inertia('/', 'public/beranda')->name('home')`). 22 route publik lain belum ada.                                                                                                                                                                                                                                                                                          |
| 03.10 Gambar              | Tidak ada komponen gambar sama sekali. `config/media.php` sudah menghasilkan varian `thumb` 400 / `medium` 960 / `large` 1600 WebP, tapi tabel `media` kosong dan tidak ada modul pemanggil (D-24).                                                                                                                                                                                             |

## 2.3 Yang belum ada dan harus dibuat

`PublicContainer` · `PublicSection` · `SectionHeading` · `ParishCTA` ·
`ParishCard` · `ParishImage` · 20 page Inertia · 22 route publik baru · 2
respons placeholder biner · 1 route showcase · 8 file test baru.

## 2.4 Aset yang sudah tersedia

| Aset             | Status                                                                                                                                            |
| ---------------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| Logo paroki      | `public/logo.svg`, `public/logo.png` — **aset asli, sudah di-commit** (commit `588097f`)                                                          |
| Favicon set      | `favicon.ico`, `favicon.svg`, `favicon-96x96.png`, `apple-touch-icon.png`                                                                         |
| Web app manifest | `site.webmanifest` + 2 ikon                                                                                                                       |
| `robots.txt`     | Sudah ada sebagai **file statis**. PRD §13 assigns `sitemap.xml` + `robots.txt` ke **Fase 4**, dan §20 Phase 03 melarangnya → **tidak disentuh**. |

## 2.5 Halaman beranda yang ada

`resources/js/features/public/beranda/pages/index.tsx` — placeholder Phase 01
dengan markup container/section/card yang **ditulis manual** (`mx-auto max-w-7xl
px-4 py-12 md:py-20`, `div` kartu). Phase 03 **merefactor** markup ini memakai
shared component, **tidak** menghapus atau mengganti halamannya. Konten `[ISI: …]`
dan `<Head title="Selamat Datang">` **tetap** — keduanya dikunci oleh
`tests/e2e/public.spec.ts:17`.

---

# 3. Kontrak yang Harus Dijaga

Bagian ini adalah daftar hal yang **bukan** pekerjaan Phase 03, melainkan
batasan yang, begitu dilanggar, akan membuat build gagal atau memicu regresi.

## 3.1 Empat hal yang harus sepakat saat menambah page

| Mengetahui                               | Mekanisme                                           | Otomatis?                 |
| ---------------------------------------- | --------------------------------------------------- | ------------------------- |
| `resources/js/app.tsx`                   | `import.meta.glob('./features/*/*/pages/**/*.tsx')` | ✅                        |
| `app/Support/PageFinder.php`             | membaca filesystem                                  | ✅                        |
| `app/Support/PageChunk.php`              | membaca filesystem                                  | ✅                        |
| **`config/inertia.php` → `pages.paths`** | **daftar literal**                                  | ❌ **WAJIB diisi manual** |

Arch test `the inertia page finder and the blade lookup find the same files`
(`tests/Unit/ArchitectureTest.php:504`) membandingkan setiap page yang ditemukan
`PageChunk::all()` dengan `config('inertia.pages.paths')`. Tanpa penambahan path,
test gagal.

## 3.2 Arch test yang akan bereaksi

| Asersi                                                       | Lokasi | Yang dilindungi                                                          |
| ------------------------------------------------------------ | ------ | ------------------------------------------------------------------------ |
| `frontend components do not import pages`                    | `:288` | komponen ≠ mengimpor `pages/`                                            |
| `shadcn primitives do not import features or pages`          | `:296` | `components/ui/` bebas features                                          |
| `layouts do not import pages or features`                    | `:304` | layout tidak boleh mengimpor halaman → akan jadi siklus dengan `app.tsx` |
| `a feature does not import another feature`                  | `:313` | features tidak boleh saling menyentuh                                    |
| `hooks and lib import nothing above them`                    | `:344` | daun dependency                                                          |
| `no frontend directory imports one that does not exist`      | `:359` | direktori di luar 9 nama yang dikenal = gagal build                      |
| `only the error page renders sanitized html on the frontend` | `:556` | **hanya** `rich-text.tsx` boleh `dangerouslySetInnerHTML`                |

## 3.3 Aturan yang paling mudah dilanggar

1. **Wayfinder grouping.** Wayfinder mengelompokkan route berdasarkan segmen
   pertama sebelum titik: `settings.edit` → `@/routes/settings` (export `edit`),
   `home` → `@/routes` (export `home`), `media.store` → `@/routes/media` (export
   `store`). **Import path harus dibaca dari output `wayfinder:generate`, bukan
   ditebak.**
2. **`usePage()` melempar tanpa konteks Inertia.**
   `node_modules/@inertiajs/react` → `throw new Error("usePage must be used
within the Inertia component")`. `Link` memakainya. Test Vitest untuk navbar /
   footer / CTA **wajib** memakai wrapper `<App initialPage={…}>` — bukan
   `vi.mock` sembarangan.
3. **`vp check` memakai `denyWarnings: true` + `typeAware: true`.** Setiap prop
   baru wajib bertipe eksplisit; `any` akan menggagalkan gate.
4. **`phpstan.neon` menganalisis `routes/` pada level 7.** Closure placeholder
   untuk `.ics` dan `/unduh` wajib bertipe return eksplisit.
5. **Formatter me-resort app.css.** `vite.config.ts` menyetel
   `fmt.sortTailwindcss.stylesheet: 'resources/css/app.css'`, jadi
   `npm run check:fix` akan me-resort `@apply`. Jalankan sekali di akhir dan
   review diff-nya.
6. **E2E mengunci dua hal.** `tests/e2e/public.spec.ts:17` →
   `toHaveTitle(/Selamat Datang/)`; `:47` → teks `404` terlihat.
7. **Inertia SSR tetap OFF** (D-26). Semua meta tag harus tetap dirender di
   `app.blade.php` supaya crawler dan pratinjau tautan membacanya.
8. **`resources/js/components/ui/*` diabaikan lint dan sengaja direstyle.**
   Tidak pernah diedit tangan, tidak pernah di-`shadcn add` ulang.

---

# 4. Ringkasan Task

| #     | Task                      | Perlu kerja?    | Output                                                        |
| ----- | ------------------------- | --------------- | ------------------------------------------------------------- |
| 03.01 | Design Token & Theme      | Ya, kecil       | 2 token shadow; konfirmasi 17 token + 7 radius; nol hex brand |
| 03.02 | Public Layout Foundation  | Sangat kecil    | Audit + komentar konvensi container                           |
| 03.03 | Parish Navbar             | **Ya, besar**   | 10 link nyata, active state, CTA, logo, Sheet                 |
| 03.04 | Parish Footer             | **Ya, besar**   | Hapus `'#'`, grup Sosial + Kontak + map, external link aman   |
| 03.05 | Shared Public Components  | **Ya, besar**   | 6 komponen baru + 2 audit                                     |
| 03.06 | Public Route & Page Shell | **Ya, besar**   | 23 route + 20 page file + 2 respons biner                     |
| 03.07 | SEO Foundation            | Ya, kecil       | `robots` + `og:locale` di dua tempat                          |
| 03.08 | Responsive Design         | Menyebar        | Ladder tipografi; verifikasi 10 viewport                      |
| 03.09 | Accessibility             | Kecil, menyebar | `aria-current`, `aria-controls`, focus, 44px                  |
| 03.10 | Image Foundation          | Ya, menengah    | `ParishImage`                                                 |
| 03.11 | Public Error States       | Nihil           | Audit; pertahankan copy & CTA                                 |
| 03.12 | Public Shell Showcase     | Ya              | `/design-system`                                              |
| 03.13 | Testing                   | **Ya, besar**   | 8 file test + 3 arch assertion + 5 spec E2E                   |
| 03.14 | Final Validation          | Nihil           | Gate + build + review + D-31 + §26                            |

---

# 5. Urutan Implementasi

Roadmap §19 menetapkan urutan dependency. Urutan di bawah mengikuti itu, dengan
satu penyesuaian: **03.05 (shared component) dikerjakan sebelum 03.02–03.04**
karena navbar, footer, dan 20 page placeholder akan mengonsumsi semuanya.
Membuatnya belakangan memaksa pekerjaan dua kali.

```text
03.01 Design Token
      ↓
03.05 Shared Public Components      ← dimajukan agar sekali pakai
      ↓
03.02 Public Layout
      ↓
03.03 Parish Navbar
      ↓
03.04 Parish Footer
      ↓
03.06 Public Routes / Page Shell
      ↓
03.07 SEO
      ↓
03.08 Responsive
      ↓
03.09 Accessibility
      ↓
03.10 Image Foundation
      ↓
03.11 Error States
      ↓
03.12 Shell Showcase
      ↓
03.13 Testing
      ↓
03.14 Final Validation
```

---

## 5.1 [03.01] Design Token & Theme

**Alasan dikerjakan:** `resources/css/app.css` sudah memenuhi hampir seluruh
syarat §5. Yang benar-benar hilang hanya satu kelompok: **elevation**.

1. **Audit** `@theme` terhadap §5 dokumen Phase 03:
    - Brand: `--color-primary-hover`, `--color-primary-focus`,
      `--color-red-on-dark`, `--color-navy*`, `--color-gold*` ✓
    - Surface: `--color-canvas-soft`, `--color-surface-pearl`,
      `--color-navy-dark` ✓
    - Text: `--color-ink`, `--color-ink-muted*` ✓
    - Borders: `--color-hairline`, `--color-divider-soft` ✓
    - Tipografi: **17 token** ✓ semua ada dan nilainya cocok
    - Radius: **7 token** ✓ (`xs 5px` … `pill 9999px`)
    - Spacing: tidak perlu token baru — D-22 §7 sudah mencatat bahwa Tailwind v4
      menghasilkan 4/8/12/16/24/32/48/80/112 persis sebagai `p-1`…`p-28`
    - **Shadow: ✗ tidak ada**
2. **Tambahkan tepat 2 token** di dalam blok `@theme`, berdampingan dengan blok
   radius:
    - `--shadow-soft-card: 0 8px 30px rgba(16, 24, 40, 0.06);`
      → utility `shadow-soft-card` (DESIGN.md "Soft card shadow")
    - `--shadow-image: 0 8px 30px rgba(0, 0, 0, 0.16);`
      → utility `shadow-image` (DESIGN.md "Image shadow")
      Hanya itu. DESIGN.md §530 melarang shadow pada setiap kartu; `shadow-xl` dan
      `drop-shadow-*` **tetap tidak dipakai** untuk permukaan publik.
3. **Normalisasi yang perlu komentar:** `--color-surface-pearl` (`#FCFCFD`) dan
   `--background` (`#FFFFFF`) memang **berbeda** di DESIGN.md — ini bukan
   duplikat. Tambahkan komentar agar pembaca berikutnya tidak mencangkanya.
4. **Ladder tipografi responsif TIDAK dibuat sebagai token baru.** §12 mensyaratkan
   `56 → 40 → 34 → 28` dan itu harus diekspresikan dengan utility responsif
   (`text-display-lg md:text-hero-display`), karena token baru dengan nilai yang
   sama persis akan menciptakan duplikat yang dilarang §5.
5. **Jangan** menambah dependency font — D-22 §6 sudah prohibitif, dan
   `laravel-vite-plugin/fonts` sudah pernah mendeklarasikan `Instrument Sans`
   yang dihapus karena tidak terpakai.
6. **Verifikasi nol hex brand di TSX:** `rg '\[#[0-9A-Fa-f]{3,8}\]' resources/js`
   harus kosong. Ini juga menjadi arch assertion baru (§10).

**File:** `resources/css/app.css` (ubah)

---

## 5.2 [03.05] Shared Public Components

**Alasan:** ini fondasi yang dipakai 20 page placeholder, navbar, footer, dan
seluruh fase berikutnya. Roadmap §25 menyebut Phase 04+ **tidak boleh** membuat
ulang navbar, footer, tipografi, button, container, section spacing, SEO, empty
state, atau responsive behavior.

**Lokasi:** `resources/js/components/` datar (keputusan D-8).

### 5.2.1 `public-container.tsx` — baru

- `max-w-7xl` (DESIGN.md "Standard application/content container")
- Padding horizontal responsif: `px-4 sm:px-6 lg:px-8`
- Variant `default | wide` untuk komposisi editorial penuh (DESIGN.md
  "Maximum content width: 1440px for full homepage compositions")
- Dipakai navbar, footer, setiap `PublicSection` yang `contained`, dan setiap page

### 5.2.2 `public-section.tsx` — baru

- Render `<section>`
- `size="default"` → `py-12 md:py-16 lg:py-20` (80px, DESIGN.md
  `spacing.section`)
- `size="large"` → `lg:py-28` (112px, DESIGN.md `spacing.section-lg`)
- `surface="default | soft | navy"` — DESIGN.md "Use surface changes before
  adding decorative UI chrome"
- `contained: boolean` — menentukan apakah `PublicContainer` dibungkus di dalam

### 5.2.3 `section-heading.tsx` — baru

- `eyebrow` → `text-caption-strong text-primary`
- `title` → `text-display-md` atau `text-display-lg`
- `description` → `text-lead`
- `action: ReactNode`
- `align="left | center"`
- `level: 2 | 3` — §13 "Correct heading hierarchy" demanding page dapat
  contended oleh komponen ini

### 5.2.4 `parish-cta.tsx` — baru

- Surface navy, `rounded-lg`, `p-12` (DESIGN.md `components.parish-cta`)
- **Satu** primary action + satu secondary opsional. §9.4: "Jangan membuat CTA
  penuh dengan banyak tombol"; DESIGN.md: "CTA should have one primary action,
  not a cluster of competing buttons."
- Copy default memakai `[ISI: …]`

### 5.2.5 `parish-card.tsx` — baru

- **Wrapper tipis di atas `ui/card.tsx`.** Jangan fork `card.tsx` — AGENTS.md
  menyatakan primitive `ui/` sengaja direstyle dan regeneration akan menghapus
  design system.
- Variant `default` (canvas + hairline) dan `agenda` (navy-light, sesuai
  `components.agenda-card`)
- Variant `interactive` — hover + focus-visible ring untuk card yang jadi link
  (§13 "Focus visible"; Don't: "Do not use hover as the only indication")

### 5.2.6 `empty-state.tsx` — **audit, ubah kecil**

Sudah generik dan benar. Dua perubahan:

1. Tambah prop `as: 'h2' | 'h3'` agar hierarki heading tetap benar ketika dipakai
   di halaman publik
2. Ganti `text-base` / `text-sm` → `text-caption-strong` / `text-body` agar ikut
   token DESIGN.md (sekarang masih ukuran Tailwind mentah)

### 5.2.7 `breadcrumbs.tsx` — **audit, ubah kecil**

Dipakai 5 halaman admin **dan** akan dipakai halaman publik → harus backward
compatible.

1. `aria-label` "breadcrumb" → Bahasa Indonesia
2. Tambah prop `className`
3. Anti-overflow: `min-w-0`, `break-words`, dan `truncate` pada item terakhir —
   §9.5 "Tidak overflow untuk judul panjang"

### 5.2.8 [03.05.7] Loading/Skeleton — **tidak membuat file baru**

`ui/skeleton.tsx` sudah tersedia. Pola card/heading/image/list dipakai inline di
page yang membutuhkan. Membuat `skeleton-card.tsx` sebelum ada 2 konsumen
melanggar §9.5 ("Buat folder hanya ketika memang ada file yang dibutuhkan") dan
anti-pattern §21.5.

### 5.2.9 [03.05.8] Button Variants — **tidak membuat apa pun**

Audit `ui/button.tsx`/svg`:`default | outline | secondary | gold | ghost |
destructive | link`+`size=icon`sudah memetakan`primary / secondary / navy /
gold / icon / utility` secara lengkap. Dicatat di D-31.

---

## 5.3 [03.02] Public Layout Foundation

**Alasan:** `public-layout.tsx` sudah benar dan lengkap. Yang perlu adalah
**keputusan yang ditulis**, bukan kode.

1. **Audit** — sudah memenuhi §6: `ParishNavbar` dipanggil ✓, `ParishFooter`
   dipanggil ✓, skip-link ✓, `<main id="main">` ✓. `<header>` dan `<footer>`
   berada di masing-masing komponen, yang juga memenuhi §13.
2. **Container bukan urusan layout.** Layout **tidak** membungkus children dalam
   `PublicContainer`, karena hero photographic harus bisa full-bleed
   (DESIGN.md "Hero: full-bleed where photography benefits from it"). Page yang
   membutuhkan Choosing constrained sendiri.
3. Tambahkan **komentar** yang menyatakan aturan ini secara eksplisit, supaya
   tidak ada yang "menolong" dengan membungkus seluruh children.
4. Pastikan layout tidak bergantung pada state admin — sudah terpenuhi, layout
   tidak membaca `auth`.
5. **Perhatikan Inertia persistent layout** (ARCHITECTURE.md Part B §3): layout
   tetap mounted lintas navigasi, sehingga navbar/footer tidak tear down. Pastikan
   perubahan tidak memutus ini.

**File:** `resources/js/layouts/public-layout.tsx` (ubah komentar saja)

---

## 5.4 [03.03] Parish Navbar

**Alasan:** ini pekerjaan substance terbesar di sisi shell. 9 dari 10 menu
saat ini **tidak bisa diklik** karena route-nya belum ada — dan Task 03.06
membuat route itu. Urutan 03.03 → 03.06 di sini memang terbalik versus roadmap,
tetapi keduanya dalam satu change sequence; setelah `wayfinder:generate` keduanya
selaras.

### Struktur

- Logo paroki (`public/logo.svg`) + wordmark
- 10 link navigasi
- Active route state (`aria-current` + red-tinted background + red rule)
- CTA pintasan "Jadwal Misa"
- Mobile menu trigger (shadcn `Sheet`)

### Perubahan

1. **Hapus `href?: string` opsional dan seluruh branch `<span aria-disabled>`**
   dari `NAV_ENTRIES`. Setelah 03.06, semua entry punya href, jadi tipe
   tersebut tidak lagi jujur. Menghapusnya juga menghapus alasan mengapa Phase
   01 menonaktifkan menu.
2. **Semua href dari Wayfinder**, bukan string. `Beranda` sekarang `href: '/'`
   hard-coded — pelanggaran langsung aturan AGENTS.md "Never hard-code app URLs".
3. **Urutan menu mengikuti DESIGN.md** (keputusan D-7):
   `Beranda · Profil · Jadwal Misa · Berita & Artikel · Agenda · Pelayanan ·
Komunitas · Galeri · Download · Kontak`
4. **Active state** memakai `useCurrentUrl()`
   (`resources/js/hooks/use-current-url.ts`):
    - `isCurrentUrl(home())` untuk Beranda
    - `isCurrentOrParentUrl(...)` untuk section, agar `/profil/sejarah` tetap
      menandai `/profil` sebagai aktif
    - Gaya aktif: `aria-current="page"` + `text-primary` +
      `bg-primary-light` (red-tinted, sesuai `components.profile-sidebar`) +
      **red rule di bawah**. Don't §: "Do not use hover as the only indication of
      interactivity" — maka hover tidak boleh satu-satunya pembeda.
5. **CTA pintasan "Jadwal Misa"** — `Button size="lg"` menuju
   `jadwal-misa.index()`, `hidden lg:inline-flex`, di sebelah kanan sebelum
   trigger Sheet. PRD §5.3 mensyaratkan tombol pintasan ini.
6. **Logo asli** menggantikan `[ISI: logo paroki]`, dengan `alt` deskriptif dan
   `width`/`height` untuk mencegah layout shift. D-31 mencatat transisi ke
   `site_settings.logo` begitu D-24 selesai.
7. **Sheet mobile:**
    - Pasang `onNavigate` pada `NavList` — parameternya sudah ada di
      `NavList({ onNavigate })` tetapi tidak pernah diberikan nilainya, sehingga
      menu tidak tertutup setelah diklik
    - `onNavigate` juga pada link desktop (tidak berbahaya, konsisten)
    - `overflow-y-auto` + `max-h` agar navigasi panjang bisa discroll (§7)
    - `SheetTitle` sudah ada ✓ — pertahankan untuk accessibility
    - **Verifikasi dulu** apakah `SheetTrigger` Radix sudah mengelola
      `aria-expanded`/`aria-controls`. §13 melarang redundant ARIA; jangan
      tambahkan yang sudah ada.

**File:** `resources/js/components/parish-navbar.tsx` (ubah)

---

## 5.5 [03.04] Parish Footer

**Alasan:** `href: '#'` adalah navigation to nowhere — pola yang paling sering
dilarang roadmap dan paling jarang disadari.

1. **Hapus `href: '/'` hard-coded dan seluruh `href: '#'`.**
2. **Tambah grup Media Sosial** dari group `sosial` di `config/site-settings.php`
   (`social_facebook`, `social_instagram`, `social_youtube`,
   `social_whatsapp_channel`).
3. **Tambah grup Kontak** — tautan ke `/kontak` + map link ke `maps_link`.
4. **Data (keputusan D-6):**
    - `parish_name` — **diambil dari shared prop `seo.siteName` yang sudah ada**.
      Ini satu-satunya nilai yang benar-benar ter-seed (PRD Lampiran D) sehingga
      aman ditampilkan sebagai data nyata.
    - `contact_*`, `social_*`, `maps_*` — **tetap `[ISI: …]`**, dan **tidak**
      merender link mati. `HandleInertiaRequests` tidak diperluas.
5. **External link** `target="_blank" rel="noopener noreferrer"`.
6. **Legal link tetap `<span>` non-link**, dicatat di D-31:
   `privacy_policy_content` memang ada sebagai setting HTML tersanitasi (D-25),
   tetapi PRD §5.1 **tidak** punya route `/kebijakan-privasi`. Membuka route itu
   berarti memasuki Fase 6. Ini gap yang ditumpuk, bukan gap Phase 03.
7. **Landmark:** `<h2 class="sr-only">Navigasi footer</h2>` agar setiap heading
   grup punya konteks yang jelas untuk screen reader.

**File:** `resources/js/components/parish-footer.tsx` (ubah)

---

## 5.6 [03.06] Public Route & Page Shell

**Alasan:** 03.03/03.04 tidak dapat diselesaikan tanpa route yang nyata.
Roadmap §10 mengizinkan placeholder route untuk kebutuhan pengujian navigasi,
dengan larangan tegas: **tidak ada** CRUD, query domain, business rules, skema
database baru, atau konten produksi palsu.

### 5.6.1 Daftar route

| URL                    | Route name            | Inertia component         | Catatan                                                                |
| ---------------------- | --------------------- | ------------------------- | ---------------------------------------------------------------------- |
| `/`                    | `home`                | `public/beranda`          | **sudah ada** — hanya direfactor                                       |
| `/profil`              | `profil.index`        | `public/profil`           |                                                                        |
| `/profil/sejarah`      | `profil.sejarah`      | `public/profil/sejarah`   |                                                                        |
| `/profil/visi-misi`    | `profil.visiMisi`     | `public/profil/visi-misi` | nama route camelCase agar export Wayfinder valid sebagai identifier JS |
| `/profil/wilayah`      | `profil.wilayah`      | `public/profil/wilayah`   |                                                                        |
| `/profil/pastor`       | `profil.pastor`       | `public/profil/pastor`    |                                                                        |
| `/profil/struktur`     | `profil.struktur`     | `public/profil/struktur`  |                                                                        |
| `/jadwal-misa`         | `jadwal-misa.index`   | `public/jadwal-misa`      |                                                                        |
| `/berita`              | `berita.index`        | `public/berita`           |                                                                        |
| `/berita/{slug}`       | `berita.show`         | `public/berita/show`      | `where('slug','[a-z0-9-]+')`                                           |
| `/agenda`              | `agenda.index`        | `public/agenda`           |                                                                        |
| `/agenda/{slug}`       | `agenda.show`         | `public/agenda/show`      |                                                                        |
| `/agenda/{slug}/ics`   | `agenda.ics`          | —                         | **respons placeholder**                                                |
| `/pelayanan`           | `pelayanan.index`     | `public/pelayanan`        |                                                                        |
| `/pelayanan/{slug}`    | `pelayanan.show`      | `public/pelayanan/show`   |                                                                        |
| `/komunitas`           | `komunitas.index`     | `public/komunitas`        |                                                                        |
| `/komunitas/{slug}`    | `komunitas.show`      | `public/komunitas/show`   |                                                                        |
| `/galeri`              | `galeri.index`        | `public/galeri`           |                                                                        |
| `/galeri/{slug}`       | `galeri.show`         | `public/galeri/show`      |                                                                        |
| `/kontak`              | `kontak.index`        | `public/kontak`           |                                                                        |
| `/download`            | `download.index`      | `public/download`         |                                                                        |
| `/download/{id}/unduh` | `download.unduh`      | —                         | **respons placeholder**                                                |
| `/design-system`       | `design-system.index` | `public/design-system`    | tambahan Phase 03 (D-1)                                                |

Penamaan route mengikuti konvensi repo yang sudah ada (`media.show`,
`settings.edit`, `profile.update`, `verification.notice`).

**`/sitemap.xml` dan `/robots.txt` TIDAK dibangun.** PRD §13 menugaskan keduanya ke
Fase 4, dan §20 Phase 03 melarangnya secara eksplisit. `public/robots.txt` sudah
ada sebagai file statis dan tidak disentuh.

### 5.6.2 Dua endpoint biner (keputusan D-3)

Closure langsung di `routes/web.php`, **tanpa controller** — karena tidak ada
orkestrasi, tidak ada query, tidak ada side effect (ARCHITECTURE.md Part A §2).

**`/agenda/{slug}/ics`**

- `Response` dengan `Content-Type: text/calendar; charset=utf-8`
- Body: `VCALENDAR` minimal (`VERSION`, `PRODID`, `CALSCALE`) + baris komentar
  `[ISI: agenda belum tersedia]`
- **Tanpa** `VTIMEZONE`, **tanpa** `VEVENT` → nol `Calendar business logic`
  (§20 melarangnya)
- Bentuk respons (status + content-type) sudah benar dan bisa diuji

**`/download/{id}/unduh`**

- `Response` `application/octet-stream` + `Content-Disposition: attachment`
- Body teks `[ISI: berkas belum tersedia]`
- **Tanpa** counter (Fase 4), **tanpa** nama berkas asli, **tanpa** path storage

Karena `phpstan.neon` menganalisis `routes/` pada level 7, kedua closure wajib
bertipe return eksplisit: `fn (string $slug): Response => …`. Gunakan
`where('id', '[0-9]+')` dan `where('slug', '[a-z0-9-]+')`.

### 5.6.3 Page placeholder — 20 file

Semua isomorphic: `SectionHeading` + `PublicSection` + `PublicContainer` + satu
kalimat `[ISI: halaman ini diimplementasikan pada Fase N]`. Halaman detail
menyebut slug-nya dalam teks supaya null slug terlihat jelas.

**Nol** query domain, **nol** props, **nol** CRUD, **nol** data produksi palsu.

```
resources/js/features/public/profil/pages/{index,sejarah,visi-misi,wilayah,pastor,struktur}.tsx   (6)
resources/js/features/public/jadwal-misa/pages/index.tsx                                          (1)
resources/js/features/public/berita/pages/{index,show}.tsx                                         (2)
resources/js/features/public/agenda/pages/{index,show}.tsx                                         (2)
resources/js/features/public/pelayanan/pages/{index,show}.tsx                                      (2)
resources/js/features/public/komunitas/pages/{index,show}.tsx                                      (2)
resources/js/features/public/galeri/pages/{index,show}.tsx                                         (2)
resources/js/features/public/kontak/pages/index.tsx                                                (1)
resources/js/features/public/download/pages/index.tsx                                              (1)
resources/js/features/public/design-system/pages/index.tsx                                         (1)
```

Menambah 20 page berarti ~20 page chunk produksi. `@vite()` pre-load satu per
render; bundle per navigasi tetap ter-code-split (NFR-PERF). Biaya hanya di build
time.

### 5.6.4 Kontrak yang harus diperbarui

**`config/inertia.php` → `pages.paths`** — tambah **10 path**:

```text
resource_path('js/features/public/profil/pages'),
resource_path('js/features/public/jadwal-misa/pages'),
resource_path('js/features/public/berita/pages'),
resource_path('js/features/public/agenda/pages'),
resource_path('js/features/public/pelayanan/pages'),
resource_path('js/features/public/komunitas/pages'),
resource_path('js/features/public/galeri/pages'),
resource_path('js/features/public/kontak/pages'),
resource_path('js/features/public/download/pages'),
resource_path('js/features/public/design-system/pages'),
```

Tanpa ini arch test `:504` gagal.

**`resources/js/app.tsx` → layout callback** diringkas:

```text
case name === 'public/error':          return PublicLayout;
case name.startsWith('public/auth/'):  return AuthLayout;    // WAJIB lebih dulu
case name.startsWith('public/'):       return PublicLayout;
```

**`public/auth/*` wajib diperiksa sebelum `public/*`.** Kalau urutannya salah,
halaman login memakai navbar publik — bug visual pada halaman auth. E2E mengunci
ini.

**`php artisan wayfinder:generate --with-form`** → baca output, lalu tulis import
di navbar/footer sesuai yang benar-benar di-generate.

### 5.6.5 Refactor beranda (bukan rewrite)

`resources/js/features/public/beranda/pages/index.tsx`:

- Ganti `mx-auto flex w-full max-w-7xl flex-col gap-12 px-4 py-12 md:py-20`
  manual → `<PublicSection><PublicContainer>`
- Ganti 4 `div` kartu manual → `<ParishCard>`
- Konten `[ISI: …]` **tetap**
- `<Head title="Selamat Datang">` **tetap** (dikunci E2E `:17`)
- Landing page final `/` dibangun di **Fase 4** (PRD §13 "Beranda: agregasi
  seluruh blok") — bukan di Phase 03

---

## 5.7 [03.07] SEO Foundation

**Alasan:** komponen SEO sudah lengkap; yang kurang hanya `robots` dan
`og:locale`, plus `noindex` untuk halaman placeholder.

1. `seo.tsx`: tambah dua head-key
    - `robots` — default `index,follow`; halaman placeholder & showcase mengirim
      `noindex,follow`
    - `og:locale` — `id_ID` (NFR-I18N)
2. **WAJIB** tambahkan padanan di `resources/views/app.blade.php`:
   `data-inertia="robots"` dan `data-inertia="og-locale"`. Tanpa itu, tag client
   tidak menggantikan tag server dan kita mendapatkan **duplikat** — persis yang
   dicegah §11 "Hindari duplicate meta tags". Head-key adalah satu-satunya
   mekanisme Inertia untuk mencocokkan kedua lapisan.
3. Favicon + site icon sudah di blade ✓
4. Title tidak ganda sudah ditangani `app.tsx` (`title: (t) => t ? … : appName`) ✓
5. **Jangan** menyentuh structured data `Organization`/`Event` — butuh data domain
6. Verifikasi "OG image fallback tersedia" — sudah ada lewat `seo.ogImage` ✓
7. "Canonical tidak menghasilkan invalid URL" — `absolute()` sudah ada ✓

**File:** `resources/js/components/seo.tsx` (ubah),
`resources/views/app.blade.php` (ubah)

---

## 5.8 [03.08] Responsive Design

Sudah-toierefactor Throughout, tapi lima item ini memerlukan tindakan eksplisit.

1. **Navbar** — `hidden lg:block` untuk nav desktop + trigger `lg:hidden`.
   Verifikasi tidak ada horizontal overflow di 360px dengan 10 menu.
2. **Footer** — `md:grid-cols-2 lg:grid-cols-5` sudah ada. Verifikasi readable di
   360px (§8 "Footer tetap readable pada 360px").
3. **Container** — `px-4 sm:px-6 lg:px-8`.
4. **Typography ladder** — `56 → 40 → 34 → 28` diekspresikan dengan utility
   responsif, **bukan** token baru.
5. **Card layout** — `3–5 kolom → 2 → 1`. `ParishCard` tidak memaksa grid; showcase
   yang mencontohkannya.
6. **CTA strategy** — `horizontal → wrapped → stacked` via `flex-wrap`.
7. **No horizontal scroll** — diverifikasi di E2E pada 360px
   (`scrollWidth <= innerWidth`).
8. **Matriks viewport** yang wajib di-review manual: 360 · 390 · 420 · 640 · 768 ·
   834 · 1024 · 1068 · 1280 · 1440.

---

## 5.9 [03.09] Accessibility

1. **Semantic HTML** — `<header>` (navbar) · `<nav aria-label="Navigasi utama">`
   di navbar **dan** Sheet · `<main id="main">` · `<section>` (PublicSection) ·
   `<footer>` · heading hierarchy benar via prop `level` pada `SectionHeading`.
2. **Keyboard** — `Link`/`Button` native. Sheet dari Radix sudah menangani focus
   trap dan Escape; **verifikasi** dan tambahkan hanya yang hilang.
3. **Focus visible** — navbar link dan footer link sudah punya
   `focus-visible:ring-2 … ring-offset-2`. Pertahankan.
4. **ARIA seperlunya** — `aria-current="page"` pada menu aktif;
   `aria-label` pada trigger dan icon-only button; `sr-only` heading grup footer.
   **Jangan** tambah `aria-expanded`/`aria-controls` yang sudah dikelola Radix.
5. **Touch target 44×44** — navbar link `h-11` (44px) ✓; footer link `min-h-11` ✓;
   trigger Sheet `size="icon"` = `size-11` ✓. Diverifikasi di Vitest
   (boundingBox) dan E2E.
6. **Alt text** — `ParishImage` mewajibkan `alt`; `''` untuk dekoratif.
7. **No keyboard trap** — Sheet hanya trap saat terbuka; menutupnya mengembalikan
   fokus.

---

## 5.10 [03.10] Image Foundation

**`resources/js/components/parish-image.tsx`** — satu-satunya abstraction gambar.
Dipakai hero, kartu berita, kartu pelayanan, dan galeri di fase berikutnya.

**Props**

| Prop               | Tipe      | Wajib  | Catatan                        |
| ------------------ | --------- | ------ | ------------------------------ |
| `src`              | `string`  | ya     |                                |
| `alt`              | `string`  | **ya** | `''` berarti dekoratif (XC-M5) |
| `width` / `height` | `number`  | ya     | mencegah layout shift (XC-M4)  |
| `srcSet`           | `string`  | tidak  | diisi **pemanggil**            |
| `sizes`            | `string`  | tidak  |                                |
| `aspectRatio`      | `string`  | tidak  | default `16/9`                 |
| `priority`         | `boolean` | tidak  | `true` → `loading="eager"`     |
| `className`        | `string`  | tidak  |                                |

**Perilaku**

- `priority` → `loading="eager" fetchPriority="high" decoding="sync"` (DESIGN.md
  "Above-the-fold hero imagery should load eagerly")
- default → `loading="lazy"` (XC-M4)
- `style={{ aspectRatio }}` inline → tidak ada layout shift
- `overflow-hidden` + `object-cover` → tidak overflow container
- **Fallback error state**: blok navy-light + ikon. **Tanpa
  `dangerouslySetInnerHTML`** — arch test `:556` melarangnya di luar
  `rich-text.tsx`

**Nol kontrak backend di phase ini.** `config/media.php` sudah menghasilkan varian
`thumb` 400 / `medium` 960 / `large` 1600 WebP, tetapi tabel `media` kosong dan
tidak ada modul pemanggil (D-24). Jadi `srcSet` **diisi pemanggil**; jangan buat
URL builder PHP sekarang.

**Kontrak rasio didokumentasikan di docblock:** `16/9` hero · `4/3` kartu berita ·
`4/5` potret pastor/pengurus (PRD §6.2 "Rasio 3:4 atau 1:1") · `1/1` kartu
komunitas.

**Yang TIDAK dilakukan** (§14 "Jangan lakukan"): Gallery feature · lightbox ·
batch upload · image management admin.

---

## 5.11 [03.11] Public Error States

**Audit saja.** Halaman error sudah memenuhi §15 sepenuhnya:

- 403 / 404 / 419 / 500 / 503 + fallback untuk status tak dikenal
- PublicLayout (dipetakan di `app.tsx`)
- Copy Bahasa Indonesia, CTA "Kembali ke Beranda", tombol retry untuk status
  yang mungkin sudah selesai sendiri
- Tanpa stack trace — exception tidak pernah menyeberang ke props
- `respond()` hanya aktif saat `config('app.debug')` false

**Pastikan tidak rusak:** E2E `:47` mengunci teks `404`. Tutuplah handle.

---

## 5.12 [03.12] Public Shell Showcase

Route `/design-system` (keputusan D-1), feature
`resources/js/features/public/design-system/`, `noindex,follow`.

Struktur mengikuti §16:

```text
┌──────────────────────────────────────┐
│ ParishNavbar                         │
├──────────────────────────────────────┤
│ Reference Hero                       │
│ "HSPMTB Design System Preview"       │
│ CTA primer + sekunder                │
├──────────────────────────────────────┤
│ SectionHeading (+ eyebrow/align)     │
│ ParishCard  ParishCard  ParishCard   │  ← termasuk varian `agenda`
├──────────────────────────────────────┤
│ Baris semua varian Button            │
├──────────────────────────────────────┤
│ Breadcrumbs                          │
├──────────────────────────────────────┤
│ EmptyState                           │
├──────────────────────────────────────┤
│ Skeleton: card / heading / image / list
├──────────────────────────────────────┤
│ ParishImage  (priority + lazy)       │
├──────────────────────────────────────┤
│ ParishCTA                            │
├──────────────────────────────────────┤
│ ParishFooter                         │
└──────────────────────────────────────┘
```

Copy memakai "Design System Preview" / "Sample Content" / `[ISI: …]`.
**Tidak ada data produksi.**

---

## 5.13 [03.13] Testing

Frontend test runner **sudah tersedia** (Vitest via `vp test`), jadi opsi
"Jika frontend test runner belum tersedia" di §17 tidak dipakai.

### 5.13.1 `tests/js/support/inertia.tsx` — helper baru (WAJIB, dibuat pertama)

`renderWithInertia(ui)` membungkus anak dalam `<App initialPage={…}
resolveComponent={…}>` dari `@/ertiajs/react`.

**Alasan:** `usePage()` melempar `Error: usePage must be used within the Inertia
component` di luar konteks, dan `Link` memakainya. Tanpa helper ini,
`render(<ParishNavbar/>)` gagal — dan memock `@inertiajs/react` justru membuat
test menguji mock, bukan komponen.

### 5.13.2 Test komponen Vitest

| File                                           | Cakupan                                                                                                                                                            |
| ---------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `tests/js/components/parish-navbar.test.tsx`   | 10 menu render; setiap menu punya href yang benar; `aria-current="page"` pada route aktif; sub-route menandai parent; Sheet buka / tutup / `Escape`; trigger ≥44px |
| `tests/js/components/parish-footer.test.tsx`   | semua link punya href valid; **nol `href="#"`**; nama paroki dari `seo.siteName`; grup Sosmed & Kontak ada                                                         |
| `tests/js/components/parish-cta.test.tsx`      | render, satu primary action, opsional secondary                                                                                                                    |
| `tests/js/components/empty-state.test.tsx`     | title/description/action; heading level benar                                                                                                                      |
| `tests/js/components/section-heading.test.tsx` | eyebrow/title/description/action; level 2 vs 3                                                                                                                     |

### 5.13.3 Test backend Pest

**`tests/Feature/Public/PublicRoutesTest.php`**

- Setiap route mengembalikan Inertia component yang benar
- Route publik **tidak** butuh auth
- `/halaman-yang-tidak-ada` tetap 404
- `/admin` tetap menolak tamu
- `/design-system` mengembalikan `robots` = `noindex,follow`
- Placeholder page mengembalikan `noindex,follow`

**`tests/Feature/Public/PublicPlaceholderRoutesTest.php`** — **pin D-31**

- Daftar route placeholder dikunci eksplisit (kadaluarsa saat fase modulnya tiba)
- `.ics` mengembalikan `text/calendar` dengan body placeholder
- `/unduh` mengembalikan `attachment`
- `/berita/{slug}` di luar constraint (`/berita/Berita%20Penting`) → 404

### 5.13.4 E2E `tests/e2e/public.spec.ts` — tambahan

- `/` → navbar terlihat → buka mobile menu → Profil → `/profil` → PublicLayout
  masih ada
- `/` → footer terlihat → Kontak → `/kontak`
- `/admin/login` **tetpa tanpa** navbar publik
- 360px: `document.documentElement.scrollWidth <= window.innerWidth`
- trigger Sheet `boundingBox()` ≥ 44×44

### 5.13.5 Arch assertion baru — `tests/Unit/ArchitectureTest.php`

| Assertion                                                                                   | Yang dilindungi                                                                 |
| ------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------- |
| Navbar & footer tidak boleh memuat literal `href: '/'` atau `href: '#'`                     | Aturan Wayfinder AGENTS.md; AC §10 "Navigation tidak menghasilkan broken route" |
| Tidak ada hex brand (`\[#[0-9A-Fa-f]{3,8}\]`) di `resources/js/**`                          | §21.3, DESIGN.md "Avoid arbitrary values such as `bg-[#AB020E]`"                |
| Himpunan `head-key` di `seo.tsx` **sama dengan** himpunan `data-inertia` di `app.blade.php` | NFR-SEO; mekanisme dua lapisan D-26                                             |

### 5.13.6 Yang **tidak** di-automate (keputusan D-9)

Item styling-only: pemilihan token, radius, shadow, ladder tipografi responsif,
kontras warna. Diverifikasi lewat `npm run check`, `npm run types:check`, dan
review visual manual. **Alasannya ditulis eksplisit di §26 phase record** —
ini rekonsiliasi dua aturan repo yang bertentangan.

---

## 5.14 [03.14] Final Validation

1. `composer ci:check` hijau
2. `npm run build` sukses
3. `npm run e2e` hijau
4. Review manual responsif 10 viewport
5. Review keyboard + contrast
6. **Tulis `docs/DECISIONS.md` D-31** dengan isi pada §13 dokumen ini
7. **Isi §26** `docs/roadmap/phase-03-*.md` — termasuk alasan item styling-only
   tidak di-automate
8. Update status di `docs/roadmap/README.md`
9. `AGENTS.md` **tidak** disentuh tanpa persetujuan eksplisit

---

# 6. File yang Akan Terpengaruhi

## 6.1 Dibuat — 6 shared component

```text
resources/js/components/public-container.tsx
resources/js/components/public-section.tsx
resources/js/components/section-heading.tsx
resources/js/components/parish-cta.tsx
resources/js/components/parish-card.tsx
resources/js/components/parish-image.tsx
```

## 6.2 Dibuat — 20 page Inertia

```text
resources/js/features/public/profil/pages/{index,sejarah,visi-misi,wilayah,pastor,struktur}.tsx   (6)
resources/js/features/public/jadwal-misa/pages/index.tsx                                          (1)
resources/js/features/public/berita/pages/{index,show}.tsx                                         (2)
resources/js/features/public/agenda/pages/{index,show}.tsx                                         (2)
resources/js/features/public/pelayanan/pages/{index,show}.tsx                                      (2)
resources/js/features/public/komunitas/pages/{index,show}.tsx                                      (2)
resources/js/features/public/galeri/pages/{index,show}.tsx                                         (2)
resources/js/features/public/kontak/pages/index.tsx                                                (1)
resources/js/features/public/download/pages/index.tsx                                              (1)
resources/js/features/public/design-system/pages/index.tsx                                         (1)
```

## 6.3 Dibuat — 8 file test

```text
tests/js/support/inertia.tsx
tests/js/components/parish-navbar.test.tsx
tests/js/components/parish-footer.test.tsx
tests/js/components/parish-cta.test.tsx
tests/js/components/empty-state.test.tsx
tests/js/components/section-heading.test.tsx
tests/Feature/Public/PublicRoutesTest.php
tests/Feature/Public/PublicPlaceholderRoutesTest.php
```

## 6.4 Diubah

```text
resources/css/app.css                                  + --shadow-soft-card / --shadow-image, komentar normalisasi
resources/js/components/parish-navbar.tsx              href Wayfinder, active state, CTA, logo, Sheet onNavigate
resources/js/components/parish-footer.tsx              link nyata, grup Sosial + Kontak + map, hapus '#'
resources/js/components/empty-state.tsx                prop level heading + token typography
resources/js/components/breadcrumbs.tsx                aria-label ID, className, anti-overflow (backward compatible)
resources/js/components/seo.tsx                        + robots + og:locale
resources/views/app.blade.php                          + data-inertia="robots" + data-inertia="og-locale"  [WAJIB sinkron]
resources/js/features/public/beranda/pages/index.tsx   container/section/card manual → shared component
routes/web.php                                         blok Public Routes → 23 route placeholder bernama
config/inertia.php                                     + 10 path pages.paths
resources/js/app.tsx                                   layout callback → public/* = PublicLayout (auth/* didahulukan)
tests/e2e/public.spec.ts                               + 5 spec
tests/Unit/ArchitectureTest.php                        + 3 assertion kontrak
docs/DECISIONS.md                                      + D-31
docs/roadmap/README.md                                 status Phase 1/2 → done, Phase 3 → current
docs/roadmap/phase-03-design-system-&-public-website-shell.md   §26 Phase Completion Record
```

## 6.5 Tidak disentuh — sengaja

| Path                                                                                                                     | Alasan                                                                                                                |
| ------------------------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------- |
| `resources/js/components/ui/**`                                                                                          | Generated + **restyled on purpose**. AGENTS.md: regenerasi menghapus design system. Lint-ignored.                     |
| `resources/js/{actions,routes,wayfinder}/**`                                                                             | Generated. Jangan diedit tangan.                                                                                      |
| `public/logo.*`, `favicon*`, `robots.txt`                                                                                | Sudah ada. `robots.txt` = Fase 4.                                                                                     |
| `resources/js/components/heading.tsx`                                                                                    | Dipakai 5 halaman **admin**. Tokennya di luar scope "public UI". Mengubahnya = unrelated refactoring, dilarang §21.1. |
| `resources/js/layouts/{admin,auth}/**`                                                                                   | Di luar scope Phase 03.                                                                                               |
| `app/Models/**`, `database/**`, migrations                                                                               | **Nol** schema change di Phase 03.                                                                                    |
| `resources/js/features/public/error/pages/index.tsx`                                                                     | Sudah memenuhi §15.                                                                                                   |
| `config/site-settings.php`, `config/media.php`                                                                           | Kunci & varian sudah cukup.                                                                                           |
| `phpstan.neon`, `tsconfig.json`, `vite.config.ts`, `playwright.config.ts`, `composer.json`, `package.json`, `lang/id/**` | Tidak ada yang perlu diubah.                                                                                          |
| `AGENTS.md`                                                                                                              | Instruksi — hanya dengan persetujuan eksplisit.                                                                       |

## 6.6 Dihapus

**Tidak ada file yang dihapus.** Yang dihapus hanya **di dalam file**:

- `parish-navbar.tsx`: tipe `href?: string` opsional + seluruh branch
  `<span aria-disabled>`
- `parish-footer.tsx`: `href: '/'` hard-coded + `href: '#'`

---

# 7. Dependency / Configuration Impact

| Item                                                            | Dampak                                                                                                                                                                                                                                                                            |
| --------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Dependency npm**                                              | **Tidak ada satu pun ditambahkan.** Semua sudah ada: `lucide-react`, `class-variance-authority`, `@radix-ui/react-*`, `ui/sheet.tsx`, `ui/skeleton.tsx`, `ui/card.tsx`, `ui/button.tsx`. Larangan §21.2 + D-22 berlaku. `package.json` dan `package-lock.json` **tidak berubah**. |
| **`config/inertia.php`**                                        | **Wajib diubah** — 10 path. Tanpa ini arch test `:504` gagal.                                                                                                                                                                                                                     |
| **`routes/web.php`**                                            | 23 route baru → wajib `php artisan wayfinder:generate --with-form` setelahnya.                                                                                                                                                                                                    |
| **`resources/views/app.blade.php`**                             | 2 meta tag. Lupa = tag duplikat = regresi NFR-SEO.                                                                                                                                                                                                                                |
| **`resources/js/app.tsx`**                                      | Layout mapping; urutan `switch` bersifat load-bearing.                                                                                                                                                                                                                            |
| **`resources/css/app.css`**                                     | 2 token. `vp check:fix` akan me-resort `@apply` — review diff.                                                                                                                                                                                                                    |
| **`phpstan.neon`**                                              | Tidak diubah. Tapi `routes/` masuk `paths`, jadi closure placeholder wajib bertipe return eksplisit.                                                                                                                                                                              |
| **`config/media.php`**                                          | Tidak diubah. Varian 400/960/1600 cukup untuk `srcSet` nanti.                                                                                                                                                                                                                     |
| **`config/site-settings.php`**                                  | Tidak diubah. Footer hanya membaca `parish_name` via `seo.siteName`.                                                                                                                                                                                                              |
| **`config/inertia.php` `ssr.enabled`**                          | Tetap `false` (D-26).                                                                                                                                                                                                                                                             |
| **`vite.config.ts` / `tsconfig.json` / `playwright.config.ts`** | Tidak diubah. `tests/js/**` sudah masuk `tsconfig.json` `include`.                                                                                                                                                                                                                |
| **`composer.json` / `package.json`**                            | Tidak diubah.                                                                                                                                                                                                                                                                     |

---

# 8. Risiko dan Mitigasi

| #        | Risiko                                                                                                                                                                                       | Dampak                                     | Mitigasi                                                                                                                                                                                                                                                                    |
| -------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **R-1**  | **XC-E3 belum terpenuhi.** 10 route detail mengembalikan **200 untuk slug valid apa pun** (keputusan D-2 + D-5). PRD XC-E3 (MUST): draf / nonaktif / belum terbit harus **404** bagi publik. | Penyimpensi sementara terhadap satu `MUST` | D-31 mendeklarasikan 10 route ini **temporary** dan wajib diganti controller + model binding di fase modulnya. `where('slug','[a-z0-9-]+')` + `where('id','[0-9]+')` memperketat. `PublicPlaceholderRoutesTest` meng-pin daftarnya. **Menunggu konfirmasi tertulis — §13.** |
| **R-2**  | `case name.startsWith('public/')` di `app.tsx` bisa menelan `public/auth/*` → halaman login dapat navbar publik.                                                                             | Bug visual auth                            | `public/auth/*` wajib dicek **lebih dulu**; E2E `/admin/login` mengunci layout-nya.                                                                                                                                                                                         |
| **R-3**  | **Test Vitest yang render `<Link>` melempar** `usePage must be used within the Inertia component`.                                                                                           | Test suite hijau tapi salah                | `tests/js/support/inertia.tsx` dengan `<App initialPage>`. Risiko implementasi **paling mungkin** untuk undershoot.                                                                                                                                                         |
| **R-4**  | `tests/e2e/public.spec.ts:17` mengunci `toHaveTitle(/Selamat Datang/)`.                                                                                                                      | CI merah                                   | Judul beranda tidak diubah.                                                                                                                                                                                                                                                 |
| **R-5**  | Wayfinder export path untuk `profil.index` → entah `@/routes/profil` export `index`, atau `@/routes/profil/index` — **belum diverifikasi.**                                                  | Import salah                               | Baca output `wayfinder:generate` setelah generate, baru tulis import. Jangan ditebak.                                                                                                                                                                                       |
| **R-6**  | `vp check` memakai `denyWarnings: true` + `typeAware: true`.                                                                                                                                 | Gate gagal                                 | Semua prop bertipe eksplisit; nol `any`.                                                                                                                                                                                                                                    |
| **R-7**  | 20 page baru = ~20 page chunk produksi.                                                                                                                                                      | Build time naik sedikit                    | Diterima — Inertia code-split per halaman (NFR-PERF). Bundle per navigasi tetap kecil.                                                                                                                                                                                      |
| **R-8**  | **Dua sumber kebenaran untuk logo**: `public/logo.svg` (dipakai navbar) vs `site_settings.logo` (masih text input, D-24).                                                                    | Inkonsistensi visual                       | D-31 mencatat transisi wajib begitu D-24 selesai.                                                                                                                                                                                                                           |
| **R-9**  | `aria-expanded`/`aria-controls` mungkin sudah diurus `SheetTrigger` Radix. Menambah manual = redundant ARIA, dilarang §13.                                                                   | ARIA ganda                                 | Verifikasi DOM Radix dulu; tambahkan hanya yang hilang.                                                                                                                                                                                                                     |
| **R-10** | **20 file placeholder** pasti akan dihapus total di Fase 2–4. Risiko utama: terlambat dihapus dan menjadi technical debt.                                                                    | Technical debt                             | `PublicPlaceholderRoutesTest` meng-pin daftar → tidak bisa hilang diam-diam.                                                                                                                                                                                                |
| **R-11** | **Urutan menu** masih menyimpang dari PRD §5.3 (keputusan D-7).                                                                                                                              | Divergensi terdokumentasi                  | D-31.                                                                                                                                                                                                                                                                       |
| **R-12** | `AGENTS.md` masih menyebut "Current: Phase 1" dan Known gaps "No frontend visual review has been done yet".                                                                                  | Dokumen stale                              | **Tidak** disentuh tanpa persetujuan eksplisit pengguna.                                                                                                                                                                                                                    |
| **R-13** | Arch test `no two features claim the same page name` / `no frontend directory imports one that does not exist`.                                                                              | Test merah                                 | Nama feature unik; semua import `@/components/...`.                                                                                                                                                                                                                         |
| **R-14** | `/design-system` satu-satunya route di luar PRD §5.1 dan akan **ship ke produksi**.                                                                                                          | Route publik tak terencana                 | D-31 + `noindex,follow`. Menghilangkannya di produksi berarti E2E/Pest harus di-skip saat env bukan `local`, yang menurunkan cakupan pengujian.                                                                                                                             |
| **R-15** | `resources/js/features/public/beranda/` vs `resources/js/features/public/berita/` — nama berdekatan dan mudah tertukar.                                                                      | Salah import                               | Arch test `a feature does not import another feature` sudah melindungi. Tetap baca output `wayfinder` dengan hati-hati.                                                                                                                                                     |
| **R-16** | `vp check:fix` me-resort `@apply` di `app.css`, menghasilkan diff yang tidak terkait dengan 2 token.                                                                                         | Review membingungkan                       | Jalankan sekali di akhir; review `git diff resources/css/app.css` secara terpisah.                                                                                                                                                                                          |

---

# 9. Verification Plan

```bash
# 0. Prasyarat — test DB harus hidup (RefreshDatabase menjalankan migrate:fresh)
#    CREATE DATABASE website_paroki_hspmtb_test CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
#    (AGENTS.md — buat sekali per mesin; Lihat D-03)

# 1. Wayfinder — WAJIB setelah routes berubah
php artisan wayfinder:generate --with-form
php artisan route:list --except-vendor      # 23 route publik ada, tidak ada duplikat nama

# 2. Gate penuh = yang CI jalankan
composer ci:check
#   → npm run check         oxlint type-aware, denyWarnings + formatter
#   → npm run types:check   tsc --noEmit, mencakup tests/js & tests/e2e
#   → php artisan test      Pest: Feature + Unit, termasuk ArchitectureTest

# 3. Frontend unit — iterate di sini
npm run test:unit
npm run test:unit -- tests/js/components/parish-navbar.test.tsx

# 4. Test yang paling sering gagal di phase ini
php artisan test tests/Feature/Public/
php artisan test tests/Unit/ArchitectureTest.php
php artisan test tests/Feature/Foundation/ErrorPageTest.php
php artisan test tests/Feature/Auth/AdminAccessTest.php

# 5. Production build
npm run build
#   → tidak ada "Unable to locate file in Vite manifest", tidak ada unresolved import

# 6. E2E alur kritis (butuh MySQL test DB)
npm run e2e
#   → app:e2e:prepare (migrate:fresh di DB e2e) + Playwright chromium
#   → JANGAN hard-code kredensial/database di spec (D-29)

# 7. Review manual (wajib, tidak bisa diotomasi)
composer dev        # atau: npm run dev
#   viewport : 360 390 420 640 768 834 1024 1068 1280 1440
#   Console  : nol error/warning baru sesudah Phase 03
#   DevTools : document.documentElement.scrollWidth <= window.innerWidth  (tiap viewport)
#   DevTools : Accessibility tree → aria-current pada menu aktif; aria-label pada trigger Sheet
#   Keyboard : Tab-through seluruh navbar & Sheet; focus ring terlihat; tidak ada keyboard trap
#   Lighthouse: contrast, document-title, meta-description, tap targets

# 8. Verifikasi SEO head-key soulsing (dua tempat, satu perubahan)
curl -s http://127.0.0.1:8000/profil | grep -o 'data-inertia="[^"]*"' | sort | uniq -d   # harus kosong
curl -s http://127.0.0.1:8000/galeri | grep -c 'name="description"'                  # harus 1
curl -s http://127.0.0.1:8000/design-system | grep -o 'name="robots" content="[^"]*"'  # noindex,follow

# 9. Review diff app.css secara terpisah (R-16)
git diff -- resources/css/app.css

# 10. Arch assertion manual (sementara, sebelum test ditulis)
rg '\[#[0-9A-Fa-f]{3,8}\]' resources/js        # harus kosong
rg "href: '#'" resources/js                     # harus kosong
```

---

# 10. Test Matrix

| Acceptance Criteria                                            | Sumber                | Test                                                                                    |
| -------------------------------------------------------------- | --------------------- | --------------------------------------------------------------------------------------- |
| Homepage mengembalikan Inertia component                       | §17                   | `PublicRoutesTest.php`                                                                  |
| Public placeholder route mengembalikan component benar         | §17                   | `PublicRoutesTest.php`                                                                  |
| Public route tidak butuh admin auth                            | §17                   | `PublicRoutesTest.php` + `AdminAccessTest.php` (existing)                               |
| Route tak dikenal tetap 404                                    | §17                   | `PublicRoutesTest.php` + `ErrorPageTest.php` (existing)                                 |
| Admin route tetap terlindungi                                  | §17                   | `AdminAccessTest.php` (existing — harus tetap hijau)                                    |
| Public shell tidak merusak auth                                | §17                   | E2E `/admin/login`                                                                      |
| Navbar render (10 menu)                                        | §17                   | `parish-navbar.test.tsx`                                                                |
| Active navigation bekerja                                      | §17                   | `parish-navbar.test.tsx`                                                                |
| Mobile Sheet buka                                              | §17                   | `parish-navbar.test.tsx`                                                                |
| Mobile Sheet tutup                                             | §17                   | `parish-navbar.test.tsx`                                                                |
| Escape menutup Sheet                                           | §17                   | `parish-navbar.test.tsx`                                                                |
| Footer render link                                             | §17                   | `parish-footer.test.tsx`                                                                |
| CTA render                                                     | §17                   | `parish-cta.test.tsx`                                                                   |
| Empty state render                                             | §17                   | `empty-state.test.tsx`                                                                  |
| SEO component render metadata yang benar                       | §17                   | `PublicRoutesTest.php` (head-key + robots)                                              |
| Tidak ada regresi a11y pada kontrol custom                     | §17                   | `parish-navbar.test.tsx` (`aria-current`, 44px) + E2E                                   |
| Alur kritis `/` → mobile menu → `/profil`                      | §17                   | E2E                                                                                     |
| Alur kritis `/` → footer → `/kontak`                           | §17                   | E2E                                                                                     |
| Nol scroll horizontal 360px                                    | NFR-RESP              | E2E `scrollWidth <= innerWidth`                                                         |
| Touch target ≥ 44×44                                           | §17 · NFR-RESP        | Vitest `boundingBox` + E2E                                                              |
| `.ics` & `/unduh` bentuk respons                               | §10                   | `PublicPlaceholderRoutesTest.php`                                                       |
| 10 route placeholder tidak hilang diam-diam                    | D-31                  | `PublicPlaceholderRoutesTest.php`                                                       |
| Nol hard-coded URL di nav/footer                               | §10 AC                | Arch assertion baru                                                                     |
| Nol hex brand di TSX                                           | §21.3                 | Arch assertion baru                                                                     |
| `head-key` ↔ `data-inertia` sinkron                            | NFR-SEO               | Arch assertion baru                                                                     |
| Design token / radius / shadow / tipografi responsif / kontras | §18.1 · §18.5 · §18.6 | **Tidak di-automate** (D-9) — `vp check` + `tsc` + review manual; alasan ditulis di §26 |
| Production build sukses                                        | §18.8                 | `npm run build`                                                                         |
| Nol console error baru                                         | §18.8                 | Review manual + E2E response listener                                                   |

**Jumlah:** 8 file test baru, 5 spec E2E baru, 3 arch assertion baru.

---

# 11. Acceptance Criteria

## 11.1 Roadmap §3 — Definition of Done (21 item)

20 terpenuhi otomatis atau lewat review manual. Dua item needing catatan:

- _"Public shell dapat diuji pada 360, 390, 768, 1024, 1280, dan 1440px"_ —
  review manual + `scrollWidth` check di 360px; dicatat di §26
- _"Test yang relevan hijau"_ — `composer ci:check` + `npm run e2e`

## 11.2 Roadmap §24 — Final Phase Acceptance

| Bagian                        | Kriteria                                                                                                                                                                                                                 | Status                          |
| ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | ------------------------------- |
| **A. Design**                 | Mengikuti DESIGN.md · Red/Navy/Gold sesuai hierarchy · White/soft-neutral dominan · Nol decorative gradient · Shadow restrained · Tipografi & spacing konsisten                                                          | ✅                              |
| **B. Architecture**           | Nol React Router · Laravel tetap application router · Inertia tetap bridge · Shared component tidak bergantung pada feature · Nol unrelated refactoring · Nol premature abstraction · Feature-oriented structure terjaga | ✅                              |
| **C. Public UX**              | Navbar desktop & mobile · Footer desktop & mobile · Public layout · Shared public components · Placeholder public routes                                                                                                 | ✅                              |
| **D. Accessibility**          | Semantic HTML · Keyboard accessible · Visible focus · Correct ARIA · 44×44px · Reasonable contrast                                                                                                                       | ✅ (44px diverifikasi otomatis) |
| **E. Performance Foundation** | Responsive image strategy · Hero eager · Below-fold lazy · No unnecessary client-side fetching · No unnecessary heavy dependency · No obvious layout shift                                                               | ✅                              |
| **F. Quality**                | Tests pass · TypeScript passes · Lint passes · Production build succeeds · No runtime errors · No browser console errors                                                                                                 | ✅                              |

## 11.3 Traceability PRD

| Requirement                                                       | Dimenuhi di mana                                              |
| ----------------------------------------------------------------- | ------------------------------------------------------------- |
| `XC-E1` Empty state                                               | `EmptyState` dipakai halaman publik                           |
| `XC-E2` 404/500 Bahasa Indonesia + layout publik + tautan beranda | Dipertahankan (`features/public/error`, `ErrorPageTest`, E2E) |
| `XC-E3` 404 untuk draf / belum terbit                             | **Ditunda** — lihat §12 dan R-1                               |
| `XC-M4` lazy + width/height + srcset                              | `ParishImage`                                                 |
| `XC-M5` alt text wajib                                            | `ParishImage` (`alt` wajib)                                   |
| `XC-P4` kebijakan privasi singkat di footer                       | **Parsial** — label ada, route tidak (Fase 6)                 |
| `XC-T2` · `NFR-I18N` format & bahasa Indonesia                    | Seluruh copy                                                  |
| `NFR-RESP` 360px · 44px · nol scroll horizontal                   | E2E + Vitest                                                  |
| `NFR-A11Y` kontras AA · alt · keyboard · Esc                      | `ParishImage` · Sheet · `aria-current`                        |
| `NFR-SEO` title + description per halaman · OG + Twitter          | `seo.tsx` + `app.blade.php`                                   |
| `6.8` token terpusat · kontras AA                                 | `app.css`                                                     |

---

# 12. Yang Sengaja Tidak Dikerjakan

| Item                                                                                                                                            | Alasan                                                             | Pemilik                  |
| ----------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------ | ------------------------ |
| **`XC-E3` penuh** (404 untuk konten draf/tidak terbit)                                                                                          | Route placeholder `{slug}` mengembalikan 200 sementara (R-1, D-31) | Fase masing-masing modul |
| **`sitemap.xml` + `robots.txt` otomatis**                                                                                                       | PRD §13 assigns ke Fase 4; §20 Phase 03 melarang                   | Fase 4                   |
| **Structured data `Organization` / `Event`**                                                                                                    | Butuh data domain                                                  | Fase 2 / 3               |
| **Halaman `/kebijakan-privasi`**                                                                                                                | Butuh konten yang disetujui paroki; tidak ada di PRD §5.1          | Fase 6                   |
| **Landing page final `/`**                                                                                                                      | PRD §13 "Beranda: agregasi seluruh blok"                           | Fase 4                   |
| **Hero slider, bar pengumuman, ringkasan misa**                                                                                                 | `hero_slides` / `announcements` belum ada                          | Fase 4                   |
| **Jadwal Misa, Berita, Agenda, Pelayanan, Komunitas, Galeri, Kontak, Download**                                                                 | §20 Out of Scope                                                   | Fase 2–4                 |
| **Homepage aggregation / admin dashboard analytics**                                                                                            | §20 Out of Scope                                                   | Fase 4                   |
| **SSR**                                                                                                                                         | D-26 — membutuhkan Node daemon di produksi                         | Fase UI/produksi         |
| **Image picker untuk `logo` / `favicon` / `og_image`**                                                                                          | D-24                                                               | Fase Upload              |
| **`ProfileSubNav`, `ParishTimeline`, `PastorCard`, `ParishGallery`, `MassScheduleCard`, `AgendaCard`, `SacramentServiceCard`, `CommunityCard`** | Komponen spesifik domain; butuh data domain                        | Fase 2–3                 |
| **Lightbox · batch upload**                                                                                                                     | §14 "Jangan lakukan"                                               | Fase 4                   |
| **Update `AGENTS.md`**                                                                                                                          | Instruksi; menunggu persetujuan eksplisit                          | —                        |

---

# 13. Keputusan yang Masih Menunggu Konfirmasi

## 13.1 Konfirmasi tertulis untuk D-31

Pilihan "semua 23 route" (D-2) + "catat di D-31" (D-5) berarti Phase 03
**sengaja dan sadar** menunda satu `MUST` PRD:

> **XC-E3 (MUST):** konten berstatus draf, nonaktif, atau belum waktunya terbit
> **mengembalikan 404** bagi publik walau URL diketahui.

Selama 10 route detail placeholder hidup, `/berita/slug-apa-saja-tidak-ada`
mengembalikan **200**, bukan 404.

**Usulan bunyi D-31** (akan ditulis di `docs/DECISIONS.md`):

```text
## D-31 — Public website shell Phase 03, dan 10 route sementara

**Status:** Accepted · berlaku sejak P03 · branch feat/phase-03-design-system

### 1. Route publik dibangun sebagai placeholder

23 route PRD §5.1 dibuat sebagai placeholder tanpa query, props, atau CRUD.
Menambah sub-halaman profil, halaman detail `{slug}` per fitur (bukan satu
komponen bersama), dan respons placeholder untuk `.ics` serta `/unduh`.

### 2. XC-E3 ditunda, bukan dianggap selesai

Selama route detail placeholder hidup, `/berita/{slug}` mengembalikan 200 untuk
slug valid apa pun. PRD XC-E3 (MUST) mensyaratkan 404. Ini **penundaan
bertanggal**, bukan ketidaksengaja:

| Route | Fase yang menggantinya |
| --- | --- |
| berita.show | Fase 2 |
| agenda.show, agenda.ics | Fase 2 |
| pelayanan.show | Fase 3 |
| komunitas.show | Fase 3 |
| galeri.show | Fase 4 |

Setiap penggantian HARUS memakai route model binding, sehingga slug yang tidak
ada kembali 404 secara otomatis. `PublicPlaceholderRoutesTest` meng-pin daftar
ini supaya tidak bisa hilang diam-diam.

### 3. `.ics` dan `/unduh` mengembalikan placeholder, bukan 501

`/agenda/{slug}/ics` mengembalikan VCALENDAR minimal tanpa VTIMEZONE/VEVENT.
`/download/{id}/unduh` mengembalikan body teks tanpa counter dan tanpa nama
berkas. Bentuk respons (status + content-type) benar agar bisa diuji; logika
domain (WIB, kalender, counter) tetap milik fase masing-masing.

### 4. `/design-system` adalah route di luar PRD §5.1

Satu-satunya route yang tidak ada di PRD. `noindex,follow`. Akan dihapus ketika
public shell sudah stabil.

### 5. Urutan menu mengikuti DESIGN.md, bukan PRD §5.3

DESIGN.md §576: `… Galeri, Download, Kontak`, label "Berita & Artikel".
PRD §5.3: `… Galeri, Kontak, Download`, label "Berita". Implementasi mengikuti
DESIGN.md karena itu yang sudah ada. Divergensi dicatat, bukan diperbaiki.

### 6. Footer memakai placeholder, bukan site settings

Hanya `parish_name` yang dirender dari shared prop `seo.siteName`, karena itu
satu-satunya nilai yang ter-seed (Lampiran D). `contact_*`, `social_*`, `maps_*`
tetap `[ISI: …]` sampai paroki mengisinya. `HandleInertiaRequests` tidak
diperluas.

### 7. Logo navbar memakai aset statis

`public/logo.svg`, bukan `site_settings.logo`, karena image picker D-24 belum
ada. Saat D-24 selesai, navbar harus pindah ke setting.

### 8. Kebutuhan variant Button sudah terpenuhi

`ui/button.tsx` sudah memetakan primary/secondary/navy/gold/icon/utility.
Tidak ada primitive baru.

### 9. Gap yang ditumpuk, bukan diselesaikan Phase 03

- Footer legal link: label ada, route `/kebijakan-privasi` tidak ada di PRD §5.1
- `sitemap.xml` + `robots.txt`: PRD §13 assigns ke Fase 4
- Item styling-only (token, radius, shadow, tipografi responsif, kontras) tidak
  di-automate — `tests rules` AGENTS.md menyatakan perubahan styling murni tidak
  memerlukan test. Dipilih atas "setiap AC harus punya test".
```

**Yang perlu dijawab sebelum coding:**

> Apakah Anda menerima D-31 dengan bunyi di atas — khususnya pengakuan terbuka
> bahwa **XC-E3 (MUST) ditunda** sampai tiap modul menggantikan route
> placeholder-nya?

Alternatif jika tidak diterima: 10 route detail tetap dibangun, tetapi memakai
**slug whitelisted** (`contoh-judul`), sehingga `/berita/apasaja` → 404 dan XC-E3
terpenuhi sejak sekarang. Navbar/footer tetap aman karena tidak pernah menautkan
URL detail. **Konsekuensinya:** E2E tidak bisa menguji navigasi ke halaman detail,
dan coverage pengujian halaman detail berkurang.

---

# 14. Status

```text
Phase:
PHASE 03 — Design System & Public Website Shell

Status:
[x] Plan disusun
[x] Keputusan diambil (9 pertanyaan)
[x] Implementasi
[x] Test
[x] Validasi akhir

Keputusan yang sudah diambil:
D-1  Showcase di route /design-system
D-2  Seluruh 23 route placeholder dibangun
D-3  .ics dan /unduh = respons placeholder status 200
D-4  Satu show.tsx per feature
D-5  XC-E3 dicatat di D-31 + dipin dengan test
D-6  Footer: [ISI: …], hanya parish_name dari seo.siteName
D-7  Urutan & label menu mengikuti DESIGN.md
D-8  Shared component di components/ datar
D-9  Test kontrak saja untuk item styling-only

Keputusan yang menunggu:
(nihil — D-31 sudah ditulis di docs/DECISIONS.md, termasuk pengakuan terbuka
atas XC-E3 yang ditunda dan bug halaman-error-tanpa-shared-props yang ditemukan
saat implementasi)

Berkas dokumen ini:
docs/plan-implementation/phase-03-design-system-public-website-shell.md
```

> **Dokumen ini adalah rencana, bukan implementasi. Belum ada kode yang ditulis.**
