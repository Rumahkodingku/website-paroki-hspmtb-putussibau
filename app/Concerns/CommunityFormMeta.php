<?php

namespace App\Concerns;

use App\Enums\CommunityStatus;

/**
 * The labels, hints and interface copy the Komunitas admin screens share.
 *
 * Indonesian interface copy lives in lang/id/komunitas.php and is sent to React
 * rather than typed a second time in TypeScript, for the same reason as
 * ServiceFormMeta and PostFormMeta: two copies of a label drift, and the one
 * that drifts is the one nobody re-reads.
 *
 * @see docs/DECISIONS.md D-55
 */
trait CommunityFormMeta
{
    use BuildsFormMeta;

    /**
     * @return array<string, mixed>
     */
    protected function communityFormMeta(): array
    {
        return $this->buildFormMeta('komunitas', [
            'statuses' => array_map(
                fn (string $value): array => [
                    'value' => $value,
                    'label' => (string) __('komunitas.status.'.$value),
                ],
                CommunityStatus::values(),
            ),
        ]);
    }
}
