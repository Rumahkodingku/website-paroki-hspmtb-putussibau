<?php

use App\Enums\EventStatus;
use App\Enums\PostStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| News & Agenda — the four tables
|--------------------------------------------------------------------------
|
| post_categories, posts, event_categories, events. One file because the four
| are one module and their foreign keys point at each other.
|
| Three decisions here are not obvious from the column list, and each of them
| is a trap worth stating rather than rediscovering:
|
| 1. There is no community_id on posts or events.
|
|    PRD 9.6 and 9.7 both add one (D-03, D-05) and PRD 7.5 filters the agenda by
|    "penyelenggara (komunitas)". Roadmap Phase 05 section 5 rules both out, and
|    section 5's closing paragraph says so explicitly: the Phase 05 decision
|    governs this implementation and the PRD is to be reconciled separately.
|    Nothing here joins, references or queries `communities` — that table does
|    not exist yet, it arrives with Phase 07. See D-43.
|
| 2. The unique index on slug is plain, and that is deliberate.
|
|    Phase 04 gave `locations.slug` a generated column so a soft-deleted row
|    would release its slug (D-40 section 2). These two tables do the opposite.
|    `posts` and `events` are reached by slug through route model binding, and
|    two rows holding the same slug make that binding ambiguous — `first()`
|    could return the deleted one. A slug that was used once is never used
|    again, which costs an admin nothing and removes a whole class of bug. See
|    D-45.
|
| 3. slug stays unique across soft-deleted rows for the same reason.
|
|    The consequence is that `replicate()` on a post or an event has no
|    generated column to trip over. Error 3105, which D-40 records as the reason
|    DuplicateMassScheduleAction skips `active_marker`, cannot happen here.
|
| @see docs/DECISIONS.md D-43, D-45
| @see docs/roadmap/phase-05-news-&-agenda.md section 5
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            // Makes /berita?kategori=renungan readable and shareable, and it is
            // what the public filter reads rather than an id.
            $table->string('slug', 180)->unique();
            // PRD 9.6. The admin list and the public filter both order by it, so
            // the parish decides where "Pengumuman" sits.
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('sort_order');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();

            // Roadmap 5.5: a category in use may not be deleted. RESTRICT rather
            // than CASCADE, because an article whose category vanished has no
            // place to go and no page to be listed on.
            $table->foreignId('post_category_id')->constrained()->restrictOnDelete();

            // Filled from the authenticated user, never from the request body.
            $table->foreignId('author_id')->constrained('users')->restrictOnDelete();

            // D-21: the public page shows a byline, not an account. When this is
            // empty the write falls back to the author's own name at the time,
            // so an admin who later leaves does not blank the byline.
            $table->string('author_name')->nullable();

            $table->string('title');
            // See the class docblock: unique across soft-deleted rows too.
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            // Sanitized HTML from Tiptap. Allowlist in config/html.php; the
            // sanitizer runs in the form request, not here.
            $table->longText('content');

            // The published variant's path on the public disk, written by
            // SavePostAction from the media row rather than taken from input.
            $table->string('featured_image')->nullable();
            // XC-M5: alt text is required for key images, and a news article's
            // featured image is one.
            $table->string('image_alt')->nullable();
            // PRD 7.4: shown above the body for a Renungan article when filled.
            $table->string('scripture_reference')->nullable();

            $table->enum('status', PostStatus::values())
                ->default(PostStatus::Draft->value);
            // UTC, rendered in app.display_timezone (D-01).
            $table->timestamp('published_at')->nullable();
            // POST-07: at most two published articles are pinned at a time. The
            // limit is enforced in the form request and again by the public
            // query's own limit(2).
            $table->boolean('is_pinned')->default(false);

            $table->unsignedInteger('view_count')->default(0);

            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            // The upload the featured image came from. D-6: MediaController
            // returns a redirect, so without a handle on the row the form could
            // not show a processing state or offer the image again later.
            // SET NULL, not RESTRICT: the article outlives its picture.
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();

            $table->timestamps();
            // PRD 9.6 marks posts (SD); roadmap 5.5 keeps deleted articles out
            // of every public list, search and endpoint.
            $table->softDeletes();

            // Every public query is "published, not future-dated, newest first",
            // and both halves are in the index because neither is selective
            // alone.
            $table->index(['status', 'published_at']);
            // The pinned strip is a separate, tiny query.
            $table->index('is_pinned');
            $table->index('post_category_id');
        });

        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 180)->unique();
            // A visual hint on the calendar, not the only signal: the category
            // name is always rendered next to it, because roadmap 5.3 says
            // colour may not be the single marker.
            $table->string('color', 7);
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();

            // Roadmap 5.5: same rule as post categories.
            $table->foreignId('event_category_id')->constrained()->restrictOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description')->nullable();

            // UTC (D-01). is_all_day means the clock is a date boundary rather
            // than a time of day, which is what the .ics writer needs to know.
            $table->timestamp('start_at');
            // Null means no stated finish. EVT-01 only constrains it when set.
            $table->timestamp('end_at')->nullable();
            $table->boolean('is_all_day')->default(false);

            // EVT-06: a parish writes "Lapangan Santo Yosef" far more often than
            // it creates a master location for it, so the free text is the
            // common case and the reference is the exception.
            $table->string('location_name')->nullable();
            // SET NULL rather than RESTRICT, unlike posts to post_categories:
            // deleting a location must not delete the agenda item, and Phase 04
            // can delete a location that holds no mass.
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();

            $table->string('poster_path')->nullable();
            $table->string('poster_alt')->nullable();

            $table->enum('status', EventStatus::values())
                ->default(EventStatus::Scheduled->value);
            // Draft by default. A parish adding next month's retreat should not
            // publish it by typing it in.
            $table->boolean('is_published')->default(false);

            // As on posts: D-6. The poster's handle, so the form can show a
            // processing state and re-offer the image later.
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Roadmap 5.4: index on start_at, and the pair the calendar and the
            // upcoming list both filter on.
            $table->index('start_at');
            $table->index(['is_published', 'start_at']);
            $table->index('event_category_id');
        });
    }

    public function down(): void
    {
        // Reverse dependency order: the two tables holding foreign keys first.
        Schema::dropIfExists('events');
        Schema::dropIfExists('event_categories');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('post_categories');
    }
};
