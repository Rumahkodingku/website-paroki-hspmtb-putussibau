<?php

namespace App\Policies;

use App\Models\PostCategory;
use App\Models\User;

/**
 * Who may manage PostCategory rows.
 *
 * Super Admin holds every ability through the Gate::before grant in
 * AppServiceProvider (D-16), so these methods are the rules that apply once a
 * second role exists. They are written out rather than left implicit for the
 * same reason: a permission nobody reads is a permission nobody audits.
 *
 * The permissions were seeded in Phase 01 and read by nothing until Phase 05.
 * That gap is the reason D-17 calls a permission with no code behind it worse
 * than no permission at all — the four below are what `postcategories.*` was waiting
 * for.
 *
 * @see docs/DECISIONS.md D-17, D-36, D-43
 */
class PostCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('postcategories.view');
    }

    public function create(User $user): bool
    {
        return $user->can('postcategories.create');
    }

    public function update(User $user, PostCategory $model): bool
    {
        return $user->can('postcategories.update');
    }

    /**
     * A soft delete, and reserved for super admins.
     *
     * The same reasoning MassSchedulePolicy records: nothing else guards it, so
     * the permission has to be real. For the categories it also has to be real
     * in the other direction — a delete that would orphan content is refused by
     * the database, and the policy is what stops the request reaching it.
     */
    public function delete(User $user, PostCategory $model): bool
    {
        return $user->can('postcategories.delete');
    }
}
