<?php

namespace App\Policies;

use App\Policies\Concerns\ChecksModulePermission;

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
    use ChecksModulePermission;

    protected function permissionPrefix(): string
    {
        return 'servicecategories';
    }
}
