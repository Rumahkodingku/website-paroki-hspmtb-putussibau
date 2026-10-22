import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarClock,
    CalendarDays,
    Clock3,
    MapPin,
} from 'lucide-react';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { index as jadwalMisa } from '@/routes/jadwal-misa';
import { NextMassCardData } from '@/components/next-mass-card';
import { formatMassDate, formatMassTime } from '@/lib/datetime';

type HomeNextMassSectionProps = {
    nextMass: NextMassCardData | null;
    displayTimezone: string;
};

export function HomeNextMassSection({
    nextMass,
    displayTimezone,
}: HomeNextMassSectionProps) {
    const timezone = displayTimezone || 'Asia/Pontianak';

    const massDate = nextMass
        ? formatMassDate(nextMass.starts_at, timezone)
        : null;

    const massTime = nextMass
        ? formatMassTime(nextMass.starts_at, timezone)
        : null;

    const massKindLabel =
        nextMass?.kind === 'rutin'
            ? 'Misa rutin'
            : nextMass?.kind === 'khusus'
              ? 'Misa khusus'
              : 'Jadwal misa';

    return (
        <PublicSection surface="default">
            <div className="space-y-8 md:space-y-10">
                <SectionHeading
                    eyebrow="Jadwal Liturgi"
                    title="Misa Berikutnya"
                    description="Jadwal terdekat dari seluruh lokasi paroki."
                    action={
                        <Button
                            asChild
                            variant="outline"
                            className="group h-11 rounded-full border-primary/70 bg-transparent px-5 text-primary transition-colors hover:bg-primary hover:text-primary-foreground"
                        >
                            <Link href={jadwalMisa()}>
                                Semua Jadwal
                                <ArrowRight
                                    aria-hidden="true"
                                    className="size-4 transition-transform group-hover:translate-x-1 motion-reduce:transform-none motion-reduce:transition-none"
                                />
                            </Link>
                        </Button>
                    }
                />

                {nextMass ? (
                    <article className="overflow-hidden">
                        <div className="grid grid-cols-1 divide-y divide-border/70 md:grid-cols-[1.1fr_1fr_1fr] md:divide-x md:divide-y-0">
                            {/* Mass name */}
                            <div className="flex min-w-0 items-center gap-4 p-5 sm:p-6 lg:p-7">
                                <CalendarDays
                                    aria-hidden="true"
                                    className="size-7 text-primary sm:size-8"
                                    strokeWidth={1.8}
                                />

                                <div className="min-w-0 space-y-1.5">
                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                    >
                                        {massKindLabel}
                                    </Text>

                                    <Text
                                        as="h3"
                                        variant="heading-sm"
                                        className="leading-snug"
                                    >
                                        {nextMass.name}
                                    </Text>

                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                    >
                                        {nextMass.kind === 'khusus'
                                            ? 'Misa khusus'
                                            : 'Jadwal rutin mingguan paroki'}
                                    </Text>
                                </div>
                            </div>

                            {/* Date and time */}
                            <div className="flex min-w-0 items-center gap-4 p-5 sm:p-6 lg:p-7">
                                <CalendarClock
                                    aria-hidden="true"
                                    className="size-7 text-primary sm:size-8"
                                    strokeWidth={1.8}
                                />

                                <div className="min-w-0 space-y-2">
                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                    >
                                        Hari &amp; Tanggal
                                    </Text>

                                    <Text
                                        as="p"
                                        variant="heading-sm"
                                        className="leading-relaxed"
                                    >
                                        {massDate}
                                    </Text>

                                    <div className="flex flex-wrap items-baseline gap-2">
                                        <Clock3
                                            aria-hidden="true"
                                            className="size-4 shrink-0 text-primary"
                                        />

                                        <Text
                                            as="span"
                                            variant="heading-lg"
                                            className="font-bold text-primary tabular-nums"
                                        >
                                            {massTime}
                                        </Text>

                                        <Text
                                            as="span"
                                            variant="body-sm"
                                            color="muted"
                                        >
                                            WIB
                                        </Text>
                                    </div>
                                </div>
                            </div>

                            {/* Location */}
                            <div className="flex min-w-0 items-center gap-4 p-5 sm:p-6 lg:p-7">
                                <MapPin
                                    aria-hidden="true"
                                    className="size-7 text-primary sm:size-10"
                                    strokeWidth={1.8}
                                />

                                <div className="min-w-0 space-y-2">
                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                    >
                                        Lokasi
                                    </Text>

                                    {nextMass.location.mapsUrl ? (
                                        <a
                                            href={nextMass.location.mapsUrl}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="group block rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                        >
                                            <Text
                                                as="p"
                                                variant="heading-sm"
                                                className="leading-relaxed transition-colors group-hover:text-primary"
                                            >
                                                {nextMass.location.name}
                                            </Text>

                                            <Text
                                                as="p"
                                                variant="body-sm"
                                                color="muted"
                                                className="mt-1"
                                            >
                                                {nextMass.location.address}
                                            </Text>
                                        </a>
                                    ) : (
                                        <>
                                            <Text
                                                as="p"
                                                variant="heading-sm"
                                                className="leading-relaxed"
                                            >
                                                {nextMass.location.name}
                                            </Text>

                                            <Text
                                                as="p"
                                                variant="body-sm"
                                                color="muted"
                                            >
                                                {nextMass.location.address}
                                            </Text>
                                        </>
                                    )}
                                </div>
                            </div>
                        </div>
                    </article>
                ) : (
                    <div className="rounded-2xl border border-border/70 bg-card px-6 py-10 text-center shadow-sm">
                        <CalendarDays
                            aria-hidden="true"
                            className="mx-auto size-8 text-muted-foreground"
                        />

                        <Text as="h3" variant="heading-sm" className="mt-3">
                            Belum ada jadwal misa
                        </Text>

                        <Text
                            as="p"
                            variant="body-sm"
                            color="muted"
                            className="mx-auto mt-2 max-w-md"
                        >
                            Jadwal misa berikutnya belum tersedia. Silakan
                            periksa kembali nanti.
                        </Text>
                    </div>
                )}
            </div>
        </PublicSection>
    );
}

export default HomeNextMassSection;
