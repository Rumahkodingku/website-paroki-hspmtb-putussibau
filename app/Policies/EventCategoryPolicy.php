<?php

namespace App\Policies;

use App\Policies\Concerns\ChecksModulePermission;

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
    use ChecksModulePermission;

    protected function permissionPrefix(): string
    {
        return 'eventcategories';
    }
}
