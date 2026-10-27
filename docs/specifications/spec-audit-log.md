# Spesifikasi Implementasi Audit Log

## Website Paroki HSPMTB Putussibau

| Atribut                | Nilai                                                                                        |
| ---------------------- | -------------------------------------------------------------------------------------------- |
| Dokumen                | Spesifikasi Implementasi Audit Log                                                           |
| Produk                 | Website Resmi Gereja Katolik Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau        |
| Status                 | Draft spesifikasi — menunggu persetujuan sebelum implementasi                                |
| Prioritas              | P0                                                                                           |
| Branch yang diaudit    | `style/update-ui`                                                                            |
| Snapshot repository    | `66428c8438472f5af8c08f5c26702cd011b19444`                                                   |
| Stack                  | Laravel 13, PHP 8.3+, Inertia v3, React 19, TypeScript, Tailwind CSS v4, MySQL 8             |
| Bahasa UI              | Bahasa Indonesia                                                                             |
| Zona waktu penyimpanan | UTC                                                                                          |
| Zona waktu tampilan    | `Asia/Pontianak`                                                                             |
| Dokumen sumber utama   | `docs/PRD.md`, `docs/ARCHITECTURE.md`, `docs/DECISIONS.md`, `AGENTS.md`                      |
| Batas pekerjaan ini    | Audit struktur dan spesifikasi saja. Belum membuat migration, service, UI, route, atau test. |

---

## 1. Tujuan

Audit Log menyediakan riwayat aktivitas administratif yang konsisten, dapat ditelusuri, dan dilindungi dari perubahan sembarangan. Modul harus membantu Super Admin menjawab:

1. Siapa yang melakukan tindakan?
2. Tindakan apa yang terjadi?
3. Kapan tindakan terjadi?
4. Objek atau data apa yang terdampak?
5. Apa nilai penting sebelum dan sesudah perubahan?
6. Apakah tindakan berhasil atau ditolak?

Audit Log bukan sekadar aktivitas terbaru di Dashboard, bukan pengganti log error Laravel, dan bukan sistem deteksi intrusi penuh.

## 2. Hasil audit repository

### 2.1 Snapshot dan sumber yang diperiksa

Spesifikasi ini disusun dari inspeksi branch `style/update-ui` pada snapshot SHA di atas. Pemeriksaan mencakup:

- `AGENTS.md`
- `docs/PRD.md`
- `docs/ARCHITECTURE.md`
- `docs/DECISIONS.md`
- `docs/roadmap/README.md`
- `docs/roadmap/phase-9b-admin-dashboard.md`
- `routes/web.php`
- `bootstrap/app.php`
- `composer.json`
- `package.json`
- `app/Actions/News/SavePostAction.php`
- `app/Actions/Agenda/SaveEventAction.php`
- `app/Actions/Document/SaveDocumentAction.php`
- `app/Actions/Document/DeleteDocumentAction.php`
- `app/Http/Controllers/News/PostController.php`
- `app/Http/Controllers/Agenda/EventController.php`
- `app/Http/Controllers/Document/DocumentController.php`
- `app/Http/Controllers/Settings/SettingsController.php`
- `app/Http/Controllers/Media/MediaController.php`
- `app/Http/Middleware/EnsureAdminAccess.php`
- `app/Http/Middleware/EnsureAccountIsActive.php`
- `app/Models/User.php`
- `app/Providers/AppServiceProvider.php`
- `database/seeders/PermissionSeeder.php`
- `database/migrations/2026_10_07_060202_create_permission_tables.php`
- `tests/Pest.php`
- `tests/Feature/Auth/AdminAccessTest.php`
- `tests/Feature/Settings/SiteSettingsTest.php`
- `resources/js/layouts/app/app-sidebar-nav.ts`
- `resources/js/layouts/app/components/app-sidebar.tsx`
- `docs/roadmap/phase-9b-admin-dashboard.md`

Pemeriksaan ini adalah inspeksi source jarak jauh. Aplikasi tidak dijalankan dan seluruh test suite tidak dieksekusi sebagai bagian dari audit ini.

### 2.2 Konvensi yang wajib diikuti

| Area               | Konvensi codebase                                                                                                  | Implikasi untuk Audit Log                                                                 |
| ------------------ | ------------------------------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------------- |
| Backend            | Layered Laravel; controller mengorkestrasi HTTP                                                                    | Controller Audit Log tetap tipis.                                                         |
| Operasi bisnis     | Operasi multi-langkah/transaction/side effect memakai `app/Actions`                                                | Gunakan Action untuk merekam event audit dengan validasi dan redaksi terpusat.            |
| Kemampuan reusable | Kemampuan reusable berada di `app/Services`                                                                        | Gunakan service `AuditLogger` sebagai API internal pencatatan.                            |
| ORM                | Eloquent sebagai default; tidak ada repository pattern tanpa kebutuhan                                             | Gunakan model Eloquent `AuditEvent`.                                                      |
| Otorisasi          | Backend adalah sumber kebenaran; gunakan Gate/Policy dan `can()`                                                   | Lindungi halaman, query, detail, dan ekspor di backend.                                   |
| RBAC               | Spatie Laravel Permission; role MVP hanya `super_admin`                                                            | Tambahkan permission dengan format yang konsisten, bukan role baru.                       |
| Permission naming  | Test fondasi memvalidasi pola permission lowercase dengan satu titik, mis. `resource.action`                       | Gunakan `auditlogs.view`; hindari hyphen dalam nama permission.                           |
| Frontend           | React 19 + TypeScript + Inertia v3                                                                                 | Props audit dikirim melalui Inertia, bukan state server duplikat.                         |
| Page resolver      | Nama komponen Inertia memakai konvensi audience/feature; halaman admin berada di `resources/js/features/admin/...` | Tempatkan halaman di feature admin Audit Log dan ikuti pola page resolver yang aktif.     |
| URL frontend       | Wayfinder menghasilkan route/action; file generated tidak boleh diedit manual                                      | UI mengimpor route dari `@/routes/...`; jalankan generator sesuai `AGENTS.md`.            |
| Sidebar            | Navigasi terpusat di `resources/js/layouts/app/app-sidebar-nav.ts`                                                 | Tambahkan satu entri di area administrasi yang tepat.                                     |
| UI                 | Komponen bersama berada di `resources/js/components`; komponen feature di folder feature                           | Gunakan tabel, badge, dialog, empty state, dan pola UI yang sudah ada.                    |
| Testing PHP        | Pest Feature Tests otomatis memakai `RefreshDatabase` dari `tests/Pest.php`                                        | Jangan menambahkan trait yang sama kembali di setiap test.                                |
| Test role          | Helper `superAdmin()` dan `seedRolesAndPermissions()` tersedia                                                     | Pakai helper yang ada; bersihkan cache permission jika seeder diubah sesuai pola project. |
| Database           | MySQL 8 untuk development dan test; migrasi yang sudah berjalan tidak boleh diedit                                 | Tambahkan migration baru, jangan mengubah migration lama.                                 |
| Quality gate       | `composer ci:check`; PHPStan level 7 mencakup `app/`, `config/`, `database/`, `routes/`, dan `tests/`              | Semua file baru harus lulus lint, type checks, unit/feature tests, dan PHPStan.           |
| Bahasa/waktu       | UI Bahasa Indonesia; penyimpanan UTC dan tampilan `Asia/Pontianak`                                                 | Timestamp disimpan UTC dan diformat di zona tampilan aplikasi.                            |

### 2.3 Temuan struktur terkait

1. **Belum ditemukan modul Audit Log pada daftar file branch yang diperiksa.** Tidak terlihat model, migration, controller, service, route, halaman admin, atau test khusus Audit Log pada snapshot ini. Ini merupakan temuan dari inspeksi daftar file, bukan klaim tentang sistem eksternal atau log server.
2. `SavePostAction` dan `SaveEventAction` membungkus penulisan data dalam `DB::transaction()`. Ini adalah titik integrasi yang baik agar perubahan bisnis dan catatan audit untuk kejadian yang sama konsisten.
3. `SaveDocumentAction` mengelola file dan database dengan kompensasi ketika transaksi gagal. Catatan audit tidak boleh membuat operasi file/database menjadi seolah-olah berhasil bila transaksi gagal.
4. `DeleteDocumentAction` menyimpan metadata row melalui soft delete tetapi file fisik dihapus setelah transaksi. Audit untuk penghapusan perlu menangkap snapshot metadata sebelum row dihapus dan tidak boleh bergantung pada model setelah dihapus.
5. `SettingsController::update()` menggunakan `UpdateSettingsRequest` dan `SiteSettingsService::setMany()`. Audit perubahan pengaturan perlu membandingkan nilai yang diizinkan sebelum dan sesudah perubahan, tanpa merekam secret atau field yang dikecualikan.
6. `MediaController` membuat record media dan dispatch job pemrosesan gambar setelah record dibuat. Aktivitas upload dan status pemrosesan perlu dibedakan: upload diterima bukan berarti seluruh varian gambar sudah berhasil dibuat.
7. `AppServiceProvider` memberikan seluruh ability melalui Gate kepada role `super_admin`. Pemeriksaan akses Audit Log tetap harus memakai Gate/Policy/`can()` sesuai konvensi; jangan menggunakan `hasPermissionTo()` sebagai pengganti pemeriksaan Gate di tempat yang bergantung pada `Gate::before`.
8. `PermissionSeeder` sudah memuat permission `users.view`, `users.create`, `users.update`, dan `users.disable`, walaupun UI manajemen pengguna belum terlihat pada scope route yang diperiksa. Audit Log tidak boleh menganggap UI manajemen akun sudah tersedia.
9. `routes/web.php` melindungi CMS dengan middleware `auth`, `verified`, `EnsureAccountIsActive`, dan `EnsureAdminAccess`. Route Audit Log harus berada di dalam group admin yang sama, dengan permission tambahan untuk membaca log.
10. Sidebar saat ini memiliki kelompok `Dashboard`, `Konten`, dan `Akun`. Audit Log adalah alat administrasi/keamanan, bukan konten publik; tempatkan sebagai item administrasi tersendiri dan jangan mencampurnya dengan modul konten.
11. Roadmap menyatakan QA/Performance/Security dan Production/Handover sebagai fase terpisah. Audit Log perlu memiliki test dan prosedur operasional sendiri, tetapi implementasi modul ini tidak boleh mengklaim seluruh fase keamanan atau produksi sudah selesai.

### 2.4 Sumber dan aturan yang berlaku

- `docs/PRD.md` adalah sumber kebenaran requirement produk.
- `docs/ARCHITECTURE.md` mengatur pola layer backend, frontend feature-oriented, Inertia contract, dan testing.
- `docs/DECISIONS.md` merekam keputusan arsitektur yang telah diterima.
- `AGENTS.md` mengatur workflow, command, database test, Wayfinder, dan batasan perubahan.
- Bila spesifikasi ini bertentangan dengan requirement eksplisit di PRD atau keputusan arsitektur yang diterima, hentikan implementasi pada bagian konflik dan minta klarifikasi; jangan mengubah konvensi secara diam-diam.

---

## 3. Ruang lingkup

### 3.1 In scope — MVP P0

- Pencatatan otomatis atas perubahan administratif penting.
- Identitas aktor manusia atau sistem.
- Kategori modul, aksi, objek, hasil, dan waktu kejadian.
- Snapshot perubahan yang aman untuk dicatat.
- Riwayat create, update, publish/unpublish, archive, soft delete, restore jika didukung modul, serta perubahan status penting.
- Audit perubahan konfigurasi situs.
- Audit perubahan role/permission dan status akun ketika tindakan tersebut dilakukan melalui jalur aplikasi yang tersedia.
- Daftar log, filter dasar, pagination server-side, dan detail log.
- Otorisasi backend untuk halaman dan query.
- Redaksi data sensitif.
- Perlindungan terhadap edit/delete melalui UI atau endpoint aplikasi.
- Test feature untuk pencatatan, konsistensi transaksi, otorisasi, redaksi, filter, pagination, dan akses langsung.
- Dokumentasi kebijakan retensi dan batas integritas.

### 3.2 Out of scope untuk MVP

- Dashboard SIEM atau deteksi intrusi penuh.
- Notifikasi eksternal melalui WhatsApp/email untuk setiap kejadian.
- Ekspor PDF.
- Hash chaining atau tanda tangan kriptografis pada setiap event.
- Penyimpanan log di layanan eksternal berbayar.
- UI untuk manajemen role/permission.
- Manajemen pengguna lengkap jika UI tersebut belum menjadi bagian dari ruang lingkup produk.
- Merekam setiap page view, klik, atau navigasi pengguna.
- Menyalin seluruh HTTP request, seluruh model, atau seluruh payload ke log.
- Menyimpan isi file yang diunggah sebagai bagian dari event audit.

### 3.3 Prinsip

1. **Backend authoritative:** catatan audit dibuat di server, tidak bergantung pada event dari React.
2. **Allowlist, bukan log semua field:** hanya field yang disetujui untuk audit yang boleh masuk snapshot.
3. **No secrets:** kredensial dan token tidak pernah disimpan.
4. **Consistent outcome:** event tidak boleh menyatakan mutasi berhasil jika mutasi bisnis gagal.
5. **Append-only melalui aplikasi:** tidak ada endpoint edit/hapus catatan audit.
6. **Minimal personal data:** simpan metadata yang diperlukan untuk akuntabilitas saja.
7. **No false assurance:** append-only di level aplikasi tidak menjamin administrator database/server tidak bisa memanipulasi data.
8. **Idempotency-aware:** retry job atau request tidak boleh menghasilkan duplikasi event tanpa alasan yang jelas.
9. **Readable Indonesian UI:** label aksi dan tanggal ditampilkan dengan bahasa yang mudah dipahami pengelola paroki.
10. **Follow current architecture:** jangan menambah package audit pihak ketiga tanpa evaluasi dan persetujuan.

---

## 4. Pengguna dan hak akses

### 4.1 Pengguna MVP

MVP hanya mengaktifkan role `super_admin`. Tidak perlu menambah role baru hanya untuk Audit Log.

### 4.2 Permission

Tambahkan satu permission baca:

- `auditlogs.view`

Alasan penamaan: mengikuti konvensi permission yang sudah diuji pada codebase, yaitu lowercase dengan satu titik dan tanpa tanda hubung.

Super Admin memperoleh akses melalui mekanisme Gate yang sudah ada. Permission tetap harus ditambahkan ke `PermissionSeeder` agar permission terdefinisi konsisten dan dapat dipakai jika role selain Super Admin diperkenalkan kemudian.

### 4.3 Aturan otorisasi

- Semua route Audit Log berada di dalam middleware group admin yang sudah ada.
- Controller atau Form Request memeriksa `$request->user()->can('auditlogs.view')` atau `Gate::authorize(...)`, sesuai pola route dan operasi.
- Otorisasi berlaku untuk daftar, detail, filter, dan ekspor di masa depan.
- Menyembunyikan menu di frontend bukan kontrol keamanan.
- Jangan menyediakan create/update/delete route untuk `AuditEvent`.
- Akses metadata sensitif seperti IP address dan user-agent hanya ditampilkan kepada pengguna yang memang berhak melihat Audit Log.
- Jika nanti role baru disetujui, tentukan hak akses melalui permission; jangan menambahkan pengecualian role hard-coded pada UI.

---

## 5. Model kejadian audit

### 5.1 Struktur event yang disarankan

Setiap event adalah satu kejadian, bukan satu baris per field yang berubah.

| Atribut konseptual | Wajib | Keterangan                                                                                                                                                             |
| ------------------ | ----: | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `id`               |    Ya | Primary key. Ikuti tipe key yang dipilih dan didokumentasikan saat migration dibuat.                                                                                   |
| `actor_id`         | Tidak | FK nullable ke `users`; nullable agar event tetap bertahan setelah akun dihapus.                                                                                       |
| `actor_type`       |    Ya | Misalnya `user`, `system`, atau `anonymous` untuk kejadian keamanan yang tidak memiliki aktor terautentikasi.                                                          |
| `actor_name`       | Tidak | Snapshot nama aktor pada waktu kejadian, untuk mempertahankan konteks historis.                                                                                        |
| `actor_email`      | Tidak | Snapshot email hanya jika diperlukan; minimalkan data pribadi.                                                                                                         |
| `action`           |    Ya | Kode aksi stabil seperti `created`, `updated`, `published`, `unpublished`, `archived`, `deleted`, `restored`, `login_succeeded`, `login_failed`, `permission_changed`. |
| `module`           |    Ya | Kode modul seperti `news`, `agenda`, `mass_schedules`, `settings`, `users`, `media`, `documents`.                                                                      |
| `subject_type`     | Tidak | Jenis objek/model terkait; bukan teks bebas dari request.                                                                                                              |
| `subject_id`       | Tidak | ID objek sebagai string agar dapat mendukung tipe ID yang berbeda.                                                                                                     |
| `subject_label`    | Tidak | Snapshot label manusia yang aman, misalnya judul berita atau nama jadwal.                                                                                              |
| `description`      |    Ya | Ringkasan Bahasa Indonesia yang tidak memuat rahasia.                                                                                                                  |
| `result`           |    Ya | Kode hasil seperti `success`, `failure`, `denied`; definisi dan pemakaiannya harus konsisten.                                                                          |
| `before`           | Tidak | Snapshot nilai terpilih sebelum perubahan.                                                                                                                             |
| `after`            | Tidak | Snapshot nilai terpilih sesudah perubahan.                                                                                                                             |
| `metadata`         | Tidak | Metadata allowlisted yang aman, bukan seluruh request.                                                                                                                 |
| `request_id`       | Tidak | ID korelasi request untuk investigasi.                                                                                                                                 |
| `ip_address`       | Tidak | Metadata keamanan terbatas, bila kebijakan privasi menyetujui.                                                                                                         |
| `user_agent`       | Tidak | Metadata klien terbatas, bila diperlukan.                                                                                                                              |
| `occurred_at`      |    Ya | Waktu kejadian disimpan dalam UTC.                                                                                                                                     |
| `created_at`       |    Ya | Timestamp record database. Tidak menggantikan `occurred_at`.                                                                                                           |

Nama kolom di atas adalah rancangan kontrak, bukan migration final. Sebelum migration, tetapkan tipe key, panjang string, indeks, dan batas ukuran JSON berdasarkan konvensi migration aktual.

### 5.2 Action dan module harus berupa kode stabil

Contoh kode:

```text
action = updated
module = mass_schedules
subject_type = App\Models\MassSchedule
subject_id = "42"
subject_label = "Misa Harian"
result = success
```

UI menerjemahkan kode tersebut menjadi label Bahasa Indonesia. Jangan memakai kalimat bebas sebagai nilai `action`, karena filter dan statistik akan sulit distandardisasi.

### 5.3 Nilai before/after

- Simpan hanya field yang relevan dan diizinkan.
- Untuk event `created`, `before` umumnya `null` dan `after` berisi field terpilih.
- Untuk event `updated`, simpan hanya field yang berubah.
- Untuk event `deleted`, `before` berisi snapshot ringkas dan `after` umumnya `null`.
- Untuk perubahan status, simpan status sebelumnya dan sesudahnya.
- Jangan memasukkan HTML mentah yang tidak diperlukan, binary, isi file, password, token, secret, session/cookie, atau seluruh payload request.
- Terapkan batas panjang untuk field teks; untuk rich text panjang, utamakan diff/ringkasan atau metadata terpilih daripada menyimpan seluruh isi artikel berulang kali.
- Snapshot adalah catatan audit, bukan mekanisme restore otomatis.

### 5.4 Metadata yang diizinkan

Metadata tambahan harus menggunakan allowlist berdasarkan event. Contoh yang mungkin diizinkan:

- `status_from`, `status_to`
- `scheduled_at`
- `file_mime_type`, `file_size`
- `setting_keys_changed`
- `job_name`
- `reason_code` yang tidak sensitif

Jangan menerima `metadata` arbitrer dari browser. Metadata harus dibuat oleh backend dari nilai yang sudah divalidasi.

---

## 6. Katalog event MVP

### 6.1 Berita

Modul: `news`; objek utama: `Post`.

| Kejadian                         | Kode aksi     | Field audit yang diprioritaskan                                                                          |
| -------------------------------- | ------------- | -------------------------------------------------------------------------------------------------------- |
| Berita dibuat                    | `created`     | title, slug, status, kategori, published_at                                                              |
| Berita diubah                    | `updated`     | hanya field yang berubah: title, slug, excerpt, category, status, published_at, featured image reference |
| Berita diterbitkan               | `published`   | status sebelumnya/sesudahnya, published_at                                                               |
| Publikasi dibatalkan             | `unpublished` | status sebelumnya/sesudahnya                                                                             |
| Berita dijadwalkan               | `scheduled`   | status, waktu publikasi yang dijadwalkan                                                                 |
| Berita diarsipkan                | `archived`    | status sebelumnya/sesudahnya                                                                             |
| Berita dihapus                   | `deleted`     | snapshot judul, slug, status, ID                                                                         |
| Berita dipulihkan, bila didukung | `restored`    | snapshot identitas dan status                                                                            |

Hindari event ganda `updated` dan `published` untuk satu tindakan jika satu event `published` sudah mencakup perubahan status dan field relevan. Jika dua kejadian bisnis yang berbeda benar-benar terjadi, dokumentasikan urutannya.

### 6.2 Agenda

Modul: `agenda`; objek utama: `Event`.

Catat create, update, perubahan publikasi, pembatalan, penundaan, penghapusan, dan pemulihan jika fitur restore tersedia. Field penting: judul, slug, waktu mulai/selesai, lokasi, status, dan `is_published`.

Tanggal/waktu pada deskripsi UI harus diformat di `Asia/Pontianak`; nilai waktu yang disimpan tetap mengikuti konvensi aplikasi.

### 6.3 Jadwal Misa

Modul: `mass_schedules`.

Catat perubahan jadwal rutin, Misa khusus, pemberitahuan jadwal, lokasi, status aktif, dan penghapusan/pemulihan bila didukung. Bedakan entitas jadwal rutin dari Misa khusus dan pemberitahuan; jangan menyatukan semuanya menjadi satu jenis objek.

Field penting dipilih berdasarkan model terkait: nama, hari/jam untuk jadwal rutin, waktu mulai/selesai untuk kejadian bertanggal, lokasi, status, dan teks pemberitahuan yang relevan.

### 6.4 Profil paroki dan pelayanan

Modul: `parish_profile`, `pastors`, `parish_board`, `services`, atau kode modul stabil yang disepakati sebelum implementasi.

Catat perubahan informasi profil, sejarah, wilayah pelayanan, penugasan pastor, struktur kepengurusan, langkah/ketentuan pelayanan, status aktif, dan urutan item jika urutan memengaruhi halaman publik.

Untuk aksi khusus yang mengubah lebih dari satu record—misalnya menetapkan pastor aktif atau mengaktifkan periode kepengurusan—satu event audit dapat mewakili operasi bisnis tersebut, dengan metadata yang menjelaskan objek terdampak. Jika beberapa objek harus diaudit terpisah, gunakan correlation/request ID untuk menghubungkan event.

### 6.5 Pengaturan situs

Modul: `settings`.

Catat perubahan nilai yang benar-benar berubah. Simpan nama key dan nilai before/after hanya untuk key yang dinyatakan aman.

- Pengaturan umum yang aman boleh menyimpan before/after.
- Untuk secret, kredensial, token, atau field sensitif: catat key yang berubah dan penanda `value_redacted`, bukan nilainya.
- Untuk perubahan beberapa key dalam satu simpanan, pilih satu event `settings_updated` dengan daftar key yang berubah atau event per key jika kebutuhan investigasi mengharuskannya. Keputusan final harus konsisten dan dites.
- Jangan mencatat seluruh `settings` payload mentah.

### 6.6 Akun, autentikasi, dan permission

Modul: `users` atau `auth`.

Catat sekurang-kurangnya:

- Login berhasil (`login_succeeded`).
- Login gagal (`login_failed`) dengan identitas yang sudah diminimalkan dan tanpa password/token.
- Logout (`logout`).
- Perubahan profil akun (`profile_updated`).
- Perubahan email (`email_changed`) bila perlu.
- Perubahan kata sandi (`password_changed`) tanpa menyimpan nilai kata sandi.
- Akun diaktifkan/dinonaktifkan jika jalur manajemen akun tersedia.
- Perubahan role atau permission (`role_changed`, `permission_granted`, `permission_revoked`) jika jalur perubahan tersebut tersedia.

Catatan implementasi:

- Event login gagal harus dibatasi/dikendalikan agar percobaan berulang tidak membanjiri tabel. Rate limiting yang sudah ada tetap menjadi kontrol utama.
- Jangan mengklaim UI manajemen pengguna sudah tersedia; audit struktur menunjukkan permission `users.*` ada, tetapi route UI manajemen pengguna belum terkonfirmasi.
- Jangan menambahkan fitur manajemen akun sebagai bagian dari pekerjaan Audit Log ini tanpa persetujuan scope terpisah.
- Untuk logout atau login failure yang tidak terkait transaksi mutasi, pencatatan dapat dilakukan melalui jalur autentikasi khusus. Jangan membuat catatan `success` atas mutasi yang gagal.

### 6.7 Media dan dokumen

Catat unggah yang diterima, perubahan metadata, penghapusan, perubahan status publikasi dokumen, dan kegagalan pemrosesan yang penting.

Untuk media:

- `upload_accepted` berarti file diterima dan job dipicu, bukan berarti semua varian berhasil dibuat.
- Status `ready`/`failed` yang dihasilkan job dapat dicatat sebagai event sistem terpisah jika berguna untuk akuntabilitas.
- Jangan mencatat path privat yang dapat membocorkan struktur storage jika tidak dibutuhkan.
- Jangan menyimpan file atau konten binary ke audit.

Untuk dokumen:

- Simpan snapshot metadata yang aman sebelum soft delete.
- Audit perubahan status publikasi, tanggal kedaluwarsa, kategori, dan file replacement.
- Penghapusan file fisik setelah transaksi perlu ditangani dengan hati-hati; event bisnis dan hasil operasi storage harus dibedakan bila operasi storage gagal.

### 6.8 Master data

Catat perubahan kategori berita, kategori agenda, kategori dokumen, kategori pelayanan/komunitas, lokasi, dan master data lain yang memengaruhi interpretasi konten publik.

Untuk pengurutan massal atau operasi yang memengaruhi banyak record, hindari membuat log yang sangat besar tanpa batas. Catat operasi induk beserta jumlah/ID objek yang berubah jika snapshot setiap objek tidak diperlukan.

---

## 7. Strategi pencatatan dan konsistensi

### 7.1 API internal

Gunakan service bernama sesuai konvensi proyek, misalnya `App\Services\AuditLogger`, dan Action untuk membangun/menyimpan event jika operasi memerlukan validasi, normalisasi, redaksi, atau side effect.

Nama final dapat disesuaikan setelah implementer membaca pola service/action terkini. Hindari membuat abstraksi event-bus generik jika tidak dibutuhkan.

### 7.2 Jangan mengandalkan frontend

React tidak mengirim event audit secara langsung. Frontend dapat menampilkan hasil, tetapi backend menentukan apakah tindakan benar-benar terjadi dan nilai yang benar untuk dicatat.

### 7.3 Mutasi database dan audit dalam transaksi

Untuk perubahan data yang berada dalam transaksi database yang sama:

- Ambil snapshot sebelum mutasi.
- Jalankan perubahan bisnis.
- Tentukan field yang berubah dari data yang tersimpan.
- Tulis event audit dalam transaksi yang sama bila event wajib ada hanya jika mutasi berhasil.
- Jika transaksi rollback, event sukses ikut rollback.

Pola ini mencegah log menyatakan perubahan sukses ketika database membatalkan perubahan.

### 7.4 Operasi file dan queue

Operasi storage eksternal tidak selalu bisa ikut dalam transaksi database.

- Catat event bisnis yang memang sudah terjadi dengan hasil yang akurat.
- Untuk status job, gunakan event sistem terpisah jika diperlukan.
- Jangan menulis event “selesai diproses” ketika job baru didispatch.
- Hindari mengirim event duplikat ketika job di-retry; gunakan identitas kejadian/idempotency key jika jalur tersebut terbukti membutuhkan deduplikasi.
- Untuk kebutuhan delivery yang lebih ketat di masa depan, evaluasi transactional outbox secara terpisah; jangan menambahkannya pada MVP tanpa alasan terukur.

### 7.5 Mutasi gagal

Jangan mencatat event sukses jika validasi, otorisasi, atau transaksi gagal.

Untuk kegagalan keamanan yang memang perlu ditelusuri—misalnya login gagal atau akses ditolak pada endpoint sensitif—gunakan event dengan `result = failure` atau `denied`, dan pastikan pencatatannya tidak membuka detail rahasia.

Tidak semua HTTP 4xx/5xx harus masuk Audit Log. Validasi form biasa yang gagal tidak perlu menghasilkan audit event permanen secara default.

### 7.6 Pencegahan duplikasi

- Satu operasi bisnis menghasilkan satu event utama kecuali beberapa event terpisah memang mewakili kejadian yang berbeda.
- Jangan menggabungkan event audit ke observer global yang secara otomatis mencatat semua `saved`/`deleted` model tanpa konteks. Cara itu berisiko menghasilkan log ganda, snapshot tidak aman, dan event yang tidak menjelaskan alasan bisnis.
- Untuk aksi yang menyentuh banyak model, pilih batas event pada operasi bisnis dan dokumentasikan perilakunya.

---

## 8. Desain database dan indeks

### 8.1 Tabel

Nama tabel yang disarankan: `audit_events`.

Migration harus baru dan tidak boleh mengedit migration yang telah berjalan.

### 8.2 Tipe data

- `actor_id`: nullable FK yang tidak menghapus event saat user dihapus. Pertimbangkan `nullOnDelete()` agar event tetap ada; `actor_name`/snapshot menjaga konteks historis.
- `subject_id`: string nullable, bukan FK ke semua tabel bisnis, karena objek dapat berasal dari banyak model dan harus tetap dapat dilihat setelah objek dihapus.
- `before`, `after`, `metadata`: JSON nullable dengan allowlist field.
- `occurred_at`: timestamp/datetime yang konsisten dengan konfigurasi UTC.
- `request_id`: string nullable dengan panjang yang dibatasi.
- `ip_address`: tipe yang mendukung IPv4/IPv6 sesuai konvensi schema project.
- `user_agent`: panjang dibatasi; jangan mengizinkan payload arbitrer besar.
- `description` dan `subject_label`: panjang dibatasi sesuai kebutuhan UI.

### 8.3 Indeks awal

Evaluasi indeks berikut terhadap query yang benar-benar digunakan:

- `(occurred_at)` untuk urutan terbaru dan filter rentang waktu.
- `(actor_id, occurred_at)` untuk riwayat per aktor.
- `(module, occurred_at)` untuk filter modul.
- `(action, occurred_at)` untuk filter aksi.
- `(result, occurred_at)` jika filter hasil digunakan secara rutin.
- `(subject_type, subject_id)` untuk riwayat objek.
- `request_id` jika korelasi request sering dipakai.

Jangan membuat indeks berlebihan sebelum memeriksa query plan dan volume data. MySQL 8 adalah engine target; jangan mengandalkan perilaku SQLite yang berbeda.

### 8.4 Ukuran snapshot

- Batasi jumlah field dan panjang value.
- Jangan simpan seluruh rich-text artikel jika ringkasan perubahan sudah mencukupi.
- Tentukan kebijakan untuk snapshot yang melewati batas ukuran.
- Jangan diam-diam memotong data dengan cara yang membuat perubahan tampak lengkap padahal tidak; tandai snapshot sebagai ringkas/truncated jika diperlukan.
- Rancang query daftar agar tidak mengambil JSON besar untuk semua baris; muat detail snapshot hanya ketika detail event dibuka.

---

## 9. Model, service, action, policy, controller

Nama berikut merupakan rancangan, bukan file yang sudah dibuat.

### 9.1 Backend

```text
app/
├── Actions/
│   └── Audit/
│       └── RecordAuditEventAction.php
├── Http/
│   ├── Controllers/
│   │   └── Audit/
│   │       └── AuditLogController.php
│   └── Requests/
│       └── Audit/
│           └── AuditLogIndexRequest.php
├── Models/
│   └── AuditEvent.php
├── Policies/
│   └── AuditEventPolicy.php
└── Services/
    └── AuditLogger.php
```

Jangan membuat direktori atau layer yang bertentangan dengan struktur aktual. Jika codebase memakai konvensi yang lebih spesifik untuk feature tertentu, ikuti konvensi aktual setelah memeriksanya.

### 9.2 Tanggung jawab

**`AuditLogger`**

- Menyediakan API internal yang stabil untuk merekam event.
- Menormalisasi kode action/module.
- Menerapkan allowlist dan redaksi.
- Menghindari duplikasi yang dapat diketahui secara andal.
- Tidak menerima payload audit mentah yang tidak tervalidasi dari frontend.

**`RecordAuditEventAction`**

- Membentuk event dari data terstruktur.
- Memvalidasi ukuran/shape snapshot.
- Menyimpan record melalui Eloquent.
- Menjadi satu pintu pencatatan jika kompleksitas memang membenarkan Action terpisah.

**`AuditEvent`**

- Cast JSON dan timestamp.
- Scope query yang benar-benar digunakan, misalnya rentang tanggal, aktor, modul, aksi, dan hasil.
- Tidak menyediakan operasi edit/hapus yang dipakai controller UI.

**`AuditEventPolicy`**

- Mengotorisasi `viewAny` dan `view`.
- Memakai Gate/Policy sesuai konvensi codebase.

**`AuditLogController`**

- Memeriksa otorisasi.
- Memvalidasi filter melalui Form Request.
- Menjalankan query dan pagination server-side.
- Mengembalikan props Inertia yang sudah dibatasi dan aman.
- Tidak berisi logika snapshot/redaksi.

### 9.3 Integrasi pada modul yang ada

Integrasikan pada batas operasi bisnis yang benar, terutama Actions:

- `SavePostAction`
- `SaveEventAction`
- `SaveDocumentAction`
- `DeleteDocumentAction`
- Action jadwal Misa yang relevan setelah ditelusuri pada branch aktif
- Action profil/pelayanan yang mengubah beberapa record
- `SiteSettingsService::setMany()` atau `SettingsController::update()` dengan perbandingan before/after
- Jalur autentikasi yang sesuai untuk login/logout/failure

Sebelum implementasi, telusuri controller dan Action terkait agar audit tidak ditambahkan di dua tempat sekaligus. Jangan membuat daftar integrasi ini menjadi alasan untuk merombak modul yang tidak berhubungan.

---

## 10. Routes dan kontrak Inertia

### 10.1 Route yang disarankan

Ikuti route name dan konvensi bahasa yang ada. Rancangan awal:

| Method | URI konseptual                  | Nama route konseptual | Fungsi                     |
| ------ | ------------------------------- | --------------------- | -------------------------- |
| GET    | `/admin/audit-log`              | `audit-logs.index`    | Daftar, filter, pagination |
| GET    | `/admin/audit-log/{auditEvent}` | `audit-logs.show`     | Detail satu event          |

Dua route GET sudah cukup untuk MVP. Jangan membuat POST/PUT/PATCH/DELETE untuk mengubah catatan audit.

Nama final harus diperiksa terhadap aturan route name, route model binding, dan Wayfinder. Route di atas adalah kontrak yang disarankan; belum didaftarkan.

### 10.2 Props daftar

Props Inertia konseptual:

```ts
type AuditLogIndexProps = {
    events: {
        data: AuditLogListItem[];
        links: PaginationLink[];
        meta?: PaginationMeta;
    };
    filters: {
        search: string;
        from: string;
        to: string;
        actor: string;
        module: string;
        action: string;
        result: string;
    };
    options: {
        modules: SelectOption[];
        actions: SelectOption[];
        results: SelectOption[];
        actors: SelectOption[];
    };
};
```

Bentuk akhir harus mengikuti struktur pagination Inertia/Laravel yang sudah dipakai di modul lain. Hindari membuat wrapper pagination baru jika resource/pagination project sudah menyediakan pola yang sama.

### 10.3 Detail event

Detail hanya mengirim field yang aman. Jangan mengirim `metadata`, IP, user-agent, before/after yang belum direduksi, atau data internal lainnya tanpa kebutuhan dan izin yang jelas.

Jika objek sumber sudah dihapus, detail tetap menggunakan `subject_label` dan snapshot yang disimpan, tidak memerlukan query ke objek yang tidak ada.

### 10.4 URL dan Wayfinder

- Gunakan route yang dihasilkan Wayfinder dari frontend.
- Jangan menulis URL `/admin/audit-log` secara manual di komponen jika pola project mengharuskan helper route.
- Jangan edit file hasil generate di `resources/js/{routes,actions,wayfinder}/`.
- Jalankan `php artisan wayfinder:generate --with-form` sesuai workflow setelah route baru ditambahkan.

---

## 11. Spesifikasi UI/UX

### 11.1 Navigasi

Tambahkan item **Audit Log** sebagai menu administrasi/keamanan, terpisah dari `CONTENT_ENTRIES` dan `ACCOUNT_ENTRIES`. Gunakan ikon Lucide yang sudah tersedia di dependency dan sesuai makna aktivitas/riwayat.

Item hanya ditampilkan bila pengguna berhak melihat Audit Log. Otorisasi backend tetap wajib.

### 11.2 Halaman daftar

Judul: **Audit Log**

Deskripsi: “Riwayat perubahan dan aktivitas penting pada panel pengelola paroki.”

Kolom utama:

| Kolom     | Isi                                                    |
| --------- | ------------------------------------------------------ |
| Waktu     | Tanggal dan waktu lokal `Asia/Pontianak`               |
| Pengguna  | Nama snapshot aktor; label “Sistem” untuk aktor system |
| Aktivitas | Label Bahasa Indonesia dari kode action                |
| Modul     | Label modul                                            |
| Objek     | `subject_label` jika tersedia                          |
| Hasil     | Badge dengan teks; jangan hanya memakai warna          |
| Detail    | Aksi membuka detail                                    |

Perilaku:

- Default sort terbaru lebih dahulu.
- Pagination dari backend.
- Empty state menjelaskan bahwa belum ada aktivitas yang cocok dengan filter.
- Loading state dan error state konsisten dengan UI admin yang sudah ada.
- Layout responsif tanpa horizontal overflow; pada layar sempit gunakan pola tabel responsif yang sudah dipakai project.
- Jangan tampilkan JSON mentah pada tabel.
- Jangan menampilkan seluruh `before`/`after` dalam daftar.

### 11.3 Filter

MVP:

- Rentang tanggal `from` dan `to`.
- Pengguna/aktor.
- Modul.
- Aksi.
- Hasil.
- Pencarian teks pada deskripsi/label objek jika diperlukan.

Aturan:

- Filter diterapkan di query backend.
- Validasi tanggal dan batas rentang.
- Pertahankan filter pada query string dan pagination.
- Tolak nilai filter yang tidak dikenal atau abaikan dengan perilaku konsisten yang terdokumentasi.
- Hindari memuat seluruh aktor/log ke browser hanya untuk membuat filter.

### 11.4 Detail

Tampilkan:

- Waktu kejadian.
- Aktor.
- Ringkasan tindakan.
- Modul dan objek.
- Hasil.
- Request ID jika tersedia.
- Perubahan before/after dalam format diff.
- Metadata keamanan yang diizinkan.

Untuk field yang berubah, tampilkan nilai lama dan baru. Field yang tidak berubah tidak perlu ditampilkan. Nilai null harus ditampilkan secara jelas, misalnya “Kosong”, bukan string `null`.

Untuk JSON panjang, gunakan section yang dapat diperluas. Escape teks; jangan render nilai audit sebagai HTML.

### 11.5 Label dan bahasa

Gunakan label Bahasa Indonesia yang konsisten, contohnya:

| Kode                 | Label UI             |
| -------------------- | -------------------- |
| `created`            | Dibuat               |
| `updated`            | Diperbarui           |
| `published`          | Diterbitkan          |
| `unpublished`        | Publikasi dibatalkan |
| `scheduled`          | Dijadwalkan          |
| `archived`           | Diarsipkan           |
| `deleted`            | Dihapus              |
| `restored`           | Dipulihkan           |
| `login_succeeded`    | Login berhasil       |
| `login_failed`       | Login gagal          |
| `logout`             | Logout               |
| `permission_granted` | Permission diberikan |
| `permission_revoked` | Permission dicabut   |
| `role_changed`       | Role diubah          |

Label modul, hasil, dan aktor sistem harus dipetakan melalui satu sumber definisi agar tidak berbeda antar komponen.

---

## 12. Privasi, keamanan, dan integritas

### 12.1 Redaksi wajib

Jangan simpan nilai:

- password atau konfirmasi password;
- token reset atau token autentikasi;
- session ID, cookie, atau header Authorization;
- API key, app secret, credential database;
- secret SMTP;
- payload request mentah;
- isi file atau binary;
- data pribadi yang tidak relevan untuk tujuan audit.

Gunakan allowlist per modul dan per action. Redaksi generik dapat menjadi lapisan tambahan, tetapi tidak menggantikan allowlist.

### 12.2 IP address dan user-agent

IP dan user-agent bersifat opsional. Jika dipilih:

- dokumentasikan tujuan penggunaannya;
- batasi akses;
- tentukan retensi;
- jangan menggunakannya sebagai bukti identitas tunggal;
- jangan mengeksposnya di daftar publik atau props yang tidak perlu.

### 12.3 Append-only

Pada aplikasi:

- tidak ada UI edit/hapus;
- tidak ada endpoint mutasi untuk event audit;
- model dan controller tidak mengekspos operasi update/delete kepada pengguna;
- hanya service pencatatan yang digunakan untuk insert.

Namun, ini bukan jaminan anti-manipulasi mutlak terhadap pemilik server/database. Jika diperlukan, gunakan backup terpisah dan kontrol akses infrastruktur. Hash chaining, immutable storage, atau external logging berada di luar MVP.

### 12.4 Retensi

MVP harus mendokumentasikan kebijakan retensi sebelum produksi. Rekomendasi awal untuk dipertimbangkan adalah menyimpan log administratif biasa selama 12 bulan, tetapi periode final perlu disetujui pemilik sistem berdasarkan kebutuhan operasional dan privasi.

Jangan membuat tombol “hapus semua log”. Jika pengarsipan/pembersihan otomatis ditambahkan kemudian, kebijakan harus:

- menetapkan kategori dan periode retensi;
- memerlukan otorisasi terbatas;
- mencatat operasi pemeliharaan;
- memastikan backup/arsip tersedia sesuai kebutuhan;
- diuji sebelum dijalankan di produksi.

### 12.5 Performa

- Gunakan pagination server-side.
- Batasi page size maksimum.
- Jangan eager-load objek yang sudah dihapus atau semua JSON snapshot pada daftar.
- Tambahkan indeks berdasarkan query aktual.
- Jangan membuat statistik mahal pada setiap request bila dapat dihitung secara efisien.
- Uji daftar pada dataset besar yang representatif.

---

## 13. Acceptance criteria

Setiap acceptance criterion berikut harus memiliki test otomatis atau bukti verifikasi yang sesuai sebelum fitur dianggap selesai.

### 13.1 Pencatatan

- [ ] `AUD-AC-001`: operasi create pada modul yang terintegrasi menghasilkan satu event `created` setelah operasi berhasil.
- [ ] `AUD-AC-002`: update menghasilkan event `updated` dengan hanya field yang berubah dan nilai before/after yang aman.
- [ ] `AUD-AC-003`: perubahan status publikasi dicatat dengan status sebelumnya dan sesudahnya.
- [ ] `AUD-AC-004`: delete menyimpan snapshot yang cukup untuk mengidentifikasi objek setelah record dihapus.
- [ ] `AUD-AC-005`: nama aktor dan waktu kejadian tetap tersedia bila akun pelaku dinonaktifkan atau dihapus.
- [ ] `AUD-AC-006`: event sistem dapat dibedakan dari event pengguna.
- [ ] `AUD-AC-007`: kegagalan validasi atau transaksi tidak menghasilkan event sukses.
- [ ] `AUD-AC-008`: retry job tidak menghasilkan event ganda tanpa alasan yang didokumentasikan.
- [ ] `AUD-AC-009`: perubahan pengaturan hanya mencatat key yang berubah.
- [ ] `AUD-AC-010`: perubahan secret hanya mencatat bahwa secret berubah; nilai secret tidak disimpan.

### 13.2 Keamanan dan otorisasi

- [ ] `AUD-AC-011`: guest tidak dapat membuka daftar atau detail Audit Log.
- [ ] `AUD-AC-012`: user terautentikasi tanpa izin `auditlogs.view` mendapat respons 403.
- [ ] `AUD-AC-013`: otorisasi berlaku pada akses detail langsung, bukan hanya menu.
- [ ] `AUD-AC-014`: tidak ada route UI untuk update/delete event.
- [ ] `AUD-AC-015`: password, token, cookie, secret, dan kredensial tidak muncul dalam `before`, `after`, `description`, atau `metadata`.
- [ ] `AUD-AC-016`: input filter tidak dapat digunakan untuk mengakses data di luar query yang diizinkan.
- [ ] `AUD-AC-017`: data snapshot ditampilkan sebagai teks yang aman dan tidak dieksekusi sebagai HTML.
- [ ] `AUD-AC-018`: event tetap terbaca setelah objek sumber dihapus.

### 13.3 Query dan UI

- [ ] `AUD-AC-019`: daftar default diurutkan berdasarkan waktu kejadian terbaru.
- [ ] `AUD-AC-020`: pagination dijalankan di backend dan mempunyai batas page size.
- [ ] `AUD-AC-021`: filter tanggal, aktor, modul, aksi, dan hasil bekerja serta bertahan di query string.
- [ ] `AUD-AC-022`: pencarian teks, jika disertakan, hanya mencari field yang disepakati.
- [ ] `AUD-AC-023`: detail menampilkan aktor, waktu, tindakan, objek, hasil, dan diff yang aman.
- [ ] `AUD-AC-024`: tanggal/waktu ditampilkan menggunakan `Asia/Pontianak`.
- [ ] `AUD-AC-025`: empty state, loading/error state, dan tampilan responsif mengikuti pola UI project.
- [ ] `AUD-AC-026`: menu Audit Log tidak ditampilkan bagi pengguna tanpa permission; backend tetap melindungi route.

### 13.4 Quality gate

- [ ] `AUD-AC-027`: migration baru berhasil pada MySQL 8 dan dapat di-rollback di lingkungan non-produksi.
- [ ] `AUD-AC-028`: Pest Feature Tests memakai konfigurasi database test yang ditetapkan project.
- [ ] `AUD-AC-029`: seluruh test Audit Log lulus secara individual dan dalam suite.
- [ ] `AUD-AC-030`: `composer ci:check` lulus.
- [ ] `AUD-AC-031`: route Wayfinder dihasilkan ulang dan file generated tidak diedit manual.
- [ ] `AUD-AC-032`: kebijakan retensi, field allowlist, dan keterbatasan integritas didokumentasikan.

---

## 14. Rencana test

### 14.1 Feature tests PHP/Pest

Buat test dalam `tests/Feature/Audit/` sesuai konvensi direktori per modul. Nama file final dapat dipilih implementer, dengan cakupan minimum:

1. `AuditLogAuthorizationTest`
   - guest ditolak;
   - user tanpa permission ditolak;
   - Super Admin dapat membuka daftar/detail;
   - detail dengan ID yang tidak ada menghasilkan 404;
   - tidak ada route mutasi event.

2. `AuditEventRecordingTest`
   - create/update/delete menghasilkan event;
   - actor dan subject benar;
   - before/after hanya memuat field allowlisted;
   - waktu kejadian memakai UTC;
   - rollback mutasi tidak menyisakan event sukses.

3. `AuditSensitiveDataTest`
   - password/token/secret tidak disimpan;
   - metadata arbitrer dari request tidak diterima;
   - HTML pada snapshot tidak dieksekusi saat ditampilkan.

4. `AuditLogFilteringTest`
   - filter tanggal;
   - filter aktor;
   - filter modul/action/result;
   - kombinasi filter;
   - parameter invalid;
   - query string dipertahankan pada pagination.

5. `AuditLogDeletionSnapshotTest`
   - snapshot tetap tampil setelah objek sumber soft-deleted/deleted;
   - actor snapshot tetap tersedia setelah akun tidak aktif/dihapus.

6. `AuditLogIntegrationTest`
   - integrasi untuk berita, agenda, jadwal Misa, pengaturan, dokumen/media, dan aksi profil/pelayanan yang dipilih;
   - tidak ada duplikasi event untuk satu operasi bisnis;
   - kegagalan storage/queue tidak menghasilkan deskripsi yang salah.

Jangan menambah `RefreshDatabase` secara manual pada feature test; `tests/Pest.php` sudah menerapkannya pada direktori Feature.

### 14.2 Unit tests

Gunakan unit test untuk logic yang dapat diuji tanpa database, misalnya:

- normalisasi kode action/module;
- allowlist dan redaksi;
- pembentukan diff;
- pembatasan ukuran snapshot;
- pemetaan label bila mapping berada pada class murni.

Vitest hanya diperlukan untuk logic frontend yang memang layak diuji secara unit. Jangan menambah test React untuk kode presentasi sederhana tanpa perilaku yang perlu diverifikasi.

### 14.3 E2E

E2E bukan pengganti feature test. Tambahkan satu alur Playwright bila sesuai dengan pola suite:

- login sebagai Super Admin;
- membuka Audit Log;
- memfilter aktivitas;
- membuka detail event;
- memastikan pengguna tanpa akses tidak dapat membukanya.

Playwright di project memakai `workers: 1` dan memerlukan persiapan E2E terlebih dahulu sesuai `AGENTS.md`. Jangan menjalankan E2E sebagai langkah pertama bila feature tests sudah dapat membuktikan kontrak backend.

---

## 15. Urutan implementasi

Jangan mengerjakan semua file sekaligus tanpa validasi bertahap.

### Tahap 0 — Konfirmasi kontrak

- [ ] Pastikan spesifikasi ini disetujui.
- [ ] Periksa kembali struktur branch dan keputusan terbaru sebelum coding.
- [ ] Tetapkan apakah IP/user-agent masuk MVP.
- [ ] Tetapkan retensi awal.
- [ ] Tetapkan daftar field allowlist per modul.
- [ ] Tentukan apakah login/logout/failure termasuk pada tabel audit yang sama atau sebagian tetap di log keamanan terpisah.

### Tahap 1 — Fondasi data dan pencatatan

- [ ] Tambahkan migration `audit_events`.
- [ ] Tambahkan model dan cast.
- [ ] Implementasikan redaksi dan allowlist.
- [ ] Implementasikan service/action pencatatan.
- [ ] Tambahkan unit/feature test fondasi.
- [ ] Verifikasi rollback migration di lingkungan test.

### Tahap 2 — Integrasi aktivitas bisnis

- [ ] Integrasikan berita.
- [ ] Integrasikan agenda.
- [ ] Integrasikan jadwal Misa.
- [ ] Integrasikan pengaturan situs.
- [ ] Integrasikan dokumen/media dan status job yang relevan.
- [ ] Integrasikan profil, pelayanan, serta master data terpilih.
- [ ] Integrasikan auth/permission yang jalurnya memang tersedia.
- [ ] Pastikan tidak ada pencatatan ganda.

### Tahap 3 — Read API internal, route, dan otorisasi

- [ ] Tambahkan permission `auditlogs.view` pada seeder.
- [ ] Tambahkan policy/gate sesuai konvensi.
- [ ] Tambahkan Form Request filter.
- [ ] Tambahkan route daftar dan detail di group admin.
- [ ] Implementasikan query/pagination server-side.
- [ ] Jalankan Wayfinder generator.

### Tahap 4 — UI

- [ ] Tambahkan halaman daftar di feature admin.
- [ ] Tambahkan filter dan query-string state.
- [ ] Tambahkan detail/diff.
- [ ] Tambahkan sidebar item.
- [ ] Pastikan bahasa, timezone, responsive, loading, error, dan empty state.

### Tahap 5 — Verifikasi

- [ ] Jalankan test Audit Log satu per satu.
- [ ] Jalankan test integrasi modul.
- [ ] Jalankan `composer ci:check`.
- [ ] Jalankan E2E yang relevan jika lingkungan tersedia.
- [ ] Periksa migration, query plan, redaksi, dan izin.
- [ ] Perbarui dokumentasi keputusan bila ada keputusan arsitektur baru.
- [ ] Laporkan hasil aktual; jangan mengklaim command lulus jika belum dijalankan.

---

## 16. Definition of Done

Audit Log MVP hanya dianggap selesai apabila:

1. Catatan dibuat otomatis di backend untuk seluruh event P0 yang disepakati.
2. Setiap event mempunyai aktor, waktu, aksi, modul, hasil, dan identitas objek yang relevan.
3. Before/after aman dan cukup untuk menjelaskan perubahan.
4. Secret dan data yang dilarang tidak tersimpan.
5. Tidak ada route aplikasi untuk mengubah atau menghapus event audit.
6. Route daftar/detail dilindungi middleware admin dan permission.
7. Filter dan pagination dilakukan di backend.
8. Waktu ditampilkan dalam `Asia/Pontianak`.
9. Feature tests membuktikan pencatatan, rollback, redaksi, akses, dan filter.
10. `composer ci:check` serta test yang relevan lulus pada lingkungan yang benar.
11. Kebijakan retensi dan keterbatasan append-only terdokumentasi.
12. Tidak ada klaim bahwa log immutable terhadap administrator database/server tanpa kontrol eksternal yang sesuai.

---

## 17. Keputusan yang masih perlu dikunci

Spesifikasi ini menetapkan default konservatif. Keputusan berikut perlu dikonfirmasi sebelum migration dibuat:

| ID           | Pertanyaan                          | Default yang disarankan                                                                                             |
| ------------ | ----------------------------------- | ------------------------------------------------------------------------------------------------------------------- |
| `AUD-DEC-01` | Apakah IP address disimpan?         | Tidak untuk MVP kecuali ada kebutuhan investigasi yang disetujui.                                                   |
| `AUD-DEC-02` | Apakah user-agent disimpan?         | Tidak untuk MVP kecuali diperlukan; jika ya, batasi panjang dan akses.                                              |
| `AUD-DEC-03` | Apakah login gagal masuk Audit Log? | Ya, tetapi dibatasi dan tanpa password/token; pertimbangkan log keamanan terpisah jika volume tinggi.               |
| `AUD-DEC-04` | Retensi awal                        | Usulan 12 bulan untuk aktivitas administratif; perlu persetujuan pemilik sistem.                                    |
| `AUD-DEC-05` | Bentuk before/after                 | JSON terpilih per event, bukan seluruh model/request.                                                               |
| `AUD-DEC-06` | Audit event dan transaksi bisnis    | Event sukses mutasi database ditulis dalam transaksi yang sama bila memungkinkan.                                   |
| `AUD-DEC-07` | Audit media dan storage             | Catat kejadian bisnis dan status job secara terpisah; jangan menyatakan upload diterima berarti pemrosesan selesai. |
| `AUD-DEC-08` | Ekspor CSV                          | Di luar P0; tambahkan setelah daftar/detail stabil.                                                                 |
| `AUD-DEC-09` | Hash chain / immutable external log | Di luar MVP; evaluasi hanya bila model ancaman memerlukannya.                                                       |
| `AUD-DEC-10` | Penempatan menu                     | Item administrasi tersendiri, bukan di kelompok Konten atau Akun.                                                   |

Jika keputusan tersebut berbeda dari kebutuhan paroki, dokumentasikan keputusan final di `docs/DECISIONS.md` sebelum implementasi.

---

## 18. Rujukan repository

- Branch yang diaudit: `https://github.com/Rumahkodingku/website-paroki-hspmtb-putussibau/tree/style/update-ui`
- PRD: `docs/PRD.md`
- Arsitektur: `docs/ARCHITECTURE.md`
- Keputusan: `docs/DECISIONS.md`
- Instruksi agent: `AGENTS.md`
- Route: `routes/web.php`
- Permission seeder: `database/seeders/PermissionSeeder.php`
- Test setup: `tests/Pest.php`
- Sidebar navigation: `resources/js/layouts/app/app-sidebar-nav.ts`
- Dashboard roadmap: `docs/roadmap/phase-9b-admin-dashboard.md`

Dokumen ini merupakan spesifikasi implementasi untuk ditinjau dan disetujui. Dokumen ini tidak menyatakan bahwa Audit Log sudah diimplementasikan.
