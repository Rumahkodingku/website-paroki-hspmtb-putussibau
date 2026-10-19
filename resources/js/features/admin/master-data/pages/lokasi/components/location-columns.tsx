import type { DataColumn, RowActions } from '@/components/data-table';
import { Text } from '@/components/text';
import type { LocationRow } from '../../../types/location';

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
                <Text as="p" variant="body-sm">
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
            header: 'Aksi',
            cell: ({ row }) => actions(row.original),
        },
    ];
}
