# PHASE 8A — Modul Galeri

> **Proyek:** Website Gereja Paroki HSPMTB Putussibau  
> **Fase:** 8A — Galleries  
> **Status dokumen:** Spesifikasi implementasi untuk AI agent  
> **Scope:** Modul Galeri saja. Modul Dokumen (Phase 8B) dan integrasi Kontak (Phase 8C) tidak termasuk pekerjaan fase ini.

---

## 1. Tujuan

Membangun modul Galeri yang memungkinkan Super Admin mengelola album foto kegiatan paroki melalui CMS dan memungkinkan pengunjung melihat album serta foto yang telah diterbitkan.

Galeri harus terasa mudah digunakan oleh admin non-teknis, responsif pada ponsel dan desktop, aksesibel, aman, dan konsisten dengan arsitektur serta design system proyek yang sudah ada.

## 2. Instruksi utama untuk AI agent

Sebelum menulis kode, lakukan audit codebase terkini. Repository dan branch dapat berubah setelah dokumen ini dibuat.

1. Baca `ARCHITECTURE.md`, `DESIGN.md`, `PRD.md`, `docs/DECISIONS.md`, serta dokumentasi/roadmap fase jika tersedia.
2. Periksa `composer.json`, `package.json`, `routes/web.php`, model, migrations, Form Requests, Policies, controllers, jobs, komponen upload/media, halaman admin, layout publik, navigasi, dan test terkait.
3. Periksa struktur media yang sudah ada terlebih dahulu. Jangan membuat pipeline upload gambar kedua apabila pipeline Media yang tersedia dapat digunakan atau diperluas dengan aman.
4. Identifikasi apakah model/tabel/route Galeri sudah ada sebagian. Gunakan ulang atau migrasikan secara terarah; jangan membuat duplikasi model, route, atau tabel.
5. Susun rencana implementasi berurutan beserta file yang akan dibuat/diubah, migration yang diperlukan, dependensi, dan test sebelum coding. Jangan mulai coding sebelum rencana disajikan dan disetujui oleh pengguna/orchestrator.
6. Ikuti pola kode aktual repository. Bila dokumen ini bertentangan dengan implementasi terbaru atau keputusan di `docs/DECISIONS.md`, jelaskan konflik dan usulkan keputusan sebelum membuat perubahan yang sulit dibalik.
7. Jangan mengerjakan Dokumen/Download, halaman Kontak, redesign homepage, atau refactor tidak terkait. Jangan mengubah modul yang sudah selesai kecuali diperlukan langsung untuk integrasi Galeri.
8. Setelah implementasi, jalankan pemeriksaan yang relevan dan laporkan perintah serta hasilnya secara akurat. Jangan mengklaim test lulus jika tidak dijalankan.

## 3. Konteks teknis proyek

Stack proyek yang diketahui:

- Laravel 13 dan PHP 8.3+.
- Inertia.js 3 sebagai penghubung Laravel dan React; tidak perlu membuat REST API terpisah untuk halaman Inertia.
- React 19, TypeScript, Tailwind CSS 4, dan komponen UI yang digunakan proyek.
- MySQL untuk persistence.
- Spatie Laravel Permission dan Policy/Gate Laravel untuk authorization.
- Pest untuk backend, Vitest untuk unit/component test, Playwright untuk E2E, serta pemeriksaan lint/type/build sesuai scripts repository.

Prinsip arsitektur wajib:

- Laravel adalah sumber kebenaran untuk data, validasi, authorization, query, dan business logic.
- Controller tetap tipis; gunakan Form Request untuk validasi write request.
- Gunakan Action hanya untuk operasi multi-langkah, transaksi, side effect, atau proses yang memang kompleks. CRUD sederhana tidak perlu abstraksi berlebihan.
- React fokus pada presentation dan interaksi; server state tetap dikelola melalui Laravel/Inertia.
- Gunakan named exports kecuali route/page convention proyek mengharuskan pola lain.
- Ikuti pola penamaan, struktur direktori, route name, flash/toast, pagination, filter URL, dan komponen form yang sudah dipakai modul admin lain.
- Jangan mengganti atau membangun ulang `PublicLayout`, `ParishNavbar`, `ParishFooter`, design system, maupun komponen shell global.
- Teks UI dan validasi ditulis dalam Bahasa Indonesia; nama tabel/kolom mengikuti konvensi snake_case berbahasa Inggris yang telah digunakan proyek.

### 3.1 Fondasi media yang sudah teridentifikasi

Audit sebelumnya menemukan endpoint admin `/admin/media`, model Media, job pemrosesan gambar, status pemrosesan, serta varian gambar. Endpoint media saat ini dirancang untuk gambar JPEG/PNG dan memproses gambar secara asynchronous. Detail aktual wajib diverifikasi lagi pada codebase terbaru.

Ketentuan integrasi:

- Gunakan pipeline media yang ada jika cocok.
- Jangan menyimpan binary gambar langsung di tabel album/foto.
- Simpan referensi ke record Media menggunakan pola relasi yang telah tersedia.
- Jangan menganggap upload berhasil diproses hanya karena request upload sukses; periksa status pemrosesan sebelum foto dipakai sebagai sampul atau ditampilkan publik.
- Jangan memperluas endpoint media untuk semua jenis file; dukungan berkas dokumen adalah scope Phase 8B.
- Periksa apakah media yang sudah dipakai konten lain dapat dihapus. Hindari penghapusan file bersama yang masih direferensikan.

### 3.2 Route publik yang sudah teridentifikasi

Route `/galeri` dan `/galeri/{slug}` sudah terdaftar sebagai route Inertia, tetapi sebelumnya masih berupa shell halaman. Route dan page aktual harus diperiksa ulang. Pertahankan URL publik tersebut jika tidak ada alasan teknis kuat untuk mengubahnya.

Route admin CRUD Galeri belum terlihat dalam pemeriksaan `routes/web.php` sebelumnya. Verifikasi lagi sebelum menambahkannya.

---

## 4. Batas scope

### 4.1 Termasuk Phase 8A

- Pengelolaan album galeri oleh Super Admin.
- Pengelolaan foto yang menjadi bagian dari album.
- Upload beberapa foto sekaligus dengan feedback status yang jelas.
- Pemilihan foto sampul album.
- Pengurutan foto di dalam album.
- Status Draf dan Terbit; status Arsip dapat ditambahkan jika pola status proyek mendukungnya tanpa memperumit MVP.
- Daftar galeri publik dan halaman detail album.
- Tampilan foto dalam grid dan lightbox yang aksesibel.
- Pagination atau strategi pemuatan bertahap untuk daftar album/foto jika diperlukan.
- Validasi, authorization, keamanan file, optimasi gambar, SEO dasar, dan pengujian modul.
- Pemilihan album unggulan untuk beranda hanya jika slot/kontrak konten beranda yang sudah ada mendukungnya tanpa redesign beranda. Jika tidak, catat sebagai follow-up, jangan memperluas scope.

### 4.2 Tidak termasuk Phase 8A

- Modul Dokumen, PDF, file download, kategori dokumen, dan route `/dokumen`.
- Integrasi atau redesign halaman Kontak.
- Video, streaming, embed YouTube, atau jenis media non-gambar.
- Komentar, like, akun publik, upload oleh pengunjung, dan approval workflow.
- Integrasi sosial otomatis atau social sharing kompleks.
- Redesign homepage, footer, navbar, atau design system global.
- Pembuatan media library generik baru jika fungsi serupa sudah tersedia.
- Pengelolaan role/permission UI baru. Ikuti otorisasi yang telah dipakai aplikasi.

---

## 5. Peran dan authorization

| Aktor                | Hak akses                                                                                                                                                         |
| -------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Pengunjung           | Melihat album yang berstatus Terbit dan foto yang terkait dengannya. Tidak boleh membaca draf melalui route, query parameter, ID, atau URL langsung.              |
| Super Admin          | Membuat, membaca, mengubah, menerbitkan, mengarsipkan (jika tersedia), dan menghapus album; menambah, mengurutkan, memilih sampul, serta melepas foto dari album. |
| User tidak berwenang | Tidak dapat mengakses route admin atau menjalankan mutasi Galeri. Backend harus menolak akses walaupun UI dimanipulasi.                                           |

Ketentuan:

- Semua route admin Galeri berada di dalam middleware admin yang sudah digunakan aplikasi, bukan middleware buatan baru.
- Terapkan Policy/Gate atau permission sesuai pola modul existing. Jangan mengandalkan penyembunyian tombol di frontend.
- Jika permission granular digunakan, ikuti pola penamaan permission modul lain (misalnya `galleries.view`, `galleries.create`, `galleries.update`, `galleries.delete`, `galleries.publish`) hanya setelah memeriksa sistem permission yang aktual.
- Pastikan route publik hanya mengambil album terbit dan tidak membocorkan metadata album draf.

---

## 6. Model data dan relasi yang diharapkan

Ini adalah rancangan domain, bukan instruksi untuk langsung membuat tabel tanpa audit. Periksa migration dan model existing terlebih dahulu. Sesuaikan nama tabel/kolom dengan konvensi proyek.

### 6.1 Entitas Album

Satu album merepresentasikan satu kegiatan atau kumpulan foto yang saling berkaitan.

| Field konseptual           | Tipe yang disarankan                                  | Aturan                                                                                         |
| -------------------------- | ----------------------------------------------------- | ---------------------------------------------------------------------------------------------- |
| `id`                       | Primary key                                           | Dibuat otomatis                                                                                |
| `title`                    | String, maksimum 150 karakter                         | Wajib                                                                                          |
| `slug`                     | String unik                                           | Dibuat dari judul; collision ditangani dengan suffix yang deterministik                        |
| `description`              | Text nullable                                         | Opsional; output harus di-escape jika bukan rich text tersanitasi                              |
| `event_date`               | Date nullable                                         | Tanggal kegiatan, bukan tanggal upload                                                         |
| `location`                 | String nullable                                       | Opsional; tidak perlu membuat relasi lokasi baru kecuali sudah menjadi pola domain yang sesuai |
| `status`                   | Enum/string sesuai konvensi status proyek             | Minimal Draf dan Terbit                                                                        |
| `cover_media_id`           | Foreign key/referensi media nullable                  | Hanya boleh menunjuk gambar yang valid dan selesai diproses                                    |
| `is_featured`              | Boolean, default false                                | Opsional; gunakan hanya bila integrasi beranda tersedia dan disetujui                          |
| `sort_order`               | Integer nullable                                      | Gunakan bila dibutuhkan untuk urutan album manual                                              |
| `published_at`             | Datetime nullable                                     | Terisi saat diterbitkan sesuai pola timestamp publikasi proyek                                 |
| `created_by`               | Foreign key user nullable/restrict sesuai pola proyek | Audit pembuat album                                                                            |
| `updated_by`               | Foreign key user nullable sesuai pola proyek          | Audit pengubah terakhir bila konvensi proyek mendukungnya                                      |
| `created_at`, `updated_at` | Timestamp                                             | Wajib sesuai konvensi proyek                                                                   |
| `deleted_at`               | Timestamp nullable                                    | Gunakan soft delete jika pola konten utama proyek mensyaratkannya                              |

### 6.2 Entitas Foto Album

Satu album memiliki banyak item foto. Jangan menganggap satu record Media sama dengan satu foto album; foto yang sama mungkin direferensikan atau dipakai ulang sesuai aturan sistem media.

| Field konseptual           | Tipe yang disarankan | Aturan                                                                                       |
| -------------------------- | -------------------- | -------------------------------------------------------------------------------------------- |
| `id`                       | Primary key          | Dibuat otomatis                                                                              |
| `gallery_id`               | Foreign key album    | Wajib; aturan delete eksplisit                                                               |
| `media_id`                 | Foreign key Media    | Wajib; harus merujuk media gambar yang valid dan siap                                        |
| `caption`                  | String/text nullable | Keterangan foto opsional                                                                     |
| `alt_text`                 | String nullable      | Deskripsi aksesibilitas; sebaiknya dapat diisi admin dan tidak diisi dengan keyword stuffing |
| `sort_order`               | Integer              | Wajib untuk urutan deterministik dalam album                                                 |
| `created_at`, `updated_at` | Timestamp            | Wajib sesuai konvensi proyek                                                                 |
| `deleted_at`               | Timestamp nullable   | Gunakan hanya jika sesuai pola soft delete dan kebutuhan restore                             |

### 6.3 Kategori album

Kategori album **bukan syarat MVP** kecuali PRD, master data, atau kebutuhan pengguna menegaskan bahwa kategori harus dikelola. Jangan menambah tabel kategori hanya untuk mengisi scope. Jika kategori diperlukan:

- Audit master data yang ada sebelum membuat entitas baru.
- Kategori contoh dapat berupa Liturgi, Sakramen, Komunitas, dan Kegiatan Paroki, tetapi daftar final perlu divalidasi dengan pengurus.
- Pastikan penghapusan kategori tidak merusak album yang sudah ada.

### 6.4 Constraint dan indeks

- `slug` album unik.
- Indeks yang mendukung query publik, misalnya gabungan status dan tanggal publikasi, setelah disesuaikan dengan query aktual.
- Indeks pada relasi album-foto dan pengurutan foto.
- Cegah foto yang sama ditambahkan dua kali ke album yang sama bila tidak ada use case duplikasi yang disetujui (unique `gallery_id + media_id` dapat dipertimbangkan).
- Tetapkan foreign key dan `ON DELETE` secara eksplisit. Jangan memakai cascade yang dapat menghapus Media yang juga dipakai modul lain.
- Operasi perubahan sampul dan pengurutan harus menjaga konsistensi relasi dan nilai urutan.

---

## 7. Fitur Admin

### 7.1 Daftar album

Halaman daftar album admin menampilkan:

- Judul album dan thumbnail sampul.
- Tanggal kegiatan.
- Jumlah foto yang terhubung.
- Status publikasi.
- Waktu pembaruan terakhir.
- Aksi yang tersedia: buka/edit, kelola foto, terbitkan/tarik publikasi, dan hapus sesuai status serta authorization.

Perilaku:

- Gunakan pagination server-side untuk data yang berpotensi bertambah.
- Sediakan pencarian judul album.
- Filter status wajib; filter tanggal/kategori hanya jika datanya memang tersedia.
- Filter dan pencarian yang ingin dapat dibagikan/disimpan harus tercermin di query string, mengikuti pola halaman admin lain.
- Tampilkan empty state dengan CTA “Buat Album Pertama”.
- Tampilkan loading, error, dan state kosong secara jelas.
- Jangan membuat dashboard statistik terpisah untuk MVP.

### 7.2 Tambah dan edit album

Field formulir:

1. Judul album (wajib).
2. Deskripsi (opsional).
3. Tanggal kegiatan (opsional).
4. Lokasi (opsional).
5. Foto sampul (dipilih dari foto album atau melalui picker media yang sudah ada).
6. Status publikasi atau aksi simpan draf/terbitkan sesuai pola UI existing.
7. Album unggulan hanya jika integrasi beranda memang masuk scope yang disetujui.

Ketentuan UX:

- Form dikelompokkan dengan label dan deskripsi yang mudah dipahami.
- Validasi server ditampilkan dekat field yang bermasalah.
- Judul menghasilkan slug otomatis; perubahan judul tidak boleh diam-diam merusak URL yang telah dipublikasikan. Ikuti kebijakan slug existing, dan bila perlu pertahankan slug setelah album diterbitkan.
- Jika album memiliki foto, perubahan data album tidak boleh melepaskan foto yang sudah ada.
- Hapus album harus memakai dialog konfirmasi dan menjelaskan dampaknya terhadap foto di album.

### 7.3 Mengelola foto album

Admin harus dapat:

- Mengunggah beberapa foto sekaligus ke album.
- Melihat thumbnail setiap file yang dipilih sebelum/selama upload jika komponen upload yang ada mendukungnya.
- Melihat progres dan status tiap file: menunggu, mengunggah, memproses, siap, gagal.
- Mengulang upload/pemrosesan yang gagal tanpa mengulang semua file yang berhasil.
- Menghapus/mengeluarkan foto dari album dengan konfirmasi.
- Mengubah urutan foto.
- Memilih foto sampul dari foto yang ada dalam album.
- Mengisi/mengubah caption dan alt text.

Ketentuan:

- Jangan menandai foto siap digunakan sampai job pemrosesan Media sukses.
- Jika job gagal, tampilkan pesan yang dapat dimengerti dan jangan biarkan file gagal menjadi sampul atau foto publik.
- Perubahan urutan harus disimpan di backend dan tetap konsisten setelah reload.
- Jika foto dilepas dari album, hapus relasi item album; jangan otomatis menghapus Media fisik apabila masih digunakan di tempat lain.
- Jika album dihapus, pastikan kebijakan penghapusan foto dan Media mengikuti referensi serta lifecycle Media yang sudah ada.

### 7.4 Penghapusan dan lifecycle

- Gunakan soft delete untuk album bila itu merupakan konvensi konten utama proyek.
- Berikan konfirmasi sebelum hapus.
- Jangan menghapus file Media yang masih direferensikan oleh album lain atau modul lain.
- Jika album yang terbit dihapus/diarsipkan, URL publik tidak boleh menampilkan konten lagi; tangani 404/redirect secara konsisten dengan pola aplikasi.
- Pastikan sampul tidak menunjuk item foto yang sudah dilepas atau Media yang gagal.

---

## 8. Halaman publik

### 8.1 Daftar Galeri — `/galeri`

Tujuan: membantu pengunjung menemukan dokumentasi kegiatan paroki dengan cepat.

Konten minimum:

- Judul halaman, misalnya “Galeri Kegiatan”.
- Deskripsi pengantar singkat bila sesuai konten publik yang sudah ada.
- Grid album terbit yang menampilkan foto sampul, judul, tanggal kegiatan (jika tersedia), dan jumlah foto.
- Pagination atau pemuatan bertahap jika jumlah album bertambah.
- Empty state yang ramah bila belum ada album terbit.
- Link ke halaman detail tiap album.

Perilaku:

- Hanya album berstatus Terbit yang muncul.
- Album draf, arsip, atau soft-deleted tidak boleh muncul pada daftar, hasil pencarian, atau response props.
- Urutan default harus deterministik; rekomendasi terbaru lebih dahulu berdasarkan tanggal kegiatan atau tanggal publikasi. Pilih satu aturan yang konsisten dan dokumentasikan dalam implementasi.
- Query publik harus eager-load data yang ditampilkan dan menghindari N+1.
- Gunakan varian gambar yang sesuai, bukan memuat file original beresolusi penuh untuk semua thumbnail.

### 8.2 Detail album — `/galeri/{slug}`

Konten minimum:

- Breadcrumb atau tautan kembali ke daftar galeri sesuai pola halaman publik.
- Judul album.
- Deskripsi bila tersedia.
- Tanggal dan lokasi kegiatan bila tersedia.
- Grid foto album dalam urutan tersimpan.
- Lightbox untuk memperbesar foto.
- Tombol tutup dan navigasi foto yang aksesibel.

Perilaku:

- Gunakan route model binding atau query slug sesuai pola aplikasi; jangan mengambil album hanya berdasarkan ID yang dapat menampilkan draf.
- Album yang tidak ditemukan, tidak terbit, diarsipkan, atau dihapus harus memberikan 404 atau respons yang sesuai kebijakan aplikasi tanpa membocorkan status privatnya.
- Lightbox mendukung tombol Escape untuk menutup, tombol panah untuk navigasi bila sesuai, kontrol yang dapat diakses keyboard, dan fokus yang dikelola dengan benar.
- Pada layar kecil, kontrol harus mudah digunakan dengan sentuhan.
- Jangan menampilkan foto yang status pemrosesan medianya belum siap.

### 8.3 SEO dan metadata

- Gunakan pola SEO yang telah dipakai halaman publik lain.
- Judul halaman detail berasal dari judul album.
- Deskripsi meta berasal dari deskripsi album yang tersedia, dengan fallback yang aman.
- URL slug harus stabil dan konsisten.
- Foto memiliki alt text; jangan membuat alt text palsu yang mengklaim detail yang tidak diketahui.
- Canonical/OG metadata mengikuti helper atau layout SEO yang sudah ada, jangan membuat implementasi SEO kedua.

---

## 9. Design dan UX

Ikuti `DESIGN.md` dan komponen yang sudah tersedia. Galeri adalah fitur photography-first: foto menjadi fokus, sementara UI chrome tetap tenang.

- Gunakan warna dan token HSPMTB yang sudah ditentukan dalam design system; jangan hard-code palet baru.
- Gunakan komponen UI, tombol, dialog, badge, form, tabel, toast, dan skeleton yang sudah ada.
- Admin: utamakan keterbacaan, tindakan yang jelas, status upload yang eksplisit, dan pencegahan tindakan destruktif.
- Publik: grid foto responsif dengan rasio thumbnail yang konsisten, namun jangan memotong foto penting secara berlebihan.
- Gunakan `object-fit` dan rasio thumbnail sesuai konteks; foto besar di lightbox harus mempertahankan rasio aslinya.
- Sediakan focus state, hover state, disabled state, loading state, error state, dan empty state.
- Jangan memakai warna saja untuk membedakan Draf/Terbit; sertakan label teks.
- Jangan mengubah layout global atau komponen navigasi yang tidak terkait.
- Pastikan seluruh teks UI memakai Bahasa Indonesia dan terminologi konsisten: “Galeri”, “Album”, “Foto”, “Sampul”, “Terbitkan”, “Simpan Draf”.

---

## 10. Validasi upload, keamanan, dan privasi

Nilai batas upload harus diselaraskan dengan `config/media.php`, Form Request, komponen upload, server, dan storage yang sudah ada. Jangan menetapkan limit berbeda pada frontend dan backend.

Ketentuan minimum:

- Terima hanya format gambar yang memang didukung pipeline media; repository sebelumnya menunjukkan JPEG dan PNG. Format lain seperti WebP hanya boleh diaktifkan jika validasi, decoder, job, varian, dan test sudah mendukungnya.
- Validasi MIME berdasarkan isi berkas di server, bukan hanya nama file atau `Content-Type` browser.
- Batasi ukuran file, jumlah file per batch, dan dimensi gambar sesuai kemampuan server; dokumentasikan nilai yang dipilih.
- Simpan nama file internal yang aman dan tidak menggunakan nama upload sebagai path.
- Jangan menerima SVG atau format aktif lain kecuali ada sanitasi dan alasan yang disetujui.
- Jangan merender nama file atau metadata upload sebagai HTML mentah.
- Pastikan CSRF dan middleware auth mengikuti pola aplikasi.
- Rate limit atau proteksi tambahan hanya bila dibutuhkan oleh pola endpoint yang ada.
- Strip EXIF/GPS jika pipeline saat ini mendukungnya atau tambahkan secara aman dalam scope pemrosesan gambar.
- Jangan menganggap semua foto kegiatan otomatis layak dipublikasikan. Admin bertanggung jawab memeriksa foto sebelum menerbitkan album, khususnya foto anak-anak, data pribadi, dan situasi sensitif.
- Jangan mengekspos lokasi GPS dari metadata foto.
- Pastikan URL storage publik hanya tersedia untuk media yang memang aman dipublikasikan. Jika media draf disimpan pada storage publik, evaluasi apakah URL-nya dapat ditebak/diakses langsung dan ikuti kebijakan storage existing.

---

## 11. Backend: komponen implementasi

Nama kelas di bawah adalah contoh tanggung jawab, bukan keharusan membuat setiap kelas. Ikuti penamaan dan struktur folder aktual repository.

### 11.1 Routes

Route admin yang dibutuhkan secara konseptual:

- `GET /admin/galeri` — daftar album.
- `GET /admin/galeri/tambah` — form tambah album.
- `POST /admin/galeri` — simpan album.
- `GET /admin/galeri/{gallery}/ubah` — form edit album.
- `PATCH /admin/galeri/{gallery}` — update album.
- `DELETE /admin/galeri/{gallery}` — hapus/arsipkan album sesuai kebijakan.
- Endpoint tambahan untuk upload/attach foto, remove foto, reorder foto, memilih sampul, dan publish hanya jika pola controller/route existing memerlukan endpoint khusus.

Gunakan route name yang konsisten, misalnya `galleries.index`, `galleries.create`, dan seterusnya, tetapi periksa route naming proyek sebelum menetapkannya. Hindari benturan nama dengan route yang sudah ada.

Route publik yang harus dipertahankan dan difungsikan:

- `GET /galeri` — daftar album publik.
- `GET /galeri/{slug}` — detail album publik.

Pastikan route statis tidak tertangkap route dinamis secara keliru.

### 11.2 Controllers dan Form Requests

Tanggung jawab backend minimal:

- Controller publik mengambil album terbit dan data yang dibutuhkan halaman.
- Controller admin menangani CRUD album dan respons Inertia/redirect.
- Form Request memvalidasi create/update serta operasi attach/reorder jika memakai endpoint terpisah.
- Policy/Gate atau permission mengontrol setiap mutasi.
- Gunakan transaction saat perubahan memengaruhi lebih dari satu record dan harus konsisten, misalnya perubahan sampul/urutan jika implementasinya memerlukan.
- Tambahkan Action untuk workflow multi-langkah hanya bila memang kompleks; CRUD satu model yang sederhana tetap menggunakan pola proyek.
- Gunakan flash/toast dan error bag sesuai pola existing.

### 11.3 Query dan performa

- Buat scope seperti `published()` hanya jika sesuai dengan pola model yang ada dan dipakai secara konsisten.
- Eager-load relasi sampul, Media, dan foto album yang diperlukan.
- Gunakan pagination server-side untuk album.
- Batasi kolom yang dipilih bila membantu query list.
- Hindari menghitung foto melalui query terpisah untuk setiap album; gunakan aggregate/count eager loading yang sesuai ORM.
- Jangan mengirim semua metadata Media atau data internal yang tidak diperlukan ke Inertia props.

---

## 12. Frontend: halaman dan komponen

Periksa folder aktual sebelum membuat file. `ARCHITECTURE.md` menyatakan halaman Inertia berada di `resources/js/pages` dan UI spesifik fitur berada di `resources/js/features`; gunakan struktur nyata yang berlaku pada branch terbaru.

Halaman/komponen yang dibutuhkan secara konseptual:

### Admin

- Halaman daftar album.
- Form tambah/edit album.
- Area pengelolaan foto di dalam album.
- Upload multiple files dengan status tiap file.
- Pemilih foto sampul.
- Kontrol urutan foto.
- Dialog konfirmasi hapus album/foto.

### Publik

- Halaman indeks Galeri.
- Kartu album.
- Halaman detail album.
- Grid foto.
- Lightbox dan kontrol navigasi.

Ketentuan frontend:

- Props Inertia ditentukan dan divalidasi tipe-nya menggunakan TypeScript.
- Jangan menyalin props server ke Zustand/global state tanpa kebutuhan yang jelas.
- Gunakan state lokal React untuk interaksi sementara seperti foto yang sedang dibuka di lightbox.
- Setelah mutation, gunakan pola redirect/Inertia yang sudah dipakai modul admin lain.
- Tombol dan input harus benar-benar berfungsi, bukan elemen dekoratif.
- Komponen upload harus mengikuti kontrak endpoint media existing; jangan mengasumsikan API endpoint baru tanpa implementasi backend.

---

## 13. Kriteria penerimaan (Acceptance Criteria)

### 13.1 Admin album

- [ ] Super Admin dapat membuka daftar album Galeri.
- [ ] Daftar album menampilkan judul, sampul, tanggal kegiatan, jumlah foto, status, dan waktu pembaruan jika datanya tersedia.
- [ ] Super Admin dapat membuat album dengan judul wajib.
- [ ] Slug dibuat dengan aman dan tidak duplikat.
- [ ] Super Admin dapat mengedit album tanpa kehilangan foto yang sudah terhubung.
- [ ] Super Admin dapat menyimpan album sebagai draf.
- [ ] Album hanya muncul di publik setelah diterbitkan.
- [ ] Super Admin dapat menghapus album melalui konfirmasi.
- [ ] Penghapusan album tidak menghapus Media yang masih digunakan modul lain.

### 13.2 Foto album

- [ ] Super Admin dapat mengunggah beberapa foto sekaligus.
- [ ] UI menunjukkan status upload dan pemrosesan per file.
- [ ] Kegagalan satu file tidak menyebabkan foto yang berhasil hilang atau perlu diunggah ulang tanpa alasan.
- [ ] Foto hanya dapat dipakai setelah media siap diproses.
- [ ] Super Admin dapat memilih foto sampul dari foto yang berada di album tersebut.
- [ ] Super Admin dapat mengubah urutan foto dan hasilnya bertahan setelah reload.
- [ ] Super Admin dapat mengubah caption/alt text.
- [ ] Super Admin dapat mengeluarkan foto dari album dengan konfirmasi.
- [ ] Media tidak dihapus dari storage jika masih direferensikan di tempat lain.

### 13.3 Halaman publik

- [ ] `/galeri` menampilkan album terbit saja.
- [ ] Kartu album menampilkan informasi yang tersedia tanpa placeholder kosong yang membingungkan.
- [ ] Detail album `/galeri/{slug}` hanya menampilkan album terbit.
- [ ] URL draf/arsip/tidak ditemukan menghasilkan respons yang aman dan konsisten.
- [ ] Foto tampil menggunakan varian yang sesuai dan tidak menampilkan media yang gagal.
- [ ] Lightbox dapat digunakan dengan mouse, keyboard, dan perangkat sentuh.
- [ ] Daftar dan detail galeri responsif di mobile/tablet/desktop.
- [ ] Metadata SEO mengikuti pola publik yang sudah ada.

### 13.4 Keamanan dan kualitas

- [ ] Pengunjung tidak dapat menjalankan route mutasi admin.
- [ ] Semua mutasi divalidasi di backend.
- [ ] Format dan ukuran file divalidasi server-side.
- [ ] Tidak ada path file yang dibentuk langsung dari nama file upload.
- [ ] Metadata EXIF sensitif tidak terekspos jika pipeline mendukung stripping atau setelah dukungan ditambahkan.
- [ ] Query tidak memiliki masalah N+1 yang jelas.
- [ ] UI mempunyai state loading, error, empty, success, dan disabled yang relevan.
- [ ] Tidak ada regresi pada modul media atau modul lain yang menggunakan media.

---

## 14. Strategi pengujian

Gunakan framework dan konvensi yang sudah ada. Nama test di bawah bersifat konseptual; sesuaikan dengan struktur aktual.

### 14.1 Backend / Pest

- Feature test CRUD album: create, update, delete.
- Validasi judul kosong, slug duplikat, field tidak valid, dan media yang tidak valid.
- Authorization: guest dan user tanpa permission ditolak; Super Admin dapat melakukan operasi yang diizinkan.
- Publik hanya melihat album Terbit.
- Album draf, arsip, dan soft-deleted tidak bisa dibuka melalui URL publik.
- Foto harus terkait dengan album yang valid.
- Media belum selesai/gagal diproses tidak dapat menjadi sampul atau muncul di publik.
- Reorder menyimpan urutan secara konsisten.
- Penghapusan album/foto tidak menghapus Media yang masih dipakai.
- Pagination dan query filter bekerja.
- Slug collision menghasilkan slug unik.

### 14.2 Frontend / Vitest

- Form album menampilkan error server di field yang benar.
- Komponen upload menampilkan status tiap file dan error.
- Pemilihan sampul hanya menampilkan foto yang memenuhi syarat.
- Dialog konfirmasi mencegah penghapusan tanpa persetujuan.
- Lightbox dapat dibuka, ditutup, dan menavigasi foto.
- Keyboard interaction dan aksesibilitas dasar diuji.
- Empty state dan album tanpa foto ditampilkan dengan benar.

### 14.3 End-to-end / Playwright

Skenario minimum:

1. Login sebagai Super Admin.
2. Buat album draf.
3. Unggah foto dan tunggu pemrosesan selesai.
4. Pilih sampul, atur urutan, lalu terbitkan album.
5. Buka `/galeri` sebagai pengunjung dan pastikan album muncul.
6. Buka detail album dan gunakan lightbox.
7. Pastikan album draf tidak dapat dilihat publik.
8. Uji penghapusan dengan dialog konfirmasi.

Jalankan test yang terkait terlebih dahulu, lalu suite yang lebih luas sesuai scripts proyek. Karena perubahan menyentuh media bersama, jalankan test regresi media dan modul yang memakai Media.

---

## 15. Urutan implementasi yang disarankan

AI agent harus menyajikan plan terlebih dahulu. Urutan berikut adalah acuan, bukan izin untuk langsung coding tanpa persetujuan.

### 8A.0 — Audit dan rencana

- Audit model/migration Media, job pemrosesan, status, varian, storage, upload picker, routes, settings permission, layout, SEO, serta tests.
- Temukan route/page Galeri yang sudah ada dan identifikasi placeholder.
- Buat daftar file yang perlu dibuat/diubah dan migration yang diperlukan.
- Tentukan kontrak data Inertia dan strategi relasi album-foto-media.

### 8A.1 — Domain dan persistence

- Buat atau sesuaikan model, migration, relasi, casts/status, scope, index, foreign key, factory, dan seeder bila diperlukan.
- Pastikan keputusan soft delete dan lifecycle Media jelas.
- Tambahkan test model/domain yang relevan.

### 8A.2 — Backend admin

- Tambahkan route, policy/permission, Form Requests, controller, dan Action hanya jika dibutuhkan.
- Implementasikan CRUD album, upload/attach foto, reorder, cover selection, publish, dan delete.
- Pastikan validasi dan transaksi konsisten.

### 8A.3 — UI admin

- Implementasikan daftar album, form, pengelolaan foto, status upload/pemrosesan, sampul, reorder, dan confirmation dialog.
- Reuse komponen existing.
- Tambahkan Vitest/component tests.

### 8A.4 — Halaman publik

- Implementasikan indeks `/galeri` dan detail `/galeri/{slug}`.
- Integrasikan SEO dan layout publik existing.
- Tambahkan grid foto dan lightbox aksesibel.
- Tambahkan backend/public feature tests dan frontend tests.

### 8A.5 — Hardening dan regression

- Uji authorization, status publikasi, validasi upload, EXIF/GPS, orphan file, N+1, dan responsif.
- Jalankan lint, typecheck, unit/feature tests, build, serta E2E yang relevan.
- Perbarui dokumentasi, navigasi admin, dan roadmap/phase checklist sesuai konvensi proyek.
- Laporkan file yang berubah, migration, test yang dijalankan, hasil aktual, risiko tersisa, dan keputusan yang memerlukan persetujuan.

---

## 16. Definisi selesai (Definition of Done)

Phase 8A dapat dianggap selesai jika seluruh kondisi berikut terpenuhi:

1. CRUD album admin berfungsi dan dilindungi authorization backend.
2. Admin dapat mengunggah serta mengelola banyak foto dengan status pemrosesan yang akurat.
3. Sampul dan urutan foto tersimpan secara konsisten.
4. Halaman publik indeks dan detail hanya menampilkan album/foto yang layak dipublikasikan.
5. Lightbox aksesibel dan galeri responsif.
6. Validasi file, lifecycle Media, dan metadata sensitif ditangani sesuai kemampuan pipeline.
7. Test backend, frontend, dan E2E yang relevan lulus, atau kegagalan yang belum terselesaikan didokumentasikan secara eksplisit.
8. Lint, typecheck, dan build yang diwajibkan repository lulus.
9. Tidak ada regresi pada modul yang menggunakan Media atau pada layout publik global.
10. Dokumentasi fase dan navigasi admin diperbarui sesuai implementasi final.
11. Tidak ada fitur Phase 8B (Dokumen) atau Phase 8C (Kontak) yang terselip dalam perubahan.

---

## 17. Output yang harus diberikan AI agent

Sebelum implementasi:

- Ringkasan audit kondisi repository saat ini.
- Daftar komponen yang dapat digunakan ulang.
- Daftar gap terhadap spesifikasi ini.
- Rencana implementasi per tahap.
- Daftar file yang akan dibuat/diubah.
- Rencana migration dan relasi database.
- Risiko, asumsi, dan keputusan yang perlu disetujui.
- Daftar test yang akan ditambahkan/dijalankan.

Setelah implementasi:

- Ringkasan fitur yang selesai.
- Daftar file dan migration yang berubah.
- Perintah test/check yang benar-benar dijalankan beserta hasilnya.
- Catatan keamanan/performa dan keterbatasan yang tersisa.
- Pembaruan dokumentasi/roadmap terkait.
- Konfirmasi eksplisit bahwa Phase 8B dan 8C tetap di luar scope.

## 18. Prompt singkat untuk menjalankan dokumen ini

> Baca `phase-8a-galleries.md` bersama `ARCHITECTURE.md`, `DESIGN.md`, `PRD.md`, dan `docs/DECISIONS.md`. Audit codebase terbaru terlebih dahulu. Jangan langsung coding. Sajikan audit, gap, file yang akan diubah, rancangan migration/relasi, tahapan implementasi, serta rencana test untuk Phase 8A Galeri. Reuse pola dan media pipeline yang sudah ada, hindari refactor yang tidak relevan, dan tunggu persetujuan sebelum implementasi. Scope hanya Galeri; Dokumen (Phase 8B) dan Kontak (Phase 8C) tidak termasuk.
