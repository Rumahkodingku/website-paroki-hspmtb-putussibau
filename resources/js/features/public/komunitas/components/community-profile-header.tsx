import { Image as ImageIcon } from 'lucide-react';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import type { PublicCommunityDetail } from '../types';

/**
 * The profile header: thumbnail as the hero, logo beside the name.
 *
 * Either image may be absent; the thumbnail falls back to a quiet panel and the
 * logo simply does not render, because a parish community without either is a
 * normal state and a broken-image glyph is not.
 */
export function CommunityProfileHeader({
    community,
}: {
    community: PublicCommunityDetail;
}) {
    return (
        <header className="mt-8 space-y-6">
            {community.thumbnail_url ? (
                <img
                    src={community.thumbnail_url}
                    alt=""
                    className="aspect-video w-full rounded-xl border border-hairline object-cover"
                />
            ) : (
                <div className="flex aspect-video w-full items-center justify-center rounded-xl border border-hairline bg-canvas-soft text-muted-foreground">
                    <ImageIcon className="size-10" aria-hidden="true" />
                </div>
            )}

            <div className="flex items-start gap-5">
                {community.logo_url ? (
                    <img
                        src={community.logo_url}
                        alt=""
                        className="size-16 shrink-0 rounded-lg border border-hairline object-contain p-1"
                    />
                ) : null}

                <div className="space-y-2">
                    {community.category ? (
                        <Badge variant="secondary">
                            {community.category.name}
                        </Badge>
                    ) : null}

                    <Text as="h1" variant="display-lg">
                        {community.name}
                    </Text>

                    {community.description ? (
                        <Text
                            as="p"
                            variant="lead"
                            color="muted"
                            className="max-w-3xl"
                        >
                            {community.description}
                        </Text>
                    ) : null}
                </div>
            </div>
        </header>
    );
}
