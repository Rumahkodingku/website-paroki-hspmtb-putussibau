import type { DataColumn, RowActions } from '@/components/data-table';
import { StatusBadge } from '@/components/list-parts';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { formatWibDateTime } from '@/lib/datetime';
import type { RegistrationMethod, ServiceListItem } from '../types';

function RegistrationMethodBadge({
    method,
    label,
}: {
    method: RegistrationMethod;
    label: string;
}) {
    const variant =
        method === 'direct_contact'
            ? 'default'
            : method === 'google_form'
              ? 'outline'
              : 'secondary';

    return <Badge variant={variant}>{label}</Badge>;
}

export function serviceColumns(
    copy: Record<string, string>,
    methods: Record<string, string>,
    actions: RowActions<ServiceListItem>,
): DataColumn<ServiceListItem>[] {
    return [
        {
            id: 'no',
            accessorKey: 'no',
            header: copy.col_no,
            cell: ({ row }) => row.index + 1,
        },
        {
            id: 'title',
            accessorKey: 'title',
            header: copy.col_name,
            sortKey: 'title',
            cell: ({ row }) => (
                <div className="space-y-1">
                    <Text as="p" variant="label">
                        {row.original.title}
                    </Text>
                    <div className="flex flex-wrap items-center gap-2">
                        <Text as="p" variant="body-sm" color="muted">
                            {row.original.slug}
                        </Text>
                        {!row.original.is_active ? (
                            <Badge variant="secondary">
                                {copy.needs_validation}
                            </Badge>
                        ) : null}
                    </div>
                </div>
            ),
        },
        {
            id: 'category',
            accessorKey: 'category',
            header: copy.col_category,
            cell: ({ row }) =>
                row.original.category ? (
                    <Badge variant="secondary">
                        {row.original.category.name}
                    </Badge>
                ) : (
                    '—'
                ),
        },
        {
            id: 'registration_method',
            accessorKey: 'registration_method',
            header: copy.col_registration,
            cell: ({ row }) => (
                <RegistrationMethodBadge
                    method={row.original.registration_method}
                    label={methods[row.original.registration_method]}
                />
            ),
        },
        {
            id: 'is_active',
            accessorKey: 'is_active',
            header: copy.col_status,
            sortKey: 'is_active',
            cell: ({ row }) => <StatusBadge active={row.original.is_active} />,
        },
        {
            id: 'sort_order',
            accessorKey: 'sort_order',
            header: copy.col_order,
            sortKey: 'sort_order',
            cell: ({ row }) => row.original.sort_order,
        },
        {
            id: 'updated_at',
            accessorKey: 'updated_at',
            header: copy.col_updated,
            sortKey: 'updated_at',
            cell: ({ row }) => (
                <Text as="p" variant="body-sm">
                    {row.original.updated_at
                        ? formatWibDateTime(row.original.updated_at)
                        : '—'}
                </Text>
            ),
        },
        {
            id: 'actions',
            header: copy.col_actions,
            cell: ({ row }) => actions(row.original),
        },
    ];
}
