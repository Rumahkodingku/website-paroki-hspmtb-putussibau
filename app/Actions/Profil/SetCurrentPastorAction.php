<?php

namespace App\Actions\Profil;

use App\Models\PastorAssignment;
use Illuminate\Support\Facades\DB;

/**
 * Puts one assignment in office for its pastor and clears that pastor's other
 * assignments.
 *
 * Several pastors can be in office at once; only one assignment per pastor may
 * be current. This is the only operation that writes `is_current` for an
 * existing row. It is an Action rather than a controller line because it
 * touches many rows and must be atomic.
 *
 * @see docs/DECISIONS.md D-52
 */
final class SetCurrentPastorAction
{
    public function handle(PastorAssignment $assignment): PastorAssignment
    {
        return DB::transaction(function () use ($assignment): PastorAssignment {
            // Clear only the other assignments of the *same* pastor, so several
            // pastors can be in office at once (paroki + rekan, etc.).
            PastorAssignment::query()
                ->where('pastor_id', $assignment->pastor_id)
                ->where('is_current', true)
                ->whereKeyNot($assignment->getKey())
                ->update(['is_current' => false]);

            $assignment->update(['is_current' => true]);

            return $assignment;
        });
    }
}
