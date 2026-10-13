<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Profil Paroki — periode dan anggota kepengurusan
|--------------------------------------------------------------------------
|
| parish_board_periods + parish_board_members. FK anggota → periode adalah
| CASCADE (D-52 butir 8): menghapus periode menghapus anggotanya, dan dialog
| konfirmasi menyebut jumlah anggota sebelum admin menekan tombol.
|
| Hanya satu periode boleh is_active = true pada satu waktu (PRD PROF-01,
| AC-PROF-5). Aturan itu ditegakkan di ActivateBoardPeriodAction dalam
| transaksi, bukan di database, sama seperti is_current pastor.
|
| @see docs/DECISIONS.md D-52
| @see docs/roadmap/phase-6A-parish-profile.md section 6.4, 6.5
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parish_board_periods', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->unsignedSmallInteger('end_year')->nullable();
            $table->text('description')->nullable();

            // Operasional: periode yang sedang menjabat.
            $table->boolean('is_active')->default(false);
            // Visibilitas publik: periode boleh disiapkan sebelum ditampilkan.
            $table->boolean('is_published')->default(false);

            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });

        Schema::create('parish_board_members', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parish_board_period_id')
                ->constrained('parish_board_periods')
                ->cascadeOnDelete();

            $table->string('name', 150);
            $table->string('position', 100);

            $table->foreignId('photo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('photo_alt', 255)->nullable();

            $table->text('description')->nullable();

            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(false);

            $table->timestamps();

            $table->index(['parish_board_period_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parish_board_members');
        Schema::dropIfExists('parish_board_periods');
    }
};
