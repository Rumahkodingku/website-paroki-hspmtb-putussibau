import { Link } from '@inertiajs/react';
import { Text } from '@/components/text';
import { show as post } from '@/routes/berita';
import { formatWibDate } from '@/lib/datetime';
import type { PublicPost } from '../types';

/**
 * The featured strip: at most two articles, above the list.
 *
 * POST-07 caps it at two and the query enforces that, so this component does
 * not have to. Two rather than three is the PRD's number and it is also the
 * right number editorially: three cards of the same weight reads as "this is
 * the list" and the list below becomes redundant.
 *
 * Visually distinct from the cards — wider, no border, image beside the text —
 * because "this is the editor's choice" and "this is everything" are different
 * things and identical treatment would hide the distinction.
 */
export function PinnedStrip({
    articles,
    displayTimezone,
}: {
    articles: PublicPost[];
    displayTimezone: string;
}) {
    if (articles.length === 0) {
        return null;
    }

    return (
        <section aria-labelledby="berita-semat" className="space-y-4">
            <Text as="h2" variant="h3" id="berita-semat">
                Pilihan Redaksi
            </Text>

            <div className="grid gap-6 md:grid-cols-2">
                {articles.map((article) => (
                    <article
                        key={article.id}
                        className="group relative grid gap-4 overflow-hidden rounded-lg bg-navy-soft p-6 sm:grid-cols-2 sm:items-center"
                    >
                        {article.image ? (
                            <img
                                src={article.image}
                                alt={article.image_alt ?? ''}
                                loading="lazy"
                                className="aspect-4/3 w-full rounded-md object-cover sm:aspect-square"
                            />
                        ) : null}

                        <div className="space-y-2">
                            {article.category ? (
                                <Text as="p" variant="label" color="brand">
                                    {article.category.name}
                                </Text>
                            ) : null}

                            <Text as="h3" variant="h4">
                                {article.title}
                            </Text>

                            {article.published_at ? (
                                <Text as="p" variant="body-sm" color="muted">
                                    {formatWibDate(
                                        article.published_at,
                                        displayTimezone,
                                    )}
                                </Text>
                            ) : null}
                        </div>

                        <Link
                            href={post(article.slug)}
                            prefetch
                            className="absolute inset-0 rounded-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <span className="sr-only">
                                Baca artikel: {article.title}
                            </span>
                        </Link>
                    </article>
                ))}
            </div>
        </section>
    );
}
