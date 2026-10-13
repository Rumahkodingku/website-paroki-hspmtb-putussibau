<?php

namespace App\Policies;

use App\Models\ParishServiceArea;
use App\Models\User;

/**
 * Who may read and update the service-area singleton.
 *
 * Gated on `parishprofiles.*` rather than a sixth permission group: the
 * service-area narrative is a section of the profile page, and D-52 folds it
 * into the profile group.
 *
 * @see docs/DECISIONS.md D-52
 */
class ParishServiceAreaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('parishprofiles.view');
    }

    public function update(User $user, ParishServiceArea $model): bool
    {
        return $user->can('parishprofiles.update');
    }
}
