<?php

namespace App\Actions\MassSchedule;

use App\Models\Location;
use App\Models\MassSchedule;
use Illuminate\Support\Facades\DB;

/**
 * Copies a recurring mass onto another location.
 *
 * PRD 7.3 lists duplication as an admin capability — "duplikasi jadwal untuk
 * mempercepat input antar lokasi" — because a parish with a stasi and two
 * chapels has the same Sunday mass entered four times, and four places to forget
 * to update it.
 *
 * Two things this deliberately does not copy: the id and the timestamps.
 * replicate() excludes both, which is what "do not copy IDs or timestamps"
 * (roadmap 5.7) means in practice. Everything else comes across, including
 * is_active, because a mass the parish has switched off should not reappear
 * merely by being copied.
 *
 * The transaction is not ceremony. It wraps a single insert, but the insert is
 * the only thing standing between two requests that both decide the destination
 * is free and one unique-index violation (MASS-06). Catching the QueryException
 * in the controller turns that race into a sentence rather than a 500.
 *
 * @see docs/DECISIONS.md D-34
 */
class DuplicateMassScheduleAction
{
    /**
     * The unique index on (location_id, day_of_week, start_time) is the layer
     * behind the validation: this action does not check, so a duplicate that
     * slipped past the form request — two admins saving at once — raises a
     * QueryException here, and the controller turns it into a sentence.
     *
     * @param  array{name?: string|null, language?: string|null, notes?: string|null, is_active?: bool}  $overrides
     */
    public function handle(MassSchedule $source, Location $target, array $overrides = []): MassSchedule
    {
        return DB::transaction(function () use ($source, $target, $overrides): MassSchedule {
            $copy = $source->replicate([
                'location_id' => $target->getKey(),
            ]);

            // Only apply the keys the caller actually supplied, so a partial
            // override does not blank the columns it left out.
            foreach ($overrides as $key => $value) {
                if ($value !== null) {
                    $copy->{$key} = $value;
                }
            }

            $copy->save();

            return $copy;
        });
    }
}
