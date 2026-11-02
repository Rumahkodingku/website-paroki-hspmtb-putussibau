<?php

namespace App\Enums;

/**
 * What the parish is actually doing with the broadcast.
 *
 * `live` is an operational label the admin sets after checking YouTube Studio.
 * It is not verified against the YouTube API, and the application never claims
 * otherwise. An admin who forgets to end a stream leaves it on the homepage
 * until they do; that is the accepted MVP limitation (D-101).
 *
 * @see docs/DECISIONS.md D-101
 */
enum LivestreamBroadcastStatus: string
{
    /** Planned, but not started. Never shown publicly. */
    case Scheduled = 'scheduled';

    /** Marked live by an admin. Shown on the homepage and archive page. */
    case Live = 'live';

    /** Finished. Only a published recording with a replay enters the archive. */
    case Ended = 'ended';

    /** The video cannot be played; must not appear as live. */
    case Unavailable = 'unavailable';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return (string) __('livestreaming.broadcast_statuses.'.$this->value);
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
