<?php

namespace App\Policies;

use App\Models\ParishBoardPeriod;
use App\Models\User;

/**
 * Who may manage leadership periods.
 *
 * @see docs/DECISIONS.md D-52
 */
class ParishBoardPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('boardperiods.view');
    }

    public function create(User $user): bool
    {
        return $user->can('boardperiods.create');
    }

    public function update(User $user, ParishBoardPeriod $model): bool
    {
        return $user->can('boardperiods.update');
    }

    public function delete(User $user, ParishBoardPeriod $model): bool
    {
        return $user->can('boardperiods.delete');
    }
}
