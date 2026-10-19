import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Text } from '@/components/text';
import { show as post } from '@/routes/berita';
import { formatWibDate } from '@/lib/datetime';

/**
 * One article, as the public list and homepage cards need it.
 *
 * The type lives beside the component rather than in a feature's types file:
 * two features render this card now, and ARCHITECTURE.md Part B section 4
 * forbids one feature reaching into another. A feature's own resource shape is
 * structurally compatible with this one.
 */
export type ArticleCardData = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    image: string | null;
    image_alt: string | null;
    /** Fallback illustration, not a photograph of the parish (D-71). */
    image_is_illustration?: boolean;
    /** Sent only for locally committed illustrations. */
    image_srcset?: string;
    image_sizes?: string;
    category: {
        id: number;
        name: string;
        slug: string;
    } | null;
    published_at: string | null;
};

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
    article: ArticleCardData;
    displayTimezone: string;
    headingLevel?: 'h2' | 'h3';
}) {
    return (
        <article className="group relative overflow-hidden rounded-lg border border-border bg-card transition-colors focus-within:border-primary-on-surface hover:border-primary-on-surface">
            {article.image ? (
                <>
                    <img
                        src={article.image}
                        alt={article.image_alt ?? ''}
                        srcSet={article.image_srcset}
                        sizes={article.image_sizes}
                        loading="lazy"
                        className="aspect-16/10 w-full rounded-t-lg object-cover"
                    />
                    {article.image_is_illustration ? (
                        <span className="absolute top-3 left-3 rounded-pill bg-gold-soft px-2.5 py-1 text-fine-print font-medium text-navy-deep">
                            Ilustrasi
                        </span>
                    ) : null}
                </>
            ) : null}

            <div className="space-y-3 p-6">
                <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                    {article.category ? (
                        <Text as="p" variant="label" color="brand">
                            {article.category.name}
                        </Text>
                    ) : null}
                    {article.published_at ? (
                        <Text as="p" variant="body-sm" color="muted">
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
                    className="inline-flex items-center gap-2 text-sm text-primary-on-surface transition-transform group-hover:translate-x-1 motion-reduce:transform-none motion-reduce:transition-none"
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
                className="absolute inset-0 rounded-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <span className="sr-only">Baca artikel: {article.title}</span>
            </Link>
        </article>
    );
}
