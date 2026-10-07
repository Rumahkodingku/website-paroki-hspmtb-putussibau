<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * PRD 9.2 and Phase 01 8.2 agree on this schema. The canonical key list
     * lives in PRD Lampiran B; the SiteSettingsService in P09 is the single
     * access point for reading and writing it.
     *
     * `key` and `group` are reserved words in MySQL. That is safe here because
     * Laravel wraps every identifier in backticks in the MySQL grammar, so the
     * schema builder and the query builder both quote them. The same applies to
     * hand-written raw SQL in future work.
     *
     * No index on `group`: the table holds roughly thirty configuration rows,
     * so scanning is cheaper than maintaining an index. The unique constraint
     * on `key` already covers the lookup path.
     *
     * No foreign key: configuration rows are standalone.
     */
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->longText('value')->nullable();
            $table->string('group', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
