import { usePage } from '@inertiajs/react';
import { JsonLd } from '@/components/json-ld';
import type { BreadcrumbItem, SeoDefaults } from '@/types';
import { absoluteJsonLdUrl } from '@/lib/json-ld';

/**
 * The machine-readable half of the breadcrumb the page already shows.
 *
 * The visible component owns the list; this one only turns the same list into
 * ListItem entries, so the two can never disagree about the trail.
 */
export function BreadcrumbJsonLd({ items }: { items: BreadcrumbItem[] }) {
    const { seo } = usePage<{ seo: SeoDefaults }>().props;

    const elements = items.map((item, index) => {
        const href = item.href;
        const raw =
            typeof href === 'string'
                ? href
                : href && typeof href === 'object' && 'url' in href
                  ? String(href.url)
                  : seo.page.canonical;

        return {
            '@type': 'ListItem',
            position: index + 1,
            name: item.title,
            item: absoluteJsonLdUrl(raw, seo.appUrl) ?? seo.page.canonical,
        };
    });

    const data = {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: elements,
    };

    return <JsonLd data={data} headKey="breadcrumb-json-ld" />;
}
