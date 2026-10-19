import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { DataTable, type DataColumn } from '@/components/data-table';
import { DeleteDialog } from '@/components/delete-dialog';
import { ListEmpty, ListHeader, ListToolbar } from '@/components/list-parts';
import { ListPagination } from '@/components/list-pagination';
import { RowActions } from '@/components/row-actions';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import {
    create as createCategory,
    destroy as destroyCategory,
    edit as editCategory,
    index as categoriesIndex,
} from '@/routes/service-categories';
import type { ServiceCategoryRow } from '../../types/service-category';
import type { Paginated } from '@/types';

type Props = {
    categories: Paginated<ServiceCategoryRow>;
    filters: {
        search: string;
        status: string;
        sort: string;
        direction: string;
    };
    labels: Record<string, string>;
};

const STATUS_OPTIONS = [
    { value: '', label: 'Semua status' },
    { value: 'active', label: 'Aktif' },
    { value: 'inactive', label: 'Nonaktif' },
];

export default function Index({ categories, filters, labels }: Props) {
    const [deleting, setDeleting] = useState<ServiceCategoryRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const applyFilter = (next: Partial<typeof filters>) => {
        router.get(
            categoriesIndex().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const sortBy = (sort: string, direction: 'asc' | 'desc') => {
        router.get(
            categoriesIndex().url,
            { ...filters, sort, direction },
            { preserveScroll: true, replace: true },
        );
    };

    const hasFilter = filters.search !== '' || filters.status !== '';

    const columns: DataColumn<ServiceCategoryRow>[] = [
        {
            id: 'no',
            accessorKey: 'no',
            header: 'No',
            cell: ({ row }) => row.index + 1,
        },
        {
            id: 'name',
            accessorKey: 'name',
            header: 'Nama Kategori',
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
            id: 'description',
            header: 'Deskripsi',
            cell: ({ row }) => row.original.description ?? '—',
        },
        {
            id: 'services_count',
            header: labels.services_count ?? 'Jumlah layanan',
            cell: ({ row }) => row.original.services_count ?? '—',
        },
        {
            id: 'is_active',
            accessorKey: 'is_active',
            header: 'Status',
            sortKey: 'is_active',
            cell: ({ row }) => (
                <Badge
                    variant={row.original.is_active ? 'default' : 'secondary'}
                >
                    {row.original.is_active ? 'Aktif' : 'Nonaktif'}
                </Badge>
            ),
        },
        {
            id: 'sort_order',
            accessorKey: 'sort_order',
            header: 'Urutan',
            sortKey: 'sort_order',
            cell: ({ row }) => row.original.sort_order,
        },
        {
            id: 'actions',
            header: 'Aksi',
            cell: ({ row }) => (
                <RowActions
                    canUpdate={row.original.can.update}
                    canDelete={row.original.can.delete}
                    updateHref={editCategory(row.original.id)}
                    onDelete={() => setDeleting(row.original)}
                />
            ),
        },
    ];

    return (
        <>
            <Head title="Kategori Pelayanan" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Kategori Pelayanan"
                    description="Kelola kategori yang mengelompokkan layanan pada halaman publik."
                    action={
                        <Button asChild>
                            <Link href={createCategory()}>Tambah Kategori</Link>
                        </Button>
                    }
                />

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder="Cari nama kategori"
                    onSearch={(search) => applyFilter({ search })}
                >
                    <select
                        aria-label="Filter status"
                        value={filters.status}
                        onChange={(event) =>
                            applyFilter({ status: event.target.value })
                        }
                        className="h-11 rounded-md border border-border bg-background px-3 text-sm focus-visible:border-primary"
                    >
                        {STATUS_OPTIONS.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>
                </ListToolbar>

                <DataTable
                    columns={columns}
                    rows={categories.data}
                    sort={filters.sort}
                    direction={filters.direction}
                    onSort={sortBy}
                    rowId={(row) => row.id}
                    empty={
                        <ListEmpty
                            title={
                                hasFilter
                                    ? 'Tidak ada kategori untuk filter ini.'
                                    : 'Belum ada kategori pelayanan.'
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
                    meta={categories.meta}
                    previous={categories.links.prev}
                    next={categories.links.next}
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
                    itemLabel={`Kategori "${deleting.name}"`}
                    consequences={[]}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);

                        router.delete(destroyCategory(deleting.id), {
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
        { title: 'Kategori Pelayanan', href: categoriesIndex() },
    ],
};
