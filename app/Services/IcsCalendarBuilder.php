<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Models\Event;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Writes one activity as an RFC 5545 VEVENT.
 *
 * A Service and not an Action because it writes nothing: it takes a model and
 * returns a string. It also never touches the HTTP request, which
 * ArchitectureTest asserts for App\Services — the controller reads the slug and
 * the Accept header, and this class only knows about events and time.
 *
 * Five things in here are not obvious, and each of them is a way a calendar
 * file ends up broken in a way no test on the HTTP response would notice.
 *
 * 1. Line endings are CRLF. RFC 5545 requires it and several clients reject a
 *    calendar written with bare LF without saying why.
 *
 * 2. Every text value is escaped, and \r\n is escaped BEFORE the other
 *    characters. The order is load-bearing: a title of `X\r\nBEGIN:VEVENT`
 *    replaced with the single characters first becomes `X\\nBEGIN:VEVENT`,
 *    whose backslash-n is an escape and whose event stays inside the summary.
 *    Replaced with the pair first it becomes `X\nBEGIN:VEVENT`, one property
 *    with a newline in it, which is what the injection needs.
 *
 * 3. UID is stable — slug at the app URL — so a parishioner who downloads the
 *    file twice, or changes one field, updates the entry in their calendar
 *    rather than accumulating copies. A UUID per download would do the opposite.
 *
 * 4. Times are UTC with a Z suffix. DTSTART;TZID= would need a VTIMEZONE
 *    block, and an activity is stored in UTC already (D-01), so the conversion
 *    is free and the file has one fewer thing to get wrong.
 *
 * 5. An all-day activity is a date, not a midnight. VALUE=DATE with DTSTART as
 *    YYYYMMDD, and DTEND as the following day — the end of an all-day event is
 *    exclusive, so writing the same day twice is how an activity ends up lasting
 *    no time at all.
 *
 * @see docs/PRD.md EVT-07
 * @see docs/DECISIONS.md D-44
 */
class IcsCalendarBuilder
{
    /**
     * The whole calendar file for one activity.
     */
    public function calendar(Event $event): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Paroki HSPMTB Putussibau//Agenda//ID',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
        ];

        foreach ($this->event($event) as $line) {
            $lines[] = $line;
        }

        $lines[] = 'END:VCALENDAR';

        // The trailing CRLF is part of the format, not decoration: a file whose
        // last line has no terminator is rejected by some parsers.
        return implode("\r\n", $lines)."\r\n";
    }

    /**
     * The VEVENT block, without the calendar wrapper.
     *
     * @return list<string>
     */
    public function event(Event $event): array
    {
        $lines = [
            'BEGIN:VEVENT',
            'UID:'.$this->uid($event),
            'DTSTAMP:'.$this->timestamp(Carbon::now()),
            'SUMMARY:'.$this->escape($event->title),
            $this->when($event),
        ];

        $end = $this->end($event);

        if ($end !== null) {
            $lines[] = $end;
        }

        $location = $event->displayLocationName();

        if ($location !== null) {
            $lines[] = 'LOCATION:'.$this->escape($location);
        }

        $description = $event->plainDescription();

        if ($description !== null) {
            $lines[] = 'DESCRIPTION:'.$this->escape($description);
        }

        $lines[] = 'URL:'.route('agenda.show', ['event' => $event->slug]);
        // EVT-07: a cancelled activity is still in the file, and says so.
        $lines[] = 'STATUS:'.($event->status === EventStatus::Cancelled ? 'CANCELLED' : 'CONFIRMED');
        $lines[] = 'END:VEVENT';

        return $lines;
    }

    /**
     * A UID that survives re-downloading.
     *
     * Slug rather than key: the slug is already unique and it is already the
     * public address, so the identifier a calendar stores is the one a person
     * would recognise.
     */
    public function uid(Event $event): string
    {
        return $event->slug.'@'.preg_replace('#^https?://#', '', (string) config('app.url'));
    }

    /**
     * DTSTART, in whichever of the two forms the activity calls for.
     */
    private function when(Event $event): string
    {
        if ($event->is_all_day) {
            return 'DTSTART;VALUE=DATE:'.$event->start_at
                ->setTimezone($this->displayTimezone())
                ->format('Ymd');
        }

        return 'DTSTART:'.$this->timestamp($event->start_at);
    }

    /**
     * DTEND, or null when the activity states no finish.
     *
     * All-day ends the day AFTER it starts: the RFC's end date is exclusive, so
     * a retreat on the 12th is DTSTART;VALUE=DATE:20261012 and
     * DTEND;VALUE=DATE:20261013. Writing the 12th twice is the single most
     * common all-day bug and it renders as an activity of zero length.
     */
    private function end(Event $event): ?string
    {
        if ($event->is_all_day) {
            $start = $event->start_at->setTimezone($this->displayTimezone())->startOfDay();

            return 'DTEND;VALUE=DATE:'.$start->addDay()->format('Ymd');
        }

        if ($event->end_at === null) {
            return null;
        }

        return 'DTEND:'.$this->timestamp($event->end_at);
    }

    /**
     * An instant as RFC 5545 UTC: 20261012T020000Z.
     *
     * CarbonInterface rather than Carbon, and that is not pedantry. Event casts
     * start_at and end_at as immutable_datetime, so a model loaded from the
     * database hands over a CarbonImmutable — a sibling of Carbon, not a
     * subclass of it. A `Carbon` type hint here throws a TypeError on every
     * request that serves a calendar file, and not on the unit-style calls that
     * pass a freshly made model, because those still hold the Carbon the
     * factory assigned.
     */
    private function timestamp(CarbonInterface $moment): string
    {
        return $moment->utc()->format('Ymd\THis\Z');
    }

    private function displayTimezone(): string
    {
        return (string) config('app.display_timezone');
    }

    /**
     * Escape one text value per RFC 5545 section 3.3.11.
     *
     * The order of the first three pairs is the injection defence and is not
     * interchangeable — see the class docblock.
     */
    private function escape(string $value): string
    {
        return str_replace(
            ['\\', "\r\n", "\n", "\r", ';', ','],
            ['\\\\', '\\n', '\\n', '\\n', '\\;', '\\,'],
            $value
        );
    }
}
