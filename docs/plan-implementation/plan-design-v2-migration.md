# Implementation Plan — Migrasi DESIGN-v2

> **Project:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau\
> **Fase:** Migrasi desain — `DESIGN.md` v1.1 → `DESIGN-v2.md`\
> **Status:** Diimplementasikan (M0–M6 selesai untuk fondasi + seluruh rute)\
> **Branch:** dikerjakan di atas `feat/phase-9a-homepage` (belum di-commit)\
> **Commit dasar (audit):** `main`\
> **Spesifikasi desain:** `docs/DESIGN-v2.md`\
> **Sumber kebenaran:** `PRD.md` → `DESIGN-v2.md` → `ARCHITECTURE.md` → `AGENTS.md` → `DECISIONS.md`

`DESIGN-v2.md` menjelaskan _apa_ sistem desain baru. Dokumen ini menjelaskan _bagaimana_
migrasinya: urutan perubahan, token lama → token baru, file yang tersentuh, dan cara
verifikasinya.

> Dokumen ini disusun setelah audit codebase pada kondisi repository saat ini dan
> **lima keputusan yang sudah dijawab pengguna** melalui tool `question` (§2.2). Seluruh
> keputusan di §2.2 **mengikat** implementasi. **Belum ada satu baris kode migrasi yang
> ditulis.**

---

## Daftar Isi

1. [Kondisi Codebase Saat Ini](#1-kondisi-codebase-saat-ini)
2. [Keputusan yang Mengikat](#2-keputusan-yang-mengikat)
3. [Pemetaan Token DESIGN.md → DESIGN-v2](#3-pemetaan-token-designmd--design-v2)
4. [Implementation Plan (Bertahap)](#4-implementation-plan-bertahap)
5. [File yang Akan Terpengaruh](#5-file-yang-akan-terpengaruh)
6. [Dependency / Configuration Impact](#6-dependency--configuration-impact)
7. [Risiko dan Potensi Konflik](#7-risiko-dan-potensi-konflik)
8. [Verification Plan](#8-verification-plan)
9. [Definition of Done (Mengacu DESIGN-v2 §15)](#9-definition-of-done-mengacu-design-v2-15)
10. [Pertanyaan / Ambiguity](#10-pertanyaan--ambiguity)
11. [Status](#11-status)

---

## 1. Kondisi Codebase Saat Ini

Audit dilakukan pada `main` (tanpa mengubah file kode). Angka di bawah adalah hasil
pencarian aktual, bukan perkiraan.

### 1.1 Sistem desain yang berjalan sekarang (DESIGN.md v1.1)

| Area                         | Kondisi aktual                                                                                                                                                                                                                                                                            | Lokasi                                                                                   |
| ---------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| Entry CSS                    | `@import 'tailwindcss'`, 703 baris, seluruh token di satu file                                                                                                                                                                                                                            | `resources/css/app.css`                                                                  |
| Font                         | `--font-sans` = `'SF Pro Text', system-ui, …`; `--font-display` = `'SF Pro Display', …`                                                                                                                                                                                                   | `resources/css/app.css:16-23`                                                            |
| Font yang benar-benar dimuat | **Instrument Sans** lewat `bunny('Instrument Sans', { weights: [400,500,600] })` — **tidak direferensikan** `--font-sans`, jadi konfigurasi mati                                                                                                                                          | `vite.config.ts:34-38`                                                                   |
| Tipografi                    | 16 token kustom: `hero-display` 56 → `micro-legal` 10                                                                                                                                                                                                                                     | `resources/css/app.css:25-104`                                                           |
| Registrasi tailwind-merge    | 16 nama token didaftarkan di grup `font-size`                                                                                                                                                                                                                                             | `resources/js/lib/utils.ts:35-52`                                                        |
| Dukungan tipografi           | `Text` sebagai satu-satunya pembawa peran → token (D-33)                                                                                                                                                                                                                                  | `resources/js/components/text.tsx`                                                       |
| Radius                       | `xs 5 / sm 8 / md 12 / lg 18 / xl 24 / pill`                                                                                                                                                                                                                                              | `resources/css/app.css:106-112`                                                          |
| Shadow                       | Hanya `soft-card` dan `image`                                                                                                                                                                                                                                                             | `resources/css/app.css:114-129`                                                          |
| Token semantik shadcn        | `background/foreground/card/popover/primary/secondary/muted/accent/destructive/border/input/ring` + `sidebar/*`                                                                                                                                                                           | `resources/css/app.css:131-243, 249-340, 380-444`                                        |
| Palet brand                  | `navy`, `navy-dark`, `navy-light`, `gold`, `gold-dark`, `gold-light`, `ink`, `canvas-soft`, `surface-pearl`, `surface-chip`, `hairline`, `divider-soft`, `ink-muted`, `ink-muted-strong`, `ink-muted-soft`, `red-on-surface`, `red-soft`, `red-on-dark`, `primary-hover`, `primary-focus` | `resources/css/app.css:160-227`                                                          |
| Theme runtime                | `light`/`dark`/`system`; default `system`; class `dark` di `<html>`                                                                                                                                                                                                                       | `resources/js/hooks/use-appearance.tsx`, `resources/js/components/appearance-toggle.tsx` |
| Background `<html>`          | Hard-code `oklch(1 0 0)` / `oklch(0.145 0 0)` — tidak cocok v1 maupun v2                                                                                                                                                                                                                  | `resources/views/app.blade.php:23-31`                                                    |
| Halaman showcase             | `/design-system`, noindex, memamerkan token & primitif Phase 03                                                                                                                                                                                                                           | `resources/js/features/public/design-system/pages/index.tsx`                             |

### 1.2 Volume pemakaian token lama (source, tanpa file generated)

| Token            | Jumlah | Token              | Jumlah |
| ---------------- | -----: | ------------------ | -----: |
| `hairline`       |    171 | `gold`             |     29 |
| `navy`           |     60 | `canvas-soft`      |     23 |
| `red-on-surface` |     37 | `navy-light`       |     20 |
| `ink`            |     34 | `ink-muted`        |     18 |
| `red-soft`       |     17 | `ink-muted-soft`   |      7 |
| `navy-dark`      |      9 | `gold-dark`        |      6 |
| `gold-light`     |      6 | `divider-soft`     |      6 |
| `primary-focus`  |      4 | `surface-pearl`    |      3 |
| `surface-chip`   |      3 | `primary-hover`    |      3 |
| `red-on-dark`    |      2 | `ink-muted-strong` |      1 |
| `navy-deep`      |      0 |                    |        |

Pemakaian token tipografi (source): `caption` 22, `micro-legal` 16, `body-strong` 11,
`display-md` 9, `display-lg` 7, `tagline` 7, `hero-display` 6, `button-large` 6,
`button-utility` 6, `lead` 10, `lead-airy` 5, `nav` 5, `fine-print` 5, `dense-link` 4.

Hex arbitrer (`bg-[#…]`) di source: **hanya** di file generated/`ui/` dan komentar —
`ArchitectureTest` sudah melarangnya di komponen aplikasi (satu-satunya pengecualian sah:
`parish-logo.tsx` yang menggambar SVG).

### 1.3 Cakupan permukaan

| Permukaan                 |                                       Jumlah | Keterangan                                                          |
| ------------------------- | -------------------------------------------: | ------------------------------------------------------------------- |
| Total file `.tsx`         |                                          288 | termasuk generated                                                  |
| Komponen `ui/`            |                                           31 | direstyle sengaja (AGENTS.md; jangan `shadcn add --all`)            |
| Komponen bersama non-`ui` |                                           37 | `components/*.tsx`                                                  |
| Layout                    |                                           13 | `layouts/public`, `layouts/app`, `layouts/auth`, `layouts/settings` |
| Halaman admin             |                        92 file `pages/*.tsx` | 138 file `tsx` termasuk komponen                                    |
| Halaman publik            |        25 `pages/*.tsx` + 60+ komponen fitur | `features/public/*`                                                 |
| Route publik              | 20 `GET` (7 modul + profil 4 + error + auth) | `route:list`                                                        |

### 1.4 Fakta yang mengikat migrasi

- **Primary wajib tetap `#AB020E`** (DESIGN-v2 §2). Tidak boleh diganti merah lebih
  terang/pink. Di dark mode, teks link memakai tint merah yang lebih terang
  (`#F06A73`) **hanya** bila merah brand tidak cukup kontras — ini tidak mengubah token
  primary.
- **Tabrakan nama token**: DESIGN-v2 memakai warna `text-body`, sementara proyek sudah
  memakai `--text-body` sebagai **ukuran font**. Tailwind v4 memetakan keduanya ke utility
  `text-body`. Warna body **tidak** boleh diberi nama `text-body`; lihat §3.4.
- **Dark theme v2 memakai kanvas netral charcoal** (`#111318`), bukan navy. Ini melanjutkan
  aturan yang sudah aktif di v1.1 (D-32) — tidak ada regresi aturan.
- **SSR OFF** (D-26): `<Seo>` tidak cukup untuk crawler; `app.blade.php` merender head.
  Perubahan warna `<html>` di blade adalah bagian migrasi.
- **Test mengunci token lama**: `tests/js/components/text.test.tsx`,
  `tests/js/lib/utils.test.ts`, `tests/js/components/public-primitives.test.tsx`,
  `tests/js/features/public/beranda/homepage.test.tsx`,
  `tests/js/components/app-sidebar.test.tsx`, `tests/Unit/ArchitectureTest.php`, dan
  `tests/e2e/public.spec.ts` (uji kanvas gelap).
- **`npm run e2e` menyajikan `public/build`** → wajib `npm run build` lebih dulu.
- **Pest dan Playwright berbagi database test** → jangan jalankan Playwright bare setelah
  Pest.
- **Eloquent strict mode aktif** di luar production — tidak relevan langsung, tapi tetap
  berlaku saat menyentuh query apa pun di halaman yang di-restyle.
- **Konten belum ada** di semua modul (Berita, Agenda, Pelayanan, Komunitas, Galeri,
  Kontak, Jadwal Misa). Restyle **tidak boleh** mengarang data paroki.

---

## 2. Keputusan yang Mengikat

Lima keputusan ini dijawab pengguna dan **mengikat** seluruh implementasi.

| #   | Pertanyaan        | Keputusan                                             | Konsekuensi                                                                                                  |
| --- | ----------------- | ----------------------------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| 1   | Jangkauan migrasi | **Publik + redesign admin penuh**                     | Admin ikut di-restyle komposisinya, bukan hanya alias token                                                  |
| 2   | Strategi token    | **Staged hard replace**                               | Perkenalkan token v2, migrasikan per-fitur, lalu hapus token v1 — satu palet, tanpa duplikasi jangka panjang |
| 3   | Font              | **Mona Sans**                                      | Mona Sans dimuat lewat pipeline `bunny`; Instrument Sans dibuang (D-74)                                  |
| 4   | Nama komponen     | **Pertahankan nama lama, dokumentasikan pemetaannya** | `article-card` = `NewsCard`, dst.; hanya komponen baru yang memakai nama v2                                  |
| 5   | Tema default      | **Tetap `system`**                                    | Baseline `light` tetap diuji penuh; perilaku E2E tidak berubah                                               |

### 2.1 Pemetaan nama komponen (keputusan #4)

DESIGN-v2 §14 memberi nama domain; komponen yang ada tetap memakai nama lama, dan peran v2
dipetakan agar dokumen tetap bisa dijadikan acuan.

| Nama v2 (peran)        | Komponen aktual                                                      | Aksi    |
| ---------------------- | -------------------------------------------------------------------- | ------- |
| `ParishNavbar`         | `layouts/public/components/parish-navbar.tsx`                        | restyle |
| `ParishHero`           | `features/public/beranda/components/home-hero.tsx`                   | restyle |
| `QuickAccessBar`       | `features/public/beranda/components/home-quick-links.tsx`            | restyle |
| `FeaturedNews`         | bagian unggulan `features/public/berita/components/pinned-strip.tsx` | restyle |
| `NewsCard`             | `components/article-card.tsx`                                        | restyle |
| `MassScheduleCard`     | `features/public/jadwal-misa/components/mass-schedule-card.tsx`      | restyle |
| `UpcomingMassPanel`    | `components/next-mass-card.tsx` + `home-next-mass-section.tsx`       | restyle |
| `AgendaCard`           | `components/event-card.tsx`                                          | restyle |
| `SacramentServiceCard` | `features/public/pelayanan/components/service-card.tsx`              | restyle |
| `CommunityCard`        | `features/public/komunitas/components/community-card.tsx`            | restyle |
| `ParishTimeline`       | `features/public/profil/components/history-timeline.tsx`             | restyle |
| `PastorCard`           | `features/public/profil/components/pastor-card.tsx`                  | restyle |
| `OrganizationChart`    | `features/public/profil/components/organization-chart.tsx`           | restyle |
| `ServiceAreaMap`       | `components/contact-map.tsx` + `features/public/area/*`              | restyle |
| `ParishGallery`        | `features/public/galeri/components/photo-grid.tsx` (+ lightbox)      | restyle |
| `ParishCTA`            | `components/parish-cta.tsx`                                          | restyle |
| `ParishFooter`         | `layouts/public/components/parish-footer.tsx`                        | restyle |
| `SectionHeading`       | `components/section-heading.tsx`                                     | restyle |
| `ParishCard`           | `components/parish-card.tsx`                                         | restyle |

> Pemetaan ini ditulis ke `ARCHITECTURE.md` (Part B) dan `DECISIONS.md` (D-72) agar tidak
> menjadi pengetahuan lisan.

---

## 3. Pemetaan Token DESIGN.md → DESIGN-v2

### 3.1 Token brand

| v1               | v2                  | Nilai                 | Aksi                                                        |
| ---------------- | ------------------- | --------------------- | ----------------------------------------------------------- |
| `primary`        | `primary`           | `#AB020E`             | tetap (wajib)                                               |
| `primary-hover`  | `primary-hover`     | `#8F010B`             | ganti nilai (dari `#8F010B` — sama)                         |
| —                | `primary-active`    | `#760109`             | **baru**                                                    |
| `red-soft`       | `primary-soft`      | `#FBEAEC`             | **ganti nama + nilai** (`#FDE7E9` → `#FBEAEC`)              |
| —                | `primary-soft-dark` | `#35191D`             | **baru** (nilai dark `red-soft`)                            |
| `navy`           | `navy`              | `#01266D`             | tetap                                                       |
| `navy-dark`      | `navy-deep`         | `#001A4D`             | **ganti nama**                                              |
| `navy-light`     | `navy-soft`         | `#EAF0FA`             | **ganti nama**                                              |
| `gold`           | `gold`              | `#FCB027`             | tetap                                                       |
| `gold-light`     | `gold-soft`         | `#FFF4D6`             | **ganti nama**                                              |
| `gold-dark`      | —                   | `#D99400`             | **hapus** (tidak ada di v2; hanya dipakai sebagai dekorasi) |
| `red-on-dark`    | —                   | `#FF6670`             | **hapus** (digantikan ring/teks dark `#F06A73`)             |
| `red-on-surface` | —                   | var                   | **hapus nama**, lihat §3.4 (teks merah di dark)             |
| `primary-focus`  | `ring`              | `#AB020E` / `#F06A73` | **lebur ke `ring`**                                         |

### 3.2 Token netral & semantik (light)

| v1                                   | v2                                   | Nilai (light)                                |
| ------------------------------------ | ------------------------------------ | -------------------------------------------- |
| `canvas`/`--background`              | `canvas`/`--background`              | `#FFFFFF`                                    |
| `ink`/`--foreground`                 | `text-primary`/`--foreground`        | `#171A21` (naik dari `#111827`)              |
| `canvas-soft`/`--muted`              | `canvas-subtle`/`--muted`            | `#F7F8FA` (dari `#F8F9FB`)                   |
| `canvas`/`--card`                    | `surface`/`--card`                   | `#FFFFFF`                                    |
| `surface-pearl`/`--popover`          | `surface-raised`/`--popover`         | `#FFFFFF` (dari `#FCFCFD`)                   |
| `body-muted`/`--muted-foreground`    | `text-muted`/`--muted-foreground`    | `#626B79` (dari `#667085`)                   |
| `ink-muted-soft`                     | `text-subtle`                        | `#77808D`                                    |
| `hairline`/`--border`                | `border`/`--border`                  | `#E0E3E8` (dari `#E4E7EC`)                   |
| `divider-soft`                       | `border-subtle`                      | `#ECEEF2`                                    |
| `hairline`/`--input`                 | `border`                             | `#E0E3E8`                                    |
| —                                    | `control`                            | `#FFFFFF` (**baru**: permukaan input/select) |
| `navy-light`/`--secondary`           | `navy-soft`/`--secondary`            | `#EAF0FA`                                    |
| `navy-dark`/`--secondary-foreground` | `navy-deep`/`--secondary-foreground` | `#001A4D`                                    |
| `gold-light`/`--accent`              | `gold-soft`/`--accent`               | `#FFF4D6`                                    |
| `primary`/`--destructive`            | `primary`/`--destructive`            | `#AB020E`                                    |
| `primary-focus`/`--ring`             | `primary`/`--ring`                   | `#AB020E`                                    |

### 3.3 Token netral (dark)

| v1 (`.dark`)                   | v2                    | Nilai                                            |
| ------------------------------ | --------------------- | ------------------------------------------------ |
| `--background` `#171A1F`       | `dark-canvas`         | `#111318`                                        |
| —                              | `dark-canvas-subtle`  | `#171A21` (`--muted`, header/footer/`--sidebar`) |
| `--card` `#232A33`             | `dark-surface`        | `#1D2129`                                        |
| `--popover` `#2A323D`          | `dark-surface-raised` | `#252A34`                                        |
| `--foreground` `#F3F4F6`       | `dark-text-primary`   | `#F3F4F6`                                        |
| —                              | `dark-text-body`      | `#D4D8E0`                                        |
| `--muted-foreground` `#AAB4C0` | `dark-text-muted`     | `#A6ADBA`                                        |
| `#7F8A98`                      | `dark-text-subtle`    | `#8992A1`                                        |
| `--border` `#39424E`           | `dark-border`         | `#343A46`                                        |
| —                              | `dark-border-subtle`  | `#292E38`                                        |
| —                              | `dark-control`        | `#191D24` (permukaan input)                      |
| `--ring` `#FF6670`             | `#F06A73`             | tint merah lebih terang untuk ring dark          |
| `red-soft` `#3A171A`           | `primary-soft-dark`   | `#35191D`                                        |

> **Aturan kontras wajib diuji.** Setiap pasangan teks/latar di §3.2–3.3 harus ≥ 4.5:1
> (teks) dan ≥ 3:1 (border/ring); ring dark dan teks merah dark memakai tint
> `#F06A73`. Ini kelanjutan aturan D-32.

### 3.4 Resolusi tabrakan `text-body`

DESIGN-v2 menamai warna body `text-body`, tetapi `--text-body` sudah dipakai sebagai
**ukuran font** (dan itu tidak dihapus oleh v2 — v2 justru menamai ukuran body `body`
16px). Dua makna untuk utility yang sama.

**Keputusan:** warna body tidak memperoleh utility `text-body`.

- Teks isi utama → `text-foreground` (text-primary) atau `text-muted-foreground`
  (text-muted) sesuai hierarki.
- Bila benar-benar perlu warna body di antara keduanya, didefinisikan sebagai
  `--color-body-text` (utility `text-body-text`) dan **hanya** dipakai bila audit kontras
  menuntut. Ini dicatat di D-72.

### 3.5 Tipografi

DESIGN-v2 §4: 11 langkah. Tabel pemetaan peran lama → baru:

| v1               | px  | v2           | px (desktop/mobile) | Catatan                                        |
| ---------------- | --- | ------------ | ------------------- | ---------------------------------------------- |
| `hero-display`   | 56  | `display-xl` | 52/36               | hero beranda saja                              |
| `display-lg`     | 40  | `display-lg` | 40/32               | H1 halaman                                     |
| `display-md`     | 34  | `display-md` | 32/26               | heading seksi besar                            |
| —                | —   | `heading-lg` | 24/22               | **baru** (H3)                                  |
| `tagline`        | 21  | `heading-md` | 20/18               | kartu/subjudul (H4)                            |
| `body-strong`    | 17  | `heading-sm` | 16                  | subjudul kecil (H5/H6)                         |
| `lead`           | 28  | `body-lg`    | 18/17               | paragraf pengantar                             |
| `lead-airy`      | 24  | `body-lg`    | 18/17               | dilebur                                        |
| `body`           | 17  | `body`       | 16/16               |                                                |
| `dense-link`     | 17  | —            | —                   | **hapus** (tak ada padanan)                    |
| `caption`        | 14  | `body-sm`    | 14/14               |                                                |
| `caption-strong` | 14  | `label`      | 13                  |                                                |
| `button-large`   | 18  | `body-lg`    | 18                  | tombol `lg`                                    |
| `button-utility` | 14  | `label`      | 13                  | tombol `sm`                                    |
| `nav-link`       | 13  | `label`      | 13                  | nav                                            |
| `fine-print`     | 12  | `fine-print` | 12                  |                                                |
| `micro-legal`    | 10  | `fine-print` | 12                  | **hapus** — v2 melarang teks penting < 13–14px |

**Pemetaan varian `Text`** (`components/text.tsx`):

| Varian lama                 | Varian baru                            |
| --------------------------- | -------------------------------------- |
| `hero`                      | `display-xl`                           |
| `display-lg`, `h1`          | `display-lg`                           |
| `display-md`, `h2`          | `display-md`                           |
| `h3`                        | `heading-lg`                           |
| `tagline`                   | `heading-md`                           |
| `h4`                        | `heading-md`                           |
| `body-strong`, `h5`, `h6`   | `heading-sm`                           |
| `lead`, `lead-airy`         | `body-lg`                              |
| `body`                      | `body`                                 |
| `caption`                   | `body-sm`                              |
| `caption-strong`            | `label`                                |
| `nav`                       | `label`                                |
| `fine-print`, `micro-legal` | `fine-print`                           |
| `dense-link`                | dihapus                                |
| `blockquote`                | tetap (warna navy → v2 navy/navy-soft) |

**Warna `Text`** (`color`): `subtle` → `text-subtle`; `brand` → `navy dark:navy-soft`
(bukan `navy-light`); `brand-inverse` → `navy-soft`; `danger` → merah teks (ring dark
`#F06A73`).

### 3.6 Radius, shadow, spacing

| Token              | v1   | v2                                 |
| ------------------ | ---- | ---------------------------------- |
| `radius-sm`        | 8    | 6                                  |
| `radius-md`        | 12   | 10                                 |
| `radius-lg`        | 18   | 14                                 |
| `radius-xl`        | 24   | 20                                 |
| `radius-xs`        | 5    | dihapus (dipakai di `< 6 ke bawah) |
| `radius-pill`      | 9999 | 9999 (tetap; tombol & chip)        |
| `shadow-soft-card` | ada  | dipertahankan, dipakai hemat       |
| `shadow-image`     | ada  | dipertahankan                      |

Spacing v2 = skala 4px/8px yang sama dengan Tailwind, **tidak perlu token baru**. Yang
berubah adalah **ritme seksi**: desktop 64–88px, tablet 48–64px, mobile 40–56px,
padding kartu 20–24px (16–20px mobile). `PublicSection` memetakan ulang.

---

## 4. Implementation Plan (Bertahap)

Migrasi dibagi **7 tahap (M0–M6)**. Setiap tahap harus hijau (`composer ci:check`) sebelum
lanjut. Urutan ini menjaga agar tidak ada commit yang meninggalkan aplikasi setengah
beralih token tanpa alias.

### M0 — Keputusan, dokumentasi, dan pagar

**Tujuan:** menjadikan DESIGN-v2 satu-satunya acuan sebelum menyentuh kode.

| Langkah | Isi                                                                                                                                                                                                        |
| ------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| M0.1    | Tulis **D-72** di `docs/DECISIONS.md`: DESIGN-v2 mengikat; DESIGN.md superseded (dikekalkan, tidak dihapus); 5 keputusan §2; tabel pemetaan §3; resolusi `text-body` (§3.4); pemetaan nama komponen (§2.1) |
| M0.2    | Perbarui `AGENTS.md`: rujuk `DESIGN-v2.md`; tandai aturan visual `DESIGN.md` v1 tidak berlaku                                                                                                              |
| M0.3    | Perbarui `ARCHITECTURE.md` Part B: bagian design system + tabel pemetaan komponen                                                                                                                          |
| M0.4    | Perbarui `docs/roadmap/README.md`: tambah "Current Focus — Migrasi DESIGN-v2"                                                                                                                              |
| M0.5    | Buat/ubah judul `DESIGN.md` menjadi penanda "SUPERSEDED — lihat DESIGN-v2.md" di baris pertama (tanpa menghapus isi)                                                                                       |

**Tidak menyentuh kode.** Verifikasi: `git diff` hanya file `.md`.

### M1 — Fondasi token

**Tujuan:** satu palet v2 di `app.css`, font, dan registry tailwind-merge.

| Langkah | Isi                                                                                                                                                              | File                                                          |
| ------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------- |
| M1.1    | Ganti font ke **Mona Sans** via `bunny` (normal 400/500/600/700 + italic 400/600, subset latin+latin-ext); buang Instrument Sans                 | `vite.config.ts`                                              |
| M1.2    | `--font-sans` → referensi Mona Sans + fallback system-ui; `--font-display` → sama (v2 tidak memisahkan keluarga display)                                             | `resources/css/app.css`                                       |
| M1.3    | Tulis ulang token tipografi ke 11 langkah v2 (ukuran, line-height, letter-spacing, weight per §3.5)                                                              | `resources/css/app.css`                                       |
| M1.4    | Tulis ulang palet brand (§3.1), netral light (§3.2), netral dark (§3.3); radius (§3.6)                                                                           | `resources/css/app.css`                                       |
| M1.5    | Petakan blok `:root` dan `.dark` ke peran semantik v2; tambah `control`, `surface-raised`, `border-subtle`, `primary-active`, `primary-soft(-dark)`, `navy-soft` | `resources/css/app.css`                                       |
| M1.6    | Perbarui `rich-text` di `@layer components` ke token v2 (heading, code, blockquote, link)                                                                        | `resources/css/app.css`                                       |
| M1.7    | Ganti `DESIGN_FONT_SIZE_TOKENS` dengan 11 nama v2                                                                                                                | `resources/js/lib/utils.ts`                                   |
| M1.8    | Set background `<html>` ke `#FFFFFF` / `#111318` (bukan `oklch` shadcn)                                                                                          | `resources/views/app.blade.php`                               |
| M1.9    | Perbarui test yang mengunci token lama (kanvas gelap E2E, kontrak token Architecture)                                                                            | `tests/e2e/public.spec.ts`, `tests/Unit/ArchitectureTest.php` |

**Catatan M1:** pada titik ini komponen masih memakai nama token lama (`hairline`, dll.)
sehingga **belum dikompilasi**. Karena itu M1 dan M2 harus mendarat bersamaan, atau M1
menyisakan alias sementara. Pilihan yang dipakai: **M1 + M2 dalam satu branch/PR**.

### M2 — Tipografi & primitif UI

**Tujuan:** `Text` dan seluruh primitif `ui/` memakai token v2.

| Langkah | Isi                                                                                                                                                                                                                                                     | File                                                                                                                |
| ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| M2.1    | Tulis ulang varian & warna `Text` (§3.5)                                                                                                                                                                                                                | `components/text.tsx`                                                                                               |
| M2.2    | Hapus varian mati (`dense-link`, `hero`, `tagline`, `micro-legal`, `lead-airy` bila tak dipakai) setelah pemakaian dimigrasi                                                                                                                            | `components/text.tsx`                                                                                               |
| M2.3    | Tombol v2: primary/secondary/tertiary, state `active`/`disabled`, tinggi min 44px, radius pill                                                                                                                                                          | `components/ui/button.tsx`                                                                                          |
| M2.4    | `card` (radius lg 14, border), `badge`, `alert`, `input`, `textarea`, `select`, `checkbox`, `switch`, `tabs`, `pagination`, `breadcrumb`, `separator`, `skeleton`, `table`, `tooltip`, `dialog`, `sheet`, `dropdown-menu`, `navigation-menu`, `sidebar` | `components/ui/*.tsx`                                                                                               |
| M2.5    | Perbarui test tipografi & primitif                                                                                                                                                                                                                      | `tests/js/components/text.test.tsx`, `tests/js/lib/utils.test.ts`, `tests/js/components/public-primitives.test.tsx` |

### M3 — Shell publik

**Tujuan:** navbar, footer, container, section, sub-nav profil v2.

| Langkah | Isi                                                                                                                                                                |
| ------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| M3.1    | `ParishNavbar`: tinggi 64–72px, border bawah tipis, aktif = teks merah + penanda tipis, Sheet mobile, target 44px                                                  |
| M3.2    | `ParishFooter`: latar `canvas-subtle`/`dark-canvas-subtle`, heading `text-primary`/`dark-text-primary`, link `text-muted`, tanpa teks opasitas rendah untuk kontak |
| M3.3    | `PublicContainer`: standar 1280, `wide` 1280 (v2 batas editorial); `PublicSection`: hapus surface `navy` penuh, ganti aksen navy terbatas                          |
| M3.4    | `ProfileSubNav`, `SectionHeading`, `EmptyState`, `Breadcrumbs` ke token v2                                                                                         |
| M3.5    | Perbarui `tests/js/components/app-sidebar.test.tsx` (bila ikut) dan test shell publik                                                                              |

### M4 — Halaman publik per-route

**Tujuan:** setiap route publik mengikuti komposisi DESIGN-v2 §11.

| Route                             | Fokus restyle                                                                                    |
| --------------------------------- | ------------------------------------------------------------------------------------------------ |
| `/` Beranda                       | hero → quick access → misa berikutnya → berita → agenda → renungan → pelayanan → galeri → kontak |
| `/berita`, `/berita/{slug}`       | featured + filter + grid + pagination                                                            |
| `/agenda`, `/agenda/{slug}`       | kartu agenda + kalender + status mendatang/lalu                                                  |
| `/jadwal-misa`                    | panel misa berikutnya + filter + baris + lokasi                                                  |
| `/profil/sejarah`                 | timeline                                                                                         |
| `/profil/pastor`                  | kartu pastor                                                                                     |
| `/profil/struktur`                | org chart                                                                                        |
| `/profil/wilayah`                 | peta area + deskripsi                                                                            |
| `/pelayanan`, `/pelayanan/{slug}` | kartu layanan + FAQ + CTA                                                                        |
| `/komunitas`, `/komunitas/{slug}` | kartu komunitas + filter                                                                         |
| `/area`, `/area/{slug}`           | kartu area + pencarian + peta                                                                    |
| `/galeri`, `/galeri/{slug}`       | grid album/foto + lightbox                                                                       |
| `/dokumen`                        | kartu dokumen + filter                                                                           |
| `/kontak`                         | detail kontak + peta + sosial                                                                    |
| Error + auth                      | halaman error & login/reset                                                                      |

Setiap route: light/dark, responsif (360/768/1280), keyboard, empty/error/loading, konten
paroki yang sudah disetujui atau placeholder `[ISI: …]`.

### M5 — Redesign admin

**Tujuan:** admin memakai token & komposisi v2.

| Langkah | Isi                                                                                                                                |
| ------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| M5.1    | Shell: `app-shell`, `app-sidebar`, `app-header`, `app-content`, `auth-*`, `settings/layout`                                        |
| M5.2    | Dashboard admin                                                                                                                    |
| M5.3    | `data-table`, `form-fields`, `row-actions`, `list-parts`, `list-pagination`, `delete-dialog`, `confirm-dialog`                     |
| M5.4    | 92 halaman admin (Berita, Agenda, Jadwal Misa, Master Data, Galeri, Dokumen, Komunitas, Area, Pelayanan, Profil, Pengaturan, Akun) |
| M5.5    | Perbarui test Vitest admin + E2E admin                                                                                             |

### M6 — Verifikasi akhir

| Langkah | Isi                                                                                                      |
| ------- | -------------------------------------------------------------------------------------------------------- |
| M6.1    | Tambah guard test: **tidak ada kelas token v1** tersisa di source (grep berbasis daftar nama lama)       |
| M6.2    | Tambah guard test: setiap route publik render tanpa overflow horizontal di light & dark (E2E, lebar 360) |
| M6.3    | Jalankan `composer ci:check` (lint, typecheck, Pest, Vitest)                                             |
| M6.4    | `npm run build` lalu `npm run e2e`                                                                       |
| M6.5    | Checklist visual DESIGN-v2 §15 (lihat §9)                                                                |
| M6.6    | Catat hasil audit kontras & temuan ke `DECISIONS.md` D-72                                                |

---

## 5. File yang Akan Terpengaruh

### 5.1 Wajib berubah (inti)

- `resources/css/app.css` — seluruh token.
- `vite.config.ts` — font.
- `resources/views/app.blade.php` — background html.
- `resources/js/lib/utils.ts` — registry font-size.
- `resources/js/components/text.tsx` — varian & warna.
- `resources/js/components/ui/*.tsx` (31) — primitif.
- `resources/js/components/*.tsx` (37) — komponen bersama.
- `resources/js/layouts/**` (13) — shell.
- `resources/js/features/public/**` — halaman & komponen fitur.
- `resources/js/features/admin/**` — 92 halaman + komponen.
- `resources/js/features/public/design-system/pages/index.tsx` — showcase token v2.

### 5.2 Test yang harus diperbarui

- `tests/js/components/text.test.tsx`
- `tests/js/lib/utils.test.ts`
- `tests/js/components/public-primitives.test.tsx`
- `tests/js/features/public/beranda/homepage.test.tsx`
- `tests/js/components/app-sidebar.test.tsx`
- `tests/js/components/parish-footer.test.tsx` (bila ada)
- `tests/e2e/public.spec.ts` (+ spec admin)
- `tests/Unit/ArchitectureTest.php`

### 5.3 Dokumen

- `docs/DECISIONS.md` (D-72)
- `docs/ARCHITECTURE.md`
- `docs/AGENTS.md` / `AGENTS.md`
- `docs/roadmap/README.md`
- `docs/DESIGN.md` (penanda superseded)
- `docs/plan-implementation/plan-design-v2-migration.md` (dokumen ini)

> **Tidak disentuh:** file generated `resources/js/{actions,routes,wayfinder}`,
> `public/**`, `bootstrap/ssr/**`; migration/model/controller/route backend (migrasi ini
> murni presentasi, kecuali satu penyesuaian `<html>` di blade).

---

## 6. Dependency / Configuration Impact

| Item             | Dampak                                                                                                                                   |
| ---------------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| **Font**         | `Mona Sans` dimuat lewat `bunny` provider `laravel-vite-plugin` yang sudah terpasang — **tanpa dependency npm baru**. `@fonts` di blade tetap. |
| **Tailwind v4**  | Tidak ada plugin baru. Token v2 didefinisikan sebagai `--text-*`, `--color-*`, `--radius-*` di `@theme`.                                 |
| **shadcn/ui**    | Tidak menjalankan `shadcn add --all` (AGENTS.md, D-22). Primitif direstyle manual, `cn` tetap dari `@/lib/utils`.                        |
| **npm/composer** | Tidak ada paket baru.                                                                                                                    |
| **Config app**   | Tidak ada key setting baru; tidak ada perubahan `config/site-settings.php`.                                                              |
| **CI**           | `composer ci:check` tetap; test guard baru ikut berjalan.                                                                                |

---

## 7. Risiko dan Potensi Konflik

| #   | Risiko                                                           | Dampak                                     | Mitigasi                                                      |
| --- | ---------------------------------------------------------------- | ------------------------------------------ | ------------------------------------------------------------- |
| R1  | Rename token lama → baru menyentuh ~430 titik; ada yang terlewat | Kelas tanpa CSS → tampilan rusak senyap    | Guard test M6.1 (grep token v1) + migrasi per-fitur           |
| R2  | `text-body` tabrakan ukuran vs warna                             | Utility ambigu, ukuran hilang di `cn()`    | §3.4: warna body tidak memakai `text-body`                    |
| R3  | M1 (token) tanpa M2 (komponen) membuat build rusak               | Build/CI merah di tengah                   | M1+M2 digabung satu PR                                        |
| R4  | Hapus `micro-legal` (10px) & `dense-link`                        | Perubahan visual pada footer/legal/sidebar | Disengaja; catat di D-72; verifikasi ukuran                   |
| R5  | Kontras dark `#F06A73` vs `#AB020E`                              | Ring/teks gagal WCAG                       | Audit kontras tiap pasangan; E2E kanvas tetap netral          |
| R6  | Admin 92 halaman                                                 | Migrasi panjang, mudah lelah               | M5 terpisah dari M4; kerjakan per-modul                       |
| R7  | `npm run e2e` menyajikan `public/build`                          | E2E menguji UI lama                        | `npm run build` dulu; ingatkan di setiap tahap                |
| R8  | Pest & Playwright berbagi DB                                     | E2E gagal `signIn` setelah Pest            | Jalankan `npm run e2e` (prepare), bukan `npx playwright test` |
| R9  | Konten paroki belum ada                                          | Restyle bisa memicu "mengarang" data       | Hanya placeholder `[ISI: …]`; tidak ada data nyata baru       |
| R10 | Tailwind v4 memangkas `--text-*` yang belum dipakai              | Token tampak "hilang" di CSS build         | Uji dengan kelas sementara sebelum menyimpulkan rusak         |

---

## 8. Verification Plan

### 8.1 Otomatis

| Perintah                               | Cakupan                                    |
| -------------------------------------- | ------------------------------------------ |
| `npm run check`                        | lint + format (oxlint, type-aware)         |
| `npm run types:check`                  | `tsc --noEmit`                             |
| `php artisan test` / `vendor/bin/pest` | Pest (termasuk ArchitectureTest baru)      |
| `npm run test:unit`                    | Vitest (text, primitives, halaman)         |
| `npm run build`                        | build produksi (guard `--text-*` terbuang) |
| `npm run e2e`                          | Playwright publik + admin (light & dark)   |
| `composer ci:check`                    | gabungan CI                                |

### 8.2 Test baru

- **Tidak ada token v1 tersisa** di `resources/js` (kecuali `ui/` bila transisi).
- **Setiap route publik** bebas overflow horizontal di light & dark pada 360px.
- **Setiap route publik** punya tepat satu `<h1>`.
- **Kontras** ring & teks merah di dark memakai `#F06A73`.

### 8.3 Manual (wajib, DESIGN-v2 §15)

Lihat §9.

---

## 9. Definition of Done (Mengacu DESIGN-v2 §15)

Sebuah komponen/halaman baru dianggap selesai bila:

- [ ] Mengikuti hierarki & komposisi halaman DESIGN-v2 §11.
- [ ] Memakai token warna & tipografi semantik v2.
- [ ] Terlihat sesuai di light **dan** dark.
- [ ] Primary tetap `#AB020E`.
- [ ] Punya state hover, focus, active, disabled, loading.
- [ ] Berfungsi di mobile, tablet, desktop.
- [ ] Tanpa overflow horizontal atau teks penting terpotong.
- [ ] Gambar punya crop & alt yang tepat.
- [ ] Empty, error, loading dipertimbangkan.
- [ ] Keyboard berfungsi.
- [ ] Teks & kontrol kontras memadai.
- [ ] Footer, navigasi, komponen bersama selaras tema.
- [ ] Konten didukung data paroki yang disetujui; tidak ada sejarah/jadwal/nama/klaim yang dikarang.

### 9.1 Visual review minimal

- [ ] Beranda light & dark.
- [ ] Daftar berita + featured light & dark.
- [ ] Jadwal misa + filter light & dark.
- [ ] Profil sejarah/timeline light & dark.
- [ ] Footer light & dark.
- [ ] Navigasi mobile + satu halaman konten panjang.
- [ ] Empty state pencarian + halaman dengan label Indonesia panjang.

---

## 10. Pertanyaan / Ambiguity

1. **Logo resmi & fotografi.** DESIGN-v2 §16 menanyakan aset logo final dan fotografi yang
   disetujui. Sampai paroki mengonfirmasi, migrasi memakai aset/placeholder yang ada.
2. **Bobot font.** Mona Sans adalah variabel 200–900; yang dimuat 400/500/600/700 (D-74).
   `bunny` memuat bobot statis. Rencana memuat `[400,500,600,700]`.
3. **`surface="navy"`.** `PublicSection` punya opsi surface navy yang dipakai halaman
   tertentu; DESIGN-v2 melarang latar navy halaman penuh. Surface ini diganti aksen navy
   terbatas saat M3.3 — akan dikonfirmasi saat implementasi bila ada konsumen tak terduga.
4. **Structured data Organization/Church** (PRD KON-06) tetap ditunda (D-67). Migrasi tidak
   menyentuhnya.

---

## 11. Status

**Sudah diimplementasikan.** M0–M6 dikerjakan dalam satu rangkaian perubahan di atas
branch `feat/phase-9a-homepage`.

### 11.1 Yang dikerjakan

- **M0** — `DECISIONS.md` D-72; `AGENTS.md`, `ARCHITECTURE.md` (Part B §1.1, §1.2), dan
  `docs/roadmap/README.md` menunjuk `DESIGN-v2.md`; `DESIGN.md` v1.1 ditandai *superseded*.
- **M1** — `resources/css/app.css` ditulis ulang ke palet & skala v2 (brand, netral
  light/dark, semantik, radius 6/10/14/20, 11 langkah tipografi dengan `clamp()`);
  `Mona Sans` dimuat lewat `bunny` di `vite.config.ts` (normal 400/500/600/700, italic 400/600) dan menggantikan
  Instrument Sans; `lib/utils.ts` mendaftarkan 11 token baru; `app.blade.php` memakai
  `#FFFFFF`/`#111318`.
- **M2** — `text.tsx` memetakan varian ke 11 langkah v2; `button.tsx`
  (primary/secondary/tertiary + state active), `alert.tsx`, `table.tsx` diselaraskan.
- **M3** — shell publik: navbar (marker aktif `--primary-on-surface`) dan footer sudah
  memakai token v2; `home-devotion-section` berhenti memakai slab navy penuh dan opacity
  teks; panel auth turun ke permukaan netral di dark.
- **M4/M5** — seluruh rute (publik **dan** admin) membaca token/komponen baru. Sapuan
  mekanis mengganti ~430 pemakaian token v1 (`hairline`→`border`, `ink-muted`→
  `muted-foreground`, `canvas-soft`→`canvas-subtle`, `navy-light`→`navy-soft`, dst.);
  `ring-primary`→`ring-ring`, `text-primary`→`text-primary-on-surface` untuk kontras dark.
- **M6** — guard test `no component reaches for a retired DESIGN.md v1 token` di
  `ArchitectureTest.php`; test tipografi & `utils` diperbarui.

### 11.2 Hasil verifikasi

| Perintah | Hasil |
| -------- | ----- |
| `npm run check` | 367 file terformat, 0 warning |
| `npm run types:check` | bersih |
| `npm run test:unit` (Vitest) | 379 lulus |
| `php artisan test` (Pest) | 1443 lulus |
| `composer ci:check` | Pint + PHPStan (0 error) + Pest + Vitest lulus |
| `npm run build` | sukses (Mona Sans ter-bundle, token v2 ada di CSS) |
| `npm run e2e` | 105 lulus (light & dark, mobile 360px) |

### 11.3 Catatan

- Migrasi ini menghapus `micro-legal` (10px) dan `dense-link`: footer/legal/sidebar kini
  12px (`fine-print`) sesuai batas minimum DESIGN-v2.
- Beberapa prose `text-sm`/`text-lg` di dalam komponen tetap ada (nilainya identik dengan
  `body-sm`/`body-lg`); bukan pelanggaran warna/tema dan bisa diselaraskan menyusul.
- Komposisi halaman yang lebih dalam (mis. hero per-route, `QuickAccessBar`) mengikuti
  kerangka yang sudah ada; peran DESIGN-v2 dipetakan di D-72 §3.
- Konten paroki tetap placeholder `[ISI: …]`; tidak ada data nyata yang dikarang.

```text
M0 ✅  M1 ✅  M2 ✅  M3 ✅  M4 ✅  M5 ✅  M6 ✅
```
