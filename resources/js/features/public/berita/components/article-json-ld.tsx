import { usePage } from '@inertiajs/react';
import { JsonLd } from '@/components/json-ld';
import type { PublicPostDetail } from '../types';
import type { SeoDefaults } from '@/types';
import { absoluteJsonLdUrl } from '@/lib/json-ld';

/**
 * The article as structured data.
 *
 * Only fields the detail resource actually sends are used. A missing image or
 * author is left out rather than filled with a placeholder: a schema property
 * that says nothing is worse than one that is absent.
 */
export function articleJsonLdData(
    post: PublicPostDetail,
    seo: SeoDefaults,
): Record<string, unknown> {
    const canonical = seo.page.canonical;

    const data: Record<string, unknown> = {
        '@context': 'https://schema.org',
        '@type': 'NewsArticle',
        headline: post.title,
        mainEntityOfPage: {
            '@type': 'WebPage',
            '@id': canonical,
        },
        url: canonical,
        inLanguage: 'id-ID',
        author: {
            '@type': 'Person',
            name: post.author_name,
        },
        publisher: {
            '@type': 'Organization',
            name: seo.parishName ?? seo.siteName ?? seo.page.title,
            url: seo.appUrl,
        },
    };

    if (post.seo.description) {
        data.description = post.seo.description;
    }

    const image = absoluteJsonLdUrl(post.image, seo.appUrl);

    if (image !== null) {
        data.image = image;
    }

    if (post.published_at) {
        data.datePublished = post.published_at;
    }

    return data;
}

export function ArticleJsonLd({ post }: { post: PublicPostDetail }) {
    const { seo } = usePage<{ seo: SeoDefaults }>().props;

    return (
        <JsonLd data={articleJsonLdData(post, seo)} headKey="article-json-ld" />
    );
}
