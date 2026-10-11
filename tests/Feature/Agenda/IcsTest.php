<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Location;
use App\Services\IcsCalendarBuilder;
use Illuminate\Support\Carbon;

/*
|--------------------------------------------------------------------------
| The calendar file — PRD EVT-07
|--------------------------------------------------------------------------
|
| AC-EVT-3 is "a visitor can add the activity to their calendar", and a .ics
| file is the part of that which can be checked exactly: every line either
| matches RFC 5545 or the file is wrong in a way no browser will show.
|
| The four traps this file exists for, in the order they actually bite:
|
|   1. An all-day activity's DTEND is exclusive. Writing the start date twice
|      is the most common all-day bug in the format and it renders as an
|      activity of zero length.
|
|   2. A WIB instant is 07:00 behind UTC. DTSTART has to carry the Z, and the
|      conversion has to happen.
|
|   3. Text is escaped, and the backslash has to be escaped first or the
|      escapes introduced by the later passes get escaped again.
|
|   4. CRLF everywhere, including after the last line.
|
| @see app/Services/IcsCalendarBuilder.php
| @see docs/DECISIONS.md D-44
*/

/*
|--------------------------------------------------------------------------
| The envelope
|--------------------------------------------------------------------------
*/

it('wraps the event in a calendar', function () {
    $event = Event::factory()->create();

    $ics = (new IcsCalendarBuilder)->calendar($event);

    expect($ics)->toStartWith("BEGIN:VCALENDAR\r\n")
        ->and($ics)->toEndWith("END:VCALENDAR\r\n");
});

it('declares the version and the calendar scale', function () {
    $ics = (new IcsCalendarBuilder)->calendar(Event::factory()->create());

    expect($ics)->toContain('VERSION:2.0')
        ->and($ics)->toContain('CALSCALE:GREGORIAN')
        ->and($ics)->toContain('PRODID:-//Paroki HSPMTB Putussibau//Agenda//ID');
});

it('uses CRLF on every line', function () {
    $ics = (new IcsCalendarBuilder)->calendar(Event::factory()->create());

    // A bare LF is accepted by some clients and not others, so a file that
    // mixes them is a file that works on the secretary's phone and not on the
    // parish laptop.
    expect($ics)->not->toContain("\n\n")
        ->and(preg_match('/(?<!\r)\n/', $ics))->toBe(0);
});

it('terminates the last line', function () {
    $ics = (new IcsCalendarBuilder)->calendar(Event::factory()->create());

    // Some parsers reject a final line with no terminator.
    expect($ics)->toEndWith("\r\n");
});

it('contains exactly one event', function () {
    $ics = (new IcsCalendarBuilder)->calendar(Event::factory()->create());

    expect(substr_count($ics, 'BEGIN:VEVENT'))->toBe(1)
        ->and(substr_count($ics, 'END:VEVENT'))->toBe(1);
});

it('stamps the generation time', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-11 02:00:00', 'UTC'));

    $ics = (new IcsCalendarBuilder)->calendar(Event::factory()->create());

    expect($ics)->toContain('DTSTAMP:20261011T020000Z');
});

it('gives the event a UID built from the slug', function () {
    $event = Event::factory()->create(['slug' => 'synodi-pastoral']);

    $ics = (new IcsCalendarBuilder)->calendar($event);

    // The UID has to survive the reader re-downloading the file months later
    // and importing it a second time: a UID that changes every time makes every
    // re-import a duplicate entry in their calendar.
    expect($ics)->toContain('UID:synodi-pastoral@');
});

it('gives the same UID for the same event twice', function () {
    $event = Event::factory()->create(['slug' => 'synodi-pastoral']);
    $builder = new IcsCalendarBuilder;

    expect($builder->uid($event))->toBe($builder->uid($event));
});

/*
|--------------------------------------------------------------------------
| Timed activities — the 07:00 conversion
|--------------------------------------------------------------------------
*/

it('writes a timed start in UTC', function () {
    $event = Event::factory()->create([
        'start_at' => Carbon::parse('2026-11-14 02:00:00', 'UTC'),
    ]);

    // 09:00 WIB is 02:00 UTC. A builder that skipped the Z and wrote 09:00Z
    // would put the activity seven hours early in every calendar on earth.
    expect((new IcsCalendarBuilder)->calendar($event))
        ->toContain('DTSTART:20261114T020000Z');
});

it('writes a timed end in UTC', function () {
    $event = Event::factory()->create([
        'start_at' => Carbon::parse('2026-11-14 02:00:00', 'UTC'),
        'end_at' => Carbon::parse('2026-11-14 05:30:00', 'UTC'),
    ]);

    expect((new IcsCalendarBuilder)->calendar($event))
        ->toContain('DTEND:20261114T053000Z');
});

it('omits the end when the activity states none', function () {
    // A DTEND of the start would make the activity zero-length in a calendar
    // that requires one.
    $event = Event::factory()->create([
        'start_at' => Carbon::parse('2026-11-14 02:00:00', 'UTC'),
        'end_at' => null,
    ]);

    expect((new IcsCalendarBuilder)->calendar($event))
        ->not->toContain('DTEND');
});

/*
|--------------------------------------------------------------------------
| All-day activities — the exclusive end
|--------------------------------------------------------------------------
*/

it('writes an all-day start as a bare date', function () {
    $event = Event::factory()->allDay()->create([
        'start_at' => Carbon::parse('2026-10-12 00:00:00', 'Asia/Pontianak')->utc(),
    ]);

    $ics = (new IcsCalendarBuilder)->calendar($event);

    expect($ics)->toContain('DTSTART;VALUE=DATE:20261012')
        // No clock on an all-day entry. A time on it is what turns a retreat
        // into a meeting that begins at midnight.
        ->and($ics)->not->toMatch('/DTSTART;VALUE=DATE:\d{8}T/');
});

it('ends an all-day activity the day after it starts', function () {
    $event = Event::factory()->allDay()->create([
        'start_at' => Carbon::parse('2026-10-12 00:00:00', 'Asia/Pontianak')->utc(),
    ]);

    // The exclusive end. DTSTART 12th, DTEND 13th. Writing the 12th twice is the
    // most common all-day bug in the format and every calendar renders it as an
    // activity of zero length.
    expect((new IcsCalendarBuilder)->calendar($event))
        ->toContain('DTEND;VALUE=DATE:20261013');
});

it('resolves the all-day date in WIB, not in UTC', function () {
    // Midnight WIB on the 12th is 17:00 UTC on the 11th. A builder that read
    // the UTC date would file the retreat on the 11th.
    $event = Event::factory()->allDay()->create([
        'start_at' => Carbon::parse('2026-10-12 00:00:00', 'Asia/Pontianak')->utc(),
    ]);

    $ics = (new IcsCalendarBuilder)->calendar($event);

    // Scoped to the two date properties rather than the whole file. DTSTAMP is
    // the moment the file was generated, so a bare not->toContain on the UTC
    // date fails for a reason that has nothing to do with the conversion under
    // test — it fails on any test run in the UTC day before the retreat.
    expect($ics)->toContain('DTSTART;VALUE=DATE:20261012')
        ->and($ics)->toContain('DTEND;VALUE=DATE:20261013')
        ->and($ics)->not->toContain('VALUE=DATE:20261011');
});

/*
|--------------------------------------------------------------------------
| Text
|--------------------------------------------------------------------------
*/

it('escapes a comma in the title', function () {
    $event = Event::factory()->create(['title' => 'Synodi, Keuskupan']);

    expect((new IcsCalendarBuilder)->calendar($event))
        ->toContain('SUMMARY:Synodi\\, Keuskupan');
});

it('escapes a semicolon in the title', function () {
    $event = Event::factory()->create(['title' => 'Jamuan; malam syukur']);

    expect((new IcsCalendarBuilder)->calendar($event))
        ->toContain('SUMMARY:Jamuan\\; malam syukur');
});

it('escapes a newline in the title rather than splitting the property', function () {
    $event = Event::factory()->create([
        'title' => "Synodi Pastoral\nKeuskupan",
    ]);

    $ics = (new IcsCalendarBuilder)->calendar($event);

    // The title, not the description. An unescaped newline ends the SUMMARY
    // property and the next line becomes a property of its own, which is how a
    // stray word ends up as a LOCATION in somebody's calendar.
    //
    // The title is the field that can actually carry one: validation is
    // `string|max:255`, which allows a newline, and the column is free text an
    // admin types. The description cannot — HtmlSanitizer::toPlainText collapses
    // every run of whitespace to a single space before the builder ever sees it,
    // so its newline branch is defence in depth rather than a reachable path.
    expect($ics)->toContain('SUMMARY:Synodi Pastoral\\nKeuskupan')
        ->and($ics)->not->toContain("\nKeuskupan");
});

it('keeps the description on one line', function () {
    $event = Event::factory()->create([
        'description' => "<p>Baris satu.</p>\n<p>Baris dua.</p>",
    ]);

    $ics = (new IcsCalendarBuilder)->calendar($event);

    // Two paragraphs become one line, with the word break preserved —
    // HtmlSanitizer::blockElementsToSpaces exists for exactly that, and a
    // calendar entry reading "satu. dua." is the visible result of getting it
    // wrong.
    foreach (explode("\r\n", $ics) as $line) {
        if (str_contains($line, 'DESCRIPTION')) {
            expect($line)->toStartWith('DESCRIPTION:')->toEndWith('.');
        }
    }

    expect($ics)->toContain('DESCRIPTION:Baris satu. Baris dua.');
});

it('escapes the backslash before it escapes anything else', function () {
    $event = Event::factory()->create(['title' => 'Ruang \\\\ nama']);

    $ics = (new IcsCalendarBuilder)->calendar($event);

    // Order-dependent. If the semicolon pass ran first, the backslash it added
    // would then be doubled by the backslash pass, and the reader's calendar
    // would show a stray backslash in every title.
    expect($ics)->toContain('SUMMARY:Ruang \\\\\\\\ nama');
});

it('carries the location', function () {
    $event = Event::factory()->create([
        'location_name' => 'Gereja St. Theresia, Putussibau',
    ]);

    expect((new IcsCalendarBuilder)->calendar($event))
        ->toContain('LOCATION:Gereja St. Theresia\\, Putussibau');
});

it('omits the location when there is none', function () {
    $event = Event::factory()->create(['location_name' => null, 'location_id' => null]);

    expect((new IcsCalendarBuilder)->calendar($event))
        ->not->toContain('LOCATION:');
});

it('prefers the master location name over the free text', function () {
    $location = Location::factory()->create(['name' => 'Gereja Induk Santo Yusuf']);
    $event = Event::factory()->create([
        'location_id' => $location->getKey(),
        'location_name' => 'nama lama',
    ]);

    expect((new IcsCalendarBuilder)->calendar($event))
        ->toContain('LOCATION:Gereja Induk Santo Yusuf');
});

it('carries the description as one line', function () {
    $event = Event::factory()->create(['description' => '<p>Rapat para pastor.</p>']);

    $ics = (new IcsCalendarBuilder)->calendar($event);

    expect($ics)->toContain('DESCRIPTION:Rapat para pastor.');
});

it('omits the description when the activity has none', function () {
    expect((new IcsCalendarBuilder)->calendar(Event::factory()->create()))
        ->not->toContain('DESCRIPTION:');
});

it('points at the public page', function () {
    $event = Event::factory()->create(['slug' => 'synodi-pastoral']);

    expect((new IcsCalendarBuilder)->calendar($event))
        ->toContain('URL:'.route('agenda.show', ['event' => 'synodi-pastoral']));
});

/*
|--------------------------------------------------------------------------
| Status — EVT-08
|--------------------------------------------------------------------------
*/

it('confirms a scheduled activity', function () {
    $event = Event::factory()->create(['status' => EventStatus::Scheduled]);

    expect((new IcsCalendarBuilder)->calendar($event))
        ->toContain('STATUS:CONFIRMED');
});

it('marks a cancelled activity as cancelled and still includes it', function () {
    // EVT-08: the activity stays in the file. Somebody who imported it before
    // it was called off needs their calendar to say so, and deleting the entry
    // silently is the one outcome they cannot act on.
    $event = Event::factory()->cancelled()->create();

    $ics = (new IcsCalendarBuilder)->calendar($event);

    expect($ics)->toContain('STATUS:CANCELLED')
        ->and($ics)->toContain('BEGIN:VEVENT');
});

it('confirms a postponed activity', function () {
    // Postponed means the date moves later, not that the entry is void, and
    // the calendar has no way to say "maybe". CONFIRMED plus the date in the
    // summary is the honest encoding.
    $event = Event::factory()->postponed()->create();

    expect((new IcsCalendarBuilder)->calendar($event))
        ->toContain('STATUS:CONFIRMED');
});

/*
|--------------------------------------------------------------------------
| The endpoint
|--------------------------------------------------------------------------
*/

it('serves the file', function () {
    $event = Event::factory()->create();

    $this->get(route('agenda.ics', $event))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->assertHeader('Content-Disposition', sprintf('attachment; filename="%s.ics"', $event->slug));
});

it('serves it as a download rather than inline', function () {
    // A browser rendering text/calendar as text shows the parish a wall of
    // VTIMEZONE instead of a file.
    expect(route('agenda.ics', Event::factory()->create()))->toContain('/ics');
});

it('answers 404 for an unpublished activity', function () {
    $event = Event::factory()->unpublished()->create();

    $this->get(route('agenda.ics', $event))->assertNotFound();
});

it('answers 404 for a soft-deleted activity', function () {
    $event = Event::factory()->create()->delete();

    $this->get(route('agenda.ics', $event))->assertNotFound();
});

it('answers 404 for a slug nobody wrote', function () {
    $this->get('/agenda/tidak-ada/ics')->assertNotFound();
});
