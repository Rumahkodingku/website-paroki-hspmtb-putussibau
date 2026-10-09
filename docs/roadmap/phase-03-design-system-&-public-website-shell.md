# PHASE 03 --- Design System & Public Website Shell

> **Project:** Website Paroki Hati Santa Perawan Maria Tak Bernoda
> (HSPMTB) Putussibau\
> **Phase:** 03\
> **Status:** READY FOR REVIEW — implementasi selesai, menunggu persetujuan\
> **Stack:** Laravel 13 + Inertia.js + React 19 + TypeScript + Tailwind
> CSS + shadcn/ui\
> **Primary references:** `PRD.md`, `ARCHITECTURE.md`, `DESIGN.md`\
> **Scope:** Public UI foundation only

------------------------------------------------------------------------

## 0. Tujuan Dokumen

Dokumen ini adalah task specification untuk **Phase 03 --- Design System
& Public Website Shell**.

Phase ini bertujuan menyelesaikan fondasi antarmuka publik sebelum modul
bisnis/public information mulai dibangun pada fase berikutnya.

Fokus utama:

1. Menstabilkan design token HSPMTB.
2. Menyelesaikan `PublicLayout`.
3. Menyelesaikan `ParishNavbar`.
4. Menyelesaikan `ParishFooter`.
5. Menyiapkan reusable public components.
6. Menyiapkan public route/page shell.
7. Menyiapkan fondasi SEO publik.
8. Menjamin responsive behavior dan accessibility.
9. Menyiapkan image presentation foundation.
10. Menyiapkan public error states.
11. Membuat public shell showcase/reference page.
12. Menambahkan test yang relevan.
13. Melakukan final validation sebelum masuk phase berikutnya.

### Prinsip utama

> **Phase 03 bukan phase untuk membangun fitur bisnis.**

Jangan mengimplementasikan business logic Jadwal Misa, Berita, Agenda,
Profil, Pelayanan, Komunitas, Galeri, Kontak, atau Download secara penuh
di phase ini.

------------------------------------------------------------------------

# 1. Referensi dan Source of Truth

Urutan sumber kebenaran:

1. `PRD.md`
2. `ARCHITECTURE.md`
3. `DESIGN.md`
4. Konvensi aktual codebase
5. Keputusan yang terdokumentasi di `docs/DECISIONS.md`

Jika terdapat konflik, jangan membuat asumsi diam-diam. Catat konflik
dan gunakan keputusan proyek yang telah disepakati.

### Aturan penting dari PRD

- Jangan mengarang data nyata paroki.
- Gunakan placeholder yang jelas untuk data yang belum dikonfirmasi.
- Jangan membangun fitur yang berada di luar scope MVP.
- Kerjakan phase berdasarkan dependency.
- Requirement dan acceptance criteria harus dapat
    diverifikasi/testable.
- Konten nyata yang belum disetujui paroki tidak boleh diperlakukan
    sebagai data produksi.

### Aturan penting dari Architecture

- Laravel adalah source of truth untuk routing, data, authorization,
    dan business logic.
- Inertia adalah bridge Laravel → React.
- Jangan menggunakan React Router.
- Page Inertia tetap tipis.
- Feature-specific UI berada di `resources/js/features`.
- Reusable UI berada di `resources/js/components`.
- `components/ui` adalah primitive shadcn/ui.
- Jangan menambahkan abstraction tanpa kebutuhan nyata.
- Jangan melakukan unrelated refactoring.
- Setelah shared code berubah, jalankan affected tests dan full suite
    bila relevan.

### Aturan penting dari Design

- Tailwind CSS adalah styling foundation.
- shadcn/ui adalah primitive layer.
- Custom HSPMTB components digunakan untuk parish-specific UI.
- Public website bersifat photography-first.
- White dan soft-neutral surface dominan.
- HSPMTB Red adalah primary interactive color.
- Navy adalah structural/editorial dark color.
- Gold adalah restrained ceremonial accent.
- Hindari decorative gradient.
- Hindari heavy shadow.
- Gunakan semantic tokens.
- Minimum interactive touch target adalah 44×44px.
- Mobile-first.
- Keyboard accessible.
- Gunakan semantic HTML.

------------------------------------------------------------------------

# 2. Kondisi Awal Phase

Codebase telah memiliki sebagian fondasi public UI.

Area yang perlu diaudit dan diselesaikan:

``` text
resources/js/
├── components/
│   ├── parish-navbar.tsx
│   ├── parish-footer.tsx
│   ├── seo.tsx
│   ├── empty-state.tsx
│   ├── breadcrumbs.tsx
│   └── ui/
│
├── layouts/
│   └── public-layout.tsx
│
├── features/
│   └── public/
│       └── beranda/
│
└── ...
```

**Jangan membuat ulang component yang sudah ada tanpa alasan.**

Pendekatan Phase 03:

``` text
Audit
  ↓
Refine
  ↓
Complete
  ↓
Integrate
  ↓
Test
  ↓
Validate
```

Bukan:

``` text
Delete existing code
  ↓
Rewrite everything
```

------------------------------------------------------------------------

# 3. Definition of Done Phase 03

Phase 03 selesai apabila:

- [ ] Design tokens HSPMTB terimplementasi secara konsisten.
- [ ] `PublicLayout` menjadi shell tunggal untuk public pages.
- [ ] `ParishNavbar` selesai dan responsive.
- [ ] `ParishFooter` selesai dan responsive.
- [ ] Shared public primitives tersedia.
- [ ] Public navigation tidak menggunakan hard-coded URL bila route
    helper tersedia.
- [ ] Public page shell dapat digunakan oleh phase berikutnya.
- [ ] SEO foundation siap digunakan oleh public pages.
- [ ] Public error pages menggunakan visual language yang sama.
- [ ] Image foundation mendukung responsive image behavior.
- [ ] Mobile 360px tidak mengalami horizontal overflow.
- [ ] Touch target interactive minimal 44×44px.
- [ ] Keyboard navigation berfungsi pada custom interactive elements.
- [ ] Public shell dapat diuji pada 360, 390, 768, 1024, 1280, dan
    1440px.
- [ ] Test yang relevan hijau.
- [ ] Production build berhasil.
- [ ] Tidak ada feature/business logic yang sengaja dibangun di luar
    scope.
- [ ] Tidak ada data nyata paroki yang dikarang.
- [ ] Tidak ada UI library baru yang ditambahkan tanpa approval.

------------------------------------------------------------------------

# 4. Struktur Task Phase 03

``` text
PHASE 03
│
├── 03.01 Design Token & Theme
├── 03.02 Public Layout Foundation
├── 03.03 Parish Navbar
├── 03.04 Parish Footer
├── 03.05 Shared Public Components
├── 03.06 Public Route & Page Shell
├── 03.07 SEO Foundation
├── 03.08 Responsive Design
├── 03.09 Accessibility
├── 03.10 Image Foundation
├── 03.11 Public Error States
├── 03.12 Public Shell Showcase
├── 03.13 Testing
└── 03.14 Final Validation
```

------------------------------------------------------------------------

# 5. Task 03.01 --- Design Token & Theme

## Objective

Memastikan seluruh public UI menggunakan design system HSPMTB dan tidak
bergantung pada arbitrary styling.

## Design Tokens

### Brand

``` text
Primary Red       #AB020E
Primary Hover     #8F010B
Primary Focus     #C51624
Primary on Dark   #FF6670

Navy              #01266D
Navy Dark         #001A4D
Navy Light        #EAF0FA

Gold              #FCB027
Gold Dark         #D99400
Gold Light        #FFF4D6
```

### Surface

``` text
Canvas            #FFFFFF
Canvas Soft       #F8F9FB
Pearl             #FCFCFD
Surface Navy      #01266D
Surface Navy Dark #001A4D
Surface Red       #AB020E
Surface Gold      #FFF4D6
Surface Black     #0B1220
```

### Text

``` text
Ink               #111827
Body              #1F2937
Body on Dark      #FFFFFF
Body Muted        #667085
Ink Muted 80      #475467
Ink Muted 48      #98A2B3
```

### Borders

``` text
Divider Soft      #F2F4F7
Hairline          #E4E7EC
```

## Tasks

- [ ] Audit `resources/css/app.css`.
- [ ] Audit Tailwind theme variables.
- [ ] Pastikan semantic tokens tersedia.
- [ ] Pastikan public components menggunakan semantic tokens.
- [ ] Hindari hard-coded brand hex di JSX/TSX.
- [ ] Audit typography tokens.
- [ ] Audit spacing tokens.
- [ ] Audit radius tokens.
- [ ] Audit shadow/elevation tokens.
- [ ] Audit responsive breakpoint usage.
- [ ] Pastikan token naming konsisten dengan shadcn/ui.
- [ ] Pastikan tidak ada duplicate token dengan makna sama.
- [ ] Pastikan dark-mode token tidak mengganggu light public site.
- [ ] Jangan menambahkan font dependency baru tanpa keputusan proyek.

## Typography baseline

Gunakan token dari `DESIGN.md`:

``` text
Hero Display       56px / 600 / 1.07
Display LG         40px / 600 / 1.10
Display MD         34px / 600 / 1.18
Lead               28px / 400 / 1.14
Lead Airy          24px / 300 / 1.50
Tagline            21px / 600 / 1.19
Body Strong        17px / 600 / 1.24
Body               17px / 400 / 1.47
Caption            14px / 400 / 1.43
Caption Strong     14px / 600 / 1.29
Button Large       18px / 500 / 1.00
Button Utility     14px / 400 / 1.29
Fine Print         12px / 400 / 1.30
Micro Legal        10px / 400 / 1.30
Navigation         13px / 400 / 1.00
```

## Spacing baseline

``` text
4px
8px
12px
16px
24px
32px
48px
80px
112px
```

## Radius baseline

``` text
0px
5px
8px
12px
18px
24px
9999px
```

## Acceptance Criteria

- [ ] Public component tidak membutuhkan inline brand hex untuk
    styling yang sudah memiliki token.
- [ ] Button, card, navigation, footer, CTA, dan surface utama
    mengikuti token.
- [ ] Tidak terdapat visual inconsistency yang berasal dari duplicate
    color values.
- [ ] Type scale dapat digunakan kembali oleh Phase berikutnya.

------------------------------------------------------------------------

# 6. Task 03.02 --- Public Layout Foundation

## Objective

Menjadikan `PublicLayout` sebagai shell utama seluruh website publik.

## File

``` text
resources/js/layouts/public-layout.tsx
```

## Struktur target

``` text
PublicLayout
├── Header / ParishNavbar
├── Main
│   └── Page Content
└── ParishFooter
```

## Tasks

- [ ] Audit implementation `PublicLayout`.
- [ ] Pastikan `ParishNavbar` dipanggil dari layout.
- [ ] Pastikan `ParishFooter` dipanggil dari layout.
- [ ] Gunakan semantic `<header>`.
- [ ] Gunakan semantic `<main>`.
- [ ] Gunakan semantic `<footer>`.
- [ ] Pastikan children/page content memiliki container yang benar.
- [ ] Pastikan layout tidak bergantung pada admin state.
- [ ] Pastikan public layout dapat digunakan semua public pages.
- [ ] Pastikan page tidak perlu membuat navbar/footer sendiri.
- [ ] Pastikan Inertia navigation tidak merusak shell.
- [ ] Pastikan scroll behavior normal.
- [ ] Pastikan layout tidak menyebabkan horizontal overflow.

## Acceptance Criteria

Public page dapat menggunakan `PublicLayout` tanpa perlu:

- membuat navbar;
- membuat footer;
- mengulang container;
- mengulang SEO shell;
- mengulang public global structure.

------------------------------------------------------------------------

# 7. Task 03.03 --- Parish Navbar

## Objective

Menyelesaikan navigation publik sesuai PRD dan DESIGN.

## Desktop navigation

Menu:

``` text
Beranda
Profil
Jadwal Misa
Berita & Artikel
Agenda
Pelayanan
Komunitas
Galeri
Download
Kontak
```

Shortcut utama:

``` text
Jadwal Misa
```

## Tasks

### Structure

- [ ] Logo paroki.
- [ ] Wordmark/nama paroki.
- [ ] Navigation links.
- [ ] Active route state.
- [ ] CTA/shortcut Jadwal Misa.
- [ ] Mobile menu trigger.

### Interaction states

Setiap interactive item harus memiliki:

``` text
Default
Hover
Focus
Active/Pressed
Selected
Disabled jika applicable
```

- [ ] Active navigation jelas.
- [ ] Focus ring terlihat.
- [ ] Hover tidak menjadi satu-satunya indicator.
- [ ] Pressed state tersedia bila relevan.

### Mobile

Gunakan shadcn/ui `Sheet`.

- [ ] Hamburger button.
- [ ] Minimum 44×44px.
- [ ] Sheet open.
- [ ] Sheet close.
- [ ] Close button accessible.
- [ ] Escape closes sheet.
- [ ] Keyboard focus management.
- [ ] Active route visible.
- [ ] Navigation dapat discroll jika panjang.
- [ ] Tidak terjadi horizontal overflow.

### Route integration

- [ ] Gunakan generated route helper/Wayfinder bila tersedia.
- [ ] Jangan menggunakan React Router.
- [ ] Hindari hard-coded application URLs jika helper tersedia.
- [ ] Pastikan route navigation menggunakan Inertia.

## Acceptance Criteria

- [ ] Semua menu utama tersedia.
- [ ] Semua menu mengarah ke route yang benar.
- [ ] Active state bekerja.
- [ ] Mobile menu bekerja.
- [ ] Keyboard navigation bekerja.
- [ ] Touch target minimal 44×44px.
- [ ] Navbar tidak overflow pada 360px.

------------------------------------------------------------------------

# 8. Task 03.04 --- Parish Footer

## Objective

Menyediakan footer publik yang konsisten dan menjadi secondary
information architecture.

## Content groups

``` text
Identitas Paroki
Menu Utama
Profil
Pelayanan
Komunitas
Kontak
Media Sosial
Legal
Copyright
```

## Tasks

- [ ] Logo/identitas.
- [ ] Nama paroki.
- [ ] Deskripsi/tagline.
- [ ] Menu utama.
- [ ] Profile links.
- [ ] Service links.
- [ ] Community links.
- [ ] Contact links.
- [ ] Social media links.
- [ ] Privacy/legal link.
- [ ] Copyright.
- [ ] Responsive layout.
- [ ] Mobile stacking/accordion bila diperlukan.
- [ ] Accessible link labels.
- [ ] External links memiliki behavior yang tepat.

## Data

Data kontak final tidak boleh dikarang.

Gunakan data dari site settings ketika contract backend sudah tersedia.

Untuk data yang belum tersedia:

``` text
[ISI: nomor sekretariat]
[ISI: alamat]
[ISI: media sosial]
```

atau placeholder internal yang tidak dipublikasikan sebagai data nyata.

## Acceptance Criteria

- [ ] Footer responsive.
- [ ] Semua link dapat difokuskan keyboard.
- [ ] Tidak ada hard-coded data nyata yang belum disetujui.
- [ ] Footer tetap readable pada 360px.
- [ ] Footer tidak menggunakan visual treatment yang bertentangan
    dengan DESIGN.

------------------------------------------------------------------------

# 9. Task 03.05 --- Shared Public Components

## Objective

Menyediakan primitive/composition yang akan dipakai oleh phase
berikutnya.

## 03.05.1 Public Container

Buat/review:

``` text
PublicContainer
```

Tanggung jawab:

- max width;
- responsive horizontal padding;
- consistent content alignment.

Target:

``` text
max-w-7xl
```

dengan penyesuaian untuk full editorial composition bila dibutuhkan.

------------------------------------------------------------------------

## 03.05.2 Public Section

Buat/review:

``` text
PublicSection
```

Tanggung jawab:

- vertical section spacing;
- surface;
- optional container;
- responsive spacing.

Default desktop:

``` text
80px
```

Large editorial:

``` text
112px
```

Mobile dapat turun ke:

``` text
48–64px
```

------------------------------------------------------------------------

## 03.05.3 Section Heading

Buat/review:

``` text
SectionHeading
```

Mendukung:

``` text
eyebrow
title
description
action
```

Tujuan:

- menghindari heading pattern yang berbeda-beda;
- menjaga editorial hierarchy.

------------------------------------------------------------------------

## 03.05.4 Parish CTA

Buat/review:

``` text
ParishCTA
```

Default:

``` text
Navy background
White text
One primary action
Optional secondary action
```

Jangan membuat CTA penuh dengan banyak tombol.

------------------------------------------------------------------------

## 03.05.5 Breadcrumb

Gunakan shadcn/ui primitive jika tersedia.

- [ ] Accessible breadcrumb.
- [ ] Mobile-friendly.
- [ ] Current page semantics.
- [ ] Tidak overflow untuk judul panjang.

------------------------------------------------------------------------

## 03.05.6 Empty State

Audit:

``` text
resources/js/components/empty-state.tsx
```

Pastikan dapat digunakan untuk:

``` text
Tidak ada berita
Belum ada agenda
Belum ada album
Belum ada dokumen
```

Jangan menambahkan feature-specific content ke component generic.

------------------------------------------------------------------------

## 03.05.7 Loading/Skeleton

Gunakan shadcn/ui `Skeleton` jika tersedia.

Siapkan pattern untuk:

- card;
- heading;
- image;
- list;
- page block.

Jangan membangun data fetching hanya untuk skeleton.

------------------------------------------------------------------------

## 03.05.8 Button Variants

Gunakan existing shadcn/ui Button sebelum membuat primitive baru.

Variants yang dibutuhkan:

``` text
primary
secondary
navy
gold
icon
utility
```

Gold bukan default primary CTA.

------------------------------------------------------------------------

## Acceptance Criteria

- [ ] Reusable components tidak bergantung pada feature tertentu.
- [ ] Tidak ada circular dependency.
- [ ] Components tidak import `features/*` bila seharusnya shared.
- [ ] Component variants eksplisit.
- [ ] States default/focus/active/disabled/loading dipertimbangkan.
- [ ] Semua reusable UI mengikuti token.

------------------------------------------------------------------------

# 10. Task 03.06 --- Public Route & Page Shell

## Objective

Menyiapkan route/page contract agar seluruh navigation publik dapat
diuji tanpa membangun business logic.

## Public routes yang harus dipersiapkan

``` text
/
 /profil
 /profil/sejarah
 /profil/visi-misi
 /profil/wilayah
 /profil/pastor
 /profil/struktur

/jadwal-misa

/berita
/berita/{slug}

/agenda
/agenda/{slug}
/agenda/{slug}/ics

/pelayanan
/pelayanan/{slug}

/komunitas
/komunitas/{slug}

/galeri
/galeri/{slug}

/kontak

/download
/download/{id}/unduh
```

## Scope Phase 03

Route/page shell boleh berupa placeholder.

Contoh:

``` text
Profil
Halaman ini akan diimplementasikan pada phase Profil.
```

Tidak boleh ada:

- CRUD;
- query domain;
- business rules;
- database schema baru;
- fake production content.

## Feature structure

Ikuti feature-oriented architecture.

Contoh:

``` text
resources/js/features/public/

├── beranda/
├── profil/
├── jadwal-misa/
├── berita/
├── agenda/
├── pelayanan/
├── komunitas/
├── galeri/
├── kontak/
└── download/
```

Feature dapat memiliki:

``` text
components/
hooks/
types.ts
utils.ts
pages/
```

Buat folder hanya ketika memang ada file yang dibutuhkan.

## Important architecture rule

Jangan membuat:

``` text
React Router
```

Jangan membuat internal API hanya untuk mengisi placeholder public page.

Routing tetap:

``` text
Laravel routes
    ↓
Inertia
    ↓
React page
```

## Acceptance Criteria

- [ ] Navigation tidak menghasilkan broken route.
- [ ] Public pages dapat menggunakan `PublicLayout`.
- [ ] Placeholder pages tidak berisi business logic.
- [ ] Route naming konsisten dengan Laravel/Inertia.
- [ ] Generated route helper diperbarui jika menggunakan Wayfinder.

------------------------------------------------------------------------

# 11. Task 03.07 --- SEO Foundation

## Objective

Menyiapkan contract SEO yang dapat digunakan seluruh public pages.

File existing:

``` text
resources/js/components/seo.tsx
```

## Minimum support

``` text
title
description
canonical
robots
og:title
og:description
og:image
og:url
og:type
twitter/card
twitter:title
twitter:description
twitter:image
```

## Tasks

- [ ] Audit existing SEO component.
- [ ] Pastikan default site title.
- [ ] Pastikan description fallback.
- [ ] Pastikan canonical handling.
- [ ] Pastikan OG tags.
- [ ] Pastikan Twitter/X card.
- [ ] Pastikan favicon/site icon.
- [ ] Pastikan locale Indonesia.
- [ ] Hindari duplicate `<title>`.
- [ ] Hindari duplicate meta tags.
- [ ] Pastikan page dapat override default SEO.
- [ ] Pastikan tidak mengirim data sensitif.

## Per-page contract

Gunakan konsep:

``` ts
type SeoProps = {
    title?: string;
    description?: string;
    canonical?: string;
    image?: string;
    type?: string;
};
```

Sesuaikan dengan implementation aktual jika type/contract telah
tersedia.

## Acceptance Criteria

- [ ] Homepage dapat memiliki title/description.
- [ ] Page lain dapat override metadata.
- [ ] OG image fallback tersedia.
- [ ] Canonical tidak menghasilkan invalid URL.
- [ ] SEO component tidak terikat ke satu feature.

------------------------------------------------------------------------

# 12. Task 03.08 --- Responsive Design

## Objective

Public website harus mobile-first dan tidak menjadi desktop layout yang
sekadar diperkecil.

## Viewport test matrix

``` text
360px
390px
420px
640px
768px
834px
1024px
1068px
1280px
1440px
```

## Breakpoint reference

``` text
≤ 419px      Small phone
420–640px    Phone
641–735px    Large phone
736–833px    Tablet portrait
834–1023px   Tablet landscape
1024–1068px  Small desktop
1069–1440px  Desktop
≥ 1441px     Wide desktop
```

## Tasks

- [ ] Navbar responsive.
- [ ] Footer responsive.
- [ ] Container responsive.
- [ ] Typography responsive.
- [ ] Button groups responsive.
- [ ] CTA responsive.
- [ ] Breadcrumb responsive.
- [ ] Card layout responsive.
- [ ] Long labels wrap correctly.
- [ ] Indonesian parish names do not overflow.
- [ ] No horizontal scroll.
- [ ] No fixed width that breaks mobile.
- [ ] Mobile navigation is accessible.
- [ ] Images scale/crop correctly.

## Typography responsive strategy

Hero:

``` text
56px → 40px → 34px → 28px
```

sesuai viewport dan content density.

## Card strategy

``` text
3–5 columns
    ↓
2 columns
    ↓
1 column
```

## CTA strategy

``` text
horizontal
    ↓
wrapped
    ↓
stacked
```

## Footer strategy

``` text
multi-column
    ↓
stacked
```

------------------------------------------------------------------------

# 13. Task 03.09 --- Accessibility

## Objective

Public shell harus semantic, keyboard accessible, dan memiliki visible
interaction states.

## Semantic HTML

- [ ] `<header>`
- [ ] `<nav>`
- [ ] `<main>`
- [ ] `<section>`
- [ ] `<footer>`
- [ ] Correct heading hierarchy.
- [ ] Links use `<a>`/Inertia `Link`.
- [ ] Buttons use `<button>`.

## Keyboard

- [ ] Navbar keyboard navigation.
- [ ] Mobile Sheet keyboard navigation.
- [ ] Escape behavior.
- [ ] Focus visible.
- [ ] Focus order logical.
- [ ] No keyboard trap.
- [ ] Interactive custom component can be operated without mouse.

## ARIA

Gunakan ARIA hanya ketika semantic HTML tidak cukup.

Contoh:

``` text
aria-label
aria-current
aria-expanded
aria-controls
```

Jangan menambahkan redundant ARIA.

## Touch

Minimum:

``` text
44 × 44px
```

untuk interactive controls.

## Contrast

Periksa:

- body text;
- navigation;
- primary buttons;
- navy surfaces;
- footer;
- focus ring;
- disabled states.

## Images

Setiap meaningful image harus memiliki meaningful alt.

Decorative image:

``` text
alt=""
```

bila benar-benar decorative.

------------------------------------------------------------------------

# 14. Task 03.10 --- Image Foundation

## Objective

Menyiapkan presentation layer untuk photography-first design.

## Image principles

- Hero photography full-width.
- Hero image eager load.
- Below-fold images lazy.
- Gunakan responsive `srcset`.
- Gunakan `sizes`.
- Gunakan WebP/AVIF bila pipeline mendukung.
- Jangan mengorbankan meaningful composition hanya untuk aspect ratio.
- Portrait mempertahankan headroom/face crop yang intentional.

## Tasks

- [ ] Audit existing image components.
- [ ] Buat/review reusable responsive image abstraction hanya jika
    benar-benar diperlukan.
- [ ] Tentukan standard width/height contract.
- [ ] Pastikan aspect ratio mencegah layout shift.
- [ ] Pastikan hero `loading="eager"` jika sesuai.
- [ ] Pastikan image bawah fold `loading="lazy"`.
- [ ] Pastikan `sizes` sesuai layout.
- [ ] Pastikan alt text tersedia.
- [ ] Pastikan fallback/error state.
- [ ] Pastikan image tidak overflow container.

## Jangan lakukan

- [ ] Jangan membuat Gallery feature.
- [ ] Jangan membuat lightbox.
- [ ] Jangan membuat batch upload.
- [ ] Jangan membuat image management admin baru.

Itu adalah scope phase berikutnya.

------------------------------------------------------------------------

# 15. Task 03.11 --- Public Error States

## Objective

Public error pages harus menjadi bagian dari design system, bukan
halaman default yang terasa seperti aplikasi berbeda.

## States

``` text
404
403
500
```

## Tasks

- [ ] Audit existing 404.
- [ ] Audit existing 500.
- [ ] Pastikan menggunakan PublicLayout bila sesuai.
- [ ] Gunakan typography tokens.
- [ ] Gunakan HSPMTB visual language.
- [ ] Sediakan CTA kembali ke Beranda.
- [ ] Jangan menampilkan stack trace.
- [ ] Responsive.
- [ ] Accessible.
- [ ] Tidak bergantung pada business module.

## Acceptance Criteria

User yang masuk ke route tidak ditemukan tetap melihat:

``` text
HSPMTB identity
+
clear error message
+
way back to homepage
```

------------------------------------------------------------------------

# 16. Task 03.12 --- Public Shell Showcase

## Objective

Membuat satu reference page untuk membuktikan seluruh public shell dan
design system bekerja bersama.

**Catatan:** ini bukan Homepage final.

## Isi showcase

Minimal:

``` text
ParishNavbar
Hero/reference area
SectionHeading
ParishCard
Button variants
CTA
EmptyState
Breadcrumb
Footer
```

## Suggested structure

``` text
┌──────────────────────────────────────┐
│ ParishNavbar                         │
├──────────────────────────────────────┤
│ Reference Hero                       │
│ HSPMTB Design System                 │
│ Primary / Secondary CTA              │
├──────────────────────────────────────┤
│ Section Heading                      │
│                                      │
│ Card   Card   Card                   │
├──────────────────────────────────────┤
│ Empty State                          │
├──────────────────────────────────────┤
│ Parish CTA                           │
├──────────────────────────────────────┤
│ ParishFooter                         │
└──────────────────────────────────────┘
```

## Tujuan

Menguji:

``` text
tokens
  ↓
primitives
  ↓
shared components
  ↓
layout
  ↓
responsive behavior
  ↓
accessibility
```

## Important

Showcase content bukan data produksi.

Gunakan:

``` text
Design System Preview
Sample Content
Placeholder
```

bila diperlukan.

------------------------------------------------------------------------

# 17. Task 03.13 --- Testing

## Backend

Public route tests minimal:

- [ ] Homepage returns expected Inertia component.
- [ ] Public placeholder routes return expected Inertia component.
- [ ] Public routes do not require admin auth.
- [ ] Missing route returns 404.
- [ ] Admin routes remain protected.
- [ ] Public shell changes do not break authentication.

## Frontend

Jika frontend test runner telah tersedia:

- [ ] Navbar renders.
- [ ] Active navigation works.
- [ ] Mobile Sheet opens.
- [ ] Mobile Sheet closes.
- [ ] Escape closes Sheet.
- [ ] Footer renders links.
- [ ] CTA renders.
- [ ] Empty state renders.
- [ ] SEO component renders expected metadata.
- [ ] No accessibility regression on custom controls.

Jika frontend test runner belum tersedia:

- [ ] Jangan menambahkan test framework baru tanpa approval.
- [ ] Gunakan existing project checks.
- [ ] Rely on TypeScript, lint, build, backend Inertia assertions, dan
    manual browser validation sesuai project setup.

## E2E critical flow

Minimum:

``` text
Visit /
  ↓
Navbar visible
  ↓
Open mobile menu
  ↓
Click Profil
  ↓
/profil
  ↓
PublicLayout remains
```

Dan:

``` text
Visit /
  ↓
Footer visible
  ↓
Click Kontak
  ↓
/kontak
```

## Responsive manual test

Wajib:

``` text
360
390
768
1024
1280
1440
```

------------------------------------------------------------------------

# 18. Task 03.14 --- Final Validation

## 18.1 Design System

- [ ] Colors follow DESIGN.md.
- [ ] Typography follows DESIGN.md.
- [ ] Spacing follows DESIGN.md.
- [ ] Radius follows DESIGN.md.
- [ ] Shadow follows DESIGN.md.
- [ ] No decorative gradients.
- [ ] No uncontrolled arbitrary brand colors.

## 18.2 Public Shell

- [ ] PublicLayout.
- [ ] ParishNavbar.
- [ ] ParishFooter.
- [ ] PublicContainer.
- [ ] PublicSection.
- [ ] SectionHeading.
- [ ] ParishCTA.
- [ ] Breadcrumb.
- [ ] EmptyState.
- [ ] Skeleton/loading pattern.

## 18.3 Navigation

- [ ] Desktop menu.
- [ ] Mobile menu.
- [ ] Active state.
- [ ] Focus state.
- [ ] Keyboard navigation.
- [ ] 44×44px touch target.
- [ ] No broken route.

## 18.4 SEO

- [ ] Title.
- [ ] Description.
- [ ] Canonical.
- [ ] OG.
- [ ] Twitter/X.
- [ ] Favicon.
- [ ] Default fallback.
- [ ] Per-page override.

## 18.5 Responsive

- [ ] 360px.
- [ ] 390px.
- [ ] 420px.
- [ ] 640px.
- [ ] 768px.
- [ ] 834px.
- [ ] 1024px.
- [ ] 1068px.
- [ ] 1280px.
- [ ] 1440px.

## 18.6 Accessibility

- [ ] Semantic HTML.
- [ ] Keyboard.
- [ ] Focus.
- [ ] ARIA.
- [ ] Contrast.
- [ ] Image alt.
- [ ] Touch targets.
- [ ] No keyboard trap.

## 18.7 Code Quality

- [ ] TypeScript passes.
- [ ] ESLint passes if configured.
- [ ] Formatting passes.
- [ ] No unnecessary `any`.
- [ ] No React Router.
- [ ] No new UI library.
- [ ] No unrelated refactor.
- [ ] No duplicate component implementation.
- [ ] No feature-to-feature dependency violation.
- [ ] No business logic inside shared public components.

## 18.8 Production Build

- [ ] Production build succeeds.
- [ ] No unresolved import.
- [ ] No TypeScript error.
- [ ] No runtime error on public shell.
- [ ] No console error caused by Phase 03.
- [ ] Asset loading works.

------------------------------------------------------------------------

# 19. Dependency Rules

Implement in this order:

``` text
03.01 Design Token
      ↓
03.02 Public Layout
      ↓
03.03 Navbar
      ↓
03.04 Footer
      ↓
03.05 Shared Components
      ↓
03.06 Public Routes/Shell
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
03.14 Validation
```

Do not skip foundational tasks merely because a component visually
appears to work.

------------------------------------------------------------------------

# 20. Out of Scope

The following MUST NOT be implemented as business features in Phase 03:

``` text
❌ Jadwal Misa business logic
❌ NextMassResolver
❌ Mass schedule CRUD
❌ Berita CRUD
❌ PublishScheduledPosts
❌ Agenda CRUD
❌ Calendar business logic
❌ Profil database/module
❌ Pastor management
❌ Struktur kepengurusan management
❌ Pelayanan management
❌ Google Form business validation
❌ Community management
❌ Gallery album management
❌ Batch gallery upload
❌ Contact management
❌ WhatsApp normalization
❌ Download management
❌ Download counter
❌ Homepage aggregation
❌ Admin dashboard analytics
❌ Sitemap implementation if scheduled for later phase
❌ robots.txt implementation if scheduled for later phase
```

Placeholder route/page untuk kebutuhan navigation testing diperbolehkan.

------------------------------------------------------------------------

# 21. Anti-Patterns

Jangan melakukan:

### 21.1 Rewrite existing public components

Jika:

``` text
ParishNavbar
ParishFooter
PublicLayout
SEO
```

sudah ada, audit dan improve.

Jangan menghapus lalu membuat ulang tanpa alasan.

### 21.2 Menambah UI library

Jangan menambahkan:

``` text
Ant Design
Material UI
Bootstrap
Chakra
Mantine
```

Public UI tetap:

``` text
Tailwind CSS
+
shadcn/ui
+
custom HSPMTB components
```

### 21.3 Hard-coded colors

Jangan:

``` tsx
className="bg-[#AB020E]"
```

jika token semantic sudah tersedia.

Prefer:

``` tsx
className="bg-primary"
```

### 21.4 Feature leakage

Shared component tidak boleh bergantung pada:

``` text
features/berita
features/agenda
features/pelayanan
```

Jika component benar-benar reusable, tempatkan di shared/public
component layer.

### 21.5 Premature abstraction

Jangan membuat:

``` text
BaseCardFactory
UniversalPageBuilder
DynamicComponentEngine
GenericDataRenderer
```

hanya untuk menghindari beberapa baris JSX.

Abstraction harus menyelesaikan kebutuhan nyata.

### 21.6 Fake production data

Jangan mengarang:

- nama pastor;
- nomor telepon;
- alamat;
- jadwal misa;
- sejarah paroki;
- koordinat;
- Google Form;
- social media.

Gunakan placeholder yang jelas.

------------------------------------------------------------------------

# 22. Suggested Commit Structure

Commit dapat dipisahkan berdasarkan logical task.

Contoh:

``` text
feat(public): establish HSPMTB design tokens
feat(public): refine public layout
feat(public): refine parish navbar
feat(public): refine parish footer
feat(public): add shared public components
feat(public): add public page shells
feat(public): complete SEO foundation
feat(public): improve responsive public shell
feat(public): improve public accessibility
feat(public): establish responsive image foundation
feat(public): refine public error states
test(public): cover public shell navigation
chore(public): validate phase 03
```

Gunakan conventional commit style yang sudah dipakai project.

------------------------------------------------------------------------

# 23. Suggested Implementation Checklist

## 03.01

- [ ] Audit tokens
- [ ] Fix semantic tokens
- [ ] Remove duplicate colors
- [ ] Validate typography
- [ ] Validate spacing
- [ ] Validate radius
- [ ] Validate shadows

## 03.02

- [ ] Audit PublicLayout
- [ ] Integrate Navbar
- [ ] Integrate Footer
- [ ] Semantic main
- [ ] Validate layout behavior

## 03.03

- [ ] Desktop navbar
- [ ] Navigation links
- [ ] Active states
- [ ] CTA
- [ ] Mobile Sheet
- [ ] Keyboard
- [ ] Touch targets

## 03.04

- [ ] Identity
- [ ] Quick links
- [ ] Contact
- [ ] Social links
- [ ] Legal
- [ ] Responsive footer

## 03.05

- [ ] Container
- [ ] Section
- [ ] SectionHeading
- [ ] CTA
- [ ] Breadcrumb
- [ ] EmptyState
- [ ] Skeleton
- [ ] Button variants

## 03.06

- [ ] Public routes
- [ ] Placeholder pages
- [ ] Layout assignment
- [ ] Route navigation

## 03.07

- [ ] SEO defaults
- [ ] Page overrides
- [ ] OG
- [ ] Canonical
- [ ] Twitter/X
- [ ] Favicon

## 03.08

- [ ] 360
- [ ] 390
- [ ] 420
- [ ] 640
- [ ] 768
- [ ] 834
- [ ] 1024
- [ ] 1068
- [ ] 1280
- [ ] 1440

## 03.09

- [ ] Semantic HTML
- [ ] Keyboard
- [ ] Focus
- [ ] ARIA
- [ ] Contrast
- [ ] Touch

## 03.10

- [ ] Responsive images
- [ ] srcset
- [ ] sizes
- [ ] eager hero
- [ ] lazy below fold
- [ ] alt
- [ ] layout stability

## 03.11

- [ ] 404
- [ ] 403
- [ ] 500

## 03.12

- [ ] Shell showcase
- [ ] Token showcase
- [ ] Component showcase
- [ ] Responsive showcase

## 03.13

- [ ] Backend tests
- [ ] Frontend checks
- [ ] E2E critical flows
- [ ] Responsive manual test

## 03.14

- [ ] Full validation
- [ ] Build
- [ ] No console errors
- [ ] No unrelated changes
- [ ] Phase approved

------------------------------------------------------------------------

# 24. Final Phase Acceptance Criteria

Phase 03 dapat dinyatakan **APPROVED** hanya jika seluruh kriteria
berikut terpenuhi:

### A. Design

- [ ] UI mengikuti `DESIGN.md`.
- [ ] HSPMTB Red/Navy/Gold digunakan sesuai hierarchy.
- [ ] White/soft-neutral tetap dominan.
- [ ] Tidak ada decorative gradient.
- [ ] Shadows restrained.
- [ ] Typography konsisten.
- [ ] Spacing konsisten.

### B. Architecture

- [ ] Tidak menggunakan React Router.
- [ ] Laravel tetap menjadi application router.
- [ ] Inertia tetap menjadi bridge.
- [ ] Shared component tidak bergantung pada feature.
- [ ] Tidak ada unrelated refactoring.
- [ ] Tidak ada premature abstraction.
- [ ] Feature-oriented structure tetap dipertahankan.

### C. Public UX

- [ ] Navbar desktop.
- [ ] Navbar mobile.
- [ ] Footer desktop.
- [ ] Footer mobile.
- [ ] Public layout.
- [ ] Shared public components.
- [ ] Placeholder public routes.

### D. Accessibility

- [ ] Semantic HTML.
- [ ] Keyboard accessible.
- [ ] Visible focus.
- [ ] Correct ARIA where necessary.
- [ ] 44×44px minimum interactive target.
- [ ] Reasonable color contrast.

### E. Performance Foundation

- [ ] Responsive image strategy.
- [ ] Hero eager loading.
- [ ] Below-fold lazy loading.
- [ ] No unnecessary client-side fetching.
- [ ] No unnecessary heavy dependency.
- [ ] No obvious layout shift from media dimensions.

### F. Quality

- [ ] Tests pass.
- [ ] TypeScript passes.
- [ ] Lint passes if configured.
- [ ] Production build succeeds.
- [ ] No runtime errors.
- [ ] No browser console errors introduced by Phase 03.

------------------------------------------------------------------------

# 25. Handoff ke Phase Berikutnya

Setelah Phase 03 selesai, public foundation harus dapat dianggap stabil.

Phase berikutnya boleh langsung membangun modul domain di atas:

``` text
PublicLayout
      │
      ├── Navbar
      ├── Footer
      ├── SEO
      ├── Container
      ├── Section
      ├── SectionHeading
      ├── CTA
      ├── Breadcrumb
      ├── EmptyState
      └── Skeleton
              │
              ▼
      Public Feature Modules
```

Modul berikutnya tidak boleh membuat ulang:

- navbar;
- footer;
- typography system;
- button primitive;
- container;
- section spacing;
- generic SEO;
- generic empty state;
- generic responsive behavior.

Mereka hanya menggunakan public foundation yang telah selesai.

------------------------------------------------------------------------

# 26. Phase Completion Record

## 26.1 Status

```text
Phase:  PHASE 03 — Design System & Public Website Shell
Status: READY FOR REVIEW
```

Implementation date: 2026-10-09
Branch: `feat/phase-03-design-system-public-website-shell`

## 26.2 Verification

| Perintah | Hasil |
| --- | --- |
| `npm run check` | 111 file terformat, 0 warning / 0 error |
| `npm run types:check` | LULUS |
| `npm run test:unit` (Vitest) | **59 / 59**, termasuk 6 spec AppearanceToggle |
| `./vendor/bin/pint --test` | LULUS |
| `./vendor/bin/phpstan analyse` | LULUS, 0 error (level 7) |
| `php artisan test` (Pest) | **388 / 388**, 1531 asersi |
| `npm run build` | LULUS, 20 page chunk baru |
| `npm run e2e` (Playwright) | **27 / 27**, termasuk 11 spec publik + 8 spec tema |
| Skrip kontras token | **45 / 45** pasangan lolos (lihat §26.3) |

### 26.3 Verifikasi kontras (D-32)

45 pasangan foreground/background dihitung dengan skrip yang **membaca ulang**
`app.css` — mengambil nilai dari blok `:root` dan `.dark`, bukan dari daftar
yang diketik manual, sehingga angka di D-32 bisa direproduksi setelah token
berubah.

Semua lolos. Yang terendah: `red-on-surface` di atas `popover` pada dark mode,
4.55:1. Border dan langkah permukaan sengaja di bawah 3:1 — DESIGN.md meminta
"clear but quiet borders" dan "one surface step above the page", dan itu memang
kontras yang rendah.

Fill tombol utama terhadap halaman adalah 2.28:1 di dark dan **sengaja
diterima**: tombol terisi teridentifikasi oleh labelnya (putih di atas merah,
7.65:1), bukan oleh warna isinya. Yang wajib 3:1 adalah *border*, dan border
outline memakai `red-on-surface`.

### 26.4 Catatan lingkungan untuk `npm run e2e`

Suite E2E memakai `php artisan serve` milik sendiri dengan `APP_DEBUG=false`
dan database e2e. Bila `artisan dev` sedang berjalan di port 8000,
`reuseExistingServer` memakainya dan **enam spec gagal tanpa disengaja** —
halaman error kembali ke 404 bawaan Laravel dan login tidak pernah selesai,
karena server tersebut memakai `APP_DEBUG=true` dan database dari `.env`.

Ini perilaku yang sudah didokumentasikan di `playwright.config.ts`. Verifikasi
Phase 03 dilakukan pada port 8010 untuk_confirmation tanpa mengganggu sesi
`artisan dev` yang sedang berjalan.


### 26.5 Apa yang ditambahkan

| Task | Hasil |
| --- | --- |
| 03.01 | 2 token elevation (`--shadow-soft-card`, `--shadow-image`). 17 token tipografi dan 7 radius sudah ada dari Phase 01 dan diverifikasi cocok. Nol hex brand di TSX. |
| 03.02 | `public-layout.tsx` diaudit; aturan "layout tidak membungkus children dalam container" ditulis sebagai komentar. |
| 03.03 | Navbar: 10 link Wayfinder (9 sebelumnya `aria-disabled`), active state per section + `aria-current`, CTA Jadwal Misa, logo asli, Sheet tertutup setelah navigasi. |
| 03.04 | Footer: 3 → 4 grup, `href: '#'` dan path hard-coded dihapus, nama paroki dari `seo.siteName`. |
| 03.05 | 6 komponen baru (`PublicContainer`, `PublicSection`, `SectionHeading`, `ParishCTA`, `ParishCard`, `ParishImage`); `EmptyState` dan `Breadcrumbs` diaudit. |
| 03.06 | 23 route placeholder + 20 page + 10 path `config/inertia.php`. |
| 03.07 | `robots` dan `og:locale` di `seo.tsx` **dan** `app.blade.php`. |
| 03.08 | Ladder tipografi responsif, container `px-4 sm:px-6 lg:px-8`, grid footer 6 kolom. |
| 03.09 | `aria-current`, heading level, skip-link; 44px diverifikasi di Vitest dan E2E. |
| 03.10 | `ParishImage`: `srcset`, `sizes`, eager/lazy, aspect-ratio, fallback. |
| 03.11 | Halaman error diaudit dan **dipertahankan**; ditemukan dan diperbaiki bug shared props. |
| 03.12 | `/design-system`, `noindex,follow`. |
| 03.13 | 53 test Vitest, 92 test Pest baru, 3 arch assertion, 6 spec E2E. |
| 03.14 | Gate penuh hijau. D-31 ditulis. |

### 26.6 Item yang TIDAK di-automate, dan kenapa

Checklist §18.1 dan §18.6 memuat item yang seluruhnya styling: pemilihan token,
radius, shadow, tipografi responsif, kontras warna. `tests rules` AGENTS.md
berkata "Pure copy, styling, and layout-only changes do not require new or
updated tests", sedangkan aturan lain berkata setiap acceptance criterion harus
punya test. Keduanya benar dan tidak dapat dipenuhi bersamaan.

**Yang di-automate** adalah kontrak yang bisa rusak sendiri dan tidak terlihat
dari mata: existence dan bentuk route, active state, semantik ARIA, target sentuh,
overflow, head-key soulsing dua lapisan, dan larangan URL hard-coded.

**Yang di-automate manual** adalah pilihan visual, melalui `vp check`, `tsc`, dan
review di sepuluh viewport.

### 26.7 Known issues

| # | Isu | Status |
| --- | --- | --- |
| 1 | 10 route `{slug}` mengembalikan 200 untuk slug apa pun, Melawan XC-E3 (MUST) | **Diusulkan** — D-31 §2 menamai fase yang menggantinya; `PublicPlaceholderRoutesTest` meng-pin daftarnya |
| 2 | `/design-system` akan ship ke produksi | **Diusulkan** — `noindex,follow`, D-31 §5 |
| 3 | Footer tidak punya tautan kebijakan privasi | **Diusulkan** — tidak ada route di PRD §5.1, D-31 §14 |
| 4 | `og:title` / `og:description` kosong sampai setting diisi | **Diusulkan** — blade memang begitu; dua test menutup kedua arah, D-31 §14 |
| 5 | Vitest mencetak `ECONNRESET` setelah suite | Kosmetik, exit code 0 — happy-dom mencoba memuat `<img>`, D-31 §13 |
| 6 | Footer dan navbar harus tahan halaman tanpa shared props | **Sudah diperbaiki** — D-31 §9 |
| 7 | `border-destructive` di 7 primitive shadcn 2.28:1 di dark | **Menunggu keputusan** — perlu `red-on-surface`, tapi file-nya tidak termasuk daftar yang AGENTS.md acknowledge sebagai restyled. D-32 §10 |
| 8 | `use-appearance.tsx` masih default `system`, DESIGN.md meminta `light` | **Menunggu keputusan** — perubahan perilaku, bukan styling. D-32 §10 |
| 9 | Warna merek yang tidak berbalik (`text-ink`, `text-navy`, `text-ink-muted`) gagal di dark | **Diusulkan** — sudah gagal sejak Phase 01, dark mode v1.1 hanya membuatnya terlihat. D-32 §7 |
| 10 | Hover baris sidebar ikut merah | **Diusulkan** — `ui/sidebar.tsx` memakai pasangan yang sama untuk hover dan active. D-32 §5 |
| 11 | Review visual dua tema belum dilakukan | **Belum** — angka bukan mata |

### 26.8 Decision references

- `D-31` — public website shell, 10 route sementara, halaman error tanpa shared props
- `D-32` — DESIGN.md v1.1: dark mode netral, pemisahan merah, ring fokus, tombol tema publik

---

# 27. Phase 03 Summary

``` text
PHASE 03
Design System & Public Website Shell

INPUT
├── PRD.md
├── ARCHITECTURE.md
└── DESIGN.md

        ↓

DESIGN FOUNDATION
├── Tokens
├── Typography
├── Spacing
├── Radius
└── Elevation

        ↓

PUBLIC SHELL
├── PublicLayout
├── ParishNavbar
├── ParishFooter
└── Public Page Shell

        ↓

SHARED UI
├── Container
├── Section
├── SectionHeading
├── CTA
├── Breadcrumb
├── EmptyState
└── Skeleton

        ↓

QUALITY
├── SEO
├── Responsive
├── Accessibility
├── Image foundation
└── Error states

        ↓

VALIDATION
├── Tests
├── Build
├── Responsive QA
├── Accessibility QA
└── Phase approval

        ↓

READY FOR NEXT PHASE
```

**End of Phase 03 specification.**
