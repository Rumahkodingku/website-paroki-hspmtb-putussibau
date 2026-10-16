<?php

namespace App\Policies;

use App\Models\CommunityCategory;
use App\Models\User;

/**
 * Who may manage CommunityCategory rows.
 *
 * Super Admin holds every ability through the Gate::before grant in
 * AppServiceProvider (D-16); these are the rules for the day a second role exists.
 *
 * Deletion is a soft delete and is refused outright while a community still
 * points at the category (roadmap 5.1) — that refusal lives in the controller,
 * because it is a data question this policy cannot answer from the user alone.
 *
 * @see docs/DECISIONS.md D-55
 */
class CommunityCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('communitycategories.view');
    }

    public function create(User $user): bool
    {
        return $user->can('communitycategories.create');
    }

    public function update(User $user, CommunityCategory $model): bool
    {
        return $user->can('communitycategories.update');
    }

    public function delete(User $user, CommunityCategory $model): bool
    {
        return $user->can('communitycategories.delete');
    }
}
