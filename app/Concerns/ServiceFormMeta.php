<?php

namespace App\Concerns;

/**
 * The labels, hints and interface copy the Pelayanan admin screens share.
 *
 * Indonesian interface copy lives in lang/id/pelayanan.php and is sent to React
 * rather than typed a second time in TypeScript, for the same reason as
 * PostFormMeta and MassScheduleFormMeta: two copies of a label drift, and the one
 * that drifts is the one nobody re-reads.
 *
 * The image fields (services.thumbnail, service_categories) are uploaded through
 * the media picker, not chosen from a config allowlist, so no icon list is sent.
 *
 * @see docs/DECISIONS.md D-54
 */
trait ServiceFormMeta
{
    use BuildsFormMeta;

    /**
     * @return array<string, mixed>
     */
    protected function serviceFormMeta(): array
    {
        return $this->buildFormMeta('pelayanan', [
            'methods' => (array) __('pelayanan.methods'),
            'conditions' => (array) __('pelayanan.requirement_conditions'),
        ]);
    }
}
