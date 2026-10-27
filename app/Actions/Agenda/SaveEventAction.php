<?php

namespace App\Actions\Agenda;

use App\Enums\EventStatus;
use App\Enums\MediaStatus;
use App\Models\Event;
use App\Models\Media;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Writes an activity, filling in the two things the form cannot be trusted with.
 *
 * The counterpart to SavePostAction, and narrower because an activity has no
 * author: it does not derive a slug when the field was empty, and it does not
 * stamp a publication date — is_published is a switch, not a date, and an
 * activity with no date is not something the model can express.
 *
 * @see docs/DECISIONS.md D-44
 */
class SaveEventAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $attributes  the validated payload
     */
    public function handle(Event $event, array $attributes): Event
    {
        return DB::transaction(function () use ($event, $attributes): Event {
            $isNew = ! $event->exists;
            $statusBefore = $isNew ? null : $event->status;
            $publishedBefore = $isNew ? false : $event->is_published;
            $before = $isNew ? [] : $this->audit->snapshot($event, 'agenda');

            $event->fill([
                ...$attributes,
                'slug' => $this->resolveSlug($event, $attributes),
                'poster_path' => $this->resolvePosterPath($event, $attributes),
            ]);

            $event->save();

            if ($isNew) {
                $this->audit->created('agenda', $event);
            } else {
                $this->audit->updated(
                    'agenda',
                    $event,
                    $before,
                    $this->auditAction($statusBefore, $event->status, $publishedBefore, $event->is_published),
                );
            }

            return $event;
        });
    }

    private function auditAction(
        ?EventStatus $statusBefore,
        EventStatus $statusAfter,
        bool $publishedBefore,
        bool $publishedAfter,
    ): string {
        if ($statusBefore !== $statusAfter) {
            return match ($statusAfter) {
                EventStatus::Cancelled => 'cancelled',
                EventStatus::Postponed => 'postponed',
                EventStatus::Scheduled => 'updated',
            };
        }

        if ($publishedBefore !== $publishedAfter) {
            return $publishedAfter ? 'published' : 'unpublished';
        }

        return 'updated';
    }

    /**
     * A slug that was typed is kept as typed.
     *
     * EVT-05 says there is no recurring activity, so the way a parish copies
     * last month's meeting into this month is duplication — and a duplicated
     * activity gets a fresh slug rather than the source's, because the two have
     * different dates and a shared URL would send last month's date to a
     * calendar. See DuplicateEventAction.
     */
    /** @param  array<string, mixed>  $attributes */
    private function resolveSlug(Event $event, array $attributes): string
    {
        $given = $attributes['slug'] ?? null;

        if (is_string($given) && trim($given) !== '') {
            return trim($given);
        }

        $base = Str::slug($attributes['title'] ?? '');
        $slug = $base;
        $suffix = 2;

        while (Event::query()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;

            if ($suffix > 100) {
                $slug = $base.'-'.Str::random(6);

                break;
            }
        }

        return $slug === '' ? (string) Str::uuid() : $slug;
    }

    /**
     * @see SavePostAction::resolveFeaturedImage()
     */
    /** @param  array<string, mixed>  $attributes */
    private function resolvePosterPath(Event $event, array $attributes): ?string
    {
        $mediaId = $attributes['media_id'] ?? null;

        if ($mediaId === null) {
            return $event->exists ? $event->poster_path : null;
        }

        $media = Media::query()
            ->whereKey($mediaId)
            ->where('status', MediaStatus::Ready->value)
            ->first();

        if ($media === null) {
            return $event->poster_path;
        }

        return $media->variantPath($this->largestVariantName());
    }

    /**
     * @see SavePostAction::largestVariantName()
     */
    private function largestVariantName(): ?string
    {
        $variants = (array) config('media.variants');

        if ($variants === []) {
            return null;
        }

        $name = null;
        $widest = -1;

        foreach ($variants as $candidate => $width) {
            if ((int) $width > $widest) {
                $widest = (int) $width;
                $name = (string) $candidate;
            }
        }

        return $name;
    }
}
