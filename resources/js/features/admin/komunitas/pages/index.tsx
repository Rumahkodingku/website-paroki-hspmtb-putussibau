import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
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
import { dashboard } from '@/routes';
import {
    create as createCommunity,
    destroy as destroyCommunity,
    edit as editCommunity,
    index as communitiesIndex,
} from '@/routes/communities';
import {
    CommunityStatusBadge,
    PublicationBadge,
} from '../components/community-status-badge';
import type { CategoryOption, CommunityRow, StatusOption } from '../types';
import type { Paginated } from '@/types';

type Props = {
    communities: Paginated<CommunityRow>;
    categories: CategoryOption[];
    filters: {
        search: string;
        category: string;
        status: string;
        publication: string;
        sort: string;
        direction: string;
    };
    empty: Record<string, string>;
    statuses: StatusOption[];
};

export default function Index({
    communities,
    categories,
    filters,
    empty,
    statuses,
}: Props) {
    const [deleting, setDeleting] = useState<CommunityRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const applyFilter = (next: Partial<typeof filters>) => {
        router.get(
            communitiesIndex().url,
            { ...filters, ...next, page: 1 },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const sortBy = (sort: string, direction: 'asc' | 'desc') => {
        router.get(
            communitiesIndex().url,
            { ...filters, sort, direction, page: 1 },
            { preserveScroll: true, replace: true },
        );
    };

    const hasFilter =
        filters.search !== '' ||
        filters.category !== '' ||
        filters.status !== '' ||
        filters.publication !== '';

    const reset = () =>
        applyFilter({
            search: '',
            category: '',
            status: '',
            publication: '',
        });

    const columns: DataColumn<CommunityRow>[] = [
        {
            id: 'no',
            header: 'No',
            cell: ({ row }) => row.index + 1,
        },
        {
            id: 'name',
            accessorKey: 'name',
            header: 'Nama Komunitas',
            sortKey: 'name',
            cell: ({ row }) => (
                <div className="space-y-0.5">
                    <Text as="p" variant="label">
                        {row.original.name}
                    </Text>
                    <Text as="p" variant="body-sm" color="muted">
                        {row.original.slug}
                    </Text>
                </div>
            ),
        },
        {
            id: 'category',
            header: 'Kategori',
            cell: ({ row }) => row.original.category?.name ?? '—',
        },
        {
            id: 'status',
            accessorKey: 'status',
            header: 'Status',
            sortKey: 'status',
            cell: ({ row }) => (
                <CommunityStatusBadge
                    status={row.original.status}
                    label={row.original.status_label}
                />
            ),
        },
        {
            id: 'is_published',
            accessorKey: 'is_published',
            header: 'Publikasi',
            sortKey: 'is_published',
            cell: ({ row }) => (
                <PublicationBadge published={row.original.is_published} />
            ),
        },
        {
            id: 'actions',
            header: 'Aksi',
            cell: ({ row }) => (
                <RowActions
                    canUpdate={row.original.can.update}
                    canDelete={row.original.can.delete}
                    updateHref={editCommunity(row.original.id)}
                    onDelete={() => setDeleting(row.original)}
                />
            ),
        },
    ];

    return (
        <>
            <Head title="Komunitas" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Komunitas"
                    description="Kelola profil komunitas, kontak, dan publikasi."
                    action={
                        <Button asChild>
                            <Link href={createCommunity()}>
                                Tambah Komunitas
                            </Link>
                        </Button>
                    }
                />

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder="Cari nama komunitas"
                    onSearch={(search) => applyFilter({ search })}
                >
                    <FilterSelect
                        id="filter-kategori"
                        label="Filter kategori"
                        value={filters.category}
                        onChange={(category) => applyFilter({ category })}
                        options={[
                            { value: '', label: 'Semua kategori' },
                            ...categories.map((category) => ({
                                value: String(category.id),
                                label: category.name,
                            })),
                        ]}
                    />

                    <FilterSelect
                        id="filter-status"
                        label="Filter status"
                        value={filters.status}
                        onChange={(status) => applyFilter({ status })}
                        options={[
                            { value: '', label: 'Semua status' },
                            ...statuses.map((option) => ({
                                value: option.value,
                                label: option.label,
                            })),
                        ]}
                    />

                    <FilterSelect
                        id="filter-publikasi"
                        label="Filter publikasi"
                        value={filters.publication}
                        onChange={(publication) => applyFilter({ publication })}
                        options={[
                            { value: '', label: 'Semua publikasi' },
                            { value: 'published', label: 'Dipublikasikan' },
                            { value: 'draft', label: 'Draf' },
                        ]}
                    />

                    {hasFilter ? <ResetFilters onReset={reset} /> : null}
                </ListToolbar>

                <DataTable
                    columns={columns}
                    rows={communities.data}
                    sort={filters.sort}
                    direction={filters.direction}
                    onSort={sortBy}
                    rowId={(row) => row.id}
                    empty={
                        <ListEmpty
                            title={
                                hasFilter
                                    ? (empty.filtered ??
                                      'Tidak ada komunitas yang cocok dengan filter.')
                                    : (empty.communities ??
                                      'Belum ada komunitas.')
                            }
                            description={
                                hasFilter
                                    ? 'Coba reset filter untuk melihat seluruh komunitas.'
                                    : 'Tambahkan komunitas pertama untuk mulai.'
                            }
                        />
                    }
                />

                <ListPagination
                    meta={communities.meta}
                    previous={communities.links.prev}
                    next={communities.links.next}
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
                    itemLabel={`Komunitas "${deleting.name}"`}
                    consequences={[]}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);

                        router.delete(destroyCommunity(deleting.id), {
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
        { title: 'Komunitas', href: communitiesIndex() },
    ],
};
