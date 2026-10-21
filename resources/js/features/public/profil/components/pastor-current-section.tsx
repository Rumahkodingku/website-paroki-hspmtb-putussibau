import { SectionHeading } from '@/components/section-heading';
import { RichText } from '@/components/rich-text';
import { Text } from '@/components/text';
import { Church, CalendarDays, UserRound } from 'lucide-react';
import type { PublicPastor } from '../types';
import { formatPastorName, formatServicePeriod } from './pastor-formatters';

export function PastorCurrentSection({ current }: { current: PublicPastor[] }) {
    return (
        <section aria-labelledby="pastor-bertugas" className="space-y-8">
            <div className="max-w-2xl space-y-2">
                <SectionHeading
                    title="Pastor yang Bertugas"
                    level={2}
                    className="gap-3"
                />

                <Text as="p" variant="body" color="muted">
                    Para imam yang saat ini melayani dan mendampingi kehidupan
                    umat paroki.
                </Text>
            </div>

            {current.length === 0 ? (
                <Text
                    as="p"
                    variant="body"
                    color="muted"
                    className="rounded-xl border border-dashed border-border p-6 sm:p-8"
                >
                    Data pastor yang bertugas belum dipublikasikan.
                </Text>
            ) : (
                <div className="space-y-4 sm:space-y-10">
                    {current.map((pastor) => {
                        const name = formatPastorName(
                            pastor.title,
                            pastor.name,
                        );

                        const period = formatServicePeriod(
                            pastor.start_year,
                            pastor.end_year,
                            pastor.is_current ?? false,
                        );

                        return (
                            <article
                                key={pastor.id}
                                className="grid min-w-0 grid-cols-1 gap-5 lg:items-center lg:gap-6 xl:grid-cols-[200px_minmax(0,1.3fr)_minmax(0,0.8fr)]"
                            >
                                {/* Foto pastor */}
                                <div className="min-w-0">
                                    {pastor.photo ? (
                                        <img
                                            src={pastor.photo}
                                            alt={
                                                pastor.photo_alt ??
                                                `Foto ${name}`
                                            }
                                            loading="lazy"
                                            decoding="async"
                                            className="aspect-video w-full rounded-xl object-cover object-top sm:aspect-16/7 lg:aspect-4/5 lg:max-h-62"
                                        />
                                    ) : (
                                        <div
                                            aria-hidden="true"
                                            className="flex aspect-video w-full items-center justify-center rounded-xl bg-muted lg:aspect-4/5 lg:max-h-52"
                                        >
                                            <UserRound className="size-10 text-muted-foreground" />
                                        </div>
                                    )}
                                </div>

                                {/* Identitas dan biografi */}
                                <div className="flex min-w-0 flex-col gap-3">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-1 text-primary">
                                            <Church className="size-3.5" />

                                            <Text
                                                as="span"
                                                variant="label"
                                                color="danger"
                                                weight="medium"
                                            >
                                                {pastor.position_label}
                                            </Text>
                                        </span>
                                    </div>

                                    <Text
                                        as="h3"
                                        variant="h3"
                                        className="wrap-break-word"
                                    >
                                        {name}
                                    </Text>

                                    <div className="flex items-center gap-2">
                                        <CalendarDays className="size-4 shrink-0 text-primary" />

                                        <Text
                                            as="p"
                                            variant="body-sm"
                                            color="muted"
                                        >
                                            Periode pelayanan: {period}
                                        </Text>
                                    </div>

                                    {pastor.bio && (
                                        <div className="border-t border-border pt-3">
                                            <div className="mb-2 flex items-center gap-2">
                                                <UserRound className="size-4 text-primary" />

                                                <Text
                                                    as="p"
                                                    variant="label"
                                                    color="muted"
                                                >
                                                    Biografi
                                                </Text>
                                            </div>

                                            <RichText
                                                html={pastor.bio}
                                                className="max-w-none"
                                            />
                                        </div>
                                    )}
                                </div>
                            </article>
                        );
                    })}
                </div>
            )}
        </section>
    );
}

export default PastorCurrentSection;
