<?php

use App\Models\Media;
use App\Models\ParishProfile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Informasi Profil — konten profil paroki
|--------------------------------------------------------------------------
|
| The editorial profile content moved back from site settings in D-78. These
| tests cover the page, the validation, and the singleton write.
*/

it('sends a guest to the login page', function () {
    $this->get(route('parish-profile.edit'))->assertRedirect(route('login'));
});

it('refuses a signed-in user without the role', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->get(route('parish-profile.edit'))
        ->assertForbidden();
});

it('lets a super admin open the page', function () {
    ParishProfile::factory()->create(['established_year' => 1956]);

    $this->actingAs(superAdmin())
        ->get(route('parish-profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/profil/informasi/index')
            ->where('profile.established_year', 1956));
});

it('stores the profile and sanitizes the description', function () {
    $this->actingAs(superAdmin())
        ->put(route('parish-profile.update'), [
            'description' => '<p>Profil</p><script>alert(1)</script>',
            'established_year' => 1956,
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ])
        ->assertSessionHasNoErrors();

    $profile = ParishProfile::current();

    expect($profile->description)->toBe('<p>Profil</p>')
        ->and($profile->established_year)->toBe(1956)
        ->and($profile->video_url)->toBe('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
});

it('accepts only a YouTube video link', function () {
    $this->actingAs(superAdmin())
        ->put(route('parish-profile.update'), [
            'video_url' => 'https://example.test/video',
        ])
        ->assertSessionHasErrors('video_url');

    $this->actingAs(superAdmin())
        ->put(route('parish-profile.update'), [
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ])
        ->assertSessionHasNoErrors();
});

it('rejects a cover that has not finished processing', function () {
    $media = Media::factory()->create(); // pending

    $this->actingAs(superAdmin())
        ->put(route('parish-profile.update'), [
            'cover_media_id' => $media->getKey(),
            'cover_alt' => 'Sampul paroki',
        ])
        ->assertSessionHasErrors('cover_media_id');
});

it('requires alt text when a cover is selected', function () {
    $media = Media::factory()->ready()->create();

    $this->actingAs(superAdmin())
        ->put(route('parish-profile.update'), [
            'cover_media_id' => $media->getKey(),
        ])
        ->assertSessionHasErrors('cover_alt');
});

it('rejects an impossible founding year', function () {
    $this->actingAs(superAdmin())
        ->put(route('parish-profile.update'), [
            'established_year' => 999,
        ])
        ->assertSessionHasErrors('established_year');

    $this->actingAs(superAdmin())
        ->put(route('parish-profile.update'), [
            'established_year' => (int) date('Y') + 1,
        ])
        ->assertSessionHasErrors('established_year');
});
