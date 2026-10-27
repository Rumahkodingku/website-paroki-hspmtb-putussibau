<?php

namespace App\Actions\Profil;

use App\Models\PastorAssignment;
use App\Services\AuditLogger;
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
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(PastorAssignment $assignment, bool $record = true): PastorAssignment
    {
        return DB::transaction(function () use ($assignment, $record): PastorAssignment {
            $affected = PastorAssignment::query()
                ->where('pastor_id', $assignment->pastor_id)
                ->where('is_current', true)
                ->whereKeyNot($assignment->getKey())
                ->pluck('id')
                ->all();

            // Clear only the other assignments of the *same* pastor, so several
            // pastors can be in office at once (paroki + rekan, etc.).
            PastorAssignment::query()
                ->where('pastor_id', $assignment->pastor_id)
                ->where('is_current', true)
                ->whereKeyNot($assignment->getKey())
                ->update(['is_current' => false]);

            $assignment->update(['is_current' => true]);

            if ($record) {
                $this->audit->recordModel(
                    'pastor_assignments',
                    'activated',
                    'success',
                    $assignment,
                    [],
                    $this->audit->snapshot($assignment, 'pastor_assignments'),
                    ['affected_ids' => $affected],
                );
            }

            return $assignment;
        });
    }
}
