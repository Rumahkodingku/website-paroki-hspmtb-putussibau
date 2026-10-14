<?php

namespace App\Policies;

use App\Models\ServiceCategory;
use App\Models\User;

/**
 * Who may manage ServiceCategory rows.
 *
 * Super Admin holds every ability through the Gate::before grant in
 * AppServiceProvider (D-16); these are the rules for the day a second role exists.
 *
 * Deletion is a soft delete and is refused outright while a service still points
 * at the category (roadmap 6B-102, D-54) — that refusal lives in the controller,
 * because it is a data question this policy cannot answer from the user alone.
 *
 * @see docs/DECISIONS.md D-54
 */
class ServiceCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('servicecategories.view');
    }

    public function create(User $user): bool
    {
        return $user->can('servicecategories.create');
    }

    public function update(User $user, ServiceCategory $model): bool
    {
        return $user->can('servicecategories.update');
    }

    public function delete(User $user, ServiceCategory $model): bool
    {
        return $user->can('servicecategories.delete');
    }
}
