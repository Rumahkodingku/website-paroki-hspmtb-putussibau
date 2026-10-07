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

**Konsekuensi:** Docker untuk MySQL menyusul pada Phase 01 Workstream 04 (P04).
Sampai saat itu, developer memerlukan MySQL yang sudah berjalan di host.

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

## D-09 — 2FA dan Passkeys akan dihapus

**Status:** Accepted · **belum dieksekusi** · blok terpisah setelah P03

Starter kit menyalakan `Features::twoFactorAuthentication()` dan
`@laravel/passkeys`. Keduanya akan dinonaktifkan dan berkasnya dihapus karena
Phase 01 §4 memasukkan "2FA" ke Out of Scope dan PRD §12 menundanya ke P1.

**Kenapa blok terpisah:** penghapusan menyentuh ~12 file React, 3 file test,
`config/fortify.php`, dan kolom `two_factor_*`. Mencampungkannya dengan P02
akan membuat verifikasi satu blok terlalu berat.

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

## Known divergences (belum diselesaikan)

| # | Deviasi | Risiko / alasan | Rencana |
| --- | --- | --- | --- |
| 1 | `HandleInertiaRequests::share()` mengirim **seluruh model User** (`'user' => $request->user()`), padahal ARCHITECTURE.md Part C §2/§5 dan PRD §11 melarangnya. | Risiko kebocoran kolom (`email_verified_at`, `two_factor_*`) ke browser. | P07 — kirim hanya `id`, `name`, `email`, plus ringkasan role/permission |
| 2 | Flash message memakai `Inertia::flash('toast', ...)` + `useFlashToast()`, bukan shared prop `flash` seperti ARCHITECTURE.md Part C §6. | Kontrak berbeda dari dokumen; perlu diputuskan sebelum layout publik dibangun. | P07 |
| 3 | `display_timezone` belum dikirim ke frontend. | UI belum bisa merender WIB. | P07 (shared prop `displayTimezone`) |
| 4 | `Model::preventLazyLoading()` / `shouldBeStrict()` belum aktif. | ARCHITECTURE.md Part A §6 dan PRD §3.3 mewajibkannya. | P13 — setelah Fortify/Passkeys/Settings diaudit |
| 5 | ~~`DatabaseSeeder` masih membuat `test@example.com`.~~ **Ditutup di P03** (D-14). | — | Selesai |
| 6 | `Features::registration()` masih aktif sehingga `/register` publik hidup. | Melanggar AUTH-R2 dan Phase 01 §F. | P06 |
| 7 | Tabel `site_settings` sudah ada, tetapi belum ada model, `SiteSettingsService`, cache, maupun halaman `/admin/pengaturan`. | Tidak ada single access point untuk pengaturan situs. | P09 (D-12) |
| 8 | `is_active` sudah ada di skema, tetapi belum ada yang menegakkan aturan "akun nonaktif tidak dapat login". | PRD D-08 belum berlaku penuh. | P06 |

---

## Yang masih diperlukan (Phase 01)

P03 database · P04 Docker · P05 Spatie Permission · P06 authentication ·
P07 Inertia foundation · P08 UI & layout · P09 site settings · P10 media ·
P11 sanitasi · P12 error/SEO/infrastruktur · P13 quality · P14 Git · P15 docs.

Referensi versi untuk langkah berikutnya: `spatie/laravel-permission` **8.3.0**
sudah kompatibel (`php ^8.3`, `illuminate/* ^12.0|^13.0`).
