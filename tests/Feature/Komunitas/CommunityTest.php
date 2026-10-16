<?php

use App\Models\Community;
use App\Models\CommunityCategory;
use App\Models\Media;

/*
|--------------------------------------------------------------------------
| Komunitas — CRUD, gambar, pivot area, dan publikasi
|--------------------------------------------------------------------------
|
| AC-COM-01..12 and roadmap 5.2.
|
| @see docs/DECISIONS.md D-55, D-59
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function communityPayload(CommunityCategory $category, array $overrides = []): array
{
    return array_merge([
        'name' => 'OMK Contoh',
        'slug' => 'omk-contoh',
        'community_category_id' => $category->getKey(),
        'logo_media_id' => null,
        'logo_alt' => null,
        'thumbnail_media_id' => null,
        'thumbnail_alt' => null,
        'description' => 'Deskripsi singkat.',
        'vision' => null,
        'mission' => null,
        'meeting_schedule' => null,
        'meeting_location' => null,
        'eligibility' => null,
        'joining_info' => null,
        'contact_name' => null,
        'contact_phone' => null,
        'show_contact' => false,
        'facebook' => null,
        'instagram' => null,
        'tiktok' => null,
        'youtube' => null,
        'website' => null,
        'status' => 'forming',
        'is_published' => false,
    ], $overrides);
}

it('creates a community and lands on its edit page', function () {
    $category = CommunityCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('communities.store'), communityPayload($category))
        ->assertRedirect(route('communities.edit', Community::query()->sole()));

    $community = Community::query()->sole();

    expect($community->name)->toBe('OMK Contoh')
        ->and($community->status->value)->toBe('forming')
        ->and($community->published_at)->toBeNull();
});

it('rejects invalid input and persists nothing', function () {
    $category = CommunityCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('communities.store'), communityPayload($category, [
            'name' => '',
            'slug' => 'bukan slug!',
            'community_category_id' => 999999,
        ]))
        ->assertSessionHasErrors(['name', 'slug', 'community_category_id']);

    expect(Community::count())->toBe(0);
});

it('rejects a duplicate slug', function () {
    $category = CommunityCategory::factory()->create();
    Community::factory()->create(['slug' => 'omk-contoh']);

    $this->actingAs(superAdmin())
        ->post(route('communities.store'), communityPayload($category))
        ->assertSessionHasErrors('slug');
});

it('refuses a new community in an inactive category', function () {
    $category = CommunityCategory::factory()->inactive()->create();

    $this->actingAs(superAdmin())
        ->post(route('communities.store'), communityPayload($category))
        ->assertSessionHasErrors('community_category_id');
});

it('keeps a community in an inactive category it already belongs to', function () {
    $category = CommunityCategory::factory()->create();
    $community = Community::factory()->create(['community_category_id' => $category->getKey()]);

    $category->update(['is_active' => false]);

    $this->actingAs(superAdmin())
        ->patch(route('communities.update', $community), communityPayload($category, [
            'name' => 'Nama Baru',
        ]))
        ->assertSessionHasNoErrors();

    expect($community->fresh()->name)->toBe('Nama Baru');
});

/*
|--------------------------------------------------------------------------
| Gambar
|--------------------------------------------------------------------------
*/

it('accepts ready media for logo and thumbnail', function () {
    $category = CommunityCategory::factory()->create();
    $logo = Media::factory()->ready()->create();
    $thumbnail = Media::factory()->ready()->create();

    $this->actingAs(superAdmin())
        ->post(route('communities.store'), communityPayload($category, [
            'logo_media_id' => $logo->getKey(),
            'thumbnail_media_id' => $thumbnail->getKey(),
        ]))
        ->assertSessionHasNoErrors();

    expect(Community::query()->sole()->thumbnail_media_id)->toBe($thumbnail->getKey());
});

it('rejects a media row that is not ready yet', function () {
    $category = CommunityCategory::factory()->create();
    $pending = Media::factory()->create(); // Pending by default

    $this->actingAs(superAdmin())
        ->post(route('communities.store'), communityPayload($category, [
            'thumbnail_media_id' => $pending->getKey(),
        ]))
        ->assertSessionHasErrors('thumbnail_media_id');
});

/*
|--------------------------------------------------------------------------
| Publikasi (D-55 point 9)
|--------------------------------------------------------------------------
*/

it('stamps published_at once, on the first publication', function () {
    $category = CommunityCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('communities.store'), communityPayload($category, [
            'status' => 'active',
            'is_published' => true,
        ]));

    $community = Community::query()->sole();
    $firstPublishedAt = $community->published_at;

    expect($firstPublishedAt)->not->toBeNull();

    // Withdraw it, then publish again: the date does not move.
    $this->actingAs(superAdmin())
        ->patch(route('communities.update', $community), communityPayload($category, [
            'status' => 'active',
            'is_published' => false,
        ]));

    expect($community->fresh()->published_at?->toIso8601String())
        ->toBe($firstPublishedAt->toIso8601String());

    $this->actingAs(superAdmin())
        ->patch(route('communities.update', $community), communityPayload($category, [
            'status' => 'active',
            'is_published' => true,
        ]));

    expect($community->fresh()->published_at?->toIso8601String())
        ->toBe($firstPublishedAt->toIso8601String());
});

/*
|--------------------------------------------------------------------------
| Update, delete, dan halaman admin
|--------------------------------------------------------------------------
*/

it('updates a community', function () {
    $category = CommunityCategory::factory()->create();
    $community = Community::factory()->create(['community_category_id' => $category->getKey()]);

    $this->actingAs(superAdmin())
        ->patch(route('communities.update', $community), communityPayload($category, [
            'name' => 'Judul Baru',
            'slug' => $community->slug,
        ]))
        ->assertRedirect(route('communities.edit', $community));

    expect($community->fresh()->name)->toBe('Judul Baru');
});

it('soft deletes a community', function () {
    $community = Community::factory()->create();

    $this->actingAs(superAdmin())
        ->delete(route('communities.destroy', $community))
        ->assertRedirect(route('communities.index'));

    expect(Community::count())->toBe(0)
        ->and(Community::withTrashed()->count())->toBe(1);
});

it('renders the community list', function () {
    Community::factory()->published()->create();

    $this->actingAs(superAdmin())
        ->get(route('communities.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/komunitas')
            ->has('communities.data', 1));
});

it('filters the community list by publication', function () {
    Community::factory()->published()->create();
    Community::factory()->forming()->create();

    $this->actingAs(superAdmin())
        ->get(route('communities.index', ['publikasi' => 'published']))
        ->assertInertia(fn ($page) => $page->has('communities.data', 1));

    $this->actingAs(superAdmin())
        ->get(route('communities.index', ['publikasi' => 'draft']))
        ->assertInertia(fn ($page) => $page->has('communities.data', 1));
});

it('renders the edit page', function () {
    $community = Community::factory()->create();

    $this->actingAs(superAdmin())
        ->get(route('communities.edit', $community))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/komunitas/ubah')
            ->where('community.name', $community->name));
});

/*
| Social links (D-59).
|
| Plain `url`, one message per column. The tests that matter are the negative
| ones and the normalization: a community that never touches a link must store
| null, not an empty string, because the public page decides whether to draw its
| Media Sosial section from what the resource sends.
*/

it('accepts a valid link on every social column', function (string $field) {
    $category = CommunityCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('communities.store'), communityPayload($category, [
            $field => 'https://contoh.test/nama-komunitas',
        ]))
        ->assertSessionHasNoErrors();

    expect(Community::query()->sole()->{$field})
        ->toBe('https://contoh.test/nama-komunitas');
})->with(['facebook', 'instagram', 'tiktok', 'youtube', 'website']);

it('rejects a value that is not a url on every social column', function (string $field) {
    $category = CommunityCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('communities.store'), communityPayload($category, [
            $field => 'bukan-url',
        ]))
        ->assertSessionHasErrors($field);

    expect(Community::query()->count())->toBe(0);
})->with(['facebook', 'instagram', 'tiktok', 'youtube', 'website']);

it('normalizes an untouched link box to null instead of an empty string', function () {
    $category = CommunityCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('communities.store'), communityPayload($category, [
            'facebook' => '   ',
            'website' => '',
        ]))
        ->assertSessionHasNoErrors();

    $community = Community::query()->sole();

    expect($community->facebook)->toBeNull()
        ->and($community->website)->toBeNull();
});

/*
| Visi and misi as rich text (D-60).
|
| Two halves, and the second is the one that matters. The editor lets an admin
| write a bold lead-in or a list; the sanitizer is what makes storing markup safe,
| and it is invisible when it works. So the tests assert what SURVIVES the trip
| and what does not, rather than that the field accepted a string.
*/

it('stores allowed markup in the vision and mission', function () {
    $category = CommunityCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('communities.store'), communityPayload($category, [
            'vision' => '<p>Menjadi <strong>mitra</strong> bagi semua.</p>',
            'mission' => '<ul><li>Mengwiredi</li><li>Menolong</li></ul>',
        ]))
        ->assertSessionHasNoErrors();

    $community = Community::query()->sole();

    expect($community->vision)->toContain('<strong>mitra</strong>')
        ->and($community->mission)->toContain('<li>Mengwiredi</li>');
});

it('strips scripts and event handlers out of the vision', function () {
    $category = CommunityCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('communities.store'), communityPayload($category, [
            'vision' => '<p onclick="alert(1)">Visi</p><script>alert(2)</script>',
        ]))
        ->assertSessionHasNoErrors();

    $vision = (string) Community::query()->sole()->vision;

    expect($vision)->not->toContain('<script')
        ->and($vision)->not->toContain('onclick')
        ->and($vision)->toContain('Visi');
});

it('normalizes an emptied editor to null, but keeps a document that only holds an image', function () {
    $category = CommunityCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('communities.store'), communityPayload($category, [
            // What an emptied Tiptap document posts: an empty paragraph. Left
            // as-is it is truthy, and the public page draws a "Visi" heading
            // over nothing.
            'vision' => '<p></p>',
            'mission' => '',
        ]))
        ->assertSessionHasNoErrors();

    $community = Community::query()->sole();

    expect($community->vision)->toBeNull()
        ->and($community->mission)->toBeNull();

    // The other half of that decision: an image-only document is content.
    $other = CommunityCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('communities.store'), communityPayload($other, [
            'slug' => 'omk-gambar',
            'vision' => '<p><img src="https://contoh.test/visi.png" alt=""></p>',
        ]))
        ->assertSessionHasNoErrors();

    expect(Community::query()->where('slug', 'omk-gambar')->sole()->vision)
        ->toContain('<img');
});
