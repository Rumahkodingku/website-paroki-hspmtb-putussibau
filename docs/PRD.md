# PRD — Website Paroki HSPMTB Putussibau (MVP P0)

| Atribut | Nilai |
| --- | --- |
| Produk | Website resmi Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau |
| Versi PRD | 1.1 (RBAC Foundation — 6 Oktober 2026) |
| Status | Siap diimplementasikan; item bertanda `[CONFIRM]` menunggu konfirmasi pihak paroki |
| Stack | Laravel 13, Inertia.js, React, MySQL |
| Bahasa antarmuka | Bahasa Indonesia |
| Zona waktu tampilan | Asia/Pontianak (WIB, UTC+7) |
| Audiens dokumen | AI coding agents dan pengembang |

---

## 0. Cara Memakai Dokumen Ini (Untuk AI Agents)

1. Dokumen ini adalah **sumber kebenaran tunggal**. Jika ada konflik antara dokumen ini dan asumsi Anda, dokumen ini menang. Jika dokumen diam, ikuti bagian 2 (Keputusan & Asumsi), lalu pilih opsi paling sederhana dan catat di `docs/DECISIONS.md`.
2. Kata kunci: **MUST** = wajib; **SHOULD** = sangat dianjurkan; **MAY** = opsional; **MUST NOT** = dilarang.
3. Setiap requirement punya ID (contoh `MASS-07`). Rujuk ID ini di commit message, nama test, dan PR.
4. Kriteria penerimaan (`AC-*`) harus bisa diubah menjadi test otomatis. Satu AC minimal satu test.
5. **Jangan mengarang data nyata paroki** (nomor telepon, alamat, nama pastor, nama pengurus, tautan Google Form, koordinat). Gunakan placeholder yang jelas, misalnya `[ISI: nomor sekretariat]`, dan tandai seeder sebagai data contoh.
6. **Jangan membangun apa pun dari bagian 12 (Di Luar Cakupan).**
7. Verifikasi versi paket (Laravel 13, Inertia, React, Tiptap, dsb.) dengan dokumentasi resmi saat setup proyek. Nama paket dan API di dokumen ini bersifat panduan, bukan jaminan.
8. Urutan pengerjaan ada di bagian 13. Kerjakan per fase; jangan loncat ke modul yang bergantung pada fondasi yang belum selesai.

---

## 1. Ringkasan Produk

### 1.1 Latar Belakang

Paroki membutuhkan website resmi agar umat dan masyarakat umum dapat mengakses jadwal misa, pengumuman, agenda, dan prosedur pelayanan sakramen kapan saja, terutama lewat ponsel, tanpa harus datang ke sekretariat.

### 1.2 Tujuan Produk

- G1: Informasi paroki akurat, mudah ditemukan, selalu terbarui.
- G2: Umat dapat mengetahui jadwal misa, agenda, dan prosedur pelayanan tanpa ke sekretariat.
- G3: Memperkuat identitas dan kehadiran digital paroki.
- G4: Pengurus non-teknis dapat mengelola konten sendiri lewat panel admin.

### 1.3 Pengguna

| Pengguna | Kebutuhan utama | Perangkat |
| --- | --- | --- |
| Umat (semua usia) | Jadwal misa, berita, agenda | Dominan ponsel, jaringan beragam |
| Calon umat/pendatang | Jadwal misa, alamat gereja | Ponsel |
| Calon penerima sakramen dan keluarga | Syarat dan prosedur, tautan pendaftaran | Ponsel |
| Pengurus/admin paroki | Mengelola konten rutin | Ponsel dan komputer |
| Masyarakat umum | Mengenal paroki | Semua |

### 1.4 Prinsip Perancangan

- **Mobile-first** (mulai 360 px), **ringan dan cepat**, **mudah dikelola** (semua konten dinamis lewat admin, tanpa sentuh kode), **sederhana** (bahasa Indonesia ramah, navigasi konsisten), **menghormati privasi** (data pribadi tidak tampil tanpa persetujuan).

### 1.5 Metrik Keberhasilan (peluncuran)

- Lighthouse mobile: Performance > 80, Accessibility > 90, SEO > 90.
- LCP < 2,5 detik pada 4G.
- Umat menemukan jadwal Misa Minggu dalam maksimal 2 klik dari Beranda.
- Admin non-teknis dapat menulis dan menerbitkan artikel berikut gambar dalam < 5 menit.

### 1.6 Ruang Lingkup

10 fitur publik P0 + komponen pendukung wajib (Panel Admin dan Pengaturan Situs):

| No | Fitur | Rute dasar |
| --- | --- | --- |
| 1 | Beranda | `/` |
| 2 | Profil Paroki | `/profil` |
| 3 | Jadwal Misa | `/jadwal-misa` |
| 4 | Berita dan Artikel | `/berita` |
| 5 | Agenda | `/agenda` |
| 6 | Pelayanan | `/pelayanan` |
| 7 | Komunitas | `/komunitas` |
| 8 | Galeri | `/galeri` |
| 9 | Kontak | `/kontak` |
| 10 | Download | `/download` |
| — | Panel Admin + Pengaturan Situs | `/admin` |

---

## 2. Keputusan, Asumsi, dan Celah Spesifikasi yang Sudah Diputuskan

Bagian ini menutup celah/ketidakkonsistenan pada spesifikasi sumber. Agent MUST mengikuti keputusan ini.

| ID | Masalah di spesifikasi | Keputusan |
| --- | --- | --- |
| D-01 | Waktu "disimpan UTC" tetapi "zona waktu aplikasi Asia/Pontianak" (bertentangan di Laravel) | `config('app.timezone')` = `UTC` (penyimpanan). Tambahkan `config('app.display_timezone')` = `Asia/Pontianak`. Semua datetime dikirim ke frontend sebagai ISO 8601 dan dirender di WIB. Kalkulasi "hari ini", "misa berikutnya", "agenda mendatang" dilakukan berdasarkan WIB. |
| D-02 | `mass_schedules.start_time` adalah jadwal berulang mingguan | Simpan sebagai **jam dinding WIB** (tipe `time`, tanpa konversi UTC), karena tidak terikat tanggal. Hanya kolom `datetime` yang disimpan UTC. |
| D-03 | Komunitas detail harus menampilkan "berita terkait" tetapi `posts` tidak punya relasi komunitas | Tambah `posts.community_id` (nullable FK). |
| D-04 | Komunitas detail menampilkan "galeri singkat"; Galeri "menautkan album dengan berita/agenda" | Tambah `gallery_albums.post_id`, `event_id`, `community_id` (semua nullable FK, `ON DELETE SET NULL`). |
| D-05 | Filter agenda "penyelenggara (komunitas/seksi)" tetapi hanya ada `community_id` | Penyelenggara = komunitas saja (`events.community_id`). Seksi tidak dimodelkan pada MVP. |
| D-06 | Kategori berita "Pengumuman" vs tabel `announcements` (banner) | Dua hal berbeda. `posts` kategori Pengumuman = artikel pengumuman resmi. `announcements` = banner singkat di Beranda. Tidak saling terhubung otomatis. |
| D-07 | Rute `.ics` tidak tercantum | Tambah rute publik `GET /agenda/{slug}/ics` untuk unduhan `.ics`; tautan Google Calendar dibangun di sisi klien dari data event. |
| D-08 | Akun Super Admin bisa "dinonaktifkan" tetapi `users` tidak punya kolom status | Tambah `users.is_active` (bool, default true). Akun nonaktif tidak dapat login. |
| D-09 | Pencegahan duplikasi jadwal misa | Unique index `(location_id, day_of_week, start_time)` + validasi Form Request dengan pesan Indonesia yang jelas. |
| D-10 | Seeder konten layanan | Seed isi Lampiran A (Bagian 16) sebagai contoh dengan `is_active = false`. Layanan baru terbit setelah Super Admin memvalidasi. Pengecualian: kerangka enam layanan wajib ada. |
| D-11 | Pencarian berita "judul dan isi" | MVP memakai `LIKE`/FULLTEXT MySQL pada `title`, `excerpt`, `content` (teks tanpa HTML). Tidak perlu Scout/Meilisearch. |
| D-12 | `view_count` rawan inflasi oleh bot | Increment maksimal 1 kali per sesi per artikel dan abaikan user-agent bot umum. |
| D-13 | Pembatalan kegiatan | Kegiatan `cancelled` tetap tampil dengan label; kegiatan `postponed` tampil dengan label "Ditunda". Keduanya tetap muncul di daftar mendatang. |
| D-14 | Rich text editor | Tiptap (React). Output HTML disanitasi di server (mis. `mews/purifier` atau HTMLPurifier) dengan allowlist tag. Sanitasi di server wajib, sanitasi klien saja tidak cukup. |
| D-15 | Autentikasi | Gunakan starter kit/Fortify Laravel yang kompatibel Inertia + React, lalu **hapus rute registrasi**. Login `/admin/login`, reset kata sandi via email. |
| D-16 | Fondasi role dan permission | Gunakan **Spatie Laravel Permission** sebagai fondasi RBAC sejak MVP agar penambahan role/peran dan permission di masa depan tidak memerlukan migrasi dari custom `users.role`. MVP hanya mengaktifkan role `super_admin`; Role/Permission Management UI belum termasuk MVP. Otorisasi backend menggunakan Spatie Permission + Policy/Gate Laravel. |

---

## 3. Stack Teknis dan Konvensi

### 3.1 Stack

| Lapisan | Teknologi | Catatan |
| --- | --- | --- |
| Backend | Laravel 13 (PHP) | Routing, Form Request, Policy/Gate, Scheduler, Queue |
| Penghubung | Inertia.js | Monolit; **tidak ada REST API terpisah**. SSR dipertimbangkan untuk SEO (lihat NFR-SEO) |
| Frontend | React | Halaman publik dan admin |
| Basis data | MySQL | Migrasi Laravel; `utf8mb4` |
| Berkas | Filesystem Laravel | Disk `public` untuk gambar, disk `private` (`local`) untuk dokumen; dapat dialihkan ke S3-compatible |
| Gaya | Tailwind CSS + shadcn/ui (disarankan) | Konsistensi dan kecepatan |
| Gambar | Intervention Image atau Spatie Media Library (opsional) | Thumbnail, WebP, strip EXIF |
| Rich text | Tiptap | Output HTML disanitasi |
| Build | Vite | |

### 3.2 Struktur Direktori

```
app/
  Http/Controllers/Public/*      # controller tipis, return Inertia::render
  Http/Controllers/Admin/*
  Http/Requests/*                # Form Request: validasi + pesan Indonesia
  Models/*                       # Eloquent + scope (published, upcoming, active)
  Policies/*
  Services/ atau Actions/*       # logika: NextMassResolver, ImageProcessor, HtmlSanitizer, IcsBuilder
  Jobs/*                         # GenerateImageVariants
  Console/Commands/*             # PublishScheduledPosts
database/{migrations,seeders,factories}
resources/js/
  Pages/Public/*   Pages/Admin/*
  Components/*     Layouts/{PublicLayout,AdminLayout}.jsx
lang/id/*                        # string validasi dan UI
tests/{Feature,Unit}
docs/{DECISIONS.md,ADMIN_GUIDE.md}
```

### 3.3 Konvensi Kode

- Controller tipis; logika bisnis di Service/Action. Validasi hanya di Form Request.
- Eloquent scope wajib untuk aturan tampil: `published()`, `upcoming()`, `active()`.
- Eager loading wajib (cegah N+1). Aktifkan `Model::preventLazyLoading()` pada non-produksi.
- Foreign key dengan `ON DELETE` eksplisit (`restrict` atau `set null`), bukan default.
- Tabel konten utama memakai soft delete (`deleted_at`); tabel `id`, `created_at`, `updated_at` selalu ada.
- Nama tabel dan kolom: `snake_case` Inggris (seperti pada bagian 9). Teks UI, pesan validasi, dan konten: Bahasa Indonesia.
- Jangan ada string UI hard-coded di komponen; gunakan berkas bahasa atau konstanta terpusat.
- Slug dibuat dengan `Str::slug` + dedup suffix.
- Format ISO 8601 untuk semua datetime pada props.

---

## 4. Peran dan Hak Akses

| Peran | Deskripsi | Akses |
| --- | --- | --- |
| Pengunjung | Siapa pun tanpa login | Baca konten terbit, unduh dokumen, buka tautan Google Form, cari/filter |
| Super Admin | Satu-satunya role pengelola; dapat dipegang >1 orang (disarankan min. 2 akun) | Penuh atas seluruh modul, pengaturan situs, media, dan akun Super Admin |

- AUTH-R1 (MUST): seluruh rute `/admin/*` (kecuali login/reset kata sandi) dilindungi `auth` + cek `is_active` + authorization berbasis Spatie Permission; pada MVP role yang memiliki akses admin penuh adalah `super_admin`.
- AUTH-R2 (MUST): tidak ada pendaftaran publik.
- AUTH-R3 (MUST): model `User` menggunakan `Spatie\Permission\Traits\HasRoles` dan role/permission menjadi sumber kebenaran otorisasi; jangan membuat kolom `users.role` sebagai sumber otorisasi kedua.
- AUTH-R4 (MUST): Policy/Gate Laravel tetap digunakan untuk aturan akses berbasis resource/domain. Spatie Permission menyediakan role/permission assignment, sedangkan Policy menentukan otorisasi kontekstual bila diperlukan.
- AUTH-R5 (MUST): `super_admin` adalah system role awal. Role/permission management UI belum dibangun pada MVP.
- AUTH-R6 (SHOULD): desain permission naming menggunakan pola berbasis aksi-resource (mis. `posts.view`, `posts.create`, `posts.update`, `posts.delete`) agar role baru dapat ditambahkan tanpa refaktor struktur otorisasi.
- Istilah "admin" di seluruh dokumen = Super Admin pada MVP.

Matriks akses:

| Modul | Super Admin | Pengunjung |
| --- | --- | --- |
| Akun dan pengaturan situs | Penuh | — |
| Beranda (hero, pengumuman) | Penuh | Lihat |
| Profil | Penuh | Lihat |
| Jadwal Misa | Penuh | Lihat |
| Berita (termasuk terbit dan hapus) | Penuh | Lihat |
| Agenda | Penuh | Lihat |
| Pelayanan (termasuk tautan Google Form) | Penuh | Lihat dan buka Google Form |
| Komunitas | Penuh | Lihat |
| Galeri | Penuh | Lihat |
| Kontak | Penuh | Lihat |
| Download | Penuh | Lihat dan unduh |

---

## 5. Arsitektur Informasi dan Rute

### 5.1 Rute Publik

| URL | Halaman | Catatan |
| --- | --- | --- |
| `/` | Beranda | Agregasi data banyak modul |
| `/profil`, `/profil/sejarah`, `/profil/visi-misi`, `/profil/wilayah`, `/profil/pastor`, `/profil/struktur` | Profil | `/profil` redirect atau menampilkan ringkasan; sub-halaman via tab/menu samping |
| `/jadwal-misa` | Jadwal Misa | Tab Mingguan dan Misa Khusus |
| `/berita`, `/berita/{slug}` | Berita | Daftar (filter, pencarian) dan detail |
| `/agenda`, `/agenda/{slug}` | Agenda | Kalender, daftar, detail |
| `/agenda/{slug}/ics` | Unduh .ics | Tambahan (D-07) |
| `/pelayanan`, `/pelayanan/{slug}` | Pelayanan | Indeks dan detail |
| `/komunitas`, `/komunitas/{slug}` | Komunitas | Indeks, detail, bagian Wilayah dan Lingkungan |
| `/galeri`, `/galeri/{slug}` | Galeri | Daftar album, detail album |
| `/kontak` | Kontak | |
| `/download`, `/download/{id}/unduh` | Download | Unduhan lewat controller |
| `/sitemap.xml`, `/robots.txt` | SEO | Dihasilkan otomatis |

### 5.2 Rute Admin

| URL | Fungsi |
| --- | --- |
| `/admin/login`, `/admin/logout` | Autentikasi (plus alur lupa/reset kata sandi) |
| `/admin` | Dasbor |
| `/admin/hero`, `/admin/pengumuman` | Hero dan pengumuman Beranda |
| `/admin/profil/*` | Sejarah, visi/misi, pastor, periode dan anggota pengurus |
| `/admin/lokasi`, `/admin/jadwal-misa`, `/admin/misa-khusus`, `/admin/pemberitahuan-jadwal` | Master lokasi, jadwal rutin, misa khusus, pemberitahuan perubahan |
| `/admin/berita`, `/admin/kategori-berita` | Artikel dan kategori |
| `/admin/agenda`, `/admin/kategori-agenda` | Kegiatan dan kategori |
| `/admin/pelayanan` | Layanan, syarat, langkah, FAQ |
| `/admin/komunitas`, `/admin/jenis-komunitas`, `/admin/wilayah` | Komunitas, jenis, wilayah/lingkungan |
| `/admin/galeri` | Album dan foto |
| `/admin/download`, `/admin/kategori-download` | Dokumen dan kategori |
| `/admin/pengaturan` | Pengaturan situs dan kontak |
| `/admin/akun` | Profil, kata sandi, kelola akun Super Admin |

### 5.3 Navigasi Utama (header publik)

Logo paroki; menu: Profil, Jadwal Misa, Berita, Agenda, Pelayanan, Komunitas, Galeri, Kontak, Download; tombol pintasan "Jadwal Misa". Ponsel: menu hamburger. Footer: identitas paroki, tautan cepat, kontak, media sosial, tautan kebijakan privasi singkat, hak cipta.

---

## 6. Persyaratan Lintas Fitur (Cross-Cutting)

### 6.1 Waktu dan Tanggal

- XC-T1 (MUST): ikuti D-01 dan D-02.
- XC-T2 (MUST): format lokal Indonesia, contoh `Minggu, 5 Oktober 2026`, jam `07.30 WIB` (24 jam).
- XC-T3 (MUST): "misa berikutnya", "agenda mendatang", "pengumuman aktif" dievaluasi terhadap waktu sekarang WIB.

### 6.2 Gambar dan Media

| Jenis | Format | Maks. | Aturan |
| --- | --- | --- | --- |
| Hero Beranda | JPG/PNG/WebP | 3 MB | Rasio disarankan 16:9, lebar min. 1600 px, `alt_text` wajib |
| Gambar utama berita | JPG/PNG/WebP | 3 MB | Varian: thumbnail, medium, besar; konversi WebP; `image_alt` wajib |
| Foto profil pastor/pengurus | JPG/PNG/WebP | 2 MB | Rasio 3:4 atau 1:1 |
| Foto galeri | JPG/PNG/WebP | 5 MB | Thumbnail + medium + WebP; file asli disimpan terpisah; EXIF GPS dihapus |
| Poster agenda, logo komunitas, foto linimasa | JPG/PNG/WebP | 3 MB | Dioptimalkan sama seperti di atas |

- XC-M1 (MUST): pemrosesan varian lewat **queue job**; UI admin menampilkan status pemrosesan.
- XC-M2 (MUST): strip EXIF (termasuk GPS) dari semua gambar yang dipublikasikan.
- XC-M3 (MUST): validasi MIME di server, bukan hanya ekstensi.
- XC-M4 (MUST): `loading="lazy"` untuk gambar di bawah lipatan; sediakan `width`/`height` untuk mencegah layout shift; gunakan `srcset`.
- XC-M5 (MUST): teks alternatif wajib untuk gambar kunci (hero, utama berita); tampilkan peringatan jika kosong pada gambar lain.

### 6.3 Sanitasi dan Keamanan Konten

- XC-S1 (MUST): semua HTML rich text (`content`, bagian profil, deskripsi) disanitasi di server sebelum disimpan. Allowlist: `p, br, strong, em, u, ul, ol, li, h2, h3, h4, blockquote, a[href|target|rel], img[src|alt], figure, figcaption, table...` sesuai kebutuhan editor. Tautan eksternal otomatis `rel="noopener noreferrer"`.
- XC-S2 (MUST): kode embed peta hanya menerima domain Google Maps.
- XC-S3 (MUST): `form_url` hanya domain Google Form (lihat SVC-05).
- XC-S4 (MUST): konfirmasi sebelum hapus pada semua aksi destruktif di admin.

### 6.4 Privasi

- XC-P1 (MUST): nomor telepon dan alamat pribadi (pengurus, ketua komunitas/lingkungan) hanya tampil bila `show_contact = true` (persetujuan orang yang bersangkutan).
- XC-P2 (MUST NOT): menampilkan data pribadi anak (nama lengkap, alamat, telepon) pada konten BIA/BIR.
- XC-P3 (MUST): website tidak menyimpan data pendaftar layanan; seluruhnya ada di Google Form/Sheets milik paroki.
- XC-P4 (MUST): footer memuat kebijakan privasi singkat (halaman atau modal; kontennya `[CONFIRM]` dari paroki).

### 6.5 Empty State dan Error

- XC-E1 (MUST): blok tanpa data disembunyikan atau menampilkan pesan kosong sopan (bukan area kosong). Contoh: "Belum ada berita terbaru."
- XC-E2 (MUST): halaman 404 dan 500 berbahasa Indonesia, memakai layout publik, dengan tautan kembali ke Beranda.
- XC-E3 (MUST): konten berstatus draf, nonaktif, atau belum waktunya terbit **mengembalikan 404** bagi publik walau URL diketahui.

### 6.6 Berbagi dan Cetak

- XC-B1: tombol bagikan WhatsApp memakai `https://wa.me/?text=<encoded judul + URL>`; tombol "Salin tautan" memakai Clipboard API dengan fallback.
- XC-B2: halaman Jadwal Misa dan detail Pelayanan memiliki stylesheet cetak (`@media print`) yang rapi: tanpa navigasi, tanpa banner, tipografi bersih.

### 6.7 Cache

- XC-C1 (SHOULD): cache pengaturan situs, menu, dan data jarang berubah (jadwal misa rutin); invalidasi saat admin menyimpan. Cache **tidak boleh** menyebabkan "misa berikutnya" salah; hitung ulang per request dari data terkache.

### 6.8 Tema dan Identitas Visual

- Logo, warna, dan font mengikuti identitas paroki `[CONFIRM]`. Sampai dikonfirmasi, gunakan tema netral dengan token warna terpusat di Tailwind config sehingga mudah diganti.
- Ukuran huruf dasar min. 16 px; area sentuh min. 44 px; kontras WCAG AA.

---

## 7. Spesifikasi Fitur

Skema tabel lengkap ada di **Bagian 9**. Pada tiap fitur, "Data" hanya merujuk nama tabel.

### 7.1 Beranda — `/`

**Tujuan:** pintu masuk; merangkum info terpenting dari semua modul agar jadwal misa, kabar, agenda, dan pengumuman ditemukan tanpa membuka banyak halaman.

**Data:** `hero_slides`, `announcements`, `site_settings`, `mass_schedules`, `special_masses`, `locations`, `posts`, `events`, `services`, `gallery_photos`.

**Blok (urutan atas ke bawah):**

| # | Blok | Isi | Sumber |
| --- | --- | --- | --- |
| 1 | Header | Logo, menu, tombol pintasan Jadwal Misa; hamburger di ponsel | `site_settings` |
| 2 | Hero | Slider maks. 5 slide: gambar, judul, tagline/ayat, 2 tombol (Lihat Jadwal Misa, Hubungi Kami) | `hero_slides` aktif, urut `sort_order` |
| 3 | Bar pengumuman | Banner dengan warna sesuai `level`; dapat ditutup | `announcements` aktif dalam rentang tanggal |
| 4 | Identitas paroki | Nama lengkap, logo, pelindung/pesta pelindung, ringkasan, tautan ke Profil | `site_settings` |
| 5 | Ringkasan Jadwal Misa | Misa terdekat berikutnya + jadwal mingguan ringkas + lokasi + tautan ke halaman lengkap | Resolver "misa berikutnya" |
| 6 | Berita terbaru | 3–6 kartu (gambar, kategori, judul, tanggal, cuplikan) | `posts` terbit |
| 7 | Agenda mendatang | 3–5 kegiatan terdekat (tanggal, judul, jam, lokasi) | `events` |
| 8 | Renungan | 1 kartu renungan terbaru | `posts` kategori Renungan |
| 9 | Pintasan layanan | Ikon ke Baptis, Komuni Pertama, Krisma, Pernikahan, Perminyakan Orang Sakit | `services` aktif |
| 10 | Cuplikan galeri | 6 foto terbaru + tautan galeri | `gallery_photos` dari album terbit |
| 11 | Kontak singkat + peta | Alamat, telepon/WA, tautan ke Kontak | `site_settings` (sumber tunggal) |
| 12 | Footer | Identitas, tautan cepat, kontak, media sosial, hak cipta | `site_settings` |

**Fungsi publik:** melihat ringkasan tanpa login; pindah ke halaman detail lewat kartu/tombol; menutup banner pengumuman (status disimpan di browser agar tidak muncul terus; kunci berdasarkan `announcement.id` + `updated_at` sehingga pengumuman yang diedit muncul lagi).

**Fungsi admin:**

- Kelola slide hero (unggah gambar, judul, tagline, teks dan URL tombol, urutan, aktif/nonaktif).
- Buat, jadwalkan, nonaktifkan pengumuman (mulai/selesai tampil, level).
- Ubah identitas situs: nama paroki, logo, tagline, deskripsi singkat, ayat/motto.
- Aktifkan/sembunyikan blok tertentu (mis. Renungan, Galeri) dan atur jumlah item (`home_news_limit`, dst.).

**Aturan bisnis:**

- HOME-01: berita tampil otomatis berdasar tanggal terbit terbaru, hanya status `published` dengan `published_at <= now`.
- HOME-02: agenda hanya `start_at >= awal hari ini WIB`, urut terdekat, `is_published = true`.
- HOME-03: pengumuman hanya tampil dalam rentang aktif; jika tidak ada, bar disembunyikan.
- HOME-04: hero maksimal 5 slide aktif; validasi saat menyimpan.
- HOME-05: gambar hero wajib `alt_text`, maks. 3 MB, disarankan 16:9, min. lebar 1600 px.
- HOME-06: blok tanpa data disembunyikan atau menampilkan pesan sopan.
- HOME-07: tombol hero "Hubungi Kami" menuju `/kontak`; "Lihat Jadwal Misa" menuju `/jadwal-misa`.

**Kriteria penerimaan:**

- AC-HOME-1: semua blok aktif tampil dan memuat data terbaru dari modulnya.
- AC-HOME-2: perubahan hero, pengumuman, dan pengaturan dari admin muncul di Beranda tanpa perubahan kode.
- AC-HOME-3: pada lebar 360 px tidak ada konten terpotong atau scroll horizontal.
- AC-HOME-4: ada `<title>`, meta description, dan gambar Open Graph agar pratinjau WhatsApp baik.
- AC-HOME-5: pengumuman di luar rentang tanggal tidak tampil; menutup banner tidak memunculkannya lagi pada sesi yang sama.

---

### 7.2 Profil Paroki — `/profil/*`

**Tujuan:** memperkenalkan paroki: sejarah, arah pelayanan, wilayah, pastor, pengurus. Konten relatif statis namun mudah diperbarui saat pergantian pastor atau periode.

**Data:** `parish_profile_sections`, `history_timeline`, `clergy`, `board_periods`, `board_members`, `areas` (dikelola di Komunitas).

**Sub-halaman:**

| Sub-halaman | Isi |
| --- | --- |
| Sejarah | Narasi + linimasa (tahun, judul peristiwa, uraian, foto) |
| Visi dan Misi | Visi, daftar misi, motto/arah pastoral periode berjalan |
| Wilayah Pelayanan | Daftar wilayah, stasi, lingkungan; ringkasan jumlah; tautan ke detail di Komunitas; peta opsional |
| Pastor Paroki | Kartu imam bertugas (foto, nama, jabatan, masa tugas, riwayat singkat, kutipan sambutan); bagian "Riwayat Pastor Paroki" untuk yang purna tugas |
| Struktur Kepengurusan | Bagan/kartu DPP per periode: Ketua, Wakil Ketua, Sekretaris, Bendahara, koordinator seksi/bidang; pemilih periode jika arsip diaktifkan |

Navigasi sub-halaman memakai tab atau menu samping.

**Fungsi admin:**

- Edit teks sejarah, visi, misi, motto (rich text).
- CRUD linimasa sejarah + atur urutan.
- CRUD pastor; tandai Bertugas Saat Ini / Purna Tugas; ubah masa tugas.
- Buat periode baru, tambah anggota, atur seksi dan urutan, arsipkan periode lama.
- Wilayah/stasi/lingkungan dikelola di modul Komunitas dan hanya **ditampilkan** di sini.

**Aturan bisnis:**

- PROF-01: hanya satu `board_periods.is_active = true` pada satu waktu; mengaktifkan satu periode otomatis menonaktifkan lainnya (dalam transaksi DB).
- PROF-02: foto pastor/pengurus JPG/PNG/WebP, maks. 2 MB, rasio 3:4 atau 1:1.
- PROF-03: `phone` anggota pengurus hanya tampil bila `show_contact = true`.
- PROF-04: konten rich text disanitasi (XC-S1).
- PROF-05: mengganti pastor bertugas/periode tidak menghapus data lama (arsip).
- PROF-06: struktur tampil berurutan sesuai `sort_order` yang diatur admin.

**Kriteria penerimaan:**

- AC-PROF-1: semua sub-halaman dapat diakses dan terbaca baik di ponsel.
- AC-PROF-2: admin dapat mengganti pastor bertugas dan periode pengurus tanpa menghapus data lama.
- AC-PROF-3: struktur tampil sesuai urutan admin.
- AC-PROF-4: setiap sub-halaman memiliki URL yang dapat dibagikan dan metadata SEO.
- AC-PROF-5: mengaktifkan periode B otomatis menjadikan periode A arsip (tepat satu aktif).

---

### 7.3 Jadwal Misa — `/jadwal-misa`

**Tujuan:** halaman paling sering dicari. Menampilkan misa mingguan, misa khusus, dan lokasi.

**Data:** `locations`, `mass_schedules`, `special_masses`, `schedule_notices`.

**Komponen:**

- **Kartu Misa Berikutnya:** misa terdekat, dihitung otomatis WIB.
- **Tab Mingguan:** dikelompokkan per hari (Sabtu sore/Minggu, Senin–Sabtu); jam, nama misa (mis. "Misa Minggu I"), bahasa (jika berbeda), lokasi.
- **Tab Misa Khusus:** daftar per tanggal (hari raya, Natal, Paskah, Rabu Abu, Pekan Suci, pesta pelindung, misa arwah, dll.) + keterangan.
- **Filter lokasi:** gereja paroki, stasi, kapel tertentu; juga filter hari.
- **Info lokasi:** nama, alamat singkat, tombol "Buka di Google Maps".
- **Pemberitahuan perubahan jadwal:** kotak peringatan jika ada notice aktif.
- **Aksi bantu:** Bagikan (WhatsApp/salin tautan) dan Cetak.
- Tampilan **kartu/daftar**, bukan tabel yang terpotong di ponsel.

**Fungsi admin:** CRUD master lokasi (nonaktifkan, tidak hapus); CRUD jadwal rutin (hari, jam, nama, bahasa, lokasi, catatan, aktif); CRUD misa khusus (tanggal, jam, deskripsi); CRUD pemberitahuan perubahan dengan rentang tampil; **duplikasi** jadwal untuk mempercepat input antar lokasi.

**Aturan bisnis:**

- MASS-01: jam disimpan format 24 jam, tampil lokal (`07.30 WIB`).
- MASS-02: misa khusus yang `start_at`-nya sudah lewat tidak tampil di tab Misa Khusus, tetapi tetap tersimpan.
- MASS-03: setiap jadwal wajib terhubung ke satu lokasi **aktif**.
- MASS-04: jadwal rutin nonaktif tidak tampil publik, tidak dihapus permanen.
- MASS-05: pemberitahuan berhenti tampil otomatis setelah `ends_at`.
- MASS-06: cegah duplikasi `(location_id, day_of_week, start_time)` (D-09).
- MASS-07: menonaktifkan lokasi yang masih punya jadwal aktif MUST diperingatkan; jadwal lokasi nonaktif tidak tampil di publik.
- MASS-08 **Algoritma Misa Berikutnya** (`NextMassResolver`):
  1. `now` = waktu sekarang di Asia/Pontianak.
  2. Untuk tiap `mass_schedules` aktif (lokasi aktif), hitung kemunculan berikutnya: tanggal terdekat ≥ `now` dengan `day_of_week` sama (0=Minggu … 6=Sabtu) dan jam `start_time` pada tanggal tersebut; jika hari ini tetapi jam sudah lewat, maju 7 hari.
  3. Untuk tiap `special_masses` dengan `start_at >= now` (UTC dibanding UTC), ambil sebagai kandidat.
  4. Kandidat dengan waktu paling awal menang; jika sama, misa khusus didahulukan dan keduanya dapat ditampilkan.
  5. Mendukung filter `location_id` opsional. Kembalikan `null` bila tidak ada kandidat (tampilkan pesan sopan).
  6. Fungsi MUST dapat diuji dengan waktu yang bisa diinjeksi (mis. `Carbon::setTestNow`).

**Kriteria penerimaan:**

- AC-MASS-1: jadwal Misa Minggu dapat ditemukan dalam ≤ 2 klik dari Beranda.
- AC-MASS-2: Kartu Misa Berikutnya selalu benar sesuai WIB (test: Sabtu 23.59, Minggu 07.29, Minggu 07.31, pergantian hari, misa khusus lebih awal dari rutin).
- AC-MASS-3: perubahan jadwal oleh admin langsung tampil di publik.
- AC-MASS-4: setiap lokasi punya tombol Google Maps yang berfungsi.
- AC-MASS-5: tampilan terbaca di ponsel (kartu/daftar, bukan tabel terpotong).
- AC-MASS-6: misa khusus lewat tidak tampil di tab Misa Khusus.
- AC-MASS-7: input jadwal duplikat ditolak dengan pesan jelas.

---

### 7.4 Berita dan Artikel — `/berita`, `/berita/{slug}`

**Tujuan:** pusat publikasi (berita kegiatan, laporan acara, renungan, pengumuman resmi).

**Data:** `post_categories`, `posts`.

**Komponen publik:**

- Daftar: kartu (gambar utama, kategori, judul, tanggal, cuplikan); paginasi **atau** tombol "Muat Lebih Banyak".
- Filter kategori: Semua, Berita, Kegiatan, Renungan, Pengumuman. Pencarian judul dan isi.
- Berita sematan (pinned): 1–2 di atas daftar.
- Detail: judul, penulis/byline, tanggal terbit, kategori, gambar utama, isi, tombol bagikan (WhatsApp, Facebook, salin tautan), berita terkait, navigasi artikel sebelumnya/berikutnya.
- Khusus Renungan: kutipan ayat (`scripture_reference`) di atas artikel jika diisi.

**Fungsi admin:**

- CRUD artikel (soft delete) dengan Tiptap (judul, isi, gambar sisipan, tautan, daftar).
- Unggah gambar utama + `image_alt`; pilih kategori; sematkan; isi `author_name`; opsional hubungkan ke komunitas (D-03).
- Status: **Draf, Dijadwalkan, Terbit, Diarsipkan**.
- Metadata SEO opsional (kosong → ambil dari judul dan ringkasan).
- Kelola kategori (nama, slug, urutan).

**Aturan bisnis:**

- POST-01: slug otomatis dari judul, unik, **tidak berubah setelah terbit** kecuali diubah manual.
- POST-02: publik hanya melihat `status = published` dan `published_at <= now`.
- POST-03: artikel `scheduled` diterbitkan otomatis oleh Laravel Scheduler (command berjalan tiap menit) saat `published_at <= now`: ubah status ke `published`.
- POST-04: gambar utama maks. 3 MB, dikonversi ke varian + WebP (XC-M1).
- POST-05: isi disanitasi (XC-S1).
- POST-06: pada MVP semua artikel dibuat dan diterbitkan Super Admin; tanpa alur Editor/persetujuan.
- POST-07: maksimal 2 artikel `is_pinned` tampil di atas; sisanya urut `published_at desc`.
- POST-08: `view_count` mengikuti D-12.
- POST-09: draf/terjadwal/arsip yang diakses lewat URL langsung → 404 untuk publik (XC-E3). Arsip tidak tampil di daftar publik.
- POST-10: meta Open Graph (judul, deskripsi, gambar medium/besar) wajib pada detail.

**Kriteria penerimaan:**

- AC-POST-1: admin dapat menulis dan menerbitkan artikel dengan gambar dalam < 5 menit.
- AC-POST-2: artikel terjadwal terbit otomatis tepat waktu (test dengan scheduler dan `setTestNow`).
- AC-POST-3: pratinjau WhatsApp menampilkan judul, deskripsi, gambar yang benar.
- AC-POST-4: filter kategori dan pencarian mengembalikan hasil sesuai dan cepat.
- AC-POST-5: artikel draf tidak dapat diakses publik meski URL diketahui.
- AC-POST-6: HTML berbahaya (`<script>`, `onerror=`) pada isi dibersihkan.

---

### 7.5 Agenda — `/agenda`, `/agenda/{slug}`

**Tujuan:** kalender kegiatan paroki (rapat, rekoleksi, latihan koor, kegiatan OMK, perayaan, bakti sosial, dll.).

**Data:** `event_categories`, `events`.

**Komponen:**

- **Kalender bulanan:** grid dengan penanda kegiatan, navigasi bulan, tombol "Hari Ini".
- **Tampilan Daftar:** kegiatan mendatang urut tanggal. **Bawaan di ponsel**; kalender lewat tombol alih tampilan.
- **Filter:** kategori (Liturgi, Pembinaan, Sosial, Rapat, Lainnya) dan penyelenggara (komunitas). Filter bekerja di kedua tampilan.
- **Detail:** judul, tanggal dan jam, lokasi, deskripsi, penyelenggara, poster, status.
- **Tambah ke Kalender:** tautan Google Calendar dan unduhan `.ics`.
- **Label status:** Dibatalkan/Ditunda tampil jelas.
- Bagikan tautan kegiatan.

**Fungsi admin:** CRUD kegiatan (tanggal/jam mulai-selesai, "Sepanjang Hari", lokasi, deskripsi, poster, kategori, penyelenggara); tandai Dibatalkan/Ditunda tanpa menghapus; kelola kategori dan warna penanda; sembunyikan sebagai draf (`is_published = false`); duplikasi kegiatan.

**Aturan bisnis:**

- EVT-01: `end_at >= start_at` (jika diisi).
- EVT-02: tampilan Daftar hanya kegiatan mendatang; kegiatan lampau tetap terlihat di kalender bulan lampau.
- EVT-03: waktu disimpan UTC, tampil WIB (D-01).
- EVT-04: kegiatan `cancelled` tetap tampil dengan label khusus (D-13).
- EVT-05: kegiatan berulang otomatis **tidak** ada; gunakan entri terpisah atau duplikasi.
- EVT-06: lokasi dapat berupa teks bebas (`location_name`) atau master lokasi (`location_id`); jika keduanya ada, `location_id` diutamakan untuk tautan peta.
- EVT-07: `.ics` MUST valid RFC 5545 (UID stabil, `DTSTART/DTEND` UTC, `SUMMARY`, `LOCATION`, `DESCRIPTION`, `STATUS:CANCELLED` bila dibatalkan); kegiatan sepanjang hari memakai `VALUE=DATE`.
- EVT-08: structured data `Event` (schema.org) pada detail.

**Kriteria penerimaan:**

- AC-EVT-1: kalender menampilkan kegiatan pada tanggal yang benar (WIB), termasuk kegiatan yang melewati tengah malam.
- AC-EVT-2: di ponsel tampilan daftar jadi bawaan; kalender dapat diakses lewat tombol alih.
- AC-EVT-3: tombol Tambah ke Kalender menghasilkan acara dengan judul, waktu, lokasi yang benar.
- AC-EVT-4: kegiatan dibatalkan menampilkan label jelas.
- AC-EVT-5: filter kategori berfungsi di kedua tampilan.
- AC-EVT-6: `end_at < start_at` ditolak.

---

### 7.6 Pelayanan — `/pelayanan`, `/pelayanan/{slug}`

**Tujuan:** menjelaskan syarat dan prosedur sakramen dan pelayanan pastoral agar umat tidak perlu bolak-balik ke sekretariat. **Pendaftaran memakai tautan Google Form** yang diisi/diganti Super Admin. Website **tidak** menyimpan atau memproses data pendaftar.

**Enam layanan wajib (MVP):**

| # | Layanan | Slug | Kategori | Tombol Daftar (Google Form) |
| --- | --- | --- | --- | --- |
| 1 | Baptis Bayi | `baptis-bayi` | `inisiasi` | Ya |
| 2 | Baptis Dewasa | `baptis-dewasa` | `inisiasi` | Ya |
| 3 | Komuni Pertama | `komuni-pertama` | `inisiasi` | Ya |
| 4 | Krisma | `krisma` | `inisiasi` | Ya |
| 5 | Pernikahan | `pernikahan` | `perkawinan` | Ya |
| 6 | Pelayanan Perminyakan Orang Sakit | `perminyakan-orang-sakit` | `orang_sakit` | **Tidak**; kotak kontak darurat |

Pengelompokan indeks: Sakramen Inisiasi, Perkawinan, Pelayanan Orang Sakit.

**Data:** `services`, `service_requirements`, `service_steps`, `service_faqs`, `document_service` (pivot ke `documents`).

**Halaman indeks:** kartu layanan (ikon, ringkasan singkat), dikelompokkan menurut kategori.

**Halaman detail (urutan seksi):**

1. Pengantar
2. Tombol Daftar (atas) — lihat aturan status di bawah
3. Alur pendaftaran singkat 4 langkah: (1) baca syarat, (2) siapkan dokumen, (3) isi Google Form, (4) tunggu konfirmasi sekretariat (tidak ditampilkan untuk layanan darurat)
4. Syarat Dokumen (daftar centang terstruktur)
5. Prosedur (langkah bernomor)
6. Jadwal dan Persiapan (`schedule_info`)
7. Biaya/Persembahan (`fee_info`; atau pernyataan tanpa biaya) `[CONFIRM]`
8. Kontak Penanggung Jawab (`contact_name`, `contact_phone`) dengan tombol telepon/WhatsApp
9. Unduhan terkait (dari modul Download)
10. FAQ
11. Tombol Daftar (bawah)
12. Navigasi ke layanan lain
13. "Terakhir diperbarui: {updated_at}"

**Logika tombol Daftar (MUST, tabel keputusan):**

| Kondisi | Tampilan |
| --- | --- |
| `is_emergency = true` | Tidak ada tombol Daftar; tampil kotak kontak cepat + tombol **"Hubungi Sekarang"** (telepon/WhatsApp) |
| `form_url` terisi dan `is_form_open = true` | Tombol menonjol (label `form_button_label`, bawaan "Daftar Sekarang") yang membuka `form_url` di **tab baru**, `rel="noopener noreferrer"` |
| `form_url` terisi dan `is_form_open = false` | Tombol nonaktif (tidak dapat diklik) berlabel **"Pendaftaran Ditutup"** + `form_closed_message` |
| `form_url` kosong (bukan darurat) | Pesan "Silakan hubungi sekretariat" + kontak sekretariat |

**Fungsi admin:**

- CRUD layanan (judul, slug, kategori, ikon, ringkasan, pengantar, status aktif, urutan).
- Isi/ganti `form_url`, ubah teks tombol, **tombol "Buka Tautan"** untuk menguji tautan.
- Atur status pendaftaran Dibuka/Ditutup + pesan penutupan, tanpa menghapus tautan.
- Kelola syarat (`service_requirements`), langkah (`service_steps`), FAQ (`service_faqs`): tambah, ubah, urutkan (drag atau tombol naik/turun).
- Isi jadwal persiapan, biaya, kontak penanggung jawab.
- Tautkan dokumen dari modul Download.

**Aturan bisnis:**

- SVC-01: syarat dan prosedur adalah kebijakan pastoral; harus divalidasi Pastor Paroki/sekretariat sebelum terbit. Itu sebabnya seed awal `is_active = false` (D-10).
- SVC-02: syarat berbentuk **daftar terstruktur**, bukan paragraf panjang (mudah dicetak).
- SVC-03: tampilkan "Terakhir diperbarui" di setiap detail.
- SVC-04: **website tidak menyimpan/memproses data pendaftar.** MUST NOT membuat form pendaftaran native.
- SVC-05 **Validasi `form_url`** (MUST), ditolak saat disimpan dengan pesan jelas jika salah:
  - skema `https`;
  - host `docs.google.com` **dan** path diawali `/forms/`, atau host `forms.gle`;
  - tidak boleh mengandung kredensial (`user:pass@`);
  - host dibandingkan setelah parsing URL (bukan `str_contains`), untuk mencegah akal-akalan seperti `docs.google.com.evil.com` atau `evil.com/?docs.google.com/forms`.
- SVC-06: layanan darurat wajib `contact_phone` terisi **sebelum** dapat dipublikasikan (`is_active = true`); validasi di Form Request.
- SVC-07: status Ditutup di website harus diselaraskan manual dengan pengaturan "Terima Jawaban" di Google Form (tampilkan pengingat di admin).
- SVC-08: halaman layanan dapat dicetak rapi (XC-B2).
- SVC-09: pendaftaran langsung di database (tanpa Google Form) di luar MVP (bagian 12).

**Kriteria penerimaan:**

- AC-SVC-1: keenam layanan punya halaman dengan syarat/prosedur yang telah disetujui paroki (gerbang konten sebelum peluncuran).
- AC-SVC-2: lima layanan non-darurat memiliki tombol Daftar yang membuka Google Form yang benar di tab baru.
- AC-SVC-3: Super Admin dapat mengganti tautan dan membuka/menutup pendaftaran tanpa mengubah kode.
- AC-SVC-4: tautan selain domain Google Form ditolak dengan pesan jelas (uji termasuk kasus bypass SVC-05).
- AC-SVC-5: saat Ditutup, tombol tidak dapat diklik dan pesan penutupan tampil.
- AC-SVC-6: dokumen pendukung dapat diunduh dari halaman layanan.
- AC-SVC-7: tombol Hubungi membuka aplikasi telepon/WhatsApp (`tel:` dan `https://wa.me/...`).
- AC-SVC-8: halaman layanan dapat dicetak rapi.
- AC-SVC-9: layanan darurat tanpa `contact_phone` tidak bisa dipublikasikan.

**Rekomendasi operasional Google Form (di luar kode; tampilkan sebagai bantuan di admin):** satu form per layanan; form dan Sheets pada akun Google resmi paroki dengan editor terbatas; aktifkan notifikasi email ke sekretariat; cantumkan persetujuan data pribadi dan minta data seperlunya; fitur unggah berkas Google Form mewajibkan login Google (bila tidak praktis, minta berkas diserahkan ke sekretariat); matikan "Terima Jawaban" saat ditutup.

---

### 7.7 Komunitas — `/komunitas`, `/komunitas/{slug}`

**Tujuan:** memperkenalkan semua kelompok dalam paroki (OMK, WKRI, BIA/BIR, kategorial, kelompok doa, koor) dan pembagian wilayah/lingkungan.

**Data:** `community_types`, `communities`, `areas`.

**Komponen:**

- **Indeks:** kartu (logo/foto, nama, singkatan, deskripsi singkat); tab/filter: Organisasi/Kategorial, Kelompok Doa dan Pelayanan, Wilayah dan Lingkungan; pencarian nama/jenis.
- **Detail komunitas:** profil, visi/tujuan, kegiatan rutin, jadwal pertemuan, lokasi, ketua dan pembina, kontak (jika disetujui), cara bergabung, galeri singkat (D-04), agenda dan berita terbaru terkait (D-03, `events.community_id`, `posts.community_id`).
- **Wilayah dan Lingkungan:** hierarki (Wilayah memuat banyak Lingkungan), nama ketua, jadwal doa lingkungan, kontak.

**Fungsi admin:** CRUD komunitas; CRUD jenis komunitas (master data: OMK, WKRI, BIA/BIR, Kategorial, Kelompok Doa, Koor, lainnya); CRUD wilayah/lingkungan hierarkis (ketua, jadwal doa); atur urutan dan status aktif.

**Aturan bisnis:**

- COM-01: jenis komunitas dikelola via master data (tambah tanpa ubah kode).
- COM-02: `areas.type = lingkungan` wajib punya `parent_id` yang menunjuk `wilayah`; `type = wilayah` dan `stasi` memiliki `parent_id` null. Cegah siklus; hierarki maksimal 2 tingkat.
- COM-03: `contact_phone` hanya tampil jika `show_contact = true`.
- COM-04: komunitas nonaktif tidak tampil publik, data tetap tersimpan.
- COM-05: untuk jenis BIA/BIR **tidak boleh** menampilkan data pribadi anak (nama lengkap, alamat, telepon). Hanya data pembina/pengurus dewasa yang boleh tampil, dan hanya bila `show_contact` dicentang.
- COM-06: menghapus `community_types` yang masih dipakai ditolak (`restrict`).

**Kriteria penerimaan:**

- AC-COM-1: semua komunitas aktif tampil; filter jenis berfungsi.
- AC-COM-2: detail menampilkan agenda dan berita terkait komunitas tsb.
- AC-COM-3: daftar wilayah dan lingkungan tampil hierarkis dan benar.
- AC-COM-4: kontak tidak muncul jika `show_contact` belum dicentang.
- AC-COM-5: lingkungan tanpa wilayah induk ditolak.

---

### 7.8 Galeri — `/galeri`, `/galeri/{slug}`

**Tujuan:** dokumentasi foto kegiatan dalam album (Natal, Paskah, penerimaan sakramen, OMK, bakti sosial).

**Data:** `gallery_albums`, `gallery_photos`.

**Komponen:**

- **Daftar album:** kartu (sampul, judul, tanggal, jumlah foto), paginasi, filter tahun.
- **Detail album:** judul, tanggal, deskripsi, grid foto responsif.
- **Lightbox:** foto besar, navigasi sebelumnya/berikutnya (swipe di ponsel, tombol panah di desktop, `Esc` menutup), keterangan, tombol tutup; fokus terkelola (aksesibel).
- **Lazy loading** pada thumbnail.
- Bagikan tautan album.

**Fungsi admin:** CRUD album (judul, tanggal, deskripsi, sampul, status terbit); **batch upload** banyak foto dengan indikator kemajuan; keterangan foto, atur urutan, pilih sampul, hapus foto; tautkan ke berita/agenda/komunitas (opsional).

**Aturan bisnis:**

- GAL-01: JPG/PNG/WebP, maks. 5 MB per foto.
- GAL-02: otomatis buat thumbnail + ukuran sedang + WebP; file asli disimpan terpisah (via queue).
- GAL-03: hapus EXIF GPS otomatis.
- GAL-04: album draf (`is_published = false`) tidak tampil publik (404 lewat URL langsung).
- GAL-05: izin publikasi foto anak/individu adalah tanggung jawab admin; tampilkan pengingat di form unggah.
- GAL-06: video **tidak** termasuk MVP; dokumentasi video ditautkan via YouTube pada artikel.
- GAL-07: album tanpa foto tidak tampil di publik atau tampil dengan status kosong yang sopan; sampul bawaan = foto pertama bila `cover_photo_id` null.
- GAL-08: unggahan batch memproses tiap file independen; satu file gagal tidak menggagalkan yang lain, dan kegagalan dilaporkan per file.

**Kriteria penerimaan:**

- AC-GAL-1: admin dapat mengunggah ≥ 20 foto sekaligus dan semuanya menjadi thumbnail otomatis.
- AC-GAL-2: grid rapi di ponsel, tablet, desktop.
- AC-GAL-3: lightbox berfungsi dengan swipe (ponsel) dan panah (desktop).
- AC-GAL-4: halaman tetap cepat (thumbnail + lazy loading).
- AC-GAL-5: EXIF GPS tidak ada pada berkas yang disajikan publik.
- AC-GAL-6: file bukan gambar atau > 5 MB ditolak dengan pesan jelas.

---

### 7.9 Kontak — `/kontak`

**Tujuan:** membantu pengunjung menemukan dan menghubungi paroki dengan cepat.

**Data:** `site_settings` (kunci `contact_*`, `maps_*`, `social_*`, `office_hours`), `contact_persons` (opsional).

**Komponen:** kartu informasi (alamat, telepon, WhatsApp, email, jam pelayanan sekretariat); tombol aksi cepat (Telepon `tel:`, Chat WhatsApp `wa.me` dengan pesan awal, Kirim Email `mailto:`, Petunjuk Arah Google Maps); peta Google Maps embed (lazy); kontak khusus (sekretariat, pelayanan orang sakit, admin website); tautan media sosial (Facebook, Instagram, YouTube, kanal WhatsApp bila ada); aksi **Salin** alamat/nomor.

**Fungsi admin:** ubah alamat, telepon, WhatsApp, email, jam; atur tautan Maps dan kode embed; kelola kontak khusus dan media sosial.

**Aturan bisnis:**

- KON-01: nomor WhatsApp disimpan format internasional tanpa `+` (mis. `62812xxxxxxx`) dan dikonversi ke `https://wa.me/{nomor}`; normalisasi input (`0812…` → `62812…`, buang spasi/strip/plus).
- KON-02: kode embed peta divalidasi: hanya menerima URL `https://www.google.com/maps/embed...` (atau `maps.google.com`); admin menempelkan **URL embed atau iframe**, sistem mengekstrak `src` dan hanya menyimpan URL tervalidasi; **tidak menyimpan HTML iframe mentah**.
- KON-03: kontak kosong → komponennya tidak ditampilkan.
- KON-04: data kontak adalah **sumber tunggal** untuk halaman Kontak, footer, Beranda.
- KON-05: peta dimuat lazy (iframe `loading="lazy"`).
- KON-06: structured data `Organization`/`Church` dasar.

**Kriteria penerimaan:**

- AC-KON-1: semua tombol (telepon, WhatsApp, email, petunjuk arah) berfungsi di ponsel.
- AC-KON-2: peta tampil dengan penanda benar dan tidak memperlambat halaman signifikan.
- AC-KON-3: perubahan kontak di admin langsung terlihat di Kontak, footer, Beranda.
- AC-KON-4: embed dari domain non-Google ditolak.
- AC-KON-5: nomor `0812…` dinormalisasi menjadi tautan `wa.me/62812…` yang benar.

---

### 7.10 Download — `/download`

**Tujuan:** pusat dokumen resmi (formulir pendaftaran sakramen, surat keterangan, pedoman, panduan liturgi, warta paroki PDF).

**Data:** `document_categories`, `documents`, `document_service`.

**Komponen:** daftar kartu/baris (ikon tipe berkas, judul, deskripsi singkat, ukuran, tanggal pembaruan, tombol Unduh); filter kategori (Formulir Pelayanan, Pedoman, Warta Paroki, Liturgi, Lainnya); pencarian judul; tombol "Lihat" untuk pratinjau PDF di tab baru (opsional).

**Fungsi admin:** unggah dokumen (judul, deskripsi, kategori, status terbit); **ganti berkas tanpa mengubah tautan unduhan**; tautkan ke layanan; lihat jumlah unduhan; kelola kategori.

**Aturan bisnis:**

- DL-01: tipe diizinkan PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, ZIP; maks. 10 MB per berkas.
- DL-02: validasi **MIME type di server** (mis. `finfo`), bukan hanya ekstensi; tolak ketidakcocokan ekstensi–MIME.
- DL-03: berkas disimpan di **disk non-publik**; unduhan lewat rute controller `/download/{id}/unduh` (hitung unduhan + kontrol akses). Pratinjau PDF memakai respons `inline` lewat rute terkendali yang sama (mis. `?inline=1`).
- DL-04: nama berkas unduhan = slug judul + ekstensi asli (bukan nama acak).
- DL-05: dokumen draf (`is_published = false`) tidak dapat diunduh publik (404).
- DL-06: `download_count` bertambah **setiap unduhan** (increment atomik, `DB::increment`).
- DL-07: mengganti berkas memperbarui `file_path`, `file_name`, `mime_type`, `file_size`, `updated_at`; ID dan URL unduhan tetap; berkas lama dihapus dari storage.
- DL-08: header `Content-Disposition` aman (nama berkas disanitasi) dan `X-Content-Type-Options: nosniff`.

**Kriteria penerimaan:**

- AC-DL-1: dokumen yang diunggah admin dapat diunduh publik dengan nama berkas yang benar.
- AC-DL-2: jumlah unduhan bertambah tiap kali diunduh.
- AC-DL-3: berkas bertipe tidak diizinkan ditolak saat unggah dengan pesan jelas.
- AC-DL-4: dokumen terkait tampil di halaman layanan yang sesuai.
- AC-DL-5: file `.php` berekstensi `.pdf` ditolak (MIME tidak cocok).
- AC-DL-6: URL langsung ke path storage tidak dapat mengakses berkas (non-publik).

---

## 8. Panel Admin (Sistem Pengelola Konten)

Dibangun dengan stack yang sama (Laravel + Inertia + React), di `/admin`, dengan `AdminLayout` terpisah.

| Komponen | Persyaratan |
| --- | --- |
| Autentikasi | Login email+kata sandi; logout; lupa/reset kata sandi; **rate limiting** login (mis. 5 percobaan/menit per email+IP); tanpa registrasi publik; akun dibuat oleh Super Admin |
| Manajemen akun | Ubah profil dan kata sandi sendiri; tambah/nonaktifkan Super Admin lain; **tidak boleh menonaktifkan/menghapus diri sendiri atau akun aktif terakhir** |
| Dasbor | Jumlah berita, agenda mendatang, dokumen, aktivitas terbaru; pintasan "Tulis Berita" dan "Tambah Agenda" |
| CRUD modul | Form tambah/ubah/hapus untuk seluruh modul Bab 7; tabel daftar dengan pencarian, filter, paginasi |
| Manajemen media | Unggah, validasi, kompresi, varian otomatis; alt text wajib untuk gambar kunci |
| Editor teks kaya | Tiptap; keluaran HTML disanitasi di server |
| Pengaturan situs | Nama paroki, logo, favicon, kontak, media sosial, SEO dasar, blok Beranda |
| Notifikasi dan validasi | Pesan sukses/gagal jelas dalam Bahasa Indonesia; dialog konfirmasi sebelum hapus |

Persyaratan UX admin:

- ADM-01 (MUST): admin dapat dipakai di ponsel (responsif), karena sebagian pengurus mengelola dari ponsel.
- ADM-02 (MUST): semua pesan validasi dalam Bahasa Indonesia (`lang/id`).
- ADM-03 (SHOULD): simpan otomatis draf artikel atau peringatan sebelum meninggalkan halaman dengan perubahan belum disimpan.
- ADM-04 (MUST): pratinjau artikel sebelum terbit.
- ADM-05 (MUST): operasi hapus memakai soft delete pada tabel konten utama; tidak ada hard delete dari UI pada MVP kecuali berkas/foto yang diganti atau dihapus eksplisit.
- ADM-06 (SHOULD): tampilkan indikator "belum ada alt text" dan "Google Form belum diisi" pada dasbor sebagai pengingat kualitas konten.

---

## 9. Rancangan Basis Data

Aturan umum: semua tabel punya `id` (bigint unsigned PK), `created_at`, `updated_at`. Kolom `deleted_at` (soft delete) ditandai **SD**. Tipe `datetime` = UTC (D-01). Nama dan tipe bersifat usulan; boleh disesuaikan saat implementasi selama perilaku di dokumen ini terpenuhi. Format kolom: `nama tipe [constraint]`. FK menyebut aturan `ON DELETE`.

### 9.1 Relasi Utama

- `posts` → `post_categories` (restrict), `users` (restrict), `communities` (set null)
- `mass_schedules`, `special_masses` → `locations` (restrict)
- `events` → `event_categories` (restrict), `communities` (set null), `locations` (set null)
- `communities` → `community_types` (restrict)
- `areas` → `areas.parent_id` (restrict)
- `board_members` → `board_periods` (cascade)
- `gallery_photos` → `gallery_albums` (cascade); `gallery_albums.cover_photo_id` → `gallery_photos` (set null); `gallery_albums.post_id|event_id|community_id` (set null)
- `documents` → `document_categories` (restrict); `document_service` pivot ↔ `services` (cascade)
- `service_requirements|service_steps|service_faqs` → `services` (cascade)

### 9.2 Sistem

**users**

- `name string`, `email string unique`, `password string` (hash bcrypt/argon2), `is_active bool default true` (D-08), `email_verified_at datetime null`, `remember_token`. Role/permission dikelola oleh Spatie Permission; tidak ada kolom `role` pada `users`.

**site_settings**

- `key string(100) unique`, `value longText null`, `group string(50) null` (identitas, kontak, sosial, seo, beranda). Dibaca lewat helper/Service dengan cache (XC-C1). Daftar kunci baku: Lampiran B.

Tabel standar Laravel: `password_reset_tokens`, `sessions`, `jobs`, `failed_jobs`, `cache`, `cache_locks`.

### 9.3 Beranda

**hero_slides**

- `title string`, `subtitle string null`, `image_path string`, `alt_text string`, `button_label string null`, `button_url string null`, `sort_order int default 0`, `is_active bool default true`.

**announcements**

- `title string`, `body text null`, `link_url string null`, `level enum('info','penting','mendesak') default 'info'`, `starts_at datetime null`, `ends_at datetime null`, `is_active bool default true`.
- Aktif jika `is_active` dan `(starts_at null atau <= now)` dan `(ends_at null atau >= now)`.

### 9.4 Profil

**parish_profile_sections**

- `key string unique` (`sejarah`, `visi`, `misi`, `motto`, `pelindung`, `ringkasan`, dll.), `title string`, `content longText` (HTML tersanitasi).

**history_timeline**

- `year smallint`, `title string`, `description text`, `image_path string null`, `image_alt string null`, `sort_order int`.

**clergy** (SD)

- `name string`, `title string null` (mis. "Pst."), `position string` (`pastor_paroki`, `pastor_rekan`, `lainnya`), `photo_path string null`, `bio text null`, `quote text null`, `start_year smallint null`, `end_year smallint null`, `is_current bool default false`, `sort_order int`.

**board_periods**

- `name string` (mis. "Periode 2025–2028"), `start_year smallint`, `end_year smallint`, `is_active bool default false`.

**board_members**

- `board_period_id FK cascade`, `name string`, `position string`, `section string null`, `photo_path string null`, `sort_order int`, `show_contact bool default false`, `phone string null`.

### 9.5 Misa

**locations** (SD)

- `name string`, `type enum('gereja_paroki','stasi','kapel','lainnya')`, `address text null`, `latitude decimal(10,7) null`, `longitude decimal(10,7) null`, `maps_url string null`, `is_active bool default true`, `sort_order int default 0`.

**mass_schedules**

- `location_id FK restrict`, `day_of_week tinyint` (0=Minggu … 6=Sabtu), `start_time time` (WIB, D-02), `name string`, `language string null`, `notes text null`, `is_active bool default true`.
- **unique** `(location_id, day_of_week, start_time)`; index `(day_of_week, start_time)`.

**special_masses** (SD)

- `title string`, `description text null`, `location_id FK restrict`, `start_at datetime`, `end_at datetime null`. Index `start_at`.

**schedule_notices**

- `message text`, `starts_at datetime null`, `ends_at datetime null`, `is_active bool default true`.

### 9.6 Berita

**post_categories**

- `name string`, `slug string unique`, `sort_order int`. Seed: Berita, Kegiatan, Renungan, Pengumuman.

**posts** (SD)

- `post_category_id FK restrict`, `author_id FK users restrict`, `community_id FK null set null` (D-03), `author_name string null`, `title string`, `slug string unique`, `excerpt text null`, `content longText`, `featured_image string null`, `image_alt string null`, `scripture_reference string null`, `status enum('draft','scheduled','published','archived') default 'draft'`, `published_at datetime null`, `is_pinned bool default false`, `view_count unsigned int default 0`, `meta_title string null`, `meta_description string null`.
- Index `(status, published_at)`, `is_pinned`, FULLTEXT opsional `(title, excerpt, content_text)` bila memakai kolom teks polos (`content_text` MAY ditambah untuk pencarian).
- Validasi: `status = scheduled` wajib `published_at` di masa depan; `status = published` mengisi `published_at = now` jika kosong.

### 9.7 Agenda

**event_categories**

- `name string`, `slug string unique`, `color string(7)` (hex, mis. `#2E7D32`). Seed: Liturgi, Pembinaan, Sosial, Rapat, Lainnya.

**events** (SD)

- `event_category_id FK restrict`, `community_id FK null set null`, `title string`, `slug string unique`, `description longText null`, `start_at datetime`, `end_at datetime null`, `is_all_day bool default false`, `location_name string null`, `location_id FK null set null`, `poster_path string null`, `poster_alt string null`, `status enum('scheduled','cancelled','postponed') default 'scheduled'`, `is_published bool default false`.
- Index `start_at`, `(is_published, start_at)`.

### 9.8 Pelayanan

**services** (SD)

- `title string`, `slug string unique`, `category enum('inisiasi','perkawinan','orang_sakit')`, `icon string null`, `summary text null`, `introduction longText null`, `schedule_info text null`, `fee_info text null`, `contact_name string null`, `contact_phone string null`, `form_url string null`, `form_button_label string default 'Daftar Sekarang'`, `is_form_open bool default false`, `form_closed_message string null`, `is_emergency bool default false`, `is_active bool default false`, `sort_order int default 0`.

**service_requirements**: `service_id FK cascade`, `text string`, `note string null`, `sort_order int`.
**service_steps**: `service_id FK cascade`, `title string`, `description text null`, `sort_order int`.
**service_faqs**: `service_id FK cascade`, `question string`, `answer text`, `sort_order int`.
**document_service** (pivot): `document_id FK cascade`, `service_id FK cascade`, unique `(document_id, service_id)`.

### 9.9 Komunitas

**community_types**: `name string`, `slug string unique`, `sort_order int`. Seed: OMK, WKRI, BIA/BIR, Kategorial, Kelompok Doa, Koor. Tambah kolom `group enum('organisasi','doa_pelayanan')` agar tab indeks (Organisasi/Kategorial vs Kelompok Doa dan Pelayanan) bisa dipetakan ke jenis.

**communities** (SD)

- `community_type_id FK restrict`, `name string`, `slug string unique`, `abbreviation string null`, `description text null`, `activities text null`, `how_to_join text null`, `meeting_schedule string null`, `meeting_place string null`, `leader_name string null`, `advisor_name string null`, `contact_phone string null`, `show_contact bool default false`, `logo_path string null`, `logo_alt string null`, `is_active bool default true`, `sort_order int default 0`.

**areas** (SD)

- `parent_id FK null areas restrict`, `name string`, `type enum('wilayah','lingkungan','stasi')`, `leader_name string null`, `contact_phone string null`, `show_contact bool default false`, `prayer_schedule string null`, `description text null`, `sort_order int default 0`, `is_active bool default true`.

### 9.10 Galeri

**gallery_albums** (SD)

- `title string`, `slug string unique`, `description text null`, `event_date date null`, `cover_photo_id FK null set null`, `post_id FK null set null`, `event_id FK null set null`, `community_id FK null set null`, `is_published bool default false`.

**gallery_photos**

- `gallery_album_id FK cascade`, `path string` (medium/WebP), `original_path string null` (disk non-publik/terpisah), `thumb_path string`, `caption string null`, `alt_text string null`, `width int`, `height int`, `file_size int`, `sort_order int`.

### 9.11 Download

**document_categories**: `name string`, `slug string unique`, `sort_order int`. Seed: Formulir Pelayanan, Pedoman, Warta Paroki, Liturgi, Lainnya.

**documents** (SD)

- `document_category_id FK restrict`, `title string`, `description text null`, `file_path string` (disk private), `file_name string` (nama asli), `mime_type string`, `file_size unsigned int`, `download_count unsigned int default 0`, `is_published bool default false`, `published_at datetime null`.

### 9.12 Kontak

**contact_persons** (opsional): `label string`, `name string null`, `phone string null`, `sort_order int`, `is_active bool default true`.

### 9.13 Indeks Wajib (ringkas)

`slug` unik pada semua tabel konten; `posts(status, published_at)`; `events(start_at)`; `mass_schedules(location_id, day_of_week, start_time)` unique; FK dengan `ON DELETE` eksplisit.

---

## 10. Persyaratan Non-Fungsional

| ID | Aspek | Persyaratan |
| --- | --- | --- |
| NFR-RESP | Responsif | Optimal 360 px hingga desktop; area sentuh ≥ 44 px; tanpa scroll horizontal |
| NFR-PERF | Kinerja | LCP < 2,5 s pada 4G; gambar WebP + responsif + lazy; cache data jarang berubah; eager loading (tanpa N+1); jaga ukuran bundel JS (code splitting per halaman via Inertia/Vite); uji dengan throttling 3G/4G |
| NFR-SEO | SEO | Judul + meta description per halaman (Inertia `Head`); Open Graph + Twitter Card; URL bersih bergaris slug; `sitemap.xml` + `robots.txt` otomatis; structured data `Organization` dan `Event`; **pertimbangkan Inertia SSR** agar crawler dan pratinjau tautan membaca konten (bila SSR tidak diaktifkan, pastikan meta tag OG tetap dirender di server pada respons HTML awal untuk halaman Beranda, Berita, Agenda, Pelayanan) |
| NFR-SEC | Keamanan | HTTPS wajib; CSRF; validasi input server; sanitasi HTML; anti-XSS, SQL injection (Eloquent/binding), mass assignment (`$fillable`); rate limit login; validasi MIME unggahan; validasi domain tautan eksternal (Google Form, peta); dokumen non-publik; hash kata sandi; header keamanan (CSP bila memungkinkan, `X-Content-Type-Options`, `Referrer-Policy`, `X-Frame-Options`); `APP_DEBUG=false` di produksi; peninjauan 2FA untuk fase berikutnya |
| NFR-PRIV | Privasi | Tidak menampilkan data pribadi tanpa persetujuan; hapus EXIF; kebijakan privasi singkat di footer; website tidak menyimpan data pendaftar layanan |
| NFR-A11Y | Aksesibilitas | Kontras WCAG AA; alt text; navigasi keyboard; label form; font dasar ≥ 16 px; lightbox dan menu hamburger aksesibel (fokus, ARIA, `Esc`) |
| NFR-COMPAT | Kompatibilitas | Dua versi terbaru Chrome, Edge, Firefox, Safari (iOS/macOS), dan browser bawaan Android |
| NFR-AVAIL | Ketersediaan | Backup otomatis harian basis data + berkas, retensi ≥ 14 hari, uji pemulihan berkala, pemantauan uptime dasar |
| NFR-SCALE | Skalabilitas | Ratusan–ribuan kunjungan/hari pada satu VPS sederhana; dapat ditingkatkan nanti |
| NFR-I18N | Lokalisasi | Bahasa Indonesia, WIB, format tanggal/jam lokal; `app.locale = id` |
| NFR-MAINT | Pemeliharaan | Kode terstruktur; migrasi + seeder; test otomatis alur kritis; dokumentasi pengelolaan untuk pengurus |

---

## 11. Arsitektur dan Pemrosesan Latar Belakang

- **Monolit Laravel + Inertia:** controller mengembalikan `Inertia::render('Public/...', $props)`. Tanpa REST API terpisah. Gunakan Resource/DTO ringan agar bentuk props eksplisit dan **tidak membocorkan kolom internal** (mis. `deleted_at`, `phone` saat `show_contact = false`).
- **Aturan akses data publik terpusat di scope Eloquent**, bukan tersebar di controller. Contoh: `Post::published()`, `Event::publicUpcoming()`, `Announcement::activeNow()`, `Document::published()`.
- **Queue:** pembuatan varian gambar (`GenerateImageVariants`). Gunakan driver `database` (cukup untuk MVP) dan supervisor/worker di server.
- **Scheduler (`routes/console.php`):**
  - tiap menit: `PublishScheduledPosts` (ubah `scheduled` → `published` bila waktunya tiba);
  - harian: pembersihan data kedaluwarsa (mis. token reset, sesi lama, berkas sementara unggahan gagal);
  - harian: backup (via paket/ skrip server).
- **Penyimpanan berkas:** disk `public` untuk gambar (varian, WebP), disk `private` untuk dokumen dan file asli galeri. Konfigurasi dapat dialihkan ke S3-compatible via `.env`.
- **Media:** Spatie Media Library/Intervention Image MAY digunakan sesuai kebutuhan pemrosesan gambar.
- **RBAC:** Spatie Laravel Permission **MUST** digunakan sebagai fondasi role dan permission (D-16).

---

## 12. Di Luar Cakupan MVP (JANGAN DIBANGUN)

| Fitur | Prioritas nanti | Catatan |
| --- | --- | --- |
| Formulir kontak / kirim pesan | P1 | Perlu anti-spam dan inbox admin |
| Pendaftaran sakramen native di website | P1 | MVP memakai tautan Google Form saja |
| UI manajemen Role/Permission + role tambahan (Admin Konten, Editor) + alur persetujuan | P1 | Fondasi RBAC sudah tersedia pada MVP melalui Spatie Permission; penambahan role, permission, UI management, dan workflow approval dilakukan pada fase lanjutan |
| Pencarian global lintas modul | P1 | MVP: pencarian per modul |
| Warta paroki digital (arsip mingguan) | P1 | Sementara via kategori Download |
| Bacaan harian + kalender liturgi otomatis | P1 | Perlu sumber data berizin |
| Siaran langsung misa | P1 | Cukup sisipkan tautan pada artikel |
| Autentikasi dua faktor admin | P1 | Sangat disarankan |
| Log aktivitas admin (audit trail) | P1 | |
| Galeri video | P2 | Gunakan YouTube/Vimeo |
| Newsletter / notifikasi (email/WhatsApp) | P2 | |
| Donasi / persembahan online | P2 | |
| Login umat, data keluarga/lingkungan | P2 | Data pribadi sensitif |
| Kegiatan berulang otomatis dan RSVP agenda | P2 | |
| Multi-bahasa | P2 | |

Agent MUST NOT menambahkan: UI manajemen Role/Permission, role tambahan selain `super_admin`, akun umat, modul komentar, form kontak, RSVP, pembayaran, atau integrasi pihak ketiga selain Google Maps embed dan tautan Google Form/Calendar.

**Catatan RBAC:** tabel dan struktur role/permission dari Spatie **memang harus ada** sebagai fondasi teknis MVP. Yang berada di luar cakupan MVP adalah UI untuk membuat/mengubah/menghapus role dan permission serta penggunaan role tambahan. `super_admin` adalah role sistem awal yang di-seed dan tidak boleh dihapus melalui UI pada MVP.

---

## 13. Rencana Implementasi untuk Agent (Urutan Kerja)

Estimasi manusia 8–13 minggu (1–2 pengembang). Untuk agent, ikuti **urutan dependensi** berikut. Tiap fase selesai bila semua tugas tercentang dan test fase itu hijau.

### Fase 0 — Persiapan (non-kode, dikerjakan pihak paroki)

- [ ] Konfirmasi dokumen; kumpulkan konten awal (bagian 17.1); identitas visual; wireframe halaman utama; siapkan Google Form per layanan.

### Fase 1 — Fondasi

- [ ] Inisialisasi Laravel 13 + Inertia + React + Vite + Tailwind (+ shadcn/ui); MySQL `utf8mb4`.
- [ ] `config/app.php`: `timezone=UTC`, `display_timezone=Asia/Pontianak`, `locale=id`; paket bahasa `lang/id`.
- [ ] Migrasi `users` (+`is_active`), `site_settings`, tabel sistem.
- [ ] Instal dan konfigurasi **Spatie Laravel Permission**; publish migration/config; tambahkan `HasRoles` pada `User`; seed role sistem `super_admin` dan permission dasar; pastikan guard yang digunakan konsisten dengan autentikasi admin.
- [ ] Seeder Super Admin dari variabel `.env` (**jangan hard-code kata sandi**) dan assign role `super_admin` melalui Spatie.
- [ ] Autentikasi `/admin/login`, logout, reset kata sandi, rate limiting; hapus rute registrasi (D-15).
- [ ] Terapkan authorization admin menggunakan `role`/`permission` dari Spatie + Policy/Gate Laravel; jangan gunakan `users.role`.
- [ ] `PublicLayout` (header, footer, hamburger) dan `AdminLayout`; komponen UI dasar (Button, Card, Modal konfirmasi, Toast, Pagination, EmptyState).
- [ ] Service pengaturan situs (+cache) dan halaman `/admin/pengaturan`.
- [ ] Pipeline media: `ImageProcessor` (validasi MIME, strip EXIF, varian, WebP) + queue job + komponen unggah admin dengan progres.
- [ ] `HtmlSanitizer` + komponen Tiptap.
- [ ] Halaman error 404/500; komponen SEO (`Head`, OG).
- **Selesai bila:** Super Admin dapat login, mengubah pengaturan situs, dan mengunggah gambar yang diproses menjadi varian WebP tanpa EXIF.

### Fase 2 — Modul Inti

- [ ] **Jadwal Misa:** `locations`, `mass_schedules`, `special_masses`, `schedule_notices` (migrasi, model, scope, Form Request, admin CRUD + duplikasi, halaman publik, `NextMassResolver` + unit test).
- [ ] **Berita:** kategori, posts (status, penjadwalan, pinned, pencarian, filter, share, OG), command `PublishScheduledPosts` + scheduler.
- [ ] **Agenda:** kategori, events, kalender bulanan + daftar, filter, `.ics`, Google Calendar link, label status.

### Fase 3 — Modul Informasi

- [ ] **Profil Paroki:** sections, linimasa, pastor, periode + anggota (aturan satu periode aktif).
- [ ] **Pelayanan:** services + requirements/steps/faqs, validator `GoogleFormUrl`, logika tombol Daftar, layanan darurat, seeder Lampiran A (inaktif), pivot dokumen.
- [ ] **Komunitas:** community_types, communities, areas (hierarki), relasi ke agenda/berita/galeri.

### Fase 4 — Modul Pelengkap

- [ ] **Galeri:** album, batch upload, lightbox, lazy loading.
- [ ] **Kontak:** pengaturan kontak, normalisasi WhatsApp, validasi embed peta, kontak khusus, media sosial.
- [ ] **Download:** kategori, dokumen, rute unduh terkendali, hitung unduhan, ganti berkas.
- [ ] **Beranda:** agregasi seluruh blok, hero, pengumuman, toggle blok.
- [ ] Dasbor admin, sitemap.xml, robots.txt.

### Fase 5 — QA dan Peluncuran

- [ ] Jalankan seluruh test; uji manual 360/768/1280 px dan browser utama.
- [ ] Lighthouse mobile (target di bagian 1.5); throttling 3G/4G.
- [ ] Audit keamanan checklist NFR-SEC; `APP_DEBUG=false`; HTTPS.
- [ ] Isi konten awal (min. 5 berita, agenda 3 bulan ke depan); pasang dan uji tautan Google Form.
- [ ] Backup otomatis aktif dan pemulihan diuji.
- [ ] Tulis `docs/ADMIN_GUIDE.md` (panduan Super Admin) dan serahkan pelatihan singkat.

---

## 14. Persyaratan Pengujian

Gunakan Pest atau PHPUnit untuk backend (Feature + Unit) dan pengujian komponen/E2E ringan (mis. Playwright) untuk alur kritis frontend. **Alur kritis yang MUST punya test otomatis:**

| Area | Kasus uji minimum |
| --- | --- |
| Misa berikutnya | Batas hari/jam (Sabtu 23.59, Minggu 07.29 vs 07.31); misa khusus lebih awal dari rutin; lokasi nonaktif diabaikan; tidak ada kandidat → `null`; filter lokasi |
| Jadwal | Duplikasi `(lokasi, hari, jam)` ditolak; misa khusus lampau tidak tampil; notice kedaluwarsa tidak tampil |
| Berita | Draf/terjadwal/arsip → 404 publik; command terjadwal menerbitkan tepat waktu; slug unik dan stabil; sanitasi XSS; pinned maks. 2 |
| Agenda | `end_at < start_at` ditolak; kegiatan lintas tengah malam tampil di tanggal benar; `.ics` valid; label dibatalkan |
| Pelayanan | Validator `form_url` (valid: `https://docs.google.com/forms/...`, `https://forms.gle/...`; invalid: `http://`, domain lain, `docs.google.com.evil.com`, path bukan `/forms/`, ada kredensial); logika tombol 4 kondisi; layanan darurat wajib telepon sebelum aktif; seed Lampiran A tidak aktif |
| Profil | Hanya satu periode aktif; `show_contact=false` menyembunyikan `phone` dari props |
| Komunitas | Lingkungan wajib wilayah induk; kontak tersembunyi tanpa persetujuan |
| Galeri | Unggah batch 20 foto; EXIF hilang; file non-gambar/oversize ditolak; album draf 404 |
| Kontak | Normalisasi WhatsApp; embed non-Google ditolak |
| Download | MIME palsu ditolak; unduh menambah counter; file draf 404; path storage tidak dapat diakses langsung; ganti berkas mempertahankan URL |
| Auth & RBAC | Rate limit login; akun nonaktif tidak bisa login; rute admin menolak tamu; registrasi tidak ada; user tanpa role `super_admin` ditolak dari area admin MVP; role `super_admin` terpasang melalui Spatie; tidak bisa menonaktifkan akun aktif terakhir |
| Privasi | Props publik tidak memuat kolom `phone` kecuali `show_contact` benar |

Test harus memakai factory dan `Carbon::setTestNow` untuk waktu deterministik.

---

## 15. Definition of Done (MVP)

- [ ] Seluruh 10 fitur P0 memenuhi semua `AC-*` masing-masing.
- [ ] Seluruh konten dinamis dapat dikelola lewat panel admin oleh pengurus non-teknis.
- [ ] Konten awal (profil, jadwal misa, syarat layanan, kontak, ≥ 5 berita, agenda 3 bulan ke depan) terisi dan disetujui paroki.
- [ ] Tautan Google Form untuk lima layanan terpasang dan diuji; jawaban masuk ke Google Sheets milik akun resmi paroki.
- [ ] Uji pada 360, 768, 1280 px dan browser utama berhasil.
- [ ] Lighthouse mobile: Performance > 80, Accessibility > 90, SEO > 90.
- [ ] HTTPS aktif, backup otomatis berjalan, akun admin berkata sandi kuat (minimal 2 akun Super Admin).
- [ ] Panduan admin (PDF/video) dan pelatihan singkat diserahkan.
- [ ] Tidak ada bug kritis atau mayor yang terbuka; seluruh test hijau.
- [ ] Tidak ada fitur dari bagian 12 yang ikut terbangun.
- [ ] Spatie Permission aktif sebagai fondasi RBAC; role `super_admin` berjalan; tidak ada custom `users.role` sebagai sumber otorisasi; UI manajemen Role/Permission belum dibangun pada MVP.

---

## 16. Lampiran

### Lampiran A — Data Seed Layanan (CONTOH; wajib divalidasi Pastor Paroki dan ketentuan Keuskupan Sintang)

> Seed dengan `is_active = false`. Setiap layanan non-darurat: `form_url = null`, `is_form_open = false`, `form_button_label = 'Daftar Sekarang'`. Tampilkan tanda "perlu validasi" di admin sampai Super Admin mengaktifkan.

**A.1 Baptis Bayi** (`baptis-bayi`, `inisiasi`)

- Syarat: Surat nikah gereja (nikah Katolik) orang tua, atau keterangan khusus bila belum menikah gereja · Akta kelahiran atau surat keterangan lahir bayi · Fotokopi Kartu Keluarga · Surat keterangan dari ketua lingkungan/wilayah · Data wali baptis (beragama Katolik, sudah menerima Komuni dan Krisma) beserta surat baptis/krisma wali · Pas foto sesuai ketentuan sekretariat (bila diminta).
- Prosedur: (1) Orang tua membaca syarat, mengisi Google Form, dan menyiapkan berkas · (2) Mengikuti pembekalan/katekese orang tua dan wali baptis sesuai jadwal · (3) Menentukan jadwal pelaksanaan bersama pastor/sekretariat · (4) Pelaksanaan sakramen baptis · (5) Pengambilan surat baptis setelah pencatatan di buku baptis.

**A.2 Baptis Dewasa** (`baptis-dewasa`, `inisiasi`)

- Syarat: Fotokopi KTP dan Kartu Keluarga · Surat permohonan menjadi Katolik · Surat keterangan pernah/belum dibaptis (bila diperlukan) · Data wali baptis · Surat keterangan/izin dari pihak terkait sesuai ketentuan paroki (mis. bila ada pernikahan).
- Prosedur: (1) Mengisi Google Form dan menyampaikan permohonan kepada pastor paroki/sekretariat · (2) Mengikuti masa katekumenat hingga dinyatakan siap · (3) Mengikuti ritus penerimaan dan tahapan katekumenat sesuai ketentuan · (4) Menerima sakramen inisiasi (Baptis, Krisma, Ekaristi) pada waktu yang ditetapkan · (5) Pencatatan dan penerimaan surat baptis.

**A.3 Komuni Pertama** (`komuni-pertama`, `inisiasi`)

- Syarat: Fotokopi surat baptis · Fotokopi Kartu Keluarga · Surat keterangan telah mengikuti katekese/persiapan Komuni Pertama · Persetujuan orang tua/wali.
- Prosedur: (1) Mengisi Google Form; sekretariat atau pembina katekese menindaklanjuti · (2) Mengikuti pelajaran persiapan, termasuk pengakuan dosa pertama sesuai jadwal · (3) Gladi/latihan bersama · (4) Perayaan Komuni Pertama dalam misa yang ditentukan.

**A.4 Krisma** (`krisma`, `inisiasi`)

- Syarat: Fotokopi surat baptis dan bukti Komuni Pertama · Fotokopi Kartu Keluarga · Surat keterangan telah mengikuti katekese persiapan Krisma · Data wali krisma dan surat baptis/krisma wali · Surat pengantar dari lingkungan (bila diminta).
- Prosedur: (1) Mengisi Google Form pendaftaran peserta · (2) Mengikuti katekese persiapan Krisma sesuai jadwal · (3) Pengakuan dosa dan persiapan akhir · (4) Perayaan sakramen Krisma yang dipimpin Uskup atau pastor yang diberi mandat.

**A.5 Pernikahan** (`pernikahan`, `perkawinan`)

- Syarat: Surat baptis terbaru (biasanya diterbitkan kurang dari 6 bulan) kedua calon · Surat keterangan belum menikah/status dari lingkungan atau instansi terkait · Fotokopi KTP dan Kartu Keluarga kedua calon · Pas foto berdampingan · Surat keterangan telah mengikuti kursus persiapan perkawinan · Surat izin/dispensasi bila ada halangan (mis. beda agama/beda gereja) sesuai ketentuan Gereja.
- Prosedur: (1) Mengisi Google Form dan menghubungi pastor paroki/sekretariat paling lambat beberapa bulan sebelum rencana pernikahan · (2) Pemeriksaan kanonik (penyelidikan) dan pengisian berkas · (3) Mengikuti kursus persiapan perkawinan · (4) Pengumuman rencana perkawinan (warta) sesuai ketentuan · (5) Pelaksanaan pemberkatan nikah dan pencatatan · (6) Pengurusan administrasi sipil sesuai ketentuan pemerintah.

**A.6 Pelayanan Perminyakan Orang Sakit** (`perminyakan-orang-sakit`, `orang_sakit`, `is_emergency = true`)

- Syarat: Tidak ada syarat dokumen; diberikan kepada umat Katolik yang sakit berat atau lanjut usia · Informasi dasar yang dibutuhkan: nama, usia, alamat/lokasi (rumah atau rumah sakit), kondisi singkat, nomor yang dapat dihubungi.
- Prosedur: (1) Keluarga atau ketua lingkungan segera menghubungi nomor pelayanan darurat paroki (telepon/WhatsApp) · (2) Menyampaikan data dasar dan lokasi orang sakit · (3) Pastor atau petugas pelayanan menyesuaikan kunjungan dan pelayanan sakramen · (4) Keluarga menyiapkan suasana doa sederhana bila memungkinkan.
- Pendaftaran: tanpa Google Form; hubungi langsung nomor darurat. `contact_phone` = `[ISI: nomor pelayanan darurat]` (wajib sebelum aktif).

### Lampiran B — Kunci `site_settings` Baku

| Grup | Kunci |
| --- | --- |
| identitas | `parish_name`, `parish_short_name`, `tagline`, `short_description`, `motto_verse`, `patron_saint`, `logo`, `favicon` |
| kontak | `contact_address`, `contact_phone`, `contact_whatsapp`, `contact_email`, `office_hours`, `maps_embed_url`, `maps_link` |
| sosial | `social_facebook`, `social_instagram`, `social_youtube`, `social_whatsapp_channel` |
| seo | `seo_default_title`, `seo_default_description`, `seo_default_og_image` |
| beranda | `home_news_limit` (3–6), `home_events_limit` (3–5), `home_gallery_limit` (6), `home_show_devotion`, `home_show_gallery`, `home_show_services`, `home_show_contact` |
| privasi | `privacy_policy_content` (rich text, `[CONFIRM]`) |

### Lampiran C — Enum Baku

| Enum | Nilai |
| --- | --- |
| `locations.type` | `gereja_paroki`, `stasi`, `kapel`, `lainnya` |
| `announcements.level` | `info`, `penting`, `mendesak` |
| `posts.status` | `draft`, `scheduled`, `published`, `archived` |
| `events.status` | `scheduled`, `cancelled`, `postponed` |
| `services.category` | `inisiasi`, `perkawinan`, `orang_sakit` |
| `areas.type` | `wilayah`, `lingkungan`, `stasi` |
| Role sistem | `super_admin` (dikelola Spatie Permission) |
| `day_of_week` | 0 Minggu, 1 Senin, 2 Selasa, 3 Rabu, 4 Kamis, 5 Jumat, 6 Sabtu |

### Lampiran D — Seed Master Data

- `post_categories`: Berita, Kegiatan, Renungan, Pengumuman.
- `event_categories`: Liturgi, Pembinaan, Sosial, Rapat, Lainnya (warna berbeda, kontras cukup).
- `community_types`: OMK, WKRI, BIA/BIR, Kategorial, Kelompok Doa, Koor.
- `document_categories`: Formulir Pelayanan, Pedoman, Warta Paroki, Liturgi, Lainnya.
- `parish_profile_sections`: kerangka kosong untuk `sejarah`, `visi`, `misi`, `motto`.
- `services`: enam layanan (Lampiran A, tidak aktif).
- `site_settings`: nilai awal `parish_name = "Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau"` dan `parish_short_name = "HSPMTB"` (`[CONFIRM]`); sisanya kosong/placeholder.

---

## 17. Kesiapan Konten, Risiko, dan Pertanyaan Terbuka

### 17.1 Konten yang Harus Disiapkan Paroki

| Konten | Penanggung jawab (usulan) | Dibutuhkan untuk |
| --- | --- | --- |
| Logo resmi, foto gereja, foto kegiatan beresolusi baik | Sekretariat / Seksi Komsos | Beranda, Galeri, semua halaman |
| Teks sejarah paroki dan foto lama | Pastor / pengurus senior | Profil |
| Visi, misi, arah pastoral | Dewan Pastoral Paroki | Profil |
| Data pastor (foto, riwayat, masa tugas) dan pengurus DPP | Sekretariat | Profil |
| Daftar wilayah, stasi, lingkungan, ketua | Sekretariat / pengurus wilayah | Profil, Komunitas |
| Jadwal misa rutin dan lokasi (koordinat/tautan peta) | Sekretariat / Pastor | Jadwal Misa |
| Syarat dan prosedur tiap sakramen, kontak, formulir | Pastor Paroki / Sekretariat | Pelayanan, Download |
| Tautan Google Form per layanan | Sekretariat | Pelayanan |
| Profil komunitas dan logo | Ketua tiap komunitas | Komunitas |
| Alamat, telepon, WhatsApp, email, jam sekretariat | Sekretariat | Kontak |
| Dokumen dan formulir PDF/DOCX | Sekretariat | Download |

### 17.2 Risiko dan Mitigasi

| Risiko | Mitigasi |
| --- | --- |
| Konten tidak siap tepat waktu | Mulai kumpulkan sejak Fase 0, satu koordinator konten, seed contoh |
| Admin kesulitan memakai panel | UI sederhana, pelatihan, panduan admin |
| Jaringan lambat | Optimasi gambar, lazy loading, batasi bundel, uji throttling |
| Informasi sakramen tidak akurat | Validasi Pastor Paroki sebelum terbit; tampilkan "Terakhir diperbarui" |
| Privasi data/foto umat | Izin foto, kontak dengan persetujuan, tanpa data anak, hapus EXIF |
| Ketergantungan pada satu pengembang/admin | Dokumentasi, repo terkelola, minimal 2 akun Super Admin |
| Serangan/kehilangan data | HTTPS, update rutin, rate limit, backup + uji pemulihan |
| Ketergantungan pada Google Form | Akun Google resmi paroki, editor terbatas, uji tautan berkala, ganti tautan lewat admin |
| Seluruh akses di satu role | Kata sandi kuat, soft delete, backup, min. 2 akun; 2FA dan audit log di P1 |

### 17.3 Pertanyaan Terbuka (dengan Asumsi Bawaan Agar Pengembangan Tidak Terblokir)

| # | Pertanyaan untuk paroki | Asumsi bawaan sementara |
| --- | --- | --- |
| Q1 | Nama resmi dan singkatan sudah tepat (Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau / HSPMTB)? | Pakai seperti tertulis; mudah diubah di `site_settings` |
| Q2 | Jumlah dan nama stasi, wilayah, lingkungan; apakah pembagian sering berubah? | Data kosong dengan struktur hierarkis siap diisi admin |
| Q3 | Siapa pemegang akun Super Admin dan siapa penyetuju konten? | Seed 1 akun dari `.env`; admin menambah akun kedua |
| Q4 | Satu Google Form per layanan atau satu untuk semua? Akun pemilik? | Satu form per layanan (field `form_url` per layanan) |
| Q5 | Siapa memantau jawaban Google Form dan target waktu tanggapan? | Tidak mempengaruhi kode; tampilkan teks umum "tunggu konfirmasi dari sekretariat" |
| Q6 | Sudah ada domain dan hosting? | Konfigurasi via `.env`; siapkan panduan deploy generik (VPS + Nginx + PHP-FPM + MySQL) |
| Q7 | Identitas visual (logo, warna, font)? | Tema netral dengan token warna terpusat |
| Q8 | Perlu mencantumkan biaya/persembahan di Pelayanan? | Kolom `fee_info` ada; bila kosong tampilkan "Hubungi sekretariat untuk informasi lebih lanjut" |
| Q9 | Tampilkan kontak pribadi pastor/pengurus atau hanya sekretariat? | Hanya sekretariat; kontak pribadi hanya bila `show_contact = true` |
| Q10 | Perlu bahasa selain Indonesia? | Tidak; hanya Bahasa Indonesia |
| Q11 | Akun media sosial resmi yang perlu ditautkan? | Kosong; tautan disembunyikan bila tidak diisi |

---

## 18. Ringkasan Guardrail untuk Agent (Baca Sebelum Setiap Tugas)

1. Draf, nonaktif, belum terbit, atau di luar rentang aktif → **404/tersembunyi** untuk publik; selalu lewat scope Eloquent.
2. Semua HTML rich text → sanitasi **di server**.
3. Semua tautan eksternal admin (Google Form, peta) → validasi **host hasil parsing URL**, bukan pencocokan substring.
4. Data pribadi hanya tampil bila `show_contact = true`; tidak pernah ada data pribadi anak (BIA/BIR).
5. Dokumen di disk non-publik; unduh lewat controller; MIME divalidasi di server.
6. Zona waktu: simpan UTC, tampil WIB; jam misa mingguan disimpan sebagai jam dinding WIB.
7. Tidak ada form pendaftaran native, role tambahan, atau fitur dari bagian 12.
8. Tidak mengarang data nyata paroki; gunakan placeholder `[ISI: ...]`.
9. Setiap AC → minimal satu test otomatis; setiap requirement ber-ID dirujuk di commit/PR.
10. Ragu? Pilih opsi paling sederhana yang konsisten dengan dokumen ini dan catat di `docs/DECISIONS.md`.
