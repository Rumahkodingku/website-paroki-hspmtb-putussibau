<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->string('contact_email', 255)->nullable()->after('contact_phone');
            $table->string('contact_whatsapp', 50)->nullable()->after('contact_email');
            $table->unsignedBigInteger('photo_media_id')->nullable()->after('contact_whatsapp');
            $table->foreign('photo_media_id')->references('id')->on('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropForeign(['photo_media_id']);
            $table->dropColumn(['contact_email', 'contact_whatsapp', 'photo_media_id']);
        });
    }
};
