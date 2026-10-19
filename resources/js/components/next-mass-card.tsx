import { Card } from '@/components/ui/card';
import { EmptyState } from '@/components/empty-state';
import { Text } from '@/components/text';
import { formatWibDateTime } from '@/lib/datetime';

/**
 * The resolved "next mass" shape. It lives with the component because the
 * homepage and the schedule page both render this card; each feature's own
 * resource type is structurally compatible.
 */
export type NextMassLocation = {
    id: number;
    name: string;
    type: string;
    address: string | null;
    mapsUrl: string | null;
};

export type NextMassCardData = {
    kind: 'rutin' | 'khusus';
    starts_at: string;
    name: string;
    location: NextMassLocation;
};

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
    nextMass: NextMassCardData | null;
    displayTimezone: string;
    /**
     * Hide the card's own "Misa Berikutnya" label when the surrounding section
     * already provides that heading. Defaults to true so /jadwal-misa is
     * unchanged.
     */
    showLabel?: boolean;
    /**
     * The mass name is the page's principal heading on /jadwal-misa, but a
     * homepage section already owns the h2. Defaults to h2 so the schedule page
     * is unchanged.
     */
    headingLevel?: 'h2' | 'h3';
};

export function NextMassCard({
    nextMass,
    displayTimezone,
    showLabel = true,
    headingLevel = 'h2',
}: Props) {
    if (nextMass === null) {
        return (
            <Card className="border-border bg-card p-6">
                <Text as={headingLevel} variant="h3">
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
                    {showLabel ? (
                        <Text
                            as="p"
                            variant="fine-print"
                            className="text-primary-on-surface uppercase"
                        >
                            Misa Berikutnya
                        </Text>
                    ) : null}

                    <Text as={headingLevel} variant="h3">
                        {nextMass.name}
                    </Text>

                    <Text as="p" variant="body">
                        {formatWibDateTime(nextMass.starts_at, displayTimezone)}
                    </Text>

                    {nextMass.kind === 'rutin' ? (
                        <Text as="p" variant="body-sm" color="muted">
                            Jadwal rutin mingguan
                        </Text>
                    ) : (
                        <Text as="p" variant="body-sm" color="muted">
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
                    <Text as="p" variant="body-sm" color="muted">
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
export function LocationLink({ location }: { location: NextMassLocation }) {
    if (location.mapsUrl === null) {
        return null;
    }

    return (
        <a
            href={location.mapsUrl}
            target="_blank"
            rel="noopener noreferrer"
            data-test="maps-link"
            className="inline-flex h-11 items-center rounded-md border border-border px-4 text-sm hover:border-primary-on-surface focus-visible:border-primary focus-visible:ring-2 focus-visible:outline-none"
        >
            Buka di Google Maps
        </a>
    );
}
