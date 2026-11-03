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

        {{--
            Inline style for the HTML background, matching the DESIGN-v2 canvas
            tokens so there is no flash before app.css loads. Light canvas is
            #FFFFFF, dark canvas is #111318 (D-72).
        --}}
        <style>
            html {
                background-color: #ffffff;
            }

            html.dark {
                background-color: #111318;
            }
        </style>

        @php
            /*
             * The installer renders before site_settings exists. Both blocks
             * below read that table, so they are skipped for installation
             * pages: the committed placeholder icons and the app name are
             * enough, and a query here would turn the first wizard request into
             * a 500. See docs/DECISIONS.md D-94.
             */
            $isInstaller = str_starts_with((string) ($page['component'] ?? ''), 'installation/');
            $faviconUrl = null;
        @endphp

        @unless($isInstaller)
            @php
                /*
                 * The parish's favicon, if one has been uploaded. It is a media row
                 * (a media id), so the URL is resolved from it here rather than
                 * stored as a path. When unset, the committed placeholder icons are
                 * used instead.
                 */
                $faviconSetting = app(\App\Services\SiteSettingsService::class)->get('favicon');
                $faviconUrl = is_string($faviconSetting) && $faviconSetting !== ''
                    ? \App\Models\Media::query()->with('variants')->find((int) $faviconSetting)?->url('thumb')
                    : null;
            @endphp
        @endunless

        @if($faviconUrl)
            <link rel="icon" href="{{ $faviconUrl }}">
            <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
            <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        @endif

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
            $seoSiteName = null;
            $seoTitle = null;
            $seoDescription = null;
            $seoOgImage = null;

            if (! $isInstaller) {
                $seo = app(\App\Services\SiteSettingsService::class);
                $seoRead = static function (string $key) use ($seo): ?string {
                    $value = $seo->get($key);

                    return is_string($value) && trim($value) !== '' ? $value : null;
                };
                $seoSiteName = $seoRead('parish_name');
                $seoTitleName = $seoRead('parish_short_name') ?? $seoSiteName;
                $isHome = request()->routeIs('home');

                /*
                 * The homepage has three defaults the other pages do not need.
                 *
                 * The short name is a useful document title when the parish has not
                 * configured seo_default_title. The short description is the only
                 * honest meta description available before a page-specific one is
                 * written, and the approved hero photograph makes a valid Open
                 * Graph image even when seo_default_og_image is unset. Every value
                 * remains optional: an empty setting still omits the tag rather
                 * than emitting an empty one.
                 */
                $seoTitle = $seoRead('seo_default_title');

                if ($isHome) {
                    $seoTitle ??= $seoRead('parish_short_name') ?? $seoRead('parish_name');
                }

                $seoDescription = $seoRead('seo_default_description');

                if ($isHome) {
                    $seoDescription ??= $seoRead('short_description');
                }

                $seoOgImage = $seoRead('seo_default_og_image');

                if ($isHome) {
                    $seoOgImage ??= (string) config('homepage.fallback_hero_og_image');
                }

                $seoOgImage = $seoOgImage && ! str_starts_with($seoOgImage, 'http')
                    ? rtrim(config('app.url'), '/').'/'.ltrim($seoOgImage, '/')
                    : $seoOgImage;
            }
        @endphp

        @php
            /*
             * The page-specific values the resolver produced for this request.
             * Blade and React render the same array; a null means "not set", and
             * the global defaults below are used only when there is no page value.
             */
            $seoPage = $page['props']['seo']['page'] ?? null;
            $seoPageDescription = $seoPage['description'] ?? $seoDescription;
            $seoPageTitle = $seoPage['title'] ?? $seoTitle;
            $seoPageOgImage = $seoPage['ogImage'] ?? $seoOgImage;
            $seoPageOgType = $seoPage['ogType'] ?? 'website';
            $seoPageRobots = $seoPage['robots'] ?? 'index, follow';

            // The exception handler renders the error page outside the normal
            // route resolution, so its noindex answer is decided here.
            if (str_starts_with((string) ($page['component'] ?? ''), 'public/error')) {
                $seoPageRobots = 'noindex, follow';
            }

            $seoPageCanonical = $seoPage['canonical'] ?? url()->current();
        @endphp

        <x-inertia::head>
            <title>{{ \App\Support\SeoTitle::format($seoPageTitle ?? config('app.name', 'Laravel'), $seoTitleName ?? $seoSiteName) }}</title>

            {{--
                Counterpart of the two tags Seo adds in Phase 03. They have to
                live here as well or they exist only after hydration, which is the
                exact failure the rest of this block exists to prevent. A client
                tag whose key has no server counterpart does not replace anything:
                Inertia matches on data-inertia, so the two end up side by side
                and the crawler sees the indexable default.
            --}}
            <meta data-inertia="robots" name="robots" content="{{ $seoPageRobots }}">
            <meta data-inertia="og-locale" property="og:locale" content="id_ID">

            @if($seoSiteName)
                <meta data-inertia="og-site-name" property="og:site_name" content="{{ $seoSiteName }}">
            @endif

            @if($seoPageTitle)
                <meta data-inertia="og-title" property="og:title" content="{{ $seoPageTitle }}">
            @endif

            @if($seoPageDescription)
                <meta data-inertia="description" name="description" content="{{ $seoPageDescription }}">
                <meta data-inertia="og-description" property="og:description" content="{{ $seoPageDescription }}">
                <meta data-inertia="twitter-description" name="twitter:description" content="{{ $seoPageDescription }}">
            @endif

            <meta data-inertia="og-type" property="og:type" content="{{ $seoPageOgType }}">

            <meta data-inertia="og-url" property="og:url" content="{{ $seoPageCanonical }}">
            <link data-inertia="canonical" rel="canonical" href="{{ $seoPageCanonical }}">

            @if($seoPageOgImage)
                <meta data-inertia="og-image" property="og:image" content="{{ $seoPageOgImage }}">
                <meta data-inertia="twitter-image" name="twitter:image" content="{{ $seoPageOgImage }}">
            @endif

            <meta data-inertia="twitter-card" name="twitter:card" content="summary_large_image">
        </x-inertia::head>

        @unless($isInstaller)
            @php
                /*
                 * Site-wide identity schema. It is deliberately outside the
                 * Inertia head slot: it never changes between pages, so it
                 * should survive a soft navigation instead of being replaced.
                 *
                 * JSON_HEX_TAG keeps a value such as </script> from closing the
                 * element, which is the same reason the React serializer escapes
                 * its angle brackets.
                 */
                $seoAbsolute = static function (?string $value): ?string {
                    if ($value === null || trim($value) === '') {
                        return null;
                    }

                    if (preg_match('#^https?://#i', $value) === 1 || str_starts_with($value, '//')) {
                        return $value;
                    }

                    $base = rtrim((string) config('app.url'), '/');

                    if ($base === '') {
                        $base = rtrim(url('/'), '/');
                    }

                    return $base.'/'.ltrim($value, '/');
                };

                $seoOrganizationUrl = rtrim((string) config('app.url'), '/');

                if ($seoOrganizationUrl === '') {
                    $seoOrganizationUrl = rtrim(url('/'), '/');
                }

                $seoLogoId = $seo->get('logo');
                $seoLogoUrl = is_string($seoLogoId) && $seoLogoId !== ''
                    ? \App\Models\Media::query()->with('variants')->find((int) $seoLogoId)?->url('medium')
                    : null;

                $seoSameAs = array_values(array_filter([
                    $seoRead('social_facebook'),
                    $seoRead('social_instagram'),
                    $seoRead('social_youtube'),
                    $seoRead('social_whatsapp_channel'),
                ], static fn (?string $url): bool => $url !== null && preg_match('#^https?://#i', $url) === 1));

                $seoOrganization = array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    'name' => $seoSiteName,
                    'url' => $seoOrganizationUrl,
                    'logo' => $seoAbsolute($seoLogoUrl),
                    'sameAs' => $seoSameAs === [] ? null : $seoSameAs,
                    'address' => $seoRead('contact_address'),
                    'telephone' => $seoRead('contact_phone'),
                    'email' => $seoRead('contact_email'),
                ], static fn (mixed $value): bool => $value !== null && $value !== '');

                $seoWebsite = array_filter([
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => $seoSiteName ?? config('app.name'),
                    'url' => $seoOrganizationUrl,
                    'inLanguage' => 'id-ID',
                ], static fn (mixed $value): bool => $value !== null && $value !== '');

                $seoOrganizationJson = json_encode($seoOrganization, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
                $seoWebsiteJson = json_encode($seoWebsite, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
            @endphp

            @if(is_string($seoOrganizationJson))
                <script type="application/ld+json">{!! $seoOrganizationJson !!}</script>
            @endif

            @if(is_string($seoWebsiteJson))
                <script type="application/ld+json">{!! $seoWebsiteJson !!}</script>
            @endif
        @endunless
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
