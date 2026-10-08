# Decisions

Catatan keputusan arsitektur yang **tidak eksplisit** di `PRD.md`. PRD tetap
menjadi sumber kebenaran; berkas ini hanya merekam alasan di balik pilihan
implementasi dan konsekuensinya.

Format: `D-xx` untuk keputusan Phase 01. Nomor `D-01` sampai `D-16` pada PRD
Bagian 2 **milik PRD** — jangan dipakai ulang di sini.

Status: **Accepted** = sudah diputuskan dan diterapkan.

---

## D-01 — Penyimpanan UTC, tampilan Asia/Pontianak (implementasi PRD D-01)

**Status:** Accepted · Phase 01 Workstream 02

`config('app.timezone')` tetap `'UTC'` dan **tidak boleh** diubah ke WIB.
Ditambahkan `config('app.display_timezone')` = `env('APP_DISPLAY_TIMEZONE',
'Asia/Pontianak')`.

**Alasan:** PHP hanya menerima satu zona waktu global
(`date_default_timezone_set()`), jadi dua kebutuhan — menyimpan UTC dan
menampilkan WIB — tidak bisa dijamin satu kunci.

**Konsekuensi:**

- Semua `datetime` yang dikirim ke frontend harus berupa string ISO 8601 dan
  dirender di `app.display_timezone`. Ini menjadi shared prop pada P07.
- Perhitungan "hari ini", "misa berikutnya", dan "agenda mendatang"
  (`XC-T3`) memakai zona WIB, bukan UTC.
- Jadwal misa mingguan disimpan sebagai **jam dinding WIB** pada kolom `time`
  dan tidak dikonversi (PRD D-02).
- Pengujian: `tests/Feature/Foundation/ApplicationConfigurationTest.php`.

---

## D-02 — Locale `id` dengan terjemahan penuh

**Status:** Accepted · Phase 01 Workstream 02

`app.locale` = `id`, `app.fallback_locale` = `id`, `app.faker_locale` =
`id_ID`. Berkas `lang/id/validation.php` diterjemahkan **lengkap** (111 kunci,
menyamakan persis `vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php`).

**Alasan:** Phase 01 §7.2 mengizinkan `fallback_locale = id` **atau**
konfigurasi lain yang konsisten. Karena `fallback_locale` juga `id`, tidak ada
jaring pengaman ke Bahasa Inggris — aturan yang tidak diterjemahkan akan
menampilkan string kunci mentah. Terjemahan penuh dipilih agar
`AC-04` (Phase 01) dan `ADM-02` (PRD) selalu terpenuhi.

**Konsekuensi:** menambah aturan validasi baru di Laravel **wajib** menambah
terjemahan Indonesian-nya. Dua guard menjaga hal ini:

- `no validation rule falls back to an untranslated key`
- `every English validation rule exists in the Indonesian file`

Catatan: Laravel 13 **tidak** punya kunci `validation.failed`, jadi berkas ini
tidak memuatnya.

---

## D-03 — Test memakai MySQL, bukan SQLite in-memory

**Status:** Accepted · Phase 01 Workstream 01

`phpunit.xml` menunjuk `DB_CONNECTION=mysql` dan
`DB_DATABASE=website_paroki_hspmtb_test`.

**Alasan:** ekstensi `pdo_sqlite` tidak tersedia pada host pengembangan
(`php -m` hanya memuat `PDO`, `pdo_mysql`, `pdo_pgsql`, `mysqli`), sehingga
39 dari 40 test gagal dengan _"could not find driver"_. Secondary benefit:
test berjalan di engine yang sama dengan produksi (`utf8mb4`, strict mode),
sehingga perbedaan perilaku seperti `ONLY_FULL_GROUP_BY` dan batas panjang
index ketahuan sejak awal.

**Konsekuensi:**

- `RefreshDatabase` menjalankan `migrate:fresh`, jadi database test wajib
  terpisah dari database pengembangan.
- `php artisan db:create` **tidak ada** di Laravel 13, sehingga database harus
  dibuat manual sekali per mesin:
    ```sql
    CREATE DATABASE website_paroki_hspmtb_test
        CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
    ```
    Di CI, database dibuat otomatis oleh service container (lihat D-04).
- Nilai database test ditulis **hard-code** di `phpunit.xml`, bukan dari
  `.env`, agar tidak mungkin "tidak sengaja" menunjuk database dev.
  Dijaga oleh test `tests never run against the development database`.

---

## D-04 — Kredensial test tidak pernah ada di source

**Status:** Accepted · Phase 01 Workstream 01

`phpunit.xml` hanya mengatur `DB_CONNECTION` dan `DB_DATABASE`.
`DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` dibaca dari `.env` lokal atau
job `env` di GitHub Actions.

Di CI, `.github/workflows/tests.yml` menambahkan MySQL service container dengan
`MYSQL_DATABASE: website_paroki_hspmtb_test` (membuat database otomatis, tanpa
langkah `CREATE DATABASE` terpisah) dan job-level `env` berisi kredensial.
`DB_HOST` di CI harus `127.0.0.1` karena job berjalan langsung di runner, bukan
di dalam container.

**Alasan:** Phase 01 §38 ("password tidak boleh masuk source") dan
`AGENTS.md` ("Never commit secrets or keys").

---

## D-05 — `DB_URL` tidak pernah diisi

**Status:** Accepted

Baris `<env name="DB_URL" value=""/>` dihapus dari `phpunit.xml`, dan key
`DB_URL` tidak ada di `.env` maupun `.env.example`.

**Alasan:** `config/database.php` membaca `'url' => env('DB_URL')` pada setiap
connection. Selama key **ada**, Laravel mem-parse `DB_URL` sebagai URL koneksi.
String kosong menghasilkan konfigurasi yang tidak terduga; di CI, variabel
lingkungan yang tidak terisi bisa membuat seluruh workflow gagal.

---

## D-06 — Dokumen markdown tidak diformat oleh `vp`

**Status:** Accepted

`docs/**`, `*.md`, `.codex/**`, dan `skills-lock.json` ditambahkan ke
`fmt.ignorePatterns` pada `vite.config.ts`.

**Alasan:** `PRD.md`, `ARCHITECTURE.md`, dan `DESIGN.md` bersifat normative dan
dirujuk sebagai source of truth. `vp check --fix` akan merombak isinya dan
berisiko mengubah kutipan requirement ID (`XC-M1`, `AC-HOME-3`, dan
sebagainya). `npm run check` harus tetap hijau agar `composer ci:check`
menjadi gate yang bermakna.

---

## D-07 — Database pengembangan lokal adalah MySQL

**Status:** Accepted · supersedes catatan lama di `AGENTS.md`

`.env` memakai MySQL; target PRD §3.1 juga MySQL dengan `utf8mb4`. Teks lama
di `AGENTS.md` ("SQLite locally", "No Docker") sudah tidak akurat dan telah
diperbarui.

**Konsekuensi:** MySQL dijalankan langsung di host pengembangan, bukan lewat
container. Lihat D-15 untuk keputusan menghapus Docker.

---

## D-08 — Struktur direktori frontend

**Status:** Accepted · dieksekusi pada P06/P07

Tetap memakai `resources/js/pages/` **lowercase** dengan subfolder
`public/` dan `admin/`. Halaman auth dipindahkan ke `pages/public/auth/*`.

**Alasan:** PRD §3.2 menulis `Pages/Public/*` dan Phase 01 §9.1 menulis
`Pages/Admin/*`, sedangkan starter kit dan `AGENTS.md` memakai lowercase flat.
Phase 01 §32 sendiri menyatakan _"Actual tree may differ slightly according to
existing Laravel/Inertia starter structure. Do not force unnecessary
restructuring."_ Mengikuti konvensi yang sudah ada menghindari rename massal
yang mengubah nama komponen Inertia dan layout switch di `app.tsx`.

Layout baru: `layouts/public-layout.tsx` (PublicLayout) dan
`layouts/admin-layout.tsx` (AdminLayout).

---

## D-09 — 2FA dan Passkeys dihapus dari MVP

**Status:** Accepted · berlaku sejak branch `feat/phase-01-foundation-refine`
· **membalik** versi pertama keputusan ini (2FA dipertahankan)

`Features::twoFactorAuthentication()` dan `Features::passkeys()` **dihapus**,
serta seluruh kode, route, halaman, dan kolom database miliknya. D-09 yang
pertama justru mempertahankan keduanya sebagai deviasi sadar dari Phase 01 §4;
keputusan itu dibalik karena satu-satunya akun admin MVP tidak memerlukannya dan
karena Phase 01 §4 sendiri sudah mencantumkan 2FA sebagai Out of Scope.

**Alasan:**

- **Phase 01 §4 mencantumkan "2FA" sebagai Out of Scope.** Menghapusnya
  mengembalikan keselarasan dengan roadmap, bukan menyimpang darinya.
- **MVP ini satu role dan satu akun.** `super_admin` adalah satu-satunya role
  dan Phase 01 tidak membangun Role Management UI, sehingga tidak ada akun
  kedua yang perlu dilindungi dari session hijacking milik akun pertama.
- **Kode 2FA yang ada tidak pernah dipakai.** Tidak ada akun yang pernah
  mengaktifkan `two_factor_confirmed_at`, jadi ini menghapus kode mati, bukan
 crippled produk.
- **Akun dibuat dari `.env`.** Kredensial Super Admin berasal dari environment,
  bukan dari UI publik, sehingga tidak ada alur pendaftaran yang perlu dilindungi.

**Konsekuensi yang harus disadari:** login kini hanya punya **satu** lapisan —
password, dengan rate limit 5 percobaan/menit per kombinasi email+IP. Sebelumnya
ada tiga pintu masuk (password, two-factor challenge, passkey); sekarang hanya
satu. Jadi D-09 ini adalah **pengurangan** defense-in-depth dan bukan netral.
Wajar untuk ukuran MVP ini, dan harus ditinjau ulang begitu role kedua atau
session hijacking jadi ancaman nyata.

**Tidak boleh dilupakan kalau 2FA dibutuhkan lagi** (phase berikutnya):

- `config('fortify.features')` harus menyalakan `Features::twoFactorAuthentication()`
  kembali, dan `config('fortify.limiters')` harus punya entri `'two-factor'`
  yang dipetakan ke rate limiter bernama sama.
- Kolom `two_factor_secret`, `two_factor_recovery_codes`, dan
  `two_factor_confirmed_at` **sudah tidak ada** di tabel `users`. Perlu
  migration baru, bukan menghidupkan kembali migration lama.
- Tabel `passkeys` juga sudah tidak ada, dengan konsekuensi yang sama.
- `EnsureAccountIsActive` dan `EnsureAccountCanLogIn` sengaja ditulis tanpa
  historis ini sehingga tidak perlu diubah saat 2FA kembali, tapi saat itu
  argumen posisi pipe di `EnsureAccountCanLogIn` perlu ditulis ulang lagi.

**Catatan teknis:** `laravel/passkeys` **tidak bisa** di-uninstall karena
`laravel/fortify` v1.40.0 mensyaratkannya sebagai dependency. Yang dihapus
adalah feature-nya, kode aplikasi kita, dan dependency npm
(`input-otp`, `@laravel/passkeys`). File
`resources/js/components/ui/input-otp.tsx` ikut terhapus: tanpa dependency
`input-otp` file itu tidak bisa dikompilasi, dan setelah semua pengimpornya
dihapus tidak ada satu pun importer yang tersisa, sehingga `npm run types:check`
akan gagal selama file itu ada.

---

## D-10 — Phase 01 §41 memakai script yang benar-benar ada

**Status:** Accepted

Final gate di Phase 01 §41 memanggil `composer lint`, `composer analyse`,
`npm run lint`, dan `npm run typecheck` — sebagian besar tidak ada di repo.
§41 sendiri menyatakan _"sesuaikan command dengan script aktual"_. Yang
dipakai:

| Ganti | Dengan |
| --- | --- |
| `composer lint` | `composer lint:check` |
| `composer analyse` | `composer types:check` |
| `npm run lint` + `npm run typecheck` | `npm run check` + `npm run types:check` |
| seluruhnya | `composer ci:check` lalu `composer test` |

---

## D-11 — `site_settings` memakai kolom `key` dan `group`

**Status:** Accepted · Phase 01 Workstream 03

PRD §9.2 dan Phase 01 §8.2 sama-sama menyebut kolom `key` dan `group`, dan
skema itu diikuti apa adanya.

**Masalah:** keduanya adalah reserved word MySQL
(`information_schema.KEYWORDS` → `RESERVED = 1` untuk `KEY` dan `GROUP`).

**Mengapa aman:** Laravel membungkus seluruh identifier dengan backtick di
`MySqlGrammar`, baik pada schema builder maupun query builder, sehingga
`CREATE TABLE`, `insert`, dan `where` semuanya aman. Sudah diverifikasi dengan
`SHOW CREATE TABLE` dan sebuah smoke test insert/select.

**Batasnya:** hanya berlaku pada SQL yang lewat query builder atau Eloquent.
Raw SQL manual harus tetap menulis backtick.

**Tanpa index `group`:** tabel ini ±30 baris konfigurasi, sehingga full scan
lebih murah daripada menyimpan index. `unique` pada `key` sudah menutup jalur
lookup yang dibutuhkan.

---

## D-12 — Seeder `site_settings` ditunda ke P09

**Status:** Accepted · Phase 01 Workstream 03 · eksekusi di P09

P03 hanya membuat skema; baris data dan model `SiteSetting` adalah bagian dari
`SiteSettingsService` (P09) yang menjadi single access point.

Nilai awal yang disepakati saat P09 berjalan:

- `parish_name` = `"Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau"`
- `parish_short_name` = `"HSPMTB"`
- seluruh kunci Lampiran B lainnya bernilai kosong

Kedua nilai tersebut diambil dari PRD Lampiran D, bukan dikarang, dan PRD
menandainya `[CONFIRM]` — belum dikonfirmasi pihak paroki. Karena itu
`AGENTS.md` melarang cheesy data paroki nyata: nilai yang belum dikonfirmasi
harus tetap kosong sampai diisi Super Admin lewat `/admin/pengaturan`.

---

## D-13 — `is_active` tidak masuk `$fillable`

**Status:** Accepted · Phase 01 Workstream 03

Kolom `is_active` dicast ke `boolean` dan punya scope `active()`, tetapi
sengaja **tidak** ditambahkan ke `#[Fillable]`.

**Alasan:** NFR-SEC mewajibkan controlled mass assignment, dan tidak ada form
pada P03 yang boleh menogol status akun. Menambahkannya sekarang berarti
`_fillable` menerima `is_active` dari input apa pun yang tidak kita awasi.

P06 akan menambahkannya ke `$fillable` **secara sadar**, bersamaan dengan
form manajemen akun yang memang butuh kemampuan itu. Sampai saat itu, status
akun hanya berubah lewat kode.

---

## D-14 — `DatabaseSeeder` dikosongkan pada P03

**Status:** Accepted · Phase 01 Workstream 03

Seeder bawaan starter membuat `test@example.com`. Itu adalah data fiktif dan
melanggar `AGENTS.md` ("Never invent real parish data"), jadi dihapus.

**Konsekuensi yang perlu diketahui:** setelah `php artisan migrate:fresh --seed`
**tidak ada akun yang bisa login**. Ini memang benar untuk Phase 01 — role
`super_admin` belum ada sampai P05 Workstream 06. Jangan mengira ini bug.

Pemanggilan seeder akan ditambahkan saat masing-masing seeder ada:

| Seeder | Phase | Isi |
| --- | --- | --- |
| `SuperAdminSeeder` | P05 | akun Super Admin dari `ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` |
| `SiteSettingsSeeder` | P09 | kunci kanonik PRD Lampiran B |

Dijaga oleh test `the seeder creates no fabricated parish or admin data`.

---

## D-15 — Docker tidak dipakai; MySQL dijalankan langsung di host

**Status:** Accepted · Phase 01 · P04 dibatalkan

Phase 01 Workstream 04 (Docker Development Environment), item `P04` pada §33, dan
bagian §3.1.D **tidak dijalankan**. MySQL 8 dijalankan langsung di host
pengembangan.

**Mengapa ini tidak menyimpang dari PRD.** Aturan Phase 01 §1: *"Jika dokumen
Phase 01 ini berbeda dengan PRD, PRD harus diprioritaskan dan perbedaan harus
dicatat di docs/DECISIONS.md."* PRD **tidak pernah menyebut Docker sama sekali**
(`grep -i docker docs/PRD.md` → nol hasil). Yang PRD lakukan justru mengarahkan
arsitektur deployment lain:

> PRD §17.3 Q6 — *"Konfigurasi via `.env`; siapkan panduan deploy generik
> (**VPS + Nginx + PHP-FPM + MySQL**)"*

Jadi Docker adalah temuan Phase 01 saja, dan menghapuskannya justru lebih
cocok dengan PRD.

**AC-01 tetap terpenuhi.** AC-01 hanya menuntut
`install → configure .env → migrate → seed → build → serve` tanpa menyebut
Docker. Checkbox §37 "MySQL dapat dijalankan" juga tetap terpenuhi lewat
MySQL native.

**Dampak ke dokumen Phase 01** — dicatat di sini, dokumen spec-nya sendiri
**tidak** diedit supaya bukti divergensinya tetap ada:

| Lokasi | Efek |
| --- | --- |
| §3.1.D | tidak dijalankan |
| §9 Workstream 04 | tidak dijalankan |
| §33 P04 | dihapus dari urutan |
| §31 DEVELOPMENT.md | bullet "Docker" diganti bagian setup MySQL native |

**Yang berubah secara praktis:**

- Developer wajib memasang MySQL 8 sendiri. `docs/DEVELOPMENT.md` (P15) akan
  mendokumentasikannya.
- `docs/DEVELOPMENT.md` tidak akan punya bagian `docker compose up`.
- Ekstensi PHP untuk image processing tetap harus dipasang manual —
  lihat bagian Known divergences nomor 9.

CI tidak terpengaruh: `.github/workflows/tests.yml` memakai MySQL **service
container** milik GitHub Actions, yang merupakan hal berbeda dari Docker
sebagai alat pengembangan lokal.

---

## D-16 — Akses penuh `super_admin` lewat `Gate::before`

**Status:** Accepted · Phase 01 Workstream 05

Phase 01 §10.6 hanya menulis "`super_admin` harus memiliki akses penuh melalui
Spatie" tanpa menyebut mekanismenya. Dipilih `Gate::before()` di
`AppServiceProvider::boot()`, sesuai rekomendasi resmi Spatie, **bukan**
menempelkan permission ke role.

**Alasan:** permission baru di phase berikutnya otomatis ter-given tanpa perlu
diingat menempelkannya ke role. Kalau setiap permission-permission
eksplisit, Super Admin diam-diam kehilangan akses begitu ada permission baru.

**Dua hal yang mudah salah dan harus diingat:**

1. Closure **wajib mengembalikan `null`, bukan `false`**. Mengembalikan `false`
   akan memotong seluruh policy di aplikasi. Dijaga oleh test
   `a user without the super_admin role is denied`.
2. Berlaku **hanya pada pemeriksaan Gate** — `can()`, `Gate::authorize()`, dan
   method Policy. Panggilan langsung `$user->hasPermissionTo()` melewati Gate dan
   **tidak** ikut ter-cover. Kode otorisasi karena itu harus memakai `can()`.

**Pola yang dipakai aplikasi** (ARCHITECTURE.md Part A §8 "jangan hand-roll
gate"):

```php
Gate::before(fn (User $user, string $ability): ?bool
    => $user->hasRole(AppServiceProvider::SUPER_ADMIN) ?: null);
```

Nama role disimpan sebagai konstanta `AppServiceProvider::SUPER_ADMIN` agar
seeder dan provider tidak mengulang string-nya.

---

## D-17 — `SuperAdminSeeder`: dilewati bila env kosong, divalidasi bila terisi

**Status:** Accepted · Phase 01 Workstream 05

`SuperAdminSeeder` membaca `ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD`.

**Bila email atau password kosong → seeder dilewati**, bukan error. Diperlukan
karena `php artisan migrate:fresh --seed` dijalankan di mesin yang belum
dikonfigurasi `.env` dan di CI, keduanya tidak punya kredensial admin.

**Bila terisi → password divalidasi terhadap `Password::defaults()`** dan seeder
menolak dengan pesan jelas kalau lemah.

**Konsekuensi `Password::defaults()`:** di production aturan ini berisi
`uncompromised()`, yang memanggil `https://api.pwnedpasswords.com`. Jadi
seeding di production punya ketergantungan jaringan — disengaja, karena itu
memakai kebijakan password yang sama dengan form ganti kata sandi, bukan aturan
 terpisah yang bisa melenceng. Di luar production, `AppServiceProvider`
 mengembalikan `null`, dan `Password::default()` jatuh ke `min(8)` — sehingga
 tidak ada panggilan jaringan sama sekali saat test atau development.

**`forceFill()`, bukan `updateOrCreate()`.** `is_active` sengaja tidak ada di
`$fillable` (D-13), jadi mass assignment akan membuangnya diam-diam dan akun
Super Admin berakhir **nonaktif** — kebalikan dari yang diminta Workstream 06.
Seeder adalah kode tepercaya, jadi melewati guard fillable di sini benar. Ini
sudah ketahuan karena test gagal saat `updateOrCreate` dipakai.

**Role `super_admin` di-seed oleh `PermissionSeeder`, bukan
`SuperAdminSeeder`.** Phase 01 §10.3 menyebutnya *system role*, jadi harus ada
bahkan di mesin yang tidak punya `ADMIN_EMAIL` — tanpa akun pun. Ini juga
ketahuan lewat pemeriksaan manual, bukan lewat test.

**Idempoten** lewat `firstOrNew(['email' => ...])` + `syncRoles()`, sehingga
menjalankan seeder berkali-kali tidak menghasilkan akun duplikat.

---

## D-18 — Daftar permission fondasi

**Status:** Accepted · Phase 01 Workstream 05

`PermissionSeeder` membuat **11 permission, persis contoh Phase 01 §10.4**:

```text
admin.access        <- ditambahkan pada P06, lihat D-19

dashboard.view

settings.view
settings.update

users.view
users.create
users.update
users.disable

posts.view
posts.create
posts.update
posts.delete
```

Sebelas permission pertama diambil persis dari contoh §10.4 tanpa dikarang.
Penamaan mengikuti PRD AUTH-R6 (`resource.action`), dijaga test. Dua belas
permission di-seed setelah P06 menambah `admin.access`.

**Permission modul lain sengaja tidak dibuat** (agenda, galeri, komunitas,
jadwal-misa, profile, pelayanan, download, dan seterusnya).
Setiap modul butuh set permission yang berbeda, jadi dibuatnya saat modulnya
dibangun daripada membuat permission yang belum dibaca apa pun — persis
kaidah "jangan abstraksi prematur" di ARCHITECTURE.md, dan menghindari
perlu migration tambahan.

---

## D-19 — Autentikasi dan area admin pindah ke prefix `/admin`

**Status:** Accepted · Phase 01 Workstream 07

Semua autentikasi Fortify dipindah ke `/admin/*` lewat
`config('fortify.php')`: `prefix` menjadi `admin`, `home` menjadi `/admin`.
Dashboard pindah ke `/admin`, dan halaman akun sesuai PRD §5.2 menjadi
`/admin/akun/{profile,security,appearance}`. Nama route tidak berubah
(`dashboard`, `profile.edit`, `security.edit`), jadi pemanggilan `route()` di
seluruh test tetap bekerja tanpa perubahan.

**Registrasi publik dihapus** (AUTH-R2, Phase 01 §F). `Features::registration()`
dicabut dari `config/fortify.php`, `Fortify::registerView()` dan
`Fortify::createUsersUsing()` dihapus dari provider, `CreateNewUser`, halaman
`register.tsx`, dan `RegistrationTest` dihapus. Tautan "Register" juga dibuang
dari halaman login dan landing page.

**Gerbang `/admin/*` adalah permission `admin.access`**, bukan nama role.
`EnsureAdminAccess` memanggil `Gate::authorize('admin.access')`, jadi Spatie tetap
menjadi sumber kebenaran otorisasi seperti AUTH-R3, bukan closure Gate
atau `if ($user->role === ...)`. inilah yang membuat grant `Gate::before` untuk
`super_admin` berlaku di sini, dan satu-satunya role pada MVP adalah
`super_admin` (AUTH-R5). Ada test struktural yang memverifikasi ketujuh halaman
admin memakai ketiga lapisan middleware.

**Urutan middleware itu penting:** `auth` → `verified` →
`EnsureAccountIsActive` → `EnsureAdminAccess`. Akun nonaktif harus mendapat
pesan "akun dinonaktifkan", bukan error izin — jadi pemeriksaan aktif lebih dulu.

**`.well-known/passkey-endpoints` tetap di root**, karena path itu ditentukan
spesifikasi WebAuthn, bukan pilihan kita.

---

## D-20 — `is_active` ditegakkan di dua lapis

**Status:** Accepted · Phase 01 Workstream 07

PRD D-08 mensyaratkan akun nonaktif tidak dapat login. Satu titik pengecekan
tidak cukup, karena ada dua cara berbeda untuk membuat sesi: saat login, dan
pada setiap request berikutnya.

| Lapis | Kelas | Tugas |
| --- | --- | --- |
| Login | `EnsureAccountCanLogIn` | pipe di pipeline Fortify, menolak sebelum sesi dibuat |
| Area admin | `EnsureAccountIsActive` | middleware `/admin/*`, yang benar-benar menegakkan |

Pipe-nya disisipkan **setelah `CanonicalizeUsername` dan sebelum
`Authenticate`**, sehingga akun nonaktif ditolak sebelum kredensial dicek dan
sebelum sesi sempat dibuat. 2FA dan passkey sudah tidak ada (D-09), jadi posisi
ini kini satu-satunya yang relevan.

Override `config('fortify.pipelines.login')` **mengganti** pipeline default
Fortify sepenuhnya, jadi daftar default ditulis ulang secara eksplisit di
config. Salah satu namespace di therein mudah terbalik: pipe Fortify ada di
`Laravel\Fortify\Actions`, bukan `Http\Middleware`.

**Middleware adalah lapisan otoritatif.** Kalau akun dinonaktifkan selagi
seseorang sudah login, hanya middleware yang bisa menangkapnya, karena pipe login
tidak dijalankan lagi pada request berikutnya.

**Pesan memakai `auth.inactive` yang spesifik**, bukan `auth.failed` generik.
Panel admin tidak diindeks dan tidak ada registrasi publik, jadi risiko
membocorkan "akun ini ada tapi dinonaktifkan" sangat kecil, sementara
penghobi paroki tidak perlu bingung kenapa kata sandinya benar tapi ditolak.
Lapis middleware tetap yang menentukan keamanan; pesan hanya soal UX.

---

## D-21 — Bentuk `auth.user` ditulis eksplisit, bukan model mentah

**Status:** Accepted · berlaku sejak P07

`HandleInertiaRequests::share()` dulu mengirim `$request->user()` apa adanya.
Sekarang `auth.user` hanya berisi `id`, `name`, `email`, dan
`email_verified_at`, ditulis eksplisit di middleware tersebut.

**Yang sebenarnya bocor, dan yang tidak.** Pernyataan "model User mentah dikirim
seluruhnya" benar, tapi lengkapnya perlu diluruskan: `User` memakai atribut
`#[Hidden]` (`app/Models/User.php`), jadi `password`, `remember_token`, dan
dua kolom 2FA **tidak pernah** masuk ke payload. Yang benar-benar bocor hanya
`is_active`, `created_at`, `updated_at`, dan `email_verified_at` — tidak ada
secret di antaranya. Jadi ini **bukan lubang keamanan**, melainkan pelanggaran
kontrak: ARCHITECTURE.md Part C aturan 1 melarang model mentah, dan aturan 5
menyuruh shared props tetap kecil.

**Mengapa tetap dikerjakan.** Atribut `#[Hidden]` adalah jaring pengaman, bukan
kontrak. begitu ada `$user->load('roles')` di suatu tempat, atau ada kolom baru
yang lupa didaftarkan, data itu bocor tanpa ada yang menyadar. Kontrak eksplisit
lebih tahan.

**Sisi TypeScript.** `types/auth.ts` sebelumnya punya
`User = { ...; [key: string]: unknown }` dan `Auth = { user: User }`. Dua
pembohongan tipe:

- `user` dideklarasikan non-null, padahal backend mengirim `null` untuk tamu.
  `nav-user.tsx` sudah `if (!auth.user) return null` — kodenya benar, tipenya
  salah.
- Index signature membuat `user.avatar` terbaca tanpa error padahal kolom itu
  tidak pernah ada. Setelah `avatar` dihapus (commit `2c33154`), tipe ketat
  membuat `tsc` langsung menolak, dan tiga pembacaan `auth.user.*` di
  `profile.tsx` yang tanpa null-check ikut tertangkap.

Jadi tipe ketat ini sudah membayar ongkos dengan menemukan tiga bug nyata.

**Ditambah:** shared prop `locale` (`id`) dan `displayTimezone`
(`Asia/Pontianak`), keduanya diwajibkan PRD §14. `types/index.ts` sekarang
memiliki `SharedProps`, dan `global.d.ts` memakainya supaya `usePage()` tanpa
generic ikut benar.

**`errors` dan `flash` tidak disentuh** karena keduanya sudah disediakan
Inertia v3: `errors` oleh `parent::share()`, `flash` sebagai event DOM
`flash` (bukan prop, dan sengaja tidak ikut history state). Hook
`useFlashToast` sudah benar sejak awal.

---

## D-22 — Palet, token, dan penyimpangan dari DESIGN.md

**Status:** Accepted · berlaku sejak P08 · branch `feat/phase-01-ui-foundation`

P08 menerapkan `docs/DESIGN.md` ke `resources/`. Dokumen itu tidak selalu bisa
diikuti persis; semua penyimpangan dicatat di sini beserta alasannya.

### 1. `--all` shadcn tidak dipakai, hanya 3 primitive

`npx shadcn@latest add --all` diuji dan **ditolak**, dengan bukti:

- Menarik paket `radix-ui` terpadu padahal repo memakai 13 paket
  `@radix-ui/react-*` terpisah.
- Menambahkan paket `cn` dan menulis ulang import menjadi `from "cn"`, padahal
  repo punya `cn` sendiri di `@/lib/utils`.
- Menjalankan `pnpm install` di proyek npm dan meninggalkan `pnpm-lock.yaml`.
- **Registry `sonner` mengimpor `next-themes`,** library khusus Next.js. Repo ini
  Vite + Inertia. Overwrite akan mematikan `useFlashToast` dan flash toast P07.
- Menarik ±22 dependency untuk komponen yang tidak dipakai Phase 01
  (`recharts` 150KB, `react-day-picker` + `date-fns`, `embla-carousel-react`,
  `vaul`, `react-resizable-panels`, `react-hook-form` + `zod`).

Hanya `textarea`, `table`, dan `pagination` yang diinstal — ketiganya **0
dependency baru**. `package.json` dan `package-lock.json` tidak berubah sama
sekali. `ConfirmDialog` dibangun di atas `dialog.tsx` yang sudah ada, bukan
`AlertDialog`, agar daftar dependency tidak bertambah.

### 2. `accent-foreground` bukan `gold-dark`

DESIGN.md memetakan `accent-foreground: gold-dark (#D99400)`. Pasangan itu
terhadap `accent: gold-light (#FFF4D6)` hanya **2.34:1** — gagal WCAG AA bahkan
untuk teks besar. Button variant `outline` dan `ghost` menaruh teks persis di
pasangan itu, jadi mengikuti dokumen secara literal akan mengirim teks hover
yang tidak terbaca. Diganti `navy-dark (#001A4D)` yang **14.62:1**, dan nilainya
sudah ada di dokumen.

`--color-gold-dark` tetap tersedia untuk penggunaan dekoratif, bukan teks di
latar terang.

### 3. Dark mode diturunkan, bukan dari dokumen

DESIGN.md §905 menyatakan *"Dark-mode requirements have not been established"*.
Keputusan: dark mode **dipertahankan** (switcher light/dark/system di halaman
Akun tetap berfungsi) dan paletnya diturunkan dari ramp navy yang sudah
dokumentasi, memakai `color-mix()` terhadap `navy-dark` sehingga setiap nilai
masih bisa ditelusuri ke warna di DESIGN.md.

Interaktif memakai `primary-on-dark (#FF6670)` karena merah primary tidak
terbaca di atas navy. Hasil: body 16.73:1, muted 6.50:1, on-primary 6.59:1.

**Palet ini di luar DESIGN.md dan perlu konfirmasi paroki.**

### 4. `red-light` tidak ada di DESIGN.md

Dokumen memakai `{colors.red-light}` untuk `components.profile-sidebar`
(activeBackgroundColor) tapi **tidak pernah mendefinisikannya** di blok
`colors`. Diturunkan dari `primary` dengan `color-mix(in oklab, #ab020e 8%,
#ffffff)` → `#F8EBEC`, rasio teks 6.58:1. Dipakai juga untuk permukaan Alert
destructive.

### 5. Tidak ada warna sukses di palet

Tiga pesan sukses (`text-green-600`) diganti `text-navy` / `text-navy-light`.
Hijau tidak ada di palet DESIGN.md, dan menambahkan satu berarti melanggar
*"Do not use arbitrary brand colors outside the documented palette"*. Navy
struktural lebih tepat secara merek, tapi **kurang ideal secara semantik**.
Kalau paroki ingin hijau konfirmasi, nilainya harus masuk DESIGN.md lebih dulu.

### 6. Tanpa paket font baru

DESIGN.md meminta SF Pro Display / SF Pro Text dengan fallback `system-ui`, dan
§444 melarang menambah dependency font tanpa keputusan eksplisit. SF Pro tidak
berlisensi untuk distribusi web, jadi yang dipakai adalah stack system — **tanpa
paket font baru**.

Deklarasi lama `'Instrument Sans'` dihapus: tidak ada `@font-face` maupun
`@import` yang memuatnya, jadi browser diam-diam jatuh ke `system-ui` selama ini.

### 7. Spacing tidak perlu token baru

DESIGN.md meminta skala 4/8/12/16/24/32/48/80/112. Tailwind v4 sudah
menghasilkan angka persis itu sebagai `p-1`, `p-2`, `p-3`, `p-4`, `p-6`, `p-8`,
`p-12`, `p-20`, `p-28`. Dicatat di komentar CSS, bukan diduplikasi.

### 8. `shadcn` CLI kadang merusak `node_modules`

CLI menjalankan `pnpm install` di proyek npm. Dalam keadaan itu `tsc`
melaporkan `auth` sebagai `unknown` di enam file — gejalanya persis seperti
regresi tipe shared prop, padahal kodenya tidak salah. `npm install` dari
`package-lock.json` menyelesaikannya. Kalau `tsc` tiba-tiba gagal dengan
`unknown` pada shared props, cek struktur `node_modules` lebih dulu.

---

## D-23 — Site settings: konfigurasi tunggal, tanpa seeding

**Status:** Accepted · berlaku sejak P09 · branch `feat/phase-01-settings`

P09 membangun service, cache, dan halaman `/admin/pengaturan` untuk `site_settings`.
Schema-nya sudah benar sejak P03 (PRD §9.2) dan **tidak diubah** — P09 murni
model, service, dan UI.

### 1. `config/site-settings.php` adalah sumber kebenaran tunggal

Daftar kunci, tipe, default, aturan validasi, dan konfigurasi cache semuanya di
satu file. Service, FormRequest, dan form React sama-sama membacanya.

Alternatifnya adalah menggandakan daftar itu di PHP dan TypeScript. Tiga
salinan akan cepat berbeda satu sama lain, lalu tidak ada yang tahu mana yang
benar.

Menambah kunci baru cukup: tambah di `config/site-settings.php`, tambah label di
`lang/id/site-settings.php`. Field-nya otomatis muncul di form.

### 2. Hanya dua nilai yang di-seed, sesuai PRD Lampiran D

PRD Lampiran D menyebut `site_settings` bernilai awal
`parish_name = "Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau"` dan
`parish_short_name = "HSPMTB"` (`[CONFIRM]`), sisanya kosong. **D-12 sudah
menyepakati ini di P03**, dengan alasan kedua nilai itu dikutip dari PRD dan
bukan dikarang — jadi tidak melanggar larangan data paroki karangan.

Keputusan awal P09 justru "tidak ada seeding sama sekali". Itu **bertentangan
dengan PRD dan D-12**, dan sudah dikoreksi: `SiteSettingsSeeder` sekarang
menyemai tepat dua kunci itu saja, memakai `firstOrCreate` supaya menjalankannya
dua kali tidak menimpa nilai yang sudah diubah Super Admin.

**Kunci lainnya tetap tidak punya baris.** `get()` mengembalikan default dari
konfigurasi bila baris belum ada, sehingga **"belum diatur"** tetap terbedakan
dari **"sengaja dikosongkan"**, dan `home_news_limit` yang belum diatur tetap
punya nilai yang bisa dipakai halaman publik tanpa perlu baris database.

### 3. 30 kunci dari 6 grup; `privasi` aktif sejak P11

Roadmap §18 menyebut lima grup (identity, contact, social, seo, homepage).
PRD Lampiran B punya enam — tambahan `privasi` dengan
`privacy_policy_content`.

Grup itu **ditunda di P09** karena isinya rich text dan PRD D-14 mewajibkan
sanitasi server-side, sementara `HtmlSanitizer` belum ada. **P11 melunaskannya**
dan kunci itu sekarang aktif: tipenya `html` di `config/site-settings.php`, dan
`UpdateSettingsRequest` menyanitasinya sebelum validasi. Satu-satunya field rich
text di aplikasi pada saat ini.

Sisa 29 kunci tanpa grup `privasi`: identitas 8, kontak 7, sosial 4, seo 3,
beranda 7.

### 4. Field gambar = teks path/URL, dan janji P10 tidak ditepati

D-23 §4 yang pertama pernah menulis: *"widget unggah menyusul di P10"*.

**P10 selesai dan widget itu TIDAK ada.** P10 hanya membangun pipeline dan
endpoint (D-24 §1), tanpa antarmuka. Jadi `logo`, `favicon`, dan
`seo_default_og_image` **tetap input teks** di `/admin/pengaturan`.

Cara memperbaikinya ada di tangan: pipeline P10 siap, yang kurang hanya satu
kolom `media_id` dan tombol unggah di form pengaturan. Itu pekerjaan **phase
modul**, bukan fondasi.

Ini bukan keputusan sementara yang menunggu; fitur ini memang belum dibangun.

### 5. Default `home_show_*` = `true` — asumsi, bukan dari dokumen

Lampiran B hanya menyebut rentang untuk tiga `*_limit` (3–6, 3–5, 6) dan
menyebut `home_show_*` sebagai toggle tanpa nilai awal. **Asumsi: semua blok
muncul secara default**, dengan asumsi paroki menyembunyikan blok yang tidak
dipakai, bukan sebaliknya.

Kalau paroki lebih suka situs started kosong, ubah `defaults` di
`config/site-settings.php`. Tidak ada kode yang perlu disentuh.

### 6. Satu kunci cache untuk seluruh peta

Roadmap §24 memberi contoh `site_settings` dan `site_settings:{group}`. Yang
dipakai hanya yang pertama.

Tiga puluh baris terlalu sedikit untuk membenarkan banyak kunci, dan kunci
terpisah adalah tempat bug invalidasi tinggal. TTL 24 jam hanya **jaring
pengaman**; yang benar adalah `forget()` setiap kali admin menyimpan
(XC-C1).

Service sengaja **tidak** di-binding sebagai singleton dan **tidak** melakukan
memoization: ia tanpa state, dan memo akan bertahan meski `forget()` sudah
dijalankan — persis basi yang seharusnya dicegah cache.

### 7. Otorisasi di dua tempat, sesuai aturan single-place

ARCHITECTURE.md Part C §8: FormRequest adalah satu-satunya tempat untuk
`store`/`update`. Maka `settings.update` dicek di
`UpdateSettingsRequest::authorize()`, sedangkan `settings.view` dicek dengan
`Gate::authorize()` di controller karena GET tidak punya request untuk
diotorisasi.

Kunci yang tidak dikenal **ditolak**, bukan diabaikan. Tanpa itu, siapa pun yang
memiliki `settings.update` bisa menulis baris sembarang ke tabel — dan baris itu
nanti dibaca kembali sebagai konfigurasi.

### 8. Pintasan CLI shadcn

P08 membuktikan `npx shadcn@latest add` merusak lingkungan proyek ini (paket
`radix-ui` terpadu, paket `cn`, `pnpm install` di proyek npm). Karena itu
`switch` dan `tabs` diambil langsung dari registry, lalu dua paket Radix-nya
dipasang dengan `npm install`. `package.json` hanya dapat dua baris.

---

## D-24 — Fondasi media: GD, tabel `media`, tanpa UI

**Status:** Accepted · berlaku sejak P10 · branch `feat/phase-01-media`

### 1. P10 adalah fondasi, bukan fitur

Tidak ada halaman media library, tidak ada galeri, tidak ada pemilih gambar di
halaman mana pun. Endpoint-nya (`POST /admin/media`, `GET /admin/media/{media}`,
`DELETE /admin/media/{media}`) **hanya bisa dipanggil manual**.

Konsekuensi yang harus terus diingat: **tabel `media` kosong dalam pemakaian
normal**, karena tidak ada kode aplikasi yang membuat baris media.

Alasannya disepakati: PRD Fase 1 meminta pipeline dan job; antarmuka browse
menyusul bersama modul pertama yang membutuhkannya (Beranda, Berita, atau
Galeri). Roadmap §34 mewajibkan urutan P01→P15 tanpa lompatan.

Yang bisa diverifikasi sekarang: test suite membuktikan transformasinya benar.
Yang belum: tidak ada playthrough.

### 2. Tabel `media` dan `media_variants` dibuat di P10

PRD §9 **tidak punya** tabel media — semua gambar disimpan sebagai kolom
`image_path` di tabel masing-masing (`hero_slides`, `clergy`, `gallery_photos`).
 Roadmap §20 tetap meminta `width`, `height`, `file_size`, `mime_type`, `path`
di-persist, dan XC-M1 meminta UI menampilkan status pemrosesan. Keduanya butuh
tempat.

Roadmap §9 menyatakan "nama dan tipe bersifat usulan; boleh disesuaikan selama
perilaku dalam dokumen ini terpenuhi", jadi menambah tabel ini tidak melanggar
PRD.

Asli disimpan di disk privat, varian di disk publik (§19).

### 3. GD langsung, tanpa `intervention/image`

Yang tersedia: **GD 2.3.3** (WebP ✅, JPEG ✅, PNG ✅) + `exif`. Yang ditambahkan:
**nol dependency**.

Pipeline-nya hanya: validasi → decode → baca orientasi → resize → re-encode →
simpan. Semuanya beberapa baris terhadap GD. Konsisten dengan sikap repo yang
menolak menambah pustaka untuk satu masalah terisolasi (D-22 melarang `--all`
shadcn dengan alasan serupa).

### 4. Penghapusan EXIF adalah konsekuensi, bukan langkah

XC-M2 mewajibkan EXIF (termasuk GPS) hilang dari setiap gambar yang
dipublikasikan. **GD tidak menulis blok EXIF sama sekali**, jadi apa pun yang
melewati pipeline ini keluar tanpa EXIF. Tidak ada langkah penghapusan terpisah
yang bisa terlupa dan tidak ada cabang kode yang bisa melewatinya.

Orientasi **tetap** dibaca. Foto ponsel sering tersimpan menyamping dengan tag
Orientation; pipeline yang hanya resize akan menerbitkan setiap foto itu
terbalik 90°.

### 5. Ekstensi file berasal dari MIME terdeteksi

Nama berkas di disk memakai ekstensi dari **MIME yang terdeteksi**, bukan dari
nama kiriman. Versi pertama memakai nama kiriman, sehingga unggahancrafted bisa
berakhir sebagai `something.php` di disk. Nama asli tetap disimpan di
`Media.original_name` untuk ditampilkan.

`Media::url()` mengembalikan `null` untuk disk non-publik. `Storage::disk()->url()`
tetap membuat path `/storage/...` walau tidak ada yang disajikan di sana, sehingga
file asli privat akan mendapat URL yang tampil sebagai gambar rusak.

### 6. Tiga permission `media.*`

Ditambahkan `media.view`, `media.create`, `media.delete`; total jadi **15**.
Docblock `PermissionSeeder` sendiri menyatakan permission tidak boleh ada untuk
kode yang belum membacanya — ketiganya **dibaca seketika** oleh endpoint unggah.

### 7. Batas unggah 3 MB butuh penyesuaian php.ini

PRD POST-04 menyebut 3 MB. Nilai `upload_max_filesize` di mesin ini **2M**.
PHP_INI_PERDIR tidak bisa diubah dari `.env`, jadi PHP akan menolak request
**sebelum** Laravel melihatnya. Validasi aplikasi tetap 3 MB; menaikkan limit
host menjadi catatan deployment untuk `DEVELOPMENT.md` (P15).

### 8. SVG ditolak

Bukan karena GD tidak bisa membacanya, tapi karena SVG adalah dokumen yang bisa
memuat skrip dan tidak bisa dirasterkan dengan aman tanpa sanitiser terpisah
(P11).

## Known divergences (belum diselesaikan)

| # | Deviasi | Risiko / alasan | Rencana |
| --- | --- | --- | --- |
| 1 | ~~`HandleInertiaRequests::share()` mengirim **seluruh model User**.~~ **Ditutup di P07** (D-21). Sekarang hanya `id`, `name`, `email`, `email_verified_at`. Catatan koreksi: yang bocor hanya metadata tidak sensitif — `#[Hidden]` sudah melindungi semua secret, jadi ini pelanggaran kontrak, bukan kebocoran. | — | Selesai |
| 2 | ~~Flash message memakai `Inertia::flash('toast', ...)` + `useFlashToast()`, bukan shared prop `flash`.~~ **Ditutup di P07** (D-21): ini memang mekanisme **Inertia v3** — flash dikirim sebagai event DOM `flash` dan sengaja tidak masuk history state. Hook yang sudah ada benar; tidak ada yang perlu diubah. | — | Selesai |
| 3 | ~~`display_timezone` belum dikirim ke frontend.~~ **Ditutup di P07** (D-21): sudah jadi shared prop `displayTimezone`, bersanding dengan `locale`. | — | Selesai |
| 4 | ~~`Model::preventLazyLoading()` / `shouldBeStrict()` belum aktif.~~ **Ditutup di P13** (D-27): aktif di non-produksi sesuai PRD §3.3, karena `preventLazyLoading()` melempar exception dan satu lazy load di produksi akan jadi 500 di depan pengunjung. Ketiga guard diverifikasi hidup, bukan diasumsikan. | ARCHITECTURE.md Part A §6 dan PRD §3.3 mewajibkannya. | — | Selesai |
| 5 | ~~`DatabaseSeeder` masih membuat `test@example.com`.~~ **Ditutup di P03** (D-14). | — | Selesai |
| 6 | ~~`Features::registration()` masih aktif sehingga `/register` publik hidup.~~ **Ditutup di P06** (D-19). | — | Selesai |
| 7 | Tabel `site_settings` sudah ada, tetapi belum ada model, `SiteSettingsService`, cache, maupun halaman `/admin/pengaturan`. | Tidak ada single access point untuk pengaturan situs. | P09 (D-12) |
| 8 | ~~`is_active` ada di skema tapi belum ditegakkan.~~ **Ditutup di P06** (D-20). | — | Selesai |
| 9 | ~~PHP extensions `gd` / `imagick` tidak terpasang.~~ **Ditutup** — `gd` 2.3.3 dengan WebP support sudah terpasang. | — | Selesai |
| 10 | Ekstensi PHP `intl` tidak terpasang. | Belum memblokir apa pun, tapi beberapa library dapat memakainya. | Bila perlu |
| 11 | `super_admin` mendapat akses ke **semua** ability lewat `Gate::before`, termasuk ability yang di masa depan mungkin tidak boleh dimiliki siapa pun. | Otorisasi implisit: tidak ada daftar permission yang bisa dibaca untuk audit. Ini trade-off yang disepakati di D-16, bukan kelalaian. | P06 — kalau muncul ability yang harus dikecualikan, pakai `Gate::after` atau `deny` eksplisit |

---

## Yang masih diperlukan (Phase 01)

P15 docs.

Test matrix: **semua 34 baris ter-cover**.

**Sudah selesai:** P01, P02, P03, P05, P06, P07, P08, P09, P10, P11, P12, P13, dan **P14**.
P04 (Docker) dibatalkan
— lihat D-15. Status per butir tercatat di `docs/roadmap/phase-01-project-foundation.md` §33.

Autentikasi sudah pindah ke `/admin/*`, registrasi publik sudah dihapus,
`is_active` sudah ditegakkan, dan gerbang `/admin/*` memakai permission
`admin.access`. Lihat D-19 dan D-20.

**2FA dan passkey sudah dihapus** (D-09), jadi login kini satu lapis: password
dengan rate limit 5 percobaan/menit. Endpoint hapus akun juga dihapus karena
PRD §8 melarang penghapusan akun diri sendiri dan route lama tidak punya
parameter `{user}` sehingga hanya bisa menghapus pemanggilnya.

Fondasi Inertia sudah beres: `auth.user` hanya mengirim `id`, `name`, `email`,
`email_verified_at`, ditambah shared prop `locale` dan `displayTimezone`. Lihat
D-21.

**Fondasi media sudah aktif:** pipeline GD (MIME, orientasi, WebP, varian, EXIF),
job `GenerateImageVariants`, dan endpoint di `/admin/media`. **Tanpa antarmuka** —
tabel `media` kosong dalam pemakaian normal. Lihat D-24.

**Pengaturan situs sudah aktif:** `SiteSettingsService` + cache, halaman
`/admin/pengaturan` dengan 30 kunci dari 6 grup. Tidak ada seeding, jadi
"belum diatur" tetap berbeda dari "sengaja dikosongkan". Lihat D-23.

**Sanitasi HTML sudah aktif:** `HtmlSanitizer` di allowlist
`config/html.php`, dipasang pada `UpdateSettingsRequest` sebelum validasi.
Satu field rich text memakainya sekarang (`privacy_policy_content`), dan
Tiptap sudah tersedia untuk field berikutnya. Belum ada halaman publik yang
merender rich text. Lihat D-25.

**Fondasi UI sudah menerapkan DESIGN.md:** palet merah/navy/gold HSPMTB,
tipografi, skala radius, `PublicLayout` dan `AdminLayout`. Semua penyimpangan
terhadap dokumen — termasuk dark mode yang diturunkan dari ramp navy — tercatat
di D-22.

Fondasi RBAC sudah aktif: `spatie/laravel-permission` **8.3.0** terpasang,
`HasRoles` pada `User`, role `super_admin` + 12 permission ter-seed, dan
`Gate::before` di `AppServiceProvider`. Lihat D-16, D-17, D-18.

Daftar 12 permission yang sudah di-seed (11 dari §10.4 + `admin.access`) tercatat di D-18 dan D-19.

Permission per modul lain (agenda, galeri, komunitas, jadwal-misa, dst.)
dibuat saat modulnya dibangun, supaya tidak perlu migration tambahan sekarang.

---

## D-25 — Sanitasi HTML: `symfony/html-sanitizer` di allowlist sendiri

**Status:** Accepted · berlaku sejak P11 · branch `feat/phase-01-sanitization`

PRD D-14 mewajibkan sanitasi server-side untuk setiap HTML yang disimpan, dan
menyatakan sanitasi klien saja tidak cukup. P11 membangunnya.

### 1. Pustaka, dan kenapa bukan yang lain

PRD D-14 menyebut *"`mews/purifier` atau HTMLPurifier"* — **sebagai contoh, bukan
syarat**. Tiga opsi, dipertimbangkan dengan saksama:

| Opsi | Verdict |
| --- | --- |
| `symfony/html-sanitizer` ^7.4 | **Dipakai.** Vendor resmi, API `allowlist()` persis kebutuhan PRD, satu dependency kecil, repo sudah 33 paket Symfony 7.4 di vendor |
| Tulis sendiri di atas `DOMDocument` | Ditolak. Sanitasi HTML adalah salah satu area XSS paling rawan; allowlist sendiri harus diaudit, bukan diasumsikan benar |
| `mews/purifier` | Ditolak. Menarik `ezyang/htmlpurifier` yang relatif lama, dan kompatibilitas Laravel 13 belum diverifikasi |

Ini **tidak** mencerminkan keputusan P10 menolak `intervention/image` demi GD.
P10 menolak library karena masalahnya terisolasi dan GD cukup untuk itu. Sanitasi
HTML bukan masalah terisolasi, dan konsekuensi dari allowlist yang salah jauh
lebih serius daripada baris kode yang dihemat. Konsistensi alasan lebih penting
daripada konsistensi hasil.

Satu dependency baru, dan hanya satu, disetujui eksplisit.

### 2. Allowlist ada di `config/html.php`

Bukan di konstruktor service, untuk alasan yang sama seperti `config/media.php`:
service membangun konfigurasi Symfony dari file itu, dan salinan kedua dari
daftar tag akan perlahan berbeda dari yang dibaca test.

Tag persis roadmap §22, plus `thead`/`tbody`/`tr`/`th`/`td` yang tidak disebut
PRD tetapi wajib ada — `<table>` tanpa mereka tidak merender apa pun.

**`class`, `id`, dan `style` tidak diizinkan di mana pun.** Menempel konten dari
Word atau Google Docs tidak boleh bisa menyuntikkan layout atau CSS. Catatan:
Filament mengizinkan `style` lewat sanitizer-nya, tapi karena CSS di dalamnya
tidak diparsing — itu alasan menelantarkannya di sini.

`img` hanya boleh `src` dan `alt`. `width`/`height` memang cara menghindari
layout shift, tapi juga cara markup mengklaim ukuran yang tidak dimiliki, dan
modul media sudah tahu dimensi asli setiap gambar yang diterbitkan.

### 3. Tiga default yang tidak boleh dibiarkan begitu

Default Symfony tidak cocok untuk CMS, dan ketiganya diketatkan di
`config/html.php`:

- **`default_action` = `block`, bukan `drop`.** Default Symfony menghapus
  *isi* setiap tag yang tidak dikenal. Itu artinya satu `<div>` nyasar dari
  hasil paste akan menghapus seluruh paragrafnya. Dengan `block`, tag tak
  dikenal kehilangan tagnya tapi teksnya selamat. Elemen berbahaya tetap
  *drop* eksplisit lewat `drop_elements`, jadi setelan ini tidak pernah
  memutuskan nasib sebuah script.
- **Skema media diketatkan ke `['http', 'https']`.** Symfony mengizinkan
  `data:` secara default. Tidak ada kebutuhan di sini, dan `data:` URL di
  `img src` adalah dokumen yang menyamar.
- **Konten elemen raw-text dibuang, bukan hanya tagnya.** `<style>` dan
  `<title>` tidak punya content model markup; parser membaca isinya sebagai
  satu run teks. Drop tag ternyata tidak drop isi — stylesheet hasil paste
  berakhir sebagai teks terlihat `p{color:red}` di tengah artikel. Untuk
  `<title>` itu kebocoran informasi, bukan sekadar kosmetik.

### 4. Normalisasi `<h1>`, `<b>`, `<i>`

Tiga hal tidak bisa diselesaikan oleh allowlist saja, karena kelakuannya
adalah menghapus tag dan menyimpan teks:

- **`<h1>` → `<h2>`.** Allowlist punya h2/h3/h4 dan tidak punya h1, karena h1
  milik judul halaman dan dipegang layout, bukan field yang diisi admin.
  Memblock h1 akan meninggalkan kalimatnya sebagai body text dan menghapus
  struktur dokumen penulis. Dipromosikan, bukan dibuang.
- **`<b>` → `<strong>`, `<i>` → `<em>`.** Konten yang di-paste penuh dengan
  keduanya; kehilangan emphasis setiap kali paste adalah regresi, bukan
  sanitasi.

Pola regex mensyaratkan `>` atau whitespace langsung setelah huruf, supaya `<b>`
tidak ikut mencocoki `<blockquote>`, `<br>`, atau `<body>`. Ada test untuk itu.

### 5. Sanitasi di `prepareForValidation`, bukan setelah rules

Urutan itu disengaja, dua alasan:

1. Aturan panjang harus mengukur **apa yang akan disimpan**, bukan apa yang
   diposting. Kalau tidak, admin diberi tahu kebijakannya kelewat panjang
   padahal yang membuatnya panjang adalah markup yang memang tidak boleh
   disimpan.
2. Aturan validasi hanya bisa menolak; tidak ada cara membuatnya menulis ulang
   nilai yang divalidasinya.

Kunci mana yang rich text dibaca dari `config('site-settings.types')`, dan
`SettingsController` menurunkan daftar editor dari peta yang **sama**. Satu
deklarasi, dua tempat yang tidak bisa menyimpang.

### 6. P11 memakai Tiptap, dan Tiptap belum dipakai halaman publik

`@tiptap/react` + `@tiptap/pm` + `@tiptap/starter-kit`. StarterKit v3 sudah
membawa `Link` dan `Underline`, jadi tidak perlu paket tambahan.

Konfigurasi yang lebih penting dari toolbar-nya:

- Level heading berhenti di 4, mengikuti allowlist.
- `code`, `codeBlock`, `strike`, `horizontalRule` **dimatikan** — StarterKit
  mengaktifkan keempatnya secara default dan tidak satupun ada di allowlist.
  Editor yang bisa menghasilkan konten yang server hapus diam-diam lebih buruk
  daripada tidak ada editor.

**Image dan table sengaja tidak ada.** Image butuh image picker dari P10 yang
tidak pernah dibangun (D-24 §1); roadmap §23 memang mengatakan "bila
dibutuhkan". Tabel tidak dibutuhkan modul Fase 2 mana pun.

Editor dimuat lewat `React.lazy`. Radix tidak mount tab yang tidak aktif, jadi
import lazy cukup untuk mengeluarkan Tiptap dari chunk pengaturan: **438 kB
menjadi 35 kB**, dan chunk editor 404 kB hanya diambil saat tab yang memakainya
dibuka.

Styling prose adalah kelas `.rich-text` di `app.css`, dipakai bersama oleh
permukaan edit dan renderer, memakai token `@theme` yang sudah ada —
**bukan** `@tailwindcss/typography`, karena DESIGN.md meminta tipografi memetakan
token terdokumentasi dan plugin membawa skala sendiri.

`resources/js/components/rich-text.tsx` adalah satu-satunya
`dangerouslySetInnerHTML` di repo. Alasannya ditulis sebagai komentar blok,
**bukan** suppressing lint: `react/no-danger` bukan rule yang aktif di
`vite.config.ts`, jadi disable directive hanya memberi rasa aman yang salah.

### 7. Yang dikecualikan

Sanitasi ini juga berlaku untuk field non-rich-text nanti — `maps_embed_url`
(XC-S2, hanya domain Google Maps) dan `form_url` (XC-S3, hanya Google Form).
Keduanya **belum** dikerjakan: keduanya punya aturan host yang lebih ketat dari
allowlist skema URL, dan tidak ada field-nya di Phase 01. Saat itu menyebut
`HtmlSanitizer::toPlainText()` untuk mengekstrak teks polos, bukan mengandalkan
sanitasi HTML untuk memvalidasi host.

`isClean()` ada untuk membuktikan idempotensi di test. Tidak ada kode aplikasi
yang mempercayai hasilnya.
---

## D-26 — Infrastruktur: scheduler, halaman error, dan SEO tanpa SSR

**Status:** Accepted · berlaku sejak P12 · branch `feat/phase-01-infrastructure`

Roadmap section 33 P12 punya enam checkbox. Empat di antaranya sudah benar
sebelum P12 dimulai (`QUEUE_CONNECTION=database`, `CACHE_STORE=database`,
`QUEUE_FAILED_DRIVER=database-uuids`, dan ketiga tabelnya sudah ada), dan tiga
tidak sama sekali ada: `withSchedule()`, halaman error berbahasa Indonesia, dan
komponen SEO.

### 1. Scheduler: satu task, dan cara menjalankannya bukan bagian dari schedule

Satu task: `queue:prune-failed --hours=168` harian. Tabel `failed_jobs` tumbuh
selamanya tanpa pruning, dan tujuh hari adalah waktu yang cukup untuk dibaca dan
didelegasikan lewat `queue:retry` sebelum hilang.

`model:prune` **sengaja tidak** dijadwalkan. Tidak ada model `Prunable` di repo,
jadi perintahnya no-op. Mendaftarkan perintah yang tidak melakukan apa-apa hanya
membuat checklist terlihat terpenuhi.

Dua hal yang tidak dijadwalkan, dan alasannya:

- **`schedule:run`.** Dokumentasi Laravel untuk production memakai cron
  `* * * * * php artisan schedule:run`; development memakai `schedule:work`.
  Menjadwalkan `schedule:run` dari dalam schedule membuatnya memanggil dirinya
  sendiri.
- **`PublishScheduledPosts`.** Roadmap section 25 melarangnya secara eksplisit.

Rencana awal P12 menjadwalkan `schedule:run`. Itu keliru, dan dikoreksi ketika
dokumentasi Laravel dibaca.

### 2. `composer dev` tidak menjalankan scheduler

`Illuminate\Foundation\DevCommands::registerDefaults()` mendaftarkan `serve`,
`queue:listen`, `pail`, dan `vite`. Tidak ada scheduler. Itu default yang benar
untuk aplikasi tanpa task terjadwal, dan aplikasi ini sekarang punya satu.

`AppServiceProvider::registerSchedulerProcess()` mendaftarkan
`schedule:work` sebagai proses kelima, dijaga oleh `runningInConsole()`.

Gejalanya kalau tidak dikerjakan adalah jenis yang mahal untuk dicari: kode benar,
schedule terdaftar, dan tidak terjadi apa-apa selama pengembangan.

### 3. Halaman error: satu komponen, gate `app.debug`

PRD XC-E2 mewajibkan 404 dan 500 berbahasa Indonesia dengan layout publik.
**403 dan 419 juga ditangani** karena aplikasi ini benar-benar menghasilkan
keduanya: user signed-in tanpa role mendapat 403 di `/admin`, dan sesi
kedaluwarsa mendapat 419. Tanpa itu, administrator Indonesia bertemu halaman
bahasa Inggris untuk kasus yang benar-benar akan ia temui.

Gate-nya `config('app.debug')`, **bukan** daftar environment seperti pada contoh
dokumentasi Inertia. Keduanya ada untuk mempertahankan halaman framework, tapi
berdasar environment juga mematikan halaman kita selama test berjalan — dan
halaman yang satu-satunya test-nya adalah "assertion bahwa tidak terpakai"
adalah halaman yang tidak pernah diverifikasi. `phpunit.xml` juga tidak menyetel
`APP_DEBUG`, jadi test suite mewarisi `true` dari `.env`, dan setiap test di
`ErrorPageTest` menyetelnya secara eksplisit.

Syarat kedua adalah content negotiation. Dua gerbang sengaja meniru
`shouldRenderJsonWhen`: request yang menerima JSON sekaligus HTML harus
diperlakukan sebagai JSON, karena itulah yang dilakukan framework, dan dua
gerbang yang saling berbeda akan membuat halaman error bergantung pada urutan
registrasi. `expectsHtml()` sudah dihapus di Laravel 13, jadi `acceptsHtml()`
membawa separuh browser.

Hanya `status` yang menyeberang ke response. Exception-nya tidak pernah sampai,
sehingga tidak ada yang perlu disensor dan tidak ada stack trace yang bisa bocor.

### 4. PublicLayout untuk semua error, termasuk error admin

Roadmap section 26 mengizinkan admin shell untuk error admin "jika konteksnya
sesuai". Konteksnya tidak sesuai: `AdminLayout` punya dua belas item sidebar dan
sepuluh di antaranya adalah tautan placeholder ke route yang belum ada.
Menampilkannya di halaman error berarti navigasi ke mana-mana pada satu-satunya
halaman di mana pengunjung paling butuh satu jalan keluar yang jelas.

### 5. SSR ditunda — keputusan terbuka

PRD NFR-SEO (baris 934): *"pertimbangkan Inertia SSR agar crawler dan pratinjau
tautan membaca konten (bila SSR tidak diaktifkan, pastikan meta tag OG tetap
dirender di server pada respons HTML awal untuk halaman Beranda, Berita, Agenda,
Pelayanan)"*.

SSR **tidak** diaktifkan di Phase 01. Alasannya:

1. Kata kuncinya "pertimbangkan", bukan `MUST`. Bandingkan XC-S1 yang ditulis
   `MUST`.
2. Butir fallback itu berlaku untuk empat halaman yang **belum ada satu pun** di
   Phase 01, jadi tidak ada yang bisa diuji sekarang.
3. SSR adalah keputusan deployment: daemon Node di produksi, Supervisor,
   `inertia:start-ssr`, dan artefak build kedua.
4. Roadmap section 27 menyatakan Phase 01 hanya menyediakan fondasi SEO yang
   dapat dipakai ulang.

Yang dikerjakan sebagai gantinya adalah **klaus fallback itu**, dan itu
memerlukan mekanisme yang tidak banyak orang anticipate: React merender setelah
hydration, jadi tag `<Head>` saja **tidak ada** di respons HTML awal. Crawler dan
pratinjau WhatsApp hanya membaca body respons.

Jadi tag OG dan Twitter Card dirender **dua kali**:

- `resources/views/app.blade.php`, di slot `<x-inertia::head>`, dibaca dari
  `SiteSettingsService`. Ini yang sampai ke crawler.
- `resources/js/components/seo.tsx`, untuk nilai per halaman.

Keduanya dihubungkan oleh `data-inertia`, bukan oleh harapan. Inertia hanya
mengelola head element yang membawa atribut itu, dan mencocokkan tag server
dengan tag klien **berdasarkan nilai atributnya**. Maka string `data-inertia` di
Blade dan nilai `head-key` di `seo.tsx` adalah string yang sama: halaman yang
merender `<Seo>` akan **mengganti** default, dan halaman yang tidak merender apa
pun mempertahankannya. Menambah key di satu tempat berarti menambahkannya di
tempat lain, dan `InfrastructureFoundationTest` mengunci daftar itu sebagai
kontrak.

### 6. `seo_default_*` akhirnya punya konsumen

Tiga kunci itu bisa diubah di `/admin/pengaturan` dan **tidak dibaca siapa pun**,
sehingga mengubahnya tidak mengubah halaman apa pun. Sekarang dibaca lewat shared
prop `seo` dari `HandleInertiaRequests::share()`, yang memakai
`SiteSettingsService` sesuai D-23.

`appUrl` ikut dikirim. `og:image` harus berupa URL absolut atau pratinjau tautan
menampilkan tanpa gambar, dan nilainya masih path relatif karena widget
unggahnya tidak pernah dibangun (D-24). Komponen **tidak pernah menyentuh
`window.location`**, sehingga tetap aman untuk server-render nanti, dan itulah
perubahan yang akan dibutuhkan SSR.

Tag yang nilainya kosong **tidak** dirender. Deskripsi meta kosong lebih buruk
daripada tidak ada sama sekali: crawler dan unfurler sama-sama memperlakukannya
sebagai deskripsi dan menampilkan pratinjau kosong.

### 7. Kesenjangan yang dicatat, bukan disembunyikan

**Copy Bahasa Indonesia pada halaman error tidak diuji otomatis.** React merender
setelah hydration dan proyek ini tidak punya frontend test runner, jadi string-nya
hanya ada di browser. Mengujinya dari PHP berarti melakukan grep pada berkas
`.tsx`, dan itu test yang gagal ketika seseorang mengedit komponen sekaligus
lulus ketika tidak ada yang menyentuh test-nya.

Yang diuji adalah kontrak backend: status code, komponen Inertia, dan prop
`status`. Persyaratan kebahasa dijamin oleh konstruksi dan ditinjau secara
visual. Ini kesenjangan nyata, dan dicatat sebagai such.

> **Ditutup oleh P16 (D-29).** Suite Playwright sekarang mem-*assert* copy
> Bahasa Indonesia itu di browser, termasuk di halaman 404. Yang menarik dari
> prosesnya: assertion pertamanya **hijau untuk alasan yang salah**, karena mesin
> development punya `APP_DEBUG=true` sehingga callback `respond()` mengembalikan
> response asli lebih awal dan yang tampil adalah halaman debug Laravel — bukan
> halaman error Inertia. Status tetap 404 dan teks "404" tetap ada, jadi keduanya
> lolos terhadap halaman yang tidak ditulis siapa pun di sini. Web server E2E kini
> dipaksa `APP_DEBUG=false`.

### 8. T26 ternyata tidak pernah ter-cover

Test matrix menyebut T26 "Queue failed job → Recorded". `MediaTest` punya test
bernama "a failure marks the row failed and keeps the original" yang memanggil
`->failed()` secara manual. Itu menjalankan handler tanpa pernah melewati queue.

Penyebabnya: `phpunit.xml` menyetel `QUEUE_CONNECTION=sync`, jadi `dispatch()`
menjalankan job inline dan exception-nya masuk langsung ke test.
`FailedJobTest` memaksa koneksi `database` dan menjalankan worker sungguhan.
---

## D-27 — Kualitas: strict mode, cakupan PHPStan, dan arch test

**Status:** Accepted · berlaku sejak P13 · branch `feat/phase-01-quality`

Roadmap §33 P12 dan P13 punya enam checkbox yang seluruhnya menunjuk tool yang
**sudah terpasang sejak P01**. T29–T33 sudah dijalankan CI lewat
`composer ci:check` dan hijau. P13 tidak memasang apa pun dan tidak menambah satu
dependency pun.

Yang tersisa adalah satu divergence yang ditugaskan ke P13, satu baris test
matrix, dan dua hal yang ditemukan saat menelusuri.

### 1. Strict mode: non-produksi

Divergence #4 sudah tertutup. `AppServiceProvider::configureStrictModels()`
memanggil `Model::shouldBeStrict(! $this->app->isProduction())`.

Satu panggilan itu menyalakan tiga hal sekaligus, jadi docblock menyebut
ketiganya: melempar saat relasi di-lazy-load, saat atribut dibuang diam-diam
saat mass assignment, dan saat atribut yang tidak pernah dipilih dibaca.

**Non-produksi** mengikuti kalimat PRD §3.3, dan alasannya bukan sekadar
patuh dokumen. `preventLazyLoading()` **melempar exception**, jadi satu
`with()` yang terlupa di produksi menjadi 500 di depan pengunjung, bukan
halaman yang hanya lambat.

241 test tetap hijau dengan ketiga guard hidup, dan itu **diverifikasi**:
`Model::preventsLazyLoading()` dibaca secara langsung. Tanpa itu, "testsuite
lulus" akan terlihat sama persis dengan "guard-nya mati".

### 2. PHPStan sekarang menganalisis `tests/`

Sebelumnya `phpstan.neon` hanya memuat `app/`, `bootstrap/app.php`, `config/`,
`database/`, dan `routes/`. Ada **176 error** yang tidak pernah terlihat.
Sekarang `tests/` masuk, dan **tidak ada baseline**.

Tiga `ignoreErrors`, masing-masing dipatok ke pesan, identifier, dan direktori:

| Identifier | Pesan |
| --- | --- |
| `method.notFound` | `Call to an undefined method Pest\` |
| `property.notFound` | `Access to an undefined property Pest\` |
| `argument.templateType` | `Unable to resolve the template type TValue in call to function expect` |

Pola keduanya menuntut **tipe dari namespace `Pest\`**. Itu yang membuatnya sempit:
kesalahan tipe asli pada kelas aplikasi tetap gagal.

`vendor/pestphp/pest/phpstan-pest-extension.neon` ikut di-*include*, dan itu
menyelesaikan rantai `expect()`. Yang tidak diselesaikan adalah metode HTTP
fluent — itu bagian terbesar dari 176.

Delapan dari 176 itu **nyata** dan diperbaiki, bukan diabaikan. Dua yang paling
menarik:

- **`method_exists(User::class, 'roles')` selalu true**, karena trait
  `HasRoles` yang menyediakannya. Assert itu tidak memberi informasi apa pun
  tentang apakah `User` mendeklarasikan `roles()` sendiri.
  `getDeclaringClass()` **nama `User`**, bukan trait, karena refleksi melaporkan
  kelas yang *memakai* trait. `getTraitName()` adalah jawaban yang wajar dan
  **dihapus di PHP 8**. Yang benar: bandingkan file tempat body method itu
  hidup. Kalau `User` meng-override, file itu `User.php`.
- **`Media::find()` bertipe `Model|Collection`**, jadi setiap bacaan atributnya
  error padahal pencarian primary key jelas tidak bisa mengembalikan dua model.
  Membaca kolom lewat `value()` mengatakan hal yang sama tanpa memutar union
  dengan cast.

Satu pola ignore **dihapus**, bukan disimpan: PHPStan melaporkan pola ignore
yang tidak cocok apa pun, jadi pola yang berhenti dibutuhkan akan menggagalkan
build di momen itu juga.

### 3. Arch test

`pest-plugin-arch` sudah terpasang sejak P01 dan belum pernah dipakai.
`tests/Unit/ArchitectureTest.php` menerjemahkan larangan yang tadinya hanya
tertulis di dokumen:

| Aturan | Menegakkan |
| --- | --- |
| tidak ada `App\Repositories`, `App\Domain`, repository provider | roadmap §5.2 |
| `App\Services` tidak memakai `Illuminate\Http\Request` | ARCHITECTURE Part A §6 |
| service tidak memakai controller | aturan yang sama, arah yang berbahaya |
| tidak ada `RoleManagementController` / `PermissionManagementController` | roadmap §10.7 |
| semua model memakai atribut `Fillable` | ARCHITECTURE Part A §6 |
| `User` memakai trait `HasRoles` | PRD AUTH-R3 |
| model tidak memakai facade `DB` | Eloquent adalah persistence layer |
| `App\Http\Requests` memakai `HtmlSanitizer` | PRD XC-S1, D-25 |
| hanya `rich-text.tsx` boleh memakai `dangerouslySetInnerHTML` | D-25 |

Kolom `users.role` **sengaja tidak** diulang: arch memantulkan kode dan tidak
melihat skema, dan `RbacFoundationTest` sudah menjaganya terhadap database.

**Setiap aturan diuji dengan sengaja melanggar lalu dipastikan merah**, karena
arch test yang tidak bisa gagal adalah komentar dengan galat sintaks. Latihan
itu memberi hasil dua kali:

- Aturan controller memakai `not->toContain()` dengan empat jarum, yang
  sebenarnya berarti "tidak mengandung semuanya", jadi **satu pelanggaran
  lolos diam-diam**. Sekarang himpunan irisan.
- Aturan sanitasi **lebih lemah dari kelihatannya**, karena arch me-*resolve*
  import, bukan call site. Ia memperingatkan bahwa sebuah dependency
  menghilang, bukan membuktikan `clean()` masih dipanggil. Keduanya kini
  tertulis di docblock masing-masing, dan yang kedua menunjuk
  `SiteSettingsTest` sebagai jaminan perilaku yang sesungguhnya.

Dua aturan tidak bisa ditulis sebagai arch assertion dan mengatakannya:
namespace yang tidak ada tidak punya daftar kelas untuk di-*assert*, dan plugin
menolak target path. Keduanya ditulis sebagai assertion biasa, bukan dipaksa
menjadi aturan arch yang hanya terlihat seperti hal yang menggantikannya.

### 4. T34: fresh install + seed

Satu-satunya baris test matrix yang belum ter-cover.

`RbacFoundationTest` sudah menguji `SuperAdminSeeder::seedSuperAdmin()` dengan
baik: idempotensi, password lemah, reaktivasi, role, `is_active`. Semuanya
memanggil method itu langsung supaya tidak menyentuh process environment, dan
itu benar di sana.

Yang tertinggal adalah jalur yang dipakai instalasi sungguhan:
`DatabaseSeeder::run()` → `SuperAdminSeeder::run()` → `config('admin.*')`.
Kalau `run()` mulai melempar exception, **semua test yang ada tetap hijau**
sementara `migrate:fresh --seed` rusak di mana-mana. `FreshInstallTest` menutup
itu: admin tercipta aktif dengan password ter-hash dan langsung bisa login,
rantainya menghasilkan 15 permission dan tepat dua pengaturan, dua kali jalan
tidak menggandakan apa pun, dan **semuanya tetap berhasil tanpa kredensial
sama sekali** — itu yang menjaga fresh clone dan CI tetap jalan. `.env` yang
setengah terisi (email ada, password tidak) juga diuji, karena kasus itulah yang
akan menghasilkan akun yang tidak bisa dipakai siapa pun.

"Fresh install" di sini berarti skema yang baru dimigrasikan dan kosong, bukan
drop-all-tables: yang latter akan meratakan transaksi `RefreshDatabase`.

### 5. Penjaga `composer ci:check`

Satu test asserting bahwa `ci:check` masih menyebut setiap gate yang
T29–T33 andalkan. Menghapus `npm run check` dari sana tidak merusak apa pun yang
terlihat: frontend tetap tanpa lint error, TypeScript tetap kompilasi, build
tetap sukses. Satu-satunya buktinya adalah perubahan perilaku yang tidak pernah
terjadi.

Dua hal di dalamnya perlu perhatian. Script Composer berbentuk **array**, bukan
string, jadi casting melempar "Array to string conversion" dan pencarian
substring pada array berarti kesamaan elemen. Assertion urutan membandingkan
offset dengan salinan yang sudah di-*sort*, bukan mengindeks, karena
`array_filter` atas list dua elemen meninggalkan key-nya opsional.

Dokumen gate dibaca dari `docs/roadmap`, bukan `AGENTS.md`, karena yang kedua
**gitignored**: test yang membacanya akan gagal di mesin yang tidak memilikinya
dan lulus di mesin yang punya versi basi.

File ini juga bisa menggagalkan build dengan cara yang lebih mungkin terjadi
daripada mencegahnya: menambahkan gate yang belum dipenuhi siapa pun.

### 6. Yang ditolak

**Frontend test runner tidak ditambahkan**, sesuai keputusan yang sama seperti
saat gap D-26 §7 dibahas. Roadmap §29 hanya menyebut TypeScript, ESLint, dan
build, dan AGENTS.md melarang menambah test runner tanpa persetujuan. Kesenjangan
tersebut tetap tercatat.

> **Dibalik oleh P16 (D-29), atas permintaan eksplisit.** Alasannya berubah:
> ketika tidak ada frontend di Phase 01, tidak ada yang perlu diuji, jadi
>menambah runner adalah biaya tanpa manfaat. Sekarang ada frontend, dan gap
> D-26 §7 terbukti tidak bisa ditutup tanpanya. Ketetapan di bawah tetap
> berlaku: **coverage tetap tidak ditambahkan**, dan E2E tetap di luar gate.

**Code coverage threshold** tidak ditambahkan: tidak diminta roadmap maupun PRD,
dan CI memakai `coverage: none`. **Level PHPStan tetap 7** — tidak ada satu pun
dokumen yang menyebut level, jadi mengubahnya jadi keputusan tanpa dokumen.
**Plugin `profanity`** tidak dipakai: kosmetik.

Satu catatan pengamatan, bukan pekerjaan P13: `laravel/sail` masih ada di
`require-dev` padahal D-15 menghapus Docker. Itu milik butir P01 "remove
starter artifacts".
---

## D-28 — Git workflow: husky, commitlint, dan gate penuh saat commit

**Status:** Accepted · berlaku sejak P14 · branch `feat/phase-01-git`

Roadmap §33 P14 punya empat butir: Husky, Commitlint, Conventional Commits, dan
dua hook. Semuanya **tidak ada** sebelum P14 — tidak ada `.husky/`,
`core.hooksPath` kosong, dan `.git/hooks` hanya berisi contoh bawaan git.

Yang sebenarnya sudah ada adalah **disiplin Conventional Commits**: 42 commit
di PR #1 dan seluruh commit di `main` sudah conforms. Yang belum ada adalah
*paksaannya*.

### 1. `commitlint.config.js` dipakai apa adanya

`extends: ['@commitlint/config-conventional']` dan tidak ada rule tambahan.

Bukan karena malas, tapi karena tidak ada yang perlu dipatuhi ulang: default
`header-max-length` adalah **100**, dan subject terpanjang yang pernah ditulis di
repo ini **83 karakter**. Menambah aturan hanya akan mempersempit gerbang yang
sedang menerima pekerjaan nyata.

Berkasnya **ESM** karena `package.json` mendeklarasikan `"type": "module"`.
`module.exports` di sini adalah galat **pada saat commit**, bukan saat build,
yang berarti ia akan gagal di commit pertama seseorang, bukan di CI tempat
melihatnya murah.

### 2. `npx husky init`, bukan menulis hook sendiri

Nilai `npx husky init` bukan pada apa yang ia tulis, melainkan pada apa yang ia
*tidak* izinkan. Hook yang ditulis tangan akan mengikuti versi. Pada 9.1.7, dua
baris yang muncul di hampir semua tutorial

```
#!/usr/bin/env sh
. "$(dirname -- "$0")/_/husky.sh"
```

sudah **ditolak aktif**: `husky.sh` yang dihasilkan husky berisi pesan bahwa baris
itu WILL FAIL di v10, dan `core.hooksPath` diarahkan ke `.husky/_`.

Hook di `.husky/` karena itu **tanpa shebang** dan tanpa sourcing.

`.husky/_/.gitignore` berisi `*` dan ditulis oleh husky sendiri, jadi
`.gitignore` utama **tidak perlu disentuh**.

### 3. `pre-commit` menjalankan gate penuh, dan ini pilihan yang mahal

```
npx lint-staged || exit 1
composer ci:check
```

Ini **gate penuh**, termasuk 265 test, sekitar **22 detik** secara lokal.

Konsekuensinya, dituliskan karena harus diketahui orang berikutnya:

- **Commit membutuhkan MySQL berjalan.** Test suite memakai database
  `website_paroki_hspmtb_test`. Kontributor yang belum menyiapkan MySQL akan
  gagal commit, bukan hanya gagal CI.
- Commit yang hanya menyentuh dokumentasi tetap membayar 22 detik.
- **Commit bisa gagal setelah file di-fix**, kalau yang merah adalah test.
- `--no-verify` adalah jalan keluar, dan itu juga yang dibutuhkan `git revert`
  karena pesan yang dihasilkannya ditolak.

`|| exit 1` itu wajib, bukan hiasan. Husky tidak menjalankan hook di bawah
`set -e`, jadi tanpa baris itu kegagalan lint-staged akan **diikuti**
`composer ci:check` yang berhasil, dan hook akan keluar dengan 0 pada repo
yang baru saja gagal diformat.

lint-staged jalan lebih dulu supaya hasil perbaikannya **masuk ke dalam commit**,
bukan tertinggal di working tree untuk percobaan berikutnya.

### 4. `lint-staged` untuk PHP dan frontend

Pint menerima path PHP yang di-staged; `vp check --fix` menerima path
frontend. `--no-error-on-unmatched-pattern` mencegah gagal pada file yang tidak
cocok globaunya.

**YAML sengaja tidak dimasukkan.** `vp check` gagal keras pada berkas `.yml`
dengan "Expected at least one target file", jadi menambah pola `*.{yml,yaml}`
akan **merusak** commit, bukan merapikannya. Akibatnya commit yang hanya
menyentuh YAML atau Markdown mencetak "lint-staged could not find any staged
files matching configured tasks" — cosmetics, bukan kegagalan.

### 5. Job commitlint di CI

Hook lokal bisa dilewati `--no-verify`, dan tidak berjalan sama sekali saat
push. Job terpisah menutup keduanya untuk pull request.

Job **terpisah dari `ci`** dengan sengaja: `composer ci:check` adalah gerbang
kode, dan mencampur pemeriksaan riwayat commit ke sana berarti subjek yang salah
ketik menggagalkan job yang sama dengan kegagalan test, sehingga alasan sebenarnya
tenggelam di antara keluaran test.

Job ini **di-guard** dengan `github.event_name == 'pull_request'`, karena
workflow yang sama jalan saat push di mana `github.event.pull_request` tidak
ada. Tanpa guard, setiap push ke `main` akan gagal dengan `base.sha` kosong.
`fetch-depth: 0` diperlukan karena rentangnya dua ujung: clone dangkal tidak punya
merge base, dan `--from` lalu menyelesaikan ke nol — yang commitlint perlakukan
sebagai "tidak ada yang diperiksa", bukan sebagai error.

**Judul pull request juga divalidasi.** Squash merge mengambil pesannya dari
judul PR, jadi repo yang seluruh riwayatnya complies tetap bisa squashed
menjadi satu commit yang tidak. Itu dicek pada PR dari fork, karena judul PR
milik orang lain bukan wewenang kita untuk menegakkan.

### 6. Menemukan masalah pada aturan yang baru dibuat sendiri

Menambahkan validasi judul PR langsung **menggagalkan PR #1 yang sedang
terbuka**: judulnya 119 karakter, melewati batas 100 yang sama. Kalau step itu
tidak diuji lebih dulu, ia akan menggagalkan build atas alasan yang tidak ada
hubungannya dengan kode.

Judulnya diperpendek ke 84 karakter.

### 7. Warning yang dibiarkan, dengan alasannya

Empat commit memicu `footer-leading-blank`. itu **warning**, bukan error, dan
rentang commit tetap keluar dengan exit 0.

Penyebabnya sudah ditelusuri dan **bukan** kesalahan penulisan pesan: commitlint
membaca **baris prose yang di-*wrap* dan mengandung titik dua** sebagai trailer
footer. `foo:` dan `suggests:` mereproduksi peringatan yang sama, dan
`feat(x): a` + satu baris `suggest: ...` sendirian **tidak** —
hanya berhasil bila baris itu melanjutkan baris lain. Artinya peringatan muncul
karena paragraf di-*wrap* pada 72 karakter dan salah satunya mengandung titik dua.

Tidak diperbaiki, karena diperbaiki berarti berhenti menulis paragraf di-*wrap*
dan editorisial yang tidak sebanding dengan warning yang memang tidak memblokir
apa pun.

### 8. Paket

Empat paket, **58 dependensi transitif**, hampir semuanya rantai
conventional-changelog milik commitlint. Itu banyak pohon untuk memeriksa satu
pesan commit, dan itulah biayanya memilih tidak menulis sendiri: alternatifnya
adalah memercayai regex untuk memutuskan seperti apa commit message yang sah,
dan regex itu akan diam-diam salah untuk commit yang cukup panjang.

**Lima advisory `critical` di npm bukan dari perubahan ini.** `shell-quote` 1.9.0
datang bersama `concurrently` dari starter dan masih ada. Diverifikasi
membandingkan lockfile sebelum dan sesudah, bukan diasumsikan.

Paket ditaruh di npm `devDependencies`, bukan Composer `require-dev`: keduanya
adalah tooling Node, repo ini npm-based, dan hook dieksekusi lewat Node saat
commit. Menaruh commitlint di Composer akan menambah bridge tanpa gunanya dan
membuat `composer dev` bergantung pada Node untuk hal yang tidak memerlukannya.

### 9. Verifikasi

Kedua hook diuji dengan **merusaknya secara sengaja**, bukan dengan membacanya:

| Yang dirusak | Hasil |
| --- | --- |
| commit dengan pesan `perbaiki stuff` | ditolak `commit-msg`, **exit 1**, HEAD tidak bergeser |
| test yang sengaja gagal | ditolak `pre-commit`, **exit 1**, HEAD tidak bergeser |
| commit yang baik | lolos, `lint-staged` → `composer ci:check` → 265 hijau |

**Di luar cakupan:** commitizen (CLI interaktif), hook `pre-push`, branch
protection GitHub, dan `PULL_REQUEST_TEMPLATE.md`. Hook `pre-push` sengaja
tidak ada: PR #1 sudah memberi bukti bahwa alur kerja repo ini adalah push
branch ke PR terbuka, dan menambah satu gerbang di sana tidak menambah keamanan
yang berarti.
---

## D-29 — Frontend testing: Vitest lewat vite-plus, Playwright untuk alur nyata

**Status:** Accepted · berlaku sejak P16 · branch `feat/phase-01-testing`

Ini **membalik** keputusan yang tercatat di D-26 §7 dan D-27 §6, yaitu "tidak ada
frontend test runner". Keduanya menuliskan gap itu dengan jujur sebagai konsekuensi
dari pilihan saat itu, jadi membukanya di sini adalah koreksi, bukan tambahan.

Tidak ada frontend di Phase 01, jadi tidak ada test frontend yang perlu dibongkar.

### 1. Unit test tidak memasang Vitest

`vp test` sudah ada di vite-plus dan **adalah** Vitest: `vitest@4.1.11` sudah
terpasang transitif, dan API-nya diambil dari `vite-plus/test`, bukan dari paket
`vitest`. Jadi tidak ada dependensi test runner yang baru — hanya `happy-dom` dan
Testing Library.

Konfigurasinya blok `test` di `vite.config.ts`. Dokumentasi vite-plus secara
eksplisit **tidak** menyarankan `vitest.config.ts`, dan alasannya masuk akal: satu
berkas konfigurasi lebih mudah dibaca daripada dua yang sebagian besar isinya
sama.

> **Diverifikasi, bukan diasumsikan.** Dokumentasi itu menjelaskan vite-plus 1.0
> dengan Vitest 5, sedangkan yang terpasang di sini **0.3.0 dengan Vitest
> 4.1.11**. Blok `test` ternyata diterima, alias `@/` ke-resolve, dan JSX jalan
> tanpa setup tambahan. Tidak ada efek samping: `git status` tetap bersih sesudah
> `vp test`, jadi plugin Wayfinder **tidak** menulis ulang
> `resources/js/routes/` saat test berjalan.

### 2. `tests/` sudah milik Pest, jadi frontend masuk ke dalamnya

Bukan `tests-js/` atau apa pun. Frontend unit ada di `tests/js/`, E2E di
`tests/e2e/`. `composer.json` memetakan PSR-4 `Tests\` → `tests/`, tetapi
autoloader PHP hanya memetakan `.php`, jadi berkas `.ts` di sana tidak mengganggu
apa pun. Satu pohon, satu kebiasaan.

`include` Vitest sengaja hanya `tests/js/**`, dengan `exclude` eksplisit
`tests/e2e/**`. Globe bawaan Vitest adalah `**/*.spec.*`, yang dengan senang hati
akan **mengumpulkan spec Playwright lalu menjalankannya sebagai unit test**. Keduanya
disebut, karena salah satu saja masih membuka jebaknya: `include` yang sempit
melindungi hari ini, `exclude` melindungi kalau suatu saat `include` dilebarkan.

`tsconfig.json` gaining `tests/js`, `tests/e2e`, dan `playwright.config.ts`.
`include`-nya hanya `resources/js/**`, jadi tanpa itu file test berada di luar
**kedua** `tsc --noEmit` dan lint type-aware.

### 3. `globals` dimatikan, jadi cleanup ditulis sendiri

Testing Library hanya membersihkan DOM otomatis ketika globals aktif. Di sini
`globals: false` secara sengaja, jadi `tests/js/setup.ts` memanggil `cleanup()`
sendiri. Tanpa itu setiap file test meninggalkan DOM terpasang, dan `getByRole`
berikutnya bisa mencocokkan elemen milik test sebelumnya — gagal karena alasan
yang keliru.

### 4. Dua fakta yang ditemukan test pertama, bukan dari membaca kode

**`<input type="password">` tidak punya implicit ARIA role.** Password input
sengaja dikeluarkan dari accessibility tree sebagai textbox, jadi
`getByRole('textbox')` tidak akan pernah bisa mencocokkannya, ter-masked atau
tidak. Test pertama sempat gagal karena itu dan terlihat seperti komponen rusak.
Field-nya dicari lewat label, dengan `aria-label` disuplai test karena layar
sesungguhnya memasangkan `PasswordInput` dengan `<InputLabel>` dari luar.

**Toggle password harus `type="button"`.** Tombolnya ada di dalam `<form>` login
tanpa atribut `type`, dan default `<button>` adalah submit — jadi klik pertama
mengirim form, bukan membuka kata sandi.

### 5. Playwright: tiga hal harus sepakat, dan dua dari mereka PHP

Ini bagian yang paling banyak mengubah desain, dan tidak ada yang terlihat dari
konfigurasinya saja.

**`.auth.json` ditulis oleh `app:e2e:prepare`, bukan diisi dua kali.** Email,
password, **dan nama database**. Alasannya adalah kegagalan pertama: `php artisan
serve` membaca koneksinya dari environment, jadi ia berbicara ke database di
`.env`, sementara prepare sudah me-*rebuild* database test. Kredensialnya benar,
loginnya tetap gagal. Kalau spec menyimpan sendiri nama database, tiga hal akan
menyimpang satu per satu dan gejalanya selalu "login gagal" — yang mengarah ke
form login, bukan ke wiring-nya.

**`data-test`, bukan `data-testid`.** Komponen di repo ini menandai hook dengan
`data-test`, sedangkan default Playwright adalah `data-testid`, jadi `getByTestId`
tidak menemukan apa pun. Disesuaikan sekali di `testIdAttribute`, bukan dengan
mengulang `[data-test="..."]` di setiap spec.

**`getByLabel('Password')` ambigu.** Playwright mencocokkan label sebagai
substring, dan tombol toggle punya `aria-label="Show password"` — jadi locator itu
mencocok dua elemen lalu melempar strict-mode violation. Field dipanggil
`input[name="password"]`. Field email kebetulan aman hanya karena toggle berkata
"password", bukan "address".

**`APP_DEBUG=false` dipaksa di web server.** Yang ini menghasilkan **hijau
palsu**. Mesin development punya `APP_DEBUG=true`, dan callback `respond()` di
`bootstrap/app.php` mengembalikan response asli lebih awal dalam kasus itu, sehingga
halaman debug Laravel yang muncul — bukan halaman error Inertia. Status tetap 404
dan teks "404" tetap ada di layar, jadi kedua assertion hijau, terhadap halaman
yang tidak ditulis siapa pun di sini.

Specs sekarang meng-*assert* copy Bahasa Indonesia-nya, yang benar-benar menutup gap
D-26 §7: copy itu sebelumnya hanya diperiksa sebagai prop yang sampai ke React,
yang tidak mengatakan apa pun soal apakah ia sampai ke layar.

### 6. Urutan test, dan kenapa suite bisa hijau sendiri tapi merah bersama

Spec password salah memicu throttle login Fortify. Limiter-nya tinggal di cache,
yang di sini adalah database — state yang dipakai bersama seluruh test dalam satu
run. Akibatnya login yang benar di test berikutnya terkunci: logout test **lolos
sendiri** (1,8 detik) dan **gagal setelah test password salah**, dan berulang kali
lokal akan menahan kunci itu sampai jendela limiter habis.

Tiap test sekarang menjalankan `php artisan cache:clear` lebih dulu, 0,2 detik.
Tidak ada coverage yang hilang: throttling sendiri sudah di-assert di
`tests/Feature/Auth/AuthenticationTest.php`. Yang penting di sini hanya bahwa
test-test ini tidak bergantung pada urutan satu sama lain.

### 7. `app:e2e:prepare` mengambil target dari konfigurasi sendiri

`migrate:fresh` menjatuhkan setiap tabel yang ditemuinya. Perintah ini membaca
`config('e2e.database')` dan **memindahkan koneksinya ke sana lebih dulu**, sebelum
ada langkah destruktif apa pun.

Sengaja **tidak** membaca environment. Dengan begitu `npm run e2e` berperilaku sama
apakah `.env` menunjuk database pengembangan atau tidak, dan langkah destructive
hanya bisa mengikuti konfigurasi suite itu sendiri. Perintah mencetak database yang
diabaikan, jadi keputusannya terlihat, bukan diam-diam.

> **Dua percobaan sebelum modelnya benar.** Versi pertama **menolak** jalan kalau
> `DB_DATABASE` tidak sama dengan database test — lalu saya sadar itu membuat suite
> mustahil dipakai di laptop mana pun yang `.env`-nya menunjuk database dev.
> Protectif, tapi tidak berguna. Yang kedua membalik modelnya: property yang benar
> bukan "menolak konfigurasi salah", melainkan "tidak pernah membaca konfigurasi
> itu".

Test Pest memakai nama database yang **memang tidak ada** untuk membuktikan ini.
Kalau suatu saat perintahnya regresi ke database environment, `migrate:fresh` akan
gagal keras dengan "database tidak ada", alih-alih diam-diam menjatuhkan tabel
milik seseorang — dan kegagalannya akan menyebut bug-nya.

### 8. Yang masuk gate, dan yang tidak

`npm run test:unit` masuk `composer ci:check`, jadi ikut pre-commit: **1,4
detik**. Murah, dan test yang tidak dijalankan tiap commit akan membusuk.

Playwright **tidak** masuk gate. Commit yang memulai web server, me-*rebuild*
database, dan mengunduh browser mengubah perubahan satu baris menjadi beberapa
menit — dan di mesin tanpa Chromium ia **gagal**, bukan dilewati. Gate-nya adalah
job `e2e` di CI.

Kedua hal itu di-assert di `tests/Unit/ContinuousIntegrationScriptTest.php`,
dengan alasan yang sama seperti production build yang dikecualikan di sana: yang
tidak diuji adalah keputusan yang diam-diam berubah.

### 9. `workers: 1`, dan kenapa

`php artisan serve` menjawab satu request pada satu waktu. Worker paralel akan
antre di belakang satu sama lain, dan gejalanya timeout acak pada suite yang
sebenarnya tidak menguji apa pun yang terkait waktu.

Readiness probe memakai `/up`, bukan halaman nyata. `/up` menjawab tanpa menyentuh
database atau manifest Vite, jadi ia probe yang adil: menunggu halaman sungguhan
akan melaporkan server rusak setiap kali frontend-nya memang belum di-build.

### 10. Yang tidak dikerjakan

**Coverage.** Butuh `@vitest/coverage-v8` dan gate baru. Tidak ada yang memintanya,
dan coverage yang tidak pernah gagal diverifikasi hanya menambah waktu ke gate.

**Test komponen di browser sungguhan.** vite-plus sudah membundel Playwright
sebagai provider browser-mode, jadi ini mungkin tanpa dependensi baru — tapi
memaksa setiap developer dan setiap CI run mengunduh Chromium untuk memeriksa
layout dan CSS, sementara suite 10 spec ini belum butuh itu. `--dom` (happy-dom)
yang dipilih untuk itu.

**Mock Inertia.** Komponen yang memakai `router` atau `useForm` belum diuji unit.
Memock router bukan cara memverifikasi runner-nya, jadi itu pekerjaan berikutnya
bila ada yang benar-benar butuh.

### 11. Verifikasi

Kedua suite **dijalankan**, bukan hanya dikonfigurasi. E2E dijalankan empat kali
berturut-turut untuk memastikan tidak ada ketergantungan antar-run pada limiter:
**10 passed dalam 9,3 detik** setiap kali. Gate penuh: **271 test, 870 assertion,
PHPStan 0 error.**

Sebelas test unit dan sepuluh spec E2E sengaja dibuat kecil. Yang diuji adalah
runner, alias, setup, dan kebocoran state antar-test — hal-hal yang tidak bisa
dipastikan dengan membaca konfigurasi.
