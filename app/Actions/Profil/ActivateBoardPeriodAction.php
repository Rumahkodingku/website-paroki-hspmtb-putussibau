<?php

namespace App\Actions\Profil;

use App\Models\ParishBoardPeriod;
use App\Services\AuditLogger;
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
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(ParishBoardPeriod $period, bool $record = true): ParishBoardPeriod
    {
        return DB::transaction(function () use ($period, $record): ParishBoardPeriod {
            $affected = ParishBoardPeriod::query()
                ->where('is_active', true)
                ->whereKeyNot($period->getKey())
                ->pluck('id')
                ->all();

            ParishBoardPeriod::query()
                ->where('is_active', true)
                ->whereKeyNot($period->getKey())
                ->update(['is_active' => false]);

            $period->update(['is_active' => true]);

            if ($record) {
                $this->audit->recordModel(
                    'parish_boards',
                    'activated',
                    'success',
                    $period,
                    [],
                    $this->audit->snapshot($period, 'parish_boards'),
                    ['affected_ids' => $affected],
                );
            }

            return $period;
        });
    }
}
