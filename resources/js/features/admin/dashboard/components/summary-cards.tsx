import type { LucideIcon } from 'lucide-react';
import { Text } from '@/components/text';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { DashboardSummary } from '../types';
import { buildSummaryCards, type Metric } from './summary-card-config';

type SummaryCardProps = {
    label: string;
    value: number;
    icon: LucideIcon;
    iconClassName: string;
    iconBackgroundClassName: string;
    breakdown: Metric[];
    className?: string;
};

const metricToneClasses: Record<NonNullable<Metric['tone']>, string> = {
    neutral: 'bg-muted-foreground/40',
    success: 'bg-emerald-600',
    warning: 'bg-amber-500',
    danger: 'bg-destructive',
};

function SummaryCard({
    label,
    value,
    icon: Icon,
    iconClassName,
    iconBackgroundClassName,
    breakdown,
    className,
}: SummaryCardProps) {
    return (
        <Card
            className={cn(
                'h-full gap-0 rounded-xl border-border bg-card py-0 shadow-none transition-colors',
                className,
            )}
        >
            <CardContent className="flex h-full flex-col px-4 py-4">
                <div className="flex items-start justify-between gap-3">
                    <Text
                        as="p"
                        variant="label"
                        color="muted"
                        className="min-w-0 leading-5"
                    >
                        {label}
                    </Text>

                    <span
                        className={cn(
                            'flex size-9 shrink-0 items-center justify-center rounded-lg',
                            iconBackgroundClassName,
                        )}
                    >
                        <Icon
                            className={cn('size-4.5', iconClassName)}
                            aria-hidden={true}
                        />
                    </span>
                </div>

                <Text
                    as="p"
                    variant="heading-lg"
                    className="mt-1 text-3xl font-bold tracking-tight text-foreground tabular-nums"
                >
                    {value.toLocaleString('id-ID')}
                </Text>

                <div className="mt-3 flex flex-1 flex-col gap-2">
                    {breakdown.map((metric) => (
                        <div
                            key={metric.label}
                            className="flex min-w-0 items-center gap-2"
                        >
                            <span
                                aria-hidden="true"
                                className={cn(
                                    'size-2 shrink-0 rounded-full',
                                    metricToneClasses[metric.tone ?? 'neutral'],
                                )}
                            />

                            <Text
                                as="span"
                                variant="fine-print"
                                color="muted"
                                className="min-w-0 flex-1 leading-4"
                            >
                                {metric.label}
                            </Text>

                            <Text
                                as="span"
                                variant="fine-print"
                                className="shrink-0 font-semibold text-foreground tabular-nums"
                            >
                                {metric.value.toLocaleString('id-ID')}
                            </Text>
                        </div>
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
    const cards = buildSummaryCards(summary);

    return (
        <section
            aria-label="Ringkasan pengelolaan paroki"
            data-test="dashboard-summary"
            className={cn(
                'grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5',
                className,
            )}
        >
            {cards.map((card) => (
                <SummaryCard
                    key={card.label}
                    label={card.label}
                    value={card.value}
                    icon={card.icon}
                    iconClassName={card.iconClassName}
                    iconBackgroundClassName={card.iconBackgroundClassName}
                    breakdown={card.breakdown}
                />
            ))}
        </section>
    );
}
