<?php

namespace App\Policies;

use App\Models\ParishProfile;
use App\Models\User;

/**
 * Who may read and update the parish profile singleton.
 *
 * Only view and update exist: there is exactly one profile row, so there is no
 * create or delete endpoint to guard (D-52).
 *
 * @see docs/DECISIONS.md D-52
 */
class ParishProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('parishprofiles.view');
    }

    public function update(User $user, ParishProfile $model): bool
    {
        return $user->can('parishprofiles.update');
    }
}
