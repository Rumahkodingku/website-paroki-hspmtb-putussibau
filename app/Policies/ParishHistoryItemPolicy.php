<?php

namespace App\Policies;

use App\Models\ParishHistoryItem;
use App\Models\User;

/**
 * Who may manage history timeline items.
 *
 * @see docs/DECISIONS.md D-52
 */
class ParishHistoryItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('parishhistories.view');
    }

    public function create(User $user): bool
    {
        return $user->can('parishhistories.create');
    }

    public function update(User $user, ParishHistoryItem $model): bool
    {
        return $user->can('parishhistories.update');
    }

    public function delete(User $user, ParishHistoryItem $model): bool
    {
        return $user->can('parishhistories.delete');
    }
}
