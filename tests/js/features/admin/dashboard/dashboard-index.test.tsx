import { describe, expect, it } from 'vitest';
import { screen } from '@testing-library/react';
import Dashboard from '@/features/admin/dashboard/pages/index';
import type {
    DashboardProps,
    DashboardSummary,
} from '@/features/admin/dashboard/types';
import { renderWithInertia } from '../../../support/inertia';

/*
 * The dashboard page's behaviour.
 *
 * The numbers and the status definitions belong to the Pest suite; what is
 * asserted here is that the page composes — that an empty database produces
 * empty states rather than a blank grid, and that a shortcut points at the
 * route an admin expects. A real navigation belongs in tests/e2e.
 */

const ZERO: DashboardSummary = {
    posts: { total: 0, draft: 0, scheduled: 0, published: 0, archived: 0 },
    events: { upcoming: 0, draft: 0, cancelled: 0, postponed: 0 },
    documents: { total: 0, draft: 0, published: 0, archived: 0, expired: 0 },
    massSchedules: { regular: 0, special: 0, notices: 0 },
    services: { active: 0, inactive: 0 },
};

const EMPTY_PROPS: DashboardProps = {
    summary: ZERO,
    upcomingEvents: [],
    recentPosts: [],
    attentionItems: [],
    recentContent: [],
};

describe('Dashboard', () => {
    it('shows a composed empty state rather than a blank page', () => {
        renderWithInertia(<Dashboard {...EMPTY_PROPS} />);

        expect(
            screen.getByText('Belum ada agenda mendatang'),
        ).toBeInTheDocument();
        expect(screen.getByText('Belum ada berita terbit')).toBeInTheDocument();
        expect(
            screen.getByText('Tidak ada yang perlu ditindaklanjuti'),
        ).toBeInTheDocument();
        expect(screen.getByText('Belum ada konten')).toBeInTheDocument();
    });

    it('renders all five module summaries', () => {
        renderWithInertia(<Dashboard {...EMPTY_PROPS} />);

        // Exact labels: "Tulis Berita" and "Tambah Agenda" are quick actions and
        // would match a loose query, so a dropped card has to be caught here
        // rather than by a substring that also finds its neighbours.
        for (const label of [
            'Berita',
            'Agenda mendatang',
            'Dokumen',
            'Misa rutin aktif',
            'Pelayanan aktif',
        ]) {
            expect(
                screen.getByText(label, { exact: true }),
            ).toBeInTheDocument();
        }
    });

    it('links each quick action to its admin route', () => {
        renderWithInertia(<Dashboard {...EMPTY_PROPS} />);

        expect(
            screen.getByRole('link', { name: /Tulis Berita Artikel/ }),
        ).toHaveAttribute('href', '/admin/berita/tambah');
        expect(
            screen.getByRole('link', { name: /Tambah Agenda Kegiatan/ }),
        ).toHaveAttribute('href', '/admin/agenda/tambah');
        expect(
            screen.getByRole('link', { name: /Pengaturan Situs/ }),
        ).toHaveAttribute('href', '/admin/pengaturan');
    });

    it('renders a recent article and an upcoming activity', () => {
        renderWithInertia(
            <Dashboard
                {...EMPTY_PROPS}
                recentPosts={[
                    {
                        id: 1,
                        title: 'Pesta Nama Paroki',
                        status: 'published',
                        status_label: 'Terbit',
                        published_at: '2026-10-01T02:00:00+00:00',
                        updated_at: '2026-10-01T02:00:00+00:00',
                        image: null,
                        image_alt: null,
                    },
                ]}
                upcomingEvents={[
                    {
                        id: 2,
                        title: 'Doa Rosario Bersama',
                        status: 'scheduled',
                        status_label: 'Terjadwal',
                        is_published: true,
                        start_at: '2026-10-20T02:00:00+00:00',
                        end_at: null,
                        is_all_day: false,
                        location_name: 'Gereja Paroki',
                    },
                ]}
            />,
        );

        expect(screen.getByText('Pesta Nama Paroki')).toBeInTheDocument();
        expect(screen.getByText('Doa Rosario Bersama')).toBeInTheDocument();
    });
});
