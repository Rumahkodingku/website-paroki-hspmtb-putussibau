<?php

use App\Models\ParishHistoryItem;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Linimasa sejarah
|--------------------------------------------------------------------------
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validHistoryItem(array $overrides = []): array
{
    return array_merge([
        'title' => 'Peletakan batu pertama',
        'year' => 1956,
        'date' => null,
        'description' => '<p>Peristiwa bersejarah.</p>',
        'media_id' => null,
        'image_alt' => '',
        'sort_order' => 0,
        'is_published' => true,
    ], $overrides);
}

it('renders the timeline list', function () {
    ParishHistoryItem::factory()->count(2)->create();

    $this->actingAs(superAdmin())
        ->get(route('parish-histories.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/profil/sejarah/index')
            ->has('items.data', 2)
            ->where('items.data.0.id', fn ($id) => is_int($id)));
});

it('creates a timeline item', function () {
    $this->actingAs(superAdmin())
        ->post(route('parish-histories.store'), validHistoryItem())
        ->assertRedirect(route('parish-histories.index'));

    $this->assertDatabaseHas('parish_history_items', ['title' => 'Peletakan batu pertama']);
});

it('requires a title', function () {
    $this->actingAs(superAdmin())
        ->post(route('parish-histories.store'), validHistoryItem(['title' => '']))
        ->assertSessionHasErrors('title');

    expect(ParishHistoryItem::count())->toBe(0);
});

it('rejects an implausible year', function () {
    $this->actingAs(superAdmin())
        ->post(route('parish-histories.store'), validHistoryItem(['year' => 50]))
        ->assertSessionHasErrors('year');
});

it('sanitizes the description', function () {
    $this->actingAs(superAdmin())
        ->post(route('parish-histories.store'), validHistoryItem([
            'description' => '<p>Ok</p><script>alert(1)</script>',
        ]));

    expect(ParishHistoryItem::first()->description)->not->toContain('<script>');
});

it('updates and deletes an item', function () {
    $item = ParishHistoryItem::factory()->create(['title' => 'Lama']);

    $this->actingAs(superAdmin())
        ->patch(route('parish-histories.update', $item), validHistoryItem(['title' => 'Baru']))
        ->assertRedirect(route('parish-histories.index'));

    expect($item->fresh()->title)->toBe('Baru');

    $this->actingAs(superAdmin())
        ->delete(route('parish-histories.destroy', $item))
        ->assertRedirect(route('parish-histories.index'));

    expect(ParishHistoryItem::count())->toBe(0);
});

it('keeps an unpublished item out of the published scope', function () {
    ParishHistoryItem::factory()->unpublished()->create();

    expect(ParishHistoryItem::published()->count())->toBe(0);
});
