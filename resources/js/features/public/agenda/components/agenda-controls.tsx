import { Link, router } from '@inertiajs/react';
import { Text } from '@/components/text';
import { index as agendaIndex } from '@/routes/agenda';
import type { AgendaMode, PublicEventCategory } from '../types';

const MODE_LABEL: Record<AgendaMode, string> = {
    daftar: 'Daftar',
    kalender: 'Kalender',
};

/**
 * A shareable agenda URL.
 *
 * Built from the generated route's path plus a URLSearchParams rather than by
 * passing an object to the route helper. Wayfinder's generated `index()` takes
 * RouteQueryOptions, which is the pagination and sort whitelist and not
 * arbitrary filters, so `agendaIndex({ mode: 'daftar' })` is a type error — and
 * the workarounds are all worse: a hand-written '/agenda?mode=...' string is
 * the thing Wayfinder exists to prevent, and reading window.location in an
 * effect means the first paint is always the unfiltered page.
 *
 * Defaults are omitted rather than sent empty. An empty `kategori=` in a
 * shared link is a link somebody has to look at twice to see is unfiltered.
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
 * List or calendar, a category, and where to go next.
 *
 * The mode switch is a tab pair rather than a toggle because it is navigation
 * between two readings of the same data, and a tab says that; a switch says
 * "a setting changed". The month buttons only appear in calendar mode — in the
 * list they would move a view that is not arranged by month.
 *
 * Every control is a link to a URL, not a client-side state change. That is
 * what makes the month shareable, which is most of the value of a calendar: the
 * parish secretary forwards "the December agenda" as a link.
 */
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
    const tabClass = (value: AgendaMode) =>
        `inline-flex h-11 items-center rounded-md px-4 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none ${
            mode === value
                ? 'bg-primary text-primary-foreground'
                : 'text-muted-foreground hover:text-foreground'
        }`;

    const chipClass = (active: boolean) =>
        `inline-flex h-11 items-center rounded-pill border px-4 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none ${
            active
                ? 'border-primary bg-primary text-primary-foreground'
                : 'border-border hover:border-primary-on-surface'
        }`;

    const monthClass =
        'inline-flex h-11 items-center rounded-md px-3 text-sm text-primary-on-surface underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';

    return (
        <div className="flex flex-col gap-4">
            <div
                role="tablist"
                aria-label="Tampilan agenda"
                className="inline-flex gap-1 self-start rounded-md border border-border p-1"
            >
                {(Object.keys(MODE_LABEL) as AgendaMode[]).map((value) => (
                    <Link
                        key={value}
                        role="tab"
                        aria-selected={mode === value}
                        href={agendaUrl({
                            mode: value,
                            kategori: activeCategory,
                            bulan: month,
                        })}
                        preserveScroll
                        className={tabClass(value)}
                    >
                        {MODE_LABEL[value]}
                    </Link>
                ))}
            </div>

            <div className="flex flex-wrap items-center gap-3">
                {/*
                 * Category chips rather than a <select>. On a phone a select
                 * costs two taps to read and opens a wheel the reader cannot
                 * see the rest of; as chips every option is visible and the
                 * current one is obvious.
                 */}
                <Link
                    href={agendaUrl({ mode, kategori: '', bulan: month })}
                    preserveScroll
                    className={chipClass(activeCategory === '')}
                >
                    Semua
                </Link>

                {categories.map((item) => (
                    <Link
                        key={item.id}
                        href={agendaUrl({
                            mode,
                            kategori: item.slug,
                            bulan: month,
                        })}
                        preserveScroll
                        className={chipClass(activeCategory === item.slug)}
                    >
                        {item.name}
                    </Link>
                ))}
            </div>

            {mode === 'kalender' ? (
                <div className="flex items-center justify-between gap-2">
                    <Link
                        href={agendaUrl({
                            mode,
                            kategori: activeCategory,
                            bulan: previousMonth,
                        })}
                        preserveScroll
                        rel="prev"
                        className={monthClass}
                    >
                        &larr; Bulan sebelumnya
                    </Link>

                    {/*
                     * "Hari Ini" only once the reader has navigated away from
                     * the current month. On the current month it is a control
                     * that does the thing they are already doing.
                     */}
                    {month === todayMonth ? (
                        <Text
                            as="p"
                            variant="label"
                            color="brand"
                            className="px-3"
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
                                    { preserveScroll: true },
                                )
                            }
                            className={monthClass}
                        >
                            {`Kembali ke bulan ini (${today})`}
                        </button>
                    )}

                    <Link
                        href={agendaUrl({
                            mode,
                            kategori: activeCategory,
                            bulan: nextMonth,
                        })}
                        preserveScroll
                        rel="next"
                        className={monthClass}
                    >
                        Bulan berikutnya &rarr;
                    </Link>
                </div>
            ) : null}
        </div>
    );
}
