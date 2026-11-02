import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Video } from 'lucide-react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable, type DataColumn } from '@/components/data-table';
import { DeleteDialog } from '@/components/delete-dialog';
import Image from '@/components/image';
import {
    FilterSelect,
    ListEmpty,
    ListHeader,
    ListToolbar,
    ResetFilters,
} from '@/components/list-parts';
import { ListPagination } from '@/components/list-pagination';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { showMutationErrorToast } from '@/lib/mutation-error-toast';
import { formatWibDateTime } from '@/lib/datetime';
import { dashboard } from '@/routes';
import {
    create as createLivestream,
    destroy as destroyLivestream,
    end as endLivestream,
    index as livestreamsIndex,
    start as startLivestream,
    unavailable as markUnavailable,
} from '@/routes/livestreams';
import { LivestreamRowActions } from '../components/livestream-row-actions';
import {
    LivestreamBroadcastBadge,
    LivestreamEditorialBadge,
} from '../components/livestream-status-badge';
import type {
    ActiveLive,
    LivestreamFilters,
    LivestreamFormMeta,
    LivestreamRow,
} from '../types';
import type { Paginated } from '@/types';

type Props = LivestreamFormMeta & {
    livestreams: Paginated<LivestreamRow>;
    activeLive: ActiveLive | null;
    filters: LivestreamFilters;
};

type ConfirmationType = 'start' | 'end' | 'unavailable';

type Confirmation = {
    type: ConfirmationType;
    stream: LivestreamRow;
};

export default function Index({
    livestreams,
    activeLive,
    filters,
    labels,
    copy,
    empty,
    categories,
    statuses,
    broadcastStatuses,
}: Props) {
    const [deleting, setDeleting] = useState<LivestreamRow | null>(null);
    const [confirmation, setConfirmation] = useState<Confirmation | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const applyFilter = (next: Partial<LivestreamFilters>) => {
        router.get(
            livestreamsIndex().url,
            { ...filters, ...next, page: 1 },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const sortBy = (sort: string, direction: 'asc' | 'desc') => {
        router.get(
            livestreamsIndex().url,
            { ...filters, sort, direction, page: 1 },
            { preserveScroll: true, replace: true },
        );
    };

    const hasFilter =
        filters.search !== '' ||
        filters.status !== '' ||
        filters.broadcast_status !== '' ||
        filters.kategori !== '';

    const reset = () =>
        applyFilter({
            search: '',
            status: '',
            broadcast_status: '',
            kategori: '',
        });

    const dateFor = (row: LivestreamRow): string | null =>
        row.scheduled_at ?? row.started_at ?? row.recorded_at;

    const confirmationTitle = (): string => {
        if (confirmation?.type === 'end') {
            return copy.end_title ?? 'Akhiri siaran?';
        }

        if (confirmation?.type === 'unavailable') {
            return copy.unavailable_title ?? 'Tandai tidak tersedia?';
        }

        return copy.start_title ?? 'Mulai siaran?';
    };

    const confirmationDescription = (): string => {
        if (confirmation === null) {
            return '';
        }

        if (confirmation.type === 'end') {
            return (
                copy.end_description ??
                'Siaran akan hilang dari beranda. Tandai rekaman tersedia di form ubah setelah replay dipastikan ada.'
            );
        }

        if (confirmation.type === 'unavailable') {
            return (
                copy.unavailable_description ??
                'Video tidak akan tampil sebagai siaran langsung atau rekaman.'
            );
        }

        if (activeLive && activeLive.id !== confirmation.stream.id) {
            return (
                copy.start_replace_description ??
                'Siaran ":title" sedang live dan akan diakhiri. Lanjutkan memulai siaran ini?'
            ).replace(':title', activeLive.title);
        }

        return (
            copy.start_description ??
            'Siaran akan tampil di beranda dan halaman Livestreaming sampai diakhiri.'
        );
    };

    const confirmLabel = (): string => {
        if (confirmation?.type === 'end') {
            return copy.end ?? 'Akhiri Siaran';
        }

        if (confirmation?.type === 'unavailable') {
            return copy.unavailable ?? 'Tandai Tidak Tersedia';
        }

        return copy.start ?? 'Mulai Siaran';
    };

    const postConfirmation = () => {
        if (confirmation === null) {
            return;
        }

        const { stream, type } = confirmation;
        const url =
            type === 'start'
                ? startLivestream(stream.id)
                : type === 'end'
                  ? endLivestream(stream.id)
                  : markUnavailable(stream.id);

        setSubmitting(true);

        router.post(
            url,
            {},
            {
                preserveScroll: true,
                onError: (errors) =>
                    showMutationErrorToast(
                        errors,
                        'Gagal mengubah status siaran',
                    ),
                onFinish: () => {
                    setSubmitting(false);
                    setConfirmation(null);
                },
            },
        );
    };

    const columns: DataColumn<LivestreamRow>[] = [
        {
            id: 'no',
            header: 'No',
            cell: ({ row }) => row.index + 1,
        },
        {
            id: 'title',
            accessorKey: 'title',
            header: 'Judul',
            sortKey: 'title',
            cell: ({ row }) => (
                <div className="flex min-w-0 items-center gap-3">
                    {row.original.thumbnail_url ? (
                        <Image
                            src={row.original.thumbnail_url}
                            alt=""
                            aspectRatio="16 / 9"
                            fit="cover"
                            loading="lazy"
                            className="w-20 shrink-0 rounded-md"
                        />
                    ) : (
                        <span
                            aria-hidden="true"
                            className="flex aspect-video w-20 shrink-0 items-center justify-center rounded-md border border-dashed border-border text-muted-foreground"
                        >
                            <Video className="size-4" />
                        </span>
                    )}

                    <div className="min-w-0 space-y-0.5">
                        <Text as="p" variant="label" className="truncate">
                            {row.original.title}
                        </Text>
                        <Text as="p" variant="body-sm" color="muted">
                            {row.original.youtube_video_id}
                        </Text>
                    </div>
                </div>
            ),
        },
        {
            id: 'category',
            header: 'Kategori',
            cell: ({ row }) => row.original.category_label ?? '—',
        },
        {
            id: 'schedule',
            header: 'Jadwal / Rekaman',
            sortKey: 'scheduled_at',
            cell: ({ row }) => {
                const date = dateFor(row.original);

                return date ? formatWibDateTime(date) : '—';
            },
        },
        {
            id: 'status',
            accessorKey: 'status',
            header: 'Publikasi',
            sortKey: 'status',
            cell: ({ row }) => (
                <LivestreamEditorialBadge
                    status={row.original.status}
                    label={row.original.status_label}
                />
            ),
        },
        {
            id: 'broadcast_status',
            accessorKey: 'broadcast_status',
            header: 'Siaran',
            sortKey: 'broadcast_status',
            cell: ({ row }) => (
                <LivestreamBroadcastBadge
                    status={row.original.broadcast_status}
                    label={row.original.broadcast_status_label}
                />
            ),
        },
        {
            id: 'updated_at',
            accessorKey: 'updated_at',
            header: labels.updated_at ?? 'Terakhir Diperbarui',
            sortKey: 'updated_at',
            cell: ({ row }) => formatWibDateTime(row.original.updated_at),
        },
        {
            id: 'actions',
            header: 'Aksi',
            cell: ({ row }) => (
                <LivestreamRowActions
                    stream={row.original}
                    onStart={() =>
                        setConfirmation({ type: 'start', stream: row.original })
                    }
                    onEnd={() =>
                        setConfirmation({ type: 'end', stream: row.original })
                    }
                    onUnavailable={() =>
                        setConfirmation({
                            type: 'unavailable',
                            stream: row.original,
                        })
                    }
                    onDelete={() => setDeleting(row.original)}
                />
            ),
        },
    ];

    return (
        <>
            <Head title="Livestreaming" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title={copy.heading ?? 'Livestreaming'}
                    description={
                        copy.description ??
                        'Kelola siaran langsung dan arsip rekaman YouTube paroki.'
                    }
                    action={
                        <Button asChild>
                            <Link href={createLivestream()}>
                                {copy.add ?? 'Tambah Livestreaming'}
                            </Link>
                        </Button>
                    }
                />

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder={
                        copy.search_placeholder ?? 'Cari judul livestreaming…'
                    }
                    onSearch={(search) => applyFilter({ search })}
                >
                    <FilterSelect
                        id="filter-status"
                        label={copy.filter_status ?? 'Status publikasi'}
                        value={filters.status}
                        onChange={(status) => applyFilter({ status })}
                        options={[
                            { value: '', label: copy.all ?? 'Semua' },
                            ...statuses,
                        ]}
                    />

                    <FilterSelect
                        id="filter-broadcast-status"
                        label={copy.filter_broadcast_status ?? 'Status siaran'}
                        value={filters.broadcast_status}
                        onChange={(broadcast_status) =>
                            applyFilter({ broadcast_status })
                        }
                        options={[
                            { value: '', label: copy.all ?? 'Semua' },
                            ...broadcastStatuses,
                        ]}
                    />

                    <FilterSelect
                        id="filter-category"
                        label={copy.filter_category ?? 'Kategori'}
                        value={filters.kategori}
                        onChange={(kategori) => applyFilter({ kategori })}
                        options={[
                            { value: '', label: copy.all ?? 'Semua' },
                            ...categories,
                        ]}
                    />

                    {hasFilter ? <ResetFilters onReset={reset} /> : null}
                </ListToolbar>

                <DataTable
                    columns={columns}
                    rows={livestreams.data}
                    sort={filters.sort}
                    direction={filters.direction}
                    onSort={sortBy}
                    rowId={(row) => row.id}
                    empty={
                        <ListEmpty
                            title={
                                hasFilter
                                    ? (empty.filtered ??
                                      'Tidak ada livestreaming yang cocok dengan filter.')
                                    : (empty.items ??
                                      'Belum ada livestreaming.')
                            }
                            description={
                                hasFilter
                                    ? 'Coba reset filter untuk melihat seluruh livestreaming.'
                                    : 'Buat livestreaming pertama untuk mulai.'
                            }
                        />
                    }
                />

                <ListPagination
                    meta={livestreams.meta}
                    previous={livestreams.links.prev}
                    next={livestreams.links.next}
                />
            </div>

            <ConfirmDialog
                open={confirmation !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setConfirmation(null);
                    }
                }}
                title={confirmationTitle()}
                description={confirmationDescription()}
                confirmLabel={confirmLabel()}
                disabled={submitting}
                onConfirm={postConfirmation}
            />

            {deleting ? (
                <DeleteDialog
                    open
                    onOpenChange={(open) => {
                        if (!open) {
                            setDeleting(null);
                        }
                    }}
                    itemLabel={`Livestreaming "${deleting.title}"`}
                    consequences={[]}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);

                        router.delete(destroyLivestream(deleting.id), {
                            preserveScroll: true,
                            onError: (errors) =>
                                showMutationErrorToast(
                                    errors,
                                    'Gagal menghapus livestreaming',
                                ),
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
        { title: 'Livestreaming', href: livestreamsIndex() },
    ],
};
