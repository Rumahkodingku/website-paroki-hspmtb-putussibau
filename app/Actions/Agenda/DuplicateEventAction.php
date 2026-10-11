<?php

namespace App\Actions\Agenda;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Copies an activity into a new one.
 *
 * EVT-05 forbids recurring activities, so duplication is how the parish enters
 * the same meeting every month — which is exactly the input the parish would
 * otherwise repeat by hand and get one of the twelve wrong.
 *
 * Three things are deliberately not copied, and each is a decision rather than
 * an oversight:
 *
 *   - the slug. Two activities sharing a URL would send a calendar that was
 *     given last month's link to this month's date. The copy gets a fresh one
 *     derived from its title.
 *
 *   - is_published. A copy is a draft until somebody looks at it. Publishing it
 *     because the original was published would put next month's meeting on the
 *     site before the parish has decided on it, and the whole point of
 *     is_published is that it is the everyday editorial switch.
 *
 *   - status. A copy of a cancelled activity is not cancelled; whatever it
 *     becomes is a fresh decision.
 *
 * The key and the timestamps are excluded by replicate() itself. deleted_at is
 * passed explicitly for the reason D-40 records: a copy of a deleted activity
 * would be a second deleted one, and this action can only be reached through
 * route model binding, which cannot find a soft-deleted row — but relying on
 * that is exactly what the argument is about.
 *
 * @see docs/DECISIONS.md D-44
 */
class DuplicateEventAction
{
    /**
     * @param  array<string, mixed>  $overrides
     */
    public function handle(Event $source, array $overrides = []): Event
    {
        return DB::transaction(function () use ($source, $overrides): Event {
            $copy = $source->replicate(['deleted_at']);

            $copy->slug = $this->uniqueSlug($source->title, $source->getKey());
            $copy->status = EventStatus::Scheduled;
            $copy->is_published = false;

            foreach ($overrides as $key => $value) {
                if ($value !== null) {
                    $copy->{$key} = $value;
                }
            }

            $copy->save();

            return $copy;
        });
    }

    /**
     * A slug no activity holds, derived from the title.
     *
     * Deterministic for the same reason SavePostAction's is: an admin who
     * duplicates twice should be able to predict the third URL, and a UUID would
     * make the address bar unreadable and unshareable.
     */
    private function uniqueSlug(string $title, int $sourceKey): string
    {
        $base = Str::slug($title) !== '' ? Str::slug($title) : 'kegiatan';
        $slug = $base.'-copy';
        $suffix = 2;

        while (Event::query()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-copy-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * The default start for a copy: the same time of day, one month later.
     *
     * Offered to the form rather than applied silently. An admin who duplicates
     * a meeting on the 14th wants the 14th, and guessing wrong is worse than
     * making them confirm — the value arrives in the edit form as a starting
     * point they can correct before saving.
     *
     * @return array<string, mixed>
     */
    public function defaultOverrides(Event $source): array
    {
        return [
            'start_at' => $source->start_at->copy()->addMonthNoOverflow(),
            'end_at' => $source->end_at?->copy()->addMonthNoOverflow(),
        ];
    }
}
