import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import type { DataColumn } from '@/components/data-table';
import { DataTable } from '@/components/data-table';
import { DeleteDialog } from '@/components/delete-dialog';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import {
    create as createPastor,
    destroy as destroyPastor,
    edit as editPastor,
} from '@/routes/pastors';
import {
    ListEmpty,
    ListHeader,
    ListToolbar,
    StatusBadge,
} from '@/components/list-parts';
import { ListPagination } from '@/components/list-pagination';
import { RowActions } from '@/components/row-actions';
import { Text } from '@/components/text';
import type { Paginated } from '@/types';
import type { PastorRow } from '@/features/admin/profil/types';

type Props = {
    pastors: Paginated<PastorRow>;
    filters: { search: string; sort: string; direction: string };
};

export default function Index({ pastors, filters }: Props) {
    const [deleting, setDeleting] = useState<PastorRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const applyFilter = (next: Partial<typeof filters>) => {
        router.get(
            '/admin/profil/pastor',
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const columns: DataColumn<PastorRow>[] = [
        {
            id: 'no',
            accessorKey: 'no',
            header: 'No',
            cell: ({ row }) => row.index + 1,
        },
        {
            id: 'photo',
            header: 'Foto',
            cell: ({ row }) =>
                row.original.photo_url ? (
                    <img
                        src={row.original.photo_url}
                        alt={row.original.name}
                        loading="lazy"
                        className="size-16 rounded-md border border-border object-cover"
                    />
                ) : (
                    <Text as="span" variant="body-sm" color="muted">
                        —
                    </Text>
                ),
        },
        {
            id: 'name',
            accessorKey: 'name',
            header: 'Nama',
            sortKey: 'name',
            cell: ({ row }) => (
                <Text as="p" variant="body-sm">
                    {row.original.title
                        ? `${row.original.title} ${row.original.name}`
                        : row.original.name}
                </Text>
            ),
        },
        {
            id: 'assignment',
            header: 'Penugasan',
            cell: ({ row }) =>
                row.original.current_assignment
                    ? `${row.original.current_assignment.position_label}${
                          row.original.current_assignment.start_year
                              ? ` · sejak ${row.original.current_assignment.start_year}`
                              : ''
                      }`
                    : '—',
        },
        {
            id: 'published',
            header: 'Status',
            cell: ({ row }) => (
                <StatusBadge active={row.original.is_published} />
            ),
        },
        {
            id: 'actions',
            header: 'Aksi',
            cell: ({ row }) => (
                <RowActions
                    canUpdate={row.original.can.update}
                    canDelete={row.original.can.delete}
                    updateHref={editPastor(row.original.id)}
                    onDelete={() => setDeleting(row.original)}
                />
            ),
        },
    ];

    return (
        <>
            <Head title="Pastor Paroki" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Pastor Paroki"
                    description="Data pastor dan riwayat penugasannya."
                    action={
                        <Button asChild>
                            <Link href={createPastor()}>Tambah pastor</Link>
                        </Button>
                    }
                />

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder="Cari nama pastor"
                    onSearch={(search) => applyFilter({ search })}
                />

                <DataTable
                    columns={columns}
                    rows={pastors.data}
                    sort={filters.sort}
                    direction={filters.direction}
                    onSort={(sort, direction) =>
                        applyFilter({ sort, direction })
                    }
                    rowId={(row) => row.id}
                    empty={
                        <ListEmpty
                            title={
                                filters.search !== ''
                                    ? 'Tidak ada pastor untuk pencarian ini.'
                                    : 'Belum ada data pastor.'
                            }
                            description="Tambahkan data pastor untuk mulai."
                        />
                    }
                />

                <ListPagination
                    meta={pastors.meta}
                    previous={pastors.links.prev}
                    next={pastors.links.next}
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
                    itemLabel={`Pastor "${deleting.name}"`}
                    consequences={[
                        `${deleting.assignments_count ?? deleting.assignments.length} riwayat penugasan`,
                    ]}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);
                        router.delete(destroyPastor(deleting.id), {
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
        { title: 'Profil Paroki' },
        { title: 'Pastor Paroki' },
    ],
};
