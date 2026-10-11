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
    create as createPost,
    destroy as destroyPost,
    edit as editPost,
    index as postsIndex,
    preview as previewPost,
} from '@/routes/posts';
import type { Paginated } from '@/types';
import type { Option } from '@/types/news-agenda';
import type { PostFilters, PostFormMeta, PostRow } from '../types';
import { PinnedBadge, PostStatusBadge } from '../components/post-status-badge';
import { Eye } from 'lucide-react';

type Props = {
    posts: Paginated<PostRow>;
    filters: PostFilters;
    postCategories: Option[];
    displayTimezone: string;
} & Pick<PostFormMeta, 'postStatusChoices'>;

export default function Index({
    posts,
    filters,
    postCategories,
    displayTimezone,
    postStatusChoices,
}: Props) {
    const [deleting, setDeleting] = useState<PostRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const applyFilter = (next: Partial<PostFilters>) => {
        router.get(
            postsIndex().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hasFilter =
        filters.search !== '' ||
        filters.status !== '' ||
        filters.kategori !== '';

    const statusOptions = [
        { value: '', label: 'Semua status' },
        ...postStatusChoices.map((choice) => ({
            value: choice.value,
            label: choice.label,
        })),
    ];

    const categoryOptions = [
        { value: '', label: 'Semua kategori' },
        ...postCategories.map((category) => ({
            value: String(category.id),
            label: category.name,
        })),
    ];

    const columns: DataColumn<PostRow>[] = [
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
                        {row.original.author_name}
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
            id: 'status',
            accessorKey: 'status',
            header: 'Status',
            sortKey: 'status',
            cell: ({ row }) => (
                <div className="flex flex-wrap items-center gap-2">
                    <PostStatusBadge status={row.original.status} />
                    <PinnedBadge pinned={row.original.is_pinned} />
                </div>
            ),
        },
        {
            id: 'published_at',
            accessorKey: 'published_at',
            header: 'Waktu terbit',
            sortKey: 'published_at',
            cell: ({ row }) =>
                row.original.published_at ? (
                    formatWibDateTime(
                        row.original.published_at,
                        displayTimezone,
                    )
                ) : (
                    <Text variant="caption" color="muted">
                        Belum ada tanggal
                    </Text>
                ),
        },
        {
            id: 'view_count',
            accessorKey: 'view_count',
            header: 'Tayangan',
            cell: ({ row }) => row.original.view_count,
        },
        {
            id: 'actions',
            header: () => <span className="sr-only">Aksi</span>,
            cell: ({ row }) => (
                <div className="flex items-center justify-end gap-4">
                    {row.original.can.preview ? (
                        <Link
                            href={previewPost(row.original.id)}
                            prefetch
                            className="rounded-sm text-primary underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                        >
                            <Eye className="mr-1 inline h-4 w-4" />
                        </Link>
                    ) : null}
                    <RowActions
                        canUpdate={row.original.can.update}
                        canDelete={row.original.can.delete}
                        updateHref={
                            row.original.can.update
                                ? editPost(row.original.id)
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
            <Head title="Berita" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Berita"
                    description="Tulis, jadwalkan, dan terbitkan artikel paroki."
                    action={
                        <Button asChild>
                            <Link href={createPost()}>Tulis artikel</Link>
                        </Button>
                    }
                />

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder="Cari judul artikel"
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

                    {hasFilter || filters.sort !== '' ? (
                        <ResetFilters
                            onReset={() =>
                                applyFilter({
                                    search: '',
                                    status: '',
                                    kategori: '',
                                    sort: '',
                                    direction: '',
                                })
                            }
                        />
                    ) : null}
                </ListToolbar>

                <DataTable
                    columns={columns}
                    rows={posts.data}
                    sort={filters.sort}
                    direction={filters.direction}
                    onSort={(sortKey, direction) =>
                        router.get(
                            postsIndex().url,
                            { ...filters, sort: sortKey, direction },
                            { preserveScroll: true, replace: true },
                        )
                    }
                    rowId={(row) => row.id}
                    empty={
                        <ListEmpty
                            title={
                                hasFilter
                                    ? 'Tidak ada artikel untuk filter ini.'
                                    : 'Belum ada artikel.'
                            }
                            description={
                                hasFilter
                                    ? 'Coba reset filter untuk melihat seluruh artikel.'
                                    : 'Mulai dengan menulis artikel pertama.'
                            }
                        />
                    }
                />

                <ListPagination
                    meta={posts.meta}
                    previous={posts.links.prev}
                    next={posts.links.next}
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
                    itemLabel={`Artikel "${deleting.title}"`}
                    consequences={[
                        ...(deleting.status === 'published'
                            ? ['artikel berhenti tampil di halaman berita']
                            : []),
                        ...(deleting.is_pinned
                            ? ['artikel berhenti disematkan']
                            : []),
                    ]}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);
                        router.delete(destroyPost(deleting.id), {
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
    breadcrumbs: [{ title: 'Berita' }],
};
