<?php

namespace App\Policies;

use App\Models\Community;
use App\Models\User;

/**
 * Who may manage Community rows.
 *
 * Super Admin holds every ability through the Gate::before grant in
 * AppServiceProvider (D-16); these are the rules for the day a second role exists.
 *
 * The areas a community serves are edited as part of the community, so they
 * ride on `communities.update` rather than a permission of their own (D-55
 * point 11).
 *
 * @see docs/DECISIONS.md D-55
 */
class CommunityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('communities.view');
    }

    public function create(User $user): bool
    {
        return $user->can('communities.create');
    }

    public function update(User $user, Community $model): bool
    {
        return $user->can('communities.update');
    }

    public function delete(User $user, Community $model): bool
    {
        return $user->can('communities.delete');
    }
}
