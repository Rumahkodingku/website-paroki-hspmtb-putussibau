import { describe, expect, it } from 'vite-plus/test';
import { render, screen, within } from '@testing-library/react';
import {
    MassScheduleCard,
    DAY_LABELS,
} from '@/features/public/jadwal-misa/components/mass-schedule-card';
import type { PublicMassSchedule } from '@/features/public/jadwal-misa/types';

/*
 * MassScheduleCard — one row of the weekly schedule.
 *
 * The assertion that matters is the time. `start_time` is a wall clock
 * (PRD D-02), so 07:30 has to render as 07.30 WIB and not as 00.30 WIB. Any
 * implementation that hands the value to Intl would pass a test that only looked
 * for a colon and fail this one.
 */

function mass(overrides: Partial<PublicMassSchedule> = {}): PublicMassSchedule {
    return {
        id: 1,
        day_of_week: 0,
        start_time: '07:30',
        name: 'Misa Minggu Pagi',
        notes: null,
        location: {
            id: 1,
            name: 'Gereja Paroki',
            type: 'gereja_paroki',
            address: null,
            mapsUrl: null,
        },
        ...overrides,
    };
}

/**
 * Render one row and hand back the element the assertions run against, so each
 * test reads as "given a mass with these fields, this is what the card says".
 */
function renderCard(overrides: Partial<PublicMassSchedule> = {}): HTMLElement {
    render(
        <ul>
            <MassScheduleCard mass={mass(overrides)} dayLabels={DAY_LABELS} />
        </ul>,
    );

    // By role rather than by data-test: this repo's component tests query by
    // role or label, and the data-test hooks exist for Playwright. Using them
    // here would couple the component test to the E2E selector.
    return screen.getByRole('listitem');
}

describe('MassScheduleCard', () => {
    it('shows the day, the time and the mass name', () => {
        const card = renderCard();

        expect(card).toHaveTextContent('Minggu');
        expect(card).toHaveTextContent('07.30 WIB');
        expect(card).toHaveTextContent('Misa Minggu Pagi');
    });

    it('formats the wall clock without shifting it', () => {
        // The regression D-02 exists to prevent: a datetime-cast start_time
        // renders as 00.30 WIB, and every other assertion here still passes.
        const card = renderCard({ start_time: '17:00' });

        expect(card).toHaveTextContent('17.00 WIB');
        expect(card).not.toHaveTextContent('10.00');
    });

    it('shows the location name', () => {
        expect(renderCard()).toHaveTextContent('Gereja Paroki');
    });

    it('shows the notes when the parish wrote any', () => {
        expect(
            renderCard({ notes: 'Dengan koir paduan suara.' }),
        ).toHaveTextContent('Dengan koir paduan suara.');
    });

    it('renders no map link when the location has nothing to point at', () => {
        // Roadmap 7.2: no link from an empty address, and a disabled Maps button
        // on the parish's own page would read as a bug.
        const card = renderCard();

        expect(
            within(card).queryByRole('link', { name: /Google Maps/i }),
        ).toBeNull();
    });

    it('renders a map link when the server resolved one', () => {
        const card = renderCard({
            location: {
                id: 1,
                name: 'Gereja Paroki',
                type: 'gereja_paroki',
                address: null,
                mapsUrl: 'https://maps.example/paroki',
            },
        });

        expect(
            within(card).getByRole('link', { name: /Google Maps/i }),
        ).toHaveAttribute('href', 'https://maps.example/paroki');
    });

    /**
     * The link points somewhere else, so it must not be able to reach back into
     * this window. `rel` is the whole guarantee and is invisible in a
     * screenshot, which is why it is asserted rather than assumed.
     */
    it('opens a map link in a new tab without leaking the opener', () => {
        const card = renderCard({
            location: {
                id: 1,
                name: 'Gereja Paroki',
                type: 'gereja_paroki',
                address: null,
                mapsUrl: 'https://maps.example/paroki',
            },
        });

        const link = within(card).getByRole('link', { name: /Google Maps/i });

        expect(link).toHaveAttribute('target', '_blank');
        expect(link).toHaveAttribute(
            'rel',
            expect.stringContaining('noopener'),
        );
    });
});

describe('DAY_LABELS', () => {
    it('starts the week on Sunday and runs to Saturday', () => {
        // The server sends rows in this order and the card renders whichever
        // label the number maps to, so the table itself has to start at 0 —
        // the same numbering as App\Enums\DayOfWeek.
        expect(Object.keys(DAY_LABELS)).toEqual([
            '0',
            '1',
            '2',
            '3',
            '4',
            '5',
            '6',
        ]);
        expect(DAY_LABELS[0]).toBe('Minggu');
        expect(DAY_LABELS[6]).toBe('Sabtu');
    });
});
