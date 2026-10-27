import {
    CalendarDays,
    CheckCircle2,
    ChevronDown,
    ClipboardList,
} from 'lucide-react';
import { PublicSection } from '@/components/public-section';
import { Text } from '@/components/text';
import { RequirementList } from '../requirement-list';
import { StepsTimeline } from '../steps-timeline';
import { ScheduleAndFee } from '../schedule-and-fee';
import type { PublicServiceDetail } from '../../types';

export function RequirementsProcedureSection({
    service,
}: {
    service: PublicServiceDetail;
}) {
    if (!(
        service.requirements.length > 0 ||
        service.steps.length > 0 ||
        service.schedule_info ||
        service.fee_info
    )) {
        return null;
    }

    return (
        <PublicSection
            id="requirements"
            className="scroll-mt-20 py-10 sm:py-12 lg:py-16"
        >
            <div className="space-y-3 lg:hidden">
                {service.requirements.length > 0 ? (
                    <details className="group overflow-hidden">
                        <summary className="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset [&::-webkit-details-marker]:hidden">
                            <span className="flex items-center gap-3">
                                <CheckCircle2
                                    className="size-5 shrink-0 text-primary"
                                    aria-hidden="true"
                                />
                                <span className="font-semibold">
                                    Syarat Dokumen
                                </span>
                            </span>
                            <ChevronDown
                                className="size-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-180"
                                aria-hidden="true"
                            />
                        </summary>
                        <div className="border-t border-border px-4 pt-1 pb-5">
                            <Text
                                as="p"
                                variant="body-sm"
                                color="muted"
                                className="mt-4"
                            >
                                Persiapkan dokumen sesuai ketentuan paroki.
                            </Text>
                            <RequirementList
                                requirements={service.requirements}
                            />
                        </div>
                    </details>
                ) : null}

                {service.steps.length > 0 ? (
                    <details className="group overflow-hidden">
                        <summary className="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset [&::-webkit-details-marker]:hidden">
                            <span className="flex items-center gap-3">
                                <ClipboardList
                                    className="size-5 shrink-0 text-primary"
                                    aria-hidden="true"
                                />
                                <span className="font-semibold">
                                    Alur Pendaftaran
                                </span>
                            </span>
                            <ChevronDown
                                className="size-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-180"
                                aria-hidden="true"
                            />
                        </summary>
                        <div className="border-t border-border px-4 pt-1 pb-5">
                            <StepsTimeline steps={service.steps} />
                        </div>
                    </details>
                ) : null}

                {service.schedule_info || service.fee_info ? (
                    <details className="group overflow-hidden rounded-xl border border-border bg-background">
                        <summary className="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset [&::-webkit-details-marker]:hidden">
                            <span className="flex items-center gap-3">
                                <CalendarDays
                                    className="size-5 shrink-0 text-primary"
                                    aria-hidden="true"
                                />
                                <span className="font-semibold">
                                    Jadwal dan Informasi Biaya
                                </span>
                            </span>
                            <ChevronDown
                                className="size-4 shrink-0 text-muted-foreground transition-transform group-open:rotate-180"
                                aria-hidden="true"
                            />
                        </summary>
                        <div className="border-t border-border p-4">
                            <ScheduleAndFee service={service} />
                        </div>
                    </details>
                ) : null}
            </div>

            {service.requirements.length > 0 || service.steps.length > 0 ? (
                <div className="hidden lg:grid lg:grid-cols-2 lg:gap-10">
                    {service.requirements.length > 0 ? (
                        <section
                            aria-labelledby="requirements-title"
                            className="min-w-0 p-6 xl:p-8"
                        >
                            <div className="flex items-center gap-3">
                                <div className="flex size-10 items-center justify-center rounded-xl bg-primary/5 text-primary">
                                    <CheckCircle2
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                </div>
                                <div>
                                    <Text
                                        as="h2"
                                        variant="display-md"
                                        className="text-2xl"
                                        id="requirements-title"
                                        weight="semibold"
                                    >
                                        Syarat Dokumen
                                    </Text>
                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                        className="mt-1"
                                    >
                                        Persiapkan dokumen sesuai ketentuan
                                        paroki.
                                    </Text>
                                </div>
                            </div>
                            <RequirementList
                                requirements={service.requirements}
                            />
                        </section>
                    ) : null}

                    {service.steps.length > 0 ? (
                        <section
                            aria-labelledby="steps-title"
                            className="min-w-0 p-6 xl:p-8"
                        >
                            <div className="flex items-center gap-3">
                                <div className="flex size-10 items-center justify-center rounded-xl bg-background text-primary">
                                    <ClipboardList
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                </div>
                                <div>
                                    <Text
                                        as="h2"
                                        variant="display-md"
                                        className="text-2xl"
                                        id="steps-title"
                                        weight="semibold"
                                    >
                                        Alur Pendaftaran
                                    </Text>
                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                        className="mt-1"
                                    >
                                        Tahapan untuk mempersiapkan{' '}
                                        {service.title}.
                                    </Text>
                                </div>
                            </div>
                            <StepsTimeline steps={service.steps} />
                        </section>
                    ) : null}
                </div>
            ) : null}
        </PublicSection>
    );
}
