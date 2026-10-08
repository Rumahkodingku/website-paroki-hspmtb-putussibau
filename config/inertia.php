<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server Side Rendering
    |--------------------------------------------------------------------------
    |
    | These options configures if and how Inertia uses Server Side Rendering
    | to pre-render each initial request made to your application's pages
    | so that server rendered HTML is delivered for the user's browser.
    |
    | See: https://inertiajs.com/server-side-rendering
    |
    */

    'ssr' => [
        'enabled' => true,
        'url' => 'http://127.0.0.1:13714',
        // 'bundle' => base_path('bootstrap/ssr/ssr.mjs'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | These options configure how Inertia discovers page components on the
    | filesystem. The paths and extensions are used to locate components
    | when rendering responses and during testing assertions.
    |
    */

    'pages' => [

        /*
         | One entry per feature that owns an Inertia page.
         |
         | Inertia's own view finder concatenates each path with the component
         | name, so this list is what makes `assertInertia()->component(...)`
         | work at all, and it is why pages cannot simply live anywhere: the
         | finder takes literal directories and does not expand wildcards.
         |
         | NOT the mechanism. AppServiceProvider replaces `inertia.view-finder`
         | with App\Support\PageFinder, because Laravel's FileViewFinder can only
         | concatenate a directory with a name, and neither the audience/feature
         | prefix nor index.tsx survives that. This list is kept accurate rather
         | than accurate-and-load-bearing: if the override is ever removed, the
         | plain finder points at these real directories and fails on a name like
         | admin/akun/profile, which is a far better failure than a stale path.
         |
         | See docs/DECISIONS.md D-30.
         */
        'paths' => [
            resource_path('js/features/admin/akun/pages'),
            resource_path('js/features/admin/dashboard/pages'),
            resource_path('js/features/admin/pengaturan/pages'),
            resource_path('js/features/public/auth/pages'),
            resource_path('js/features/public/beranda/pages'),
            resource_path('js/features/public/error/pages'),
        ],

        'extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    |
    | The values described here are used to locate Inertia components on the
    | filesystem. For instance, when using `assertInertia`, the assertion
    | attempts to locate the component as a file relative to the paths.
    |
    */

    'testing' => [

        'ensure_pages_exist' => true,

    ],

];
