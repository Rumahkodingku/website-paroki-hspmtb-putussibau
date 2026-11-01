<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parish_profiles', function (Blueprint $table) {
            $table->string('video_title', 150)->nullable()->after('video_url');
            $table->string('video_description', 300)->nullable()->after('video_title');
        });
    }

    public function down(): void
    {
        Schema::table('parish_profiles', function (Blueprint $table) {
            $table->dropColumn(['video_title', 'video_description']);
        });
    }
};
