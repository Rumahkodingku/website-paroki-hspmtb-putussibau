# PHASE 6A — Parish Profile (Profil Paroki)

> **Proyek:** Website Gereja Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau  
> **Dokumen:** `docs/roadmap/phase-6A-parish-profile.md`  
> **Status:** Ready for implementation planning  
> **Prioritas:** Setelah PHASE 5 — News & Agenda dinyatakan selesai  
> **Stack:** Laravel 13, Inertia.js 3, React 19, TypeScript, Tailwind CSS 4, MySQL  
> **Bahasa UI:** Bahasa Indonesia  
> **Zona waktu tampilan:** `Asia/Pontianak`

---

## 1. Tujuan Dokumen

Dokumen ini adalah implementation plan untuk AI agent yang mengerjakan **PHASE 6A — Module Profil Paroki**. Gunakan dokumen ini sebagai sumber scope dan acceptance criteria selama implementasi.

AI agent MUST:

1. Memeriksa codebase dan konvensi aktual sebelum mengubah kode.
2. Mengikuti `docs/ARCHITECTURE.md`, `docs/PRD.md`, `docs/DESIGN.md`, `docs/DECISIONS.md`, serta pola implementasi PHASE 4 dan PHASE 5.
3. Mengimplementasikan fitur secara bertahap, menjalankan test yang relevan, dan melaporkan hasil nyata.
4. Memperbarui dokumentasi roadmap setelah perubahan yang benar-benar selesai.
5. Tidak mengarang data resmi paroki untuk migration, seeder, UI, atau screenshot.

Dokumen ini **tidak menginstruksikan rewrite aplikasi**. Perubahan harus bersifat inkremental dan menggunakan kembali infrastruktur yang telah tersedia.

## 2. Tujuan Produk

PHASE 6A menyediakan pengelolaan informasi profil paroki melalui Admin Panel dan menampilkannya pada halaman publik. Admin yang berwenang harus dapat memperbarui konten tanpa mengubah kode.

Hasil akhir mencakup:

- Informasi utama Profil Paroki.
- Sejarah dan linimasa sejarah.
- Informasi wilayah pelayanan.
- Profil pastor serta riwayat penugasan.
- Struktur kepengurusan berdasarkan periode.
- Halaman publik dinamis untuk setiap bagian tersebut.
- Pengujian, otorisasi, sanitasi konten, responsivitas, dan dokumentasi.

## 3. Scope dan Batas Modul

### 3.1 Termasuk dalam PHASE 6A

| ID | Area | Halaman publik | Fitur admin |
| --- | --- | --- | --- |
| A-01 | Overview Profil Paroki | `/profil` | Halaman ringkasan dan tautan ke submenu |
| A-02 | Informasi Profil | Ditampilkan pada `/profil` | Kelola identitas dan deskripsi utama |
| A-03 | Sejarah Paroki | `/profil/sejarah` | Kelola narasi, linimasa, gambar pendukung |
| A-04 | Wilayah Pelayanan | `/profil/wilayah` | Kelola narasi cakupan dan informasi pendukung |
| A-05 | Pastor Paroki | `/profil/pastor` | Kelola data pastor dan riwayat penugasan |
| A-06 | Struktur Kepengurusan | `/profil/struktur` | Kelola periode dan anggota kepengurusan |

### 3.2 Secara eksplisit tidak termasuk

- **Visi & Misi:** tidak membuat route, halaman, submenu, field, tabel, migration, seeder, placeholder, atau tautan untuk Visi & Misi.
- **PHASE 6B — Pelayanan:** prosedur, persyaratan, FAQ, formulir pendaftaran, dan manajemen layanan sakramental.
- **PHASE 7 — Komunitas:** CRUD komunitas, stasi, lingkungan, seksi, dan domain komunitas. PHASE 6A hanya boleh menyiapkan kontrak integrasi yang diperlukan.
- **PHASE 8 — Galeri/Download/Kontak:** jangan membangun ulang pengelolaan media atau dokumen. Gunakan infrastruktur media yang sudah ada dan buat integrasi lanjutan hanya jika sesuai kontrak yang tersedia.
- Homepage aggregation, dashboard analytics, atau refactor modul PHASE 4–5 yang tidak diperlukan.
- REST API terpisah untuk data yang sudah dapat dikirim melalui Inertia.

### 3.3 Aturan scope

- Jangan menambahkan fitur hanya karena tampak menarik jika tidak diperlukan untuk acceptance criteria.
- Jangan menganggap data contoh sebagai data resmi paroki.
- Jangan membuat struktur domain komunitas duplikat di modul Profil Paroki.
- Jika kebutuhan tidak jelas, periksa PRD dan decisions terlebih dahulu; catat pertanyaan yang membutuhkan keputusan manusia.

## 4. Prasyarat dan Audit Sebelum Implementasi

Sebelum mengubah file, AI agent MUST:

- [ ] Membaca `docs/ARCHITECTURE.md`, `docs/PRD.md`, `docs/DESIGN.md`, `docs/DECISIONS.md`.
- [ ] Membaca `docs/roadmap/README.md` dan dokumen rencana PHASE 4–5 yang ada.
- [ ] Memeriksa `composer.json` dan `package.json` untuk perintah test, lint, typecheck, dan build yang aktual.
- [ ] Memeriksa struktur model, migration, controller, Form Request, Policy, Action, media, dan komponen UI yang sudah tersedia.
- [ ] Memeriksa route publik profil yang sekarang ada dan test placeholder terkait.
- [ ] Memeriksa pola sidebar admin dan izin akses dari PHASE 4–5.
- [ ] Memeriksa cara media disimpan, direlasikan, diproses, dan ditampilkan.
- [ ] Mencatat baseline test/build sebelum perubahan apabila lingkungan memungkinkan.

**Jangan menebak nama class, permission, route name, komponen, atau script npm.** Gunakan nama dan pola yang ditemukan dalam codebase.

## 5. Prinsip Arsitektur

1. **Modular monolith:** gunakan Laravel + Inertia; jangan menambahkan REST API terpisah.
2. **Backend adalah sumber kebenaran:** validasi, otorisasi, query publikasi, dan aturan domain harus berada di server.
3. **Controller tipis:** gunakan Form Request untuk validasi write; gunakan Action hanya untuk operasi multi-model, transaksi, side effect, operasi kompleks, atau operasi yang digunakan di beberapa tempat.
4. **Eloquent default:** jangan menambahkan Repository untuk CRUD biasa.
5. **Inertia props:** data server dikirim melalui props; jangan menyalin seluruh props ke global state.
6. **Reuse UI:** gunakan AdminLayout/PublicLayout, design tokens, form, tabel, dialog, toast, upload/media picker, rich text editor, dan pola navigasi yang sudah ada.
7. **Authorization backend:** ikuti middleware, Policy/Gate, dan Spatie Permission yang telah diterapkan. Jangan hanya menyembunyikan tombol di frontend.
8. **No unrelated refactor:** perubahan di luar scope harus dihindari.
9. **Bahasa:** teks UI dan pesan validasi dalam Bahasa Indonesia; nama tabel/kolom dan kode mengikuti konvensi Inggris `snake_case`.
10. **Data resmi:** jangan membuat data pastor, sejarah, tahun, jabatan, wilayah, nomor telepon, atau informasi lain yang tidak disediakan pihak paroki.

## 6. Domain dan Rekomendasi Data

Nama tabel di bawah adalah **usulan domain**, bukan izin untuk membuat schema tanpa audit. Sebelum migration, bandingkan dengan model/migration yang sudah ada dan konvensi proyek. Hindari duplikasi informasi yang sebenarnya telah dikelola modul lain.

### 6.1 `parish_profiles` — profil utama

Secara logis hanya ada satu profil aktif untuk paroki. Terapkan pola singleton sesuai kemampuan database dan konvensi proyek.

Field kandidat:

| Field | Tipe konseptual | Aturan |
| --- | --- | --- |
| `id` | bigint | PK |
| `name` | string | Required |
| `short_description` | text | Nullable |
| `description` | text/longText | Nullable; rich text jika memang memakai editor |
| `address` | string/text | Nullable |
| `phone` | string | Nullable; simpan sebagai string |
| `email` | string | Nullable; validasi format |
| `website` | string | Nullable; validasi URL |
| `established_year` | unsigned small integer | Nullable; validasi rentang masuk akal |
| `logo_media_id` | FK media yang sudah tersedia | Nullable |
| `cover_media_id` | FK media yang sudah tersedia | Nullable |
| `map_url` | string | Nullable; validasi URL/host sesuai kebutuhan |
| `video_url` | string | Nullable; validasi URL dan host yang diizinkan |
| `is_published` | boolean | Default sesuai konvensi konten yang ada |
| timestamps | — | Wajib mengikuti pola proyek |

Ketentuan:

- Jangan menyimpan visi atau misi pada tabel ini.
- Jangan menyimpan file upload langsung sebagai blob di database.
- Jangan membuat sistem media baru.
- Batasi akses admin untuk mengubah profil sesuai otorisasi proyek.
- Jika profil belum tersedia, halaman publik harus menampilkan empty state yang disengaja, bukan data rekaan.

### 6.2 `parish_history_items` — linimasa sejarah

Field kandidat:

| Field | Tipe konseptual | Aturan |
| --- | --- | --- |
| `id` | bigint | PK |
| `title` | string | Required |
| `year` | unsigned small integer | Nullable jika peristiwa tidak memiliki tahun pasti |
| `date` | date | Nullable; gunakan bila tanggal tepat diketahui |
| `description` | text/longText | Nullable atau required sesuai editor |
| `media_id` | FK media | Nullable |
| `sort_order` | integer | Default `0` |
| `is_published` | boolean | Default sesuai konvensi konten |
| timestamps | — | Wajib |

Ketentuan:

- Pisahkan narasi utama sejarah dari daftar linimasa jika keduanya perlu dikelola secara independen.
- Urutan publik harus deterministik, misalnya `sort_order` lalu tanggal/tahun dan `id`.
- Jangan mewajibkan tanggal tepat ketika hanya tahun yang diketahui.
- Hanya tampilkan item yang memenuhi aturan publikasi.
- Pertimbangkan soft delete jika konsisten dengan tabel konten proyek.

### 6.3 Pastor dan riwayat penugasan

**Jangan langsung mengasumsikan satu tabel `parish_pastors` cukup.** Audit kebutuhan riwayat terlebih dahulu. Model yang lebih konsisten bila satu pastor dapat memiliki beberapa periode penugasan adalah:

- `pastors`: identitas orang (nama, gelar opsional, foto media opsional, biografi opsional).
- `pastor_assignments`: relasi pastor dengan periode penugasan, tanggal mulai/akhir, status, urutan jika diperlukan.

Field kandidat `pastors`:

| Field | Tipe konseptual | Aturan |
| --- | --- | --- |
| `id` | bigint | PK |
| `name` | string | Required |
| `title` | string | Nullable |
| `photo_media_id` | FK media | Nullable |
| `bio` | text/longText | Nullable |
| `sort_order` | integer | Default `0` |
| `is_published` | boolean | Default sesuai konvensi |
| timestamps | — | Wajib |

Field kandidat `pastor_assignments`:

| Field | Tipe konseptual | Aturan |
| --- | --- | --- |
| `id` | bigint | PK |
| `pastor_id` | FK `pastors` | Required |
| `start_date` | date/year-compatible | Nullable jika tidak diketahui |
| `end_date` | date/year-compatible | Nullable untuk penugasan aktif |
| `is_current` | boolean | Aturan konsistensi harus diuji |
| `description` | text | Nullable |
| timestamps | — | Wajib |

Ketentuan:

- Tentukan apakah model menyimpan tanggal penuh atau hanya tahun berdasarkan data yang tersedia dan keputusan domain.
- Jangan mengarang tanggal penugasan.
- Jika hanya satu pastor paroki dapat aktif, tegakkan aturan itu di backend dan, bila memungkinkan, di level database/transaction.
- Perubahan pastor aktif harus menutup/menonaktifkan penugasan sebelumnya secara konsisten; gunakan Action + transaksi bila menyentuh beberapa record.
- Jangan menghapus riwayat penugasan hanya karena pastor tidak lagi aktif.
- Halaman publik harus memisahkan pastor yang sedang bertugas dan riwayat terdahulu jika data tersedia.

### 6.4 `parish_board_periods` — periode kepengurusan

Field kandidat:

| Field | Tipe konseptual | Aturan |
| --- | --- | --- |
| `id` | bigint | PK |
| `name` | string | Required; contoh format nama periode, bukan data resmi |
| `start_year` | unsigned small integer | Nullable bila belum dikonfirmasi |
| `end_year` | unsigned small integer | Nullable bila belum dikonfirmasi |
| `description` | text | Nullable |
| `is_active` | boolean | Default `false` |
| `is_published` | boolean | Default sesuai pola konten |
| `sort_order` | integer | Default `0` |
| timestamps | — | Wajib |

Ketentuan:

- Validasi `end_year >= start_year` ketika keduanya terisi.
- Jika hanya satu periode boleh aktif, pastikan hanya satu periode aktif melalui operasi atomik/transaction dan test.
- Periode lama dapat tetap dipublikasikan sebagai arsip jika `is_published = true`.
- Jangan membuat periode contoh yang seolah-olah benar.

### 6.5 `parish_board_members` — anggota kepengurusan

Field kandidat:

| Field | Tipe konseptual | Aturan |
| --- | --- | --- |
| `id` | bigint | PK |
| `period_id` | FK `parish_board_periods` | Required |
| `name` | string | Required |
| `position` | string | Required |
| `photo_media_id` | FK media | Nullable |
| `description` | text | Nullable |
| `sort_order` | integer | Default `0` |
| `is_published` | boolean | Default sesuai pola konten |
| timestamps | — | Wajib |

Ketentuan:

- Anggota harus terhubung ke satu periode.
- Pengurutan tidak bergantung pada urutan default database.
- Nama/jabatan tidak boleh berisi data pribadi yang tidak disetujui untuk publikasi.
- Jangan menganggap foto wajib.
- Penghapusan periode yang memiliki anggota harus menggunakan FK behavior yang eksplisit; jangan menghapus anggota secara tak sengaja.

### 6.6 Wilayah pelayanan

PHASE 6A harus menyediakan halaman `/profil/wilayah`, tetapi **tidak boleh menduplikasi domain PHASE 7 — Komunitas**.

Mulai dengan opsi paling sederhana yang memenuhi kebutuhan:

1. Konten narasi/cakupan wilayah disimpan pada profil atau section khusus yang konsisten dengan model konten yang ada; atau
2. Tabel `parish_service_areas` hanya dibuat jika informasi tersebut perlu dikelola sebagai record terstruktur yang terpisah.

Field kandidat jika tabel khusus benar-benar diperlukan:

| Field | Tipe konseptual | Aturan |
| --- | --- | --- |
| `id` | bigint | PK |
| `content` | text/longText | Narasi wilayah |
| `map_media_id` | FK media | Nullable |
| `map_url` | string | Nullable; validasi URL |
| `is_published` | boolean | Aturan publikasi |
| timestamps | — | Wajib |

Jangan membuat field seperti jumlah stasi/lingkungan atau daftar komunitas yang disalin manual jika datanya nantinya dimiliki PHASE 7. Jika kontrak integrasi belum tersedia, tampilkan narasi yang disetujui dan dokumentasikan integrasi yang ditunda.

### 6.7 Relasi media

- Gunakan sistem media yang sudah tersedia pada codebase.
- Periksa pola FK, model, upload, varian gambar, authorization, dan penghapusan file yang digunakan modul lain.
- Media bersifat opsional kecuali ada kebutuhan nyata yang mengharuskan sebaliknya.
- Jangan menghapus media bersama saat sebuah konten dihapus jika media tersebut mungkin dipakai record lain.
- Gunakan alt text yang sesuai pada halaman publik bila infrastruktur media mendukungnya.
- File yang tidak valid harus ditolak di server; jangan hanya mengandalkan validasi browser.

## 7. Status Publikasi dan Query Publik

Gunakan pola status yang konsisten dengan model konten PHASE 5. Jangan membuat sistem workflow baru jika `is_published`, `published_at`, soft delete, atau scope yang ada sudah mencukupi.

Aturan minimum:

- Record draft/nonaktif tidak tampil di halaman publik.
- Record terhapus tidak tampil.
- Query publik hanya mengambil kolom dan relasi yang diperlukan.
- Gunakan eager loading untuk relasi media dan anggota/periode guna mencegah N+1.
- Urutan konten eksplisit dan stabil.
- Pastikan endpoint/route publik tidak bisa menampilkan konten privat dengan mengubah slug/ID.
- Status `is_active` dan `is_published` memiliki arti berbeda jika keduanya dipakai:
  - `is_active`: status operasional/domain, misalnya pastor atau periode yang sedang aktif.
  - `is_published`: apakah data boleh ditampilkan publik.
- Hindari dua kolom status jika tidak ada kebutuhan bisnis yang jelas.

## 8. Backend Implementation Tasks

Kerjakan berurutan. Tandai task selesai hanya setelah implementasi dan verifikasi yang relevan berhasil.

### 8.1 Persiapan

- [ ] **6A-01** Buat/selaraskan dokumen implementasi ini pada lokasi `docs/roadmap/phase-6A-parish-profile.md`.
- [ ] **6A-02** Perbarui `docs/roadmap/README.md` untuk memisahkan PHASE 6A dan PHASE 6B, tanpa mengubah status fase yang belum diverifikasi.
- [ ] **6A-03** Audit pola CRUD PHASE 4/5 dan catat file contoh yang akan digunakan ulang.
- [ ] **6A-04** Audit routes/placeholder profil yang sudah ada.
- [ ] **6A-05** Audit media, rich text sanitizer, Policy, permission, dan UI primitives.
- [ ] **6A-06** Finalisasi schema berdasarkan hasil audit; hapus field kandidat yang tidak dibutuhkan.
- [ ] **6A-07** Catat keputusan terbuka, khususnya struktur wilayah pelayanan dan representasi periode pastor.

### 8.2 Database dan model

- [ ] **6A-08** Buat migration untuk profil utama jika belum tersedia.
- [ ] **6A-09** Buat migration narasi/linimasa sejarah sesuai model final.
- [ ] **6A-10** Buat migration identitas pastor dan riwayat penugasan jika diperlukan.
- [ ] **6A-11** Buat migration periode kepengurusan.
- [ ] **6A-12** Buat migration anggota kepengurusan.
- [ ] **6A-13** Buat tabel wilayah khusus hanya jika hasil audit membuktikan perlu.
- [ ] **6A-14** Tambahkan FK dengan `onDelete` eksplisit dan index yang relevan.
- [ ] **6A-15** Implementasikan model, relasi, casts, scopes, fillable, dan factory yang diperlukan.
- [ ] **6A-16** Implementasikan seeder tanpa data historis/orang/jabatan rekaan; gunakan database kosong untuk verifikasi migrasi.
- [ ] **6A-17** Pastikan skema bisa dijalankan dari fresh database dan rollback yang aman.

### 8.3 Backend profil dan sejarah

- [ ] **6A-18** Implementasikan halaman/form admin untuk informasi profil utama.
- [ ] **6A-19** Implementasikan Form Request untuk create/update yang diperlukan.
- [ ] **6A-20** Pastikan profil utama tidak bisa memiliki record duplikat.
- [ ] **6A-21** Implementasikan pengelolaan narasi sejarah.
- [ ] **6A-22** Implementasikan CRUD item linimasa.
- [ ] **6A-23** Implementasikan validasi tahun/tanggal dan sort order.
- [ ] **6A-24** Integrasikan media untuk cover dan gambar item sejarah.
- [ ] **6A-25** Sanitasi HTML rich text di server menggunakan fasilitas/allowlist yang sudah disetujui proyek.

### 8.4 Backend pastor

- [ ] **6A-26** Implementasikan CRUD identitas pastor.
- [ ] **6A-27** Implementasikan riwayat penugasan jika struktur domain terpisah.
- [ ] **6A-28** Implementasikan penetapan pastor yang sedang bertugas.
- [ ] **6A-29** Pastikan perubahan status pastor/penugasan aktif konsisten dan transaksional bila melibatkan beberapa record.
- [ ] **6A-30** Validasi rentang tanggal/tahun dan konflik penugasan.
- [ ] **6A-31** Implementasikan status publikasi dan urutan tampilan.

### 8.5 Backend kepengurusan

- [ ] **6A-32** Implementasikan CRUD periode kepengurusan.
- [ ] **6A-33** Implementasikan CRUD anggota pada periode tertentu.
- [ ] **6A-34** Validasi rentang tahun.
- [ ] **6A-35** Terapkan aturan satu periode aktif bila keputusan domain menetapkannya.
- [ ] **6A-36** Terapkan urutan jabatan/anggota yang eksplisit.
- [ ] **6A-37** Pertahankan arsip periode lama tanpa mengubah record historis ketika periode baru dibuat.

### 8.6 Backend wilayah dan otorisasi

- [ ] **6A-38** Implementasikan penyimpanan konten wilayah berdasarkan desain domain final.
- [ ] **6A-39** Siapkan kontrak integrasi PHASE 7 tanpa membuat CRUD komunitas.
- [ ] **6A-40** Gunakan Form Request untuk seluruh write operation.
- [ ] **6A-41** Terapkan middleware, Policy/Gate, dan permission sesuai pola aktual proyek.
- [ ] **6A-42** Pastikan validasi backend melindungi URL, file, HTML, dan data yang ditampilkan.
- [ ] **6A-43** Pastikan query publik tidak mengungkap draft atau data nonaktif.

## 9. Admin Panel — UI/UX

### 9.1 Sidebar

Tambahkan item **Profil Paroki** dengan submenu yang dapat dibuka/tutup dan mengikuti pola active-state sidebar saat ini.

Struktur yang diharapkan:

```text
Profil Paroki
├── Overview
├── Informasi Profil
├── Sejarah Paroki
├── Wilayah Pelayanan
├── Pastor Paroki
└── Struktur Kepengurusan
```

Ketentuan:

- Tidak ada submenu Visi & Misi.
- Jangan mengubah perilaku active-state menu lain kecuali perbaikan diperlukan dan diuji.
- Pastikan state parent/child mengikuti konvensi sidebar yang sudah diterapkan.
- Menu dan route admin harus dilindungi otorisasi backend.

### 9.2 Overview Profil Paroki

- [ ] **6A-44** Buat halaman overview dengan judul dan deskripsi yang jelas.
- [ ] **6A-45** Tampilkan kartu navigasi ke lima area: Informasi Profil, Sejarah Paroki, Wilayah Pelayanan, Pastor Paroki, Struktur Kepengurusan.
- [ ] **6A-46** Gunakan ilustrasi/gambar hanya dari aset yang tersedia atau placeholder visual netral; jangan membuat foto palsu yang diklaim sebagai foto paroki.
- [ ] **6A-47** Tampilkan ringkasan jumlah record jika bisa dihitung secara akurat dan bermanfaat.
- [ ] **6A-48** Tampilkan catatan editorial: konten yang belum disetujui jangan dipublikasikan.
- [ ] **6A-49** Jangan menambahkan dashboard analytics yang tidak diperlukan.

### 9.3 Informasi Profil

- [ ] **6A-50** Buat form untuk nama, deskripsi singkat, deskripsi, alamat, kontak, website, tahun berdiri, logo, cover, dan URL pendukung sesuai schema final.
- [ ] **6A-51** Gunakan komponen editor/media yang sudah ada.
- [ ] **6A-52** Tampilkan preview yang berguna dan validasi field.
- [ ] **6A-53** Sediakan feedback sukses/gagal serta loading state.
- [ ] **6A-54** Jelaskan field opsional dan jangan memaksa admin mengisi data yang belum tersedia.

### 9.4 Sejarah Paroki

- [ ] **6A-55** Buat halaman daftar linimasa dengan judul, tahun/tanggal, status publikasi, dan urutan.
- [ ] **6A-56** Buat form tambah/edit item linimasa.
- [ ] **6A-57** Sediakan kontrol urutan yang mudah dipahami.
- [ ] **6A-58** Sediakan dialog konfirmasi penghapusan sesuai pola proyek.
- [ ] **6A-59** Sediakan empty state dan error state.
- [ ] **6A-60** Bedakan dengan jelas konten narasi sejarah dan item linimasa jika keduanya terpisah.

### 9.5 Wilayah Pelayanan

- [ ] **6A-61** Buat form pengelolaan narasi/cakupan wilayah.
- [ ] **6A-62** Sediakan URL peta atau media peta hanya bila dibutuhkan dan didukung schema.
- [ ] **6A-63** Jangan membuat tabel pengelolaan komunitas, stasi, atau lingkungan di modul ini.
- [ ] **6A-64** Berikan petunjuk bahwa data rinci komunitas akan dikelola oleh modul PHASE 7.

### 9.6 Pastor Paroki

- [ ] **6A-65** Buat halaman daftar pastor dengan status penugasan dan publikasi.
- [ ] **6A-66** Buat form tambah/edit data pastor.
- [ ] **6A-67** Sediakan pengelolaan riwayat penugasan.
- [ ] **6A-68** Buat aksi khusus untuk menetapkan pastor aktif jika diperlukan.
- [ ] **6A-69** Konfirmasi sebelum mengubah penugasan aktif bila tindakan memengaruhi riwayat.
- [ ] **6A-70** Tampilkan foto sebagai opsional dan gunakan media picker yang ada.

### 9.7 Struktur Kepengurusan

- [ ] **6A-71** Buat daftar periode dengan indikator periode aktif dan status publikasi.
- [ ] **6A-72** Buat form tambah/edit periode.
- [ ] **6A-73** Buat daftar anggota berdasarkan periode terpilih.
- [ ] **6A-74** Buat form tambah/edit anggota yang terkait dengan periode.
- [ ] **6A-75** Sediakan urutan anggota/jabatan.
- [ ] **6A-76** Pastikan penghapusan periode menjelaskan konsekuensi terhadap anggotanya.
- [ ] **6A-77** Sediakan tampilan arsip periode terdahulu.

### 9.8 UX dan aksesibilitas admin

- [ ] **6A-78** Gunakan label input, pesan validasi Bahasa Indonesia, dan hint yang jelas.
- [ ] **6A-79** Sediakan loading, empty, error, dan success state.
- [ ] **6A-80** Semua aksi berbahaya harus memiliki konfirmasi sesuai pola aplikasi.
- [ ] **6A-81** Semua interaksi penting dapat dilakukan dengan keyboard.
- [ ] **6A-82** Jangan menggunakan warna sebagai satu-satunya pembeda status.
- [ ] **6A-83** Pastikan form dan tabel usable pada viewport sempit.
- [ ] **6A-84** Jangan mengulang string UI hard-coded jika proyek sudah memakai file bahasa/konstanta terpusat.

## 10. Halaman Publik

### 10.1 Route dan navigasi

Pastikan halaman berikut menggunakan konten dinamis:

| Route | Konten |
| --- | --- |
| `/profil` | Ringkasan profil dan tautan ke subhalaman |
| `/profil/sejarah` | Narasi dan linimasa sejarah |
| `/profil/wilayah` | Cakupan wilayah pelayanan |
| `/profil/pastor` | Pastor aktif dan riwayat penugasan yang dipublikasikan |
| `/profil/struktur` | Periode kepengurusan dan anggota yang dipublikasikan |

- [ ] **6A-85** Ganti placeholder yang ada dengan halaman dinamis sesuai route di atas.
- [ ] **6A-86** Gunakan nama route dan pola controller yang konsisten dengan codebase.
- [ ] **6A-87** Tambahkan breadcrumb dan navigasi antarbagian jika sesuai dengan design system.
- [ ] **6A-88** Periksa footer/navigation global; hilangkan tautan Visi & Misi dari UI yang berada dalam scope perubahan ini.
- [ ] **6A-89** Jangan menghapus halaman/fitur di luar scope tanpa audit dampaknya.

### 10.2 Overview `/profil`

- [ ] **6A-90** Tampilkan nama paroki, ringkasan, deskripsi, dan gambar yang tersedia.
- [ ] **6A-91** Tampilkan kartu navigasi ke Sejarah, Wilayah Pelayanan, Pastor, dan Struktur Kepengurusan.
- [ ] **6A-92** Jangan menampilkan kartu Visi & Misi.
- [ ] **6A-93** Sembunyikan elemen opsional jika datanya kosong; hindari ruang kosong besar atau label yang menyesatkan.

### 10.3 Sejarah `/profil/sejarah`

- [ ] **6A-94** Tampilkan narasi sejarah dan linimasa berdasarkan urutan yang ditetapkan.
- [ ] **6A-95** Tampilkan gambar pendukung jika tersedia.
- [ ] **6A-96** Jangan menyimpulkan atau mengarang peristiwa/tahun sejarah.
- [ ] **6A-97** Pastikan HTML rich text aman untuk dirender.

### 10.4 Wilayah `/profil/wilayah`

- [ ] **6A-98** Tampilkan narasi cakupan wilayah yang telah disetujui.
- [ ] **6A-99** Tampilkan media atau tautan peta hanya jika datanya tersedia dan tervalidasi.
- [ ] **6A-100** Jangan menampilkan angka jumlah stasi/lingkungan kecuali berasal dari sumber data yang benar.
- [ ] **6A-101** Integrasi dengan data komunitas PHASE 7 harus mengikuti kontrak yang didokumentasikan.

### 10.5 Pastor `/profil/pastor`

- [ ] **6A-102** Tampilkan pastor yang sedang bertugas jika ada.
- [ ] **6A-103** Tampilkan riwayat pastor terdahulu hanya jika record terkait dipublikasikan.
- [ ] **6A-104** Tampilkan nama, gelar, foto, biografi, dan periode hanya jika tersedia.
- [ ] **6A-105** Jangan menampilkan data draft atau penugasan yang belum disetujui.

### 10.6 Struktur `/profil/struktur`

- [ ] **6A-106** Tampilkan periode aktif yang dipublikasikan.
- [ ] **6A-107** Tampilkan anggota dan jabatan sesuai `sort_order`.
- [ ] **6A-108** Sediakan akses ke arsip periode lama bila dipublikasikan.
- [ ] **6A-109** Jika belum ada data, tampilkan empty state yang ramah dan tidak mengarang struktur.

### 10.7 SEO, performa, dan responsive

- [ ] **6A-110** Gunakan title dan metadata halaman yang sesuai jika mekanisme SEO telah tersedia.
- [ ] **6A-111** Pastikan heading hierarchy, link text, alt text, dan focus state layak.
- [ ] **6A-112** Gunakan gambar responsif dan varian media yang tersedia.
- [ ] **6A-113** Hindari query berulang/N+1 dan kirim hanya props yang dibutuhkan.
- [ ] **6A-114** Uji layout minimal pada lebar 360, 390, 768, 1024, dan 1440 px.
- [ ] **6A-115** Pastikan navigasi halaman publik konsisten dengan screenshot/reference design yang diberikan, tanpa menyalin konten fiktif.

## 11. Validasi dan Keamanan

- [ ] **6A-116** Seluruh operasi write menggunakan Form Request sesuai konvensi proyek.
- [ ] **6A-117** Authorization diperiksa di backend, bukan hanya UI.
- [ ] **6A-118** Rich text disanitasi di server menggunakan allowlist yang disetujui.
- [ ] **6A-119** URL eksternal divalidasi dan dibatasi host-nya jika konteks mengharuskannya.
- [ ] **6A-120** File upload divalidasi jenis, ukuran, dan izin aksesnya di server.
- [ ] **6A-121** Query publik mengecualikan draft, nonaktif, dan soft-deleted records.
- [ ] **6A-122** Relasi dan penghapusan data memiliki perilaku FK eksplisit.
- [ ] **6A-123** Aksi yang mengubah pastor/periode aktif memakai transaksi jika lebih dari satu record terdampak.
- [ ] **6A-124** Tidak ada data pribadi yang dipublikasikan tanpa persetujuan yang sesuai.
- [ ] **6A-125** Jangan menambahkan logging yang membocorkan isi data sensitif atau informasi autentikasi.

## 12. Testing

Gunakan framework dan script aktual yang ada di repository. Jangan mengganti framework test.

### 12.1 Feature tests

- [ ] **6A-126** Profil utama dapat dibuat/diperbarui dan tidak menjadi duplikat.
- [ ] **6A-127** Sejarah dan linimasa mendukung create/read/update/delete sesuai izin.
- [ ] **6A-128** Validasi tahun/tanggal linimasa bekerja.
- [ ] **6A-129** Pastor dan riwayat penugasan bekerja sesuai aturan domain final.
- [ ] **6A-130** Aturan pastor aktif ditegakkan jika hanya satu yang diperbolehkan.
- [ ] **6A-131** Periode kepengurusan dan anggota dapat dikelola.
- [ ] **6A-132** Validasi rentang tahun dan aturan periode aktif bekerja.
- [ ] **6A-133** Wilayah pelayanan dapat disimpan dan dibaca sesuai desain domain.
- [ ] **6A-134** User tanpa izin tidak dapat mengakses atau memutasi data admin.
- [ ] **6A-135** Draft/nonaktif tidak tampil pada halaman publik.
- [ ] **6A-136** Rich text berbahaya disanitasi atau ditolak.
- [ ] **6A-137** Relasi media dan FK berperilaku sesuai aturan.
- [ ] **6A-138** Empty state publik ditangani dengan benar.
- [ ] **6A-139** Route Visi & Misi tidak dibuat sebagai bagian PHASE 6A.

### 12.2 Frontend/unit tests

- [ ] **6A-140** Form menampilkan pesan validasi dan status submit dengan benar.
- [ ] **6A-141** Sidebar Profil Paroki membuka submenu dengan active state yang benar.
- [ ] **6A-142** Komponen daftar/form menangani empty, loading, dan error state.
- [ ] **6A-143** Pengurutan linimasa dan anggota ditampilkan sesuai data props.

### 12.3 End-to-end tests

- [ ] **6A-144** Admin dapat membuka submenu Profil Paroki dan berpindah ke tiap halaman pengelolaan.
- [ ] **6A-145** Admin dapat menyimpan konten profil dan konten tersebut muncul pada halaman publik setelah dipublikasikan.
- [ ] **6A-146** Admin dapat mengelola sejarah, pastor, periode, dan anggota dengan alur utama.
- [ ] **6A-147** Pengunjung dapat menavigasi kelima halaman publik Profil Paroki.
- [ ] **6A-148** Tidak ada link publik yang mengarah ke route Visi & Misi dari bagian profil.
- [ ] **6A-149** Uji regresi untuk fitur PHASE 4 dan PHASE 5 tetap lulus.

## 13. Quality Gates dan Definition of Done

PHASE 6A **belum selesai** sampai seluruh kriteria wajib berikut terpenuhi:

- [ ] Semua route publik dalam scope menggunakan data dinamis.
- [ ] Admin Panel memiliki submenu Overview, Informasi Profil, Sejarah Paroki, Wilayah Pelayanan, Pastor Paroki, dan Struktur Kepengurusan.
- [ ] Semua operasi write tervalidasi dan diotorisasi di backend.
- [ ] Status publikasi benar-benar membatasi data publik.
- [ ] Tidak ada Visi & Misi di route, menu, field, migration, seeder, atau halaman yang dibuat pada PHASE 6A.
- [ ] Data sejarah, pastor, wilayah, dan kepengurusan tidak direkayasa.
- [ ] Integrasi media menggunakan infrastruktur yang sudah ada.
- [ ] Tidak ada duplikasi domain komunitas dari PHASE 7.
- [ ] Tidak ada regresi PHASE 4–5.
- [ ] Test suite yang relevan, typecheck, lint/format, E2E, dan production build lulus sesuai script aktual repository.
- [ ] Tampilan diperiksa pada viewport mobile sampai desktop.
- [ ] Dokumentasi dan roadmap diperbarui.
- [ ] Laporan akhir agent menyertakan file yang diubah, migrasi, route, test yang dijalankan, hasil aktual, dan keputusan yang masih tertunda.

## 14. Perintah Verifikasi

Sebelum menjalankan perintah, periksa `composer.json`, `package.json`, dan `.github/workflows/tests.yml`. Gunakan script repository yang benar, misalnya:

```bash
# Contoh pemeriksaan; sesuaikan dengan script yang benar-benar tersedia.
php artisan test
npm run typecheck
npm run build
```

Jangan menyatakan lulus jika perintah tidak dijalankan. Jika test gagal karena lingkungan atau dependensi, laporkan error, command, dan alasannya dengan jelas.

Setelah migration dibuat, verifikasi juga pada database testing/fresh database sesuai workflow proyek. Jangan menjalankan reset/destructive command pada database bersama atau production.

## 15. Urutan Implementasi yang Direkomendasikan

1. Audit repository dan tetapkan schema final.
2. Buat migration, model, relasi, scope, dan factory/seeder yang aman.
3. Implementasikan backend/Form Request/Policy.
4. Implementasikan CRUD Admin Panel.
5. Tambahkan submenu sidebar dan overview.
6. Implementasikan halaman publik dinamis.
7. Tambahkan feature, frontend, dan E2E tests.
8. Jalankan regresi PHASE 4–5, typecheck, lint/format, dan build.
9. Perbarui dokumen dan roadmap.
10. Laporkan hasil serta blocker yang masih tersisa.

**Jangan mengerjakan seluruh modul dalam satu perubahan besar tanpa checkpoint.** Selesaikan satu kelompok domain, verifikasi, lalu lanjutkan ke kelompok berikutnya.

## 16. Keputusan yang Harus Dikunci Sebelum Migration

AI agent harus memeriksa keputusan ini terhadap codebase dan meminta keputusan manusia jika tidak dapat diselesaikan dari PRD/DECISIONS:

1. Apakah profil utama memakai satu tabel singleton atau mekanisme site settings yang sudah ada?
2. Apakah narasi sejarah disimpan pada profil utama atau record terpisah?
3. Apakah pastor dan riwayat penugasan memerlukan dua tabel?
4. Apakah periode pastor disimpan sebagai tahun atau tanggal penuh?
5. Apakah wilayah pelayanan hanya narasi atau membutuhkan tabel tersendiri?
6. Apakah `is_active`, `is_published`, soft delete, dan `published_at` sudah memiliki pola baku di proyek?
7. Apa model media yang benar dan aturan FK/penghapusan yang digunakan?
8. Apakah hanya satu pastor dan satu periode kepengurusan boleh aktif pada waktu yang sama?

Jangan menambahkan kompleksitas database sebelum kebutuhan dikonfirmasi. Pilih desain terkecil yang memenuhi kebutuhan dan tetap kompatibel dengan PHASE 7–8.

## 17. Format Laporan Akhir AI Agent

Setelah pengerjaan, agent wajib melaporkan:

```text
PHASE 6A — Implementation Report

Status:
- Completed / Partially completed / Blocked

Implemented:
- ...

Database:
- Migrations:
- Models and relationships:

Routes:
- Admin:
- Public:

Authorization:
- Middleware / Policies / Permissions:

Tests executed:
- Command:
- Result:

Build / Typecheck / Lint:
- Command:
- Result:

Files changed:
- ...

Outstanding decisions / blockers:
- ...

Regression notes:
- PHASE 4:
- PHASE 5:
```

Jangan tandai phase selesai hanya karena semua file telah dibuat. Status selesai didasarkan pada acceptance criteria, hasil test, dan build yang benar-benar diverifikasi.
