import { Head, router } from '@inertiajs/react';
import { PublicSection } from '@/components/public-section';
import PublicHeroBanner from '@/components/public-hero-banner';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { ListPagination } from '@/components/list-pagination';
import { index as galeriIndex } from '@/routes/galeri';
import { AlbumCard } from '../components/album-card';
import type { PublicGalleryCard, PublicGalleryFilters } from '../types';
import type { Paginated } from '@/types';
import { home } from '@/routes';

type Props = {
    albums: Paginated<PublicGalleryCard>;
    years: number[];
    filters: PublicGalleryFilters;
};

/**
 * The public gallery directory.
 *
 * Only published albums reach this page: the controller starts every query from
 * GalleryAlbum::published(), so a draft is not filtered out here, it never
 * arrived. The year filter lives in the query string, so a link to one year can
 * be shared.
 */
export default function GaleriIndex({ albums, years, filters }: Props) {
    const isFiltered = filters.tahun !== '';

    const applyYear = (tahun: string) => {
        router.get(
            galeriIndex().url,
            { tahun },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    return (
        <>
            <Seo
                title="Galeri"
                description="Dokumentasi foto kegiatan Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau."
            />

            <Head title="Galeri" />

            <PublicHeroBanner
                eyebrow="Galeri"
                title="Galeri Kegiatan"
                description="Dokumentasi foto kegiatan paroki."
                breadcrumbs={[
                    { title: 'Beranda', href: home() },
                    { title: 'Galeri Kegiatan' },
                ]}
            />

            <PublicSection>
                <div className="space-y-10">
                    {years.length > 0 ? (
                        <div className="flex flex-wrap items-center gap-3">
                            <label
                                htmlFor="gallery-year"
                                className="text-sm text-muted-foreground"
                            >
                                Tahun
                            </label>
                            <select
                                id="gallery-year"
                                value={filters.tahun}
                                onChange={(event: any) =>
                                    applyYear(event.target.value)
                                }
                                className="h-11 rounded-md border border-border bg-background px-3 text-sm focus-visible:border-primary"
                            >
                                <option value="">Semua tahun</option>
                                {years.map((year) => (
                                    <option key={year} value={String(year)}>
                                        {year}
                                    </option>
                                ))}
                            </select>
                        </div>
                    ) : null}

                    {albums.data.length > 0 ? (
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {albums.data.map((album) => (
                                <AlbumCard key={album.id} album={album} />
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-lg border border-dashed border-border p-10 text-center">
                            <Text as="p" variant="heading-sm">
                                {isFiltered
                                    ? 'Tidak ada album pada tahun ini.'
                                    : 'Belum ada album yang dipublikasikan.'}
                            </Text>
                            <Text
                                as="p"
                                variant="body-sm"
                                color="muted"
                                className="mt-1"
                            >
                                {isFiltered
                                    ? 'Pilih tahun lain atau lihat semua album.'
                                    : 'Album galeri akan muncul di sini setelah dipublikasikan.'}
                            </Text>
                        </div>
                    )}

                    {albums.meta.last_page > 1 ? (
                        <ListPagination
                            meta={albums.meta}
                            previous={albums.links.prev}
                            next={albums.links.next}
                        />
                    ) : null}
                </div>
            </PublicSection>
        </>
    );
}
