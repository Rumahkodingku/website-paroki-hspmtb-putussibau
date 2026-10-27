import type { ArticleCardData } from '@/types/news-agenda';
import { Link } from '@inertiajs/react';
import { Image as ImageIcon } from 'lucide-react';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { show as post } from '@/routes/berita';
import { formatWibDate } from '@/lib/datetime';

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
        <div className="group relative h-full min-w-0 overflow-hidden py-0">
            {/* Article image */}
            {article.image ? (
                <div className="relative shrink-0 overflow-hidden rounded-xl bg-muted">
                    <img
                        src={article.image}
                        alt={article.image_alt ?? ''}
                        srcSet={article.image_srcset}
                        sizes={
                            article.image_sizes ??
                            '(max-width: 639px) 100vw, (max-width: 1023px) 50vw, 33vw'
                        }
                        loading="lazy"
                        className="aspect-video w-full object-cover transition-transform duration-500 group-hover:scale-[1.03] motion-reduce:transform-none motion-reduce:transition-none"
                    />

                    {article.image_is_illustration ? (
                        <Badge
                            variant="secondary"
                            className="absolute top-3 left-3 rounded-md"
                        >
                            Ilustrasi
                        </Badge>
                    ) : null}
                </div>
            ) : (
                <div
                    aria-hidden="true"
                    className="flex aspect-video shrink-0 items-center justify-center bg-muted text-muted-foreground"
                >
                    <ImageIcon className="size-8 opacity-40" />
                </div>
            )}

            {/* Article content */}
            <div className="flex flex-1 flex-col gap-3 p-4 sm:p-5">
                <div className="flex flex-wrap items-center gap-x-2.5 gap-y-1.5">
                    {article.category ? (
                        <Badge
                            variant="secondary"
                            className="rounded-full text-[10px] tracking-wide uppercase"
                        >
                            {article.category.name}
                        </Badge>
                    ) : null}

                    {article.published_at ? (
                        <Text as="p" variant="fine-print" color="muted">
                            {formatWibDate(
                                article.published_at,
                                displayTimezone,
                            )}
                        </Text>
                    ) : null}
                </div>

                <Text
                    as={headingLevel}
                    variant="h4"
                    className="line-clamp-2 transition-colors group-hover:text-primary-on-surface"
                >
                    {article.title}
                </Text>

                {article.excerpt ? (
                    <Text
                        as="p"
                        variant="body-sm"
                        color="muted"
                        className="line-clamp-3"
                    >
                        {article.excerpt}
                    </Text>
                ) : null}
            </div>

            {/* Accessible stretched link */}
            <Link
                href={post(article.slug)}
                prefetch
                aria-label={`Baca artikel: ${article.title}`}
                className="absolute inset-0 z-10 rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
            />
        </div>
    );
}

export type { ArticleCardData };
