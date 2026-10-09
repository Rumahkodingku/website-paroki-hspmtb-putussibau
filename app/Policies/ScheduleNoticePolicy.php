<?php

namespace App\Policies;

use App\Models\ScheduleNotice;
use App\Models\User;

/**
 * Who may manage ScheduleNotice rows.
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
class ScheduleNoticePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('schedulenotices.view');
    }

    public function create(User $user): bool
    {
        return $user->can('schedulenotices.create');
    }

    public function update(User $user, ScheduleNotice $model): bool
    {
        return $user->can('schedulenotices.update');
    }
}
