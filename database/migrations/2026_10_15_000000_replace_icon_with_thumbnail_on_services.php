<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Replace icon with a media thumbnail
|--------------------------------------------------------------------------
|
| The service tables shipped with an `icon` key drawn from
| config/service-icons.php. That mechanism is gone: a service now shows a real
| uploaded image instead of a symbol, and the category icon went with it.
|
| Thumbnail follows the media convention the rest of the application already
| uses (parish_profiles.logo_media_id, pastors.photo_media_id, posts.media_id):
| a nullable foreign key into `media` with ON DELETE SET NULL, plus an alt-text
| column. There is no path column — the URL is resolved from the Media row when
| a resource renders it. A service without a thumbnail is a valid state.
|
| This is a new migration rather than an edit of the create migration, which has
| already run on every machine. See AGENTS.md.
|
| @see docs/DECISIONS.md D-54
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('thumbnail_media_id')
                ->nullable()
                ->after('service_category_id')
                ->constrained('media')
                ->nullOnDelete();
            $table->string('thumbnail_alt', 255)->nullable()->after('thumbnail_media_id');
        });

        // Dropped after the new columns exist, so a failed add cannot leave the
        // table without either the old icon or the new thumbnail.
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('icon');
        });

        Schema::table('service_categories', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('thumbnail_media_id');
            $table->dropColumn('thumbnail_alt');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->string('icon', 100)->nullable()->after('service_category_id');
        });

        Schema::table('service_categories', function (Blueprint $table) {
            $table->string('icon', 100)->nullable()->after('description');
        });
    }
};
