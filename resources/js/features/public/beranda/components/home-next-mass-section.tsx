import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { NextMassCard } from '@/components/next-mass-card';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Button } from '@/components/ui/button';
import { index as jadwalMisa } from '@/routes/jadwal-misa';
import type { NextMassCardData } from '@/components/next-mass-card';

/**
 * The next mass is the single most requested piece of information on a parish
 * site. It gets a full-width soft band of its own, directly below the quick
 * links, rather than sharing a grid with news or agenda.
 */
export function HomeNextMassSection({
    nextMass,
    displayTimezone,
}: {
    nextMass: NextMassCardData | null;
    displayTimezone: string;
}) {
    return (
        <PublicSection surface="soft">
            <div className="space-y-8">
                <SectionHeading
                    title="Misa Berikutnya"
                    description="Jadwal terdekat dari seluruh lokasi paroki."
                    action={
                        <Button asChild variant="outline">
                            <Link href={jadwalMisa()}>
                                Semua Jadwal
                                <ArrowRight aria-hidden="true" />
                            </Link>
                        </Button>
                    }
                />

                <NextMassCard
                    nextMass={nextMass}
                    displayTimezone={displayTimezone}
                    showLabel={false}
                    headingLevel="h3"
                />
            </div>
        </PublicSection>
    );
}

export default HomeNextMassSection;
