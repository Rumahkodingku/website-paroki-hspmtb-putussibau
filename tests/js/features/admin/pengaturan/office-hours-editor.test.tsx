import { describe, expect, it, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    OfficeHoursEditor,
    emptyOfficeHoursWeek,
} from '@/features/admin/pengaturan/components/office-hours-editor';
import type { OfficeHoursWeek } from '@/features/admin/pengaturan/types';

/*
 * The weekly office-hours editor.
 *
 * The behaviour worth pinning is what a switch does to the times beside it:
 * switching a day off has to disable its inputs without clearing them, so
 * switching it back on restores what was there. Everything else here is the
 * shape of the value the form sends.
 */

const DAY_LABELS = [
    'Senin',
    'Selasa',
    'Rabu',
    'Kamis',
    'Jumat',
    'Sabtu',
    'Minggu',
];

function openWeek(): OfficeHoursWeek {
    const week = emptyOfficeHoursWeek();

    for (const day of [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
    ] as const) {
        week[day] = { is_open: true, open_time: '08:00', close_time: '15:00' };
    }

    return week;
}

function lastWeek(onChange: ReturnType<typeof vi.fn>): OfficeHoursWeek {
    return onChange.mock.calls.at(-1)?.[0] as OfficeHoursWeek;
}

describe('OfficeHoursEditor', () => {
    it('renders seven days in week order', () => {
        render(
            <OfficeHoursEditor
                id="oh"
                value={emptyOfficeHoursWeek()}
                onChange={() => {}}
            />,
        );

        for (const label of DAY_LABELS) {
            expect(screen.getByText(label)).toBeInTheDocument();
        }

        expect(screen.getAllByRole('switch')).toHaveLength(7);
    });

    it('marks open and closed days with a status label', () => {
        render(
            <OfficeHoursEditor
                id="oh"
                value={openWeek()}
                onChange={() => {}}
            />,
        );

        expect(screen.getAllByText('Buka')).toHaveLength(5);
        expect(screen.getAllByText('Tutup')).toHaveLength(2);
    });

    it('disables the time inputs of a closed day and enables the rest', () => {
        render(
            <OfficeHoursEditor
                id="oh"
                value={openWeek()}
                onChange={() => {}}
            />,
        );

        expect(screen.getByLabelText('Jam buka Senin')).toBeEnabled();
        expect(screen.getByLabelText('Jam tutup Senin')).toBeEnabled();
        expect(screen.getByLabelText('Jam buka Sabtu')).toBeDisabled();
        expect(screen.getByLabelText('Jam tutup Sabtu')).toBeDisabled();
    });

    it('switches one day independently of the others', async () => {
        const onChange = vi.fn();

        render(
            <OfficeHoursEditor
                id="oh"
                value={openWeek()}
                onChange={onChange}
            />,
        );

        await userEvent.click(
            screen.getByRole('switch', { name: 'Aktifkan pelayanan Senin' }),
        );

        const week = lastWeek(onChange);

        expect(week.monday.is_open).toBe(false);
        expect(week.tuesday.is_open).toBe(true);
    });

    it('keeps the times when a day is switched off', async () => {
        const onChange = vi.fn();

        render(
            <OfficeHoursEditor
                id="oh"
                value={openWeek()}
                onChange={onChange}
            />,
        );

        await userEvent.click(
            screen.getByRole('switch', { name: 'Aktifkan pelayanan Senin' }),
        );

        const week = lastWeek(onChange);

        expect(week.monday.open_time).toBe('08:00');
        expect(week.monday.close_time).toBe('15:00');
    });

    it('reports a changed time for the right day', async () => {
        const onChange = vi.fn();

        render(
            <OfficeHoursEditor
                id="oh"
                value={openWeek()}
                onChange={onChange}
            />,
        );

        await userEvent.click(
            screen.getByRole('button', { name: 'Jam buka Senin' }),
        );

        const comboboxes = screen.getAllByRole('combobox');
        await userEvent.click(comboboxes[0]);
        await userEvent.click(screen.getByRole('option', { name: '09' }));

        await userEvent.click(comboboxes[1]);
        await userEvent.click(screen.getByRole('option', { name: '30' }));

        const week = lastWeek(onChange);

        expect(week.monday.open_time).toBe('09:30');
        expect(week.monday.close_time).toBe('15:00');
        expect(week.tuesday.open_time).toBe('08:00');
    });

    it('shows a legacy value instead of the editor, and can replace it', async () => {
        const onChange = vi.fn();

        render(
            <OfficeHoursEditor
                id="oh"
                value="Senin–Jumat, 08.00–15.00"
                onChange={onChange}
            />,
        );

        expect(
            screen.getByText('Senin–Jumat, 08.00–15.00'),
        ).toBeInTheDocument();
        expect(screen.queryByLabelText('Jam buka Senin')).toBeNull();

        await userEvent.click(
            screen.getByRole('button', {
                name: 'Ganti dengan jadwal mingguan',
            }),
        );

        expect(lastWeek(onChange).monday.is_open).toBe(false);
    });

    it('treats an empty string as "not configured", not as legacy text', () => {
        // The generic settings form turns null into '' on the way in, which must
        // not drop the admin into the legacy branch.
        render(<OfficeHoursEditor id="oh" value="" onChange={() => {}} />);

        expect(screen.queryByText('Jadwal lama masih berupa teks')).toBeNull();
        expect(screen.getAllByRole('switch')).toHaveLength(7);
    });

    it('renders a per-day validation message', () => {
        render(
            <OfficeHoursEditor
                id="oh"
                value={openWeek()}
                errors={{
                    'settings.office_hours.monday.open_time':
                        'Jam buka Senin wajib diisi saat pelayanan aktif.',
                }}
                onChange={() => {}}
            />,
        );

        expect(
            screen.getByText(
                'Jam buka Senin wajib diisi saat pelayanan aktif.',
            ),
        ).toBeInTheDocument();
    });
});
