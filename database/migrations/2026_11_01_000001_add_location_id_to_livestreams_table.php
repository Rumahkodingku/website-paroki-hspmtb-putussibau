<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Livestreaming: location becomes a master-data reference
|--------------------------------------------------------------------------
|
| The first version of the livestream table stored a free-text `location`.
| That allowed an admin to type a name that matched no Location row, which is
| exactly the inconsistency the master-data module exists to prevent.
|
| This migration adds a nullable `location_id` foreign key, backfills it from
| an exact (case-insensitive) name match, then drops the free-text column. The
| development database had no livestream rows when this was written; the
| backfill is kept so a database with rows does not silently lose a location
| that can be matched safely.
|
| `restrictOnDelete` is deliberate: LocationController refuses to delete a
| location that is still used by a livestream, and the foreign key is the
| database-level backstop for a force delete.
|
| @see docs/DECISIONS.md D-101
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('livestreams', function (Blueprint $table) {
            $table->foreignId('location_id')
                ->nullable()
                ->after('youtube_url')
                ->constrained('locations')
                ->restrictOnDelete();
        });

        // Deterministic by id: two locations with the same name would make a
        // join update pick an arbitrary one. The subquery takes the oldest.
        DB::statement(<<<'SQL'
            UPDATE livestreams
            SET location_id = (
                SELECT locations.id
                FROM locations
                WHERE LOWER(TRIM(locations.name)) = LOWER(TRIM(livestreams.location))
                  AND locations.deleted_at IS NULL
                ORDER BY locations.id
                LIMIT 1
            )
            WHERE location_id IS NULL
              AND location IS NOT NULL
              AND TRIM(location) <> ''
        SQL);

        Schema::table('livestreams', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }

    public function down(): void
    {
        Schema::table('livestreams', function (Blueprint $table) {
            $table->string('location', 150)->nullable()->after('youtube_url');
        });

        DB::statement(<<<'SQL'
            UPDATE livestreams
            JOIN locations ON locations.id = livestreams.location_id
            SET livestreams.location = locations.name
            WHERE livestreams.location_id IS NOT NULL
        SQL);

        Schema::table('livestreams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });
    }
};
