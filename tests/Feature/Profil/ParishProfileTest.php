<?php

use App\Models\Media;
use App\Models\MediaVariant;
use App\Models\ParishProfile;
use App\Services\HtmlSanitizer;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Profil utama — singleton
|--------------------------------------------------------------------------
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function validProfile(array $overrides = []): array
{
    return array_merge([
        'name' => 'Paroki Contoh',
        'short_description' => 'Ringkasan paroki.',
        'description' => '<p>Deskripsi lengkap.</p>',
        'history_description' => '<p>Narasi sejarah.</p>',
        'address' => 'Jl. Contoh No. 1',
        'phone' => '0561-000000',
        'email' => 'paroki@example.test',
        'website' => 'https://paroki.example.test',
        'established_year' => 1956,
        'logo_media_id' => null,
        'logo_alt' => '',
        'cover_media_id' => null,
        'cover_alt' => '',
        'map_url' => 'https://maps.example.test/peta',
        'video_url' => '',
        'is_published' => true,
    ], $overrides);
}

it('renders the edit form with an empty singleton', function () {
    $this->actingAs(superAdmin())
        ->get(route('parish-profile.edit'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/profil/informasi/index')
            ->where('profile.name', null)
            ->has('labels')
            ->has('hints'));
});

it('creates the one profile row on first save', function () {
    $this->actingAs(superAdmin())
        ->put(route('parish-profile.update'), validProfile())
        ->assertRedirect(route('parish-profile.edit'));

    expect(ParishProfile::count())->toBe(1)
        ->and(ParishProfile::current()->name)->toBe('Paroki Contoh');
});

it('edits the existing row rather than adding a second', function () {
    ParishProfile::factory()->create(['name' => 'Lama']);

    $this->actingAs(superAdmin())
        ->put(route('parish-profile.update'), validProfile(['name' => 'Baru']));

    expect(ParishProfile::count())->toBe(1)
        ->and(ParishProfile::current()->name)->toBe('Baru');
});

it('sanitizes both rich text fields on the way in', function () {
    $this->actingAs(superAdmin())
        ->put(route('parish-profile.update'), validProfile([
            'description' => '<p>Baik</p><script>alert(1)</script>',
            'history_description' => '<p>Sejarah</p><iframe src="x"></iframe>',
        ]));

    $profile = ParishProfile::current();

    expect($profile->description)->not->toContain('<script>')
        ->and($profile->history_description)->not->toContain('<iframe>')
        ->and(app(HtmlSanitizer::class)->isClean($profile->description))->toBeTrue();
});

it('rejects an invalid payload', function (array $overrides, array $errors) {
    $this->actingAs(superAdmin())
        ->put(route('parish-profile.update'), validProfile($overrides))
        ->assertSessionHasErrors($errors);

    expect(ParishProfile::count())->toBe(0);
})->with([
    'no name' => [['name' => ''], ['name']],
    'bad email' => [['email' => 'bukan-surel'], ['email']],
    'bad website' => [['website' => 'bukan-url'], ['website']],
    'future year' => [['established_year' => 3000], ['established_year']],
    'bad map url' => [['map_url' => 'bukan-url'], ['map_url']],
]);

it('refuses a media id that has not finished processing', function () {
    $media = Media::factory()->create(); // pending

    $this->actingAs(superAdmin())
        ->put(route('parish-profile.update'), validProfile(['logo_media_id' => $media->getKey()]))
        ->assertSessionHasErrors('logo_media_id');
});

it('accepts a ready media id', function () {
    $media = Media::factory()->ready()->create();

    $this->actingAs(superAdmin())
        ->put(route('parish-profile.update'), validProfile(['logo_media_id' => $media->getKey()]))
        ->assertSessionHasNoErrors();

    expect(ParishProfile::current()->logo_media_id)->toBe($media->getKey());
});

it('renders the edit form with an attached image and its variant URL', function () {
    // Guards the eager load of `*.variants`: Media::url() reads the relation,
    // and with lazy loading prevented outside production a missing load is a
    // 500 rather than a blank image. The variant row must exist for url() to
    // resolve a name.
    $media = Media::factory()->ready()->create();
    MediaVariant::factory()->create([
        'media_id' => $media->getKey(),
        'name' => 'medium',
        'disk' => (string) config('media.variant_disk'),
        'path' => 'media/variants/'.$media->getKey().'/medium.webp',
    ]);

    ParishProfile::factory()->create(['logo_media_id' => $media->getKey()]);

    $this->actingAs(superAdmin())
        ->get(route('parish-profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('profile.logo_url', fn ($url) => is_string($url) && str_contains($url, 'storage')));
});
