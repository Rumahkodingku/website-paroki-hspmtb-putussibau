import { Head, usePage } from '@inertiajs/react';
import type { SeoDefaults } from '@/types';

/*
 * Page-level document head: title, description, canonical and Open Graph.
 *
 * The keys on the tags below are not decoration. Inertia turns head-key into a
 * data-inertia attribute and manages the document's head by that attribute, so
 * a key is what decides whether a tag replaces another one or sits next to it.
 * Every string here has a counterpart in resources/views/app.blade.php, which
 * renders the same tags server-side; a page that renders <Seo> therefore
 * replaces the defaults instead of stacking a second copy underneath them, and a
 * page that renders nothing keeps them. Adding a key here means adding the same
 * one there.
 *
 * Why both layers exist at all: PRD NFR-SEO requires Open Graph tags in the
 * first HTML response, and SSR is not enabled (D-26). React renders after
 * hydration, so on its own it would be invisible to a crawler or a WhatsApp
 * unfurl, which only ever reads the response body.
 *
 * Nothing here reads window. An Open Graph image must be an absolute URL or link
 * previews show nothing at all, and seo_default_og_image is a relative path
 * because its upload widget was never built (D-24), so the absolute base comes
 * from the shared prop instead. Reading window.location here would make the
 * component unsafe to server-render, which is the change SSR will need.
 *
 * A tag whose value is missing is omitted rather than emitted empty. An empty
 * meta description is worse than none: crawlers and unfurlers both treat it as
 * a description and render a blank preview.
 *
 * The title is emitted without the site name on purpose. createInertiaApp in
 * app.tsx already appends it, and doing it in both places produced
 * "Berita - Paroki - Paroki" before this component existed.
 *
 * Phase 01 section 27 asks for the foundation only. No page renders this yet:
 * the routes that would, are the public modules from Fase 2.
 *
 * @see docs/DECISIONS.md D-26
 */

type SeoProps = {
    /** Page-specific. The site name is appended by app.tsx. */
    title: string;
    description?: string | null;
    /** Absolute URL. Falls back to the current page's own URL. */
    canonical?: string | null;
    ogImage?: string | null;
    /** article for a news post or event detail, website for everything else. */
    ogType?: 'website' | 'article';
};

export function Seo({
    title,
    description,
    canonical,
    ogImage,
    ogType = 'website',
}: SeoProps) {
    const page = usePage<{ seo: SeoDefaults }>();
    const { seo } = page.props;

    const resolvedDescription = description ?? seo.description;
    const resolvedImage = ogImage ?? seo.ogImage;
    const pageUrl = canonical ?? page.url;

    return (
        <Head>
            <title>{title}</title>

            {resolvedDescription ? (
                <>
                    <meta
                        head-key="description"
                        name="description"
                        content={resolvedDescription}
                    />
                    <meta
                        head-key="og-description"
                        property="og:description"
                        content={resolvedDescription}
                    />
                    <meta
                        head-key="twitter-description"
                        name="twitter:description"
                        content={resolvedDescription}
                    />
                </>
            ) : null}

            {seo.siteName ? (
                <meta
                    head-key="og-site-name"
                    property="og:site_name"
                    content={seo.siteName}
                />
            ) : null}

            <meta head-key="og-title" property="og:title" content={title} />
            <meta head-key="og-type" property="og:type" content={ogType} />
            <meta
                head-key="twitter-card"
                name="twitter:card"
                content="summary_large_image"
            />

            {resolvedImage ? (
                <>
                    <meta
                        head-key="og-image"
                        property="og:image"
                        content={absolute(resolvedImage, seo.appUrl)}
                    />
                    <meta
                        head-key="twitter-image"
                        name="twitter:image"
                        content={absolute(resolvedImage, seo.appUrl)}
                    />
                </>
            ) : null}

            {/*
                The page's own URL, absolutised the same way. og:url is what a
                unfurler records as the identity of the link, and a relative
                value there is routinely discarded.
            */}
            <meta
                head-key="og-url"
                property="og:url"
                content={absolute(pageUrl, seo.appUrl)}
            />
            <link head-key="canonical" rel="canonical" href={pageUrl} />
        </Head>
    );
}

/**
 * Make a URL absolute against the application origin.
 *
 * Protocol-relative URLs and anything with a scheme are already resolved and
 * are returned untouched, so an admin pasting a full https:// address into
 * seo_default_og_image does not end up with https://localhost/https://....
 */
function absolute(value: string, appUrl: string): string {
    if (/^https?:\/\//i.test(value) || value.startsWith('//')) {
        return value;
    }

    return `${appUrl}${value.startsWith('/') ? value : `/${value}`}`;
}

export default Seo;
