import { describe, expect, it } from 'vitest';
import { screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { TooltipProvider } from '@/components/ui/tooltip';
import Index from '@/features/admin/livestreaming/pages/index';
import type {
    LivestreamFilters,
    LivestreamFormMeta,
    LivestreamRow,
} from '@/features/admin/livestreaming/types';
import type { Paginated } from '@/types';
import { renderWithInertia } from '../../../support/inertia';

/*
 * Starting a second stream must tell the admin which live stream is about to
 * be ended. The backend swaps them transactionally; the dialog is the warning
 * that makes the swap deliberate rather than a surprise.
 */

function stream(overrides: Partial<LivestreamRow> = {}): LivestreamRow {
    return {
        id: 1,
        title: 'Misa Minggu Pagi',
        description: null,
        youtube_url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        youtube_video_id: 'dQw4w9WgXcQ',
        thumbnail_url: null,
        location_id: null,
        location: null,
        category_id: null,
        category: 'liturgi',
        category_label: 'Liturgi',
        status: 'published',
        status_label: 'Terbit',
        broadcast_status: 'scheduled',
        broadcast_status_label: 'Dijadwalkan',
        scheduled_at: null,
        started_at: null,
        ended_at: null,
        recorded_at: null,
        is_recording_available: false,
        published_at: null,
        updated_at: '2026-11-01T00:00:00+00:00',
        can: { update: true, delete: true },
        ...overrides,
    };
}

const FILTERS: LivestreamFilters = {
    search: '',
    status: '',
    broadcast_status: '',
    kategori: '',
    sort: '',
    direction: '',
};

const META: LivestreamFormMeta = {
    labels: {},
    hints: {},
    copy: {
        start_title: 'Mulai siaran?',
        start_description:
            'Siaran akan tampil di beranda dan halaman Livestreaming sampai diakhiri.',
        start_replace_description:
            'Siaran ":title" sedang live dan akan diakhiri. Lanjutkan memulai siaran ini?',
        start: 'Mulai Siaran',
        end: 'Akhiri Siaran',
        unavailable: 'Tandai Tidak Tersedia',
    },
    empty: {},
    categories: [],
    statuses: [],
    broadcastStatuses: [],
};

function paginated(data: LivestreamRow[]): Paginated<LivestreamRow> {
    return {
        data,
        links: { first: null, last: null, prev: null, next: null },
        meta: {
            current_page: 1,
            from: data.length > 0 ? 1 : null,
            last_page: 1,
            per_page: 10,
            to: data.length > 0 ? data.length : null,
            total: data.length,
            links: [],
        },
    };
}

describe('admin livestream start confirmation', () => {
    it('names the live stream that will be ended', async () => {
        const live = stream({
            id: 1,
            title: 'Live Lama',
            broadcast_status: 'live',
            broadcast_status_label: 'Sedang Live',
        });
        const scheduled = stream({ id: 2, title: 'Misa Baru' });

        renderWithInertia(
            <TooltipProvider>
                <Index
                    livestreams={paginated([live, scheduled])}
                    activeLive={{ id: live.id, title: live.title }}
                    filters={FILTERS}
                    {...META}
                />
            </TooltipProvider>,
            { url: '/admin/livestreaming' },
        );

        await userEvent.click(
            screen.getByRole('button', { name: 'Mulai siaran' }),
        );

        const dialog = await screen.findByRole('dialog');

        expect(within(dialog).getByText(/Live Lama/)).toBeVisible();
        expect(
            within(dialog).getByRole('button', { name: 'Mulai Siaran' }),
        ).toBeVisible();
    });
});
