<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Gallery (Galeri) tables
|--------------------------------------------------------------------------
|
| The two tables behind /galeri: the album and the photos that belong to it.
|
| Four decisions here are not obvious from the column list and are recorded in
| D-65 rather than rediscovered:
|
| 1. There are no path columns. PRD 9.10 stores path, thumb_path, original_path,
|    width, height and file_size on gallery_photos, but every recent module
|    resolves an image from a `media` row instead (communities.logo_media_id,
|    pastors.photo_media_id, services.thumbnail_media_id). A photo here is a
|    reference to one Media, so the file is produced once and reused, and only
|    this table holds the album ordering and caption.
|
| 2. `cover_photo_id` points at a photo in the same album, not at a Media row.
|    PRD 9.10 asks for it and GAL-07 makes the first photo the fallback when it
|    is null, so a cover can never point at something no longer in the album.
|    It is a circular reference (gallery_photos.gallery_album_id -> albums,
|    albums.cover_photo_id -> photos), which is why the column is added in its
|    own Schema::table call after gallery_photos exists.
|
| 3. Slug stays unique across soft-deleted rows, the posts/events/communities
|    rule (D-44/D-45/D-55), not the locations/mass_schedules rule (D-40). The
|    public route binds {gallery:slug}, so two rows answering one slug makes the
|    binding ambiguous. There is no generated active_marker column.
|
| 4. `media_id` cascades on delete, and that direction is deliberate: removing a
|    Media row removes the photo row that referenced it, but never the other way
|    around. MediaController::destroy() deletes the row for real, so a RESTRICT
|    here would turn "delete an image an album uses" into a 500. The album
|    itself is soft-deleted, so the DB cascade only runs on a force delete.
|
| ON DELETE for the rest mirrors the dependency: an album going away takes its
| photo rows (cascade); a user going away clears the audit reference (set null).
|
| @see docs/DECISIONS.md D-65
| @see docs/PRD.md 7.8, 9.10
| @see docs/roadmap/phase-8A-galleries.md section 6
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_albums', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            // Unique across soft-deleted rows on purpose — see the header.
            $table->string('slug', 180)->unique();

            // Plain text. It is the fallback meta description, so markup here
            // would reach a crawler rather than a reader.
            $table->text('description')->nullable();

            // Tanggal kegiatan, not the upload date. A wall clock: it is a
            // calendar date and must never be shifted by a timezone cast.
            $table->date('event_date')->nullable();
            $table->string('location', 255)->nullable();

            // Draft / Terbit. PRD 9.10 names the flag; only two states exist,
            // so there is no enum and no archived status (D-65).
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // The public list filters on the flag and orders by the date.
            $table->index(['is_published', 'event_date']);
        });

        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gallery_album_id')->constrained('gallery_albums')->cascadeOnDelete();

            // The image itself is a Media row (D-65 point 1). Cascade removes
            // the join row when the media is deleted — never the media.
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();

            $table->string('caption', 255)->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            // The same photo twice in one album is not a gallery.
            $table->unique(['gallery_album_id', 'media_id']);
            // The detail page reads one album's photos in order.
            $table->index(['gallery_album_id', 'sort_order']);
        });

        // The cover, added last: it references gallery_photos, which did not
        // exist when gallery_albums was created. SET NULL keeps a deleted photo
        // from taking the album with it, and the resource falls back to the
        // first photo (GAL-07).
        Schema::table('gallery_albums', function (Blueprint $table) {
            $table->foreignId('cover_photo_id')->nullable()->after('location')
                ->constrained('gallery_photos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // The cover has to go before gallery_photos, or the album still
        // references a table being dropped.
        Schema::table('gallery_albums', function (Blueprint $table) {
            $table->dropForeign(['cover_photo_id']);
            $table->dropColumn('cover_photo_id');
        });

        Schema::dropIfExists('gallery_photos');
        Schema::dropIfExists('gallery_albums');
    }
};
