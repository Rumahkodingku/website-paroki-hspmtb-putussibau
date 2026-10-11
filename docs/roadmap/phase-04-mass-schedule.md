# Phase 04 --- Mass Schedule (Jadwal Misa)

> **Proyek:** Website Gereja Paroki Hati Santa Perawan Maria Tak Bernoda
> (HSPMTB) Putussibau\
> **Fase:** 04 --- Mass Schedule\
> **Status awal:** Siap diimplementasikan setelah Phase 03 (public page
> shell)\
> **Stack:** Laravel 13, Inertia.js, React, TypeScript, Tailwind CSS,
> MySQL\
> **Dokumen acuan:** `PRD.md` §7.3, `ARCHITECTURE.md`, dan keputusan
> proyek pada `docs/DECISIONS.md`

---

## 1. Tujuan

Membangun modul Jadwal Misa secara end-to-end: database dan business
logic backend, halaman administrasi, halaman publik, integrasi Inertia,
serta pengujian otomatis. Implementasi harus mengikuti arsitektur proyek
yang sudah ada dan tidak melakukan refactor pada modul atau folder di
luar kebutuhan fase ini.

Hasil akhirnya memungkinkan admin mengelola lokasi, misa rutin, misa
khusus, dan pemberitahuan perubahan jadwal. Pengunjung dapat menemukan
misa berikutnya, memfilter jadwal, melihat lokasi, membuka petunjuk
Google Maps, membagikan tautan, dan mencetak jadwal.

## 2. Ruang Lingkup

### 2.1 Termasuk

- Master lokasi gereja paroki, stasi, atau kapel.
- CRUD jadwal misa rutin mingguan.
- CRUD misa khusus pada tanggal/waktu tertentu.
- CRUD pemberitahuan perubahan jadwal dengan rentang tampil.
- Duplikasi jadwal rutin untuk mempercepat input antar-lokasi.
- Halaman publik `/jadwal-misa` yang menggantikan placeholder Phase 03.
- Kartu **Misa Berikutnya** dengan algoritma `NextMassResolver`.
- Filter lokasi dan hari, tab misa rutin dan misa khusus.
- Tombol Google Maps, bagikan WhatsApp, salin tautan, dan cetak.
- Validasi, otorisasi admin, aksesibilitas dasar, responsive UI, dan
  testing.
- Seeder/factory untuk data pengujian; data contoh produksi tidak
  boleh diasumsikan sebagai jadwal resmi paroki.

### 2.2 Tidak termasuk

- Modul agenda, berita, komunitas, atau profil paroki.
- Notifikasi push/email/SMS.
- Sinkronisasi Google Calendar.
- Booking/pendaftaran misa.
- Fitur tambahan yang tidak diminta PRD.
- Refactor global sidebar, layout, sistem autentikasi, atau
  konfigurasi proyek di luar kebutuhan modul.

## 3. Ketentuan Arsitektur dan Konvensi

1. **Periksa implementasi yang sudah ada sebelum mengubah file.**
   Pertahankan pola route, layout, komponen, form, tabel, toast,
   dialog, pagination, dan testing yang telah digunakan proyek.
2. Gunakan Laravel Eloquent secara langsung untuk CRUD sederhana.
   Jangan membuat repository layer tanpa kebutuhan nyata.
3. Gunakan **Form Request** untuk validasi input dan **Policy/Gate**
   bila pola otorisasi proyek memerlukannya.
4. Controller mengatur HTTP/Inertia dan tetap tipis. Letakkan operasi
   multi-langkah yang reusable pada Action, dan business logic
   `NextMassResolver` pada service/action khusus.
5. Halaman Inertia berada di `resources/js/pages`; UI spesifik fitur
   ditempatkan pada folder fitur yang sesuai di
   `resources/js/features`. Jangan memindahkan atau merombak folder
   lain.
6. Ikuti aturan export, penamaan, komponen UI, ikon, form, dan
   TypeScript yang telah ditetapkan `ARCHITECTURE.md` serta kode yang
   sudah ada.
7. Route admin wajib memakai middleware autentikasi dan pemeriksaan
   akses admin yang telah digunakan proyek. Jangan membuat sistem role
   baru.
8. Semua perubahan harus kompatibel dengan Laravel 13 dan versi
   dependency yang benar-benar terpasang. Jangan menebak API package.
9. Jangan menambahkan dependency baru bila kebutuhan dapat dipenuhi
   oleh stack yang sudah ada.

## 4. Data Model dan Database

Buat migration, model, relasi, factory, dan indeks yang diperlukan untuk
empat tabel berikut. Sesuaikan konvensi timestamps, casts, soft-delete,
dan penamaan dengan kode proyek; jangan menambahkan soft delete jika
kebijakan modul sudah cukup menggunakan `is_active`.

### 4.1 `locations`

---

Kolom Tipe / aturan Keterangan

---

`id` BIGINT PK Primary key

`name` VARCHAR(150), required Nama lokasi

`slug` VARCHAR(180), unique Slug lokasi bila
digunakan oleh pola
proyek

`type` VARCHAR(30), required Paroki, stasi, atau
kapel

`address` TEXT, nullable Alamat

`latitude` DECIMAL(10,7), nullable Koordinat lintang

`longitude` DECIMAL(10,7), nullable Koordinat bujur

`maps_url` VARCHAR(2048), nullable URL Google Maps
langsung bila tersedia

`notes` TEXT, nullable Catatan internal/publik
sesuai UI

`is_active` BOOLEAN, default true Status lokasi

timestamps --- `created_at`,
`updated_at`

---

Relasi: `Location hasMany MassSchedule`, `SpecialMass`, dan notice yang
terhubung sesuai desain relasi akhir.

### 4.2 `mass_schedules`

---

Kolom Tipe / aturan Keterangan

---

`id` BIGINT PK Primary key

`location_id` FK, required Wajib menunjuk lokasi
aktif saat
dibuat/diubah

`day_of_week` TINYINT UNSIGNED `0 = Minggu`,
`1 = Senin`, ...,
`6 = Sabtu`

`start_time` TIME, required Jam dinding lokal WIB;
tidak dikonversi ke UTC

`name` VARCHAR(150), required Nama misa

`language` VARCHAR(50), nullable Bahasa misa

`notes` TEXT, nullable Keterangan

`is_active` BOOLEAN, default true Status jadwal

timestamps --- ---

---

Constraint wajib: unique index `(location_id, day_of_week, start_time)`.
Tambahkan indeks untuk query jadwal aktif/hari/lokasi sesuai kebutuhan.
`start_time` adalah waktu lokal berulang, bukan timestamp.

### 4.3 `special_masses`

Kolom Tipe / aturan Keterangan

---

`id` BIGINT PK Primary key
`location_id` FK, required Lokasi misa
`title` VARCHAR(180), required Judul/nama misa khusus
`description` TEXT, nullable Deskripsi
`start_at` DATETIME, required Disimpan UTC
`end_at` DATETIME, nullable Disimpan UTC
`notes` TEXT, nullable Catatan
`is_active` BOOLEAN, default true Status publikasi
timestamps --- ---

Indeks yang disarankan: `(start_at, is_active)` dan
`(location_id, start_at)`. Misa yang sudah lewat tetap tersimpan, tetapi
tidak muncul pada tab Misa Khusus.

### 4.4 `schedule_notices`

---

Kolom Tipe / aturan Keterangan

---

`id` BIGINT PK Primary key

`title` VARCHAR(180), required Judul pemberitahuan

`message` TEXT, required Isi pemberitahuan

`location_id` FK, nullable Target lokasi, bila ada

`mass_schedule_id` FK, nullable Jadwal rutin terkait,
bila ada

`special_mass_id` FK, nullable Misa khusus terkait,
bila ada

`starts_at` DATETIME, required Awal tampil, UTC

`ends_at` DATETIME, nullable Akhir tampil, UTC; null
berarti tanpa batas
akhir

`is_active` BOOLEAN, default true Status publikasi

timestamps --- ---

---

Notice dengan semua foreign key nullable dapat berlaku umum. Validasi
harus memastikan referensi yang diisi benar-benar ada dan relasi
konsisten. Indekskan kolom status/rentang waktu serta foreign key yang
dipakai untuk filter.

### 4.5 Foreign key dan penghapusan

- Jangan menghapus lokasi yang masih memiliki jadwal; utamakan
  menonaktifkan lokasi.
- Gunakan kebijakan foreign key yang aman: referensi jadwal ke lokasi
  tidak boleh membuat jadwal yatim; notice yang mengacu ke entitas
  yang dihapus boleh memakai `SET NULL` jika sesuai keputusan
  implementasi.
- Tentukan perilaku delete secara eksplisit dalam migration dan
  selaraskan dengan relasi Eloquent.
- Jangan melakukan cascade delete yang menghapus jadwal historis tanpa
  persetujuan spesifikasi.

## 5. Backend --- Implementasi

### 5.1 Migrations, Models, dan relasi

- [ ] Buat migration keempat tabel, foreign key, unique constraint,
      dan indeks.
- [ ] Buat model `Location`, `MassSchedule`, `SpecialMass`,
      `ScheduleNotice`.
- [ ] Definisikan relasi Eloquent dan casts boolean/datetime yang
      sesuai.
- [ ] Pastikan `start_time` diperlakukan sebagai jam dinding dan tidak
      melewati konversi timezone.
- [ ] Gunakan factory untuk variasi data aktif/nonaktif, jadwal hari
      ini, misa khusus mendatang/terlewat, dan notice aktif/kedaluwarsa.
- [ ] Jangan menaruh data jadwal resmi yang belum dikonfirmasi ke
      seeder production.

### 5.2 Validasi input

Buat Form Request per kebutuhan create/update/duplicate sesuai pola
proyek, minimal mencakup:

- Nama, jenis, slug, dan status lokasi.
- Hari `0..6`, format jam valid, nama misa, lokasi, bahasa, catatan,
  dan status.
- Judul, lokasi, `start_at`, `end_at`, dan deskripsi misa khusus;
  `end_at` tidak boleh mendahului `start_at`.
- Judul/pesan notice, referensi opsional, rentang tampil yang valid,
  dan status.
- URL Maps harus URL yang valid dan memakai skema HTTP/HTTPS.
- Lokasi yang dipilih untuk jadwal baru/diubah harus aktif.
- Pesan validasi berbahasa Indonesia dan mudah dipahami.

Pencegahan duplikasi dilakukan **dua lapis**: validasi aplikasi untuk
pesan yang ramah pengguna dan unique index database untuk menjamin
konsistensi saat request bersamaan. Tangani duplicate-key exception dan
tampilkan pesan yang jelas.

### 5.3 Business rules wajib

- **MASS-01 --- Waktu:** jam jadwal rutin disimpan 24 jam sebagai
  `TIME`, ditampilkan seperti `07.30 WIB`. Kolom datetime disimpan UTC
  dan ditampilkan dalam `Asia/Pontianak`.
- **MASS-02 --- Misa khusus lewat:** `start_at` yang lebih kecil dari
  waktu sekarang tidak tampil pada tab Misa Khusus, tetapi record
  tetap tersimpan.
- **MASS-03 --- Lokasi aktif:** jadwal rutin dan misa khusus baru/yang
  diubah harus terhubung ke lokasi aktif. Jadwal yang terkait lokasi
  nonaktif tidak ditampilkan publik.
- **MASS-04 --- Jadwal rutin nonaktif:** tidak tampil publik, tidak
  dihapus permanen hanya karena dinonaktifkan.
- **MASS-05 --- Notice kedaluwarsa:** notice tidak tampil bila
  `is_active = false`, belum mencapai `starts_at`, atau waktu sekarang
  sudah melewati `ends_at`. Bila `ends_at = null`, tidak ada batas
  akhir.
- **MASS-06 --- Duplikasi:** kombinasi
  `(location_id, day_of_week, start_time)` tidak boleh ganda.
- **MASS-07 --- Nonaktifkan lokasi:** jika lokasi masih mempunyai
  jadwal aktif, UI/admin harus mendapat peringatan sebelum konfirmasi.
  Jadwal pada lokasi nonaktif tidak tampil publik.
- **MASS-08 --- Misa berikutnya:** implementasikan `NextMassResolver`
  sesuai bagian 5.4.

### 5.4 `NextMassResolver`

Implementasikan service yang menerima waktu saat ini yang bisa dikontrol
saat testing, serta filter `location_id` opsional.

Algoritma:

1. Tentukan `now` dalam `Asia/Pontianak`.
2. Ambil jadwal rutin aktif yang lokasinya aktif dan memenuhi filter
   lokasi.
3. Untuk setiap jadwal rutin, cari tanggal terdekat dengan
   `day_of_week` yang cocok dan jam `start_time` **lebih besar atau
   sama dengan** waktu sekarang. Jika hari ini dan jamnya sudah lewat,
   gunakan kemunculan tujuh hari berikutnya.
4. Ambil misa khusus aktif di lokasi aktif dengan `start_at >= now`;
   lakukan perbandingan datetime dalam UTC.
5. Bandingkan seluruh kandidat berdasarkan waktu mulai paling awal.
6. Jika waktunya sama, prioritaskan misa khusus. Kontrak hasil boleh
   menyertakan kedua kandidat bila UI memang mendukungnya; jika UI
   hanya menampilkan satu, gunakan kandidat misa khusus.
7. Jika tidak ada kandidat, kembalikan `null` dan UI menampilkan empty
   state yang sopan.

**Penting:** `config('app.timezone')` tetap `UTC`. Tambahkan/gunakan
`config('app.display_timezone') = 'Asia/Pontianak'` sesuai keputusan
proyek. Jangan mengubah konfigurasi timezone global menjadi WIB.
`Carbon::setTestNow()` atau injeksi waktu setara harus dapat menguji
boundary tanpa bergantung jam sistem.

### 5.5 Query publik dan controller

- [ ] Query publik hanya mengambil lokasi aktif dan data yang memang
      boleh dipublikasikan.
- [ ] Jadwal rutin difilter `is_active = true` dan lokasi aktif,
      kemudian dikelompokkan per hari.
- [ ] Misa khusus difilter aktif, `start_at >= now`, dan lokasi aktif;
      urutkan dari yang terdekat.
- [ ] Notice difilter aktif dan sedang berada dalam rentang tampil.
- [ ] Filter `location_id` dan `day` divalidasi; jangan percaya query
      parameter mentah.
- [ ] Muat relasi yang diperlukan secara efisien (`with`) dan hindari
      N+1.
- [ ] Controller mengirim props Inertia yang stabil, terstruktur, dan
      tidak menyertakan data internal yang tidak diperlukan.
- [ ] Pertahankan route `/jadwal-misa` yang sudah ada; ubah dari
      shell/placeholder ke halaman yang terhubung data tanpa mengganti
      layout global.

### 5.6 Rute dan admin authorization

Tambahkan route admin mengikuti prefix, name, dan middleware yang sudah
digunakan proyek. Semua endpoint mutasi harus dilindungi middleware
admin yang ada dan validasi server-side.

Rute konseptual (sesuaikan dengan pola route aktual):

- Lokasi: index, store, show/edit, update, deactivate/activate.
- Misa rutin: index, store, show/edit, update, deactivate/activate,
  duplicate.
- Misa khusus: index, store, show/edit, update, deactivate/activate.
- Pemberitahuan: index, store, show/edit, update, deactivate/activate.

Gunakan HTTP method yang benar dan route name konsisten. Jangan
menambahkan endpoint delete permanen bila kebutuhan PRD cukup dengan
nonaktifkan. Untuk duplikasi, gunakan endpoint/action khusus yang
memvalidasi data tujuan.

### 5.7 Duplikasi jadwal rutin

- Admin memilih jadwal sumber dan lokasi tujuan.
- Salin hari, jam, nama, bahasa, serta catatan yang memang relevan;
  jangan menyalin ID atau timestamps.
- Pastikan lokasi tujuan aktif.
- Tolak duplikasi jika kombinasi lokasi tujuan, hari, dan jam sudah
  ada.
- Gunakan transaksi database bila operasi membuat lebih dari satu
  jadwal.
- Berikan pesan sukses/gagal yang jelas tanpa menutupi validation
  error.

## 6. Frontend Admin --- UI dan interaksi

Pertahankan shell admin yang sudah ada: sidebar, header, breadcrumb, dan
sistem visual proyek. Tambahkan submenu/entry **Jadwal Misa** pada
navigasi admin dengan perubahan sekecil mungkin.

### 6.1 Halaman ringkasan Jadwal Misa

- [ ] Judul dan deskripsi modul.
- [ ] Empat pintasan: **Lokasi**, **Misa Rutin**, **Misa Khusus**,
      **Pemberitahuan**.
- [ ] Statistik yang dihitung dari database: jadwal rutin aktif,
      lokasi aktif, misa khusus mendatang, notice yang sedang tampil.
- [ ] Tabel/daftar dengan pencarian, filter relevan, pagination,
      status, dan menu aksi.
- [ ] Tombol tambah sesuai konteks.
- [ ] Loading, empty, error, dan success state.

Angka statistik dan isi tabel bukan hard-coded mock data.

### 6.2 CRUD lokasi

Form berisi nama, slug bila diperlukan, jenis lokasi, alamat, koordinat,
URL Maps, catatan, dan status. Tampilkan daftar lokasi dengan status
aktif/nonaktif.

Saat admin menonaktifkan lokasi yang masih memiliki jadwal aktif,
tampilkan dialog peringatan yang menyebut dampaknya terhadap visibilitas
publik dan minta konfirmasi. Backend tetap harus memvalidasi kondisi
ini; dialog frontend bukan satu-satunya perlindungan.

### 6.3 CRUD misa rutin

Field: lokasi aktif, hari, jam, nama misa, bahasa opsional, catatan
opsional, dan status aktif. Gunakan input waktu yang sesuai dan label
hari berbahasa Indonesia.

- [ ] Validasi inline dan ringkasan error.
- [ ] Tampilkan pesan duplikat secara jelas.
- [ ] Aksi aktif/nonaktif tanpa menghapus data.
- [ ] Aksi duplikasi jadwal ke lokasi lain dengan pemilihan lokasi
      tujuan.
- [ ] Cegah submit ganda selama request berjalan.

### 6.4 CRUD misa khusus

Field: lokasi, judul/nama misa, deskripsi, tanggal dan waktu mulai,
waktu selesai opsional, catatan, dan status. Input datetime harus jelas
menggunakan waktu lokal `Asia/Pontianak`; konversikan dengan benar
sebelum dikirim sebagai datetime UTC sesuai pola aplikasi. Jangan
menampilkan misa lampau sebagai misa mendatang di publik.

### 6.5 CRUD pemberitahuan

Field: judul, pesan, target umum atau target lokasi/jadwal/misa khusus,
waktu mulai tampil, waktu akhir opsional, dan status. Jelaskan bahwa
notice tanpa target berlaku umum. Tampilkan status draft/nonaktif,
terjadwal, sedang tampil, atau kedaluwarsa bila status tersebut dapat
ditentukan dari data.

### 6.6 UX dan aksesibilitas admin

- Form memiliki label yang terlihat, deskripsi bantuan jika
  diperlukan, dan error yang terhubung ke field.
- Tombol memiliki nama aksi yang jelas; aksi berisiko memakai
  konfirmasi.
- Status tidak hanya dibedakan oleh warna.
- Dialog dapat dioperasikan dengan keyboard dan fokus tidak hilang.
- Layout tetap usable pada layar sempit; tabel boleh memiliki pola
  responsif yang sesuai komponen proyek.
- Ikuti design system dan komponen yang sudah tersedia; hindari
  membuat primitive baru yang duplikatif.

## 7. Frontend Publik --- `/jadwal-misa`

Bangun halaman berdasarkan mockup yang disepakati dan design system
HSPMTB. Pertahankan header/footer publik yang sudah ada. Jangan
mengganti shell global.

### 7.1 Susunan halaman

1. **Hero:** label kategori "Ibadah", judul "Jadwal Misa", deskripsi
   singkat, serta visual gereja bila aset yang sesuai tersedia.
2. **Kartu Misa Berikutnya:** nama misa, hari/tanggal, jam WIB, lokasi,
   dan tombol petunjuk lokasi. Bila resolver mengembalikan `null`,
   tampilkan pesan yang ramah.
3. **Notice aktif:** banner perubahan jadwal yang kontras dan mudah
   dibaca; notice umum atau terkait filter harus ditampilkan sesuai
   targetnya.
4. **Tab:** `Misa Rutin` dan `Misa Khusus`.
5. **Filter:** lokasi dan hari. Filter harus dapat digunakan
   bersama-sama dan tidak menghilangkan state yang masih valid.
6. **Daftar misa rutin:** kelompokkan menurut hari, tampilkan waktu,
   nama, bahasa bila tersedia, lokasi, dan catatan relevan.
7. **Daftar misa khusus:** urutkan tanggal/waktu menaik dan tampilkan
   judul, waktu lokal, lokasi, serta deskripsi.
8. **Info lokasi:** alamat dan tombol "Buka di Google Maps" bila
   tautan/koordinat tersedia.
9. **Aksi bantuan:** bagikan melalui WhatsApp, salin tautan halaman,
   dan cetak jadwal.

### 7.2 Perilaku interaksi

- Filter lokasi dan hari bekerja tanpa memerlukan perubahan backend
  yang tidak perlu; gunakan query string bila konsisten dengan pola
  aplikasi agar URL dapat dibagikan.
- Pastikan filter yang aktif tetap terlihat dan tersedia aksi reset
  bila diperlukan.
- Tab misa khusus hanya memuat data aktif yang belum lewat.
- Tombol Maps membuka URL yang valid. Gunakan `maps_url` bila
  tersedia; bila hanya koordinat yang ada, buat tautan Maps dari
  koordinat yang tervalidasi. Jangan membuat tautan dari alamat
  kosong.
- Tombol WhatsApp membuat pesan dan URL berbagi yang valid, dengan
  teks yang di-encode.
- Tombol salin tautan memakai mekanisme clipboard yang telah digunakan
  proyek; tampilkan feedback sukses/gagal.
- Mode cetak menyembunyikan navigasi, tombol aksi, dan elemen
  dekoratif yang tidak dibutuhkan. Jadwal harus tetap terbaca.
- Jangan menampilkan tabel desktop yang terpotong di ponsel; gunakan
  kartu/daftar pada layar kecil.
- Format waktu rutin seperti `07.30 WIB`; datetime misa khusus
  ditampilkan dalam `Asia/Pontianak`.
- Gunakan data yang dikirim backend sebagai sumber kebenaran, bukan
  membuat perhitungan jadwal kedua yang berbeda di React.

### 7.3 State yang wajib ditangani

- Loading/inertia navigation.
- Tidak ada jadwal.
- Tidak ada misa berikutnya.
- Filter tidak menghasilkan hasil.
- Tidak ada notice aktif.
- Lokasi tidak memiliki URL Maps.
- Error validasi/admin dan kegagalan copy link.
- Jadwal nonaktif/lokasi nonaktif tidak bocor ke halaman publik.

## 8. Integrasi Backend--Frontend

- [ ] Definisikan kontrak props Inertia dengan tipe TypeScript yang
      eksplisit.
- [ ] Bedakan data `TIME` (`start_time`) dari ISO datetime
      (`start_at`, `end_at`, `starts_at`, `ends_at`).
- [ ] Datetime dari server dikirim sebagai ISO 8601 yang memiliki
      zona/offset yang tidak ambigu.
- [ ] Format datetime di UI menggunakan `Asia/Pontianak`; jangan
      mengandalkan timezone perangkat.
- [ ] Pastikan filter lokasi/hari memengaruhi query atau state yang
      memang menjadi sumber data.
- [ ] Setelah create/update/deactivate, data publik harus mencerminkan
      perubahan dari database pada request berikutnya. Hindari cache
      stale; bila cache ditambahkan, implementasikan invalidasi.
- [ ] Gunakan flash message/notification yang sesuai dengan pola
      proyek.
- [ ] Pastikan pagination, sorting, filter, dan error tetap bekerja
      setelah navigasi Inertia.
- [ ] Verifikasi route name dan URL aktual; jangan menyimpan URL admin
      secara hard-coded bila named route helper sudah digunakan.
- [ ] Pastikan form request dan respons validasi Inertia sesuai dengan
      mekanisme form yang dipakai proyek.
- [ ] Periksa tidak ada query database dari komponen React atau
      perhitungan bisnis penting yang hanya terjadi di client.

## 9. Testing

Gunakan stack testing yang telah dikonfigurasi dalam repository.
Database test wajib menggunakan MySQL sesuai keputusan proyek
(D-03/D-04); jangan mengganti ke SQLite karena `pdo_sqlite` tidak
tersedia. Gunakan database test terpisah, `website_paroki_hspmtb_test`,
dan pastikan konfigurasi test tidak berisiko menyentuh database
development/production.

### 9.1 Backend unit tests

- [ ] `NextMassResolver` memilih jadwal rutin berikutnya dengan benar.
- [ ] Hari ini dan waktu jadwal tepat sama dengan `now` tetap memenuhi
      aturan `>= now`.
- [ ] Jika jam jadwal hari ini telah lewat, kemunculan berikutnya
      adalah tujuh hari kemudian.
- [ ] Misa khusus lebih awal daripada misa rutin menjadi kandidat
      terpilih.
- [ ] Kandidat dengan waktu sama memprioritaskan misa khusus.
- [ ] Filter `location_id` membatasi kandidat dengan benar.
- [ ] Tidak ada kandidat menghasilkan `null`.
- [ ] Boundary diuji dengan waktu yang dapat
      diinjeksi/`Carbon::setTestNow()` dan timezone yang eksplisit.

### 9.2 Backend feature tests

- [ ] Migration dapat dijalankan pada MySQL test.
- [ ] CRUD lokasi hanya dapat diakses admin yang berwenang.
- [ ] CRUD jadwal rutin tervalidasi; lokasi nonaktif ditolak untuk
      jadwal baru/perubahan.
- [ ] Unique constraint menolak jadwal duplikat dan respons
      menampilkan pesan Indonesia yang jelas.
- [ ] Jadwal nonaktif tidak muncul pada query publik.
- [ ] Misa khusus lampau tersimpan tetapi tidak muncul pada tab
      publik.
- [ ] Notice belum mulai, nonaktif, dan kedaluwarsa tidak tampil.
- [ ] Notice tanpa target dapat tampil umum sesuai rentang aktif.
- [ ] Menonaktifkan lokasi dengan jadwal aktif memberi
      peringatan/konfirmasi pada alur admin; endpoint tetap aman.
- [ ] Duplikasi jadwal berhasil untuk lokasi tujuan yang valid dan
      ditolak bila tujuan tidak aktif atau terjadi duplikasi.
- [ ] Pengunjung tidak dapat mengakses endpoint admin atau melakukan
      mutasi.
- [ ] URL Maps/koordinat yang tidak valid ditangani dengan aman.

### 9.3 Frontend tests

Gunakan framework test yang sudah terpasang; jangan menambahkan
framework baru tanpa kebutuhan.

- [ ] Form admin menampilkan field, label, error validasi, loading,
      dan disabled state yang benar.
- [ ] Aksi konfirmasi nonaktifkan lokasi ditampilkan saat kondisi
      memerlukan.
- [ ] Filter lokasi dan hari memperbarui daftar yang terlihat.
- [ ] Tab rutin/khusus berganti konten dengan benar.
- [ ] Empty state dan pesan tidak ada misa berikutnya muncul sesuai
      kondisi.
- [ ] Tombol copy link/WhatsApp/Maps memiliki URL dan feedback yang
      benar.
- [ ] Print stylesheet menyembunyikan kontrol yang tidak relevan.
- [ ] Tipe props Inertia dan TypeScript lolos pemeriksaan.

### 9.4 Regression dan manual QA

- [ ] Header, footer, sidebar, breadcrumb, dan layout yang sudah ada
      tidak rusak.
- [ ] Beranda dan route publik lain tetap berjalan.
- [ ] Route admin lain tetap berjalan.
- [ ] Tampilan desktop, tablet, dan ponsel diuji.
- [ ] Keyboard focus, label form, kontras, dan state status diperiksa.
- [ ] Perubahan jadwal oleh admin terlihat di publik setelah request
      berikutnya.
- [ ] Tidak ada error console, exception, atau query N+1 yang jelas.

### 9.5 Kasus waktu wajib AC-MASS-2

Gunakan jadwal fixture yang deterministik dan timezone `Asia/Pontianak`:

---

Kasus Waktu uji Ekspektasi

---

Sabtu malam Sabtu 23.59 WIB Misa berikutnya
mengikuti kandidat
valid paling awal
sesudah waktu tersebut

Sebelum misa Minggu Minggu 07.29 WIB Misa Minggu 07.30 WIB
menjadi kandidat bila
jadwal tersebut
tersedia

Setelah misa Minggu Minggu 07.31 WIB Jadwal Minggu 07.30 WIB
bergeser ke Minggu
berikutnya

Pergantian hari Menjelang/setelah Hari dan tanggal
tengah malam kandidat tidak salah

Misa khusus lebih awal Sebelum misa rutin Misa khusus terpilih
berikutnya bila `start_at` lebih
awal

Tidak ada kandidat Semua jadwal Resolver mengembalikan
nonaktif/tidak tersedia `null`

---

Waktu contoh `07.30` hanya fixture pengujian, bukan klaim jadwal resmi
paroki.

## 10. Acceptance Criteria

- **AC-MASS-1:** Jadwal Misa Minggu dapat ditemukan dalam maksimal dua
  klik dari Beranda.
- **AC-MASS-2:** Kartu Misa Berikutnya benar menurut WIB, termasuk
  seluruh boundary pada tabel pengujian.
- **AC-MASS-3:** Perubahan jadwal admin tercermin di halaman publik
  pada request berikutnya.
- **AC-MASS-4:** Setiap lokasi dengan informasi Maps yang valid
  memiliki tombol Google Maps yang berfungsi.
- **AC-MASS-5:** Halaman dapat dibaca dan digunakan di ponsel tanpa
  tabel yang terpotong.
- **AC-MASS-6:** Misa khusus yang telah lewat tidak tampil di tab Misa
  Khusus, tetapi tetap tersimpan.
- **AC-MASS-7:** Jadwal duplikat ditolak dengan pesan yang jelas.
- Semua route mutasi terlindungi authorization dan validasi
  server-side.
- Seluruh test modul lulus, serta test/regression suite yang relevan
  tidak mengalami kegagalan baru.

## 11. Urutan Pengerjaan yang Disarankan

### Tahap A --- Audit dan persiapan

- [ ] Baca `PRD.md` §7.3, `ARCHITECTURE.md`, `docs/DECISIONS.md`,
      route, model, layout, komponen, dan test yang sudah ada.
- [ ] Identifikasi pola form/table/dialog/flash message yang digunakan
      proyek.
- [ ] Konfirmasi nama folder feature dan route name tanpa mengubah
      struktur yang tidak terkait.
- [ ] Pastikan MySQL test terkonfigurasi aman.

### Tahap B --- Database dan backend

- [ ] Migration + model + relasi + factory.
- [ ] Form Request dan aturan validasi.
- [ ] CRUD lokasi, jadwal rutin, misa khusus, notice.
- [ ] Implementasi duplikasi jadwal.
- [ ] `NextMassResolver` dan query publik.
- [ ] Route, middleware, authorization, dan props Inertia.
- [ ] Jalankan backend tests sebelum membangun UI penuh.

### Tahap C --- Frontend admin

- [ ] Tambahkan navigasi modul.
- [ ] Ringkasan dan daftar data.
- [ ] CRUD lokasi.
- [ ] CRUD misa rutin dan duplikasi.
- [ ] CRUD misa khusus.
- [ ] CRUD pemberitahuan.
- [ ] Tambahkan loading, empty, error, confirmation, dan success
      state.

### Tahap D --- Frontend publik dan integrasi

- [ ] Ubah placeholder `/jadwal-misa` menjadi halaman data nyata.
- [ ] Bangun kartu Misa Berikutnya, notice, tab, filter, dan daftar.
- [ ] Tambahkan info Maps, WhatsApp, salin tautan, dan cetak.
- [ ] Verifikasi timezone dan serialisasi props.
- [ ] Verifikasi tampilan responsif dan aksesibilitas dasar.

### Tahap E --- Verifikasi akhir

- [ ] Jalankan formatter/linter/typecheck sesuai script proyek.
- [ ] Jalankan seluruh test modul menggunakan MySQL.
- [ ] Jalankan test suite/regression yang relevan.
- [ ] Periksa manual acceptance criteria AC-MASS-1 sampai AC-MASS-7.
- [ ] Periksa diff agar tidak ada refactor di luar scope.
- [ ] Perbarui dokumentasi hanya bila implementasi menghasilkan
      keputusan baru yang perlu dicatat.

## 12. Perintah Verifikasi

Periksa `package.json`, `composer.json`, dan konfigurasi test terlebih
dahulu, kemudian jalankan script yang benar-benar tersedia. Contoh
kategori perintah yang harus diverifikasi:

```bash
# Backend
php artisan migrate --env=testing
php artisan test

# Frontend — gunakan script aktual yang tersedia di package.json
npm run lint
npm run types
npm run test
npm run build
```

Perintah frontend di atas bersifat contoh; nama script bisa berbeda.
Jangan menjalankan migration destruktif terhadap database selain
database test yang telah diverifikasi.

## 13. Definisi Selesai (Definition of Done)

Phase 04 dianggap selesai bila:

- Keempat tabel dan relasi sudah diterapkan melalui migration.
- Seluruh CRUD admin berfungsi dengan authorization dan validasi.
- Duplikasi jadwal bekerja serta menolak kombinasi duplikat.
- Halaman publik menampilkan data aktual dan seluruh fungsi utama
  sesuai PRD.
- Perhitungan Misa Berikutnya lolos tes waktu dan timezone.
- Waktu rutin tetap diperlakukan sebagai jam dinding WIB; semua
  datetime tersimpan UTC dan ditampilkan dalam `Asia/Pontianak`.
- Responsif, empty/error/loading state, aksi Maps/share/copy/print,
  dan aksesibilitas dasar telah diperiksa.
- Test backend, test frontend yang tersedia, typecheck/lint/build yang
  relevan lulus.
- Tidak ada regression pada halaman/layout/modul lain.
- Tidak ada jadwal resmi atau statistik yang direkayasa sebagai data
  produksi.

---

## 14. Checklist Ringkas untuk Agent

Sebelum menyatakan selesai, agent harus memberikan laporan yang memuat:

1. File yang dibuat/diubah, dikelompokkan menjadi backend, frontend
   admin, frontend publik, dan test.
2. Migration dan nama tabel/constraint yang ditambahkan.
3. Route admin dan publik yang ditambahkan/diubah.
4. Business rules MASS-01 sampai MASS-08 yang telah diimplementasikan.
5. Daftar perintah test/lint/typecheck/build yang benar-benar
   dijalankan beserta hasilnya.
6. Acceptance criteria yang lulus dan yang belum lulus.
7. Risiko, blocker, atau keputusan yang perlu persetujuan; jangan
   mengklaim test lulus bila tidak dijalankan.
