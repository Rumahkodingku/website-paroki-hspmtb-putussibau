<?php

namespace App\Enums;

/**
 * What has become of a scheduled activity.
 *
 * EVT-04 and the public page both say the same thing: a cancelled event is
 * still an event. Removing it from the calendar would leave a parishioner who
 * drove to the church with nothing explaining why. Postponed is the harder one
 * — it means it will happen, on a different day, and only a later edit can say
 * which — but it is still worth showing, because silence reads as "missed it".
 *
 * This is why the enum has no "completed" case. Past events are not a status a
 * human sets; they are the consequence of start_at being in the past, and
 * modelling that as a status would mean something has to flip it every day.
 *
 * @see docs/DECISIONS.md D-44
 */
enum EventStatus: string
{
    /** Happening as planned. The default. */
    case Scheduled = 'scheduled';

    /** Not happening. Still listed, with a label. */
    case Cancelled = 'cancelled';

    /** Will happen, on a date not yet fixed. Still listed, with a label. */
    case Postponed = 'postponed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The interface label, read from the language file rather than hardcoded.
     *
     * PRD AC-EVT-4 requires the label to be unmistakable, and a label written in
     * two places is a label that eventually says one thing on the list and
     * another on the detail page.
     */
    public function label(): string
    {
        return (string) __('agenda.statuses.'.$this->value);
    }
}
