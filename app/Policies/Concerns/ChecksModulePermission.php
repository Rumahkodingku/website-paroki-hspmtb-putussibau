<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The four ability checks every category policy shares.
 *
 * A policy only declares its permission prefix; the model argument is unused
 * because the permission alone decides the answer.
 */
trait ChecksModulePermission
{
    abstract protected function permissionPrefix(): string;

    public function viewAny(User $user): bool
    {
        return $user->can($this->permissionPrefix().'.view');
    }

    public function create(User $user): bool
    {
        return $user->can($this->permissionPrefix().'.create');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->permissionPrefix().'.update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can($this->permissionPrefix().'.delete');
    }
}
