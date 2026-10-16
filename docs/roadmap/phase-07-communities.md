# Phase 07 — Communities

> **Proyek:** Website Gereja Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau  
> **Phase:** 07 — Communities  
> **Status:** Implemented — engineering complete (lihat catatan status di §0)  
> **Target implementer:** AI coding agent  
> **Stack utama:** Laravel 13, Inertia.js, React 19, TypeScript, Tailwind CSS 4, shadcn/ui, MySQL  
> **Dokumen acuan:** `PRD.md`, `ARCHITECTURE.md`, `DESIGN.md`, serta roadmap fase yang sudah disetujui.

---

## 0. Catatan Status Implementasi

Phase 07 selesai secara **engineering**. Cabang `feat/phase-07-communities`.

- Tiga tabel (`community_categories`, `areas`, `communities`) beserta model,
  enum, dan factory tersedia. Pivot `community_area` dari D-55 dihapus di D-57.
- Kategori komunitas dan Komunitas dikelola di `/admin/master-data/kategori-komunitas`
  dan `/admin/komunitas`; Area dikelola di `/admin/area`
  sebagai modul mandiri.
- Halaman publik dinamis: `/komunitas` + `/komunitas/{slug}` (hanya komunitas
  `active` + `is_published`, filter kategori), dan `/area` +
  `/area/{slug}` (hanya area aktif, semua tipe).
- Privasi kontak ditegakkan di server (XC-P1), baik kontak komunitas maupun
  kontak/pengurus area pada halaman publik Area.
- Keputusan dan divergensi dicatat di `docs/DECISIONS.md` **D-55**, **D-56**,
  **D-57**; daftar permission di `docs/RBAC.md`.
- Relasi komunitas↔area dilepas (D-57); tidak ada `community_id` pada
  `posts`/`events`; galeri komunitas ditunda ke Phase 8.

**Yang belum:** tidak ada seeder produksi. Kategori, wilayah, lingkungan, dan
profil komunitas harus diisi dan divalidasi paroki. `DemoContentSeeder` mengisi
contoh (tanpa kontak/nama nyata) hanya saat `demo.enabled`. Ini prinsip D-34,
bukan kekurangan implementasi.

> Checklist di §11 dan Definition of Done di §16 di bawah menandai pekerjaan
> _engineering_ sebagai selesai; isi konten paroki tetap menjadi pekerjaan fase
> pengisian konten.

---

## 1. Instruksi Utama untuk AI Agent

Implementasikan Phase 7 secara menyeluruh sesuai spesifikasi ini dan konvensi codebase yang sudah ada.

Sebelum mengubah kode:

1. Baca `PRD.md`, `ARCHITECTURE.md`, `DESIGN.md`, file roadmap yang relevan, dan dokumentasi modul sebelumnya.
2. Periksa struktur repository, migration, model, route, controller, Form Request, Policy, seeder, test, layout, komponen UI, pola upload gambar, dan pola Inertia yang benar-benar digunakan saat ini.
3. Periksa status implementasi placeholder untuk route/halaman komunitas dan test yang memang ditujukan untuk diganti pada Phase 7.
4. Identifikasi pola yang sudah mapan dari modul Phase 4–6B. Gunakan pola tersebut; jangan membangun pola paralel tanpa alasan.
5. Buat rencana implementasi singkat berdasarkan temuan aktual, lalu kerjakan Phase 7 sampai seluruh acceptance criteria terpenuhi.

**Jangan mengasumsikan nama file, nama route, API komponen, package, atau command yang belum diverifikasi di repository.** Sesuaikan detail implementasi dengan codebase aktual.

### 1.1 Constraint final yang wajib dipatuhi

- **Jangan menambahkan `community_id` ke tabel `posts` atau `events`.**
- Jangan membuat relasi langsung dari `posts` atau `events` ke `communities`.
- Jangan mengubah migration Phase 5 untuk menambahkan hubungan tersebut.
- Galeri foto komunitas bukan bagian Phase 7; pengelolaan galeri ditunda ke Phase 8.
- Jangan menambahkan modul keanggotaan individual, pendaftaran anggota online, absensi, chat, atau sistem pengurus terstruktur dalam Phase 7.
- Gunakan tabel `community_categories`, bukan `community_types`.
- Logo dan thumbnail komunitas harus diunggah melalui UI upload; pengguna admin tidak menginput path file secara manual.
- Pertahankan arsitektur modular monolith Laravel + Inertia. Jangan membuat REST API terpisah atau memakai React Router untuk halaman Inertia.
- Hindari refactor yang tidak berhubungan dengan Phase 7.

---

## 2. Tujuan Phase

Phase 7 membangun modul Communities agar admin dapat mengelola kategori komunitas, profil komunitas, wilayah/lingkungan, serta hubungan komunitas dengan wilayah pelayanan. Pengunjung website dapat menemukan komunitas dan membaca profil komunitas yang sudah dipublikasikan.

### 2.1 Hasil yang diharapkan

1. Master kategori komunitas tersedia di admin.
2. Data wilayah dan lingkungan tersedia dengan hierarki yang valid.
3. Admin dapat melakukan CRUD komunitas, termasuk upload logo dan thumbnail.
4. Admin dapat menghubungkan komunitas dengan satu atau beberapa area pelayanan.
5. Website publik memiliki direktori komunitas dengan pencarian dan filter.
6. Website publik memiliki halaman detail komunitas berdasarkan slug.
7. Status publikasi dan privasi kontak diterapkan di backend, bukan hanya disembunyikan di UI.
8. Test otomatis mencakup jalur sukses, validasi, otorisasi, publikasi, privasi, upload, dan integritas relasi.
9. Placeholder Phase 7 diganti tanpa merusak fitur yang sudah selesai pada fase sebelumnya.

---

## 3. Ruang Lingkup

### 3.1 Termasuk dalam Phase 7

- Migration dan model untuk:
  - `community_categories`
  - `communities`
  - `areas`
  - `community_area`
- Relasi Eloquent, factories/seeders jika sesuai pola repository.
- CRUD kategori komunitas di admin.
- CRUD wilayah dan lingkungan di admin.
- CRUD komunitas di admin.
- Upload, preview, penggantian, dan penghapusan file logo serta thumbnail komunitas.
- Status komunitas dan status publikasi.
- Penugasan komunitas ke beberapa area pelayanan.
- Pencarian, filter, pagination, empty state, dan responsive UI.
- Halaman publik `/komunitas`.
- Halaman publik `/komunitas/{slug}`.
- SEO metadata dasar untuk halaman detail.
- Otorisasi, validasi, privasi, error handling, dan test.
- Pembaruan navigasi admin agar tersedia menu **Komunitas** dan submenu **Kategori Komunitas** di bawah **Master Data**.
- Pembaruan dokumentasi dan checklist roadmap Phase 7.

### 3.2 Tidak termasuk dalam Phase 7

- Galeri foto komunitas dan pengelolaan banyak foto. Dikerjakan pada Phase 8.
- Modul galeri umum paroki.
- Modul Download dan Kontak.
- Hubungan berita dengan komunitas.
- Hubungan agenda dengan komunitas.
- Tabel anggota individual komunitas.
- Form pendaftaran anggota online.
- Absensi atau jadwal kegiatan individual.
- Chat/pesan internal.
- Dashboard analytics baru yang tidak dibutuhkan untuk CRUD.
- Refactor besar pada modul yang sudah selesai.
- Mengisi seluruh daftar jenis komunitas Katolik sebagai data aktif secara otomatis.

### 3.3 Batas antara thumbnail dan galeri

`communities.thumbnail_path` adalah **satu foto representatif** yang dipakai pada kartu komunitas dan bagian hero/detail komunitas. Field tersebut bukan galeri.

Tabel `community_photos`, album, lightbox, caption foto dokumentasi, dan pengelolaan banyak foto ditunda ke Phase 8. Pada Phase 7, jangan membuat section galeri yang bergantung pada data yang belum tersedia. Jika desain menampilkan area galeri sebagai placeholder, lebih baik sembunyikan section tersebut sampai Phase 8 daripada menampilkan data palsu.

---

## 4. Konvensi Arsitektur

Ikuti `ARCHITECTURE.md` dan pola yang telah ada dalam repository.

### 4.1 Tanggung jawab lapisan

- **Laravel:** routing, autentikasi, otorisasi, validasi, query, business rules, penyimpanan file, dan persistence.
- **Inertia:** mengirim props dari Laravel ke halaman React.
- **React:** presentasi, interaksi UI, form, filter, dan state lokal yang memang diperlukan.
- **Eloquent:** persistence dan relasi.
- **Form Request:** validasi input dan authorization request sesuai pola proyek.
- **Policy/Gate:** otorisasi backend.
- **Action/Service:** gunakan hanya ketika kompleksitas memenuhi aturan di `ARCHITECTURE.md`; jangan membuat abstraksi untuk CRUD sederhana.
- **Resource/DTO:** gunakan sesuai pola proyek untuk membentuk props dan menghindari pengiriman field internal yang tidak diperlukan.
- **React frontend:** ikuti struktur feature-oriented yang sudah ada, named exports untuk komponen, dan default export hanya jika pola route/page proyek memang mengharuskannya.

### 4.2 Aturan umum

- Controller harus tipis.
- Jangan menambahkan repository layer secara default.
- Jangan menduplikasi server state Inertia ke Zustand atau state global lain.
- Jangan menambahkan library UI baru. Gunakan shadcn/ui dan komponen proyek.
- Gunakan design tokens dan utilitas Tailwind yang sudah tersedia.
- Jangan hardcode permission check hanya di frontend.
- Semua query publik harus menerapkan batas publikasi secara konsisten.
- Semua perilaku baru harus disertai test.
- Jangan mock Eloquent/database; gunakan database test dan factory.
- Gunakan `Storage::fake()` untuk pengujian file.
- Ikuti test runner dan format test yang benar-benar digunakan repository; jangan mencampur Pest dan PHPUnit secara sembarangan.

---

## 5. Skema Database

Gunakan empat tabel inti berikut. Sebelum membuat migration, periksa migration yang sudah ada agar tidak membuat tabel duplikat atau mengubah migration yang telah dirilis/diterapkan.

### 5.1 `community_categories`

Menyimpan master kategori komunitas.

| Kolom                      | Tipe yang disarankan           | Aturan           |
| -------------------------- | ------------------------------ | ---------------- |
| `id`                       | `id()`                         | Primary key      |
| `name`                     | `string(100)`                  | Required         |
| `slug`                     | `string(120)`                  | Required, unique |
| `description`              | `text`, nullable               | Opsional         |
| `sort_order`               | `unsignedInteger`, default `0` | Urutan kategori  |
| `is_active`                | `boolean`, default `true`      | Status master    |
| `created_at`, `updated_at` | `timestamps()`                 | Timestamp        |

Indeks:

- Unique index pada `slug`.
- Index pada `is_active` dan/atau `sort_order` bila bermanfaat untuk query yang digunakan.

Aturan:

- Kategori aktif dapat dipilih saat membuat atau mengubah komunitas.
- Kategori nonaktif tidak dapat dipilih untuk komunitas baru atau penggantian kategori.
- Kategori nonaktif yang masih digunakan tidak boleh menyebabkan profil komunitas hilang.
- Kategori yang masih digunakan oleh komunitas tidak dapat dihapus. Tampilkan pesan validasi yang jelas dan arahkan admin untuk memindahkan komunitas terlebih dahulu.
- Slug dibuat otomatis dari nama jika pola proyek mendukungnya, tetapi harus tetap divalidasi unik di server.
- Jangan membuat enum kategori. Kategori harus dapat dikelola melalui admin.

### 5.2 `communities`

Menyimpan profil komunitas.

| Kolom                      | Tipe yang disarankan               | Aturan                                    |
| -------------------------- | ---------------------------------- | ----------------------------------------- |
| `id`                       | `id()`                             | Primary key                               |
| `community_category_id`    | `foreignId`                        | Required, FK ke `community_categories.id` |
| `name`                     | `string(150)`                      | Required                                  |
| `slug`                     | `string(180)`                      | Required, unique                          |
| `logo_path`                | `string`, nullable                 | Path hasil upload                         |
| `thumbnail_path`           | `string`, nullable                 | Path hasil upload                         |
| `description`              | `text`, nullable                   | Deskripsi komunitas                       |
| `vision`                   | `text`, nullable                   | Visi                                      |
| `mission`                  | `text`, nullable                   | Misi                                      |
| `meeting_schedule`         | `text`, nullable                   | Jadwal pertemuan                          |
| `meeting_location`         | `string`, nullable                 | Lokasi pertemuan                          |
| `eligibility`              | `text`, nullable                   | Persyaratan bergabung                     |
| `joining_info`             | `text`, nullable                   | Cara bergabung                            |
| `contact_name`             | `string`, nullable                 | Nama kontak                               |
| `contact_phone`            | `string`, nullable                 | Nomor kontak                              |
| `show_contact`             | `boolean`, default `false`         | Kontrol tampilan kontak publik            |
| `status`                   | enum/string sesuai konvensi proyek | `forming`, `active`, `inactive`           |
| `is_published`             | `boolean`, default `false`         | Kontrol publikasi                         |
| `published_at`             | `timestamp`, nullable              | Waktu publikasi                           |
| `created_at`, `updated_at` | `timestamps()`                     | Timestamp                                 |

Indeks:

- Unique index pada `slug`.
- Index pada `community_category_id`.
- Index pada kolom yang digunakan untuk filter publik, khususnya `status` dan `is_published`, bila sesuai kebutuhan query.

Foreign key:

- `community_category_id` wajib menunjuk kategori valid.
- Tentukan aturan delete yang aman. Jangan menggunakan cascade yang dapat menghapus komunitas secara tidak sengaja ketika kategori dihapus.

#### 5.2.1 Definisi status

Gunakan nilai status berikut secara konsisten:

- `forming`: komunitas dalam pembentukan.
- `active`: komunitas aktif.
- `inactive`: komunitas tidak aktif.

Ikuti konvensi enum PHP yang sudah digunakan repository jika ada. Jangan membuat representasi status berbeda di frontend dan backend.

#### 5.2.2 Aturan publikasi

- Draft: `is_published = false`.
- Dipublikasikan: `is_published = true`.
- Profil publik hanya boleh ditampilkan ketika `is_published = true` **dan** `status = active`.
- `forming` dapat disimpan dan dikelola admin, tetapi tidak ditampilkan sebagai profil komunitas publik pada MVP.
- `inactive` tidak ditampilkan di halaman publik meskipun `is_published` masih bernilai true.
- Saat publikasi pertama kali dilakukan, isi `published_at` sesuai pola proyek.
- Saat sebuah komunitas ditarik dari publikasi, set `is_published = false`; jangan menghapus data.
- Jangan mengandalkan kondisi UI saja untuk menyembunyikan komunitas yang tidak memenuhi syarat.

#### 5.2.3 Logo dan thumbnail

- `logo_path` menyimpan path file logo hasil upload.
- `thumbnail_path` menyimpan path foto representatif hasil upload.
- Field path tidak boleh diisi melalui text input oleh admin.
- Kedua field boleh nullable agar profil dapat disimpan sebelum aset tersedia, kecuali PRD atau validasi proyek yang telah disetujui menetapkan kewajiban lain.
- Gunakan Laravel Filesystem dengan disk yang sesuai konfigurasi proyek. Jangan mengasumsikan disk publik/privat sebelum memeriksa pola upload yang sudah ada.
- Gunakan nama file/path yang aman; jangan mempercayai nama file pengguna sebagai path.
- Validasi file di server, bukan hanya di browser.
- Jika file diganti, simpan file baru dan perbarui database dengan urutan yang aman, lalu hapus file lama hanya setelah perubahan berhasil.
- Jika penghapusan komunitas berhasil, bersihkan file milik komunitas tersebut sesuai pola proyek.
- Kegagalan operasi database tidak boleh meninggalkan perubahan file yang tidak terkendali.
- Berikan fallback UI jika logo atau thumbnail tidak tersedia.

Format gambar yang diizinkan harus konsisten dengan aturan gambar pada PRD dan modul lain. Bila belum ada validator reusable, gunakan format raster yang disetujui proyek seperti JPEG, PNG, atau WebP. Terapkan batas ukuran sesuai standar gambar PRD/proyek dan tampilkan pesan error dalam bahasa Indonesia.

**Penting:** thumbnail adalah satu gambar representatif, bukan kumpulan foto. Optimasi/konversi gambar hanya jika library atau service tersebut sudah tersedia atau disetujui di proyek; jangan menambahkan dependency baru tanpa kebutuhan nyata.

### 5.3 `areas`

Menyimpan wilayah dan lingkungan dengan hierarki maksimal dua tingkat.

| Kolom                      | Tipe yang disarankan               | Aturan                          |
| -------------------------- | ---------------------------------- | ------------------------------- |
| `id`                       | `id()`                             | Primary key                     |
| `parent_id`                | `foreignId`, nullable              | FK self-reference ke `areas.id` |
| `name`                     | `string(150)`                      | Required                        |
| `slug`                     | `string(180)`                      | Required, unique                |
| `type`                     | enum/string sesuai konvensi proyek | `wilayah`, `lingkungan`         |
| `description`              | `text`, nullable                   | Opsional                        |
| `sort_order`               | `unsignedInteger`, default `0`     | Urutan tampilan                 |
| `is_active`                | `boolean`, default `true`          | Status area                     |
| `created_at`, `updated_at` | `timestamps()`                     | Timestamp                       |

Aturan hierarki:

- `wilayah` harus memiliki `parent_id = null`.
- `lingkungan` wajib memiliki `parent_id` yang menunjuk ke area bertipe `wilayah`.
- Wilayah tidak boleh menjadi anak dari wilayah lain.
- Lingkungan tidak boleh memiliki anak.
- Tidak boleh ada siklus parent.
- Perubahan tipe atau parent harus memvalidasi seluruh aturan tersebut di server.
- Jika lingkungan dipindahkan ke wilayah lain, relasi komunitas terhadap area harus tetap konsisten.
- Area nonaktif tidak ditawarkan sebagai pilihan baru, tetapi hubungan lama tidak boleh hilang diam-diam.

Penghapusan:

- Wilayah yang masih memiliki lingkungan tidak dapat dihapus sebelum lingkungan dipindahkan atau dihapus dengan prosedur yang valid.
- Area yang masih digunakan di `community_area` tidak dapat dihapus tanpa menangani relasi secara eksplisit.
- Jangan menggunakan cascade yang menghapus data relasi tanpa persetujuan aturan bisnis yang jelas.

### 5.4 `community_area`

Tabel pivot relasi many-to-many antara komunitas dan area.

| Kolom          | Tipe yang disarankan | Aturan                 |
| -------------- | -------------------- | ---------------------- |
| `community_id` | `foreignId`          | FK ke `communities.id` |
| `area_id`      | `foreignId`          | FK ke `areas.id`       |

Aturan:

- Gunakan primary key gabungan `(community_id, area_id)` atau unique constraint setara.
- Cegah pasangan relasi duplikat.
- Gunakan FK dan aturan delete yang aman.
- Sinkronisasi pilihan area dari form harus atomik sesuai pola Eloquent dan kebutuhan operasi.
- Komunitas dapat memiliki nol atau beberapa area pelayanan.
- Hanya area aktif yang dapat dipilih untuk relasi baru.
- Data publik hanya menampilkan nama area yang valid dan relevan.

### 5.5 Relasi Eloquent yang diperlukan

- `CommunityCategory` hasMany `Community`.
- `Community` belongsTo `CommunityCategory`.
- `Community` belongsToMany `Area` melalui `community_area`.
- `Area` belongsToMany `Community` melalui `community_area`.
- `Area` belongsTo parent `Area`.
- `Area` hasMany child `Area`.

Nama relasi dan konfigurasi pivot harus konsisten dengan konvensi proyek.

### 5.6 Migration dan kompatibilitas

- Jangan mengedit migration lama yang sudah menjadi bagian dari fase sebelumnya hanya untuk menambahkan fitur Phase 7.
- Buat migration baru jika perlu menyesuaikan struktur yang sudah ada.
- Jangan menambahkan kolom `community_id` ke `posts` atau `events`.
- Jangan menambahkan relasi Eloquent dari `Post`/`Event` ke `Community`.
- Periksa database/migration aktual sebelum menentukan urutan migration.
- Pastikan fresh migration dan rollback bekerja sesuai standar proyek.
- Jangan menambahkan seed komunitas palsu sebagai konten production. Factory boleh menggunakan data fiktif untuk test.

---

## 6. Fitur Admin

### 6.1 Navigasi

Tambahkan menu admin untuk **Komunitas** dan submenu **Kategori Komunitas** di dalam **Master Data**.

- **Komunitas** menjadi menu utama pengelolaan profil komunitas.
- **Kategori Komunitas** berada di bawah **Master Data**, sejajar dengan master kategori berita/agenda/pelayanan sesuai pola sidebar aktual.
- Ikuti komponen, icon set, active-state, collapse behavior, dan aturan responsive sidebar yang sudah ada.
- Pastikan parent menu dan child menu memiliki active state yang konsisten dengan pola menu lain.
- Jangan mengaktifkan menu fitur Phase 8 yang masih ditandai belum tersedia kecuali Phase 7 memang membutuhkan perubahan statusnya.

### 6.2 CRUD kategori komunitas

Route/path harus mengikuti konvensi admin proyek; PRD menyebut `/admin/komunitas` dan `/admin/jenis-komunitas` pada versi sebelumnya, tetapi nama tabel dan label yang disetujui sekarang adalah `community_categories` / **Kategori Komunitas**. Periksa route naming yang berlaku dan gunakan label baru secara konsisten.

Fitur:

- List kategori.
- Tambah kategori.
- Detail/edit kategori jika pola resource memerlukan halaman tersebut.
- Hapus kategori dengan konfirmasi.
- Search nama kategori.
- Filter aktif/nonaktif jika jumlah kategori dan pola admin mendukungnya.
- Sort order.
- Pagination bila diperlukan sesuai standar list admin.
- Validation errors dan success feedback.
- Empty state.
- Proteksi kategori yang masih digunakan.

Kolom list yang disarankan:

- Nama kategori.
- Slug.
- Jumlah komunitas yang menggunakan kategori, bila dapat dihitung efisien.
- Urutan.
- Status.
- Aksi edit/hapus.

Form kategori:

- Nama.
- Slug (otomatis/terbantu dari nama tetapi dapat diedit jika pola proyek mendukung).
- Deskripsi.
- Urutan.
- Status aktif.

### 6.3 CRUD wilayah dan lingkungan

Gunakan menu/lokasi admin yang sesuai struktur aktual. Jangan membuat master area kedua jika sudah ada tabel lokasi yang mempunyai tujuan berbeda; audit dahulu dan pisahkan konsep lokasi misa/konten dari struktur Wilayah/Lingkungan jika memang berbeda.

Fitur:

- Daftar hierarkis wilayah dan lingkungan.
- Tambah/edit/hapus wilayah.
- Tambah/edit/hapus lingkungan.
- Parent selector untuk lingkungan yang hanya menawarkan Wilayah aktif.
- Search nama.
- Filter tipe dan status.
- Sort order.
- Validasi parent/type.
- Konfirmasi sebelum menghapus.
- Error message yang menjelaskan jika area masih digunakan komunitas atau masih memiliki child.

UI dapat menggunakan tabel hierarkis atau tabel dengan kolom Tipe dan Wilayah Induk. Jangan membuat visualisasi hierarki kompleks bila tabel bertingkat sederhana cukup.

### 6.4 Daftar komunitas admin

List minimal harus menyediakan:

- Thumbnail kecil.
- Nama komunitas.
- Kategori.
- Wilayah pelayanan (ringkas; bila banyak tampilkan beberapa dan indikator `+N`).
- Status komunitas.
- Status publikasi.
- Tanggal pembaruan.
- Aksi detail, edit, dan hapus.

Fitur list:

- Search nama komunitas.
- Filter kategori.
- Filter status komunitas.
- Filter status publikasi.
- Reset filter.
- Pagination.
- Pilihan jumlah item per halaman jika sudah menjadi pola komponen list.
- Empty state ketika data belum ada.
- Empty state ketika filter tidak menghasilkan data.
- Feedback ketika operasi CRUD berhasil/gagal.
- Konfirmasi untuk aksi destruktif.
- Query filter harus tervalidasi dan aman.
- Pertahankan query string saat pagination dan filter agar dapat dibagikan/bookmark, sesuai pola proyek.

### 6.5 Form tambah/edit komunitas

Form sebaiknya dikelompokkan menjadi section agar tidak menjadi form panjang yang membingungkan.

#### Section A — Informasi dasar

- Nama komunitas (wajib).
- Slug (wajib/terbentuk otomatis sesuai pola proyek).
- Kategori komunitas (wajib dan hanya kategori aktif untuk pilihan baru).
- Status komunitas: `forming`, `active`, `inactive`.
- Deskripsi singkat/panjang sesuai pola editor yang sudah tersedia.

#### Section B — Logo dan thumbnail

- Upload logo komunitas.
- Upload thumbnail komunitas.
- Preview file terpilih.
- Menampilkan gambar yang sudah tersimpan pada halaman edit.
- Mengganti gambar.
- Menghapus gambar dengan kontrol yang jelas.
- Validasi format dan ukuran.
- Pesan error per field.
- Input file tidak menerima path manual.

#### Section C — Profil komunitas

- Visi.
- Misi.

Jika rich-text editor digunakan, ikuti editor dan sanitasi yang sudah digunakan modul lain. Jangan memperkenalkan editor baru. Jika field memakai textarea biasa pada fase ini, jangan membangun WYSIWYG baru tanpa kebutuhan yang disetujui.

#### Section D — Jadwal dan lokasi

- Jadwal pertemuan (teks bebas; tidak membangun event scheduler).
- Lokasi pertemuan (teks bebas; bukan FK ke lokasi misa kecuali kebutuhan dan domain existing benar-benar sama).

#### Section E — Wilayah pelayanan

- Pilih nol atau beberapa area aktif.
- Tampilkan struktur wilayah/lingkungan secara mudah dipahami.
- Cegah duplikasi pilihan.
- Simpan melalui relasi `community_area`.

#### Section F — Informasi bergabung dan kontak

- Persyaratan/eligibility.
- Cara bergabung.
- Nama kontak.
- Nomor telepon.
- Switch/checkbox `show_contact`.
- Jelaskan bahwa kontak hanya ditampilkan pada halaman publik bila switch aktif.
- Jangan menampilkan kontak publik jika nilai `show_contact` false, sekalipun data kontak tersimpan.

#### Section G — Publikasi

- Status draft/dipublikasikan.
- Preview status.
- `published_at` dikelola server sesuai aturan proyek, bukan input tanggal bebas kecuali memang ada fitur penjadwalan publikasi yang sudah disetujui.
- Status komunitas `inactive` tidak boleh ditampilkan sebagai profil publik aktif.
- Perubahan status publikasi harus divalidasi di backend.

Form wajib memiliki:

- Error summary atau feedback error yang sesuai pola proyek.
- Tombol simpan dan batal.
- Indikator submitting/loading.
- Pencegahan double-submit sesuai pola form proyek.
- Konfirmasi jika keluar dengan perubahan belum tersimpan, hanya bila pola tersebut sudah tersedia atau dapat diterapkan tanpa kompleksitas berlebihan.

### 6.6 Penghapusan komunitas

- Tampilkan konfirmasi sebelum menghapus.
- Periksa relasi pivot.
- Hapus relasi `community_area` dengan cara yang aman dan konsisten.
- Bersihkan logo/thumbnail dari storage hanya setelah penghapusan database berhasil.
- Jangan pernah menghapus post atau event saat komunitas dihapus karena relasi tersebut memang tidak ada.
- Berikan feedback sukses atau error.
- Jangan menghapus komunitas hanya karena statusnya inactive; gunakan status untuk mempertahankan histori profil.

---

## 7. Public Website

### 7.1 Halaman direktori — `/komunitas`

Gunakan public layout, header, footer, SEO, design tokens, dan komponen yang sudah ada. Ganti placeholder Phase 7 dengan implementasi sebenarnya.

Konten:

1. Breadcrumb/eyebrow yang sesuai desain publik.
2. Hero title dan deskripsi singkat.
3. Search input.
4. Filter kategori.
5. Filter wilayah bila relevan dengan UX yang telah disepakati.
6. Daftar kartu komunitas.
7. Pagination jika diperlukan.
8. Empty state.
9. Footer publik yang sudah ada.

Setiap kartu komunitas menampilkan:

- Thumbnail komunitas dengan fallback.
- Logo jika sesuai layout; jangan membuat kartu terlalu padat.
- Nama komunitas.
- Badge kategori.
- Deskripsi ringkas.
- Ringkasan wilayah pelayanan jika tersedia.
- Link “Lihat Detail”.

Gunakan data aktual dari database. Jangan hardcode kartu contoh OMK/Legio Maria/Koor sebagai konten permanen. Contoh boleh dipakai pada factory/test, bukan pada query publik.

### 7.2 Query publik direktori

Direktori hanya boleh memuat komunitas dengan:

- `is_published = true`
- `status = active`

Kategori yang ditampilkan pada filter publik harus mengikuti aturan konsisten. Jangan menampilkan kategori nonaktif sebagai pilihan filter untuk komunitas baru; tetapi jika komunitas aktif yang sudah dipublikasikan menggunakan kategori yang kemudian dinonaktifkan, jangan sampai terjadi kegagalan rendering. Tentukan perilaku konsisten: label kategori yang sudah terhubung tetap bisa ditampilkan pada komunitas terkait, sementara kategori nonaktif tidak ditawarkan sebagai filter pilihan baru jika tidak ada hasil yang valid.

Search:

- Minimal mencari nama komunitas.
- Jika pola dan query tetap sederhana, deskripsi dapat disertakan sesuai kebutuhan.
- Gunakan query parameter yang tervalidasi.
- Hindari query N+1 dengan eager loading kategori dan area.

Filter:

- Kategori menggunakan ID atau slug sesuai konvensi query proyek.
- Area menggunakan ID atau slug sesuai pola proyek.
- Filter yang tidak valid tidak boleh menimbulkan error server; tangani sesuai standar validasi/filter proyek.
- Query string dipertahankan saat pagination.
- Jangan menambahkan filter yang tidak didukung UX atau data.

Pagination:

- Gunakan pagination Laravel yang cocok dengan pola Inertia saat ini.
- Pertahankan search dan filter pada URL.
- Pastikan response tidak memuat field internal yang tidak diperlukan.

### 7.3 Halaman detail — `/komunitas/{slug}`

Tampilkan detail hanya jika komunitas memenuhi aturan publikasi.

Susunan konten:

1. Breadcrumb `Beranda / Komunitas / [Nama Komunitas]`.
2. Header profil dengan thumbnail, logo, nama, kategori, dan ringkasan.
3. Section Tentang Komunitas.
4. Section Visi dan Misi jika data tersedia.
5. Section Jadwal dan Lokasi Pertemuan jika data tersedia.
6. Section Wilayah Pelayanan jika data tersedia.
7. Section Cara Bergabung jika data tersedia.
8. Section Kontak Pengurus hanya jika diizinkan dan data kontak tersedia.
9. CTA yang sesuai untuk menghubungi sekretariat/pengurus, hanya jika link atau informasi tujuan yang valid tersedia.
10. Navigasi kembali ke direktori.

Ketentuan tampilan:

- Sembunyikan section opsional jika datanya kosong; jangan menampilkan heading kosong atau placeholder mentah.
- Jangan menampilkan galeri foto pada Phase 7.
- Jangan menampilkan relasi berita atau agenda.
- Jangan membuat data kontak palsu.
- Jangan menampilkan kontak ketika `show_contact = false`.
- Jangan menampilkan komunitas draft, forming, atau inactive pada route publik.
- Jika slug tidak ditemukan atau profil tidak boleh diakses publik, kembalikan 404 sesuai pola proyek agar keberadaan draft tidak bocor.
- Thumbnail dan logo menggunakan fallback yang accessible.
- Gambar memiliki alt text yang bermakna jika tersedia; jika gambar dekoratif, gunakan alt kosong sesuai konteks.
- Halaman harus responsif.

### 7.4 SEO dan metadata

- Gunakan mekanisme metadata/SEO yang sudah dipakai pada halaman publik lain.
- Title halaman detail harus mencakup nama komunitas dan identitas paroki sesuai pola yang sudah ada.
- Meta description berasal dari deskripsi komunitas yang telah dipublikasikan, dengan panjang yang wajar.
- Jangan membangun SEO subsystem baru.
- Pastikan slug URL stabil dan unik.

### 7.5 Search/filter dan navigasi

- Gunakan Laravel route sebagai satu-satunya routing aplikasi.
- Jangan menggunakan React Router.
- Search/filter sebaiknya GET/query-string berdasarkan pola Inertia yang sudah dipakai pada modul berita/agenda.
- Pastikan filter dapat digunakan dengan keyboard dan label yang jelas.
- Berikan tombol reset filter jika filter lebih dari satu.

---

## 8. Otorisasi dan Keamanan

### 8.1 Otorisasi admin

- Audit middleware, role/permission, Policy, dan Gate yang sudah digunakan.
- Gunakan otorisasi backend yang sudah berlaku pada modul admin.
- MVP menggunakan role `super_admin` sesuai keputusan PRD, tetapi implementasi harus mengikuti mekanisme Spatie Laravel Permission dan Policy/Gate yang sudah terpasang.
- Jangan membuat role/permission UI baru.
- Semua route mutasi kategori, komunitas, dan area wajib dilindungi.
- Jangan menganggap menu yang disembunyikan sudah cukup sebagai proteksi.
- Test guest dan user tanpa permission/role yang sesuai berdasarkan skema auth aktual.

### 8.2 Validasi server

Validasi minimal:

- Field wajib dan batas panjang.
- Slug unik dan format valid.
- Kategori harus ada dan valid.
- Kategori harus aktif untuk kategori baru/perubahan kategori.
- Status hanya salah satu nilai yang diizinkan.
- Boolean harus divalidasi.
- Area harus ada, aktif, dan memenuhi aturan hierarki.
- File harus bertipe gambar yang diizinkan dan memenuhi batas ukuran.
- Kontak harus divalidasi sesuai format yang digunakan proyek.
- Query filter/pagination divalidasi.
- Jangan mempercayai field `published_at` yang dikirim langsung dari browser.

### 8.3 Privasi kontak

- `show_contact = false` berarti field kontak tidak boleh dirender pada UI publik.
- Field kontak juga tidak boleh disertakan dalam props halaman publik ketika tidak diizinkan, bukan hanya disembunyikan lewat conditional React.
- Admin yang berwenang tetap dapat melihat dan mengedit kontak melalui panel admin.
- Test harus memverifikasi bahwa response Inertia publik tidak memuat nomor/nama kontak ketika disembunyikan.
- Jangan memasukkan nomor telepon fiktif ke seed production.

### 8.4 Perlindungan anak dan foto

- Hindari menampilkan data pribadi anak.
- Jangan menambahkan tabel anggota atau daftar anak.
- Thumbnail komunitas yang memuat orang harus berupa gambar yang memang boleh dipublikasikan.
- Jangan mengunggah atau menambahkan foto contoh dari internet sebagai data production.
- Ikuti kebijakan penyimpanan dan optimasi gambar proyek; hapus metadata sensitif jika pipeline optimasi yang sudah ada mendukungnya.

### 8.5 File storage

- Gunakan Laravel Filesystem.
- Validasi MIME/type di server.
- Jangan menyimpan file dengan nama/path yang dikontrol langsung oleh pengguna.
- Jangan menghapus file lama sebelum file baru berhasil disimpan dan referensi database berhasil diperbarui.
- Hindari file yatim ketika operasi gagal; gunakan pola rollback/cleanup yang sesuai.
- Gunakan `Storage::fake()` pada test.
- Jangan mengekspos disk/path internal atau data sensitif ke props frontend.

---

## 9. UX dan Design System

Ikuti `DESIGN.md` dan implementasi desain yang telah disetujui.

### 9.1 Prinsip visual

- Gunakan HSPMTB Red `#AB020E` sebagai sinyal interaksi utama.
- Gunakan HSPMTB Navy `#01266D` untuk penekanan struktural/editorial.
- Gunakan HSPMTB Gold `#FCB027` secara terbatas.
- Putih dan soft-neutral tetap menjadi permukaan dominan.
- Gunakan Tailwind tokens dan komponen shadcn/ui yang sudah ada.
- Pertahankan whitespace, border halus, dan shadow yang restrained.
- Jangan menambahkan UI library baru.
- Jangan membuat public page seperti dashboard admin.
- Target area sentuh minimal 44×44 px jika sesuai komponen.
- Focus state keyboard harus terlihat.
- Ikuti icon set yang sudah digunakan.

### 9.2 UX admin

- Form dibagi menjadi section yang jelas.
- Upload logo dan thumbnail memiliki label, hint format/ukuran, preview, dan tombol ganti/hapus.
- Badge status komunitas dan publikasi harus berbeda makna.
- Filter mudah di-reset.
- Aksi destruktif diberi konfirmasi.
- Tabel memiliki loading, empty, dan error feedback sesuai pola proyek.
- Validasi error ditampilkan dekat field.
- Semua label UI menggunakan bahasa Indonesia yang konsisten.
- Gunakan komponen table/form/filter yang sudah ada bila cocok.

### 9.3 UX publik

- Halaman direktori memudahkan umat menemukan komunitas.
- Kartu fokus pada foto representatif, nama, kategori, ringkasan, dan CTA.
- Jangan menampilkan terlalu banyak metadata pada kartu.
- Detail komunitas memiliki urutan informasi yang jelas.
- Section kosong disembunyikan.
- Filter dan search usable pada mobile.
- Jangan menambahkan galeri palsu hanya agar halaman terlihat penuh.

---

## 10. Struktur Implementasi yang Diharapkan

**Ini adalah panduan, bukan daftar path yang harus dibuat secara membabi buta.** Periksa struktur repository dan ikuti pola yang ada. Jangan membuat file duplikat jika padanannya sudah tersedia.

### 10.1 Backend

Kemungkinan area perubahan:

- Migration empat tabel inti.
- Model `CommunityCategory`, `Community`, dan `Area`.
- Enum status bila pola proyek memakai PHP backed enum.
- Factory untuk kategori, komunitas, dan area.
- Seeder hanya jika diperlukan untuk data master awal yang memang disetujui.
- Controller resource untuk kategori, komunitas, dan area.
- Form Request untuk store/update sesuai pola proyek.
- Policy/Gate bila diperlukan.
- Resource/transformer untuk props Inertia jika digunakan pada modul lain.
- Route admin dan publik.
- Logic upload file mengikuti pola proyek.

CRUD sederhana tidak harus memiliki Action/Service. Buat Action hanya jika operasi melibatkan beberapa model, transaksi, side effects, atau kompleksitas yang memenuhi `ARCHITECTURE.md`.

### 10.2 Frontend admin

Ikuti feature-oriented structure aktual, misalnya fitur komunitas di area `resources/js/features/` yang digunakan repository.

Halaman yang diperlukan:

- List komunitas.
- Create komunitas.
- Edit komunitas.
- Detail admin jika konsisten dengan pola CRUD yang ada.
- List kategori komunitas.
- Form kategori.
- List/hierarki area.
- Form wilayah/lingkungan.

Komponen reusable hanya dibuat jika benar-benar dipakai bersama, misalnya:

- Upload gambar dengan preview.
- Badge status.
- Area selector.
- Filter toolbar.
- Ringkasan area terpilih.

Jangan membuat abstraction component untuk satu penggunaan sederhana tanpa kebutuhan.

### 10.3 Frontend publik

Halaman yang diperlukan:

- Index komunitas.
- Detail komunitas.

Komponen yang mungkin diperlukan:

- Community card.
- Category filter.
- Area filter.
- Community profile header.
- Section informasi komunitas.

Gunakan public layout dan shell yang sudah ada. Jangan membangun header/footer baru.

### 10.4 Navigasi

- Tambahkan menu `Komunitas`.
- Tambahkan submenu `Kategori Komunitas` di dalam `Master Data`.
- Ikuti active state dan responsive behavior sidebar yang sudah digunakan.
- Periksa link publik `Komunitas` yang sudah ada; ganti placeholder route/page, jangan menambah duplikat.

---

## 11. Daftar Pekerjaan Bertahap

Kerjakan berurutan agar dependensi tetap jelas.

### Task 0 — Audit codebase dan rencana

- [x] Baca dokumen arsitektur, PRD, desain, dan roadmap.
- [x] Audit model/migration/route/controller/test placeholder terkait komunitas.
- [x] Audit pola CRUD admin yang paling dekat.
- [x] Audit upload image dan disk storage.
- [x] Audit pola auth/Policy/Spatie.
- [x] Audit komponen list, form, filter, pagination, dan layout.
- [x] Tulis rencana implementasi ringkas berdasarkan file aktual.
- [x] Konfirmasi tidak ada `community_id` pada `posts`/`events` yang akan ditambahkan.

**Exit criteria:** agent memahami pola aktual repository dan tidak mengimplementasikan struktur paralel.

### Task 1 — Database dan model

- [x] Buat migration `community_categories`.
- [x] Buat migration `areas` dengan self-reference.
- [x] Buat migration `communities`.
- [x] Buat migration `community_area`.
- [x] Tambahkan FK, unique constraint, dan index yang relevan.
- [x] Implementasikan model dan relasi Eloquent.
- [x] Implementasikan status enum jika konsisten dengan proyek.
- [x] Buat factories untuk test.
- [x] Buat seeder kategori hanya bila dibutuhkan dan sesuai strategi seeding proyek.
- [x] Jalankan migration fresh pada database test.
- [x] Jalankan rollback untuk migration baru sesuai standar.
- [x] Pastikan tidak ada perubahan ke relasi posts/events.

**Exit criteria:** schema dan relasi lulus test dasar dan kompatibel dengan data existing.

### Task 2 — Kategori komunitas admin

- [x] Tambahkan route admin.
- [x] Implementasikan list kategori.
- [x] Implementasikan create/update/delete.
- [x] Implementasikan search/filter jika diperlukan.
- [x] Implementasikan slug unique.
- [x] Implementasikan proteksi kategori yang masih digunakan.
- [x] Tambahkan validasi dan error/success feedback.
- [x] Tambahkan submenu `Kategori Komunitas` di Master Data.
- [x] Tambahkan test feature untuk CRUD, validasi, dan authorization.

**Exit criteria:** kategori dapat dikelola admin dan kategori terpakai tidak bisa dihapus.

### Task 3 — Wilayah dan lingkungan admin

- [x] Implementasikan query hierarki area.
- [x] Implementasikan list wilayah dan lingkungan.
- [x] Implementasikan create/update/delete wilayah.
- [x] Implementasikan create/update/delete lingkungan.
- [x] Validasi aturan `wilayah` tanpa parent.
- [x] Validasi aturan `lingkungan` wajib parent wilayah.
- [x] Cegah child bertingkat lebih dari dua level.
- [x] Cegah penghapusan area yang masih memiliki child/relasi tanpa penanganan.
- [x] Tambahkan pencarian/filter yang sesuai.
- [x] Tambahkan test hierarchy, delete constraints, dan authorization.

**Exit criteria:** hierarki area selalu valid dan dapat dikelola melalui admin.

### Task 4 — CRUD komunitas admin

- [x] Implementasikan list komunitas.
- [x] Implementasikan pencarian nama.
- [x] Implementasikan filter kategori/status/publikasi.
- [x] Implementasikan pagination dan mempertahankan query string.
- [x] Implementasikan create/edit/delete komunitas.
- [x] Implementasikan upload logo.
- [x] Implementasikan upload thumbnail.
- [x] Implementasikan preview, replacement, dan remove image.
- [x] Implementasikan validasi server dan cleanup file aman.
- [x] Implementasikan relasi kategori.
- [x] Implementasikan relasi banyak area melalui pivot.
- [x] Implementasikan deskripsi, visi, misi, jadwal, lokasi, persyaratan, cara bergabung, kontak, `show_contact`, status, dan publikasi.
- [x] Implementasikan konfirmasi delete dan feedback.
- [x] Tambahkan test CRUD, validation, upload, storage cleanup, dan authorization.

**Exit criteria:** admin dapat mengelola profil komunitas secara end-to-end.

### Task 5 — Halaman publik direktori

- [x] Ganti placeholder index komunitas.
- [x] Query hanya komunitas aktif dan dipublikasikan.
- [x] Eager-load kategori dan area.
- [x] Implementasikan search.
- [x] Implementasikan filter kategori.
- [x] Implementasikan filter area bila masuk desain final.
- [x] Implementasikan pagination.
- [x] Implementasikan community card dan fallback image.
- [x] Implementasikan empty state.
- [x] Pastikan query filter tetap di URL.
- [x] Tambahkan test publikasi, search, filter, pagination, dan Inertia props.

**Exit criteria:** direktori hanya menampilkan komunitas yang boleh dilihat publik dan filter bekerja.

### Task 6 — Halaman publik detail komunitas

- [x] Ganti placeholder detail komunitas.
- [x] Resolve berdasarkan slug.
- [x] Terapkan aturan `status = active` dan `is_published = true`.
- [x] Tampilkan profil, visi/misi, jadwal/lokasi, area, dan informasi bergabung.
- [x] Tampilkan kontak hanya jika `show_contact = true` dan data tersedia.
- [x] Jangan mengirim field kontak ke props publik jika `show_contact = false`.
- [x] Sembunyikan section yang datanya kosong.
- [x] Tampilkan logo/thumbnail dengan fallback.
- [x] Implementasikan metadata SEO sesuai pola proyek.
- [x] Tampilkan 404 untuk slug tidak ada, draft, forming, atau inactive.
- [x] Jangan menambahkan galeri, related news, atau related agenda.
- [x] Tambahkan test detail, 404, privacy payload, dan SEO props jika test pattern mendukungnya.

**Exit criteria:** halaman detail memenuhi aturan publikasi dan privasi.

### Task 7 — Integrasi navigasi dan UI

- [x] Integrasikan menu admin Komunitas.
- [x] Integrasikan submenu Master Data → Kategori Komunitas.
- [x] Pastikan active state parent/child benar.
- [x] Pastikan tautan publik menuju `/komunitas`.
- [x] Gunakan layout/header/footer publik existing.
- [x] Periksa mobile, tablet, dan desktop.
- [x] Periksa keyboard focus, label input, alt text, dan target sentuh.
- [x] Periksa loading, empty, error, dan success states.

**Exit criteria:** modul terintegrasi konsisten dengan UI aplikasi dan design system.

### Task 8 — Regression, dokumentasi, dan penutupan

- [x] Jalankan test khusus Phase 7.
- [x] Jalankan seluruh test backend yang relevan.
- [x] Jalankan lint/typecheck/build sesuai scripts yang benar-benar ada.
- [x] Jalankan E2E Playwright yang relevan jika tersedia.
- [x] Perbaiki semua regression.
- [x] Pastikan CI relevan lulus.
- [x] Perbarui dokumentasi Phase 7.
- [x] Perbarui status roadmap.
- [x] Laporkan ringkasan file yang berubah, migration, routes, tests, commands, hasil, dan keterbatasan yang masih ada.

**Exit criteria:** seluruh acceptance criteria lulus dan tidak ada blocker yang disembunyikan.

---

## 12. Route dan Kontrak Halaman

Nama route aktual wajib mengikuti konvensi repository. Contoh di bawah menjelaskan perilaku, bukan mewajibkan nama route persis.

### 12.1 Route publik

- `GET /komunitas` — direktori komunitas.
- `GET /komunitas/{slug}` — detail komunitas.

### 12.2 Route admin

Resource routes untuk:

- Kategori komunitas.
- Komunitas.
- Wilayah/lingkungan.

Semua route mutasi harus menggunakan middleware auth dan authorization yang berlaku. Gunakan named route Laravel dan helper route frontend sesuai setup proyek (misalnya Wayfinder jika benar-benar terpasang).

### 12.3 Kontrak props direktori

Props harus ringkas, terstruktur, dan hanya berisi data yang dibutuhkan UI. Bentuk konseptual:

- `communities`: paginated collection.
- Setiap item: `id`, `name`, `slug`, `description` ringkas, `logo_url`/`logo`, `thumbnail_url`/`thumbnail`, kategori, ringkasan area.
- `filters`: nilai search/kategori/area saat ini.
- `categories`: opsi kategori yang relevan untuk filter.
- `areas`: opsi area yang relevan bila filter area diterapkan.

Gunakan naming dan transformasi yang konsisten dengan response Inertia modul lain. Jangan membuat kontrak duplikat jika project sudah memiliki pola Resource/props.

### 12.4 Kontrak props detail

Data konseptual:

- `community`: profil yang dipublikasikan.
- `community.category`.
- `community.areas`.
- `community.contact` hanya jika `show_contact = true` dan ada data yang boleh ditampilkan.
- SEO metadata sesuai pola public page existing.

Jangan mengirim nomor kontak/nama kontak dalam payload jika `show_contact = false`.

### 12.5 Kontrak props admin

- List: paginated records, opsi filter, filter aktif.
- Create/edit: opsi kategori aktif dan area aktif yang valid.
- Edit: data komunitas termasuk path/URL gambar untuk preview, tetapi jangan mengekspos path internal yang tidak dibutuhkan.
- Kategori yang sudah digunakan boleh ditampilkan pada edit record lama jika nonaktif, namun jangan ditawarkan untuk penetapan baru tanpa aturan eksplisit.

---

## 13. Acceptance Criteria

### 13.1 Kategori komunitas

- **AC-CAT-01:** Super Admin dapat melihat daftar kategori.
- **AC-CAT-02:** Super Admin dapat membuat kategori dengan data valid.
- **AC-CAT-03:** Slug kategori harus unik.
- **AC-CAT-04:** Kategori dapat diedit.
- **AC-CAT-05:** Kategori yang digunakan komunitas tidak dapat dihapus.
- **AC-CAT-06:** Kategori dapat dinonaktifkan tanpa menghapus komunitas terkait.
- **AC-CAT-07:** Guest/tidak berwenang tidak dapat melakukan operasi admin.

### 13.2 Wilayah dan lingkungan

- **AC-AREA-01:** Wilayah tersimpan tanpa parent.
- **AC-AREA-02:** Lingkungan wajib memiliki parent berupa Wilayah.
- **AC-AREA-03:** Wilayah tidak dapat menjadi child.
- **AC-AREA-04:** Lingkungan tidak dapat memiliki child.
- **AC-AREA-05:** Sistem menolak parent/type yang tidak valid.
- **AC-AREA-06:** Area yang masih digunakan tidak dapat dihapus tanpa penanganan relasi.
- **AC-AREA-07:** CRUD area hanya tersedia untuk admin yang berwenang.

### 13.3 Komunitas admin

- **AC-COM-01:** Super Admin dapat membuat komunitas dengan kategori valid.
- **AC-COM-02:** Slug komunitas unik.
- **AC-COM-03:** Logo diunggah melalui file upload dan path disimpan server.
- **AC-COM-04:** Thumbnail diunggah melalui file upload dan path disimpan server.
- **AC-COM-05:** Format file yang tidak diizinkan ditolak dengan error yang jelas.
- **AC-COM-06:** Admin dapat mengganti logo/thumbnail dan file lama dibersihkan setelah perubahan berhasil.
- **AC-COM-07:** Admin dapat mengubah profil, visi, misi, jadwal, lokasi, cara bergabung, dan kontak.
- **AC-COM-08:** Admin dapat menghubungkan komunitas dengan beberapa area.
- **AC-COM-09:** Status dan publikasi dapat dikelola secara terpisah.
- **AC-COM-10:** Komunitas dapat dicari dan difilter pada list admin.
- **AC-COM-11:** Hapus komunitas meminta konfirmasi dan tidak menghapus posts/events.
- **AC-COM-12:** Operasi admin dilindungi authorization backend.

### 13.4 Public directory

- **AC-PUB-01:** `/komunitas` menampilkan hanya komunitas dengan `status = active` dan `is_published = true`.
- **AC-PUB-02:** Pencarian nama bekerja.
- **AC-PUB-03:** Filter kategori bekerja.
- **AC-PUB-04:** Filter area bekerja jika disertakan pada UI final.
- **AC-PUB-05:** Pagination mempertahankan query string.
- **AC-PUB-06:** Kartu menampilkan thumbnail/logo dengan fallback, nama, kategori, ringkasan, area, dan link detail sesuai data tersedia.
- **AC-PUB-07:** Empty state tampil saat tidak ada data/hasil.
- **AC-PUB-08:** Tidak ada data contoh hardcoded sebagai hasil publik.

### 13.5 Public detail dan privasi

- **AC-DETAIL-01:** Slug komunitas yang valid dan dipublikasikan membuka halaman detail.
- **AC-DETAIL-02:** Slug tidak ada mengembalikan 404.
- **AC-DETAIL-03:** Draft, forming, dan inactive tidak bisa diakses sebagai profil publik.
- **AC-DETAIL-04:** Detail menampilkan profil dan section opsional hanya jika data tersedia.
- **AC-DETAIL-05:** Area pelayanan yang valid ditampilkan.
- **AC-DETAIL-06:** Kontak hanya tampil ketika `show_contact = true` dan data tersedia.
- **AC-DETAIL-07:** Saat `show_contact = false`, nama dan nomor kontak tidak ada dalam props publik/Inertia response.
- **AC-DETAIL-08:** Metadata SEO menggunakan nama dan deskripsi komunitas.
- **AC-DETAIL-09:** Tidak ada section galeri foto komunitas pada Phase 7.
- **AC-DETAIL-10:** Tidak ada related news/agenda atau relasi `community_id`.

### 13.6 Kualitas dan regression

- **AC-QA-01:** Semua test baru lulus.
- **AC-QA-02:** Lint/typecheck/build yang tersedia lulus.
- **AC-QA-03:** E2E yang relevan lulus jika tersedia.
- **AC-QA-04:** Tidak ada regression pada fitur Phase 1–6B.
- **AC-QA-05:** CI yang relevan lulus.
- **AC-QA-06:** Dokumentasi dan roadmap diperbarui.

---

## 14. Test Plan

Ikuti test runner yang digunakan repository. Gunakan factory dan database test, bukan data seed yang diasumsikan selalu ada.

### 14.1 Category tests

- List kategori untuk admin.
- Create kategori valid.
- Required/length validation.
- Duplicate slug ditolak.
- Update kategori.
- Delete kategori yang tidak digunakan.
- Delete kategori yang masih digunakan ditolak.
- Authorization guest dan user yang tidak berwenang.

### 14.2 Area tests

- Create wilayah tanpa parent.
- Reject wilayah dengan parent.
- Create lingkungan dengan parent wilayah.
- Reject lingkungan tanpa parent.
- Reject lingkungan dengan parent lingkungan.
- Reject wilayah dengan parent wilayah.
- Reject hierarki yang membentuk siklus.
- Update/pindah lingkungan.
- Reject delete wilayah yang masih memiliki lingkungan.
- Reject delete area yang masih terhubung komunitas tanpa penanganan.
- Authorization.

### 14.3 Community admin tests

- List, create, show/edit, update, delete.
- Required validation.
- Duplicate slug.
- Invalid category.
- Inactive category tidak bisa dipilih untuk perubahan baru.
- Upload logo dan thumbnail berhasil.
- Format/ukuran file invalid ditolak.
- Replacement image memperbarui path dan menghapus file lama sesuai desain.
- Delete community members/pivot cleanup sesuai relasi.
- Area pivot tersinkronisasi.
- Status/publikasi divalidasi.
- Filter/search/pagination.
- Authorization setiap endpoint mutasi.

### 14.4 Public tests

- Index hanya memuat active + published.
- Draft tidak muncul.
- Forming tidak muncul.
- Inactive tidak muncul.
- Search bekerja.
- Filter kategori bekerja.
- Filter area bekerja jika diimplementasikan.
- Pagination dan query string.
- Detail active + published sukses.
- Detail unknown slug 404.
- Detail draft/forming/inactive 404.
- `show_contact = false` mengecualikan field kontak dari Inertia props.
- `show_contact = true` menampilkan kontak hanya ketika data tersedia.
- Optional sections kosong tidak menyebabkan error.
- Tidak ada related posts/events props atau query.
- Tidak ada gallery props/section Phase 8.

### 14.5 Frontend/E2E

Jika Playwright sudah dikonfigurasi:

- Admin dapat membuka menu Komunitas dan Kategori Komunitas.
- Admin dapat membuat kategori dan komunitas.
- Upload logo/thumbnail berfungsi.
- Search/filter direktori publik bekerja.
- Detail komunitas tampil.
- Kontak tersembunyi sesuai konfigurasi.
- Layout dasar diperiksa pada desktop dan mobile.
- Jangan memasang Playwright MCP hanya karena package Playwright tersedia; gunakan setup test repository yang sudah ada.

### 14.6 Commands

Jangan mengasumsikan scripts. Baca `composer.json`, `package.json`, dan CI workflow lalu jalankan command yang memang ada, misalnya:

- Test backend.
- Lint.
- Typecheck.
- Build frontend.
- Playwright/E2E jika sudah dikonfigurasi.

Catat command yang dijalankan dan hasil aktualnya dalam laporan akhir.

---

## 15. Dokumentasi dan Laporan Akhir Agent

Setelah implementasi:

1. Perbarui roadmap Phase 7 dengan status aktual, bukan sekadar menandai semua task selesai.
2. Tambahkan/ubah dokumentasi phase jika repository memakai format roadmap per fase.
3. Catat migration baru, model, route, halaman, komponen, dan test yang dibuat.
4. Catat command yang dijalankan dan hasilnya.
5. Laporkan test yang gagal atau tidak dapat dijalankan secara jujur.
6. Catat keputusan implementasi yang perlu diketahui fase berikutnya.
7. Pastikan Phase 8 dapat menambahkan galeri tanpa perlu membongkar `communities.thumbnail_path`.

Laporan akhir minimal berisi:

- Ringkasan implementasi.
- Daftar file utama yang dibuat/diubah.
- Migration dan relasi.
- Route admin/publik.
- Test/quality checks dan hasilnya.
- Hal yang belum selesai atau blocker.
- Konfirmasi bahwa `posts` dan `events` tidak diubah untuk relasi komunitas.
- Konfirmasi bahwa galeri komunitas ditunda ke Phase 8.

---

## 16. Definition of Done

Phase 7 baru boleh dinyatakan **DONE** jika semua poin berikut terpenuhi:

- [x] Skema empat tabel inti terimplementasi dengan constraint dan relasi yang benar.
- [x] Kategori komunitas dapat dikelola melalui admin.
- [x] Wilayah dan lingkungan dapat dikelola dengan hierarki maksimal dua tingkat.
- [x] Komunitas dapat dibuat, dilihat, diedit, dan dihapus dengan aman.
- [x] Upload logo dan thumbnail berfungsi.
- [x] File replacement/deletion ditangani dengan aman.
- [x] Komunitas dapat dihubungkan dengan beberapa area pelayanan.
- [x] Search/filter/pagination admin berfungsi.
- [x] Public directory berfungsi dan hanya menampilkan komunitas aktif serta dipublikasikan.
- [x] Public detail berfungsi berdasarkan slug.
- [x] Privasi kontak ditegakkan pada response server.
- [x] Draft/forming/inactive tidak bocor ke halaman publik.
- [x] UI mengikuti `DESIGN.md` dan pola layout existing.
- [x] Tidak ada galeri foto komunitas pada Phase 7.
- [x] Tidak ada relasi `community_id` pada `posts` atau `events`.
- [x] Test yang relevan lulus.
- [x] Lint/typecheck/build/E2E yang tersedia sudah dijalankan sesuai repository.
- [x] Tidak ada regression pada fase sebelumnya.
- [x] Dokumentasi dan roadmap diperbarui.
- [x] Laporan akhir agent mencantumkan hasil aktual dan sisa pekerjaan.

---

## 17. Pengingat Penting

1. **Audit sebelum coding.** Repository adalah sumber kebenaran untuk nama route, komponen, package, dan pola implementasi.
2. **Jangan mengubah scope yang telah disetujui.** Tidak ada `community_id` pada `posts`/`events`; tidak ada galeri pada Phase 7.
3. **Jangan mengarang data paroki.** Data komunitas awal harus dikonfirmasi pihak paroki. Jangan mengaktifkan semua contoh kategori atau komunitas Katolik secara otomatis.
4. **Server adalah sumber kebenaran.** Validasi, authorization, publikasi, hierarki area, dan privasi harus ditegakkan di Laravel.
5. **Jangan menambah dependency tanpa alasan.** Gunakan design system, upload flow, dan test tooling yang sudah ada.
6. **Hindari refactor unrelated.** Fokus pada penyelesaian Phase 7 dan regression test.
7. **Jangan klaim selesai tanpa bukti.** Laporkan test, build, dan CI berdasarkan hasil command aktual.

**Akhir spesifikasi Phase 07 — Communities.**
