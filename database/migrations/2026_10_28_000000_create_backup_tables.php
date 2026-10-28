<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('type', 10);
            $table->string('source', 20);
            $table->string('status', 20);
            $table->string('validation_status', 20)->default('pending');

            $table->string('disk', 30);
            $table->string('path', 500)->nullable();
            $table->string('original_filename', 255)->nullable();

            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->string('format_version', 10)->nullable();
            $table->string('note', 500)->nullable();

            $table->string('failure_code', 50)->nullable();
            $table->string('failure_message', 500)->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('created_by_label', 150)->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('file_missing_at')->nullable();
            $table->timestamp('protected_until')->nullable();

            $table->string('scheduled_slot', 100)->nullable()->unique();

            $table->timestamps();

            $table->index('type');
            $table->index('source');
            $table->index('status');
            $table->index('validation_status');
            $table->index('created_at');
            $table->index('created_by');
        });

        Schema::create('backup_restores', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('backup_id')
                ->constrained('backups')
                ->cascadeOnDelete();

            $table->foreignId('safety_backup_id')
                ->nullable()
                ->constrained('backups')
                ->nullOnDelete();

            $table->string('status', 20);

            $table->string('database_result', 20)->nullable();
            $table->string('files_result', 20)->nullable();

            $table->unsignedBigInteger('requested_by')->nullable();
            $table->string('requested_by_label', 150)->nullable();

            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();

            $table->string('failure_code', 50)->nullable();
            $table->string('failure_message', 500)->nullable();
            $table->json('verification')->nullable();

            $table->timestamps();

            $table->index('backup_id');
            $table->index('status');
            $table->index('created_at');
            $table->index('requested_by');
        });

        Schema::create('backup_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();

            $table->boolean('schedule_enabled')->default(true);
            $table->boolean('database_enabled')->default(true);
            $table->boolean('full_enabled')->default(true);

            $table->char('database_time', 5)->default('02:00');
            $table->unsignedTinyInteger('full_day')->default(0);
            $table->char('full_time', 5)->default('02:30');
            $table->string('timezone', 64)->default('Asia/Pontianak');

            $table->unsignedSmallInteger('retention_daily_days')->default(14);
            $table->unsignedSmallInteger('retention_weekly_weeks')->default(4);
            $table->unsignedSmallInteger('retention_invalid_upload_days')->default(7);
            $table->unsignedSmallInteger('retention_safety_days')->default(30);

            $table->unsignedInteger('max_upload_megabytes')->default(256);
            $table->unsignedInteger('max_expanded_megabytes')->default(4096);
            $table->unsignedInteger('max_files')->default(10000);
            $table->unsignedInteger('max_compression_ratio')->default(100);
            $table->unsignedInteger('low_disk_threshold_megabytes')->default(1024);

            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_settings');
        Schema::dropIfExists('backup_restores');
        Schema::dropIfExists('backups');
    }
};
