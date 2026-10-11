import type { ReactNode } from 'react';
import type { DataColumn } from './data-table';
import { StatusBadge, NoticeStatusBadge } from './list-parts';
import { Text } from '@/components/text';
import {
    formatDayName,
    formatWibDateTime,
    formatWibTime,
} from '@/lib/datetime';
import type {
    LocationRow,
    MassScheduleRow,
    ScheduleNoticeRow,
    SpecialMassRow,
} from '../types';

export type RowActions<T> = (row: T) => ReactNode;

export function locationColumns(
    types: { find: (value: string) => string | undefined },
    actions: RowActions<LocationRow>,
): DataColumn<LocationRow>[] {
    return [
        {
            id: 'no',
            accessorKey: 'no',
            header: 'No',
            cell: ({ row }) => row.index + 1,
        },
        {
            id: 'name',
            accessorKey: 'name',
            header: 'Nama lokasi',
            sortKey: 'name',
            cell: ({ row }) => (
                <Text as="p" variant="caption">
                    {row.original.name}
                </Text>
            ),
        },
        {
            id: 'type',
            accessorKey: 'type',
            header: 'Jenis',
            sortKey: 'type',
            cell: ({ row }) =>
                types.find(row.original.type) ?? row.original.type,
        },
        {
            id: 'active_schedules_count',
            header: 'Jadwal aktif',
            cell: ({ row }) => row.original.active_schedules_count ?? '—',
        },
        {
            id: 'maps_url',
            header: 'Peta',
            cell: ({ row }) => (row.original.maps_url ? 'Ada' : '—'),
        },
        {
            id: 'actions',
            header: () => <span className="sr-only">Aksi</span>,
            cell: ({ row }) => actions(row.original),
        },
    ];
}

export function massScheduleColumns(
    days: Array<{ value: number; label: string }>,
    actions: RowActions<MassScheduleRow>,
): DataColumn<MassScheduleRow>[] {
    const dayLabel = (value: number): string =>
        days.find((day) => day.value === value)?.label ?? formatDayName(value);

    return [
        {
            id: 'no',
            accessorKey: 'no',
            header: 'No',
            cell: ({ row }) => row.index + 1,
        },
        {
            id: 'day_of_week',
            accessorKey: 'day_of_week',
            header: 'Hari',
            sortKey: 'day_of_week',
            cell: ({ row }) => dayLabel(row.original.day_of_week),
        },
        {
            id: 'start_time',
            accessorKey: 'start_time',
            header: 'Jam',
            sortKey: 'start_time',
            cell: ({ row }) => formatWibTime(row.original.start_time),
        },
        {
            id: 'name',
            accessorKey: 'name',
            header: 'Nama misa',
            sortKey: 'name',
            cell: ({ row }) => (
                <Text as="p" variant="caption">
                    {row.original.name}
                </Text>
            ),
        },
        {
            id: 'location',
            header: 'Lokasi',
            sortKey: 'location',
            cell: ({ row }) => row.original.location.name,
        },
        {
            id: 'is_active',
            accessorKey: 'is_active',
            header: 'Status',
            sortKey: 'is_active',
            cell: ({ row }) => <StatusBadge active={row.original.is_active} />,
        },
        {
            id: 'actions',
            header: () => <span className="sr-only">Aksi</span>,
            cell: ({ row }) => actions(row.original),
        },
    ];
}

export function specialMassColumns(
    actions: RowActions<SpecialMassRow>,
): DataColumn<SpecialMassRow>[] {
    return [
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
                <Text as="p" variant="caption">
                    {row.original.title}
                </Text>
            ),
        },
        {
            id: 'start_at',
            accessorKey: 'start_at',
            header: 'Mulai',
            sortKey: 'start_at',
            cell: ({ row }) => formatWibDateTime(row.original.start_at),
        },
        {
            id: 'end_at',
            accessorKey: 'end_at',
            header: 'Selesai',
            sortKey: 'end_at',
            cell: ({ row }) =>
                row.original.end_at
                    ? formatWibDateTime(row.original.end_at)
                    : '—',
        },
        {
            id: 'location',
            header: 'Lokasi',
            sortKey: 'location',
            cell: ({ row }) => row.original.location.name,
        },
        {
            id: 'is_active',
            accessorKey: 'is_active',
            header: 'Status',
            sortKey: 'is_active',
            cell: ({ row }) => <StatusBadge active={row.original.is_active} />,
        },
        {
            id: 'actions',
            header: () => <span className="sr-only">Aksi</span>,
            cell: ({ row }) => actions(row.original),
        },
    ];
}

export function scheduleNoticeColumns(
    generalLabel: string,
    actions: RowActions<ScheduleNoticeRow>,
): DataColumn<ScheduleNoticeRow>[] {
    const scopeLabel = (notice: ScheduleNoticeRow): string => {
        if (notice.is_general) {
            return generalLabel;
        }

        if (notice.mass_schedule) {
            return `Jadwal rutin — ${notice.mass_schedule.name}`;
        }

        if (notice.special_mass) {
            return `Misa khusus — ${notice.special_mass.title}`;
        }

        return notice.location ? `Lokasi — ${notice.location.name}` : '—';
    };

    return [
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
                <Text as="p" variant="caption">
                    {row.original.title}
                </Text>
            ),
        },
        {
            id: 'target',
            header: 'Target',
            cell: ({ row }) => scopeLabel(row.original),
        },
        {
            id: 'starts_at',
            accessorKey: 'starts_at',
            header: 'Mulai tampil',
            sortKey: 'starts_at',
            cell: ({ row }) => formatWibDateTime(row.original.starts_at),
        },
        {
            id: 'status',
            header: 'Status',
            cell: ({ row }) => (
                <div className="flex items-center gap-2">
                    <NoticeStatusBadge status={row.original.status} />

                    {row.original.target_deleted ? (
                        <Text as="span" variant="micro-legal" color="muted">
                            Target terhapus
                        </Text>
                    ) : null}
                </div>
            ),
        },
        {
            id: 'actions',
            header: () => <span className="sr-only">Aksi</span>,
            cell: ({ row }) => actions(row.original),
        },
    ];
}
