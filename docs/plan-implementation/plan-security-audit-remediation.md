# Rencana Implementasi Perbaikan Keamanan (`plan-security-audit-remediation.md`)

| Atribut   | Nilai                                                                                                                         |
| --------- | ----------------------------------------------------------------------------------------------------------------------------- |
| Dokumen   | Rencana Implementasi Perbaikan Keamanan                                                                                       |
| Produk    | Website Resmi Gereja Katolik Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau                                         |
| Status    | Draft — siap direview dan dieksekusi                                                                                          |
| Sumber    | `.security-audit/REPORT.md`, `.security-audit/FINDINGS-DETAIL.md`, `.security-audit/NEEDS-VALIDATION.md`, `docs/DECISIONS.md` |
| Cakupan   | Perbaikan 6 vulnerability terkonfirmasi, 3 temuan *needs validation*, dan catatan hardening                                   |
| Prioritas | P0 (Critical/High) → P1 (Medium) → P2 (Low/Hardening)                                                                         |
| Tanggal   | 2026-11-07                                                                                                                    |

---

## 1. Tujuan dan prinsip

Rencana ini bertujuan menutup celah keamanan yang ditemukan dalam audit menyeluruh (*read-only*) terhadap codebase, tanpa mengubah perilaku aplikasi yang tidak diperlukan.

**Prinsip yang dipegang:**

1. **Revalidasi sebelum coding:** Karena `coverage-ledger.json` hanya mencatat pemeriksaan source code (tidak ada local reproduction atau verifier independen), setiap temuan `confirmed` harus direproduksi secara lokal dan direklasifikasi sebelum patch diterapkan.
2. **Pertahankan keputusan proyek yang sudah ada:** D-20 (pesan akun nonaktif spesifik), D-90 (operator mengelola `INSTALLER_ENABLED`), D-107 ( trusted proxy `X-Forwarded-Host`), dan D-59 (URL bebas host) tetap berlaku, dengan penyesuaian minimal yang disepakati.
3. **Tidak ada asumsi:** Setiap perubahan yang ambigu telah diklarifikasi dan disepakati (kebijakan restore eksternal, perilaku sesi, batas skema URL, validasi dump SQL, dan cutover sesi).
4. **Eksternal tetap didukung:** Arsip backup eksternal tetap dapat dipulihkan (tanpa HMAC wajib), namun ditandai sebagai "asal tidak terverifikasi" dan harus menolak tabel operasional yang dilarang spesifikasi.

---

## 2. Tahapan Implementasi

### Fase 0 — Revalidasi temuan audit (Pre-requisite)

Sebelum menulis patch, lakukan revalidasi lokal untuk memastikan `confirmed` findings benar-benar valid dan sesuai dengan keputusan proyek.

- **Tugas:**
  1. Uji klaim keamanan yang dapat direproduksi (mis. `preg_replace` backreference di `EnvironmentWriter`, validasi ekstensi file di `BackupPathGuard`).
  2. Pisahkan temuan backup menjadi tiga root cause independen: validasi file, isi SQL, dan invalidasi sesi.
  3. Jangan klaim **PHP RCE** dari arsip backup tanpa bukti runtime (deployment menggunakan Nginx yang menyajikan `/storage/` sebagai file statis).
  4. Klasifikasikan shell injection workflow dan URL sosial sebagai *hardening* kecuali revalidasi membuktikan dampak lintas trust boundary.
- **Output:** Perbarui status `confirmed`/`needs_validation` di `.security-audit/findings.json` dan `.security-audit/REPORT.md` jika ada perubahan verdict.

### Fase 1 — P1: Amankan backup dan restore (`backup-restore-unrestricted-archive-and-sql`)

**File terdampak:** `BackupPathGuard.php`, `BackupArchiveInspector.php`, `RestoreBackupJob.php`, `confirm-restore-dialog.tsx`.

1. **Validasi ekstensi file per scope:**
   - `media/variants`: hanya `.webp` valid.
   - `media/originals`: format yang diterima pipeline media (`.jpg`, `.jpeg`, `.png`).
   - `documents`: ekstensi dan MIME dari konfigurasi dokumen (`config('documents.extensions')`).
2. **Tolak tabel operasional pada dump SQL:**
   - Tambahkan satu dependency SQL parser yang kompatibel dengan dump MySQL 8.
   - Tolak dump yang menyentuh tabel `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, atau tabel backup.
3. **Validasi konten file di staging:**
   - Periksa MIME type dan (untuk gambar) dimensi maksimum sebelum menulis ke storage aktif.
   - Pertahankan pemeriksaan path, checksum, ukuran, dan batas arsip yang sudah ada.
4. **Invalidasi sesi setelah restore:**
   - Hapus semua sesi autentikasi di tabel `sessions` setelah restore database dimulai (termasuk hasil parsial). Jangan gunakan `Cache::flush()` (berisiko menghapus lock dan flag maintenance).
   - Tampilkan peringatan di UI bahwa restore mengakhiri sesi aktif (semua pengguna harus login ulang).
5. **Label asal tidak terverifikasi:**
   - Tampilkan peringatan di `show.tsx` dan `confirm-restore-dialog.tsx` bahwa backup unggahan (source `uploaded`) tidak memiliki verifikasi kriptografis asal. Pertahankan safety backup dan konfirmasi `PULIHKAN DATA`.

**Tes (Pest):** Perluas `tests/Unit/Backup/BackupPathGuardTest.php`, `BackupArchiveInspectorTest.php`, dan `tests/Feature/Backup/BackupRestoreTest.php`. Tambahkan kasus file berbahaya, tabel operasional terlarang, dump normal, restore parsial, serta invalidasi sesi.

### Fase 2 — P1: Amankan siklus hidup sesi (`session-persistence-after-password-change`)

**File terdampak:** `bootstrap/app.php`, `SecurityController.php`, `ResetUserPassword.php`.

- Tambahkan middleware Laravel `auth.session` ke grup middleware admin.
- Pada `SecurityController::update()`, gunakan `Auth::logoutOtherDevices()` dengan password saat ini. Pertahankan sesi yang dipakai untuk perubahan.
- Pada `ResetUserPassword::reset()`, pastikan sesi lama ditolak (Fortify sudah merotasi `remember_token`, dan `auth.session` akan memutus sesi dengan hash password lama).
- **Cutover deployment:** Saat rollout pertama, kosongkan sesi autentikasi lama satu kali (karena sesi lama belum memiliki hash password untuk dibandingkan). Semua admin harus login ulang.

**Tes (Pest):** Perluas `tests/Feature/Settings/SecurityTest.php` dan `tests/Feature/Auth/PasswordResetTest.php` untuk memverifikasi sesi kini tetap aktif saat ganti password, sesi lain ditolak, dan sesi lama gagal setelah reset.

### Fase 3 — P2: Batasi percobaan login akun nonaktif (`auth-inactive-account-oracle-before-password-check`)

**File terdampak:** `app/Http/Middleware/EnsureAccountCanLogIn.php`.

- Pertahankan pesan `auth.inactive` sesuai D-20 (pesan khusus, bukan generik).
- Tambahkan percobaan akun nonaktif ke `LoginRateLimiter` Fortify yang sama (increment).
- Jangan ubah menjadi pesan generik tanpa keputusan baru.

**Tes (Pest):** Perluas `tests/Feature/Auth/AdminAccessTest.php` dan `AuthenticationTest.php`. Verifikasi pesan D-20 tetap sama dan limiter akhirnya menolak percobaan berulang.

### Fase 4 — P2: Amankan tautan sosial komunitas (`community-social-links-unsafe-url-schemes`)

**File terdampak:** `CommunityValidationRules.php`, `PublicCommunityDetailResource.php`, `resources/js/features/public/komunitas/pages/show.tsx`, `docs/DECISIONS.md`.

- Terima hanya skema `http` dan `https`; host tetap bebas (sesuai D-59, tanpa pembatasan host).
- Tolak URL dengan kredensial `user:pass@`.
- Filter juga data lama/import pada resource publik (`PublicCommunityDetailResource::socialLinks()`); jangan hanya mengandalkan validasi form.
- Catat perubahan kebijakan HTTP(S) di `docs/DECISIONS.md` (update D-59).

**Tes (Pest):** Perluas `tests/Feature/Komunitas/CommunityTest.php` dan `PublicCommunityDetailTest.php`. Uji HTTP(S), skema non-web, kredensial URL, dan data legacy.

### Fase 5 — P2: Perbaiki penulisan `.env` dan pesan error installer (`installer-env-writer-corruption-and-injection`)

**File terdampak:** `EnvironmentWriter.php`, `InstallationRunner.php`, `MailConnectionTester.php`.

- Reproduksi dulu efek `$n` pada `preg_replace()` dengan PHP 8.3.
- Jika terbukti, ganti replacement string dengan `preg_replace_callback()`.
- Gunakan encoding yang kompatibel dengan parser Dotenv (uji round-trip nilai `$`, backslash, tanda kutip, `#`, `=`, dan newline).
- Jangan menulis `INSTALLER_ENABLED=false`; D-90 menyerahkan flag itu kepada operator.
- Ganti exception SMTP mentah dengan pesan Bahasa Indonesia yang aman (catat kelas error saja, tanpa kredensial atau pesan server).

**Tes (Pest):** Tambahkan test terisolasi untuk `EnvironmentWriter`; perluas test installer untuk memastikan pesan respons tidak memuat detail exception. Pertahankan installer access gate yang sudah dikonfirmasi.

### Fase 6 — P3: Hardening workflow CI (`ci-workflow-pr-title-command-injection`)

**File:** `.github/workflows/tests.yml`.

- Pindahkan judul PR ke `env: PR_TITLE:`.
- Validasi dengan `printf '%s\n' "$PR_TITLE" | npx --no-install commitlint`.
- Klasifikasikan sebagai *hardening*, bukan vulnerability terkonfirmasi, kecuali revalidasi menemukan dampak tambahan (job saat ini bersifat read-only tanpa secret dan hanya berjalan untuk PR dari repo yang sama).

### Fase 7 — Validasi deployment dan penutupan

- **Jangan** mengubah `trustProxies`, menghapus `X-Forwarded-Host`, atau memaksa skema URL. Pertahankan D-107 dan tes redirect HTTPS yang sudah ada.
- Pertahankan konfigurasi `SESSION_SECURE_COOKIE=true`, security headers edge, dan installer access gate sesuai fakta deployment yang telah diverifikasi.
- Jalankan tes terdampak terlebih dahulu, lalu `composer ci:check` dengan database test MySQL.
- Jalankan `composer audit` dan `npm audit --omit=dev`; jangan mengubah dependency lain tanpa advisory terkonfirmasi.
- Setelah hasil tes tersedia, koreksi verdict dan coverage di `.security-audit/`; validator format saja bukan bukti keamanan.

---

## 3. Syarat Selesai (`Definition of Done`)

1. **Arsip valid tetap dapat dipulihkan:** File dengan path, tipe, atau isi terlarang ditolak *sebelum* menyentuh storage aktif.
2. **Restore eksternal tetap berfungsi:** Diberi label "asal tidak terverifikasi", tidak dapat memulihkan tabel operasional, dan membutuhkan konfirmasi admin.
3. **Sesi aman:** Restore mengakhiri sesi aktif; pergantian password mempertahankan sesi kini dan mencabut sesi lain.
4. **D-20 tetap berlaku:** Pesan akun nonaktif tetap ditampilkan, tetapi percobaannya dibatasi limiter.
5. **Tautan aman:** Tautan komunitas hanya memakai HTTP(S); penulisan `.env` tidak mengubah nilai khusus.
6. **Audit diperbarui:** Hanya menyebut temuan `confirmed` setelah reproduksi dan verifikasi yang diwajibkan berhasil.
7. **Tidak ada coding atau perubahan file pada langkah ini:** Plan ini hanya panduan untuk dieksekusi setelah disetujui.

### Risiko Tersisa yang Disetujui

- Arsip eksternal tidak memiliki verifikasi kriptografis asal; keputusan restore tetap tanggung jawab admin yang berwenang.
