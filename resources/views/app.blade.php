<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @viteReactRefresh
        @php
            /*
             * The page chunk is passed to @vite() by component name rather than
             * by a fixed directory. It used to be
             * "resources/js/pages/{$page['component']}.tsx", which only worked
             * while every page lived in one place; pages now live under
             * features/<audience>/<feature>/pages/ and the name says nothing
             * about which feature owns it.
             *
             * Keep the lookup: Vite emits the page as a dynamic entry, and a
             * dynamic entry only becomes a preload when it is named in @vite().
             * Drop it and the first paint of every page waits an extra round
             * trip for its own JavaScript.
             */
            $pageChunk = \App\Support\PageChunk::source($page['component']);
        @endphp
        @vite(array_filter([
            'resources/css/app.css',
            'resources/js/app.tsx',
            $pageChunk,
        ]))
        @php
            /*
             * Baseline head, rendered on the server into the initial HTML.
             *
             * PRD NFR-SEO asks for Open Graph tags in the first response when
             * server-side rendering is not enabled, and SSR is not enabled here
             * (D-26). A React <Head> cannot do that: it renders after hydration,
             * so a crawler or a link unfurl that reads only the response body
             * sees nothing. These tags are what it sees.
             *
             * The data-inertia attributes are the load-bearing part. Inertia only
             * manages head elements that carry that attribute, and it matches a
             * server tag to a client tag by its value. Using the same strings as
             * head-key in resources/js/components/seo.tsx means a page that
             * renders <Seo> replaces these defaults instead of stacking a second
             * copy underneath them. A page that renders nothing keeps them.
             *
             * Every value is optional. A tag with no value is worse than no tag,
             * because both crawlers and WhatsApp read an empty description as a
             * description.
             */
            $seo = app(\App\Services\SiteSettingsService::class);
            $seoRead = static function (string $key) use ($seo): ?string {
                $value = $seo->get($key);

                return is_string($value) && trim($value) !== '' ? $value : null;
            };
            $seoSiteName = $seoRead('parish_name');
            $seoTitle = $seoRead('seo_default_title');
            $seoDescription = $seoRead('seo_default_description');
            $seoOgImage = $seoRead('seo_default_og_image');
            $seoOgImage = $seoOgImage && ! str_starts_with($seoOgImage, 'http')
                ? rtrim(config('app.url'), '/').'/'.ltrim($seoOgImage, '/')
                : $seoOgImage;
        @endphp

        <x-inertia::head>
            <title>{{ $seoTitle ?? config('app.name', 'Laravel') }}</title>

            {{--
                Counterpart of the two tags Seo adds in Phase 03. They have to
                live here as well or they exist only after hydration, which is the
                exact failure the rest of this block exists to prevent. A client
                tag whose key has no server counterpart does not replace anything:
                Inertia matches on data-inertia, so the two end up side by side
                and the crawler sees the indexable default.

                index,follow is the right server-side default because the majority
                of pages are real pages; a placeholder page that renders <Seo
                noIndex> replaces this by the same key.
            --}}
            <meta data-inertia="robots" name="robots" content="index, follow">
            <meta data-inertia="og-locale" property="og:locale" content="id_ID">

            @if($seoSiteName)
                <meta data-inertia="og-site-name" property="og:site_name" content="{{ $seoSiteName }}">
            @endif

            @if($seoTitle)
                <meta data-inertia="og-title" property="og:title" content="{{ $seoTitle }}">
            @endif

            @if($seoDescription)
                <meta data-inertia="description" name="description" content="{{ $seoDescription }}">
                <meta data-inertia="og-description" property="og:description" content="{{ $seoDescription }}">
                <meta data-inertia="twitter-description" name="twitter:description" content="{{ $seoDescription }}">
            @endif

            <meta data-inertia="og-type" property="og:type" content="website">

            {{--
                og:url and canonical are rendered here as well as in the Seo
                component, for the same reason: an unfurler reads og:url as the
                identity of the link and a relative value is routinely discarded.
                request()->url() is the path without the query string, which is
                what a canonical URL should be.
            --}}
            <meta data-inertia="og-url" property="og:url" content="{{ rtrim(config('app.url'), '/') }}{{ request()->getRequestUri() }}">
            <link data-inertia="canonical" rel="canonical" href="{{ rtrim(config('app.url'), '/') }}{{ request()->getRequestUri() }}">

            @if($seoOgImage)
                <meta data-inertia="og-image" property="og:image" content="{{ $seoOgImage }}">
                <meta data-inertia="twitter-image" name="twitter:image" content="{{ $seoOgImage }}">
            @endif

            <meta data-inertia="twitter-card" name="twitter:card" content="summary_large_image">
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
