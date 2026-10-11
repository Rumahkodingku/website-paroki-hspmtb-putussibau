<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;

/**
 * Who may manage Location rows.
 *
 * Super Admin holds every ability through the Gate::before grant in
 * AppServiceProvider (D-16), so these methods are the rules that apply once a
 * second role exists. They are written out rather than left implicit for the
 * same reason: a permission nobody reads is a permission nobody audits.
 *
 * Deletion arrived later than the rest of the module and is a soft delete, so
 * "locations.delete" hides a row rather than removing it. Roadmap 4.5 still
 * holds and is enforced one layer up, in LocationController::destroy(): a
 * location with schedules or special masses is refused, because both point here
 * ON DELETE RESTRICT.
 *
 * @see docs/DECISIONS.md D-36, D-40
 */
class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('locations.view');
    }

    public function create(User $user): bool
    {
        return $user->can('locations.create');
    }

    public function update(User $user, Location $model): bool
    {
        return $user->can('locations.update');
    }

    /**
     * Deletion is a soft delete, and it is reserved for super admins.
     *
     * Nothing else guards it: there is no delete ability to fall through, no
     * second role to compare against, and the migration writes deleted_at
     * rather than removing the row. Which is exactly why the permission has to
     * be real - see D-40.
     */
    public function delete(User $user, Location $model): bool
    {
        return $user->can('locations.delete');
    }
}
