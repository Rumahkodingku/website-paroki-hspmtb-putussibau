<?php

namespace App\Actions\Profil;

use App\Models\PastorAssignment;
use Illuminate\Support\Facades\DB;

/**
 * Puts one assignment in office and clears the rest.
 *
 * There is at most one current assignment in the whole table, and this is the
 * only operation that writes `is_current` for an existing row. It is an Action
 * rather than a controller line because it touches many rows and must be
 * atomic: a half-applied run would leave two pastors in office or none.
 *
 * @see docs/DECISIONS.md D-52
 */
final class SetCurrentPastorAction
{
    public function handle(PastorAssignment $assignment): PastorAssignment
    {
        return DB::transaction(function () use ($assignment): PastorAssignment {
            PastorAssignment::query()
                ->where('is_current', true)
                ->whereKeyNot($assignment->getKey())
                ->update(['is_current' => false]);

            $assignment->update(['is_current' => true]);

            return $assignment;
        });
    }
}
