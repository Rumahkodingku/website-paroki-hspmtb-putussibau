<?php

namespace App\Models;

use App\Enums\LivestreamBroadcastStatus;
use App\Enums\LivestreamEditorialStatus;
use Database\Factories\LivestreamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * One curated YouTube broadcast or recording.
 *
 * Four things about this model are load-bearing and easy to undo by accident.
 *
 * 1. `status` and `broadcast_status` are two different questions. `status` is
 *    editorial visibility; `broadcast_status` is the operational state an admin
 *    sets by hand. A published record with `broadcast_status = ended` is an
 *    archive item, not a live stream.
 *
 * 2. `live_slot` is the database-level guarantee that at most one published
 *    stream is live. It is set to 1 by StartLivestreamAction and cleared by
 *    EndLivestreamAction, MarkLivestreamUnavailableAction and destroy. The
 *    unique index on the nullable column is what a transaction alone cannot
 *    provide when two requests start from an empty live set.
 *
 * 3. The public site is one set of scopes, not a filter repeated in every
 *    controller. `published()` is the editorial gate, `archiveReady()` adds the
 *    ended-plus-replay rule, and `currentLive()` is the single definition of
 *    "the stream on the homepage".
 *
 * 4. `youtube_video_id` is derived server-side from `youtube_url`. The embed,
 *    watch link and thumbnail are built from it by App\Support\YoutubeEmbed;
 *    no component ever accepts an iframe URL or a browser-supplied id.
 *
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string $youtube_video_id
 * @property string $youtube_url
 * @property int|null $location_id
 * @property int|null $livestream_category_id
 * @property LivestreamEditorialStatus $status
 * @property-read LivestreamCategory|null $category
 * @property LivestreamBroadcastStatus $broadcast_status
 * @property int|null $live_slot
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $recorded_at
 * @property bool $is_recording_available
 * @property Carbon|null $published_at
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Location|null $location
 * @property-read User|null $createdBy
 * @property-read User|null $updatedBy
 */
#[Fillable([
    'title',
    'description',
    'youtube_video_id',
    'youtube_url',
    'location_id',
    'livestream_category_id',
    'status',
    'scheduled_at',
    'started_at',
    'ended_at',
    'recorded_at',
    'is_recording_available',
    'published_at',
    'created_by',
    'updated_by',
])]
#[Hidden(['live_slot'])]
class Livestream extends Model
{
    /** @use HasFactory<LivestreamFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<LivestreamCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(LivestreamCategory::class, 'livestream_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LivestreamEditorialStatus::class,
            'broadcast_status' => LivestreamBroadcastStatus::class,
            'live_slot' => 'integer',
            'is_recording_available' => 'boolean',
            // UTC storage, Asia/Pontianak display (D-01).
            'scheduled_at' => 'immutable_datetime',
            'started_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
            'recorded_at' => 'immutable_datetime',
            'published_at' => 'immutable_datetime',
        ];
    }

    /**
     * Everything an admin has published, regardless of broadcast state.
     *
     * Soft-deleted rows are already excluded by SoftDeletes.
     *
     * @param  Builder<Livestream>  $query
     * @return Builder<Livestream>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', LivestreamEditorialStatus::Published);
    }

    /**
     * @param  Builder<Livestream>  $query
     * @return Builder<Livestream>
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('broadcast_status', LivestreamBroadcastStatus::Live);
    }

    /**
     * A published record with a replay an admin has verified.
     *
     * `ended` is required: a scheduled stream has no recording, and a live
     * stream belongs at the top of the page rather than in the grid.
     *
     * @param  Builder<Livestream>  $query
     * @return Builder<Livestream>
     */
    public function scopeArchiveReady(Builder $query): Builder
    {
        return $query
            ->where('broadcast_status', LivestreamBroadcastStatus::Ended)
            ->where('is_recording_available', true);
    }

    /**
     * Newest recording first, with a total order for pagination.
     *
     * A recording without a date is not useful in a date-ordered archive, but
     * hiding it would silently drop a row; keeping it last is the honest
     * option. The id tie-breaker stops two rows with the same date from
     * swapping between pages.
     *
     * @param  Builder<Livestream>  $query
     * @return Builder<Livestream>
     */
    public function scopeOrderedForArchive(Builder $query): Builder
    {
        return $query
            ->orderByRaw('recorded_at is null')
            ->orderByDesc('recorded_at')
            ->orderByDesc('id');
    }

    /**
     * The one stream the homepage and archive page may show as live.
     *
     * A single definition rather than the same two conditions in two
     * controllers, so the homepage and the archive page cannot disagree about
     * what "live" means.
     */
    public static function currentLive(): ?self
    {
        return static::query()->published()->live()->with('location')->first();
    }
}
