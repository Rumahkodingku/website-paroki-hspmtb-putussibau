import { usePage } from '@inertiajs/react';
import { ArrowRight, Play, Video } from 'lucide-react';
import Image from '@/components/image';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { formatWibLongDate } from '@/lib/datetime';
import type { SharedProps } from '@/types';
import type { PublicLivestream } from '../types';

type Props = {
    stream: PublicLivestream;
    onPlay: () => void;
};

/**
 * One archive recording.
 *
 * The whole card is the play affordance, so the title, date and category stay
 * readable without a second nested link. The button's accessible name names the
 * recording; the visual play icon is decorative.
 */
export function LivestreamArchiveCard({ stream, onPlay }: Props) {
    const { displayTimezone } = usePage<SharedProps>().props;

    return (
        <article
            data-test="livestream-card"
            className="group relative overflow-hidden rounded-lg border border-border transition-colors focus-within:border-primary-on-surface hover:border-primary-on-surface"
        >
            <div className="relative aspect-video overflow-hidden bg-canvas-subtle">
                {stream.thumbnail_url ? (
                    <Image
                        src={stream.thumbnail_url}
                        alt=""
                        aspectRatio="16 / 9"
                        fit="cover"
                        loading="lazy"
                        className="size-full"
                    />
                ) : (
                    <span
                        aria-hidden="true"
                        className="flex size-full items-center justify-center text-muted-foreground"
                    >
                        <Video className="size-8" />
                    </span>
                )}

                <span
                    aria-hidden="true"
                    className="absolute inset-0 flex items-center justify-center bg-black/10 transition-colors group-hover:bg-black/20 motion-reduce:transition-none"
                >
                    <span className="flex size-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform duration-200 group-hover:scale-105 motion-reduce:transform-none motion-reduce:transition-none">
                        <Play
                            className="size-6 translate-x-0.5"
                            fill="currentColor"
                        />
                    </span>
                </span>
            </div>

            <div className="space-y-3 p-5">
                {stream.category_label ? (
                    <Badge variant="secondary">{stream.category_label}</Badge>
                ) : null}

                <Text as="h3" variant="h3" className="line-clamp-2">
                    {stream.title}
                </Text>

                {stream.recorded_at ? (
                    <Text as="p" variant="body-sm" color="muted">
                        {formatWibLongDate(stream.recorded_at, displayTimezone)}
                    </Text>
                ) : null}

                <span
                    aria-hidden="true"
                    className="inline-flex items-center gap-2 text-sm font-medium text-primary-on-surface transition-transform group-hover:translate-x-1 motion-reduce:transform-none motion-reduce:transition-none"
                >
                    Tonton rekaman
                    <ArrowRight className="size-4" />
                </span>
            </div>

            <button
                type="button"
                onClick={onPlay}
                aria-label={`Putar rekaman ${stream.title}`}
                className="absolute inset-0 rounded-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <span className="sr-only">Putar rekaman {stream.title}</span>
            </button>
        </article>
    );
}
