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
import { dashboard } from '@/routes';
import {
    create as createPostCategory,
    destroy as destroyPostCategory,
    edit as editPostCategory,
    index as postCategoriesIndex,
} from '@/routes/post-categories';
import type { Paginated } from '@/types';
import type { CategoryFilters } from '@/types/news-agenda';
import type { PostCategoryRow } from '../../types/post-category';

type Props = {
    postCategories: Paginated<PostCategoryRow>;
    filters: CategoryFilters;
};

export default function Index({ postCategories, filters }: Props) {
    const [deleting, setDeleting] = useState<PostCategoryRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const applyFilter = (next: Partial<CategoryFilters>) => {
        router.get(
            postCategoriesIndex().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const sortBy = (sortKey: string, direction: 'asc' | 'desc') => {
        router.get(
            postCategoriesIndex().url,
            { ...filters, sort: sortKey, direction },
            { preserveScroll: true, replace: true },
        );
    };

    const hasFilter = filters.search !== '' || filters.sort !== '';

    const canDelete = (row: PostCategoryRow): boolean =>
        row.can.delete && (row.posts_count ?? 0) === 0;

    const consequencesOf = (row: PostCategoryRow): string[] => {
        const count = row.posts_count ?? 0;

        return count > 0 ? [`${count} artikel yang memakai kategori ini`] : [];
    };

    const columns: DataColumn<PostCategoryRow>[] = [
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
            id: 'sort_order',
            accessorKey: 'sort_order',
            header: 'Urutan',
            sortKey: 'sort_order',
        },
        {
            id: 'posts_count',
            accessorKey: 'posts_count',
            header: 'Artikel',
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
                            ? editPostCategory(row.original.id)
                            : undefined
                    }
                    onDelete={() => setDeleting(row.original)}
                />
            ),
        },
    ];

    return (
        <>
            <Head title="Kategori Berita" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Kategori Berita"
                    description="Kelompokkan artikel agar mudah dibaca dan difilter pengunjung."
                    action={
                        <Button asChild>
                            <Link href={createPostCategory()}>
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
                    rows={postCategories.data}
                    sort={filters.sort}
                    direction={filters.direction}
                    onSort={sortBy}
                    rowId={(row) => row.id}
                    empty={
                        <ListEmpty
                            title={
                                hasFilter
                                    ? 'Tidak ada kategori untuk filter ini.'
                                    : 'Belum ada kategori berita.'
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
                    meta={postCategories.meta}
                    previous={postCategories.links.prev}
                    next={postCategories.links.next}
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
                    consequences={consequencesOf(deleting)}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);
                        router.delete(destroyPostCategory(deleting.id), {
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
        { title: 'Kategori Berita' },
    ],
};
