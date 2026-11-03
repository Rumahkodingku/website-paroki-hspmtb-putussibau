import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { CalendarDays, MapPin, Youtube } from 'lucide-react';
import { ListPagination } from '@/components/list-pagination';
import {
    FilterSelect,
    ListEmpty,
    ListToolbar,
    ResetFilters,
} from '@/components/list-parts';
import { LivestreamPlayer } from '@/components/livestream-player';
import PublicHeroBanner from '@/components/public-hero-banner';
import { PublicSection } from '@/components/public-section';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatWibDateTime } from '@/lib/datetime';
import { home } from '@/routes';
import { index as livestreamingIndex } from '@/routes/livestreaming';
import type { Paginated, SharedProps } from '@/types';
import { LivestreamArchiveCard } from '../components/livestream-archive-card';
import type {
    LivestreamArchiveFilters,
    LivestreamCategoryOption,
    PublicLivestream,
} from '../types';

type Props = {
    activeStream: PublicLivestream | null;
    archives: Paginated<PublicLivestream>;
    years: number[];
    categories: LivestreamCategoryOption[];
    filters: LivestreamArchiveFilters;
};

/**
 * The public livestreaming page.
 *
 * Only published recordings arrive: PublicLivestreamController starts every
 * query from published()->archiveReady(). The active stream is the same
 * Livestream::currentLive() the homepage uses, so the two pages cannot disagree.
 *
 * No iframe is mounted for the grid. The active player and a card's modal load
 * YouTube only after the visitor asks for it.
 */
export default function LivestreamingIndex({
    activeStream,
    archives,
    years,
    categories,
    filters,
}: Props) {
    const { displayTimezone } = usePage<SharedProps>().props;
    const [selected, setSelected] = useState<PublicLivestream | null>(null);

    const hasFilter =
        filters.search !== '' ||
        filters.kategori !== '' ||
        filters.tahun !== '';

    const applyFilter = (next: Partial<LivestreamArchiveFilters>) => {
        router.get(
            livestreamingIndex().url,
            { ...filters, ...next, page: 1 },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const reset = () => applyFilter({ search: '', kategori: '', tahun: '' });

    const activeDate = activeStream?.started_at ?? activeStream?.scheduled_at;
    const selectedDate = selected?.recorded_at ?? selected?.started_at;

    return (
        <>
            <Seo />
            <PublicHeroBanner
                eyebrow="LIVESTREAMING"
                title="Arsip Livestreaming"
                description="Saksikan kembali perayaan Ekaristi dan kegiatan rohani Paroki HSPMTB Putussibau."
                breadcrumbs={[
                    { title: 'Beranda', href: home() },
                    { title: 'Livestreaming' },
                ]}
            />

            <PublicSection>
                <div className="space-y-12 md:space-y-16">
                    {activeStream ? (
                        <section
                            aria-labelledby="livestream-live-title"
                            className="overflow-hidden rounded-2xl border border-border"
                        >
                            <div className="grid lg:grid-cols-[1.65fr_1fr]">
                                <div className="min-w-0 bg-black">
                                    <LivestreamPlayer
                                        stream={activeStream}
                                        className="rounded-none border-0"
                                    />
                                </div>

                                <div className="flex min-w-0 flex-col gap-5 bg-canvas-subtle p-6 sm:p-8">
                                    <div className="flex flex-wrap items-center gap-3">
                                        <Badge>SEDANG LIVE</Badge>
                                        <Text
                                            as="p"
                                            variant="body-sm"
                                            color="muted"
                                        >
                                            Siaran langsung
                                        </Text>
                                    </div>

                                    <Text
                                        id="livestream-live-title"
                                        as="h2"
                                        variant="h2"
                                        className="text-balance"
                                    >
                                        {activeStream.title}
                                    </Text>

                                    <div className="space-y-2">
                                        {activeDate ? (
                                            <p className="flex items-center gap-2 text-sm text-muted-foreground">
                                                <CalendarDays
                                                    className="size-4 shrink-0"
                                                    aria-hidden="true"
                                                />
                                                {formatWibDateTime(
                                                    activeDate,
                                                    displayTimezone,
                                                )}
                                            </p>
                                        ) : null}

                                        {activeStream.location ? (
                                            <p className="flex items-center gap-2 text-sm text-muted-foreground">
                                                <MapPin
                                                    className="size-4 shrink-0"
                                                    aria-hidden="true"
                                                />
                                                {activeStream.location}
                                            </p>
                                        ) : null}
                                    </div>

                                    {activeStream.description ? (
                                        <Text
                                            as="p"
                                            variant="body"
                                            color="muted"
                                        >
                                            {activeStream.description}
                                        </Text>
                                    ) : null}

                                    <div className="mt-auto flex flex-wrap gap-3 pt-2">
                                        {activeStream.watch_url ? (
                                            <Button asChild>
                                                <a
                                                    href={
                                                        activeStream.watch_url
                                                    }
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    <Youtube
                                                        className="size-4"
                                                        aria-hidden="true"
                                                    />
                                                    Tonton di YouTube
                                                </a>
                                            </Button>
                                        ) : null}
                                    </div>
                                </div>
                            </div>
                        </section>
                    ) : null}

                    <section
                        id="arsip"
                        aria-labelledby="livestream-archive-title"
                        className="scroll-mt-24 space-y-6"
                    >
                        <div className="space-y-2">
                            <Text
                                id="livestream-archive-title"
                                as="h2"
                                variant="h2"
                            >
                                Rekaman Sebelumnya
                            </Text>
                            <Text as="p" variant="body" color="muted">
                                Telusuri rekaman perayaan Ekaristi dan kegiatan
                                paroki yang sudah berlangsung.
                            </Text>
                        </div>

                        <ListToolbar
                            search={filters.search}
                            onSearch={(search) => applyFilter({ search })}
                            searchPlaceholder="Cari rekaman..."
                        >
                            <FilterSelect
                                id="filter-livestream-category"
                                label="Filter kategori"
                                value={filters.kategori}
                                onChange={(kategori) =>
                                    applyFilter({ kategori })
                                }
                                options={[
                                    {
                                        value: '',
                                        label: 'Semua Kategori',
                                    },
                                    ...categories,
                                ]}
                            />

                            <FilterSelect
                                id="filter-livestream-year"
                                label="Filter tahun"
                                value={filters.tahun}
                                onChange={(tahun) => applyFilter({ tahun })}
                                options={[
                                    { value: '', label: 'Semua Tahun' },
                                    ...years.map((year) => ({
                                        value: String(year),
                                        label: String(year),
                                    })),
                                ]}
                            />

                            {hasFilter ? (
                                <ResetFilters onReset={reset} />
                            ) : null}
                        </ListToolbar>

                        {archives.data.length > 0 ? (
                            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {archives.data.map((stream) => (
                                    <LivestreamArchiveCard
                                        key={stream.id}
                                        stream={stream}
                                        onPlay={() => setSelected(stream)}
                                    />
                                ))}
                            </div>
                        ) : (
                            <ListEmpty
                                title={
                                    hasFilter
                                        ? 'Tidak ada rekaman yang sesuai dengan pencarian.'
                                        : 'Belum ada rekaman livestreaming.'
                                }
                                description={
                                    hasFilter
                                        ? 'Coba kata kunci lain atau atur ulang filter.'
                                        : 'Rekaman akan muncul setelah siaran berakhir dan admin menandai replay tersedia.'
                                }
                            />
                        )}

                        {archives.meta.last_page > 1 ? (
                            <ListPagination
                                meta={archives.meta}
                                previous={archives.links.prev}
                                next={archives.links.next}
                            />
                        ) : null}
                    </section>
                </div>
            </PublicSection>

            <Dialog
                open={selected !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setSelected(null);
                    }
                }}
            >
                <DialogContent className="overflow-hidden p-0 sm:max-w-4xl">
                    {selected ? (
                        <div className="space-y-4 p-5 sm:p-6">
                            <DialogHeader>
                                <DialogTitle>{selected.title}</DialogTitle>
                                <DialogDescription>
                                    {[
                                        selectedDate
                                            ? formatWibDateTime(
                                                  selectedDate,
                                                  displayTimezone,
                                              )
                                            : null,
                                        selected.category_label,
                                        selected.location,
                                    ]
                                        .filter(Boolean)
                                        .join(' · ')}
                                </DialogDescription>
                            </DialogHeader>

                            <LivestreamPlayer stream={selected} autoLoad />

                            <div className="flex flex-wrap items-start justify-between gap-3">
                                {selected.description ? (
                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                        className="max-w-2xl"
                                    >
                                        {selected.description}
                                    </Text>
                                ) : (
                                    <span />
                                )}

                                {selected.watch_url ? (
                                    <Button variant="outline" size="sm" asChild>
                                        <a
                                            href={selected.watch_url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <Youtube
                                                className="size-4"
                                                aria-hidden="true"
                                            />
                                            Buka di YouTube
                                        </a>
                                    </Button>
                                ) : null}
                            </div>
                        </div>
                    ) : null}
                </DialogContent>
            </Dialog>
        </>
    );
}
