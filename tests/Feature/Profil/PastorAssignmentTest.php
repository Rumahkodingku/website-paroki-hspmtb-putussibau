<?php

use App\Enums\PastorPosition;
use App\Models\Pastor;
use App\Models\PastorAssignment;

/*
|--------------------------------------------------------------------------
| Riwayat penugasan pastor
|--------------------------------------------------------------------------
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validAssignment(array $overrides = []): array
{
    return array_merge([
        'position' => PastorPosition::PastorRekan->value,
        'start_year' => 2010,
        'end_year' => 2012,
        'is_current' => false,
        'description' => '',
    ], $overrides);
}

it('adds an assignment to a pastor', function () {
    $pastor = Pastor::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('pastor-assignments.store', $pastor), validAssignment())
        ->assertRedirect(route('pastor-assignments.create', $pastor));

    expect($pastor->assignments()->count())->toBe(1);
});

it('rejects an unknown position', function () {
    $pastor = Pastor::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('pastor-assignments.store', $pastor), validAssignment(['position' => 'paus']))
        ->assertSessionHasErrors('position');
});

it('rejects an end year before the start year', function () {
    $pastor = Pastor::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('pastor-assignments.store', $pastor), validAssignment([
            'start_year' => 2020,
            'end_year' => 2010,
        ]))
        ->assertSessionHasErrors('end_year');
});

it('keeps exactly one current assignment when one is set current', function () {
    $pastor = Pastor::factory()->create();
    $first = PastorAssignment::factory()->for($pastor)->current()->create();
    $second = PastorAssignment::factory()->for($pastor)->create();

    $this->actingAs(superAdmin())
        ->post(route('pastor-assignments.store', $pastor), validAssignment([
            'is_current' => true,
        ]));

    expect(PastorAssignment::where('is_current', true)->count())->toBe(1)
        ->and($first->fresh()->is_current)->toBeFalse()
        ->and($second->fresh()->is_current)->toBeFalse();
});

it('activates an assignment and clears the previous one', function () {
    $pastor = Pastor::factory()->create();
    $current = PastorAssignment::factory()->for($pastor)->current()->create();
    $next = PastorAssignment::factory()->for($pastor)->create();

    $this->actingAs(superAdmin())
        ->post(route('pastor-assignments.activate', [
            'pastor' => $pastor->getKey(),
            'assignment' => $next->getKey(),
        ]))
        ->assertRedirect(route('pastor-assignments.create', $pastor));

    expect($next->fresh()->is_current)->toBeTrue()
        ->and($current->fresh()->is_current)->toBeFalse()
        ->and(PastorAssignment::where('is_current', true)->count())->toBe(1);
});

it('refuses an assignment that belongs to another pastor', function () {
    $pastor = Pastor::factory()->create();
    $other = Pastor::factory()->create();
    $assignment = PastorAssignment::factory()->for($other)->create();

    $this->actingAs(superAdmin())
        ->delete(route('pastor-assignments.destroy', [
            'pastor' => $pastor->getKey(),
            'assignment' => $assignment->getKey(),
        ]))
        ->assertNotFound();
});
