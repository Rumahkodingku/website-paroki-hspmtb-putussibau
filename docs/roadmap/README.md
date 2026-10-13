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
| **Phase 6A** | Parish Profile (Profil Paroki)             | 🚧 Active  |
| **Phase 6B** | Parish Services (Pelayanan)                | ⚪ Planned |
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

### Phase 6A — Profil Paroki (Parish Profile)

Phase ini membangun modul **Profil Paroki**: profil utama (singleton),
sejarah (narasi + linimasa), wilayah pelayanan (singleton narasi), pastor
(identitas + riwayat penugasan), dan struktur kepengurusan (periode +
anggota). Admin panel berada di `/admin/profil/*` dan halaman publik dinamis
di `/profil`, `/profil/sejarah`, `/profil/wilayah`, `/profil/pastor`, dan
`/profil/struktur`.

**Visi & Misi tidak termasuk** dalam Phase 6A dan route/halaman/link-nya
dihapus (D-52). Phase 6B (Pelayanan) dikerjakan terpisah.

Dokumen implementasi:

```text
roadmap/phase-6A-parish-profile.md
plan-implementation/plan-phase-06a-parish-profile.md
```

Phase 1 sampai Phase 5 selesai lebih dahulu. Keputusan Phase 6A tercatat di
`docs/DECISIONS.md` D-52 ke atas.

> **Isi modul belum ada.** Tabel dan form-nya ada; data resmi paroki (sejarah,
> pastor, pengurus, wilayah) belum. Tidak ada seeder konten — hanya struktur
> kosong. Lihat D-34 untuk prinsip yang sama pada Jadwal Misa.

Setelah Phase 6A selesai:

```text
Phase 5 (Done)
   ↓
Phase 6A (Active)
   ↓
Phase 6B
```

---

