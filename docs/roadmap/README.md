# Project Roadmap

Roadmap pengembangan **Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau**.

README ini merupakan **high-level roadmap** proyek. Setiap phase memiliki dokumen implementasi tersendiri di dalam folder `/roadmap`.

> **Source of Truth:** `PRD.md`. AI agent wajib membaca `PRD.md` dan dokumen phase terkait sebelum melakukan implementasi.

---

## Development Roadmap

```text
PHASE 0  — Product Scope & Initial Preparation (DONE)
    ↓
PHASE 1  — Project Foundation
    ↓
PHASE 2  — Authentication & Admin Foundation
    ↓
PHASE 3  — Design System & Public Website Shell
    ↓
PHASE 4  — Mass Schedule
    ↓
PHASE 5  — News & Agenda (DONE)
    ↓
PHASE 6A — Parish Profile (Profil Paroki)
    ↓
PHASE 6B — Parish Services (Pelayanan)
    ↓
PHASE 7  — Communities
    ↓
PHASE 8  — Gallery, Download & Contact
    ↓
PHASE 9  — Homepage & Admin Dashboard
    ↓
PHASE 10 — QA, Performance & Security
    ↓
PHASE 11 — Production, Content Population & Handover
```

---

## Phase Overview

| Phase        | Focus                                     | Status     |
| ------------ | ----------------------------------------- | ---------- |
| **Phase 0**  | Product Scope & Initial Preparation       | ✅ Done    |
| **Phase 1**  | Project Foundation                        | ✅ Done    |
| **Phase 2**  | Authentication & Admin Foundation         | ✅ Done    |
| **Phase 3**  | Design System & Public Website Shell      | ✅ Done    |
| **Phase 4**  | Mass Schedule                             | ✅ Done    |
| **Phase 5**  | News & Agenda                             | ✅ Done    |
| **Phase 6A** | Parish Profile (Profil Paroki)             | ✅ Done    |
| **Phase 6B** | Parish Services (Pelayanan)                | ✅ Done    |
| **Phase 7**  | Communities                               | ✅ Done    |
| **Phase 8**  | Gallery, Download & Contact               | 🟡 8A Galeri, 8B Dokumen & 8C Kontak selesai |
| **Phase 9**  | Homepage & Admin Dashboard                | 🟡 9A Beranda & 9B Dashboard selesai |
| **Phase 10** | QA, Performance & Security                | ⚪ Planned |
| **Phase 11** | Production, Content Population & Handover | ⚪ Planned |

---

## Phase Documents

Setiap phase memiliki file implementasi detail:

```text
roadmap/
├── README.md
├── phase-01-project-foundation.md
├── phase-02-authentication-admin-foundation.md
├── phase-03-design-system-public-website-shell.md
├── phase-04-mass-schedule.md
├── phase-05-news-agenda.md
├── phase-6A-parish-profile.md
├── phase-06-parish-profile-services.md
├── phase-07-communities.md
├── phase-08-gallery-download-contact.md
├── phase-09-homepage-admin-dashboard.md
├── phase-10-qa-performance-security.md
└── phase-11-production-content-handover.md
```

> Nama file dapat disesuaikan saat masing-masing phase dibuat, tetapi **urutan dan pembagian phase mengikuti roadmap di atas**.

---

## Cara Menggunakan Roadmap

### 1. Tentukan Phase Aktif

AI agent hanya mengerjakan phase yang sedang aktif.

Contoh:

```text
Current Phase:
Phase 01 — Project Foundation
```

### 2. Baca Dokumen Phase

Setelah phase ditentukan, agent membaca file implementasinya:

```text
roadmap/phase-01-project-foundation.md
```

File tersebut berisi detail:

- scope;
- tasks;
- dependencies;
- acceptance criteria;
- testing;
- Definition of Done;
- guardrails.

### 3. Implementasikan Secara Bertahap

Agent mengerjakan task berdasarkan urutan dependency yang terdapat pada dokumen phase.

### 4. Verifikasi Phase

Phase hanya dapat dinyatakan selesai apabila seluruh:

- task;
- test;
- acceptance criteria;
- Definition of Done

telah terpenuhi.

Kemudian lanjut ke phase berikutnya.

---

## Rules for AI Agents

1. **Jangan melompati phase.**
2. Baca `PRD.md` sebelum implementasi.
3. Baca dokumen phase yang sedang dikerjakan.
4. Jangan mengimplementasikan fitur yang berada di phase berikutnya.
5. Jangan mengarang data nyata paroki.
6. Setiap Acceptance Criteria harus memiliki test yang sesuai.
7. Gunakan architecture dan conventions yang telah ditentukan project.
8. Jika menemukan requirement yang ambigu, dokumentasikan keputusan di `docs/DECISIONS.md`.
9. Jangan membangun fitur yang berada di luar scope MVP.
10. Phase hanya boleh ditandai `DONE` setelah Definition of Done terpenuhi.

---

## Current Focus

### Migrasi DESIGN-v2 (aktif)

`docs/DESIGN-v2.md` menggantikan `docs/DESIGN.md` v1.1 sebagai satu-satunya
acuan desain UI. Migrasi berjalan bertahap M0–M6: dokumentasi & token, lalu
tipografi/primitif, shell publik, halaman publik per-route, **redesign admin
penuh**, dan verifikasi. Primary tetap `#AB020E`; dark mode memakai kanvas
netral. Rencana dan tabel pemetaan token ada di
`plan-implementation/plan-design-v2-migration.md`; keputusan di `DECISIONS.md`
D-72.

### Phase 9A — Beranda Publik (selesai)

Phase 9A mengganti placeholder Beranda dengan halaman publik final yang
mengagregasi modul Berita, Agenda, Pelayanan, dan Galeri. Misa berikutnya tetap
berasal dari `NextMassResolver`; berita dan agenda mengikuti scope publik
masing-masing; renungan membaca post terbaru kategori `renungan`; galeri
membatasi jumlah **foto** sesuai `home_gallery_limit`; dan blok kontak memakai
shared prop `contact` beserta petanya.

Hero sekarang dapat dikelola dari **Pengaturan Situs** melalui empat key baru:
`home_hero_title`, `home_hero_description`, `home_hero_image`, dan
`home_hero_image_alt`. Bila gambar belum diunggah, Beranda memakai aset
`public/hero-section.webp` (OG memakai PNG). Announcement bar dan `hero_slides`
**tidak** dibangun di fase ini; keputusan dan divergensinya dicatat di D-70.

Dokumen implementasi:

```text
roadmap/phase-9a-homepage.md
plan-implementation/plan-phase-9a-homepage.md
```

> **Engineering complete; konten belum.** Berita, agenda, pelayanan, galeri, dan
> kontak tetap kosong sampai paroki mengisi dan memvalidasi kontennya. E2E
> berjalan pada database bare dan sengaja menguji empty state.

### Phase 9B — Admin Dashboard (selesai)

Phase 9B mengganti placeholder Dashboard dengan agregator read-only:
lima kartu ringkasan (Berita, Agenda, Dokumen, Jadwal Misa, Pelayanan), Aksi
Cepat, Agenda Terdekat, Berita Terbaru, Perlu Perhatian, dan Konten Terbaru.
Semua angka dihitung di backend dengan definisi yang terdokumentasi; tidak ada
tabel statistik, analytics, atau audit log baru. Route `dashboard` berpindah dari
`Route::inertia` ke controller tipis, dengan middleware dan otorisasi yang sama.

Dokumen implementasi:

```text
roadmap/phase-9b-admin-dashboard.md
plan-implementation/plan-phase-9b-admin-dashboard.md
```

Keputusan definisi statistik dan cakupan "Perlu Perhatian" ada di D-76 dan D-77.
Delapan belas test Pest (`tests/Feature/DashboardTest.php`), satu suite Vitest, dan
satu spec Playwright (`tests/e2e/dashboard.spec.ts`) menjaga perilakunya.

> **Engineering complete; konten belum.** Database kosong menampilkan angka 0 dan
> empty state, bukan error; tidak ada seeder produksi.

### Phase 8A — Galeri (modul selesai)

Phase 8A membangun modul **Galeri**: album dan fotonya, admin dan publik. Admin
Galeri berada di `/admin/galeri` dengan pengelola foto di
`/admin/galeri/{album}/foto`; halaman publik dinamis `/galeri` dan
`/galeri/{slug}` menggantikan route placeholder Phase 03.

Foto menunjuk baris `media` (bukan kolom path), sampul menunjuk foto di album
dengan fallback foto pertama, status cukup `is_published` boolean, dan limit
unggah mengikuti `config/media.php` (JPEG/PNG, 3 MB) — semuanya dicatat di D-65.
Phase 8B (Dokumen) dan Phase 8C (Kontak) selesai; lihat bagian berikut.

Dokumen implementasi:

```text
roadmap/phase-8A-galleries.md
roadmap/phase-8b-documents.md
roadmap/phase-8c-contacts.md
plan-implementation/plan-phase-8a-galleries.md
plan-implementation/plan-phase-8b-documents.md
plan-implementation/plan-phase-8c-contacts.md
```

> **Engineering complete; konten belum.** Tabel, CRUD, unggah batch, sampul,
> urutan, lightbox, dan test ada. Tidak ada seeder produksi: album dan foto
> harus diunggah dan divalidasi paroki sebelum dipublikasikan.

### Phase 8B — Dokumen (modul selesai)

Phase 8B membangun modul **Dokumen**: berkas PDF/Office/ZIP disimpan di disk
privat, diunduh lewat endpoint terkontrol, dan dikelola dari `/admin/dokumen`
dengan kategori di `/admin/master-data/kategori-dokumen`. Halaman publik
`/dokumen` menampilkan dokumen Terbit, filter kategori, pencarian, dan seksi
unggulan. Dokumen juga dapat ditautkan ke layanan lewat tab “Dokumen Terkait”,
menutup penundaan `document_service` dari D-54. Status memakai enum
`draft/published/archived`, berkas memakai pipeline terpisah dari Media, dan URL
lama `/download` diarahkan permanen. Detail keputusan ada di D-66.

> **Engineering complete; konten belum.** Tidak ada seeder dokumen produksi.
> Kategori di-seed dari PRD, tetapi berkas dan metadatanya harus diunggah dan
> divalidasi paroki.

### Phase 8C — Kontak (modul selesai)

Phase 8C membangun halaman publik `/kontak` dari **Pengaturan Situs**, tanpa
tabel atau menu admin baru. Data kontak menjadi satu shared prop `contact`
(`HandleInertiaRequests`), sehingga halaman Kontak, footer, dan blok kontak
beranda membaca sumber yang sama. Telepon/WhatsApp memakai `PhoneNumber`,
email hanya dirender bila valid, dan titik aksi disembunyikan bila datanya
kosong. Admin hanya menempel satu link Google Maps (`maps_link`); iframe peta
dan tautan petunjuk arah **diturunkan otomatis** dari link itu (endpoint publik
`?output=embed`, tanpa API key), jadi tidak ada field URL embed. Link pendek
`maps.app.goo.gl` ditolak karena tidak memuat koordinat. Aksi Salin disertakan.
`home_show_contact` mengatur blok beranda saja, bukan `/kontak`. Structured data
Organization ditunda. Detail keputusan ada di D-67.

> **Engineering complete; konten belum.** Tidak ada seeder kontak produksi.
> Alamat, telepon, WhatsApp, surel, jam layanan, dan URL peta harus diisi dan
> divalidasi paroki sebelum dipublikasikan.

### Revisi — Konsolidasi identitas paroki (D-69)

Modul Informasi Profil (`/admin/profil/informasi`, `/profil`, tabel
`parish_profiles`) **dihapus**. Identitas dan kontak global kini hanya dikelola
di **Pengaturan Situs** (grup Identitas Paroki, Kontak, dan grup baru Profil
Paroki). Narasi sejarah pindah ke tabel singleton `parish_history_narratives`
dan dikelola di halaman admin **Sejarah Paroki**; halaman publik `/profil/sejarah`
tetap ada, sedangkan `/profil` (ringkasan) dihapus dan tautan "Profil" di navbar
dan footer diarahkan ke `/profil/sejarah`. Navbar dan footer membaca identitas
dari shared prop `identity`. Detail dan divergensi ada di D-69.

### Phase 7 — Communities (Komunitas)

Phase ini membangun modul **Komunitas** (master kategori komunitas, profil
komunitas, logo dan thumbnail, kontak dengan kontrol privasi, serta publikasi)
dan modul terpisah **Area** (hierarki stasi/wilayah/lingkungan
beserta pengurusnya). Admin Komunitas berada di `/admin/komunitas` dengan
Kategori Komunitas di `/admin/master-data/kategori-komunitas`; admin Area
berada di `/admin/area`. Halaman publik dinamis:
`/komunitas`, `/komunitas/{slug}`, `/area`, dan
`/area/{slug}`.

Kategori dinormalisasi dari `community_types` PRD ke tabel master
`community_categories` (D-55); logo/thumbnail memakai FK `*_media_id` ke tabel
`media`, bukan kolom path (divergensi sadar dari roadmap §5.2.3); `areas`
diperluas dengan `stasi` dan field pengurus (PRD §9.9) dan kini modul mandiri.
Relasi komunitas↔area dilepas dan halaman publik Area ditambahkan
(D-57); kontak pengurus dirender publik dengan privasi server-side (XC-P1).
Tidak ada `community_id` pada `posts`/`events` (D-43), dan galeri komunitas
ditunda ke Phase 8.

Dokumen implementasi:

```text
roadmap/phase-07-communities.md
plan-implementation/plan-phase-07-communities.md
```

Keputusan Phase 7 tercatat di `docs/DECISIONS.md` D-55, D-56, D-57.

> **Engineering complete; konten belum.** Tabel, CRUD, halaman publik, validasi,
> privasi kontak, dan test ada. Tidak ada seeder produksi: kategori, wilayah,
> lingkungan, dan profil komunitas harus diisi dan divalidasi paroki sebelum
> dipublikasikan. `DemoContentSeeder` mengisi contoh (tanpa kontak/nama nyata)
> hanya saat `demo.enabled`. Lihat D-34/D-53/D-55.

```text
Phase 6B (Done — engineering complete)
   ↓
Phase 7 (Done — engineering complete)
   ↓
Phase 8 — Gallery, Download & Contact
```

---

