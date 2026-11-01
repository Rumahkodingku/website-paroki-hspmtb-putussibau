<?php

namespace App\Policies;

use App\Models\LivestreamCategory;
use App\Models\User;

class LivestreamCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('livestreamcategories.view');
    }

    public function create(User $user): bool
    {
        return $user->can('livestreamcategories.create');
    }

    public function update(User $user, LivestreamCategory $model): bool
    {
        return $user->can('livestreamcategories.update');
    }

    public function delete(User $user, LivestreamCategory $model): bool
    {
        return $user->can('livestreamcategories.delete');
    }
}
