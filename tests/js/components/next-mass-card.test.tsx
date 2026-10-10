import { describe, expect, it } from 'vite-plus/test';
import { render, screen, within } from '@testing-library/react';
import { NextMassCard } from '@/features/public/jadwal-misa/components/next-mass-card';
import type { NextMass } from '@/features/public/jadwal-misa/types';

/*
 * The "Misa Berikutnya" card.
 *
 * Everything on it was decided by NextMassResolver and arrives ready. These tests
 * are therefore about presentation and about the null case, not about which mass
 * is next — that is NextMassResolverTest's job, and repeating the calculation
 * here to check it would be the second calculation roadmap 7.2 forbids.
 */

function mass(overrides: Partial<NextMass> = {}): NextMass {
    return {
        kind: 'rutin',
        starts_at: '2026-10-04T07:30:00+07:00',
        name: 'Misa Minggu Pagi',
        language: null,
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

function renderCard(nextMass: NextMass | null) {
    render(
        <NextMassCard nextMass={nextMass} displayTimezone="Asia/Pontianak" />,
    );
}

describe('NextMassCard with a resolved mass', () => {
    it('names the mass', () => {
        renderCard(mass());

        expect(screen.getByText('Misa Minggu Pagi')).toBeInTheDocument();
    });

    it('shows when it starts, in the parish timezone', () => {
        renderCard(mass());

        // The ISO string already carries +07:00, so a formatter that ignored the
        // offset would print 00.30 and the card would be wrong by seven hours.
        expect(screen.getByText(/Minggu, 4 Oktober 2026/)).toHaveTextContent(
            '07.30 WIB',
        );
    });

    it('says a recurring mass is recurring', () => {
        renderCard(mass());

        expect(screen.getByText(/Jadwal rutin/)).toBeInTheDocument();
    });

    it('says a special mass is special', () => {
        renderCard(
            mass({ kind: 'khusus', name: 'Misa Natal', language: null }),
        );

        expect(screen.getByText('Misa Natal')).toBeInTheDocument();
        expect(screen.getByText(/Misa khusus/)).toBeInTheDocument();
        expect(screen.queryByText(/Jadwal rutin/)).toBeNull();
    });

    it('shows the language beside the recurring label', () => {
        renderCard(mass({ language: 'Latin' }));

        expect(screen.getByText(/Jadwal rutin/)).toHaveTextContent('Latin');
    });

    it('names the location', () => {
        renderCard(mass());

        expect(screen.getByText('Gereja Paroki')).toBeInTheDocument();
    });

    it('renders no map button when the location has nothing to point at', () => {
        renderCard(mass());

        expect(screen.queryByRole('link', { name: /Google Maps/i })).toBeNull();
    });

    it('renders a map button when the server resolved one', () => {
        renderCard(
            mass({
                location: {
                    ...mass().location,
                    mapsUrl: 'https://maps.example/paroki',
                },
            }),
        );

        const link = screen.getByRole('link', { name: /Google Maps/i });

        expect(link).toHaveAttribute('href', 'https://maps.example/paroki');
        expect(link).toHaveAttribute(
            'rel',
            expect.stringContaining('noopener'),
        );
    });
});

describe('NextMassCard with nothing scheduled', () => {
    /**
     * null is a state, not a failure. A parish that has not published a schedule
     * gets a sentence — roadmap 7.3 says so, and XC-E1 forbids an empty region.
     */
    it('says so instead of rendering nothing', () => {
        renderCard(null);

        expect(screen.getByText('Misa Berikutnya')).toBeInTheDocument();
        expect(
            screen.getByText(/Belum ada misa terjadwal/),
        ).toBeInTheDocument();
    });

    it('does not render the map button', () => {
        renderCard(null);

        expect(
            within(
                screen
                    .getByRole('heading', { name: 'Misa Berikutnya' })
                    .closest('div') as HTMLElement,
            ).queryByRole('link', { name: /Google Maps/i }),
        ).toBeNull();
    });

    it('does not claim a time', () => {
        renderCard(null);

        expect(screen.queryByText(/WIB/)).toBeNull();
    });
});
