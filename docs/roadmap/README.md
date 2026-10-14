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
| **Phase 7**  | Communities                               | ⚪ Planned |
| **Phase 8**  | Gallery, Download & Contact               | ⚪ Planned |
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

### Phase 6B — Parish Services (Pelayanan)

Phase ini membangun modul **Pelayanan**: master kategori layanan, enam layanan
MVP (`baptis-bayi`, `baptis-dewasa`, `komuni-pertama`, `krisma`, `pernikahan`,
`perminyakan-orang-sakit`), persyaratan, prosedur, FAQ, jadwal/biaya, kontak per
layanan, dan opsi Google Form. Admin panel berada di `/admin/pelayanan` dan
`/admin/master-data/kategori-pelayanan`; halaman publik dinamis di `/pelayanan`
dan `/pelayanan/{slug}`.

Kategori layanan dinormalisasi dari ENUM PRD ke tabel master `service_categories`
(D-54). Kontak global tetap satu sumber di Pengaturan Situs; `document_service`
ditunda ke Phase 8.

Dokumen implementasi:

```text
roadmap/phase-6B-parish-service.md
plan-implementation/plan-phase-06b-parish-service.md
```

Phase 1 sampai Phase 6A selesai lebih dahulu. Keputusan Phase 6B tercatat di
`docs/DECISIONS.md` D-54.

> **Engineering complete; konten belum.** Tabel, CRUD, halaman publik, validasi,
> dan test ada. Enam layanan di-seed **nonaktif** tanpa `form_url` dan tanpa
> syarat/prosedur/FAQ — konten resmi (syarat, prosedur, biaya, jadwal, nomor
> kontak darurat, URL Google Form) harus diisi dan divalidasi paroki sebelum
> layanan diaktifkan. Lihat D-34/D-54.

Setelah Phase 6B selesai:

```text
Phase 6A (Done)
   ↓
Phase 6B (Done — engineering complete)
   ↓
Phase 7 — Communities
```

---

