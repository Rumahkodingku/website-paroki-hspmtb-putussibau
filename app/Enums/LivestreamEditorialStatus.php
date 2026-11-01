<?php

namespace App\Enums;

/**
 * Where a livestream record is in its editorial life cycle.
 *
 * This is not the broadcast state. `status` answers "may a visitor see this
 * record at all?", while `broadcast_status` answers "what is happening on
 * YouTube right now?". Keeping them apart is what lets an ended recording stay
 * published while a draft never leaks into the archive.
 *
 * @see docs/DECISIONS.md D-101
 */
enum LivestreamEditorialStatus: string
{
    /** Being prepared. Only an admin sees it. */
    case Draft = 'draft';

    /** Public, subject to the broadcast/recording rules. */
    case Published = 'published';

    /** Pulled from the public site, still kept for the admin. */
    case Archived = 'archived';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The interface label, read from the language file rather than hardcoded.
     */
    public function label(): string
    {
        return (string) __('livestreaming.statuses.'.$this->value);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases(),
        );
    }
}
