<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Add social links to communities
|--------------------------------------------------------------------------
|
| Five nullable URL columns for a community's own presence on the web. D-59:
| this is an extension beyond PRD §9.9, which never asked for per-community
| socials — the only social links the PRD names are the parish's own, in
| site_settings, and they are a different thing.
|
| A new migration, not an edit to 2026_10_16_000000_create_community_tables:
| that one has already run everywhere, and changing a migration that has run is
| how a developer's database and production end up disagreeing about the schema.
|
| Column order mirrors the order the form shows them in. It is the only place that
| order is written down, and it costs nothing to keep it readable.
|
| down() drops the five columns. The values are gone with them, which is the
| honest guarantee for a destructive schema change.
|
| @see docs/DECISIONS.md D-59
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->string('facebook', 255)->nullable()->after('show_contact');
            $table->string('instagram', 255)->nullable()->after('facebook');
            $table->string('tiktok', 255)->nullable()->after('instagram');
            $table->string('youtube', 255)->nullable()->after('tiktok');
            $table->string('website', 255)->nullable()->after('youtube');
        });
    }

    public function down(): void
    {
        Schema::table('communities', function (Blueprint $table) {
            $table->dropColumn(['facebook', 'instagram', 'tiktok', 'youtube', 'website']);
        });
    }
};
