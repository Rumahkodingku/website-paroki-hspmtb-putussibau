<?php

namespace App\Concerns;

use App\Enums\LivestreamBroadcastStatus;
use App\Enums\LivestreamEditorialStatus;
use App\Models\LivestreamCategory;

/**
 * The labels, hints and interface copy the Livestreaming admin screens share.
 *
 * Indonesian interface copy lives in lang/id/livestreaming.php and is sent to
 * React rather than typed a second time in TypeScript, for the same reason as
 * GalleryFormMeta: two copies of a label drift, and the one that drifts is the
 * one nobody re-reads.
 *
 * @see docs/DECISIONS.md D-101
 */
trait LivestreamFormMeta
{
    /**
     * @return array<string, mixed>
     */
    protected function livestreamFormMeta(): array
    {
        return [
            'labels' => (array) __('livestreaming.labels'),
            'hints' => (array) __('livestreaming.hints'),
            'copy' => (array) __('livestreaming.admin'),
            'empty' => (array) __('livestreaming.empty'),
            'categories' => LivestreamCategory::query()
                ->active()
                ->ordered()
                ->get()
                ->map(fn (LivestreamCategory $category): array => [
                    'value' => (string) $category->id,
                    'label' => $category->name,
                ])
                ->all(),
            'statuses' => LivestreamEditorialStatus::options(),
            'broadcastStatuses' => LivestreamBroadcastStatus::options(),
        ];
    }
}
