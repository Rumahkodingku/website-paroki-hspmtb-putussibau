# PHASE 8B — Modul Dokumen

> **Proyek:** Website Gereja Paroki HSPMTB Putussibau  
> **Fase:** 8B — Documents  
> **Status dokumen:** Spesifikasi implementasi untuk AI agent  
> **Scope:** Modul Dokumen saja. Modul Galeri (Phase 8A) dan integrasi Kontak (Phase 8C) tidak termasuk pekerjaan fase ini.

---

## 1. Tujuan

Membangun modul **Dokumen** sebagai pusat berkas resmi dan informasi yang ingin dibagikan oleh Paroki HSPMTB Putussibau. Admin dapat mengelola metadata, kategori, berkas, dan status publikasi dokumen melalui CMS. Pengunjung dapat menemukan, membaca informasi, dan mengunduh dokumen yang telah diterbitkan.

Nama yang terlihat oleh pengguna adalah **Dokumen**, bukan Download. URL publik utama yang dituju adalah `/dokumen`. Modul harus mudah dipahami oleh admin non-teknis, responsif, aksesibel, aman, dan konsisten dengan arsitektur serta design system proyek.

Contoh konten yang mungkin dikelola setelah divalidasi oleh pengurus paroki:

- Formulir administrasi.
- Formulir atau persyaratan sakramen.
- Panduan pelayanan paroki.
- Pengumuman atau surat resmi yang memang layak dipublikasikan.
- Panduan kegiatan dan komunitas.
- Dokumen informasi paroki lainnya.

Daftar kategori dan jenis dokumen final harus dapat disesuaikan dengan kebutuhan nyata paroki. Jangan menganggap contoh tersebut sebagai daftar konten yang sudah disetujui.

## 2. Instruksi utama untuk AI agent

**Jangan mulai coding sebelum audit dan rencana implementasi disajikan serta disetujui oleh pengguna/orchestrator.**

1. Baca `ARCHITECTURE.md`, `DESIGN.md`, `PRD.md`, `docs/DECISIONS.md`, serta dokumentasi fase/roadmap yang tersedia.
2. Audit codebase terbaru: `composer.json`, `package.json`, `routes/web.php`, migrations, models, controllers, Form Requests, Policies/Gates, middleware, storage configuration, settings, UI admin, public layout, navigasi, serta test terkait.
3. Verifikasi kembali route `/download`, route unduh `/download/{id}/unduh`, halaman Inertia `public/download`, serta seluruh referensi `download` pada kode, tes, navigasi, breadcrumb, permission, dan dokumentasi. Sebelumnya route tersebut terdaftar, dan endpoint unduh mengembalikan placeholder. Kondisi ini dapat berubah; jangan menganggapnya pasti masih sama.
4. Cari apakah model, tabel, migration, komponen upload, storage disk, atau permission untuk Dokumen sudah tersedia. Gunakan kembali fondasi yang sesuai; hindari tabel, route, komponen, atau pipeline duplikat.
5. Audit pengelolaan Media yang sudah ada. Pipeline Media saat ini diketahui berfokus pada gambar JPEG/PNG dan pemrosesan varian gambar. **Jangan menggunakan pipeline gambar untuk PDF atau berkas dokumen tanpa desain yang memang mendukungnya.**
6. Tentukan strategi penyimpanan private untuk dokumen dan endpoint unduh terkontrol. Dokumen draf/arsip tidak boleh dapat diakses langsung dengan menebak URL file.
7. Sajikan rencana implementasi berurutan beserta file yang akan dibuat/diubah, migration, route, model, policy, komponen UI, risiko, dan test sebelum coding.
8. Ikuti pola aktual repository. Jika dokumen ini bertentangan dengan codebase terbaru atau `docs/DECISIONS.md`, jelaskan konflik dan usulkan keputusan sebelum perubahan yang sulit dibalik.
9. Jangan mengerjakan Galeri, integrasi Kontak, redesign homepage, atau refactor yang tidak terkait.
10. Setelah implementasi, jalankan pemeriksaan yang relevan dan laporkan perintah serta hasilnya secara akurat. Jangan mengklaim test lulus jika tidak dijalankan.

## 3. Konteks teknis proyek

Stack proyek yang diketahui:

- Laravel 13 dan PHP 8.3+.
- Inertia.js 3 sebagai penghubung Laravel dan React.
- React 19, TypeScript, Tailwind CSS 4, dan komponen UI proyek.
- MySQL untuk persistence.
- Spatie Laravel Permission serta Policy/Gate Laravel untuk authorization.
- Pest untuk backend, Vitest untuk unit/component test, Playwright untuk E2E, serta lint/type/build scripts sesuai repository.

Prinsip implementasi:

- Laravel menjadi sumber kebenaran untuk data, validasi, authorization, query, status publikasi, dan proses unduh.
- Controller tetap tipis. Gunakan Form Request untuk validasi write request.
- Gunakan Policy/Gate/permission mengikuti pola yang sudah ada; jangan mengandalkan UI untuk keamanan.
- Jangan membuat REST API terpisah jika alur Inertia yang ada sudah sesuai.
- Gunakan named exports kecuali konvensi route/page aktual mengharuskan pola lain.
- Ikuti pola route name, pagination, filter query string, flash/toast, dialog konfirmasi, form, dan tabel dari modul admin lain.
- Teks UI dan validasi menggunakan Bahasa Indonesia.
- Nama tabel/kolom mengikuti konvensi snake_case berbahasa Inggris yang dipakai proyek.
- Jangan mengubah layout publik, navbar, footer, design system, atau modul lain kecuali perubahan kecil memang diperlukan untuk menambahkan tautan Dokumen.

### 3.1. Kondisi codebase yang sebelumnya teridentifikasi

Audit sebelumnya menemukan:

- Route publik `/download` menggunakan halaman Inertia `public/download`.
- Route `/download/{id}/unduh` masih mengembalikan berkas placeholder, bukan dokumen yang tersimpan.
- Fondasi `/admin/media` menerima gambar JPEG/PNG, dan pipeline gambar memproses gambar secara asynchronous.
- Pengaturan situs dan modul admin berada di dalam middleware admin yang telah tersedia.

Ini merupakan snapshot audit sebelumnya, bukan jaminan kondisi repository saat implementasi dimulai. Agent harus memeriksa ulang codebase terbaru.

### 3.2. Penamaan dan URL

- Nama fitur dan label navigasi admin/publik: **Dokumen**.
- URL daftar publik yang diinginkan: `/dokumen`.
- Nama halaman publik yang disarankan mengikuti konvensi proyek, misalnya `public/dokumen`.
- Gunakan route name yang konsisten, misalnya `documents.index` dan `documents.download`, setelah memeriksa konvensi aktual.
- Pertimbangkan redirect permanen dari `/download` ke `/dokumen` agar tautan lama tidak rusak. Periksa konflik route dan kebutuhan SEO terlebih dahulu.
- Route unduh yang disarankan berbentuk ID/identifier yang tidak mudah ambigu, misalnya `/dokumen/{document}/unduh`. Slug detail opsional dan tidak wajib untuk MVP.
- Jangan mengubah semua penggunaan kata `download` secara membabi buta. Istilah teknis untuk aksi mengunduh boleh tetap digunakan di nama fungsi/metode jika tepat; yang harus konsisten adalah nama fitur dan label pengguna.

---

## 4. Batas scope

### 4.1. Termasuk Phase 8B

- CRUD metadata dokumen.
- Upload berkas dokumen.
- Mengganti berkas dokumen dengan versi terbaru.
- Pengelolaan kategori dokumen.
- Status Draf, Terbit, dan Arsip.
- Pencarian dan filter dokumen di admin.
- Halaman daftar dokumen publik `/dokumen`.
- Filter kategori publik.
- Pencarian publik jika sesuai kapasitas MVP.
- Unduh berkas dengan pemeriksaan status dan otorisasi publikasi.
- Informasi format, ukuran, tanggal, dan versi dokumen jika tersedia.
- Pratinjau dokumen untuk admin jika format dan browser mendukungnya.
- Statistik jumlah unduhan jika dapat diimplementasikan dengan definisi yang jelas.
- Validasi, keamanan storage, pembersihan berkas, aksesibilitas, SEO dasar, dan pengujian.
- Redirect dari route lama `/download` jika diperlukan dan disetujui setelah audit.

### 4.2. Tidak termasuk Phase 8B

- Galeri, album foto, upload foto galeri, atau lightbox.
- Integrasi dan redesign halaman Kontak.
- Penyimpanan berkas publik tanpa pemeriksaan status publikasi.
- Pengelolaan dokumen rahasia internal atau dokumen yang hanya boleh dilihat oleh pengguna login, kecuali kebutuhan tersebut disetujui sebagai perubahan scope.
- Tanda tangan digital, alur persetujuan berjenjang, OCR, konversi format, editor PDF, atau versioning kompleks.
- Integrasi penyimpanan eksternal baru jika storage yang tersedia sudah memadai.
- Redesign homepage, footer, navbar, atau design system global.
- Pembuatan media library generik baru yang menduplikasi fondasi Media.

---

## 5. Aktor dan authorization

| Aktor | Hak akses |
| --- | --- |
| Pengunjung | Melihat metadata dokumen Terbit dan mengunduh berkas yang berstatus Terbit. Tidak boleh membaca dokumen Draf/Arsip melalui route, ID, atau URL file langsung. |
| Super Admin | Membuat, melihat, mengubah, mengganti berkas, menerbitkan, menarik publikasi, mengarsipkan, dan menghapus dokumen sesuai permission yang tersedia. Dapat mengelola kategori jika diizinkan. |
| User tidak berwenang | Tidak dapat mengakses route admin atau menjalankan mutasi Dokumen. Backend harus menolak akses meskipun request dibuat manual. |

Ketentuan keamanan:

- Semua route admin berada di dalam middleware admin yang sudah digunakan aplikasi.
- Terapkan Policy/Gate atau permission mengikuti pola modul lain.
- Jika permission granular digunakan, pertimbangkan nama seperti `documents.view`, `documents.create`, `documents.update`, `documents.delete`, `documents.publish`, dan permission kategori sesuai pola aktual. Jangan menambahkan permission tanpa memeriksa seeder/konfigurasi permission yang ada.
- Jangan mengekspos path storage, disk internal, atau nama path acak dalam response publik.
- Endpoint unduh harus mengambil record dokumen, memeriksa status publikasi, lalu menyajikan file dari private storage.
- Jangan menerima path berkas arbitrer dari request untuk proses unduh.
- Dokumen yang tidak ada atau tidak dapat diakses harus menghasilkan respons yang aman dan konsisten, umumnya 404 untuk route publik.
- Periksa rate limiting jika sesuai pola aplikasi, tetapi jangan menambah kompleksitas yang tidak dibutuhkan untuk MVP.

---

## 6. Model data dan relasi yang diharapkan

Ini rancangan domain, bukan instruksi membuat tabel tanpa audit. Periksa model/migration yang sudah ada dan sesuaikan nama field dengan konvensi repository.

### 6.1. Entitas Dokumen

Satu record Dokumen merepresentasikan satu dokumen yang ditampilkan kepada pengunjung dan dapat menunjuk ke satu berkas aktif.

| Field konseptual | Tipe yang disarankan | Wajib? | Aturan |
| --- | --- | :---: | --- |
| `id` | Primary key | Ya | Dibuat otomatis |
| `title` | String, maks. 180 karakter | Ya | Judul yang jelas dan tidak kosong |
| `slug` | String unik, nullable bila tidak digunakan | Opsional | Hanya diperlukan jika halaman detail dokumen memakai slug |
| `description` | Text nullable | Tidak | Teks biasa; output di-escape |
| `document_category_id` | Foreign key kategori | Ya untuk MVP | Harus mengarah ke kategori aktif/valid |
| `file_disk` | String sesuai konfigurasi storage | Ya | Disk tempat berkas disimpan; jangan diberikan kepada publik |
| `file_path` | String | Ya | Path internal yang dibuat sistem, bukan input bebas dari pengguna |
| `original_filename` | String | Ya | Nama asli yang telah ditangani dengan aman untuk tampilan/unduhan |
| `mime_type` | String | Ya | MIME hasil deteksi berkas |
| `file_size` | Big integer | Ya | Ukuran aktual dalam byte |
| `version_label` | String nullable | Tidak | Misalnya `1.0` atau `Revisi Oktober 2026` |
| `document_date` | Date nullable | Tidak | Tanggal dokumen, bukan tanggal upload |
| `expires_at` | Date nullable | Tidak | Masa berlaku jika relevan |
| `status` | Enum/string sesuai konvensi proyek | Ya | Minimal Draf, Terbit, Arsip |
| `is_featured` | Boolean, default false | Tidak | Menandai dokumen unggulan; hanya berlaku pada dokumen Terbit |
| `sort_order` | Integer nullable | Tidak | Urutan manual jika diperlukan |
| `published_at` | Datetime nullable | Tidak | Terisi sesuai pola publikasi proyek |
| `download_count` | Big integer, default 0 | Tidak | Counter sistem; tidak boleh dapat diubah lewat form biasa |
| `uploaded_by` | Foreign key user nullable/restrict sesuai pola | Ya bila audit trail mendukung | Pengguna yang membuat/unggah dokumen |
| `updated_by` | Foreign key user nullable | Tidak | Pengguna yang terakhir mengubah data |
| `created_at`, `updated_at` | Timestamp | Ya | Dikelola Laravel |
| `deleted_at` | Timestamp nullable | Sesuai pola proyek | Gunakan soft delete jika sesuai pola modul konten existing |

Pertimbangan desain:

- File aktif dapat disimpan sebagai field pada tabel `documents` untuk MVP.
- Jika kebutuhan audit versi berkas historis sudah disetujui, pertimbangkan tabel `document_versions`. Jangan menambahkannya otomatis jika kebutuhan MVP hanya mengganti file aktif.
- Jangan menyimpan binary file di database.
- Jangan menggunakan nama file asli sebagai path fisik.
- Jika ada model Media yang hanya mendukung gambar, jangan memaksakan file PDF masuk ke tabel Media gambar. Buat mekanisme berkas dokumen yang sesuai dan terpisah dari pipeline gambar.

### 6.2. Entitas Kategori Dokumen

Kategori memungkinkan admin mengelompokkan dokumen secara konsisten.

| Field konseptual | Tipe yang disarankan | Wajib? | Aturan |
| --- | --- | :---: | --- |
| `id` | Primary key | Ya | Dibuat otomatis |
| `name` | String, maks. 100 karakter | Ya | Nama kategori harus unik |
| `slug` | String unik | Jika digunakan | Identifier URL/filter |
| `description` | Text nullable | Tidak | Deskripsi kategori |
| `sort_order` | Integer, default sesuai konvensi | Tidak | Urutan kategori di UI |
| `is_active` | Boolean, default true | Ya | Kategori nonaktif tidak tersedia untuk dokumen baru |
| `created_at`, `updated_at` | Timestamp | Ya | Dikelola Laravel |

Kategori awal yang dapat dipertimbangkan setelah dikonfirmasi dengan paroki:

- Formulir.
- Administrasi Sakramen.
- Panduan Pelayanan.
- Pengumuman Resmi.
- Dokumen Paroki.

Jangan hardcode kategori di frontend jika admin perlu mengelolanya. Audit dahulu apakah master data existing memiliki pola yang dapat digunakan.

### 6.3. Constraint dan indeks

- Tambahkan indeks untuk status publikasi, kategori, tanggal publikasi, dan kolom pencarian sesuai query aktual.
- `slug` harus unik jika digunakan.
- `document_category_id` harus memiliki foreign key yang jelas.
- Status Terbit harus konsisten dengan `published_at` sesuai aturan domain yang dipilih.
- `download_count` tidak boleh negatif dan tidak boleh berasal dari input form admin.
- Saat kategori akan dihapus, cegah penghapusan jika masih dipakai atau minta kategori pengganti. Jangan menghapus dokumen yang terkait.
- Tetapkan perilaku penghapusan berkas secara eksplisit agar database dan storage tidak menyimpang.
- Jika menggunakan soft delete, tentukan apakah file tetap dipertahankan selama masa pemulihan.
- Gunakan transaksi database untuk perubahan metadata dan referensi file bila relevan, tetapi ingat operasi storage bukan transaksi database; rancang kompensasi/cleanup bila proses gagal.

---

## 7. Kebijakan berkas dan storage

Angka berikut merupakan rekomendasi awal untuk MVP, bukan konfigurasi yang telah diverifikasi pada repository. Agent harus memeriksa konfigurasi dan menyesuaikan batas secara eksplisit.

| Aspek | Rekomendasi awal | Catatan |
| --- | --- | --- |
| Format yang didukung | PDF sebagai format utama | Tambahkan DOC/DOCX atau format lain hanya jika ada kebutuhan yang disetujui |
| Ukuran maksimum | 10 MB per file sebagai default awal | Pastikan konsisten antara validasi Laravel, konfigurasi PHP/server, dan UI |
| Penyimpanan | Private disk / private storage | File tidak boleh tersedia lewat URL publik langsung |
| Validasi | Ukuran, MIME hasil deteksi, ekstensi yang diizinkan, dan konten bila memungkinkan | Jangan percaya nama file atau MIME dari browser saja |
| Nama fisik | UUID atau nama acak aman dengan ekstensi yang ditentukan server | Hindari path traversal dan nama executable dari input |
| Nama unduhan | Nama file yang dibersihkan, berasal dari metadata tersimpan | Cegah header injection dan karakter berbahaya |
| Response download | Attachment dengan MIME yang sesuai dan header keamanan yang tepat | Jangan menerima path bebas dari request |
| Penggantian file | Ganti file aktif dengan alur aman; pertahankan identitas dokumen | Jangan hapus file lama sebelum file baru berhasil disimpan dan referensi berhasil diperbarui |
| Cleanup | Hapus file yang tidak lagi direferensikan setelah perubahan berhasil | Hindari file yatim maupun menghapus file yang masih dipakai |
| Statistik | Definisikan kapan counter bertambah | Pilih satu definisi yang konsisten, misalnya saat respons download valid berhasil dimulai |
| Pratinjau | Opsional, terbatas pada format yang didukung browser | Jangan membuka file draf untuk publik |

### 7.1. Alur upload yang diharapkan

1. Admin memilih file dan mengisi metadata.
2. Server memvalidasi request serta jenis dan ukuran file.
3. File disimpan pada private storage dengan nama fisik yang aman.
4. Metadata dokumen disimpan atau diperbarui.
5. Jika proses database gagal, file baru yang belum direferensikan harus dibersihkan.
6. Jika mengganti file, file lama baru dibersihkan setelah perubahan berhasil dan dipastikan tidak digunakan oleh versi/record lain.
7. UI menampilkan pesan sukses atau error yang jelas.
8. Dokumen tidak otomatis terbit hanya karena upload berhasil; status publikasi harus mengikuti aksi yang dipilih admin.

### 7.2. Unduh berkas yang aman

Endpoint unduh harus:

1. Menerima identifier dokumen yang valid.
2. Memuat record dari database.
3. Memastikan dokumen berstatus Terbit untuk akses publik.
4. Memastikan file yang direferensikan memang ada pada disk yang diizinkan.
5. Menyajikan file dari path yang diambil dari record, bukan path dari query/body.
6. Menggunakan nama file unduhan yang aman.
7. Menangani file hilang dengan respons aman dan logging yang sesuai.
8. Memperbarui statistik unduhan hanya sesuai definisi yang disepakati.
9. Tidak membocorkan absolute path, disk internal, stack trace, atau metadata sensitif.

---

## 8. Fitur admin

### 8.1. Daftar dokumen

Tabel admin sebaiknya menampilkan kolom:

- Judul dokumen.
- Kategori.
- Format berkas.
- Ukuran.
- Versi.
- Tanggal dokumen atau tanggal pembaruan.
- Status publikasi.
- Jumlah unduhan, jika statistik diaktifkan.
- Aksi: lihat/edit, terbitkan/tarik publikasi, arsipkan, dan hapus sesuai izin.

Perilaku:

- Gunakan pagination server-side.
- Pencarian minimal berdasarkan judul.
- Filter kategori dan status.
- Filter tanggal hanya bila relevan dan didukung oleh field yang disepakati.
- Pertahankan filter/pencarian pada query string sesuai pola halaman admin lain.
- Sediakan empty state dengan CTA “Tambah Dokumen”.
- Sediakan feedback untuk loading, error, dan hasil kosong.
- Hindari dashboard statistik terpisah pada MVP.

### 8.2. Tambah dan edit dokumen

Field form:

1. Judul dokumen — wajib.
2. Kategori — wajib untuk MVP.
3. Deskripsi — opsional.
4. File — wajib saat membuat dokumen baru.
5. Versi — opsional.
6. Tanggal dokumen — opsional.
7. Berlaku sampai — opsional.
8. Status publikasi — Draf/Terbit sesuai authorization dan pola UI.
9. Unggulan — opsional jika diaktifkan.

Ketentuan UX:

- Label dan helper text menjelaskan maksud setiap field.
- Tampilkan format dan batas ukuran file sebelum upload.
- Tampilkan validasi server dekat field terkait.
- Saat edit, file lama tetap aktif jika admin tidak memilih file pengganti.
- Penggantian file harus jelas bagi admin dan meminta konfirmasi bila dampaknya signifikan.
- Jangan memaksa admin mengunggah ulang file saat hanya mengubah judul atau kategori.
- Tombol aksi menggunakan label yang jelas, misalnya “Simpan Draf”, “Terbitkan”, dan “Simpan Perubahan”.
- Jika dokumen sudah terbit, perubahan status dan penggantian file harus memiliki feedback yang jelas.

### 8.3. Pengelolaan kategori

Admin yang berwenang dapat:

- Melihat daftar kategori.
- Membuat kategori.
- Mengubah nama/deskripsi/urutan kategori.
- Mengaktifkan atau menonaktifkan kategori.
- Menghapus kategori hanya bila tidak digunakan atau setelah dokumen terkait dipindahkan ke kategori lain.

Untuk MVP, pengelolaan kategori boleh berada di subhalaman atau dialog sesuai pola master data existing. Jangan membangun UI kategori baru yang berbeda dari pola modul lain.

### 8.4. Penggantian dan penghapusan

- Penggantian berkas harus mempertahankan ID dokumen dan URL publik jika memungkinkan.
- Catat versi dan waktu pembaruan jika field tersebut tersedia.
- Jangan menghapus file lama sebelum file baru tersimpan dan record berhasil diperbarui.
- Penghapusan dokumen meminta konfirmasi.
- Dokumen yang sudah diarsipkan tidak tampil pada daftar publik.
- Jika soft delete digunakan, definisikan kapan file benar-benar dihapus.
- Hapus file fisik hanya jika tidak ada referensi lain yang sah.

---

## 9. Halaman publik

### 9.1. Daftar Dokumen — `/dokumen`

Tujuan: membantu pengunjung menemukan dokumen resmi tanpa harus mengetahui nama file penyimpanan atau struktur internal website.

Konten minimum:

- Judul halaman, misalnya “Dokumen Paroki”.
- Deskripsi singkat tentang jenis dokumen yang tersedia.
- Daftar dokumen Terbit.
- Judul dokumen.
- Kategori.
- Deskripsi singkat jika tersedia.
- Format dan ukuran berkas.
- Tanggal dokumen atau tanggal pembaruan jika tersedia.
- Tombol “Unduh Dokumen”.
- Pencarian dan filter kategori jika masuk implementasi MVP yang disetujui.
- Empty state jika belum ada dokumen terbit.

Perilaku:

- Query publik hanya mengambil dokumen Terbit.
- Jangan mengirim `file_path`, absolute path, nama disk internal, atau metadata storage ke Inertia props publik.
- Dokumen Draf/Arsip tidak boleh muncul pada hasil pencarian, kategori, jumlah total publik, atau endpoint unduh publik.
- Gunakan pagination bila jumlah dokumen dapat bertambah.
- Query pencarian harus di-escape/ditangani sebagai parameter query, bukan SQL mentah.
- Daftar harus responsif dan tetap mudah digunakan pada layar kecil.
- Jika dokumen memiliki tanggal berlaku, aturan penampilan dokumen kedaluwarsa harus konsisten dan ditentukan oleh kebijakan bisnis; jangan otomatis menyembunyikan tanpa persetujuan.

### 9.2. Unduh dokumen

- Tombol mengarah ke route unduh terkontrol, bukan URL storage publik.
- Backend memeriksa status publikasi setiap kali unduhan diminta.
- Gunakan response attachment dengan nama file yang aman.
- Jika dokumen tidak ditemukan, tidak terbit, atau file hilang, tampilkan perilaku error yang aman.
- Jangan menyediakan tombol unduh untuk dokumen yang belum terbit.
- Jika pratinjau publik tidak menjadi kebutuhan yang disetujui, jangan membuatnya sebagai fitur tambahan.
- Counter unduhan harus bertambah secara konsisten dan tidak dapat dimanipulasi melalui form biasa.

### 9.3. SEO dan aksesibilitas

- Gunakan satu heading utama yang deskriptif.
- Judul halaman dan metadata SEO mengikuti pola halaman publik lain.
- Tautan unduh memiliki label yang menjelaskan dokumen, bukan sekadar “Klik di sini”.
- Tombol dan filter dapat dioperasikan menggunakan keyboard.
- Fokus keyboard terlihat.
- Informasi format/ukuran tersedia sebagai teks, bukan hanya ikon.
- Gunakan kontras dan ukuran target sentuh sesuai design system.
- Jangan menambahkan structured data khusus tanpa kebutuhan yang jelas.

---

## 10. Daftar fitur dan prioritas

Prioritas: **P0** = kebutuhan inti MVP; **P1** = penyempurnaan yang disarankan jika kapasitas memungkinkan.

| ID | Fitur | Pengguna | Perilaku yang diharapkan | Prioritas |
| --- | --- | --- | --- | --- |
| DOC-01 | Daftar dokumen di admin | Admin | Melihat judul, kategori, format, ukuran, versi, status, dan tanggal pembaruan | P0 |
| DOC-02 | Pencarian dokumen admin | Admin | Mencari berdasarkan judul/nama dokumen | P1 |
| DOC-03 | Filter kategori dan status | Admin | Menyaring dokumen berdasarkan kategori dan status publikasi | P0 |
| DOC-04 | Tambah dokumen | Admin | Membuat data dokumen sekaligus mengunggah berkas | P0 |
| DOC-05 | Edit metadata | Admin | Mengubah judul, deskripsi, kategori, versi, dan tanggal dokumen | P0 |
| DOC-06 | Ganti berkas | Admin | Mengunggah revisi berkas dan mengganti file aktif dengan aman | P0 |
| DOC-07 | Hapus dokumen | Admin | Meminta konfirmasi sebelum menghapus dokumen | P0 |
| DOC-08 | Kelola kategori | Admin | Membuat, mengubah, mengurutkan, dan menonaktifkan kategori | P1 |
| DOC-09 | Draf dan terbitkan | Admin | Menyimpan dokumen tanpa langsung membuatnya tersedia untuk publik | P0 |
| DOC-10 | Arsipkan dokumen | Admin | Menandai dokumen yang sudah tidak aktif tetapi perlu dipertahankan | P1 |
| DOC-11 | Pratinjau dokumen | Admin | Membuka pratinjau jika format dan browser mendukung | P1 |
| DOC-12 | Validasi tipe dan ukuran file | Sistem | Menolak berkas yang melanggar kebijakan upload | P0 |
| DOC-13 | Deteksi metadata berkas | Sistem | Menentukan tipe MIME dan ukuran aktual | P0 |
| DOC-14 | Daftar dokumen publik | Pengunjung | Melihat semua dokumen Terbit secara terstruktur | P0 |
| DOC-15 | Filter kategori publik | Pengunjung | Menemukan dokumen berdasarkan kategori | P0 |
| DOC-16 | Pencarian publik | Pengunjung | Mencari berdasarkan judul dan deskripsi | P1 |
| DOC-17 | Unduh dokumen | Pengunjung | Mengunduh berkas melalui endpoint terkontrol | P0 |
| DOC-18 | Informasi format dan ukuran | Pengunjung | Mengetahui tipe dan ukuran file sebelum mengunduh | P0 |
| DOC-19 | Jumlah unduhan | Admin | Melihat jumlah unduhan setiap dokumen | P1 |
| DOC-20 | Dokumen unggulan | Admin/Pengunjung | Menampilkan dokumen penting di bagian yang mudah ditemukan | P1 |
| DOC-21 | Tampilan responsif | Pengunjung | Daftar dokumen nyaman di ponsel dan desktop | P0 |
| DOC-22 | Peringatan masa berlaku | Admin | Memberi tanda jika dokumen melewati tanggal berlaku | P1 |
| DOC-23 | Kontrol akses berkas | Sistem | Draf/arsip tidak dapat diakses melalui URL publik biasa | P0 |
| DOC-24 | Validasi status pada unduhan | Sistem | Memeriksa status Terbit setiap kali file diminta | P0 |
| DOC-25 | Lifecycle file | Sistem | Mencegah file yatim dan penghapusan file yang masih direferensikan | P0 |
| DOC-26 | Redirect route lama | Sistem | Menangani URL `/download` agar tautan lama tidak rusak | P1 |

P0/P1 di atas merupakan usulan prioritas implementasi untuk fase ini. Agent harus melaporkan bila PRD atau keputusan repository menetapkan prioritas berbeda.

---

## 11. Perubahan navigasi dan route lama

- Ganti label navigasi yang mengacu pada fitur Download menjadi **Dokumen**.
- Tambahkan/ubah tautan publik menjadi `/dokumen`.
- Tambahkan item sidebar admin Dokumen sesuai pola navigasi admin yang ada.
- Perbarui judul halaman, breadcrumb, empty state, CTA, dan teks UI yang memang merujuk ke nama fitur.
- Periksa referensi route name lama di frontend, test, menu, dan dokumentasi.
- Jika route lama `/download` pernah dipublikasikan, pertimbangkan redirect permanen ke `/dokumen`.
- Jangan membuat redirect yang loop atau mengubah route unduh lama tanpa mengaudit link yang sudah digunakan.
- Jika ada route name lama yang dipakai oleh kode internal, migrasikan secara terarah dan jalankan test regresi.
- Jangan mengubah istilah `download` pada API/library/variabel yang berarti tindakan teknis mengunduh apabila tidak perlu.

---

## 12. Error handling dan UX state

Setiap halaman/form harus menangani state berikut:

- **Loading:** indikator selama data atau operasi diproses.
- **Empty state:** penjelasan dan CTA yang relevan ketika belum ada dokumen/kategori.
- **Validation error:** pesan dekat field yang bermasalah.
- **Upload error:** alasan kegagalan yang aman dan dapat dimengerti.
- **Storage error:** pesan generik untuk pengguna; detail teknis hanya di log server.
- **Not found:** dokumen tidak ditemukan atau tidak dapat diakses.
- **Permission denied:** akses admin ditolak secara konsisten.
- **Success:** toast/flash mengikuti pola proyek.
- **Confirm destructive action:** dialog konfirmasi untuk menghapus dokumen, kategori, atau mengganti file bila relevan.
- **Network/retry:** jika pola frontend mendukungnya, berikan cara mencoba ulang tanpa menggandakan dokumen.

Jangan menampilkan stack trace, absolute path, kredensial, atau detail internal storage ke pengguna.

---

## 13. Testing

Agent harus menambah atau menyesuaikan test mengikuti struktur repository.

### 13.1. Backend / Pest

Minimal uji:

1. Admin berwenang dapat membuka daftar dokumen.
2. Pengunjung tidak dapat mengakses route admin.
3. Admin dapat membuat dokumen dengan file yang valid.
4. Validasi menolak file yang tidak diizinkan.
5. Validasi menolak file yang melebihi batas ukuran.
6. Metadata MIME/ukuran disimpan berdasarkan data aktual yang dideteksi server.
7. Admin dapat memperbarui metadata tanpa mengunggah ulang file.
8. Penggantian file memperbarui referensi dan tidak menghapus file lama sebelum operasi sukses.
9. Dokumen Draf tidak muncul pada daftar publik.
10. Dokumen Arsip tidak muncul pada daftar publik.
11. Hanya dokumen Terbit yang dapat diunduh oleh pengunjung.
12. Route unduh tidak menerima path arbitrer dari request.
13. File hilang ditangani secara aman.
14. Nama file unduhan dibersihkan dan tidak menimbulkan header injection.
15. Jumlah unduhan berubah sesuai definisi yang dipilih.
16. Kategori yang digunakan tidak dapat dihapus tanpa aturan migrasi/pemindahan yang benar.
17. Penghapusan dokumen menangani file storage sesuai lifecycle yang dipilih.
18. Redirect `/download` ke `/dokumen` bekerja jika diimplementasikan.
19. Pengunjung tidak menerima field internal seperti `file_path` atau `file_disk` pada props halaman.
20. Status publikasi dan authorization tetap diperiksa pada endpoint unduh meskipun UI dimanipulasi.

Gunakan fake storage pada test bila sesuai, dan pastikan test memverifikasi perilaku, bukan hanya status HTTP.

### 13.2. Frontend / Vitest

Minimal uji:

- Form dokumen menampilkan field wajib dan pesan validasi.
- File pengganti bersifat opsional pada mode edit.
- Filter/pencarian menampilkan state sesuai data.
- Status Draf/Terbit/Arsip memiliki label yang jelas.
- Dialog konfirmasi hapus dapat dibuka dan dibatalkan.
- Halaman publik menampilkan metadata dokumen yang tersedia.
- Tombol unduh memiliki label yang bermakna.
- Empty state dan error state ditampilkan dengan benar.

### 13.3. E2E / Playwright

Alur yang disarankan:

1. Admin masuk dan membuka menu Dokumen.
2. Admin membuat kategori bila fitur kategori masuk implementasi.
3. Admin membuat dokumen berstatus Draf.
4. Pastikan dokumen draf tidak tampil di publik dan tidak dapat diunduh.
5. Admin menerbitkan dokumen.
6. Pengunjung melihat dokumen dan mengunduh berkas.
7. Admin mengedit metadata.
8. Admin mengganti berkas.
9. Admin mengarsipkan dokumen dan memastikan dokumen tidak lagi dapat diunduh publik.
10. Admin menghapus dokumen dan memastikan perilaku storage sesuai desain.

### 13.4. Regression checks

Jalankan scripts yang benar-benar tersedia pada `package.json` dan `composer.json`, misalnya lint/check, type-check, unit tests, Pest, build, dan E2E jika environment memungkinkan. Periksa scripts aktual sebelum menjalankan. Laporkan hasil setiap perintah, termasuk kegagalan dan test yang tidak dijalankan.

---

## 14. Acceptance criteria

Phase 8B dianggap selesai jika:

- [ ] Nama fitur dan navigasi menggunakan **Dokumen**.
- [ ] Halaman publik utama menggunakan `/dokumen`.
- [ ] Route admin dilindungi middleware dan authorization yang sesuai.
- [ ] Admin dapat membuat, melihat, mengubah, dan menghapus metadata dokumen.
- [ ] Admin dapat mengunggah file yang valid dan mengganti file dengan aman.
- [ ] Kategori dokumen berfungsi sesuai scope yang disepakati.
- [ ] Dokumen memiliki status Draf/Terbit; Arsip tersedia bila masuk scope yang disetujui.
- [ ] Hanya dokumen Terbit tampil pada halaman publik.
- [ ] Endpoint unduh memeriksa status publikasi dan menggunakan private storage.
- [ ] File draf/arsip tidak dapat diakses melalui URL langsung atau endpoint unduh publik.
- [ ] Validasi tipe dan ukuran berkas berjalan di server.
- [ ] Metadata internal storage tidak bocor ke props publik.
- [ ] Pencarian/filter berjalan sesuai scope MVP yang disepakati.
- [ ] UI menyediakan loading, empty, validation, error, dan success state.
- [ ] Tampilan responsif dan dapat digunakan dengan keyboard.
- [ ] Tautan lama `/download` ditangani jika diperlukan.
- [ ] Test backend, frontend, dan E2E yang relevan ditambahkan/diperbarui.
- [ ] Lint/type/build dan test relevan dijalankan, hasil dilaporkan secara jujur.
- [ ] Tidak ada perubahan tidak terkait pada Galeri atau Kontak.

---

## 15. Urutan implementasi yang disarankan

Agent harus mengajukan rencana rinci untuk disetujui sebelum coding. Urutan konseptual yang disarankan:

### 8B.0 — Audit dan rencana

- Audit arsitektur, pola modul admin, status route lama, storage, permission, settings, dan test.
- Identifikasi komponen yang dapat digunakan kembali.
- Putuskan format file, batas ukuran, disk storage, kebijakan versi, kategori, dan redirect lama.
- Sajikan rencana file-by-file dan test plan.

### 8B.1 — Domain dan persistence

- Buat/ubah migration dokumen dan kategori berdasarkan audit.
- Implementasikan model, relasi, casts, scope status publik, dan aturan slug bila dibutuhkan.
- Tetapkan constraint dan indeks.

### 8B.2 — Upload, replace, dan storage aman

- Implementasikan Form Request dan alur penyimpanan private.
- Implementasikan penggantian file dengan cleanup aman.
- Tambahkan validasi tipe, ukuran, nama file, dan penanganan error.

### 8B.3 — Admin CRUD

- Daftar, pencarian/filter, create, edit, publish/unpublish, archive, dan delete sesuai scope.
- Integrasikan menu admin dan komponen UI yang sudah tersedia.
- Tambahkan pengelolaan kategori jika masuk MVP.

### 8B.4 — Halaman publik dan unduh

- Implementasikan `/dokumen`.
- Query hanya mengambil dokumen Terbit.
- Implementasikan route unduh terkontrol.
- Tangani route lama `/download` bila disetujui.

### 8B.5 — Hardening dan pengujian

- Uji authorization, status publikasi, storage, upload, replace, delete, dan unduhan.
- Uji halaman publik, navigasi, aksesibilitas, dan responsivitas.
- Jalankan lint/type-check/unit/Pest/build/E2E yang tersedia.
- Perbarui dokumentasi dan roadmap sesuai konvensi proyek.

---

## 16. Definition of Done

Pekerjaan bukan hanya selesai ketika halaman terlihat. Modul dinyatakan selesai jika:

1. Fitur memenuhi acceptance criteria.
2. Tidak ada endpoint unduh yang mengirim placeholder atau melewati pemeriksaan status publikasi.
3. Tidak ada akses publik ke file draf/arsip melalui storage langsung.
4. Operasi file dan database memiliki penanganan kegagalan yang masuk akal.
5. Tidak ada file yang dihapus ketika masih direferensikan.
6. Permission diuji pada backend, bukan hanya UI.
7. Test relevan lulus atau kegagalan yang tersisa dijelaskan secara eksplisit.
8. Tidak ada regression pada modul yang sudah selesai.
9. Dokumentasi dan roadmap diperbarui bila memang menjadi pola proyek.
10. Ringkasan akhir menyebutkan file yang diubah, migration/route baru, keputusan teknis, test yang dijalankan, hasil, dan pekerjaan tersisa.

---

## 17. Prompt awal untuk AI agent

Gunakan instruksi berikut untuk memulai pekerjaan:

> Baca `phase-8b-documents.md` secara menyeluruh, lalu audit codebase terbaru dan dokumentasi arsitektur/keputusan proyek. Fokus hanya pada Phase 8B — Modul Dokumen. Jangan langsung coding. Periksa implementasi route `/download`, endpoint unduh placeholder, pola CRUD admin, permission, storage, dan pipeline Media yang ada. Sajikan rencana implementasi file-by-file beserta migration, route, model, authorization, UI, strategi penyimpanan private, penanganan route lama, dan test yang akan dibuat. Tandai asumsi yang belum terverifikasi dan keputusan yang membutuhkan persetujuan. Tunggu persetujuan pengguna/orchestrator sebelum mengubah kode. Jangan mengerjakan Phase 8A Galeri atau Phase 8C Kontak.

---

**Catatan final:** dokumen ini adalah spesifikasi implementasi dan sumber keputusan awal untuk Phase 8B. Detail database, nama route, permission, batas upload, serta kebijakan storage harus diverifikasi terhadap codebase terkini sebelum coding; jangan menganggap rancangan konseptual sebagai struktur yang sudah ada.
