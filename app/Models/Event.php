<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Services\HtmlSanitizer;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * One thing the parish is doing on a date: a retreat, a meeting, a social.
 *
 * The distinction that keeps getting confused here is between `is_published`
 * and `status`. is_published is the parish's editorial switch — is this on the
 * website yet. status is what has become of the activity itself. A cancelled
 * activity stays published and is shown with a label, because removing it would
 * leave somebody who read the announcement with no explanation (EVT-04).
 *
 * start_at and end_at are real instants in UTC (D-01), unlike MassSchedule's
 * start_time, which is a wall clock on a weekly recurrence and is deliberately
 * not cast. An activity happens once, on a date, so it has a date to convert.
 *
 * @property int $id
 * @property int $event_category_id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property Carbon $start_at
 * @property Carbon|null $end_at
 * @property bool $is_all_day
 * @property string|null $location_name
 * @property int|null $location_id
 * @property string|null $poster_path
 * @property string|null $poster_alt
 * @property EventStatus $status
 * @property bool $is_published
 * @property int|null $media_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read EventCategory $category
 * @property-read Location|null $location
 * @property-read Media|null $media
 */
#[Fillable([
    'event_category_id',
    'title',
    'slug',
    'description',
    'start_at',
    'end_at',
    'is_all_day',
    'location_name',
    'location_id',
    'poster_path',
    'poster_alt',
    'status',
    'is_published',
    'media_id',
])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @return BelongsTo<EventCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    /**
     * Nullable on purpose. EVT-06 allows a free-text location, and Phase 04's
     * ON DELETE SET NULL means a deleted master location leaves the activity
     * standing with its location_name rather than taking it away.
     *
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'is_published' => 'boolean',
            'is_all_day' => 'boolean',
            'start_at' => 'immutable_datetime',
            'end_at' => 'immutable_datetime',
        ];
    }

    /**
     * Everything a visitor is allowed to read.
     *
     * Deliberately no condition on start_at. EVT-02 says the upcoming list is
     * for what is ahead and the calendar still shows what was in a past month —
     * both are the same published event, filtered differently at the call site.
     *
     * Soft-deleted rows are excluded by SoftDeletes itself.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Activities that have not finished their day yet.
     *
     * "Ahead" means from the start of today in the parish's timezone, not from
     * now(): an activity that began an hour ago and has not finished is still
     * something somebody might walk into, and one that began this morning
     * belongs on today's list rather than yesterday's.
     *
     * One definition, used by both /agenda and the homepage. A second copy of
     * this boundary is how the two pages end up disagreeing at midnight.
     *
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeUpcomingFromToday(Builder $query): Builder
    {
        $startOfToday = Carbon::now()
            ->setTimezone((string) config('app.display_timezone'))
            ->startOfDay()
            ->utc();

        return $query->where('start_at', '>=', $startOfToday);
    }

    /**
     * Bind `{event:slug}` on the public routes to a published activity only.
     *
     * The same mechanism Post uses. The .ics endpoint is bound through it too,
     * which is what makes roadmap 8.5's "unpublished activities must 404" hold
     * for the calendar file and not just the page.
     *
     * @param  string|int  $value
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== 'slug') {
            return parent::resolveRouteBinding($value, $field);
        }

        return $this->published()->where('slug', $value)->first();
    }

    /**
     * The location as the public page should read it.
     *
     * EVT-06: a master location wins because it carries the address and the map
     * link; the free text is the fallback. Returns null when neither is filled,
     * which the detail page renders as "Lokasi menyusul" rather than as a gap.
     */
    public function displayLocationName(): ?string
    {
        if ($this->location !== null) {
            return $this->location->name;
        }

        return $this->location_name !== null && trim($this->location_name) !== ''
            ? $this->location_name
            : null;
    }

    /**
     * A link to the map, when the master location has the coordinates for one.
     *
     * Built here rather than in React for the same reason the byline is: the
     * browser receives a finished URL, not a coordinate pair it has to trust.
     */
    public function displayMapsUrl(): ?string
    {
        return $this->location?->mapsUrl();
    }

    /**
     * The description reduced to text, for the card and the calendar file.
     */
    public function plainDescription(): ?string
    {
        if ($this->description === null || trim($this->description) === '') {
            return null;
        }

        $text = app(HtmlSanitizer::class)->toPlainText($this->description);

        return $text === '' ? null : $text;
    }
}
