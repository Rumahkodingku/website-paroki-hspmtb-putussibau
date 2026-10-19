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
import { dashboard } from '@/routes';
import {
    create as createEventCategory,
    destroy as destroyEventCategory,
    edit as editEventCategory,
    index as eventCategoriesIndex,
} from '@/routes/event-categories';
import type { Paginated } from '@/types';
import type { CategoryFilters } from '@/types/news-agenda';
import type { EventCategoryRow } from '../../types/event-category';

type Props = {
    eventCategories: Paginated<EventCategoryRow>;
    filters: CategoryFilters;
};

export default function Index({ eventCategories, filters }: Props) {
    const [deleting, setDeleting] = useState<EventCategoryRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const applyFilter = (next: Partial<CategoryFilters>) => {
        router.get(
            eventCategoriesIndex().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const sortBy = (sortKey: string, direction: 'asc' | 'desc') => {
        router.get(
            eventCategoriesIndex().url,
            { ...filters, sort: sortKey, direction },
            { preserveScroll: true, replace: true },
        );
    };

    const hasFilter = filters.search !== '' || filters.sort !== '';

    const canDelete = (row: EventCategoryRow): boolean =>
        row.can.delete && (row.events_count ?? 0) === 0;

    const consequencesOf = (row: EventCategoryRow): string[] => {
        const count = row.events_count ?? 0;

        return count > 0 ? [`${count} kegiatan yang memakai kategori ini`] : [];
    };

    const columns: DataColumn<EventCategoryRow>[] = [
        {
            id: 'no',
            accessorKey: 'no',
            header: 'No',
            cell: ({ row }) => row.index + 1,
        },
        {
            id: 'name',
            accessorKey: 'name',
            header: 'Nama kategori',
            sortKey: 'name',
        },
        {
            id: 'slug',
            accessorKey: 'slug',
            header: 'Slug',
            sortKey: 'slug',
        },
        {
            id: 'color',
            accessorKey: 'color',
            header: 'Warna',
            cell: ({ row }) => (
                <span
                    aria-hidden="true"
                    className="inline-block size-5 rounded-sm border border-border"
                    style={{
                        backgroundColor:
                            row.original.color_value ?? 'transparent',
                    }}
                />
            ),
        },
        {
            id: 'events_count',
            accessorKey: 'events_count',
            header: 'Kegiatan',
        },
        {
            id: 'actions',
            header: 'Aksi',
            cell: ({ row }) => (
                <RowActions
                    canUpdate={row.original.can.update}
                    canDelete={canDelete(row.original)}
                    updateHref={
                        row.original.can.update
                            ? editEventCategory(row.original.id)
                            : undefined
                    }
                    onDelete={() => setDeleting(row.original)}
                />
            ),
        },
    ];

    return (
        <>
            <Head title="Kategori Agenda" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Kategori Agenda"
                    description="Kelompokkan kegiatan agar mudah dicari di kalender."
                    action={
                        <Button asChild>
                            <Link href={createEventCategory()}>
                                Tambah kategori
                            </Link>
                        </Button>
                    }
                />

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder="Cari nama kategori"
                    onSearch={(search) => applyFilter({ search })}
                >
                    {hasFilter ? (
                        <ResetFilters
                            onReset={() =>
                                applyFilter({
                                    search: '',
                                    sort: '',
                                    direction: '',
                                })
                            }
                        />
                    ) : null}
                </ListToolbar>

                <DataTable
                    columns={columns}
                    rows={eventCategories.data}
                    sort={filters.sort}
                    direction={filters.direction}
                    onSort={sortBy}
                    rowId={(row) => row.id}
                    empty={
                        <ListEmpty
                            title={
                                hasFilter
                                    ? 'Tidak ada kategori untuk filter ini.'
                                    : 'Belum ada kategori agenda.'
                            }
                            description={
                                hasFilter
                                    ? 'Coba reset filter untuk melihat seluruh kategori.'
                                    : 'Tambahkan kategori pertama untuk mulai.'
                            }
                        />
                    }
                />

                <ListPagination
                    meta={eventCategories.meta}
                    previous={eventCategories.links.prev}
                    next={eventCategories.links.next}
                />

                <Text as="p" variant="fine-print" color="muted">
                    Warna adalah petunjuk visual pada kalender. Nama kategori
                    selalu ditampilkan bersama, sehingga warna tidak pernah
                    menjadi satu-satunya penanda.
                </Text>
            </div>

            {deleting ? (
                <DeleteDialog
                    open
                    onOpenChange={(open) => {
                        if (!open) {
                            setDeleting(null);
                        }
                    }}
                    itemLabel={`Kategori "${deleting.name}"`}
                    consequences={consequencesOf(deleting)}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);
                        router.delete(destroyEventCategory(deleting.id), {
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
        { title: 'Master Data' },
        { title: 'Kategori Agenda' },
    ],
};
