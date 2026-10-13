<?php

namespace App\Http\Resources\Profil;

use App\Models\Media;

/**
 * Turns a media relation into a browser URL for a resource.
 *
 * The Phase 6A tables keep only a `*_media_id` foreign key — there is no path
 * column — so the URL is resolved from the loaded Media row here rather than
 * in React, keeping the disk convention on the server. The relation must be
 * eager loaded; a missing one is null and yields null.
 */
trait ResolvesMediaUrl
{
    protected function mediaUrl(?Media $media, string $variant = 'medium'): ?string
    {
        return $media?->url($variant);
    }
}
