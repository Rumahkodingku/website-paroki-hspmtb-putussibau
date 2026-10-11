<?php

use App\Models\Location;
use App\Models\SpecialMass;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Special masses — /admin/misa-khusus
|--------------------------------------------------------------------------
|
| MASS-02 is the rule worth a file of its own: a mass that has already happened
| is *kept*, and simply stops being selected. The admin list shows it (an admin
| asking what happened in December needs an answer); the public tab does not (a
| visitor must not arrive at a mass that ended).
|
| The datetime handling is the other thing here. start_at is UTC and the form
| posts a WIB wall clock with no offset, so the conversion happens in exactly one
| place — the form request's prepareForValidation — and these tests pin that the
| right instant came out the other side (D-01).
*/

beforeEach(function () {
    $this->location = Location::factory()->create();
});

/*
|--------------------------------------------------------------------------
| Access
|--------------------------------------------------------------------------
*/

it('sends a guest from the list', function () {
    $this->get(route('special-masses.index'))->assertRedirect(route('login'));
});

it('refuses a signed-in user without the role', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->get(route('special-masses.index'))
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| Creating
|--------------------------------------------------------------------------
*/

/**
 * A wall clock in WIB, as an <input type="datetime-local"> sends it.
 */
function wib(string $value): string
{
    return Carbon::parse($value, config('app.display_timezone'))->utc()->format('Y-m-d H:i:s');
}

it('creates a special mass, storing the posted local time as UTC', function () {
    $this->actingAs(superAdmin())
        ->post(route('special-masses.store'), [
            'location_id' => $this->location->getKey(),
            'title' => 'Misa Natal',
            'description' => 'Misa pertama natal di Paroki.',
            'start_at' => '2026-12-25T09:00',
            'end_at' => '2026-12-25T11:00',
            'notes' => null,
            'is_active' => true,
        ])
        ->assertRedirect(route('special-masses.index'))
        ->assertSessionHasNoErrors();

    $stored = SpecialMass::query()->firstOrFail();

    // 09.00 WIB is 02.00 UTC. If this ever reads 09.00 the conversion is gone
    // and every mass will be seven hours late for a visitor in the parish.
    expect($stored->start_at->format('Y-m-d H:i:s'))->toBe('2026-12-25 02:00:00')
        ->and($stored->start_at->setTimezone(config('app.display_timezone'))->format('H:i'))
        ->toBe('09:00');
});

it('accepts a mass with no end time', function () {
    $this->actingAs(superAdmin())
        ->post(route('special-masses.store'), [
            'location_id' => $this->location->getKey(),
            'title' => 'Misa Natal',
            'start_at' => '2026-12-25T09:00',
            'end_at' => '',
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();

    expect(SpecialMass::query()->firstOrFail()->end_at)->toBeNull();
});

it('rejects an end time before the start', function () {
    $this->actingAs(superAdmin())
        ->post(route('special-masses.store'), [
            'location_id' => $this->location->getKey(),
            'title' => 'Misa',
            'start_at' => '2026-12-25T11:00',
            'end_at' => '2026-12-25T09:00',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('end_at');
});

it('accepts an end time equal to the start', function () {
    // An admin who does not know the duration should be able to leave it alone
    // rather than invent an end.
    $this->actingAs(superAdmin())
        ->post(route('special-masses.store'), [
            'location_id' => $this->location->getKey(),
            'title' => 'Misa',
            'start_at' => '2026-12-25T09:00',
            'end_at' => '2026-12-25T09:00',
            'is_active' => true,
        ])
        ->assertSessionHasNoErrors();
});

it('rejects an inactive location', function () {
    $inactive = Location::factory()->inactive()->create();

    $this->actingAs(superAdmin())
        ->post(route('special-masses.store'), [
            'location_id' => $inactive->getKey(),
            'title' => 'Misa',
            'start_at' => '2026-12-25T09:00',
            'is_active' => true,
        ])
        ->assertSessionHasErrors('location_id');
});

it('rejects a missing title or start time', function () {
    $this->actingAs(superAdmin())
        ->post(route('special-masses.store'), [
            'location_id' => $this->location->getKey(),
            'is_active' => true,
        ])
        ->assertSessionHasErrors(['title', 'start_at']);

    expect(SpecialMass::query()->count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| MASS-02 — a past mass is kept but not published
|--------------------------------------------------------------------------
*/

it('keeps a past mass but hides it from the public tab (AC-MASS-6)', function () {
    SpecialMass::factory()->for($this->location)->past()->create();

    // Still there — the record is the parish's history.
    expect(SpecialMass::query()->count())->toBe(1);

    $this->get(route('jadwal-misa.index'))
        ->assertInertia(fn (Assert $page) => $page->has('specialMasses', 0));
});

it('shows a future mass on the public tab, soonest first', function () {
    SpecialMass::factory()->for($this->location)
        ->startingAt(Carbon::now()->addDays(3))->create(['title' => 'Yang ketiga']);
    SpecialMass::factory()->for($this->location)
        ->startingAt(Carbon::now()->addDay())->create(['title' => 'Yang pertama']);

    $this->get(route('jadwal-misa.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('specialMasses', 2)
            ->where('specialMasses.0.title', 'Yang pertama')
            ->where('specialMasses.1.title', 'Yang ketiga')
        );
});

it('sends the public special mass time as ISO 8601 with an offset', function () {
    SpecialMass::factory()->for($this->location)
        ->startingAt(Carbon::parse('2026-12-25T09:00', config('app.display_timezone')))
        ->create();

    $this->get(route('jadwal-misa.index'))
        ->assertInertia(fn (Assert $page) => $page
            // The offset is what makes the instant unambiguous — a bare local
            // string means two different moments depending on the reader. The
            // stored value is UTC (D-01), so that is the offset it carries; the
            // rendering to 09.00 WIB happens in the browser.
            ->where('specialMasses.0.start_at', '2026-12-25T02:00:00+00:00')
        );
});

it('hides an inactive mass from the public tab but keeps it in the admin list', function () {
    SpecialMass::factory()->for($this->location)->inactive()->create();

    $this->get(route('jadwal-misa.index'))
        ->assertInertia(fn (Assert $page) => $page->has('specialMasses', 0));

    $this->actingAs(superAdmin())
        ->get(route('special-masses.index'))
        ->assertInertia(fn (Assert $page) => $page->has('specialMasses.data', 1));
});

/*
|--------------------------------------------------------------------------
| Listing and editing
|--------------------------------------------------------------------------
*/

it('lists every mass, past ones included', function () {
    SpecialMass::factory()->for($this->location)->past()->create();
    SpecialMass::factory()->for($this->location)->upcoming()->create();

    $this->actingAs(superAdmin())
        ->get(route('special-masses.index'))
        ->assertInertia(fn (Assert $page) => $page->has('specialMasses.data', 2));
});

it('filters the admin list by time', function () {
    SpecialMass::factory()->for($this->location)->past()->create();
    SpecialMass::factory()->for($this->location)->upcoming()->create();

    $this->actingAs(superAdmin())
        ->get(route('special-masses.index', ['status' => 'past']))
        ->assertInertia(fn (Assert $page) => $page->has('specialMasses.data', 1));
});

it('renders the edit form with the mass in WIB', function () {
    $mass = SpecialMass::factory()->for($this->location)->create([
        'start_at' => Carbon::parse('2026-12-25T09:00', config('app.display_timezone'))->utc(),
    ]);

    $this->actingAs(superAdmin())
        ->get(route('special-masses.edit', $mass))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/jadwal-misa/misa-khusus/ubah')
            ->where('specialMass.title', $mass->title)
            // 02:00 UTC rendered back to the parish's clock.
            ->where('specialMass.start_at', '2026-12-25T02:00:00+00:00')
        );
});

it('updates a special mass', function () {
    $mass = SpecialMass::factory()->for($this->location)->create();

    $this->actingAs(superAdmin())
        ->patch(route('special-masses.update', $mass), [
            'location_id' => $this->location->getKey(),
            'title' => 'Judul baru',
            'start_at' => '2026-12-25T09:00',
            'is_active' => true,
        ])
        ->assertRedirect(route('special-masses.index'))
        ->assertSessionHasNoErrors();

    expect($mass->fresh()->title)->toBe('Judul baru');
});
