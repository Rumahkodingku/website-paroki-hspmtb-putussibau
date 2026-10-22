import { Head, usePage } from '@inertiajs/react';
import { PublicSection } from '@/components/public-section';
import PublicHeroBanner from '@/components/public-hero-banner';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { ListPagination } from '@/components/list-pagination';
import type { Paginated } from '@/types';
import type { PublicCategory, PublicPost, PublicPostFilters } from '../types';
import { ArticleCard } from '@/components/article-card';
import { NewsFilters } from '../components/news-filters';
import { PinnedStrip } from '../components/pinned-strip';
import { home } from '@/routes';

type Props = {
    pinned: PublicPost[];
    posts: Paginated<PublicPost>;
    categories: PublicCategory[];
    filters: PublicPostFilters;
};

/*
 * The list page.
 *
 * PRD 7.4 wants a heading, a summary, search, a category filter, a featured
 * area, a grid, and pagination. DESIGN.md wants whitespace to do work and
 * warns against turning every section into a rounded container — so the
 * featured strip is the only part with a surface, and the grid below it is
 * cards on the page background.
 *
 * One eyebrow, not one per section. DESIGN.md permits a small uppercase label
 * above a headline, and a page that repeats it above every heading turns into
 * a template.
 */
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
                    {/*
                     * The strip is hidden while a filter is on. A pinned article
                     * is an editorial choice about the front page; under a
                     * filter the visitor asked for something else, and leaving
                     * two articles they did not ask for above the results reads
                     * as a bug.
                     */}
                    {!isFiltered ? (
                        <PinnedStrip
                            articles={pinned}
                            displayTimezone={displayTimezone}
                        />
                    ) : null}

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
