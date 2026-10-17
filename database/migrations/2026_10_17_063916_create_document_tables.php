<?php

use App\Enums\DocumentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('sort_order');
            $table->index('is_active');
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_category_id')->constrained()->restrictOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('file_disk', 50);
            $table->string('file_path', 500);
            $table->string('original_filename', 255);
            $table->string('mime_type', 150);
            $table->unsignedBigInteger('file_size');
            $table->string('version_label', 100)->nullable();
            $table->date('document_date')->nullable();
            $table->date('expires_at')->nullable();
            $table->enum('status', DocumentStatus::values())->default(DocumentStatus::Draft->value);
            $table->boolean('is_featured')->default(false);
            $table->unsignedBigInteger('download_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['document_category_id', 'status']);
            $table->index('is_featured');
            $table->index('expires_at');
        });

        Schema::create('document_service', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();

            $table->primary(['document_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_service');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_categories');
    }
};
