import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index as massSchedulesIndex } from '@/routes/mass-schedules';
import {
    create as createLocation,
    destroy as destroyLocation,
    edit as editLocation,
    index as locationsIndex,
} from '@/routes/locations';
import { DataTable } from '../../components/data-table';
import { locationColumns } from '../../components/columns';
import { DeleteDialog } from '../../components/delete-dialog';
import { LocationCards } from './components/location-cards';
import {
    ListEmpty,
    ListHeader,
    ListToolbar,
    ResetFilters,
} from '../../components/list-parts';
import { RowActions } from '../../components/row-actions';
import { TablePagination } from '../../components/table-pagination';
import type { FormMeta, LocationRow } from '../../types';
import type { Paginated } from '@/types';

type Props = {
    locations: Paginated<LocationRow>;
    filters: {
        search: string;
        status: string;
        sort: string;
        direction: string;
    };
} & Pick<FormMeta, 'types'>;

const STATUS_OPTIONS = [
    { value: '', label: 'Semua status' },
    { value: 'active', label: 'Aktif' },
    { value: 'inactive', label: 'Nonaktif' },
];

export default function Index({ locations, filters, types }: Props) {
    const [deleting, setDeleting] = useState<LocationRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const applyFilter = (next: Partial<typeof filters>) => {
        router.get(
            locationsIndex().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const sortBy = (sortKey: string, direction: 'asc' | 'desc') => {
        router.get(
            locationsIndex().url,
            { ...filters, sort: sortKey, direction },
            { preserveScroll: true, replace: true },
        );
    };

    const hasFilter = filters.search !== '' || filters.status !== '';

    const typeLabel = (value: string): string =>
        types.find((type) => type.value === value)?.label ?? value;

    const consequencesOf = (location: LocationRow): string[] => {
        const notices = location.schedule_notices_count ?? 0;

        return notices > 0
            ? [`${notices} pemberitahuan yang berhenti tampil`]
            : [];
    };

    const canDelete = (location: LocationRow): boolean =>
        (location.mass_schedules_count ?? 0) === 0 &&
        (location.special_masses_count ?? 0) === 0;

    return (
        <>
            <Head title="Lokasi" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Lokasi"
                    description="Gereja paroki, stasi, dan kapel tempat misa dilayani."
                    action={
                        <Button asChild>
                            <Link href={createLocation()}>Tambah lokasi</Link>
                        </Button>
                    }
                />

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder="Cari nama lokasi"
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
                        {STATUS_OPTIONS.map((option) => (
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
                                    sort: '',
                                    direction: '',
                                })
                            }
                        />
                    ) : null}
                </ListToolbar>

                <LocationCards rows={locations.data} typeLabel={typeLabel} />

                <div className="hidden md:block">
                    <DataTable
                        columns={locationColumns(
                            {
                                find: (value) =>
                                    types.find((type) => type.value === value)
                                        ?.label,
                            },
                            (row) => (
                                <RowActions
                                    canUpdate={row.can.update}
                                    canDelete={row.can.delete && canDelete(row)}
                                    updateHref={editLocation(row.id)}
                                    onDelete={() => setDeleting(row)}
                                />
                            ),
                        )}
                        rows={locations.data}
                        sort={filters.sort}
                        direction={filters.direction}
                        onSort={sortBy}
                        rowId={(row) => row.id}
                        empty={
                            <ListEmpty
                                title={
                                    hasFilter
                                        ? 'Tidak ada lokasi untuk filter ini.'
                                        : 'Belum ada lokasi.'
                                }
                                description={
                                    hasFilter
                                        ? 'Coba reset filter untuk melihat seluruh lokasi.'
                                        : 'Tambahkan lokasi pertama untuk mulai.'
                                }
                            />
                        }
                    />
                </div>

                <TablePagination
                    meta={locations.meta}
                    previous={locations.links.prev}
                    next={locations.links.next}
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
                    itemLabel={`Lokasi "${deleting.name}"`}
                    consequences={consequencesOf(deleting)}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);

                        router.delete(destroyLocation(deleting.id), {
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
        { title: 'Lokasi' },
    ],
};
