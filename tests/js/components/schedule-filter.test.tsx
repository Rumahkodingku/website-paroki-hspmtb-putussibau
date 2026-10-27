import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ScheduleFilter } from '@/features/public/jadwal-misa/components/schedule-filter';
import { DAY_LABELS } from '@/features/public/jadwal-misa/components/regular-mass-schedule-card';

/*
 * The location and day filter.
 *
 * The filter writes to the URL rather than to component state, so what these
 * tests check is the value handed back on change — not where it was stored.
 * Chasing the router would test Inertia rather than this component, and the
 * resulting query string is asserted end to end in the Playwright spec instead.
 */

const LOCATIONS = [
    { id: 1, name: 'Gereja Paroki' },
    { id: 2, name: 'Stasi Tanah Empang' },
];

type Change = { location_id: number | null; day: number | null };

function renderFilter(
    overrides: Partial<{
        locationId: number | null;
        day: number | null;
    }> = {},
    onChange: (next: Change) => void = () => {},
) {
    const { container } = render(
        <ScheduleFilter
            locations={LOCATIONS}
            dayLabels={DAY_LABELS}
            locationId={overrides.locationId ?? null}
            day={overrides.day ?? null}
            onChange={onChange}
        />,
    );

    // The wrapper is a plain div with no role, so it is reached through the
    // render container rather than through a query that would have to guess at
    // its structure.
    return { onChange, wrapper: container.firstElementChild as HTMLElement };
}

describe('ScheduleFilter', () => {
    it('offers every location plus an all-locations option', async () => {
        const user = userEvent.setup();
        renderFilter();

        const select = screen.getByLabelText('Lokasi misa');

        await user.click(select);

        expect(
            screen.getByRole('option', { name: 'Semua lokasi' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('option', { name: 'Stasi Tanah Empang' }),
        ).toBeInTheDocument();
    });

    it('offers every day, Sunday first', async () => {
        const user = userEvent.setup();
        renderFilter();

        await user.click(screen.getByLabelText('Hari misa'));

        expect(
            screen.getByRole('option', { name: 'Minggu' }),
        ).toBeInTheDocument();
        expect(
            screen.getByRole('option', { name: 'Sabtu' }),
        ).toBeInTheDocument();
    });

    /**
     * The two are applied together. Roadmap 7.2 asks that a filter still in
     * effect is not discarded when the other changes, so the change carries both
     * values rather than only the one that moved.
     */
    it('keeps the day when the location changes', async () => {
        const user = userEvent.setup();
        const seen: Change[] = [];

        renderFilter({ day: 3 }, (next) => seen.push(next));

        await user.click(screen.getByLabelText('Lokasi misa'));
        await user.click(
            screen.getByRole('option', { name: 'Stasi Tanah Empang' }),
        );

        expect(seen).toEqual([{ location_id: 2, day: 3 }]);
    });

    it('keeps the location when the day changes', async () => {
        const user = userEvent.setup();
        const seen: Change[] = [];

        renderFilter({ locationId: 2 }, (next) => seen.push(next));

        await user.click(screen.getByLabelText('Hari misa'));
        await user.click(screen.getByRole('option', { name: 'Minggu' }));

        expect(seen).toEqual([{ location_id: 2, day: 0 }]);
    });

    it('clears the value when the empty option is chosen', async () => {
        const user = userEvent.setup();
        const seen: Change[] = [];

        renderFilter({ locationId: 1, day: 0 }, (next) => seen.push(next));

        await user.click(screen.getByLabelText('Lokasi misa'));
        await user.click(screen.getByRole('option', { name: 'Semua lokasi' }));

        // Day stays: clearing one filter is not a request to clear both.
        expect(seen).toEqual([{ location_id: null, day: 0 }]);
    });

    it('shows the reset action only when something is filtered', async () => {
        const user = userEvent.setup();
        const seen: Change[] = [];

        const { unmount } = render(
            <ScheduleFilter
                locations={LOCATIONS}
                dayLabels={DAY_LABELS}
                locationId={null}
                day={null}
                onChange={(next) => seen.push(next)}
            />,
        );

        // A reset button on an unfiltered list is a control that does nothing.
        expect(
            screen.queryByRole('button', { name: /reset filter/i }),
        ).toBeNull();

        unmount();
        renderFilter({ locationId: 1 }, (next) => seen.push(next));

        await user.click(screen.getByRole('button', { name: /reset filter/i }));

        expect(seen).toEqual([{ location_id: null, day: null }]);
    });

    it('tells the visitor the filter is applied and shareable', () => {
        renderFilter({ day: 0 });

        expect(screen.getByText(/filter aktif/i)).toBeInTheDocument();
    });

    it('leaves no note behind when nothing is filtered', () => {
        renderFilter();

        expect(screen.queryByText(/filter aktif/i)).toBeNull();
    });

    /**
     * The filter is removed from the printed page: a paper copy should show the
     * schedule, not controls for choosing one.
     */
    it('is hidden when the page is printed', () => {
        const { wrapper } = renderFilter({ day: 0 });

        expect(wrapper).toHaveClass('print:hidden');
    });
});
