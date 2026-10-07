<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PRD decision D-08: an account must be able to be deactivated, and a
     * deactivated account must not be able to log in. P06 enforces the login
     * side; this migration only adds the column.
     *
     * The base users migration already ran, so it is left untouched and this
     * file adds the column instead.
     *
     * No index: Phase 01 has at most a handful of Super Admin accounts and the
     * login flow looks a user up by email before reading the flag. An index
     * here would cost write throughput for no read benefit.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
