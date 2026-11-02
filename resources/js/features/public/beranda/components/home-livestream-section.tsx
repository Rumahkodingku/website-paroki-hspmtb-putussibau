import { Link, usePage } from '@inertiajs/react';
import { CalendarDays, MapPin, Youtube } from 'lucide-react';
import { LivestreamPlayer } from '@/components/livestream-player';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatWibDateTime } from '@/lib/datetime';
import { index as livestreamingIndex } from '@/routes/livestreaming';
import type { SharedProps } from '@/types';
import type { PublicLivestream } from '@/components/livestream-player';

type Props = {
    stream: PublicLivestream | null;
    enabled: boolean;
};

/**
 * The homepage live block.
 *
 * It renders nothing when there is no published live stream. That is the whole
 * visibility rule: no placeholder, no fallback to an old recording, and no
 * empty space where a player would have been.
 */
export function HomeLivestreamSection({ stream, enabled }: Props) {
    const { displayTimezone } = usePage<SharedProps>().props;

    if (!enabled || stream === null) {
        return null;
    }

    const date = stream.started_at ?? stream.scheduled_at;

    return (
        <PublicSection>
            <div
                data-test="home-livestream-section"
                className="space-y-8 md:space-y-10"
            >
                <SectionHeading
                    eyebrow="SIARAN LANGSUNG"
                    title="Ikuti Kegiatan Paroki Secara Online"
                    description="Saksikan perayaan Ekaristi dan kegiatan rohani Paroki HSPMTB Putussibau melalui siaran langsung YouTube."
                    action={
                        <Button
                            asChild
                            variant="outline"
                            className="group h-11 rounded-full border-primary/70 bg-transparent px-5 text-sm text-primary transition-colors hover:bg-primary hover:text-primary-foreground"
                        >
                            <Link href={livestreamingIndex()}>Lihat Arsip</Link>
                        </Button>
                    }
                />

                <div className="grid gap-6 lg:grid-cols-[1.85fr_1fr]">
                    <div className="min-w-0">
                        <LivestreamPlayer stream={stream} />
                    </div>

                    <div className="flex min-w-0 flex-col gap-4 rounded-xl p-4">
                        <div className="flex flex-wrap items-center gap-3">
                            <Badge className="text-[10px]">SEDANG LIVE</Badge>
                            <Text as="p" variant="body-sm" color="muted">
                                Siaran langsung
                            </Text>
                        </div>

                        <Text as="h3" variant="h3" className="text-balance">
                            {stream.title}
                        </Text>

                        <div className="space-y-2">
                            {date ? (
                                <p className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <CalendarDays
                                        className="size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {formatWibDateTime(date, displayTimezone)}
                                </p>
                            ) : null}

                            {stream.location ? (
                                <p className="flex items-center gap-2 text-sm text-muted-foreground">
                                    <MapPin
                                        className="size-4 shrink-0"
                                        aria-hidden="true"
                                    />
                                    {stream.location}
                                </p>
                            ) : null}
                        </div>

                        {stream.description ? (
                            <Text as="p" variant="body-sm" color="muted">
                                {stream.description}
                            </Text>
                        ) : null}

                        <div className="flex flex-wrap gap-3 pt-2">
                            {stream.watch_url ? (
                                <Button asChild>
                                    <a
                                        href={stream.watch_url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-sm"
                                    >
                                        <Youtube
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                        Tonton di YouTube
                                    </a>
                                </Button>
                            ) : null}
                        </div>
                    </div>
                </div>
            </div>
        </PublicSection>
    );
}
