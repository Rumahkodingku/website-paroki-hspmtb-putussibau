<?php

use App\Enums\LivestreamBroadcastStatus;
use App\Enums\LivestreamCategory;
use App\Enums\LivestreamEditorialStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Livestreaming table
|--------------------------------------------------------------------------
|
| One row is one broadcast/recording the parish curates. YouTube is the player;
| this table only stores metadata, visibility and the operational state an
| admin sets by hand.
|
| Three decisions are not obvious from the column list and are recorded in
| D-101 rather than rediscovered:
|
| 1. `live_slot` is a nullable unique marker. MySQL allows many NULLs in a
|    unique index, so exactly one row can carry the value 1. It is set only
|    while `status = published` and `broadcast_status = live`, and cleared on
|    end/unavailable/delete. Transactions and row locks enforce the same rule;
|    the index is the concurrency backstop two simultaneous "start" requests
|    cannot bypass.
|
| 2. `youtube_video_id` is stored separately from `youtube_url`. The id is what
|    the embed, watch link and thumbnail are built from, and it is extracted
|    server-side by App\Support\YoutubeEmbed — never accepted from a browser.
|
| 3. `recorded_at` is the archive's clock, not the upload date. It is nullable
|    because a scheduled stream has no recording yet; the end action defaults
|    it so a finished stream still has a deterministic archive position.
|
| @see docs/DECISIONS.md D-101
| @see docs/specifications/spec-livestreaming.md
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livestreams', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->text('description')->nullable();

            // The id is derived from youtube_url by the save action. The URL is
            // kept as the admin's source/canonical reference, never as an embed.
            $table->string('youtube_video_id', 11);
            $table->string('youtube_url', 500);

            $table->string('location', 150)->nullable();

            $table->enum('category', LivestreamCategory::values())->nullable();

            // Editorial visibility: draft, published, archived.
            $table->enum('status', LivestreamEditorialStatus::values())
                ->default(LivestreamEditorialStatus::Draft->value);

            // Operational state: scheduled, live, ended, unavailable.
            $table->enum('broadcast_status', LivestreamBroadcastStatus::values())
                ->default(LivestreamBroadcastStatus::Scheduled->value);

            // Exactly one row may be 1. NULL for every other row; MySQL treats
            // multiple NULLs as distinct in a unique index.
            $table->unsignedTinyInteger('live_slot')->nullable()->unique();

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('recorded_at')->nullable();

            $table->boolean('is_recording_available')->default(false);

            // Set once, by the action, on first publication. Never a request field.
            $table->timestamp('published_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Homepage: published + live. Admin list: status/broadcast filters.
            $table->index('status');
            $table->index('broadcast_status');

            // Archive: public recordings ordered by recorded_at.
            $table->index(
                ['status', 'broadcast_status', 'is_recording_available', 'recorded_at'],
                'livestreams_archive_index',
            );

            $table->index('scheduled_at');
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livestreams');
    }
};
