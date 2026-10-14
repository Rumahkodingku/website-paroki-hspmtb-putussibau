# PHASE 6B — Parish Service (Modul Pelayanan)

> **Proyek:** Website Gereja Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau  
> **File:** `docs/roadmap/phase-6B-parish-service.md`  
> **Status:** Implementation plan — siap dikerjakan setelah audit codebase  
> **Dependency:** PHASE 6A — Profil Paroki  
> **Stack:** Laravel 13, Inertia.js 3, React 19, TypeScript, Tailwind CSS 4, MySQL  
> **Bahasa UI:** Bahasa Indonesia  
> **Zona waktu tampilan:** `Asia/Pontianak`

---

## 1. Tujuan Dokumen dan Instruksi untuk AI Agent

Dokumen ini merupakan panduan implementasi PHASE 6B bagi AI coding agent. Dokumen ini menentukan scope, model data yang diharapkan, urutan task, aturan bisnis, acceptance criteria, dan Definition of Done.

AI agent MUST:

1. Membaca dokumen ini beserta `docs/PRD.md`, `docs/ARCHITECTURE.md`, `docs/DESIGN.md`, `docs/DECISIONS.md`, dan implementasi PHASE 6A.
2. Memeriksa codebase aktual sebelum membuat migration, model, route, halaman, atau komponen.
3. Menggunakan pola yang sudah dipakai di PHASE 4, PHASE 5, dan PHASE 6A.
4. Mengerjakan task secara bertahap; jalankan test yang sesuai pada setiap workstream.
5. Memperbarui task dan dokumentasi hanya setelah hasil implementasi diverifikasi.
6. Melaporkan test/build yang benar-benar dijalankan; jangan mengklaim lulus tanpa bukti.
7. Memisahkan status **engineering complete** dan **content/go-live ready**.

Dokumen ini **bukan instruksi untuk melakukan rewrite aplikasi**. Perubahan harus inkremental, menggunakan kembali infrastruktur yang ada, dan tidak merusak PHASE 4, 5, maupun 6A.

### Prioritas sumber kebenaran

1. `docs/PRD.md` adalah source of truth requirement produk.
2. `docs/ARCHITECTURE.md` mengatur struktur kode dan pola implementasi.
3. `docs/DECISIONS.md` memuat keputusan proyek yang sudah disepakati.
4. Dokumen ini memecah scope PHASE 6B menjadi task implementasi.
5. Kondisi codebase aktual harus diaudit agar fitur/tabel yang sudah tersedia tidak dibuat ulang.

Jika konflik tidak dapat diselesaikan dari dokumen tersebut, jangan mengambil keputusan berisiko secara diam-diam. Catat pertanyaan dan usulkan opsi di laporan.

---

## 2. Tujuan Modul

Modul Pelayanan memberikan informasi yang jelas bagi umat mengenai syarat, prosedur, persiapan, kontak, dan cara mendaftar untuk layanan sakramental/pastoral. Super Admin dapat mengelola informasi melalui Admin Panel tanpa mengubah kode.

Hasil PHASE 6B:

- Halaman publik indeks layanan di `/pelayanan`.
- Halaman publik detail layanan di `/pelayanan/{slug}`.
- Pengelolaan layanan dari Admin Panel.
- Pengelolaan syarat dokumen per layanan.
- Pengelolaan langkah prosedur per layanan.
- Pengelolaan FAQ per layanan.
- Pengelolaan jadwal/persiapan, biaya/persembahan, dan kontak per layanan.
- Opsi Google Form yang **sepenuhnya opsional untuk setiap layanan**.
- Alur kontak langsung untuk layanan yang tidak menggunakan Google Form.
- Perlakuan khusus pada layanan darurat Perminyakan Orang Sakit.
- Validasi backend, otorisasi, sanitasi konten, testing, responsivitas, mode cetak, dan dokumentasi.

---

## 3. Scope PHASE 6B

### 3.1 Enam layanan awal yang wajib tersedia

|   # | Nama layanan                      | Slug awal                 | Kategori      | Google Form                      |
| --: | --------------------------------- | ------------------------- | ------------- | -------------------------------- |
|   1 | Baptis Bayi                       | `baptis-bayi`             | `inisiasi`    | Opsional                         |
|   2 | Baptis Dewasa                     | `baptis-dewasa`           | `inisiasi`    | Opsional                         |
|   3 | Komuni Pertama                    | `komuni-pertama`          | `inisiasi`    | Opsional                         |
|   4 | Krisma                            | `krisma`                  | `inisiasi`    | Opsional                         |
|   5 | Pernikahan                        | `pernikahan`              | `perkawinan`  | Opsional                         |
|   6 | Pelayanan Perminyakan Orang Sakit | `perminyakan-orang-sakit` | `orang_sakit` | Tidak digunakan; kontak langsung |

Enam record awal ini merupakan kerangka layanan MVP yang ditetapkan PRD. Nama/slug/kategori awal tidak berarti syarat dan prosedurnya sudah disetujui. Layanan harus berstatus nonaktif sampai konten divalidasi pihak paroki.

Admin boleh menentukan cara pendaftaran **untuk setiap layanan secara terpisah**:

- mengisi Google Form; atau
- tidak menggunakan Google Form dan mengarahkan umat ke kontak sekretariat/penanggung jawab yang tersedia.

URL Google Form **bukan field wajib** dan tidak boleh dipaksa terisi pada create/update. Status `is_form_open` hanya bermakna jika `form_url` terisi dan layanan bukan layanan darurat.

### 3.2 Route publik

| Route               | Tujuan                                                   |
| ------------------- | -------------------------------------------------------- |
| `/pelayanan`        | Daftar layanan aktif, dikelompokkan berdasarkan kategori |
| `/pelayanan/{slug}` | Detail satu layanan yang aktif dan boleh dipublikasikan  |

Route yang ada saat ini mungkin masih berupa placeholder. Audit dan ganti implementasinya tanpa mengubah nama route yang dipakai bagian lain kecuali memang diperlukan.

### 3.3 Admin Panel: navigasi Pelayanan dan Master Data

**Keputusan UI terbaru berdasarkan instruksi pemilik proyek:** menu **Pelayanan** adalah tautan navigasi biasa. Saat diklik, halaman langsung membuka **Daftar Pelayanan**. Menu Pelayanan **tidak expandable**, tidak memiliki chevron, dan tidak memiliki submenu.

Kategori pelayanan dikelola dari submenu **Master Data**, bukan dari dalam menu Pelayanan. Kontak global paroki tetap dikelola melalui menu **Pengaturan / Pengaturan Situs** yang sudah ada; jangan membuat halaman atau submenu Kontak & Pengaturan khusus Pelayanan.

Struktur navigasi yang diharapkan:

```text
Pelayanan  → /admin/pelayanan (langsung membuka Daftar Pelayanan)

Master Data  (tetap expandable sesuai sidebar existing)
├── [submenu Master Data yang sudah ada, jangan dihapus]
└── Kategori Pelayanan  → /admin/master-data/kategori-pelayanan

Pengaturan  → Pengaturan Situs yang sudah ada
└── Kontak (tab existing untuk alamat, telepon, WhatsApp, email, jam layanan, dan peta)
```

Ketentuan:

- Item sidebar `Pelayanan` langsung menuju halaman Daftar Pelayanan dengan route `/admin/pelayanan` (atau nama route setara yang telah dipakai codebase). Jangan membuat toggle expand/collapse untuk Pelayanan.
- Jangan menampilkan submenu `Overview`, `Daftar Pelayanan`, `Kategori`, `Kontak & Pengaturan`, atau nama-nama layanan di bawah menu Pelayanan. Daftar Pelayanan adalah halaman tujuan langsung parent.
- `Kategori Pelayanan` berada di bawah parent `Master Data`, bersama submenu Master Data lain yang sudah ada. Jangan menghapus atau mengganti submenu existing.
- `Kategori Pelayanan` membuka pengelolaan master kategori layanan pada route yang mengikuti konvensi aktual; usulan route baru adalah `/admin/master-data/kategori-pelayanan`.
- Kontak global paroki dikelola di Pengaturan Situs > tab Kontak yang telah tersedia. Gunakan `site_settings`/helper existing sebagai fallback; jangan membuat tabel, page, route, atau submenu kontak global kedua.
- Kontak yang khusus untuk satu layanan (misalnya penanggung jawab/nomor layanan) tetap dapat diatur melalui tab **Kontak & Pendaftaran** di halaman edit layanan.
- Admin mengelola tiap layanan lewat tabel Daftar Pelayanan dan tombol aksi Edit; jangan hard-code ID database ke navigasi.
- Pastikan active state `Pelayanan`, `Master Data`, dan `Kategori Pelayanan` sesuai route yang sedang dibuka, tanpa mengubah active state menu lain.

**Perhatian schema kategori:** PRD awal mendefinisikan `services.category` sebagai ENUM. Karena kategori akan dikelola lewat Master Data, rancangan ini menggunakan master `service_categories` dan FK `services.service_category_id`. Sebelum migration, agent MUST mencatat keputusan di `docs/DECISIONS.md` dan menyelaraskan schema PRD. Migrasi data lama dari enum harus mempertahankan relasi layanan. Jangan mempertahankan enum dan FK sebagai dua sumber data aktif.

### 3.4 Fitur yang tidak termasuk

- Form pendaftaran native yang mengirim data umat ke server website.
- Tabel pendaftar, calon penerima sakramen, submissions, atau penyimpanan jawaban Google Form.
- Sinkronisasi Google Form/Sheets API, notifikasi jawaban, atau otomasi Google Workspace.
- Pembayaran/persembahan online.
- Workflow approval multi-role; MVP menggunakan otorisasi Super Admin yang tersedia.
- Pembuatan modul Download baru. Integrasi dokumen mengikuti kesiapan PHASE 8.
- CRUD Komunitas, Stasi, Lingkungan, dan master wilayah milik PHASE 7.
- Pembuatan isi kebijakan pastoral dari asumsi AI agent seolah-olah resmi.
- Role/permission management UI atau role baru di luar scope MVP.

---

## 4. Prasyarat dan Audit Codebase

Sebelum menulis kode:

- [ ] **6B-001** Baca `docs/PRD.md`, `docs/ARCHITECTURE.md`, `docs/DESIGN.md`, dan `docs/DECISIONS.md`.
- [ ] **6B-002** Baca `docs/roadmap/README.md` dan file implementasi PHASE 6A.
- [ ] **6B-003** Audit route publik `/pelayanan` dan `/pelayanan/{slug}` yang ada.
- [ ] **6B-004** Audit apakah model, migration, factory, seeder, controller, Form Request, atau test untuk `Service`/Pelayanan sudah tersedia.
- [ ] **6B-005** Audit pola CRUD PHASE 4–6A: route, controller, Action, Policy, Resource, pagination, flash message, dan validasi.
- [ ] **6B-006** Audit implementasi sidebar: parent click, submenu, active state, collapse, dan aksesibilitas.
- [ ] **6B-007** Audit rich text editor dan sanitasi HTML server-side yang tersedia.
- [ ] **6B-008** Audit media system dan icon system. Jangan membuat mekanisme media/ikon baru.
- [ ] **6B-009** Audit model/tabel Download (`documents`, `document_categories`) dan pivot terkait bila sudah ada.
- [ ] **6B-010** Audit `site_settings` untuk data kontak sekretariat dan sumber kontak tunggal.
- [ ] **6B-011** Audit middleware `auth`, `verified`, `EnsureAccountIsActive`, `EnsureAdminAccess`, serta pola Policy/Gate.
- [ ] **6B-012** Periksa `composer.json`, `package.json`, dan CI untuk command test, lint, typecheck, build.
- [ ] **6B-013** Catat baseline test/build jika lingkungan memungkinkan.
- [ ] **6B-014** Buat daftar perubahan yang benar-benar diperlukan. Jangan buat ulang tabel atau komponen yang sudah ada.
- [ ] **6B-015** Catat keputusan domain yang belum ditetapkan di `docs/DECISIONS.md` setelah audit.

**Dilarang menebak nama class, komponen, route name, permission, atau npm script.** Gunakan pola aktual proyek.

---

## 5. Prinsip Arsitektur dan Konvensi

1. **Modular monolith:** Laravel + Inertia. Jangan menambahkan REST API terpisah untuk halaman Inertia.
2. **Laravel adalah sumber kebenaran:** validasi, authorization, business rules, dan query publikasi ditangani backend.
3. **Controller tipis:** CRUD satu model sederhana tetap menggunakan Eloquent di controller; gunakan Action untuk operasi multi-model, transaction, side effect, atau operasi yang dipakai ulang.
4. **Form Request:** gunakan Form Request untuk write operation sesuai pola Architecture.
5. **Authorization satu tempat:** store/update di Form Request jika itu pola yang digunakan proyek; index/edit/destroy/custom actions di Policy/Gate/route middleware. Hindari otorisasi ganda yang tidak konsisten.
6. **Eloquent default:** jangan menambah Repository untuk CRUD biasa.
7. **Inertia props:** server mengirim data yang dibutuhkan halaman. Jangan menduplikasi seluruh props ke global state.
8. **Reuse:** gunakan AdminLayout, PublicLayout, UI primitives, form fields, dialog, toast, uploader/media picker, rich text editor, dan data-table existing.
9. **Status publik:** record nonaktif/draft/soft-deleted tidak boleh terungkap melalui route publik.
10. **Bahasa:** teks UI dan validasi Bahasa Indonesia; nama kode/tabel Inggris `snake_case`.
11. **Security:** sanitasi HTML di server; validasi URL eksternal dan nomor telepon di backend.
12. **No unrelated refactor:** jangan mengubah PHASE 4, 5, atau 6A kecuali diperlukan untuk integrasi; tambahkan regression tests bila terjadi perubahan.
13. **No invented parish content:** data syarat, prosedur, biaya, jadwal, telepon, dan form URL tidak boleh dianggap resmi tanpa validasi pihak paroki.

---

## 6. Model Data

Schema pada PRD Bagian 9.8 merupakan acuan. Gunakan bagian ini sebagai kontrak perilaku dan audit migration actual sebelum membuat tabel. Field yang sudah tersedia tidak boleh diduplikasi.

### 6.1 Tabel `service_categories`

Tabel master kategori diperlukan agar halaman **Kategori Pelayanan** di bawah menu **Master Data** benar-benar berfungsi sebagai halaman pengelolaan kategori, bukan tautan statis. Perubahan dari enum kategori di PRD awal wajib dicatat pada `docs/DECISIONS.md` dan diselaraskan di schema PRD sebelum migration dibuat.

| Field         | Tipe konseptual        | Aturan                                          |
| ------------- | ---------------------- | ----------------------------------------------- |
| `id`          | BIGINT UNSIGNED        | PK                                              |
| `name`        | VARCHAR(100)           | Wajib; label kategori                           |
| `slug`        | VARCHAR(100)           | Wajib, unique                                   |
| `description` | TEXT, nullable         | Deskripsi kategori opsional                     |
| `icon`        | VARCHAR(100), nullable | Icon key dari allowlist icon proyek             |
| `sort_order`  | INT UNSIGNED           | Default `0`                                     |
| `is_active`   | BOOLEAN                | Default `true`                                  |
| `created_at`  | TIMESTAMP              | Wajib                                           |
| `updated_at`  | TIMESTAMP              | Wajib                                           |
| `deleted_at`  | TIMESTAMP, nullable    | Soft delete bila mengikuti konvensi master data |

Seed kategori bawaan:

- `inisiasi` — Sakramen Inisiasi
- `perkawinan` — Sakramen Perkawinan
- `orang-sakit` — Pelayanan Orang Sakit

Aturan:

- Kategori awal berasal dari PRD; seeder idempotent dan tidak boleh menimpa perubahan admin.
- Kategori yang masih dipakai layanan tidak boleh dihapus secara fisik; tampilkan validasi atau nonaktifkan kategori.
- Kategori nonaktif tidak dapat dipilih untuk layanan baru dan layanan di kategori nonaktif tidak boleh diaktifkan/dipublikasikan sampai kategori aktif kembali atau layanan dipindahkan.
- Slug kategori yang sudah digunakan URL/query tidak boleh berubah diam-diam.
- Validasi keunikan nama/slug dan active state dilakukan di backend.

### 6.2 Tabel `services`

Tabel induk untuk satu layanan.

| Field                 | Tipe konseptual         | Aturan                                                                                        |
| --------------------- | ----------------------- | --------------------------------------------------------------------------------------------- |
| `id`                  | BIGINT UNSIGNED         | PK                                                                                            |
| `title`               | VARCHAR(255)            | Wajib                                                                                         |
| `slug`                | VARCHAR(255)            | Wajib, unique                                                                                 |
| `service_category_id` | BIGINT UNSIGNED FK      | Wajib; mengarah ke `service_categories.id`                                                    |
| `icon`                | VARCHAR(100), nullable  | Icon key dari allowlist icon proyek                                                           |
| `summary`             | TEXT, nullable          | Ringkasan di kartu publik                                                                     |
| `introduction`        | LONGTEXT, nullable      | Pengantar; sanitasi HTML di server jika rich text                                             |
| `schedule_info`       | TEXT, nullable          | Jadwal/persiapan                                                                              |
| `fee_info`            | TEXT, nullable          | Biaya/persembahan; nullable jika belum dikonfirmasi                                           |
| `contact_name`        | VARCHAR(255), nullable  | Nama penanggung jawab                                                                         |
| `contact_phone`       | VARCHAR(50), nullable   | Nomor telepon untuk kontak                                                                    |
| `form_url`            | VARCHAR(2048), nullable | Google Form opsional; `NULL` berarti tidak memakai Google Form                                |
| `form_button_label`   | VARCHAR(100)            | Default `Daftar Sekarang`                                                                     |
| `is_form_open`        | BOOLEAN                 | Default `false`; hanya relevan jika form URL terisi                                           |
| `form_closed_message` | TEXT, nullable          | Pesan ketika pendaftaran ditutup                                                              |
| `is_emergency`        | BOOLEAN                 | Default `false`; layanan kontak langsung                                                      |
| `is_active`           | BOOLEAN                 | Default `false`; satu-satunya flag publikasi sesuai PRD kecuali audit menemukan konvensi lain |
| `sort_order`          | INT UNSIGNED            | Default `0`                                                                                   |
| `created_at`          | TIMESTAMP               | Dibuat                                                                                        |
| `updated_at`          | TIMESTAMP               | Diperbarui; harus mencerminkan perubahan konten anak juga                                     |
| `deleted_at`          | TIMESTAMP, nullable     | Soft delete bila mengikuti tabel konten utama                                                 |

Ketentuan penting:

- Jangan menambah `is_published` kedua apabila `is_active` sudah mengatur apakah layanan dipublikasikan.
- `form_url` nullable dan **bukan required** pada Form Request.
- `is_form_open = true` tanpa `form_url` tidak boleh menghasilkan tombol aktif. Lebih baik validasi/normalisasi agar state tidak membingungkan.
- `is_emergency = true` menonaktifkan mekanisme Google Form. Jika URL lama tersimpan, jangan tampilkan atau gunakan URL tersebut di publik; UI admin harus memberi peringatan dan idealnya mengosongkannya saat menyimpan konfigurasi emergency melalui aksi yang eksplisit.
- Layanan darurat yang diaktifkan wajib memiliki `contact_phone`.
- Slug awal enam layanan ditetapkan PRD. Hindari mengubah slug yang sudah dipakai URL publik tanpa mekanisme redirect.
- Kategori layanan diambil dari relasi `serviceCategory`; jangan menyimpan nama/slug kategori kedua kali pada `services`.
- Validasi `icon` menggunakan daftar icon yang renderer-nya dukung; jangan menerima markup atau component code arbitrary dari admin.

### 6.3 Tabel `service_requirements`

Satu record mewakili satu persyaratan.

| Field        | Tipe konseptual | Aturan                                  |
| ------------ | --------------- | --------------------------------------- |
| `id`         | BIGINT UNSIGNED | PK                                      |
| `service_id` | BIGINT UNSIGNED | FK → `services.id`, `ON DELETE CASCADE` |
| `text`       | VARCHAR(255)    | Wajib; teks persyaratan                 |
| `note`       | TEXT, nullable  | Catatan tambahan                        |
| `sort_order` | INT UNSIGNED    | Urutan tampil, default `0`              |
| `created_at` | TIMESTAMP       | Wajib                                   |
| `updated_at` | TIMESTAMP       | Wajib                                   |

Aturan:

- Setiap layanan mempunyai daftar persyaratan sendiri.
- Admin boleh tambah, ubah, hapus, dan mengurutkan item.
- Jangan menyimpan semua syarat dalam satu field paragraf.
- Tampilkan sebagai list/checklist publik dan gunakan urutan deterministik.
- Gunakan HTML teks biasa untuk `text` dan `note`; jangan izinkan HTML bebas tanpa kebutuhan dan sanitizer.

### 6.4 Tabel `service_steps`

Satu record mewakili satu langkah prosedur.

| Field         | Tipe konseptual | Aturan                                  |
| ------------- | --------------- | --------------------------------------- |
| `id`          | BIGINT UNSIGNED | PK                                      |
| `service_id`  | BIGINT UNSIGNED | FK → `services.id`, `ON DELETE CASCADE` |
| `title`       | VARCHAR(255)    | Wajib                                   |
| `description` | TEXT, nullable  | Penjelasan tambahan                     |
| `sort_order`  | INT UNSIGNED    | Default `0`                             |
| `created_at`  | TIMESTAMP       | Wajib                                   |
| `updated_at`  | TIMESTAMP       | Wajib                                   |

Aturan:

- Nomor langkah pada halaman publik diturunkan dari `sort_order`, bukan nomor yang diketik ke judul.
- Admin dapat tambah, ubah, hapus, dan urutkan.
- Jika tidak ada langkah, jangan menampilkan daftar dummy.

### 6.5 Tabel `service_faqs`

Satu record mewakili satu pertanyaan/jawaban.

| Field        | Tipe konseptual | Aturan                                  |
| ------------ | --------------- | --------------------------------------- |
| `id`         | BIGINT UNSIGNED | PK                                      |
| `service_id` | BIGINT UNSIGNED | FK → `services.id`, `ON DELETE CASCADE` |
| `question`   | VARCHAR(255)    | Wajib                                   |
| `answer`     | TEXT            | Wajib                                   |
| `sort_order` | INT UNSIGNED    | Default `0`                             |
| `created_at` | TIMESTAMP       | Wajib                                   |
| `updated_at` | TIMESTAMP       | Wajib                                   |

Aturan:

- FAQ dikelola per layanan, bukan satu FAQ global.
- Jawaban menggunakan teks biasa kecuali desain existing memang memerlukan rich text.
- FAQ diurutkan secara eksplisit dan ditampilkan dengan accordion yang accessible.
- FAQ yang kosong tidak dikirim atau ditampilkan sebagai placeholder.

### 6.6 Pivot `document_service` — integrasi PHASE 8

PRD menetapkan pivot untuk menghubungkan `services` dengan `documents`.

- Kolom inti: `document_id`, `service_id`, unique constraint `(document_id, service_id)`.
- FK dan delete behavior harus mengikuti schema tabel Download yang aktual.
- **Jangan membuat tabel `documents` atau modul Download alternatif** jika PHASE 8 belum tersedia.
- Jika model dokumen sudah ada, implementasikan relasi many-to-many sesuai konvensi aktual.
- Jika model belum ada, dokumentasikan integrasi sebagai dependency dan tunda UI final. Core CRUD pelayanan tetap dapat diselesaikan tanpa dokumen terkait.

### 6.7 Relasi Eloquent

Kontrak relasi:

```text
ServiceCategory
  └── hasMany → Service

Service
  ├── belongsTo → ServiceCategory
  ├── hasMany → ServiceRequirement
  ├── hasMany → ServiceStep
  ├── hasMany → ServiceFaq
  └── belongsToMany → Document
                       melalui document_service (bila modul Download tersedia)
```

- [ ] **6B-016** Buat migration tabel `service_categories` setelah mencatat keputusan schema di `docs/DECISIONS.md`.
- [ ] **6B-017** Buat/backfill migration dari nilai `services.category` lama ke `services.service_category_id`, jika tabel enum tersebut sudah diterapkan. Migration harus aman untuk data yang sudah ada dan rollback tidak boleh menghilangkan data tanpa strategi.
- [ ] **6B-018** Seed tiga kategori bawaan secara idempotent dan tanpa menimpa perubahan admin.
- [ ] **6B-019** Tambahkan FK dan index `services.service_category_id` serta aturan `onDelete` yang eksplisit.
- [ ] **6B-020** Implementasikan model `ServiceCategory`, relasi one-to-many, casts/scope, serta factory/test yang diperlukan.
- [ ] **6B-021** Implementasikan/validasi model `Service`.
- [ ] **6B-022** Implementasikan/validasi model `ServiceRequirement`.
- [ ] **6B-023** Implementasikan/validasi model `ServiceStep`.
- [ ] **6B-024** Implementasikan/validasi model `ServiceFaq`.
- [ ] **6B-025** Implementasikan relasi Eloquent.
- [ ] **6B-026** Terapkan `$fillable` secara eksplisit dan casts sesuai konvensi.
- [ ] **6B-027** Terapkan scope/query publik aktif dan urutan yang stabil.
- [ ] **6B-028** Tambahkan FK, index, dan constraint eksplisit pada migration yang memang diperlukan.
- [ ] **6B-029** Verifikasi migration pada database test kosong dan periksa rollback yang aman.

### 6.8 Seeder enam layanan

Seeder harus idempotent dan tidak menimpa konten yang telah dimodifikasi admin.

- [ ] **6B-030** Seed Baptis Bayi (`baptis-bayi`, `inisiasi`).
- [ ] **6B-031** Seed Baptis Dewasa (`baptis-dewasa`, `inisiasi`).
- [ ] **6B-032** Seed Komuni Pertama (`komuni-pertama`, `inisiasi`).
- [ ] **6B-033** Seed Krisma (`krisma`, `inisiasi`).
- [ ] **6B-034** Seed Pernikahan (`pernikahan`, `perkawinan`).
- [ ] **6B-035** Seed Pelayanan Perminyakan Orang Sakit (`perminyakan-orang-sakit`, `orang_sakit`, `is_emergency = true`).
- [ ] **6B-036** Set keenam record awal `is_active = false`.
- [ ] **6B-037** Set `form_url = null` dan `is_form_open = false` sebagai default; jangan seed URL fiktif.
- [ ] **6B-038** Jangan menampilkan syarat/prosedur Lampiran A PRD sebagai konten resmi. Jika seed konten contoh memang dibutuhkan, beri penanda internal “CONTOH / PERLU VALIDASI” dan pastikan record tetap nonaktif.
- [ ] **6B-039** Verifikasi seeder aman dijalankan berulang kali dan tidak menggandakan record.

---

## 7. Aturan Bisnis

### 7.1 Persetujuan konten sebelum publikasi — SVC-01

Syarat dan prosedur layanan merupakan kebijakan pastoral dan harus diverifikasi Pastor Paroki/sekretariat sebelum layanan aktif.

- Layanan yang baru dibuat harus nonaktif.
- Tampilkan badge “Perlu Validasi” pada Admin Panel untuk layanan nonaktif.
- Aktivasi dilakukan melalui aksi eksplisit, dengan konfirmasi sebelum mengubah status.
- Jangan mengaktifkan layanan otomatis hanya karena semua field sudah terisi.
- Jangan menampilkan data contoh sebagai konten resmi.

### 7.2 Persyaratan terstruktur — SVC-02

- Syarat harus disimpan satu item per record di `service_requirements`.
- Urutan yang diatur admin harus konsisten di preview admin, halaman publik, dan mode cetak.
- Catatan tambahan dapat dikosongkan.
- Jangan memaksa satu daftar persyaratan identik untuk semua layanan.

### 7.3 “Terakhir diperbarui” — SVC-03

Setiap halaman detail publik menampilkan tanggal terakhir diperbarui.

- Perubahan pada service atau child rows (requirements, steps, FAQs) harus tercermin.
- Implementasikan strategi sederhana yang konsisten: touch parent `services.updated_at` ketika child berubah, atau hitung nilai max timestamp parent dan seluruh child.
- Jangan mengklaim tanggal konten berubah jika perubahan child tidak tercermin pada nilai yang ditampilkan.
- Format waktu sesuai Bahasa Indonesia dan `Asia/Pontianak`.

### 7.4 Google Form opsional per layanan

**Keputusan produk terbaru:** admin dapat bebas memilih apakah setiap layanan menggunakan Google Form. Form URL tidak wajib.

| Kondisi                                                              | UI publik                                                                                            |
| -------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| `form_url` valid dan `is_form_open = true`, layanan non-darurat      | Tombol aktif dengan label `form_button_label` atau fallback “Daftar Sekarang”; buka form di tab baru |
| `form_url` terisi tetapi `is_form_open = false`, layanan non-darurat | Tombol nonaktif “Pendaftaran Ditutup” dan tampilkan `form_closed_message` jika ada                   |
| `form_url = null`/kosong, layanan non-darurat                        | Tidak ada tombol Google Form; tampilkan arahan menghubungi sekretariat/kontak yang tersedia          |
| `is_emergency = true`                                                | Tidak menggunakan Google Form; tampilkan kontak cepat dan tombol “Hubungi Sekarang”                  |

- `is_form_open` tidak mengindikasikan status real-time pada Google Form. Admin harus menyelaraskan status “Terima Jawaban” di Google Form secara manual.
- Jika form URL kosong, UI admin menunjukkan status “Tidak menggunakan Google Form”, bukan error atau kelengkapan wajib.
- Form Request tidak boleh memakai rule `required` untuk `form_url`.
- Jika `form_url` diisi, validasi URL tetap wajib di server.

### 7.5 Validasi Google Form — SVC-05

Untuk nilai `form_url` non-null:

- Skema harus `https`.
- Host harus tepat `docs.google.com` dengan path yang diawali `/forms/`, atau tepat `forms.gle`.
- Tolak username/password pada URL.
- Parsing URL lalu periksa scheme, hostname, dan path. Jangan menggunakan `str_contains`.
- Pesan validasi dalam Bahasa Indonesia.
- Link admin “Buka Tautan” hanya muncul/aktif saat URL valid dan membuka tab baru menggunakan rel aman.

Kasus yang wajib ditolak oleh test:

- `http://docs.google.com/forms/...`
- `https://docs.google.com.evil.com/forms/...`
- `https://evil.com/?next=docs.google.com/forms/...`
- `https://user:pass@docs.google.com/forms/...`
- `https://docs.google.com/not-forms/...`

Bila format URL Google berubah, jangan melonggarkan validator tanpa verifikasi dan test yang diperbarui.

### 7.6 Pelayanan darurat — SVC-06

- `is_emergency = true` berarti kontak langsung, bukan pendaftaran online.
- `contact_phone` wajib sebelum layanan darurat dapat diaktifkan.
- Tidak ada tombol daftar, CTA Google Form, atau alur pendaftaran empat langkah untuk layanan darurat.
- Tampilkan tombol telepon `tel:` dan/atau WhatsApp `https://wa.me/{nomor}` bila datanya tersedia dan valid.
- Jangan mengarang nomor darurat.
- Jangan membuat klaim bahwa sistem memonitor kondisi pasien atau menjamin waktu respons.
- Jika admin mengaktifkan `is_emergency`, form URL lama tidak boleh digunakan oleh halaman publik. UI perlu menjelaskan bahwa URL tidak digunakan bagi layanan darurat.

### 7.7 Kontak dan privasi

- Gunakan kontak layanan bila telah disetujui untuk publikasi.
- Jika kontak layanan tidak tersedia, gunakan fallback sekretariat dari `site_settings` hanya jika sesuai sumber data tunggal yang ditetapkan proyek.
- Jangan menampilkan nomor pribadi tanpa persetujuan.
- Nomor telepon harus dinormalisasi/divalidasi sebelum dibuat menjadi `tel:` atau `wa.me`.
- Jangan menyimpan data calon penerima sakramen pada website.

### 7.8 Penghapusan dan publikasi

- Record nonaktif/soft-deleted tidak boleh ditampilkan publik.
- Request ke URL detail untuk layanan nonaktif/soft-deleted mengembalikan 404.
- Penghapusan UI harus dikonfirmasi dan mengikuti pola soft delete tabel konten.
- Enam layanan standar MVP harus tetap dapat dikelola sebagai record. Jangan membuat hard delete yang menghilangkan kerangka standar tanpa kebutuhan eksplisit.
- Child rows mengikuti relasi parent. Penghapusan fisik parent akan menghapus children sesuai FK cascade; soft delete parent tidak otomatis menghapus children.

---

## 8. Backend Implementation Tasks

### 8.1 Routes dan controller

- [ ] **6B-040** Implementasikan route admin untuk Daftar Pelayanan dan halaman tambah/edit sesuai konvensi route existing.
- [ ] **6B-041** Pastikan route `/admin/pelayanan` merender halaman Daftar Pelayanan dan menjadi tujuan langsung item sidebar Pelayanan.
- [ ] **6B-042** Implementasikan route/action CRUD untuk `services`.
- [ ] **6B-043** Implementasikan route/action CRUD untuk requirements.
- [ ] **6B-044** Implementasikan route/action CRUD untuk steps.
- [ ] **6B-045** Implementasikan route/action CRUD untuk FAQs.
- [ ] **6B-046** Implementasikan controller publik untuk indeks dan detail layanan atau perluas controller yang tepat bila ada.
- [ ] **6B-047** Pertahankan nama route existing yang masih digunakan; jangan merusak link publik dari layout/navigation.
- [ ] **6B-048** Tambahkan route admin Kategori Pelayanan di bawah Master Data (usulan `/admin/master-data/kategori-pelayanan`) beserta CRUD kategori.
- [ ] **6B-049** Tambahkan Form Request dan authorization untuk create/update kategori; cegah penghapusan kategori yang masih digunakan.
- [ ] **6B-050** Gunakan route/helper/Service `SiteSetting` existing untuk fallback kontak; jangan menambahkan route khusus Kontak & Pengaturan untuk PHASE 6B.
- [ ] **6B-051** Pastikan rute kategori terdaftar dengan urutan yang tidak membuatnya tertangkap oleh route model binding dinamis, sesuai struktur route aktual.

### 8.2 Form Request dan otorisasi

- [ ] **6B-052** Buat/benahi Form Request create/update service.
- [ ] **6B-053** Buat/benahi Form Request untuk requirement.
- [ ] **6B-054** Buat/benahi Form Request untuk step.
- [ ] **6B-055** Buat/benahi Form Request untuk FAQ.
- [ ] **6B-056** Validasi `title`, `slug`, `service_category_id`, panjang teks, status, URL, dan icon.
- [ ] **6B-057** Validasi URL Google Form hanya saat tidak null/tidak kosong.
- [ ] **6B-058** Validasi `contact_phone` saat record emergency hendak diaktifkan.
- [ ] **6B-059** Terapkan authorization pada seluruh read/write/admin action sesuai pola yang ada.
- [ ] **6B-060** Pastikan frontend tidak menjadi satu-satunya tempat validasi/otorisasi.
- [ ] **6B-061** Pesan validasi harus Bahasa Indonesia dan konsisten dengan sistem terjemahan proyek.

### 8.3 Operasi business logic

- [ ] **6B-062** Implementasikan operasi CRUD dengan controller tipis.
- [ ] **6B-063** Gunakan Action dan DB transaction hanya ketika sebuah operasi benar-benar memodifikasi multi-record, mengurutkan banyak record, atau memiliki aturan bisnis transaksional.
- [ ] **6B-064** Saat item detail berubah, perbarui timestamp induk service secara konsisten.
- [ ] **6B-065** Pastikan pengurutan menggunakan operasi yang aman dan tidak menghasilkan `sort_order` duplikat yang merusak UI.
- [ ] **6B-066** Pastikan seeder tidak menimpa perubahan admin saat dijalankan ulang.
- [ ] **6B-067** Sanitasi pengantar HTML di server menggunakan fasilitas/sanitizer yang sudah ada.
- [ ] **6B-068** Eager-load data publik dan hanya kirim field yang diperlukan agar tidak terjadi N+1 atau kebocoran data internal.
- [ ] **6B-069** Tulis tests untuk status publikasi, update children, dan otorisasi.

### 8.4 Integrasi dokumen

- [ ] **6B-070** Periksa apakah model Document/Download sudah tersedia.
- [ ] **6B-071** Jika tersedia, buat relasi many-to-many ke dokumen dan selector pada admin.
- [ ] **6B-072** Pastikan hanya dokumen published yang ditampilkan publik.
- [ ] **6B-073** Jika PHASE 8 belum menyediakan dokumen, dokumentasikan integrasi pending tanpa membuat tabel/module duplikat.
- [ ] **6B-074** Pastikan modul layanan tetap lengkap dan berfungsi saat tidak ada dokumen terkait.

---

## 9. Admin Panel — UI/UX

Bagian ini adalah spesifikasi UI Admin yang harus diikuti. Gunakan gambar referensi Admin Pelayanan yang diberikan pemilik proyek sebagai panduan visual: layout sidebar putih, aksen merah paroki, top bar dan breadcrumb, kartu/form dengan border tipis, radius sedang, jarak lapang, badge status, kontrol yang konsisten, serta form berbentuk tab. Gambar referensi menggambarkan empat keadaan utama: daftar pelayanan, tambah pelayanan, edit persyaratan, dan edit kontak/pendaftaran.

**Keputusan navigasi terbaru:** item sidebar `Pelayanan` adalah tautan langsung ke Daftar Pelayanan, tanpa chevron dan tanpa submenu. `Kategori Pelayanan` berada di bawah `Master Data`, sementara kontak global dikelola dari `Pengaturan Situs` yang sudah ada.

Jangan hanya meniru screenshot sebagai gambar statis. Seluruh tabel, badge, formulir, urutan, status, CTA, dan navigasi harus bersumber dari data/backend atau state UI aktual.

### 9.1 Design direction dan layout bersama

- [ ] **6B-075** Gunakan `AdminLayout`, sidebar, top bar, breadcrumb, token design, font, button, input, badge, dropdown, dialog, toast, dan table yang sudah tersedia.
- [ ] **6B-076** Ikuti gaya screenshot referensi: background utama putih/abu sangat muda, permukaan putih, teks navy/gelap, aksen merah paroki untuk tombol utama, item aktif, ikon aksi edit, dan focus/active state.
- [ ] **6B-077** Terapkan border halus, radius konsisten, shadow seperlunya, padding yang lapang, dan hierarki heading yang jelas; jangan menambahkan gradien atau ornamen yang tidak ada di design system.
- [ ] **6B-078** Pertahankan top bar dengan breadcrumb di kiri serta pencarian admin/notifikasi/akun jika komponen tersebut memang tersedia pada AdminLayout. Jangan membangun ulang komponen global untuk halaman ini.
- [ ] **6B-079** Form edit harus memiliki breadcrumb yang jelas, judul `Edit Pelayanan`, nama layanan yang sedang dikelola, tombol `Kembali`, dan tombol utama `Simpan Perubahan`.
- [ ] **6B-080** Form tambah harus memiliki breadcrumb `Pelayanan > Daftar Pelayanan > Tambah Pelayanan`, tombol `Kembali`, dan tombol utama `Simpan`.
- [ ] **6B-081** Tombol utama, tombol sekunder, link, dan ikon aksi harus mempunyai state hover, focus, disabled, loading, dan error yang konsisten.
- [ ] **6B-082** Jangan membuat elemen UI yang hanya dekoratif jika datanya seharusnya datang dari backend; angka statistik, badge, URL, urutan, dan status harus nyata.
- [ ] **6B-083** Gunakan teks Bahasa Indonesia yang eksplisit dan ramah untuk label, helper text, empty state, konfirmasi, dan error state.
- [ ] **6B-084** Gunakan responsive layout. Pada layar sempit, filter boleh stack, tabel boleh memakai overflow container atau pola kartu yang accessible, dan tab harus bisa digeser/diganti tanpa memotong isi.
- [ ] **6B-085** Pastikan semua action dapat dioperasikan dengan keyboard, mempunyai focus ring, dan tidak bergantung pada warna saja.

### 9.2 Sidebar dan lokasi pengelolaan kategori/kontak

**Ikuti keputusan terbaru, bukan struktur sidebar yang muncul pada screenshot versi sebelumnya.** Menu Pelayanan harus menjadi item navigasi langsung. Ia tidak memiliki submenu maupun tombol expand/collapse. Kategori dipindahkan ke Master Data dan pengaturan kontak global tetap berada di Pengaturan Situs yang sudah ada.

Struktur sidebar:

```text
Pelayanan  → /admin/pelayanan

Master Data  (expandable seperti saat ini)
├── [semua submenu existing tetap dipertahankan]
└── Kategori Pelayanan  → /admin/master-data/kategori-pelayanan

Pengaturan  → Pengaturan Situs (existing)
└── Kontak (tab existing, bukan halaman baru di bawah Pelayanan)
```

- [ ] **6B-086** Ubah item sidebar Pelayanan menjadi tautan biasa ke halaman Daftar Pelayanan; hapus chevron, toggle, dan state expanded khusus Pelayanan.
- [ ] **6B-087** Pastikan klik Pelayanan langsung membuka `/admin/pelayanan`, bukan halaman Overview dan bukan submenu.
- [ ] **6B-088** Hapus seluruh konfigurasi submenu lama Pelayanan, termasuk Overview, Kategori, Kontak & Pengaturan, dan submenu per layanan jika pernah dibuat.
- [ ] **6B-089** Tambahkan submenu `Kategori Pelayanan` di bawah parent `Master Data`, tanpa mengganti atau menghapus submenu Master Data yang sudah ada.
- [ ] **6B-090** Gunakan route Kategori Pelayanan yang konsisten dengan route naming proyek; usulan `/admin/master-data/kategori-pelayanan` harus diverifikasi pada codebase.
- [ ] **6B-091** Pastikan `Master Data` expanded/active ketika route Kategori Pelayanan aktif; hanya child yang sesuai mendapat highlight.
- [ ] **6B-092** Pastikan `Pelayanan` active hanya untuk halaman daftar/tambah/edit pelayanan, dan tidak aktif hanya karena route Master Data Kategori Pelayanan dibuka.
- [ ] **6B-093** Pertahankan akses ke Pengaturan Situs dan tab Kontak existing; jangan membuat halaman, route, atau submenu Kontak & Pengaturan baru di modul Pelayanan.
- [ ] **6B-094** Pastikan fallback kontak pelayanan membaca sumber global `site_settings` existing, tanpa menyimpan salinan kontak global pada tabel baru.
- [ ] **6B-095** Pastikan kontak khusus yang berbeda tetap dapat diisi pada tab Kontak & Pendaftaran di edit layanan.
- [ ] **6B-096** Uji sidebar untuk mouse, keyboard, Inertia navigation, browser back/forward, dan viewport sempit.

#### 9.2.1 Master Data — Kategori Pelayanan

Halaman ini berada di bawah **Master Data**, bukan di bawah Pelayanan. Kategori dikelola sebagai master data agar Super Admin dapat mengatur nama, slug, deskripsi, ikon, urutan, dan status tanpa menyunting kode. Karena PRD awal mendefinisikan kategori sebagai enum, schema dinormalisasi ke `service_categories` sesuai Bagian 6.1 setelah keputusan tersebut dicatat di `docs/DECISIONS.md`.

- [ ] **6B-097** Buat halaman daftar kategori di route Kategori Pelayanan pada parent Master Data, dengan header `Kategori Pelayanan`, deskripsi, dan aksi tambah kategori.
- [ ] **6B-098** Tampilkan kolom nama, slug, deskripsi, jumlah layanan yang memakai kategori, status aktif/nonaktif, urutan, dan aksi.
- [ ] **6B-099** Implementasikan pencarian dan filter aktif/nonaktif jika pola table proyek mendukungnya.
- [ ] **6B-100** Implementasikan create/edit kategori dengan nama wajib, slug unik, deskripsi opsional, ikon opsional dari allowlist, urutan, dan status.
- [ ] **6B-101** Sediakan tiga kategori awal: Inisiasi, Perkawinan, Orang Sakit; gunakan label publik “Sakramen Inisiasi”, “Sakramen Perkawinan”, dan “Pelayanan Orang Sakit”.
- [ ] **6B-102** Jangan mengizinkan penghapusan kategori yang masih dipakai layanan; tampilkan pesan jelas dan arahkan admin untuk memindahkan layanan atau menonaktifkan kategori.
- [ ] **6B-103** Validasi bahwa kategori nonaktif tidak dapat digunakan untuk layanan baru atau aktivasi layanan.
- [ ] **6B-104** Pastikan seeder kategori idempotent dan tidak menimpa perubahan admin.
- [ ] **6B-105** Tambahkan feature tests untuk CRUD, slug unik, FK behavior, kategori nonaktif, dan kategori yang masih digunakan.

#### 9.2.2 Kontak global dan kontak khusus layanan

Tidak ada halaman **Kontak & Pengaturan** khusus Pelayanan. Kontak global paroki sudah dikelola di **Pengaturan Situs > Kontak**. Modul Pelayanan hanya membaca data itu sebagai fallback ketika kontak khusus layanan kosong. Kontak khusus tetap diedit pada tab `Kontak & Pendaftaran` per layanan.

- [ ] **6B-106** Audit key `site_settings` aktual yang mewakili telepon, WhatsApp, email, alamat, dan jam layanan sebelum menggunakan data fallback.
- [ ] **6B-107** Pastikan fallback menggunakan helper/Service `SiteSetting` existing beserta cache invalidation yang ada; jangan menambah tabel atau form pengaturan kontak duplikat.
- [ ] **6B-108** Tetapkan urutan fallback: kontak khusus layanan yang valid terlebih dahulu, lalu kontak global dari `site_settings`; jika keduanya kosong, tampilkan pesan teks yang sopan tanpa membuat tombol palsu.
- [ ] **6B-109** Pastikan halaman edit layanan dapat mengelola `contact_name` dan `contact_phone` khusus jika dibutuhkan.
- [ ] **6B-110** Validasi dan normalisasi nomor/email sesuai kebutuhan pada backend; hanya tampilkan kontak yang disetujui untuk publik.
- [ ] **6B-111** Tambahkan test bahwa fallback kontak global digunakan dengan benar dan tidak ada route/page baru untuk Kontak & Pengaturan.

### 9.3 Halaman Daftar Pelayanan

Halaman dibuka langsung ketika item sidebar `Pelayanan` diklik. Layout mengikuti panel kiri-atas pada referensi; kategori dikelola di bawah `Master Data`, sedangkan kontak global tetap di `Pengaturan Situs`.

#### Header halaman

Tampilkan:

- Judul **Daftar Pelayanan**.
- Deskripsi singkat: `Kelola informasi setiap layanan sakramen dan pastoral yang ditampilkan di website.`
- Tombol utama **+ Tambah Pelayanan** di sisi kanan, hanya jika create service diperbolehkan oleh requirement/pola aplikasi.

#### Area filter

Susun filter dalam satu baris di desktop:

- Pencarian nama pelayanan.
- Filter kategori: Semua Kategori, Sakramen Inisiasi, Sakramen Perkawinan, Pelayanan Orang Sakit; options bersumber dari `service_categories` aktif.
- Filter status: Semua Status, Aktif, Nonaktif.
- Sort order atau sorting control di kanan jika pola table existing mendukungnya.

Pada layar sempit, filter stack atau wrap dengan label/ukuran yang tetap mudah digunakan.

#### Kolom tabel

Gunakan tabel yang rapi dengan header jelas dan baris yang cukup tinggi untuk dibaca:

| Kolom               | Isi                                                                                                                        |
| ------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| `#`                 | Nomor baris pada halaman aktif                                                                                             |
| Nama Pelayanan      | Thumbnail/ikon opsional, nama layanan, slug dalam teks sekunder                                                            |
| Kategori            | Badge kategori                                                                                                             |
| Pendaftaran         | `Google Form`, `Kontak Langsung`, atau `Tidak Diatur`/label setara berdasarkan konfigurasi; Google Form bukan metode wajib |
| Status              | Badge `Aktif` atau `Nonaktif`                                                                                              |
| Urutan              | Nilai `sort_order`                                                                                                         |
| Terakhir Diperbarui | Tanggal yang diformat Bahasa Indonesia/WIB, jika data tersebut tersedia                                                    |
| Aksi                | Preview/lihat, edit, dan menu aksi lain sesuai komponen yang tersedia                                                      |

Task implementasi:

- [ ] **6B-112** Buat halaman daftar yang mengambil record dari database dan menerapkan pencarian/filter melalui server atau pola filter URL yang sudah ada.
- [ ] **6B-113** Tampilkan heading, deskripsi, dan tombol tambah sesuai layout referensi.
- [ ] **6B-114** Tampilkan pencarian nama layanan.
- [ ] **6B-115** Tampilkan filter kategori dari `service_categories` dan filter status.
- [ ] **6B-116** Tampilkan kontrol urutan/sorting jika sesuai pola existing; urutan awal mengikuti `sort_order ASC`.
- [ ] **6B-117** Tampilkan kolom tabel sesuai spesifikasi di atas tanpa membocorkan kontak atau data internal yang tidak diperlukan.
- [ ] **6B-118** Tampilkan thumbnail jika service memang mempunyai gambar/media yang tersedia. Jangan mengarang foto layanan resmi; jika tidak ada media, gunakan ikon netral dari icon set.
- [ ] **6B-119** Gunakan badge berbeda untuk kategori dan status, disertai teks; jangan mengandalkan warna saja.
- [ ] **6B-120** Label metode pendaftaran berdasarkan data: URL valid terisi berarti `Google Form`; tanpa URL, layanan non-darurat berarti `Kontak Langsung` jika ada kontak yang dapat dipakai, atau `Tidak Diatur`; emergency berarti `Kontak Langsung`.
- [ ] **6B-121** Sediakan aksi preview/lihat publik jika service aktif, edit, dan menu aksi dengan opsi yang benar-benar didukung backend.
- [ ] **6B-122** Gunakan pagination jika pola data table existing menggunakannya; informasi kaki tabel harus menunjukkan jumlah record aktual seperti `Menampilkan 1–6 dari 6 layanan`.
- [ ] **6B-123** Tampilkan empty state ketika tidak ada record atau filter tidak menghasilkan data.
- [ ] **6B-124** Tampilkan loading, error state, feedback berhasil/gagal, dan disabled action state.
- [ ] **6B-125** Pastikan tombol tambah, preview, edit, filter, pencarian, sort, dan pagination berfungsi; tidak boleh ada kontrol palsu yang terlihat interaktif tetapi inert.

### 9.4 Struktur halaman tambah/edit pelayanan

Halaman Tambah Pelayanan dan Edit Pelayanan memakai pola layout yang sama, dengan data/form state berbeda.

Header konten:

- Breadcrumb.
- Judul `Tambah Pelayanan` atau `Edit Pelayanan`.
- Deskripsi sesuai mode.
- Tombol `Kembali`.
- Tombol `Simpan` atau `Simpan Perubahan`.

Tab bar di dalam panel form:

```text
Informasi Umum | Persyaratan | Prosedur | Jadwal & Biaya |
Kontak & Pendaftaran | FAQ | Dokumen Terkait
```

Ketentuan tab:

- `Informasi Umum` aktif ketika membuat layanan baru.
- Tab lain untuk layanan baru dapat dinonaktifkan atau mengarahkan pengguna untuk menyimpan dahulu; jelaskan perilakunya dengan helper text. Jangan membuat child records sebelum parent service tersedia.
- Pada edit layanan, seluruh tab tersedia dan membawa konteks layanan yang sama.
- Tab menggunakan URL/query/navigation state jika pola aplikasi menyediakan state yang bisa dibagikan; jika tidak, local state boleh dipakai selama tidak menyebabkan data yang belum disimpan hilang tanpa peringatan.
- Ketika pindah tab dengan perubahan belum disimpan, pertahankan form state atau tampilkan peringatan sesuai pola aplikasi.

- [ ] **6B-126** Buat shared form shell/layout untuk Tambah dan Edit agar header dan tabs konsisten.
- [ ] **6B-127** Implementasikan tab bar dengan active indicator merah halus seperti referensi.
- [ ] **6B-128** Pastikan active tab, breadcrumb, nama layanan, dan route/edit model selalu cocok.
- [ ] **6B-129** Tampilkan validasi server-side per field dan pertahankan input lain saat error.
- [ ] **6B-130** Cegah kehilangan perubahan yang belum disimpan ketika ganti tab atau menekan tombol Kembali.
- [ ] **6B-131** Simpan hanya data pada tab aktif atau gunakan satu form terintegrasi secara konsisten; jangan melakukan double-submit antar tab.
- [ ] **6B-132** Tampilkan loading indicator pada tombol Simpan dan cegah submit ganda.

### 9.5 Tab Informasi Umum — tambah/edit

Mengikuti panel kanan-atas pada referensi. Pada desktop, gunakan grid dua kolom: kolom utama untuk identitas/konten dan kolom kanan untuk status/urutan. Pada viewport sempit, jadikan satu kolom.

**Kolom utama:**

- Nama Layanan (required).
- Slug (required, unik; bisa dibuat otomatis saat mulai mengetik judul sesuai konvensi aplikasi).
- Kategori (required).
- Ikon (pilih dari daftar ikon yang diizinkan).
- Ringkasan singkat (required bila memang menjadi kebutuhan kartu publik; panjang maksimum sesuai validasi backend).
- Pengantar Layanan menggunakan rich text editor existing.

**Panel kanan:**

- Urutan Tampilan.
- Switch `Aktifkan layanan ini`.
- Helper text: hanya layanan aktif yang dapat ditampilkan di website publik.
- Callout informasi bahwa layanan baru sebaiknya tetap nonaktif sampai informasi diverifikasi.

Tasks:

- [ ] **6B-133** Buat field Nama Layanan dengan label dan tanda required sesuai Form Request.
- [ ] **6B-134** Buat field Slug dengan helper text dan validasi keunikan.
- [ ] **6B-135** Buat dropdown Kategori dari record `service_categories` yang aktif; jangan hard-code opsi di komponen React.
- [ ] **6B-136** Buat icon selector menggunakan icon set yang tersedia; simpan icon key yang aman, bukan arbitrary HTML/SVG dari pengguna.
- [ ] **6B-137** Buat field Ringkasan Singkat dengan counter karakter bila bermanfaat dan validasi panjang.
- [ ] **6B-138** Integrasikan rich text editor existing untuk Pengantar Layanan; output tetap harus disanitasi di server.
- [ ] **6B-139** Buat panel Status & Urutan yang dipisahkan secara visual dari konten utama.
- [ ] **6B-140** Implementasikan status aktif dengan konfirmasi saat mengaktifkan layanan nonaktif.
- [ ] **6B-141** Tampilkan callout bahwa isi wajib diverifikasi sebelum dipublikasikan.
- [ ] **6B-142** Simpan nilai urutan dan pastikan pengurutan konsisten pada tabel admin dan halaman publik.
- [ ] **6B-143** Tampilkan field error di lokasi yang relevan dan bukan hanya toast global.
- [ ] **6B-144** Pada create, jangan paksa pengisian field opsional yang belum dikonfirmasi paroki.

### 9.6 Tab Persyaratan — edit layanan

Mengikuti panel kiri-bawah pada referensi. Ini adalah UI penting untuk mengelola persyaratan per layanan.

Header tab:

- Judul **Persyaratan Dokumen**.
- Penjelasan singkat: `Daftar dokumen yang perlu disiapkan oleh umat sebelum mendaftar.`
- Tombol **+ Tambah Persyaratan** di kanan.

Setiap item berupa card/baris terpisah:

- Nomor urut dalam lingkaran.
- Teks persyaratan dengan penekanan.
- Catatan tambahan opsional di bawah teks.
- Tombol urut naik dan urut turun.
- Tombol edit.
- Tombol hapus dengan konfirmasi.

Callout di bagian bawah memberikan petunjuk: urutkan persyaratan sesuai alur paling mudah dipahami umat dan gunakan catatan jika diperlukan.

Tasks:

- [ ] **6B-145** Buat section/tab Persyaratan Dokumen untuk layanan yang sedang diedit.
- [ ] **6B-146** Tampilkan semua record `service_requirements` milik layanan aktif, diurutkan berdasarkan `sort_order`.
- [ ] **6B-147** Buat aksi Tambah Persyaratan yang membuka dialog/sheet/form inline sesuai pola komponen existing.
- [ ] **6B-148** Form persyaratan memiliki field `text` (wajib) dan `note` (opsional).
- [ ] **6B-149** Buat edit persyaratan tanpa mewajibkan admin menghapus lalu membuat ulang item.
- [ ] **6B-150** Hapus persyaratan dengan dialog konfirmasi yang menyebut item yang akan dihapus.
- [ ] **6B-151** Sediakan aksi naik/turun atau drag-and-drop yang accessible untuk mengurutkan persyaratan.
- [ ] **6B-152** Simpan urutan secara konsisten dan gunakan transaction bila perlu mengubah banyak row sekaligus.
- [ ] **6B-153** Tampilkan nomor urut berdasarkan urutan aktual, bukan nilai `id`.
- [ ] **6B-154** Tampilkan empty state yang menjelaskan bahwa belum ada persyaratan dan menyediakan tombol tambah.
- [ ] **6B-155** Tampilkan callout/petunjuk di bawah daftar seperti referensi.
- [ ] **6B-156** Setelah aksi CRUD, tampilkan feedback dan pertahankan konteks layanan/tab.

### 9.7 Tab Prosedur

Pertahankan pola visual yang sama dengan Persyaratan; perbedaannya adalah setiap item menggambarkan satu langkah alur layanan.

- [ ] **6B-157** Buat section/tab Prosedur.
- [ ] **6B-158** Tampilkan judul section, penjelasan singkat, serta tombol Tambah Langkah.
- [ ] **6B-159** Setiap langkah ditampilkan dalam card/baris dengan nomor, judul, deskripsi opsional, dan aksi urut/edit/hapus.
- [ ] **6B-160** Nomor langkah dibuat dari posisi setelah sorting, bukan diketik manual pada judul.
- [ ] **6B-161** Gunakan empty state yang ramah ketika prosedur belum disiapkan.
- [ ] **6B-162** Pastikan perubahan urutan menggunakan backend dan tidak hanya state visual lokal.

### 9.8 Tab Jadwal & Biaya

- [ ] **6B-163** Buat tab `Jadwal & Biaya` dengan section terpisah untuk informasi jadwal/persiapan dan biaya/persembahan.
- [ ] **6B-164** Gunakan textarea atau editor yang sesuai kebutuhan; jangan memaksa rich text jika plain text mencukupi.
- [ ] **6B-165** Jelaskan bahwa field biaya boleh kosong sampai informasi resmi tersedia.
- [ ] **6B-166** Jangan menampilkan field kosong sebagai `Rp0`, `Gratis`, atau biaya pasti tanpa informasi yang disetujui.
- [ ] **6B-167** Tampilkan validasi dan helper text konsisten dengan design system.

### 9.9 Tab Kontak & Pendaftaran — Google Form opsional

Mengikuti panel kanan-bawah pada referensi. Layout desktop memakai dua kolom: panel **Pendaftaran (Opsional)** di kiri dan panel **Kontak Penanggung Jawab** di kanan. Panel layanan darurat ditampilkan terpisah sebagai callout berwarna hangat, disusul panel catatan penting.

**Panel Pendaftaran (Opsional), sisi kiri:**

- Penjelasan bahwa admin boleh menggunakan Google Form atau tidak.
- Input Link Google Form, tanpa required marker.
- Tombol sekunder **Buka Tautan** ketika URL valid telah tersimpan/diinput.
- Input Label Tombol Pendaftaran.
- Switch `Buka pendaftaran di website` (secara data memetakan `is_form_open`) hanya relevan jika `form_url` terisi dan layanan bukan darurat.
- Textarea `Pesan ketika pendaftaran ditutup`.
- Helper text bahwa form kosong berarti tidak memakai Google Form dan pengunjung akan diarahkan ke kontak yang tersedia.

**Panel Kontak Penanggung Jawab, sisi kanan:**

- Nama penanggung jawab (opsional).
- Nomor telepon (opsional untuk layanan non-darurat; wajib untuk mengaktifkan layanan emergency).
- Helper text tentang pemakaian kontak untuk telepon/WhatsApp.

**Panel Layanan Darurat:**

- Switch `Ini adalah layanan darurat`.
- Penjelasan bahwa layanan darurat menggunakan kontak langsung dan tidak memakai Google Form.
- Saat switch diaktifkan, UI harus menyembunyikan atau menonaktifkan kontrol Google Form secara jelas, menghindari keadaan data yang kontradiktif, serta memberi tahu admin bahwa `form_url` tidak digunakan untuk layanan emergency.
- Aktivasi layanan emergency tetap divalidasi backend.

**Panel Catatan Penting:**

- Link Google Form bersifat opsional.
- Gunakan tombol Buka Tautan untuk menguji URL yang diinput.
- Status website harus diselaraskan manual dengan setting “Terima Jawaban” di Google Form.
- Informasi kontak hanya ditampilkan jika tersedia dan disetujui untuk publikasi.

Tasks:

- [ ] **6B-168** Buat tab `Kontak & Pendaftaran` memakai two-column layout pada desktop dan satu kolom pada mobile.
- [ ] **6B-169** Buat panel `Pendaftaran (Opsional)` dengan input URL Google Form opsional.
- [ ] **6B-170** Helper text harus secara eksplisit menyebut: `Kosongkan link jika layanan ini tidak menggunakan Google Form.`
- [ ] **6B-171** URL form tidak boleh memiliki tanda required pada UI maupun rule `required` backend.
- [ ] **6B-172** Saat URL valid, sediakan tombol `Buka Tautan` dengan target tab baru dan `rel="noopener noreferrer"`; jangan membuat anchor aktif jika URL invalid/kosong.
- [ ] **6B-173** Validasi URL saat disimpan di server mengikuti SVC-05.
- [ ] **6B-174** Buat field `Label Tombol Pendaftaran`, default `Daftar Sekarang`, dengan helper text bahwa label akan tampil pada halaman publik.
- [ ] **6B-175** Buat switch `Buka pendaftaran di website` yang mengontrol `is_form_open`.
- [ ] **6B-176** Jika URL kosong, tampilkan state UI `Tidak menggunakan Google Form`, nonaktifkan switch buka/tutup, dan tidak menampilkan error kelengkapan.
- [ ] **6B-177** Jika URL terisi tetapi pendaftaran ditutup, tampilkan field `Pesan ketika pendaftaran ditutup`.
- [ ] **6B-178** Tampilkan peringatan non-blocking untuk menyelaraskan status `Terima Jawaban` secara manual di Google Form.
- [ ] **6B-179** Buat panel `Kontak Penanggung Jawab` dengan field nama dan nomor telepon.
- [ ] **6B-180** Helper text menerangkan kontak dapat menghasilkan tautan telepon/WhatsApp pada halaman publik, jika sesuai.
- [ ] **6B-181** Buat panel `Layanan Darurat` dengan switch dan penjelasan yang jelas.
- [ ] **6B-182** Ketika emergency aktif, nonaktifkan/hide pengaturan Google Form dengan helper text; jangan silently delete URL tanpa konfirmasi.
- [ ] **6B-183** Ketika emergency hendak diaktifkan, tampilkan peringatan bahwa nomor kontak wajib diisi sebelum menyimpan/mengaktifkan.
- [ ] **6B-184** Backend harus menolak activation emergency tanpa nomor kontak meskipun frontend berhasil dilewati.
- [ ] **6B-185** Buat panel `Catatan Penting` dengan list tip singkat seperti referensi.
- [ ] **6B-186** Pada layanan non-darurat tanpa URL, jangan memaksa admin mengisi Google Form dan jangan menunjukkan error.
- [ ] **6B-187** Setelah menyimpan, perbarui status pendaftaran yang terlihat pada Daftar Pelayanan.

### 9.10 Tab FAQ dan Dokumen Terkait

- [ ] **6B-188** Buat tab FAQ dengan desain daftar card/accordion yang konsisten dengan tab Persyaratan.
- [ ] **6B-189** Setiap FAQ memiliki pertanyaan wajib, jawaban wajib, sort order, dan aksi edit/hapus.
- [ ] **6B-190** Sediakan tambah/edit/hapus/urut untuk FAQ.
- [ ] **6B-191** Buat empty state dan preview accordion untuk FAQ.
- [ ] **6B-192** Tab `Dokumen Terkait` gunakan selector model Document existing jika tersedia.
- [ ] **6B-193** Jangan membangun uploader/download system duplikat; jika PHASE 8 belum tersedia, tampilkan informasi fitur belum tersedia secara eksplisit untuk admin atau tunda tab sesuai arahan proyek.
- [ ] **6B-194** Pastikan dokumen unpublished tidak bisa ditautkan atau ditampilkan publik.

### 9.11 UX dan QA Admin

- [ ] **6B-195** Semua field memiliki label, helper text, validation message, required marker yang benar, dan `aria-describedby` bila perlu.
- [ ] **6B-196** Semua destructive actions menggunakan dialog konfirmasi.
- [ ] **6B-197** Sediakan pending/saving state dan cegah double-submit.
- [ ] **6B-198** Pertahankan form state saat server mengembalikan validation error.
- [ ] **6B-199** Tampilkan toast/flash success dan error menggunakan pola existing.
- [ ] **6B-200** Jika berpindah tab dengan unsaved changes, pertahankan nilai atau tampilkan konfirmasi/peringatan sesuai pola aplikasi.
- [ ] **6B-201** Pastikan tab dapat dinavigasi memakai keyboard dan tidak menjadi trap fokus.
- [ ] **6B-202** Pastikan active tab dan focus indicator jelas, bukan hanya beda warna teks.
- [ ] **6B-203** Uji semua form desktop dan mobile.
- [ ] **6B-204** Bandingkan hasil UI dengan design reference: halaman daftar/tabel, tambah layanan, persyaratan, kontak & pendaftaran.
- [ ] **6B-205** Pastikan Pelayanan adalah tautan langsung tanpa submenu; Kategori Pelayanan berada di Master Data dan tidak ada Kontak & Pengaturan di bawah Pelayanan.
- [ ] **6B-206** Pastikan tidak ada hard-coded status, link palsu, jumlah statistik palsu, atau foto yang diklaim sebagai aset paroki.
- [ ] **6B-207** Verifikasi responsivitas minimal 360 px, 390 px, 768 px, 1024 px, dan 1440 px.

---

## 10. Halaman Publik

### 10.1 Indeks `/pelayanan`

- [ ] **6B-208** Ganti placeholder indeks menjadi halaman dinamis.
- [ ] **6B-209** Ambil hanya layanan aktif yang tidak soft-deleted.
- [ ] **6B-210** Kelompokkan berdasarkan `service_categories.sort_order` dan label kategori: Sakramen Inisiasi, Sakramen Perkawinan, Pelayanan Orang Sakit untuk tiga kategori bawaan.
- [ ] **6B-211** Urutkan sesuai `sort_order`, dengan fallback deterministik.
- [ ] **6B-212** Kartu menampilkan ikon, nama, ringkasan, dan link detail.
- [ ] **6B-213** Jangan menampilkan badge “Google Form” seolah wajib; tampilkan metode pendaftaran yang sesuai konfigurasi layanan jika dibutuhkan.
- [ ] **6B-214** Sembunyikan kategori tanpa layanan aktif atau tampilkan empty state yang sopan.
- [ ] **6B-215** Pastikan halaman responsif serta tidak memiliki scroll horizontal.

### 10.2 Detail `/pelayanan/{slug}`

Susunan konten mengikuti PRD Bagian 7.6:

1. Pengantar.
2. CTA pendaftaran/kontak di bagian atas.
3. Alur singkat empat langkah hanya bagi layanan non-darurat yang memakai pendaftaran form.
4. Syarat Dokumen berupa daftar terstruktur.
5. Prosedur berupa langkah bernomor.
6. Jadwal dan Persiapan.
7. Biaya/Persembahan jika diisi dan dikonfirmasi.
8. Kontak Penanggung Jawab.
9. Dokumen Terkait jika integrasi PHASE 8 tersedia.
10. FAQ.
11. CTA pendaftaran/kontak di bagian bawah.
12. Navigasi ke layanan lain.
13. “Terakhir diperbarui”.

Tasks:

- [ ] **6B-216** Implementasikan halaman detail dinamis untuk slug layanan yang aktif.
- [ ] **6B-217** Tampilkan pengantar dan ringkasan.
- [ ] **6B-218** Tampilkan syarat dengan urutan dan catatan yang benar.
- [ ] **6B-219** Tampilkan prosedur bernomor dengan urutan yang benar.
- [ ] **6B-220** Tampilkan informasi persiapan/jadwal jika terisi.
- [ ] **6B-221** Tampilkan biaya hanya jika telah diisi; jangan mengasumsikan kosong artinya gratis.
- [ ] **6B-222** Tampilkan kontak yang tersedia dan disetujui.
- [ ] **6B-223** Tampilkan FAQ dalam accordion yang accessible.
- [ ] **6B-224** Tampilkan dokumen terkait hanya bila model/relasi Download tersedia.
- [ ] **6B-225** Tampilkan link menuju layanan terkait yang aktif.
- [ ] **6B-226** Tampilkan last-updated akurat, termasuk perubahan child records.
- [ ] **6B-227** Gunakan title, meta description, dan metadata sosial sesuai mekanisme proyek.
- [ ] **6B-228** Gunakan eager loading dan props eksplisit.

### 10.3 Perilaku CTA publik

- [ ] **6B-229** Jika non-darurat dan URL valid terisi serta `is_form_open = true`, tampilkan tombol aktif dengan label yang disetel admin atau fallback “Daftar Sekarang”.
- [ ] **6B-230** Buka Google Form di tab baru menggunakan `target="_blank"` dan `rel="noopener noreferrer"`.
- [ ] **6B-231** Jika URL terisi tetapi `is_form_open = false`, tampilkan tombol nonaktif “Pendaftaran Ditutup” dan pesan penutupan bila ada.
- [ ] **6B-232** Jika URL kosong, jangan tampilkan tombol Google Form; arahkan ke sekretariat atau kontak penanggung jawab yang tersedia.
- [ ] **6B-233** Pada layanan emergency, jangan tampilkan tombol Daftar, Google Form, atau alur pendaftaran empat langkah.
- [ ] **6B-234** Pada layanan emergency yang aktif, tampilkan tombol Hubungi Sekarang jika nomor valid tersedia.
- [ ] **6B-235** Tombol nonaktif benar-benar tidak dapat diklik dan tidak memiliki href aktif.
- [ ] **6B-236** Jangan pernah menyematkan Google Form di halaman; cukup membuka link eksternal.

### 10.4 Layanan Perminyakan Orang Sakit

- [ ] **6B-237** Tampilkan layanan sebagai kontak langsung/darurat.
- [ ] **6B-238** Wajibkan nomor kontak ketika layanan diaktifkan pada backend.
- [ ] **6B-239** Gunakan `tel:` untuk panggilan telepon dan `https://wa.me/` untuk WhatsApp jika sesuai data kontak.
- [ ] **6B-240** Jika nomor belum tersedia, layanan harus tetap nonaktif; jangan menampilkan placeholder seolah-olah nomor resmi.
- [ ] **6B-241** Jangan mengklaim sistem memonitor kondisi pasien atau menjamin waktu tanggapan.

### 10.5 Mode cetak

- [ ] **6B-242** Tambahkan `@media print` untuk halaman detail pelayanan.
- [ ] **6B-243** Sembunyikan header/footer navigasi, tombol interaktif, CTA form, dan elemen dekoratif yang tidak relevan ketika mencetak.
- [ ] **6B-244** Pastikan semua syarat dan langkah prosedur tetap terlihat dan tidak terpotong.
- [ ] **6B-245** Gunakan tipografi, spacing, dan kontras yang baik pada hasil cetak.

### 10.6 Accessibility, SEO, dan responsive

- [ ] **6B-246** Uji viewport 360, 390, 768, 1024, dan 1440 px.
- [ ] **6B-247** Pastikan tidak ada scroll horizontal atau konten terpotong.
- [ ] **6B-248** Pastikan link text jelas, focus state terlihat, heading hierarchy semantik, dan label accessible.
- [ ] **6B-249** Pastikan semua icon informatif memiliki label teks atau accessible name.
- [ ] **6B-250** Pastikan accordion FAQ bisa digunakan dengan keyboard.
- [ ] **6B-251** Gunakan metadata halaman sesuai infrastruktur SEO yang sudah ada.

---

## 11. Keamanan dan Privasi

- [ ] **6B-252** Seluruh write operation menggunakan Form Request atau pola validasi baku proyek.
- [ ] **6B-253** Authorization dilakukan di backend.
- [ ] **6B-254** Validasi Google Form mem-parsing scheme/host/path; jangan gunakan pencocokan string sederhana.
- [ ] **6B-255** Rich text disanitasi server-side dengan allowlist existing.
- [ ] **6B-256** Field teks biasa dirender sebagai teks, bukan HTML raw.
- [ ] **6B-257** Jangan pernah menyimpan data pendaftar, jawaban Google Form, data anak, berkas pendaftaran, atau submissions.
- [ ] **6B-258** Validasi/normalisasi nomor sebelum membuat `tel:` atau WhatsApp link.
- [ ] **6B-259** Layanan nonaktif/soft-deleted menghasilkan 404 untuk akses publik langsung.
- [ ] **6B-260** Foreign key dan delete behavior ditentukan eksplisit.
- [ ] **6B-261** Semua aksi delete menggunakan konfirmasi UI.
- [ ] **6B-262** Kontak pribadi hanya ditampilkan sesuai persetujuan.
- [ ] **6B-263** Jangan menampilkan data internal seperti `deleted_at`, catatan internal, atau flag yang tidak dibutuhkan kepada pengunjung.
- [ ] **6B-264** Pastikan link eksternal mengikuti aturan keamanan target/rel dan validator server.

---

## 12. Testing

Gunakan framework dan command yang memang tersedia di repository. Setiap acceptance criterion perlu memiliki test otomatis yang sesuai. Jangan mengganti runner framework tanpa alasan.

### 12.1 Feature tests

- [ ] **6B-265** Seeder menghasilkan enam layanan awal dan tetap idempotent.
- [ ] **6B-266** Enam layanan awal berstatus nonaktif dan tidak mempunyai URL Google Form palsu.
- [ ] **6B-267** CRUD layanan bekerja sesuai hak akses.
- [ ] **6B-268** CRUD kategori bekerja, slug unik, kategori nonaktif tidak dapat dipilih untuk layanan baru, serta kategori yang masih dipakai tidak bisa dihapus.
- [ ] **6B-269** Pelayanan membaca `site_settings` existing sebagai fallback kontak ketika layanan tidak memiliki kontak khusus; tidak ada page untuk menyalin atau mengedit pengaturan global dari modul Pelayanan.
- [ ] **6B-270** URL Google Form bersifat opsional; store/update menerima `null`/kosong.
- [ ] **6B-271** URL form valid diterima oleh validator.
- [ ] **6B-272** HTTP, host palsu/suffix, path tidak valid, kredensial URL, dan URL bypass ditolak.
- [ ] **6B-273** `is_form_open` tanpa URL tidak menghasilkan CTA aktif.
- [ ] **6B-274** CRUD/ordering `service_requirements` bekerja.
- [ ] **6B-275** CRUD/ordering `service_steps` bekerja.
- [ ] **6B-276** CRUD/ordering `service_faqs` bekerja.
- [ ] **6B-277** Layanan emergency tidak bisa aktif tanpa `contact_phone`.
- [ ] **6B-278** Layanan emergency tidak menggunakan URL Google Form pada query/props publik.
- [ ] **6B-279** Service nonaktif/dihapus tidak muncul di index publik.
- [ ] **6B-280** Detail service nonaktif/soft-deleted menghasilkan 404.
- [ ] **6B-281** Semua keadaan CTA sesuai tabel pada Bagian 7.4.
- [ ] **6B-282** Tanggal terakhir diperbarui berubah saat child record berubah.
- [ ] **6B-283** Kontak privat tidak terekspos tanpa persetujuan.
- [ ] **6B-284** Rich text berbahaya disanitasi.
- [ ] **6B-285** Relasi dokumen hanya memuat dokumen published jika integrasi tersedia.
- [ ] **6B-286** Pengunjung tidak dapat menjalankan operasi tulis admin.
- [ ] **6B-287** Seeder/konten contoh tidak membuat layanan siap dipublikasikan secara tidak sengaja.

### 12.2 Frontend/component tests

- [ ] **6B-288** Sidebar menjadikan Pelayanan tautan langsung ke Daftar Pelayanan tanpa submenu atau chevron.
- [ ] **6B-289** Menu Pelayanan membuka halaman Daftar Pelayanan secara langsung.
- [ ] **6B-290** Kategori Pelayanan membuka halaman yang benar di bawah Master Data; tidak ada submenu Pelayanan dan tidak ada halaman Kontak & Pengaturan khusus Pelayanan.
- [ ] **6B-291** Form URL Google Form tidak required dan hint opsional tampil.
- [ ] **6B-292** CTA menguji state form terbuka, tertutup, tidak digunakan, dan emergency.
- [ ] **6B-293** Persyaratan ditampilkan dan diurutkan dengan benar.
- [ ] **6B-294** Prosedur, FAQ, validasi, loading, empty, dan error state bekerja.
- [ ] **6B-295** Tombol disabled tidak melakukan navigasi.
- [ ] **6B-296** Print class dan konten inti siap dicetak.

### 12.3 E2E dan regresi

- [ ] **6B-297** Super Admin dapat membuka Daftar Pelayanan dengan mengklik item Pelayanan pada sidebar.
- [ ] **6B-298** Super Admin dapat membuka Kategori Pelayanan dari Master Data; kontak global tetap dapat dikelola dari Pengaturan Situs existing.
- [ ] **6B-299** Super Admin dapat mengedit informasi umum sebuah layanan.
- [ ] **6B-300** Super Admin dapat menambah/mengubah/menghapus/mengurutkan persyaratan layanan.
- [ ] **6B-301** Super Admin dapat mengelola prosedur dan FAQ.
- [ ] **6B-302** Admin dapat menyimpan layanan tanpa URL Google Form.
- [ ] **6B-303** Admin dapat memasukkan URL valid, menguji tautan, serta membuka/menutup pendaftaran.
- [ ] **6B-304** Pengunjung hanya melihat layanan yang aktif.
- [ ] **6B-305** Pengunjung dapat menelusuri index dan detail service.
- [ ] **6B-306** Emergency service menampilkan kontak dan tidak menampilkan CTA pendaftaran.
- [ ] **6B-307** Uji navigasi mobile dan layout cetak.
- [ ] **6B-308** Jalankan regresi PHASE 4 — Jadwal Misa.
- [ ] **6B-309** Jalankan regresi PHASE 5 — Berita & Agenda.
- [ ] **6B-310** Jalankan regresi PHASE 6A — Profil Paroki.
- [ ] **6B-311** Pastikan perubahan sidebar tidak merusak menu lain maupun active states.

---

## 13. Quality Gates: Test, Build, dan CI

- [ ] **6B-312** Jalankan feature dan unit test yang relevan.
- [ ] **6B-313** Jalankan TypeScript checks sesuai script yang tersedia.
- [ ] **6B-314** Jalankan lint/formatter sesuai script yang tersedia.
- [ ] **6B-315** Jalankan production build.
- [ ] **6B-316** Jalankan migration terhadap database testing kosong; pastikan database test bukan database development/production.
- [ ] **6B-317** Jalankan E2E suite yang digunakan proyek.
- [ ] **6B-318** Jalankan seluruh regresi yang diwajibkan.
- [ ] **6B-319** Periksa GitHub Actions dan pastikan job diwajibkan sukses.
- [ ] **6B-320** Periksa hasil pada browser lebar 360–1440 px.
- [ ] **6B-321** Catat command, hasil, dan error yang sebenarnya. Jangan menandai gate lulus jika belum dijalankan.

Sebelum menentukan command, baca `composer.json`, `package.json`, dan `.github/workflows/tests.yml`. Jangan berasumsi nama script seperti `typecheck`, `lint`, atau `build` tanpa memeriksa package scripts yang ada.

---

## 14. Dokumentasi dan Penutupan

- [ ] **6B-322** Perbarui panduan Admin Panel (`docs/ADMIN_GUIDE.md` atau lokasi dokumentasi yang dipakai proyek).
- [ ] **6B-323** Dokumentasikan pengelolaan layanan, persyaratan, prosedur, FAQ, dan pengurutan.
- [ ] **6B-324** Dokumentasikan opsi Google Form dan cara menguji URL yang diinput.
- [ ] **6B-325** Dokumentasikan bahwa link Google Form opsional dan kosong berarti layanan tidak menggunakan Google Form.
- [ ] **6B-326** Dokumentasikan sinkronisasi manual status “Terima Jawaban” di Google Form.
- [ ] **6B-327** Dokumentasikan validasi konten oleh Pastor Paroki/sekretariat sebelum aktivasi.
- [ ] **6B-328** Dokumentasikan dependency integrasi dokumen dengan PHASE 8.
- [ ] **6B-329** Catat keputusan domain baru di `docs/DECISIONS.md`.
- [ ] **6B-330** Perbarui `docs/roadmap/README.md` hanya setelah hasil task, acceptance criteria, test dan build diverifikasi.
- [ ] **6B-331** Buat laporan akhir implementasi.

---

## 15. Acceptance Criteria Produk

Berikut criteria yang diturunkan dari PRD Bagian 7.6. Setiap AC perlu test teknis yang sesuai.

- **AC-SVC-1:** enam layanan wajib memiliki struktur data/detail. Konten syarat dan prosedur harus divalidasi paroki sebelum layanan aktif/peluncuran.
- **AC-SVC-2:** lima layanan non-darurat boleh menggunakan Google Form. Tombol tampil hanya ketika URL valid dimasukkan dan pendaftaran dibuka.
- **AC-SVC-3:** Super Admin dapat mengubah URL Google Form dan status buka/tutup tanpa deploy kode; URL tidak diwajibkan.
- **AC-SVC-4:** URL yang tidak sesuai allowlist host/path ditolak, termasuk kasus bypass.
- **AC-SVC-5:** Saat form ditutup, tombol tidak bisa diklik dan pesan penutupan ditampilkan bila ada.
- **AC-SVC-6:** Dokumen terkait dapat ditampilkan/diunduh ketika integrasi modul Download PHASE 8 tersedia.
- **AC-SVC-7:** Tombol telepon/WhatsApp membuka target kontak yang benar dan tervalidasi.
- **AC-SVC-8:** Halaman detail dapat dicetak rapi.
- **AC-SVC-9:** Layanan darurat tidak dapat diaktifkan tanpa kontak telepon.
- **AC-SVC-10:** Admin dapat mengelola daftar persyaratan individual untuk setiap layanan.
- **AC-SVC-11:** Klik menu Pelayanan langsung membuka Daftar Pelayanan tanpa submenu/chevron; Kategori Pelayanan berada di bawah Master Data; kontak global tetap dikelola melalui Pengaturan Situs.
- **AC-SVC-12:** Website tidak menyimpan atau memproses data pendaftar.
- **AC-SVC-13:** Halaman Kategori dapat mengelola kategori layanan melalui sumber data yang konsisten, dan kategori yang digunakan layanan tidak dapat dihapus sehingga relasi tetap aman.
- **AC-SVC-14:** Pelayanan menggunakan `site_settings` sebagai fallback kontak global, tanpa membuat halaman, tabel, atau sumber data kontak global kedua.

Kesiapan teknis tidak identik dengan kesiapan konten. Test dapat lulus ketika enam layanan masih nonaktif karena konten atau URL belum disetujui.

---

## 16. Definition of Done

PHASE 6B dapat dinyatakan **Engineering Complete** jika:

- [ ] Seluruh tabel/model/relasi yang diperlukan tersedia tanpa duplikasi schema existing.
- [ ] Enam layanan awal disediakan melalui seeder idempotent dan berstatus nonaktif.
- [ ] Admin dapat mengelola data umum, persyaratan, prosedur, jadwal/biaya, kontak, opsi Google Form, FAQ, dan urutan.
- [ ] Google Form opsional untuk setiap layanan, termasuk validasi backend ketika URL diisi.
- [ ] Sidebar menjadikan Pelayanan tautan langsung ke Daftar Pelayanan tanpa submenu; Kategori Pelayanan ditempatkan di bawah Master Data; kontak global tetap pada Pengaturan Situs.
- [ ] Halaman publik indeks/detail mengambil data dinamis dari database.
- [ ] Empat kondisi CTA ditangani: form terbuka, form tertutup, tanpa form, dan layanan darurat.
- [ ] Layanan darurat tidak dapat aktif tanpa nomor kontak dan tidak menggunakan form CTA.
- [ ] Layanan nonaktif/soft-deleted menghasilkan 404 pada akses detail langsung.
- [ ] Persyaratan, prosedur, FAQ, dan informasi terakhir diperbarui berfungsi dengan benar.
- [ ] Validasi HTML/URL/nomor kontak dan aturan otorisasi dilakukan di backend.
- [ ] Tidak ada penyimpanan data pendaftar dalam website.
- [ ] Integrasi Download menggunakan modul PHASE 8, bukan modul duplikat.
- [ ] Test, E2E, TypeScript, lint/format, production build, dan CI yang diwajibkan lulus.
- [ ] Tidak ada regresi PHASE 4, 5, atau 6A.
- [ ] Responsivitas, aksesibilitas dasar, dan mode cetak sudah diverifikasi.
- [ ] Dokumentasi dan roadmap diperbarui berdasarkan hasil nyata.

**Content/Go-live Ready** membutuhkan verifikasi tambahan oleh pihak paroki:

- syarat/prosedur telah disetujui Pastor Paroki/sekretariat;
- biaya dan jadwal yang ditampilkan benar;
- nomor kontak layanan darurat dikonfirmasi;
- URL Google Form yang digunakan benar dan dapat diakses;
- status layanan yang hendak diluncurkan telah disetujui.

Jangan menyatakan Content/Go-live Ready tanpa konfirmasi tersebut.

---

## 17. Urutan Implementasi yang Direkomendasikan

1. Audit codebase, schema dan pola existing.
2. Finalisasi schema berdasarkan PRD dan kondisi actual.
3. Implementasi migration, models, relations, scopes, factory, seeder.
4. Implementasi Form Requests, validation, authorization, service CRUD.
5. Implementasi CRUD persyaratan, prosedur, FAQ dan urutannya.
6. Implementasi validasi Google Form opsional dan aturan emergency.
7. Implementasi halaman Daftar Pelayanan dan Kategori Pelayanan di bawah Master Data; gunakan Pengaturan Situs existing untuk kontak global dan jadikan Pelayanan tautan langsung tanpa submenu.
8. Implementasi form edit tiap layanan dengan section/tab.
9. Implementasi halaman publik index dan detail.
10. Implementasi CTA, kontak, last-updated, FAQ, dan dokumen (jika tersedia).
11. Implementasi print styles, accessibility, SEO dan responsif.
12. Tambahkan feature/component/E2E tests.
13. Jalankan regresi PHASE 4–6A, typecheck, lint/format, build dan CI.
14. Perbarui dokumentasi dan roadmap.
15. Laporkan blockers, keputusan belum final, dan status kesiapan konten.

Jangan mengerjakan semua workstream dalam satu perubahan besar tanpa checkpoint. Selesaikan satu workstream, verifikasi, baru lanjut.

---

## 18. Format Laporan Akhir AI Agent

Agent harus memberi laporan dengan struktur berikut:

```text
PHASE 6B — Parish Service Implementation Report

Status:
- Engineering Complete / Partially Complete / Blocked
- Content/Go-live Ready / Waiting for parish approval

Audit:
- Existing service tables/features:
- Reused implementation patterns:
- Schema decisions (including `service_categories` normalization):

Database:
- Migrations:
- Models / relationships:
- Seeder behavior:

Routes:
- Admin listing:
- Admin sidebar (Pelayanan direct link; Kategori Pelayanan under Master Data) and service edit:
- Public index/detail:

Business rules:
- Google Form optional:
- Google Form URL validation:
- Emergency service:
- Public visibility:
- Requirements/steps/FAQ ordering:

Tests executed:
- Command:
- Result:

Lint / TypeScript / Build / CI:
- Command:
- Result:

Regression:
- PHASE 4:
- PHASE 5:
- PHASE 6A:

Files changed:
- ...

Open decisions / blockers:
- ...

Content awaiting parish validation:
- ...
```

Jangan tandai phase selesai hanya karena seluruh file telah dibuat. Selesaikan berdasarkan perilaku aplikasi, acceptance criteria, hasil test/build yang benar-benar diperiksa, dan persetujuan konten bila akan diluncurkan.
