<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Community (Komunitas) tables
|--------------------------------------------------------------------------
|
| The four tables behind /komunitas: the master categories, the area hierarchy
| (wilayah / lingkungan / stasi), the community profiles, and the pivot that
| links a community to the areas it serves.
|
| Four decisions here are not obvious from the column list and are recorded in
| D-55 rather than rediscovered:
|
| 1. Category is a table, not the `community_types` + `group enum` PRD 7.7/9.9
|    describes. Roadmap 1.1/5.1 requires `community_categories` managed from
|    Master Data, so communities.community_category_id is a plain FK.
|
| 2. The logo and thumbnail are `*_media_id` foreign keys into `media`, not
|    `logo_path`/`thumbnail_path` columns. Roadmap 5.2.3 names string paths, but
|    every recent module (parish_profiles.logo_media_id, pastors.photo_media_id,
|    services.thumbnail_media_id) resolves the URL from a Media row. This is a
|    deliberate divergence.
|
| 3. `areas` is wider than roadmap 5.3. PRD 9.9/Lampiran C adds `stasi` and the
|    leader/contact/prayer fields; the phase doc is binding on the two-type
|    shape but the project owner chose the PRD width. Hierarchy (PRD COM-02) is
|    enforced in the request layer: wilayah/stasi have no parent, lingkungan
|    must have a wilayah parent, two levels at most.
|
| 4. slug stays unique across soft-deleted rows on all three master tables.
|    This is the posts/events/services rule (D-44/D-45), not the
|    locations/mass_schedules rule (D-40): the public route binds
|    {community:slug}, so two rows answering one slug makes the binding
|    ambiguous. There is no generated active_marker column.
|
| ON DELETE rules mirror the dependency: a category in use cannot be deleted
| (restrict); an area in use or with children cannot be deleted (restrict); the
| pivot follows its community (cascade); a media row going away simply clears
| the reference (set null).
|
| @see docs/DECISIONS.md D-55
| @see docs/PRD.md 7.7, 9.9
| @see docs/roadmap/phase-07-communities.md section 5
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            // Unique across soft-deleted rows on purpose — see the header.
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            // Categories are deactivated rather than deleted while in use: the
            // controller refuses the delete outright while a community points
            // here. See D-55.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('sort_order');
            $table->index('is_active');
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->id();

            // Self-reference. RESTRICT so a wilayah with children cannot be
            // removed without moving or deleting them first (roadmap 5.3).
            $table->foreignId('parent_id')->nullable()->constrained('areas')->restrictOnDelete();

            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            // wilayah | lingkungan | stasi (AreaType).
            $table->string('type', 20);
            $table->text('description')->nullable();

            // PRD 9.9 fields. The leader/contact pair is personal data: the
            // public detail page only renders it behind area.show_contact
            // (XC-P1, D-55 point 4).
            $table->string('leader_name', 150)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->boolean('show_contact')->default(false);
            $table->string('prayer_schedule', 255)->nullable();

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('is_active');
            $table->index('sort_order');
        });

        Schema::create('communities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 180)->unique();

            // Roadmap 5.1: a category in use may not be deleted. RESTRICT rather
            // than CASCADE, because a community whose category vanished has
            // nowhere to be grouped and no page to appear on.
            $table->foreignId('community_category_id')->constrained()->restrictOnDelete();

            // D-55 point 2: the media convention, not a path column. Nullable,
            // SET NULL: the row is cleared when the media is removed, and a
            // community without a logo or thumbnail is a valid state.
            $table->foreignId('logo_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('logo_alt', 255)->nullable();
            $table->foreignId('thumbnail_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('thumbnail_alt', 255)->nullable();

            $table->text('description')->nullable();
            $table->text('vision')->nullable();
            $table->text('mission')->nullable();
            $table->text('meeting_schedule')->nullable();
            $table->string('meeting_location', 255)->nullable();
            $table->text('eligibility')->nullable();
            $table->text('joining_info')->nullable();

            $table->string('contact_name', 255)->nullable();
            $table->string('contact_phone', 50)->nullable();
            // XC-P1: contact is published only with consent. The read path
            // enforces it in the resource, not only in the UI.
            $table->boolean('show_contact')->default(false);

            // forming | active | inactive (CommunityStatus). A new community
            // starts forming; only active + published is public (roadmap 5.2.2).
            $table->string('status', 20)->default('forming');
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('is_published');
        });

        Schema::create('community_area', function (Blueprint $table) {
            // The pivot follows its community; an area in use is RESTRICT, so a
            // relationship cannot be erased by deleting the area.
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->constrained()->restrictOnDelete();

            // Composite key: the same pair twice is not a relationship.
            $table->primary(['community_id', 'area_id']);
        });
    }

    public function down(): void
    {
        // Reverse dependency order: pivot first, then the profile, the
        // hierarchy, and the master categories.
        Schema::dropIfExists('community_area');
        Schema::dropIfExists('communities');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('community_categories');
    }
};
