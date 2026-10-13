<?php

namespace App\Concerns;

use App\Enums\PastorPosition;

/**
 * The labels, hints and position choices the Profil Paroki admin screens share.
 *
 * Interface copy lives in lang/id/profil.php and is sent to React rather than
 * typed a second time in TypeScript, following PostFormMeta. Two copies of a
 * label drift, and the copy that drifts is the one nobody re-reads.
 *
 * The positions come from PastorPosition::cases(), so a new position cannot
 * reach the database without a label for it.
 *
 * @see docs/DECISIONS.md D-52
 */
trait ProfilFormMeta
{
    /**
     * @return array<string, mixed>
     */
    protected function profilFormMeta(): array
    {
        return [
            'labels' => (array) __('profil.labels'),
            'hints' => (array) __('profil.hints'),
            'positions' => array_map(
                static fn (PastorPosition $position): array => [
                    'value' => $position->value,
                    'label' => $position->label(),
                ],
                PastorPosition::cases(),
            ),
        ];
    }
}
