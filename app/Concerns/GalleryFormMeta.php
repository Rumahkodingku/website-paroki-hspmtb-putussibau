<?php

namespace App\Concerns;

/**
 * The labels, hints and interface copy the Galeri admin screens share.
 *
 * Indonesian interface copy lives in lang/id/galeri.php and is sent to React
 * rather than typed a second time in TypeScript, for the same reason as
 * CommunityFormMeta and ServiceFormMeta: two copies of a label drift, and the
 * one that drifts is the one nobody re-reads.
 *
 * @see docs/DECISIONS.md D-65
 */
trait GalleryFormMeta
{
    /**
     * @return array<string, mixed>
     */
    protected function galleryFormMeta(): array
    {
        return [
            'labels' => (array) __('galeri.labels'),
            'hints' => (array) __('galeri.hints'),
            // Headings, buttons, dialogs and empty states, so the components
            // hold no string of their own.
            'copy' => (array) __('galeri.admin'),
            'empty' => (array) __('galeri.empty'),
        ];
    }
}
