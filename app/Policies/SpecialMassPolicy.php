<?php

namespace App\Policies;

use App\Models\SpecialMass;
use App\Models\User;

/**
 * Who may manage SpecialMass rows.
 *
 * Super Admin holds every ability through the Gate::before grant in
 * AppServiceProvider (D-16), so these methods are the rules that apply once a
 * second role exists. They are written out rather than left implicit for the
 * same reason: a permission nobody reads is a permission nobody audits.
 *
 * There is no delete ability because there is no delete endpoint. Roadmap 5.6
 * forbids adding one while deactivation covers the need, and PRD 7.3 says the
 * admin "nonaktifkan, tidak hapus". See D-34.
 *
 * @see docs/DECISIONS.md D-34, D-36
 */
class SpecialMassPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('specialmasses.view');
    }

    public function create(User $user): bool
    {
        return $user->can('specialmasses.create');
    }

    public function update(User $user, SpecialMass $model): bool
    {
        return $user->can('specialmasses.update');
    }

    /**
     * Deletion is a soft delete, and it is reserved for super admins.
     *
     * Nothing else guards it: there is no delete ability to fall through, no
     * second role to compare against, and the migration writes deleted_at
     * rather than removing the row. Which is exactly why the permission has to
     * be real - see D-40.
     */
    public function delete(User $user, SpecialMass $model): bool
    {
        return $user->can('specialmasses.delete');
    }
}
