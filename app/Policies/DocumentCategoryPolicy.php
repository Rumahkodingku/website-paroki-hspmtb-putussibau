<?php

namespace App\Policies;

use App\Models\DocumentCategory;
use App\Models\User;

class DocumentCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('documentcategories.view');
    }

    public function create(User $user): bool
    {
        return $user->can('documentcategories.create');
    }

    public function update(User $user, DocumentCategory $category): bool
    {
        return $user->can('documentcategories.update');
    }

    public function delete(User $user, DocumentCategory $category): bool
    {
        return $user->can('documentcategories.delete');
    }
}
