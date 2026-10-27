# Rencana Implementasi Audit Log

## Website Paroki HSPMTB Putussibau

| Atribut           | Nilai                                                                                 |
| ----------------- | ------------------------------------------------------------------------------------- |
| Dokumen           | Rencana Implementasi Audit Log                                                        |
| Produk            | Website Resmi Gereja Katolik Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau |
| Status            | Final — siap dieksekusi                                                               |
| Prioritas         | P0                                                                                    |
| Dokumen sumber    | `docs/specifications/spec-audit-log.md`                                               |
| Stack             | Laravel 13, PHP 8.3+, Inertia v3, React 19, TypeScript, Tailwind CSS v4, MySQL 8      |
| Bahasa UI         | Bahasa Indonesia                                                                      |
| Penyimpanan waktu | UTC                                                                                   |
| Zona tampilan     | `Asia/Pontianak`                                                                      |
| Cakupan           | Seluruh modul admin, termasuk galeri, komunitas, dan area                             |

---

## 1. Ringkasan keputusan

| Topik                         | Keputusan                                                                                                                                |
| ----------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| Cakupan modul                 | Semua modul admin: berita, agenda, jadwal misa, profil/pelayanan, pengaturan, auth, media, dokumen, master data, komunitas, area, galeri |
| IP address                    | Disimpan                                                                                                                                 |
| User-agent                    | Disimpan, panjang dibatasi                                                                                                               |
| Login berhasil/gagal/logout   | Satu tabel audit; login gagal menyimpan email yang dicoba, tanpa password/token                                                          |
| Reset password Fortify        | Dicatat sebagai `password_changed`, tanpa nilai sensitif                                                                                 |
| Retensi                       | 12 bulan, dokumentasi saja; belum ada auto-purge                                                                                         |
| Snapshot pengaturan           | Nilai biasa disimpan; rich text panjang diringkas                                                                                        |
| Status job media              | Berhasil dan gagal dicatat sebagai event sistem                                                                                          |
| Operasi massal                | Satu event agregat per operasi dengan metadata `count` dan ID terbatas                                                                   |
| Role/permission & status akun | Dilewati karena belum ada UI; didokumentasikan sebagai batas MVP                                                                         |
| Detail event                  | Halaman terpisah `/admin/audit-log/{auditEvent}`                                                                                         |
| Grup sidebar                  | **Administrasi**, terpisah dari Konten dan Akun                                                                                          |

### 1.1 Keputusan teknis

1. Tabel `audit_events`, primary key `bigint`, `subject_id` string.
2. `before`, `after`, dan `metadata` bertipe JSON.
3. `actor_id` nullable FK ke `users` dengan `nullOnDelete()`.
4. `request_id` dibuat otomatis per request (ULID) melalui middleware.
5. Pencatatan memakai `App\Services\AuditLogger` dan `App\Actions\Audit\RecordAuditEventAction`.
6. Tidak memakai global model observer untuk mencatat semua `saved`/`deleted`.
7. Permission `auditlogs.view`; route dilindungi Gate/Policy.
8. Route bernama `audit-logs.index` dan `audit-logs.show`; tidak ada route mutasi.
9. Visibilitas menu memakai shared prop `abilities` yang dihitung dengan `can()`, bukan daftar permission Spatie, karena `super_admin` memperoleh akses melalui `Gate::before`.
10. Kolom `before`/`after` mengikuti nama konseptual spesifikasi; Schema Builder Laravel meng-quote identifier MySQL.

---

## 2. Prinsip implementasi

1. **Backend authoritative** — event dibuat di server, bukan dari React.
2. **Allowlist, bukan log semua field** — hanya field yang disetujui per modul.
3. **No secrets** — password, token, cookie, session, authorization, API key, secret, dan credential tidak pernah disimpan.
4. **Consistent outcome** — event sukses tidak ditulis bila mutasi bisnis gagal.
5. **Append-only melalui aplikasi** — tidak ada endpoint edit/hapus.
6. **Minimal personal data** — IP, user-agent, dan email login gagal disimpan hanya untuk kebutuhan investigasi yang disetujui.
7. **No false assurance** — append-only aplikasi tidak menjamin administrator database/server tidak dapat memanipulasi data.
8. **Idempotency-aware** — retry job tidak menghasilkan duplikasi event tanpa alasan yang jelas.
9. **Readable Indonesian UI** — label aksi, modul, hasil, dan tanggal mudah dipahami pengelola paroki.
10. **Follow current architecture** — tidak menambah package audit pihak ketiga.

---

## 3. Arsitektur solusi

```text
Controller / Action / Listener / Job / Command
        │
        ▼
App\Services\AuditLogger
  - menentukan aktor dan konteks request
  - mengambil snapshot field allowlisted
  - menghitung diff before/after
  - menyusun deskripsi Bahasa Indonesia
        │
        ▼
App\Actions\Audit\RecordAuditEventAction
  - memvalidasi kode module/action/result
  - menerapkan redaksi dan ringkasan
  - membatasi panjang/jumlah field
  - menangani idempotency key
  - menyimpan event
        │
        ▼
audit_events
```

Aturan konsistensi:

- Mutasi database dan event audit berada dalam transaksi yang sama.
- Jika transaksi rollback, event sukses ikut rollback.
- Operasi storage/queue dicatat dengan hasil yang benar; status job dicatat terpisah sebagai event sistem.
- Validasi, otorisasi, atau transaksi yang gagal tidak menghasilkan event sukses.

---

## 4. Skema database

### 4.1 Migration

File baru:

```text
database/migrations/2026_10_27_000000_create_audit_events_table.php
```

```php
Schema::create('audit_events', function (Blueprint $table) {
    $table->id();

    $table->foreignId('actor_id')->nullable()
        ->constrained('users')->nullOnDelete();
    $table->string('actor_type', 20);          // user | system | anonymous
    $table->string('actor_name', 150)->nullable();
    $table->string('actor_email', 255)->nullable();

    $table->string('action', 50);
    $table->string('module', 50);

    $table->string('subject_type', 150)->nullable();
    $table->string('subject_id', 64)->nullable();
    $table->string('subject_label', 255)->nullable();

    $table->string('description', 500);
    $table->string('result', 20);              // success | failure | denied

    $table->json('before')->nullable();
    $table->json('after')->nullable();
    $table->json('metadata')->nullable();

    $table->string('request_id', 64)->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->string('user_agent', 500)->nullable();

    $table->timestamp('occurred_at');
    $table->timestamp('created_at')->useCurrent();

    $table->string('idempotency_key', 120)->nullable()->unique();

    $table->index('occurred_at');
    $table->index(['actor_id', 'occurred_at']);
    $table->index(['module', 'occurred_at']);
    $table->index(['action', 'occurred_at']);
    $table->index(['result', 'occurred_at']);
    $table->index(['subject_type', 'subject_id']);
    $table->index('request_id');
});
```

### 4.2 Catatan skema

- Tidak ada `updated_at`; model memakai `const UPDATED_AT = null`.
- `idempotency_key` hanya dipakai untuk event sistem yang benar-benar dapat terduplikasi karena retry.
- `subject_id` string agar objek dari model berbeda tetap dapat ditelusuri setelah record dihapus.
- `actor_id` menjadi null setelah user dihapus; `actor_name` dan `actor_email` tetap menjadi snapshot historis.
- `ip_address` panjang 45 mendukung IPv4 dan IPv6.
- `user_agent` dibatasi 500 karakter.
- `before`/`after`/`metadata` tidak diambil pada query daftar.

### 4.3 Model

File baru: `app/Models/AuditEvent.php`

- Cast: `before`, `after`, `metadata` → array; `occurred_at` → `immutable_datetime`.
- Scope:
  - `latestFirst`
  - `occurredBetween`
  - `forActor`
  - `forModule`
  - `forAction`
  - `forResult`
  - `search`
- Append-only guard:
  - `updating` melempar `LogicException`.
  - `deleting` melempar `LogicException`.
- Guard ini tidak mencakup mass update; batas tersebut didokumentasikan.

### 4.4 Factory

File baru: `database/factories/AuditEventFactory.php`

- State default: actor user, module `news`, action `updated`, result `success`, `occurred_at` sekarang.
- State `system()` dan `anonymous()`.
- State `forModule()`, `forAction()`, `forResult()`, dan `occurredAt()` untuk test filter.

---

## 5. Konfigurasi dan label

### 5.1 `config/audit.php`

Berisi struktur, bukan teks UI:

```php
return [
    'modules' => [/* daftar kode modul */],
    'actions' => [/* daftar kode aksi */],
    'results' => ['success', 'failure', 'denied'],

    'fields' => [
        'news' => ['title', 'slug', 'status', 'published_at', /* ... */],
        'agenda' => ['title', 'slug', 'start_at', 'end_at', /* ... */],
        // ...
    ],

    'metadata' => [
        'settings' => ['setting_keys_changed', 'summarized_fields', 'truncated_fields'],
        'media' => ['media_id', 'file_mime_type', 'file_size', 'job_name', 'error_class', 'variants'],
        'documents' => ['file_replaced', 'file_mime_type', 'file_size'],
        // ...
    ],

    'summarize' => [
        'settings' => ['privacy_policy_content', 'terms_and_conditions_content'],
        // ...
    ],

    'redact_keys' => [
        'password',
        'password_confirmation',
        'token',
        'secret',
        'api_key',
        'authorization',
        'cookie',
        'session',
        'credential',
    ],

    'search_fields' => ['description', 'subject_label', 'actor_name'],

    'snapshot' => [
        'max_value_length' => 1000,
        'max_fields' => 30,
    ],

    'pagination' => [
        'default' => 10,
        'allowed' => [10, 15, 25, 50],
    ],

    'max_date_range_days' => 366,
    'retention_months' => 12,
    'user_agent_max_length' => 500,
];
```

### 5.2 `lang/id/audit.php`

Berisi:

- `modules.*`
- `actions.*`
- `results.*`
- `descriptions.*`
- `actors.system`
- `actors.anonymous`

Contoh label aksi:

| Kode                         | Label UI                  |
| ---------------------------- | ------------------------- |
| `created`                    | Dibuat                    |
| `updated`                    | Diperbarui                |
| `published`                  | Diterbitkan               |
| `unpublished`                | Publikasi dibatalkan      |
| `scheduled`                  | Dijadwalkan               |
| `archived`                   | Diarsipkan                |
| `cancelled`                  | Dibatalkan                |
| `postponed`                  | Ditunda                   |
| `deleted`                    | Dihapus                   |
| `restored`                   | Dipulihkan                |
| `activated`                  | Diaktifkan                |
| `deactivated`                | Dinonaktifkan             |
| `reordered`                  | Diurutkan ulang           |
| `attached`                   | Ditambahkan               |
| `duplicated`                 | Digandakan                |
| `upload_accepted`            | Unggahan diterima         |
| `media_processing_completed` | Pemrosesan gambar selesai |
| `media_processing_failed`    | Pemrosesan gambar gagal   |
| `login_succeeded`            | Login berhasil            |
| `login_failed`               | Login gagal               |
| `logout`                     | Logout                    |
| `profile_updated`            | Profil diperbarui         |
| `email_changed`              | Email diubah              |
| `password_changed`           | Kata sandi diubah         |

Test `AuditConfigTest` memastikan setiap kode di config memiliki label dan tidak ada key sensitif di allowlist.

### 5.3 Kode modul

```text
news
agenda
mass_schedules
special_masses
schedule_notices
parish_profile
parish_histories
pastors
pastor_assignments
parish_boards
parish_board_members
parish_service_area
services
service_requirements
service_steps
service_faqs
settings
users
media
documents
document_categories
post_categories
event_categories
service_categories
community_categories
communities
areas
locations
galleries
gallery_photos
```

### 5.4 Kode aksi

```text
created
updated
published
unpublished
scheduled
archived
cancelled
postponed
deleted
restored
activated
deactivated
reordered
attached
duplicated
upload_accepted
media_processing_completed
media_processing_failed
login_succeeded
login_failed
logout
profile_updated
email_changed
password_changed
```

`restored` didefinisikan untuk masa depan, tetapi belum menghasilkan event karena belum ada route restore.

---

## 6. Service dan Action pencatatan

### 6.1 `App\Services\AuditLogger`

File baru: `app/Services/AuditLogger.php`

API internal:

```php
public function snapshot(Model $subject, string $module): array;

public function created(string $module, Model $subject, ?string $label = null): void;

public function updated(
    string $module,
    Model $subject,
    array $before,
    ?string $action = null,
    ?string $label = null,
): void;

public function deleted(
    string $module,
    Model $subject,
    array $before,
    ?string $label = null,
): void;

public function settingsUpdated(
    array $before,
    array $after,
    array $changedKeys,
): void;

public function passwordChanged(User $user): void;

public function loginSucceeded(User $user): void;

public function loginFailed(?string $email): void;

public function logout(?User $user): void;

public function system(
    string $module,
    string $action,
    string $result,
    string $description,
    array $metadata = [],
    ?string $subjectType = null,
    ?string $subjectId = null,
    ?string $subjectLabel = null,
): void;
```

Tanggung jawab:

- Menentukan aktor dari `Auth::user()`.
- Menentukan `actor_type` (`user`, `system`, `anonymous`).
- Mengambil `request_id`, IP, dan user-agent dari request aktif; null untuk console/queue.
- Mengambil snapshot hanya dari field allowlist.
- Menghitung field yang berubah saja untuk `updated`.
- Menyusun `description` dari label Bahasa Indonesia.
- Menyerahkan payload terstruktur ke `RecordAuditEventAction`.
- Tidak menerima payload audit mentah dari frontend.

Subject label diambil dari atribut aman secara berurutan: `title`, `name`, `original_name`, lalu `null`.

### 6.2 `App\Actions\Audit\RecordAuditEventAction`

File baru: `app/Actions/Audit/RecordAuditEventAction.php`

Tanggung jawab:

1. Memvalidasi kode module/action/result terhadap config; kode tak dikenal adalah programmer error.
2. Menerapkan allowlist field dan metadata.
3. Membuang key sensitif secara rekursif pada level field.
4. Meringkas field yang masuk `summarize` menjadi penanda seperti `[ringkas: 12.345 karakter]`.
5. Memotong nilai string lain sesuai `snapshot.max_value_length` dan menandai `truncated_fields`.
6. Membatasi jumlah field sesuai `snapshot.max_fields` dan menandai `snapshot_truncated`.
7. Menangani `idempotency_key` dengan `firstOrCreate`; duplikat karena race diabaikan.
8. Menyimpan event melalui Eloquent.

### 6.3 Middleware `AssignRequestId`

File baru: `app/Http/Middleware/AssignRequestId.php`

- Ditambahkan ke grup `web` di `bootstrap/app.php`.
- Memakai `X-Request-ID` hanya jika formatnya aman (`[A-Za-z0-9._-]{8,64}`).
- Jika tidak valid atau tidak ada, membuat ULID.
- Menyimpan ke `request()->attributes`.
- Menambahkan header response `X-Request-ID`.

### 6.4 Redaksi

- Key sensitif dibuang dari `before`, `after`, dan `metadata`.
- Allowlist tetap menjadi lapisan utama; redaksi generik adalah lapisan tambahan.
- Tidak menyimpan:
  - password atau konfirmasi password;
  - token reset atau token autentikasi;
  - session ID, cookie, atau header Authorization;
  - API key, app secret, credential database;
  - secret SMTP;
  - payload request mentah;
  - isi file atau binary;
  - data pribadi yang tidak relevan.
- HTML tidak pernah dirender sebagai HTML di UI; React melakukan escaping.

---

## 7. Katalog event dan allowlist

### 7.1 Berita

Modul: `news`  
Objek: `App\Models\Post`  
File: `app/Actions/News/SavePostAction.php`, `app/Http/Controllers/News/PostController.php`, `app/Console/Commands/PublishScheduledPosts.php`

| Operasi                    | Aksi                          | Field audit                                                                                                                                                  |
| -------------------------- | ----------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Create                     | `created`                     | title, slug, excerpt, status, published_at, post_category_id, is_pinned, author_name, media_id, image_alt, scripture_reference, meta_title, meta_description |
| Update biasa               | `updated`                     | field yang berubah dari daftar di atas                                                                                                                       |
| Draft → published          | `published`                   | status, published_at, field lain yang berubah                                                                                                                |
| Published → draft          | `unpublished`                 | status                                                                                                                                                       |
| Draft → scheduled          | `scheduled`                   | status, published_at                                                                                                                                         |
| → archived                 | `archived`                    | status                                                                                                                                                       |
| Delete                     | `deleted`                     | snapshot sebelum soft delete: title, slug, status, published_at                                                                                              |
| Publish terjadwal otomatis | `published` (sistem, agregat) | metadata `count`, `post_ids` maksimal 100                                                                                                                    |

Aturan:

- `content` tidak masuk snapshot.
- Jangan membuat dua event (`updated` + `published`) untuk satu simpanan; pilih aksi paling spesifik.
- `PostController::destroy` mencatat `deleted` dalam transaksi yang sama dengan `delete()`.
- `PublishScheduledPosts` mencatat satu event sistem agregat, bukan satu event per artikel.

### 7.2 Agenda

Modul: `agenda`  
Objek: `App\Models\Event`  
File: `app/Actions/Agenda/SaveEventAction.php`, `app/Actions/Agenda/DuplicateEventAction.php`, `app/Http/Controllers/Agenda/EventController.php`

| Operasi           | Aksi                        | Field audit                                                                                                                          |
| ----------------- | --------------------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| Create            | `created`                   | title, slug, start_at, end_at, is_all_day, event_category_id, location_id, location_name, status, is_published, media_id, poster_alt |
| Update            | `updated` / status-specific | field berubah                                                                                                                        |
| Publish/unpublish | `published` / `unpublished` | is_published                                                                                                                         |
| Status cancelled  | `cancelled`                 | status                                                                                                                               |
| Status postponed  | `postponed`                 | status                                                                                                                               |
| Duplicate         | `created`                   | field objek baru + metadata `duplicated_from`                                                                                        |
| Delete            | `deleted`                   | snapshot title, slug, start_at, status, is_published                                                                                 |

Aturan:

- `description` tidak masuk snapshot.
- Duplikasi menghasilkan satu event `created` untuk objek baru, bukan event pada objek sumber.

### 7.3 Jadwal Misa

Modul: `mass_schedules`, `special_masses`, `schedule_notices`  
File:

- `app/Http/Controllers/MassSchedule/MassScheduleController.php`
- `app/Http/Controllers/MassSchedule/SpecialMassController.php`
- `app/Http/Controllers/MassSchedule/ScheduleNoticeController.php`
- `app/Actions/MassSchedule/DuplicateMassScheduleAction.php`

| Entitas       | Field audit                                                                                             |
| ------------- | ------------------------------------------------------------------------------------------------------- |
| Misa rutin    | name, day_of_week, start_time, location_id, notes (ringkas), is_active                                  |
| Misa khusus   | title, start_at, end_at, location_id, notes (ringkas), is_active                                        |
| Pemberitahuan | title, message (ringkas), location_id, mass_schedule_id, special_mass_id, starts_at, ends_at, is_active |

Aksi: `created`, `updated`, `activated`, `deactivated`, `duplicated`, `deleted`.

### 7.4 Profil paroki dan pelayanan

Modul: `parish_profile`, `parish_histories`, `pastors`, `pastor_assignments`, `parish_boards`, `parish_board_members`, `parish_service_area`, `services`, `service_requirements`, `service_steps`, `service_faqs`

File utama:

- `app/Http/Controllers/Profil/ParishProfileController.php`
- `app/Http/Controllers/Profil/ParishHistoryItemController.php`
- `app/Http/Controllers/Profil/PastorController.php`
- `app/Http/Controllers/Profil/PastorAssignmentController.php`
- `app/Http/Controllers/Profil/ParishBoardPeriodController.php`
- `app/Http/Controllers/Profil/ParishBoardMemberController.php`
- `app/Http/Controllers/Profil/ParishServiceAreaController.php`
- `app/Http/Controllers/Pelayanan/ServiceController.php`
- `app/Http/Controllers/Pelayanan/ServiceRequirementController.php`
- `app/Http/Controllers/Pelayanan/ServiceStepController.php`
- `app/Http/Controllers/Pelayanan/ServiceFaqController.php`
- `app/Actions/Profil/SetCurrentPastorAction.php`
- `app/Actions/Profil/ActivateBoardPeriodAction.php`
- `app/Actions/Pelayanan/SaveServiceAction.php`
- `app/Actions/Pelayanan/ReorderServiceItemsAction.php`

Allowlist ringkas:

| Modul                  | Field                                                                                                                                                                                                                                                                                            |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `parish_profile`       | description (ringkas), established_year, cover_media_id, cover_alt, video_url                                                                                                                                                                                                                    |
| `parish_histories`     | title, year, date, description (ringkas), media_id, image_alt, sort_order, is_published                                                                                                                                                                                                          |
| `pastors`              | name, title, photo_media_id, photo_alt, bio (ringkas), sort_order, is_published                                                                                                                                                                                                                  |
| `pastor_assignments`   | pastor_id, position, start_year, end_year, is_current, description (ringkas)                                                                                                                                                                                                                     |
| `parish_boards`        | name, start_year, end_year, description (ringkas), is_active, is_published, sort_order                                                                                                                                                                                                           |
| `parish_board_members` | parish_board_period_id, name, position, photo_media_id, photo_alt, description (ringkas), sort_order, is_published                                                                                                                                                                               |
| `parish_service_area`  | content (ringkas), map_media_id, map_url, is_published                                                                                                                                                                                                                                           |
| `services`             | title, slug, service_category_id, thumbnail_media_id, thumbnail_alt, summary, is_form_open, is_emergency, is_active, sort_order, contact_name, contact_phone, form_url, form_button_label, form_closed_message; introduction, audience_info, preparation_info, schedule_info, fee_info diringkas |
| `service_requirements` | service_id, text, note, requirement_condition, applies_to, sort_order                                                                                                                                                                                                                            |
| `service_steps`        | service_id, title, description (ringkas), sort_order                                                                                                                                                                                                                                             |
| `service_faqs`         | service_id, question, answer (ringkas), sort_order                                                                                                                                                                                                                                               |

Aksi khusus:

- Aktivasi pastor/periode: satu event `activated` dengan metadata objek terdampak.
- Reorder persyaratan/prosedur/FAQ: satu event `reordered` dengan metadata `count` dan `ids`, bukan satu event per item.
- Delete: snapshot sebelum delete.

### 7.5 Pengaturan situs

Modul: `settings`  
File: `app/Http/Controllers/Settings/SettingsController.php`, `app/Services/SiteSettingsService.php`

- Baca nilai sebelum dari `SiteSettingsService` sebelum `setMany()`.
- Bungkus `setMany()` dan audit dalam transaksi luar.
- Bandingkan hanya key yang benar-benar berubah.
- Satu event `settings_updated` per simpanan.
- Metadata: `setting_keys_changed`, `summarized_fields`, `truncated_fields`.
- `before`/`after` hanya untuk key yang berubah.
- Rich text (`privacy_policy_content`, `terms_and_conditions_content`) disimpan sebagai penanda ringkas + panjang, bukan HTML penuh.
- Tidak ada secret saat ini di `site_settings`; redaksi generik tetap diterapkan sebagai lapisan pertahanan.

### 7.6 Akun dan autentikasi

Modul: `users`, `auth`  
File baru:

- `app/Listeners/RecordLoginSucceeded.php`
- `app/Listeners/RecordLoginFailed.php`
- `app/Listeners/RecordLogout.php`

Event Laravel:

| Event                           | Aksi              | Aktor     | Catatan                                                               |
| ------------------------------- | ----------------- | --------- | --------------------------------------------------------------------- |
| `Illuminate\Auth\Events\Login`  | `login_succeeded` | user      | actor snapshot                                                        |
| `Illuminate\Auth\Events\Failed` | `login_failed`    | anonymous | `actor_email` dari kredensial; tanpa password/token; `result=failure` |
| `Illuminate\Auth\Events\Logout` | `logout`          | user      | actor snapshot                                                        |

File yang diubah:

- `app/Http/Controllers/Settings/ProfileController.php`
  - `profile_updated` atau `email_changed` bila email berubah.
  - Field: `name`, `email`.
- `app/Http/Controllers/Settings/SecurityController.php`
  - `password_changed`, tanpa `before`/`after`.
- `app/Actions/Fortify/ResetUserPassword.php`
  - `password_changed` untuk reset password, tanpa nilai sensitif.

Aturan:

- Rate limit login yang sudah ada (5/menit) tetap menjadi kontrol utama volume event `login_failed`.
- Listener diletakkan di `app/Listeners`; verifikasi dengan feature test bahwa listener terpanggil. Bila auto-discovery tidak aktif, daftarkan eksplisit melalui `withEvents()` di `bootstrap/app.php`.
- Role/permission dan status akun tidak dicatat karena belum ada jalur UI di aplikasi; batas ini didokumentasikan.

### 7.7 Media

Modul: `media`  
File: `app/Http/Controllers/Media/MediaController.php`, `app/Jobs/GenerateImageVariants.php`

| Operasi         | Aksi                         | Aktor  | Metadata                            |
| --------------- | ---------------------------- | ------ | ----------------------------------- |
| Upload diterima | `upload_accepted`            | user   | original_name, mime_type, file_size |
| Hapus media     | `deleted`                    | user   | snapshot metadata sebelum dihapus   |
| Job selesai     | `media_processing_completed` | system | media_id, variants                  |
| Job gagal       | `media_processing_failed`    | system | media_id, job_name, error_class     |

Aturan:

- Jangan menyimpan `path`, `disk`, atau isi file.
- Jangan menyatakan pemrosesan selesai saat job baru di-dispatch.
- `GenerateImageVariants::failed()` menggunakan `app(AuditLogger::class)` karena tidak ada method injection.
- Event sukses/gagal job tidak memakai idempotency key: Laravel memanggil `handle()` sekali per attempt sukses dan `failed()` sekali per siklus job. Batas ini didokumentasikan.
- `MediaController::store` membuat row dan audit di dalam transaksi, lalu dispatch job setelah transaksi selesai.
- `MediaController::destroy` menghapus row dan mencatat audit di dalam transaksi, lalu menghapus file. Jika penghapusan file gagal, catat event sistem kegagalan storage dan rethrow.

### 7.8 Dokumen

Modul: `documents`  
File: `app/Actions/Document/SaveDocumentAction.php`, `app/Actions/Document/DeleteDocumentAction.php`

| Operasi | Aksi      | Field/metadata                                                                                                                                    |
| ------- | --------- | ------------------------------------------------------------------------------------------------------------------------------------------------- |
| Create  | `created` | title, document_category_id, version_label, document_date, expires_at, status, is_featured, published_at, original_filename, mime_type, file_size |
| Update  | `updated` | field berubah + metadata `file_replaced`                                                                                                          |
| Delete  | `deleted` | snapshot sebelum delete                                                                                                                           |

Aturan:

- Jangan menyimpan `file_path`/`file_disk`.
- Audit berada di dalam transaksi yang sama dengan penyimpanan row.
- Penghapusan file fisik setelah transaksi; jika gagal, catat event sistem kegagalan storage dan rethrow agar perilaku lama tidak berubah.

### 7.9 Master data

Modul: `post_categories`, `event_categories`, `service_categories`, `community_categories`, `document_categories`, `locations`, `areas`

File:

- `app/Http/Controllers/MasterData/PostCategoryController.php`
- `app/Http/Controllers/MasterData/EventCategoryController.php`
- `app/Http/Controllers/MasterData/ServiceCategoryController.php`
- `app/Http/Controllers/MasterData/CommunityCategoryController.php`
- `app/Http/Controllers/MasterData/DocumentCategoryController.php`
- `app/Http/Controllers/MasterData/LocationController.php`
- `app/Http/Controllers/Area/AreaController.php`

Field allowlist:

| Modul                  | Field                                                                                                                                                       |
| ---------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `post_categories`      | name, slug, sort_order                                                                                                                                      |
| `event_categories`     | name, slug, color                                                                                                                                           |
| `service_categories`   | name, slug, description (ringkas), sort_order, is_active                                                                                                    |
| `community_categories` | name, slug, description (ringkas), sort_order, is_active                                                                                                    |
| `document_categories`  | name, slug, description (ringkas), sort_order, is_active                                                                                                    |
| `locations`            | name, slug, type, address, latitude, longitude, maps_url, notes (ringkas), is_active                                                                        |
| `areas`                | parent_id, name, slug, type, description (ringkas), address, maps_url, latitude, longitude, leader_name, contact_phone, show_contact, sort_order, is_active |

Aksi: `created`, `updated`, `deleted`, `activated`, `deactivated`, `reordered`.

### 7.10 Komunitas

Modul: `communities`  
File: `app/Http/Controllers/Komunitas/CommunityController.php`, `app/Actions/Komunitas/SaveCommunityAction.php`, `app/Actions/Komunitas/DeleteCommunityAction.php`

Field allowlist:

```text
community_category_id, name, slug, logo_media_id, logo_alt,
thumbnail_media_id, thumbnail_alt, meeting_schedule, meeting_location,
contact_name, contact_phone, show_contact, facebook, instagram,
tiktok, youtube, website, status, is_published, published_at
```

Field panjang yang diringkas: `description`, `vision`, `mission`, `eligibility`, `joining_info`.

Aksi: `created`, `updated`, `published`, `unpublished`, `deleted`.

### 7.11 Galeri

Modul: `galleries`, `gallery_photos`  
File: `app/Http/Controllers/Galeri/GalleryController.php`, `app/Http/Controllers/Galeri/GalleryPhotoController.php`, `app/Actions/Galeri/SaveGalleryAlbumAction.php`, `app/Actions/Galeri/AttachGalleryPhotosAction.php`, `app/Actions/Galeri/ReorderGalleryPhotosAction.php`

Field allowlist:

| Entitas | Field                                                                                                |
| ------- | ---------------------------------------------------------------------------------------------------- |
| Album   | title, slug, event_date, location, cover_photo_id, is_published, published_at; description diringkas |
| Foto    | gallery_album_id, media_id, caption, alt_text, sort_order                                            |

Aksi:

- Tambah foto massal: satu event `attached` pada album dengan metadata `photo_ids`/`count`.
- Reorder foto: satu event `reordered`.
- Hapus foto: `deleted` per foto.
- Hapus album: `deleted` dengan snapshot album.

---

## 8. Pola integrasi transaksi

### 8.1 Create

```php
DB::transaction(function () use ($request, $audit): void {
    $model = Model::query()->create($request->payload());
    $audit->created('module', $model);
});
```

### 8.2 Update

```php
DB::transaction(function () use ($request, $model, $audit): void {
    $before = $audit->snapshot($model, 'module');

    $model->update($request->payload());

    $audit->updated('module', $model, $before);
});
```

### 8.3 Delete

```php
DB::transaction(function () use ($model, $audit): void {
    $before = $audit->snapshot($model, 'module');

    $model->delete();

    $audit->deleted('module', $model, $before);
});
```

### 8.4 Action yang sudah memakai transaksi

- Tambahkan audit di dalam `DB::transaction` yang sudah ada.
- Jangan membuka transaksi kedua di controller.
- Untuk `SavePostAction`, `SaveEventAction`, dan `SaveDocumentAction`, tentukan `created` vs `updated` dari `$model->exists` sebelum `save()`.
- Untuk perubahan status, tentukan aksi paling spesifik sebelum menulis event.

### 8.5 Operasi massal

- Reorder: satu event `reordered`, metadata `count` dan `ids` terbatas.
- Aktivasi periode/pastor: satu event `activated`, metadata objek terdampak.
- Publish terjadwal: satu event sistem `published`, metadata `count` dan `post_ids` terbatas.
- Jangan membuat event per record untuk operasi massal.

### 8.6 Storage dan queue

- Operasi storage tidak selalu bisa ikut transaksi database.
- Catat event bisnis yang benar-benar terjadi.
- Status job dicatat sebagai event sistem terpisah.
- Jangan menulis “selesai diproses” saat job baru di-dispatch.
- Kegagalan storage setelah transaksi dicatat sebagai event sistem `failure` dan exception tetap dilempar.

---

## 9. Read API, route, dan otorisasi

### 9.1 Permission

File diubah: `database/seeders/PermissionSeeder.php`

Tambahkan:

```php
'auditlogs.view',
```

Update `docs/RBAC.md`.

Tidak ada role baru. Super Admin tetap memperoleh akses melalui `Gate::before`.

### 9.2 Policy

File baru: `app/Policies/AuditEventPolicy.php`

```php
public function viewAny(User $user): bool
{
    return $user->can('auditlogs.view');
}

public function view(User $user, AuditEvent $event): bool
{
    return $user->can('auditlogs.view');
}
```

### 9.3 Route

File diubah: `routes/web.php`

Di dalam group admin:

```php
Route::get('/audit-log', [AuditLogController::class, 'index'])
    ->name('audit-logs.index');

Route::get('/audit-log/{auditEvent}', [AuditLogController::class, 'show'])
    ->name('audit-logs.show');
```

Tidak ada POST/PUT/PATCH/DELETE. Jalankan `php artisan wayfinder:generate --with-form` setelah route ditambahkan.

### 9.4 Form Request

File baru: `app/Http/Requests/Audit/AuditLogIndexRequest.php`

- `authorize()` → `$this->user()->can('auditlogs.view')`.
- Filter:
  - `search` — string, maksimal 100 karakter.
  - `from`, `to` — `date_format:Y-m-d`.
  - `actor` — `user:{id}`, `system`, atau `anonymous`.
  - `module`, `action`, `result` — string, maksimal 50 karakter.
- `after()`:
  - `from <= to`.
  - Rentang maksimal 366 hari.
- Nilai modul/aksi/hasil yang tidak dikenal diabaikan, konsisten dengan list admin lain.
- `per_page` dibatasi 10/15/25/50; default 10.
- Helper `filters()`, `fromUtc()`, `toUtc()`, dan `perPage()`.

### 9.5 Controller

File baru: `app/Http/Controllers/Audit/AuditLogController.php`

`index`:

- `select` hanya kolom daftar:
  - `id`, `actor_id`, `actor_type`, `actor_name`, `action`, `module`, `subject_id`, `subject_label`, `description`, `result`, `occurred_at`.
- Terapkan filter:
  - rentang `occurred_at`;
  - aktor;
  - modul;
  - aksi;
  - hasil;
  - pencarian pada `description`, `subject_label`, `actor_name`.
- Urutkan `occurred_at DESC, id DESC`.
- `paginate($perPage)->withQueryString()`.
- Kirim props: `events`, `filters`, `options`.

`show`:

- `Gate::authorize('view', $auditEvent)`.
- Kirim detail lengkap yang sudah aman.
- Tidak ada query ke objek sumber yang sudah dihapus.

Opsi filter:

- Modul/aksi/hasil dari config + label.
- Aktor dari `DISTINCT actor_type, actor_id, actor_name` pada `audit_events`, dibatasi 200 baris.
- Tambahkan opsi “Sistem” dan “Anonim” bila ada event terkait.

### 9.6 Resource

File baru:

- `app/Http/Resources/Audit/AuditEventListResource.php`
- `app/Http/Resources/Audit/AuditEventDetailResource.php`

List:

```ts
{
  id: number;
  occurred_at: string;
  actor: { type: 'user' | 'system' | 'anonymous'; name: string | null; label: string };
  action: string;
  action_label: string;
  module: string;
  module_label: string;
  subject_label: string | null;
  description: string;
  result: string;
  result_label: string;
}
```

Detail menambah:

```ts
{
  actor: { ...; email: string | null };
  subject_type: string | null;
  subject_id: string | null;
  before: Record<string, unknown> | null;
  after: Record<string, unknown> | null;
  metadata: Record<string, unknown> | null;
  request_id: string | null;
  ip_address: string | null;
  user_agent: string | null;
}
```

### 9.7 Props Inertia

Halaman daftar:

```ts
type AuditLogIndexProps = {
    events: Paginated<AuditLogRow>;
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
        modules: { value: string; label: string }[];
        actions: { value: string; label: string }[];
        results: { value: string; label: string }[];
        actors: { value: string; label: string }[];
    };
};
```

---

## 10. UI dan navigasi

### 10.1 Sidebar

File diubah:

- `resources/js/layouts/app/app-sidebar-nav.ts`
- `resources/js/layouts/app/components/app-sidebar.tsx`
- `resources/js/layouts/app/components/app-sidebar-entry.tsx`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `resources/js/types/auth.ts`
- `resources/js/types/index.ts`

Perubahan:

1. Tambah `Entry.ability?: string`.
2. Tambah `ADMINISTRATION_ENTRIES`:

```ts
export const ADMINISTRATION_ENTRIES: Entry[] = [
    {
        title: 'Audit Log',
        href: auditLogsIndex(),
        icon: History,
        ability: 'auditlogs.view',
    },
];
```

1. Tambah grup sidebar:

```tsx
<SidebarGroup label="Administrasi" entries={ADMINISTRATION_ENTRIES} />
```

1. Tambah shared prop:

```php
'abilities' => fn (): array => [
    'auditlogs.view' => (bool) $request->user()?->can('auditlogs.view'),
],
```

1. Filter entri dan child berdasarkan `abilities` melalui fungsi murni `visibleEntries(entries, abilities)`.
2. Tambah `abilities: Record<string, boolean>` pada tipe `SharedProps`.
3. Backend tetap satu-satunya kontrol keamanan; menyembunyikan menu bukan kontrol keamanan.

### 10.2 Halaman daftar

File baru: `resources/js/features/admin/audit-log/pages/index.tsx`

- Judul: **Audit Log**
- Deskripsi: “Riwayat perubahan dan aktivitas penting pada panel pengelola paroki.”
- Kolom:
  - Waktu
  - Pengguna
  - Aktivitas
  - Modul
  - Objek
  - Hasil
  - Detail
- Waktu memakai `formatWibDateTime(occurred_at, displayTimezone)`.
- Pengguna memakai `actor.label`; “Sistem” untuk aktor system.
- Hasil memakai badge dengan teks, bukan hanya warna.
- Detail berupa link ke route `audit-logs.show`.
- Filter:
  - pencarian teks;
  - rentang tanggal `from`/`to`;
  - aktor;
  - modul;
  - aksi;
  - hasil.
- Filter disimpan di query string dan pagination.
- Empty state membedakan “belum ada aktivitas” dan “tidak cocok dengan filter”.
- Tidak menampilkan JSON mentah atau snapshot di daftar.
- Responsif mengikuti `DataTable`/`Table` yang sudah ada.

### 10.3 Halaman detail

File baru: `resources/js/features/admin/audit-log/pages/show.tsx`

- Header: waktu, aktor, aksi, modul, objek, hasil.
- Diff before/after:
  - union key;
  - hanya field berubah;
  - nilai lama/baru;
  - `null`/tidak ada → “Kosong”.
- JSON/array dirender dalam `<pre>` dengan React escaping.
- Tidak memakai `dangerouslySetInnerHTML`.
- Metadata teknis (request ID, IP, user-agent) di section terpisah.
- Metadata event di section yang dapat diperluas (`<details>`).
- Nilai panjang memakai `break-words` agar tidak overflow.

### 10.4 Komponen

File baru:

- `resources/js/features/admin/audit-log/components/audit-result-badge.tsx`
- `resources/js/features/admin/audit-log/components/audit-diff.tsx`
- `resources/js/features/admin/audit-log/types.ts`

Tidak membuat map label duplikat di React; label berasal dari server.

---

## 11. Keamanan, privasi, integritas, dan performa

### 11.1 Redaksi wajib

Tidak pernah disimpan:

- password atau konfirmasi password;
- token reset atau token autentikasi;
- session ID, cookie, atau header Authorization;
- API key, app secret, credential database;
- secret SMTP;
- payload request mentah;
- isi file atau binary;
- data pribadi yang tidak relevan.

### 11.2 IP address dan user-agent

- Disimpan untuk investigasi.
- Hanya dikirim di halaman detail, bukan daftar.
- Hanya dapat diakses pengguna dengan `auditlogs.view`.
- Diretensi mengikuti kebijakan 12 bulan.
- Tidak dijadikan bukti identitas tunggal.

### 11.3 Append-only

- Tidak ada UI edit/hapus.
- Tidak ada endpoint mutasi.
- Model menolak update/delete instance.
- Hanya service pencatatan yang digunakan untuk insert.
- Batas: administrator database/server tetap dapat memanipulasi data. Backup terpisah dan kontrol akses infrastruktur berada di luar MVP.

### 11.4 Retensi

- Kebijakan awal: 12 bulan untuk aktivitas administratif.
- Belum ada job pembersihan otomatis pada MVP.
- Tidak ada tombol “hapus semua log”.
- Prosedur peninjauan dan pengarsipan didokumentasikan di `docs/audit-log.md`.

### 11.5 Performa

- Pagination server-side.
- Page size maksimal 50.
- Daftar tidak mengambil kolom JSON besar.
- Index sesuai query aktual.
- Filter tanggal dibatasi maksimal 366 hari.
- Opsi aktor dibatasi 200 baris.
- Tidak ada eager-load objek sumber.
- Uji `EXPLAIN` pada dataset minimal 10.000 event sebelum produksi.

---

## 12. Rencana test

### 12.1 Feature tests

Direktori: `tests/Feature/Audit/`

#### `AuditLogAuthorizationTest`

- guest ditolak/redirect;
- user dengan `admin.access` tanpa `auditlogs.view` → 403;
- Super Admin bisa membuka daftar/detail;
- detail ID tidak ada → 404;
- tidak ada route mutasi (POST/PUT/PATCH/DELETE → 405);
- shared prop `abilities.auditlogs.view` benar.

#### `AuditEventRecordingTest`

- create/update/delete menghasilkan satu event;
- aktor, subject, module, action, result benar;
- `before`/`after` hanya field allowlisted;
- update hanya field berubah;
- status publish/unpublish/scheduled/archived;
- `occurred_at` UTC;
- rollback transaksi tidak menyisakan event sukses;
- event sistem berbeda dari event pengguna;
- actor snapshot tetap ada setelah user dihapus.

#### `AuditSensitiveDataTest`

- password/token/secret dibuang;
- metadata arbitrer tidak diterima;
- nilai HTML tetap string aman;
- event password tidak menyimpan nilai.

#### `AuditLogFilteringTest`

- filter tanggal, aktor, modul, action, result;
- kombinasi filter;
- parameter invalid diabaikan;
- rentang tanggal > 366 hari ditolak/diabaikan sesuai kontrak;
- pagination server-side dan page size cap;
- query string bertahan di link pagination;
- search hanya pada field yang disepakati.

#### `AuditLogDeletionSnapshotTest`

- event tetap terbaca setelah post/event/document dihapus;
- `subject_label` dan snapshot tetap tampil;
- actor snapshot tetap ada setelah akun tidak aktif/dihapus.

#### `AuditLogIntegrationTest`

- berita, agenda, jadwal misa, pengaturan, dokumen, media, profil/pelayanan, master data, komunitas, area, galeri;
- tidak ada duplikasi event untuk satu operasi;
- kegagalan storage/queue tidak menghasilkan deskripsi sukses yang salah.

#### `AuditAuthenticationTest`

- login berhasil, gagal, logout;
- email login gagal tersimpan, password tidak;
- rate limit tidak membanjiri tabel.

#### `AuditSettingsTest`

- hanya key berubah;
- satu event per simpanan;
- rich text diringkas;
- nilai biasa tersimpan.

#### `AuditMediaJobTest`

- upload diterima;
- job sukses → event sistem `media_processing_completed`;
- job gagal → event sistem `media_processing_failed`;
- tidak ada event sukses saat job baru dispatch.

#### `AuditScheduledPublishingTest`

- command publish terjadwal menghasilkan satu event sistem agregat dengan count/ID.

#### `AuditSchemaTest`

- tabel, kolom, index, FK `nullOnDelete`, unique idempotency key;
- migration rollback pada database test/scratch MySQL 8.

#### `AuditConfigTest`

- setiap kode module/action/result punya label;
- allowlist tidak memuat key sensitif.

### 12.2 Unit tests

Direktori: `tests/Unit/Audit/`

- normalisasi module/action/result;
- redaksi key sensitif;
- ringkasan nilai panjang;
- pembentukan diff;
- pemetaan label;
- `visibleEntries()` sidebar.

### 12.3 Vitest

Direktori: `tests/js/`

- `visibleEntries` menyembunyikan entri tanpa ability;
- `audit-diff` menampilkan “Kosong” untuk null dan tidak merender HTML;
- format waktu memakai timezone tampilan.

### 12.4 E2E

File opsional: `tests/e2e/audit-log.spec.ts`

Alur:

1. login Super Admin;
2. buka Audit Log;
3. filter modul/aksi;
4. buka detail;
5. pastikan event tampil;
6. user tanpa akses tidak dapat membuka route.

E2E bukan pengganti feature test. Jalankan hanya bila lingkungan Playwright tersedia.

### 12.5 Pemetaan acceptance criteria

| AC         | Bukti utama                                                          |
| ---------- | -------------------------------------------------------------------- |
| AUD-AC-001 | `AuditEventRecordingTest` — create menghasilkan satu event `created` |
| AUD-AC-002 | `AuditEventRecordingTest` — update hanya field berubah               |
| AUD-AC-003 | `AuditEventRecordingTest` — status before/after                      |
| AUD-AC-004 | `AuditEventRecordingTest` / `AuditLogDeletionSnapshotTest`           |
| AUD-AC-005 | `AuditLogDeletionSnapshotTest` — actor snapshot                      |
| AUD-AC-006 | `AuditEventRecordingTest` — system vs user                           |
| AUD-AC-007 | `AuditEventRecordingTest` — rollback/validasi                        |
| AUD-AC-008 | `AuditMediaJobTest` — retry job                                      |
| AUD-AC-009 | `AuditSettingsTest` — key berubah                                    |
| AUD-AC-010 | `AuditSettingsTest` / `AuditSensitiveDataTest`                       |
| AUD-AC-011 | `AuditLogAuthorizationTest` — guest                                  |
| AUD-AC-012 | `AuditLogAuthorizationTest` — 403                                    |
| AUD-AC-013 | `AuditLogAuthorizationTest` — detail langsung                        |
| AUD-AC-014 | `AuditLogAuthorizationTest` — tidak ada route mutasi                 |
| AUD-AC-015 | `AuditSensitiveDataTest`                                             |
| AUD-AC-016 | `AuditLogFilteringTest` — filter injection                           |
| AUD-AC-017 | `AuditSensitiveDataTest` + detail React                              |
| AUD-AC-018 | `AuditLogDeletionSnapshotTest`                                       |
| AUD-AC-019 | `AuditLogFilteringTest` — default sort                               |
| AUD-AC-020 | `AuditLogFilteringTest` — pagination/cap                             |
| AUD-AC-021 | `AuditLogFilteringTest` — filter + query string                      |
| AUD-AC-022 | `AuditLogFilteringTest` — search fields                              |
| AUD-AC-023 | Detail test / E2E                                                    |
| AUD-AC-024 | `formatWibDateTime` + shared `displayTimezone`                       |
| AUD-AC-025 | E2E + pengecekan manual responsif/loading/error                      |
| AUD-AC-026 | `AuditLogAuthorizationTest` + Vitest `visibleEntries`                |
| AUD-AC-027 | `AuditSchemaTest` + `migrate:rollback`                               |
| AUD-AC-028 | Konfigurasi database test project                                    |
| AUD-AC-029 | Seluruh test Audit Log                                               |
| AUD-AC-030 | `composer ci:check`                                                  |
| AUD-AC-031 | `php artisan wayfinder:generate --with-form`                         |
| AUD-AC-032 | `docs/audit-log.md` + `docs/DECISIONS.md`                            |

---

## 13. Urutan implementasi

### Tahap 0 — Finalisasi kontrak

- [ ] Konfirmasi plan ini.
- [ ] Catat keputusan di `docs/DECISIONS.md` sebagai D-79.
- [ ] Buat kerangka `docs/audit-log.md`.

### Tahap 1 — Fondasi data dan pencatatan

File baru:

- [ ] `config/audit.php`
- [ ] `lang/id/audit.php`
- [ ] `database/migrations/2026_10_27_000000_create_audit_events_table.php`
- [ ] `app/Models/AuditEvent.php`
- [ ] `app/Services/AuditLogger.php`
- [ ] `app/Actions/Audit/RecordAuditEventAction.php`
- [ ] `app/Http/Middleware/AssignRequestId.php`
- [ ] `database/factories/AuditEventFactory.php`

File diubah:

- [ ] `bootstrap/app.php` — append `AssignRequestId`

Verifikasi:

- [ ] unit test redaksi/diff/ringkasan;
- [ ] feature test pencatatan dasar;
- [ ] `php artisan migrate` pada MySQL 8;
- [ ] rollback migration pada database non-produksi/test.

### Tahap 2 — Read API dan otorisasi

File baru:

- [ ] `app/Http/Controllers/Audit/AuditLogController.php`
- [ ] `app/Http/Requests/Audit/AuditLogIndexRequest.php`
- [ ] `app/Http/Resources/Audit/AuditEventListResource.php`
- [ ] `app/Http/Resources/Audit/AuditEventDetailResource.php`
- [ ] `app/Policies/AuditEventPolicy.php`

File diubah:

- [ ] `database/seeders/PermissionSeeder.php`
- [ ] `routes/web.php`
- [ ] `docs/RBAC.md`

Verifikasi:

- [ ] authorization/filtering tests;
- [ ] `php artisan wayfinder:generate --with-form`;
- [ ] `php artisan route:list`.

### Tahap 3 — Integrasi inti

- [ ] Berita: `SavePostAction`, `PostController::destroy`, `PublishScheduledPosts`.
- [ ] Agenda: `SaveEventAction`, `DuplicateEventAction`, `EventController::destroy`.
- [ ] Jadwal Misa: 3 controller + `DuplicateMassScheduleAction`.
- [ ] Pengaturan: `SettingsController::update`.
- [ ] Media: `MediaController`, `GenerateImageVariants`.
- [ ] Dokumen: `SaveDocumentAction`, `DeleteDocumentAction`.
- [ ] Autentikasi: `ProfileController`, `SecurityController`, `ResetUserPassword`, 3 listener.

Verifikasi:

- [ ] feature test integrasi per modul;
- [ ] pastikan satu operasi = satu event utama;
- [ ] `composer ci:check` setelah tahap ini.

### Tahap 4 — Integrasi lanjutan

- [ ] Profil/pastor/kepengurusan/wilayah pelayanan.
- [ ] Pelayanan + item.
- [ ] Master data (kategori, lokasi).
- [ ] Komunitas + kategori.
- [ ] Area.
- [ ] Galeri album/foto.

Verifikasi:

- [ ] `AuditLogIntegrationTest` per modul;
- [ ] test rollback untuk delete;
- [ ] test reorder/aktivasi massal.

### Tahap 5 — UI

File baru:

- [ ] `resources/js/features/admin/audit-log/pages/index.tsx`
- [ ] `resources/js/features/admin/audit-log/pages/show.tsx`
- [ ] `resources/js/features/admin/audit-log/components/audit-result-badge.tsx`
- [ ] `resources/js/features/admin/audit-log/components/audit-diff.tsx`
- [ ] `resources/js/features/admin/audit-log/types.ts`

File diubah:

- [ ] `resources/js/layouts/app/app-sidebar-nav.ts`
- [ ] `resources/js/layouts/app/components/app-sidebar.tsx`
- [ ] `resources/js/layouts/app/components/app-sidebar-entry.tsx`
- [ ] `resources/js/types/auth.ts`
- [ ] `resources/js/types/index.ts`
- [ ] `app/Http/Middleware/HandleInertiaRequests.php`

Verifikasi:

- [ ] Vitest;
- [ ] E2E bila lingkungan tersedia;
- [ ] cek responsif, empty/loading/error state.

### Tahap 6 — Verifikasi akhir dan dokumentasi

- [ ] `php artisan test tests/Feature/Audit`
- [ ] `composer ci:check`
- [ ] rollback migration pada database non-produksi/test
- [ ] `EXPLAIN` pada dataset representatif (minimal 10.000 event)
- [ ] periksa tidak ada file generated Wayfinder yang diedit manual
- [ ] perbarui `docs/DECISIONS.md`
- [ ] perbarui `docs/audit-log.md`
- [ ] perbarui `docs/RBAC.md`
- [ ] laporkan hasil aktual; jangan klaim lulus tanpa eksekusi

---

## 14. Daftar file

### 14.1 File baru

```text
config/audit.php
lang/id/audit.php
database/migrations/2026_10_27_000000_create_audit_events_table.php
database/factories/AuditEventFactory.php
app/Models/AuditEvent.php
app/Services/AuditLogger.php
app/Actions/Audit/RecordAuditEventAction.php
app/Http/Controllers/Audit/AuditLogController.php
app/Http/Requests/Audit/AuditLogIndexRequest.php
app/Http/Resources/Audit/AuditEventListResource.php
app/Http/Resources/Audit/AuditEventDetailResource.php
app/Policies/AuditEventPolicy.php
app/Http/Middleware/AssignRequestId.php
app/Listeners/RecordLoginSucceeded.php
app/Listeners/RecordLoginFailed.php
app/Listeners/RecordLogout.php
resources/js/features/admin/audit-log/pages/index.tsx
resources/js/features/admin/audit-log/pages/show.tsx
resources/js/features/admin/audit-log/components/audit-result-badge.tsx
resources/js/features/admin/audit-log/components/audit-diff.tsx
resources/js/features/admin/audit-log/types.ts
docs/audit-log.md
tests/Feature/Audit/AuditLogAuthorizationTest.php
tests/Feature/Audit/AuditEventRecordingTest.php
tests/Feature/Audit/AuditSensitiveDataTest.php
tests/Feature/Audit/AuditLogFilteringTest.php
tests/Feature/Audit/AuditLogDeletionSnapshotTest.php
tests/Feature/Audit/AuditLogIntegrationTest.php
tests/Feature/Audit/AuditAuthenticationTest.php
tests/Feature/Audit/AuditSettingsTest.php
tests/Feature/Audit/AuditMediaJobTest.php
tests/Feature/Audit/AuditScheduledPublishingTest.php
tests/Feature/Audit/AuditSchemaTest.php
tests/Feature/Audit/AuditConfigTest.php
tests/Unit/Audit/*.php
tests/js/audit-*.test.ts
tests/e2e/audit-log.spec.ts (opsional)
```

### 14.2 File yang diubah

```text
routes/web.php
bootstrap/app.php
database/seeders/PermissionSeeder.php
app/Http/Middleware/HandleInertiaRequests.php
resources/js/layouts/app/app-sidebar-nav.ts
resources/js/layouts/app/components/app-sidebar.tsx
resources/js/layouts/app/components/app-sidebar-entry.tsx
resources/js/types/auth.ts
resources/js/types/index.ts
docs/DECISIONS.md
docs/RBAC.md

# Integrasi backend
app/Actions/News/SavePostAction.php
app/Actions/Agenda/SaveEventAction.php
app/Actions/Agenda/DuplicateEventAction.php
app/Actions/Document/SaveDocumentAction.php
app/Actions/Document/DeleteDocumentAction.php
app/Actions/MassSchedule/DuplicateMassScheduleAction.php
app/Actions/Profil/SetCurrentPastorAction.php
app/Actions/Profil/ActivateBoardPeriodAction.php
app/Actions/Pelayanan/SaveServiceAction.php
app/Actions/Pelayanan/ReorderServiceItemsAction.php
app/Actions/Komunitas/SaveCommunityAction.php
app/Actions/Komunitas/DeleteCommunityAction.php
app/Actions/Galeri/SaveGalleryAlbumAction.php
app/Actions/Galeri/AttachGalleryPhotosAction.php
app/Actions/Galeri/ReorderGalleryPhotosAction.php
app/Actions/Fortify/ResetUserPassword.php
app/Jobs/GenerateImageVariants.php
app/Console/Commands/PublishScheduledPosts.php
app/Http/Controllers/News/PostController.php
app/Http/Controllers/Agenda/EventController.php
app/Http/Controllers/MassSchedule/MassScheduleController.php
app/Http/Controllers/MassSchedule/SpecialMassController.php
app/Http/Controllers/MassSchedule/ScheduleNoticeController.php
app/Http/Controllers/Settings/SettingsController.php
app/Http/Controllers/Settings/ProfileController.php
app/Http/Controllers/Settings/SecurityController.php
app/Http/Controllers/Media/MediaController.php
app/Http/Controllers/Document/DocumentController.php
app/Http/Controllers/MasterData/*.php
app/Http/Controllers/Profil/*.php
app/Http/Controllers/Pelayanan/*.php
app/Http/Controllers/Komunitas/CommunityController.php
app/Http/Controllers/Area/AreaController.php
app/Http/Controllers/Galeri/GalleryController.php
app/Http/Controllers/Galeri/GalleryPhotoController.php
```

---

## 15. Definisi selesai

Audit Log MVP dianggap selesai apabila:

1. Catatan dibuat otomatis di backend untuk seluruh event P0 yang disepakati.
2. Setiap event memiliki aktor, waktu, aksi, modul, hasil, dan identitas objek yang relevan.
3. Before/after aman dan cukup untuk menjelaskan perubahan.
4. Secret dan data terlarang tidak tersimpan.
5. Tidak ada route aplikasi untuk mengubah atau menghapus event audit.
6. Route daftar/detail dilindungi middleware admin dan permission.
7. Filter dan pagination dilakukan di backend.
8. Waktu ditampilkan dalam `Asia/Pontianak`.
9. Feature tests membuktikan pencatatan, rollback, redaksi, akses, dan filter.
10. `composer ci:check` serta test relevan lulus pada lingkungan yang benar.
11. Kebijakan retensi dan keterbatasan append-only terdokumentasi.
12. Tidak ada klaim bahwa log immutable terhadap administrator database/server tanpa kontrol eksternal yang sesuai.

---

## 16. Batas dan risiko

### 16.1 Di luar MVP

- Dashboard SIEM atau deteksi intrusi penuh.
- Notifikasi eksternal WhatsApp/email untuk setiap kejadian.
- Ekspor PDF atau CSV.
- Hash chaining atau tanda tangan kriptografis.
- Penyimpanan log di layanan eksternal berbayar.
- UI manajemen role/permission.
- Manajemen pengguna lengkap jika UI tersebut belum ada.
- Merekam page view, klik, atau navigasi pengguna.
- Menyalin seluruh HTTP request atau payload.
- Menyimpan isi file yang diunggah.
- Auto-purge retensi.
- Restore event (belum ada route restore objek).

### 16.2 Risiko

| Risiko                                                                           | Mitigasi                                                                                          |
| -------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------- |
| Integrasi menyentuh ~30 controller/action                                        | Implementasi bertahap, test integrasi per modul, `composer ci:check` per tahap                    |
| Event audit di dalam transaksi dapat menggagalkan mutasi bila insert audit gagal | Diperlakukan sebagai kegagalan sistem yang wajar; transaksi rollback dan admin dapat mencoba lagi |
| Data pribadi (IP, user-agent, email login gagal)                                 | Akses hanya lewat permission, retensi 12 bulan, dokumentasi privasi                               |
| Operasi massal membanjiri tabel                                                  | Satu event agregat dengan metadata count/ID terbatas                                              |
| `super_admin` tidak memiliki permission ter-attach                               | Menu memakai `can()` melalui shared `abilities`, bukan daftar permission Spatie                   |
| Queue/console tidak memiliki request context                                     | `request_id`, IP, user-agent null; actor system                                                   |
| File fisik gagal dihapus setelah transaksi                                       | Event bisnis tetap akurat; kegagalan storage dicatat sebagai event sistem                         |
| MySQL reserved keyword pada `before`/`after`                                     | Schema Builder meng-quote identifier; diverifikasi pada MySQL 8                                   |
| Lupa regenerasi Wayfinder                                                        | Jalankan `php artisan wayfinder:generate --with-form`; jangan edit file generated                 |

---

## 17. Dokumentasi yang diperbarui

### `docs/DECISIONS.md`

Tambah D-79 “Audit Log MVP” yang mencatat:

- tabel dan tipe kolom;
- allowlist dan redaksi;
- transaksi dan konsistensi;
- IP/user-agent disimpan;
- login gagal menyimpan email;
- retensi 12 bulan tanpa auto-purge;
- operasi massal agregat;
- role/permission UI ditunda;
- batas append-only.

### `docs/audit-log.md`

Berisi:

- tujuan dan ruang lingkup;
- kebijakan retensi 12 bulan;
- daftar modul dan field allowlist;
- tujuan penyimpanan IP/user-agent/email login gagal;
- batas append-only dan integritas;
- prosedur backup dan akses;
- prosedur peninjauan log;
- titik integrasi yang ditunda.

### `docs/RBAC.md`

- Tambah `auditlogs.view`.
- Catat bahwa akses Super Admin tetap melalui `Gate::before`.

---

## 18. Rujukan

- `docs/specifications/spec-audit-log.md`
- `docs/PRD.md`
- `docs/ARCHITECTURE.md`
- `docs/DECISIONS.md`
- `docs/RBAC.md`
- `AGENTS.md`
- `routes/web.php`
- `database/seeders/PermissionSeeder.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Middleware/HandleInertiaRequests.php`
- `resources/js/layouts/app/app-sidebar-nav.ts`
