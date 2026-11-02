<?php

namespace App\Policies;

use App\Policies\Concerns\ChecksModulePermission;

class LivestreamCategoryPolicy
{
    use ChecksModulePermission;

    protected function permissionPrefix(): string
    {
        return 'livestreamcategories';
    }
}
