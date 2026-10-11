<?php

namespace App\Concerns;

use App\Enums\EventStatus;

/**
 * The labels and choices the Agenda admin screens share.
 *
 * The same call PostFormMeta makes, against lang/id/agenda.php. Derived from
 * the enum for the same reason: EventStatus::label() reads the language file,
 * so a status cannot exist in the column without somebody having named it.
 *
 * @see docs/DECISIONS.md D-44
 */
trait EventFormMeta
{
    /**
     * @return array<string, mixed>
     */
    protected function eventFormMeta(): array
    {
        return [
            'labels' => (array) __('agenda.labels'),
            'hints' => (array) __('agenda.hints'),
            'statuses' => (array) __('agenda.statuses'),
            'eventStatusChoices' => array_map(
                static fn (EventStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                EventStatus::cases(),
            ),
        ];
    }
}
