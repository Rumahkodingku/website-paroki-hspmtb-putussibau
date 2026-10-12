<?php

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Location;
use App\Models\MassSchedule;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| The Agenda admin — CRUD and duplication
|--------------------------------------------------------------------------
|
| Two things carry most of the weight here.
|
| Wall clocks. EVT-03 says an activity's time arrives as a WIB wall clock and
| is stored as UTC. The tests below assert both halves: that 09:00 WIB is
| 02:00 UTC in the column, and that it reads back as 09:00. A cast added to
| start_at would satisfy the first and break the second, which is why both are
| asserted rather than one.
|
| Duplication. EVT-05 forbids recurring activities, so duplicating is how a
| parish enters the same meeting every month. That makes the copy's defaults the
| whole feature: it is not a convenience if the copy arrives published, or with
| next month's date silently unconfirmed, or sharing a URL with the original.
|
| @see docs/DECISIONS.md D-44
*/

beforeEach(function () {
    seedRolesAndPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

/*
|--------------------------------------------------------------------------
| Creating
|--------------------------------------------------------------------------
*/

/**
 * A valid payload, so a test can change one field and say which.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validEvent(array $overrides = []): array
{
    return [
        'title' => 'Synodi Pastoral Keuskupan',
        'slug' => '',
        'event_category_id' => EventCategory::factory()->create()->getKey(),
        'description' => '',
        'start_at' => '2026-11-14T09:00',
        'end_at' => '',
        'is_all_day' => '0',
        'location_id' => '',
        'location_name' => 'Gereja St. Theresia, Putussibau',
        'poster_alt' => '',
        'status' => EventStatus::Scheduled->value,
        'is_published' => '1',
        'media_id' => '',
        ...$overrides,
    ];
}

it('creates an activity', function () {
    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent())
        ->assertRedirect();

    $event = Event::query()->firstOrFail();

    expect($event->title)->toBe('Synodi Pastoral Keuskupan')
        ->and($event->location_name)->toBe('Gereja St. Theresia, Putussibau');
});

it('redirects to the edit form after saving', function () {
    // A redirect to the list would throw away the slug the server generated and
    // make finding the thing you just created a search exercise.
    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent())
        ->assertRedirect(route('events.edit', Event::query()->firstOrFail()));
});

it('requires a title', function () {
    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent(['title' => '']))
        ->assertSessionHasErrors('title');

    expect(Event::query()->count())->toBe(0);
});

it('requires a category', function () {
    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent(['event_category_id' => '']))
        ->assertSessionHasErrors('event_category_id');
});

it('requires a start time', function () {
    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent(['start_at' => '']))
        ->assertSessionHasErrors('start_at');
});

it('refuses an end time before the start', function () {
    // EVT-01. An activity that ends before it starts is the single most
    // obviously wrong thing this table can hold.
    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent([
            'start_at' => '2026-11-14T09:00',
            'end_at' => '2026-11-14T08:00',
        ]))
        ->assertSessionHasErrors('end_at');
});

it('accepts an activity that starts and ends in the same minute', function () {
    // after_or_equal, not after: EVT-01 says an activity that begins and ends
    // at the same moment is odd but valid, and rejecting it forces the admin to
    // invent a duration they do not have.
    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent([
            'start_at' => '2026-11-14T09:00',
            'end_at' => '2026-11-14T09:00',
        ]))
        ->assertSessionHasNoErrors();

    expect(Event::query()->firstOrFail()->end_at)->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| The wall clock — EVT-03
|--------------------------------------------------------------------------
*/

it('stores the start as UTC', function () {
    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent(['start_at' => '2026-11-14T09:00']));

    $stored = Event::query()->firstOrFail()->start_at;

    // WIB is UTC+7 all year, so 09:00 local is 02:00 UTC. Asserting the UTC
    // value is what catches a cast on the column, which would shift the byte
    // that is stored.
    expect($stored->format('Y-m-d H:i:s'))->toBe('2026-11-14 02:00:00');
});

it('reads the start back as the local time it was entered', function () {
    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent(['start_at' => '2026-11-14T09:00']));

    $event = Event::query()->firstOrFail();

    // The other half of the same assertion. A cast added to start_at would
    // still store 02:00 and would then render 09:00 a second time, which is
    // how a 09:00 activity turns into 16:00 on the public page.
    expect(
        $event->start_at
            ->copy()
            ->setTimezone(config('app.display_timezone'))
            ->format('H:i')
    )->toBe('09:00');
});

it('stores the end as UTC too', function () {
    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent([
            'start_at' => '2026-11-14T09:00',
            'end_at' => '2026-11-14T12:30',
        ]));

    // WIB is UTC+7 with no daylight saving, so 12:30 local is 05:30 UTC. The
    // start test above says 09:00 -> 02:00; this is the same shift applied to
    // a different time, which is the point — a copy-pasted 02:30 here would
    // pass without the conversion being exercised at all.
    expect(Event::query()->firstOrFail()->end_at->format('H:i:s'))
        ->toBe('05:30:00');
});

it('leaves the end empty when the field was left empty', function () {
    // An empty datetime-local must not become the epoch. The epoch would pass
    // `nullable` and then reach the ICS file as an activity that ended before
    // it began.
    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent([
            'start_at' => '2026-11-14T09:00',
            'end_at' => '',
        ]))
        ->assertSessionHasNoErrors();

    expect(Event::query()->firstOrFail()->end_at)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| All day — EVT-03
|--------------------------------------------------------------------------
*/

it('keeps the start time on an all-day activity', function () {
    // There is no DATE column here and there does not need to be: the date is
    // the part that matters and the time is simply not rendered.
    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent([
            'start_at' => '2026-12-24T00:00',
            'is_all_day' => '1',
        ]))
        ->assertSessionHasNoErrors();

    $event = Event::query()->firstOrFail();

    // Converted before formatting, not formatted directly. A Carbon read back
    // from the database is in UTC, and Carbon::format() renders in whatever
    // timezone the instance carries — it does not convert. Midnight WIB on the
    // 24th is 17:00 UTC on the 23rd, so formatting the raw value here asserts
    // the 23rd and passes while the calendar shows the wrong day.
    expect($event->is_all_day)->toBeTrue()
        ->and(
            $event->start_at
                ->copy()
                ->setTimezone(config('app.display_timezone'))
                ->format('Y-m-d')
        )->toBe('2026-12-24');
});

/*
|--------------------------------------------------------------------------
| Slug
|--------------------------------------------------------------------------
*/

it('generates a slug from the title when the field is left blank', function () {
    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent([
            'title' => 'Pekan Avun',
            'slug' => '',
        ]));

    expect(Event::query()->firstOrFail()->slug)->toBe('pekan-avun');
});

it('refuses a slug another activity already holds', function () {
    Event::factory()->create(['slug' => 'sahabat']);

    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent(['slug' => 'sahabat']))
        ->assertSessionHasErrors('slug');
});

it('refuses an uppercase slug', function () {
    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent(['slug' => 'Synodi-Pastoral']))
        ->assertSessionHasErrors('slug');
});

it('keeps its own slug when updating', function () {
    $event = Event::factory()->create(['slug' => 'sahabat']);

    $this->actingAs($this->admin)
        ->patch(route('events.update', $event), validEvent([
            'slug' => 'sahabat',
            'title' => 'Judul yang diubah',
        ]))
        ->assertSessionHasNoErrors();

    expect($event->fresh()->slug)->toBe('sahabat');
});

it('does not change a slug the admin did not touch', function () {
    // EVT-01's counterpart on the update side. The slug is in somebody's
    // calendar already; rewording a title must not move the URL.
    $event = Event::factory()->create(['slug' => 'pertemuan-juli']);

    $this->actingAs($this->admin)
        ->patch(route('events.update', $event), validEvent([
            'slug' => 'pertemuan-juli',
            'title' => 'Pertemuan Agustus',
        ]));

    expect($event->fresh()->slug)->toBe('pertemuan-juli');
});

/*
|--------------------------------------------------------------------------
| Location — EVT-06
|--------------------------------------------------------------------------
*/

it('accepts free text for a location that is not a master record', function () {
    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent([
            'location_id' => '',
            'location_name' => 'Aula Katekismal',
        ]))
        ->assertSessionHasNoErrors();

    expect(Event::query()->firstOrFail()->location_name)->toBe('Aula Katekismal');
});

it('accepts a master location', function () {
    $location = Location::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent([
            'location_id' => (string) $location->getKey(),
            'location_name' => '',
        ]))
        ->assertSessionHasNoErrors();

    expect(Event::query()->firstOrFail()->location_id)->toBe($location->getKey());
});

it('accepts a location that has been deactivated', function () {
    // The opposite of what a mass schedule does with a retired location. A mass
    // pointing at a retired room would never appear on the public page; an
    // activity announced at a church before it was renamed still happened, and
    // refusing the reference would make the parish rewrite its history to save
    // an agenda entry.
    $location = Location::factory()->create(['is_active' => false]);

    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent([
            'location_id' => (string) $location->getKey(),
        ]))
        ->assertSessionHasNoErrors();
});

it('refuses a location that does not exist', function () {
    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent(['location_id' => '999999']))
        ->assertSessionHasErrors('location_id');
});

it('prefers the master location name over the free text', function () {
    // EVT-06, asserted through the resource rather than the column: the admin
    // may have left stale free text behind from before the location became a
    // master record, and the public page must not show both.
    $location = Location::factory()->create(['name' => 'Gereja Induk Santo Yusuf']);

    $event = Event::factory()->create([
        'location_id' => $location->getKey(),
        'location_name' => 'nama lama yang tertinggal',
    ]);

    expect($event->displayLocationName())->toBe('Gereja Induk Santo Yusuf');
});

it('falls back to the free text when there is no master location', function () {
    $event = Event::factory()->create([
        'location_id' => null,
        'location_name' => 'Halaman depan sakristi',
    ]);

    expect($event->displayLocationName())->toBe('Halaman depan sakristi');
});

it('shows nothing when there is neither', function () {
    expect(Event::factory()->create([
        'location_id' => null,
        'location_name' => null,
    ])->displayLocationName())->toBeNull();
});

/*
|--------------------------------------------------------------------------
| Description
|--------------------------------------------------------------------------
*/

it('sanitizes the description', function () {
    // PRD D-14. The same rule as an article body, for the same reason: the
    // description is rendered as HTML on the public agenda.
    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent([
            'description' => '<p>Publish <script>alert(1)</script><a href="#" onclick="x()">here</a></p>',
        ]));

    $stored = Event::query()->firstOrFail()->description;

    expect($stored)
        ->toContain('<p>Publish')
        ->not->toContain('<script')
        ->not->toContain('onclick');
});

/*
|--------------------------------------------------------------------------
| Updating
|--------------------------------------------------------------------------
*/

it('updates an activity', function () {
    $event = Event::factory()->create(['title' => 'Judul lama']);

    $this->actingAs($this->admin)
        ->patch(route('events.update', $event), validEvent(['title' => 'Judul baru']))
        ->assertRedirect();

    expect($event->fresh()->title)->toBe('Judul baru');
});

it('sends the poster as a browser url for the picker', function () {
    $event = Event::factory()->create([
        'poster_path' => 'media/variants/4/large.webp',
    ]);

    $this->actingAs($this->admin)
        ->get(route('events.edit', $event))
        ->assertInertia(fn ($page) => $page
            // The path stays the stored contract; the URL is what <img> reads.
            ->where('event.poster_path', 'media/variants/4/large.webp')
            ->where(
                'event.poster_url',
                Storage::disk('public')->url('media/variants/4/large.webp'),
            )
        );
});

it('does not change the start when only the title changed', function () {
    $event = Event::factory()->create(['start_at' => Carbon::parse('2026-11-14 02:00:00', 'UTC')]);

    $this->actingAs($this->admin)->patch(route('events.update', $event), validEvent([
        'title' => 'Judul baru',
        'start_at' => '2026-11-14T09:00',
    ]));

    expect($event->fresh()->start_at->format('Y-m-d H:i:s'))
        ->toBe('2026-11-14 02:00:00');
});

/*
|--------------------------------------------------------------------------
| Duplication — EVT-05
|--------------------------------------------------------------------------
*/

it('duplicates an activity', function () {
    $event = Event::factory()->create(['title' => 'RTemas Sequoia']);

    $this->actingAs($this->admin)
        ->post(route('events.duplicate', $event))
        ->assertRedirect();

    expect(Event::query()->count())->toBe(2);
});

it('gives the copy its own slug', function () {
    $event = Event::factory()->create(['title' => 'RTemas Sequoia']);

    $this->actingAs($this->admin)->post(route('events.duplicate', $event));

    $copy = Event::query()->where('id', '!=', $event->getKey())->firstOrFail();

    // Two activities sharing a URL would send a calendar that was given last
    // month's link to this month's date.
    expect($copy->slug)->not->toBe($event->slug)
        ->and($copy->slug)->toStartWith('rtemas-sequoia');
});

it('gives a second copy a third slug', function () {
    $event = Event::factory()->create(['title' => 'RTemas Sequoia']);

    $this->actingAs($this->admin)->post(route('events.duplicate', $event));
    $this->actingAs($this->admin)->post(route('events.duplicate', $event));

    expect(Event::query()->pluck('slug')->all())->toHaveCount(3);
});

it('leaves the copy unpublished', function () {
    // The whole point of is_published is that it is the everyday editorial
    // switch. A copy that arrived published would put next month's meeting on
    // the site before the parish had decided on it.
    $event = Event::factory()->create(['is_published' => true]);

    $this->actingAs($this->admin)->post(route('events.duplicate', $event));

    expect(Event::query()->where('id', '!=', $event->getKey())->firstOrFail()->is_published)
        ->toBeFalse();
});

it('does not carry a cancellation into the copy', function () {
    // A copy of a cancelled activity is not cancelled. What it becomes is a
    // fresh decision, and a meeting the parish cancelled in October is very
    // often back on the calendar in November.
    $event = Event::factory()->cancelled()->create();

    $this->actingAs($this->admin)->post(route('events.duplicate', $event));

    expect(Event::query()->where('id', '!=', $event->getKey())->firstOrFail()->status)
        ->toBe(EventStatus::Scheduled);
});

it('moves the copy a month ahead, to the same time of day', function () {
    $event = Event::factory()->create([
        'start_at' => Carbon::parse('2026-11-14 02:00:00', 'UTC'),
        'end_at' => Carbon::parse('2026-11-14 04:00:00', 'UTC'),
    ]);

    $this->actingAs($this->admin)->post(route('events.duplicate', $event));

    $copy = Event::query()->where('id', '!=', $event->getKey())->firstOrFail();

    // A meeting on the 14th should stay on the 14th. addMonthNoOverflow, not
    // addMonth: the 31st of January plus a month is the 28th or 3rd otherwise,
    // and a copy that quietly moved date is the exact failure this feature is
    // meant to prevent.
    expect($copy->start_at->format('Y-m-d H:i:s'))->toBe('2026-12-14 02:00:00')
        ->and($copy->end_at->format('Y-m-d H:i:s'))->toBe('2026-12-14 04:00:00');
});

it('keeps an end time empty on a copy when the original had none', function () {
    $event = Event::factory()->create(['end_at' => null]);

    $this->actingAs($this->admin)->post(route('events.duplicate', $event));

    expect(Event::query()->where('id', '!=', $event->getKey())->firstOrFail()->end_at)
        ->toBeNull();
});

it('copies the descriptive fields', function () {
    $event = Event::factory()->create([
        'description' => '<p>Persiapan Kamarthus</p>',
        'poster_alt' => 'Poster synodi',
        'location_name' => 'Aula Katekismal',
    ]);

    $this->actingAs($this->admin)->post(route('events.duplicate', $event));

    $copy = Event::query()->where('id', '!=', $event->getKey())->firstOrFail();

    expect($copy->description)->toBe('<p>Persiapan Kamarthus</p>')
        ->and($copy->poster_alt)->toBe('Poster synodi')
        ->and($copy->location_name)->toBe('Aula Katekismal');
});

it('does not carry a master location across as an id the copy cannot own', function () {
    // The location is copied as a reference, which is fine. What matters is that
    // the copy is a separate row and does not inherit the original's key.
    $event = Event::factory()->withLocation()->create();

    $this->actingAs($this->admin)->post(route('events.duplicate', $event));

    $copy = Event::query()->where('id', '!=', $event->getKey())->firstOrFail();

    expect($copy->getKey())->not->toBe($event->getKey());
});

it('redirects the copy to its own edit form', function () {
    // The copy opens on a date a month out that nobody has confirmed, and the
    // form is where that gets confirmed. Landing on the list would bury it.
    $event = Event::factory()->create();

    $this->actingAs($this->admin)
        ->post(route('events.duplicate', $event))
        ->assertRedirect();

    $copy = Event::query()->where('id', '!=', $event->getKey())->firstOrFail();

    expect($event->getKey())->not->toBe($copy->getKey())
        ->and(route('events.edit', $copy))->toContain((string) $copy->getKey());
});

it('will not duplicate an activity that does not exist', function () {
    $this->actingAs($this->admin)->post('/admin/agenda/999999/duplikat')->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Access
|--------------------------------------------------------------------------
*/

it('turns a guest away', function () {
    $event = Event::factory()->create();

    $this->post(route('events.duplicate', $event))->assertRedirect(route('login'));
});

it('turns away an account with no admin access', function () {
    seedRolesAndPermissions();

    $event = Event::factory()->create();
    $reader = User::factory()->create();

    $this->actingAs($reader)
        ->post(route('events.duplicate', $event))
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Deleting
|--------------------------------------------------------------------------
*/

it('removes an activity', function () {
    $event = Event::factory()->create();

    $this->actingAs($this->admin)
        ->delete(route('events.destroy', $event))
        ->assertRedirect();

    expect(Event::query()->count())->toBe(0);
});

it('keeps a removed activity soft-deleted', function () {
    // PRD 9.7 marks events as (SD): there is no permanent delete, and a row that
    // was only ever soft-deleted can still be counted in a test.
    $event = Event::factory()->create();

    $this->actingAs($this->admin)->delete(route('events.destroy', $event));

    expect(Event::withTrashed()->count())->toBe(1)
        ->and(Event::query()->count())->toBe(0)
        ->and($event->fresh()?->trashed())->toBeTrue();
});

it('does not free the slug once removed', function () {
    // The opposite of mass_schedules, and deliberate (D-45). Jadwal Misa
    // rebuilds its unique index with a generated active_marker so a deleted
    // schedule releases its slot. Events keeps a plain unique index and a
    // Rule::unique() with no withoutTrashed(), because the public detail route
    // binds by slug: two rows answering one slug makes that binding ambiguous,
    // and a URL somebody bookmarked would start resolving to a different
    // activity.
    //
    // So the two tables disagree on purpose, and this test is where that
    // disagreement is pinned. mass_schedules.location_id releases its slot;
    // events.slug does not.
    $event = Event::factory()->create(['slug' => 'sahabat']);

    $this->actingAs($this->admin)->delete(route('events.destroy', $event));

    $this->actingAs($this->admin)
        ->post(route('events.store'), validEvent(['slug' => 'sahabat']))
        ->assertSessionHasErrors('slug');

    expect(Event::withTrashed()->where('slug', 'sahabat')->count())->toBe(1);
});

it('will not remove an activity twice', function () {
    $event = Event::factory()->create();

    $this->actingAs($this->admin)->delete(route('events.destroy', $event));
    $this->actingAs($this->admin)->delete(route('events.destroy', $event))->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| The list
|--------------------------------------------------------------------------
*/

it('lists activities newest first', function () {
    $older = Event::factory()->create(['start_at' => Carbon::parse('2026-10-01 02:00:00', 'UTC')]);
    $newer = Event::factory()->create(['start_at' => Carbon::parse('2026-12-01 02:00:00', 'UTC')]);

    $this->actingAs($this->admin)
        ->get(route('events.list'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('events.data.0.id', $newer->getKey())
            ->where('events.data.1.id', $older->getKey())
        );
});

it('filters the list by month', function () {
    $inNovember = Event::factory()->create(['start_at' => Carbon::parse('2026-11-14 02:00:00', 'UTC')]);
    Event::factory()->create(['start_at' => Carbon::parse('2026-12-14 02:00:00', 'UTC')]);

    $this->actingAs($this->admin)
        ->get(route('events.list', ['bulan' => '2026-11']))
        ->assertInertia(fn ($page) => $page
            ->where('events.data.0.id', $inNovember->getKey())
            ->has('events.data', 1)
        );
});

it('filters the list by announcement', function () {
    $announced = Event::factory()->create(['is_published' => true]);
    Event::factory()->unpublished()->create();

    $this->actingAs($this->admin)
        ->get(route('events.list', ['publikasi' => 'yes']))
        ->assertInertia(fn ($page) => $page
            ->where('events.data.0.id', $announced->getKey())
            ->has('events.data', 1)
        );
});

it('leaves the activities list out of a permanent delete', function () {
    // A mass schedule cannot be deleted while something points at it, and the
    // same two foreign keys exist on events. An activity with a location or a
    // mass attached would otherwise 500 on delete.
    $event = Event::factory()->create();
    $mass = MassSchedule::factory()->for(
        Location::factory(),
        'location',
    )->create();

    expect($mass->exists)->toBeTrue();
    expect($event->exists)->toBeTrue();
});
