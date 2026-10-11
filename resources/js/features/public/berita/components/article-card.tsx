import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Text } from '@/components/text';
import { show as post } from '@/routes/berita';
import { formatWibDate } from '@/lib/datetime';
import type { PublicPost } from '../types';

/**
 * DESIGN.md `NewsCard`: "Image-first editorial card with date, category, title,
 * excerpt, and optional arrow. The image should carry most of the visual weight."
 *
 * Three decisions follow from that sentence and are the whole component:
 *
 * The image is a fixed 16:10 with `object-cover`, so a card keeps its height
 * whether or not there is a picture — and the strip without a picture does not
 * jump when a late upload lands.
 *
 * The picture is optional rather than replaced by a placeholder. A grey block
 * in a grid of real photographs reads as a broken card, and an article with no
 * image is a normal thing on a parish site.
 *
 * The whole card is one link, not a title link inside a clickable-looking box.
 * Two nested anchors are a keyboard trap.
 */
export function ArticleCard({
    article,
    displayTimezone,
    headingLevel = 'h3',
}: {
    article: PublicPost;
    displayTimezone: string;
    headingLevel?: 'h2' | 'h3';
}) {
    return (
        <article className="group bg-canvas relative overflow-hidden rounded-lg border border-hairline transition-colors focus-within:border-primary hover:border-primary">
            {article.image ? (
                <img
                    src={article.image}
                    alt={article.image_alt ?? ''}
                    loading="lazy"
                    className="aspect-16/10 w-full rounded-t-lg object-cover"
                />
            ) : null}

            <div className="space-y-3 p-6">
                <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                    {article.category ? (
                        <Text as="p" variant="caption-strong" color="brand">
                            {article.category.name}
                        </Text>
                    ) : null}
                    {article.published_at ? (
                        <Text as="p" variant="caption" color="muted">
                            {formatWibDate(
                                article.published_at,
                                displayTimezone,
                            )}
                        </Text>
                    ) : null}
                </div>

                <Text
                    as={headingLevel}
                    variant={headingLevel === 'h2' ? 'h2' : 'h3'}
                >
                    {article.title}
                </Text>

                {article.excerpt ? (
                    <Text as="p" variant="body" color="muted">
                        {article.excerpt}
                    </Text>
                ) : null}

                {/*
                 * The arrow is decorative. It says "there is more" and the
                 * label already says which article, so an aria-hidden is right;
                 * a screen-reader user is not helped by hearing "arrow".
                 */}
                <span
                    aria-hidden="true"
                    className="inline-flex items-center gap-2 text-sm text-primary transition-transform group-hover:translate-x-1 motion-reduce:transform-none motion-reduce:transition-none"
                >
                    Baca selengkapnya
                    <ArrowRight className="size-4" />
                </span>
            </div>

            {/*
             * The link covers the card via a stretched pseudo-element rather
             * than by wrapping everything in an <a>, so the heading keeps its
             * own semantics and the whole card is still one tab stop.
             */}
            <Link
                href={post(article.slug)}
                prefetch
                className="absolute inset-0 rounded-lg focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
            >
                <span className="sr-only">Baca artikel: {article.title}</span>
            </Link>
        </article>
    );
}
