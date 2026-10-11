import { Head, Link, usePage } from '@inertiajs/react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { PublicSection } from '@/components/public-section';
import { RichText } from '@/components/rich-text';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { formatWibDate } from '@/lib/datetime';
import { home } from '@/routes';
import { index as beritaIndex, show as postArticle } from '@/routes/berita';
import type { PublicPost, PublicPostDetail } from '../types';
import { ShareActions } from '../components/share-actions';
import { ArticleCard } from '../components/article-card';

type Props = {
    post: PublicPostDetail;
    related: PublicPost[];
    adjacent: { previous: PublicPost | null; next: PublicPost | null };
};

/*
 * The article page.
 *
 * Three things the PRD asks for and that are easy to get wrong:
 *
 * A reading measure. DESIGN.md puts text-heavy content at 980–1120px; max-w-3xl
 * is narrower than that, which is the point — 17px body text across 1100px is
 * about 130 characters a line and nobody reads that. This is the one page on
 * the site that is a column of prose.
 *
 * The scripture reference above the body, and only when it was filled in. A
 * Renungan without one is normal; a Renungan with an empty quotation mark
 * under it is not.
 *
 * Previous and next. PRD 7.4 calls them optional. They are here because a reader
 * who reaches the end of an article usually wants the next one, and a list page
 * is a worse way to get it.
 */
export default function Show({ post, related, adjacent }: Props) {
    const { displayTimezone } = usePage().props;
    const url = typeof window === 'undefined' ? '' : window.location.href;

    return (
        <>
            <Seo
                title={post.seo.title}
                description={post.seo.description}
                ogType="article"
                ogImage={post.image}
                canonical={url || undefined}
            />

            <Head title={post.title} />

            <PublicSection>
                <Breadcrumbs
                    className="mb-8"
                    breadcrumbs={[
                        { title: 'Beranda', href: home() },
                        { title: 'Berita', href: beritaIndex() },
                        { title: post.title },
                    ]}
                />

                <div className="mx-auto max-w-3xl space-y-8">
                    <article className="space-y-6">
                        <header className="space-y-3">
                            {post.category ? (
                                <Text as="p" variant="tagline" color="brand">
                                    {post.category.name}
                                </Text>
                            ) : null}

                            <Text as="h1" variant="h1">
                                {post.title}
                            </Text>

                            <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                                <Text as="p" variant="caption" color="muted">
                                    {post.author_name}
                                </Text>
                                {post.published_at ? (
                                    <Text
                                        as="p"
                                        variant="caption"
                                        color="muted"
                                    >
                                        {formatWibDate(
                                            post.published_at,
                                            displayTimezone,
                                        )}
                                    </Text>
                                ) : null}
                            </div>
                        </header>

                        {post.image ? (
                            <img
                                src={post.image}
                                alt={post.image_alt ?? ''}
                                className="w-full rounded-md object-cover"
                            />
                        ) : null}

                        {post.scripture_reference ? (
                            <blockquote className="border-l-4 border-primary pl-4">
                                <Text as="p" variant="body-strong">
                                    {post.scripture_reference}
                                </Text>
                            </blockquote>
                        ) : null}

                        <RichText html={post.content} />
                    </article>

                    <ShareActions url={url} title={post.title} />

                    {/*
                     * Both arrows, or neither. A single "back to the list" on
                     * one side and a "next article" on the other is a layout
                     * that reads as broken when one of them is missing, so the
                     * block only appears when there is something to go to.
                     */}
                    {adjacent.previous || adjacent.next ? (
                        <nav
                            aria-label="Navigasi artikel"
                            className="grid gap-4 border-t border-hairline pt-6 sm:grid-cols-2"
                        >
                            {adjacent.previous ? (
                                <Link
                                    href={postArticle(adjacent.previous.slug)}
                                    prefetch
                                    className="rounded-sm text-sm text-primary underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                                >
                                    <Text
                                        as="span"
                                        variant="caption"
                                        color="muted"
                                    >
                                        Sebelumnya
                                    </Text>
                                    <br />
                                    {adjacent.previous.title}
                                </Link>
                            ) : (
                                <span />
                            )}

                            {adjacent.next ? (
                                <Link
                                    href={postArticle(adjacent.next.slug)}
                                    prefetch
                                    className="rounded-sm text-right text-sm text-primary underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none sm:col-start-2"
                                >
                                    <Text
                                        as="span"
                                        variant="caption"
                                        color="muted"
                                    >
                                        Berikutnya
                                    </Text>
                                    <br />
                                    {adjacent.next.title}
                                </Link>
                            ) : null}
                        </nav>
                    ) : null}
                </div>

                {related.length > 0 ? (
                    <section
                        aria-labelledby="berita-terkait"
                        className="mx-auto mt-16 max-w-3xl space-y-6"
                    >
                        <Text as="h2" variant="h3" id="berita-terkait">
                            Berita terkait
                        </Text>

                        <div className="grid gap-6 sm:grid-cols-3">
                            {related.map((article) => (
                                <ArticleCard
                                    key={article.id}
                                    article={article}
                                    displayTimezone={displayTimezone}
                                />
                            ))}
                        </div>
                    </section>
                ) : null}
            </PublicSection>
        </>
    );
}
