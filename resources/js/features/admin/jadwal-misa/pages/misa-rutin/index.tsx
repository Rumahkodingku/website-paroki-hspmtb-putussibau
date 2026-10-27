import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import {
    create as createMassSchedule,
    destroy as destroyMassSchedule,
    edit as editMassSchedule,
    index as massSchedulesIndex,
    list as massSchedulesList,
} from '@/routes/mass-schedules';
import { DataTable } from '@/components/data-table';
import { massScheduleColumns } from '../../components/columns';
import { DeleteDialog } from '@/components/delete-dialog';
import {
    FilterSelect,
    ListEmpty,
    ListHeader,
    ListToolbar,
    ResetFilters,
} from '@/components/list-parts';
import { MassScheduleCards } from './components/mass-schedule-cards';
import { RowActions } from '@/components/row-actions';
import { ListPagination } from '@/components/list-pagination';
import type { FormMeta, LocationOption, MassScheduleRow } from '../../types';
import type { Paginated } from '@/types';

type Props = {
    massSchedules: Paginated<MassScheduleRow>;
    locations: LocationOption[];
    filters: {
        search: string;
        location_id: string;
        status: string;
        sort: string;
        direction: string;
    };
} & Pick<FormMeta, 'days'>;

const STATUS_OPTIONS = [
    { value: '', label: 'Semua status' },
    { value: 'active', label: 'Aktif' },
    { value: 'inactive', label: 'Nonaktif' },
];

export default function MisaRutin({
    massSchedules,
    locations,
    filters,
    days,
}: Props) {
    const [deleting, setDeleting] = useState<MassScheduleRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const applyFilter = (next: Partial<typeof filters>) => {
        router.get(
            massSchedulesList().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hasFilter =
        filters.search !== '' ||
        filters.status !== '' ||
        filters.location_id !== '';

    const sortBy = (sortKey: string, direction: 'asc' | 'desc') => {
        router.get(
            massSchedulesList().url,
            { ...filters, sort: sortKey, direction },
            { preserveScroll: true, replace: true },
        );
    };

    const consequencesOf = (schedule: MassScheduleRow): string[] => {
        const count = schedule.schedule_notices_count ?? 0;
        return count > 0 ? [`${count} pemberitahuan yang berhenti tampil`] : [];
    };

    const empty = (
        <ListEmpty
            title={
                hasFilter
                    ? 'Tidak ada jadwal untuk filter ini.'
                    : 'Belum ada jadwal misa rutin.'
            }
            description={
                hasFilter
                    ? 'Coba reset filter untuk melihat seluruh jadwal.'
                    : 'Tambahkan jadwal pertama untuk mulai.'
            }
        />
    );

    return (
        <>
            <Head title="Misa Rutin" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Misa Rutin"
                    description="Jadwal misa yang berulang setiap minggu."
                    action={
                        <Button asChild>
                            <Link href={createMassSchedule()}>
                                Tambah misa rutin
                            </Link>
                        </Button>
                    }
                />

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder="Cari nama misa atau lokasi"
                    onSearch={(search) => applyFilter({ search })}
                >
                    <FilterSelect
                        id="filter-lokasi"
                        label="Filter lokasi"
                        value={filters.location_id}
                        onChange={(location_id) => applyFilter({ location_id })}
                        options={[
                            { value: '', label: 'Semua lokasi' },
                            ...locations.map((location) => ({
                                value: String(location.id),
                                label: location.name,
                            })),
                        ]}
                    />

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
                                    location_id: '',
                                    sort: '',
                                    direction: '',
                                })
                            }
                        />
                    ) : null}
                </ListToolbar>

                <MassScheduleCards rows={massSchedules.data} days={days} />

                <div className="hidden md:block">
                    <DataTable
                        columns={massScheduleColumns(days, (row) => (
                            <RowActions
                                canUpdate={row.can.update}
                                canDelete={row.can.delete}
                                updateHref={editMassSchedule(row.id)}
                                onDelete={() => setDeleting(row)}
                            />
                        ))}
                        rows={massSchedules.data}
                        sort={filters.sort}
                        direction={filters.direction}
                        onSort={sortBy}
                        rowId={(row) => row.id}
                        empty={empty}
                    />
                </div>

                <ListPagination
                    meta={massSchedules.meta}
                    previous={massSchedules.links.prev}
                    next={massSchedules.links.next}
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
                    itemLabel={`Jadwal misa rutin "${deleting.name}"`}
                    consequences={consequencesOf(deleting)}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);

                        router.delete(destroyMassSchedule(deleting.id), {
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

MisaRutin.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Jadwal Misa', href: massSchedulesIndex() },
        { title: 'Misa Rutin' },
    ],
};
