# Audit Log

Dokumen operasional dan kebijakan untuk modul Audit Log. Spesifikasi asalnya ada
di `docs/specifications/spec-audit-log.md`; keputusan arsitektur tercatat sebagai
D-79 di `docs/DECISIONS.md`.

## 1. Tujuan dan ruang lingkup

Audit Log mencatat aktivitas administratif penting di panel pengelola paroki:
siapa melakukan apa, kapan, pada objek apa, dengan hasil apa. Modul ini bukan
sistem SIEM, bukan pengganti log error Laravel, dan bukan jaminan bahwa data
tidak dapat dimanipulasi oleh administrator database/server.

Cakupan MVP: berita, agenda, jadwal misa, profil/pastor/kepengurusan/pelayanan,
pengaturan situs, autentikasi, media, dokumen, master data, komunitas, area, dan
galeri.

## 2. Retensi

- Kebijakan awal: **12 bulan** untuk aktivitas administratif biasa.
- Belum ada job pembersihan otomatis pada MVP.
- Tidak ada tombol “hapus semua log”.
- Peninjauan retensi dilakukan sebelum produksi dan minimal setahun sekali
  bersama pemilik sistem.
- Jika auto-purge ditambahkan kemudian, operasi pemeliharaan itu sendiri harus
  dicatat dan memerlukan otorisasi terbatas.

## 3. Data yang disimpan

Setiap event menyimpan:

- aktor: `actor_id` (nullable), `actor_type` (`user`, `system`, `anonymous`),
  snapshot `actor_name` dan `actor_email`;
- aksi dan modul berupa kode stabil;
- objek: `subject_type`, `subject_id` (string), dan `subject_label`;
- deskripsi Bahasa Indonesia;
- hasil: `success`, `failure`, atau `denied`;
- snapshot `before`/`after` yang sudah melalui allowlist;
- metadata allowlisted;
- `request_id`, `ip_address`, dan `user_agent`;
- `occurred_at` dalam UTC dan `created_at`;
- `idempotency_key` bila event sistem membutuhkannya.

### 3.1 Field allowlist

Allowlist berada di `config/audit.php` pada kunci `fields`, `metadata`, dan
`summarize`. Field yang tidak terdaftar tidak pernah masuk snapshot, walaupun
kolomnya ada di model. Menambah field audit berarti mengubah config, bukan hanya
model.

### 3.2 Redaksi

Key yang mengandung `password`, `password_confirmation`, `current_password`,
`token`, `secret`, `api_key`, `authorization`, `cookie`, `session`, `credential`,
atau `remember_token` dibuang. Redaksi generik adalah lapisan tambahan; allowlist
tetap lapisan utama.

### 3.3 Rich text

Konten HTML panjang seperti kebijakan privasi, syarat dan ketentuan, deskripsi
profil, dan narasi sejarah disimpan sebagai penanda ringkas + panjang karakter,
bukan HTML penuh. Metadata `summarized_fields` menandai field tersebut.

## 4. IP address, user-agent, dan email login gagal

- IP dan user-agent disimpan untuk investigasi keamanan.
- Keduanya hanya ditampilkan di halaman detail Audit Log dan hanya dapat diakses
  pemegang permission `auditlogs.view`.
- Keduanya tidak ditampilkan di daftar, tidak diekspor, dan tidak pernah menjadi
  bukti identitas tunggal.
- Login gagal menyimpan email yang dicoba agar percobaan berulang dapat
  ditelusuri. Password, token, dan cookie tidak pernah disimpan.
- Rate limit login (5 percobaan/menit) tetap menjadi kontrol utama volume.
- Data ini mengikuti retensi 12 bulan yang sama.

## 5. Append-only dan batas integritas

Di level aplikasi:

- tidak ada route atau UI untuk mengubah/menghapus event;
- hanya `AuditLogger`/`RecordAuditEventAction` yang menulis event;
- model `AuditEvent` menolak update/delete instance.

Batas yang harus dipahami:

- administrator database atau server tetap dapat mengubah atau menghapus baris
  dengan akses langsung;
- mass update/query builder dapat melewati guard model;
- hash chaining, immutable storage, dan external logging berada di luar MVP.

Mitigasi operasional: backup database terpisah, kontrol akses infrastruktur,
dan pembatasan akses panel admin.

## 6. Akses dan operasi

- Permission: `auditlogs.view`.
- Super Admin memperoleh akses melalui `Gate::before`; permission tetap
  didefinisikan di `PermissionSeeder` untuk role lain di masa depan.
- Menu berada di grup **Administrasi** dan hanya tampil bila ability
  `auditlogs.view` bernilai true.
- Halaman: `/admin/audit-log` (daftar, filter, pagination) dan
  `/admin/audit-log/{auditEvent}` (detail).
- Filter: pencarian, rentang tanggal, aktor, modul, aksi, hasil.
- Daftar tidak memuat kolom JSON besar; detail memuat snapshot.

## 7. Keterbatasan MVP

- Tidak ada ekspor CSV/PDF.
- Tidak ada hash chain atau tanda tangan kriptografis.
- Tidak ada notifikasi eksternal per kejadian.
- Tidak ada UI manajemen role/permission atau status akun; perubahan role dan
  status akun belum dicatat karena belum ada jalur aplikasinya.
- Tidak ada route restore objek, sehingga aksi `restored` belum menghasilkan
  event.
- Tidak ada auto-purge retensi.
- Tidak merekam page view, klik, atau navigasi publik.

## 8. Titik integrasi

Pencatatan dilakukan di dalam transaksi bisnis pada action/controller yang
relevan. Operasi massal (reorder, aktivasi periode/pastor, publish terjadwal)
dicatat sebagai satu event agregat dengan metadata `count` dan ID terbatas.
Status job media dicatat sebagai event sistem terpisah oleh
`GenerateImageVariants`. Event status job tidak memakai idempotency key: setiap
siklus job yang benar-benar selesai atau gagal adalah kejadian tersendiri, dan
`RegenerateImageVariants` yang dijalankan ulang memang sengaja menghasilkan
kejadian baru.

## 9. Rujukan

- `docs/specifications/spec-audit-log.md`
- `docs/plan-implementation/plan-audit-log.md`
- `docs/DECISIONS.md` D-79
- `config/audit.php`
- `app/Services/AuditLogger.php`
- `app/Actions/Audit/RecordAuditEventAction.php`
