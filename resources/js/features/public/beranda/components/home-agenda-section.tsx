import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { EventCard, type EventCardData } from '@/components/event-card';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Button } from '@/components/ui/button';
import { index as agenda } from '@/routes/agenda';

/**
 * The nearest published activities.
 *
 * Cancelled and postponed activities stay in this list with their labels: a
 * visitor who saw last week's announcement needs to learn that a gathering is
 * off, not to find the entry quietly missing.
 */
export function HomeAgendaSection({
    events,
    displayTimezone,
}: {
    events: EventCardData[];
    displayTimezone: string;
}) {
    if (events.length === 0) {
        return null;
    }

    return (
        <PublicSection surface="soft">
            <div className="space-y-8">
                <SectionHeading
                    title="Agenda Mendatang"
                    description="Kegiatan yang akan datang, terdekat lebih dahulu."
                    action={
                        <Button asChild variant="outline">
                            <Link href={agenda()}>
                                Semua Agenda
                                <ArrowRight aria-hidden="true" />
                            </Link>
                        </Button>
                    }
                />

                <div className="grid gap-x-12 gap-y-8 md:grid-cols-2">
                    {events.map((event) => (
                        <EventCard
                            key={event.id}
                            event={event}
                            displayTimezone={displayTimezone}
                        />
                    ))}
                </div>
            </div>
        </PublicSection>
    );
}

export default HomeAgendaSection;
