import { Head, Link, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { formatWibDateTime } from '@/lib/datetime';
import { dashboard } from '@/routes';
import {
    create as createSpecialMass,
    edit as editSpecialMass,
    index as specialMassesIndex,
} from '@/routes/special-masses';
import {
    ListEmpty,
    ListHeader,
    ListToolbar,
    ResetFilters,
    StatusBadge,
} from '../components/list-parts';
import { TablePagination } from '../components/table-pagination';
import type { FormMeta, LocationOption, SpecialMassRow } from '../types';
import type { Paginated } from '@/types';

/*
 * /admin/misa-khusus — the special-mass list.
 *
 * Past masses stay in the list. MASS-02 says they are not deleted, and an admin
 * asking what happened in December should find the answer here rather than in a
 * log. What they must not do is appear on the public page, and that is decided
 * by SpecialMass::scopeUpcoming rather than by this screen.
 *
 * Times render in Asia/Pontianak from the shared displayTimezone prop, never the
 * device's clock (D-01).
 */

type Props = {
    specialMasses: Paginated<SpecialMassRow>;
    locations: LocationOption[];
    filters: { search: string; status: string };
    meta: Pick<FormMeta, 'labels' | 'hints' | 'statuses' | 'days' | 'types'>;
};

const STATUS_OPTIONS = [
    { value: '', label: 'Semua status' },
    { value: 'upcoming', label: 'Mendatang' },
    { value: 'past', label: 'Sudah lewat' },
    { value: 'inactive', label: 'Nonaktif' },
];

export default function Index({ specialMasses, filters }: Props) {
    const { displayTimezone } = usePage().props;

    const applyFilter = (next: Partial<typeof filters>) => {
        router.get(
            specialMassesIndex().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hasFilter = filters.search !== '' || filters.status !== '';

    return (
        <>
            <Head title="Misa Khusus" />

            <Heading title="Misa Khusus" variant="small" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Misa Khusus"
                    description="Misa pada tanggal tertentu."
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
                    <select
                        aria-label="Filter waktu"
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

                {specialMasses.data.length === 0 ? (
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
                ) : (
                    <>
                        <ul className="space-y-3 md:hidden">
                            {specialMasses.data.map((mass) => (
                                <li
                                    key={mass.id}
                                    className="rounded-lg border border-hairline p-4"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="space-y-1">
                                            <Text as="p" variant="body-strong">
                                                {mass.title}
                                            </Text>
                                            <Text
                                                as="p"
                                                variant="caption"
                                                color="muted"
                                            >
                                                {formatWibDateTime(
                                                    mass.start_at,
                                                    displayTimezone,
                                                )}
                                            </Text>
                                            <Text
                                                as="p"
                                                variant="caption"
                                                color="muted"
                                            >
                                                {mass.location.name}
                                            </Text>
                                        </div>
                                        <StatusBadge active={mass.is_active} />
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
                                            Judul
                                        </th>
                                        <th
                                            scope="col"
                                            className="p-3 font-medium"
                                        >
                                            Mulai
                                        </th>
                                        <th
                                            scope="col"
                                            className="p-3 font-medium"
                                        >
                                            Selesai
                                        </th>
                                        <th
                                            scope="col"
                                            className="p-3 font-medium"
                                        >
                                            Lokasi
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
                                    {specialMasses.data.map((mass) => (
                                        <tr
                                            key={mass.id}
                                            className="border-b border-hairline"
                                        >
                                            <td className="p-3">
                                                {mass.title}
                                            </td>
                                            <td className="p-3">
                                                {formatWibDateTime(
                                                    mass.start_at,
                                                    displayTimezone,
                                                )}
                                            </td>
                                            <td className="p-3">
                                                {mass.end_at
                                                    ? formatWibDateTime(
                                                          mass.end_at,
                                                          displayTimezone,
                                                      )
                                                    : '—'}
                                            </td>
                                            <td className="p-3">
                                                {mass.location.name}
                                            </td>
                                            <td className="p-3">
                                                <StatusBadge
                                                    active={mass.is_active}
                                                />
                                            </td>
                                            <td className="p-3 text-right">
                                                {mass.can.update ? (
                                                    <Link
                                                        href={editSpecialMass(
                                                            mass.id,
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
                    meta={specialMasses.meta}
                    previous={specialMasses.links.prev}
                    next={specialMasses.links.next}
                />
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Jadwal Misa' },
        { title: 'Misa Khusus' },
    ],
};
