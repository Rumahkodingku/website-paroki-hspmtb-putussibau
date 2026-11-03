<?php

use App\Models\AuditEvent;
use App\Models\Media;
use App\Models\PageSeo;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('forbids a user without the seo permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('seo.edit'))->assertForbidden();
});

it('shows the static page SEO editor to an authorized admin', function () {
    $this->actingAs(superAdmin())
        ->get(route('seo.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/seo')
            ->has('pages', 18)
            ->where('pages.0.key', 'home')
        );
});

it('does not keep the old nested SEO URL', function () {
    $this->actingAs(superAdmin())
        ->get('/admin/pengaturan/seo')
        ->assertNotFound();
});

it('saves a static page override and records the change', function () {
    $admin = superAdmin();

    $this->actingAs($admin)
        ->put(route('seo.update'), [
            'pages' => [
                'news' => [
                    'meta_title' => 'Judul SEO',
                    'meta_description' => 'Deskripsi SEO.',
                    'og_media_id' => null,
                ],
            ],
        ])
        ->assertRedirect(route('seo.edit'));

    $this->assertDatabaseHas('page_seo', [
        'page_key' => 'news',
        'meta_title' => 'Judul SEO',
        'meta_description' => 'Deskripsi SEO.',
    ]);

    expect(AuditEvent::query()
        ->where('module', 'page_seo')
        ->where('action', 'updated')
        ->count())->toBe(1);
});

it('rejects a page key outside the allowlist', function () {
    $this->actingAs(superAdmin())
        ->put(route('seo.update'), [
            'pages' => [
                'halaman-palsu' => [
                    'meta_title' => 'Judul',
                    'meta_description' => null,
                    'og_media_id' => null,
                ],
            ],
        ])
        ->assertSessionHasErrors('pages');
});

it('rejects an overlong title', function () {
    $this->actingAs(superAdmin())
        ->put(route('seo.update'), [
            'pages' => [
                'news' => [
                    'meta_title' => str_repeat('a', 256),
                    'meta_description' => null,
                    'og_media_id' => null,
                ],
            ],
        ])
        ->assertSessionHasErrors('pages.news.meta_title');
});

it('rejects a media id that is not ready', function () {
    $media = Media::factory()->create();

    $this->actingAs(superAdmin())
        ->put(route('seo.update'), [
            'pages' => [
                'news' => [
                    'meta_title' => 'Judul',
                    'meta_description' => null,
                    'og_media_id' => (string) $media->getKey(),
                ],
            ],
        ])
        ->assertSessionHasErrors('pages.news.og_media_id');
});

it('removes the row when every field is cleared', function () {
    PageSeo::query()->create([
        'page_key' => 'news',
        'meta_title' => 'Judul',
        'meta_description' => 'Deskripsi',
    ]);

    $this->actingAs(superAdmin())
        ->put(route('seo.update'), [
            'pages' => [
                'news' => [
                    'meta_title' => '',
                    'meta_description' => '',
                    'og_media_id' => null,
                ],
            ],
        ])
        ->assertRedirect(route('seo.edit'));

    $this->assertDatabaseMissing('page_seo', ['page_key' => 'news']);
});
