<?php

namespace App\Policies;

use App\Models\Area;
use App\Models\User;

/**
 * Who may manage Area rows.
 *
 * Super Admin holds every ability through the Gate::before grant in
 * AppServiceProvider (D-16); these are the rules for the day a second role exists.
 *
 * Deletion is refused outright while an area has children or is served by a
 * community (roadmap 5.3) — that refusal lives in the controller, because it is
 * a data question this policy cannot answer from the user alone.
 *
 * @see docs/DECISIONS.md D-55
 */
class AreaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('areas.view');
    }

    public function create(User $user): bool
    {
        return $user->can('areas.create');
    }

    public function update(User $user, Area $model): bool
    {
        return $user->can('areas.update');
    }

    public function delete(User $user, Area $model): bool
    {
        return $user->can('areas.delete');
    }
}
