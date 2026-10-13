<?php

namespace App\Policies;

use App\Models\Pastor;
use App\Models\User;

/**
 * Who may manage pastors and their assignments.
 *
 * Assignment endpoints are gated on `pastors.*` too: an assignment has no
 * meaning apart from its pastor (D-52).
 *
 * @see docs/DECISIONS.md D-52
 */
class PastorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('pastors.view');
    }

    public function create(User $user): bool
    {
        return $user->can('pastors.create');
    }

    public function update(User $user, Pastor $model): bool
    {
        return $user->can('pastors.update');
    }

    public function delete(User $user, Pastor $model): bool
    {
        return $user->can('pastors.delete');
    }
}
