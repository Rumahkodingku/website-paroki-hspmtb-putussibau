<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Location;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| The public agenda — /agenda and /agenda/{slug}
|--------------------------------------------------------------------------
|
| Two rules carry this file.
|
| Only published activities, and 404 for the rest. The public detail route
| binds through Event::published(), so a draft, a soft-deleted row, and a slug
| nobody wrote all answer 404 without a check in the controller to forget.
|
| Cancelled activities stay. EVT-08 asks for a cancelled activity to remain on
| the calendar and say so, which is the opposite of the usual instinct and
\* easy to "fix" into a bug: a visitor who followed a link from last week's
| announcement needs to learn it is off before they set off, not to find the
| entry quietly gone.
|
| @see docs/DECISIONS.md D-44
*/

/*
|--------------------------------------------------------------------------
| The list — EVT-02
|--------------------------------------------------------------------------
*/

it('lists published activities', function () {
    $event = Event::factory()->create(['title' => 'Synodi Pastoral']);

    $this->get('/agenda')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/agenda')
            ->where('upcoming.0.id', $event->getKey())
            // D-39: `upcoming` is a non-paginated prop, so `has(..., 1)` would
            // pass on a {data: [...]} wrapper because it counts one key.
            ->missing('upcoming.data')
            ->where('mode', 'daftar')
        );
});

it('lists nothing rather than failing when there is no agenda', function () {
    $this->get('/agenda')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('upcoming', [])
            ->where('monthly', [])
        );
});

it('sorts the upcoming list by the nearest start', function () {
    $later = Event::factory()->create(['start_at' => Carbon::now()->addDays(30)]);
    $sooner = Event::factory()->create(['start_at' => Carbon::now()->addDays(3)]);

    $this->get('/agenda')
        ->assertInertia(fn (Assert $page) => $page
            ->where('upcoming.0.id', $sooner->getKey())
            ->where('upcoming.1.id', $later->getKey())
        );
});

it('leaves out an activity that is not published', function () {
    Event::factory()->unpublished()->create();

    $this->get('/agenda')
        ->assertInertia(fn (Assert $page) => $page->where('upcoming', []));
});

it('leaves out a soft-deleted activity', function () {
    Event::factory()->create()->delete();

    $this->get('/agenda')
        ->assertInertia(fn (Assert $page) => $page->where('upcoming', []));
});

it('leaves out an activity that has already finished', function () {
    // "Ahead" means from the start of today, not from now(): an activity that
    // began an hour ago and runs until noon is still something somebody can walk
    // into, and one that began this morning belongs on today's list.
    Event::factory()->past()->create();
    $today = Event::factory()->create([
        'start_at' => Carbon::now()->subHours(2),
        'end_at' => Carbon::now()->addHours(2),
    ]);

    $this->get('/agenda')
        ->assertInertia(fn (Assert $page) => $page
            ->where('upcoming.0.id', $today->getKey())
            ->has('upcoming', 1)
        );
});

it('keeps a cancelled activity on the list', function () {
    $event = Event::factory()->cancelled()->create();

    $this->get('/agenda')
        ->assertInertia(fn (Assert $page) => $page
            ->where('upcoming.0.id', $event->getKey())
            ->where('upcoming.0.status', EventStatus::Cancelled->value)
            ->where('upcoming.0.status_label', 'Dibatalkan')
        );
});

it('caps the upcoming list', function () {
    Event::factory()->count(15)->create([
        'start_at' => Carbon::now()->addDays(20),
    ]);

    $this->get('/agenda')
        ->assertInertia(fn (Assert $page) => $page->has('upcoming', 12));
});

it('sends the description as plain text, not as markup', function () {
    // Cards render no body and a calendar entry has to be one line, so shipping
    // the HTML would mean fifteen copies of the description in the page source
    // for a string nobody renders.
    Event::factory()->create(['description' => '<p>Rapat para pastor</p>']);

    $this->get('/agenda')
        ->assertInertia(fn (Assert $page) => $page
            ->where('upcoming.0.summary', 'Rapat para pastor')
        );

    expect($this->get('/agenda')->assertOk()->getContent())
        ->not->toContain('<p>Rapat para pastor</p>');
});

it('filters by category slug', function () {
    $category = EventCategory::factory()->create(['slug' => 'sinodal']);
    $match = Event::factory()->create(['event_category_id' => $category->getKey()]);
    Event::factory()->create();

    $this->get('/agenda?kategori=sinodal')
        ->assertInertia(fn (Assert $page) => $page
            ->where('upcoming.0.id', $match->getKey())
            ->has('upcoming', 1)
        );
});

it('treats an unknown category as no filter', function () {
    Event::factory()->count(2)->create();

    $this->get('/agenda?kategori=tidak-ada')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('upcoming', 2));
});

it('sends only categories that have something published', function () {
    $used = EventCategory::factory()->create();
    EventCategory::factory()->create();
    Event::factory()->create(['event_category_id' => $used->getKey()]);

    $this->get('/agenda')
        ->assertInertia(fn (Assert $page) => $page
            ->has('categories', 1)
            ->where('categories.0.id', $used->getKey())
        );
});

/*
|--------------------------------------------------------------------------
| The month and the mode — EVT-10
|--------------------------------------------------------------------------
*/

it('defaults to the current month', function () {
    $this->get('/agenda')
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.bulan', now(config('app.display_timezone'))->format('Y-m'))
        );
});

it('sends the activities of the requested month', function () {
    $inNovember = Event::factory()->create([
        'start_at' => Carbon::parse('2026-11-14 02:00:00', 'UTC'),
    ]);
    Event::factory()->create([
        'start_at' => Carbon::parse('2026-12-14 02:00:00', 'UTC'),
    ]);

    $this->get('/agenda?bulan=2026-11')
        ->assertInertia(fn (Assert $page) => $page
            ->where('monthly.0.id', $inNovember->getKey())
            ->has('monthly', 1)
        );
});

it('ignores a month that is not a month', function () {
    // The month reaches ORDER BY and a BETWEEN, so an unchecked string from a
    // bookmarked link would be a 500 rather than the current month.
    $this->get('/agenda?bulan=bukan-bulan')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            // A regex, not has(..., 1): `has` counts elements and bulan is a
            // string, so that assertion fails on a type error rather than on a
            // wrong month.
            ->where('filters.bulan', fn (string $value): bool => (bool) preg_match('/^\d{4}-\d{2}$/', $value))
        );
});

it('keeps a past month reachable', function () {
    // A calendar that could only show the current month would be useless for
    // "what was on last month", which is a real question about a parish year.
    $lastMonth = Carbon::parse('2026-11-14 02:00:00', 'UTC');
    $event = Event::factory()->create(['start_at' => $lastMonth]);

    $this->get('/agenda?bulan=2026-11&mode=kalender')
        ->assertInertia(fn (Assert $page) => $page
            ->where('monthly.0.id', $event->getKey())
        );
});

it('switches to the calendar view', function () {
    $this->get('/agenda?mode=kalender')
        ->assertInertia(fn (Assert $page) => $page->where('mode', 'kalender'));
});

it('treats an unknown mode as the list', function () {
    $this->get('/agenda?mode=apa-saja')
        ->assertInertia(fn (Assert $page) => $page->where('mode', 'daftar'));
});

it('sends both readings at once, so switching mode needs no round trip', function () {
    Event::factory()->create(['start_at' => Carbon::now()->addHours(1)]);

    $this->get('/agenda')
        ->assertInertia(fn (Assert $page) => $page
            ->has('upcoming', 1)
            ->has('monthly', 1)
        );
});

it('sends today computed in the display timezone', function () {
    // The calendar's "today" ring and the "Bulan ini" button have to agree, so
    // the server sends one value and React does not read its own clock.
    $this->get('/agenda')
        ->assertInertia(fn (Assert $page) => $page
            ->where('today', now(config('app.display_timezone'))->format('Y-m-d'))
        );
});

/*
|--------------------------------------------------------------------------
| The detail page — EVT-09
|--------------------------------------------------------------------------
*/

it('shows a published activity', function () {
    $event = Event::factory()->create(['title' => 'Retreat Perrenakan']);

    $this->get(route('agenda.show', $event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/agenda/show')
            ->where('event.id', $event->getKey())
            ->where('event.title', 'Retreat Perrenakan')
        );
});

it('answers 404 for an activity that is not published', function () {
    $this->get(route('agenda.show', Event::factory()->unpublished()->create()))
        ->assertNotFound();
});

it('answers 404 for a soft-deleted activity', function () {
    $this->get(route('agenda.show', Event::factory()->create()->delete()))
        ->assertNotFound();
});

it('answers 404 for a slug nobody wrote', function () {
    $this->get('/agenda/tidak-pernah-dibuat')->assertNotFound();
});

it('still shows a cancelled activity, labelled', function () {
    // The opposite of what a filter for `status` would do, and the whole point
    // of EVT-08. Asserted as reachable *and* labelled, because a page that is
    // reachable but reads as normal is a worse outcome than a 404: the reader
    // sets off for a cancelled activity.
    $event = Event::factory()->cancelled()->create();

    $this->get(route('agenda.show', $event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('event.status', EventStatus::Cancelled->value)
            ->where('event.status_label', 'Dibatalkan')
        );
});

it('marks a postponed activity as postponed', function () {
    $event = Event::factory()->postponed()->create();

    $this->get(route('agenda.show', $event))
        ->assertInertia(fn (Assert $page) => $page
            ->where('event.status_label', 'Ditunda')
        );
});

it('sends the instants the calendar actions need', function () {
    // AC-EVT-3 is about the two buttons working, and a browser that has to
    // re-derive these from a formatted date string is a browser that gets them
    // wrong somewhere.
    $event = Event::factory()->create([
        'start_at' => Carbon::parse('2026-11-14 02:00:00', 'UTC'),
        'end_at' => Carbon::parse('2026-11-14 05:30:00', 'UTC'),
    ]);

    $this->get(route('agenda.show', $event))
        ->assertInertia(fn (Assert $page) => $page
            ->where('event.start_at', '2026-11-14T02:00:00+00:00')
            ->where('event.end_at', '2026-11-14T05:30:00+00:00')
            ->where('event.is_all_day', false)
        );
});

it('sends a null end rather than a missing key', function () {
    // A missing key is `undefined` in React and a Google Calendar URL built
    // from it produces `.../undefined`, which is a silent 400 from Google.
    $event = Event::factory()->create(['end_at' => null]);

    $this->get(route('agenda.show', $event))
        ->assertInertia(fn (Assert $page) => $page
            ->where('event.end_at', null)
            ->has('event.end_at')
        );
});

it('sends the description as sanitized HTML and a plain copy', function () {
    $event = Event::factory()->create([
        'description' => '<p>Rapat para <strong>pastor</strong>.</p>',
    ]);

    $this->get(route('agenda.show', $event))
        ->assertInertia(fn (Assert $page) => $page
            ->where('event.description', '<p>Rapat para <strong>pastor</strong>.</p>')
            ->where('event.plain_description', 'Rapat para pastor.')
        );
});

it('resolves the location name', function () {
    $location = Location::factory()->create(['name' => 'Gereja Induk Santo Yusuf']);
    $event = Event::factory()->create(['location_id' => $location->getKey()]);

    $this->get(route('agenda.show', $event))
        ->assertInertia(fn (Assert $page) => $page
            ->where('event.location_name', 'Gereja Induk Santo Yusuf')
        );
});

it('offers related activities from the same category', function () {
    $category = EventCategory::factory()->create();
    $event = Event::factory()->create(['event_category_id' => $category->getKey()]);
    $other = Event::factory()->create(['event_category_id' => $category->getKey()]);

    $this->get(route('agenda.show', $event))
        ->assertInertia(fn (Assert $page) => $page
            ->where('related.0.id', $other->getKey())
            ->has('related', 1)
        );
});

it('does not offer itself as related', function () {
    $category = EventCategory::factory()->create();
    $event = Event::factory()->create(['event_category_id' => $category->getKey()]);

    $this->get(route('agenda.show', $event))
        ->assertInertia(fn (Assert $page) => $page->where('related', []));
});

it('does not offer an unpublished activity as related', function () {
    $category = EventCategory::factory()->create();
    $event = Event::factory()->create(['event_category_id' => $category->getKey()]);
    Event::factory()->unpublished()->create([
        'event_category_id' => $category->getKey(),
    ]);

    $this->get(route('agenda.show', $event))
        ->assertInertia(fn (Assert $page) => $page->where('related', []));
});

it('offers a cancelled activity as related, still labelled', function () {
    // Kept, on the same reasoning as the rest of EVT-08: filtering cancellations
    // out of the related list would remove them from the one place a reader goes
    // to find out what else is happening this month, and the activity they were
    // told about has just been called off. The EventCard renders the badge, so
    // what arrives is "Dibatalkan", not silence.
    //
    // Asserted as present *and* labelled, which is the form that stops somebody
    // later adding a `where status` to related() and breaking it quietly.
    $category = EventCategory::factory()->create();
    $event = Event::factory()->create(['event_category_id' => $category->getKey()]);
    $cancelled = Event::factory()->cancelled()->create([
        'event_category_id' => $category->getKey(),
    ]);

    $this->get(route('agenda.show', $event))
        ->assertInertia(fn (Assert $page) => $page
            ->where('related.0.id', $cancelled->getKey())
            ->where('related.0.status', EventStatus::Cancelled->value)
            ->where('related.0.status_label', 'Dibatalkan')
        );
});
