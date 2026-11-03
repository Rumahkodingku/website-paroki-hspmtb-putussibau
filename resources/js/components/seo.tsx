import { Head, usePage } from '@inertiajs/react';
import type { SeoDefaults } from '@/types';

type SeoProps = {
    /** Page-specific. The site name is appended by app.tsx. */
    title?: string;
    description?: string | null;
    /** Absolute URL. Falls back to the canonical the server resolved. */
    canonical?: string | null;
    ogImage?: string | null;
    /** article for a news post or event detail, website for everything else. */
    ogType?: 'website' | 'article';
    noIndex?: boolean;
};

export function Seo({
    title,
    description,
    canonical,
    ogImage,
    ogType,
    noIndex = false,
}: SeoProps) {
    const page = usePage<{ seo: SeoDefaults }>();
    const { seo } = page.props;
    const resolved = seo.page;

    const resolvedTitle = title ?? resolved.title;
    const resolvedDescription =
        description ?? resolved.description ?? seo.description;
    const resolvedImage = absolute(
        ogImage ?? resolved.ogImage ?? seo.ogImage,
        seo.appUrl,
    );
    const resolvedCanonical = absolute(
        canonical ?? resolved.canonical,
        seo.appUrl,
    );
    const resolvedOgType = ogType ?? resolved.ogType;
    const resolvedRobots = noIndex ? 'noindex, follow' : resolved.robots;

    return (
        <Head>
            <title>{resolvedTitle}</title>
            <meta head-key="robots" name="robots" content={resolvedRobots} />

            <meta head-key="og-locale" property="og:locale" content="id_ID" />

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

            {(seo.parishName ?? seo.siteName) ? (
                <meta
                    head-key="og-site-name"
                    property="og:site_name"
                    content={seo.parishName ?? seo.siteName ?? ''}
                />
            ) : null}

            <meta
                head-key="og-title"
                property="og:title"
                content={resolvedTitle}
            />
            <meta
                head-key="og-type"
                property="og:type"
                content={resolvedOgType}
            />
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
                        content={resolvedImage}
                    />
                    <meta
                        head-key="twitter-image"
                        name="twitter:image"
                        content={resolvedImage}
                    />
                </>
            ) : null}

            <meta
                head-key="og-url"
                property="og:url"
                content={resolvedCanonical}
            />
            <link
                head-key="canonical"
                rel="canonical"
                href={resolvedCanonical}
            />
        </Head>
    );
}

function absolute(value: string | null | undefined, appUrl: string): string {
    if (value === null || value === undefined || value.trim() === '') {
        return '';
    }

    if (/^https?:\/\//i.test(value) || value.startsWith('//')) {
        return value;
    }

    return `${appUrl}${value.startsWith('/') ? value : `/${value}`}`;
}

export default Seo;
