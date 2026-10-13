<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Profil Paroki — profil utama, linimasa sejarah, wilayah pelayanan
|--------------------------------------------------------------------------
|
| parish_profiles (singleton), parish_history_items, parish_service_areas.
|
| Tiga hal yang tidak terlihat dari daftar kolom:
|
| 1. parish_profiles dan parish_service_areas adalah singleton. Tidak ada
|    kolom unik yang memaksanya di level database; penegakannya ada di
|    aplikasi (ParishProfile::current() / ParishServiceArea::current() yang
|    memakai firstOrCreate) dan diuji oleh test. Menambahkan trik kolom di
|    sini hanya akan menyulitkan migrasi tanpa menambah jaminan.
|
| 2. Tidak ada slug di ketiga tabel. Halaman publiknya bukan
|    /profil/{slug}, melainkan URL tetap, jadi tidak ada alasan mengikat
|    baris lewat slug.
|
| 3. Tidak ada kolom active_marker/generated column. Konsekuensi soft delete
|    pada unique index (D-40) tidak berlaku karena tidak ada unique slot yang
|    perlu dirilis. Tidak ada soft delete di modul ini.
|
| @see docs/DECISIONS.md D-52
| @see docs/roadmap/phase-6A-parish-profile.md section 6.1, 6.2, 6.6
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parish_profiles', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->text('short_description')->nullable();
            // Sanitized HTML from Tiptap. Allowlist in config/html.php; the
            // sanitizer runs in the form request, not here.
            $table->longText('description')->nullable();
            // Narasi sejarah tinggal pada profil, sedangkan linimasa tinggal
            // pada parish_history_items (D-52 butir 2/13).
            $table->longText('history_description')->nullable();

            $table->text('address')->nullable();
            // Disimpan sebagai string: nomor telepon bukan angka (PRD 6).
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('website', 255)->nullable();
            $table->unsignedSmallInteger('established_year')->nullable();

            // SET NULL, bukan RESTRICT: profil tetap hidup walau logonya
            // dihapus; kolom *_alt menyertai setiap media.
            $table->foreignId('logo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('logo_alt', 255)->nullable();
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('cover_alt', 255)->nullable();

            $table->string('map_url', 500)->nullable();
            $table->string('video_url', 500)->nullable();

            // Draft secara default. Paroki yang menambah profil tidak
            // mempublikasikannya hanya dengan mengetik.
            $table->boolean('is_published')->default(false);

            $table->timestamps();
        });

        Schema::create('parish_history_items', function (Blueprint $table) {
            $table->id();

            $table->string('title', 200);
            // Tahun dan/atau tanggal keduanya nullable: banyak peristiwa
            // sejarah hanya diketahui tahunnya (roadmap 6.2).
            $table->unsignedSmallInteger('year')->nullable();
            $table->date('date')->nullable();

            $table->longText('description')->nullable();

            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('image_alt', 255)->nullable();

            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(false);

            $table->timestamps();

            // Urutan publik: published dulu, lalu sort_order, lalu tahun.
            $table->index(['is_published', 'sort_order', 'year']);
        });

        Schema::create('parish_service_areas', function (Blueprint $table) {
            $table->id();

            // Narasi cakupan wilayah. Tidak ada hierarki wilayah/stasi/
            // lingkungan di sini — itu milik modul Komunitas Phase 7 dan
            // hanya ditampilkan di modul ini (PRD 7.2, roadmap 6.6).
            $table->longText('content')->nullable();
            $table->foreignId('map_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('map_url', 500)->nullable();
            $table->boolean('is_published')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parish_service_areas');
        Schema::dropIfExists('parish_history_items');
        Schema::dropIfExists('parish_profiles');
    }
};
