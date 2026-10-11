# PHASE 05 — News & Agenda

> **Proyek:** Website Resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau  
> **Dokumen:** `phase-05-news-&-agenda.md`  
> **Status:** Draft implementasi — gunakan setelah audit fondasi Phase 01–04  
> **Prioritas:** MVP  
> **Source of Truth:** `PRD.md`, lalu `ARCHITECTURE.md` dan `DESIGN.md`  
> **Stack:** Laravel 13, Inertia.js 3, React 19, TypeScript, Tailwind CSS 4, MySQL, Vite, Wayfinder, shadcn/ui, Tiptap

---5

## 1. Tujuan Phase

Membangun modul **Berita & Artikel** dan **Agenda Kegiatan** secara end-to-end: mulai dari skema database, model, validasi, otorisasi, panel admin, halaman publik, SEO, hingga pengujian.

Phase ini harus mengikuti fondasi aplikasi yang sudah ada. Jangan membuat ulang project, mengganti arsitektur, atau melakukan refactor lintas modul yang tidak dibutuhkan.

### 1.1 Hasil yang diharapkan

- Super Admin dapat membuat, mengubah, mencari, memfilter, mempratinjau, menerbitkan, menjadwalkan, mengarsipkan, dan menghapus secara soft delete artikel.
- Super Admin dapat mengelola kategori artikel.
- Artikel publik hanya menampilkan konten yang memang boleh diterbitkan.
- Super Admin dapat membuat, mengubah, menggandakan, memublikasikan, membatalkan, menunda, dan menghapus secara soft delete agenda.
- Super Admin dapat mengelola kategori agenda.
- Pengunjung dapat mencari dan memfilter berita, membaca detail artikel, melihat agenda dalam mode daftar atau kalender, dan membuka detail kegiatan.
- Pengunjung dapat menambahkan agenda ke Google Calendar atau mengunduh berkas `.ics`.
- Seluruh antarmuka responsif, aksesibel, konsisten dengan `DESIGN.md`, dan menggunakan Bahasa Indonesia untuk pesan antarmuka/validasi.
- Fitur memiliki automated tests yang relevan dan lolos pemeriksaan proyek.

### 1.2 Batasan penting

- **`posts` dan `events` TIDAK berelasi dengan `communities`.**
- Jangan menambahkan kolom `community_id` pada tabel `posts` maupun `events`.
- Jangan membuat foreign key atau relationship Eloquent dari `Post`/`Event` ke model `Community`.
- Sidebar admin harus memiliki group **Master Data** untuk menampung menu **Lokasi**, **Kategori Berita**, dan **Kategori Agenda**.
- Menu Lokasi merupakan bagian dari master data bersama yang sudah digunakan oleh modul Jadwal Misa. Phase 05 hanya perlu memastikan menu tersebut ditempatkan pada group sidebar yang benar; jangan membangun ulang modul Lokasi bila sudah tersedia.
- Modul ini tidak mencakup RSVP, komentar, kegiatan berulang otomatis, workflow persetujuan editor, atau manajemen Role/Permission UI.
- MVP hanya memakai role `super_admin`, dengan fondasi otorisasi Spatie Laravel Permission yang sudah ditetapkan dalam PRD.
- Tidak ada REST API terpisah. Gunakan Laravel + Inertia untuk alur aplikasi.
- Jangan mengubah modul Jadwal Misa Phase 04 kecuali diperlukan untuk kompatibilitas dan telah disetujui.

---

## 2. Prasyarat dan Audit Awal

Sebelum implementasi:

1. Baca `AGENTS.md` jika tersedia, `PRD.md`, `ARCHITECTURE.md`, `DESIGN.md`, roadmap utama, serta dokumen Phase 04.
2. Audit implementasi aktual; jangan berasumsi bahwa seluruh fondasi telah tersedia hanya karena tercantum di dokumen.
3. Periksa versi dan pola yang sudah digunakan untuk Laravel, Inertia, React, TypeScript, Tailwind, shadcn/ui, Wayfinder, Tiptap, testing, serta lint/type checking.
4. Periksa `users`, autentikasi admin, role `super_admin`, `locations`, layout admin, layout publik, navigasi, komponen UI, dan pola flash message.
5. Pastikan route dan nama route Phase 04 tidak tertimpa.
6. Periksa apakah scheduler, queue, filesystem public, proses gambar, sanitasi HTML, serta Wayfinder sudah dikonfigurasi.
7. Catat gap aktual sebelum coding. Jika implementasi bertentangan dengan PRD atau dokumen arsitektur, hentikan bagian yang terdampak dan laporkan konflik; jangan diam-diam mengganti keputusan.

### 2.1 Aturan perubahan kode

- Kerjakan hanya ruang lingkup Phase 05.
- Pertahankan struktur proyek dan konvensi yang sudah ada.
- Jangan menambahkan dependency baru bila kemampuan yang diperlukan sudah tersedia.
- Jika dependency baru benar-benar dibutuhkan, jelaskan alasan dan dampaknya sebelum menambahkannya.
- Jangan mengedit file generated secara manual jika file tersebut dapat dihasilkan ulang.
- Jangan mengklaim test, build, atau CI berhasil jika belum benar-benar dijalankan.
- Ikuti workflow proyek: **Audit → Plan → Approval bila diwajibkan → Implement → Test → Review/PR**.
- Jangan bekerja langsung pada `main`; ikuti strategi branch yang ditetapkan repository.

---

## 3. Ruang Lingkup Fitur

### 3.1 Berita & Artikel — Admin

- Daftar artikel dengan pencarian, filter kategori/status, paginasi, dan indikator status.
- Membuat dan mengubah artikel.
- Rich text editor menggunakan Tiptap.
- Unggah gambar utama dan gambar sisipan sesuai kemampuan editor.
- Mengisi teks alternatif gambar.
- Mengisi ringkasan/excerpt.
- Memilih kategori artikel.
- Mengisi referensi ayat khusus untuk kategori Renungan.
- Menyematkan artikel (_pinned_), dengan batas maksimal dua artikel aktif yang disematkan.
- Status artikel: `draft`, `scheduled`, `published`, `archived`.
- Penjadwalan publikasi otomatis.
- Pratinjau artikel sebelum diterbitkan.
- Metadata SEO opsional.
- Soft delete dan konfirmasi sebelum menghapus.
- Pengelolaan kategori artikel: nama, slug, dan urutan.
- Indikator/peringatan bila ada perubahan form yang belum disimpan, jika pola aplikasi mendukungnya.

### 3.2 Berita & Artikel — Publik

- Halaman daftar `/berita`.
- Halaman detail `/berita/{slug}`.
- Pencarian judul dan isi.
- Filter kategori.
- Artikel pinned tampil lebih dahulu, maksimal dua artikel pinned.
- Artikel lainnya diurutkan berdasarkan `published_at` terbaru.
- Kartu artikel memuat gambar, kategori, tanggal, judul, ringkasan, dan tautan baca selengkapnya.
- Detail memuat kategori, judul, penulis yang ditampilkan, tanggal terbit, gambar utama, isi, serta referensi ayat jika tersedia.
- Tombol berbagi ke WhatsApp, Facebook, dan salin tautan.
- Artikel terkait berdasarkan kategori; navigasi sebelumnya/berikutnya dapat disertakan jika sesuai dengan pola UI.
- Metadata SEO dan Open Graph untuk detail artikel.
- Tampilan responsif dan status kosong yang jelas.

### 3.3 Agenda — Admin

- Daftar agenda dengan pencarian, filter kategori, filter periode/bulan, status kegiatan, dan paginasi.
- Membuat, mengubah, melihat/pratinjau, menggandakan, dan soft delete agenda.
- Mengelola kategori agenda.
- Menentukan tanggal dan waktu mulai serta waktu selesai.
- Mendukung agenda sepanjang hari (`is_all_day`).
- Memilih lokasi dari master `locations` atau mengisi nama lokasi bebas.
- Mengunggah poster dan mengisi teks alternatif poster.
- Status kegiatan: `scheduled`, `cancelled`, `postponed`.
- Toggle publikasi (`is_published`).
- Dialog konfirmasi sebelum penghapusan.
- Form dan pesan validasi menggunakan Bahasa Indonesia.

### 3.4 Agenda — Publik

- Halaman daftar `/agenda`.
- Halaman detail `/agenda/{slug}`.
- Mode daftar sebagai tampilan utama yang mudah digunakan di ponsel.
- Mode kalender bulanan dengan navigasi bulan dan tombol “Hari Ini”.
- Filter kategori agenda.
- Daftar agenda mendatang.
- Agenda `cancelled` tetap ditampilkan dengan label “Dibatalkan”.
- Agenda `postponed` tetap ditampilkan dengan label “Ditunda”.
- Detail kegiatan menampilkan judul, kategori, tanggal, waktu, lokasi, deskripsi, poster, dan status.
- Tombol “Tambahkan ke Google Calendar”.
- Tautan unduh kalender `.ics` melalui `/agenda/{slug}/ics`.
- Metadata SEO dan structured data `Event` jika sesuai implementasi.
- Status kosong, agenda tanpa poster, agenda sepanjang hari, dan agenda yang dibatalkan/ditunda harus ditangani dengan baik.

---

## 4. Desain UI/UX

Ikuti `DESIGN.md` sebagai sumber kebenaran desain. Gunakan referensi UI yang telah disetujui untuk halaman admin dan publik sebagai arahan visual, bukan alasan untuk membuat sistem desain baru.

### 4.1 Panel admin

- Gunakan `AdminLayout` dan sidebar yang telah ada.
- Pertahankan pola header halaman, breadcrumb, tombol aksi utama, filter, tabel/list, badge status, pagination, dialog konfirmasi, toast/flash message.
- Perbarui navigasi sidebar admin dengan group **Master Data** yang berisi tiga menu:
    - **Lokasi** — menuju modul pengelolaan lokasi yang sudah digunakan oleh Jadwal Misa.
    - **Kategori Berita** — menuju pengelolaan `post_categories`.
    - **Kategori Agenda** — menuju pengelolaan `event_categories`.
- Group **Master Data** harus terlihat sebagai kelompok navigasi tersendiri, terpisah dari group **Konten** dan **Akun**.
- Jangan menduplikasi menu Lokasi di beberapa group. Pindahkan/daftarkan item Lokasi yang sudah ada ke group Master Data dan pertahankan route serta perilaku existing-nya.
- Ikon, indentasi, active state, expanded/collapsed state, hover/focus state, dan spacing harus mengikuti pola sidebar yang sudah ada di proyek.
- Active state pada item Master Data harus mengikuti route item yang tepat. Jangan membuat parent group terlihat aktif hanya karena salah satu submenu aktif, kecuali memang itulah pola visual sidebar yang disepakati proyek.
- Halaman daftar harus memiliki hierarki visual yang jelas dan tidak terlalu padat.
- Form artikel dibagi menjadi bagian yang mudah dipahami:
    1. Informasi utama: judul, slug, kategori, ringkasan, status.
    2. Isi artikel: Tiptap.
    3. Media: gambar utama, alt text, gambar sisipan.
    4. Pengaturan publikasi: tanggal publikasi, pinned.
    5. SEO: meta title dan meta description.
    6. Referensi ayat, jika relevan untuk Renungan.
- Form agenda dibagi menjadi:
    1. Informasi utama: judul, slug, kategori, deskripsi.
    2. Waktu kegiatan: tanggal/waktu mulai, selesai, sepanjang hari.
    3. Lokasi: master lokasi atau lokasi bebas.
    4. Poster dan alt text.
    5. Status kegiatan dan publikasi.
- Gunakan validasi inline dekat dengan field yang bermasalah.
- Form create/edit sebaiknya memakai komponen bersama bila sesuai konvensi proyek.
- Jangan mengandalkan warna saja untuk menyampaikan status.

### 4.2 Halaman publik

- Gunakan header, footer, tipografi, warna, spacing, dan komponen publik yang telah ada.
- Halaman berita memiliki heading, ringkasan, pencarian, filter kategori, area artikel unggulan, grid/list artikel, dan pagination.
- Halaman detail artikel harus nyaman dibaca: lebar konten terkontrol, jarak paragraf memadai, gambar responsif, dan metadata mudah ditemukan.
- Halaman agenda menyediakan pergantian mode daftar/kalender, filter kategori, navigasi bulan, dan kartu agenda.
- Detail agenda menonjolkan judul, tanggal/waktu, lokasi, status, serta aksi Google Calendar dan `.ics`.
- Jangan mengarang identitas, alamat, jadwal, atau konten nyata paroki untuk seed produksi. Gunakan data contoh yang jelas atau data seed kategori saja.
- Pastikan UI berfungsi di viewport ponsel, tablet, dan desktop.

---

## 5. Skema Database

> **Keputusan skema yang disetujui:** tabel `posts` dan `events` tidak memiliki relasi ke `communities`. Jika `PRD.md` versi lama masih menyebut `community_id`, keputusan khusus Phase 05 ini yang digunakan untuk implementasi fitur tersebut dan konflik dokumentasi harus dilaporkan untuk pembaruan PRD secara terpisah.

### 5.1 Tabel `post_categories`

| Kolom        | Tipe/aturan                    | Keterangan        |
| ------------ | ------------------------------ | ----------------- |
| `id`         | BIGINT UNSIGNED, PK            | ID kategori       |
| `name`       | VARCHAR(255), NOT NULL         | Nama kategori     |
| `slug`       | VARCHAR(255), UNIQUE, NOT NULL | Slug URL          |
| `sort_order` | INT, NOT NULL, default 0       | Urutan tampilan   |
| `created_at` | TIMESTAMP nullable             | Timestamp Laravel |
| `updated_at` | TIMESTAMP nullable             | Timestamp Laravel |

Seed kategori awal:

| Nama       | Slug         | Urutan |
| ---------- | ------------ | -----: |
| Berita     | `berita`     |      1 |
| Kegiatan   | `kegiatan`   |      2 |
| Renungan   | `renungan`   |      3 |
| Pengumuman | `pengumuman` |      4 |

### 5.2 Tabel `posts`

| Kolom                 | Tipe/aturan                                                          | Keterangan                                                                        |
| --------------------- | -------------------------------------------------------------------- | --------------------------------------------------------------------------------- |
| `id`                  | BIGINT UNSIGNED, PK                                                  | ID artikel                                                                        |
| `post_category_id`    | FK ke `post_categories.id`, NOT NULL, ON DELETE RESTRICT             | Kategori artikel                                                                  |
| `author_id`           | FK ke `users.id`, NOT NULL, ON DELETE RESTRICT                       | User pembuat artikel                                                              |
| `author_name`         | VARCHAR(255), nullable                                               | Nama penulis yang ditampilkan; jika kosong gunakan fallback yang telah ditetapkan |
| `title`               | VARCHAR(255), NOT NULL                                               | Judul                                                                             |
| `slug`                | VARCHAR(255), UNIQUE, NOT NULL                                       | Slug URL                                                                          |
| `excerpt`             | TEXT, nullable                                                       | Ringkasan                                                                         |
| `content`             | LONGTEXT, NOT NULL                                                   | HTML artikel hasil Tiptap yang telah disanitasi                                   |
| `featured_image`      | VARCHAR(255), nullable                                               | Path gambar utama                                                                 |
| `image_alt`           | VARCHAR(255), nullable                                               | Teks alternatif gambar utama                                                      |
| `scripture_reference` | VARCHAR(255), nullable                                               | Referensi ayat untuk Renungan                                                     |
| `status`              | ENUM(`draft`, `scheduled`, `published`, `archived`), default `draft` | Status publikasi                                                                  |
| `published_at`        | DATETIME, nullable                                                   | Waktu publikasi yang disimpan dalam UTC                                           |
| `is_pinned`           | BOOLEAN, default false                                               | Artikel sematan                                                                   |
| `view_count`          | INT UNSIGNED, default 0                                              | Jumlah tampilan                                                                   |
| `meta_title`          | VARCHAR(255), nullable                                               | SEO title                                                                         |
| `meta_description`    | TEXT, nullable                                                       | SEO description                                                                   |
| `created_at`          | TIMESTAMP nullable                                                   | Timestamp Laravel                                                                 |
| `updated_at`          | TIMESTAMP nullable                                                   | Timestamp Laravel                                                                 |
| `deleted_at`          | TIMESTAMP nullable                                                   | Soft delete                                                                       |

**Tidak boleh ada `community_id` di tabel ini.**

Indeks yang dibutuhkan:

- UNIQUE pada `slug`.
- Index gabungan `(status, published_at)`.
- Index pada `is_pinned` bila bermanfaat sesuai query aktual.
- FULLTEXT opsional untuk pencarian bila implementasi dan dukungan MySQL cocok. Jangan menambahkan `content_text` tanpa kebutuhan yang terukur dan keputusan eksplisit.

Relasi Eloquent:

- `Post belongsTo PostCategory`.
- `Post belongsTo User` sebagai author.
- `Post` menggunakan `SoftDeletes`.
- `PostCategory hasMany Post`.
- Tidak ada relasi `Post` ↔ `Community`.

### 5.3 Tabel `event_categories`

| Kolom        | Tipe/aturan                    | Keterangan                    |
| ------------ | ------------------------------ | ----------------------------- |
| `id`         | BIGINT UNSIGNED, PK            | ID kategori                   |
| `name`       | VARCHAR(255), NOT NULL         | Nama kategori                 |
| `slug`       | VARCHAR(255), UNIQUE, NOT NULL | Slug kategori                 |
| `color`      | VARCHAR(7), NOT NULL           | Warna hex, misalnya `#2E7D32` |
| `created_at` | TIMESTAMP nullable             | Timestamp Laravel             |
| `updated_at` | TIMESTAMP nullable             | Timestamp Laravel             |

Seed kategori awal:

| Nama      | Slug        | Warna contoh |
| --------- | ----------- | ------------ |
| Liturgi   | `liturgi`   | `#2E7D32`    |
| Pembinaan | `pembinaan` | `#1976D2`    |
| Sosial    | `sosial`    | `#F57C00`    |
| Rapat     | `rapat`     | `#7B1FA2`    |
| Lainnya   | `lainnya`   | `#757575`    |

Warna harus divalidasi sebagai kode hex valid. Warna merupakan petunjuk visual, bukan satu-satunya penanda kategori.

### 5.4 Tabel `events`

| Kolom               | Tipe/aturan                                                      | Keterangan                                        |
| ------------------- | ---------------------------------------------------------------- | ------------------------------------------------- |
| `id`                | BIGINT UNSIGNED, PK                                              | ID kegiatan                                       |
| `event_category_id` | FK ke `event_categories.id`, NOT NULL, ON DELETE RESTRICT        | Kategori kegiatan                                 |
| `title`             | VARCHAR(255), NOT NULL                                           | Judul kegiatan                                    |
| `slug`              | VARCHAR(255), UNIQUE, NOT NULL                                   | Slug URL                                          |
| `description`       | LONGTEXT, nullable                                               | Deskripsi HTML tersanitasi bila rich text dipakai |
| `start_at`          | DATETIME, NOT NULL                                               | Waktu mulai, disimpan UTC                         |
| `end_at`            | DATETIME, nullable                                               | Waktu selesai, disimpan UTC                       |
| `is_all_day`        | BOOLEAN, default false                                           | Kegiatan sepanjang hari                           |
| `location_name`     | VARCHAR(255), nullable                                           | Nama lokasi bebas                                 |
| `location_id`       | FK ke `locations.id`, nullable, ON DELETE SET NULL               | Referensi lokasi master                           |
| `poster_path`       | VARCHAR(255), nullable                                           | Path poster                                       |
| `poster_alt`        | VARCHAR(255), nullable                                           | Alt text poster                                   |
| `status`            | ENUM(`scheduled`, `cancelled`, `postponed`), default `scheduled` | Status kegiatan                                   |
| `is_published`      | BOOLEAN, default false                                           | Apakah agenda tampil publik                       |
| `created_at`        | TIMESTAMP nullable                                               | Timestamp Laravel                                 |
| `updated_at`        | TIMESTAMP nullable                                               | Timestamp Laravel                                 |
| `deleted_at`        | TIMESTAMP nullable                                               | Soft delete                                       |

**Tidak boleh ada `community_id` di tabel ini.**

Indeks yang dibutuhkan:

- UNIQUE pada `slug`.
- Index pada `start_at`.
- Index gabungan `(is_published, start_at)`.
- Foreign key `event_category_id` dengan `ON DELETE RESTRICT`.
- Foreign key `location_id` nullable dengan `ON DELETE SET NULL`.

Relasi Eloquent:

- `Event belongsTo EventCategory`.
- `Event belongsTo Location` secara nullable.
- `Event` menggunakan `SoftDeletes`.
- `EventCategory hasMany Event`.
- Tidak ada relasi `Event` ↔ `Community`.

### 5.5 Aturan relasi dan penghapusan kategori

- Kategori yang masih dipakai artikel/kegiatan tidak boleh dihapus melalui UI; tampilkan pesan yang jelas.
- Jangan menghapus artikel/kegiatan secara cascade ketika kategori dihapus.
- Artikel/kegiatan yang dihapus menggunakan soft delete.
- Soft-deleted content tidak boleh tampil di daftar publik, kalender, hasil pencarian, atau endpoint `.ics`.
- Lokasi agenda bersifat opsional. Jika `location_id` tersedia, data master lokasi menjadi sumber informasi lokasi utama; jika tidak, gunakan `location_name` bila terisi.
- Tidak ada tabel, kolom, foreign key, maupun query join ke `communities` dalam modul News & Agenda.

---

## 6. Model dan Aturan Domain

### 6.1 Model

Implementasikan model sesuai konvensi repository:

- `PostCategory`
- `Post`
- `EventCategory`
- `Event`

Gunakan cast untuk boolean, status enum bila pola proyek mendukungnya, dan datetime. Terapkan `SoftDeletes` pada `Post` dan `Event`.

Jangan memasukkan logika bisnis yang kompleks ke controller atau komponen React. Ikuti pola service/action/domain yang benar-benar sudah dipakai di repository; hindari membuat lapisan abstraksi baru tanpa kebutuhan.

### 6.2 Slug

- Slug dibuat otomatis dari judul ketika field kosong.
- Slug harus unik dan divalidasi di server.
- Jika slug bentrok, hasilkan slug unik dengan suffix yang deterministik dan aman.
- Untuk artikel yang sudah terbit, slug tidak berubah otomatis ketika judul diedit.
- Perubahan slug setelah artikel terbit hanya terjadi bila admin mengubahnya secara manual.
- Perubahan slug agenda yang sudah dibagikan sebaiknya memerlukan perubahan manual yang disengaja.
- Gunakan route model binding berdasarkan slug untuk halaman publik sesuai konvensi aplikasi.

### 6.3 Zona waktu

- Simpan nilai `datetime` dalam UTC.
- Zona waktu tampilan dan perhitungan kalender untuk paroki adalah `Asia/Pontianak` (WIB/UTC+7).
- Konversi timezone harus dilakukan konsisten di boundary input/output; jangan mengonversi nilai `time` yang bukan bagian dari Phase ini.
- Pengujian tanggal/jam harus mencakup pergantian hari dan batas zona waktu.

---

## 7. Aturan Bisnis Berita

### 7.1 Status publikasi

- `draft`: hanya terlihat oleh admin.
- `scheduled`: menunggu `published_at`; tidak tampil untuk publik sebelum waktunya.
- `published`: dapat tampil untuk publik jika `published_at` tidak berada di masa depan.
- `archived`: tidak tampil pada daftar publik dan detail publik.
- Jika status menjadi `published` dan `published_at` kosong, tetapkan waktu sekarang sesuai aturan domain dan simpan dalam UTC.
- Jika status `scheduled`, `published_at` wajib tersedia dan harus berada di masa depan saat penjadwalan dibuat/diubah.
- Scheduler memeriksa artikel terjadwal setiap menit. Artikel dengan `published_at <= now` diubah ke status `published`.
- Proses publikasi harus idempotent dan aman ketika dijalankan berulang.

### 7.2 Akses publik

- Daftar dan detail publik hanya boleh mengambil artikel yang `status = published`, `published_at` tidak null dan `published_at <= now`, serta tidak soft-deleted.
- Draf, terjadwal, arsip, dan konten yang tidak memenuhi aturan publikasi harus menghasilkan 404 pada URL publik, termasuk ketika slug diketahui.
- Jangan membocorkan keberadaan artikel privat melalui hasil pencarian, related posts, metadata, sitemap, atau Open Graph.

### 7.3 Pinned

- Maksimal dua artikel published yang disematkan ditampilkan di bagian unggulan.
- Artikel pinned diurutkan secara konsisten; sisanya berdasarkan `published_at DESC`.
- Validasi batas pinned dilakukan secara aman di server agar dua request bersamaan tidak mudah menghasilkan lebih dari dua pinned aktif.
- Jika batas dua pinned tidak dapat dipenuhi karena kondisi race, penanganan harus konsisten dan diuji.

### 7.4 Pencarian dan jumlah tampilan

- Pencarian MVP menggunakan `LIKE` atau FULLTEXT MySQL pada judul, ringkasan, dan isi teks yang aman untuk dicari.
- Tidak perlu Laravel Scout atau Meilisearch untuk MVP.
- Query harus mempertahankan scope publikasi dan soft delete.
- `view_count` bertambah maksimal sekali per sesi per artikel.
- Abaikan user-agent bot umum untuk mengurangi inflasi hitungan.
- Jangan menerima `view_count` dari input pengguna.

### 7.5 Sanitasi HTML

- HTML dari Tiptap harus disanitasi di server menggunakan allowlist elemen/atribut yang diperlukan.
- Jangan mengandalkan sanitasi klien.
- Blokir `script`, event handler seperti `onerror`, javascript URL, iframe/embed yang tidak diizinkan, dan elemen berbahaya lain.
- Pastikan output aman sebelum dirender di halaman publik.
- Bila sanitasi memerlukan dependency baru, audit opsi yang tersedia dan jelaskan sebelum menambahkan package.

### 7.6 Media artikel

- Gambar utama maksimum 3 MB.
- Format yang diizinkan mengikuti PRD dan implementasi filesystem (JPG/JPEG, PNG, WebP).
- Validasi MIME nyata, bukan hanya ekstensi.
- Buat varian gambar dan WebP sesuai mekanisme media proyek/PRD.
- Strip EXIF bila mekanisme image processing proyek mendukungnya.
- Path disimpan di database; file dikelola melalui Laravel Filesystem.
- Alt text harus tersedia untuk gambar utama yang informatif; sediakan validasi/indikator yang sesuai dengan kebijakan konten.
- Saat gambar diganti, bersihkan file lama hanya setelah penyimpanan baru berhasil dan aturan storage mengizinkan.
- Jangan menghapus file yang masih dipakai konten lain.

### 7.7 SEO artikel

- Meta title dan description boleh kosong.
- Jika kosong, gunakan fallback judul dan excerpt/description yang sesuai.
- Halaman detail wajib menghasilkan Open Graph title, description, dan image.
- Pastikan artikel yang tidak publik tidak dimasukkan ke sitemap.
- URL canonical harus konsisten dengan slug final.

---

## 8. Aturan Bisnis Agenda

### 8.1 Validasi tanggal dan waktu

- `start_at` wajib.
- `end_at` opsional.
- Jika `end_at` terisi, nilainya harus sama dengan atau setelah `start_at`.
- Untuk kegiatan bukan sepanjang hari, form harus menjelaskan waktu yang belum diisi dan memvalidasi kombinasi tanggal/jam dengan jelas.
- Untuk `is_all_day = true`, UI tidak perlu memaksa admin memasukkan jam; simpan dan tampilkan sebagai kegiatan sepanjang hari sesuai konvensi aplikasi.
- Konversi tanggal/waktu input dari `Asia/Pontianak` ke UTC sebelum disimpan.
- Jangan menerima tanggal yang gagal diparse atau nilai timezone yang tidak diharapkan.

### 8.2 Publikasi dan status

- Agenda hanya dapat tampil publik bila `is_published = true` dan tidak soft-deleted.
- `scheduled`: kegiatan terjadwal.
- `cancelled`: tetap ditampilkan pada daftar/kalender dengan label “Dibatalkan”.
- `postponed`: tetap ditampilkan dengan label “Ditunda”.
- Agenda yang dibatalkan atau ditunda tidak otomatis dihapus.
- Perubahan status harus terlihat jelas pada detail dan daftar.
- Tidak ada workflow persetujuan untuk publikasi agenda pada MVP.

### 8.3 Daftar mendatang dan kalender

- Daftar mendatang menggunakan waktu mulai berdasarkan `Asia/Pontianak`.
- Kegiatan lampau tetap dapat dilihat pada navigasi kalender bulan lampau.
- Kalender menampilkan indikator untuk tanggal yang memiliki agenda dan daftar kegiatan untuk tanggal terpilih jika sesuai desain.
- Navigasi kalender menyediakan bulan sebelumnya, bulan berikutnya, dan “Hari Ini”.
- Pada ponsel, mode daftar menjadi tampilan awal; kalender tidak boleh memaksa tabel/grid yang terpotong.
- Filter kategori harus bekerja pada mode daftar dan kalender.
- Status `cancelled` dan `postponed` tetap masuk hasil filter serta tetap memiliki label.

### 8.4 Lokasi

- Agenda dapat memakai `location_id` dari tabel `locations` atau nama lokasi bebas di `location_name`.
- `location_id` nullable dengan `ON DELETE SET NULL`.
- Bila `location_id` valid tersedia, gunakan nama/alamat dari master lokasi untuk tampilan utama dan link peta bila tersedia.
- Jika tidak ada `location_id`, tampilkan `location_name` bila ada.
- Jangan menambahkan `community_id` atau penyelenggara komunitas sebagai bagian dari form agenda.

### 8.5 Google Calendar dan ICS

- Tombol Google Calendar membangun URL event dari data yang sudah tervalidasi dan dienkode dengan benar.
- Endpoint publik unduh kalender: `GET /agenda/{slug}/ics`.
- ICS mengikuti RFC 5545 dan memuat UID yang stabil, DTSTART, DTEND bila ada, SUMMARY, LOCATION, DESCRIPTION, STATUS, serta format tanggal all-day bila berlaku.
- Gunakan waktu UTC untuk DTSTART/DTEND sesuai spesifikasi ICS.
- Status kegiatan dibatalkan direpresentasikan dengan `STATUS:CANCELLED`.
- Endpoint ICS hanya tersedia untuk agenda published dan tidak soft-deleted; agenda nonpublik harus 404.
- Set header `Content-Type` dan nama unduhan yang sesuai.
- Hindari CRLF injection pada data yang dimasukkan ke file ICS dengan melakukan escaping yang benar.
- Uji kompatibilitas format, event sepanjang hari, event tanpa waktu selesai, dan status pembatalan.

### 8.6 Poster agenda

- Validasi tipe dan ukuran file mengikuti pola media proyek; tentukan batas ukuran berdasarkan konfigurasi media/PRD yang berlaku.
- Simpan poster melalui Laravel Filesystem.
- Sediakan alt text.
- UI tetap berfungsi tanpa poster.
- Hapus file lama secara aman saat poster diganti, tanpa menghapus file yang masih direferensikan.

---

## 9. Rute

Nama route akhir harus mengikuti pola proyek dan Wayfinder. Daftar di bawah adalah kebutuhan rute, bukan instruksi untuk menimpa penamaan yang sudah ada.

### 9.1 Publik

| Method | Path                 | Tujuan                                        |
| ------ | -------------------- | --------------------------------------------- |
| GET    | `/berita`            | Daftar artikel, pencarian, filter, pagination |
| GET    | `/berita/{slug}`     | Detail artikel publik                         |
| GET    | `/agenda`            | Daftar/kalender agenda                        |
| GET    | `/agenda/{slug}`     | Detail agenda publik                          |
| GET    | `/agenda/{slug}/ics` | Unduh kalender ICS                            |

### 9.2 Admin

Semua rute admin harus berada di dalam middleware autentikasi dan otorisasi yang sesuai.

### 9.3 Navigasi sidebar admin

Struktur group sidebar admin yang diharapkan setelah Phase 05:

```text
Dashboard
Konten
├── Beranda
├── Profil
├── Jadwal Misa
├── Berita
├── Agenda
├── Pelayanan
├── Komunitas
├── Galeri
├── Kontak
└── Download
Master Data
├── Lokasi
├── Kategori Berita
└── Kategori Agenda
Akun
├── Pengaturan
└── Akun
```

Catatan implementasi:

- Gunakan komponen/config sidebar existing; jangan membuat sidebar baru khusus Phase 05.
- Menu **Lokasi** harus memakai route yang sudah digunakan modul Lokasi/Jadwal Misa. Jangan menambahkan route duplikat.
- **Kategori Berita** mengarah ke CRUD kategori artikel (`post_categories`).
- **Kategori Agenda** mengarah ke CRUD kategori agenda (`event_categories`).
- Ketiga menu hanya boleh diakses Super Admin sesuai otorisasi backend.
- Pastikan sidebar tetap usable pada viewport desktop dan mobile, termasuk saat group diperluas atau diciutkan.
- Struktur di atas adalah pengelompokan navigasi yang diharapkan; pertahankan label dan route existing bila ada konvensi proyek yang berbeda, tanpa mengubah maksud group Master Data.

| Path konseptual                   | Fungsi                             |
| --------------------------------- | ---------------------------------- |
| `/admin/berita`                   | Daftar artikel                     |
| `/admin/berita/create`            | Form tambah artikel                |
| `/admin/berita/{post}/edit`       | Form edit artikel                  |
| `/admin/berita/{post}/preview`    | Pratinjau aman artikel untuk admin |
| `/admin/kategori-berita`          | CRUD kategori artikel              |
| `/admin/agenda`                   | Daftar agenda                      |
| `/admin/agenda/create`            | Form tambah agenda                 |
| `/admin/agenda/{event}/edit`      | Form edit agenda                   |
| `/admin/agenda/{event}/duplicate` | Aksi menggandakan agenda           |
| `/admin/kategori-agenda`          | CRUD kategori agenda               |

Gunakan resource routes atau pola route yang konsisten dengan proyek. Tentukan route names, binding, dan method berdasarkan implementasi aktual. Hindari endpoint mutasi melalui GET.

---

## 10. Struktur Frontend

Ikuti arsitektur feature-oriented yang sudah ditetapkan pada `ARCHITECTURE.md`. Jangan memindahkan atau merefactor fitur lain untuk menyesuaikan Phase 05.

Struktur konseptual yang disarankan:

```text
resources/js/features/
├── admin/
│   ├── berita/
│   │   ├── pages/
│   │   ├── components/
│   │   ├── hooks/
│   │   ├── types.ts
│   │   └── utils/
│   ├── kategori-berita/
│   │   ├── pages/
│   │   ├── components/
│   │   └── types.ts
│   ├── agenda/
│   │   ├── pages/
│   │   ├── components/
│   │   ├── hooks/
│   │   ├── types.ts
│   │   └── utils/
│   └── kategori-agenda/
│       ├── pages/
│       ├── components/
│       └── types.ts
└── public/
    ├── berita/
    │   ├── pages/
    │   ├── components/
    │   └── types.ts
    └── agenda/
        ├── pages/
        ├── components/
        └── types.ts
```

Struktur di atas merupakan panduan fitur; sesuaikan dengan struktur yang benar-benar digunakan repository dan jangan membuat folder duplikat bila pola yang sama sudah tersedia.

### 10.1 Konvensi frontend

- TypeScript strict sesuai konfigurasi proyek.
- Gunakan named exports untuk komponen/utilitas; default export hanya untuk route/page bila diwajibkan konvensi repository.
- Gunakan Inertia untuk props dan form submission.
- Gunakan Wayfinder untuk URL/action bila telah dikonfigurasi; jangan hardcode URL yang seharusnya dihasilkan.
- Gunakan komponen shadcn/ui yang sudah tersedia.
- Pisahkan tipe list item, detail, dan form bila bentuk datanya berbeda.
- Jangan menyalin seluruh model backend ke props bila halaman hanya membutuhkan subset.
- Tampilkan loading/processing, error validasi, success state, empty state, dan konfirmasi destructive action.
- Hindari komponen besar yang mencampur form, tabel, upload, editor, dan logika bisnis dalam satu file.
- Jangan menambahkan global state jika state lokal/Inertia sudah memadai.

---

## 11. Struktur Backend dan Otorisasi

Gunakan pola backend yang telah ditetapkan di `ARCHITECTURE.md`. Controller sebaiknya tipis; validasi berada di Form Request atau mekanisme validasi proyek; aturan domain diletakkan di tempat yang konsisten dengan aplikasi.

### 11.1 Tanggung jawab backend

- Migration dan seeder untuk empat tabel.
- Model dan relationship yang tercantum pada §5.
- Form Request untuk store/update sesuai pola proyek.
- Policy/Gate atau otorisasi yang telah dipakai aplikasi.
- Query publik yang menerapkan scope publikasi dan soft delete.
- Proses publikasi artikel terjadwal.
- Proses upload, validasi, transformasi media, dan cleanup.
- Builder/response ICS.
- SEO/metadata yang dikirim ke halaman publik.
- Pesan validasi Bahasa Indonesia.

### 11.2 Otorisasi

- Semua operasi tulis dan manajemen kategori hanya dapat dilakukan `super_admin`.
- Gunakan Spatie Laravel Permission sebagai fondasi role dan permission sesuai PRD.
- Backend harus memeriksa otorisasi; menyembunyikan tombol pada UI saja tidak cukup.
- Jangan membuat UI manajemen role/permission atau role baru.
- Endpoint preview hanya dapat diakses admin berwenang.
- Pastikan binding resource tidak memungkinkan akses terhadap konten milik resource yang tidak berhak diakses.

---

## 12. Scheduler, Queue, dan Media

### 12.1 Publikasi artikel terjadwal

- Implementasikan command/action untuk memublikasikan artikel berstatus `scheduled` yang waktu publikasinya telah tercapai.
- Jadwalkan berjalan setiap menit menggunakan konfigurasi scheduler proyek, umumnya di `routes/console.php`.
- Operasi harus idempotent.
- Pastikan timezone perbandingan konsisten (UTC).
- Uji command secara langsung dan melalui scheduler/fake clock bila pola test proyek mendukungnya.
- Jangan menjadwalkan agenda otomatis; agenda hanya memakai status dan `is_published`.

### 12.2 Queue media

- Jika fondasi queue/media telah tersedia, gunakan untuk membuat varian gambar dan WebP sesuai kebijakan proyek.
- Pastikan kegagalan job tidak menyebabkan path database menunjuk ke file yang belum tersedia.
- Sediakan penanganan kegagalan dan cleanup file sementara.
- Jangan menambahkan sistem media baru jika fitur yang ada sudah memadai.

### 12.3 Storage

- Gunakan Laravel Filesystem.
- Gambar publik disimpan pada disk publik yang dikonfigurasi proyek.
- Jangan menyimpan binary gambar langsung ke database.
- Validasi file di server.
- Pastikan file lama dibersihkan hanya setelah file pengganti berhasil disimpan dan tidak digunakan di tempat lain.

---

## 13. SEO dan Aksesibilitas

### 13.1 SEO

- Judul dan deskripsi halaman relevan dengan konten.
- Artikel detail memiliki Open Graph title, description, image.
- Agenda detail memiliki metadata yang sesuai.
- Tambahkan structured data `Event` pada detail agenda jika sesuai dengan pola SEO yang tersedia.
- Sitemap hanya memuat artikel yang publik dan agenda yang dipublikasikan.
- Slug harus stabil dan canonical URL harus benar.
- Jangan mengekspos draf/arsip/nonpublik pada sitemap, metadata, atau endpoint kalender.

### 13.2 Aksesibilitas

- Semua input memiliki label yang jelas.
- Error validasi terkait dengan field yang benar.
- Tombol ikon memiliki accessible name.
- Navigasi keyboard dapat digunakan pada dialog, dropdown, filter, editor, dan kalender.
- Status tidak hanya dibedakan dengan warna.
- Gambar informatif memiliki alt text yang sesuai; gambar dekoratif memiliki alt kosong.
- Kontras dan focus state mengikuti design system.
- Kalender harus tetap dapat digunakan melalui keyboard atau menyediakan alternatif daftar yang lengkap.

---

## 14. Strategi Pengujian

Gunakan test framework yang sudah terpasang dan menjadi standar repository (misalnya Pest/Vitest/Playwright bila memang sudah tersedia). Jangan memasang framework baru tanpa alasan.

### 14.1 Backend — database dan model

- Migration dapat dijalankan dari database kosong.
- Seeder kategori tidak menghasilkan duplikasi saat dijalankan sesuai strategi seeding proyek.
- Foreign key dan aturan penghapusan sesuai spesifikasi.
- Tidak ada `community_id` pada migration `posts` maupun `events`.
- Tidak ada relationship/query ke `communities` pada modul ini.
- Soft delete berfungsi.
- Slug unik dan valid.
- Kategori yang sedang dipakai tidak dapat dihapus.
- `end_at` tidak boleh sebelum `start_at`.

### 14.2 Backend — otorisasi dan CRUD

- Guest tidak dapat mengakses admin.
- User tanpa role yang sesuai tidak dapat melakukan mutasi.
- `super_admin` dapat melakukan CRUD.
- Validasi field wajib, enum, kategori, slug, tanggal, waktu, dan upload berfungsi.
- Penghapusan memakai soft delete.
- Preview tidak terbuka untuk publik.
- Error validasi disampaikan dengan pesan Bahasa Indonesia.

### 14.3 Berita — aturan bisnis

- Artikel draft tidak terlihat pada daftar/detail publik.
- Artikel scheduled tidak terlihat sebelum waktunya.
- Artikel scheduled dipublikasikan ketika waktunya tiba.
- Artikel archived tidak terlihat pada publik.
- Artikel dengan `published_at` di masa depan tidak tampil walaupun statusnya published.
- Batas dua pinned diterapkan.
- Pencarian memfilter hanya konten publik.
- `view_count` tidak meningkat berulang pada sesi yang sama dan bot umum diabaikan.
- HTML berbahaya dibuang oleh sanitasi server.
- Upload dengan MIME/ukuran tidak valid ditolak.
- Metadata Open Graph mengambil nilai yang benar.

### 14.4 Agenda — aturan bisnis

- Agenda unpublished dan soft-deleted tidak muncul di publik.
- Agenda published tampil pada daftar dan kalender.
- Agenda cancelled dan postponed tetap terlihat dengan label benar.
- Filter kategori dan navigasi bulan bekerja.
- `end_at` sebelum `start_at` ditolak.
- Konversi timezone benar di batas tanggal.
- Agenda all-day ditampilkan dengan benar.
- Google Calendar URL ter-encode dengan benar.
- File ICS valid untuk event biasa, all-day, tanpa `end_at`, dan cancelled.
- Endpoint ICS mengembalikan 404 untuk agenda nonpublik/soft-deleted.
- Input ICS yang berpotensi menyebabkan CRLF injection di-escape.

### 14.5 Frontend dan alur pengguna

- Halaman daftar, create/edit, preview, kategori, detail publik, kalender, dan mode daftar dapat digunakan.
- Pencarian, filter, pagination, form, upload, dan status menghasilkan UI yang benar.
- UI tidak pecah pada ponsel, tablet, dan desktop.
- Dialog destructive action meminta konfirmasi.
- Loading, error, empty state, dan success state ditangani.
- Navigasi dan form dapat dioperasikan dengan keyboard.
- Tautan berbagi, Google Calendar, dan unduhan ICS berfungsi.

### 14.6 Pemeriksaan akhir

Jalankan perintah yang benar-benar tersedia di `composer.json` dan `package.json`, setidaknya mencakup pemeriksaan yang relevan:

- test backend
- test frontend
- lint/format check
- TypeScript typecheck
- production build
- static analysis backend bila dikonfigurasi

Catat perintah dan hasil aktual. Jangan menyatakan lulus jika belum dijalankan.

---

## 15. Kriteria Penerimaan

### 15.1 Berita & Artikel

- [ ] Super Admin dapat membuat artikel dengan kategori, judul, slug, ringkasan, isi, dan gambar.
- [ ] Artikel dapat disimpan sebagai draft dan dipratinjau sebelum diterbitkan.
- [ ] Artikel dapat diterbitkan langsung atau dijadwalkan.
- [ ] Artikel scheduled diterbitkan otomatis ketika waktunya tiba.
- [ ] Artikel pinned dibatasi maksimal dua artikel yang tampil di area unggulan.
- [ ] Pencarian judul/isi, filter kategori, status admin, dan pagination bekerja.
- [ ] Artikel draft/scheduled/archived tidak bisa dibaca publik meskipun URL diketahui.
- [ ] HTML berbahaya dibersihkan di server.
- [ ] Gambar utama divalidasi dan alt text dapat dikelola.
- [ ] Metadata Open Graph pada detail artikel benar.
- [ ] Soft delete berfungsi dan konten terhapus tidak muncul di publik.
- [ ] Kategori artikel dapat dikelola dan kategori yang masih dipakai terlindungi dari penghapusan.

### 15.2 Agenda

- [ ] Super Admin dapat membuat, mengubah, menggandakan, memublikasikan, membatalkan, menunda, dan soft delete agenda.
- [ ] Kategori agenda dapat dikelola.
- [ ] Validasi waktu kegiatan berjalan benar.
- [ ] Agenda dapat memakai master lokasi atau nama lokasi bebas.
- [ ] Daftar dan kalender publik menampilkan agenda yang dipublikasikan.
- [ ] Agenda cancelled/postponed tetap tampil dengan label yang benar.
- [ ] Pengunjung dapat membuka detail agenda.
- [ ] Google Calendar link bekerja.
- [ ] Unduhan `.ics` valid dan hanya tersedia untuk agenda publik.
- [ ] Agenda nonpublik dan soft-deleted tidak muncul di publik.
- [ ] Kategori yang masih dipakai tidak dapat dihapus.

### 15.3 Teknis dan desain

- [ ] Tidak ada `community_id` pada tabel `posts` dan `events`.
- [ ] Tidak ada relationship `Post`/`Event` ke `Community`.
- [ ] Sidebar admin memiliki group **Master Data** dengan menu Lokasi, Kategori Berita, dan Kategori Agenda.
- [ ] Menu Lokasi menggunakan modul dan route existing, tanpa duplikasi.
- [ ] Seluruh mutasi dilindungi autentikasi dan otorisasi backend.
- [ ] UI admin dan publik mengikuti `DESIGN.md`.
- [ ] Halaman responsif dan pesan validasi berbahasa Indonesia.
- [ ] Test, typecheck, lint, dan build yang relevan telah dijalankan serta hasilnya dicatat.
- [ ] Tidak ada perubahan di luar cakupan tanpa alasan dan persetujuan.

---

## 16. Urutan Implementasi

Kerjakan berurutan dan verifikasi setiap bagian sebelum melanjutkan.

### Tahap 0 — Audit dan rencana

- [ ] Baca dokumen sumber.
- [ ] Audit fondasi Phase 01–04 dan pola aktual repository.
- [ ] Identifikasi gap, konflik, dependency, dan file yang akan terdampak.
- [ ] Susun rencana perubahan terperinci.
- [ ] Pastikan keputusan tanpa relasi `communities` diterapkan.

**Output:** catatan audit dan rencana implementasi.

### Tahap 1 — Database, model, dan seeder

- [ ] Buat migration `post_categories`.
- [ ] Buat migration `posts` tanpa `community_id`.
- [ ] Buat migration `event_categories`.
- [ ] Buat migration `events` tanpa `community_id`.
- [ ] Definisikan index dan foreign key secara eksplisit.
- [ ] Buat model dan relationship sesuai §5.
- [ ] Terapkan soft delete pada `Post` dan `Event`.
- [ ] Buat seeder kategori awal.
- [ ] Uji migration, rollback, dan seeder.

**Output:** database dan domain dasar yang dapat diuji.

### Tahap 2 — Admin kategori

- [ ] CRUD kategori artikel.
- [ ] CRUD kategori agenda.
- [ ] Validasi nama, slug, dan warna kategori.
- [ ] Urutan kategori artikel.
- [ ] Proteksi kategori yang masih dipakai.
- [ ] Responsive UI dan test.

**Output:** kedua master kategori siap digunakan oleh form konten.

### Tahap 3 — Admin berita

- [ ] Daftar, pencarian, filter status/kategori, dan pagination.
- [ ] Form create/edit.
- [ ] Integrasi Tiptap.
- [ ] Upload gambar utama dan alt text.
- [ ] Slug dan excerpt.
- [ ] Status, penjadwalan, pinned, referensi ayat, dan SEO.
- [ ] Preview admin.
- [ ] Soft delete dan dialog konfirmasi.
- [ ] Sanitasi HTML di server.
- [ ] Test aturan bisnis.

**Output:** alur pengelolaan artikel lengkap.

### Tahap 4 — Publik berita

- [ ] Halaman `/berita`.
- [ ] Filter kategori dan pencarian.
- [ ] Area pinned dan daftar artikel.
- [ ] Pagination dan empty state.
- [ ] Halaman `/berita/{slug}`.
- [ ] Related/previous-next bila sesuai rancangan.
- [ ] Share actions.
- [ ] SEO, Open Graph, canonical URL, dan view count.
- [ ] Test akses publik dan responsive UI.

**Output:** berita dapat dibaca dan dibagikan secara publik.

### Tahap 5 — Publikasi terjadwal

- [ ] Implementasikan command publikasi.
- [ ] Daftarkan scheduler setiap menit sesuai pola proyek.
- [ ] Pastikan proses idempotent.
- [ ] Uji batas waktu dengan fake clock/test time.
- [ ] Verifikasi konten terjadwal tidak bocor sebelum waktunya.

**Output:** scheduled posts bekerja otomatis.

### Tahap 6 — Admin agenda

- [ ] Daftar agenda, filter, pencarian, dan pagination.
- [ ] Form create/edit.
- [ ] Kategori, slug, deskripsi, tanggal/waktu, all-day.
- [ ] Lokasi master/lokasi bebas.
- [ ] Poster dan alt text.
- [ ] Status agenda dan publikasi.
- [ ] Duplicate event.
- [ ] Soft delete dan konfirmasi.
- [ ] Test validasi tanggal/timezone.

**Output:** pengelolaan agenda lengkap.

### Tahap 7 — Publik agenda

- [ ] Halaman `/agenda`.
- [ ] Mode daftar dan kalender bulanan.
- [ ] Navigasi bulan dan “Hari Ini”.
- [ ] Filter kategori.
- [ ] Agenda mendatang dan status cancelled/postponed.
- [ ] Detail `/agenda/{slug}`.
- [ ] Google Calendar URL.
- [ ] Endpoint ICS.
- [ ] SEO dan structured data `Event` bila sesuai.
- [ ] Test publikasi, timezone, ICS, dan responsive UI.

**Output:** pengunjung dapat menemukan, membaca, dan menambahkan agenda ke kalender.

### Tahap 8 — Integrasi, regression, dan review

- [ ] Periksa integrasi dengan header/footer publik.
- [ ] Periksa sidebar dan layout admin.
- [ ] Periksa bahwa Phase 04 Mass Schedule tetap berjalan.
- [ ] Jalankan seluruh test relevan.
- [ ] Jalankan lint, typecheck, static analysis, dan build yang tersedia.
- [ ] Periksa accessibility dasar dan responsive UI.
- [ ] Periksa query, eager loading, N+1, dan indeks.
- [ ] Periksa route publik agar konten nonpublik tidak bocor.
- [ ] Catat hasil test dan known issue.
- [ ] Update dokumentasi teknis yang memang terdampak.

**Output:** Phase 05 siap direview dan digabung sesuai workflow repository.

---

## 17. Di Luar Cakupan

Jangan implementasikan hal berikut dalam Phase 05:

- Relasi berita atau agenda ke `communities`.
- Pengelolaan komunitas, jenis komunitas, wilayah, atau lingkungan.
- RSVP/pendaftaran peserta agenda.
- Kegiatan berulang otomatis.
- Komentar, like, atau forum diskusi.
- Workflow persetujuan editor atau role selain `super_admin`.
- UI manajemen role/permission.
- Newsletter atau notifikasi massal email/WhatsApp.
- Pencarian global lintas modul.
- Integrasi kalender pihak ketiga selain tautan Google Calendar dan file `.ics`.
- REST API terpisah.
- Refactor besar pada layout atau fitur yang tidak terkait.
- Pengisian konten produksi yang belum divalidasi oleh pihak paroki.

---

## 18. Risiko dan Penanganan

| Risiko                                                 | Penanganan                                                                                    |
| ------------------------------------------------------ | --------------------------------------------------------------------------------------------- |
| Konflik keputusan lama pada PRD tentang `community_id` | Terapkan keputusan Phase 05 tanpa relasi komunitas; catat kebutuhan sinkronisasi PRD terpisah |
| Artikel scheduled tampil terlalu cepat                 | Scope query publik ketat dan test waktu publikasi                                             |
| HTML XSS dari rich text                                | Sanitasi server dengan allowlist dan test payload berbahaya                                   |
| Pinned lebih dari dua karena request bersamaan         | Validasi server dan operasi yang aman terhadap race condition                                 |
| Zona waktu agenda bergeser                             | Simpan datetime UTC, konversi di boundary, uji `Asia/Pontianak`                               |
| File yatim saat upload/ganti gambar                    | Cleanup terkontrol dan tidak menghapus file yang masih digunakan                              |
| ICS rusak atau rentan injection                        | Format RFC 5545, escaping CRLF, dan test file hasil                                           |
| Kategori terhapus padahal masih dipakai                | FK RESTRICT dan validasi aplikasi                                                             |
| Halaman publik membocorkan konten nonpublik            | Query scope bersama dan test URL langsung                                                     |
| UI kalender sulit dipakai di ponsel                    | Jadikan mode daftar sebagai default mobile                                                    |

---

## 19. Definition of Done

Phase 05 dianggap selesai apabila:

1. Semua kriteria penerimaan pada §15 terpenuhi.
2. Tidak ada relasi `posts` atau `events` ke `communities`.
3. CRUD admin, halaman publik, kategori, scheduler, dan ICS berfungsi sesuai cakupan.
4. Otorisasi diterapkan di backend.
5. Sanitasi HTML dan validasi upload telah diuji.
6. Data nonpublik tidak dapat diakses dari halaman publik atau ICS.
7. UI responsif dan konsisten dengan `DESIGN.md`.
8. Test, lint, typecheck, static analysis, dan build yang tersedia sudah dijalankan; hasil aktual dicatat.
9. Tidak ada regresi yang diketahui pada Phase 04.
10. Dokumentasi yang terdampak diperbarui dan perubahan siap direview.

---

## 20. Instruksi untuk AI Agent

Saat menerima dokumen ini:

1. Jangan langsung menulis kode sebelum membaca dokumen sumber dan mengaudit repository aktual.
2. Laporkan ringkasan kondisi repository, file yang akan diubah, dependency yang dibutuhkan, serta rencana implementasi.
3. Jika approval diwajibkan workflow proyek, tunggu persetujuan sebelum implementasi.
4. Implementasikan tahap demi tahap sesuai §16.
5. **Jangan menambahkan `community_id` ke `posts` atau `events`, dan jangan membuat relationship ke `Community`.**
6. Pastikan sidebar admin memiliki group **Master Data** yang berisi **Lokasi**, **Kategori Berita**, dan **Kategori Agenda**. Menu Lokasi harus menggunakan implementasi/route existing.
7. Jangan memperluas cakupan ke phase lain.
8. Setelah setiap tahap, jalankan test yang relevan.
9. Pada akhir pekerjaan, laporkan perubahan, hasil test aktual, command yang dijalankan, serta kendala/pekerjaan tersisa.
10. Jangan menyatakan Phase 05 selesai jika kriteria penerimaan belum terpenuhi.
