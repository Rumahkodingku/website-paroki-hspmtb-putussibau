<?php

use App\Models\Media;
use App\Models\MediaVariant;
use App\Models\ParishBoardMember;
use App\Models\ParishBoardPeriod;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Anggota kepengurusan
|--------------------------------------------------------------------------
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validMember(array $overrides = []): array
{
    return array_merge([
        'name' => 'Nama Pengurus',
        'position' => 'Ketua',
        'photo_media_id' => null,
        'photo_alt' => '',
        'description' => '',
        'sort_order' => 0,
        'is_published' => true,
    ], $overrides);
}

it('adds a member to a period', function () {
    $period = ParishBoardPeriod::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('board-members.store', $period), validMember())
        ->assertRedirect(route('board-periods.index', ['periode' => $period->getKey()]));

    expect($period->members()->count())->toBe(1);
});

it('requires name and position', function () {
    $period = ParishBoardPeriod::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('board-members.store', $period), validMember(['name' => '', 'position' => '']))
        ->assertSessionHasErrors(['name', 'position']);

    expect(ParishBoardMember::count())->toBe(0);
});

it('updates and deletes a member', function () {
    $period = ParishBoardPeriod::factory()->create();
    $member = ParishBoardMember::factory()->create(['parish_board_period_id' => $period->getKey()]);

    $this->actingAs(superAdmin())
        ->patch(route('board-members.update', ['period' => $period->getKey(), 'member' => $member->getKey()]), validMember(['name' => 'Baru']))
        ->assertRedirect();

    expect($member->fresh()->name)->toBe('Baru');

    $this->actingAs(superAdmin())
        ->delete(route('board-members.destroy', ['period' => $period->getKey(), 'member' => $member->getKey()]))
        ->assertRedirect();

    expect(ParishBoardMember::count())->toBe(0);
});

it('refuses a member that belongs to another period', function () {
    $period = ParishBoardPeriod::factory()->create();
    $other = ParishBoardPeriod::factory()->create();
    $member = ParishBoardMember::factory()->create(['parish_board_period_id' => $other->getKey()]);

    $this->actingAs(superAdmin())
        ->delete(route('board-members.destroy', ['period' => $period->getKey(), 'member' => $member->getKey()]))
        ->assertNotFound();
});

it('orders members by sort_order', function () {
    $period = ParishBoardPeriod::factory()->create();
    ParishBoardMember::factory()->create(['parish_board_period_id' => $period->getKey(), 'sort_order' => 2]);
    ParishBoardMember::factory()->create(['parish_board_period_id' => $period->getKey(), 'sort_order' => 1]);

    expect($period->members()->pluck('sort_order')->all())->toBe([1, 2]);
});

it('includes the member photo URL on the structure page', function () {
    // Guards the members.photoMedia.variants eager load: the admin list renders
    // member.photo_url, which the resource only sends when the media is loaded.
    $media = Media::factory()->ready()->create();
    MediaVariant::factory()->create([
        'media_id' => $media->getKey(),
        'name' => 'medium',
        'disk' => (string) config('media.variant_disk'),
        'path' => 'media/variants/'.$media->getKey().'/medium.webp',
    ]);

    $period = ParishBoardPeriod::factory()->create();
    ParishBoardMember::factory()->create([
        'parish_board_period_id' => $period->getKey(),
        'photo_media_id' => $media->getKey(),
    ]);

    $this->actingAs(superAdmin())
        ->get(route('board-periods.index', ['periode' => $period->getKey()]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('selected.members.0.photo_url', fn ($url) => is_string($url) && str_contains($url, 'storage')));
});
