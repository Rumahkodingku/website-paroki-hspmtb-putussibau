import type { DataColumn, RowActions } from '@/components/data-table';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import type { AreaRow } from '../types';

export function areaColumns(
    nameById: Map<number, string>,
    actions: RowActions<AreaRow>,
): DataColumn<AreaRow>[] {
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
            header: 'Nama',
            sortKey: 'name',
            cell: ({ row }) => (
                <div
                    data-test={`area-row-${row.original.slug}`}
                    className={
                        row.original.parent_id === null
                            ? 'space-y-0.5'
                            : 'space-y-0.5 border-l-2 border-hairline ps-4'
                    }
                >
                    <Text as="p" variant="caption-strong">
                        {row.original.name}
                    </Text>
                    <Text as="p" variant="caption" color="muted">
                        {row.original.slug}
                    </Text>
                </div>
            ),
        },
        {
            id: 'type',
            accessorKey: 'type',
            header: 'Tipe',
            sortKey: 'type',
            cell: ({ row }) => (
                <Badge variant="secondary">{row.original.type_label}</Badge>
            ),
        },
        {
            id: 'parent',
            header: 'Wilayah Induk',
            cell: ({ row }) =>
                row.original.parent_id === null
                    ? '—'
                    : (nameById.get(row.original.parent_id) ?? '—'),
        },
        {
            id: 'leader_name',
            header: 'Ketua',
            cell: ({ row }) => row.original.leader_name ?? '—',
        },
        {
            id: 'is_active',
            accessorKey: 'is_active',
            header: 'Status',
            sortKey: 'is_active',
            cell: ({ row }) => (
                <Badge
                    variant={row.original.is_active ? 'default' : 'secondary'}
                >
                    {row.original.is_active ? 'Aktif' : 'Nonaktif'}
                </Badge>
            ),
        },
        {
            id: 'sort_order',
            accessorKey: 'sort_order',
            header: 'Urutan',
            sortKey: 'sort_order',
            cell: ({ row }) => row.original.sort_order,
        },
        {
            id: 'actions',
            header: 'Aksi',
            cell: ({ row }) => actions(row.original),
        },
    ];
}
