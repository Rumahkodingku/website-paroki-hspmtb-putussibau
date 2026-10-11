<?php

namespace App\Http\Resources\MassSchedule;

use App\Models\ScheduleNotice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

/**
 * A notice as the admin list needs it.
 *
 * status is derived, not stored. The four states an admin actually needs to tell
 * apart — switched off, not yet showing, showing now, finished — are all
 * functions of is_active and the display window (MASS-05), so persisting one
 * would add a column that can disagree with the two it is computed from.
 * Deriving it here keeps the answer and the reason next to each other.
 *
 * @mixin ScheduleNotice
 */
class ScheduleNoticeResource extends JsonResource
{
    use ResolvesNestedResources;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'location_id' => $this->location_id,
            'mass_schedule_id' => $this->mass_schedule_id,
            'special_mass_id' => $this->special_mass_id,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'is_active' => $this->is_active,
            'is_general' => $this->isGeneral(),
            // 'rutin' or 'khusus' when the notice targets a schedule, null when
            // it targets a location or applies to everyone.
            'target_kind' => $this->targetKind()?->value,
            'status' => $this->status(),
            'location' => $this->nested(LocationOptionResource::class, $this->whenLoaded('location')),
            'mass_schedule' => $this->nested(MassScheduleOptionResource::class, $this->whenLoaded('massSchedule')),
            'special_mass' => $this->nested(SpecialMassOptionResource::class, $this->whenLoaded('specialMass')),
            // True when this notice names a target that has since been soft
            // deleted. The relations above are loaded withTrashed() precisely so
            // this can be told apart from a notice that never had a target;
            // without it the two look identical and the first has quietly
            // stopped appearing on the public page. See D-40.
            'target_deleted' => $this->targetDeleted(),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }

    /**
     * Whether the target this notice names has been deleted.
     *
     * Read from the three counts rather than the relations, because a trashed
     * row is excluded from the relation and a null relation cannot tell "the
     * target was deleted" from "this notice was never aimed at anything".
     *
     * whenCounted, not a plain zero: on a page that did not count, the field is
     * absent rather than a false that would show a badge which is not there.
     */
    protected function targetDeleted(): ?bool
    {
        $counts = [
            'location_id' => 'trashed_location_count',
            'mass_schedule_id' => 'trashed_mass_schedule_count',
            'special_mass_id' => 'trashed_special_mass_count',
        ];

        $seen = false;

        foreach ($counts as $column => $alias) {
            if ($this->{$column} === null) {
                continue;
            }

            // whenCounted() hands back a MissingValue on a page that did not
            // count, and that object cannot be compared to an integer - it has
            // to be recognised, not coerced.
            $count = $this->whenCounted($alias);

            if ($count instanceof MissingValue) {
                continue;
            }

            $seen = true;

            if ($count > 0) {
                return true;
            }
        }

        return $seen ? false : null;
    }

    /**
     * Which of MASS-05's four states this notice is in.
     *
     * 'draft' before the window opens rather than 'scheduled', matching the
     * wording an admin would use: it is written but not published.
     */
    protected function status(): string
    {
        if (! $this->is_active) {
            return 'draft';
        }

        $now = now();

        if ($this->starts_at->isAfter($now)) {
            return 'scheduled';
        }

        if ($this->ends_at !== null && $this->ends_at->isBefore($now)) {
            return 'expired';
        }

        return 'visible';
    }
}
