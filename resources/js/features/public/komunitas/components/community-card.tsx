import { Link } from '@inertiajs/react';
import { ArrowRight, Image as ImageIcon } from 'lucide-react';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { show as communityShow } from '@/routes/komunitas';
import type { PublicCommunityCard as PublicCommunityCardData } from '../types';

/**
 * The directory card.
 *
 * A fixed 16:10 image keeps every card the same height whether or not there is a
 * picture, and a quiet placeholder stands in when there is none rather than a
 * missing box. The whole card is one link via a stretched pseudo-element, so the
 * heading keeps its semantics and the card is one tab stop.
 */
export function CommunityCard({
    community,
}: {
    community: PublicCommunityCardData;
}) {
    return (
        <article className="group relative overflow-hidden rounded-lg border border-hairline transition-colors focus-within:border-primary hover:border-primary">
            {community.thumbnail_url ? (
                <img
                    src={community.thumbnail_url}
                    alt=""
                    loading="lazy"
                    className="aspect-16/10 w-full rounded-t-lg object-cover"
                />
            ) : (
                <div className="flex aspect-16/10 w-full items-center justify-center rounded-t-lg bg-canvas-soft text-muted-foreground">
                    <ImageIcon className="size-8" aria-hidden="true" />
                </div>
            )}

            <div className="space-y-3 p-6">
                {community.category ? (
                    <Badge variant="secondary">{community.category.name}</Badge>
                ) : null}

                <Text as="h3" variant="h3">
                    {community.name}
                </Text>

                {community.description ? (
                    <Text
                        as="p"
                        variant="body"
                        color="muted"
                        className="line-clamp-3"
                    >
                        {community.description}
                    </Text>
                ) : null}

                <span
                    aria-hidden="true"
                    className="inline-flex items-center gap-2 text-sm text-primary transition-transform group-hover:translate-x-1 motion-reduce:transform-none motion-reduce:transition-none"
                >
                    Lihat Detail
                    <ArrowRight className="size-4" />
                </span>
            </div>

            <Link
                href={communityShow(community.slug)}
                prefetch
                className="absolute inset-0 rounded-lg focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
            >
                <span className="sr-only">
                    Lihat detail komunitas {community.name}
                </span>
            </Link>
        </article>
    );
}
