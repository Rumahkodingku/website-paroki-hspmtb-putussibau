<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Add address and coordinates to areas
|--------------------------------------------------------------------------
|
| Where the place is, on paper and on a map. D-61: an extension beyond PRD §9.9,
| which gives areas a leader and a phone but no address.
|
| Every column type is copied from `locations` rather than chosen here, and that
| is the whole point of copying: two modules that both store a coordinate must
| store it the same way, or a comparison written for one silently rounds for the
| other.
|
|   address   string(2000)  - same ceiling as locations.address
|   maps_url  string(2048)  - a Google Maps link is longer than a URL usually is
|   lat/long  decimal(10,7) - decimal, not float: a coordinate round-trips
|                             through the database as text, which a float does
|                             not (see Location's casts)
|
| A new migration, not an edit to 2026_10_16_000000_create_community_tables:
| that one has already run everywhere.
|
| down() drops the four columns, and their values go with them.
|
| @see docs/DECISIONS.md D-61
| @see app/Concerns/LocationValidationRules.php
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->string('address', 2000)->nullable()->after('description');
            $table->string('maps_url', 2048)->nullable()->after('address');
            $table->decimal('latitude', 10, 7)->nullable()->after('maps_url');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn(['address', 'maps_url', 'latitude', 'longitude']);
        });
    }
};
