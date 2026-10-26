import { describe, expect, it, vi } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    FilterMonth,
    FilterSelect,
    ListToolbar,
} from '@/components/list-parts';

const STATUS_OPTIONS = [
    { value: '', label: 'Semua status' },
    { value: 'active', label: 'Aktif' },
];

describe('FilterSelect', () => {
    it('sends an empty value when the all option is chosen', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();

        render(
            <FilterSelect
                id="filter-status"
                label="Filter status"
                value="active"
                onChange={onChange}
                options={STATUS_OPTIONS}
            />,
        );

        await user.click(screen.getByLabelText('Filter status'));
        await user.click(screen.getByRole('option', { name: 'Semua status' }));

        expect(onChange).toHaveBeenCalledWith('');
    });

    it('sends the option value for a real option', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();

        render(
            <FilterSelect
                id="filter-status"
                label="Filter status"
                value=""
                onChange={onChange}
                options={STATUS_OPTIONS}
            />,
        );

        await user.click(screen.getByLabelText('Filter status'));
        await user.click(screen.getByRole('option', { name: 'Aktif' }));

        expect(onChange).toHaveBeenCalledWith('active');
    });
});

describe('ListToolbar', () => {
    it('hands the typed search text back after debounce', async () => {
        const user = userEvent.setup();
        const onSearch = vi.fn();

        render(
            <ListToolbar
                search=""
                onSearch={onSearch}
                searchPlaceholder="Cari nama"
            />,
        );

        await user.type(screen.getByLabelText('Cari nama'), 'foo bar');

        await waitFor(() => {
            expect(onSearch).toHaveBeenCalledWith('foo bar');
        });
    });

    it('clears the search through the X button', async () => {
        const user = userEvent.setup();
        const onSearch = vi.fn();

        render(
            <ListToolbar
                search="foo"
                onSearch={onSearch}
                searchPlaceholder="Cari nama"
            />,
        );

        await user.click(
            screen.getByRole('button', { name: 'Hapus pencarian' }),
        );

        expect(onSearch).toHaveBeenCalledWith('');
    });
});

describe('FilterMonth', () => {
    it('shows the month label for the current value', () => {
        render(
            <FilterMonth
                id="filter-bulan"
                label="Filter bulan"
                value="2026-10"
                onChange={() => {}}
            />,
        );

        expect(screen.getByLabelText('Filter bulan')).toHaveTextContent(
            /oktober 2026/i,
        );
    });

    it('shows the all-months label when empty', () => {
        render(
            <FilterMonth
                id="filter-bulan"
                label="Filter bulan"
                value=""
                onChange={() => {}}
            />,
        );

        expect(screen.getByLabelText('Filter bulan')).toHaveTextContent(
            'Semua bulan',
        );
    });

    it('clears the value through the popover action', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();

        render(
            <FilterMonth
                id="filter-bulan"
                label="Filter bulan"
                value="2026-10"
                onChange={onChange}
            />,
        );

        await user.click(screen.getByLabelText('Filter bulan'));
        await user.click(
            screen.getByRole('button', { name: 'Hapus filter bulan' }),
        );

        expect(onChange).toHaveBeenCalledWith('');
    });
});
