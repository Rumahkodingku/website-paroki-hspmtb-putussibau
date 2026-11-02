import { Head, Link, usePage } from '@inertiajs/react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { PublicSection } from '@/components/public-section';
import { RichText } from '@/components/rich-text';
import { Seo } from '@/components/seo';
import Image from '@/components/image';
import { Text } from '@/components/text';
import { formatWibDate } from '@/lib/datetime';
import { home } from '@/routes';
import { index as beritaIndex, show as postArticle } from '@/routes/berita';
import type { PublicPost, PublicPostDetail } from '../types';
import { ShareActions } from '../components/share-actions';
import { RelatedArticleItem } from '../components/related-article-card';
import { Badge } from '@/components/ui/badge';

type Props = {
    post: PublicPostDetail;
    related: PublicPost[];
    adjacent: {
        previous: PublicPost | null;
        next: PublicPost | null;
    };
};

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
                <div className="mx-auto max-w-7xl">
                    <Breadcrumbs
                        className="mb-8"
                        breadcrumbs={[
                            { title: 'Beranda', href: home() },
                            { title: 'Berita', href: beritaIndex() },
                            ...(post.category
                                ? [
                                      {
                                          title: post.category.name,
                                          href: beritaIndex({
                                              query: {
                                                  kategori: post.category.slug,
                                              },
                                          }),
                                      },
                                  ]
                                : []),
                            { title: post.title },
                        ]}
                    />

                    <div className="mt-10 grid grid-cols-1 items-start gap-10 lg:grid-cols-12 lg:gap-12">
                        {/* Main article */}
                        <article className="min-w-0 space-y-8 lg:col-span-8">
                            <header className="space-y-4">
                                {post.category ? (
                                    <Badge
                                        variant="secondary"
                                        className="px-3 py-1 text-xs font-semibold tracking-wide uppercase"
                                    >
                                        {post.category.name}
                                    </Badge>
                                ) : null}

                                <Text
                                    as="h1"
                                    variant="h1"
                                    className="max-w-4xl text-balance"
                                >
                                    {post.title}
                                </Text>

                                <div className="flex flex-wrap items-center gap-x-3 gap-y-2 text-muted-foreground">
                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                    >
                                        {post.author_name}
                                    </Text>

                                    {post.published_at ? (
                                        <>
                                            <span
                                                aria-hidden="true"
                                                className="text-border"
                                            >
                                                ·
                                            </span>
                                            <Text
                                                as="p"
                                                variant="body-sm"
                                                color="muted"
                                            >
                                                {formatWibDate(
                                                    post.published_at,
                                                    displayTimezone,
                                                )}
                                            </Text>
                                        </>
                                    ) : null}

                                    {typeof post.view_count === 'number' ? (
                                        <>
                                            <span
                                                aria-hidden="true"
                                                className="text-border"
                                            >
                                                ·
                                            </span>
                                            <Text
                                                as="p"
                                                variant="body-sm"
                                                color="muted"
                                            >
                                                {post.view_count.toLocaleString(
                                                    'id-ID',
                                                )}{' '}
                                                kali dibaca
                                            </Text>
                                        </>
                                    ) : null}
                                </div>
                            </header>

                            {post.image ? (
                                <figure className="space-y-2">
                                    <div className="overflow-hidden rounded-xl bg-muted">
                                        <Image
                                            src={post.image}
                                            alt={post.image_alt ?? ''}
                                            fetchPriority="high"
                                            aspectRatio="16 / 9"
                                            fit="cover"
                                            className="w-full"
                                        />
                                    </div>

                                    {post.image_alt ? (
                                        <figcaption>
                                            <Text
                                                as="p"
                                                variant="fine-print"
                                                color="muted"
                                                className="text-center italic"
                                            >
                                                {post.image_alt}
                                            </Text>
                                        </figcaption>
                                    ) : null}
                                </figure>
                            ) : null}

                            {post.scripture_reference ? (
                                <blockquote className="rounded-r-lg border-l-4 border-primary bg-muted/50 px-5 py-4">
                                    <Text as="p" variant="heading-sm">
                                        {post.scripture_reference}
                                    </Text>
                                </blockquote>
                            ) : null}

                            <div className="max-w-3xl min-w-0">
                                <RichText html={post.content} />
                            </div>

                            {/* Previous and next articles */}
                            {adjacent.previous || adjacent.next ? (
                                <nav
                                    aria-label="Navigasi artikel"
                                    className="grid gap-4 border-t border-border pt-6 sm:grid-cols-2"
                                >
                                    {adjacent.previous ? (
                                        <Link
                                            href={postArticle(
                                                adjacent.previous.slug,
                                            )}
                                            prefetch
                                            className="group rounded-lg border border-border p-4 transition-colors hover:bg-muted/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                        >
                                            <Text
                                                as="p"
                                                variant="fine-print"
                                                color="muted"
                                            >
                                                ← Artikel sebelumnya
                                            </Text>
                                            <Text
                                                as="p"
                                                variant="body-sm"
                                                className="mt-2 font-semibold transition-colors group-hover:text-primary"
                                            >
                                                {adjacent.previous.title}
                                            </Text>
                                        </Link>
                                    ) : (
                                        <span />
                                    )}

                                    {adjacent.next ? (
                                        <Link
                                            href={postArticle(
                                                adjacent.next.slug,
                                            )}
                                            prefetch
                                            className="group rounded-lg border border-border p-4 text-left transition-colors hover:bg-muted/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:text-right"
                                        >
                                            <Text
                                                as="p"
                                                variant="fine-print"
                                                color="muted"
                                            >
                                                Artikel selanjutnya →
                                            </Text>
                                            <Text
                                                as="p"
                                                variant="body-sm"
                                                className="mt-2 font-semibold transition-colors group-hover:text-primary"
                                            >
                                                {adjacent.next.title}
                                            </Text>
                                        </Link>
                                    ) : null}
                                </nav>
                            ) : null}
                        </article>

                        {/* Editorial sidebar */}
                        <aside className="min-w-0 space-y-6 lg:col-span-4">
                            {related.length > 0 ? (
                                <section aria-labelledby="berita-terkait">
                                    <div className="mb-5 flex items-center justify-between gap-3">
                                        <Text
                                            as="h2"
                                            variant="heading-md"
                                            id="berita-terkait"
                                        >
                                            Berita & artikel terkait
                                        </Text>
                                    </div>

                                    <div className="space-y-4">
                                        {related.map((article) => (
                                            <RelatedArticleItem
                                                key={article.id}
                                                article={article}
                                                displayTimezone={
                                                    displayTimezone
                                                }
                                            />
                                        ))}
                                    </div>
                                </section>
                            ) : null}

                            <section
                                aria-labelledby="bagikan-artikel"
                                className="border-t py-4"
                            >
                                <Text
                                    as="h2"
                                    variant="heading-md"
                                    id="bagikan-artikel"
                                    className="mb-4"
                                    weight="semibold"
                                >
                                    Bagikan artikel
                                </Text>

                                <ShareActions url={url} title={post.title} />
                            </section>
                        </aside>
                    </div>
                </div>
            </PublicSection>
        </>
    );
}
