# Revision Spec — Konsolidasi Data Identitas Paroki dan Pengaturan Situs

> **Repository:** `Rumahkodingku/website-paroki-hspmtb-putussibau`  
> **Target branch:** `main` (periksa ulang kondisi branch saat mulai bekerja)  
> **Status:** Implementation specification untuk AI coding agent  
> **Jenis pekerjaan:** Bug fix + konsolidasi ownership data + UX admin + regression tests  
> **Prioritas:** P1 — data consistency / admin usability  
> **Bahasa aplikasi:** Bahasa Indonesia

---

## 0. Instruksi Utama untuk AI Agent

Tugas Anda adalah memperbaiki duplikasi pengelolaan identitas paroki yang saat ini terlihat pada dua tempat:

1. **Admin → Profil Paroki → Informasi Profil** (`/admin/profil/informasi`)
2. **Admin → Pengaturan → Pengaturan Situs** (`/admin/pengaturan`)

Jangan langsung mengedit kode berdasarkan asumsi dari dokumen ini. Mulailah dengan mengaudit codebase aktual pada branch yang sedang aktif. Verifikasi setiap nama file, route, field, model, prop, test, dan pola UI sebelum mengubahnya. Codebase terbaru adalah sumber fakta implementasi; dokumen ini menetapkan hasil yang harus dicapai.

### 0.1 Dokumen proyek yang wajib dibaca

- `docs/PRD.md`
- `docs/ARCHITECTURE.md`
- `docs/DESIGN.md`
- `docs/DECISIONS.md`
- `AGENTS.md` di root jika tersedia
- `config/site-settings.php`
- `app/Services/SiteSettingsService.php`

Ikuti aturan arsitektur yang sudah berlaku. Jangan melakukan refactor besar atau perubahan di luar cakupan masalah ini. Pertahankan Laravel + Inertia + React + TypeScript, pola Form Request, named exports sesuai konvensi, Wayfinder, dan pola testing yang sudah dipakai.

### 0.2 Hasil akhir yang diwajibkan

- Setiap data identitas global hanya memiliki **satu tempat untuk diedit**.
- Data yang ditampilkan pada beberapa halaman publik mengambil nilai dari sumber resmi yang sama.
- `Profil Paroki → Informasi Profil` hanya mengelola informasi yang memang merupakan konten/profil paroki, bukan salinan identitas global.
- Tidak ada penghapusan data atau migrasi destruktif sebelum semua pemakaian nilai lama diaudit.
- Regression tests menutup konsistensi settings/profile dan tampilan publik.
- Perubahan terkait selesai tanpa mengubah perilaku fitur lain.

---

## 1. Konteks dan Gejala

Dua halaman admin yang ada pada screenshot sama-sama menyediakan field yang tampak merepresentasikan identitas paroki. Pengelola harus menebak halaman mana yang menjadi tempat edit yang benar. Bila field yang sama disimpan pada dua sumber berbeda, nilai berpotensi tidak sinkron dan rendering publik dapat menjadi tidak konsisten.

**Catatan penting:** jangan menyebut semua field sebagai duplikat hanya karena nama atau maknanya mirip. Audit dahulu apakah nilai benar-benar mewakili satu informasi global yang sama, atau merupakan dua konsep yang berbeda (contoh: deskripsi singkat untuk identitas situs vs narasi editorial yang lebih panjang di halaman profil).

### 1.1 Temuan codebase yang telah terverifikasi saat spesifikasi disusun

Repositori terbaru menggunakan beberapa bagian konkret berikut:

- `config/site-settings.php` mendefinisikan kelompok dan kunci pengaturan, termasuk grup `identitas` dan `kontak`.
- Grup `identitas` pada `config/site-settings.php` saat ini mencakup `parish_name`, `parish_short_name`, `tagline`, `short_description`, `motto_verse`, `patron_saint`, `logo`, dan `favicon`.
- Grup `kontak` saat ini mencakup `contact_address`, `contact_phone`, `contact_whatsapp`, `contact_email`, `office_hours`, dan `maps_link`.
- `app/Services/SiteSettingsService.php` dimaksudkan sebagai satu-satunya jalur aplikasi untuk membaca/menulis site settings serta mengatur cache.
- `app/Models/ParishProfile.php` merupakan model singleton profil. Kolom yang tercantum saat ini termasuk `name`, `short_description`, `description`, `history_description`, `address`, `phone`, `email`, `website`, `established_year`, `logo_media_id`, `logo_alt`, `cover_media_id`, `cover_alt`, `map_url`, `video_url`, dan `is_published`.
- `app/Http/Controllers/Profil/ParishProfileController.php` mengelola form Informasi Profil dan menggunakan `UpdateParishProfileRequest`.
- `app/Http/Controllers/Settings/SettingsController.php` mengelola `/admin/pengaturan`, menggunakan `SiteSettingsService` serta `UpdateSettingsRequest`.
- Halaman React yang relevan termasuk `resources/js/features/admin/profil/pages/informasi/index.tsx`, `resources/js/features/admin/profil/pages/informasi/components/parish-profile-form.tsx`, dan `resources/js/features/admin/pengaturan/pages/index.tsx`.
- Tests terkait termasuk `tests/Feature/Profil/ParishProfileTest.php`, `tests/Feature/Settings/SiteSettingsTest.php`, `tests/e2e/profil-paroki.spec.ts`, dan `tests/e2e/settings.spec.ts`.

Daftar ini adalah titik awal audit, bukan daftar lengkap file yang boleh/tidak boleh diubah. Verifikasi struktur terbaru sebelum implementasi.

---

## 2. Prinsip Desain: Single Source of Truth

Terapkan prinsip **Single Source of Truth (SSOT)** pada tingkat informasi domain:

1. Satu informasi global memiliki satu pemilik data kanonis.
2. Beberapa komponen/halaman publik boleh membaca informasi yang sama.
3. Jangan membuat nilai kedua hanya untuk memudahkan sebuah halaman merender data.
4. UI tidak boleh menyediakan beberapa form edit independen bagi nilai global yang sama.
5. Data di React/Inertia tetap server state; jangan menyalin prop server ke global store hanya untuk mengatasi masalah ini.
6. Jangan membuat service/repository baru jika service yang ada sudah melayani kebutuhan.
7. Jangan mengubah semua field `ParishProfile` secara massal tanpa menilai kegunaan domain masing-masing.

### 2.1 Pemilik data yang disarankan

Gunakan mapping di bawah sebagai keputusan target, lalu cocokkan dengan kebutuhan yang sudah terimplementasi:

| Konsep                                        | Pemilik kanonis yang dituju                                                  | Aturan                                                                                                                                      |
| --------------------------------------------- | ---------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------- |
| Nama resmi paroki yang digunakan lintas situs | `site_settings.parish_name` melalui `SiteSettingsService`                    | Satu tempat edit: Pengaturan Situs → Identitas Paroki                                                                                       |
| Nama singkat/akronim global                   | `site_settings.parish_short_name`                                            | Satu tempat edit: Pengaturan Situs → Identitas Paroki                                                                                       |
| Tagline global situs                          | `site_settings.tagline`                                                      | Satu tempat edit: Pengaturan Situs → Identitas Paroki                                                                                       |
| Deskripsi singkat global situs                | `site_settings.short_description`                                            | Satu tempat edit bila definisinya sama dengan ringkasan pada Profil; jika berbeda, dokumentasikan perbedaan semantik dan label dengan jelas |
| Motto/ayat identitas global                   | `site_settings.motto_verse`                                                  | Satu tempat edit: Pengaturan Situs → Identitas Paroki                                                                                       |
| Patron santo identitas global                 | `site_settings.patron_saint`                                                 | Satu tempat edit: Pengaturan Situs → Identitas Paroki                                                                                       |
| Logo situs                                    | `site_settings.logo`                                                         | Satu tempat edit untuk logo yang dipakai header, footer, metadata; jangan memiliki dua kontrol edit aktif untuk logo global                 |
| Favicon situs                                 | `site_settings.favicon`                                                      | Satu tempat edit: Pengaturan Situs → Identitas Paroki                                                                                       |
| Alamat kontak resmi paroki                    | `site_settings.contact_address`                                              | Satu tempat edit untuk Kontak, Footer, Beranda, dan identitas lokasi publik yang menggunakan alamat sekretariat yang sama                   |
| Telepon sekretariat resmi                     | `site_settings.contact_phone`                                                | Satu tempat edit lintas halaman                                                                                                             |
| Email sekretariat resmi                       | `site_settings.contact_email`                                                | Satu tempat edit lintas halaman                                                                                                             |
| Tautan peta untuk kontak/sekretariat          | `site_settings.maps_link`                                                    | Satu tempat edit; gunakan alur normalisasi/validasi map yang sudah ada                                                                      |
| Deskripsi editorial/profil panjang            | `ParishProfile.description` bila masih menjadi konsep yang digunakan         | Bukan pengganti otomatis untuk `short_description`; tetapkan semantik dan label                                                             |
| Narasi sejarah panjang                        | `ParishProfile.history_description` atau modul sejarah yang saat ini dipakai | Tetap di domain profil/sejarah; jangan dipindahkan ke settings                                                                              |
| Tahun berdiri                                 | `ParishProfile.established_year` bila digunakan halaman profil               | Data editorial/profile, bukan setting global kecuali implementasi aktual membuktikan sebaliknya                                             |
| Foto sampul profil                            | `ParishProfile.cover_media_id` dan `cover_alt` bila digunakan                | Milik halaman profil; bukan logo/favikon global                                                                                             |
| Video profil                                  | `ParishProfile.video_url` bila digunakan                                     | Milik konten profil                                                                                                                         |
| Status publikasi profil                       | `ParishProfile.is_published`                                                 | Milik lifecycle profil; tidak dipindah ke site settings                                                                                     |

**Aturan konflik:** bila `ParishProfile.name` berisi nama global yang sama dengan `site_settings.parish_name`, `site_settings.parish_name` menjadi nilai kanonis untuk identitas global. Bila `ParishProfile.short_description` dan `site_settings.short_description` secara produk memiliki makna berbeda, jangan satukan nilai secara membabi buta; tentukan apakah satu harus dihapus, atau tetap sebagai copy editorial yang berbeda dan diberi nama/label berbeda agar admin memahami perbedaannya.

### 2.2 Alamat, telepon, email dan peta

`ParishProfile` memiliki field `address`, `phone`, `email`, dan `map_url`, sementara settings memiliki `contact_address`, `contact_phone`, `contact_email`, dan `maps_link`. Audit seluruh read/write call sites.

- Untuk **kontak resmi/sekretariat** yang digunakan oleh halaman Kontak, Footer, Beranda dan CTA kontak, gunakan field `site_settings` sebagai sumber utama.
- Field pada `ParishProfile` hanya boleh dipertahankan sebagai data terpisah jika memang mewakili informasi yang berbeda (misalnya alamat fisik profil historis atau lokasi berbeda), dan perbedaan tersebut harus nyata serta dijelaskan di UI. Jangan mempertahankan duplikasi semantik yang sama tanpa kebutuhan.
- Jangan menghapus kolom profile yang berisi data produksi sebelum menyiapkan strategi migrasi data dan mengganti semua consumer.
- Untuk peta, pertahankan validasi dan normalisasi yang ada. Jangan menyimpan raw iframe; simpan URL embed/tautan yang telah diekstraksi dan divalidasi sesuai aturan yang saat ini berlaku.

---

## 3. Keputusan UX Admin

### 3.1 Halaman Pengaturan Situs

`/admin/pengaturan` tetap menjadi pusat pengelolaan konfigurasi global situs. Pertahankan pola tab dan field yang didefinisikan oleh `config/site-settings.php` serta metadata yang disediakan backend.

Grup yang ada saat audit:

- Identitas Paroki
- Kontak
- Media Sosial
- SEO Dasar
- Beranda
- Privasi

Pengaturan untuk nama resmi/pendek, tagline, logo, favicon, serta identitas global harus tetap berada di grup Identitas Paroki. Kontak resmi, jam pelayanan, serta tautan Maps berada di grup Kontak.

Jangan menambahkan hard-coded daftar kunci kedua pada React apabila `config/site-settings.php` atau metadata server sudah menjadi sumber daftar. Pertahankan validasi payload terhadap daftar key yang dikenal.

### 3.2 Profil Paroki → Informasi Profil

Halaman `/admin/profil/informasi` harus fokus pada detail profil paroki yang tidak merupakan konfigurasi global situs.

Target UX:

- Tidak menyediakan form kedua untuk mengubah nama global, nama pendek global, tagline global, motto/ayat identitas global, patron santo global, logo global, atau favicon.
- Tidak menyediakan form kedua untuk mengubah alamat/telepon/email/peta sekretariat yang sama dengan data Pengaturan Situs.
- Field konten seperti deskripsi editorial panjang, narasi sejarah, tahun berdiri, sampul profil, video profil, dan status publikasi tetap dipertahankan bila memang dikonsumsi oleh public profile dan sesuai model sekarang.
- Field yang dipertahankan harus mempunyai label yang akurat dan helper text yang menjelaskan cakupan informasinya.
- Jangan meninggalkan halaman kosong atau rusak setelah field duplikat dihapus.

### 3.3 Pilih perlakuan halaman Informasi Profil setelah audit

Pilih satu alternatif berikut berdasarkan kebutuhan codebase aktual:

**Alternatif A — Hilangkan form edit Informasi Profil dan arahkan ke halaman profil yang benar**

Gunakan hanya jika seluruh fungsi halaman tersebut ternyata hanya duplikasi pengaturan global atau kontennya sudah dikelola di modul sejarah/profil lain. Bila route/sidebar/test saat ini menganggap halaman ada, perbarui navigasi secara konsisten dan pertahankan redirect yang masuk akal bila dibutuhkan.

**Alternatif B — Pertahankan Informasi Profil sebagai form konten profil** (**default yang direkomendasikan jika model `ParishProfile` masih memiliki konten yang diperlukan**)

Hapus/ubah hanya field yang berduplikasi semantik dengan settings. Halaman masih mengelola konten profil editorial seperti deskripsi panjang, tahun berdiri, cover, video, dan publish state jika memang dipakai.

AI agent MUST memilih berdasarkan consumer aktual dan test yang tersedia, bukan preferensi estetika. Jangan menghapus navigasi atau seluruh form hanya agar duplikasi hilang, jika fitur profil masih dibutuhkan.

### 3.4 Jika identitas global perlu tampak pada halaman Profil

Boleh menampilkan ringkasan identitas global sebagai **read-only**, menggunakan data dari `SiteSettingsService` yang dikirim server melalui Inertia prop yang bertipe jelas. Ringkasan sebaiknya menyediakan tautan menuju Pengaturan Situs → Identitas Paroki.

Jangan:

- membuat form read/write kedua;
- mengirim data settings ke React lewat fetch internal baru;
- menggunakan dua state React untuk menyimpan salinan setting dan profile;
- melakukan sinkronisasi dua arah melalui `useEffect`;
- menulis nilai ke dua tabel ketika admin menyimpan.

---

## 4. Audit Wajib Sebelum Mengubah Kode

Laksanakan audit dan tulis temuan singkat dalam ringkasan akhir. Jangan membuat laporan terpisah kecuali dibutuhkan oleh repo.

### 4.1 Telusuri seluruh key identitas dan kontak

Cari pembacaan maupun penulisan terhadap:

- `parish_name`
- `parish_short_name`
- `tagline`
- `short_description`
- `motto_verse`
- `patron_saint`
- `logo`
- `favicon`
- `contact_address`
- `contact_phone`
- `contact_whatsapp`
- `contact_email`
- `maps_link`
- field profile `name`, `short_description`, `address`, `phone`, `email`, `map_url`, `logo_media_id`, `logo_alt`

Telusuri minimal seluruh:

- `app/`, `config/`, `database/`, `resources/js/`, `routes/`, `lang/`, `tests/`
- `SiteSettingsService` consumers
- `ParishProfile` consumers
- Inertia shared props / Resource public
- Seeder dan factory
- Header/navbar, footer, Beranda, Kontak, SEO/head, halaman Profil

Gunakan pencarian repo lokal (misalnya `rg`) bila tersedia. Jangan hanya mencari label UI karena consumer dapat menggunakan key atau model property.

### 4.2 Buat matriks consumer

Sebelum implementasi, susun matriks internal seperti berikut:

| Consumer            | Field yang dibaca sekarang | Sumber target                                                  | File aktual | Tindakan                      |
| ------------------- | -------------------------- | -------------------------------------------------------------- | ----------- | ----------------------------- |
| Public header       | audit                      | site settings                                                  | verifikasi  | ubah hanya bila diperlukan    |
| Public footer       | audit                      | site settings untuk kontak/identitas global                    | verifikasi  | konsolidasikan                |
| Home contact        | audit                      | site settings                                                  | verifikasi  | konsolidasikan                |
| Public profile      | audit                      | settings untuk identitas global; ParishProfile untuk editorial | verifikasi  | pisahkan tanggung jawab       |
| Admin settings      | audit                      | site settings                                                  | verifikasi  | pertahankan pusat edit global |
| Admin profile form  | audit                      | hanya data profil                                              | verifikasi  | hapus kontrol edit duplikat   |
| Public contact page | audit                      | site settings                                                  | verifikasi  | konsolidasikan                |
| SEO metadata        | audit                      | settings/SEO field sesuai kontrak existing                     | verifikasi  | jangan buat metadata kedua    |

---

## 5. Implementasi Backend

### 5.1 `SiteSettingsService` tetap sebagai single access point

Gunakan service yang sudah tersedia untuk membaca dan menulis site settings. Jangan membaca tabel `site_settings` secara langsung di controller/component baru jika service sudah menjadi kontrak proyek.

Pastikan:

- key baru/tetap yang digunakan sudah ada di `config/site-settings.php`;
- kelompok dan tipe tidak terduplikasi pada beberapa daftar;
- penyimpanan settings tetap melalui `UpdateSettingsRequest` dan `SettingsController`;
- invalidasi cache tetap berjalan melalui `SiteSettingsService` setelah write;
- key yang tidak dikenal tetap ditolak/dibuang sesuai kontrak existing;
- validasi dan normalisasi maps yang sekarang berlaku tidak dilemahkan.

### 5.2 `ParishProfile` dan `ParishProfileController`

Jangan mengubah model/controller sampai diketahui field profile mana yang diperlukan untuk konten editorial.

Jika `ParishProfile` tetap memiliki kolom yang tidak lagi boleh diedit karena duplikat:

1. Update form React agar field tersebut tidak dapat diedit dari Profil.
2. Update request validation dan persistence dengan hati-hati agar field tersebut tidak secara tidak sengaja terhapus atau tertimpa nilai kosong saat seluruh form disimpan.
3. Jangan mengandalkan UI saja sebagai proteksi. Payload yang dibuat manual harus tidak dapat menulis ulang field global melalui endpoint profile.
4. Putuskan apakah field lama tetap disimpan sementara untuk kompatibilitas atau perlu migrasi penghapusan setelah semua consumer dipindah.
5. Bila field model tidak lagi digunakan di mana pun, hapus lewat migration baru hanya setelah data aman dan regression tests membuktikannya.

**Penting untuk partial update:** bila endpoint saat ini mengharapkan payload penuh dari form, revisi payload/validation harus memastikan field yang dikeluarkan dari UI tidak diisi `null` atau nilai kosong yang kemudian menimpa data existing. Pilih salah satu pendekatan yang konsisten dengan gaya request existing:

- Form profile hanya mengirim field profile yang benar-benar dimilikinya, sementara field non-owned tidak masuk ke fillable payload; atau
- Backend mempertahankan nilai non-owned yang lama dan tidak menerima write pada field tersebut.

Jangan membuat save profile yang memperbarui data global melalui `SiteSettingsService` secara paralel. Global settings disimpan hanya oleh endpoint settings.

### 5.3 Public data contract

Jika public profile perlu nama paroki/identity settings:

- bangun data di sisi Laravel memakai service yang ada;
- gunakan prop Inertia eksplisit, typed, dan sekecil yang diperlukan;
- update TypeScript prop/type bersamaan;
- jangan mengirim raw Eloquent model atau seluruh settings yang tidak dipakai;
- semua halaman publik yang butuh identity harus mengonsumsi sumber yang sama, bukan memilih fallback antara dua sumber yang dapat berbeda.

Hindari pola seperti `profile.name || settings.parish_name` sebagai solusi permanen. Fallback dari dua sumber yang keduanya dapat diedit menciptakan dua pemilik nilai dan menyembunyikan inkonsistensi. Jika fallback diperlukan saat migrasi, beri batas waktu/tujuan jelas, test, dan hapus setelah migrasi.

### 5.4 Perubahan database

Jangan menambah migration hanya untuk mengubah UI jika model dan data canonical tidak perlu berubah.

Jika audit membuktikan harus menghapus kolom duplikat:

- buat migration baru; jangan edit migration yang sudah dijalankan;
- sebelum drop, data yang relevan harus disalin/dikonsolidasikan secara deterministik;
- bila nilai `site_settings` dan `parish_profiles` berbeda, jangan menimpa salah satunya secara senyap. Tentukan nilai mana yang benar-benar sudah dipakai publik, logika backfill, dan/atau pertahankan data profile sampai disetujui;
- migration harus aman untuk lingkungan existing; jangan mengasumsikan database kosong;
- seeder/factory harus mengikuti model ownership baru;
- dokumentasikan perubahan arsitektur di `docs/DECISIONS.md` jika keputusan baru melampaui aturan PRD.

---

## 6. Implementasi Frontend (React + Inertia)

### 6.1 Pengaturan Situs

- Pertahankan tab, labels, hints, dan field rendering berbasis konfigurasi/metadata server.
- Jangan hard-code ulang daftar key settings di komponen jika sudah berasal dari `config/site-settings.php` dan prop Inertia.
- Identitas dan kontak global dapat diedit hanya di halaman ini.
- Pastikan field masih tersimpan melalui method/rute Wayfinder yang dihasilkan dan tidak hard-code URL baru.

### 6.2 Form Informasi Profil

- Hapus field identitas/kontak global yang duplikat sesuai audit dan matriks ownership final.
- Pertahankan field yang merupakan konten profil dan punya consumer nyata.
- Perbarui `emptyParishProfileFormData`, `parishProfileToFormData`, `ParishProfileFormData`, labels/hints, dan props terkait sesuai bentuk form akhir.
- Hapus field dari form data dan request jika memang benar-benar bukan bagian kontrak profile; jika kolom tetap dibutuhkan untuk kompatibilitas, pastikan endpoint profile tidak bisa mengubah nilainya.
- Jangan meninggalkan label, validasi error, `htmlFor`, test ID, atau copy yang mengacu pada field yang telah dihapus.
- Pertahankan `useForm` Inertia, pola komponen form yang ada, tombol submit/loading/error state dan accessibility.

### 6.3 UX untuk mengarahkan admin

Jika halaman Profil menampilkan identitas global secara read-only:

- beri keterangan singkat: `Identitas umum paroki dikelola melalui Pengaturan Situs.`
- berikan link nyata menggunakan Wayfinder ke halaman Pengaturan Situs;
- pastikan link menggunakan label yang jelas seperti `Buka Pengaturan Situs`;
- tidak menggunakan disabled inputs yang menyerupai form edit bila tidak diperlukan. Lebih baik tampilkan sebagai ringkasan read-only.

Jangan menampilkan CTA ke settings jika halaman tidak mempunyai kebutuhan ringkasan identitas tersebut.

### 6.4 Konsistensi visual

Ikuti `docs/DESIGN.md` dan primitive yang sudah ada. Jangan menambah component library baru atau melakukan redesign halaman yang tidak terkait. Form tetap responsif, label terhubung ke input, error state terlihat, dan keyboard focus berfungsi.

---

## 7. Strategi Migrasi Data

Bagian ini hanya dikerjakan bila audit membuktikan nilai global memang disimpan di kedua model/database.

### 7.1 Inventaris nilai

Untuk setiap pasangan nilai yang berpotensi duplikat, bandingkan:

- nilai `site_settings` terkini;
- nilai `ParishProfile` terkini;
- sumber yang dipakai masing-masing halaman publik saat ini;
- apakah nilai kosong atau hanya contoh/test data;
- apakah perbedaan nilai disengaja secara domain.

Field yang diperiksa minimal: nama, deskripsi singkat, alamat, telepon, email, logo, dan map URL.

### 7.2 Aturan konsolidasi

- **Nama dan identitas situs:** canonical ke `site_settings`.
- **Kontak sekretariat/site:** canonical ke `site_settings`.
- **Konten editorial profil:** tetap di `ParishProfile` jika digunakan dan berbeda semantik.
- Jangan menyalin konten editorial panjang ke settings.
- Jangan menghapus media/file yang digunakan field lain. Untuk logo media, cek apakah media asset juga dipakai profile cover atau record lain sebelum menghapus file.

### 7.3 Konflik

Jika dua nilai berbeda:

1. Jangan memilih nilai hanya berdasarkan mana yang tidak kosong.
2. Telusuri consumer publik dan keputusan PRD.
3. Untuk data kontak resmi, ikuti kontrak PRD bahwa kontak halaman Kontak/footer/Beranda berasal dari settings; periksa setting saat ini dan konfirmasi tidak akan kehilangan nilai yang diperlukan.
4. Untuk nama yang dilihat di halaman profil, canonical adalah `site_settings.parish_name`; pastikan test dan render diubah agar tidak mengambil nama profile kedua.
5. Jika data produksi berpotensi hilang atau tidak dapat diselesaikan dengan aman, pertahankan field lama pada tahap pertama sambil memutus write path dan report sebagai migration follow-up. Jangan mengarang nilai paroki.

---

## 8. Testing Requirements

Perubahan wajib disertai unit/feature/E2E regression tests mengikuti test runner dan pola yang tersedia. Gunakan factory, jangan bergantung pada seed real data.

### 8.1 Unit/Feature: Site Settings

Periksa tests yang sudah ada sebelum menambah test duplikat. Pastikan test meliputi:

- `SiteSettingsService` masih membaca key canonical.
- Update settings menulis satu row untuk setiap key (key unik) dan nilai tersimpan benar.
- Cache invalidation tetap membuat pembacaan setelah save mengembalikan nilai terbaru.
- Key tak dikenal tidak dapat ditulis.
- Validasi maps/settings tidak melemah.

### 8.2 Feature: Profil

Tambahkan/ubah test agar membuktikan:

- Form edit profil hanya mengekspos field yang menjadi milik profil; identitas/settings global tidak dapat diedit lewat payload profile.
- Request yang mencoba mengirim `name`, `address`, `phone`, `email`, `map_url`, atau field global lain yang telah dikeluarkan (sesuaikan mapping final) tidak mengubah nilai canonical di settings.
- Save profil tetap bekerja bagi field profil yang dipertahankan.
- Perubahan profil tidak membuat row settings kedua/duplikat.
- Jika profil masih memakai ringkasan identitas read-only, props-nya berasal dari `SiteSettingsService`, bukan nilai profile kedua.

Jangan menguji field yang memang diputuskan tetap terpisah sebagai error bila field itu bukan duplikat menurut mapping final.

### 8.3 Public contract consistency

Test bahwa nilai canonical yang sama dirender konsisten di tempat yang menggunakannya, minimal sesuai use cases di codebase:

- header/logo global;
- footer;
- halaman Beranda / section kontak;
- halaman Kontak;
- halaman `/profil` untuk nama/identity yang ditampilkan.

Pilih test Feature/Inertia atau UI component/E2E berdasarkan pola test existing. Hindari membuat E2E besar jika test Feature cukup membuktikan kontraknya.

### 8.4 E2E admin

Update `tests/e2e/profil-paroki.spec.ts` dan/atau `tests/e2e/settings.spec.ts` sesuai rancangan akhir agar membuktikan:

- `/admin/pengaturan` memiliki field identitas global;
- `/admin/profil/informasi` tidak lagi punya input edit untuk informasi global yang sama;
- field profil yang memang dipertahankan tetap terlihat dan dapat disimpan;
- update identitas melalui Pengaturan Situs tampil konsisten di halaman publik setelah reload;
- navigasi/tautan ke settings bila ditambahkan bekerja;
- tidak ada label/field duplikat pada form yang terlihat oleh admin.

Test E2E harus deterministik, menghindari data lintas test yang mengotori database, dan memakai helper login/session existing.

### 8.5 Command checks

Baca `composer.json`, `package.json`, `AGENTS.md`, workflow CI, dan dokumen arsitektur untuk menentukan command aktual. Jalankan pemeriksaan relevan, lalu test suite yang sesuai. Jangan menebak nama script.

Laporkan command yang benar-benar dijalankan dan hasilnya; jangan mengklaim test lolos jika tidak dijalankan.

---

## 9. Acceptance Criteria

Gunakan ID berikut untuk test, commit/PR description, dan ringkasan kerja.

### DATA-SSOT-01 — Satu tempat edit identitas global

Identitas global (nama resmi, nama singkat, tagline, motto/ayat global, patron santo, logo, favicon) hanya memiliki satu form edit: Pengaturan Situs → Identitas Paroki.

### DATA-SSOT-02 — Satu tempat edit kontak resmi

Alamat, telepon, WhatsApp, email, jam pelayanan dan tautan Maps untuk kontak/sekretariat hanya diedit dari Pengaturan Situs → Kontak, kecuali ada konsep lokasi lain yang terbukti berbeda di domain.

### DATA-SSOT-03 — Tidak ada write kedua

Endpoint Form Informasi Profil tidak dapat menulis field yang ownership-nya dipindah ke site settings, walaupun request manual mengirim field tersebut.

### DATA-SSOT-04 — Profil tidak kehilangan fungsi editorial

Field profil unik yang masih dikonsumsi, seperti deskripsi editorial panjang, narasi sejarah, tahun berdiri, cover, video, dan status publikasi, tetap berfungsi sesuai audit final.

### DATA-SSOT-05 — Public consumers konsisten

Nilai global ditampilkan dari sumber canonical di setiap consumer publik yang relevan. Tidak ada fallback permanen yang memilih dari dua nilai editable.

### DATA-SSOT-06 — Cache settings valid

Setelah Pengaturan Situs disimpan, cache settings diinvalidasi dan pembacaan berikutnya mengembalikan nilai terbaru.

### DATA-SSOT-07 — Migration aman

Tidak ada data produksi yang dihapus sebelum consumer dan strategi backfill diperiksa. Migration baru digunakan bila benar-benar diperlukan; migration terdahulu tidak diedit.

### DATA-SSOT-08 — UI dapat dipahami

Admin bisa mengetahui dengan jelas bahwa identitas/kontak global dikelola melalui Pengaturan Situs dan form Profil hanya mengelola konten profil.

### DATA-SSOT-09 — Regression suite

Test feature serta test E2E yang terdampak lulus; test lama yang masih mewakili perilaku yang benar tidak dihapus untuk menghindari kegagalan.

### DATA-SSOT-10 — Scope terkendali

Tidak ada refactor atau fitur yang tidak berkaitan dengan masalah duplikasi ini.

---

## 10. Batasan / Hal yang Tidak Boleh Dilakukan

AI agent MUST NOT:

- membuat tabel baru untuk menyalin settings yang sudah ada;
- melakukan sinkronisasi dua arah antara `ParishProfile` dan `site_settings` saat save;
- mempertahankan dua form edit untuk nilai global yang sama;
- menyembunyikan konflik dengan pola fallback multi-source permanen;
- membaca/menulis `site_settings` secara langsung ketika aturan codebase mengharuskan `SiteSettingsService`;
- menaruh server data settings ke Zustand/global client state;
- menambahkan REST API internal untuk halaman Inertia;
- mengedit migration lama yang sudah dijalankan;
- menghapus nilai/media yang masih dipakai;
- mengarang data asli paroki atau mengganti dengan contoh hard-coded;
- menghapus test lama sekadar agar test suite hijau;
- mengubah label/route/navigation yang tidak berkaitan tanpa alasan teknis;
- melakukan mass refactor ke modul Profil atau Pengaturan di luar kebutuhan task.

---

## 11. Urutan Implementasi yang Disarankan

### Step 1 — Audit

- Baca dokumen dan file yang disebut di atas.
- Audit route/admin form/model/request/settings config/service/public consumer.
- Telusuri tests yang ada.
- Catat mapping final “field → owner → consumers”.

### Step 2 — Putuskan cakupan field

- Bedakan field identitas global, kontak global, dan konten editorial profil.
- Konfirmasi consumer aktual sebelum menghapus/mengganti field.
- Pilih Alternatif A/B untuk halaman Informasi Profil.

### Step 3 — Konsolidasikan read path

- Ubah public consumer agar identitas/kontak global yang relevan menggunakan `SiteSettingsService` atau data Inertia yang sudah dibangun darinya.
- Hindari fetch internal baru.
- Update Resource/props/TypeScript types jika bentuk data berubah.

### Step 4 — Konsolidasikan write path

- Pengaturan Situs tetap menjadi satu-satunya jalur penyimpanan identitas/kontak global.
- Hapus field duplikat dari form profil dan batasi request server.
- Pastikan nilai non-owned tidak terhapus akibat payload yang berkurang.

### Step 5 — Database dan data existing

- Hanya jika perlu, lakukan backfill/migration baru yang aman.
- Jika tidak perlu menghapus kolom pada tahap ini, pilih UI + write-path consolidation dan dokumentasikan bila field legacy dipertahankan untuk kompatibilitas.

### Step 6 — Tests

- Update Feature tests.
- Update tests TypeScript/komponen yang terdampak.
- Update E2E untuk ketiadaan input duplikat dan konsistensi publik.

### Step 7 — Validasi akhir

- Jalankan formatting/lint/type checking sesuai script aktual repo.
- Jalankan test Feature yang terdampak.
- Jalankan UI/component tests yang terdampak.
- Jalankan E2E relevan; jangan menjalankan seluruh E2E tanpa seleksi jika workflow proyek mahal, kecuali diperlukan oleh perubahan luas.
- Review `git diff` untuk memastikan perubahan fokus dan tidak ada data/field yang hilang.

### Step 8 — Ringkasan

Sampaikan:

1. File yang diubah.
2. Ownership final dari setiap kelompok field.
3. Apakah halaman Informasi Profil dipertahankan sebagai form konten profil atau diarahkan ulang.
4. Apakah migration diperlukan dan cara penanganan data existing.
5. Test command yang dijalankan beserta hasil faktual.
6. Risiko/follow-up yang masih tersisa.

---

## 12. Definition of Done

Pekerjaan dianggap selesai hanya jika seluruh kondisi berikut terpenuhi:

- [ ] Ownership field sudah diaudit terhadap consumer aktual.
- [ ] Tidak ada identitas global yang sama dapat diedit dari dua form admin.
- [ ] Kontak resmi tidak dapat diedit dari dua form admin.
- [ ] Public rendering menggunakan sumber canonical.
- [ ] Field editorial profil tetap berfungsi.
- [ ] Request profile tidak dapat mengubah field yang sudah bukan miliknya.
- [ ] Cache settings tetap benar setelah save.
- [ ] Tidak ada migration destruktif tanpa strategi data.
- [ ] Feature/E2E regression tests untuk behavior yang terdampak telah diperbarui.
- [ ] Typecheck, lint/format check, dan tests yang relevan telah dijalankan dan hasilnya dicatat.
- [ ] Diff terakhir ditinjau, tanpa perubahan out-of-scope.
- [ ] Jika keputusan akhir mengubah ownership dari dokumen arsitektur/PRD, keputusan tersebut dicatat di `docs/DECISIONS.md`.

---

## 13. Prompt Eksekusi Singkat

> Baca seluruh `REVISION-DATA-DUPLICATION-PROFIL-PENGATURAN.md`, lalu audit codebase terbaru sebelum coding. Susun mapping field → canonical owner → consumer berdasarkan kode aktual. Implementasikan konsolidasi Single Source of Truth untuk identitas dan kontak paroki: `site_settings` melalui `SiteSettingsService` untuk nilai global, dan `ParishProfile` hanya untuk konten editorial yang memang unik. Hapus write path duplikat dari form Informasi Profil tanpa merusak konten profil atau menimpa data existing. Gunakan migration baru hanya jika diperlukan dan aman. Update Inertia props, TypeScript types, labels/hints, Feature/component/E2E tests. Ikuti `docs/PRD.md`, `docs/ARCHITECTURE.md`, `docs/DESIGN.md`, `docs/DECISIONS.md`, pola Wayfinder, dan scripts aktual repo. Jangan melakukan refactor di luar scope. Jangan mengklaim tests lulus jika tidak menjalankannya. Akhiri dengan ringkasan file, keputusan, migration, test command/result, dan risiko tersisa.
