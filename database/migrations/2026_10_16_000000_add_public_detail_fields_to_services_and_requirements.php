<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Add public detail fields to services and requirements
|--------------------------------------------------------------------------
|
| Revisi skema data modul Pelayanan: sasaran layanan, persiapan, kondisi
| persyaratan, dan pihak yang dikenai persyaratan. Hanya mengubah tabel
| `services` dan `service_requirements`; `service_categories` tidak disentuh.
|
| @see docs/revision/revision-service-detail-data.md
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->text('audience_info')->nullable();
            $table->text('preparation_info')->nullable();
        });

        Schema::table('service_requirements', function (Blueprint $table) {
            $table->string('requirement_condition', 32)->default('required');
            $table->string('applies_to', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['audience_info', 'preparation_info']);
        });

        Schema::table('service_requirements', function (Blueprint $table) {
            $table->dropColumn(['requirement_condition', 'applies_to']);
        });
    }
};
