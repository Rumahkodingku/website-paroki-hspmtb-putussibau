<?php

namespace App\Concerns;

use App\Enums\AreaType;

/**
 * The labels, hints and interface copy the Area admin screens share.
 *
 * Indonesian interface copy lives in lang/id/area.php and is sent to
 * React rather than typed a second time in TypeScript, for the same reason as
 * CommunityFormMeta: two copies of a label drift, and the one that drifts is the
 * one nobody re-reads.
 *
 * @see docs/DECISIONS.md D-55, D-57
 */
trait AreaFormMeta
{
    use BuildsFormMeta;

    /**
     * @return array<string, mixed>
     */
    protected function areaFormMeta(): array
    {
        return $this->buildFormMeta('area', [
            'areaTypes' => array_map(
                fn (string $value): array => [
                    'value' => $value,
                    'label' => (string) __('area.area_types.'.$value),
                ],
                AreaType::values(),
            ),
        ]);
    }
}
