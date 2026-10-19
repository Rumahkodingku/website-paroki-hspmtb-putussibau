import type { ReactNode } from 'react';
import {
    FlexRender,
    coreRowsFeature,
    rowSortingFeature,
    tableFeatures,
    useTable,
    type ColumnDef,
    type RowData,
} from '@tanstack/react-table';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Text } from '@/components/text';
import { ListEmpty } from './list-parts';

const features = tableFeatures({
    coreRowsFeature,
    rowSortingFeature,
});

export type DataColumn<T extends RowData> = ColumnDef<typeof features, T> & {
    sortKey?: string;
};

type Props<T extends RowData> = {
    columns: DataColumn<T>[];
    rows: T[];
    sort: string;
    direction: string;
    onSort: (sortKey: string, direction: 'asc' | 'desc') => void;
    rowId: (row: T) => string | number;
    empty: ReactNode;
};

function SortableHead<T extends RowData>({
    label,
    sortKey,
    current,
    direction,
    onSort,
}: {
    label: ReactNode;
    sortKey: string | undefined;
    current: string;
    direction: string;
    onSort: Props<T>['onSort'];
}) {
    const active = sortKey !== undefined && current === sortKey;
    const next = active && direction !== 'desc' ? 'desc' : 'asc';

    if (sortKey === undefined) {
        return <TableHead>{label}</TableHead>;
    }

    return (
        <TableHead>
            <button
                type="button"
                onClick={() => onSort(sortKey, next)}
                aria-label={`Urutkan berdasarkan ${typeof label === 'string' ? label : 'kolom ini'}`}
                className="-mx-1 inline-flex items-center gap-1 rounded-sm px-1 py-0.5 hover:text-primary-on-surface focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                {label}

                <span aria-hidden="true" className="text-muted-foreground">
                    {active ? (direction === 'desc' ? '↓' : '↑') : '↕'}
                </span>
            </button>

            <span className="sr-only">
                {active
                    ? direction === 'desc'
                        ? ', diurutkan menurun'
                        : ', diurutkan menaik'
                    : ''}
            </span>
        </TableHead>
    );
}

export function DataTable<T extends RowData>({
    columns,
    rows,
    sort,
    direction,
    onSort,
    rowId,
    empty,
}: Props<T>) {
    const table = useTable({
        features,
        columns,
        data: rows,
        manualSorting: true,
        state: {
            sorting:
                sort === '' ? [] : [{ id: sort, desc: direction === 'desc' }],
        },
        getRowId: (row: T) => String(rowId(row)),
    });

    if (rows.length === 0) {
        return <>{empty}</>;
    }

    return (
        <Table>
            <TableHeader>
                <TableRow>
                    {table.getHeaderGroups()[0]?.headers.map((header) => (
                        <SortableHead
                            key={header.id}
                            label={headerContent(header)}
                            sortKey={sortKeyOf(header.column.columnDef)}
                            current={sort}
                            direction={direction}
                            onSort={onSort}
                        />
                    ))}
                </TableRow>
            </TableHeader>

            <TableBody>
                {table.getRowModel().rows.map((row) => (
                    <TableRow key={row.id}>
                        {row.getAllCells().map((cell) => (
                            <TableCell key={cell.id}>
                                <FlexRender cell={cell} />
                            </TableCell>
                        ))}
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

function headerContent(header: {
    id: string;
    column: { columnDef: { header?: unknown } };
}): ReactNode {
    const content = header.column.columnDef.header;

    if (typeof content === 'function') {
        return (content as (context: unknown) => ReactNode)(undefined);
    }

    return (content as ReactNode) ?? header.id;
}

function sortKeyOf(columnDef: unknown): string | undefined {
    return (columnDef as { sortKey?: string }).sortKey;
}

export function TableEmpty({
    title,
    description,
}: {
    title: string;
    description: string;
}) {
    return <ListEmpty title={title} description={description} />;
}

export function SortHint() {
    return (
        <Text as="p" variant="body-sm" color="muted" className="sr-only">
            Klik nama kolom untuk mengurutkan daftar.
        </Text>
    );
}

/**
 * Renders the actions cell of one row.
 *
 * Declared here rather than in the column module it first shipped with,
 * because four column builders across two features (Jadwal Misa's three lists
 * and the location list in Master Data) pass an actions renderer, and
 * ARCHITECTURE.md Part B section 1.1 puts a shape with more than one consumer
 * in components/. The alternative was importing one feature from another,
 * which that same rule forbids.
 */
export type RowActions<T> = (row: T) => ReactNode;
