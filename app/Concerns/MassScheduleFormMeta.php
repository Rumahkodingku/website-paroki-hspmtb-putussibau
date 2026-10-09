<?php

namespace App\Concerns;

use App\Enums\DayOfWeek;
use App\Enums\LocationType;
use App\Enums\MassKind;

/**
 * The labels and choices the Jadwal Misa admin screens share.
 *
 * Indonesian interface copy lives in lang/id/mass-schedule.php and is sent to
 * React rather than typed a second time in TypeScript. That is the same decision
 * SettingsController::formMeta() made for the settings form, and for the same
 * reason: two copies of a label drift, and the one that drifts is usually the
 * one nobody re-reads.
 *
 * It matters more here than for settings, because the values here are not just
 * labels — the day names and the location types have to match the enum cases
 * exactly. Deriving both sides from the enum means a new DayOfWeek case cannot
 * appear in the database with no label for it.
 *
 * @see docs/ARCHITECTURE.md Part C section 3
 */
trait MassScheduleFormMeta
{
    /**
     * Everything the admin forms need that is not a record.
     *
     * @return array<string, mixed>
     */
    protected function formMeta(): array
    {
        return [
            'labels' => (array) __('mass-schedule.labels'),
            'hints' => (array) __('mass-schedule.hints'),
            'statuses' => (array) __('mass-schedule.statuses'),
            'noticeStatuses' => (array) __('mass-schedule.notice_statuses'),
            'noticeScopes' => (array) __('mass-schedule.notice_scopes'),
            'days' => $this->dayChoices(),
            'types' => $this->locationTypeChoices(),
            'kinds' => $this->massKindChoices(),
        ];
    }

    /**
     * Day number paired with its label, Sunday first.
     *
     * @return list<array{value: int, label: string}>
     */
    protected function dayChoices(): array
    {
        return array_map(
            static fn (DayOfWeek $day): array => ['value' => $day->value, 'label' => $day->label()],
            DayOfWeek::all()
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    protected function locationTypeChoices(): array
    {
        return array_map(
            static fn (LocationType $type): array => ['value' => $type->value, 'label' => $type->label()],
            LocationType::cases()
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    protected function massKindChoices(): array
    {
        return [
            ['value' => MassKind::Rutin->value, 'label' => (string) __('mass-schedule.next_mass.regular')],
            ['value' => MassKind::Khusus->value, 'label' => (string) __('mass-schedule.next_mass.special')],
        ];
    }
}
