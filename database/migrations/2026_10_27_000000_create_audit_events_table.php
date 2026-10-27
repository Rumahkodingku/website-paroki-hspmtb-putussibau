<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('actor_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('actor_type', 20);
            $table->string('actor_name', 150)->nullable();
            $table->string('actor_email', 255)->nullable();

            $table->string('action', 50);
            $table->string('module', 50);

            $table->string('subject_type', 150)->nullable();
            $table->string('subject_id', 64)->nullable();
            $table->string('subject_label', 255)->nullable();

            $table->string('description', 500);
            $table->string('result', 20);

            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('metadata')->nullable();

            $table->string('request_id', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->string('idempotency_key', 120)->nullable()->unique();

            $table->index('occurred_at');
            $table->index(['actor_id', 'occurred_at']);
            $table->index(['module', 'occurred_at']);
            $table->index(['action', 'occurred_at']);
            $table->index(['result', 'occurred_at']);
            $table->index(['subject_type', 'subject_id']);
            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
