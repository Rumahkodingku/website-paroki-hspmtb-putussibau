import { Head, Link, usePage } from '@inertiajs/react';
import { PublicSection } from '@/components/public-section';
import PublicHeroBanner from '@/components/public-hero-banner';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { ListPagination } from '@/components/list-pagination';
import type { Paginated } from '@/types';
import type { PublicCategory, PublicPost, PublicPostFilters } from '../types';
import { ArticleCard } from '@/features/public/berita/components/article-card';
import { NewsFilters } from '../components/news-filters';
import { PinnedStrip } from '../components/pinned-strip';
import { home } from '@/routes';
import { index as beritaIndex } from '@/routes/berita';

type Props = {
    pinned: PublicPost[];
    posts: Paginated<PublicPost>;
    categories: PublicCategory[];
    filters: PublicPostFilters;
};

export default function Index({ pinned, posts, categories, filters }: Props) {
    const { displayTimezone } = usePage().props;
    const isFiltered = filters.q !== '' || filters.kategori !== '';

    return (
        <>
            <Seo
                title="Berita & Artikel"
                description="Berita kegiatan, laporan acara, renungan, dan pengumuman resmi Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau."
            />

            <Head title="Berita & Artikel" />

            <PublicHeroBanner
                eyebrow="Berita & Artikel"
                title="Berita & Artikel"
                description="Berita kegiatan, laporan acara, renungan, dan pengumuman resmi paroki."
                breadcrumbs={[
                    { title: 'Beranda', href: home() },
                    { title: 'Berita & Artikel' },
                ]}
            />

            <PublicSection>
                <div className="space-y-12">
                    <PinnedStrip
                        articles={pinned}
                        displayTimezone={displayTimezone}
                    />

                    <section
                        aria-labelledby="berita-semua"
                        className="space-y-6"
                    >
                        <Text as="h2" variant="h3" id="berita-semua">
                            {isFiltered ? 'Hasil pencarian' : 'Semua berita'}
                        </Text>

                        <NewsFilters
                            filters={filters}
                            categories={categories}
                            resultCount={posts.meta.total}
                        />

                        {posts.data.length > 0 ? (
                            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {posts.data.map((article) => (
                                    <ArticleCard
                                        key={article.id}
                                        article={article}
                                        displayTimezone={displayTimezone}
                                    />
                                ))}
                            </div>
                        ) : (
                            <div className="rounded-lg border border-dashed border-border p-10 text-center">
                                <Text as="p" variant="heading-sm">
                                    {isFiltered
                                        ? 'Tidak ada berita untuk pencarian ini.'
                                        : 'Belum ada berita yang diterbitkan.'}
                                </Text>
                                <Text
                                    as="p"
                                    variant="body-sm"
                                    color="muted"
                                    className="mt-1"
                                >
                                    {isFiltered
                                        ? 'Coba kata lain atau reset filter.'
                                        : 'Berita paroki akan muncul di sini setelah diterbitkan.'}
                                </Text>

                                {isFiltered ? (
                                    <Link
                                        href={beritaIndex()}
                                        className="mt-4 inline-flex text-sm font-medium text-primary-on-surface underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                    >
                                        Reset filter
                                    </Link>
                                ) : null}
                            </div>
                        )}
                    </section>

                    {posts.meta.last_page > 1 ? (
                        <ListPagination
                            meta={posts.meta}
                            previous={posts.links.prev}
                            next={posts.links.next}
                        />
                    ) : null}
                </div>
            </PublicSection>
        </>
    );
}
