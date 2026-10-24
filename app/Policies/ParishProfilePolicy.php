<?php

namespace App\Policies;

use App\Models\ParishProfile;
use App\Models\User;

/**
 * Who may read and update the profile-content singleton.
 *
 * The permission group `parishprofiles.*` already exists and is still used by
 * the service-area policy (D-52, D-69), so the page does not introduce a new
 * permission.
 *
 * @see docs/DECISIONS.md D-78
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
