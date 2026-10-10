import { Card } from '@/components/ui/card';
import { Text } from '@/components/text';
import { formatWibDateTime } from '@/lib/datetime';
import { EmptyState } from '@/components/empty-state';
import type { NextMass, PublicLocation } from '../types';

/*
 * The "Misa Berikutnya" card.
 *
 * Everything on it was decided on the server by NextMassResolver and arrives as
 * `nextMass` already resolved to the display timezone. Nothing here compares two
 * dates or asks what day it is — roadmap 7.2 forbids a second schedule
 * calculation in React, and a page that computed its own idea of "next" would
 * disagree with the one the tests pinned.
 *
 * null is a real state, not an error: a parish that has not published a schedule
 * yet gets a sentence, not a spinner and not a blank (XC-E1).
 */

type Props = {
    nextMass: NextMass | null;
    displayTimezone: string;
};

export function NextMassCard({ nextMass, displayTimezone }: Props) {
    if (nextMass === null) {
        return (
            <Card className="border-hairline bg-card p-6">
                <Text as="h2" variant="h3">
                    Misa Berikutnya
                </Text>
                <div className="mt-4">
                    <EmptyState
                        title="Belum ada misa terjadwal"
                        description="Jadwal misa berikutnya belum tersedia. Silakan kembali lagi nanti."
                    />
                </div>
            </Card>
        );
    }

    return (
        <Card
            className="border-primary/40 bg-card p-6"
            data-test="next-mass-card"
        >
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="space-y-2">
                    <Text
                        as="p"
                        variant="micro-legal"
                        className="text-primary uppercase"
                    >
                        Misa Berikutnya
                    </Text>

                    <Text as="h2" variant="h3">
                        {nextMass.name}
                    </Text>

                    <Text as="p" variant="body">
                        {formatWibDateTime(nextMass.starts_at, displayTimezone)}
                    </Text>

                    {nextMass.kind === 'rutin' ? (
                        <Text as="p" variant="caption" color="muted">
                            {['Jadwal rutin mingguan', nextMass.language]
                                .filter(Boolean)
                                .join(' · ')}
                        </Text>
                    ) : (
                        <Text as="p" variant="caption" color="muted">
                            Misa khusus
                        </Text>
                    )}
                </div>

                {/* The location is named whether or not it has a map link.
                    Roadmap 7.1.2 lists "lokasi" as part of the card, so
                    rendering it inside LocationLink would hide it for every
                    location the parish has not given coordinates to — which is
                    most of them until the ministry adds them. */}
                <div className="space-y-2 text-right sm:min-w-48">
                    <Text as="p" variant="caption" color="muted">
                        {nextMass.location.name}
                    </Text>

                    <div className="flex justify-end">
                        <LocationLink location={nextMass.location} />
                    </div>
                </div>
            </div>
        </Card>
    );
}

/**
 * The map button, or nothing at all.
 *
 * mapsUrl is null when the location has neither a stored URL nor a coordinate
 * pair — Location::mapsUrl() decides that server-side and the address is never
 * used, because a partial address produces a link to the wrong building.
 */
export function LocationLink({ location }: { location: PublicLocation }) {
    if (location.mapsUrl === null) {
        return null;
    }

    return (
        <a
            href={location.mapsUrl}
            target="_blank"
            rel="noopener noreferrer"
            data-test="maps-link"
            className="inline-flex h-11 items-center rounded-md border border-hairline px-4 text-sm hover:border-primary focus-visible:border-primary focus-visible:ring-2 focus-visible:outline-none"
        >
            Buka di Google Maps
        </a>
    );
}
