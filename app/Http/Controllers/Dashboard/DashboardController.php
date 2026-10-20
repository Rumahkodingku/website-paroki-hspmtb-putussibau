<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\DocumentStatus;
use App\Enums\EventStatus;
use App\Enums\MediaStatus;
use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\RecentPostResource;
use App\Http\Resources\Dashboard\UpcomingEventResource;
use App\Models\Document;
use App\Models\Event;
use App\Models\MassSchedule;
use App\Models\Post;
use App\Models\ScheduleNotice;
use App\Models\Service;
use App\Models\SpecialMass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The admin dashboard — a read-only aggregator, not a second CMS.
 *
 * Every number is a query against the module that already owns the content, and
 * every list is bounded in the database. Nothing here is computed from a
 * paginated payload in React, and nothing is cached: the schedule and the
 * content are cheap to read and a stale dashboard is worse than a slow one.
 *
 * Three rules are load-bearing:
 *
 * 1. "Published" means the module's own scope, never a generic `published`
 *    filter. Posts use status + published_at; events keep is_published apart
 *    from their activity status; documents use their enum. A second definition
 *    of "public" is how this page and the public site end up disagreeing.
 *
 * 2. The media-without-alt-text indicator reads the content's own alt column
 *    (posts.image_alt, events.poster_alt, services.thumbnail_alt), because that
 *    is what the public pages render. media.alt_text is not what a post image
 *    falls back to, so a check against it would flag every illustrated article
 *    and be misleading. See D-77.
 *
 * 3. Lists are returned as plain arrays. A non-paginated JsonResource
 *    collection arrives at the browser as {data: [...]} (D-39), so the two list
 *    props are resolved with array_values().
 *
 * @see docs/roadmap/phase-9b-admin-dashboard.md
 * @see docs/plan-implementation/plan-phase-9b-admin-dashboard.md
 * @see docs/DECISIONS.md D-76, D-77
 */
class DashboardController extends Controller
{
    /** Upcoming events shown in the Agenda Terdekat panel. */
    private const UPCOMING_EVENTS_LIMIT = 5;

    /** Newest articles shown in the Berita Terbaru panel. */
    private const RECENT_POSTS_LIMIT = 3;

    /** Rows in the merged Konten Terbaru panel. */
    private const RECENT_CONTENT_LIMIT = 8;

    /** Rows shown per Perlu Perhatian indicator, before the merge. */
    private const ATTENTION_ITEM_LIMIT = 5;

    public function __invoke(): Response
    {
        $today = today((string) config('app.display_timezone'))->toDateString();

        return Inertia::render('admin/dashboard', [
            'summary' => [
                'posts' => $this->postSummary(),
                'events' => $this->eventSummary(),
                'documents' => $this->documentSummary($today),
                'massSchedules' => $this->massScheduleSummary(),
                'services' => $this->serviceSummary(),
            ],
            'upcomingEvents' => array_values(
                UpcomingEventResource::collection($this->upcomingEvents())->resolve()
            ),
            'recentPosts' => array_values(
                RecentPostResource::collection($this->recentPosts())->resolve()
            ),
            'attentionItems' => $this->attentionItems($today),
            'recentContent' => $this->recentContent(),
        ]);
    }

    /**
     * Counts per article status. "Terbit" is the publication scope, not the
     * status alone: a published article dated in the future is not public yet.
     *
     * @return array<string, int>
     */
    private function postSummary(): array
    {
        return [
            'total' => Post::query()->count(),
            'draft' => Post::query()->where('status', PostStatus::Draft)->count(),
            'scheduled' => Post::query()->where('status', PostStatus::Scheduled)->count(),
            'published' => Post::query()->published()->count(),
            'archived' => Post::query()->where('status', PostStatus::Archived)->count(),
        ];
    }

    /**
     * Publication and activity status are separate facts.
     *
     * `upcoming` counts published, not-yet-over activities that are still
     * `scheduled` — a cancelled or postponed activity is not a normal upcoming
     * one (AC-02), so it is counted apart. `draft` is simply unpublished.
     *
     * @return array<string, int>
     */
    private function eventSummary(): array
    {
        return [
            'upcoming' => Event::query()
                ->published()
                ->upcomingFromToday()
                ->where('status', EventStatus::Scheduled)
                ->count(),
            'draft' => Event::query()->where('is_published', false)->count(),
            'cancelled' => Event::query()->published()->where('status', EventStatus::Cancelled)->count(),
            'postponed' => Event::query()->published()->where('status', EventStatus::Postponed)->count(),
        ];
    }

    /**
     * "Terbit" follows Document::published(); expired is a date fact, not a
     * status, and a null expires_at is never expired.
     *
     * @return array<string, int>
     */
    private function documentSummary(string $today): array
    {
        return [
            'total' => Document::query()->count(),
            'draft' => Document::query()->where('status', DocumentStatus::Draft)->count(),
            'published' => Document::query()->published()->count(),
            'archived' => Document::query()->where('status', DocumentStatus::Archived)->count(),
            'expired' => $this->expiredDocumentsQuery($today)->count(),
        ];
    }

    /**
     * The three mass sources, counted apart so they are never read as one.
     *
     * @return array<string, int>
     */
    private function massScheduleSummary(): array
    {
        return [
            'regular' => MassSchedule::query()->active()->count(),
            'special' => SpecialMass::query()->upcoming()->count(),
            'notices' => ScheduleNotice::query()->currentlyVisible()->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function serviceSummary(): array
    {
        return [
            'active' => Service::query()->active()->count(),
            'inactive' => Service::query()->where('is_active', false)->count(),
        ];
    }

    /**
     * The soonest published activities, earliest first.
     *
     * The location is eager loaded because the resource reads it and Eloquent
     * strict mode turns a missing eager load into a 500 rather than a silent
     * N+1.
     *
     * @return Collection<int, Event>
     */
    private function upcomingEvents(): Collection
    {
        return Event::query()
            ->published()
            ->upcomingFromToday()
            ->with('location')
            ->orderBy('start_at')
            ->orderBy('id')
            ->limit(self::UPCOMING_EVENTS_LIMIT)
            ->get();
    }

    /**
     * The newest published articles.
     *
     * The thumbnail comes from featured_image, the same variant path the public
     * card renders, so the dashboard and /berita cannot show two different
     * pictures for one article.
     *
     * @return Collection<int, Post>
     */
    private function recentPosts(): Collection
    {
        return Post::query()
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(self::RECENT_POSTS_LIMIT)
            ->get();
    }

    /**
     * The Perlu Perhatian indicators that are computable and actionable.
     *
     * Only indicators with a real follow-up are returned, and only when the
     * count is above zero. Ordering is stable so the panel does not reshuffle
     * between requests.
     *
     * @return list<array{key: string, count: int, items: list<array{type: string, id: int, title: string}>}>
     */
    private function attentionItems(string $today): array
    {
        $items = [];

        $expired = $this->expiredDocumentsQuery($today);
        $expiredCount = (clone $expired)->count();

        if ($expiredCount > 0) {
            $items[] = [
                'key' => 'expired_documents',
                'count' => $expiredCount,
                'items' => array_values(
                    $expired
                        ->orderBy('expires_at')
                        ->limit(self::ATTENTION_ITEM_LIMIT)
                        ->get(['id', 'title'])
                        ->map(fn (Document $document): array => [
                            'type' => 'document',
                            'id' => $document->id,
                            'title' => $document->title,
                        ])
                        ->all()
                ),
            ];
        }

        $missingAlt = $this->contentWithoutAltText();

        if ($missingAlt['count'] > 0) {
            $items[] = [
                'key' => 'media_alt_text',
                'count' => $missingAlt['count'],
                'items' => $missingAlt['items'],
            ];
        }

        return $items;
    }

    /**
     * Documents that are past their expiry date. expires_at is a date column and
     * a null is never expired.
     *
     * @return Builder<Document>
     */
    private function expiredDocumentsQuery(string $today): Builder
    {
        return Document::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $today);
    }

    /**
     * Content whose rendered image has no alt text.
     *
     * The alt text is read from the content's own column because that is the
     * value the public pages send to the browser. The referenced media must
     * still be `ready`, so an upload that never finished processing is not
     * counted as a content quality problem.
     *
     * @return array{count: int, items: list<array{type: string, id: int, title: string}>}
     */
    private function contentWithoutAltText(): array
    {
        $readyWithoutSource = fn (Builder $query): Builder => $query
            ->where('status', MediaStatus::Ready);

        $posts = Post::query()
            ->published()
            ->whereNotNull('media_id')
            ->whereNotNull('featured_image')
            ->where(fn (Builder $query): Builder => $this->altTextMissing($query))
            ->whereHas('media', $readyWithoutSource)
            ->orderByDesc('updated_at');

        $events = Event::query()
            ->published()
            ->whereNotNull('media_id')
            ->whereNotNull('poster_path')
            ->where(fn (Builder $query): Builder => $this->altTextMissing($query, 'poster_alt'))
            ->whereHas('media', $readyWithoutSource)
            ->orderByDesc('updated_at');

        $services = Service::query()
            ->active()
            ->whereNotNull('thumbnail_media_id')
            ->where(fn (Builder $query): Builder => $this->altTextMissing($query, 'thumbnail_alt'))
            ->whereHas('thumbnailMedia', $readyWithoutSource)
            ->orderByDesc('updated_at');

        $count = $posts->count() + $events->count() + $services->count();

        $items = [
            ...$this->missingAltItems($posts, 'post'),
            ...$this->missingAltItems($events, 'event'),
            ...$this->missingAltItems($services, 'service'),
        ];

        return [
            'count' => $count,
            'items' => array_slice($items, 0, self::ATTENTION_ITEM_LIMIT),
        ];
    }

    /**
     * A column that is null or blank. The blank half matters: the generic
     * settings form turns every null into '' on the way in.
     *
     * @param  Builder<*>  $query
     * @return Builder<*>
     */
    private function altTextMissing(Builder $query, string $column = 'image_alt'): Builder
    {
        return $query->where(function (Builder $inner) use ($column): void {
            $inner->whereNull($column)->orWhere($column, '');
        });
    }

    /**
     * The first few titles of a bounded content query, shaped for the panel.
     *
     * @param  Builder<*>  $query
     * @return list<array{type: string, id: int, title: string}>
     */
    private function missingAltItems(Builder $query, string $type): array
    {
        /** @var Collection<int, Post|Event|Service> $rows */
        $rows = (clone $query)
            ->limit(self::ATTENTION_ITEM_LIMIT)
            ->get(['id', 'title']);

        return array_values(
            $rows
                ->map(fn (Post|Event|Service $row): array => [
                    'type' => $type,
                    'id' => $row->id,
                    'title' => $row->title,
                ])
                ->all()
        );
    }

    /**
     * The newest rows across articles, activities and documents.
     *
     * Three bounded queries are merged in PHP and sliced, which is cheaper and
     * simpler than a UNION across three differently-shaped tables. The panel is
     * "Konten Terbaru", not an audit log: updated_at says when a row changed, and
     * no user is ever attached to it.
     *
     * @return list<array{type: string, id: int, title: string, status: string, status_label: string, is_published: bool|null, updated_at: string}>
     */
    private function recentContent(): array
    {
        $limit = self::RECENT_CONTENT_LIMIT;

        $posts = Post::query()
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['id', 'title', 'status', 'updated_at'])
            ->map(fn (Post $post): array => [
                'type' => 'post',
                'id' => $post->id,
                'title' => $post->title,
                'status' => $post->status->value,
                'status_label' => $post->status->label(),
                'is_published' => null,
                'updated_at' => $post->updated_at->toIso8601String(),
            ]);

        $events = Event::query()
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['id', 'title', 'status', 'is_published', 'updated_at'])
            ->map(fn (Event $event): array => [
                'type' => 'event',
                'id' => $event->id,
                'title' => $event->title,
                'status' => $event->status->value,
                'status_label' => $event->status->label(),
                'is_published' => $event->is_published,
                'updated_at' => $event->updated_at->toIso8601String(),
            ]);

        $documents = Document::query()
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get(['id', 'title', 'status', 'updated_at'])
            ->map(fn (Document $document): array => [
                'type' => 'document',
                'id' => $document->id,
                'title' => $document->title,
                'status' => $document->status->value,
                'status_label' => $document->status->label(),
                'is_published' => null,
                'updated_at' => $document->updated_at->toIso8601String(),
            ]);

        return array_values(
            $posts
                ->concat($events)
                ->concat($documents)
                ->sortByDesc('updated_at')
                ->take($limit)
                ->values()
                ->all()
        );
    }
}
