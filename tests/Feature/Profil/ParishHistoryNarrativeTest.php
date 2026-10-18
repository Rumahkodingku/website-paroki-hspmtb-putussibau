<?php

use App\Models\ParishHistoryNarrative;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Narasi sejarah
|--------------------------------------------------------------------------
|
| The narrative used to live on parish_profiles.history_description and was
| edited on the removed Informasi Profil page. It now has its own singleton
| and is edited on the Sejarah page (D-69).
*/

it('renders the narrative on the history list', function () {
    ParishHistoryNarrative::factory()->create(['narrative' => '<p>Narasi.</p>']);

    $this->actingAs(superAdmin())
        ->get(route('parish-histories.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/profil/sejarah/index')
            ->where('narrative', '<p>Narasi.</p>'));
});

it('saves and sanitizes the narrative', function () {
    $this->actingAs(superAdmin())
        ->put(route('parish-histories.narrative.update'), [
            'narrative' => '<p>Baik</p><script>alert(1)</script>',
        ])
        ->assertRedirect(route('parish-histories.index'));

    $narrative = ParishHistoryNarrative::current()->narrative;

    expect(ParishHistoryNarrative::query()->count())->toBe(1)
        ->and($narrative)->toContain('Baik')
        ->and($narrative)->not->toContain('<script>');
});

it('edits the one row rather than adding a second', function () {
    ParishHistoryNarrative::factory()->create(['narrative' => '<p>Lama</p>']);

    $this->actingAs(superAdmin())
        ->put(route('parish-histories.narrative.update'), ['narrative' => '<p>Baru</p>']);

    expect(ParishHistoryNarrative::query()->count())->toBe(1)
        ->and(ParishHistoryNarrative::current()->narrative)->toBe('<p>Baru</p>');
});

it('accepts an empty narrative', function () {
    ParishHistoryNarrative::factory()->create(['narrative' => '<p>Ada</p>']);

    $this->actingAs(superAdmin())
        ->put(route('parish-histories.narrative.update'), ['narrative' => ''])
        ->assertRedirect(route('parish-histories.index'));

    expect(ParishHistoryNarrative::current()->narrative)->toBeNull();
});

it('sends a guest to the login page', function () {
    $this->put(route('parish-histories.narrative.update'), ['narrative' => '<p>x</p>'])
        ->assertRedirect(route('login'));
});

it('refuses a signed-in user without the permission', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->put(route('parish-histories.narrative.update'), ['narrative' => '<p>x</p>'])
        ->assertForbidden();
});
