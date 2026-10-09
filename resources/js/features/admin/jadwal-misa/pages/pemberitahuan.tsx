import { Head, Link, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { formatWibDateTime } from '@/lib/datetime';
import { dashboard } from '@/routes';
import {
    create as createScheduleNotice,
    edit as editScheduleNotice,
    index as scheduleNoticesIndex,
} from '@/routes/schedule-notices';
import {
    ListEmpty,
    ListHeader,
    ListToolbar,
    NoticeStatusBadge,
    ResetFilters,
} from '../components/list-parts';
import { TablePagination } from '../components/table-pagination';
import type { FormMeta, ScheduleNoticeRow } from '../types';
import type { Paginated } from '@/types';

/*
 * /admin/pemberitahuan-jadwal — the notice list.
 *
 * Every notice appears here, including the ones no longer visible. An admin
 * asking "did the January notice ever go up?" needs an answer, and hiding the
 * finished ones would answer it by deletion. Whether a notice is on the public
 * page is a derived status on the row, computed from is_active and the display
 * window — MASS-05's three conditions in one place — so the list shows the
 * reason rather than filtering on it.
 */

type Props = {
    notices: Paginated<ScheduleNoticeRow>;
    filters: { search: string; status: string };
    meta: Pick<
        FormMeta,
        'labels' | 'hints' | 'statuses' | 'days' | 'types' | 'noticeScopes'
    >;
};

const STATUS_OPTIONS = [
    { value: '', label: 'Semua status' },
    { value: 'visible', label: 'Sedang tampil' },
    { value: 'scheduled', label: 'Terjadwal' },
    { value: 'draft', label: 'Nonaktif' },
    { value: 'expired', label: 'Kedaluwarsa' },
];

export default function Index({ notices, filters, meta }: Props) {
    const { displayTimezone } = usePage().props;

    const applyFilter = (next: Partial<typeof filters>) => {
        router.get(
            scheduleNoticesIndex().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hasFilter = filters.search !== '' || filters.status !== '';

    const scopeLabel = (notice: ScheduleNoticeRow): string => {
        if (notice.is_general) {
            return meta.noticeScopes?.general ?? 'Umum';
        }

        if (notice.mass_schedule) {
            return `Jadwal rutin — ${notice.mass_schedule.name}`;
        }

        if (notice.special_mass) {
            return `Misa khusus — ${notice.special_mass.title}`;
        }

        return notice.location ? `Lokasi — ${notice.location.name}` : 'Umum';
    };

    return (
        <>
            <Head title="Pemberitahuan Jadwal" />

            <Heading title="Pemberitahuan Jadwal" variant="small" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Pemberitahuan Perubahan Jadwal"
                    description="Pemberitahuan yang tampil di halaman publik selama rentang waktunya."
                    action={
                        <Button asChild>
                            <Link href={createScheduleNotice()}>
                                Tambah pemberitahuan
                            </Link>
                        </Button>
                    }
                />

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder="Cari judul atau isi pemberitahuan"
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

                {notices.data.length === 0 ? (
                    <ListEmpty
                        title={
                            hasFilter
                                ? 'Tidak ada pemberitahuan untuk filter ini.'
                                : 'Belum ada pemberitahuan.'
                        }
                        description={
                            hasFilter
                                ? 'Coba reset filter untuk melihat seluruh pemberitahuan.'
                                : 'Tambahkan pemberitahuan pertama untuk mulai.'
                        }
                    />
                ) : (
                    <>
                        <ul className="space-y-3 md:hidden">
                            {notices.data.map((notice) => (
                                <li
                                    key={notice.id}
                                    className="rounded-lg border border-hairline p-4"
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="space-y-1">
                                            <Text as="p" variant="body-strong">
                                                {notice.title}
                                            </Text>
                                            <Text
                                                as="p"
                                                variant="caption"
                                                color="muted"
                                            >
                                                {scopeLabel(notice)}
                                            </Text>
                                            <Text
                                                as="p"
                                                variant="caption"
                                                color="muted"
                                            >
                                                {formatWibDateTime(
                                                    notice.starts_at,
                                                    displayTimezone,
                                                )}
                                            </Text>
                                        </div>
                                        <NoticeStatusBadge
                                            status={notice.status}
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
                                            Judul
                                        </th>
                                        <th
                                            scope="col"
                                            className="p-3 font-medium"
                                        >
                                            Target
                                        </th>
                                        <th
                                            scope="col"
                                            className="p-3 font-medium"
                                        >
                                            Mulai tampil
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
                                    {notices.data.map((notice) => (
                                        <tr
                                            key={notice.id}
                                            className="border-b border-hairline"
                                        >
                                            <td className="p-3">
                                                {notice.title}
                                            </td>
                                            <td className="p-3">
                                                {scopeLabel(notice)}
                                            </td>
                                            <td className="p-3">
                                                {formatWibDateTime(
                                                    notice.starts_at,
                                                    displayTimezone,
                                                )}
                                            </td>
                                            <td className="p-3">
                                                <NoticeStatusBadge
                                                    status={notice.status}
                                                />
                                            </td>
                                            <td className="p-3 text-right">
                                                {notice.can.update ? (
                                                    <Link
                                                        href={editScheduleNotice(
                                                            notice.id,
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
                    meta={notices.meta}
                    previous={notices.links.prev}
                    next={notices.links.next}
                />
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Jadwal Misa' },
        { title: 'Pemberitahuan' },
    ],
};
