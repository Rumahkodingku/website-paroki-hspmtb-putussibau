import { SectionHeading } from '@/components/section-heading';
import Image from '@/components/image';
import { Text } from '@/components/text';
import type { PublicPastorAssignment } from '../types';
import { formatPastorName, formatServicePeriod } from './pastor-formatters';

export function PastorHistorySection({
    history,
}: {
    history: PublicPastorAssignment[];
}) {
    if (history.length === 0) {
        return null;
    }

    return (
        <section
            aria-labelledby="riwayat-pastor"
            className="border-t border-border pt-10 sm:pt-12"
        >
            <div className="mb-8 max-w-2xl space-y-2">
                <SectionHeading
                    title="Riwayat Pastor Paroki"
                    level={2}
                    className="gap-3"
                />

                <Text as="p" variant="body" color="muted">
                    Dokumentasi riwayat penugasan para imam yang pernah melayani
                    umat Paroki HSPMTB Putussibau.
                </Text>
            </div>

            <ol className="relative space-y-5 sm:space-y-6">
                {history.map((assignment) => {
                    const name = formatPastorName(
                        assignment.title,
                        assignment.name,
                    );

                    const period = formatServicePeriod(
                        assignment.start_year,
                        assignment.end_year,
                        assignment.is_current,
                    );

                    return (
                        <li
                            key={assignment.id}
                            className="grid grid-cols-1 gap-3 sm:grid-cols-[180px_minmax(0,1fr)] sm:gap-5 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-8"
                        >
                            {/* Periode dan jabatan */}
                            <div className="flex flex-col gap-1 sm:pt-5">
                                <Text
                                    as="p"
                                    variant="h4"
                                    color="danger"
                                    className="leading-tight"
                                >
                                    {period}
                                </Text>

                                <Text as="p" variant="body-sm" color="muted">
                                    {assignment.position_label}
                                </Text>
                            </div>

                            {/* Detail riwayat */}
                            <article className="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-start">
                                {assignment.photo ? (
                                    <Image
                                        src={assignment.photo}
                                        alt={
                                            assignment.photo_alt ??
                                            `Foto ${name}`
                                        }
                                        loading="lazy"
                                        decoding="async"
                                        aspectRatio="4 / 5"
                                        fit="cover"
                                        objectPosition="top"
                                        className="w-24 shrink-0 rounded-lg sm:w-28"
                                    />
                                ) : (
                                    <div
                                        aria-hidden="true"
                                        className="flex aspect-4/5 w-24 shrink-0 items-center justify-center rounded-lg bg-muted sm:w-28"
                                    >
                                        <Text
                                            as="span"
                                            variant="label"
                                            color="muted"
                                        >
                                            Foto tidak tersedia
                                        </Text>
                                    </div>
                                )}

                                <div className="min-w-0 flex-1 space-y-2">
                                    <Text as="h3" variant="h4">
                                        {name}
                                    </Text>

                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="rounded-full bg-primary/10 px-3 py-1">
                                            <Text
                                                as="span"
                                                variant="label"
                                                color="danger"
                                            >
                                                {assignment.position_label}
                                            </Text>
                                        </span>

                                        {assignment.is_current && (
                                            <Text
                                                as="span"
                                                variant="label"
                                                color="muted"
                                            >
                                                Sedang bertugas
                                            </Text>
                                        )}
                                    </div>

                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                    >
                                        Periode pelayanan: {period}
                                    </Text>

                                    {assignment.description && (
                                        <Text
                                            as="p"
                                            variant="body"
                                            color="muted"
                                            className="pt-1"
                                        >
                                            {assignment.description}
                                        </Text>
                                    )}
                                </div>
                            </article>
                        </li>
                    );
                })}
            </ol>
        </section>
    );
}

export default PastorHistorySection;
