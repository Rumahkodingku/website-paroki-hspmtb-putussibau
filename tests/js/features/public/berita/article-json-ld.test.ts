import { describe, expect, it } from 'vitest';
import { articleJsonLdData } from '@/features/public/berita/components/article-json-ld';
import type { PublicPostDetail } from '@/features/public/berita/types';
import type { SeoDefaults } from '@/types';

const post: PublicPostDetail = {
    id: 1,
    title: 'Misa Syukur',
    slug: 'misa-syukur',
    author_name: 'Tim Redaksi',
    content: '<p>Isi berita.</p>',
    image: '/storage/berita.webp',
    image_alt: 'Foto misa',
    scripture_reference: null,
    category: null,
    published_at: '2026-11-01T01:00:00+00:00',
    view_count: 4,
    seo: {
        title: 'Misa Syukur',
        description: 'Deskripsi misa syukur.',
    },
};

const seo: SeoDefaults = {
    appUrl: 'https://paroki.test',
    siteName: 'HSPMTB Putussibau',
    parishName: 'Paroki HSPMTB Putussibau',
    title: null,
    description: null,
    ogImage: null,
    page: {
        title: 'Misa Syukur',
        description: 'Deskripsi misa syukur.',
        canonical: 'https://paroki.test/berita/misa-syukur',
        ogImage: null,
        ogType: 'article',
        robots: 'index, follow',
    },
};

describe('articleJsonLdData', () => {
    it('uses the canonical URL and absolute image', () => {
        const data = articleJsonLdData(post, seo);

        expect(data['@type']).toBe('NewsArticle');
        expect(data.url).toBe('https://paroki.test/berita/misa-syukur');
        expect(data.image).toBe('https://paroki.test/storage/berita.webp');
        expect(data.datePublished).toBe(post.published_at);
    });

    it('uses the parish name and author', () => {
        const data = articleJsonLdData(post, seo) as {
            author: { name: string };
            publisher: { name: string };
        };

        expect(data.author.name).toBe('Tim Redaksi');
        expect(data.publisher.name).toBe('Paroki HSPMTB Putussibau');
    });
});
