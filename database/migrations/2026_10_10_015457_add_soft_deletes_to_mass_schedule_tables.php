<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Soft deletes on the mass schedule tables
|--------------------------------------------------------------------------
|
| Phase 04 shipped the module with no delete anywhere: is_active was the whole
| lifecycle, PRD 7.3 says the admin "nonaktifkan, tidak hapus", and roadmap 5.6
| forbids a permanent-delete endpoint while deactivation covers the need. That
| left ADM-05 — a MUST that says deletion on content tables is a soft delete —
| unfulfilled, and it was recorded as a divergence in D-34 section 1.
|
| This migration is what closes that divergence. It is additive on purpose:
| 2026_10_09_125009 is not touched, so nothing that already ran is rewritten.
|
| The second half is the part that is easy to get wrong. A soft-deleted row is
| still a row, and two unique indexes here are load-bearing:
|
|   locations.slug                                  UNIQUE
|   mass_schedules(location_id, day_of_week, start_time)  UNIQUE  (MASS-06)
|
| Left alone, deleting the 07.30 Sunday mass would leave that slot occupied and
| the next attempt to create a mass at 07.30 would fail on the index with a
| QueryException — a 500 for something the secretary did correctly. Adding
| deleted_at to the index does not fix it either: MySQL treats NULLs as
| distinct in a unique index, so (location, day, time, deleted_at) would let
| any number of active rows share one slot and MASS-06 would stop holding.
|
| So each index gains a generated column that is 1 while the row is live and
| NULL once it is not. NULL is distinct from everything, so the unique index
| still admits exactly one live row per slot while unlimited deleted rows sit
| behind it. This is the standard MySQL soft-delete pattern for a table that
| has to keep enforcing uniqueness on live rows.
|
| STORED rather than VIRTUAL: MySQL has supported indexing virtual generated
| columns since 5.7, but STORED has no such caveat, and these tables are small
| enough that rewriting one is not worth thinking about.
|
| special_masses and schedule_notices have no unique index, so they only get
| the column. Their foreign keys are untouched: ON DELETE RESTRICT from the two
| schedule tables and SET NULL from schedule_notices are now unreachable in
| practice, because a soft delete never removes a row, and they stay in place
| for the case where a row is purged later.
|
| @see docs/DECISIONS.md D-40 (supersedes D-34 section 1)
| @see docs/PRD.md ADM-05, 7.3
*/

return new class extends Migration
{
    public function up(): void
    {
        foreach (['locations', 'mass_schedules', 'special_masses', 'schedule_notices'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->timestamp('deleted_at')->nullable()->after('updated_at');
                // Every admin list filters out deleted rows, and every one of
                // them paginates, so the scope is applied to every query.
                $table->index('deleted_at');
            });
        }

        Schema::table('locations', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->unsignedTinyInteger('active_marker')
                ->storedAs('if(`deleted_at` is null, 1, null)');
            $table->unique(['slug', 'active_marker'], 'locations_slug_active_unique');
        });

        Schema::table('mass_schedules', function (Blueprint $table): void {
            // The unique index being dropped is also the only index MySQL has
            // on location_id, and that column carries the foreign key to
            // locations — dropping it first fails with error 1553, "needed in a
            // foreign key constraint". The replacement has to exist before the
            // old one goes.
            $table->index('location_id');

            $table->dropUnique('mass_schedules_location_day_time_unique');
            $table->unsignedTinyInteger('active_marker')
                ->storedAs('if(`deleted_at` is null, 1, null)');
            $table->unique(
                ['location_id', 'day_of_week', 'start_time', 'active_marker'],
                'mass_schedules_location_day_time_active_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('mass_schedules', function (Blueprint $table): void {
            $table->dropUnique('mass_schedules_location_day_time_active_unique');
            $table->dropIndex(['location_id']);
            $table->dropColumn('active_marker');
            // Restored as it was, which means the slot of every soft-deleted
            // schedule is now occupied again. Rolling this back onto a table
            // holding deleted rows will fail on the index, and that is correct:
            // the data would have to be merged by hand first.
            $table->unique(
                ['location_id', 'day_of_week', 'start_time'],
                'mass_schedules_location_day_time_unique'
            );
        });

        Schema::table('locations', function (Blueprint $table): void {
            $table->dropUnique('locations_slug_active_unique');
            $table->dropColumn('active_marker');
            $table->unique('slug');
        });

        foreach (['locations', 'mass_schedules', 'special_masses', 'schedule_notices'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropIndex(['deleted_at']);
                $table->dropColumn('deleted_at');
            });
        }
    }
};
