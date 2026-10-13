<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Profil Paroki — pastor dan riwayat penugasan
|--------------------------------------------------------------------------
|
| Dua tabel, bukan satu (D-52 butir 3). Satu orang dapat memiliki beberapa
| periode penugasan; memisahkan identitas orang dari penugasannya membuat
| riwayat tidak terhapus saat penugasan berubah, dan "pastor yang sedang
| bertugas" dapat ditegakkan sebagai satu baris is_current di seluruh tabel.
|
| Periode disimpan sebagai tahun (unsignedSmallInteger), bukan tanggal penuh,
| supaya tidak mengarang tanggal yang tidak diketahui paroki (D-52 butir 4).
|
| @see docs/DECISIONS.md D-52
| @see docs/roadmap/phase-6A-parish-profile.md section 6.3
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pastors', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            // Gelar opsional, mis. "Pst." atau "RD".
            $table->string('title', 50)->nullable();

            $table->foreignId('photo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('photo_alt', 255)->nullable();

            $table->longText('bio')->nullable();

            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(false);

            $table->timestamps();
        });

        Schema::create('pastor_assignments', function (Blueprint $table) {
            $table->id();

            // CASCADE: menghapus orang menghapus penugasannya. Riwayat yang
            // dipertahankan adalah pergantian penugasan, bukan penghapusan
            // orang (PRD PROF-05).
            $table->foreignId('pastor_id')->constrained('pastors')->cascadeOnDelete();

            // pastor_paroki | pastor_rekan | lainnya (PastorPosition).
            $table->string('position', 50);

            $table->unsignedSmallInteger('start_year')->nullable();
            // Null berarti penugasan belum berakhir. Hanya satu baris
            // is_current yang boleh true; ditegakkan di Action + test.
            $table->unsignedSmallInteger('end_year')->nullable();
            $table->boolean('is_current')->default(false);

            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['pastor_id', 'is_current']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pastor_assignments');
        Schema::dropIfExists('pastors');
    }
};
