<?php

use App\Enums\MediaStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 01 3.1.L asks for a media foundation and 20 asks for width, height,
     * file size, mime type and path to be persisted. PRD section 9 has no media
     * table, because it stores images as image_path columns on hero_slides,
     * clergy, gallery_photos and so on. Phase 01 section 9 allows the schema to
     * be adjusted as long as the behaviour the document describes holds, and
     * XC-M1 needs a status for the admin UI to read.
     *
     * The original file lives on a private disk and only the variants are
     * published, which is what Phase 01 section 19 asks for. Storing the
     * original unprocessed on a public disk would put an unstripped copy, EXIF
     * and all, one guessable path away.
     *
     * No soft deletes. PRD section 9 marks tables that need them with SD and
     * media is not one, so a deleted file is removed for real.
     */
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();

            $table->string('disk', 50);
            $table->string('path', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');

            // Null until the job has read the original. The upload is accepted
            // before any of that is known, so these cannot be required.
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            // XC-M5: alt text is mandatory for key images. Admin content
            // reminders (ADM-06) look for empty values here.
            $table->string('alt_text', 255)->nullable();

            $table->enum('status', MediaStatus::values())->default(MediaStatus::Pending->value);

            $table->foreignId('uploader_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('error_message')->nullable();

            $table->timestamps();

            // The media library lists newest first, and the admin UI filters by
            // status while a batch processes.
            $table->index(['status', 'created_at']);
        });

        Schema::create('media_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();

            // thumb, medium, large. See config('media.variants').
            $table->string('name', 40);

            $table->string('disk', 50);
            $table->string('path', 500);
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type', 100);

            $table->timestamps();

            // One row per name per media, and the job may be retried.
            $table->unique(['media_id', 'name']);
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_variants');
        Schema::dropIfExists('media');
    }
};
