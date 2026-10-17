import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { DataTable, type DataColumn } from '@/components/data-table';
import { DeleteDialog } from '@/components/delete-dialog';
import {
    ListEmpty,
    ListHeader,
    ListToolbar,
    ResetFilters,
    StatusBadge,
} from '@/components/list-parts';
import { ListPagination } from '@/components/list-pagination';
import { RowActions } from '@/components/row-actions';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import {
    create as createCategory,
    destroy as destroyCategory,
    edit as editCategory,
    index as categoriesIndex,
} from '@/routes/document-categories';
import type {
    DocumentCategoryFilters,
    DocumentCategoryRow,
} from '../../types/document-category';
import type { Paginated } from '@/types';

type Props = {
    categories: Paginated<DocumentCategoryRow>;
    filters: DocumentCategoryFilters;
    copy: Record<string, string>;
};

export default function Index({ categories, filters, copy }: Props) {
    const { errors } = usePage<{ errors: Record<string, string> }>().props;
    const [deleting, setDeleting] = useState<DocumentCategoryRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const applyFilter = (next: Partial<DocumentCategoryFilters>) => {
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

    const hasFilter = filters.search !== '';
    const canDelete = (row: DocumentCategoryRow) =>
        row.can.delete && (row.documents_count ?? 0) === 0;

    const columns: DataColumn<DocumentCategoryRow>[] = [
        {
            id: 'no',
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
            id: 'sort_order',
            accessorKey: 'sort_order',
            header: 'Urutan',
            sortKey: 'sort_order',
        },
        {
            id: 'documents_count',
            accessorKey: 'documents_count',
            header: 'Dokumen',
            cell: ({ row }) => row.original.documents_count,
        },
        {
            id: 'is_active',
            accessorKey: 'is_active',
            header: 'Status',
            cell: ({ row }) => <StatusBadge active={row.original.is_active} />,
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
                            ? editCategory(row.original.id)
                            : undefined
                    }
                    onDelete={() => setDeleting(row.original)}
                />
            ),
        },
    ];

    return (
        <>
            <Head title={copy.title ?? 'Kategori Dokumen'} />

            <div className="space-y-6 p-6">
                <ListHeader
                    title={copy.title ?? 'Kategori Dokumen'}
                    description={copy.description}
                    action={
                        <Button asChild>
                            <Link href={createCategory()}>Tambah kategori</Link>
                        </Button>
                    }
                />

                {errors.document_category ? (
                    <Alert variant="destructive">
                        <AlertDescription>
                            {errors.document_category}
                        </AlertDescription>
                    </Alert>
                ) : null}

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder="Cari kategori dokumen"
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
                                    : 'Belum ada kategori dokumen.'
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
                    consequences={
                        deleting.documents_count > 0
                            ? [
                                  `${deleting.documents_count} dokumen memakai kategori ini`,
                              ]
                            : []
                    }
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
        { title: 'Kategori Dokumen' },
    ],
};
