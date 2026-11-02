<?php

namespace App\Policies;

use App\Models\Livestream;
use App\Models\User;

/**
 * Who may manage Livestreaming records.
 *
 * Super Admin holds every ability through the Gate::before grant in
 * AppServiceProvider (D-16); these are the rules for the day a second role exists.
 *
 * Starting, ending and marking a stream unavailable are ordinary mutations of
 * one record, so they ride on `livestreams.update` rather than a permission
 * group of their own. There is no `livestreams.publish`: publication is a field
 * of the form, the same decision as galleries, posts and events (D-36, D-65).
 *
 * @see docs/DECISIONS.md D-101
 */
class LivestreamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('livestreams.view');
    }

    public function create(User $user): bool
    {
        return $user->can('livestreams.create');
    }

    public function update(User $user, Livestream $model): bool
    {
        return $user->can('livestreams.update');
    }

    public function delete(User $user, Livestream $model): bool
    {
        return $user->can('livestreams.delete');
    }
}
