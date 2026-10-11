import { describe, expect, it } from 'vite-plus/test';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { DataTable, type DataColumn } from '@/components/data-table';

/*
 * The shared table, and the one thing it does that a plain <table> cannot.
 *
 * Sorting is server-side, so the header's job is not to reorder rows — it is to
 * report which column was clicked and which way. That is what these tests
 * check, because the alternative failure is a table that looks sortable and
 * silently does nothing until the page reloads behind a URL nobody sees.
 *
 * The rows below are deliberately not in sorted order. A test that passed an
 * already-sorted array would not notice if the component started sorting them
 * itself, which is exactly the bug this module must not have.
 */

type Row = { id: number; name: string };

const ROWS: Row[] = [
    { id: 3, name: 'Misa Kamis' },
    { id: 1, name: 'Misa Minggu Pagi' },
    { id: 2, name: 'Misa Minggu Sore' },
];

function columns(): DataColumn<Row>[] {
    return [
        {
            id: 'name',
            accessorKey: 'name',
            header: 'Nama misa',
            sortKey: 'name',
            cell: ({ row }: { row: { original: Row } }) => row.original.name,
        },
        {
            // No sortKey: a column with nothing behind it, so the header must
            // not offer to sort it.
            id: 'status',
            header: 'Status',
            cell: () => 'Aktif',
        },
        {
            id: 'actions',
            header: () => <span className="sr-only">Aksi</span>,
            cell: () => null,
        },
    ] as unknown as DataColumn<Row>[];
}

function renderTable({
    onSort = () => {},
    sort = '',
    direction = '',
    rows = ROWS,
}: {
    onSort?: (key: string, direction: 'asc' | 'desc') => void;
    sort?: string;
    direction?: string;
    rows?: Row[];
} = {}) {
    return render(
        <DataTable
            columns={columns()}
            rows={rows}
            sort={sort}
            direction={direction}
            onSort={onSort}
            rowId={(row) => row.id}
            empty={<p>Tidak ada jadwal.</p>}
        />,
    );
}

describe('DataTable', () => {
    it('renders a row per record, in the order it was given', () => {
        renderTable();

        expect(screen.getAllByRole('row')).toHaveLength(ROWS.length + 1);
        expect(screen.getAllByRole('columnheader')).toHaveLength(3);

        // Server-sorted already; the table must not reorder what it received.
        const names = screen
            .getAllByRole('row')
            .slice(1)
            .map((row) => row.textContent);
        expect(names[0]).toContain('Misa Kamis');
        expect(names[2]).toContain('Misa Minggu Sore');
    });

    it('reports the column and an ascending direction on the first click', async () => {
        const seen: Array<[string, string]> = [];

        renderTable({
            onSort: (key, direction) => seen.push([key, direction]),
        });

        await userEvent.click(
            screen.getByRole('button', { name: /Nama misa/ }),
        );

        // Ascending first, because that is what an unsorted column means.
        expect(seen).toEqual([['name', 'asc']]);
    });

    it('flips the direction when an ascending column is clicked again', async () => {
        const seen: Array<[string, string]> = [];

        renderTable({
            onSort: (key, direction) => seen.push([key, direction]),
            sort: 'name',
            direction: 'asc',
        });

        await userEvent.click(
            screen.getByRole('button', { name: /Nama misa/ }),
        );

        expect(seen).toEqual([['name', 'desc']]);
    });

    it('returns to ascending after a descending column', async () => {
        const seen: Array<[string, string]> = [];

        renderTable({
            onSort: (key, direction) => seen.push([key, direction]),
            sort: 'name',
            direction: 'desc',
        });

        await userEvent.click(
            screen.getByRole('button', { name: /Nama misa/ }),
        );

        expect(seen).toEqual([['name', 'asc']]);
    });

    it('offers no button for a column that cannot be sorted', () => {
        renderTable();

        expect(
            screen.getByRole('button', { name: /Nama misa/ }),
        ).toBeInTheDocument();

        // A button here would be a promise the table cannot keep.
        expect(screen.queryByRole('button', { name: /Status/ })).toBeNull();
    });

    it('shows the empty state instead of an empty table', () => {
        renderTable({ rows: [] });

        expect(screen.getByText('Tidak ada jadwal.')).toBeInTheDocument();
        expect(screen.queryByRole('table')).toBeNull();
    });

    it('announces the direction of the sorted column', () => {
        renderTable({ sort: 'name', direction: 'desc' });

        // The arrow glyph is decoration; this is what a screen reader reads.
        expect(screen.getByText(/diurutkan menurun/)).toBeInTheDocument();
    });

    it('says nothing about direction when no column is sorted', () => {
        renderTable();

        expect(screen.queryByText(/diurutkan/)).toBeNull();
    });
});
