import { Head, usePage } from '@inertiajs/react';
import type { SeoDefaults } from '@/types';

type SeoProps = {
    /** Page-specific. The site name is appended by app.tsx. */
    title: string;
    description?: string | null;
    /** Absolute URL. Falls back to the current page's own URL. */
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
    ogType = 'website',
    noIndex = false,
}: SeoProps) {
    const page = usePage<{ seo: SeoDefaults }>();
    const { seo } = page.props;

    const resolvedDescription = description ?? seo.description;
    const resolvedImage = ogImage ?? seo.ogImage;
    const pageUrl = canonical ?? page.url;

    return (
        <Head>
            <title>{title}</title>
            <meta
                head-key="robots"
                name="robots"
                content={noIndex ? 'noindex, follow' : 'index, follow'}
            />

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
            <meta
                head-key="og-url"
                property="og:url"
                content={absolute(pageUrl, seo.appUrl)}
            />
            <link head-key="canonical" rel="canonical" href={pageUrl} />
        </Head>
    );
}

function absolute(value: string, appUrl: string): string {
    if (/^https?:\/\//i.test(value) || value.startsWith('//')) {
        return value;
    }

    return `${appUrl}${value.startsWith('/') ? value : `/${value}`}`;
}

export default Seo;
