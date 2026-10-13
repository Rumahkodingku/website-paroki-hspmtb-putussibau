<?php

namespace App\Actions\Profil;

use App\Models\ParishBoardPeriod;
use Illuminate\Support\Facades\DB;

/**
 * Makes one leadership period active and archives the rest.
 *
 * PRD PROF-01 and AC-PROF-5: at most one period is active at a time. Activating
 * a period must deactivate the previous one in the same transaction, or the
 * public "current period" section could show two.
 *
 * @see docs/DECISIONS.md D-52
 */
final class ActivateBoardPeriodAction
{
    public function handle(ParishBoardPeriod $period): ParishBoardPeriod
    {
        return DB::transaction(function () use ($period): ParishBoardPeriod {
            ParishBoardPeriod::query()
                ->where('is_active', true)
                ->whereKeyNot($period->getKey())
                ->update(['is_active' => false]);

            $period->update(['is_active' => true]);

            return $period;
        });
    }
}
