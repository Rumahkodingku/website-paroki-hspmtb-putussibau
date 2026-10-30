import type { ArticleCardData } from '@/types/news-agenda';
import Image from '@/components/image';
import Text from '@/components/text';
import { formatWibDate } from '@/lib/datetime';
import { Link } from '@inertiajs/react';
import { ImageIcon } from 'lucide-react';
import { show as postArticle } from '@/routes/berita';
import { Badge } from '@/components/ui/badge';

export function RelatedArticleItem({
    article,
    displayTimezone,
}: {
    article: ArticleCardData;
    displayTimezone: string;
}) {
    return (
        <Link
            href={postArticle(article.slug)}
            prefetch
            className="group flex min-w-0 gap-3 rounded-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        >
            <div className="w-24 shrink-0 overflow-hidden rounded-md bg-muted sm:w-28">
                {article.image ? (
                    <Image
                        src={article.image}
                        alt={article.image_alt ?? ''}
                        aspectRatio="4 / 3"
                        fit="cover"
                        loading="lazy"
                        imageClassName="transition-transform duration-300 group-hover:scale-105 motion-reduce:transform-none motion-reduce:transition-none"
                        className="h-full w-full"
                    />
                ) : (
                    <div
                        aria-hidden="true"
                        className="flex aspect-4/3 items-center justify-center"
                    >
                        <ImageIcon className="size-6 text-muted-foreground/50" />
                    </div>
                )}
            </div>

            <div className="min-w-0 flex-1 space-y-1">
                {article.category ? (
                    <Badge
                        variant="secondary"
                        className="text-[10px] font-semibold uppercase"
                    >
                        {article.category.name}
                    </Badge>
                ) : null}

                <Text
                    as="p"
                    variant="body-sm"
                    className="line-clamp-3 font-semibold transition-colors group-hover:text-primary"
                >
                    {article.title}
                </Text>

                {article.published_at ? (
                    <Text as="p" variant="fine-print" color="muted">
                        {formatWibDate(article.published_at, displayTimezone)}
                    </Text>
                ) : null}
            </div>
        </Link>
    );
}

export type { ArticleCardData };
