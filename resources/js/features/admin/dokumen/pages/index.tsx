import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import { formatBytes } from '@uploadcn/core';
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatWibDate, formatWibDateTime } from '@/lib/datetime';
import { dashboard } from '@/routes';
import {
    create as createDocument,
    destroy as destroyDocument,
    edit as editDocument,
    index as documentsIndex,
    preview as previewDocument,
} from '@/routes/documents';
import { DocumentStatusBadge } from '../components/document-status-badge';
import type {
    DocumentCategoryOption,
    DocumentFilters,
    DocumentFormMeta,
    DocumentRow,
} from '../types';
import type { Paginated } from '@/types';

type Props = {
    documents: Paginated<DocumentRow>;
    categories: DocumentCategoryOption[];
    filters: DocumentFilters;
} & DocumentFormMeta;

export default function Index({
    documents,
    categories,
    filters,
    copy,
    empty,
}: Props) {
    const [deleting, setDeleting] = useState<DocumentRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const applyFilter = (next: Partial<DocumentFilters>) => {
        router.get(
            documentsIndex().url,
            { ...filters, ...next, page: 1 },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const sortBy = (sort: string, direction: 'asc' | 'desc') => {
        router.get(
            documentsIndex().url,
            { ...filters, sort, direction, page: 1 },
            { preserveScroll: true, replace: true },
        );
    };

    const hasFilter =
        filters.search !== '' ||
        filters.kategori !== '' ||
        filters.status !== '';

    const reset = () => applyFilter({ search: '', kategori: '', status: '' });

    const columns: DataColumn<DocumentRow>[] = [
        {
            id: 'no',
            header: 'No',
            cell: ({ row }) => row.index + 1,
        },
        {
            id: 'title',
            accessorKey: 'title',
            header: 'Judul Dokumen',
            sortKey: 'title',
            cell: ({ row }) => (
                <div className="space-y-1">
                    <Text as="p" variant="label">
                        {row.original.title}
                    </Text>
                    <div className="flex flex-wrap items-center gap-2">
                        {row.original.category ? (
                            <Text as="p" variant="body-sm" color="muted">
                                {row.original.category.name}
                            </Text>
                        ) : null}
                        {row.original.is_featured ? (
                            <Badge variant="secondary">
                                {copy.featured ?? 'Unggulan'}
                            </Badge>
                        ) : null}
                        {row.original.is_expired ? (
                            <Badge variant="outline">
                                {copy.expired ?? 'Kedaluwarsa'}
                            </Badge>
                        ) : null}
                    </div>
                </div>
            ),
        },
        {
            id: 'file',
            header: 'Berkas',
            cell: ({ row }) => (
                <Text as="p" variant="body-sm">
                    {row.original.extension.toUpperCase()} ·{' '}
                    {formatBytes(row.original.file_size)}
                </Text>
            ),
        },
        {
            id: 'document_date',
            accessorKey: 'document_date',
            header: 'Tanggal Dokumen',
            sortKey: 'document_date',
            cell: ({ row }) =>
                row.original.document_date
                    ? formatWibDate(row.original.document_date)
                    : '—',
        },
        {
            id: 'status',
            accessorKey: 'status',
            header: 'Status',
            sortKey: 'status',
            cell: ({ row }) => (
                <DocumentStatusBadge
                    status={row.original.status}
                    label={row.original.status_label}
                />
            ),
        },
        {
            id: 'download_count',
            accessorKey: 'download_count',
            header: 'Unduhan',
            sortKey: 'download_count',
            cell: ({ row }) => row.original.download_count,
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
                    {row.original.can.preview &&
                    row.original.extension === 'pdf' ? (
                        <a
                            href={previewDocument(row.original.id).url}
                            target="_blank"
                            rel="noreferrer"
                            aria-label="Pratinjau dokumen"
                            className="cursor-pointer rounded-sm text-primary-on-surface hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <Eye className="size-4" aria-hidden="true" />
                        </a>
                    ) : null}

                    <RowActions
                        canUpdate={row.original.can.update}
                        canDelete={row.original.can.delete}
                        updateHref={
                            row.original.can.update
                                ? editDocument(row.original.id)
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
            <Head title={copy.title ?? 'Dokumen'} />

            <div className="space-y-6 p-6">
                <ListHeader
                    title={copy.title ?? 'Dokumen'}
                    description={copy.description}
                    count={documents.meta.total}
                    action={
                        <Button asChild>
                            <Link href={createDocument()}>Tambah Dokumen</Link>
                        </Button>
                    }
                />

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder="Cari judul dokumen"
                    onSearch={(search) => applyFilter({ search })}
                >
                    <FilterSelect
                        id="filter-category"
                        label="Filter kategori"
                        value={filters.kategori}
                        onChange={(kategori) => applyFilter({ kategori })}
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
                            { value: 'draft', label: 'Draf' },
                            { value: 'published', label: 'Terbit' },
                            { value: 'archived', label: 'Arsip' },
                        ]}
                    />

                    {hasFilter ? <ResetFilters onReset={reset} /> : null}
                </ListToolbar>

                <DataTable
                    columns={columns}
                    rows={documents.data}
                    sort={filters.sort}
                    direction={filters.direction}
                    onSort={sortBy}
                    rowId={(row) => row.id}
                    empty={
                        <ListEmpty
                            title={
                                hasFilter
                                    ? (empty.filtered ??
                                      'Tidak ada dokumen yang cocok dengan filter.')
                                    : (empty.documents ?? 'Belum ada dokumen.')
                            }
                            description={
                                hasFilter
                                    ? 'Coba reset filter untuk melihat seluruh dokumen.'
                                    : 'Tambahkan dokumen pertama untuk mulai.'
                            }
                        />
                    }
                />

                <ListPagination
                    meta={documents.meta}
                    previous={documents.links.prev}
                    next={documents.links.next}
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
                    itemLabel={`Dokumen "${deleting.title}"`}
                    consequences={['Tautan ke layanan akan dilepas.']}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);

                        router.delete(destroyDocument(deleting.id), {
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
        { title: 'Dokumen', href: documentsIndex() },
    ],
};
