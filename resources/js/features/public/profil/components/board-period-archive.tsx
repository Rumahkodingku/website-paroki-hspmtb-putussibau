import { SectionHeading } from '@/components/section-heading';
import { Text } from '@/components/text';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Archive,
    CalendarDays,
    CheckCircle2,
    ChevronDown,
    History,
    Users,
} from 'lucide-react';
import { OrganizationChart } from './organization-chart';
import { formatPeriod, sortMembers } from './struktur-utils';
import type { PublicBoardPeriod } from '../types';

export function BoardPeriodArchive({
    periods,
}: {
    periods: PublicBoardPeriod[];
}) {
    if (periods.length === 0) {
        return null;
    }

    return (
        <section
            aria-labelledby="arsip-periode"
            className="space-y-7 border-t border-border-subtle pt-10 sm:pt-12"
        >
            <div className="space-y-3">
                <SectionHeading
                    title="Riwayat Kepengurusan"
                    description="Dokumentasi kepengurusan sebelumnya dari masa ke masa."
                    level={2}
                    className="gap-3 sm:gap-4"
                />
            </div>

            <div className="space-y-4">
                {periods.map((period) => {
                    const members = sortMembers(period.members);

                    return (
                        <Collapsible key={period.id} defaultOpen={false}>
                            <div className="overflow-hidden">
                                <CollapsibleTrigger className="group flex w-full items-center justify-between gap-4 p-4 text-left transition-colors hover:bg-muted/40 focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none focus-visible:ring-inset sm:p-5">
                                    <div className="flex min-w-0 items-start gap-3 sm:gap-4">
                                        <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-muted text-muted-foreground sm:size-12">
                                            <Archive
                                                className="size-5"
                                                aria-hidden="true"
                                            />
                                        </span>

                                        <div className="min-w-0 space-y-2">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Text
                                                    as="span"
                                                    variant="h4"
                                                    className="wrap-break-word"
                                                >
                                                    {period.name}
                                                </Text>

                                                <span className="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-1">
                                                    <History
                                                        className="size-3"
                                                        aria-hidden="true"
                                                    />

                                                    <Text
                                                        as="span"
                                                        variant="label"
                                                        color="muted"
                                                    >
                                                        Telah berakhir
                                                    </Text>
                                                </span>
                                            </div>

                                            <div className="flex flex-wrap items-center gap-x-4 gap-y-2">
                                                <span className="inline-flex items-center gap-2">
                                                    <CalendarDays
                                                        className="size-4 text-primary"
                                                        aria-hidden="true"
                                                    />

                                                    <Text
                                                        as="span"
                                                        variant="body-sm"
                                                        color="muted"
                                                    >
                                                        {formatPeriod(period)}
                                                    </Text>
                                                </span>

                                                <span className="inline-flex items-center gap-2">
                                                    <Users
                                                        className="size-4 text-muted-foreground"
                                                        aria-hidden="true"
                                                    />

                                                    <Text
                                                        as="span"
                                                        variant="body-sm"
                                                        color="muted"
                                                    >
                                                        {members.length}{' '}
                                                        pengurus
                                                    </Text>
                                                </span>
                                            </div>

                                            {period.description && (
                                                <Text
                                                    as="p"
                                                    variant="body-sm"
                                                    color="muted"
                                                    className="max-w-2xl"
                                                >
                                                    {period.description}
                                                </Text>
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground transition-colors group-hover:bg-primary/10 group-hover:text-primary">
                                        <ChevronDown
                                            className="size-4 transition-transform duration-200 group-data-[state=open]:rotate-180"
                                            aria-hidden="true"
                                        />
                                    </div>
                                </CollapsibleTrigger>

                                <CollapsibleContent>
                                    <div className="border-t border-border p-4 sm:p-5">
                                        <div className="mb-5 flex items-center gap-2">
                                            <CheckCircle2
                                                className="size-4 text-muted-foreground"
                                                aria-hidden="true"
                                            />

                                            <Text
                                                as="p"
                                                variant="label"
                                                color="muted"
                                            >
                                                Pengurus periode{' '}
                                                {formatPeriod(period)}
                                            </Text>
                                        </div>

                                        <OrganizationChart members={members} />
                                    </div>
                                </CollapsibleContent>
                            </div>
                        </Collapsible>
                    );
                })}
            </div>
        </section>
    );
}

export default BoardPeriodArchive;
