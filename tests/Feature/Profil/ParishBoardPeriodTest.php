<?php

use App\Models\ParishBoardMember;
use App\Models\ParishBoardPeriod;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Periode kepengurusan
|--------------------------------------------------------------------------
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validPeriod(array $overrides = []): array
{
    return array_merge([
        'name' => 'Periode 2025–2028',
        'start_year' => 2025,
        'end_year' => 2028,
        'description' => '',
        'is_active' => false,
        'is_published' => true,
        'sort_order' => 0,
    ], $overrides);
}

it('renders the structure page with all periods', function () {
    ParishBoardPeriod::factory()->count(2)->create();

    $this->actingAs(superAdmin())
        ->get(route('board-periods.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/profil/struktur/index')
            ->has('periods', 2)
            ->has('selected'));
});

it('creates a period', function () {
    $this->actingAs(superAdmin())
        ->post(route('board-periods.store'), validPeriod())
        ->assertRedirect(route('board-periods.index'));

    $this->assertDatabaseHas('parish_board_periods', ['name' => 'Periode 2025–2028']);
});

it('rejects an end year before the start year', function () {
    $this->actingAs(superAdmin())
        ->post(route('board-periods.store'), validPeriod(['start_year' => 2028, 'end_year' => 2025]))
        ->assertSessionHasErrors('end_year');

    expect(ParishBoardPeriod::count())->toBe(0);
});

it('keeps exactly one active period', function () {
    $first = ParishBoardPeriod::factory()->active()->create();
    $second = ParishBoardPeriod::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('board-periods.activate', $second))
        ->assertRedirect(route('board-periods.index'));

    expect($second->fresh()->is_active)->toBeTrue()
        ->and($first->fresh()->is_active)->toBeFalse()
        ->and(ParishBoardPeriod::where('is_active', true)->count())->toBe(1);
});

it('deletes a period and cascades its members', function () {
    $period = ParishBoardPeriod::factory()->create();
    ParishBoardMember::factory()->count(3)->create(['parish_board_period_id' => $period->getKey()]);

    $this->actingAs(superAdmin())
        ->delete(route('board-periods.destroy', $period))
        ->assertRedirect(route('board-periods.index'));

    expect(ParishBoardPeriod::count())->toBe(0)
        ->and(ParishBoardMember::count())->toBe(0);
});
