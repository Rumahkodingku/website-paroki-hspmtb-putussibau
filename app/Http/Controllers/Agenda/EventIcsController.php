<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\IcsCalendarBuilder;
use Illuminate\Http\Response;

/**
 * The calendar file for one activity — GET /agenda/{slug}/ics
 *
 * Roadmap 8.5 and EVT-07. The route binds `{event:slug}`, and
 * Event::resolveRouteBinding resolves that through Event::published(), so an
 * unpublished or soft-deleted activity 404s here exactly as it does on the page
 * — with no second check in this class to forget. This is the reason the
 * binding was written that way rather than as a lookup in a controller.
 *
 * The placeholder this replaced returned a syntactically valid VCALENDAR with
 * no VEVENT, which was the honest thing to do while inventing a WIB date would
 * have been inventing parish data. See D-31.
 *
 * @see docs/PRD.md EVT-07, D-07
 * @see docs/DECISIONS.md D-44
 */
class EventIcsController extends Controller
{
    public function show(Event $event, IcsCalendarBuilder $builder): Response
    {
        return response(
            $builder->calendar($event),
            200,
            [
                // charset on the type because the calendar carries Indonesian
                // titles and several clients assume US-ASCII otherwise.
                'Content-Type' => 'text/calendar; charset=utf-8',
                // A download rather than an inline view: a browser that renders
                // text/calendar as text shows the parish a wall of VTIMEZONE.
                'Content-Disposition' => sprintf('attachment; filename="%s.ics"', $event->slug),
            ],
        );
    }
}
