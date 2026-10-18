# PHASE 8C --- Contacts (Kontak)

> **Proyek:** Website Gereja Paroki HSPMTB Putussibau\
> **Status:** Planned\
> **Branch:** `feat/phase-8c-contacts`\
> **Stack:** Laravel 13, Inertia.js 3, React 19, TypeScript, Tailwind
> CSS 4, Pest, Vitest, Playwright

## 1. Tujuan

Dokumen ini menjadi spesifikasi implementasi Phase 8C dan acuan kerja AI
agent. Agent wajib membaca dokumen ini, mengaudit codebase aktual,
menyusun TODO yang terstruktur, lalu mengerjakan dan memverifikasi
setiap item. Jangan menganggap fitur belum tersedia hanya karena
tercantum di dokumen ini; periksa kode dan test terlebih dahulu.

Tujuan fase ini adalah membuat halaman `/kontak` yang dinamis,
responsif, aman, aksesibel, dan konsisten dengan Pengaturan Situs. Data
kontak harus memiliki satu sumber kebenaran dan tidak disimpan ulang
pada tabel atau halaman admin terpisah.

## 2. Hasil yang Diharapkan

- Halaman publik `/kontak` menampilkan data kontak dari Pengaturan
  Situs.
- Pengunjung dapat menelepon, membuka WhatsApp, mengirim email melalui
  aplikasi email, dan membuka petunjuk arah bila data valid.
- Peta hanya dirender jika URL embed valid dan tersedia.
- Footer dan blok kontak beranda menggunakan sumber data yang sama.
- `home_show_contact` mengontrol blok kontak beranda, bukan halaman
  `/kontak`.
- Nilai kosong/tidak valid tidak menghasilkan tautan, tombol, atau
  iframe rusak.
- Test backend, frontend, dan end-to-end ditambahkan/diperbarui.
- Tidak ada regresi pada fase sebelumnya.

## 3. Batasan Scope

### Termasuk

1. Audit halaman kontak, pengaturan situs, footer, beranda, shared
   Inertia props, dan test.
2. Integrasi data pengaturan situs ke halaman kontak publik.
3. Penyempurnaan UI/UX halaman kontak.
4. Aksi telepon, WhatsApp, email, petunjuk arah, dan peta tertanam.
5. Integrasi kontak konsisten pada footer dan blok kontak beranda.
6. Penanganan field kosong, validasi URL, aksesibilitas, responsivitas,
   SEO, dan pengujian.
7. Pembaruan dokumentasi teknis yang relevan.

### Tidak termasuk

- Formulir kirim pesan pengunjung, inbox, dashboard pesan, SMTP, atau
  notifikasi.
- Live chat, chatbot, CRM, booking, atau appointment.
- Tabel database baru untuk alamat, telepon, WhatsApp, email, jam
  layanan, atau peta.
- Menu admin Kontak terpisah jika Pengaturan Situs sudah mencukupi.
- Redesign besar beranda/footer di luar integrasi kontak.
- Perubahan ulang modul Galeri Phase 8A atau Dokumen Phase 8B.
- Dependency baru tanpa kebutuhan yang terbukti.

## 4. Fondasi Codebase yang Sudah Diketahui

Agent harus memeriksa ulang branch terbaru sebelum implementasi.

### 4.1 Pengaturan situs

`config/site-settings.php` sudah mendefinisikan grup `kontak`:

Key Fungsi

---

`contact_address` Alamat paroki
`contact_phone` Nomor telepon
`contact_whatsapp` Nomor WhatsApp
`contact_email` Email
`office_hours` Jam layanan
`maps_embed_url` URL peta tertanam
`maps_link` Tautan peta/petunjuk arah

Grup `sosial` juga memiliki `social_facebook`, `social_instagram`,
`social_youtube`, dan `social_whatsapp_channel`. Grup `beranda` memiliki
`home_show_contact`.

### 4.2 Komponen backend yang sudah ada

- `app/Http/Controllers/Settings/SettingsController.php`
- `app/Http/Requests/Settings/UpdateSettingsRequest.php`
- `app/Services/SiteSettingsService.php`
- `app/Support/PhoneNumber.php`
- `lang/id/site-settings.php`

Gunakan `SiteSettingsService` sebagai jalur resmi membaca/menulis
pengaturan. Jangan query tabel `site_settings` langsung dari React atau
membuat service duplikat.

`App\Support\PhoneNumber` menyediakan `normalize()`, `isValid()`,
`toTel()`, dan `toWhatsApp()`. Gunakan helper ini; jangan membuat
normalisasi nomor kedua.

### 4.3 Route

`routes/web.php` saat dokumen disusun memiliki:

```php
Route::inertia('/kontak', 'public/kontak')->name('kontak.index');
```

Agent wajib menemukan file halaman yang benar-benar digunakan dan
memeriksa pola shared props serta layout publik. Jangan mengubah route
menjadi controller tanpa alasan arsitektural yang jelas.

### 4.4 File yang harus diaudit

Minimal audit: - `config/site-settings.php`,
`lang/id/site-settings.php` - `app/Services/SiteSettingsService.php` -
`app/Support/PhoneNumber.php` - `SettingsController.php` dan
`UpdateSettingsRequest.php` - `routes/web.php` - halaman publik kontak
yang aktual - layout publik dan footer - halaman beranda dan blok
kontak - shared Inertia props - test pengaturan, nomor telepon, footer,
beranda, dan halaman kontak - `ARCHITECTURE.md`, `PRD.md`, `DESIGN.md`,
`docs/DECISIONS.md`, dan roadmap terkait jika tersedia

Jangan menebak path file atau struktur direktori.

## 5. Persyaratan Fungsional

### KON-01 --- Sumber data tunggal

Semua data kontak publik dibaca melalui `SiteSettingsService` atau
mekanisme shared props yang sudah menjadi pola proyek.

**Acceptance criteria** - Tidak ada hardcode alamat, telepon, WhatsApp,
email, jam layanan, atau URL peta. - Tidak ada tabel kontak duplikat. -
Perubahan pengaturan tampil setelah cache yang ada diinvalidasi. -
Semantik nilai default, null, dan nilai yang sengaja dikosongkan tetap
terjaga.

### KON-02 --- Alamat

Tampilkan `contact_address` jika bermakna. Pertahankan line break bila
sesuai, tanpa merusak layout. Jangan menampilkan placeholder palsu.

### KON-03 --- Telepon

Tampilkan nomor dan aksi `tel:` hanya jika nomor valid. Gunakan
`PhoneNumber::toTel()`. Nomor yang ditampilkan harus mudah dibaca.

### KON-04 --- WhatsApp

Tampilkan tombol hanya jika nomor valid. Gunakan
`PhoneNumber::toWhatsApp()`. Jika dibuka di tab baru, gunakan atribut
keamanan yang sesuai. Jangan membangun URL manual bila helper tersedia.

### KON-05 --- Email

Tampilkan email dan tautan `mailto:` hanya jika alamat valid. Tidak
perlu mengirim email dari server; aksi dilakukan melalui aplikasi email
pengunjung.

### KON-06 --- Jam layanan

Tampilkan `office_hours` jika ada. Perlakukan sebagai teks biasa kecuali
tipe konfigurasi menyatakan lain. Sembunyikan bagian jika kosong.

### KON-07 --- Peta tertanam

Render peta hanya jika `maps_embed_url` tersedia dan tervalidasi. -
Gunakan HTTPS dan allowlist host peta yang benar-benar dibutuhkan. -
Tolak skema berbahaya seperti `javascript:` dan `data:`, serta URL yang
mengandung kredensial. - Parse URL dan validasi host; jangan hanya
memeriksa substring. - Gunakan iframe `title` yang bermakna,
`loading="lazy"`, dan atribut sandbox/referrer yang sesuai dengan
penyedia peta. - Jangan menganggap `maps_link` otomatis aman untuk
dipakai sebagai embed.

### KON-08 --- Petunjuk arah

Gunakan `maps_link` yang valid. Sembunyikan tombol bila kosong/tidak
valid. Jangan mengarang koordinat atau URL pengganti.

### KON-09 --- Media sosial

Tampilkan Facebook, Instagram, YouTube, atau kanal WhatsApp hanya jika
URL tersedia dan valid. Gunakan label aksesibel dan ikon dari proyek.
Jangan menambah field sosial baru tanpa kebutuhan yang terbukti.

### KON-10 --- Data parsial

Setiap bagian ditampilkan berdasarkan data valid. Hindari `href=""`, URL
`undefined`, iframe kosong, atau tombol tanpa aksi. Jika seluruh data
kosong, tampilkan struktur halaman dan pesan informatif tanpa mengarang
informasi.

### KON-11 --- Footer

Footer memakai sumber data yang sama. Hilangkan hardcode kontak yang
termasuk scope, gunakan helper nomor yang sama, sembunyikan link
invalid, dan arahkan link halaman ke route `kontak.index`. Jangan
redesign total footer.

### KON-12 --- Beranda

`home_show_contact=true` menampilkan blok kontak beranda; `false`
menyembunyikannya. Pengaturan ini tidak menonaktifkan `/kontak`. Data
dan aturan validasi harus sama dengan halaman kontak/footer. Jika blok
belum ada, ikuti pola desain beranda yang sudah ditetapkan dan hindari
redesign di luar scope.

### KON-13 --- Metadata

Gunakan pola metadata halaman publik yang sudah ada. Jangan membuat
sistem SEO baru atau menambahkan klaim/informasi kontak yang belum
diverifikasi.

## 6. Persyaratan UI/UX

### Struktur halaman yang disarankan

1. **Heading/hero ringkas:** judul "Hubungi Kami" dan pengantar yang
   tidak menjanjikan waktu respons yang belum dikonfirmasi.
2. **Informasi kontak:** alamat, telepon, WhatsApp, email, jam layanan;
   tiap item hanya muncul jika datanya valid.
3. **Lokasi:** peta tertanam dan tombol petunjuk arah bila tersedia.
4. **Media sosial:** tampilkan hanya tautan yang valid.

### Prinsip visual

- Ikuti `DESIGN.md` dan design system yang ada.
- Gunakan komponen UI dan ikon yang sudah tersedia.
- Pertahankan tipografi, warna, radius, spacing, dan pola tombol
  proyek.
- Utamakan keterbacaan; hindari kartu dekoratif yang tidak menambah
  kegunaan.
- Jangan menjadikan peta satu-satunya cara mengetahui alamat.

### Responsivitas

- Mobile-first.
- Kartu tersusun satu kolom di layar kecil.
- Peta tidak menyebabkan horizontal overflow.
- Uji viewport mobile dan desktop sesuai konvensi E2E proyek.

### Aksesibilitas

- Heading berhierarki dan fokus keyboard terlihat.
- Tautan/tombol memiliki accessible name yang jelas.
- Ikon dekoratif disembunyikan dari screen reader.
- Iframe peta memiliki `title`.
- Jangan mengandalkan warna saja.
- Hormati reduced-motion jika ada animasi.

## 7. Backend dan Arsitektur

1. Jangan membuat migration, model, atau tabel kontak baru.
2. Jangan membaca tabel `site_settings` langsung dari React.
3. Jangan menambah shared props global yang besar jika data hanya
   dibutuhkan pada halaman kontak; ikuti pola proyek.
4. Pilih pendekatan paling konsisten setelah audit:
    - controller publik bila modul sejenis menggunakannya dan perlu
      membentuk view model; atau
    - pertahankan `Route::inertia` jika shared props yang ada sudah
      mencukupi.
5. Jangan mengubah kontrak props yang digunakan halaman lain tanpa
   test.
6. Audit validasi URL yang ada sebelum menambah validasi baru.
7. Jika validasi belum cukup, buat aturan URL eksplisit yang dapat
   digunakan ulang, dengan skema HTTPS dan allowlist host.
8. Jangan mengubah URL yang disimpan secara diam-diam; dokumentasikan
   perubahan perilaku dan tambahkan test.

## 8. Pengujian

Ikuti pola test repository. Jangan mengklaim test lulus jika tidak
dijalankan.

### 8.1 Pest/backend

- Halaman kontak menerima data pengaturan yang benar.
- Nilai kosong tidak menyebabkan error.
- Helper nomor dipakai untuk `tel:` dan WhatsApp.
- Nomor invalid tidak menghasilkan URL aksi.
- URL peta valid diterima sesuai allowlist.
- URL peta tidak aman/invalid ditolak.
- Cache berubah sesuai perilaku `SiteSettingsService`.
- `/kontak` dapat diakses tanpa login.
- Tidak ada regresi authorization Pengaturan Situs.

### 8.2 Vitest/frontend

- Data tampil saat tersedia.
- Field kosong disembunyikan atau ditangani sesuai spesifikasi.
- Link telepon, WhatsApp, email, dan petunjuk arah benar.
- Peta hanya tampil jika URL valid tersedia.
- Link sosial kosong tidak dirender.
- `home_show_contact=false` menyembunyikan blok beranda.
- Label aksesibel dan perilaku responsif sesuai.

### 8.3 Playwright/E2E

1. Pengunjung tanpa login membuka `/kontak`.
2. Data fixture yang valid tampil.
3. Link telepon, WhatsApp, email, dan petunjuk arah memiliki target
   yang benar.
4. Peta tampil hanya saat URL embed valid tersedia.
5. Data kosong tidak menghasilkan tombol rusak.
6. Footer menuju halaman kontak dan menampilkan data konsisten.
7. Blok kontak beranda mengikuti `home_show_contact`.
8. Perubahan pengaturan admin terlihat di halaman publik setelah
   disimpan.
9. Tidak ada horizontal overflow pada viewport mobile.
10. Tidak ada error console/request akibat perubahan ini.

Gunakan fixture test, bukan data pribadi atau data produksi yang nyata.

## 9. TODO Implementasi

Agent wajib memelihara checklist ini atau memindahkannya ke rencana
implementasi kerja. Tandai selesai hanya setelah implementasi dan
verifikasi item tersebut benar-benar dilakukan.

### 8C.0 --- Audit dan persiapan

- [ ] Konfirmasi commit `main` terbaru dan branch dasar.
- [ ] Pastikan working tree bersih; jangan menimpa perubahan pengguna.
- [ ] Buat/pindah ke branch `feat/phase-8c-contacts` dari `main`
      terbaru. Jika branch sudah ada, audit isinya terlebih dahulu.
- [ ] Baca arsitektur, PRD, desain, keputusan, dan roadmap relevan.
- [ ] Audit konfigurasi pengaturan kontak dan labelnya.
- [ ] Audit `SiteSettingsService`, cache, dan `PhoneNumber`.
- [ ] Temukan file halaman kontak aktual tanpa menebak path.
- [ ] Audit layout publik, footer, beranda, dan shared props.
- [ ] Audit validasi URL saat ini.
- [ ] Audit test yang sudah ada dan identifikasi gap.
- [ ] Susun TODO implementasi dengan dependensi dan acceptance
      criteria.
- [ ] Catat baseline test relevan sebelum perubahan.

### 8C.1 --- Kontrak data/backend

- [ ] Tentukan kontrak props sesuai pola proyek.
- [ ] Pastikan data dibaca melalui service/mekanisme resmi.
- [ ] Gunakan `PhoneNumber` untuk URL telepon dan WhatsApp.
- [ ] Validasi email sebelum membuat `mailto:`.
- [ ] Audit dan perkuat validasi URL peta bila diperlukan.
- [ ] Tentukan allowlist host peta berdasarkan penyedia yang
      digunakan.
- [ ] Pastikan null/kosong ditangani secara konsisten.
- [ ] Hindari query atau service duplikat.
- [ ] Tambah/perbarui test backend.
- [ ] Jalankan test backend terkait dan perbaiki kegagalan akibat
      perubahan.

### 8C.2 --- Halaman publik

- [ ] Implementasikan/sempurnakan heading dan pengantar.
- [ ] Tampilkan alamat jika tersedia.
- [ ] Tampilkan telepon dan `tel:` jika valid.
- [ ] Tampilkan WhatsApp jika valid.
- [ ] Tampilkan email dan `mailto:` jika valid.
- [ ] Tampilkan jam layanan jika tersedia.
- [ ] Tampilkan peta hanya jika URL embed valid.
- [ ] Tampilkan petunjuk arah jika URL valid.
- [ ] Tampilkan tautan sosial yang tersedia dan valid.
- [ ] Tambahkan empty state untuk data parsial/kosong.
- [ ] Terapkan desain mobile-first yang sesuai.
- [ ] Pastikan tidak ada horizontal overflow.
- [ ] Pastikan label aksesibel.
- [ ] Tambahkan metadata mengikuti pola yang ada.
- [ ] Tambah/perbarui test frontend.
- [ ] Jalankan type-check dan test frontend terkait.

### 8C.3 --- Footer dan beranda

- [ ] Audit/hapus hardcode kontak dalam scope.
- [ ] Gunakan sumber data yang sama untuk footer dan halaman kontak.
- [ ] Pastikan link footer menuju `kontak.index`.
- [ ] Pastikan aksi telepon/WhatsApp memakai helper yang sama.
- [ ] Integrasikan/perbaiki blok kontak beranda sesuai desain yang
      ada.
- [ ] Pastikan `home_show_contact=true` menampilkan blok kontak.
- [ ] Pastikan `home_show_contact=false` menyembunyikan blok beranda
      saja.
- [ ] Pastikan data kosong tidak membuat link rusak.
- [ ] Tambah/perbarui test footer dan beranda.
- [ ] Jalankan regression test komponen terkait.

### 8C.4 --- QA akhir

- [ ] Jalankan Pest yang relevan.
- [ ] Jalankan Vitest yang relevan.
- [ ] Jalankan lint/check repository.
- [ ] Jalankan TypeScript type-check.
- [ ] Jalankan PHPStan sesuai script repository.
- [ ] Jalankan build produksi.
- [ ] Jalankan Playwright/E2E yang relevan.
- [ ] Jalankan regression suite yang disyaratkan.
- [ ] Periksa diff dan buang perubahan tidak terkait.
- [ ] Pastikan tidak ada dependency tak perlu.
- [ ] Pastikan tidak ada informasi kontak palsu/hardcoded.
- [ ] Perbarui dokumentasi dan roadmap sesuai konvensi repo.
- [ ] Catat hasil verifikasi aktual.
- [ ] Tandai TODO yang benar-benar selesai.
- [ ] Laporkan pekerjaan, file yang berubah, hasil test, dan
      keterbatasan.

## 10. Definition of Done

Phase 8C engineering-complete jika: 1. `/kontak` menggunakan data
Pengaturan Situs. 2. Telepon, WhatsApp, email, petunjuk arah, dan peta
hanya muncul bila valid. 3. Data kosong tidak menghasilkan
tombol/link/iframe rusak. 4. Footer dan beranda menggunakan sumber data
konsisten. 5. `home_show_contact` berfungsi sesuai definisi. 6. Tidak
ada tabel/pengaturan kontak duplikat. 7. Validasi URL peta aman dan
diuji. 8. UI responsif, aksesibel, dan sesuai design system. 9. Test
backend/frontend, type-check, lint, build, dan E2E yang diwajibkan
lulus. 10. Tidak ada regresi pada fase sebelumnya. 11. Dokumentasi
relevan diperbarui. 12. Semua TODO selesai dicentang; pekerjaan yang
belum selesai dijelaskan. 13. Laporan akhir hanya menyebut test yang
benar-benar dijalankan dan hasil aktual.

Engineering-complete tidak berarti konten produksi telah tervalidasi.
Alamat, telepon, email, jam layanan, dan URL peta harus dikonfirmasi
pihak paroki sebelum website dipublikasikan.

## 11. Instruksi Wajib untuk AI Agent

1. Baca dokumen ini secara penuh sebelum coding.
2. Periksa `git status`, branch aktif, dan commit terbaru `main`.
3. Buat dan gunakan branch `feat/phase-8c-contacts` dari `main`
   terbaru. Jangan menimpa branch yang sudah berisi pekerjaan.
4. Audit file dan test aktual.
5. Buat TODO terstruktur beserta dependensi dan acceptance criteria.
6. Kerjakan TODO secara berurutan; jangan mencentang sebelum selesai
   dan diverifikasi.
7. Hindari refactor luas yang tidak diperlukan.
8. Jangan mengubah Galeri/Dokumen kecuali ada kebutuhan lintas modul
   yang nyata; jelaskan alasannya.
9. Jalankan quality gates dan perbaiki regresi yang disebabkan
   perubahan.
10. Periksa diff dan dokumentasi sebelum menyelesaikan.
11. Berikan laporan akhir yang mencantumkan branch, commit dasar,
    implementasi, file utama, hasil test aktual, TODO tertunda, dan
    validasi konten yang masih dibutuhkan.

Jangan menyatakan pekerjaan selesai hanya karena kode sudah ditulis.
Pekerjaan selesai ketika acceptance criteria dan verifikasi relevan
terpenuhi.

## 12. Referensi Repository

- Repository:
  <https://github.com/Rumahkodingku/website-paroki-hspmtb-putussibau>
- Routes:
  <https://github.com/Rumahkodingku/website-paroki-hspmtb-putussibau/blob/main/routes/web.php>
- Site settings config:
  <https://github.com/Rumahkodingku/website-paroki-hspmtb-putussibau/blob/main/config/site-settings.php>
- Site settings service:
  <https://github.com/Rumahkodingku/website-paroki-hspmtb-putussibau/blob/main/app/Services/SiteSettingsService.php>
- Phone helper:
  <https://github.com/Rumahkodingku/website-paroki-hspmtb-putussibau/blob/main/app/Support/PhoneNumber.php>
- Settings controller:
  <https://github.com/Rumahkodingku/website-paroki-hspmtb-putussibau/blob/main/app/Http/Controllers/Settings/SettingsController.php>
- Settings request:
  <https://github.com/Rumahkodingku/website-paroki-hspmtb-putussibau/blob/main/app/Http/Requests/Settings/UpdateSettingsRequest.php>
- Indonesian labels:
  <https://github.com/Rumahkodingku/website-paroki-hspmtb-putussibau/blob/main/lang/id/site-settings.php>
- Phase 8A Gallery PR:
  <https://github.com/Rumahkodingku/website-paroki-hspmtb-putussibau/pull/11>
- Phase 8B Documents PR:
  <https://github.com/Rumahkodingku/website-paroki-hspmtb-putussibau/pull/12>
