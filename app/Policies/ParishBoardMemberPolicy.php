<?php

namespace App\Policies;

use App\Models\ParishBoardMember;
use App\Models\User;

/**
 * Who may manage the members of a leadership period.
 *
 * @see docs/DECISIONS.md D-52
 */
class ParishBoardMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('boardmembers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('boardmembers.create');
    }

    public function update(User $user, ParishBoardMember $model): bool
    {
        return $user->can('boardmembers.update');
    }

    public function delete(User $user, ParishBoardMember $model): bool
    {
        return $user->can('boardmembers.delete');
    }
}
