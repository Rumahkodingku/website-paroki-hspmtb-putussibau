<?php

namespace App\Policies;

use App\Policies\Concerns\ChecksModulePermission;

class DocumentCategoryPolicy
{
    use ChecksModulePermission;

    protected function permissionPrefix(): string
    {
        return 'documentcategories';
    }
}
