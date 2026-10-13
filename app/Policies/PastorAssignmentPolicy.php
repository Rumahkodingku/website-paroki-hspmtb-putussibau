<?php

namespace App\Policies;

use App\Models\PastorAssignment;
use App\Models\User;

/**
 * Who may manage a pastor's assignment history.
 *
 * Reads `pastors.*`; assignment routes only ever run for somebody who can
 * already manage the pastor.
 *
 * @see docs/DECISIONS.md D-52
 */
class PastorAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pastors.view');
    }

    public function create(User $user): bool
    {
        return $user->can('pastors.update');
    }

    public function update(User $user, PastorAssignment $model): bool
    {
        return $user->can('pastors.update');
    }

    public function delete(User $user, PastorAssignment $model): bool
    {
        return $user->can('pastors.update');
    }
}
