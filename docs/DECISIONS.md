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

**Status:** Accepted · dieksekusi pada P06/P07 · **page dipindah lagi di P18**

> **Digarahkan oleh D-30.** `resources/js/pages/` sudah tidak ada; page kini
> tinggal di `features/<audience>/<feature>/pages/` dengan `index.tsx` berarti
> fitur itu sendiri. Paragraf di bawah dipertahankan sebagai catatan alasan pemilihan
> lowercase.

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

## Status

Phase 01 selesai; **P04 (Docker) tetap dibatalkan** atas permintaan PRD (D-15).

**Sudah selesai:** seluruh Phase 01 (P01–P17), kecuali **P04 (Docker) yang
dibatalkan** — lihat D-15. Status per butir tercatat di
`docs/roadmap/phase-01-project-foundation.md` §33, dan cara setiap item
Definition of Done diverifikasi tercatat di §37.

Test matrix: **semua 34 baris ter-cover**, ditambah 11 test frontend (`tests/js/`)
dan 13 spec E2E (`tests/e2e/`).

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

> **Diperbarui D-50.** `style` kini diizinkan pada elemen blok, tetapi hanya
> untuk `text-align`; setiap deklarasi lain dibuang **sebelum** allowlist
> berlaku. `class` dan `id` tetap dilarang.

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

> **Diperbarui D-50.** Keempat tag di atas kini aktif, dan image serta table ada,
> setelah allowlist diperluas. Image memakai pipeline media yang kini memiliki
> pickernya.

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

#### 5a. `inertia.ssr.enabled` ternyata `true`, dan itu bukan keputusan kita

Scaffold Laravel 13 / Inertia 3 menulis `'enabled' => true` di
`config/inertia.php`, dengan `'bundle'` masih dikomentari. `HttpGateway::ssrIsEnabled()`
juga default ke `true` kalau kuncinya tidak ada. Jadi "SSR dimatikan" di §5 ini
hanya berlaku karena kebetulan, bukan karena ada yang mengatakannya.

Kebetulan itu cukup untuk production, dan tidak cukup untuk development:

- Tanpa `public/hot`, `HttpGateway::dispatch()` gagal di cek bundle
  (`bootstrap/ssr/ssr.mjs` tidak pernah dibangun), return `null`, dan slot
  `<x-inertia::head>` dirender seperti biasa.
- Dengan `public/hot` — yaitu ketika `composer dev` sedang berjalan — cek bundle
  dilewati, gateway POST ke `/__inertia_ssr` milik dev server, dan
  `Inertia\View\Components\Head::render()` mengganti slot dengan
  `{!! $response->head !!}` yang **kosong**.

Akibatnya setiap `og:*`, `rel="canonical"`, `name="twitter:card"` dan `<title>`
lenyap dari respons HTML awal, **hanya di development**. Produksi tidak pernah
menyentuh jalur itu, jadi bug-nya tak terlihat di produksi dan muncul sebagai
`InfrastructureFoundationTest::open graph tags are present in the initial html
response` yang gagal tepat ketika gate dijalankan bersamaan dengan `composer dev`.
itu kelas bug yang paling Mahal: dev dan production menampilkan head yang berbeda,
dan yang berbeda itu justru SEO.

Diperbaiki dengan `'enabled' => filter_var(env('INERTIA_SSR_ENABLED', false), FILTER_VALIDATE_BOOL)`.
`filter_var` dipakai karena `phpunit.xml` men-set variabel itu dan PHPUnit menulis
nilai `"false"` sebagai **string kosong**, bukan boolean — `env()` polos akan
menghasilkan `""`, yang falsy sehingga Gateway benar, tapi menyesatkan saat
dibaca. Sekarang unset, kosong, dan `"false"` semuanya benar-benar `false`.

`phpunit.xml` mengunci `INERTIA_SSR_ENABLED=false` supaya suite tidak pernah
bergantung pada `.env` lokal, dan test baru
`server side rendering stays off so blade owns the head` mengunci kedua sisi:
nilai config-nya, dan bahwa `SsrState::dispatch()` mengembalikan `null`.

Kalau SSR diaktifkan di phase UI, `public/hot` harus dihapus dari penyebab
peserta bug di atas, bukan hanya menyalakan flag.

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

---

## D-30 — Pages pindah ke dalam `features/`, dan empat hal yang harus sepakat

**Status:** Accepted · berlaku sejak P18 · branch `feat/phase-01-pages-in-features`

`resources/js/pages/` dihapus. Page kini tinggal di
`features/<audience>/<feature>/pages/`, dan nama page-nya adalah
`<audience>/<feature>/<path di bawah pages/>` — dengan satu pengecualian yang
menghapus seluruh sisa redundansi:

> **`index.tsx` berarti fitur itu sendiri.**

```text
admin/dashboard/pages/index.tsx      → 'admin/dashboard'
admin/pengaturan/pages/index.tsx    → 'admin/pengaturan'
admin/akun/pages/profile.tsx        → 'admin/akun/profile'
public/auth/pages/login.tsx         → 'public/auth/login'
public/beranda/pages/index.tsx      → 'public/beranda'
```

Tanpa aturan itu, `admin/pengaturan` harusnya punya file
`pages/admin/pengaturan.tsx` — URL-nya diulang dua kali di path yang sama.

**Dua belas nama page, sebelas tidak berubah.** Hanya `welcome` → `public/beranda`
yang berubah, di `routes/web.php`, satu `case` di `app.tsx`, dan satu assertion.
Itu sebabnya nama tidak ikut diratakan: nama page masuk ke aplikasi sebagai
string dari controller, jadi mengubahnya berarti menyentuh `Inertia::render()` di
seluruh controller, konfigurasi Fortify, `bootstrap/app.php`, dan setiap test
yang meng-`component()`.

### 1. `pages/` adalah kontrak, bukan preferensi

Alasan yang diberikan: "supaya lebih mudah mencari file terkait". Yang sebenarnya
terjadi adalah `resources/js/pages/` dikunci di **empat** tempat:

```text
1. @inertiajs/vite    DEFAULT_PAGE_DIRECTORIES = ['./pages', './Pages']
2. app.blade.php      "resources/js/pages/{$page['component']}.tsx" di dalam @vite()
3. inertia view finder  dipakai Inertia saat render dan di assertInertia()->component()
4. tests              lewat no. 3
```

Nomor 1 adalah suntikan otomatis. Nomor 2 itu preloading chunk — diverifikasi,
bukan diasumsikan: `login-*.js` memang muncul di HTML, dan harus tetap muncul.

### 2. Dua default framework harus ditulis ulang

**Suntikan resolver `@inertiajs/vite`.** Opsi `pages:` tidak menolong:

```js
glob   = './features/*/pages/**/*.{tsx,jsx}'      // ✓ glob wildcard jalan
lookup = pages['./features/*/pages/${name}.tsx']  // ✗ kunci literal dengan *
```

Kunci lookup berisi `*` yang tidak pernah menjadi path asli. Tidak pernah cocok,
tanpa error — hanya `Page not found` saat runtime. Jadi `app.tsx` supplying
resolver sendiri, dan plugin berhenti menyuntik begitu `resolve` ada.

**`FileViewFinder` Laravel.** Ini yang paling diam-diam, dan baru ketahuan saat
sudah dikerjakan: ia hanya menempelkan nama onto direktori
(`<configured path>/<name>.<ext>`). NiAo prefiks audience/fitur maupun
`index.tsx` bisa lewat itu. Gejalanya **bukan** gagal saat boot, melainkan dua
belas pesan `Inertia page component file [x] does not exist` — satu untuk tiap
test yang merender page, dan tidak satu pun menyebut konfigurasinya.

Maka `AppServiceProvider` mengikat `inertia.view-finder` ke
`App\Support\PageFinder`, yang memakai `PageChunk`.

Konsekuensi yang diserah dengan sadar: `config('inertia.pages.paths')` **tidak
lagi dibaca siapa pun**. Daftar direktorinya tetap akurat — kalau override
sewaktu-ever dihapus, finder biasa akan menunjuk direktori yang benar dan gagal
dengan nama seperti `admin/akun/profile`, yang jauh lebih berguna daripada path
basi.

### 3. `PageChunk` tidak bisa dihilangkan

Suntingan Blade dulu string interpolasi. Vite menandai page sebagai **dynamic
entry**, dan dynamic entry hanya jadi `<link rel="modulepreload">` kalau
disebutkan di `@vite()`.

Awalnya `PageChunk::source()` memakai `glob()` dengan wildcard. Itu benar selama
namanya masih path, dan **salah begitu file berhenti mengulang URL** — glob
melewati dua segmen audiens+fitur, lalu untuk nama yang tidak ketemu ia
mengembalikan `index.tsx` **yang pertama ditemukan**, milik fitur lain. Tidak
error, hanya page yang salah. Sekarang nama menentukan path sepenuhnya, jadi
`explode` saja cukup dan tanpa wildcard sama sekali.

### 4. Empat pembaca, satu aturan

`lib/page-name.ts` (path → nama), `PageFinder` (nama → path, untuk Inertia),
`PageChunk` (nama → path, untuk Blade), dan `app.blade.php` yang memanggilnya.
Tiga yang terakhir berbagi `PageChunk`, jadi aturannya benar-benar satu.
`ArchitectureTest.php` membandingkan kedua arah untuk **setiap** page di
aplikasi, dan dua diuji dengan sengaja dilanggar.

### 5. Yang hanya bisa ditangkap satu test

**Tabrakan nama.** Frontend mengindeks page dengan nama turunan path-nya. Dua
fitur dengan page bernama turunan sama akan saling menimpa: tanpa error build,
tanpa request gagal, dan fitur yang kalah me-render page milik fitur lain.

Hari ini mustahil secara struktural, karena nama memuat audiens dan fitur — tapi
tetap jadi test, bukan catatan, karena itu berhenti benar begitu level ketiga
muncul (`features/admin/settings/akun`).

### 6. Kesalahan yang muncul saat mengerjakan

- `PageChunk::source()` mengembalikan path absolut; `@vite()` butuh relatif root.
- `resolve` mengembalikan `Promise<{default}>`, dan `ComponentResolver` hanya
  menerima `Promise<ReactComponent>` **atau** objek modul secara sinkron — bukan
  promise dari objek modul.
- Fallback `index.tsx` wildcard mengembalikan index milik fitur yang salah, dan
  kembali ke `substr_count($name, '/')` sebagai penjaga.
- **Tiga kali** menulis glob di dalam docblock PHP menutup blok comment itu
  sendiri di `*/`. Yang pertama di `app.tsx`, yang kedua di
  `PageChunkTest`, yang ketiga di `PageChunk`. Sintaksnya rusak, jadi build
  menangkapnya — tapi ketiganya setelah yang kedua dianggap selesai.
- Test arch "no two features claim the same page name" meledak jadi 33 kegagalan
  begitu `index.tsx` masuk: dua fitur sama-sama punya `index.tsx`, keduanya
  terbaca bernama `index`. False positive yang hanya terlihat setelah dijalankan.

### 7. Komponen boleh hidup di bawah `pages/`

**Status:** Accepted · berlaku sejak P04 · branch `feat/phase-04-mass-schedule`

Rapikan Modul Jadwal Misa menaruh tiap halaman daftar di foldernya sendiri,
supaya `tambah.tsx` dan `ubah.tsx` punya tetangga:

```text
pages/lokasi.tsx + pages/lokasi/tambah.tsx   →  pages/lokasi/index.tsx + pages/lokasi/tambah.tsx
```

Yang berubah adalah **nama page ikut pindah**, bukan nama yang disembunyikan:

> `pages/lokasi/index.tsx` bernama `admin/jadwal-misa/lokasi/index`.

Ini yang membuat aturan D-30 tetap berlaku tanpa tambahan apa pun — nama itu
memang path-nya. Alasannya menolak cara lain, yaitu menamai file itu
`admin/jadwal-misa/lokasi` saja: satu file jadi punya dua nama, dan peta
`pagesByName` menyimpan satu entri per nama, jadi yang kalah dibuang tanpa error
build. Selain itu, mengorbankan aturan yang berlaku untuk 44 page lain demi 4
halaman. Sibling-nya `lokasi/tambah` dan `lokasi/ubah` sudah berupa path biasa
di folder yang sama, jadi tidak ada yang perlu dijelaskan ulang.

Yang benar-benar rusak dari perpindahan ini ada di tempat lain: `PageChunk` dan
`pageName` sama-sama membaca pola `pages/<apa saja>.tsx`, sehingga
`pages/lokasi/components/` ikut terbaca sebagai page. Lima komponen form —
`location-form`, `location-cards`, `mass-schedule-form`, `special-mass-form`,
`schedule-notice-form` — mendaftarkan diri di bawah nama yang tak pernah
ditanyakan controller. Keduanya kini menyaring segment `components/`.

Bukan `components/` sebagai nama sakral, melainkan daftar
(`PageChunk::NON_PAGE_DIRECTORIES`): menambahkan `hooks/` berarti satu baris di
dua tempat.

`pageName` dipindah dari `app.tsx` ke `resources/js/lib/page-name.ts` supaya bisa
diuji: mengimpor `app.tsx` dari test akan menjalankan `createInertiaApp()`
berserta seluruh efek sampingnya.


### 8. Kesalahan yang muncul saat mengerjakan aturan ini

- Klaim "lima komponen" meledak jadi lima, bukan empat — `location-cards` ikut
  terhitung. hitungan yang salah hampir sama dengan benar, dan hanya
  terlihat karena `ArchitectureTest` walking daftar page.
- Test JS pertama memperkilir `pageName()` untuk path diakhiri garis miring.
  Glob tidak pernah menghasilkannya, jadi test itu mengunci perilaku untuk input
  yang mustahil, bukan aturan yang benar.

---

## D-31 — Public website shell Phase 03, dan 10 route sementara

**Status:** Accepted · berlaku sejak P03 · branch
`feat/phase-03-design-system-public-website-shell`

Phase 03 membangun shell publik: design token elevation, komponen bersama,
`PublicLayout` yang sudah jadi shell tunggal, navbar dan footer yang bisa
diklik, 23 route placeholder, dan fondasi SEO. Yang tersisa di dokumen ini adalah
apa yang **sengaja tidak** diselesaikan, dan kenapa.

### 1. Route publik dibangun sebagai placeholder

PRD §5.1 mendaftarkan 23 URL. Semuanya dibuat sekarang, tanpa query, props,
controller, atau CRUD — roadmap §10 mengizinkan placeholder untuk pengujian
navigasi dan melarang sisanya. Sub-halaman profil menjadi lima halaman statis
karena PRD §5.1 memberi masing-masing URL sendiri dan bookmark harus tetap bekerja.

Teks placeholder ditulis di page React, bukan di PHP atau Blade. Kalimat seperti
`[ISI: halaman ini diimplementasikan pada Fase Profil]` hanya bisa jujur berada di
tempat yang jelas sedang menampilkan placeholder; di sisi server ia akan
terkirim sebagai konten.

### 2. XC-E3 ditunda, bukan dianggap selesai

Selama route detail placeholder hidup, `/berita/{slug}` mengembalikan **200 untuk
slug valid apa pun**. PRD XC-E3 (MUST) mensyaratkan 404 bagi konten draf atau
belum terbit.

Ini penundaan bertanggal, bukan ketidaksengaja:

| Route | Fase yang menggantinya |
| --- | --- |
| `berita.show` | Fase 4 |
| `agenda.show`, `agenda.ics` | Fase 4 |
| `pelayanan.show` | Fase 5 |
| `komunitas.show` | Fase 5 |
| `galeri.show` | Fase 6 |

Setiap penggantian **harus** memakai route dengan model binding, sehingga slug yang
tidak ada kembali 404 secara otomatis.

`tests/Feature/Public/PublicPlaceholderRoutesTest.php` mengunci daftar route ini.
Kalau satu route placeholder hilang tanpa diganti, test gagal dan pesannya
menyebut D-31 — supaya penghapusan yang disengaja selalu disertai penggantiannya.

### 3. `.ics` dan `/unduh` mengembalikan placeholder, bukan 501

`/agenda/{slug}/ics` mengembalikan `VCALENDAR` minimal tanpa `VTIMEZONE` dan tanpa
`VEVENT`; `/download/{id}/unduh` mengembalikan body teks tanpa counter dan tanpa
nama berkas. Bentuk respons (status dan content-type) benar agar bisa diuji, dan
`Content-Type: text/calendar` dengan line ending CRLF diuji secara terpisah karena
RFC 5545 mensyaratkannya.

Logika domain tetap milik fase masing-masing: konversi WIB dan pembuatan kalender
adalah bisnis Agenda; counter unduhan dan nama berkas adalah bisnis Download.
Respons 501 ditolak karena menghasilkan halaman error dan bertabrakan dengan AC
§10 "Navigation tidak menghasilkan broken route".

### 4. Halaman detail punya `show.tsx` masing-masing

Bukan satu komponen placeholder bersama. ARCHITECTURE.md Part B §4 melarang
satu feature menyentuh folder feature lain; placeholder bersama akan terhapus
bersama empat route lain begitu modul pertama mulai hidup.

### 5. `/design-system` adalah route di luar PRD §5.1

Satu-satunya route yang tidak ada di PRD: satu reference page untuk membuktikan
token, primitive, komponen bersama, layout, responsif, dan aksesibilitas
bekerja bersama. `noindex,follow`, dan **dapat diakses di setiap environment**
supaya bisa diuji — route yang digate environment tidak bisa dicakup Pest maupun
Playwright. Dihapus ketika shell publik sudah stabil.

### 6. Urutan menu mengikuti DESIGN.md, bukan PRD §5.3

DESIGN.md §576: `… Galeri, Download, Kontak`, label "Berita & Artikel".
PRD §5.3: `… Galeri, Kontak, Download`, label "Berita". Implementasi mengikuti
DESIGN.md karena itu yang sudah ada sejak Phase 01, jadi perubahannya nol. Divergensi
dicatat, bukan diperbaiki.

### 7. Footer memakai placeholder, bukan site settings

Hanya `parish_name` yang dirender dari shared prop `seo.siteName`, karena itu
satu-satunya nilai yang ter-seed (PRD Lampiran D). `contact_*`, `social_*`, dan
`maps_*` tetap `[ISI: …]`, dan **tidak** dirender sebagai link mati: tautan ke
saluran yang mungkin tidak ada lebih buruk daripada tidak ada tautan, karena
pengunjung baru menyadarinya setelah menekan.

`HandleInertiaRequests` tidak diperluas. Membaca setting yang belum terisi akan
merender string kosong di tempat alamat seharusnya ada, yang terbaca sebagai situs
rusak — bukan situs yang belum dikonfigurasi.

### 8. Logo navbar memakai aset statis

`public/logo.svg`, bukan `site_settings.logo`, karena image picker D-24 belum
ada. Saat D-24 selesai, navbar harus pindah ke setting.

### 9. Error page dirender tanpa shared props

Ini ditemukan karena `ParishFooter` mulai membaca `props.seo.siteName`, dan
seluruh halaman 404 jadi putih.

Shared props didaftarkan oleh `HandleInertiaRequests`, yang ada di middleware group
`web` — dan **routing berjalan sebelum middleware**. Permintaan ke URL yang tidak
dikenal melempar `NotFoundHttpException` sebelum pendaftaran itu terjadi, lalu
handler merender halaman error di luar pipeline. Isi responsnya persis
`{"status":404}`: tanpa `auth`, tanpa `locale`, tanpa `seo`.

Halaman error memakai `PublicLayout`, jadi navbar dan footer berada di halaman
yang **tidak punya shared props sama sekali**. Aturan yang lahir dari ini: komponen
di dalam shell publik boleh tidak menemukan shared prop.

`tests/Feature/Foundation/ErrorPageTest.php` mengunci sifat itu sebagai test,
dengan kontras ke halaman yang melewati middleware.

### 10. `BreadcrumbItem.href` jadi opsional

Admin selalu menyelesaikan breadcrumb dengan halaman yang punya link, jadi `href`
wajib benar sampai halaman publik pertama memakai `Breadcrumbs` — dengan crumb
terakhir berupa halaman yang sedang dibuka. `href?` lebih jujur: item terakhir
dirender sebagai teks, dan `Breadcrumbs` memperlakukan href yang hilang sebagai
"halaman ini", bukan sebagai tautan ke string kosong.

### 11. Kebutuhan variant Button sudah terpenuhi

`ui/button.tsx` sudah memetakan `primary / secondary / navy / gold / icon /
utility`. Tidak ada primitive baru. `ParishCta` membungkus slot sekundernya dalam
outline Button sehingga secara struktural tidak mungkin menjadi primary kedua.

### 12. Arch test bersifat tekstual, termasuk untuk prosa

Tiga asersi baru memindai isi file, bukan AST. Dua kali docblock saya sendiri yang
mematikannya: satu menyebut nama API HTML mentah yang dilarang, satu lagi menulis
bentuk `href` yang tepat dilarang. Keduanya ditulis ulang tanpa nama tersebut.

Konsekuensinya dicatat, bukan disembunyikan: file yang memperkenalkan pola terlarang
tidak bisa sekaligus menjelaskan mengapa pola itu salah, dan file yang menjelaskan
kenapa pola itu salah bisa ikut gagal. Keduanya diterima.

### 13. Kesalahan yang muncul saat mengerjakan

- **`Route::inertia()` tidak meneruskan parameter route.** Kelima halaman detail
  ditulis `function Show({ slug }: { slug: string })` dan akan menerima
  `undefined` saat runtime — tipenya berbohong. Diganti closure yang
  meneruskan `slug` secara eksplisit ke `Inertia::render`.
- **`<img>` di navbar membuat Vitest mencetak `ECONNRESET`.** happy-dom memuat
  sub-resource yang ia temukan di DOM, dan tidak tahu bahwa test komponen hanya
  peduli pada markup. Tidak ada pengaturan happy-dom untuk mematikan pemuatan
  gambar; konfigurasi yang dicoba tidak mengubahnya. Exit code tetap 0, jadi ini
  kosmetik dan tercatat di sini agar tidak dicari ulang.
- **`getByText` gagal saat dua text node berdekatan.** Dua label tombol dalam satu
  `div` terbaca sebagai satu string, jadi query harus berbasis role.
- `assertNotRedirect()` tidak ada di Laravel ini; `assertOk()` sudah cukup.

### 14. Gap yang ditumpuk, bukan diselesaikan Phase 03

- **Footer legal link.** `privacy_policy_content` ada dan tersanitasi (D-25),
  tetapi PRD §5.1 tidak punya route `/kebijakan-privasi`.
- **`sitemap.xml` + `robots.txt`.** PRD §13 menugaskan ke Fase 4 dan roadmap §20
  melarangnya di sini. `public/robots.txt` yang ada adalah file statis.
- **Open Graph bersyarat.** `og:title` dan `og:description` hanya dirender blade
  bila `seo_default_title` dan `seo_default_description` terisi, dan tidak ada
  seeding (D-12). Dua pengujian di `PublicRoutesTest` menutup kedua arah.
- **Item styling-only** (token, radius, shadow, tipografi responsif, kontras)
  tidak di-automate. `tests rules` AGENTS.md menyatakan perubahan styling murni
  tidak memerlukan test; itu dipilih atas "setiap AC harus punya test", dan
  alasannya ditulis di §26 phase record.

---

## D-32 — Design system v1.1: dark mode netral, dan pemisahan merah

**Status:** Accepted · berlaku sejak revisi DESIGN.md v1.1 · branch
`feat/phase-03-design-system-public-website-shell`

DESIGN.md naik ke versi 1.1. Perubahan terbesarnya bukan warna baru, melainkan
penegasan bahwa dark mode yang ada sebelumnya **tidak boleh dipakai**: navy sebagai kanvas
global, dan navy-di-atas-navy, itu persis yang D-22 §3 buat dan
dinyatakan masih perlu konfirmasi paroki. Dokumen sekarang mendefinisikan dark
mode sebagai tema netral-charcoal dan menyebut hasilnya sebagai sesuatu yang
*deprecated*.

**D-22 §3 superseded.** Palet dark tidak lagi diturunkan dari ramp navy dengan
`color-mix()`. Seluruh nilai sekarang berasal dari empat belas token `dark-*`
yang dokumen definisikan, dan tidak ada satu pun `color-mix()` di blok `.dark`.
Ini memenuhi aturan yangolmendokumen nyatakan eksplisit: *"Do not use HSPMTB
Navy as the global page background."*

**D-22 §4 superseded.** `colors.red-light` yang tadinya tidak pernah
didefinisikan kini bernama `red-soft` (#FDE7E9), dengan pasangan gelap
`red-soft-dark` (#3A171A). Token turunan `--color-primary-light` dihapus;
menyimpan nama turunan untuk warna yang sudah didokumentasikan adalah cara
palet mulai melenceng.

### 1. Merah harus dipisah antara fill dan teks

Ini keputusan paling menentukan di revisi ini, dan ia tidak bisa diambil
hanya dengan membaca dokumen.

| Pasangan di dark mode | Rasio | Status |
| --- | --- | --- |
| `text-primary` #AB020E di atas dark-canvas | 2.28:1 | gagal AA |
| `text-primary` #AB020E di atas dark-surface | 1.89:1 | gagal AA |
| `destructive` #AB020E di atas red-soft-dark | 2.08:1 | gagal AA |
| ring #C51624 di atas dark-canvas | 2.92:1 | gagal SC 2.4.11 |

`semantic.dark.primary` adalah `primary` (#AB020E). Nilai itu **benar** untuk
tombol terisi — putih di atasnya 7.65:1, dan merah merek memang harus tetap
menjadi sinyal aksi. Nilai itu **salah** sebagai teks di permukaan gelap, karena
merah tua tidak punya luminansi yang bisa tersisa di atas permukaan gelap.

Maka `--primary` dan `--destructive` di dark mengikuti dokumen, dan satu token
baru `--red-on-surface` (**#FF6670** = `colors.primary-on-dark`) membawa merah
ketika dipakai sebagai teks atau sebagai garis batas.

Presedennya persis sama dengan D-22 §2: `accent-foreground` yang secara literal
dokumen tunjuk ke `gold-dark` diganti `navy-dark` karena pasangan aslinya
2.34:1. Bedanya hanya bahwa nilai penggantinya benar-benar ada di dokumen.

Token itu dipakai di navbar (menu aktif), footer, eyebrow section heading,
link `.rich-text`, `input-error`, dan varian `outline` serta `link` pada Button.

### 2. Cincin fokus di dark

`semantic.dark.ring` menunjuk ke `primary-focus` (#C51624), yang **lebih gelap**
daripada kanvasnya sendiri: 2.92:1. Itu persis kebalikan dari tujuan cincin
fokus. Diganti `primary-on-dark` (#FF6670) → 6.14:1.

Preseden sama seperti di atas. `--color-primary-focus` tetap ada sebagai token
merek; hanya perannya di dark mode yang hilang.

### 3. Fill tombol utama 2.28:1 terhadap halaman — bukan kegagalan

Terverifikasi dan **sengaja diterima**. Tombol terisi teridentifikasi oleh
**labelnya** (putih di atas merah, 7.65:1), bukan oleh warna isinya, jadi syarat
3:1 WCAG 1.4.11 tidak berlaku untuk fill terhadap halaman. Yang wajib 3:1
adalah *border* — dan border outline memakai `red-on-surface`, bukan
`primary`.

### 4. Canvas admin tidak bisa berbagi `body` dengan canvas publik

DESIGN.md v1.1 memberi dua kanvas halaman: publik `canvas` (putih) dan app
canvas admin `canvas-soft` (abu). Keduanya sharing satu elemen `body`, jadi
satu token tidak bisa melayani keduanya tanpa membuat halaman publik salah
warna.

`--background` **tetap** `canvas`, sesuai `semantic.background`. Tabel
`Admin Surface Hierarchy` diterapkan di shell admin (`app-shell.tsx`), karena
bagian itu memang urusan admin — dokumen memang menaruhnya di
"Admin Appearance & Theme", bukan di blok semantic global.

Varian `header` tidak dapat `canvas-soft`: tabel yang sama memberi header admin
warna `canvas`.

### 5. Sidebar aktif jadi merah, dan hover ikut merah

`--sidebar-accent` berubah dari `navy-light` menjadi `red-soft`, dan
`--sidebar-accent-foreground` dari `navy-dark` menjadi `primary`. DESIGN.md:
*"The active item uses HSPMTB Red, a subtle red-tinted background."*

Konsekuensinya dicatat, bukan disembunyikan: `ui/sidebar.tsx` memakai pasangan
yang sama untuk `hover:` **dan** untuk `data-[active=true]:`, dan keduanya tidak
bisa dipisahkan tanpa mengedit file generated. Jadi hover baris sidebar ikut
merah. Itu sesuai merek — merah adalah warna interaktif di seluruh sistem — tapi
perubahan visualnya nyata dan perlu dilihat, bukan ditemukan kemudian.

### 6. Komponen yang harus berubah, dan alasannya

| File | Perubahan | Rasio sebelum → sesudah |
| --- | --- | --- |
| `parish-card.tsx` | varian `agenda` pakai `secondary` | 1.04:1 → 11.09:1 |
| `parish-image.tsx` | fallback `bg-secondary` | 1.04:1 → 7.63:1 |
| `parish-navbar.tsx` | `border-border` sesuai `global-nav` | — |
| `ui/alert.tsx` | `text-red-on-surface` | 2.08:1 → 5.61:1 |
| `ui/button.tsx` | `outline` dan `link` pakai `red-on-surface` | 2.28:1 → 6.14:1 |
| `error/pages/index.tsx` | `bg-red-soft text-red-on-surface` | — |
| `app-shell.tsx` | kanvas `canvas-soft` | — |
| `app-logo.tsx` | hapus `dark:text-black` | — |
| `appearance-tabs.tsx` | hapus `dark:bg-secondary`, selected `bg-card` | 1.11:1 → 1.42:1 |
| `app.css` `.rich-text blockquote` | `dark:text-navy-light` | 1.25:1 → 15.24:1 |

`ParishCard` dan `ParishImage` paling parah: keduanya memakai literal
`bg-navy-light`, yang di dark mode menjadi permukaan nyaris putih dengan teks
*nyaris* putih — 1.04:1, Placeholder yang tidak terlihat. `secondary` adalah
`navy-light` di light mode, jadi tidak ada perubahan visual sama sekali di sana.

### 7. `--sidebar-accent` dan teks yang tidak berbalik

`--color-navy`, `--color-ink`, `--color-ink-muted`, `--color-ink-muted-strong`
adalah warna merek yang **tidak** berubah antar tema. Dipakai sebagai teks di
dark mode, mereka gagal:

| | dark-canvas |
| --- | --- |
| `text-ink` | 1.02:1 |
| `text-navy` | 1.25:1 |
| `text-ink-muted-strong` | 2.27:1 |
| `text-ink-muted` | 3.51:1 |

Ini **bukan** regresi yang diperkenalkan revisi ini — token tersebut sudah
seperti itu sejak Phase 01 — tapi dark mode v1.1 membuatnya terlihat, karena
sebelumnya kanvasnya navy dan kontrasnya kebetulan lebih tinggi. Semua call site
yang bermasalah sudah memakai pasangan `dark:` (`profile.tsx`,
`login.tsx`, `forgot-password.tsx`, `verify-email.tsx`) kecuali satu di
`design-system/pages/index.tsx`. Daftar lengkapnya belum diaudit dan dicatat
sebagai pekerjaan lanjutan, bukan diperbaiki diam-diam di sini.

### 8. Tombol tema publik

Permintaan tambahan: satu tombol light/dark di navigasi publik.

Dua keadaan, bukan tiga. `theme.supported-modes` mencantumkan Light/Dark/System,
tetapi "System" di navbar publik adalah preferensi yang tidak diketahui
pengunjung — dan pengunjung dengan sistem gelap yang ingin tampilan terang
adalah justru yang akan hilang dari situs yang hanya mengikuti sistem. Klik
menetapkan pilihan secara eksplisit.

Nama aksesibelnya tetap ("Mode gelap") dengan `aria-pressed` yang menandai
status. Bentuk lain — label yang berubah mengikuti status, "Aktifkan mode
gelap" lalu "Aktifkan mode terang" — terbaca sebagai kontrol berbeda setiap kali
dan tidak memberi pengguna screen reader cara bertanya apa yang sedang aktif
tanpa harus-pressed.

Penyimpanan tetap dua tempat: `localStorage` untuk klien, cookie untuk
`HandleAppearance` dan `app.blade.php`, sehingga pilihan bertahan setelah
*pemuatan ulang* dan bukan hanya setelah navigasi Inertia. Ini diuji di E2E
karena assertion yang hanya berjalan di sisi klien tidak akan menangkapnya.

### 9. Hasil verifikasi kontras

45 pasangan dihitung dari `app.css` yang dibaca ulang oleh skrip, bukan dari
daftar yang diketik manual: setiap pasangan foreground/background di `:root` dan `.dark`,
termasuk cincin fokus, batas, dan label tombol.

Semua lolos. Yang terendah di dark mode adalah `red-on-surface` di atas
`popover` pada 4.55:1 — cukup untuk teks normal, dan `popover` sudah
merupakan permukaan yang dinaikkan.

Border dan langkah permukaan sengaja lolos di bawah 3:1 (1.71:1 dan 1.20:1):
DESIGN.md meminta "clear but quiet borders" dan "one surface step above the
page", dan itu memang kontras yang rendah.

### 10. Yang belum dikerjakan

- **`ui/checkbox.tsx`, `input.tsx`, `textarea.tsx`, `select.tsx`, `toggle.tsx`,
  `badge.tsx`, `dropdown-menu.tsx`** memakai `border-destructive` untuk state
  `aria-invalid`. Di dark mode `destructive` kini #AB020E, jadi border tersebut
  2.28:1. Semuanya perlu `red-on-surface`, dan semuanya file generated yang
  tidak termasuk daftar yang AGENTS.md acknowledge sebagai restyled.
- **`use-appearance.tsx` masih default `system`.** DESIGN.md §Theme Switching:
  *"If no preference exists, default to light"* dan `light-priority: true`.
  Ini perubahan perilaku, bukan styling, dan tidak dikerjakan di sini.
- **Review visual dua tema** di lima halaman admin dan lima halaman publik
  belum dilakukan — yang di atas adalah angka, dan angka tidak sama dengan
  mata.

---

## D-33 — `Text`: satu komponen tipografi, dan satu perbaikan `cn()`

**Status:** Accepted · Phase 03

`resources/js/components/text.tsx` menjadi satu-satunya cara memilih tipografi
di seluruh aplikasi. `variant` memetakan enam belas token di
`resources/css/app.css` ke nama peran, `as` memilih elemen, dan keduanya tidak saling menggantikan.

### 1. Token typography harus didaftarkan ke tailwind-merge

Ini bukan detail kosmetik — **ini bug yang sedang berjalan**.

tailwind-merge hanya mengenal skala Tailwind sendiri, dan `text-` adalah prefix
untuk ukuran **dan** warna. Jadi setiap token typography yang tidak dikenal
diklasifikasikan sebagai **warna**, dan dua kelas `text-*` terlihat konflik.
Urutan menentukan pemenang, secara senyap:

```text
cn('text-body', 'text-foreground')  =>  'text-foreground'
```

Dua konsekuensi yang sudah aktif sebelum perubahan ini:

| Komponen | Yang hilang | Akibat |
| --- | --- | --- |
| `ui/button.tsx`, ketiganya | `text-body`, `text-button-utility`, `text-button-large` | **setiap Button di aplikasi** tampil pada 16px bawaan browser, bukan 17/14/18px |
| `ui/badge.tsx` | `text-micro-legal` | Badge tampil pada 16px, bukan 10px |

Perbaikannya: daftarkan enam belas nama token itu pada class group `font-size`
di `lib/utils.ts`. Warna **tidak** didaftarkan — sudah tertangani oleh group
`text-color` bawaan tailwind-merge, termasuk nilai arbitrer.

Enam belas nama itu tidak merusak apa pun yang sudah ada. `text-sm`, `text-2xl`
dan `text-[13px]` tetap berfungsi persis seperti sebelumnya, dan sekarang
benar-benar berkonflik dengan token desain — sehingga `className="text-2xl"`
menang seperti yang dimaksud, bukan kalah diam-diam.

**Konsekuensi yang terlihat di layar**, dan disengaja:

| Komponen | Sebelum | Sesudah |
| --- | --- | --- |
| Button `lg` | 16px | 18px |
| Button `sm` | 16px | 14px |
| Button default | 16px | 17px |
| Badge | 16px | 10px |

Menutup ini dengan "Button memang selalu 16px selama ini" akan membiarkan bug
tetap ada dan mengabadikannya.

### 2. `h1`…`h6`: hierarki nyata, bukan tiga varian identik

DESIGN.md mendefinisikan tiga langkah display dan **tidak pernah** mendefinisikan
level heading. Pemetaan yang dipilih:

| variant | token | px |
| --- | --- | ---: |
| `h1` | `display-lg` | 40 |
| `h2` | `display-md` | 34 |
| `h3` | `tagline` | 21 |
| `h4` | `body-strong` | 17 |
| `h5`, `h6` | `caption-strong` | 14 |

Sebelum ini `h1`, `h2` **dan** `h3` semuanya `font-display text-display-md`, jadi
outline dokumen tidak membawa hierarki visual sama sekali.

`text-balance` ikut terbawa oleh varian `hero`, `display-lg`, `display-md`, `h1`
dan `h2`, dan **tidak** oleh `h3` ke bawah. Setiap heading display di aplikasi
sudah memakainya sebagai `className` tulis-tangan — empat salinan terpisah —
padahal itu justru ukuran tempat line breaking bawaan browser menghasilkan
kata yatim yang terbaca sebagai bug tata letak. Pada 21px ke bawah heading
biasanya satu atau dua baris dan `text-balance` hampir tidak melakukan apa-apa,
jadi membawa utility yang hanya untuk di-override adalah pemborosan.

`h1` dan `display-lg` identik, begitu pula `h2` dan `display-md`. Itu disengaja,
dan keduanya perlu dibaca berpasangan: nama `display-*` adalah langkah skala
yang diambil ketika pemanggil menginginkan ukuran itu pada elemen lain,
sementara `h1`…`h6` adalah jangkar semantiknya. Meminta `h3` dan mendapat 21px
adalah point-nya; meminta `h2` dan mendapat 34px tidak berubah.

**Konsekuensi yang terlihat:** h1 Beranda dan halaman error 34 → 40px.
`SectionHeading level={3}` 34 → 21px — tidak ada halaman yang mengirimnya
saat ini, jadi tidak ada yang berubah di layar.

### 3. Elemen default diturunkan dari variant

`span` untuk semua yang bukan heading atau kutipan — satu-satunya elemen yang
valid di setiap posisi teks bisa muncul. Default `<p>` akan membuat
`<Text variant="caption">` di dalam sebuah paragraf menjadi HTML tidak valid,
dan `<div>` akan rusak dengan cara yang sama di dalam heading.

Heading default ke tag-nya sendiri, supaya `variant="h3"` tidak bisa diam-diam
merender span bergaya heading. Itu heading yang tidak terlihat screen reader
yang menelusuri per level, dan itulah kesalahan yang paling mungkin disebabkan
oleh API polimorfik. `as` menimpa seluruhnya **tanpa menyentuh `variant`**.

### 4. Yang sengaja tidak ada

- **`success` dan `warning`.** DESIGN.md tidak punya hijau maupun amber di
  paletnya, dan D-22 sudah menghapus pesan sukses `text-green-600` untuk alasan
  yang sama. Menambahkannya di sini akan memasukkan warna tak terdokumentasi
  ke dalam design system lewat pintu belakang.
- **`code` dan `kbd`.** Tidak ada token, tidak ada font monospace di `@theme`,
  dan DESIGN.md melarang menambah paket font tanpa keputusan eksplisit.
  Ditunda sampai ada konsumen nyata **dan** mono font yang terdokumentasi.
  Varian `blockquote` dibuat justru karena perlakuannya sudah terbukti di
  `.rich-text blockquote` milik app.css.
- **Prop `size`.** Berduplikasi dengan `variant`, yang brief sendiri Prioritaskan.
- **`fontFamily`.** Hanya ada dua nilai, dan display sudah menyatu ke varian
  display.
- **`asChild`.** Tidak ada yang membutuhkannya; `TextLink` dan `Badge` membawa
  mechanismenya sendiri.
- **`disabled`.** Tidak ada semantik native yang sah pada elemen teks.

### 5. `Heading` (admin) pindah ke token, ukurannya bergeser ≤1px

`text-xl` (20px) dan `text-base` (16px) tidak punya token. Dipetakan ke
`tagline` (21px) dan `body-strong` (17px + medium). API `Heading` tidak berubah
— lima halaman memanggilnya dan tidak seorang pun perlu tahu bahwa tipografi
di bawahnya berpindah. Yang dibeli: tracking dan line-height sekarang berasal dari
tabel yang sama dengan halaman publik.

Admin **sengaja** memakai skala yang jauh lebih kecil dari situs publik. Halaman
admin adalah permukaan kerja, bukan halaman editorial, dan langkah display
DESIGN.md akan membuat panel pengaturan sekeras beranda.

### 6. `RichText` tidak disentuh

Invariant sanitasi server-side dan satu-satunya `dangerouslySetInnerHTML` di repo
bergantung padanya tetap apa adanya. `tests/Unit/ArchitectureTest.php` menyebut
nama file itu secara eksplisit dan akan gagal kalau `<Text>` masuk ke sana.

### 7. Bukti

- `tests/js/components/text.test.tsx` — 100 kasus: children, setiap variant,
  pemetaan heading ke level yang benar, `as`, warna semantik, alignment,
  transform, decoration, override, truncate/lineClamp/break/whitespace,
 atribut native, event handler, komposisi dengan ikon serta link, dan
  `text-balance` yang terbawa varian display.
- `tests/js/components/text.types.tsx` — dikompilasi, tidak pernah diimpor.
  Tujuh `@ts-expect-error` membuktikan tipe menolak `<Text as="p" href>`,
  `color="success"`, `weight="bold"`, dan `lineClamp={12}`. Direktorinya
  `.types.tsx` dan bukan `.test.tsx` karena glob Vitest akan mengambilnya dan
  gagal karena tidak berisi test.
- `tests/js/lib/utils.test.ts` — kontrak merge, termasuk kasus Button dan Badge
  yang selama ini diam-diam kehilangan tokennya.

### 8. `XMLHttpRequest` di-stub di `tests/js/setup.ts` — cacat lama, bukan yang baru

Tidak terkait `Text`. Ditemukan karena perubahan ini tidak bisa hijau.

`tests/js/components/parish-navbar.test.tsx` mengklik link sungguhan di drawer.
Inertia mengirim XHR-nya **sebelum** resolver komponen berjalan, jadi resolver
yang melempar di `support/inertia.tsx` tidak pernah sampai menahan permintaan
itu: `GET localhost:3000/profil` benar-benar keluar dan kembali `ECONNREFUSED`.

Di `main` kegagalan itu tidak terlihat. Rejection-nya settle **setelah** Vitest
sudah melaporkan hasil dan sedang keluar, jadi run keluar dengan exit 0 sambil
mencetak `Error: socket hang up` yang tidak terlacak. 107 kasus test baru dari
susaunan `Text` memindahkan settle itu ke dalam jendela pelaporan, di mana Vitest
menghitung unhandled rejection sebagai kegagalan — jadi suite menjadi merah
karena cacat yang sudah ada sejak navbar ditulis.

**Diverifikasi di `main`, bukan disimpulkan.** Enam run berturut-turut pada
branch yang tidak disentuh semuanya mencetak `Error: socket hang up` yang sama
dan tetap keluar dengan exit 0.

Perbaikannya ada di environment, bukan di production code, dengan alasan yang
sama seperti stub `localStorage` di file yang sama: unit test tidak pernah
navigasi, dan browser tidak pernah yang salah di sini. Tidak ada satu pun test
yang memeriksa respons, sehingga XHR yang tidak pernah selesai tidak kehilangan
apa pun — dan ini juga menghapus upaya jaringan sungguhan dari setiap render,
termasuk di mesin pengembang yang kebetulan punya dev server menyala.

Tiga jebakan yang harus dilalui, semuanya karena `happy-dom` menyimpan state
XHR di private field:

| Kesalahan | Gejala |
| --- | --- |
| `open` dijadikan no-op | `setRequestHeader` menolak: state harus OPENED |
| memakai `this.OPENED` | `undefined` — konstantanya ada di constructor, bukan di instance |
| hanya mendefinisikan `readyState` | `Cannot read properties of null (reading 'headers')` — `open()`lah yang membangun record request private itu |

Akhirnya `open()` mendefinisikan `readyState` sebagai own property dan
`setRequestHeader` menjadi no-op. `send()` tetap tidak pernah dispatch apa pun:
visit-nya tidak pernah selesai, dan itulah yang dikehendaki unit test
— tidak ada yang menunggu respons, jadi tidak ada yang perlu di-resolve.

Resolver yang melempar di `support/inertia.tsx` **tetap ada**. Ia masih menangkap
navigasi yang lolos lewat stub ini; ia hanya bukan lagi baris pertama.

### 9. Yang belum dikerjakan

- **Step-down responsif untuk hero** (56 → 40 → 34 → 28, roadmap §"Typography
  responsive strategy") tetap belum diimplementasikan. `Text` tidak membawa
  breakpoint karena itu keputusan komposisi halaman, bukan typography; pemanggil
  menuliskannya lewat `className`.
- **Review visual dua tema.** Angka kontras di atas adalah hitungan, dan
  hitungan bukan mata.

---

## D-34 — Skema tabel Jadwal Misa, dan tiga divergensi dari PRD §9.5

**Status:** Accepted · berlaku sejak P04 · branch `feat/phase-04-mass-schedule`

PRD §9.5 dan roadmap Phase 04 §4 tidak sepakat pada tiga titik. Roadmap menang
ketiganya, karena artefak yang lebih spesifik untuk fase ini, dan karena PRD §9.5
sendiri menyatakan "Nama dan tipe bersifat usulan; boleh disesuaikan saat
implementasi selama perilaku di dokumen ini terpenuhi".

### 1. Tidak ada soft delete

PRD §9.5 menandai `locations` (SD) dan `special_masses` (SD); ADM-05 (MUST)
menyatakan operasi hapus memakai soft delete pada tabel konten utama. Roadmap §4
menyatakan sebaliknya — jangan menambahkan soft delete bila `is_active` cukup —
dan §5.6 melarang endpoint hapus permanen.

Dipilih: hanya `is_active`, tanpa `deleted_at` di tabel mana pun.

Alasannya bukan sekadar perbedaan dokumen. Modul ini **tidak punya endpoint hapus
sama sekali** — 21 route admin dan tidak satu pun `DELETE`. Kolom soft delete
karena itu tidak akan pernah diisi, dan kolom yang tidak pernah diisi adalah
persis apa yang D-24 kritik pada permission yang belum punya kode pembaca.
Menghapus kolom juga membuat MASS-04 ("tidak dihapus permanen") benar secara
struktural, bukan hanya lewat konvensi.

Konsekuensi yang harus dicatat: **ADM-05 belum terpenuhi** untuk tabel konten
modul ini, dan baru akan terasa sampai suatu modul benar-benar punya endpoint
hapus.

> **Digantikan oleh D-40.** Bagian 1 ini berlaku pada saat modul dikirim.
> Soft delete kemudian ditambahkan — `deleted_at` di keempat tabel, empat
> endpoint, empat permission — dan ADM-05 kini **terpenuhi**. Alasan yang
> dipakai D-40 adalah kebalikan dari yang dipakai di sini: D-34 memilih
> "tidak ada hapus sama sekali", D-40 memilih "hapus dengan soft delete" justru
> karena tanpa endpoint hapus, ADM-05 tidak bisa dipenuhi.
>
> Bagian 2 dan 3 di bawah tidak terpengaruh dan tetap berlaku.

### 2. `locations`: `slug` + `notes`, tanpa `sort_order`

PRD §9.5 punya `sort_order` dan tidak punya `slug`/`notes`. Roadmap §4.1
sebaliknya, dan §5.2 mewajibkan validasi slug.

Dipilih `slug` (unique) dan `notes`, tanpa `sort_order`. `slug` membuat filter
publik terbaca manusia dan dapat dibagikan (`?lokasi=stasi-tanah-empang`)
daripada `?lokasi=7`. `sort_order` dihapus karena **tidak ada satu pun
requirement yang mengurutkan lokasi** — filter publik mengurutkan dengan nama.

### 3. `schedule_notices` mengikuti roadmap §4.4

PRD §9.5 hanya menyebut `message`, `starts_at` (nullable), `ends_at`,
`is_active`. Roadmap §2.1 meminta CRUD pemberitahuan dengan rentang tampil, dan
§6.5 meminta target umum / lokasi / jadwal / misa khusus. Bentuk PRD §9.5 tidak
dapat menyediakan target.

Dipilih bentuk roadmap §4.4: `title` wajib, tiga foreign key nullable, `starts_at`
wajib. **Tepat satu** target boleh terisi (D-5 di plan implementasi) — notice yang
menunjuk lokasi sekaligus jadwal di dalamnya tidak punya satu arti, dan banner
publik harus menebak mana yang dimaksud.

### 4. Konsekuensi yang bukan divergensi

- `start_time` **tidak di-cast** di model. Kolom TIME adalah jam dinding WIB
  (PRD D-02); `cast('datetime')` akan menggesernya sebesar offset UTC dan
  memindahkan "misa berikutnya" ke hari yang salah.
- Validasi ditulis di `FormRequest`; controller tetap tipis.

## D-35 — Jadwal tidak di-cache, dan kenapa XC-C1 ditunda

**Status:** Accepted · berlaku sejak P04

PRD XC-C1 (SHOULD) menyebut cache untuk "data jarang berubah (jadwal misa
rutin)" dengan invalidasi saat admin menyimpan, dan satu syarat yang tegas: cache
**tidak boleh** membuat "misa berikutnya" salah.

Cache ditunda pada fase ini. Empat tabel ini kecil — jumlah lokasi, jadwal, misa
khusus, dan notice sebuah paroki berada di bawah ratusan baris — sehingga query
publik yang sudah ter-indeks lebih murah daripada biaya invalidasi yang benar.
XC-C1 adalah SHOULD, dan "punya cache dengan invalidasi nanti" lebih aman daripada
"punya cache sekarang tanpa invalidasi".

Konsekuensi:

- **AC-MASS-3 terpenuhi tanpa pekerjaan invalidasi apa pun.** Perubahan admin
  terlihat pada request berikutnya, dan `PublicMassSchedulePageTest` +
  `DuplicateMassScheduleTest` mengukurnya setiap kali.
- Kalau cache ditambahkan nanti, aturan berikutnya wajib: hitung ulang
  `NextMassResolver` per request dari data yang ter-cache, jangan pernah
  mengambil kandidat dari cache.
- `SiteSettingsService` **tetap** memakai cache untuk pengaturan situs — yang itu
  memang jarang berubah dan D-23 sudah menyelesaikannya.

## D-36 — Granularitas permission: satu kata, bukan satu set per modul

**Status:** Accepted · berlaku sejak P04

Dua opsi tersedia dan satu dipilih:

| Opsi | Bentuk | Konsekuensi |
| --- | --- | --- |
| **A (dipilih)** | 12 permission `resource.action` satu kata: `locations.*`, `massschedules.*`, `specialmasses.*`, `schedulenotices.*` | Nol perubahan pada test yang ada |
| B | 3 permission `mass-schedule.{view,create,update}` | `RbacFoundationTest` harus dilonggarkan |

Opsi A dipilih karena `RbacFoundationTest` mengunci
`/^[a-z]+\.[a-z]+$/` untuk setiap nama permission, dan `mass-schedules.view`
tidak mengenuhinya. Melonggarkan regex itu mudah — hanya satu
baris — dan itulah sebabnya ia berbahaya: test itu ada untuk
menangkap rename yang tidak disengaja, dan melonggarkannya sebagai
efek samping akan menutup yang justru penting. `massschedules` ditulis satu
kata supaya aturan aslinya tidak perlu disentuh sama sekali.

Pertimbangan yang lebih luas: permission per resource, bukan per modul, karena
D-18 dan D-24 sama-sama mengatakan bahwa permission yang tidak dibaca lebih
buruk daripada tidak ada. Empat sumber daya dengan tiga kemampuan masing-masing
memberi peran editor di masa depan granularity yang nyata.

Tidak ada permission `*.delete`, karena tidak ada route hapus (D-34).

## D-37 — `is_active` adalah field form, bukan endpoint toggle

**Status:** Accepted · berlaku sejak P04

Roadmap §5.6 menyebut "deactivate/activate" sebagai endpoint. Yang diterapkan:
`is_active` adalah field biasa pada form `update`.

Alasan: roadmap §6 meminta "perubahan sekecil mungkin" pada admin, dan
deactivating sudah merupakan sebuah edit. Delapan endpoint toggle tambahan tidak
dibaca komponen mana pun.

Konsekuensi yang perlu diketahui:

- Peringatan MASS-07 tetap ada, diimplementasikan di `lokasi/ubah.tsx` dengan
  `ConfirmDialog`, dan **hanya ketika `active_schedules_count > 0`**.
- Dialog itu adalah courtesy, bukan jaminan. Jaminan MASS-07 ada di server:
  `Location::scopeActive` dan `MassSchedule::scopeActive` menyembunyikan lokasi
  beserta jadwalnya dari halaman publik, sehingga aturan itu tetap berlaku untuk
  request yang melewati form ini.
- `AdminAccessTest::$adminPages` tidak bertambah untuk route toggle.

## D-38 — Empat URL PRD §5.2, dan empat pintasan sebagai kartu

**Status:** Accepted · berlaku sejak P04

PRD §5.2 memetakan `/admin/lokasi`, `/admin/jadwal-misa`, `/admin/misa-khusus`
dan `/admin/pemberitahuan-jadwal` ke empat fungsi. Roadmap §6.1 meminta satu
"Halaman ringkasan Jadwal Misa" berisi empat pintasan.

Tiga cara memenuhi keduanya:

| Opsi | Bentuk | Masalah |
| --- | --- | --- |
| **A (dipilih)** | `/admin/jadwal-misa` = ringkasan + daftar Misa Rutin; tiga URL lain = halaman daftar sendiri | 13 halaman, tanpa redirect |
| B | Ringkasan di `/admin/jadwal-misa`, daftar rutin di `/admin/jadwal-misa/jadwal-rutin` | menyimpang dari PRD §5.2 |
| C | Satu halaman dengan `?tab=`, URL lain redirect | redirect yang tidak perlu |

Opsi A dipilih: URL PRD tetap persis, dan keempat pintasan tetap ada.

Kenapa pintasan berupa **kartu**, bukan sidebar kedua:

- `ArchitectureTest` melarang `layouts/` meng-import `features/`, jadi array nav
  tidak bisa tinggal di layout tanpa diteruskan ke setiap halaman.
- `layouts/settings/layout.tsx` terikat pada tiga route `akun` dengan copy
  berbahasa Inggris; menggeneralikannya berarti menambah layout kedua untuk satu
  modul.
- Roadmap §6.1 hanya meminta tombol tambah sesuai konteks, bukan navigasi
  bersarang, dan §6 meminta perubahan sekecil mungkin pada navigasi.

Yang **tidak** berubah: shell admin, sidebar, header, dan breadcrumb.
`app-sidebar.tsx` hanya mendapat satu `href` pada entri yang sudah ada.

## D-39 — Tiga jebakan yang hanya terlihat di browser, dan why E2E exists at all

**Status:** Accepted · berlaku sejak P04

Tiga bug di Phase 04 lolos dari 528 test Pest yang hijau, dari 212 test Vitest
yang hijau, dari PHPStan, dan dari `npm run build`. Semuanya ketahuan hanya
ketika `tests/e2e/mass-schedule.spec.ts` dibuka di Chromium. Dicatat di sini
karena ketiganya akan berulang di Phase 05 dan sesudahnya.

### 1. Inertia membungkus resource collection yang tidak dipaginasikan

```php
'locations' => LocationOptionResource::collection($this->activeLocations()),
```

mengirim `{"data": [...]}`, bukan `[...]`. `PropsResolver` mengambil
`Responsable::toResponse()->getData(true)`, dan `JsonResource::collection()`
dengan `->get()` (bukan `->paginate()`) menghasilkan respons tanpa paginator —
yang justru **memakai** pembungkus `data`. Akibatnya `locations.map(...)` di
React melempar `p.map is not a function`, dan seluruh halaman admin modul ini
render jadi layar kosong.

Halaman publik tidak pernah kena karena `PublicMassScheduleController` sudah
memakai `array_values(...->resolve())` sejak awal.

Yang dipatok: **prop non-paginasikan harus `array_values(Resource::collection(...)->resolve())`.**
Prop terpaginasikan (`LocationResource::collection($paginator)`) tidak boleh
disentuh — hasilnya memang `{data, links, meta}` dan itu memang yang
`Paginated<T>` baca. Satu-satunya bedanya: satu dipaginasikan dan satu tidak.

### 2. Assertion yang lulus karena menghitung kunci, bukan isinya

`->has('locations')` hanya memastikan prop itu ada. Dan `->has('locations', 1)`
justru **lolos** pada `{data: [...]}` — satu kunci, satu elemen. Dua test
yang menjaga prop ini sama-sama tidak melihat apa pun.

Assertion yang benar, dan sekarang dipakai:

```php
->where('locations.0.id', $this->location->id)
->missing('locations.data')      // the wrapper itself
```

`missing()` adalah penjaga yang sesungguhnya: ia gagal tepat pada bentuk yang
salah, bukan hanya pada jumlahnya. Diverifikasi dengan mengembalikan bug-nya
sementara — test-nya gagal dengan `Property [locations.0.id] does not exist`.

### 3. `<form>` tanpa `noValidate` membuang seluruh pesan validasi

Empat form modul ini menandai field wajib dengan atribut `required`, dan tidak
satu pun `<form>` punya `noValidate`. Chromium karena itu menjalankan
validasi HTML5-nya sendiri, memblokir submit, dan `onSubmit` Inertia **tidak
pernah jalan**. Server tidak pernah dipanggil, jadi pesan bahasa Indonesia
yang ada di `lang/id/validation.php` tidak pernah tampil — dan tidak ada satu
pun error di log karena memang tidak ada yang salah.

`noValidate` sekarang ada di keempat form. Atribut `required` tetap, karena
ia tetap berarti bagi pembaca layar; yang dipatok hanya siapa yang
memvalidasi: server, satu-satunya yang bisa berbahasa Indonesia.

Aturan turunannya: **hanya server yang memvalidasi.** Aturan HTML5 pada form
Inertia bukan lapisan kedua, dan tidak boleh ditambahkan diam-diam.

### Kenapa test browser ini bukan tambahan opsional

Keempat test yang menangkap tiga bug di atas tidak bisa ditulis sebagai test
Pest. Semuanya menanyakan "apakah React berhasil merender" — dan
`assertInertia` memeriksa payload, bukan hasil render.

## D-40 — Soft delete untuk Jadwal Misa, dan tiga konsekuensi yang tidak terlihat

**Status:** Accepted · berlaku sejak P04 (lanjutan) · menggantikan D-34 §1

Modul ini(ERROR) dikirim tanpa satu pun endpoint hapus. D-34 §1.recorded
sebagai divergensi terhadap **ADM-05**, yang berbunyi "operasi hapus memakai
soft delete pada tabel konten utama". Keputusan ini menutup divergensi itu.

### 1. Yang berubah, dan apa yang tidak

Ditambah: `deleted_at` di keempat tabel, trait `SoftDeletes` di keempat model,
empat `Route::delete`, empat method `destroy()`, empat permission `*.delete`,
satu migration baru, dan satu dialog konfirmasi bersama.

**Roadmap §5.6 dilanggar secara eksplisit.** Ia berbunyi "jangan menambahkan
endpoint delete permanen bila kebutuhan PRD cukup dengan nonaktifkan". Yang
yang ditambahkan adalah endpoint *soft* delete, dan alasannya justru karena PRD
belum terpenuhi dengan nonaktifkan saja: ADM-05 — sebuah **MUST** — meminta
soft delete pada operasi hapus, dan tanpa endpoint hapus sama sekali ADM-05
tidak bisa terpenuhi.

PRD §7.3 ("nonaktifkan, tidak hapus") tetap menggambarkan kerja
sehari-hari secara akurat; delete adalah jalur keluar untuk super admin, bukan
kata kerja harian.

Tiga hal sengaja **tidak** berubah:

- **`is_active` tetap Kendali harian.** Menghapus lokasi yang sedang tayang
  akan langsung menghilangkan dari situs publik;Menonaktifkan melakukan hal
  yang sama tanpa meninggalkanbot. Dialog delete menyebut
  pilihan ini secara eksplisit.
- **Tidak ada `deleted_by`.** Hanya `deleted_at`. Kolom tambahan hanya berarti
  kalau ada yang membacanya, dan tak seorang pun membacanya.
- **Tidak ada pemulihan.** Baris yang terhapus tersembunyi dari semua query
  default, termasuk daftar admin. Karena generated column (bagian 2) sudah
  membebaskan slot, tidak ada alasan administratif untuk memulihkannya.

### 2. Generated column, dan kenapa tidak bisa pakai `deleted_at` langsung

Ini bagian yang paling mudah ditulis salah, dan salah di sini berarti 500 pada
sesuatu yang dilakukanRural benar.

Dua index di modul ini bersifat menentukan:

```
locations.slug                                        UNIQUE
mass_schedules (location_id, day_of_week, start_time) UNIQUE  (MASS-06)
```

Baris yang di-soft-delete **tetap ada di tabel**. Kalau begitu saja, menghapus
misa Minggu 07.30 membuat slot itu tetap terkunci, dan attempt berikutnya untuk
membuat misa di 07.30 ditolak oleh index dengan `QueryException` — 500 untuk
sesuatu yangindeed dilakukanRural dengan benar.

Menambahkan `deleted_at` ke index **juga tidak memperbaiki**. MySQL menganggap
NULL berbeda dalam unique index, jadi `(location_id, day_of_week, start_time,
deleted_at)` mengizinkan sembarang jumlah baris aktif berbagi satu slot, dan
MASS-06 berhenti berlaku. Itu lebih buruk daripada bug aslinya, karena hilang
tanpa suara.

Solusinya kolom generated: `IF(deleted_at IS NULL, 1, NULL)`. Baris hidup
membawa `1` sehingga index tetap menegakkan "satu baris aktif per slot"; baris
terhapus membawa `NULL`, yang berbeda dari segalanya, sehingga bisa menumpuk
tanpa batasi. Pola MySQL standar untuk soft delete di atas unique constraint.

`STORED`, bukan `VIRTUAL`: MySQL sudah mendukung index pada virtual generated
column sejak 5.7, tetapi `STORED` tidak punya catatan tersebut, dan tabelnya
sangat kecil.

Diverifikasi di MySQL 8.0.46 sebelum kode lain ditulis — sembilan pemeriksaan,
termasuk bahwa dua baris aktif di satu slot tetap ditolak dan slug yang sudah
dihapus benar-benar dapat dipakai ulang. Sawtooth yang tidak diuji akan muncul
sebagai 500 di halaman, bukan sebagai test merah.

### 3. Empat privilege baru, dan berikan yang tidak bergerak

`locations.delete`, `massschedules.delete`, `specialmasses.delete`,
`schedulenotices.delete` — mengikuti preseden `media.delete` di
`MediaController::destroy`.

Satu catatan jujur yang terlihat dari sebuah test: **hari ini keempat
permission ini tidak menahan apa pun.** `EnsureAdminAccess` sudah membatasi
seluruh `/admin` untuk super admin, jadi user tanpa role tidak pernah mencapai
policy. Yang memblokirnya adalah middleware, bukan permission.

Permission tetap dipakai, dan bukan mati: `LocationPolicy::delete()` dan tiga
polisi lainnya membacanya hari ini, jadi ia punya pembaca nyata. Ia adalah
garis yang digeser pada hari role kedua muncul — bukan izin yang ditambahkan
untuk kemudian. `DeleteTest` menyatakan ini terbuka, bukan menyamarkan
ketidakaktifan itu di balik nama test yang terdengar.

### 4. Yang/refuse dan yang diperingatkan

Roadmap §4.5 ("jangan menghapus lokasi yang masih memiliki jadwal") menjadi
satu-satunya aturan yang ditegakkan, di `LocationController::destroy()`.
Keduanya menunjuk `locations` dengan `ON DELETE RESTRICT`, jadi tanpa
pemeriksaan database menjawab 500. Pesannya menyebut **kedua** jumlah, karena
memberi tahu "lokasi ini punya jadwal" dan kemudian menemukan misa khusus juga
tidak memberi informasi yang bisa ditindaklanjuti.

Notice **tidak** memblokir. `schedule_notices` menunjuk entitas lain dengan
`SET NULL`, jadi sebuah notice menunjuk jadwal yang dihapus akan kehilangan
targetnya. Query publik hanya menampilkan notice yang ketiga FK-nya NULL atau
yang menunjuk lokasi yang sedang difilter — sehingga notice seperti itu
**berhenti tampil tanpa jejak**. Yang dilakukan: dialog menyebut jumlahnya, dan
daftar admin menampilkan badge "Target terhapus".

Kebutuhan `withoutGlobalScope(SoftDeletingScope::class)` di dalam `withCount`
adalah bug nyata yang sempat lolos ke test: relasi membawa scope itu sendiri,
jadi baris terhapus sudah dikeluarkan sebelum `whereNotNull('deleted_at')`
berjalan, dan semua hitungan balik nol.

### 5. Dua hal yang ditemukan di luar lingkup

Keduanya ditemukan sambil menulis test dan **keduanya tidak diperbaiki di sini**:

1. **Notice yang menunjuk jadwal atau misa khusus tidak pernah tampil di
   halaman publik.** `PublicMassScheduleController::notices()` hanya
   menangani dua cabang: tanpa target, atau menunjuk lokasi yang difilter.
   Notice dengan `mass_schedule_id` atau `special_mass_id` tidak cocok dengan
   keduanya, sehingga tidak pernah tampil. Ini.jetak Phase 04, bukan D-40.
2. **`npm run check:fix` menghapus isi file, bukan hanya memformatnya.** Setelah
   berjalan, tiga file kehilangan seluruh komentar dan elemen `<Heading>`.
   `git checkout HEAD --` memulihkannya. Penyebabnya belum terisolasi — file
   yang tidak disentuh tidak berubah saat diuji ulang — sehingga catatan di
   `AGENTS.md` memperlakukan `check:fix` sebagai alat yang harus diperiksa
   hasilnya dengan `git diff`, bukan dijalankan lalu dipercaya.

### @see

- PRD ADM-05, §7.3, MASS-02, MASS-04, MASS-06
- Roadmap Phase 04 §4.5, §5.6
- D-34 §1 (digantikan), D-36 (keputusan permission), D-39 (jebakan Inertia)

## D-41 — Sub-menu di sidebar, tabel TanStack, dan satu divergensi terhadap PRD §5.2

**Status:** Accepted · berlaku sejak P04 (lanjutan kedua)

Tiga perubahan UI dalam satu keputusan karena ketiganya menyentuh hal yang
sama: bentuk navigasi modul Jadwal Misa.

### 1. Empat daftar pindah ke sub-menu, dan ringkasan bukan lagi tujuan judul

Sidebar admin punya satu entri "Jadwal Misa". Sekarang entri itu punya **empat
anak**: Lokasi, Misa Rutin, Misa Khusus, Pemberitahuan — dan anak-anaknya dapat
dibuka serta ditutup.

**Seluruh baris judul adalah disclosure**, yaitu `<button>` yang membawa
`aria-expanded`, bukan sebuah link. Jadi `/admin/jadwal-misa` **tidak lagi
terjangkau dari sidebar**.

"Ringkasan" tidak dikembalikan sebagai anak. Sebagai anak ia hanya
menduplikasi tujuan yang sudah tidak ada, dan sebagai tujuan judul ia memang
sudah tidak ada. Yang menggantikannya adalah **breadcrumb**, yang dirender setiap
halaman jadwal misa lewat `Index.layout.breadcrumbs`.

Konsekuensi yang harus diuji dari dua arah: **link-nya tidak ada, dan tombolnya
ada** dengan `aria-expanded` yang benar. Hanya salah satu dari keduanya akan
tetap lulus kalau sebagian rusaknya, jadi tes yang hanya assert link masih ada
justru akan gagal dengan pesan yang menunjuk ke tes, bukan ke komponennya.

**Ini membatalkan D-38.** D-38 memakai empat kartu pintasan di halaman
ringkasan *karena* `ArchitectureTest` melarang `layouts/` meng-import
`features/`, sehingga larik navigasi tidak bisa tinggal di sana. Dampanya yang
dihindari sudah ada: sub-menu dideklarasikan di `layouts/app/components/app-sidebar.tsx`
dengan import Wayfinder — pola yang sudah dipakai file itu sendiri untuk
`massSchedulesIndex()`. Aturan arsitekturnya tidak dilanggar; yang berubah
adalah bentuk navigasinya.

Akibatnya kartu pintasan dihapus, dan **PRD §5.2 berubah makna**. D-38
sengaja menahan `/admin/jadwal-misa` sebagai daftar misa rutin agar URL PRD
tetap persis tanpa redirect. Sekarang:

| URL | D-38 | D-41 | D-42 |
| --- | --- | --- | --- |
| `/admin/jadwal-misa` | ringkasan + tabel Misa Rutin | ringkasan saja | redirect ke tabel Misa Rutin |
| `/admin/jadwal-misa/misa-rutin` | — | tabel Misa Rutin | tabel Misa Rutin |

PRD §5.2 dipetakan ke daftar misa rutin, jadi ini **divergensi yang harus
dinyatakan**, bukan penyederhanaan. Konsekuensinya:

- Redirect setelah menyimpan, mendarat di `mass-schedules.list` dan bukan
  ringkasan — admin harus berakhir di tabel yang baru saja ia ubah.
- Anak "Misa Rutin" di sub-menu menunjuk URL barunya, bukan URL lama.

D-42 menghapus_ringkasannya seluruhnya, jadi tabel di atas punya kolom ketiga:
`/admin/jadwal-misa` kini hanya pengalihan, dan empat angka yang tersisa di sana
sudah tidak ada.

State buka-tutup ada, dan dalam React state biasa — bukan Radix Collapsible.
Alasannya ditemukan lewat E2E: `CollapsibleContent` memasang tinggi inline pada
`<ul>` anak, dan `<ul>` yang tinggi terkonsteksi itu menumpuk baris-barisnya
satu di atas yang lain. Akibatnya setiap sub-link tidak bisa diklik, dan log E2E
hanya bilang sebuah `svg` dari baris lain "intercepts pointer events" — yang
tidak pernah menunjuk ke DOM yang benar. `SidebarMenuSub` karena itu dibiarkan
apa adanya sebagai `<ul>` polos dari shadcn.

State awal dihitung dari semua URL grup, bukan hanya URL induknya: URL anak
tidak berawalan dengan URL induknya, jadi menguji induk saja membuat tiga dari
empat halaman tiba dengan menu-nya tertutup.

Satu lagi yang tidak terlihat di browser dan sudah ditulis sebagai test:
breadcrumb tiga halaman memakai `href: { url: '...' }` dan berakhir sebagai
`admin/[object Object]`.

### 2. Tabel: `ui/table` di atas TanStack Table

`components/ui/table.tsx` sudah ada di repositori sejak awal dan **tidak
dipakai di mana pun**. Keempat daftar modul ini sekarang memakainya, dengan
`@tanstack/react-table` (v9) sebagai lapisan di bawahnya.

Dependency ini adalah perubahan pertama sejak Phase 01, dan tercatat di sini
karena plan Phase 04 ditulis dengan "tanpa dependency baru". Yang dibeli darinya
adalah satu kemampuan: **sorting yang benar**. Tanpa TanStack, sorting harus
ditulis tangan di setiap tabel; TanStack menyediakan model state-nya.

Akar keputusannya adalah `manualSorting: true`. Keempat daftar ini dipaginasi
server sebanyak lima belas baris, jadi sorting sisi klien akan mengurutkan lima
belas baris itu dan meninggalkan sisanya — header yang menulis "A–Z" sementara
tiga perempat tabel belum bergerak adalah kebohongan kecil yang lebih buruk
daripada tidak ada header. Klik header karena itu menjadi **kunjungan**, bukan
reorder, dan urutannya datang dari server.

### 3. Sorting server-side, dan tiga aturan yang membuatnya tidak merusak apa pun

`app/Concerns/SortsAdminList.php` adalah separuh dari fitur ini. Tiga aturan
di dalamnya masing-masing menjawab satu cara hal ini bisa rusak:

- **Whitelist, bukan nama kolom.** Kunci sort datang dari query string dan
  masuk ke `ORDER BY`, yang tidak bisa menerima bound parameter. Kunci di luar
  daftar diabaikan — dan diabaikan **diam-diam**, dengan alasan yang sama
  dengan `?status=` yang rusak: tautan rusak menampilkan daftar, bukan 422.
- **Tie-breaker selalu ditambahkan.** Mengurutkan berdasarkan nama saja
  menyisakan semua baris bernama sama dalam urutan yang bebas diubah MySQL
  antar-kueri, sehingga baris 16 halaman 2 bisa jadi baris 1 halaman 1 dan
  tabel kehilangan baris sambil tampak lengkap. `id` ditambahkan supaya urutannya
  total, dan hanya sebagai pemutus seri — placed **terakhir**, setelah sort.
- **Urutan bawaan bertahan.** Tanpa kunci sort, urutan bawaan list tidak
  berubah. Beberapa test lama sudah mengassert urutan itu, dan mereka adalah
  buktinya.

Butir ketiga ini juga menemukan satu bug implementasi: urutan bawaan
pertama-tama ditulis di rantai query, lalu sort ditambahkan sesudahnya. MySQL
menyimpan kedua instruksi untuk satu kolom **dalam urutan itu**, jadi
`orderBy('name')` diikuti `orderBy('name','desc')` akan tetap urut menaik dan
mengabaikan sort sepenuhnya. Karena itu urutan bawaan kini melaluai
`applyAdminSort()` sebagai argumen, bukan dikirim terpisah.

### 4. Dua jebakan yang muncul di sini, dan satu yang harus dibaca

**`select()` setelah `withCount()` menghapus hitungan, tanpa error.**kedua
daftar yang bisa diurutkan menurut kolom relasi memerlukan `leftJoin`, dan
`withCount('scheduleNotices')` menambahkan hitungannya sebagai ekspresi select
tambahan — lalu `select('mass_schedules.*')` yang menyusul **mengganti** seluruh
daftar kolom. Tidak ada error; field-nya tidak ada, dan `whenCounted()` mengubah
ketiadaan itu menjadi nol. Dialog hapus jadi melaporkan "tidak ada pemberitahuan
yang menunjuk" selamanya. `select()` harus **sebelum** `withCount()`.

**`import { index as a, index as b }` dari satu modul Wayfinder.** Dua nama
untuk satu fungsi: TypeScript tidak keberatan, lint tidak keberatan, dan kedua
menu itu diam-diam menunjuk URL yang sama. "Misa Rutin" di sub-menu membuka
ringkasan. Hanya E2E yang menangkapnya, dan
`tests/js/components/app-sidebar.test.tsx` sekarang mengunci lima href-nya —
sidebar adalah satu tempat di mana href yang salah adalah tujuan yang salah.

**Satu jebakan yang konstan dan belum diperbaiki.** Sortir menguji
`special-masses.index`, `schedule-notices.index`, `locations.index` dan
`mass-schedules.list` dengan `?sort=password` dan `?sort=status` — kolom
turunan yang tidak ada di disk. Keduanya lolos. Yang perlu dibaca: **sortir
adalah perubahan yang lebih besar daripada yang terlihat di diff-nya**,
karena setiap query daftar di modul ini ikut berubah.

### @see

- PRD §5.2 (divergensi), AC-MASS-5, ADM-01
- Roadmap Phase 04 §6.1, §6.6
- D-38 (dibatalkan sebagian), D-40 (soft delete), D-39 (jebakan Inertia)

---

## D-42 — Kolom `language` dan halaman ringkasan dihapus

**Status:** Accepted · berlaku sejak P04 (lanjutan ketiga) · branch
`feat/phase-04-mass-schedule`

Dua penghapusan dalam satu keputusan karena keduanya sama-sama permanen
mengurangi apa yang ada, bukan menambahkan apa yang baru.

### 1. `mass_schedules.language` dihapus, termasuk dari situs publik

Kolomnya nullable dan tidak pernah diisi meaningful. Dihapus lewat **migration
baru** (`dropColumn`), bukan dengan mengedit migration pembuat tabel: migration
itu masih menggambarkan seperti apa tabelnya, dan mengeditnya membuat
`migrate:fresh` berbeda dari setiap database yang sudah menjalaninya.

Blok yang ikut hilang: kolom "Bahasa" di tabel dan kartu, field form, aturan
validasi, `#[Fillable]`, factory, `DuplicateMassScheduleAction`, `MassScheduleResource`,
`MassScheduleOptionResource`, `NextMass::$language`, `NextMassResolver`, dan
**kedua resource publik**.

Yang harus disadari: nama bahasa ada di dua resource publik dan di dua komponen
kartu publik. Menghapus kolom tanpa membersihkan semuanya akan meninggalkan Vue
mencari property yang selalu `undefined` — tidak error, hanya tidak pernah
ditampilkan, jadi tidak ada yang gagal pelan-pelan sampai ada yang mencari.

Yang justru **tidak** boleh terlupa adalah whitelist sorting:
`'language' => 'mass_schedules.language'` di `MassScheduleController`. Kunci sort
dimasukkan langsung ke `ORDER BY`, jadi kalau whitelist-nya bertahan sementara
kolomnya hilang, `?sort=language` yang tersimpan di bookmark atau tautan lama
menghasilkan SQL ke kolom yang tidak ada — **500**, bukan daftar dengan urutan
default. `SortTest` punya kasus yang persis menjaga ini.

### 2. Ringkasan modul dihapus, URL-nya jadi pengalihan

Empat angka tanpa tujuan navigasi bukan halaman. Halaman, `index()`, `stats()`,
`module-stats.tsx`, dan tipe `ModuleStats` semuanya hilang.

URL-nya **tidak** ikut hilang, dan itu inti keputusannya. `/admin/jadwal-misa`
adalah satu-satunya URL yang disebut PRD §5.2, dan **tujuh** halaman modul
menunjukinya lewat breadcrumb. Menghapus route akan mengubah ketujuhnya jadi 404.
Jadi route-nya bertahan sebagai pengalihan ke `mass-schedules.list`: bookmark
lama tetap bekerja, dan breadcrumb tetap bermakna.

Konsekuensi yang harus diketahui: title row sidebar tidak lagi punya tujuan
apa pun. Baris itu sudah berupa `<button>` disclosure (D-41), jadi tidak ada
yang kehilangan navigasi — tetapi `entry.href` **harus tetap ada**, karena ia
separuh dari perhitungan auto-open grup.

### 3. Yang perlu dikonfirmasi sebelum mengarang

Pertanyaan awal "kenapa tombol Hapus tidak ada di tabel lokasi" sebenarnya
**bukan bug**. Tombol itu ada; ia sengaja disembunyikan untuk lokasi yang punya
jadwal atau misa khusus, karena `mass_schedules.location_id` adalah `NOT NULL`
dengan `ON DELETE RESTRICT` — jadi opsi "lepaskan jadwal dari lokasi" secara
fisik tidak mungkin, hanya ada tolak atau hapus berantai. Atribut itu
ditemukan dengan membaca baris `canDelete`, bukan dengan menebak.

Tidak ada perubahan kode untuk bagian ini, hanya catatan supaya pertanyaan yang
sama tidak muncul lagi.

---

## D-43 — Empat tabel, satu migrasi, dan tidak ada `community_id`

**Konteks.** PRD §5.2 menyebut "Komunitas" sebagai modul tersendiri, dan
beberapa pembaca mengira artikel dan kegiatan punya kolom `community_id`.
Implementasi Phase 05 tidak membuatnya, dan ini keputusan, bukan kelalaian.

**Keputusan.** Satu file migrasi
`2026_10_11_082807_create_news_and_agenda_tables.php` membuat
`post_categories`, `posts`, `event_categories`, `events` — semuanya dalam satu
`up()`, dan **tanpa** `community_id` di `posts` maupun `events`.

**Alasan.**

1. Modul Komunitas belum ada. Tabelnya belum ada, policy-nya belum ada,
   bentuk datanya belum ada. Menambahkan kolom FK ke tabel yang belum ada
   berarti menebak bentuk datanya — dan tebakan yang dikunci oleh skema tidak
   murah diubah nanti.
2. Yang diketahui kebutuhan sebenarnya adalah "kegiatan ini milikPagung/desa/
   lingkungan mana", dan itu **bukan** relasi ke entitas Komunitas; ia atribut
   teks pada kegiatan; hubungannya dengan entitas Komunitas dibahas ketika
   tabel itu benar-benar ada.
3. Kunci skema lebih mahal daripada kolom tambahan. Kalau ternyata memang butuh
   relasi, menambahkannya adalah migrasi baru yang tidak merusak apa pun;
   sedangkan mencrumskannya berarti menyusun ulang empat tabel yang sudah ada
   isinya.

**Konsekuensi yang harus diketahui.** `Post` dan `Event` tidak punya relasi
`community`. Kalau modul Komunitas dibangun dan butuh backlink ke artikel, itu
pekerjaan Fase berikutnya, bukan yang diuncate di sini.

---

## D-44 — Slag unik lintas soft-delete untuk Berita dan Agenda

**Konteks.** D-40 membangun indeks unik Jadwal Misa dengan kolom hasil
`active_marker`, supaya baris yang soft-delete **melepaskan** slot-nya dan
`Rule::unique()` memakai `->withoutTrashed()`. Phase 05 butuh pilihan yang
berlawanan, dan kedua-duanya benar untuk modul masing-masing.

**Keputusan.** `posts.slug` dan `events.slug` memakai indeks unik **polos**,
dan `Rule::unique()` keduanya **tanpa** `withoutTrashed()`. Slag yang pernah
dipakai artikel atau kegiatan yang sudah dihapus **tidak pernah dipakai lagi**.

**Alasan.** Route publik detail mengikat berdasarkan slug:
`/berita/{post:slug}` dan `/agenda/{event:slug}`. Kalau dua baris menjawab satu
slug, pengikatan itu ambigu, dan URL yang sudah dibuku orang akan mulai
menunjuk ke artikel atau kegiatan yang berbeda. Ini kebalikan dari
`mass_schedules`, yang tidak punya route publik per jadwal — hanya filter daftar
— sehingga melepas slotnya aman.

**Konsekuensi yang harus diketahui.** Dua tabel ini **sengaja** berbeda dari
`mass_schedules` dan `locations` di@@@@
atas alasan yang sama. `EventTest` punya test bernama *"does not free the slug
once removed"* yang mengunci perbedaan ini; `MassScheduleTest` mengunci yang
sebaliknya. Kalau seseorang membuat modul berikutnya yang punya route publik per
baris, aturan yang benar adalah **yang kedua**, dan itu harus ditulis di test-nya
dari awal.

Dua keputusan kecil lain yang diambil bersama D-44:

- **Kategori** berita dan agenda mengikuti `location` / `mass_schedules`, yaitu
  soft-delete **melepas** slug-nya. Halaman publik kategori tidak ada, jadi
  tidak ada binding yang bisa ambigu.
- **`is_published` pada `events`** adalah switch editorial harian, terpisah dari
  `status`. EVT-08 meminta kegiatan yang dibatalkan tetap tampil di kalender
  publik dengan keterangannya — jadi "diumumkan" dan "berlangsung" adalah dua
  pertanyaan berbeda, dan `PublishedBadge` ada karenaExperience membaca baris
  yang sama dan menghitunginya sebagai satu.

---

## D-45 — Slug berita/agenda tidak bisa dipakai ulang; kategori bisa

Lihat D-44. Entrinya sendiri karena referenced dari `PostValidationRules` dan
`EventValidationRules`, yang keduanya menuliskan alasannya di komentar dan
menunjuk ke sini.

---

## D-46 — Dua berkas boleh memakai `dangerouslySetInnerHTML`, bukan satu

**Konteks.** `ArchitectureTest` mengklaim `rich-text.tsx` adalah satu-satunya
tempat React menyuntik HTML mentah. Phase 05 menambah JSON-LD untuk halaman
detail agenda, dan JSON-LD tidak bisa ditulis tanpa `dangerouslySetInnerHTML`.

**Keputusan.** Test-nya diganti menjadi **daftar putih dua berkas**:
`resources/js/components/rich-text.tsx` dan
`resources/js/features/public/agenda/components/event-json-ld.tsx`.

**Alasan.** React meng-escape_children, dan blok JSON-LD yang ter-escape bukan
JSON-LD yang valid — jadi `<script type="application/ld+json">` tidak bisa
dibangun dengan cara lain. Isinya `JSON.stringify()` dari prop bertipe yang
dikirim server, bukan HTML dari penyimpanan, jadi `HtmlSanitizer` tidak berlaku
dan tidak ada yang perlu dilindungi di sini.

Daftarnya ditulis eksplisit, bukan dengan pola, supaya menambahkan berkas ketiga
menjadi suntingan yang disengaja di test dan bukan hal yang lolos diam-diam.
Larangan tanpa pengecualian hanya membuat data terstruktur mustahil dikirim,
dan itu cara aturan seperti ini dihapus alih-alih dipatuhi.

**Konsekuensi yang harus diketahui.** JSON-LD ini hanya ada di dokumen
setelah React hydrate, karena Inertia SSR dimatikan (D-26). Crawler yang
menjalankan JavaScript membacanya; yang tidak, tidak melihat data terstruktur
apa pun. Berbeda dengan tag Open Graph yang_statis per situs dan karena itu
digandakan di `resources/views/app.blade.php`, isi JSON-LD berbeda per
kegiatan sehingga menggandakannya berarti menarik data kegiatan ke view Blade
tanpa hasil yang lebih baik dari yang sudah diambil Google.

---

## D-47 — Sidebar: Lokasi pindah ke Master Data, Berita dan Agenda tetap datar

**Konteks.** Phase 04 menaruh `Lokasi` sebagai anak pertama grup Jadwal Misa.
Setelah Agenda dibangun, jelas bahwa sebuah ruangan juga dipakai kegiatan —
sehingga listing-nya di bawah Jadwal Misa mengklaim kepemilikan yang tidak
dimilikinya.

**Keputusan.**

1. Grup baru **Master Data** memegang `Lokasi`, `Kategori Berita`, dan
   `Kategori Agenda` — tiga tabel referensi yang diisi sekali lalu jarang
   dipikirkan lagi.
2. `Berita` dan `Agenda` menjadi **tautan datar** ke daftar masing-masing, bukan
   grup.
3. Grup Master Data **tidak punya `href`** — tidak ada halaman
   `/admin/master-data`, dan menunjuk grup ke URL yang 404 adalah kegagalan yang
   justru dihindari `app-sidebar.test.tsx`.

**Alasan untuk (2).** Versi pertamanya membuat keduanya grup dengan satu anak,
dan itu tautan dengan dua klik tambahan. Yang lebih buruk, "Kategori Berita"
muncul di dua grup sekaligus — label yang sama dua kali di satu sidebar, yang
justru ditolak test sidebar. Karena itu kategori ditarik sepenuhnya ke Master
Data. Menu root `/admin/berita` dan `/admin/agenda` tetap bekerja sebagai
redirect ke daftar, dan tetap menjadi URL PRD §5.2 yang ditunjuk setiap
breadcrumb.

**Konsekuensi yang harus diketahui.** Mengubah isi sidebar berarti mengubah
`tests/js/components/app-sidebar.test.tsx` **dan** `tests/e2e/master-data.spec.ts`
bersamaan. Keduanya menguji href, bukan hanya keberadaan menu, jadi perubahan
navigasi selalu terlihat sebagai kegagalan test.

**Direvisi oleh D-48.** Grup sidebar saja tidak cukup. Lokasi, Kategori Berita,
dan Kategori Agenda juga pindah URL, file, dan (untuk Lokasi) controller. Lihat
D-48.

---

## D-48 — Master Data: satu feature, satu prefix, dan satu bug yang tidak
## pernah terlihat sampai ada yang mengkliknya

**Konteks.** D-47 menyatukan tiga tabel referensi di sidebar. Itu menyelesaikan
navigasi dan meninggalkan sisanya: Lokasi masih `features/admin/jadwal-misa/
pages/lokasi/` dengan URL `/admin/jadwal-misa/lokasi`, dan kedua tabel kategori
masih dua feature dengan dua prefix. Tiga pengelompokan berbeda untuk satu jenis
hal.

**Keputusan.**

1. Ketiganya menjadi satu feature, `features/admin/master-data/`, dengan satu
   prefix URL `/admin/master-data/{lokasi,kategori-berita,kategori-agenda}`.
2. **Nama route tidak berubah** (`locations.*`, `post-categories.*`,
   `event-categories.*`). Hanya URI.
3. URL lama dipertahankan sebagai redirect 302.
4. Hanya `LocationController` yang pindah namespace, ke
   `App\Http\Controllers\MasterData\`.

### 1. Kenapa nama route tidak ikut berubah

89 test Pest memanggil `route()` alih-alih menulis URL. Nama yang tidak bergerak
adalah nama yang tidak perlu dipikirkan siapa pun: tidak ada satu pun test itu
yang perlu disentuh, dan tidak ada kode yang harus tahu URL-nya berubah.

Yang harus menulis URL secara literal hanya `ArchitectureTest` (dua path
halaman), `page-name.test.ts`, E2E, dan test sidebar. Itu daftar yang bisa
dihitung, bukan daftar yang harus dicari.

### 2. Kenapa hanya `LocationController`

Lokasi benar-benar keluar dari modul Jadwal Misa: TIDAK ada layar Jadwal Misa
yang memilikinya lagi, dan sebuah ruangan dipakai kegiatan agenda sama
banyaknya dengan misa.

Dua controller kategori **tidak** ikut. Berita tetap memiliki kategorinya;
Agenda tetap memiliki kategorinya. Yang berubah adalah **URL navigasi**, bukan
kepemilikan domain, dan routing bukan kepemilikan. Memindahkan keduanya akan
mengosongkan `News\` dari controller kategorinya supaya URL-nya seragam — itu
menukar satu ketidakkonsistenan nama dengan satu ketidakkonsistenan yang lebih
besar.

Satu sisa yang diterima: `LocationController` memakai trait
`App\Concerns\MassScheduleFormMeta`, yang namanya sekarang tidak lagi cocok.
Trait itu dipakai tiga controller lain di `MassSchedule\`, jadi mengganti
namanya adalah empat suntingan demi kosmetik. Dibuang dari cakupan, dan dicatat
di sini supaya bukan kejutan.

### 3. `Route::redirect()` wajib punya nama

Bukan dokumentasi, ini load-bearing. Redirect tanpa nama tidak punya controller
untuk Wayfinder memfilenya, sehingga ia mendarat di modul yang dihasilkan untuk
route bernama yang di-redirect-*dari*-nya, dan menghasilkan `export const index`
dua kali. Build mati dengan *"Identifier 'index' has already been declared"*.

Semuanya diberi nama `legacy.*` supaya menghasilkan file sendiri. Test
`no route name is declared twice` ada karena bentuk kegagalan yang sama bisa
datang dari arah lain: route kategori lama pernah tertinggal di berkas Berita dan
Agenda setelah yang baru ditambahkan ke berkas Master Data.
Laravel tidak mengeluh — registrasi terakhir menang — sehingga satu-satunya
tempat itu terlihat adalah modul hasil generate dan build yang menolak selesai.

### 4. Bug yang ditemukan: nama component tidak cocok

**Gejala.** Mengklik "Kategori Berita" dan "Kategori Agenda" di sidebar tidak
melakukan apa pun. Tidak pindah, tidak ada error, tidak ada loading.

**Penyebab.** Controller mengirim Inertia `admin/kategori-berita/index`;
frontend mendaftarkan berkas itu sebagai `admin/kategori-berita`. Aturan di
`pageName()` membuang `index` di **akar feature** dan mempertahankannya **satu
folder di bawahnya**:

```
features/admin/berita/pages/index.tsx             -> admin/berita          (index dibuang)
features/admin/jadwal-misa/pages/lokasi/index.tsx -> admin/jadwal-misa/lokasi/index
```

Kategori dulu ada di `features/admin/kategori-berita/pages/index.tsx`, yaitu di
akar feature — jadi namanya `admin/kategori-berita`. Controller meminta
`admin/kategori-berita/index`. Kedua sisi konsisten sendiri-sendiri dan tidak
ada yang membandingkannya.

Server bahagia: `PageChunk::source()` membangun path dari nama, berkasnya ada,
respons 200 dengan component yang belum pernah didengar sisi klien. Inertia
melempar *"Page not found"* di console dan **tinggal di halaman lama** — itulah
sebabnya kliknya tampak tidak melakukan apa pun.

Bug yang sama membuat dua layar lain tidak bisa dibuka: daftar admin Berita
(`admin/berita/index`) dan daftar admin Agenda (`admin/agenda/index`).

**Kenapa dua test yang ada tidak menangkapnya.**

- `a page keeps its folder in the name` bertanya "apakah nama ini
  menghasilkan path?" — dan untuk kedua sisi jawabannya ya.
- Test bahwa frontend dan Blade melihat berkas yang sama bertanya "apakah
  finder melihat semua berkas?" — juga ya.

Tidak ada yang bertanya **"apakah nama yang dikirim controller termasuk nama
yang didaftarkan frontend?"**, dan itu pertanyaan yang berbeda.

**Perbaikannya.** Setelah pindah, ketiga tabel berada di
`master-data/pages/<nama>/`, jadi `index` dipertahankan dan namanya
`admin/master-data/<nama>/index` — cocok persis dengan URL-nya. Struktur baru
menyelesaikannya, bukan tambalan.

**Test yang menutupnya.** `every page a controller asks for is a page the
frontend registers` — menelusuri setiap `Inertia::render()` di
`app/Http/Controllers`, lalu memastikan namanya ada di `PageChunk::all()`.
Dijalankan pertama kali, test ini langsung menemukan dua halaman rusak yang
belum dilaporkan (`admin/berita/index` dan `admin/agenda/index`).

### 5. E2E: menguji href bukanlah menguji tautan

E2E yang ada menguji ketiga link ini dengan `toHaveAttribute('href', ...)` —
dan href-nya memang selalu benar. Itu celah yang membuat bug ini bisa hidup:
sebuah sidebar yang sub-link-nya tidak bisa diklik akan lulus setiap test yang
berhenti di atribut.

`tests/e2e/master-data.spec.ts` menetapkan aturannya: setiap tautan di grup
Master Data diuji href-nya **lalu diklik**, dan URL-nya diperiksa sesudahnya.
Assertion-nya juga mengecek konten yang hanya dirender halaman tujuan, supaya
navigasi yang mendarat di tempat lain gagal alih-alih lulus lewat redirect.

### 6. Konsekuensi lain

- `LocationRow` pindah ke `master-data/types/location.ts`;
  `PostCategoryRow` dan `EventCategoryRow` ikut ke sana.
- `LocationType` naik ke `types/location.ts` — ia dipakai dua feature (Master
  Data dan filter Jadwal Misa), dan ARCHITECTURE.md Part B §4 melarang satu
  feature mengimpor feature lain.
- `StatusBadge` naik dari `jadwal-misa/components/list-parts.tsx` ke
  `components/list-parts.tsx` karena kini punya konsumen di dua feature.
- `RowActions<T>` naik ke `components/data-table.tsx` karena empat column
  builder lintas dua feature memakainya.
- `locationColumns` pindah ke `master-data/pages/lokasi/components/`.
  `jadwal-misa/components/columns.tsx` menyisakan tiga builder miliknya.
- Helper E2E `pilih()` dan `buatLokasi()` pindah ke
  `tests/e2e/support/admin-forms.ts`, karena lebih dari satu spec memakainya.
- Test `judul modul hanya membuka sub-menu, modul lewat breadcrumb` di
  `mass-schedule.spec.ts` diperbaiki: ia bersandar pada breadcrumb halaman
  **Lokasi** yang kebetulan berbunyi "Jadwal Misa", dan setelah Lokasi pindah
  breadcrumb itu berbunyi "Master Data".

---

## D-49 — Semua route kembali ke satu `routes/web.php`

**Konteks.** Sampai Phase 05 selesai, `routes/` berisi enam berkas:
`web.php`, `settings.php`, `mass-schedule.php`, `news.php`, `agenda.php`, dan
`console.php`. Pola ini meniru `settings.php` dari starter kit Laravel: tiap
modul meng-`require` berkasnya sendiri dari bawah `web.php`.

**Keputusan.** Kelimanya digabung ke `routes/web.php`. `console.php` tetap —
itu bukan routing, itu berkas console routes yang di-`require`
`bootstrap/app.php::withRouting(commands:)`, dan Laravel memang menunggunya.

**Alasan.** Yang tadinya masuk akal (satu berkas per modul, gampang ditemukan)
berhenti membayar dirinya sendiri pada modul ketiga:

- Setiap berkas modul dibuka dengan mendeklarasikan ulang empat middleware layer
  yang sama dan prefix `admin` yang sama. Empat blok identik, masing-masing
  dengan satu baris yang benar-benar berbeda.
- Pertanyaan "URL apa saja yang dimiliki `/admin`?" tidak bisa dijawab dari satu
  tempat. Jawabannya tersebar di lima berkas yang harus dibaca **dalam urutan
  `require`** untuk dipahami — dan urutan itu sendiri tidak terlihat dari mana
  pun kecuali dari lima baris di bawah `web.php`.
- Karena Laravel mencocokkan route berurutan, urutan pendaftaran adalah
  perilaku, bukan kerapian. Menyembunyikannya di balik rantai `require` membuat
  satu-satunya tempat aturan itu terlihat justru yang paling mudah terlewat.

**Yang harus diketahui.** Penggabungan ini **tidak mengubah satu route pun**.
Diperiksa dengan membandingkan `php artisan route:list` sebelum dan sesudah —
method, URI, nama, dan middleware, 117 route, identik. Itu memang bentuk
verifikasi yang benar untuk perubahan seperti ini: hasil akhirnya harus sama
persis, dan cara membuktikannya adalah mendiffnya, bukan membaca ulang.

Dua hal yang sengaja dipertahankan karena mengubahnya akan mengubah perilaku:

1. **Urutan deklarasi.** `/agenda/{event:slug}/ics` sebelum
   `/agenda/{event:slug}`, dan `/admin/berita/{post}/ubah` sebelum
   `.../pratinjau`. Keduanya dikomentari di tempatnya.
2. **Redirect `legacy.*` tetap DI LUAR grup middleware.** Sebelumnya mereka
   berada di luar karena `master-data.php` mendeklarasikannya setelah grup.
   Kalau mereka dipindah ke dalam grup, tamu yang mengikuti tautan lama akan
   langsung dikirim ke halaman login alih-alih menerima 302 lebih dulu —
   dan `LocationRedirectTest` menguji justru yang pertama.

Dua hal yang dibiarkan apa adanya meski terlihat aneh, karena di luar cakupan:

- `/admin/akun` mengirim `Location: profile` (relatif), bukan path absolut.
  Itu perilaku sebelum penggabungan dan tetap sama sesudahnya. Kalau suatu saat
  diperbaiki, itu keputusan tersendiri.
- `Route::redirect('/akun', 'profile')` dipertahankan dengan tujuan relatif
  yang sama persis, supaya tidak ada yang bergeser tanpa sengaja.

**Konsekuensi untuk fase berikutnya.** Modul baru menambahkan route-nya ke
`web.php` di bagiannya sendiri, bukan membuat berkas baru. Kalau suatu hari
jumlahnya membuat berkas ini tidak terbaca lagi, membelahnya kembali adalah
keputusan yang sah — tetapi harus disertai alasan yang lebih kuat daripada
"starter kit melakukannya begitu", karena itu justru alasan pemisahan
sebelumnya.

@see D-48 untuk alasan redirect diberi nama `legacy.*`

---

## D-50 — Editor Tiptap penuh, `style` dibatasi `text-align`, dan pratinjau gambar

**Status:** Accepted · branch `fix/media-preview-rich-text-editor`

Laporan pengguna menemukan dua bug dan satu permintaan: pratinjau gambar tidak
muncul setelah unggah, UI editor tidak berubah saat toolbar dipakai, dan toolbar
editor terlalu minim. Ketiganya diperbaiki sekaligus karena satu perubahan
allowlist menyentuh ketiganya.

### 1. Bug pratinjau: symlink `public/storage` dan path yang bukan URL

Ada **dua** sebab, keduanya harus dibereskan:

1. **`public/storage` tidak ada.** Variant ditulis ke `storage/app/public/...`
   dan disajikan lewat `/storage/...`, tetapi `composer setup` tidak pernah
   menjalankan `storage:link`. File-nya ada di disk dan URL-nya 403, jadi
   pratinjau (dan setiap gambar publik) rusak. `php artisan storage:link`
   ditambahkan ke `composer setup` supaya tidak terulang.
2. **Form ubah mengirim path, bukan URL.** `PostResource::featured_image` dan
   `EventResource::poster_path` berisi `media/variants/1/large.webp`, dan
   `ubah.tsx` menyerahkannya ke prop `currentUrl` yang didokumentasikan sebagai
   URL — sehingga `<img src="media/...">` diselesaikan relatif terhadap
   `/admin/...`. Kedua resource kini menambahkan `featured_image_url` /
   `poster_url` yang di-resolve `Storage::disk(config('media.variant_disk'))`,
   sama seperti resource publik. Path aslinya tetap dikirim karena itu kontrak
   kolom.

Pratinjau lokal (`URL.createObjectURL`) ditambahkan di `MediaField`, supaya
berkas yang baru dipilih langsung terlihat sebelum job selesai — bukan kotak
kosong yang membuat admin mengunggah dua kali.

### 2. Bug reaktivitas: `useEditor` v3 tidak re-render per transaksi

Di Tiptap v3 `useEditor` default `shouldRerenderOnTransaction: false`. Toolbar
membaca `editor.isActive(...)` saat render, jadi nilainya dihitung sekali dan
beku: formatnya benar-benar diterapkan, tetapi tombolnya tidak pernah menyala.
Perbaikannya adalah `useEditorState` dengan satu selector untuk seluruh
permukaan reaktif toolbar (mark, heading, alignment, tabel, undo/redo, href
tautan). `shouldRerenderOnTransaction: true` sengaja **tidak** dipakai — itu
perilaku lama yang akan dihapus.

### 3. Toolbar lengkap, dan allowlist diperluas mengikutinya

Aturan yang dipegang: editor tidak boleh bisa menghasilkan tag yang dihapus
sanitizer. Karena itu memperluas toolbar mengharuskan memperluas
`config/html.php`:

- Tambahan: `s`, `code`, `pre`, `mark`, `sup`, `sub`, `hr`, `colgroup`, `col`.
  Ini melewati minimum PRD §22, jadi dicatat di sini sebagai divergensi resmi.
- Toolbar: strikethrough, kode sebaris, sorot, blok kode, garis pemisah,
  super/subskrip, H4, tabel (sisip + baris/kolom), gambar lewat picker media,
  dan perataan teks.
- Paket baru: `@tiptap/extension-{highlight,superscript,subscript,text-align,image,table}`.
- Gaya elemen baru ditambahkan ke `.rich-text` di `app.css` dengan token yang ada.

Image dan upload/poll berbagi satu hook, `hooks/use-media-upload.ts`; `MediaField`
dan dialog gambar editor sama-sama memakainya, supaya aturan "hanya simpan saat
`ready`" tinggal di satu tempat.

### 4. `style` hanya untuk `text-align`

Perataan teks Tiptap selalu keluar sebagai inline `style`. Symfony
**tidak** memfilter properti CSS — W3C reference-nya menandai `style` sebagai
tidak aman, dan attribute itu diterima atau ditolak utuh. Maka pembatasannya
dilakukan sendiri di `HtmlSanitizer::restrictTextAlignment()`, **sebelum**
sanitizer Symfony berjalan:

- Hanya deklarasi yang persis `text-align: left|center|right|justify` yang
  dipertahankan; nilai yang di-encode entitas atau dihias komentar tidak cocok
  dan ikut dibuang.
- Attribute yang tidak menyisakan deklarasi dihapus seluruhnya, jadi
  `<p style="x">` tetap menjadi `<p>` seperti sebelumnya.
- `style` hanya diberikan ke `p`, `h2`, `h3`, `h4`, `blockquote`, `li`, `th`,
  `td` — bukan ke `a` atau `img`.

`class` dan `id` tetap dilarang: allowlist `class` tidak bisa dibatasi nilainya,
dan di aplikasi Tailwind sebuah `class` sembarang (mis. `fixed inset-0`) sama
berbahayanya dengan CSS inline.

### 5. Yang tidak dilakukan

- Tidak memakai `shouldRerenderOnTransaction` (perilaku lama yang dihapus).
- Tidak mengizinkan `class`/`id`, dan tidak mengizinkan properti CSS selain
  `text-align`.
- Tidak mengubah kontrak path tersimpan (`featured_image`, `poster_path`); URL
  adalah field tambahan untuk pratinjau.

### 6. Test

- `HtmlSanitizerTest`: daftar tripwire diperbarui, tag baru diuji survive, dan
  enam kasus baru untuk pembatasan `text-align` (empat nilai, deklarasi lain
  dibuang, attribute kosong dihapus, nilai ter-encode ditolak, `style` tidak
  boleh di `a`).
- `PostTest`/`EventTest`: `featured_image_url`/`poster_url` di-resolve, dan null
  saat tidak ada gambar.
- Vitest: tombol toolbar menyala setelah mark aktif (regresi reaktivitas), dan
  `MediaField` menampilkan object URL lokal sebelum server menjawab.
- Playwright `editor-media.spec.ts`: klik Tebal → `aria-pressed` menjadi true
  dan `<strong>` muncul; pilih berkas → `[data-test="media-preview"] img`
  muncul dengan `src` `blob:`.

@see D-25 untuk allowlist dan alasan sanitizer ada

---

## D-51 — Skala heading rich text mengikuti `Text` h1–h4

**Status:** Accepted · branch `fix/media-preview-rich-text-editor`

`.rich-text h2`, `h3`, dan `h4` semuanya dipetakan ke `text-body-strong` (17px)
di `app.css`. Akibatnya outline dokumen tidak punya hierarki visual sama sekali:
memilih H2 di editor mengubah tag menjadi `<h2>` tetapi ukurannya tetap sama
dengan body, sehingga terlihat seperti toolbar yang tidak bekerja.

`components/text.tsx` sudah menetapkan pemetaan level heading yang benar untuk
seluruh aplikasi: `h1` display-lg, `h2` display-md, `h3` tagline, `h4`
body-strong. `.rich-text` hanya belum pernah disamakan dengannya. Keputusan ini
hanya menyelaraskan, bukan memilih skala baru:

- `.rich-text h2` → `text-display-md` (34px)
- `.rich-text h3` → `text-tagline` (21px)
- `.rich-text h4` → `text-body-strong` (17px)

Tidak ada aturan `h1` karena allowlist tidak punya `h1`; h1 adalah judul halaman
dan toolbar berhenti di H4 (D-25, D-50). Sanitizer tetap mempromosikan `<h1>`
hasil paste menjadi `<h2>`.

`tests/e2e/editor-media.spec.ts` mengunci regresinya: setelah menekan H2,
`font-size` dari `<h2>` harus lebih besar daripada teks body.

@see D-25, D-50 untuk allowlist dan editor penuh

---

## D-52 — Phase 6A Profil Paroki: schema, scope, dan divergensi dari PRD

**Status:** Accepted · branch `feat/phase-06a-parish-profile`

Phase 6A membangun modul Profil Paroki (`docs/roadmap/phase-6A-parish-profile.md`).
Audit dan keputusan tercatat lengkap di
`docs/plan-implementation/plan-phase-06a-parish-profile.md` §1. Ringkasan
keputusan yang mengikat:

1. **Visi & Misi dihapus total.** Route `profil.visiMisi`, halaman
   `features/public/profil/pages/visi-misi.tsx`, tautan di
   `parish-footer.tsx`, dan entri di `PublicRoutesTest` /
   `PublicPlaceholderRoutesTest` dihapus. Roadmap §3.2/§6A-88/§6A-139 melarang
   Visi & Misi di dalam Phase 6A. Ini **menyimpang dari PRD §5.1/§7.2** yang
   masih mendaftarkannya; PRD tidak diedit di tengah fase.
2. **Profil utama adalah tabel singleton `parish_profiles`**, bukan
   `parish_profile_sections` key-value (PRD §9.4) dan bukan site settings.
   Narasi sejarah disimpan pada kolom `history_description`, sehingga
   key-value generik tidak dibutuhkan dan pintu visi/misi tidak terbuka lagi.
3. **Pastor memakai dua tabel** `pastors` + `pastor_assignments`
   (roadmap §6.3), bukan satu tabel `clergy` (PRD §9.4). Satu orang dapat
   memiliki beberapa periode penugasan dan riwayat tidak terhapus.
4. **Periode disimpan sebagai tahun** `unsignedSmallInteger`, bukan tanggal
   penuh, untuk menghindari mengarang tanggal yang tidak diketahui.
5. **Wilayah pelayanan memakai tabel singleton `parish_service_areas`**
   (satu narasi + peta), bukan hierarki wilayah/stasi/lingkungan. PRD
   menegaskan hierarki wilayah dikelola modul Komunitas Phase 7 dan hanya
   ditampilkan di sini.
6. **`is_active`/`is_current` (operasional) + `is_published` (visibilitas
   publik)** dipakai bersama, sesuai pembedaan arti roadmap §7. `is_published`
   adalah tambahan di luar PRD §9.4.
7. **Kontak pengurus (`phone`/`show_contact`) tidak disimpan**, menyimpang dari
   PRD PROF-03 demi roadmap §6A-124 (tidak mempublikasikan data pribadi tanpa
   persetujuan).
8. **FK `parish_board_members.parish_board_period_id` = `cascadeOnDelete`.**
   Dialog hapus periode menyebut jumlah anggota.
9. **Tidak ada halaman overview admin.** Root `/admin/profil` adalah redirect
   bernama (mis. `parish-profile.index`) ke halaman Informasi Profil; sidebar
   menampilkan submenu langsung. Menyimpang dari roadmap §9.2/§6A-44…49.
10. **Permission mengikuti pola `entity.verb` satu kata:** `parishprofiles.*`
    (hanya `view`/`update` karena singleton; `parish_service_areas` memakai
    `parishprofiles.view`/`parishprofiles.update`), `parishhistories.*`,
    `pastors.*` (termasuk penugasan), `boardperiods.*`, `boardmembers.*`.
11. **Nama route admin tidak menabrak route publik `profil.*`** yang sudah ada;
    dipakai prefix `parish-*` (D-48 §3, D-49).

Seeder tidak mengisi satu pun data sejarah/pastor/jabatan/wilayah nyata; tabel
tetap kosong sampai paroki mengisi melalui admin (prinsip D-34).

@see `docs/roadmap/phase-6A-parish-profile.md`, `docs/plan-implementation/plan-phase-06a-parish-profile.md`

---

## D-53 — Seed data contoh untuk pratinjau (divergensi sadar dari D-14)

**Status:** Accepted · branch `feat/phase-06a-parish-profile`

D-14 mengosongkan `DatabaseSeeder` dari data fabrikasi. Atas permintaan pemilik
proyek, `DatabaseSeeder` sekarang memanggil `DemoContentSeeder` agar
`migrate:fresh --seed` menghasilkan situs yang bisa langsung dilihat: jadwal
misa, artikel, agenda, dan seluruh modul Profil Paroki terisi data contoh.

Isi dan aturannya:

1. **Ini data contoh, bukan data paroki.** Semua nilai adalah karangan untuk
   pratinjau UI. Tidak ada nomor telepon, alamat, koordinat, atau nama pejabat
   gereja yang nyata; `location.latitude/longitude`, `maps_url`, dan kontak
   dibiarkan kosong.
2. **Idempoten.** Setiap baris dibuat dengan `updateOrCreate` berkunci stabil
   (slug/nama/tahun), jadi menjalankan seeder berulang mengedit, bukan
   menduplikasi.
3. **Satu gambar untuk semua slot.** `public/images.jpg` disalin ke disk privat
   sebagai `media/originals/dummy-paroki.jpg` lalu diproses lewat pipeline
   `ImageProcessor` yang sama seperti unggahan biasa, sehingga path varian dan
   URL-nya identik dengan hasil unggahan nyata.
4. **Guard E2E.** `config/demo.php` menyediakan `demo.enabled`
   (`DEMO_SEED`, default `true`). `app:e2e:prepare` menetapkannya `false`
   sebelum `migrate:fresh --seed`, sehingga database Playwright tetap kosong
   dan deterministik. `composer setup` memakai `migrate --force` tanpa
   `--seed`, jadi tidak ikut terisi.
5. **Akun penulis contoh.** `posts.author_id` adalah FK wajib, jadi seeder
   membuat akun `redaksi-demo@example.test` (tidak dapat login karena kata
   sandinya acak). Akun ini bukan paroki dan tidak boleh dipakai di produksi.
6. **Pelayanan (Phase 06B).** `DemoContentSeeder` juga mengisi ketiga
   `service_categories` (deskripsi + ikon), keenam `services` (ringkasan,
   pengantar HTML, jadwal, biaya, kontak, `form_url` yang valid-secara-aturan
   tetapi jelas contoh, dan status), serta ketiga tabel anaknya
   (`service_requirements`, `service_steps`, `service_faqs`). Keenam layanan
   **diaktifkan hanya di lapisan demo** agar `/pelayanan` dan detailnya bisa
   ditinjau, dan keempat kondisi CTA PRD 7.6 diwakili (form terbuka, form
   ditutup, tanpa form, darurat). Konten diturunkan dari PRD Lampiran A sebagai
   contoh. Ini **melengkapi pembalikan D-5 untuk lapisan demo saja**:
   `ServiceSeeder` tetap men-seed keenam layanan nonaktif tanpa `form_url`
   (D-54), `ServiceSeederTest` tidak berubah, dan `app:e2e:prepare` tetap
   mematikan demo sehingga database Playwright bersih.

Konsekuensi pada media: resource Profil memakai `Media::url($variant)` yang
membaca relasi `variants`. Karena strict mode melarang lazy-load, seluruh
controller yang mengirim `*_url` kini meng-eager-load `*.variants`; tanpa itu,
gambar yang terisi akan melempar lazy-loading violation.

@see docs/DECISIONS.md D-14, D-24, D-52

---

## D-54 — Phase 6B Pelayanan: schema, flag publikasi, dan penundaan dokumen

**Status:** Accepted · branch `feat/phase-06b-parish-service`

Phase 6B membangun Modul Pelayanan (`docs/roadmap/phase-6B-parish-service.md`,
disusun di `docs/plan-implementation/plan-phase-06b-parish-service.md`). Audit
codebase dilakukan terhadap `main` pada commit `6b98aed`. Ringkasan keputusan
yang mengikat:

1. **Kategori layanan dinormalisasi dari ENUM ke tabel master
   `service_categories`.** PRD §9.8 (dan §Lampiran C) mendefinisikan
   `services.category` sebagai ENUM `('inisiasi','perkawinan','orang_sakit')`,
   sedangkan roadmap 6B §3.3/§6.1 mewajibkan kategori dikelola lewat Master Data
   (halaman `Kategori Pelayanan`). Schema sekarang memakai
   `services.service_category_id` FK → `service_categories.id`
   (`restrictOnDelete`). **Tidak** ada enum dan FK yang hidup berdampingan;
   enum tidak dipertahankan. Ini **menyimpang dari PRD** dan PRD tidak diedit di
   tengah fase (aturan repo). Kategori di-seed 3: `inisiasi`, `perkawinan`,
   `orang-sakit`.

2. **Slug kategori memakai tanda hubung `orang-sakit`,** bukan `orang_sakit`
   seperti enum PRD. Kategori adalah data yang dikelola admin, bukan enum kode,
   dan roadmap §6.1 menyebut slug `orang-sakit`.

3. **`is_active` adalah satu-satunya flag publikasi layanan.** PRD §9.8 dan
   roadmap 6B §6.2/§7.1 tidak mengenal `is_published`. Modul 6A memakai
   `is_active` (operasional) **dan** `is_published` (visibilitas), tetapi
   dokumen fase 6B yang mengikat di sini, jadi `services`/`service_categories`
   **tidak** punya `is_published`. Layanan baru selalu `is_active = false`;
   publik = `is_active = true` dan `deleted_at IS NULL`.

4. **Slug layanan tetap unik saat soft delete** — mengikuti `posts`/`events`
   (D-45), **bukan** `locations`/`mass_schedules` (D-40). Route publik
   `/pelayanan/{service:slug}` bind by slug, jadi dua baris yang menjawab satu
   slug membuat binding ambigu. Karena itu `services` **tidak** memakai generated
   column `active_marker`, dan `Rule::unique()` pada slug **tanpa**
   `->withoutTrashed()`.

5. **Soft delete hanya pada `services` dan `service_categories`; tabel anak
   (requirements/steps/faqs) hard delete** dengan FK `cascadeOnDelete`.

6. **`document_service` ditunda ke Phase 8.** Tabel `documents` belum ada dan
   roadmap 6B §6.6/6B-073 mengizinkan penundaan. Phase 6B **tidak** membuat
   migrasi pivot, tabel dokumen, atau modul Download alternatif. Tab "Dokumen
   Terkait" tidak dibuat; integrasi dicatat sebagai dependency.

7. **Permission: `services.*` + `servicecategories.*` saja** (4 verb masing-masing,
   total 8; 61 → 69). Tabel anak (requirements/steps/faqs) **tidak** punya grup
   permission sendiri; endpoint-nya diotorisasi lewat `ServicePolicy`
   (`services.update`) karena tidak ada halaman daftar mandiri untuk anak
   (RBAC.md §4). Mengikuti pola penugasan pastor yang ikut `pastors.*`.

8. **Route admin root `/admin/pelayanan` merender Daftar Pelayanan langsung**
   (nama route `services.index`), bukan redirect pola D-42. Roadmap 6B-041
   memintanya sebagai tujuan langsung item sidebar; tidak ada halaman overview.

9. **"Terakhir diperbarui" (SVC-03) memakai `$touches = ['service']`** pada tiga
   model anak, sehingga `services.updated_at` berubah saat anak dibuat, diubah,
   dihapus, atau diurutkan.

10. **Kontak global tetap satu sumber di `site_settings`.** Fallback kontak
    layanan: kontak khusus layanan → `site_settings` (`contact_phone`/
    `contact_whatsapp`) → pesan teks sopan. Tidak ada halaman/tabel/route
    Kontak kedua (PRD KON-04, AC-SVC-14).

11. **Util normalisasi kontak `App\Support\PhoneNumber`** (`toTel()`,
    `toWhatsApp()`) dibuat di Phase 6B dan dipakai ulang halaman Kontak Phase 8
    (PRD KON-01).

12. **Allowlist ikon `config/service-icons.php`** adalah sumber kebenaran key
    ikon yang valid; `resources/js/components/service-icon.tsx` merender Lucide
    dengan fallback netral. Admin tidak dapat mengirim kode ikon arbitrary.

13. **`docs/ADMIN_GUIDE.md` tidak dibuat pada Phase 6B** (ditunda atas
    keputusan pemilik proyek). Dokumentasi yang diperbarui hanya
    `docs/DECISIONS.md`, `docs/RBAC.md`, dan `docs/roadmap/README.md`.

14. **Form layanan dipecah per tab, bukan satu `<form>` yang membungkus semua
    tab.** Dialog anak (persyaratan, prosedur, FAQ) di-portal Radix ke `<body>`
    tetapi tetap keturunan React dari pembukanya; submit di dalam dialog
    menggelembung lewat React ke `<form>` induk dan mengirim PATCH layanan,
    bukan store anak. Satu `<form>` per tab skalar (Informasi Umum, Jadwal &
    Biaya, Kontak & Pendaftaran) memakai instance `useForm` yang sama, sehingga
    tidak ada form induk untuk digelembungi. Ditemukan dan ditutup oleh
    `tests/e2e/pelayanan.spec.ts`.

Seeder hanya membuat struktur: 3 kategori dan 6 layanan (nonaktif,
`form_url = null`). Tidak ada persyaratan/prosedur/FAQ contoh — Lampiran A PRD
tidak ditampilkan seolah konten resmi (prinsip D-34/D-14).

@see `docs/roadmap/phase-6B-parish-service.md`, `docs/plan-implementation/plan-phase-06b-parish-service.md`, `docs/DECISIONS.md` D-34, D-40, D-45, D-52

---

## D-55 — Phase 07 Komunitas: schema, gambar, area, dan navigasi

**Status:** Accepted · branch `feat/phase-07-communities`

Phase 07 membangun Modul Komunitas (`docs/roadmap/phase-07-communities.md`,
disusun di `docs/plan-implementation/plan-phase-07-communities.md`). Audit
codebase dilakukan terhadap `main` pada commit `578182d`. Ringkasan keputusan
yang mengikat:

1. **Kategori memakai tabel master `community_categories`, bukan
   `community_types` + `group enum`.** PRD §7.7/§9.9 mendefinisikan
   `community_types` dengan kolom `group enum('organisasi','doa_pelayanan')`,
   sedangkan roadmap §1.1/§5.1 mewajibkan `community_categories` dan melarang
   enum kategori. Kategori adalah data yang dikelola admin. Ini **menyimpang dari
   PRD** dan PRD tidak diedit di tengah fase (aturan repo).

2. **Logo & thumbnail memakai FK `*_media_id` ke tabel `media`, bukan kolom
   `logo_path`/`thumbnail_path`.** Roadmap §5.2.3 menyebut kolom string, tetapi
   seluruh modul terbaru memakai pipeline media: `parish_profiles.logo_media_id`,
   `pastors.photo_media_id`, `services.thumbnail_media_id`. `communities` memakai
   `logo_media_id` + `logo_alt` dan `thumbnail_media_id` + `thumbnail_alt`
   (nullable, `nullOnDelete`), diunggah via `MediaField`, divalidasi
   `Rule::exists('media','id')->where('status', Ready)`. Ini **divergensi sadar**
   dari roadmap §5.2.3.

3. **`areas` diperluas dari roadmap §5.3.** Roadmap §5.3 membatasi `type` ke
   `wilayah`/`lingkungan` tanpa field pengurus; PRD §9.9/Lampiran C menambah
   `stasi`, `leader_name`, `contact_phone`, `show_contact`, `prayer_schedule`
   (**catatan:** `prayer_schedule` kemudian dihapus di D-58).
   Schema mengikuti PRD (enum `AreaType`: `wilayah`/`lingkungan`/`stasi`), dan
   **menyimpang dari roadmap §5.3**. Hierarki (PRD COM-02): `wilayah`/`stasi`
   berparent null, `lingkungan` wajib berparent `wilayah` (tidak boleh `stasi`),
   maksimal dua tingkat, tanpa siklus, `lingkungan` tidak boleh punya anak.
   Aturan ini divalidasi di server.

4. **(*D-58 terbaru:* field `prayer_schedule` sudah tidak ada.)
   `leader_name`/`contact_phone`/`prayer_schedule` area dipublikasikan di
   halaman detail komunitas, dengan privasi server-side.** Roadmap §7 hanya
   mendefinisikan `/komunitas` dan `/komunitas/{slug}`; tidak ada halaman area
   publik. Ini **ekstensi sadar** atas roadmap §7: area yang direlasikan ke
   komunitas dirender di section "Wilayah Pelayanan", menampilkan nama, tipe, dan
   `prayer_schedule`; `leader_name` + `contact_phone` **dihilangkan dari payload
   Inertia** bila `show_contact = false` (XC-P1). Menambah AC-COM-14/15/16 dan
   test privasi area.

5. **Navigasi admin: `Komunitas` adalah link datar ke `/admin/komunitas`;
   `Kategori Komunitas` dan `Wilayah & Lingkungan` berada di grup Master Data.**
   Roadmap §6.1 mewajibkan menu Komunitas + submenu Kategori Komunitas di Master
   Data; Wilayah/Lingkungan tidak disebut dan ditaruh di Master Data bersama
   tabel referensi lain.

6. **Filter area disertakan di direktori publik** (roadmap §7.1/AC-PUB-04
   opsional "jika relevan"). Filter kategori + area, divalidasi, query-string
   dipertahankan saat pagination.

7. **Soft delete pada `communities`, `community_categories`, dan `areas`.** Slug
   tetap unik lintas soft delete (mengikuti `posts`/`events`/`services`, D-44/D-45),
   bukan melepas slot seperti `locations`/`mass_schedules` (D-40), karena
   `/komunitas/{community:slug}` mengikat berdasarkan slug. **Tanpa** generated
   column `active_marker`; `Rule::unique()` tanpa `->withoutTrashed()`. Tabel
   anak/pivot `community_area` hard delete (`cascade` dari komunitas).

8. **CTA kontak di detail publik memakai fallback `site_settings`**
   (`contact_phone`/`contact_whatsapp`), pola Pelayanan D-54 butir 10. Tampil
   hanya bila nilainya ada; tidak ada nomor palsu.

9. **`published_at` dikelola server dan diisi sekali** saat `is_published`
   berubah false→true dan masih null, tidak diubah saat unpublish/republish
   (pola `SavePostAction`, D-9 plan). Field ini tidak ada di rules request.

10. **Seeder hanya demo.** Tidak ada seeder produksi komunitas/kategori/area
    (roadmap §3.2/§17, prinsip D-34). `DemoContentSeeder` mengisi kategori, area
    (wilayah/lingkungan/stasi), dan komunitas contoh — tanpa kontak atau nama
    nyata — dijaga `config('demo.enabled')` (D-53). E2E dan `composer setup`
    tetap bersih.

11. **Permission: `communities.*` + `communitycategories.*` + `areas.*`** (4 verb
    masing-masing, total 12; 69 → 81). Semua satu kata (`/^[a-z]+\.[a-z]+$/`).
    Tabel pivot tidak punya grup tersendiri; relasinya dikelola lewat
    `communities.update`.

12. **Tidak ada `community_id` pada `posts`/`events`**, tidak ada relasi
    `Post`/`Event` ke `Community`, dan tidak ada section "berita/agenda terkait"
    di detail komunitas. Galeri foto komunitas (`community_photos`, album,
    lightbox) ditunda ke Phase 8. Mengikuti D-43.

@see `docs/roadmap/phase-07-communities.md`, `docs/plan-implementation/plan-phase-07-communities.md`, `docs/DECISIONS.md` D-34, D-43, D-44, D-52, D-53, D-54

---

## D-56 — Wilayah & Lingkungan sebagai halaman hierarkis, di luar Master Data

**Status:** Accepted · branch `feat/phase-07-communities`

**Konteks.** D-55 awalnya menaruh `Wilayah & Lingkungan` sebagai anak grup
**Master Data** dan menampilkannya sebagai tabel datar dengan kolom Tipe dan
Wilayah Induk. Saat ditinjau sebagai UX, dua hal jadi jelas: (1) `areas` bukan
tabel lookup datar seperti Lokasi/Kategori — ia hierarkis dan operasional
(punya ketua, kontak, jadwal doa, dan dirujuk komunitas); (2) sebuah hierarki
lebih mudah dibaca sebagai satu tampilan bertingkat daripada tersebar di
beberapa menu.

**Keputusan.**

1. **Satu resource, satu halaman, tanpa pemecahan menu.** `areas` tetap satu
   tabel/route/controller. Wilayah/Lingkungan/Stasi **tidak** dipecah menjadi
   tiga menu terpisah. Hierarkinya memang dua tingkat: `wilayah` dan `stasi`
   sama-sama top-level, hanya `lingkungan` yang punya induk `wilayah`
   (D-55 point 3). Memecahnya menjadi tiga menu akan menyiratkan tiga tingkat
   yang tidak ada dan menggandakan CRUD untuk satu tabel yang sama.

2. **Presentasi hierarkis di halaman indeks.** Daftar menampilkan setiap
   `wilayah` sebagai baris induk dengan `lingkungan`-nya ter-indentasi di
   bawahnya, `stasi` pada seksi terpisah, dan `lingkungan` tanpa induk pada
   seksi pengaman. Ini menggantikan tabel datar D-55; roadmap Phase 07 §6.3
   memang mengizinkan "tabel hierarkis".

3. **Item sidebar mandiri di luar Master Data.** `Wilayah & Lingkungan` menjadi
   item flat sendiri (ikon `MapPin`), berdampingan dengan `Komunitas`, bukan
   anak grup Master Data. URL pindah dari `/admin/master-data/wilayah-lingkungan`
   ke `/admin/wilayah-lingkungan`, dan halaman berpindah dari feature
   `admin/master-data` ke `admin/wilayah-lingkungan`. Nama route tetap
   `areas.*`. Tidak ada redirect URL lama: URL itu dibuat dan diubah di fase
   yang sama, belum pernah di-commit atau dibagikan.

4. **Pagination dihapus.** Daftar area dimuat utuh (tanpa pagination) karena
   jumlahnya kecil dan hierarki tidak boleh memisahkan anak dari induknya di
   halaman berbeda. Filter pencarian/tipe/status tetap server-side. Sorting
   kolom interaktif dihapus; urutan tetap `sort_order` lalu `name`, dan
   lingkungan mengikuti induknya.

**Konsekuensi yang harus diketahui.** Nama komponen Inertia berubah:
`admin/master-data/wilayah-lingkungan/index` → `admin/wilayah-lingkungan`, dan
tambah/ubah ikut. `AreaTest` dan E2E `master-data.spec.ts` disesuaikan;
spec baru `tests/e2e/wilayah-lingkungan.spec.ts`. Sejak D-39 prop `areas`
dikirim sebagai list non-paginasi (`array_values(...->resolve())`), bukan
`{data: [...]}`.

@see `docs/DECISIONS.md` D-39, D-48, D-55

---

## D-57 — Area (dulu "Stasi & Lingkungan") sebagai modul mandiri, relasi komunitas dilepas, dan halaman publik

**Status:** Accepted · branch `feat/phase-07-communities`

**Konteks.** D-55/D-56 membangun `areas` di dalam modul Komunitas: sebuah
komunitas dapat memilih beberapa area sebagai "Wilayah Pelayanan", halaman
admin-nya bernama "Wilayah & Lingkungan", dan area hanya muncul di dalam
halaman Komunitas. Atas keputusan pemilik proyek, area kini menjadi modul
tersendiri dan **tidak** terkait komunitas, serta tampil di situs publik.

**Keputusan.**

1. **Relasi komunitas↔area dilepas.** Tabel pivot `community_area` dihapus
   (migrasi `drop_community_area_table`), `Community::areas()` dan
   `Area::communities()` dihapus, dan pemilihan area hilang dari form komunitas
   serta dari halaman publik komunitas (termasuk filter wilayah pada direktori
   komunitas). Komunitas berdiri sendiri. Ini **menyimpang dari PRD §9.9 /
   Lampiran C** (D-03) dan PRD tidak diedit di tengah fase.

2. **Modul area mandiri.** Controller, request, dan resource pindah ke
   namespace `App\Http\Controllers\Area` / `App\Http\Requests\Area` /
   `App\Http\Resources\Area`; form meta dan copy punya trait serta bahasa
   sendiri (`AreaFormMeta`, `lang/id/area.php`). Nama tabel/model
   tetap `Area`/`areas`, permission tetap `areas.*`, dan nama route admin tetap
   `areas.*`. URL admin berpindah dari `/admin/wilayah-lingkungan` ke
   `/admin/area`, dan route publik menjadi `/area` dengan nama `area.index` /
   `area.show` (module Wayfinder `@/routes/area` berdampingan dengan
   `@/routes/areas` milik admin); label UI menjadi **"Area"** (admin dan
   publik; sempat pertama "Stasi & Lingkungan", diganti karena "Area" lebih
   pendek dan
   sudah sama dengan nama tabel, route, dan permission `areas.*`). Daftar
   admin adalah **satu tabel yang bisa diurutkan** (D-41), bukan tiga tabel per
   tipe: urutan default `type, sort_order, name` lewat `SortsAdminList`, dengan
   whitelist `name`, `type`, `is_active`, `sort_order`. Hubungan anak–induk dibaca
   dari garis-kiri pada sel Nama dan kolom "Wilayah Induk" — kolom itu sengaja
   tidak bisa diurutkan karena butuh join. Halaman publik tetap dikelompokkan
   per tipe; hanya admin yang diratakan.

3. **Lingkungan boleh tanpa induk.** Aturan hierarki PRD COM-02 dilonggarkan:
   `wilayah` dan `stasi` tetap top-level, dan `lingkungan` boleh top-level
   **atau** berinduk `wilayah`. Paroki HSPMTB tidak memiliki wilayah, sehingga
   lingkungan pusatnya berdiri sendiri. Bila induk diisi, ia tetap harus
   `wilayah` aktif.

4. **Halaman publik baru.** `GET /area` (direktori, dikelompokkan
   per tipe: stasi, lingkungan, wilayah) dan `GET /area/{area:slug}`
   (detail). Hanya area `is_active` yang tampil; detail nonaktif/tak dikenal
   menjawab 404 lewat `Area::resolveRouteBinding`. Detail memuat nama, tipe,
   deskripsi, ketua, kontak (hanya bila `show_contact`, XC-P1), dan
   daftar lingkungan anak. **Tidak ada** daftar komunitas. Tidak ada gambar per
   area (area tidak punya kolom media). Tidak ada pagination — daftar area kecil
   dan pencarian dilakukan di sisi klien.

5. **Navigasi publik.** Menu **"Area"** ditambahkan di navbar
   (setelah Komunitas) dan di footer (grup Profil Paroki). Halaman
   `/profil/wilayah` tetap memuat narasi singleton, dan kini menautkan ke modul
   baru alih-alih menyebut "modul Komunitas".

**Konsekuensi yang harus diketahui.** `CommunitySchemaTest` kehilangan uji pivot;
schema area pindah ke `tests/Feature/Area/AreaSchemaTest.php` dan halaman publik
ke `tests/Feature/Area/PublicAreaPageTest.php`. `AreaTest` pindah ke dua
assertion baru (lingkungan tanpa induk diterima). Komponen halaman area admin
berubah nama: `admin/wilayah-lingkungan/*` → `admin/area/*`. E2E
`wilayah-lingkungan.spec.ts` → `area.spec.ts`.

@see `docs/DECISIONS.md` D-39, D-43, D-55, D-56

---

## D-58 — `prayer_schedule` dihapus dari `areas`

**Status:** Accepted · branch `feat/phase-07-communities`

**Konteks.** PRD §9.9 dan Lampiran C menyebut satu field per area: "jadwal doa
lingkungan" (`prayer_schedule`). D-55 point 3 mengikutinya ke schema karena PRD
memintanya. Paroki ternyata tidak memakai jadwal doa per area.

**Keputusan.** Kolom `prayer_schedule` **dihapus sepenuhnya**, bukan
disembunyikan: migrasi baru `2026_10_18_000000_drop_prayer_schedule_from_areas_table`
(rollback hanya memulihkan bentuk kolom, isinya hilang — itu jaminan yang jujur
untuk perubahan skema destruktif), aturan validasi, `AreaResource` dan ketiga
resource publik, factory, seeder demo, field form admin, kolom tabel admin
(tabel kini **7 kolom**), kartu direktori publik, halaman detail publik (termasuk
daftar anak), dan `lang/id/area.php` (semula `stasi-l_toolbar.php`). Nama label di sidebar/navbar/footer, header halaman, dan breadcrumb adalah **"Area"**; copy yang menjelaskan isi ("stasi, wilayah, dan lingkungan") tetap deskriptif.

**Konsekuensi yang harus diketahui.** Ini **divergensi sadar dari PRD §9.9 /
Lampiran C**; PRD tidak diedit di tengah fase, hanya catatan ini. D-55 point 3
dan 4 menyebut field tersebut dan ditandai di sana. `AreaSchemaTest` kini
memastikan kolomnya `false`, supaya tidak kembali diam-diam bersama migrasi
berikutnya. Kolom `leader_name` dan `contact_phone` **tetap** ada — keduanya
dipakai untuk kontak ketua pada halaman publik (XC-P1).

@see `docs/DECISIONS.md` D-55, D-57

---

## D-59 — Tautan media sosial per komunitas

**Status:** Accepted · branch `feat/phase-07-communities`

**Konteks.** Proyek meminta kolom media sosial untuk setiap komunitas:
`facebook`, `instagram`, `tiktok`, `youtube`, dan `website`. Yang terakhir
berarti **situs milik komunitas itu sendiri** — situs paroki sudah ada di dua
tempat (`site_settings` dan `parish_profiles.website`), dan tidak boleh tercampur
dengan milik komunitas.

PRD §9.9 tidak menyebut media sosial per komunitas. Satu-satunya tautan sosial
yang disebut PRD adalah milik paroki (`social_facebook`, `social_instagram`,
`social_youtube`, `social_whatsapp_channel` di pengaturan situs, dipakai di
footer dan halaman kontak). Jadi kolom ini **ekstensi sadar di luar PRD**, dan PRD
tidak diedit di tengah fase.

**Keputusan.**

1. **Lima kolom, bukan enam.** Tidak ada kolom `url`: ia akan menduplikasi
   `website`, dan dua nama untuk satu makna adalah cara pasti mengisi kolom yang
   salah lalu mengira yang lain sudah terisi.

2. **Validasi `url` biasa, maks 255** — meniru `parish_profiles.website`, dengan
   pesan Indonesia per kolom. Bukan kunci host per platform: `facebook.com`
   bukan satu-satunya alamat Facebook yang dipakai paroki, dan `fb.me` atau
   `youtu.be` justru tautan yang benar-benar ditempel orang. Tidak ada permintaan
   HTTP keluar dari form admin.

3. **Tidak ada switch izin terpisah.** Mengisi tautan adalah bentuk persetujuan
   publik, sama seperti mengisi deskripsi. `show_contact` tetap governs kontak
   pribadi, bukan tautan yang komunitas pilih sendiri untuk ditulis.

4. **`socials` dihilangkan seluruhnya saat kosong.** `PublicCommunityDetailResource`
   mengirim satu grup `label` + `url`, dan **tidak mengirim key-nya sama sekali**
   bila kelima kolom kosong — perlakuan yang sama dengan `contact`. Halaman
   publik tidak pernah menerima lima `null` yang harus ditafsirkan.

5. **Label ikut dikirim**, bukan dibaca lagi dari string domain di React.
   Halaman publik merender satu daftar; menebak platform dari URL adalah tebakan
   yang seharusnya terjadi di server.

6. **Tempat di UI.** Tab admin ketujuh, "Media Sosial", berisi lima `FormInput`
   bertipe `url` dengan placeholder berplatform; di halaman detail publik, seksi
   "Media Sosial" (`surface="soft"`) disembunyikan bila `socials` tidak ada.
   Tautan keluar memakai `target="_blank" rel="noopener noreferrer"`, sama
   seperti tombol WhatsApp di halaman itu.

**Konsekuensi yang harus diketahui.** Migrasi baru
`2026_10_18_000001_add_social_links_to_communities_table` — yang
`create_community_tables` sudah dijalankan di mana-mana tidak disentuh. Form
komunitas kini tujuh tab, dan `TAB_FIELDS` di orkestratornya memuat lima field
baru supaya badge error dan lompat-ke-tab ikut menghitungnya. Tidak ada
permission baru, jadi `docs/RBAC.md` tidak berubah.

@see `docs/DECISIONS.md` D-55, D-57, D-58

---

---

## D-60 — Visi dan misi komunitas jadi rich text

**Status:** Accepted · branch `feat/phase-07-communities`

**Konteks.** Visi dan misi adalah dua pernyataan yang biasanya ditulis dengan
penekanan: kalimat pengantar tebal, daftar, sesekali tautan ke dokumen. Dua
`textarea` memaksa admin meniru semua itu dengan asterisk dan baris baru.

**Keputusan.**

1. **Editor Tiptap penuh** (`components/rich-text-editor.tsx`) menggantikan dua
   textarea pada tab Profil Komunitas. Field ini **tidak** memakai `Field`
   seperti field lain: `Field` merender `Label` yang terikat ke `htmlFor`,
   sedangkan badan editor adalah `div contenteditable` — label seperti itu tidak
   memberi apa-apa ke pembaca layar, sementara editor sudah membawa nama
   aksesibelnya sendiri lewat prop `label`. Pola ini mengikuti
   `pelayanan/components/tab-informasi-umum.tsx`.

2. **Sanitasi di server, di akhir `prepareCommunityInput()`.** `vision` dan
   `mission` melewati `HtmlSanitizer` dengan allowlist `config/html.php`.
   Panggilan diletakkan **setelah** `merge($input)` terakhir, bukan sebelumnya:
   sanitasi lebih dulu akan menulis nilai bersih lalu ditimpa lagi dua baris
   kemudian oleh nilai asal yang hanya di-trim, dan gejalanya terbaca sebagai
   "sanitasi tidak melakukan apa-apa" bukan sebagai merge yang hilang.

3. **Editor yang dikosongkan menjadi `null`.** Tiptap tidak mengirim string
   kosong untuk dokumen kosong, melainkan `<p></p>`. Nilai itu truthy, jadi
   halaman publik akan menggambar heading "Visi" di atas apa pun tidak ada.
   `isBlankMarkup()` mencocokkan **hanya** pembungkus paragraf kosong — dokumen
   yang hanya berisi gambar adalah `<p><img ...></p>` dan tidak cocok, jadi visi
   berbentuk satu gambar tetap tersimpan. Memakai `toPlainText()` di sini akan
   membuang gambar itu.

4. **Halaman publik merender lewat `RichText`.** `components/rich-text.tsx` adalah
   satu-satunya tempat `dangerouslySetInnerHTML` di repo, dan allowlist-nya
   (`config/html.php`) tidak berubah — tidak ada tag baru yang diizinkan.

5. **Batas `max:5000` tetap**, dan diukur pada markup yang tersimpan, bukan pada
   teks yang dilihat admin.

**Yang tidak berubah.** `description`, `meeting_schedule`, `eligibility`, dan
`joining_info` tetap plain text: tidak ada permintaan rich text untuk mereka, dan
mencampur dua jenis penyimpanan dalam satu form adalah cara agar renderernya
lupa membedakan keduanya.

@see `docs/DECISIONS.md` D-25, D-46, D-50, D-55, D-59

---

## D-61 — Alamat dan titik peta untuk area

**Status:** Accepted · branch `feat/phase-07-communities`

**Konteks.** Empat kolom baru di `areas`: `address`, `latitude`, `longitude`,
`maps_url`, dengan koordinat opsional. Modul `locations` sudah menyimpan keempatnya
sejak Fase 04, jadi pertanyaannya bukan "apa bentuknya" melainkan "apakah dua
modul boleh berbeda bentuk".

**Keputusan.**

1. **Salin, jangan karang ulang.** Tipe kolom (`string(2000)`, `string(2048)`,
   `decimal(10,7)`), cast `decimal:7`, aturan rentang, pesan, dan atribut
   semuanya diambil apa adanya dari `locations`. Dua modul yang sama-sama
   menyimpan koordinat harus menyimpannya dengan cara yang sama; aturan yang
   ditulis untuk satu diam-diam tidak berlaku untuk yang lain.

2. **Pasangan koordinat wajib berdua.** Satu setengah dari pasangan ditolak,
   karena angka tunggal tidak bisa membangun tautan peta. Diperiksa di dalam
   closure `after()` — satu-satunya tempat aman untuk menambahkan error aturan
   lintas field, karena `Validator::passes()` mengosongkan message bag di awal,
   sehingga error yang ditambahkan saat validator masih dibangun akan terhapus
   sebelum aturan berjalan. Catatan yang sama sudah berdiri di
   `LocationValidationRules`.

3. **`Area::mapsUrl()`** meniru `Location::mapsUrl()`: `maps_url` menang, kalau
   tidak pasangan koordinat, kalau tidak `null`. Alamat tidak pernah dipakai
   menyusun tautan — alamat separuh menghasilkan tautan ke gedung yang salah,
   lebih buruk daripada tidak ada tombol.

4. **Publik mengirim alamat dan tautan jadi, bukan koordinat mentah.** Resource
   publik memakai `Area::mapsUrl()`, persis seperti `PublicLocationResource`,
   sehingga React tidak perlu tahu apakah pasangan koordinat ada. Koordinat bukan
   data pribadi, tetapi halaman juga tidak membutuhkannya.

5. **Tanpa switch izin.** Alamat adalah tempat, bukan orang; `show_contact`
   tetap governs nama ketua dan nomor teleponnya. PRD NFR-PRIV melarang
   menampilkan data pribadi tanpa persetujuan, dan ini bukan data pribadi.

6. **Satu blok bertitled di form, bukan tab baru.** Form admin area masih datar,
   dan keempat field ini masuk sebagai blok "Lokasi dan Peta" berisi alamat,
   lintang, bujur, dan tautan peta. Merestrukturisasi form itu menjadi bertab
   adalah pekerjaan tersendiri dan tidak diminta di sini.

**Divergensi dari PRD.** PRD §9.9 tidak menyebut alamat maupun koordinat untuk
area — ia hanya memberi `leader_name`, `contact_phone`, dan `show_contact`. Ini
ekstensi sadar; PRD tidak diedit di tengah fase.

@see `docs/DECISIONS.md` D-55, D-57, D-60
---

## D-62 — Uploader berita dan agenda memakai komponen uploadcn di atas pipeline `/admin/media`

**Status:** Accepted · branch `feat/phase-07-communities`

**Konteks.** Scaffolding uploadcn sudah ada di repo — `ui/upload.tsx`,
`ui/image-cropper.tsx`, `hooks/use-crop-queue.ts`, dan blok
`components/upload/*.tsx` — tetapi belum dipakai satu pun halaman, dan
`components/upload/upload-config.tsx` memasang adapter
`s3Adapter({ endpoint: '/api/upload' })`. Route `/api/upload` **tidak ada** di
aplikasi ini: pipeline-nya adalah `POST /admin/media`, satu baris database, dan
satu job antrean. Dipakai apa adanya, form berita tidak gagal dengan tampilan
yang salah. Dropzone, dialog potong, progress bar, dan tombol coba ulang
semuanya tetap bekerja rapi di sekitar sebuah 404.

**Keputusan.**

1. **Adapter, bukan route.** `lib/media-upload-adapter.ts` membungkus
   `createAdapter()` dari `@uploadcn/core` lalu meneruskan ke `postMediaFile()`
   dan `waitForMedia()` di `lib/media-client.ts`. Protokol s3 yang dipakai
   uploadcn tidak diimplementasikan di server; pipeline yang sudah ada dan sudah
   diuji tetap satu-satunya jalan. Adapter global di `upload-config.tsx`
   diganti ke `mediaUploadAdapter`, dan `maxSize` 50 MB pada provider itu
   dihapus karena tidak pernah berlaku di aplikasi ini.

2. **Satu implementasi poll.** Permintaan HTTP dipindah dari
   `hooks/use-media-upload.ts` ke `lib/media-client.ts`, dan hook itu memanggilnya.
   `MediaField` (tujuh form lain, dihapus di D-64) dan dialog sisip-gambar di
   editor tidak berubah perilakunya; keduanya sekarang berbagi satu
   `waitForMedia()`. Poll punya plafon 80 percobaan dan menghormati
   `AbortSignal`, jadi komponen yang ditinggalkan tidak meninggalkan timer yang
   berdenyut setiap 1,5 detik.

3. **`media_id` hanya dari baris yang sudah `ready`.** Aturan `media_id` di
   `PostValidationRules` dan `EventValidationRules` hanya menerima `ready`,
   karena unggahan masuk antrean: baris media ada sebelum varian-variannya ada
   di disk. Adapter menolak mengembalikan baris yang belum selesai, dan
   `MediaUploadField` memeriksanya sekali lagi sebelum menulis ke form.

4. **Batas unggah lewat shared prop `media`, bukan konstanta.** `config/media.php`
   yang memutuskan MIME yang diterima dan ukuran maksimum, dan angkanya dikirim
   sebagai shared prop dari `HandleInertiaRequests::share()` — pola yang sama
   dengan `seo` — dengan cerminnya di `types/index.ts`. `MediaUploadField`
   melempar error yang menyebut D-62 bila prop itu hilang, bukan memakai nilai
   cadangan. Nilai cadangan adalah jawaban kedua untuk pertanyaan yang sama, dan
   itulah cara batas 3 MB melenceng jadi 10 MB di satu form saja.

5. **JPEG, bukan WebP.** `compressImage()` memakai `image/webp` sebagai `type`
   bawaan ketika `compress` aktif, sedangkan `config/media.php` hanya menerima
   `image/jpeg` dan `image/png`. Tanpa `type="image/jpeg"` yang eksplisit, berkas
   yang sudah dipotong admin ditolak server dengan pesan tentang sebuah field.

6. **Rasio potong eksplisit, dan tidak ada crop bebas.** Berita 16:9, agenda 4:5.
   `ImageCropper` punya `aspect = 1` sebagai nilai bawaan dan `react-easy-crop`
   mewajibkan `aspect: number`, jadi `crop={true}` berarti persegi dan bukan
   bebas. `ImageUpload` kini menerima `accept`, `title`, `description`,
   `cropTitle`, `cropDescription`, `itemActions`, dan `children`, semuanya dengan
   bawaan yang membuat blok itu tetap sama seperti yang dikirim registri.

7. **Gambar yang sudah tersimpan tetap terlihat.** Antrean mulai kosong setiap
   kali halaman dimuat, jadi uploader yang hanya menampilkan antrean mengatakan
   "tidak ada gambar" pada artikel yang punya satu, dan admin membacanya sebagai
   "gambar saya hilang". `MediaUploadField` menampilkan gambar yang sedang
   dipegang formulir beserta tombol **Ganti gambar**, dirender di dalam konteks
   `Upload` supaya bisa memakai `UploadTrigger`.

8. **Tidak ada tombol hapus.** `media_id` null berarti "tidak berubah"
   (`SavePostAction`), jadi mengosongkan antrean tidak melepas gambar dari form
   yang akan dikirim. Tombol pada tile yang sudah jadi berarti "pilih yang lain",
   bukan "lupakan" — sampai ada pustaka media yang bisa menghapus baris, tidak
   ada hapus yang jujur di sini.

9. **Komponen bersama, bukan dua salinan.** `MediaUploadField` dipakai dua
   feature, jadi ia ada di `components/` (§1.1). `MediaField` lama tidak
   disentuh pada saat itu — sempat tujuh form (Profil lima, Komunitas,
   Pelayanan), lalu tinggal lima pemakaian, dan akhirnya dihapus di **D-64**.

**Divergensi dari PRD.** Tidak ada. POST-04 dan XC-M3 tetap dipenuhi oleh
pipeline yang sama; yang berubah adalah lapisan antarmukanya.

**Utang yang diketahui.** Dialog potong memakai `<dialog>` native dan tombolnya
berlabel `Cancel` dan `Apply` — bahasa Inggris di dalam admin berbahasa
Indonesia. Label itu ada di `components/ui/image-cropper.tsx`, yang berasal dari
registri dan tidak diedit tangan karena aturan AGENTS.md; judul dan deskripsinya
bisa diterjemahkan lewat prop, label tombolnya tidak. Menembus aturan itu perlu
keputusan tersendiri.

@see `docs/DECISIONS.md` D-22, D-24, D-25, D-50
@see `docs/PRD.md` POST-04, XC-M3, HOME-05
---

## D-63 — `MediaUploadField` bervariant: enam bentuk, tiga di antaranya belum punya backend

**Status:** Accepted · branch `feat/phase-07-communities`

**Konteks.** `components/upload/` menyimpan enam blok uploadcn — `image`,
`avatar`, `logo`, `gallery`, `document`, `file` — dan sampai sekarang hanya
`image` yang dipakai, lewat `MediaUploadField` (D-62). Setiap form yang butuh
gambar lain masih memakai `MediaField` yang lebih tua: tombol, input tersembunyi,
pratinjau, tanpa crop dan tanpa antrean. Tujuannya satu komponen yang tinggal
dipilih lewat prop. (Sisa pemakaian `MediaField` itu sendiri dihapus di D-64.)

Yang membatasi bukan selera, melainkan backend. `/admin/media` satu-satunya
endpoint unggah; `config/media.php` menerima `image/jpeg` dan `image/png` saja;
`IsProcessableImage` membaca bytes-nya; dan `GenerateImageVariants` dikirim untuk
**setiap** baris yang ditulis. Tidak ada tempat menyimpan PDF atau dokumen: baris
`media` selalu berakhir di pipeline gambar. Galeri lebih jauh lagi — ia
menghasilkan N baris, sedangkan setiap kolom form hanya menampung satu
`*_media_id`. Endpoint, tabel dan izin untuk keduanya ada di **Phase 8 — Galeri,
Download & Kontak**, yang di `roadmap/README.md` masih ⚪ Planned dan belum punya
dokumen fase.

**Keputusan.**

1. **Satu prop `variant`, enam nama.** `MediaUploadField` menjadi facade:
   `image` (default, dua pemanggil lama tidak berubah), `avatar`, `logo`,
   `gallery`, `document`, `file`. `variant` dipilih alih-alih `tipe` karena
   prop lain di komponen ini berbahasa Inggris (`altValue`, `currentUrl`,
   `fieldName`) dan `document-upload.tsx` sudah memakai nama `variant` untuk
   hal serupa.

2. **Discriminated union, bukan satu tas properti opsional.** Tiap member hanya
   membawa prop yang bisa ia pakai: `aspect` cuma di `image` (karena `avatar`
   mengunci persegi dan `logo` tidak memotong), dan tiga variant tanpa backend
   hanya membawa `id`/`label`/`hint`. Memberi mereka `onMediaChange` akan
   mendeskripsikan kontrak yang tidak bisa ditepati — dokumen tidak punya baris
   di `media`, galeri butuh banyak baris.

3. **Tiga variant dirender sebagai pemberitahuan, bukan pemilih berkas.**
   `gallery`, `document` dan `file` menampilkan panel "Belum tersedia" dan
   **tidak** merender `input[type="file"]`. Merender bloknya sekarang berarti
   menaruh kontrol di layar yang setiap percobaannya berakhir sebagai 422 tentang
   sebuah field. Ini juga bukan mendahului phase: tidak ada tabel, endpoint atau
   izin yang dibangun di sini — hanya nama variant dan bentuk callback, sebagai
   seam yang Phase 8 isi. `onMediaChangeMany(ids: number[])` sudah ada di tipe
   `gallery` untuk alasan yang sama, dan satu test memastikan tidak ada input
   berkas yang bocor ke sana.

4. **`removable` opt-in, default `false`,** karena apakah `null` mengosongkan
   sebuah kolom adalah fakta tentang model, bukan tentang komponen. `posts` dan
   `events` membaca `null` sebagai "tidak berubah" (`SavePostAction`), jadi di
   sana tombol hapus akan melepas nol barang. `parish_board_members`,
   `pastors` dan `communities` menulis `null` apa adanya
   (`$model->update($validated)`, `SaveCommunityAction::fill`), jadi di tiga form
   itu tombolnya diaktifkan. Sebelum ini tidak ada cara sama sekali menghapus
   foto pengurus.

5. **Blok uploadcn boleh diedit untuk prop, bukan untuk menjadi bahasa
   Indonesia.** `avatar-upload.tsx` kini menerima `accept`, `type`, `maxSize`,
   `cropTitle`, `hint`, `uploadLabel`, `changeLabel`, `removeLabel` dan
   `removable`; `logo-upload.tsx` menerima `src`, `accept`, `removable`,
   `onRemove`, `footer` dan label yang sama. Semua bawaannya tetap seperti yang
   dikirim registri, dan pemanggil (facade) yang mengirim bahasa Indonesia serta
   batas nyata dari `config/media.php`.

6. **`type="image/jpeg"` juga wajib untuk avatar.** `avatar-upload.tsx` mengunci
   `output.type = 'image/webp'` dan `accept = 'image/*'`, dan `logo-upload.tsx`
   menawarkan SVG dan WebP. Untuk server ini ketiganya berakhir sebagai berkas
   yang ditolak setelah admin memotongnya — persis jebakan yang D-62 catat untuk
   `image-upload.tsx`, sekarang di tiga tempat.

7. **`name` tidak pernah diteruskan ke `AvatarUpload`.** `Upload` uploadcn
   merender input tersembunyi bernama `name` dengan `submit` bawaan `"value"`,
   jadi `<AvatarUpload name="photo_media_id">` menyuntikkan
   `<input type="hidden" name="photo_media_id" value="gereja.png">` ke dalam form
   — dan kolom itu `nullable|integer`, sehingga setiap submit berakhir 422.
   `LogoUpload` tidak meneruskan `name` ke uploader-nya (hanya untuk inisial
   placeholder), jadi hanya avatar yang punya jebakan ini.

8. **Gambar yang tersimpan tetap terlihat di setiap variant.** Antrean mulai
   kosong setiap halaman dimuat, jadi `AvatarUpload` dan `LogoUpload` diberi
   `src`, dan `logo-upload.tsx` merendernya di kedua pratinjau (terang dan
   gelap). `logo-upload.tsx` sebelumnya hanya bisa menampilkan berkas yang baru
   dipilih.

9. **`removable` untuk logo dirender oleh bloknya, bukan facade.** Logo yang
   tersimpan tidak ada di antrean, sehingga tombol hapus bawaan blok tidak bisa
   menjangkaunya; `logo-upload.tsx` mendapat satu tombol yang melaporkan
   penghapusan ke form. Untuk avatar hal yang sama dilakukan `AvatarRemove`.
   Hanya varian `image` yang memakai tombol di dalam tile facade, karena
   bloknya memang tidak punya konsep itu.

10. **Perilaku tanpa prop alt dipertahankan.** Ketiga prop alt opsional; bila
    `altLabel`/`onAltChange` tidak diberikan, tidak ada field teks alternatif
    yang dirender. Ketika diberikan tanpa `hint`, facade memakai kalimat
    aksesibilitas yang selama ini dibawa `MediaField`, supaya tiga form yang
    dikonversi tidak kehilangan panduannya.

**Divergensi dari PRD.** Tidak ada. XC-M5 (alt text pada gambar kunci) tetap
dipenuhi: field alt masih ada di kelima form, dan sekarang lebih banyak form
punya tombol hapus.

**Utang yang diketahui.** `logo-upload.tsx` tidak punya dialog potong, jadi
sebuah logo tidak bisa dipaksa persegi seperti avatar. Label `Cancel`/`Apply`
pada dialog potong masih berbahasa Inggris (D-62). `lib/media-upload-adapter.ts`
belum tahu apa-apa tentang berkas non-gambar, dan itu memang urusan Phase 8.

@see `docs/DECISIONS.md` D-22, D-24, D-62
@see `docs/roadmap/README.md` Phase 8 — Gallery, Download & Contact
---

## D-64 — `MediaField` dihapus; lima pemakainya pindah ke `MediaUploadField`

**Status:** Accepted · branch `feat/phase-07-communities`

**Konteks.** D-63 membuat `MediaUploadField` bervariant tetapi sengaja membiarkan
`MediaField` hidup untuk lima pemakaian: logo dan sampul profil paroki, peta
wilayah pelayanan, thumbnail layanan, dan gambar peristiwa sejarah. Dua picker di
satu aplikasi berarti dua aturan: `MediaField` menegakkan batas ukuran lewat
`accept="image/jpeg,image/png"` yang ditulis di komponennya sendiri, sedangkan
`MediaUploadField` membacanya dari shared prop `media` (D-62). Satu di antaranya
akan melenceng, dan yang pertama melenceng adalah yang tidak punya test yang
menahannya.

**Keputusan.**

1. **`MediaField` dan test-nya dihapus**, dan kelima pemakaiannya memakai
   `MediaUploadField`. Tidak ada shim kompatibilitas: yang tersisa hanyalah lima
   pemanggil, semuanya di repo ini, dan sebuah shim hanya akan memindahkan
   pekerjaan ke kemudian hari.

2. **`emptyHint` ditambahkan** ke member `image` dan `logo`. Kelima pemakaian
   membawa copy yang tidak boleh hilang — dua di antaranya menyampaikan informasi
   nyata ("Thumbnail bersifat opsional.", "Foto bersifat opsional."), bukan hanya
   "belum ada". Pada `image` ia menjadi caption yang dirender hanya ketika form
   tidak membawa gambar **dan** antrean kosong; pada `logo` ia menggantikan
   `emptyLabel`. Tidak ditambahkan ke `avatar` karena tidak ada pemakainya dan
   bloknya sudah punya baris keterangan sendiri.

3. **`aspect` menjadi opsional** pada member `image`; tanpa `aspect` tidak ada
   dialog potong dan tidak ada kalimat tentang potong di dropzone. Alasannya
   peta wilayah pelayanan: halaman publik merendernya `w-full object-cover`,
   dan mengunci 16:9 di form berarti memotong jalan dari peta secara sengaja
   dan tak terlihat. Bentuk yang tidak dibingkai tidak boleh dipaksa masuk
   bingkai.

4. **Rasio untuk empat yang tersisa:** thumbnail layanan 16:9, karena kartu
   publiknya memang `aspect-video`; sampul profil 16:9; gambar sejarah 16:9.
   Logo tidak dipotong — blok `logo-upload.tsx` memang tidak punya dialog potong
   (D-63).

5. **`removable` diaktifkan pada kelimanya.** `logo_media_id`, `cover_media_id`,
   `map_media_id`, `thumbnail_media_id` dan `media_id` semuanya nullable dan
   ditulis apa adanya oleh `create`/`update`/`fill()->save()` dengan
   `validated()`, tanpa trik "null berarti tidak berubah" seperti `SavePostAction`
   di posts dan events (D-62). Jadi `null` benar-benar mengosongkan, dan sebelum
   ini kelima field itu tidak bisa dihapus sama sekali — hanya ditimpa.

6. **Field alt untuk peta dihapus, bukan dibiarkan kosong.** `service-area-form`
   memanggil `MediaField` dengan `altValue=""`, `altLabel=""` dan
   `onAltChange={() => undefined}`, yang merender field teks berlabel kosong yang
   tidak bisa diisi siapa pun dan tidak disimpan ke kolom mana pun — tabel
   `parish_service_areas` tidak punya kolom alt. `MediaUploadField` tidak
   merender field alt bila `altLabel`/`onAltChange` tidak diberikan, jadi
   pemanggilnya cukup tidak memberikannya.

**Divergensi dari PRD.** Tidak ada. XC-M5 tetap dipenuhi di form yang punya kolom
alt; peta memang tidak pernah punya.

**Utang yang diketahui.** `hooks/use-media-upload.ts` kini punya satu konsumen
saja (dialog sisip-gambar editor) dan tinggal di `hooks/` alih-alih pindah ke
dalam editor. Label `Cancel`/`Apply` pada dialog potong masih bahasa Inggris
(D-62), dan sekarang muncul di lebih banyak form.

@see `docs/DECISIONS.md` D-22, D-24, D-62, D-63
---

## D-65 — Modul Galeri (Phase 8A): skema berbasis Media, sampul, dan status

**Status:** Accepted · branch `feat/phase-8a-galleries`

**Konteks.** Phase 8A membangun modul Galeri: album dan foto, admin dan publik.
PRD §9.10 menggambarkan `gallery_albums` dan `gallery_photos` dengan kolom path
(`path`, `thumb_path`, `original_path`, `width`, `height`, `file_size`), sedangkan
seluruh modul terbaru menyimpan gambar sebagai baris `media` dan meresolusi URL
dari sana (`communities.logo_media_id`, `pastors.photo_media_id`,
`services.thumbnail_media_id`). Roadmap `phase-8A-galleries.md` §6.1 mengizinkan
penyesuaian nama tabel/kolom dengan konvensi proyek. Tujuh keputusan diambil
sebelum implementasi dan mengikat.

**Keputusan.**

1. **Foto menunjuk `media`, bukan kolom path.** `gallery_photos` berisi
   `gallery_album_id`, `media_id`, `caption`, `alt_text`, `sort_order`. Ini
   divergensi sadar dari PRD §9.10: satu berkas diproses sekali oleh pipeline
   `/admin/media` (varian `thumb`/`medium`/`large`, WebP, EXIF hilang) dan dipakai
   ulang, sementara baris album hanya menyimpan urutan dan keterangan. Original
   tetap di disk privat (D-24).

2. **Sampul menunjuk foto di album, bukan Media.** `gallery_albums.cover_photo_id`
   → `gallery_photos` (`nullOnDelete`), dan `null` jatuh ke foto pertama menurut
   `sort_order` (PRD §9.10 dan GAL-07). Karena sampul adalah baris foto album,
   foto yang dilepas tidak bisa meninggalkan sampul menggantung. Referensi
   sirkular (`gallery_photos.gallery_album_id` ↔ `gallery_albums.cover_photo_id`)
   diselesaikan dengan menambah kolom cover lewat `Schema::table` setelah tabel
   foto dibuat.

3. **Status `is_published` boolean + `published_at`.** Tanpa enum status dan
   tanpa status Arsip: PRD §9.10 hanya menyebut satu flag, dan hanya dua keadaan
   yang ada. `published_at` disetel sekali saat pertama terbit oleh
   `SaveGalleryAlbumAction`, tidak pernah diterima dari request.

4. **Limit unggah mengikuti `config/media.php` apa adanya: JPEG/PNG, 3 MB, tanpa
   WebP.** PRD GAL-01/AC-GAL-6 menyebut JPG/PNG/**WebP** dan **5 MB**; roadmap §10
   menyuruh menyelaraskan frontend/backend dengan `config/media.php` dan tidak
   memakai limit berbeda. Mengubah limit akan menyentuh **semua** modul + shared
   prop `media` + test Media, jadi limit tetap. Divergensi dicatat di sini.

5. **Slug unik lintas soft delete; soft delete hanya pada album.** Mengikuti
   D-44/D-45/D-55, bukan aturan `active_marker` (D-40): route publik membinding
   `{gallery:slug}`, jadi dua baris yang menjawab satu slug membuat binding
   ambigu. `gallery_photos` tidak punya `deleted_at` — melepas foto berarti
   menghapus barisnya.

6. **`ON DELETE` eksplisit.** `gallery_photos.gallery_album_id` **cascade**
   (force delete album menghapus barisnya; soft delete tidak menjalankan cascade,
   sehingga restore mengembalikan album utuh). `gallery_photos.media_id`
   **cascade** — arahnya dari Media ke baris foto, tidak pernah sebaliknya; ini
   yang membuat `MediaController::destroy()` tetap bekerja. `cover_photo_id`
   **set null**. Alternatif `restrict` pada `media_id` ditolak karena akan
   mengubah "hapus gambar yang dipakai album" menjadi 500.

7. **Permission `galleries.view/create/update/delete` saja.** Tidak ada
   `galleries.publish`: publikasi adalah field biasa di form update (D-36). Semua
   operasi foto — attach, reorder, caption/alt, sampul, detach — diotorisasi lewat
   `GalleryAlbumPolicy` pada `galleries.update`, karena foto tidak punya halaman
   daftar sendiri (pola D-54 dan D-55 point 11).

8. **Tanpa kategori album; filter publik = tahun.** PRD §7.8 menyebut filter
   tahun, bukan kategori; roadmap §6.3 menyatakan kategori bukan syarat MVP.
   **Tanpa** `is_featured` dan **tanpa** `sort_order` album — belum ada konsumen
   (beranda adalah Phase 9, dan kolom tanpa pembaca melanggar prinsip D-24).
   **Tanpa** `post_id`/`event_id`/`community_id` (PRD D-04/§9.10): roadmap §6.1
   tidak mencantumkannya dan tautan berita/agenda/komunitas dicatat sebagai
   follow-up.

9. **Kelola foto di halaman terpisah** `/admin/galeri/{gallery}/foto`, bukan tab
   di form album: form tetap ringkas dan pengelola foto — unggah batch, urutan,
   sampul, caption — punya ruang.

10. **Uploader multi-file tinggal di feature**
    (`features/admin/galeri/components/gallery-photo-upload.tsx`), memakai
    `lib/media-client.ts` yang sudah ada. Setiap berkas diunggah independen dan
    hanya `media` berstatus `ready` yang dikirim ke
    `/admin/galeri/{gallery}/foto`; kegagalan satu berkas tidak membuang yang
    berhasil (GAL-08). Variant `gallery` pada `MediaUploadField` dibiarkan sebagai
    seam dan tidak diaktifkan (D-63).

11. **Urutan publik deterministik**: `event_date` terbaru dahulu (yang kosong di
    akhir), lalu `published_at`, lalu `id`.

**Divergensi dari PRD.** (a) `gallery_photos` menyimpan `media_id`, bukan kolom
path/ukuran (poin 1). (b) Format/batas unggah 3 MB JPEG/PNG tanpa WebP, bukan
5 MB JPG/PNG/WebP (poin 4). (c) Tidak ada `post_id`/`event_id`/`community_id`
(poin 8). Selebihnya sesuai: XC-E3 (draf 404), GAL-05 (pengingat tanggung jawab
foto anak), GAL-07 (sampul fallback), GAL-08 (batch independen), XC-M1/M2/M3.

**Utang / keterbatasan yang diketahui.** Foto pada album draf disimpan sebagai
varian di disk publik; URL-nya berbasis path UUID yang tidak terenumerasi, sama
seperti seluruh media lain di aplikasi, jadi ia mengikuti kebijakan storage yang
ada dan bukan jaminan privasi. Berkas yang diunggah lewat browser tidak dapat
diselesaikan di E2E karena queue `database` tidak punya worker, sehingga fixture
album ditanam langsung oleh `tests/e2e/support/gallery.ts`. Tautan album ke
berita/agenda/komunitas dan integrasi beranda menyusul di Phase 9.

@see `docs/DECISIONS.md` D-24, D-36, D-44, D-54, D-55, D-63
@see `docs/roadmap/phase-8A-galleries.md`
@see `docs/PRD.md` 7.8, 9.10

---

## D-66 — Modul Dokumen (Phase 8B): pipeline privat, status enum, dan integrasi layanan

**Status:** Accepted · branch `feat/phase-8b-documents`

Phase 8B membangun modul **Dokumen** (`docs/roadmap/phase-8b-documents.md`,
disusun di `docs/plan-implementation/plan-phase-8b-documents.md`). Audit
dilakukan terhadap `main` pada commit `3c2c3f1` dan seluruh keputusan produk
dikonfirmasi pemilik proyek sebelum implementasi. Ringkasan yang mengikat:

1. **Berkas dokumen memakai pipeline terpisah dari Media.** `config/documents.php`
   menentukan disk privat (`DOCUMENTS_DISK`, default `local`), path `documents`,
   MIME/ekstensi, dan batas 10 MB. `config/media.php` tidak diubah. Dokumen tidak
   punya baris `media` dan tidak melewati `GenerateImageVariants`.

2. **Status dokumen adalah enum `draft`/`published`/`archived`.** PRD §9.11
   menyarankan `is_published bool`, tetapi PRD §9 mengizinkan penyesuaian tipe
   selama perilakunya terpenuhi, dan roadmap 8B §4.1/§6.1 meminta tiga keadaan.
   `published_at` diisi sekali saat pertama published dan tidak diterima dari
   request. Hanya `published` yang tampil publik dan dapat diunduh.

3. **Tidak ada slug dan tidak ada halaman detail dokumen.** Roadmap menyebut slug
   opsional hanya bila ada halaman detail; PRD tidak memintanya. Route publik
   adalah `/dokumen` dan `/dokumen/{document}/unduh` (by id). Konsekuensinya tidak
   ada `resolveRouteBinding` slug dan tidak ada `active_marker`.

4. **Format mengikuti PRD DL-01**: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP;
   maks 10 MB. finfo melaporkan DOCX/XLSX/PPTX sebagai `application/zip`, jadi
   `App\Rules\AllowedDocumentFile` memeriksa isi ZIP (`[Content_Types].xml` plus
   `word/document.xml`/`xl/workbook.xml`/`ppt/presentation.xml`) dan menyimpan
   MIME kanonik Office. Legacy `.doc/.xls/.ppt` adalah kontainer OLE2; bila
   libmagic melaporkannya sebagai `application/x-ole-storage` atau
   `application/CDFV2`, ekstensi menentukan MIME kanonik Office-nya. `.php` yang
   dinamai `.pdf` tetap ditolak. OOXML membutuhkan `ext-zip`; host juga harus
   menaikkan `upload_max_filesize` dan `post_max_size` ke minimal 10 MB/12 MB.

5. **Hapus = soft delete baris + hapus file fisik.** Tidak ada UI restore. Pivot
   `document_service` dilepas dalam transaksi yang sama, file dihapus setelah
   commit. Replace menyimpan file baru dulu, dan baru menghapus file lama setelah
   transaksi berhasil serta tidak ada baris lain yang mereferensikannya.

6. **Nama unduhan = slug judul + ekstensi kanonik** (PRD DL-04), bukan nama asli.
   `original_filename` disimpan hanya sebagai metadata. Respons attachment
   mengirim `Content-Type` dari `mime_type` kanonik, bukan hasil finfo atas path
   ZIP, sehingga DOCX/XLSX/PPTX tidak dikirim sebagai `application/zip`. Counter
   bertambah atomik dengan `DB::table('documents')->increment('download_count')`
   hanya untuk respons `attachment`; pratinjau `?inline=1` tidak menghitung.

7. **Pratinjau PDF** memakai route unduh yang sama dengan `?inline=1` untuk publik
   (`published` + PDF) dan route admin `documents.preview` untuk semua status.
   Tidak ada counter pada keduanya.

8. **Dokumen unggulan** (`is_featured`) tampil sebagai seksi terpisah di `/dokumen`
   dan badge di kartu. Boleh disimpan pada draf, tetapi hanya berpengaruh publik
   saat `published`. Seksi unggulan hanya dirender saat halaman tidak sedang
   mencari atau memfilter, supaya hasil filter tidak tercampur dokumen di luar
   filter.

9. **`expires_at` hanya peringatan.** Dokumen yang lewat tanggal tetap tampil dan
   tetap dapat diunduh; badge “Kedaluwarsa” muncul di admin dan publik. Ini
   mengikuti roadmap §9.1 yang melarang auto-hide tanpa persetujuan.

10. **Integrasi dokumen-layanan menutup penundaan D-54.** Pivot
    `document_service` (composite key, cascade) dan relasi `belongsToMany` di kedua
    model. Selector “Dokumen Terkait” ada di form Pelayanan (tab, mengikuti
    `services.update`); hanya dokumen `published` yang boleh ditautkan baru,
    sementara tautan lama yang diarsipkan tetap terlihat di form dan tidak tampil
    publik. Detail Pelayanan menampilkan “Dokumen Terkait” hanya berisi dokumen
    published.

11. **Kategori Dokumen adalah Master Data** di
    `/admin/master-data/kategori-dokumen` (`document-categories.*`), di-seed lima
    kategori PRD §9.11 dengan `firstOrCreate`. Kategori nonaktif tidak muncul di
    filter publik, tidak dapat dipilih untuk dokumen baru, dan slug-nya yang
    dikirim langsung lewat URL diabaikan (halaman kembali menampilkan semua
    dokumen Terbit), tetapi dokumen Terbit yang memakainya tetap tampil. Kategori
    tidak dapat dihapus selama masih dipakai, termasuk oleh dokumen soft-deleted
    (`withTrashed()->count()`); penolakan itu ditampilkan sebagai Alert di halaman
    daftar kategori.

12. **Redirect permanen untuk URL lama.** `/download` → `/dokumen`,
    `/download/{id}/unduh` → `/dokumen/{id}/unduh`, `/admin/download` →
    `/admin/dokumen`, `/admin/kategori-download` →
    `/admin/master-data/kategori-dokumen`. Semua redirect diberi nama `legacy.*`
    agar Wayfinder tidak gagal (D-48 §3).

13. **Permission: `documents.*` + `documentcategories.*`** saja (4 verb
    masing-masing, total 8; 85 → 93). Tidak ada `documents.publish`; publikasi dan
    arsip adalah field `status` pada update (D-36, D-65). `DocumentPolicy` punya
    `view` untuk pratinjau admin.

**Divergensi dari PRD.** (a) Enum status menggantikan `is_published` (poin 2).
(b) Tidak ada `slug`/halaman detail (poin 3). (c) `documents` menyimpan
`file_disk`/`file_path`/`original_filename` dan metadata tambahan
(`version_label`, `document_date`, `expires_at`, `is_featured`,
`download_count`, audit user) sesuai roadmap §6.1. (d) Sebagian URL berubah dari
PRD §5.1/§5.2 (`/dokumen`, `/admin/dokumen`,
`/admin/master-data/kategori-dokumen`) dan URL lama diarahkan. Selebihnya
memenuhi DL-01 sampai DL-08, AC-DL-1 sampai AC-DL-6, XC-E3, dan NFR-SEC.

**Utang / keterbatasan yang diketahui.** E2E tidak membaca database secara
langsung; counter unduhan diverifikasi di test Pest, bukan di browser. Pratinjau
admin dan publik hanya untuk PDF. `expires_at` tidak menyembunyikan dokumen. Tidak
ada versioning berkas historis: replace menimpa file aktif dan menghapus file
lama. `docs/ADMIN_GUIDE.md` belum dibuat (sama seperti Phase 6B). Batas 10 MB di
production tetap bergantung pada `php.ini`/host.

@see `docs/DECISIONS.md` D-24, D-36, D-44, D-45, D-48, D-54, D-63, D-65
@see `docs/roadmap/phase-8b-documents.md`
@see `docs/PRD.md` 7.10, 9.11, DL-01..DL-08, AC-DL-1..AC-DL-6
