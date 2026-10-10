import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { formatDayName, formatWibTime } from '@/lib/datetime';
import { dashboard } from '@/routes';
import { index as locationsIndex } from '@/routes/locations';
import {
    create as createMassSchedule,
    edit as editMassSchedule,
    index as massSchedulesIndex,
} from '@/routes/mass-schedules';
import { index as scheduleNoticesIndex } from '@/routes/schedule-notices';
import { index as specialMassesIndex } from '@/routes/special-masses';
import {
    ListEmpty,
    ListHeader,
    ListToolbar,
    ResetFilters,
    StatusBadge,
} from '../components/list-parts';
import { TablePagination } from '../components/table-pagination';
import type {
    FormMeta,
    LocationOption,
    MassScheduleRow,
    ModuleStats,
} from '../types';
import type { Paginated } from '@/types';

/*
 * /admin/jadwal-misa — the module summary and the recurring-mass list.
 *
 * One page carrying both jobs, because PRD 5.2 maps this URL to the recurring
 * schedules while roadmap 6.1 asks for a module summary with four shortcuts. The
 * summary sits above the table rather than on a URL of its own (D-38), which
 * keeps PRD's four URLs exact and adds no redirect.
 *
 * The four counts come from the database and use the same scopes the public page
 * uses, so a number here cannot disagree with what a visitor sees.
 *
 * Filters live in the URL rather than in component state, per ARCHITECTURE.md
 * Part B section 6: a shareable admin URL survives a reload for free, and the
 * browser's back button steps through filter changes the way an admin expects.
 */

type Props = {
    massSchedules: Paginated<MassScheduleRow>;
    locations: LocationOption[];
    stats: ModuleStats;
    filters: { search: string; location_id: string; status: string };
} & Pick<FormMeta, 'days'>;

const STATUS_OPTIONS = [
    { value: '', label: 'Semua status' },
    { value: 'active', label: 'Aktif' },
    { value: 'inactive', label: 'Nonaktif' },
];

const SHORTCUTS = [
    { title: 'Lokasi', href: locationsIndex },
    { title: 'Misa Rutin', href: massSchedulesIndex },
    { title: 'Misa Khusus', href: specialMassesIndex },
    { title: 'Pemberitahuan', href: scheduleNoticesIndex },
];

export default function Index({
    massSchedules,
    locations,
    stats,
    filters,
    days,
}: Props) {
    const applyFilter = (next: Partial<typeof filters>) => {
        router.get(
            massSchedulesIndex().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hasFilter =
        filters.search !== '' ||
        filters.status !== '' ||
        filters.location_id !== '';

    const dayLabel = (value: number): string =>
        days.find((day) => day.value === value)?.label ?? formatDayName(value);

    return (
        <>
            <Head title="Jadwal Misa" />

            <Heading title="Jadwal Misa" variant="small" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Misa Rutin"
                    description="Kelola lokasi, jadwal misa rutin, misa khusus, dan pemberitahuan perubahan jadwal."
                    action={
                        <Button asChild>
                            <Link href={createMassSchedule()}>
                                Tambah jadwal
                            </Link>
                        </Button>
                    }
                />

                {/* Roadmap 6.1's four statistics, counted in the database. */}
                <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {[
                        {
                            label: 'Jadwal rutin aktif',
                            value: stats.active_mass_schedules,
                        },
                        {
                            label: 'Lokasi aktif',
                            value: stats.active_locations,
                        },
                        {
                            label: 'Misa khusus mendatang',
                            value: stats.upcoming_special_masses,
                        },
                        {
                            label: 'Notice sedang tampil',
                            value: stats.visible_notices,
                        },
                    ].map((stat) => (
                        <div
                            key={stat.label}
                            className="rounded-lg border border-hairline p-4"
                        >
                            <dt className="text-sm text-muted-foreground">
                                {stat.label}
                            </dt>
                            <dd className="mt-1 text-2xl font-semibold">
                                {stat.value}
                            </dd>
                        </div>
                    ))}
                </dl>

                {/* Roadmap 6.1's four shortcuts. Cards rather than a nested
                    sidebar: a second navigation column would be a larger change
                    to the admin shell than this module warrants, and the arch
                    test forbids layouts/ importing features/, so the array could
                    not live there without threading it through every page. */}
                <nav
                    aria-label="Bagian modul Jadwal Misa"
                    className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                >
                    {SHORTCUTS.map((shortcut) => (
                        <Link
                            key={shortcut.title}
                            href={shortcut.href()}
                            className="rounded-lg border border-hairline p-4 transition-colors hover:border-primary focus-visible:border-primary focus-visible:ring-2 focus-visible:outline-none"
                        >
                            <Text as="p" variant="body-strong">
                                {shortcut.title}
                            </Text>
                        </Link>
                    ))}
                </nav>

                <ListHeader title="Jadwal Misa Rutin" />

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder="Cari nama misa atau lokasi"
                    onSearch={(search) => applyFilter({ search })}
                >
                    <select
                        aria-label="Filter lokasi"
                        value={filters.location_id}
                        onChange={(event) =>
                            applyFilter({ location_id: event.target.value })
                        }
                        className="h-11 rounded-md border border-hairline bg-background px-3 text-sm focus-visible:border-primary"
                    >
                        <option value="">Semua lokasi</option>
                        {locations.map((location) => (
                            <option
                                key={location.id}
                                value={String(location.id)}
                            >
                                {location.name}
                            </option>
                        ))}
                    </select>

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
                                applyFilter({
                                    search: '',
                                    status: '',
                                    location_id: '',
                                })
                            }
                        />
                    ) : null}
                </ListToolbar>

                {massSchedules.data.length === 0 ? (
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
                ) : (
                    <>
                        {/* Cards below md, table from md up. AC-MASS-5 asks for
                            a list rather than a cropped table on a phone, and
                            ADM-01 says the admin has to work on one. */}
                        <ul className="space-y-3 md:hidden">
                            {massSchedules.data.map((schedule) => (
                                <li
                                    key={schedule.id}
                                    className="rounded-lg border border-hairline p-4"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="space-y-1">
                                            <Text as="p" variant="body-strong">
                                                {schedule.name}
                                            </Text>
                                            <Text
                                                as="p"
                                                variant="caption"
                                                color="muted"
                                            >
                                                {dayLabel(schedule.day_of_week)}
                                                ,{' '}
                                                {formatWibTime(
                                                    schedule.start_time,
                                                )}
                                            </Text>
                                            <Text
                                                as="p"
                                                variant="caption"
                                                color="muted"
                                            >
                                                {schedule.location.name}
                                                {schedule.language
                                                    ? ` · ${schedule.language}`
                                                    : ''}
                                            </Text>
                                        </div>
                                        <StatusBadge
                                            active={schedule.is_active}
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
                                            Hari
                                        </th>
                                        <th
                                            scope="col"
                                            className="p-3 font-medium"
                                        >
                                            Jam
                                        </th>
                                        <th
                                            scope="col"
                                            className="p-3 font-medium"
                                        >
                                            Nama misa
                                        </th>
                                        <th
                                            scope="col"
                                            className="p-3 font-medium"
                                        >
                                            Bahasa
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
                                    {massSchedules.data.map((schedule) => (
                                        <tr
                                            key={schedule.id}
                                            className="border-b border-hairline"
                                        >
                                            <td className="p-3">
                                                {dayLabel(schedule.day_of_week)}
                                            </td>
                                            <td className="p-3">
                                                {formatWibTime(
                                                    schedule.start_time,
                                                )}
                                            </td>
                                            <td className="p-3">
                                                {schedule.name}
                                            </td>
                                            <td className="p-3">
                                                {schedule.language ?? '—'}
                                            </td>
                                            <td className="p-3">
                                                {schedule.location.name}
                                            </td>
                                            <td className="p-3">
                                                <StatusBadge
                                                    active={schedule.is_active}
                                                />
                                            </td>
                                            <td className="p-3 text-right">
                                                {schedule.can.update ? (
                                                    <Link
                                                        href={editMassSchedule(
                                                            schedule.id,
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
                    meta={massSchedules.meta}
                    previous={massSchedules.links.prev}
                    next={massSchedules.links.next}
                />
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Jadwal Misa' },
    ],
};
