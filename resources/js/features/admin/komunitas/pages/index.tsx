import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { DataTable, type DataColumn } from '@/components/data-table';
import { DeleteDialog } from '@/components/delete-dialog';
import {
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
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const sortBy = (sort: string, direction: 'asc' | 'desc') => {
        router.get(
            communitiesIndex().url,
            { ...filters, sort, direction },
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
                    <Text as="p" variant="caption-strong">
                        {row.original.name}
                    </Text>
                    <Text as="p" variant="caption" color="muted">
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
                    <select
                        aria-label="Filter kategori"
                        value={filters.category}
                        onChange={(event) =>
                            applyFilter({ category: event.target.value })
                        }
                        className="h-11 rounded-md border border-hairline bg-background px-3 text-sm focus-visible:border-primary"
                    >
                        <option value="">Semua kategori</option>
                        {categories.map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.name}
                            </option>
                        ))}
                    </select>

                    <select
                        aria-label="Filter status"
                        value={filters.status}
                        onChange={(event) =>
                            applyFilter({ status: event.target.value })
                        }
                        className="h-11 rounded-md border border-hairline bg-background px-3 text-sm focus-visible:border-primary"
                    >
                        <option value="">Semua status</option>
                        {statuses.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>

                    <select
                        aria-label="Filter publikasi"
                        value={filters.publication}
                        onChange={(event) =>
                            applyFilter({ publication: event.target.value })
                        }
                        className="h-11 rounded-md border border-hairline bg-background px-3 text-sm focus-visible:border-primary"
                    >
                        <option value="">Semua publikasi</option>
                        <option value="published">Dipublikasikan</option>
                        <option value="draft">Draf</option>
                    </select>

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
