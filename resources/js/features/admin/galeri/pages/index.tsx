import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Images } from 'lucide-react';
import { DataTable, type DataColumn } from '@/components/data-table';
import { DeleteDialog } from '@/components/delete-dialog';
import {
    FilterSelect,
    ListEmpty,
    ListHeader,
    ListToolbar,
    ResetFilters,
} from '@/components/list-parts';
import { ListPagination } from '@/components/list-pagination';
import { RowActions } from '@/components/row-actions';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { dashboard } from '@/routes';
import {
    create as createAlbum,
    destroy as destroyAlbum,
    edit as editAlbum,
    index as galleriesIndex,
    photos as albumPhotos,
} from '@/routes/galleries';
import { GalleryPublicationBadge } from '../components/gallery-status-badge';
import type { GalleryAlbumRow } from '../types';
import type { Paginated } from '@/types';
import { formatWibDate, formatWibDateTime } from '@/lib/datetime';

type Props = {
    albums: Paginated<GalleryAlbumRow>;
    years: number[];
    filters: {
        search: string;
        status: string;
        tahun: string;
        sort: string;
        direction: string;
    };
    copy: Record<string, string>;
    empty: Record<string, string>;
};

export default function Index({ albums, years, filters, empty }: Props) {
    const [deleting, setDeleting] = useState<GalleryAlbumRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const applyFilter = (next: Partial<typeof filters>) => {
        router.get(
            galleriesIndex().url,
            { ...filters, ...next, page: 1 },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const sortBy = (sort: string, direction: 'asc' | 'desc') => {
        router.get(
            galleriesIndex().url,
            { ...filters, sort, direction, page: 1 },
            { preserveScroll: true, replace: true },
        );
    };

    const hasFilter =
        filters.search !== '' || filters.status !== '' || filters.tahun !== '';

    const reset = () => applyFilter({ search: '', status: '', tahun: '' });

    const columns: DataColumn<GalleryAlbumRow>[] = [
        {
            id: 'no',
            header: 'No',
            cell: ({ row }) => row.index + 1,
        },
        {
            id: 'title',
            accessorKey: 'title',
            header: 'Judul Album',
            sortKey: 'title',
            cell: ({ row }) => (
                <div className="flex items-center gap-3">
                    {row.original.cover_url ? (
                        <img
                            src={row.original.cover_url}
                            alt=""
                            className="size-12 shrink-0 rounded-md object-cover"
                        />
                    ) : (
                        <span
                            aria-hidden="true"
                            className="flex size-12 shrink-0 items-center justify-center rounded-md border border-dashed border-border text-muted-foreground"
                        >
                            <Images className="size-4" />
                        </span>
                    )}
                    <div className="space-y-0.5">
                        <Text as="p" variant="label">
                            {row.original.title}
                        </Text>
                        <Text as="p" variant="body-sm" color="muted">
                            {row.original.slug}
                        </Text>
                    </div>
                </div>
            ),
        },
        {
            id: 'event_date',
            accessorKey: 'event_date',
            header: 'Tanggal Kegiatan',
            sortKey: 'event_date',
            cell: ({ row }) =>
                row.original.event_date
                    ? formatWibDate(row.original.event_date)
                    : '—',
        },
        {
            id: 'photo_count',
            header: 'Jumlah Foto',
            cell: ({ row }) => `${row.original.photo_count} foto`,
        },
        {
            id: 'is_published',
            accessorKey: 'is_published',
            header: 'Status',
            sortKey: 'is_published',
            cell: ({ row }) => (
                <GalleryPublicationBadge
                    published={row.original.is_published}
                />
            ),
        },
        {
            id: 'updated_at',
            accessorKey: 'updated_at',
            header: 'Terakhir Diperbarui',
            sortKey: 'updated_at',
            cell: ({ row }) => formatWibDateTime(row.original.updated_at),
        },
        {
            id: 'actions',
            header: 'Aksi',
            cell: ({ row }) => (
                <div className="flex items-center gap-4">
                    <Tooltip>
                        <TooltipTrigger asChild>
                            <Link
                                href={albumPhotos(row.original.id)}
                                aria-label="Kelola foto"
                                className="cursor-pointer rounded-sm text-primary-on-surface hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <Images className="size-4" aria-hidden="true" />
                            </Link>
                        </TooltipTrigger>

                        <TooltipContent>
                            <p>Kelola foto</p>
                        </TooltipContent>
                    </Tooltip>

                    <RowActions
                        canUpdate={row.original.can.update}
                        canDelete={row.original.can.delete}
                        updateHref={editAlbum(row.original.id)}
                        onDelete={() => setDeleting(row.original)}
                    />
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Galeri" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Galeri"
                    description="Kelola album foto kegiatan paroki."
                    action={
                        <Button asChild>
                            <Link href={createAlbum()}>Tambah Album</Link>
                        </Button>
                    }
                />

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder="Cari judul album"
                    onSearch={(search) => applyFilter({ search })}
                >
                    <FilterSelect
                        id="filter-status"
                        label="Filter status"
                        value={filters.status}
                        onChange={(status) => applyFilter({ status })}
                        options={[
                            { value: '', label: 'Semua status' },
                            { value: 'published', label: 'Terbit' },
                            { value: 'draft', label: 'Draf' },
                        ]}
                    />

                    <FilterSelect
                        id="filter-tahun"
                        label="Filter tahun"
                        value={filters.tahun}
                        onChange={(tahun) => applyFilter({ tahun })}
                        options={[
                            { value: '', label: 'Semua tahun' },
                            ...years.map((year) => ({
                                value: String(year),
                                label: String(year),
                            })),
                        ]}
                    />

                    {hasFilter ? <ResetFilters onReset={reset} /> : null}
                </ListToolbar>

                <DataTable
                    columns={columns}
                    rows={albums.data}
                    sort={filters.sort}
                    direction={filters.direction}
                    onSort={sortBy}
                    rowId={(row) => row.id}
                    empty={
                        <ListEmpty
                            title={
                                hasFilter
                                    ? (empty.filtered ??
                                      'Tidak ada album yang cocok dengan filter.')
                                    : (empty.albums ??
                                      'Belum ada album galeri.')
                            }
                            description={
                                hasFilter
                                    ? 'Coba reset filter untuk melihat seluruh album.'
                                    : 'Buat album pertama untuk mulai.'
                            }
                        />
                    }
                />

                <ListPagination
                    meta={albums.meta}
                    previous={albums.links.prev}
                    next={albums.links.next}
                />
            </div>

            {deleting ? (
                <DeleteDialog
                    open
                    onOpenChange={(open) => {
                        if (!open) {
                            setDeleting(null);
                        }
                    }}
                    itemLabel={`Album "${deleting.title}"`}
                    consequences={[]}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);

                        router.delete(destroyAlbum(deleting.id), {
                            preserveScroll: true,
                            onFinish: () => {
                                setSubmitting(false);
                                setDeleting(null);
                            },
                        });
                    }}
                />
            ) : null}
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Galeri', href: galleriesIndex() },
    ],
};
