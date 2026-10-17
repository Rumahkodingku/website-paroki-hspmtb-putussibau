<?php

namespace App\Policies;

use App\Models\GalleryAlbum;
use App\Models\User;

/**
 * Who may manage Gallery albums.
 *
 * Super Admin holds every ability through the Gate::before grant in
 * AppServiceProvider (D-16); these are the rules for the day a second role exists.
 *
 * The photos inside an album are edited as part of the album, so attach,
 * reorder, caption, cover and detach all ride on `galleries.update` rather than
 * a permission group of their own (D-54, D-55 point 11, D-65).
 *
 * @see docs/DECISIONS.md D-65
 */
class GalleryAlbumPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('galleries.view');
    }

    public function create(User $user): bool
    {
        return $user->can('galleries.create');
    }

    public function update(User $user, GalleryAlbum $model): bool
    {
        return $user->can('galleries.update');
    }

    public function delete(User $user, GalleryAlbum $model): bool
    {
        return $user->can('galleries.delete');
    }
}
