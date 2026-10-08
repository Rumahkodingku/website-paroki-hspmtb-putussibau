<?php

use App\Support\PageChunk;

/*
|--------------------------------------------------------------------------
| Page chunk resolution
|--------------------------------------------------------------------------
|
| An Inertia page name comes from a page's path inside its feature, and both
| sides of that mapping need to agree. `index.tsx` stands for the feature
| itself, which is what lets a single-page feature drop the redundant path
| segment its URL carries:
|
|   app.tsx          import.meta.glob('./features/<audience>/<feature>/pages' + '/**' + '.tsx')
|   PageChunk.php    glob('js/features/<audience>/<feature>/pages/' . $name . '.tsx')
|
| The middle segment is spelled out rather than written as one glob: an
| asterisk followed by a slash inside a PHP docblock closes the comment.
|
| The Blade side has no alternative to looking the file up, because the
| component name arrives as a string from a controller. If these two ever
| disagree, the failure is a blank page: Vite preloads one chunk and the
| resolver throws on the other.
|
| @see docs/DECISIONS.md D-30
|
*/

test('a page nested inside a feature resolves to its file', function () {
    expect(PageChunk::source('admin/akun/profile'))
        ->toBe('resources/js/features/admin/akun/pages/profile.tsx');
});

test('an index page resolves to the feature itself', function () {
    // 'admin/pengaturan' has no admin/pengaturan.tsx anywhere: the file is
    // pages/index.tsx, and this is the only route to it.
    expect(PageChunk::source('admin/pengaturan'))
        ->toBe('resources/js/features/admin/pengaturan/pages/index.tsx');
});

test('a public index page resolves under the public audience', function () {
    expect(PageChunk::source('public/beranda'))
        ->toBe('resources/js/features/public/beranda/pages/index.tsx');
});

test('a public feature page keeps its file name in the page name', function () {
    expect(PageChunk::source('public/auth/login'))
        ->toBe('resources/js/features/public/auth/pages/login.tsx');
});

test('an index fallback is not offered to a name with three segments', function () {
    // The index rule only applies to a two-segment name. Without this guard,
    // any unresolved three-segment name would quietly resolve to some feature's
    // index page — the wrong page, with no error.
    expect(PageChunk::source('admin/akun/tidak-ada'))->toBeNull()
        ->and(PageChunk::source('tidak-ada'))->toBeNull();
});

test('an unknown page name resolves to null rather than a wrong path', function () {
    // The interesting failure is the wrong answer. A method that returned an
    // invented path would make @vite() throw a manifest error that names the
    // asset, not the page, and the real cause would be a renamed component.
    expect(PageChunk::source('admin/halaman-yang-tidak-ada'))->toBeNull()
        ->and(PageChunk::source('../../etc/passwd'))->toBeNull();
});

test('every page in the application is discoverable by name', function () {
    $pages = PageChunk::all();

    // Twelve pages today. Asserting the list rather than the count is what
    // catches a page that moved and lost its name.
    expect(array_keys($pages))->toContain(
        'public/beranda',
        'public/error',
        'public/auth/login',
        'public/auth/forgot-password',
        'public/auth/reset-password',
        'public/auth/confirm-password',
        'public/auth/verify-email',
        'admin/dashboard',
        'admin/pengaturan',
        'admin/akun/profile',
        'admin/akun/security',
        'admin/akun/appearance',
    );
});

test('the file a page resolves to is the file it is indexed under', function () {
    foreach (PageChunk::all() as $name => $path) {
        // The name is derived by stripping the pages/ directory and the
        // extension. If that derivation ever disagrees with source(), the
        // frontend and Blade would be reading two different pages.
        expect(PageChunk::source($name))->toBe($path);
    }
});
