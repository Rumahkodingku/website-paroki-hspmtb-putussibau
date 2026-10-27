import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index as massSchedulesIndex } from '@/routes/mass-schedules';
import {
    create as createSpecialMass,
    destroy as destroySpecialMass,
    edit as editSpecialMass,
    index as specialMassesIndex,
} from '@/routes/special-masses';
import { DataTable } from '@/components/data-table';
import { specialMassColumns } from '../../components/columns';
import { DeleteDialog } from '@/components/delete-dialog';
import {
    FilterSelect,
    ListEmpty,
    ListHeader,
    ListToolbar,
    ResetFilters,
} from '@/components/list-parts';
import { RowActions } from '@/components/row-actions';
import { SpecialMassCards } from './components/special-mass-cards';
import { ListPagination } from '@/components/list-pagination';
import type { SpecialMassRow } from '../../types';
import type { Paginated } from '@/types';

type Props = {
    specialMasses: Paginated<SpecialMassRow>;
    filters: {
        search: string;
        status: string;
        sort: string;
        direction: string;
    };
};

const STATUS_OPTIONS = [
    { value: '', label: 'Semua status' },
    { value: 'upcoming', label: 'Mendatang' },
    { value: 'past', label: 'Sudah lewat' },
    { value: 'inactive', label: 'Nonaktif' },
];

export default function Index({ specialMasses, filters }: Props) {
    const [deleting, setDeleting] = useState<SpecialMassRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const applyFilter = (next: Partial<typeof filters>) => {
        router.get(
            specialMassesIndex().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const sortBy = (sortKey: string, direction: 'asc' | 'desc') => {
        router.get(
            specialMassesIndex().url,
            { ...filters, sort: sortKey, direction },
            { preserveScroll: true, replace: true },
        );
    };

    const hasFilter = filters.search !== '' || filters.status !== '';

    const consequencesOf = (mass: SpecialMassRow): string[] => {
        const notices = mass.schedule_notices_count ?? 0;

        return notices > 0
            ? [`${notices} pemberitahuan yang berhenti tampil`]
            : [];
    };

    return (
        <>
            <Head title="Misa Khusus" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Misa Khusus"
                    description="Misa pada tanggal tertentu, di luar jadwal rutin."
                    action={
                        <Button asChild>
                            <Link href={createSpecialMass()}>
                                Tambah misa khusus
                            </Link>
                        </Button>
                    }
                />

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder="Cari judul misa"
                    onSearch={(search) => applyFilter({ search })}
                >
                    <FilterSelect
                        id="filter-status"
                        label="Filter status"
                        value={filters.status}
                        onChange={(status) => applyFilter({ status })}
                        options={STATUS_OPTIONS}
                    />

                    {hasFilter || filters.sort !== '' ? (
                        <ResetFilters
                            onReset={() =>
                                applyFilter({
                                    search: '',
                                    status: '',
                                    sort: '',
                                    direction: '',
                                })
                            }
                        />
                    ) : null}
                </ListToolbar>

                <SpecialMassCards rows={specialMasses.data} />

                <div className="hidden md:block">
                    <DataTable
                        columns={specialMassColumns((row) => (
                            <RowActions
                                canUpdate={row.can.update}
                                canDelete={row.can.delete}
                                updateHref={editSpecialMass(row.id)}
                                onDelete={() => setDeleting(row)}
                            />
                        ))}
                        rows={specialMasses.data}
                        sort={filters.sort}
                        direction={filters.direction}
                        onSort={sortBy}
                        rowId={(row) => row.id}
                        empty={
                            <ListEmpty
                                title={
                                    hasFilter
                                        ? 'Tidak ada misa khusus untuk filter ini.'
                                        : 'Belum ada misa khusus.'
                                }
                                description={
                                    hasFilter
                                        ? 'Coba reset filter untuk melihat seluruh misa khusus.'
                                        : 'Tambahkan misa khusus pertama untuk mulai.'
                                }
                            />
                        }
                    />
                </div>

                <ListPagination
                    meta={specialMasses.meta}
                    previous={specialMasses.links.prev}
                    next={specialMasses.links.next}
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
                    itemLabel={`Misa khusus "${deleting.title}"`}
                    consequences={consequencesOf(deleting)}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);

                        router.delete(destroySpecialMass(deleting.id), {
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
        { title: 'Jadwal Misa', href: massSchedulesIndex() },
        { title: 'Misa Khusus' },
    ],
};
