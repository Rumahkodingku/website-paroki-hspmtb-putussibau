<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Drop prayer_schedule from areas
|--------------------------------------------------------------------------
|
| The parish does not keep a prayer schedule per area, so the column leaves in
| full: schema, validation, resources, the admin form, the admin table and both
| public pages (D-58). The field was asked for by PRD §9.9 and Lampiran C, which
| makes this a deliberate divergence from the PRD rather than a cleanup; the PRD
| is not edited mid-phase.
|
| This is a new migration on purpose: 2026_10_16_000000_create_community_tables
| has already run everywhere, and editing a migration that has run is how a
| developer's database and production end up disagreeing about the schema.
|
| down() re-adds the column, but MySQL appends it at the end of the table rather
| than back between show_contact and sort_order, and the values are gone. The
| rollback restores the shape, not the data — which is the honest guarantee for
| a destructive schema change.
|
| @see docs/DECISIONS.md D-58
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn('prayer_schedule');
        });
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->string('prayer_schedule', 255)->nullable();
        });
    }
};
