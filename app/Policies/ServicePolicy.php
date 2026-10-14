<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

/**
 * Who may manage Service rows.
 *
 * Super Admin holds every ability through the Gate::before grant in
 * AppServiceProvider (D-16), so these methods are the rules that apply once a
 * second role exists. They are written out rather than left implicit for the
 * same reason: a permission nobody reads is a permission nobody audits.
 *
 * `services.update` is also the ability the requirement, step and FAQ endpoints
 * check through this policy — see D-54. Those children have no list page of their
 * own, so a per-child permission would guard nothing a screen maps to.
 *
 * Deletion is a soft delete (D-54). A service is retired with `is_active = false`
 * in ordinary use; deletion is the escape hatch.
 *
 * @see docs/DECISIONS.md D-54
 */
class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('services.view');
    }

    public function create(User $user): bool
    {
        return $user->can('services.create');
    }

    public function update(User $user, Service $model): bool
    {
        return $user->can('services.update');
    }

    public function delete(User $user, Service $model): bool
    {
        return $user->can('services.delete');
    }
}
