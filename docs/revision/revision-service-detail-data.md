# Revisi Skema Data Modul Pelayanan — Detail Sakramen

> **Proyek:** Website Gereja Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau  
> **Branch acuan:** `style/update-ui`  
> **Stack:** Laravel 13, Inertia.js 3, React 19, TypeScript, Tailwind CSS 4, MySQL  
> **Status:** Spesifikasi revisi untuk audit dan implementasi  
> **Cakupan:** `services`, `service_requirements`, `service_steps`, dan `service_faqs`  
> **Pengecualian eksplisit:** tabel `service_categories` tidak diubah.

---

## 1. Tujuan

Dokumen ini mendefinisikan revisi skema database agar halaman publik detail pelayanan/sakramen dapat menyajikan informasi yang lebih jelas, mudah dipindai, aksesibel, dan membantu umat memahami persyaratan serta tahapan pelayanan.

Perubahan harus mengikuti codebase aktual, bukan membangun ulang modul. Sebelum implementasi, coding agent wajib memeriksa migration, model, Form Request, controller/action, Resource atau props Inertia, admin form, factory, seeder, test, dan dokumen keputusan pada branch target.

Tujuan UX yang didukung data:

- Menjelaskan sasaran layanan serta hal yang perlu dipersiapkan.
- Membedakan persyaratan wajib dengan persyaratan yang hanya berlaku pada kondisi tertentu.
- Menyajikan langkah prosedur dengan urutan stabil.
- Menghindari informasi yang tidak diketahui atau belum disetujui ditampilkan sebagai fakta.
- Mempertahankan satu sumber kebenaran untuk status publikasi, kontak, CTA, media, dan dokumen.
- Mendukung UI responsif desktop, tablet, dan mobile tanpa menyimpan data presentasi yang dapat dihitung dari data yang sudah ada.

Dokumen ini berfokus pada perubahan data. Redesign frontend dan admin form perlu mengonsumsi kontrak data baru, tetapi tidak boleh menjadi alasan untuk melakukan refactor yang tidak terkait.

## 2. Ketentuan yang Tidak Boleh Dilanggar

1. **Jangan mengubah tabel `service_categories`.** Tidak ada penambahan/penghapusan kolom, index, constraint, perubahan tipe, migration, atau perubahan perilaku model kategori dalam scope ini.
2. Jangan membuat kolom duplikat bagi data yang sudah tersedia sebagai kolom, relasi, atau nilai turunan.
3. Jangan menambah `is_published`; `services.is_active` adalah satu-satunya publication flag.
4. Jangan mengubah primary key, slug, foreign key, unique constraint, atau aturan soft-delete yang sudah ada tanpa keputusan terpisah.
5. Jangan mengubah migration yang telah diterapkan. Semua perubahan database menggunakan migration baru yang dapat dijalankan pada database yang sudah ada.
6. Jangan seed atau menerbitkan data sakramen, persyaratan, jadwal, biaya, kontak, atau URL formulir fiktif sebagai data resmi.
7. Field nullable atau opsional pada database harus ditangani secara eksplisit oleh validator, backend Resource, TypeScript types, dan UI.
8. Jangan membuat tabel pendaftaran umat. Modul ini memberi informasi dan mengarahkan ke formulir eksternal/kontak; website tidak menyimpan data pendaftar.
9. Jangan membuat tabel media atau dokumen alternatif. Gunakan modul media dan Download yang sudah ada.
10. Implementasi harus mengikuti konvensi Laravel, React/Inertia, struktur feature, type, testing, dan quality gate yang sudah berlaku di repo.

## 3. Audit Skema Aktual pada Branch Acuan

### 3.1 `service_categories` — tidak diubah

Tabel ini didefinisikan pada `database/migrations/2026_10_14_000000_create_service_tables.php`. Migration tersebut awalnya membuat kolom `name`, `slug`, `description`, `icon`, `sort_order`, dan `is_active` selain ID, timestamps, serta soft deletes. Migration lanjutan `2026_10_15_000000_replace_icon_with_thumbnail_on_services.php` kemudian menghapus kolom `icon` dari `service_categories`.

Karena itu, keadaan skema akhir kategori ditentukan oleh seluruh rangkaian migration, bukan hanya migration create. Dalam revisi ini tidak boleh dibuat migration atau perubahan model yang ditujukan pada `service_categories`. Model `ServiceCategory` yang sudah ada tetap digunakan apa adanya.

### 3.2 `services` — field relevan yang sudah tersedia

Migration awal dan migration lanjutan telah menyediakan:

- `title`, `slug`, `service_category_id`
- `thumbnail_media_id` nullable FK ke `media`, dengan `ON DELETE SET NULL`
- `thumbnail_alt` nullable
- `summary`, `introduction`
- `schedule_info`, `fee_info`
- `contact_name`, `contact_phone`
- `form_url`, `form_button_label`, `is_form_open`, `form_closed_message`
- `is_emergency`, `is_active`, `sort_order`
- timestamps dan soft deletes

Media thumbnail bukan URL mentah yang disimpan di `services`; relasi `thumbnailMedia()` mengacu pada model `Media`. URL media merupakan hasil resolusi relasi/Resource. **Jangan menambahkan `thumbnail`, `thumbnail_url`, atau `thumbnail_alt` kedua kali.**

Relasi `documents()` many-to-many juga sudah ada pada model `Service`. Jangan menambah tabel atau field dokumen duplikat.

Model `Service` sudah mempunyai method aturan Google Form dan kontak seperti `hasFormUrl()`, `usesGoogleForm()`, `isAcceptingRegistrations()`, dan `hasOwnContact()`. Gunakan aturan domain existing sebagai titik awal, bukan menduplikasikannya dalam kolom status baru.

### 3.3 Tabel anak yang tersedia

Ketiga tabel anak sudah memiliki relasi `service_id`, timestamps, `sort_order`, foreign key dengan `ON DELETE CASCADE`, dan index gabungan `(service_id, sort_order)`.

- `service_requirements`: `text`, `note`, `sort_order`
- `service_steps`: `title`, `description`, `sort_order`
- `service_faqs`: `question`, `answer`, `sort_order`

Model-model saat ini memakai atribut `#[Fillable]`, cast integer pada `sort_order`, dan scope `ordered()` yang mengurutkan menurut `sort_order`, lalu `id`. Pertahankan perilaku ini.

## 4. Keputusan Perubahan Kolom

### 4.1 Ringkasan perubahan

| Tabel                  | Kolom                         | Prioritas | Tujuan                                              |
| ---------------------- | ----------------------------- | --------: | --------------------------------------------------- |
| `service_categories`   | Tidak ada perubahan           |         — | Dikecualikan dari scope                             |
| `services`             | `audience_info` nullable      |        P1 | Menjelaskan pihak/kelompok yang dituju oleh layanan |
| `services`             | `preparation_info` nullable   |        P1 | Memisahkan persiapan dari informasi jadwal          |
| `services`             | `thumbnail_alt`               | Sudah ada | Tidak boleh dibuat ulang                            |
| `service_requirements` | `requirement_condition`       |        P0 | Membedakan persyaratan wajib dan kondisional        |
| `service_requirements` | `applies_to` nullable         |        P1 | Menjelaskan pihak yang dikenai persyaratan          |
| `service_steps`        | Tidak menambah kolom pada MVP |         — | Data existing cukup untuk timeline editorial        |
| `service_faqs`         | Tidak menambah kolom pada MVP |         — | Data existing cukup untuk accordion FAQ             |

Kolom `hero_image`, `action_url`, `action_label`, `estimated_duration`, `help_text`, `content_updated_at`, kategori FAQ, dan featured flag FAQ **tidak termasuk implementasi awal**. Jangan menambahkannya tanpa kebutuhan terverifikasi setelah audit admin form dan UX.

### 4.2 `service_requirements.requirement_condition`

Field boolean `is_required` saja kurang ekspresif untuk seluruh aturan. Sebuah dokumen dapat berlaku umum, hanya berlaku pada kondisi tertentu, atau diperlukan ketika diminta. Karena itu gunakan satu field kondisi, bukan kombinasi kolom boolean dan kondisi yang tumpang tindih.

| Nilai database | Label UI     | Makna                                              |
| -------------- | ------------ | -------------------------------------------------- |
| `required`     | Wajib        | Berlaku sebagai persyaratan umum                   |
| `conditional`  | Jika berlaku | Berlaku jika situasi yang disebutkan memang sesuai |
| `upon_request` | Bila diminta | Diperlukan jika diminta oleh paroki/sekretariat    |

Ketentuan:

- Tipe konseptual: `VARCHAR(32)`.
- Default: `required`, supaya data lama tetap valid setelah migration.
- Backend wajib menolak nilai di luar allowlist tersebut.
- UI admin harus menggunakan pilihan terstruktur, bukan membebaskan admin mengetik kode kondisi.
- Nilai awal pada data existing mengikuti default dan **tidak boleh ditebak ulang** melalui parsing kata seperti “bila” atau “jika”. Data berpotensi kondisional harus ditinjau oleh admin/pihak paroki.

UI publik tidak wajib memberi badge pada `required`; untuk `conditional` tampilkan “Jika berlaku”, dan untuk `upon_request` tampilkan “Bila diminta”. Catatan `note` tetap digunakan untuk penjelasan tambahan.

### 4.3 `service_requirements.applies_to`

Field ini memberi konteks persyaratan berlaku untuk siapa, khususnya ketika satu halaman membahas kelompok yang berbeda, seperti calon baptis, orang tua, dan wali baptis.

- Tipe konseptual: `VARCHAR(100)`, nullable.
- Berisi label sasaran yang singkat dan ramah pengguna, misalnya “Orang tua”, “Wali baptis”, “Calon baptis dewasa”, atau “Calon penerima sakramen”.
- `NULL` berarti sasaran tidak ditentukan atau persyaratan berlaku umum.
- Nilai harus teks biasa; tidak menerima HTML.
- Jangan menerapkan enum global yang terlalu sempit lintas semua sakramen karena sasaran layanan dapat berbeda-beda. Jika diperlukan opsi pilihan di admin, gunakan daftar saran yang dapat dikembangkan serta validasi panjang/teks pada backend, bukan mengunci domain ke daftar yang tidak lengkap.
- Label dan aturan harus diverifikasi pihak paroki sebelum dipublikasikan.

Jika `applies_to` tidak `NULL`, UI boleh menampilkan label kecil seperti “Untuk wali baptis”. Bila kosong, jangan tampilkan placeholder.

### 4.4 `services.audience_info`

Tambahkan `TEXT NULL` untuk menjelaskan siapa yang dilayani oleh suatu sakramen atau pelayanan.

Contoh makna yang mungkin perlu dijelaskan oleh konten paroki: apakah informasi yang sama berlaku bagi bayi/anak, calon baptis dewasa, orang tua/wali, atau kelompok lain. Nilai aktual wajib ditulis dan disetujui admin/paroki; contoh ini bukan konten siap publish.

Perbedaan tanggung jawab data:

- `summary`: ringkasan singkat untuk hero dan kartu layanan.
- `introduction`: makna/konteks pastoral, berupa rich text yang tetap disanitasi menggunakan mekanisme existing.
- `audience_info`: sasaran layanan.
- `preparation_info`: persiapan sebelum mengikuti proses.
- `schedule_info`: jadwal pelaksanaan atau cara mengonfirmasi jadwal.
- `fee_info`: informasi biaya/persembahan, `NULL` jika belum dikonfirmasi.

### 4.5 `services.preparation_info`

Tambahkan `TEXT NULL` untuk petunjuk persiapan sebelum layanan. Teks biasa sudah cukup pada MVP; jangan menjadikan field ini rich text tanpa kebutuhan nyata dan sanitizer yang sesuai.

Field ini menghindari pencampuran semua hal dalam `schedule_info`. Namun, **jangan otomatis memindahkan atau menyalin** sebagian teks `schedule_info` ke `preparation_info` dengan heuristik. Konten existing harus dipetakan secara sadar dan diperiksa admin.

### 4.6 Kenapa tidak menambah kolom pada `service_steps`?

`title`, `description`, dan `sort_order` sudah memadai untuk timeline prosedur. Nomor langkah diturunkan dari urutan database. Field seperti `action_label` atau `action_url` hanya diperlukan jika ada kebutuhan produk yang konkret untuk menautkan langkah ke tindakan spesifik. Penambahan tersebut ditunda agar tidak memperbesar Form Request dan admin form tanpa manfaat yang jelas.

### 4.7 Kenapa tidak menambah kolom pada `service_faqs`?

Untuk jumlah FAQ saat ini, `question`, `answer`, dan `sort_order` sudah memadai. `category` dan `is_featured` ditunda sampai jumlah FAQ benar-benar menuntut filter, pengelompokan, atau prioritas. FAQ tetap berupa teks biasa kecuali ada kebutuhan rich text yang telah disepakati.

## 5. Migration Baru

Jangan mengedit migration yang telah diterapkan. Buat migration baru dengan timestamp berikutnya yang tersedia di repo, misalnya:

```text
database/migrations/YYYY_MM_DD_HHMMSS_add_public_detail_fields_to_services_and_requirements.php
```

Nama akhir mengikuti timestamp saat dibuat dan konvensi repository. Migration ini **hanya boleh mengubah tabel `services` dan `service_requirements`**.

Skema konseptual:

```php
Schema::table('services', function (Blueprint $table) {
    $table->text('audience_info')->nullable();
    $table->text('preparation_info')->nullable();
});

Schema::table('service_requirements', function (Blueprint $table) {
    $table->string('requirement_condition', 32)->default('required');
    $table->string('applies_to', 100)->nullable();
});
```

Kode ini adalah skema tujuan, bukan klaim bahwa migration telah dibuat atau diuji. Sebelum implementasi:

- Periksa branch dan working tree untuk memastikan tidak ada kolom serupa pada migration atau perubahan lokal lain.
- Gunakan `after()` hanya jika itu konvensi project dan urutan kolom memang diperlukan; urutan kolom bukan kontrak API.
- Jangan menambah index untuk kolom teks ini tanpa query yang membutuhkannya.
- Jangan menyentuh `service_categories`, termasuk secara tidak sengaja pada `up()` atau `down()`.
- `down()` hanya boleh menghapus kolom yang dibuat migration tersebut.
- Jalankan upgrade, migrate, dan rollback pada database test MySQL terisolasi.

## 6. Perubahan Model dan Validasi Backend

### 6.1 Model `Service`

- Tambahkan `audience_info` dan `preparation_info` ke PHPDoc jika model memelihara anotasi property.
- Tambahkan kedua kolom ke `#[Fillable]`.
- Tidak diperlukan cast khusus untuk dua kolom teks.
- Jangan mengubah relasi `serviceCategory()`, `thumbnailMedia()`, `documents()`, `requirements()`, `steps()`, dan `faqs()`.
- Jangan mengubah scope `active()`, `ordered()`, route binding slug, atau logika CTA yang sudah ada sebagai bagian dari pekerjaan ini.

### 6.2 Model `ServiceRequirement`

- Tambahkan `requirement_condition` dan `applies_to` ke PHPDoc bila digunakan.
- Tambahkan kedua field ke `#[Fillable]`.
- Tidak perlu cast khusus jika keduanya tetap string.
- Pertahankan `ordered()` dan mekanisme existing yang memperbarui `services.updated_at` ketika child content berubah. Dokumentasi model menyatakan perubahan child diproses controller/action, bukan `$touches`; jangan mengubah pola tersebut tanpa alasan dan test.

### 6.3 Form Request

Cari Form Request aktual melalui route/controller pada branch. Jangan mengarang nama class. Terapkan validasi pada jalur create dan update yang benar-benar dipakai.

Validasi minimal:

- `audience_info`: nullable string, batas panjang mengikuti keputusan field `TEXT` dan batas input aplikasi.
- `preparation_info`: nullable string, batas panjang mengikuti konvensi existing.
- `requirement_condition`: default server-side `required` untuk input yang tidak mengirim nilai; jika diberikan, hanya nilai `required`, `conditional`, atau `upon_request`.
- `applies_to`: nullable string, maksimum 100 karakter.
- `text` dan `note`: tetap teks biasa, tanpa HTML bebas.
- `introduction`: tetap disanitasi lewat sanitizer existing.
- Media, URL, nomor kontak, status publikasi, status Google Form dan aturan emergency tetap memakai validasi/domain rule existing.

Pesan validasi harus berbahasa Indonesia sesuai konvensi repo. Validasi backend adalah otoritatif; jangan hanya memvalidasi allowlist di frontend.

## 7. Perubahan Admin Form

Perbarui form layanan yang sudah ada tanpa mengganti pola layout/form component project.

### Data umum

Tambahkan field berikut di bagian yang paling sesuai:

- **Sasaran layanan** (`audience_info`) — textarea opsional.
- **Persiapan** (`preparation_info`) — textarea opsional.

Pertahankan field `summary`, editor rich text `introduction`, `schedule_info`, dan `fee_info`; jelaskan perbedaannya melalui helper text agar admin tidak mengulang isi yang sama di banyak tempat.

### Persyaratan

Setiap item persyaratan harus dapat mengelola:

- Teks persyaratan (`text`).
- Catatan tambahan (`note`).
- Kondisi persyaratan (`requirement_condition`): **Wajib**, **Jika berlaku**, atau **Bila diminta**.
- Sasaran persyaratan (`applies_to`): teks singkat opsional.
- Urutan tampilan (`sort_order`) melalui pola reorder yang telah ada.

Pastikan semua nilai tersimpan dan terbaca kembali ketika edit. Perubahan child content wajib tetap memperbarui `services.updated_at` menurut mekanisme existing.

## 8. Kontrak Data Publik / Inertia / TypeScript

Resource/DTO/props yang menyajikan detail layanan harus menambahkan field baru tanpa menghapus atau mengganti field existing.

Delta tipe konseptual:

```ts
type RequirementCondition =
    | 'required'
    | 'conditional'
    | 'upon_request';

type PublicServiceRequirement = {
    id: number;
    text: string;
    note: string | null;
    requirement_condition: RequirementCondition;
    applies_to: string | null;
};

type PublicServiceDetail = {
    // Pertahankan semua properti existing.
    audience_info: string | null;
    preparation_info: string | null;
    requirements: PublicServiceRequirement[];
};
```

Ini contoh **delta** TypeScript, bukan pengganti seluruh tipe existing. Sesuaikan naming dengan Resource dan type saat ini. Karena migration ada pada database saja, backend harus eksplisit menambahkan field baru ke payload publik; jangan menganggap perubahan kolom otomatis muncul di Inertia props.

Aturan kontrak:

- Pertahankan penamaan dan nullability yang konsisten dengan API existing.
- Urutkan `requirements`, `steps`, dan `faqs` berdasarkan `sort_order`, lalu `id`.
- Jangan mengirim kolom internal yang tidak dibutuhkan frontend.
- Jangan kirim URL media palsu; gunakan relasi media/Resource existing.
- Status CTA tetap dihitung oleh domain logic existing; jangan simpan `cta.state` sebagai kolom baru.
- `contact.tel` dan `contact.whatsapp` tetap menjadi nilai turunan dari nomor yang divalidasi, jika pola itu memang kontrak Resource existing.
- Dokumen tetap berasal dari relasi modul Download.
- SEO tetap memakai mekanisme existing.

## 9. Perilaku UI yang Diharapkan

### Hero editorial

Gunakan kategori, judul, ringkasan, media thumbnail, alt text, dan CTA existing. Desktop menggunakan dua kolom; mobile memprioritaskan judul/ringkasan/CTA dan menampilkan foto sesuai mockup yang disepakati. Tidak diperlukan `hero_image` terpisah sebelum audit membuktikan media thumbnail tidak cukup.

### Sasaran layanan

Jika `audience_info` berisi teks, tampilkan section seperti “Untuk Siapa?” atau ringkasan sasaran layanan. Jika `NULL` atau kosong, section disembunyikan. Jangan membuat copy generik yang tampak seperti konten resmi.

### Persiapan

Jika `preparation_info` berisi teks, tampilkan section “Persiapan” terpisah dari “Jadwal”. Jika kosong, section disembunyikan.

### Checklist persyaratan

- Item dirender sebagai daftar vertikal yang mudah dipindai.
- `note` ditampilkan di bawah teks utama jika tersedia.
- `requirement_condition` mengatur label kondisi.
- `applies_to` ditampilkan hanya jika ada.
- Nomor dan urutan berasal dari collection yang sudah diurutkan, bukan nomor yang diketik admin.

### Timeline prosedur

Tetap menggunakan `service_steps`. Nomor tampil diturunkan dari urutan. `description` nullable dan disembunyikan bila kosong. Tidak diperlukan field URL tindakan pada MVP.

### Jadwal, biaya, CTA, dokumen, dan FAQ

- `schedule_info` hanya muncul jika tersedia.
- `fee_info = NULL` tidak boleh dirender sebagai “Gratis”.
- CTA mengikuti kondisi form terbuka, form tertutup, tidak memakai form, dan emergency sesuai implementasi domain existing.
- Emergency service tidak boleh menampilkan Google Form.
- FAQ memakai accordion accessible; skema FAQ tidak berubah.
- Dokumen hanya ditampilkan bila relasi `documents` berisi dokumen yang eligible untuk publik.
- Informasi `updated_at` tetap mengikuti mekanisme SVC-03; perubahan child content harus memperbarui waktu layanan lewat pola existing.

## 10. Backfill dan Data Existing

Migration harus kompatibel dengan record yang sudah ada:

- `requirement_condition` default `required`, sehingga record lama tetap mempunyai nilai valid.
- `applies_to` nullable; jangan menebak sasaran dari teks lama.
- `audience_info` dan `preparation_info` nullable; jangan mengurai otomatis `introduction` atau `schedule_info` berdasarkan heuristik.
- Admin dapat melengkapi metadata baru secara bertahap.
- Item dengan teks “bila diminta” dapat ditandai untuk review konten, tetapi nilai kondisi final harus dikonfirmasi.
- Jangan mengaktifkan layanan yang sebelumnya nonaktif hanya karena migration berhasil.
- Jangan mengubah kategori yang sudah terhubung atau data `service_categories`.

## 11. Testing dan Acceptance Criteria

### Database/migration

- [ ] Migration berhasil pada database MySQL test kosong.
- [ ] Upgrade berhasil pada database yang telah menjalankan migration existing.
- [ ] Rollback hanya menghapus kolom yang dibuat oleh migration baru.
- [ ] `service_categories` tidak disentuh oleh `up()` atau `down()` dan struktur finalnya tidak berubah.
- [ ] Foreign key, soft deletes, unique slug, media relation, dan pivot dokumen tidak berubah.

### Backend/validasi

- [ ] Service create/update menerima `audience_info` dan `preparation_info` nullable.
- [ ] Requirement create/update menerima kondisi dan sasaran valid.
- [ ] Kondisi yang tidak dikenal ditolak server.
- [ ] Input kosong/null dinormalisasi secara konsisten dengan konvensi Laravel project.
- [ ] Urutan requirements/steps/FAQs tetap deterministik.
- [ ] Perubahan child record memperbarui `services.updated_at` lewat mekanisme existing.
- [ ] Aturan CTA, emergency, dan publikasi tidak mengalami regresi.
- [ ] Layanan nonaktif/soft-deleted tetap tidak dapat diakses melalui route publik.

### Frontend/admin

- [ ] Form admin bisa membuat, mengubah, dan memuat ulang field baru.
- [ ] Section sasaran disembunyikan saat `audience_info` kosong.
- [ ] Section persiapan disembunyikan saat `preparation_info` kosong.
- [ ] Kondisi persyaratan ditampilkan dengan label yang benar.
- [ ] `applies_to` hanya ditampilkan jika memiliki nilai.
- [ ] Steps dengan `description = null` tetap rapi.
- [ ] `fee_info = null` dan daftar dokumen kosong tidak menghasilkan section kosong.
- [ ] CTA terbuka, tertutup, tanpa form, dan emergency tetap berfungsi.
- [ ] Layout desktop, tablet, mobile, keyboard focus, label aksesibilitas, print mode, dan kontras dasar diuji.

### Quality gate

Periksa `composer.json`, `package.json`, dan workflow CI sebelum memilih perintah. Dokumentasi proyek menyebut command aktual seperti `composer ci:check` dan `composer test`; jalankan command yang benar-benar didefinisikan di repository. Laporkan hasil riil termasuk failure atau test yang tidak bisa dijalankan.

## 12. Urutan Implementasi

1. Audit migration, model, Form Request, controller/action, Resource/props Inertia, admin form, types, factory, seeder, tests, dan working tree.
2. Pastikan tidak ada kolom duplikat atau perubahan lokal yang belum diketahui.
3. Tambahkan migration baru hanya pada `services` dan `service_requirements`.
4. Update model, PHPDoc, dan `#[Fillable]`.
5. Update Form Request dan pesan validasi Bahasa Indonesia.
6. Update admin form beserta helper text, validation error, dan penyimpanan data.
7. Update Resource/controller/Inertia props dan TypeScript types.
8. Update halaman publik detail agar mengonsumsi `audience_info`, `preparation_info`, `requirement_condition`, dan `applies_to`.
9. Update factory serta test yang relevan.
10. Tambahkan coverage migration, request validation, props, dan conditional UI.
11. Jalankan regresi Pelayanan, media, Download, pengaturan situs, dan halaman publik.
12. Jalankan lint, format check, type check, build, dan test menggunakan script aktual.
13. Perbarui dokumentasi/roadmap hanya setelah hasil implementasi diverifikasi.

Jangan menggabungkan semua perubahan tanpa checkpoint. Setelah migration dan model lolos, lanjut ke admin form; setelah kontrak props stabil, lanjut ke UI publik.

## 13. Di Luar Scope

Jangan mengimplementasikan hal-hal berikut pada revisi ini:

- Perubahan apa pun pada `service_categories`.
- Tabel pendaftaran atau penyimpanan data calon penerima sakramen.
- Kalender jadwal terstruktur atau booking.
- Tabel kontak multipihak.
- URL tindakan pada `service_steps`.
- Kategori/featured flag pada FAQ.
- Field hero image tambahan sebelum sistem media diaudit.
- Kolom `is_published`, `cta_state`, `whatsapp_url`, `tel_url`, `thumbnail_url`, atau field turunan lain yang menggandakan data.
- Perubahan modul Download/media yang tidak diperlukan oleh kontrak pelayanan.
- Perubahan isi teologis, persyaratan, prosedur, biaya, jadwal, nomor kontak, atau URL formulir tanpa validasi pihak paroki.
- Refactor modul di luar kebutuhan integrasi.

## 14. Format Laporan Akhir Implementasi

AI coding agent harus melaporkan:

```text
Service Detail Data Revision Report

Status:
- Complete / Partially Complete / Blocked

Scope:
- service_categories unchanged:
- services fields:
- service_requirements fields:
- service_steps unchanged:
- service_faqs unchanged:

Database:
- Migration name:
- Upgrade test:
- Rollback test:

Backend:
- Models:
- Form Requests:
- Resource/Inertia props:
- Validation:

Frontend/admin:
- Admin form:
- Public detail page:
- Responsive/accessibility:

Tests and quality gates:
- Command:
- Result:

Open decisions:
- ...
Content awaiting parish approval:
- ...
```

Jangan menyatakan selesai hanya karena migration atau file UI sudah dibuat. Status selesai harus didasarkan pada schema, perilaku, pengujian, dan quality gate yang benar-benar diverifikasi.
