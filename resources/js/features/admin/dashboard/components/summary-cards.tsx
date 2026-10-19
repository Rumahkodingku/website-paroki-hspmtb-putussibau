import {
    CalendarClock,
    CalendarDays,
    FileText,
    Globe,
    HandHeart,
    type LucideIcon,
} from 'lucide-react';
import { Text } from '@/components/text';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { DashboardSummary } from '../types';

/*
 * The five summary tiles, one per content module.
 *
 * Each tile's large number is the one figure the module is judged by — the
 * public count for articles, the upcoming count for activities, the total for
 * documents — and the status split is a line of small figures underneath. The
 * split is text, never a colour-only signal.
 *
 * The expired-document figure is tinted only when it is above zero, because a
 * zero that shouts is a false alarm.
 */

type Metric = {
    label: string;
    value: number;
    danger?: boolean;
};

function SummaryCard({
    label,
    value,
    icon: Icon,
    breakdown,
}: {
    label: string;
    value: number;
    icon: LucideIcon;
    breakdown: Metric[];
}) {
    return (
        <Card className="gap-0 py-4">
            <CardContent className="px-4">
                <div className="flex items-start justify-between gap-3">
                    <Text as="p" variant="label" color="muted">
                        {label}
                    </Text>
                    <Icon
                        className="size-4 shrink-0 text-subtle"
                        aria-hidden="true"
                    />
                </div>

                <Text as="p" variant="heading-lg" className="mt-2 tabular-nums">
                    {value}
                </Text>

                <div className="mt-2 flex flex-wrap gap-x-3 gap-y-1">
                    {breakdown.map((metric) => (
                        <Text
                            key={metric.label}
                            as="span"
                            variant="fine-print"
                            color={metric.danger ? 'danger' : 'muted'}
                        >
                            {metric.label} {metric.value}
                        </Text>
                    ))}
                </div>
            </CardContent>
        </Card>
    );
}

export function SummaryCards({
    summary,
    className,
}: {
    summary: DashboardSummary;
    className?: string;
}) {
    const { posts, events, documents, massSchedules, services } = summary;

    return (
        <div
            data-test="dashboard-summary"
            className={cn(
                'grid gap-4 sm:grid-cols-2 lg:grid-cols-5',
                className,
            )}
        >
            <SummaryCard
                label="Berita"
                value={posts.published}
                icon={Globe}
                breakdown={[
                    { label: 'Draf', value: posts.draft },
                    { label: 'Terjadwal', value: posts.scheduled },
                    { label: 'Terbit', value: posts.published },
                    { label: 'Arsip', value: posts.archived },
                ]}
            />
            <SummaryCard
                label="Agenda mendatang"
                value={events.upcoming}
                icon={CalendarClock}
                breakdown={[
                    { label: 'Draf', value: events.draft },
                    { label: 'Dibatalkan', value: events.cancelled },
                    { label: 'Ditunda', value: events.postponed },
                ]}
            />
            <SummaryCard
                label="Dokumen"
                value={documents.total}
                icon={FileText}
                breakdown={[
                    { label: 'Draf', value: documents.draft },
                    { label: 'Terbit', value: documents.published },
                    { label: 'Arsip', value: documents.archived },
                    {
                        label: 'Kedaluwarsa',
                        value: documents.expired,
                        danger: documents.expired > 0,
                    },
                ]}
            />
            <SummaryCard
                label="Misa rutin aktif"
                value={massSchedules.regular}
                icon={CalendarDays}
                breakdown={[
                    {
                        label: 'Misa khusus mendatang',
                        value: massSchedules.special,
                    },
                    {
                        label: 'Pemberitahuan aktif',
                        value: massSchedules.notices,
                    },
                ]}
            />
            <SummaryCard
                label="Pelayanan aktif"
                value={services.active}
                icon={HandHeart}
                breakdown={[{ label: 'Nonaktif', value: services.inactive }]}
            />
        </div>
    );
}
