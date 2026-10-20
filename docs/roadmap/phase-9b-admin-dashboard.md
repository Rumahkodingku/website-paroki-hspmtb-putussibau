# PHASE 9B — Admin Dashboard

> **Status:** Done — engineering complete (branch `feat/phase-9b-admin-dashboard`)
> **Prioritas:** High  
> **Prasyarat:** PHASE 9A — Homepage / Beranda Publik selesai dan diverifikasi  
> **Dokumen terkait:** `docs/PRD.md`, `docs/ARCHITECTURE.md`, `docs/DESIGN.md`, `docs/DECISIONS.md`, `docs/roadmap/README.md`  
> **Referensi UI:** mockup Admin Dashboard terakhir yang disetujui pengguna.

## 1. Tujuan

Dokumen ini adalah spesifikasi implementasi bagi AI agent untuk mengganti placeholder Admin Dashboard menjadi pusat ringkasan operasional website Paroki HSPMTB Putussibau.

Dashboard harus membantu admin menjawab:

1. Bagaimana kondisi konten utama website?
2. Agenda apa yang akan berlangsung?
3. Konten atau konfigurasi apa yang perlu ditindaklanjuti?
4. Bagaimana cara cepat menuju pekerjaan yang sering dilakukan?

Dashboard adalah **agregator read-only**, bukan CMS kedua, analytics platform, atau audit log.

## 2. Keputusan dan batasan wajib

- PHASE 9B hanya mengerjakan Admin Dashboard. PHASE 9A tetap terpisah.
- Jangan membuat CRUD Dashboard, tabel statistik, atau counter yang menduplikasi data sumber.
- Hitung statistik di backend; jangan menghitung dari daftar paginasi di frontend.
- Jangan hard-code angka, agenda, berita, nama, atau konten contoh.
- Jangan menambahkan analytics, grafik trafik, pengunjung unik, audit log admin, atau sistem notifikasi baru.
- `created_at` dan `updated_at` bukan audit log. Panel gabungan konten harus dinamai **Konten Terbaru**, bukan “Aktivitas Admin”.
- Gunakan status dan scope aktual setiap modul. Jangan memakai satu filter `published` generik.
- Pertahankan middleware, authentication, authorization, dan aturan akun aktif yang sudah ada.
- Jangan mengirim data sensitif dari tabel `users`.
- Gunakan Laravel 13, Inertia.js 3, React 19, TypeScript, Tailwind CSS 4, Pest, Vitest, dan Playwright sesuai stack proyek.
- Jangan menambahkan dependency atau abstraksi baru tanpa kebutuhan yang jelas.

### Status modul yang perlu diverifikasi

Audit sebelumnya menemukan:

- `posts`: `draft`, `scheduled`, `published`, `archived`; kelayakan publik juga dapat bergantung pada `published_at`.
- `events`: `is_published` terpisah dari status kegiatan seperti `scheduled`, `cancelled`, `postponed`.
- `services`: memakai `is_active`; aturan Google Form/registrasi perlu diperiksa.
- `gallery_albums`: memiliki `is_published`.
- `media`: status pemrosesan `pending`, `processing`, `ready`, `failed`.
- `documents`: mempunyai status publikasi, `expires_at`, dan `download_count`.
- Jadwal Misa rutin, Misa khusus, dan pemberitahuan jadwal merupakan sumber yang berbeda.

Semua hal di atas harus dicek ulang pada branch aktif. Jangan mengasumsikan status atau nama field tanpa membaca implementasi aktual.

## 3. Wajib dibaca sebelum coding

AI agent harus membaca:

1. `docs/PRD.md`
2. `docs/ARCHITECTURE.md`
3. `docs/DESIGN.md`
4. `docs/DECISIONS.md`
5. `docs/roadmap/README.md`
6. Halaman Dashboard aktif, sebelumnya `resources/js/features/admin/dashboard/pages/index.tsx`
7. `tests/Feature/DashboardTest.php`
8. `routes/web.php`, middleware, dan route names admin
9. Controller/pola props Inertia pada halaman admin yang sudah ada
10. Model, migration, enum, scope, factory, dan tests untuk `Post`, `Event`, `Document`, `MassSchedule`, `SpecialMass`, `ScheduleNotice`, `Service`, `Media`, `GalleryAlbum`, `GalleryPhoto`, `SiteSetting`, dan bila relevan `User`
11. Layout/sidebar admin, card, badge status, tabel, empty state, format tanggal, dan icon yang tersedia
12. `composer.json`, `package.json`, serta workflow CI aktif.

Path di atas adalah petunjuk awal. Jika struktur berubah, cari implementasi setara dan ikuti pola aktual; jangan membuat file duplikat.

## 4. Kondisi awal dari audit sebelumnya

Pada snapshot sebelumnya, PHASE 9A telah digabungkan ke `main` (commit yang teridentifikasi: `f4229fb`), halaman Dashboard masih menggunakan `PlaceholderPattern`, dan `tests/Feature/DashboardTest.php` baru mencakup akses dasar. PRD menyebut ringkasan konten, agenda mendatang, pintasan, dan pengingat kualitas konten.

Verifikasi ulang branch/commit dan PRD terbaru sebelum coding. Jangan menganggap snapshot sebelumnya pasti identik dengan codebase aktif.

## 5. Target UI/UX

Gunakan mockup Dashboard terakhir yang disetujui sebagai acuan. Prinsip:

- Bersih, lapang, responsif, tidak padat.
- Hierarki: header/sambutan → kartu ringkasan → aksi cepat dan agenda → berita dan perhatian → konten terbaru.
- Gunakan sidebar/layout admin yang sudah ada, warna netral, aksen merah paroki, dan status badge konsisten.
- Jangan membuat setiap data menjadi kartu; hindari grafik/dekorasi yang tidak membantu pekerjaan admin.
- Status harus memiliki teks, bukan hanya warna.
- Tidak ada horizontal overflow dan semua kontrol dapat digunakan dengan keyboard.

Urutan section:

1. **Header** — judul/sambutan, deskripsi singkat, tombol “Lihat Website”.
2. **Lima kartu ringkasan** — Berita, Agenda, Dokumen, Jadwal Misa, Pelayanan.
3. **Aksi Cepat + Agenda Terdekat** — dua panel berdampingan di desktop, bertumpuk di mobile.
4. **Berita Terbaru + Perlu Perhatian** — dua panel seimbang.
5. **Konten Terbaru** — daftar/tabel ringkas dengan status, waktu pembaruan, dan aksi.

Target viewport: 360, 390, 768, 1024, dan 1440 px.

## 6. Kontrak data Dashboard

Semua angka harus berasal dari query backend dengan definisi yang terdokumentasi. Struktur berikut adalah target kebutuhan; sesuaikan dengan schema dan enum aktual.

### 6.1 Berita — `posts`

Tampilkan jumlah berita dan rincian status draft, terjadwal, terbit, arsip bila tersedia. “Terbit” harus mengikuti scope/aturan publikasi aktual, termasuk `published_at` jika dipakai. Jangan menghitung scheduled sebagai publik.

### 6.2 Agenda — `events`

Tampilkan agenda mendatang yang dipublikasikan serta rincian draft/batal/ditunda jika didukung schema. Pisahkan `is_published` dari status kegiatan. Agenda mendatang tidak boleh memasukkan kegiatan yang sudah lewat atau menghitung cancelled/postponed sebagai kegiatan normal. Gunakan zona waktu paroki `Asia/Pontianak`.

### 6.3 Dokumen — `documents`

Tampilkan jumlah dokumen dan rincian status yang memang ada. Dokumen kedaluwarsa boleh menjadi indikator perhatian bila `expires_at` memiliki makna operasional yang jelas. `expires_at = null` bukan dokumen kedaluwarsa. Bedakan status publikasi dengan tanggal kedaluwarsa.

### 6.4 Jadwal Misa

Sumber: `mass_schedules`, `special_masses`, dan `schedule_notices` jika relevan. Tampilkan:

- jadwal rutin aktif;
- Misa khusus mendatang yang memenuhi aturan aktif/publikasi;
- pemberitahuan jadwal yang sedang berlaku.
  Jangan mencampur jenis record tanpa label. Gunakan definisi model dan timezone `Asia/Pontianak`; reuse service resolver jika relevan, jangan membuat algoritma paralel.

### 6.5 Pelayanan — `services`

Tampilkan pelayanan aktif/nonaktif dan jumlah pelayanan yang perlu ditinjau terkait Google Form jika aturan model mendukungnya. Audit `usesGoogleForm()`, `isAcceptingRegistrations()` atau metode setara. Jangan menganggap semua pelayanan wajib memiliki Google Form.

### 6.6 Aksi Cepat

Maksimum empat pintasan:

1. Tulis Berita.
2. Tambah Agenda.
3. Kelola Jadwal Misa.
4. Pengaturan Situs.

Gunakan route names/helper yang aktual (termasuk Wayfinder jika dipakai). Setiap kontrol harus melakukan navigasi nyata. Jangan menebak route atau membuat tombol inert.

### 6.7 Agenda Terdekat

Tampilkan sekitar 3–5 agenda: tanggal, judul, waktu/lokasi jika tersedia, status yang benar, dan link edit/detail. Batasi di query, urutkan dari waktu terdekat, hindari agenda lewat, dan jangan menyamarkan agenda batal/ditunda. Sediakan empty state dan link ke modul Agenda.

### 6.8 Berita Terbaru

Tampilkan sekitar 3 berita dengan judul, thumbnail jika tersedia, tanggal/status, dan link edit. Gunakan fallback lokal untuk gambar yang tidak tersedia. Jangan menggunakan URL gambar palsu atau data dummy.

### 6.9 Perlu Perhatian

Tampilkan hanya indikator yang benar-benar dapat dihitung, diverifikasi, dan ditindaklanjuti.

- **Media tanpa alt text:** gunakan `media.alt_text` dan relasi aktual. Prioritaskan media yang direferensikan oleh konten publik atau identitas/hero. Jangan otomatis menganggap semua media yatim sebagai masalah publik. Jika referensi media tidak bisa ditentukan secara aman atau tidak ada jalur perbaikan, dokumentasikan gap dan jangan menampilkan angka menyesatkan.
- **Dokumen kedaluwarsa:** berdasarkan `expires_at` yang valid dan sudah lewat; abaikan null.
- **Pelayanan perlu Google Form:** hanya jika form memang wajib menurut aturan layanan.
- **Media gagal:** status `failed` atau enum aktual. Bedakan dari `pending`/`processing`; jangan menawarkan retry jika mekanisme retry tidak ada.

Jangan menambah alert hanya karena suatu kolom tersedia. Setiap alert harus punya definisi query, alasan, dan link tindakan yang berguna.

### 6.10 Konten Terbaru

Gabungkan item terbaru dari berita, agenda, dan dokumen jika dapat dilakukan sederhana. Tampilkan jenis, judul, status, waktu pembaruan, dan link edit/detail. Batasi sekitar 5–8 item di backend. Gunakan DTO/array terkontrol; jangan kirim raw Eloquent model. `updated_at` berarti “Terakhir diperbarui”, bukan bukti siapa yang mengubahnya. Jika gabungan lintas tabel terlalu kompleks, mulai dengan daftar terpisah daripada membuat abstraksi berlebihan.

### 6.11 Data yang tidak perlu menjadi kartu utama

Komunitas, area, pastor, pengurus paroki, sejarah, kategori, total foto, dan total akun tetap dikelola pada modulnya. Tambahkan ringkasan hanya jika ada kebutuhan operasional jelas dan layout tetap lapang.

Tidak termasuk scope: analytics/trafik, pengunjung unik, tren bulanan, audit log admin, approval workflow, notifikasi baru, Dashboard CRUD, dan tabel statistik baru.

## 7. Arsitektur backend dan props

- Audit route Dashboard. Jika route `Route::inertia` saat ini tidak cukup untuk agregasi, gunakan controller admin tipis mengikuti konvensi proyek.
- Buat service/query object hanya bila kompleksitas query membenarkannya.
- Kirim props Inertia terstruktur dengan tipe TypeScript eksplisit.
- Agregasi (`count`, conditional aggregate, scope) dilakukan di backend; jangan memuat seluruh tabel untuk dihitung di React.
- Batasi semua daftar recent/upcoming di query.
- Hindari N+1 pada thumbnail, kategori, lokasi, dan relasi lain.
- Gunakan soft-delete scopes dengan benar.
- Ambil hanya field yang diperlukan; jangan mengirim raw Eloquent model atau data sensitif.
- Jangan menambah cache sebelum kebutuhan dan invalidasi benar-benar dipahami.
- Format tanggal secara konsisten dalam locale Indonesia dan timezone `Asia/Pontianak`.
- Data kosong harus menghasilkan angka 0 dan empty state yang rapi; jangan membuat seeder produksi untuk mengisi UI.

Contoh struktur props konseptual (sesuaikan dengan model aktual; bukan kontrak final yang wajib persis):

```ts
type DashboardProps = {
    summary: {
        posts: Record<string, number>;
        events: Record<string, number>;
        documents: Record<string, number>;
        massSchedules: Record<string, number>;
        services: Record<string, number>;
    };
    upcomingEvents: UpcomingEventItem[];
    recentPosts: RecentPostItem[];
    attentionItems: AttentionItem[];
    recentContent: RecentContentItem[];
};
```

Hindari `any` baru. Jangan memasukkan status yang tidak ada di schema.

## 8. TODO implementasi berurutan

### PH9B-01 — Audit dan baseline

- [ ] Pastikan branch/commit dan status PHASE 9A.
- [ ] Baca dokumen wajib.
- [ ] Audit route, middleware, controller, page, layout, props, dan route names.
- [ ] Audit PRD requirement Dashboard.
- [ ] Audit model/migration/enum/scope/factory untuk semua sumber data.
- [ ] Audit timezone dan helper format tanggal.
- [ ] Audit tests dan CI.
- [ ] Jalankan baseline check atau catat kegagalan yang sudah ada.
- [ ] Catat file yang akan diubah, risiko, dan gap.

**Output:** audit dan implementation plan sebelum coding.

### PH9B-02 — Definisikan kontrak metrik

- [ ] Definisikan arti setiap angka pada lima kartu.
- [ ] Pisahkan status publikasi dan status kegiatan Agenda.
- [ ] Definisikan “agenda mendatang”, “dokumen kedaluwarsa”, dan “pemberitahuan aktif”.
- [ ] Verifikasi aturan Google Form untuk Pelayanan.
- [ ] Verifikasi cara aman menghitung media tanpa alt text.
- [ ] Tetapkan limit agenda/berita/konten terbaru.
- [ ] Tentukan tipe props dan route tindakan.
- [ ] Keluarkan metrik yang tidak dapat dihitung secara benar; jangan menebak.

**Gate:** jangan implementasikan angka sebelum definisinya jelas.

### PH9B-03 — Backend

- [ ] Implementasikan agregat berita, agenda, dokumen, jadwal Misa, dan pelayanan.
- [ ] Implementasikan agenda terdekat dan berita terbaru.
- [ ] Implementasikan konten terbaru jika layak.
- [ ] Implementasikan indikator Perlu Perhatian yang valid.
- [ ] Kirim props Inertia terstruktur dan terbatas.
- [ ] Pastikan query menghormati status, soft delete, otorisasi, dan timezone.

### PH9B-04 — UI

- [ ] Ganti placeholder Dashboard.
- [ ] Implementasikan header dan tombol Lihat Website.
- [ ] Implementasikan lima kartu ringkasan.
- [ ] Implementasikan empat aksi cepat.
- [ ] Implementasikan Agenda Terdekat, Berita Terbaru, Perlu Perhatian, dan Konten Terbaru.
- [ ] Gunakan komponen/design tokens yang sudah ada.
- [ ] Pastikan semua kontrol memiliki aksi nyata.
- [ ] Implementasikan empty state; jangan menampilkan data mockup sebagai data produksi.

### PH9B-05 — Responsive dan accessibility

- [ ] Uji 360, 390, 768, 1024, dan 1440 px.
- [ ] Pastikan grid responsif dan panel bertumpuk dengan urutan masuk akal.
- [ ] Pastikan tabel/list terbaca di mobile dan tidak menyebabkan overflow.
- [ ] Pastikan keyboard navigation, focus state, label, heading hierarchy, dan status tekstual jelas.
- [ ] Uji judul panjang, thumbnail hilang, dan semua data kosong.

### PH9B-06 — Test dan regresi

- [ ] Pest tests untuk agregasi/status dan akses Dashboard.
- [ ] Tests untuk draft tidak dihitung sebagai publik.
- [ ] Tests batas waktu agenda dan dokumen kedaluwarsa.
- [ ] Tests status jadwal Misa dan notice jika diimplementasikan.
- [ ] Tests aturan Google Form dan indikator media jika diimplementasikan.
- [ ] Tests semua tabel kosong dan limit daftar.
- [ ] Vitest untuk perilaku komponen penting bila diperlukan.
- [ ] Playwright untuk Dashboard, aksi cepat, dan viewport mobile.
- [ ] Jangan menghapus assertion lama tanpa mengganti dengan assertion perilaku baru.

### PH9B-07 — Dokumentasi dan handoff

- [ ] Perbarui `docs/roadmap/README.md` setelah acceptance criteria terpenuhi.
- [ ] Catat keputusan baru di `docs/DECISIONS.md` bila ada.
- [ ] Dokumentasikan definisi statistik.
- [ ] Catat metrik yang dikeluarkan dari scope dan alasannya.
- [ ] Laporkan file, test, status CI, dan keterbatasan.
- [ ] Pastikan PHASE 10 tetap terpisah.

## 9. Acceptance criteria

### AC-01 — Dashboard

- [ ] Placeholder diganti dengan Dashboard dinamis.
- [ ] Route tetap dilindungi middleware/authorization.
- [ ] Dashboard tetap bekerja saat tabel konten kosong.

### AC-02 — Statistik

- [ ] Berita mengikuti enum dan aturan waktu publikasi.
- [ ] Agenda memisahkan publikasi dari status kegiatan.
- [ ] Agenda mendatang tidak menghitung agenda lewat/batal/ditunda sebagai kegiatan normal.
- [ ] Dokumen mengikuti enum aktual; expired hanya jika `expires_at` valid dan lewat.
- [ ] Jadwal rutin, Misa khusus, dan notice tidak tercampur tanpa label.
- [ ] Pelayanan mengikuti `is_active` dan aturan form yang benar.
- [ ] Tidak ada perhitungan dari daftar frontend yang terbatas/paginasi.

### AC-03 — Panel dan navigasi

- [ ] Semua aksi cepat mengarah ke route valid.
- [ ] Agenda Terdekat diurutkan dan dibatasi.
- [ ] Berita Terbaru memakai sumber dan status yang benar.
- [ ] Konten Terbaru bukan audit log dan tidak menampilkan pelaku perubahan.
- [ ] Semua link edit/detail berfungsi untuk admin berwenang.

### AC-04 — Perhatian

- [ ] Setiap indikator punya query yang dapat diuji dan tindakan lanjutan.
- [ ] Media tanpa alt text tidak dihitung menyesatkan.
- [ ] Form hanya diwajibkan jika aturan layanan menyatakannya.
- [ ] Expired mengabaikan `expires_at = null`.
- [ ] Media failed dibedakan dari pending/processing.

### AC-05 — UI dan kualitas

- [ ] Layout bersih, lapang, dan konsisten dengan mockup.
- [ ] Tidak ada horizontal overflow pada semua viewport target.
- [ ] Status tidak disampaikan hanya dengan warna.
- [ ] Tidak ada N+1 yang jelas, props berlebihan, atau field sensitif.
- [ ] Tidak ada analytics palsu, audit log palsu, CRUD/tabel statistik duplikat.
- [ ] `composer ci:check`, `npm run build`, E2E relevan, dan CI final lulus.

## 10. Strategi testing

### Pest

Cakupan minimal:

1. Guest ditolak sesuai middleware.
2. User tanpa izin ditolak sesuai aturan akses.
3. Props Dashboard benar dan tidak membocorkan field sensitif.
4. Hitungan berita membedakan draft/scheduled/published/archived.
5. Agenda menghormati tanggal, publikasi, cancelled, postponed.
6. Dokumen expired menghitung benar dan mengabaikan null.
7. Jadwal rutin, Misa khusus, dan notice dihitung dengan definisi masing-masing.
8. Pelayanan aktif dan missing required form sesuai aturan.
9. Daftar upcoming/recent dibatasi.
10. Kondisi data kosong tidak exception.
11. Batas tanggal/waktu menggunakan timezone paroki.

### Vitest

Jika diperlukan, uji render summary, empty states, status badge, quick links, panel perhatian kondisional, dan item tanpa thumbnail. Hindari snapshot besar yang rapuh.

### Playwright

- Admin berwenang dapat membuka Dashboard.
- Guest tidak dapat membuka Dashboard.
- Ringkasan dan panel utama tampil.
- Aksi cepat membuka route yang benar.
- Dashboard tetap usable di mobile.
- Data kosong tidak menyebabkan error.
- Sidebar dan modul admin lain tidak regresi.

Gunakan factory/fixture deterministik; jangan bergantung pada konten produksi tertentu.

## 11. Verifikasi

Periksa script aktual pada `composer.json`, `package.json`, dan workflow CI. Script yang teridentifikasi pada audit sebelumnya dan perlu diverifikasi ulang:

```bash
composer ci:check
npm run build
npm run e2e
```

Laporkan hanya command yang benar-benar dijalankan, hasil pass/fail, jumlah test jika tersedia, baseline failures, dan status CI. Jangan mengklaim test lulus jika tidak dijalankan.

## 12. Definition of Done

PHASE 9B selesai bila:

1. Semua acceptance criteria wajib terpenuhi.
2. Dashboard bukan placeholder dan menggunakan data backend.
3. Definisi statistik terdokumentasi serta benar.
4. Semua aksi/panel berfungsi dan memiliki empty state.
5. UI bersih, responsif, dan aksesibel.
6. Tidak ada analytics/audit log palsu atau tabel duplikat.
7. Test, build, E2E relevan, dan CI memenuhi gate.
8. Roadmap dan keputusan diperbarui.
9. Laporan akhir memuat file berubah, definisi metrik, test, CI, dan keterbatasan.
10. PHASE 10 tetap terpisah.

## 13. Di luar scope

Tanpa persetujuan eksplisit, jangan membangun:

- Analytics, trafik, pengunjung unik, atau tren trafik.
- Audit log admin.
- Sistem notifikasi baru.
- Approval workflow baru.
- Dashboard CRUD/tabel statistik.
- Kartu statistik rinci untuk kategori, komunitas, area, pastor, pengurus, atau total akun.
- Refactor modul yang tidak diperlukan.
- Penambahan library UI/chart tanpa kebutuhan jelas.

## 14. Instruksi operasional untuk AI agent

1. Audit branch/commit, dokumen, route, middleware, schema, enum, scope, komponen, test, dan CI.
2. Susun implementation plan sebelum coding: petakan metrik → tabel/status → query → prop → file.
3. Jika field/status/route tidak jelas, cari implementasi aktual. Catat gap; jangan menebak.
4. Implementasikan backend dan test agregasi sebelum menghubungkan UI.
5. Implementasikan UI sesuai mockup dengan komponen proyek.
6. Jaga scope: tanpa analytics, audit log, CRUD, atau tabel statistik baru.
7. Jalankan check, build, E2E, dan periksa CI.
8. Perbarui roadmap hanya setelah gate lulus.
9. Laporkan hasil dengan jujur.

### Format laporan akhir

```text
PHASE 9B — Admin Dashboard Implementation Report
Status: DONE | BLOCKED | PARTIAL

Summary:
- ...

Statistics definitions:
- Posts:
- Events:
- Documents:
- Mass schedules:
- Services:

Panels:
- Quick actions:
- Upcoming events:
- Recent news:
- Attention items:
- Recent content:

Files changed:
- ...

Schema/cache changes:
- None | explain

Verification:
- composer ci:check: PASS/FAIL/NOT RUN
- npm run build: PASS/FAIL/NOT RUN
- npm run e2e: PASS/FAIL/NOT RUN
- CI: PASS/FAIL/PENDING

Known limitations:
- ...

Analytics/audit log added: NO
Dashboard CRUD/table added: NO
PHASE 10 remains separate: YES
```

## 15. Roadmap dan handoff

Setelah selesai, perbarui `docs/roadmap/README.md` untuk menandai PHASE 9A dan 9B secara terpisah sesuai hasil aktual. Jangan menandai PHASE 9 selesai bila acceptance criteria atau test gate masih gagal. Setelah PHASE 9B ditutup dengan hasil verifikasi yang jelas, pekerjaan dapat dilanjutkan ke PHASE 10 — QA, Performance, Security & SEO.
