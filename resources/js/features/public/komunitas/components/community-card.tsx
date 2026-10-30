import { Link } from '@inertiajs/react';
import Image from '@/components/image';
import { ArrowRight, Image as ImageIcon } from 'lucide-react';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarImage } from '@/components/ui/avatar';
import { show as communityShow } from '@/routes/komunitas';
import type { PublicCommunityCard as PublicCommunityCardData } from '../types';

export function CommunityCard({
    community,
}: {
    community: PublicCommunityCardData;
}) {
    return (
        <article className="group relative flex h-full flex-col overflow-hidden">
            {/* Thumbnail */}
            <div className="relative aspect-16/10 w-full shrink-0">
                {community.thumbnail_url ? (
                    <Image
                        src={community.thumbnail_url}
                        alt=""
                        loading="lazy"
                        aspectRatio="16 / 10"
                        fit="cover"
                        className="size-full rounded-t-2xl"
                    />
                ) : (
                    <div className="flex size-full items-center justify-center text-muted-foreground">
                        <ImageIcon className="size-10" aria-hidden="true" />
                    </div>
                )}

                {/* Community logo overlapping the thumbnail */}
                {community.logo_url ? (
                    <Avatar className="absolute -bottom-7 left-5 size-16 rounded-full border-[3px] border-background bg-white shadow-sm">
                        <AvatarImage
                            src={community.logo_url}
                            alt=""
                            className="rounded-full object-contain p-2"
                        />
                    </Avatar>
                ) : null}
            </div>

            {/* Card content */}
            <div
                className={`flex flex-1 flex-col items-start gap-3 p-3 ${
                    community.logo_url ? 'pt-11' : 'pt-5'
                }`}
            >
                {community.category ? (
                    <Badge
                        variant="secondary"
                        className="max-w-full rounded-full px-3 py-1 text-xs font-medium"
                    >
                        <span className="truncate">
                            {community.category.name}
                        </span>
                    </Badge>
                ) : null}

                <Text
                    as="h5"
                    variant="h5"
                    className="line-clamp-2 transition-colors group-hover:text-primary-on-surface"
                >
                    {community.name}
                </Text>

                {community.description ? (
                    <Text
                        as="p"
                        variant="body-sm"
                        color="muted"
                        className="line-clamp-3"
                    >
                        {community.description}
                    </Text>
                ) : null}

                <span className="mt-auto inline-flex items-center gap-2 pt-2 text-sm font-medium text-primary-on-surface">
                    Lihat Detail
                    <ArrowRight className="size-4" aria-hidden="true" />
                </span>
            </div>

            {/* Full-card navigation */}
            <Link
                href={communityShow(community.slug)}
                prefetch
                className="absolute inset-0 z-10 rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <span className="sr-only">
                    Lihat detail komunitas {community.name}
                </span>
            </Link>
        </article>
    );
}
