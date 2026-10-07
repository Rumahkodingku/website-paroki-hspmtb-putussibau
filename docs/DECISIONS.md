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
diingat menempelkannya ke role. KalauFgayaempel permission-permission
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

## Known divergences (belum diselesaikan)

| # | Deviasi | Risiko / alasan | Rencana |
| --- | --- | --- | --- |
| 1 | ~~`HandleInertiaRequests::share()` mengirim **seluruh model User**.~~ **Ditutup di P07** (D-21). Sekarang hanya `id`, `name`, `email`, `email_verified_at`. Catatan koreksi: yang bocor hanya metadata tidak sensitif — `#[Hidden]` sudah melindungi semua secret, jadi ini pelanggaran kontrak, bukan kebocoran. | — | Selesai |
| 2 | ~~Flash message memakai `Inertia::flash('toast', ...)` + `useFlashToast()`, bukan shared prop `flash`.~~ **Ditutup di P07** (D-21): ini memang mekanisme **Inertia v3** — flash dikirim sebagai event DOM `flash` dan sengaja tidak masuk history state. Hook yang sudah ada benar; tidak ada yang perlu diubah. | — | Selesai |
| 3 | ~~`display_timezone` belum dikirim ke frontend.~~ **Ditutup di P07** (D-21): sudah jadi shared prop `displayTimezone`, bersanding dengan `locale`. | — | Selesai |
| 4 | `Model::preventLazyLoading()` / `shouldBeStrict()` belum aktif. | ARCHITECTURE.md Part A §6 dan PRD §3.3 mewajibkannya. | P13 — setelah Fortify/Passkeys/Settings diaudit |
| 5 | ~~`DatabaseSeeder` masih membuat `test@example.com`.~~ **Ditutup di P03** (D-14). | — | Selesai |
| 6 | ~~`Features::registration()` masih aktif sehingga `/register` publik hidup.~~ **Ditutup di P06** (D-19). | — | Selesai |
| 7 | Tabel `site_settings` sudah ada, tetapi belum ada model, `SiteSettingsService`, cache, maupun halaman `/admin/pengaturan`. | Tidak ada single access point untuk pengaturan situs. | P09 (D-12) |
| 8 | ~~`is_active` ada di skema tapi belum ditegakkan.~~ **Ditutup di P06** (D-20). | — | Selesai |
| 9 | ~~PHP extensions `gd` / `imagick` tidak terpasang.~~ **Ditutup** — `gd` 2.3.3 dengan WebP support sudah terpasang. | — | Selesai |
| 10 | Ekstensi PHP `intl` tidak terpasang. | Belum memblokir apa pun, tapi beberapa library dapat memakainya. | Bila perlu |
| 11 | `super_admin` mendapat akses ke **semua** ability lewat `Gate::before`, termasuk ability yang di masa depan mungkin tidak boleh dimiliki siapa pun. | Otorisasi implisit: tidak ada daftar permission yang bisa dibaca untuk audit. Ini trade-off yang disepakati di D-16, bukan kelalaian. | P06 — kalau muncul ability yang harus dikecualikan, pakai `Gate::after` atau `deny` eksplisit |

---

## Yang masih diperlukan (Phase 01)

P07 Inertia foundation · P08 UI & layout · P09 site settings · P10 media ·
P11 sanitasi · P12 error/SEO/infrastruktur · P13 quality · P14 Git · P15 docs.

P01, P02, P03, P05, dan P06 sudah selesai. P04 (Docker) dibatalkan — lihat D-15.

Autentikasi sudah pindah ke `/admin/*`, registrasi publik sudah dihapus,
`is_active` sudah ditegakkan, dan gerbang `/admin/*` memakai permission
`admin.access`. Lihat D-19 dan D-20.

Fondasi RBAC sudah aktif: `spatie/laravel-permission` **8.3.0** terpasang,
`HasRoles` pada `User`, role `super_admin` + 12 permission ter-seed, dan
`Gate::before` di `AppServiceProvider`. Lihat D-16, D-17, D-18.

Daftar 12 permission yang sudah di-seed (11 dari §10.4 + `admin.access`) tercatat di D-18 dan D-19.

Permission per modul lain (agenda, galeri, komunitas, jadwal-misa, dst.)
dibuat saat modulnya dibangun, supaya tidak perlu migration tambahan sekarang.
