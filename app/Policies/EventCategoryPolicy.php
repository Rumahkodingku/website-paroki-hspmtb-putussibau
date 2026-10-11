<?php

namespace App\Policies;

use App\Models\EventCategory;
use App\Models\User;

/**
 * Who may manage EventCategory rows.
 *
 * Super Admin holds every ability through the Gate::before grant in
 * AppServiceProvider (D-16), so these methods are the rules that apply once a
 * second role exists. They are written out rather than left implicit for the
 * same reason: a permission nobody reads is a permission nobody audits.
 *
 * The permissions were seeded in Phase 01 and read by nothing until Phase 05.
 * That gap is the reason D-17 calls a permission with no code behind it worse
 * than no permission at all — the four below are what `eventcategories.*` was waiting
 * for.
 *
 * @see docs/DECISIONS.md D-17, D-36, D-43
 */
class EventCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('eventcategories.view');
    }

    public function create(User $user): bool
    {
        return $user->can('eventcategories.create');
    }

    public function update(User $user, EventCategory $model): bool
    {
        return $user->can('eventcategories.update');
    }

    /**
     * A soft delete, and reserved for super admins.
     *
     * The same reasoning MassSchedulePolicy records: nothing else guards it, so
     * the permission has to be real. For the categories it also has to be real
     * in the other direction — a delete that would orphan content is refused by
     * the database, and the policy is what stops the request reaching it.
     */
    public function delete(User $user, EventCategory $model): bool
    {
        return $user->can('eventcategories.delete');
    }
}
