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
| **Phase 8**  | Gallery, Download & Contact               | 🟡 8A Galeri selesai |
| **Phase 9**  | Homepage & Admin Dashboard                | ⚪ Planned |
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

### Phase 8A — Galeri (modul selesai)

Phase 8A membangun modul **Galeri**: album dan fotonya, admin dan publik. Admin
Galeri berada di `/admin/galeri` dengan pengelola foto di
`/admin/galeri/{album}/foto`; halaman publik dinamis `/galeri` dan
`/galeri/{slug}` menggantikan route placeholder Phase 03.

Foto menunjuk baris `media` (bukan kolom path), sampul menunjuk foto di album
dengan fallback foto pertama, status cukup `is_published` boolean, dan limit
unggah mengikuti `config/media.php` (JPEG/PNG, 3 MB) — semuanya dicatat di D-65.
Phase 8B (Dokumen/Download) dan 8C (Kontak) belum dikerjakan.

Dokumen implementasi:

```text
roadmap/phase-8A-galleries.md
plan-implementation/plan-phase-8a-galleries.md
```

> **Engineering complete; konten belum.** Tabel, CRUD, unggah batch, sampul,
> urutan, lightbox, dan test ada. Tidak ada seeder produksi: album dan foto
> harus diunggah dan divalidasi paroki sebelum dipublikasikan.

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

