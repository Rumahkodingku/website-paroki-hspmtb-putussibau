<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The language of a mass was never displayed on the parish site, and the
     * parish reads no field it cannot show. Dropping it rather than leaving it
     * nullable and unused is deliberate: a nullable column nobody reads looks
     * like a feature still to be built, and someone will eventually fill it.
     *
     * The data goes with the column, so `down()` cannot restore it. It comes
     * back empty instead, which is why this is a new migration and not an edit
     * to the one that created the table — the original still describes what the
     * table was, and editing it would make `migrate:fresh` disagree with any
     * database that already ran it.
     */
    public function up(): void
    {
        Schema::table('mass_schedules', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }

    public function down(): void
    {
        Schema::table('mass_schedules', function (Blueprint $table) {
            $table->string('language', 50)->nullable();
        });
    }
};
