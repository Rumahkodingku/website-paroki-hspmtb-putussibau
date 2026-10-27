import type { ReactNode } from 'react';
import { Image as ImageIcon } from 'lucide-react';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import type { PublicCommunityDetail } from '../types';

type CommunityProfileHeaderProps = {
    community: PublicCommunityDetail;
    actions?: ReactNode;
};

export function CommunityProfileHeader({
    community,
    actions,
}: CommunityProfileHeaderProps) {
    return (
        <header className="mt-6">
            <div className="relative aspect-16/8 overflow-hidden rounded-3xl bg-canvas-subtle sm:aspect-16/7 lg:aspect-16/6">
                {community.thumbnail_url ? (
                    <img
                        src={community.thumbnail_url}
                        alt=""
                        fetchPriority="high"
                        className="size-full object-cover"
                    />
                ) : (
                    <div className="flex size-full items-center justify-center text-muted-foreground">
                        <ImageIcon className="size-10" aria-hidden="true" />
                    </div>
                )}
                <div
                    aria-hidden="true"
                    className="pointer-events-none absolute inset-0 bg-linear-to-t from-black/20 via-transparent to-transparent"
                />
            </div>

            <div className="relative z-10 mx-2 -mt-12 rounded-3xl border border-border/80 bg-background p-4 sm:mx-5 sm:-mt-10 sm:p-6 lg:mx-6 lg:-mt-24 lg:p-7">
                <div className="flex min-w-0 flex-col gap-5 lg:flex-row lg:items-center lg:justify-between lg:gap-8">
                    <div className="flex min-w-0 items-start gap-4 sm:gap-5">
                        {community.logo_url ? (
                            <div className="flex size-16 shrink-0 items-center justify-center overflow-hidden rounded-full border border-border bg-white p-2 sm:size-20 sm:p-2.5">
                                <img
                                    src={community.logo_url}
                                    alt=""
                                    loading="lazy"
                                    className="size-full object-contain"
                                />
                            </div>
                        ) : null}

                        <div className="min-w-0 space-y-2">
                            {community.category ? (
                                <Badge
                                    variant="secondary"
                                    className="max-w-full"
                                >
                                    <span className="truncate">
                                        {community.category.name}
                                    </span>
                                </Badge>
                            ) : null}

                            <Text
                                as="h1"
                                variant="display-md"
                                className="text-balance"
                            >
                                {community.name}
                            </Text>

                            {community.description ? (
                                <Text
                                    as="p"
                                    variant="body"
                                    color="muted"
                                    className="max-w-3xl"
                                >
                                    {community.description}
                                </Text>
                            ) : null}
                        </div>
                    </div>

                    {actions ? (
                        <div className="w-full lg:w-auto lg:max-w-88">
                            {actions}
                        </div>
                    ) : null}
                </div>
            </div>
        </header>
    );
}
