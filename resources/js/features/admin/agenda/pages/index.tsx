import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { DataTable, type DataColumn } from '@/components/data-table';
import { DeleteDialog } from '@/components/delete-dialog';
import { RowActions } from '@/components/row-actions';
import { ListPagination } from '@/components/list-pagination';
import {
    ListEmpty,
    ListHeader,
    ListToolbar,
    ResetFilters,
} from '@/components/list-parts';
import { Text } from '@/components/text';
import { formatWibDateTime } from '@/lib/datetime';
import {
    create as createEvent,
    destroy as destroyEvent,
    duplicate as duplicateEvent,
    edit as editEvent,
    index as eventsIndex,
} from '@/routes/events';
import type { Paginated } from '@/types';
import type { Option } from '@/types/news-agenda';
import type { EventFilters, EventFormMeta, EventRow } from '../types';
import {
    EventStatusBadge,
    PublishedBadge,
} from '../components/event-status-badge';
import { CopyIcon, Loader } from 'lucide-react';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';

type Props = {
    events: Paginated<EventRow>;
    filters: EventFilters;
    eventCategories: Option[];
    displayTimezone: string;
} & Pick<EventFormMeta, 'eventStatusChoices'>;

function startCell(event: EventRow, timeZone: string) {
    if (event.is_all_day) {
        return formatWibDateTime(event.start_at, timeZone).split(' ')[0];
    }

    return formatWibDateTime(event.start_at, timeZone);
}

export default function Index({
    events,
    filters,
    eventCategories,
    displayTimezone,
    eventStatusChoices,
}: Props) {
    const [deleting, setDeleting] = useState<EventRow | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [duplicating, setDuplicating] = useState<number | null>(null);

    const applyFilter = (next: Partial<EventFilters>) => {
        router.get(
            eventsIndex().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hasFilter =
        filters.search !== '' ||
        filters.status !== '' ||
        filters.kategori !== '' ||
        filters.bulan !== '' ||
        filters.publikasi !== '';

    const statusOptions = [
        { value: '', label: 'Semua status' },
        ...eventStatusChoices.map((choice) => ({
            value: choice.value,
            label: choice.label,
        })),
    ];

    const categoryOptions = [
        { value: '', label: 'Semua kategori' },
        ...eventCategories.map((category) => ({
            value: String(category.id),
            label: category.name,
        })),
    ];

    const duplicate = (event: EventRow) => {
        setDuplicating(event.id);

        router.post(
            duplicateEvent(event.id),
            {},
            {
                onFinish: () => setDuplicating(null),
            },
        );
    };

    const columns: DataColumn<EventRow>[] = [
        {
            id: 'no',
            accessorKey: 'no',
            header: 'No',
            cell: ({ row }) => row.index + 1,
        },
        {
            id: 'title',
            accessorKey: 'title',
            header: 'Judul',
            sortKey: 'title',
            cell: ({ row }) => (
                <div className="flex flex-col gap-1">
                    <Text as="p" variant="caption-strong">
                        {row.original.title}
                    </Text>
                    <Text as="p" variant="caption" color="muted">
                        {row.original.location_name ?? 'Lokasi menyusul'}
                    </Text>
                </div>
            ),
        },
        {
            id: 'category',
            accessorKey: 'category',
            header: 'Kategori',
            cell: ({ row }) => row.original.category?.name ?? '—',
        },
        {
            id: 'start_at',
            accessorKey: 'start_at',
            header: 'Waktu mulai',
            sortKey: 'start_at',
            cell: ({ row }) => (
                <div className="flex flex-col gap-1">
                    <span>{startCell(row.original, displayTimezone)}</span>
                    {row.original.is_all_day ? (
                        <Text variant="caption" color="muted">
                            Sepanjang hari
                        </Text>
                    ) : null}
                </div>
            ),
        },
        {
            id: 'status',
            accessorKey: 'status',
            header: 'Status',
            sortKey: 'status',
            cell: ({ row }) => (
                <div className="flex flex-wrap items-center gap-2">
                    <EventStatusBadge status={row.original.status} />
                    <PublishedBadge published={row.original.is_published} />
                </div>
            ),
        },
        {
            id: 'actions',
            header: 'Aksi',
            cell: ({ row }) => (
                <div className="flex items-center justify-start gap-4">
                    {row.original.can.duplicate ? (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <button
                                    type="button"
                                    onClick={() => duplicate(row.original)}
                                    disabled={duplicating === row.original.id}
                                    className="cursor-pointer rounded-sm text-primary underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none disabled:opacity-50"
                                >
                                    {duplicating === row.original.id ? (
                                        <Loader className="ml-1 inline h-4 w-4" />
                                    ) : (
                                        <CopyIcon className="ml-1 inline h-4 w-4" />
                                    )}
                                </button>
                            </TooltipTrigger>

                            <TooltipContent>
                                <p>Copy Agenda</p>
                            </TooltipContent>
                        </Tooltip>
                    ) : null}
                    <RowActions
                        canUpdate={row.original.can.update}
                        canDelete={row.original.can.delete}
                        updateHref={
                            row.original.can.update
                                ? editEvent(row.original.id)
                                : undefined
                        }
                        onDelete={() => setDeleting(row.original)}
                    />
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Agenda" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Agenda"
                    description="Kelola kegiatan paroki beserta waktu, lokasi, dan posternya."
                    action={
                        <Button asChild>
                            <Link href={createEvent()}>Tambah kegiatan</Link>
                        </Button>
                    }
                />

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder="Cari judul kegiatan"
                    onSearch={(search) => applyFilter({ search })}
                >
                    <select
                        aria-label="Filter status"
                        value={filters.status}
                        onChange={(event) =>
                            applyFilter({ status: event.target.value })
                        }
                        className="h-11 rounded-md border border-hairline bg-background px-3 text-sm focus-visible:border-primary"
                    >
                        {statusOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>

                    <select
                        aria-label="Filter kategori"
                        value={filters.kategori}
                        onChange={(event) =>
                            applyFilter({ kategori: event.target.value })
                        }
                        className="h-11 rounded-md border border-hairline bg-background px-3 text-sm focus-visible:border-primary"
                    >
                        {categoryOptions.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>

                    {/*
                     * type="month" rather than a select of preset months. The
                     * parish's data is not limited to months anybody has thought
                     * of yet, and a select cannot offer next March in December.
                     */}
                    <input
                        type="month"
                        aria-label="Filter bulan"
                        value={filters.bulan}
                        onChange={(event) =>
                            applyFilter({ bulan: event.target.value })
                        }
                        className="h-11 rounded-md border border-hairline bg-background px-3 text-sm focus-visible:border-primary"
                    />

                    <select
                        aria-label="Filter pengumuman"
                        value={filters.publikasi}
                        onChange={(event) =>
                            applyFilter({ publikasi: event.target.value })
                        }
                        className="h-11 rounded-md border border-hairline bg-background px-3 text-sm focus-visible:border-primary"
                    >
                        {/*
                         * yes/no, not 1/0. EventController whitelists exactly
                         * ['yes', 'no'] and anything else is ignored silently,
                         * so sending 1/0 produced a filter that looked applied
                         * and filtered nothing.
                         */}
                        <option value="">Semua pengumuman</option>
                        <option value="yes">Diumumkan</option>
                        <option value="no">Belum diumumkan</option>
                    </select>

                    {hasFilter || filters.sort !== '' ? (
                        <ResetFilters
                            onReset={() =>
                                applyFilter({
                                    search: '',
                                    status: '',
                                    kategori: '',
                                    bulan: '',
                                    publikasi: '',
                                    sort: '',
                                    direction: '',
                                })
                            }
                        />
                    ) : null}
                </ListToolbar>

                <DataTable
                    columns={columns}
                    rows={events.data}
                    sort={filters.sort}
                    direction={filters.direction}
                    onSort={(sortKey, direction) =>
                        router.get(
                            eventsIndex().url,
                            { ...filters, sort: sortKey, direction },
                            { preserveScroll: true, replace: true },
                        )
                    }
                    rowId={(row) => row.id}
                    empty={
                        <ListEmpty
                            title={
                                hasFilter
                                    ? 'Tidak ada kegiatan untuk filter ini.'
                                    : 'Belum ada agenda.'
                            }
                            description={
                                hasFilter
                                    ? 'Coba reset filter untuk melihat seluruh kegiatan.'
                                    : 'Mulai dengan menambahkan kegiatan pertama.'
                            }
                        />
                    }
                />

                <ListPagination
                    meta={events.meta}
                    previous={events.links.prev}
                    next={events.links.next}
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
                    itemLabel={`Kegiatan "${deleting.title}"`}
                    consequences={[
                        ...(deleting.is_published
                            ? [
                                  'kegiatan berhenti tampil di halaman agenda publik',
                              ]
                            : []),
                        ...(deleting.status === 'cancelled'
                            ? [
                                  'keterangan pembatalan hilang dari agenda publik',
                              ]
                            : []),
                    ]}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);
                        router.delete(destroyEvent(deleting.id), {
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
    breadcrumbs: [{ title: 'Agenda' }],
};
