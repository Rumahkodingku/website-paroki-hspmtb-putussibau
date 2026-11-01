# Spesifikasi Fitur Livestreaming

**Produk:** Website Gereja Katolik Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau  
**Dokumen:** `SPEC-LIVESTREAMING-HSPMTB.md`  
**Versi:** 1.0 — Draft untuk implementasi  
**Tanggal:** 1 November 2026  
**Basis codebase:** branch `refactor/refactor-code`  
**Repository:** [Rumahkodingku/website-paroki-hspmtb-putussibau](https://github.com/Rumahkodingku/website-paroki-hspmtb-putussibau/tree/refactor/refactor-code)

---

## 1. Tujuan Dokumen

Dokumen ini mendefinisikan kebutuhan produk, perilaku UI, aturan bisnis, model data, kontrak backend/frontend, keamanan, dan pengujian untuk fitur Livestreaming Paroki HSPMTB Putussibau. Fitur menyediakan satu pengalaman untuk:

1. Menampilkan siaran YouTube yang sedang berlangsung di beranda.
2. Menyediakan halaman khusus untuk menonton siaran aktif dan menelusuri rekaman sebelumnya.
3. Memungkinkan administrator mengelola tautan siaran dan metadata rekaman melalui CMS.
4. Memastikan konten yang tidak dipublikasikan tidak dapat diakses publik.

Dokumen ini merupakan spesifikasi implementasi fitur baru, bukan klaim bahwa fitur tersebut sudah tersedia di branch yang ditinjau.

## 2. Ringkasan Codebase yang Ditinjau

### 2.1 Stack terverifikasi

| Area             | Implementasi pada branch                                               |
| ---------------- | ---------------------------------------------------------------------- |
| Backend          | Laravel `^13.17`, PHP `^8.3`                                           |
| Frontend         | React `^19.2`, TypeScript, Inertia.js `^3.0`                           |
| Styling          | Tailwind CSS `^4`                                                      |
| Database         | MySQL 8                                                                |
| Authorization    | Laravel Gate/Policy dan Spatie Laravel Permission `^8.3`               |
| Routing frontend | Laravel Wayfinder; jangan hard-code URL aplikasi                       |
| Testing          | Pest 4, Vitest 4, Playwright                                           |
| Build            | Vite 8                                                                 |
| Timezone         | Penyimpanan datetime UTC; tampilan/perhitungan publik `Asia/Pontianak` |

### 2.2 Pola arsitektur relevan

- Route publik didefinisikan di `routes/web.php`, menggunakan controller per domain dan named route.
- Route admin berada pada grup `/admin` yang dilindungi middleware `auth`, `verified`, `EnsureAccountIsActive`, dan `EnsureAdminAccess`.
- Controller mengembalikan halaman melalui `Inertia::render()`; data publik dibentuk sebagai resource/array eksplisit, bukan mengirim raw Eloquent model.
- Modul Galeri merupakan contoh terdekat untuk daftar konten publik dan admin: `GalleryAlbum`, `GalleryPhoto`, `PublicGalleryController`, `GalleryController`, resource, Form Request, Policy, Action, serta halaman React per fitur.
- Homepage merupakan halaman agregasi. `PublicHomeController` mengambil data dari modul pemiliknya dan React menerima props terstruktur. Section yang dinonaktifkan atau tidak mempunyai data dapat dihilangkan; jangan membuat tabel/CRUD homepage untuk menyimpan ulang data modul.
- Media image memiliki pipeline tersendiri: file asli disimpan pada disk privat, diproses melalui queue menjadi variant publik, dan statusnya dicatat. Pipeline tersebut ditujukan untuk gambar, bukan untuk menyimpan file video livestream.
- `YoutubeUrl` dan `YoutubeEmbed` telah ada untuk video YouTube di Profil Paroki. `YoutubeEmbed::id()` memvalidasi host YouTube yang diizinkan dan ID video 11 karakter; `YoutubeEmbed::url()` menghasilkan URL `youtube-nocookie.com/embed/{id}`.

### 2.3 Temuan bahwa fitur belum ada

Pada route publik branch yang diperiksa belum ditemukan route `/livestreaming`; pada grup admin belum ditemukan modul CRUD livestreaming; belum ditemukan model/tabel livestreaming atau resource/halaman publiknya. Dengan demikian seluruh komponen pada dokumen ini adalah pekerjaan baru.

### 2.4 File/pola codebase yang menjadi acuan

- `routes/web.php`
- `app/Http/Controllers/Home/PublicHomeController.php`
- `app/Http/Controllers/Galeri/PublicGalleryController.php`
- `app/Http/Controllers/Galeri/GalleryController.php`
- `app/Http/Controllers/Media/MediaController.php`
- `app/Models/Media.php`
- `app/Models/GalleryAlbum.php`
- `app/Models/GalleryPhoto.php`
- `app/Rules/YoutubeUrl.php`
- `app/Support/YoutubeEmbed.php`
- `app/Models/ParishProfile.php`
- `app/Http/Controllers/Profil/PublicParishProfileController.php`
- `docs/DECISIONS.md` — terutama D-01, D-24, D-65, D-70, D-78
- `AGENTS.md` — konvensi, Wayfinder, pengujian, dan batas arsitektur

---

## 3. Ruang Lingkup Produk

### 3.1 Termasuk MVP

1. CRUD metadata livestreaming dari admin.
2. Input URL video YouTube dan ekstraksi `video_id`.
3. Thumbnail video dari YouTube atau thumbnail kustom opsional bila kebijakan produk memerlukannya.
4. Satu siaran aktif yang dapat ditampilkan di beranda dan halaman livestreaming.
5. Arsip video yang sudah selesai/rekaman yang tersedia.
6. Halaman publik `/livestreaming` dengan pencarian/filter dasar, grid video, dan pagination.
7. Pemutar video YouTube privacy-enhanced menggunakan `youtube-nocookie.com`.
8. Section live di homepage yang hanya dirender ketika ada item aktif yang valid dan dipublikasikan.
9. Menu navbar Livestreaming yang selalu tersedia, termasuk ketika tidak ada live.
10. Authorization, validasi, audit log, SEO dasar, aksesibilitas, dan pengujian.

### 3.2 Tidak termasuk MVP

- Streaming video dari server paroki atau infrastruktur RTMP/HLS milik sendiri.
- Mengunggah, mentranscoding, atau menyimpan file video penuh di server website.
- Live chat, komentar, donasi, polling, atau interaksi YouTube.
- Multi-channel/multi-platform (Facebook Live, Instagram Live, Vimeo, dan lainnya).
- Sinkronisasi otomatis melalui YouTube Data API.
- Analytics penonton real-time atau histori statistik penonton.
- Otomatisasi pembuatan jadwal siaran dari jadwal misa.
- Notifikasi email/push untuk jadwal livestream.

### 3.3 Prinsip produk

- YouTube menjadi sumber pemutaran video; aplikasi menjadi sumber metadata editorial dan visibilitas konten.
- Admin mengontrol video yang tampil di website. Website tidak boleh menganggap sebuah video sedang live hanya karena admin memberi label aktif.
- Satu siaran aktif maksimum pada satu waktu untuk satu paroki.
- Arsip hanya berisi video yang memang dimaksudkan sebagai rekaman publik dan dapat ditonton.
- Ketiadaan siaran aktif bukan error dan tidak boleh menghasilkan placeholder besar di homepage.

---

## 4. Terminologi dan Model Status

Hindari mencampur status editorial dengan status siaran YouTube.

### 4.1 Status editorial

| Status      | Makna                                                        | Akses publik                                |
| ----------- | ------------------------------------------------------------ | ------------------------------------------- |
| `draft`     | Metadata disimpan tetapi belum diterbitkan                   | Tidak                                       |
| `published` | Konten dipublikasikan                                        | Ya, sesuai jenis konten dan validitas video |
| `archived`  | Konten dikeluarkan dari daftar publik/ditarik dari publikasi | Tidak pada MVP; tetap dapat dikelola admin  |

Status editorial dapat direpresentasikan sebagai enum `status` (`draft`, `published`, `archived`) atau mengikuti pola boolean `is_published` jika keputusan implementasi ingin sangat dekat dengan modul Galeri. Rekomendasi spec ini adalah **enum editorial** karena siaran mempunyai lifecycle yang lebih kaya dari album Galeri. Pilih satu pendekatan dan gunakan konsisten.

### 4.2 Status siaran

| Status siaran | Makna                                                         | Tindakan publik                                                               |
| ------------- | ------------------------------------------------------------- | ----------------------------------------------------------------------------- |
| `scheduled`   | Siaran direncanakan                                           | Tampilkan info jadwal hanya bila fitur jadwal diaktifkan                      |
| `live`        | Siaran sedang berlangsung menurut pengelolaan admin           | Tampilkan player dan badge LIVE, setelah validasi/pemeriksaan yang ditentukan |
| `ended`       | Siaran berakhir                                               | Hilangkan dari area live; dapat masuk arsip bila rekaman tersedia             |
| `unavailable` | Video tidak tersedia/private/deleted atau tidak dapat diputar | Jangan tampilkan sebagai live; admin diberi peringatan                        |

Status `live` pada MVP adalah status operasional yang diatur admin dan perlu ditegaskan sebagai **indikator admin**, bukan verifikasi real-time YouTube. Tanpa YouTube API, backend tidak dapat memastikan status live terkini secara otoritatif. Admin harus mengakhiri status live setelah siaran selesai.

### 4.3 Aturan konsistensi

- Maksimum satu record dengan `broadcast_status = live` yang `status = published` pada waktu yang sama.
- Menetapkan record lain sebagai live harus mengakhiri/menonaktifkan record live sebelumnya dalam transaksi yang sama.
- Item dengan `status = draft` atau `archived` tidak pernah ditampilkan ke publik.
- Item `ended` hanya masuk daftar arsip bila `is_recording_available = true` dan status editorial `published`.
- Item `scheduled` tidak masuk arsip.
- Record dengan URL/video ID invalid tidak dapat diterbitkan.

---

## 5. Pengalaman Pengunjung (Public UX)

### 5.1 Navbar

Tambahkan menu **Livestreaming** pada navbar publik, sejajar dengan Beranda, Profil, Jadwal Misa, Berita & Artikel, Agenda, Pelayanan, dan Kontak.

- URL: `/livestreaming`
- Named route: `livestreaming.index`
- Menu tetap tersedia saat tidak ada siaran aktif.
- Active state mengikuti halaman `/livestreaming`.
- Pada viewport sempit, menu harus tersedia pada drawer/mobile navigation yang sudah digunakan site.
- Perbarui navigasi footer jika footer saat ini mencantumkan tautan Menu Utama; gunakan Wayfinder/named route, bukan URL hard-coded.

### 5.2 Homepage — live section

**Posisi:** tepat setelah Hero Section/quick links dan sebelum section Jadwal Liturgi (`Misa Berikutnya`), mengikuti mockup yang disetujui dalam diskusi produk.

**Judul yang disarankan:** `Ikuti Misa Secara Online`  
**Label kecil:** `SIARAN LANGSUNG`  
**Deskripsi:** `Saksikan perayaan Ekaristi dan kegiatan rohani Paroki HSPMTB Putussibau melalui siaran langsung YouTube.`

Tata letak desktop:

- Dua kolom: player sekitar 65% dan panel informasi sekitar 35%.
- Rasio player 16:9.
- Badge merah `SEDANG LIVE` terlihat pada player dan/atau panel metadata.
- Panel memuat judul, tanggal/waktu, lokasi, deskripsi singkat, dan tombol `Tonton di YouTube`.

Tata letak mobile:

- Satu kolom: player terlebih dahulu, detail setelahnya.
- Tidak boleh menyebabkan horizontal overflow.
- Tombol dan kontrol harus memiliki target sentuh yang memadai.

**Visibility rule:**

Homepage hanya mengirim/render section jika terdapat livestreaming yang memenuhi seluruh kondisi berikut:

1. Editorial status `published`.
2. Broadcast status `live`.
3. Record tidak dihapus/diarsipkan.
4. URL/ID YouTube valid.
5. Item ditandai aktif sebagai satu-satunya live utama.

Jika syarat tidak terpenuhi, `liveStream` dikirim `null` atau prop `sections.livestreaming = false`; React tidak merender section dan tidak meninggalkan ruang kosong. Ikuti pola `PublicHomeController` untuk tidak menjalankan query section ketika tidak diperlukan.

Tidak ada fallback otomatis ke rekaman lama di section live. Rekaman lama adalah konten arsip, bukan siaran langsung.

### 5.3 Halaman arsip `/livestreaming`

Halaman memakai shell publik yang sama dengan halaman Galeri, termasuk navbar, page header/breadcrumb, ilustrasi dekoratif/visual header yang konsisten, dan footer.

Urutan isi:

1. Page header:
   - Eyebrow: `LIVESTREAMING`
   - H1: `Arsip Livestreaming`
   - Deskripsi: `Saksikan kembali perayaan Ekaristi dan kegiatan rohani Paroki HSPMTB Putussibau.`
   - Breadcrumb: `Beranda > Livestreaming`
2. Live player (kondisional): tampil di bagian atas bila siaran aktif tersedia.
3. Toolbar filter:
   - Search: `Cari rekaman...`
   - Filter kategori: `Semua Kategori`
   - Filter tahun: `Semua Tahun`
4. Grid arsip:
   - Desktop 3 kolom.
   - Tablet 2 kolom.
   - Mobile 1 kolom.
   - Thumbnail 16:9 dengan tombol/play affordance.
   - Judul video.
   - Tanggal tayang/rekaman.
   - Chip kategori.
   - Tautan/tombol untuk menonton.
5. Pagination.
6. Empty state ketika filter tidak menghasilkan rekaman:
   - `Belum ada rekaman livestreaming.` atau `Tidak ada rekaman yang sesuai dengan pencarian.`
   - Jangan tampilkan area player kosong.

**Urutan arsip:** terbaru lebih dahulu, deterministik dengan `recorded_at DESC`, lalu `id DESC` sebagai tie-breaker.

**Filter MVP:**

- `search`: pencarian title/description.
- `kategori`: filter kategori jika kategori termasuk dalam skema MVP.
- `tahun`: tahun berdasarkan `recorded_at`.
- Query filter harus dipertahankan saat pagination (`withQueryString()`).

**Pagination:** 12 item per halaman (konsisten dengan daftar publik Galeri). Jangan mengirim seluruh arsip sekaligus.

### 5.4 Pemutaran dan tautan video

- Pemutar hanya menerima video ID yang diekstrak dan divalidasi server-side.
- Embed URL dibuat dengan `YoutubeEmbed::url()` sehingga menggunakan `https://www.youtube-nocookie.com/embed/{id}`.
- Jangan menerima URL iframe/embed mentah dari admin.
- Jangan membangun HTML iframe dari input bebas.
- Beri `title` iframe yang deskriptif, `loading="lazy"` pada player arsip, dan `allowfullscreen`.
- Jangan autoplay dengan suara. Untuk live homepage, utamakan klik pengguna sebelum memuat iframe agar privasi dan performa lebih baik; alternatifnya tampilkan thumbnail + tombol play dan mount iframe setelah interaksi.
- Sediakan tautan eksternal `https://www.youtube.com/watch?v={video_id}` dengan `target="_blank"` dan `rel="noopener noreferrer"` bila membuka YouTube.
- Tampilkan pesan yang jelas jika video tidak dapat diputar; jangan meninggalkan player rusak.

---

## 6. Pengalaman Admin (CMS)

### 6.1 Navigasi admin

Tambahkan item menu **Livestreaming** pada navigasi admin di kelompok yang paling relevan untuk konten publik. Menu harus menggunakan ability/permission yang dibagikan oleh pola `HandleInertiaRequests` dan tidak muncul bila pengguna tidak memiliki hak akses.

URL admin yang direkomendasikan:

| Method | URI                                           | Named route           | Fungsi                        |
| ------ | --------------------------------------------- | --------------------- | ----------------------------- |
| GET    | `/admin/livestreaming`                        | `livestreams.index`   | Daftar/live/archive/draft     |
| GET    | `/admin/livestreaming/tambah`                 | `livestreams.create`  | Form tambah                   |
| POST   | `/admin/livestreaming`                        | `livestreams.store`   | Simpan                        |
| GET    | `/admin/livestreaming/{livestream}/ubah`      | `livestreams.edit`    | Form edit                     |
| PATCH  | `/admin/livestreaming/{livestream}`           | `livestreams.update`  | Perbarui                      |
| DELETE | `/admin/livestreaming/{livestream}`           | `livestreams.destroy` | Hapus/soft delete             |
| POST   | `/admin/livestreaming/{livestream}/mulai`     | `livestreams.start`   | Tandai mulai/live             |
| POST   | `/admin/livestreaming/{livestream}/akhiri`    | `livestreams.end`     | Akhiri live                   |
| POST   | `/admin/livestreaming/{livestream}/terbitkan` | `livestreams.publish` | Publikasikan                  |
| POST   | `/admin/livestreaming/{livestream}/arsipkan`  | `livestreams.archive` | Tarik dari publikasi/arsipkan |

Route action status harus POST agar aksi eksplisit dan dapat diotorisasi; hindari mengubah `broadcast_status` melalui field bebas pada PATCH umum.

### 6.2 Daftar admin

Kolom minimum:

- Thumbnail.
- Judul.
- Kategori.
- Tanggal/jadwal.
- Status editorial.
- Status siaran.
- Updated at.
- Aksi edit, mulai/akhiri, terbitkan/arsipkan, hapus sesuai permission.

Fitur daftar:

- Search judul.
- Filter status editorial.
- Filter status siaran.
- Filter kategori/tahun jika relevan.
- Sorting server-side dengan allowlist kolom, menggunakan pola `SortsAdminList`.
- Pagination server-side.
- Default order: updated terbaru atau jadwal/rekaman terbaru dengan tie-breaker stabil.
- Badge status memakai label dari backend/enum, bukan mapping teks yang diduplikasi di banyak komponen.

### 6.3 Form tambah/ubah

Field form:

| Field                         | Wajib               | Aturan                                                                              |
| ----------------------------- | ------------------- | ----------------------------------------------------------------------------------- |
| Judul                         | Ya                  | String, 3–150 karakter                                                              |
| URL YouTube                   | Ya                  | URL YouTube valid; max 500 karakter; harus menghasilkan video ID 11 karakter        |
| Deskripsi                     | Tidak               | String, max 1.000 karakter; plain text untuk MVP                                    |
| Kategori                      | Tidak/MVP keputusan | Referensi kategori yang tersedia, atau enum sederhana                               |
| Tanggal/jadwal siaran         | Tidak               | Datetime lokal paroki, dikonversi dan disimpan UTC bila berupa instant              |
| Waktu rekaman (`recorded_at`) | Tidak               | Diperlukan untuk urutan/tahun arsip; dapat diisi admin atau default ketika diakhiri |
| Thumbnail kustom              | Tidak               | Opsional; gunakan media image siap (`MediaStatus::Ready`) bila diimplementasikan    |
| Publikasi                     | Ya pada saat terbit | Draft/published/archive sesuai model status                                         |
| Catatan admin                 | Tidak               | Internal; tidak pernah dikirim ke publik                                            |

UI form sebaiknya memakai pola halaman `tambah` dan `ubah` yang sudah digunakan modul Galeri/Berita. Preview video/thumbnail boleh ditampilkan setelah URL berhasil divalidasi.

### 6.4 Aksi mulai dan akhiri siaran

**Mulai siaran**

- Hanya untuk record yang siap dan editorial `published`.
- Meminta konfirmasi sebelum mengganti live aktif yang lain.
- Backend memakai transaksi + lock yang sesuai untuk memastikan hanya satu live utama.
- Jika ada live lain, admin harus mengonfirmasi bahwa siaran sebelumnya akan diakhiri; jangan diam-diam mengganti tanpa feedback.
- Menetapkan `broadcast_status = live`, `started_at = now()`.
- Catat audit event.

**Akhiri siaran**

- Menetapkan `broadcast_status = ended`, `ended_at = now()`.
- Jika rekaman tersedia, set `is_recording_available = true` dan tetapkan `recorded_at` (default `ended_at`, dapat dikoreksi admin).
- Menghilangkan video dari section live homepage.
- Memasukkan ke arsip publik hanya bila editorial status published dan rekaman tersedia.
- Catat audit event.

**Penting:** aksi ini mengubah status operasional internal, bukan memulai atau menghentikan encoder/stream YouTube. Siaran harus dijalankan di YouTube Studio/perangkat streaming.

### 6.5 Permission dan authorization

Permission yang direkomendasikan:

- `livestreams.view`
- `livestreams.create`
- `livestreams.update`
- `livestreams.delete`
- `livestreams.publish` (opsional, hanya jika publikasi ingin dipisah dari update)

Untuk konsistensi dengan D-65, bila publikasi diperlakukan sebagai field biasa dalam update, cukup empat permission CRUD dan tidak perlu `publish`. Namun aksi mulai/akhiri harus memerlukan `livestreams.update` atau ability spesifik yang diturunkan dari policy.

Semua operasi harus melalui `LivestreamPolicy`/Gate. Jangan hanya menyembunyikan tombol di React. Public controller hanya mengambil record `published` melalui scope bersama.

### 6.6 Audit log

Catat minimal:

- `created`, `updated`, `deleted`.
- `published` / `archived` jika perubahan tersebut merupakan aksi tersendiri.
- `broadcast_started` dan `broadcast_ended`.
- Perubahan video ID atau URL.
- Penggantian live utama.

Ikuti `AuditLogger` dan allowlist `config/audit.php`. Jangan simpan API key/token YouTube di audit metadata.

---

## 7. Model Data dan Database

### 7.1 Tabel `livestreams`

Rekomendasi kolom MVP:

| Kolom                    | Tipe (MySQL)     | Nullable | Keterangan                                              |
| ------------------------ | ---------------- | -------: | ------------------------------------------------------- |
| `id`                     | BIGINT UNSIGNED  |    Tidak | Primary key                                             |
| `title`                  | VARCHAR(150)     |    Tidak | Judul yang ditampilkan                                  |
| `slug`                   | VARCHAR(180)     |    Tidak | Slug publik opsional; tidak wajib jika hanya index/list |
| `description`            | TEXT             |       Ya | Deskripsi singkat plain text                            |
| `youtube_video_id`       | VARCHAR(11)      |    Tidak | ID YouTube hasil ekstraksi URL                          |
| `youtube_url`            | VARCHAR(500)     |    Tidak | URL kanonis/watch URL yang disimpan; bukan iframe URL   |
| `thumbnail_media_id`     | BIGINT UNSIGNED  |       Ya | FK ke media bila thumbnail kustom dipakai               |
| `category`               | VARCHAR(50) / FK |       Ya | Kategori editorial MVP; tentukan enum atau master table |
| `broadcast_status`       | VARCHAR/ENUM     |    Tidak | `scheduled`, `live`, `ended`, `unavailable`             |
| `status`                 | VARCHAR/ENUM     |    Tidak | `draft`, `published`, `archived`                        |
| `scheduled_at`           | DATETIME         |       Ya | Jadwal tayang, disimpan UTC                             |
| `started_at`             | DATETIME         |       Ya | Waktu mulai yang dicatat aplikasi, UTC                  |
| `ended_at`               | DATETIME         |       Ya | Waktu selesai, UTC                                      |
| `recorded_at`            | DATETIME         |       Ya | Waktu rekaman untuk daftar arsip, UTC                   |
| `is_recording_available` | BOOLEAN          |    Tidak | Default false                                           |
| `published_at`           | DATETIME         |       Ya | Waktu publikasi pertama, disetel server                 |
| `created_by`             | BIGINT UNSIGNED  |       Ya | FK user, set null saat user dihapus                     |
| `updated_by`             | BIGINT UNSIGNED  |       Ya | FK user, set null saat user dihapus                     |
| `created_at`             | TIMESTAMP        |    Tidak | Laravel timestamps                                      |
| `updated_at`             | TIMESTAMP        |    Tidak | Laravel timestamps                                      |
| `deleted_at`             | TIMESTAMP        |       Ya | Soft deletes                                            |

Catatan keputusan:

1. `youtube_video_id` adalah field penting dan menjadi sumber embed; `youtube_url` disimpan untuk mempertahankan link sumber/canonical admin. Bisa juga hanya menyimpan satu kolom URL dan derive ID saat render, tetapi menyimpan ID terpisah mempermudah query/validasi dan tidak mengubahnya setiap kali.
2. Jangan simpan `embed_url` sebagai input admin. Derive melalui `YoutubeEmbed`.
3. Jangan simpan file video di tabel Media.
4. `slug` dapat dihilangkan pada MVP karena daftar arsip dapat membuka modal/player detail melalui `?video={id}` atau route show berbasis ID. Jika SEO membutuhkan halaman detail yang dapat dibagikan, gunakan slug unik.
5. `recorded_at` mewakili tanggal rekaman, bukan upload record ke website. Gunakan UTC datetime dan render timezone `app.display_timezone`.
6. Kategori dapat berupa enum awal (`liturgi`, `hari_raya`, `devosi`, `kegiatan`) agar tidak menambah master-data module. Bila admin perlu mengelola kategori, buat master data setelah kebutuhan nyata muncul.

### 7.2 Index dan constraint

- Index pada `status`, `broadcast_status`, `recorded_at`, `scheduled_at`.
- Index gabungan untuk query arsip: `(status, broadcast_status, is_recording_available, recorded_at)`.
- Unique index untuk `slug` jika slug digunakan; aturan uniqueness tetap berlaku setelah soft delete sesuai konvensi project untuk route publik.
- FK `thumbnail_media_id` → `media.id` dengan `nullOnDelete()` bila thumbnail kustom diterapkan.
- FK `created_by`/`updated_by` → `users.id` dengan `nullOnDelete()`.
- Terapkan invariant satu live aktif pada level aplikasi dengan transaksi dan lock; unique partial index tidak tersedia secara konsisten pada MySQL untuk pola ini. Pertimbangkan tabel singleton/current live bila concurrency menuntut jaminan database yang lebih kuat.

### 7.3 Kategori

Kategori awal yang direkomendasikan:

- `liturgi` — Misa Minggu/Misa harian.
- `hari_raya` — Natal, Paskah, Pentakosta, hari raya.
- `devosi` — Rosario, Adorasi, doa bersama.
- `kegiatan` — perayaan paroki dan kegiatan lain.

Simpan slug stabil dan label Indonesia melalui enum/config atau master table. Jangan mencampur kategori dengan `broadcast_status`.

### 7.4 Factory dan seeder

- Sediakan `LivestreamFactory` dengan state `draft`, `published`, `live`, `ended`, `recordingAvailable`.
- Seed kategori hanya bila kategori master table dipilih dan sesuai aturan repo untuk tidak memasukkan data demo ke installer produksi.
- Jangan membuat data contoh livestreaming secara otomatis pada instalasi produksi.

---

## 8. Aturan Domain dan Use Cases

### 8.1 Use case admin

1. Admin membuat draft dengan URL YouTube.
2. Backend memvalidasi URL dan mengekstrak video ID.
3. Admin mengisi metadata dan menerbitkan konten.
4. Admin memilih `Mulai Siaran` saat stream memang telah dimulai di YouTube.
5. Homepage menampilkan siaran aktif.
6. Admin memilih `Akhiri Siaran` setelah live benar-benar berakhir.
7. Jika YouTube menyimpan replay, admin menandai rekaman tersedia.
8. Rekaman muncul di halaman arsip.

### 8.2 Validasi URL YouTube

- Gunakan `App\Rules\YoutubeUrl` yang ada; jangan menulis parser lain yang berbeda.
- Pastikan URL hanya berasal dari host yang diizinkan `YoutubeEmbed`.
- Bentuk URL embed melalui `YoutubeEmbed::url()`.
- Tolak URL selain YouTube, URL tanpa video ID yang valid, dan URL dengan host tiruan seperti `youtube.com.evil.test`.
- Terima format yang sudah didukung helper: `youtube.com/watch?v=...`, `youtu.be/...`, `/embed/...`, `/shorts/...`, `/live/...` pada host allowlist.
- Simpan ID 11 karakter; jangan percaya input `youtube_video_id` dari browser bila ID dapat diekstrak dari `youtube_url` server-side.

### 8.3 Jadwal dan timezone

- `app.timezone` tetap UTC.
- Gunakan `app.display_timezone` (`Asia/Pontianak`) untuk input/tampilan tanggal lokal.
- Convert local datetime ke UTC sebelum persist.
- Semua datetime yang dikirim ke React berupa ISO 8601 yang jelas timezone-nya.
- Hindari mengubah event datetime menjadi string tanggal tanpa timezone sebelum konversi.

### 8.4 Ketersediaan rekaman

YouTube archive/replay bergantung pada pengaturan kanal dan batasan YouTube; aplikasi tidak boleh mengasumsikan setiap livestream otomatis menjadi replay. Admin secara eksplisit menandai `is_recording_available` setelah memastikan rekaman dapat ditonton.

### 8.5 Unavailable/private/deleted

Pada MVP tidak ada pemeriksaan status YouTube otomatis. Admin dapat mengubah broadcast status menjadi `unavailable` atau menonaktifkan publikasi bila video tidak dapat diputar. Halaman publik tidak boleh membocorkan draft atau konten privat.

---

## 9. Backend Design

### 9.1 Struktur file yang disarankan

Ikuti struktur domain yang sudah ada. Nama dapat diselaraskan dengan generator/konvensi repo saat implementasi.

```text
app/
├── Enums/
│   ├── LivestreamEditorialStatus.php
│   └── LivestreamBroadcastStatus.php
├── Models/
│   └── Livestream.php
├── Policies/
│   └── LivestreamPolicy.php
├── Actions/
│   └── Livestream/
│       ├── SaveLivestreamAction.php
│       ├── StartLivestreamAction.php
│       └── EndLivestreamAction.php
├── Http/
│   ├── Controllers/
│   │   └── Livestream/
│   │       ├── LivestreamController.php
│   │       └── PublicLivestreamController.php
│   ├── Requests/
│   │   └── Livestream/
│   │       ├── StoreLivestreamRequest.php
│   │       └── UpdateLivestreamRequest.php
│   └── Resources/
│       ├── Livestream/
│       └── Public/Livestream/
└── Services/
    └── LivestreamService.php   # hanya bila ada aturan lintas use case

resources/js/features/
├── admin/livestreaming/
│   ├── components/
│   ├── pages/
│   └── types.ts
└── public/livestreaming/
    ├── components/
    ├── pages/
    └── types.ts
```

Jangan membuat Service/Action tambahan hanya untuk memindahkan satu baris kode. `SaveLivestreamAction` direkomendasikan bila penyimpanan juga mengelola slug, published_at, actor, dan audit; Start/End Action diperlukan untuk transaksi/invariant live tunggal.

### 9.2 Controller publik

`PublicLivestreamController`:

- `index(Request $request)` mengirim `Inertia::render('public/livestreaming', ...)`.
- Query arsip hanya mengambil `published`, `ended`, `is_recording_available = true`.
- `withQueryString()` untuk pagination.
- `availableYears()` hanya menghitung tahun dari record yang memang public.
- Jika live item ada, kirim resource yang sama dengan homepage.
- Hindari N+1 dan gunakan resource untuk membatasi field.

### 9.3 Controller homepage

Perluas `PublicHomeController` sebagai aggregator:

- Resolve satu `currentLiveStream()` dari model Livestream.
- Query hanya record `published + live`.
- Kirim `liveStream` sebagai object/null dan `sections.livestreaming`.
- Jangan mengambil banyak arsip untuk homepage dalam MVP.
- Jangan membuat controller/table `HomeLivestream` terpisah.

### 9.4 Controller admin

`LivestreamController` mengikuti pola `GalleryController`:

- `Gate::authorize()` untuk setiap aksi atau Form Request `authorize()`.
- Search/filter/sort server-side.
- Form meta dikirim lewat method khusus bila dibutuhkan.
- Mutasi bisnis dilakukan lewat Action untuk menghindari controller menanggung transaksi dan audit.
- `Inertia::flash('toast', ...)` untuk feedback sesuai pola project.

### 9.5 Resources

`AdminLivestreamResource` minimum mengirim:

- `id`, `title`, `description`, `youtube_url`, `youtube_video_id`, `thumbnail_url`, `category`, `status`, `status_label`, `broadcast_status`, `broadcast_status_label`, `scheduled_at`, `started_at`, `ended_at`, `recorded_at`, `is_recording_available`, `published_at`, `updated_at`.

`PublicLivestreamCardResource` hanya mengirim:

- `id`, `title`, `slug` bila ada, `description` ringkas, `video_id`, `embed_url`, `watch_url`, `thumbnail_url`, `category`, `category_label`, `recorded_at` ISO 8601, `recorded_date_label` bila server membentuknya.

Jangan kirim `created_by`, `updated_by`, `deleted_at`, catatan admin, token/secret, atau atribut internal lain ke publik.

---

## 10. Routing dan Kontrak Halaman

### 10.1 Route publik

```php
Route::get('/livestreaming', [PublicLivestreamController::class, 'index'])
    ->name('livestreaming.index');
```

Jika halaman detail video dibutuhkan untuk SEO/shareable URLs:

```php
Route::get('/livestreaming/{livestream:slug}', [PublicLivestreamController::class, 'show'])
    ->name('livestreaming.show');
```

`show` hanya mem-bind record published yang rekamannya tersedia atau sedang live. Draft/unavailable/archived yang tidak dimaksudkan publik harus 404.

### 10.2 Route admin

Daftar route pada §6.1 diletakkan di dalam grup middleware admin. Untuk parameter route gunakan model binding sesuai konvensi Laravel dan Wayfinder.

### 10.3 Inertia props halaman arsip

Contoh kontrak konseptual:

```ts
export type PublicLivestream = {
    id: number;
    title: string;
    description: string | null;
    video_id: string;
    embed_url: string;
    watch_url: string;
    thumbnail_url: string;
    category: string | null;
    category_label: string | null;
    recorded_at: string;
};

export type LivestreamArchivePageProps = {
    activeStream: PublicLivestream | null;
    archives: PaginatedData<PublicLivestream>;
    years: number[];
    categories: Array<{ value: string; label: string }>;
    filters: {
        search: string;
        kategori: string;
        tahun: string;
    };
};
```

Bentuk pagination harus mengikuti resource pagination yang sudah digunakan modul Galeri dan jangan dipaksakan menjadi plain array bila backend mengirim paginator.

### 10.4 Inertia props homepage

```ts
export type HomeSections = {
    // existing flags...
    livestreaming: boolean;
};

export type HomepageProps = {
    // existing props...
    liveStream: PublicLivestream | null;
    sections: HomeSections;
};
```

`HomeSections` harus diperbarui tanpa menghapus properti existing.

---

## 11. UI Component Specification

### 11.1 Komponen publik

Komponen yang direkomendasikan:

- `LivestreamPlayer`: iframe privacy-enhanced atau thumbnail-to-player interaction.
- `LivestreamLiveSection`: wrapper homepage, hanya menerima live stream valid.
- `LivestreamArchiveCard`: thumbnail, judul, tanggal, kategori, play affordance.
- `LivestreamArchiveFilters`: query search/category/year yang sinkron dengan URL.
- `LivestreamStatusBadge`: label live/rekaman bila diperlukan.

### 11.2 Komponen admin

- `livestream-form.tsx`: form tambah/ubah.
- `livestream-status-badge.tsx`: status editorial dan status siaran terpisah.
- `livestream-columns.tsx`: kolom daftar.
- `livestream-preview.tsx`: preview thumbnail/player setelah URL valid.
- `confirm-start-livestream-dialog.tsx`: konfirmasi jika ada siaran aktif lain.
- `confirm-end-livestream-dialog.tsx`: konfirmasi mengakhiri live.

Reuse primitives dari `components/ui`, input/select/dialog yang sudah ada, dan media upload client yang sudah ada hanya untuk thumbnail image opsional.

### 11.3 Desain visual

Mengacu pada halaman Galeri yang diberikan:

- Background utama putih.
- Tipografi, lebar konten, spacing, header/breadcrumb, navbar, footer mengikuti komponen publik yang telah ada.
- Warna aksen merah paroki untuk eyebrow, active navigation, play/live badge, chip kategori, dan CTA.
- Header dekoratif menggunakan gaya ilustrasi arch/garis merah tipis yang dipakai halaman Galeri (gunakan aset/komponen yang ada bila tersedia, jangan membuat variasi yang tidak konsisten).
- Thumbnail 16:9, card border tipis dan radius konsisten dengan Gallery card.
- Jangan menaruh seluruh halaman dalam card besar; biarkan section punya whitespace yang serupa halaman Galeri.
- Di mobile, filter boleh menumpuk vertikal dan grid satu kolom.

---

## 12. YouTube Integration Strategy

### 12.1 MVP — manual curation

MVP tidak memerlukan YouTube API key atau OAuth. Admin memasukkan URL YouTube setiap siaran/rekaman, lalu aplikasi menyimpan ID dan metadata editorial.

Kelebihan:

- Implementasi dan operasi sederhana.
- Tidak ada secret/API quota untuk dikelola.
- Tidak ada ketergantungan pada channel ID atau OAuth consent.
- Cocok untuk jumlah siaran paroki yang terbatas.

Konsekuensi:

- Admin harus menekan Mulai/Akhiri secara manual.
- Admin harus memastikan replay tersedia sebelum memasukkannya ke arsip.
- Status YouTube tidak terverifikasi otomatis.

### 12.2 Future enhancement — YouTube Data API

Integrasi API dapat dipertimbangkan setelah MVP stabil untuk:

- Mengambil metadata video dan thumbnails.
- Memeriksa status live/replay secara otomatis bila API dan channel setup mendukung.
- Sinkronisasi video dari channel/playlist arsip.

Kebutuhan lanjutan yang harus ditentukan sebelum implementasi:

- YouTube channel ID resmi paroki.
- Apakah channel milik paroki dikelola melalui Google account yang dapat memberi OAuth consent.
- Credential strategy, secret storage, token refresh, quota, retry/backoff, dan rate limit.
- Sinkronisasi incremental, idempotency, serta kebijakan video yang dihapus/private.
- Pemisahan status API dan status editorial admin.

Tidak boleh mengklaim status `live` otomatis hanya dari keberadaan video ID. Implementasi API harus memetakan respons API yang sebenarnya dan menangani quota/error.

---

## 13. Keamanan, Privasi, dan Ketahanan

1. Validasi host YouTube melalui helper yang sudah ada; jangan mengizinkan arbitrary iframe URL.
2. Gunakan `youtube-nocookie.com` untuk privacy-enhanced embedding.
3. Tidak ada API key di frontend, HTML, props Inertia, atau log.
4. Semua route admin dilindungi middleware admin dan Policy.
5. Semua request write divalidasi melalui Form Request.
6. Escape judul/deskripsi sebagai plain text; rich HTML tidak diperlukan untuk MVP.
7. Throttle endpoint publik yang bisa disalahgunakan jika ada endpoint detail/resolve; halaman daftar GET umumnya mengikuti kebijakan rate limit global.
8. Gunakan soft delete untuk record agar penghapusan dapat diaudit/dipulihkan sesuai pola modul.
9. Jangan memuat iframe untuk seluruh grid; thumbnail saja sampai pengguna membuka video. Ini mengurangi third-party requests dan beban halaman.
10. Thumbnail dari YouTube adalah remote asset. Sediakan fallback image bila URL thumbnail gagal. Jika ingin meng-cache thumbnail lokal, gunakan pipeline Media image dengan mempertimbangkan ketentuan YouTube dan kebijakan cache; jangan menyimpan ulang gambar tanpa memastikan hak/persyaratan penggunaan.
11. Jangan mengandalkan status `live` manual sebagai verifikasi YouTube. Tampilkan label live hanya jika admin telah mengonfirmasi status dan record memenuhi aturan publish.
12. Audit log allowlist metadata dan tidak mencatat credential.

---

## 14. SEO dan Accessibility

### SEO

- Halaman arsip mempunyai title dan meta description unik.
- H1 tunggal: `Arsip Livestreaming`.
- Card tidak mengulang H1.
- Jika detail page digunakan, title/description/canonical harus berasal dari metadata record yang published.
- Open Graph image menggunakan thumbnail yang valid atau fallback paroki.
- Draft/unavailable video tidak boleh masuk sitemap.
- Structured data `VideoObject` dapat dipertimbangkan untuk detail page setelah metadata duration/uploadDate/embedUrl tersedia; jangan mengarang duration atau upload date.

### Accessibility

- Thumbnail memiliki alt text yang bermakna; bila thumbnail murni dekoratif, gunakan alt kosong dan pastikan judul card menjadi accessible name.
- Tombol play harus berupa button/link yang bisa diakses keyboard, dengan accessible label seperti `Putar Misa Minggu 1 November 2026`.
- Iframe mempunyai `title` deskriptif.
- Badge live tidak hanya dibedakan dengan warna; teks `SEDANG LIVE` harus terlihat.
- Filter mempunyai label eksplisit dan state fokus terlihat.
- Empty state diumumkan dengan struktur heading/teks yang masuk akal.
- Hormati reduced motion; tidak ada animasi berkedip pada badge live.

---

## 15. Performance

- Arsip dipaginasi 12 item per halaman.
- Daftar hanya memuat thumbnail dan metadata; jangan mount semua iframe.
- Gunakan eager/lazy loading sesuai posisi: hero active player dapat dimuat setelah interaksi, thumbnail grid lazy-load.
- Query memakai select kolom relevan, eager-load relasi thumbnail Media bila digunakan, dan menghindari N+1.
- Tambahkan index yang menunjang filter dan sorting.
- Cache query homepage active stream dengan invalidation saat mulai/akhiri/publish/update/delete. TTL pendek (mis. 30–60 detik) dapat digunakan, tetapi cache tidak boleh membuat live lama tetap muncul terlalu lama setelah admin mengakhirinya; invalidation event/action wajib.
- Jangan menggunakan API YouTube pada setiap page request dalam MVP.

---

## 16. Acceptance Criteria

### 16.1 Admin

- [ ] Pengguna tanpa permission tidak dapat mengakses daftar/form/aksi Livestreaming, termasuk lewat URL langsung.
- [ ] Admin dapat membuat draft dengan URL YouTube valid.
- [ ] URL dari host non-YouTube atau URL dengan ID invalid ditolak dengan pesan validasi.
- [ ] Video ID diekstrak server-side dan embed URL tidak berasal dari input bebas.
- [ ] Admin dapat mengubah metadata dan menyimpan perubahan.
- [ ] Admin dapat menerbitkan dan menarik/mengarsipkan konten sesuai permission.
- [ ] Tidak ada lebih dari satu siaran aktif pada waktu yang sama, termasuk saat dua request mulai live bersamaan.
- [ ] Admin mendapat konfirmasi saat memulai live baru sementara live lain aktif.
- [ ] Aksi akhiri live menghapus live dari homepage.
- [ ] Rekaman hanya muncul di arsip setelah ditandai tersedia dan published.
- [ ] Perubahan penting tercatat di Audit Log.

### 16.2 Homepage

- [ ] Section live berada setelah hero/quick links dan sebelum Jadwal Liturgi.
- [ ] Section tampil hanya jika ada record `published + live` yang valid.
- [ ] Jika tidak ada live, section tidak dirender dan tidak menyisakan whitespace.
- [ ] Menu Livestreaming tetap terlihat walau tidak ada live.
- [ ] Homepage tidak menampilkan arsip sebagai pengganti live.
- [ ] Data yang dikirim tidak memuat field internal.

### 16.3 Halaman arsip

- [ ] `/livestreaming` dapat diakses tanpa login.
- [ ] Halaman mengikuti shell dan pola visual halaman Galeri.
- [ ] Siaran aktif, jika ada, tampil sebelum grid arsip.
- [ ] Hanya published recordings yang tampil pada grid.
- [ ] Urutan terbaru ke terlama konsisten.
- [ ] Pencarian/filter kategori/tahun bekerja dan query dipertahankan saat pagination.
- [ ] Pagination bekerja dengan 12 item per halaman.
- [ ] Empty state tampil ketika tidak ada data atau hasil filter kosong.
- [ ] Video dapat diputar melalui YouTube privacy-enhanced embed atau dibuka melalui YouTube.
- [ ] Layout responsif pada desktop/tablet/mobile.

### 16.4 Keamanan dan kualitas

- [ ] Route admin menggunakan middleware yang sama dengan modul admin lainnya.
- [ ] Semua aksi dilindungi Policy/Gate di backend.
- [ ] Semua field input divalidasi server-side.
- [ ] Tidak ada API secret di client bundle atau props.
- [ ] Test memastikan draft/archived/unavailable tidak dapat diakses publik.
- [ ] Test memastikan satu live aktif saja.
- [ ] Tidak ada N+1 query pada daftar arsip.
- [ ] Lint, typecheck, feature tests, unit/component tests lulus.

---

## 17. Test Plan

### 17.1 Backend Feature Tests (Pest)

File yang disarankan:

- `tests/Feature/Livestreaming/LivestreamSchemaTest.php`
- `tests/Feature/Livestreaming/LivestreamAuthorizationTest.php`
- `tests/Feature/Livestreaming/LivestreamCrudTest.php`
- `tests/Feature/Livestreaming/LivestreamStateTransitionTest.php`
- `tests/Feature/Livestreaming/PublicLivestreamArchiveTest.php`
- `tests/Feature/Public/PublicLivestreamHomepageTest.php`

Kasus minimum:

1. Schema/factory/status defaults.
2. Permission view/create/update/delete/publish.
3. URL YouTube valid/invalid dan ekstraksi ID.
4. Draft tidak muncul di publik.
5. Published + ended + recording available muncul pada arsip.
6. Ended tanpa replay tidak muncul pada arsip.
7. Scheduled tidak muncul sebagai archive.
8. Archived/unavailable/soft-deleted tidak muncul pada publik.
9. Satu live aktif saja; start live kedua menolak atau meminta transisi terkonfirmasi.
10. Start/end mengisi timestamp yang tepat.
11. Homepage live section tampil/hilang sesuai aturan.
12. Filter tahun/search/kategori dan pagination.
13. Query hasil publik tidak mengirim `created_by`, `updated_by`, `deleted_at`, catatan internal.
14. Thumbnail Media hanya menggunakan media ready/public variant bila thumbnail upload dipakai.
15. Audit event dibuat pada transaksi sukses dan tidak dibuat bila transaksi gagal.

### 17.2 Frontend tests (Vitest + Testing Library)

File yang disarankan:

- `tests/js/features/public/livestreaming/livestream-archive.test.tsx`
- `tests/js/features/public/livestreaming/livestream-player.test.tsx`
- `tests/js/features/public/beranda/home-livestream-section.test.tsx`
- `tests/js/features/admin/livestreaming/livestream-form.test.tsx`
- `tests/js/features/admin/livestreaming/livestream-state-actions.test.tsx`

Kasus minimum:

- Card menampilkan title, thumbnail, tanggal, kategori.
- Filter dikontrol dari props/URL dan empty state muncul.
- Homepage section tidak dirender jika `liveStream = null` atau disabled.
- Player tidak mount iframe sebelum interaksi bila pola click-to-load dipakai.
- Iframe title/URL privacy-enhanced benar.
- Dialog konfirmasi start/end menjalankan aksi yang sesuai.
- Tombol/label memiliki accessible name.

### 17.3 E2E Playwright

Skenario utama:

1. Admin login → tambah draft livestream → publish.
2. Admin mulai live → homepage menunjukkan section LIVE.
3. Pengunjung membuka `/livestreaming` → player aktif terlihat.
4. Admin akhiri live dan tandai replay tersedia → section live hilang, rekaman muncul di arsip.
5. Pengunjung filter tahun/kategori dan membuka rekaman.
6. User tanpa permission mencoba akses admin → 403/redirect sesuai pola auth project.

Jalankan test yang terfokus terlebih dahulu; jangan menambah E2E untuk setiap state kecil yang sudah terbukti melalui feature/component tests.

---

## 18. Implementation Plan

### Phase 1 — Domain & Persistence

- Finalisasi status editorial/broadcast dan field wajib.
- Migration, model, casts, scopes, relationships, factory.
- Policy dan permission seeder.
- `YoutubeUrl`/`YoutubeEmbed` reuse.
- Unit/domain tests untuk status dan transisi.

### Phase 2 — Admin CMS

- Route/controller/Form Request/resource.
- Save/Start/End actions dan AuditLogger.
- Halaman daftar, tambah, ubah, confirmation dialog.
- Wayfinder generation dan admin nav.

### Phase 3 — Public Archive

- Public controller, resource, `/livestreaming` route.
- Filter, year options, pagination.
- Archive grid/card/player/empty state.
- SEO/accessibility.

### Phase 4 — Homepage Integration

- `PublicHomeController` query active stream.
- Props/types dan `HomeLivestreamSection`.
- Posisi section sesuai keputusan UI.
- Conditional rendering dan invalidation cache.
- Navbar/footer links.

### Phase 5 — Verification & Release

- Pest, Vitest, E2E targeted.
- Responsive check desktop/tablet/mobile.
- Verify Wayfinder output regenerated, lint, format, typecheck.
- Verify audit events and permission behavior.
- Update `docs/DECISIONS.md` untuk keputusan baru yang mempengaruhi arsitektur.

---

## 19. Keputusan yang Harus Dikunci Sebelum Implementasi

Berikut keputusan terbuka yang memengaruhi skema dan UI:

| #   | Keputusan                                                   | Rekomendasi MVP                                                                                             |
| --- | ----------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------- |
| 1   | Bagaimana admin mengetahui live sudah benar-benar berjalan? | Admin mulai/akhiri manual setelah memeriksa YouTube Studio; jangan klaim real-time verification             |
| 2   | Apakah hanya satu livestream aktif?                         | Ya, satu live utama per paroki                                                                              |
| 3   | Apakah semua rekaman otomatis masuk arsip?                  | Tidak; admin harus menandai replay tersedia dan publish                                                     |
| 4   | Apakah halaman detail video dibutuhkan?                     | Tunda; kartu dapat memutar modal/player. Tambah slug/detail bila SEO/shareable page dibutuhkan              |
| 5   | Kategori dikelola admin atau tetap?                         | Enum kategori MVP; master data kemudian bila dibutuhkan                                                     |
| 6   | Thumbnail menggunakan YouTube atau upload lokal?            | YouTube thumbnail default; upload lokal opsional setelah mempertimbangkan hak penggunaan                    |
| 7   | Apakah scheduled stream perlu muncul di homepage?           | Tidak pada MVP; homepage section hanya live aktif. Jadwal mendatang bisa ditambahkan sebagai fitur lanjutan |
| 8   | Integrasi YouTube API?                                      | Tunda sampai kebutuhan channel sync/status verification jelas                                               |
| 9   | Siapa yang boleh start/end/publish?                         | Pengguna dengan `livestreams.update`; publish dapat dipisah bila workflow approval diperlukan               |

---

## 20. Batasan dan Risiko yang Diketahui

- **Status live manual dapat terlambat diperbarui.** Mitigasi MVP: admin wajib mengakhiri live; dashboard admin menampilkan status yang aktif secara menonjol; sediakan timestamp mulai.
- **Replay YouTube tidak selalu tersedia.** Mitigasi: jangan otomatis menganggap live berakhir berarti replay tersedia; admin memverifikasi sebelum memasukkan arsip.
- **Video dapat menjadi private/deleted/age-restricted.** Mitigasi: admin dapat menandai unavailable dan mengeluarkan dari publik; API check dapat menjadi fase berikutnya.
- **Concurrency dua admin.** Mitigasi: transaction/row lock dan pengecekan ulang saat start action.
- **Third-party iframe berdampak privasi/performa.** Mitigasi: `youtube-nocookie.com`, thumbnail-first, lazy-load/click-to-load.
- **Timezone.** Mitigasi: UTC di database, `Asia/Pontianak` di antarmuka dan perhitungan.
- **Thumbnail remote.** Mitigasi: fallback image dan ukuran/responsive image; evaluasi kebijakan sebelum menyimpan salinan lokal.

---

## 21. Referensi

### Referensi internal repository

- [Branch refactor/refactor-code](https://github.com/Rumahkodingku/website-paroki-hspmtb-putussibau/tree/refactor/refactor-code)
- `routes/web.php`
- `app/Http/Controllers/Home/PublicHomeController.php`
- `app/Http/Controllers/Galeri/PublicGalleryController.php`
- `app/Http/Controllers/Galeri/GalleryController.php`
- `app/Http/Controllers/Media/MediaController.php`
- `app/Rules/YoutubeUrl.php`
- `app/Support/YoutubeEmbed.php`
- `docs/DECISIONS.md` — D-01, D-24, D-65, D-70, D-78
- `AGENTS.md`

### Referensi eksternal resmi

- [YouTube Help — Archive live streams](https://support.google.com/youtube/answer/6247592)
- [YouTube Help — Promote your live stream](https://support.google.com/youtube/answer/2524549)
- [YouTube Live Streaming API](https://developers.google.com/youtube/v3/live/docs)

---

## 22. Definition of Done

Fitur dianggap selesai untuk MVP apabila semua acceptance criteria pada §16 terpenuhi, tes yang relevan lulus, UI responsif dan accessible, Wayfinder sudah digenerate ulang, tidak ada route/permission yang hanya diamankan di frontend, status live tunggal dijaga secara transaksional, halaman `/livestreaming` selalu dapat diakses meskipun tidak ada live, dan homepage hanya menampilkan live section ketika ada siaran aktif yang dipublikasikan.
