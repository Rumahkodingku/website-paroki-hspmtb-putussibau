import { Link, router } from '@inertiajs/react';
import {
    CalendarDays,
    Check,
    ChevronLeft,
    ChevronRight,
    List,
} from 'lucide-react';

import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { index as agendaIndex } from '@/routes/agenda';

import type { AgendaMode, PublicEventCategory } from '../types';

const MODE_LABEL: Record<AgendaMode, string> = {
    daftar: 'Daftar',
    kalender: 'Kalender',
};

/**
 * Build a shareable agenda URL while omitting default and empty parameters.
 */
function agendaUrl(params: {
    mode: AgendaMode;
    kategori: string;
    bulan: string;
}): string {
    const query = new URLSearchParams();

    if (params.mode !== 'daftar') {
        query.set('mode', params.mode);
    }

    if (params.kategori !== '') {
        query.set('kategori', params.kategori);
    }

    if (params.bulan !== '') {
        query.set('bulan', params.bulan);
    }

    const search = query.toString();

    return search === '' ? agendaIndex().url : `${agendaIndex().url}?${search}`;
}

/**
 * Format a YYYY-MM value as an Indonesian month and year.
 * UTC prevents the first day of a month from shifting across timezones.
 */
function formatMonthLabel(month: string): string {
    return new Intl.DateTimeFormat('id-ID', {
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(`${month}-01T12:00:00Z`));
}

export function AgendaControls({
    mode,
    month,
    categories,
    today,
    todayMonth,
    previousMonth,
    nextMonth,
    activeCategory,
}: {
    mode: AgendaMode;
    month: string;
    categories: PublicEventCategory[];
    today: string;
    todayMonth: string;
    previousMonth: string;
    nextMonth: string;
    activeCategory: string;
}) {
    /**
     * Category links use the shared design tokens while retaining
     * Inertia navigation and shareable filter URLs.
     */
    const categoryLinkClass = (active: boolean) =>
        cn(
            'inline-flex min-h-10 shrink-0 items-center gap-2 rounded-full border px-3.5 text-sm font-medium transition-colors',
            'focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none',
            active
                ? 'border-primary bg-primary text-primary-foreground'
                : 'border-border bg-background text-foreground hover:border-primary/50 hover:bg-muted',
        );

    return (
        <section
            aria-label="Filter dan navigasi agenda"
            className="mb-12 space-y-4"
        >
            {/* View selector */}
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div className="min-w-0 space-y-2">
                    <Text
                        as="p"
                        variant="label"
                        color="muted"
                        className="text-xs font-semibold tracking-wider uppercase"
                    >
                        Tampilan agenda
                    </Text>

                    <Tabs value={mode} className="w-full sm:w-fit">
                        <TabsList
                            aria-label="Tampilan agenda"
                            className="h-11 w-full justify-start gap-4"
                        >
                            <TabsTrigger
                                value="daftar"
                                asChild
                                className="h-9 gap-2 rounded-md px-4 data-[state=active]:bg-primary data-[state=active]:text-primary-foreground"
                            >
                                <Link
                                    href={agendaUrl({
                                        mode: 'daftar',
                                        kategori: activeCategory,
                                        bulan: month,
                                    })}
                                    preserveScroll
                                    aria-current={
                                        mode === 'daftar' ? 'page' : undefined
                                    }
                                >
                                    <List
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                    {MODE_LABEL.daftar}
                                </Link>
                            </TabsTrigger>

                            <TabsTrigger
                                value="kalender"
                                asChild
                                className="h-9 gap-2 rounded-md px-4 data-[state=active]:bg-primary data-[state=active]:text-primary-foreground"
                            >
                                <Link
                                    href={agendaUrl({
                                        mode: 'kalender',
                                        kategori: activeCategory,
                                        bulan: month,
                                    })}
                                    preserveScroll
                                    aria-current={
                                        mode === 'kalender' ? 'page' : undefined
                                    }
                                >
                                    <CalendarDays
                                        aria-hidden="true"
                                        className="size-4"
                                    />
                                    {MODE_LABEL.kalender}
                                </Link>
                            </TabsTrigger>
                        </TabsList>
                    </Tabs>
                </div>
            </div>

            <Separator />

            {/* Category filters */}
            <div className="space-y-2.5">
                <Text
                    as="p"
                    variant="label"
                    color="muted"
                    className="text-xs font-semibold tracking-wider uppercase"
                >
                    Filter kategori
                </Text>

                <div
                    aria-label="Filter kategori agenda"
                    className="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0 sm:pb-0"
                >
                    <Link
                        href={agendaUrl({
                            mode,
                            kategori: '',
                            bulan: month,
                        })}
                        preserveScroll
                        aria-current={
                            activeCategory === '' ? 'page' : undefined
                        }
                        className={categoryLinkClass(activeCategory === '')}
                    >
                        {activeCategory === '' && (
                            <Check aria-hidden="true" className="size-3.5" />
                        )}
                        Semua
                    </Link>

                    {categories.map((item: PublicEventCategory) => {
                        const active = activeCategory === item.slug;

                        return (
                            <Link
                                key={item.id}
                                href={agendaUrl({
                                    mode,
                                    kategori: item.slug,
                                    bulan: month,
                                })}
                                preserveScroll
                                aria-current={active ? 'page' : undefined}
                                className={categoryLinkClass(active)}
                            >
                                {item.name}

                                {active && (
                                    <Check
                                        aria-hidden="true"
                                        className="size-3.5"
                                    />
                                )}
                            </Link>
                        );
                    })}
                </div>
            </div>

            {/* Calendar month navigation */}
            {mode === 'kalender' && (
                <>
                    <Separator />

                    <nav
                        aria-label="Navigasi bulan kalender"
                        className="flex items-center justify-between gap-3"
                    >
                        <Button
                            variant="outline"
                            size="icon"
                            asChild
                            aria-label="Bulan sebelumnya"
                            title="Bulan sebelumnya"
                        >
                            <Link
                                href={agendaUrl({
                                    mode,
                                    kategori: activeCategory,
                                    bulan: previousMonth,
                                })}
                                preserveScroll
                                rel="prev"
                            >
                                <ChevronLeft aria-hidden="true" />
                            </Link>
                        </Button>

                        <div className="flex min-w-0 flex-col items-center gap-1 text-center">
                            <Text
                                as="p"
                                variant="label"
                                className="truncate font-semibold"
                            >
                                {formatMonthLabel(month)}
                            </Text>

                            {month === todayMonth ? (
                                <Text
                                    as="span"
                                    variant="fine-print"
                                    color="brand"
                                >
                                    Bulan ini
                                </Text>
                            ) : (
                                <button
                                    type="button"
                                    onClick={() =>
                                        router.get(
                                            agendaUrl({
                                                mode,
                                                kategori: activeCategory,
                                                bulan: todayMonth,
                                            }),
                                            {},
                                            {
                                                preserveScroll: true,
                                            },
                                        )
                                    }
                                    className="rounded-sm text-xs font-medium text-primary-on-surface underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                >
                                    Kembali ke bulan ini
                                    <span className="sr-only">
                                        , tanggal {today}
                                    </span>
                                </button>
                            )}
                        </div>

                        <Button
                            variant="outline"
                            size="icon"
                            asChild
                            aria-label="Bulan berikutnya"
                            title="Bulan berikutnya"
                        >
                            <Link
                                href={agendaUrl({
                                    mode,
                                    kategori: activeCategory,
                                    bulan: nextMonth,
                                })}
                                preserveScroll
                                rel="next"
                            >
                                <ChevronRight aria-hidden="true" />
                            </Link>
                        </Button>
                    </nav>
                </>
            )}
        </section>
    );
}
