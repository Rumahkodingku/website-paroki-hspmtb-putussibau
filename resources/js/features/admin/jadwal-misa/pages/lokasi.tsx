import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import {
    create as createLocation,
    edit as editLocation,
    index as locationsIndex,
} from '@/routes/locations';
import {
    ListEmpty,
    ListHeader,
    ListToolbar,
    ResetFilters,
    StatusBadge,
} from '../components/list-parts';
import { TablePagination } from '../components/table-pagination';
import type { FormMeta, LocationRow } from '../types';
import type { Paginated } from '@/types';

/*
 * /admin/lokasi — the location list, and the place MASS-07's warning is raised.
 *
 * There is no delete action here and there is no delete route. PRD 7.3 says the
 * admin "nonaktifkan, tidak hapus"; roadmap 5.6 forbids a permanent-delete
 * endpoint while deactivation covers the need; roadmap 4.5 forbids removing a
 * location that still has schedules. is_active is the whole lifecycle (D-34).
 *
 * Switching a location off therefore goes through the ordinary edit form like
 * any other field change. The dialog below is the confirmation MASS-07 asks for,
 * and it fires only when the row's active_schedules_count is above zero — the
 * one situation where the admin is about to remove something from the public
 * page without deleting it.
 */

type Props = {
    locations: Paginated<LocationRow>;
    filters: { search: string; status: string };
} & Pick<FormMeta, 'types'>;

const STATUS_OPTIONS = [
    { value: '', label: 'Semua status' },
    { value: 'active', label: 'Aktif' },
    { value: 'inactive', label: 'Nonaktif' },
];

export default function Index({ locations, filters, types }: Props) {
    const applyFilter = (next: Partial<typeof filters>) => {
        router.get(
            locationsIndex().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hasFilter = filters.search !== '' || filters.status !== '';

    const typeLabel = (value: string): string =>
        types.find((type) => type.value === value)?.label ?? value;

    return (
        <>
            <Head title="Lokasi" />

            <Heading title="Lokasi" variant="small" />

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

                    {hasFilter ? (
                        <ResetFilters
                            onReset={() =>
                                applyFilter({ search: '', status: '' })
                            }
                        />
                    ) : null}
                </ListToolbar>

                {locations.data.length === 0 ? (
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
                ) : (
                    <>
                        <ul className="space-y-3 md:hidden">
                            {locations.data.map((location) => (
                                <li
                                    key={location.id}
                                    className="rounded-lg border border-hairline p-4"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="space-y-1">
                                            <Text as="p" variant="body-strong">
                                                {location.name}
                                            </Text>
                                            <Text
                                                as="p"
                                                variant="caption"
                                                color="muted"
                                            >
                                                {typeLabel(location.type)}
                                            </Text>
                                            {location.active_schedules_count ? (
                                                <Text
                                                    as="p"
                                                    variant="caption"
                                                    color="muted"
                                                >
                                                    {
                                                        location.active_schedules_count
                                                    }{' '}
                                                    jadwal aktif
                                                </Text>
                                            ) : null}
                                        </div>
                                        <StatusBadge
                                            active={location.is_active}
                                        />
                                    </div>
                                </li>
                            ))}
                        </ul>

                        <div className="hidden overflow-x-auto md:block">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-hairline text-left">
                                        <th
                                            scope="col"
                                            className="p-3 font-medium"
                                        >
                                            Nama
                                        </th>
                                        <th
                                            scope="col"
                                            className="p-3 font-medium"
                                        >
                                            Jenis
                                        </th>
                                        <th
                                            scope="col"
                                            className="p-3 font-medium"
                                        >
                                            Jadwal aktif
                                        </th>
                                        <th
                                            scope="col"
                                            className="p-3 font-medium"
                                        >
                                            Peta
                                        </th>
                                        <th
                                            scope="col"
                                            className="p-3 font-medium"
                                        >
                                            Status
                                        </th>
                                        <th
                                            scope="col"
                                            className="p-3 font-medium"
                                        >
                                            <span className="sr-only">
                                                Aksi
                                            </span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {locations.data.map((location) => (
                                        <tr
                                            key={location.id}
                                            className="border-b border-hairline"
                                        >
                                            <td className="p-3">
                                                {location.name}
                                            </td>
                                            <td className="p-3">
                                                {typeLabel(location.type)}
                                            </td>
                                            <td className="p-3">
                                                {location.active_schedules_count ??
                                                    '—'}
                                            </td>
                                            <td className="p-3">
                                                {location.maps_url
                                                    ? 'Ada'
                                                    : '—'}
                                            </td>
                                            <td className="p-3">
                                                <StatusBadge
                                                    active={location.is_active}
                                                />
                                            </td>
                                            <td className="p-3 text-right">
                                                {location.can.update ? (
                                                    <Link
                                                        href={editLocation(
                                                            location.id,
                                                        )}
                                                        className="rounded-sm text-primary hover:underline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                                                    >
                                                        Ubah
                                                    </Link>
                                                ) : null}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </>
                )}

                <TablePagination
                    meta={locations.meta}
                    previous={locations.links.prev}
                    next={locations.links.next}
                />
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Jadwal Misa', href: locationsIndex() },
        { title: 'Lokasi' },
    ],
};
