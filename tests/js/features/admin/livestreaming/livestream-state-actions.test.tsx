import { describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { TooltipProvider } from '@/components/ui/tooltip';
import { LivestreamRowActions } from '@/features/admin/livestreaming/components/livestream-row-actions';
import type { LivestreamRow } from '@/features/admin/livestreaming/types';
import { renderWithInertia } from '../../../support/inertia';

/*
 * The row actions are the only place the admin list decides which operational
 * action a row offers. The server checks the same rules again; this test keeps
 * the visible affordances from drifting away from them.
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

function renderActions(
    row: LivestreamRow,
    handlers: {
        onStart?: () => void;
        onEnd?: () => void;
        onUnavailable?: () => void;
        onDelete?: () => void;
    } = {},
) {
    renderWithInertia(
        <TooltipProvider>
            <LivestreamRowActions
                stream={row}
                onStart={handlers.onStart ?? (() => undefined)}
                onEnd={handlers.onEnd ?? (() => undefined)}
                onUnavailable={handlers.onUnavailable ?? (() => undefined)}
                onDelete={handlers.onDelete ?? (() => undefined)}
            />
        </TooltipProvider>,
        { url: '/admin/livestreaming' },
    );
}

describe('LivestreamRowActions', () => {
    it('offers start and unavailable for a published scheduled stream', () => {
        renderActions(stream());

        expect(
            screen.getByRole('button', { name: 'Mulai siaran' }),
        ).toBeVisible();
        expect(
            screen.getByRole('button', { name: 'Tandai tidak tersedia' }),
        ).toBeVisible();
        expect(
            screen.queryByRole('button', { name: 'Akhiri siaran' }),
        ).not.toBeInTheDocument();
    });

    it('offers only end for a live stream', () => {
        renderActions(
            stream({
                broadcast_status: 'live',
                broadcast_status_label: 'Sedang Live',
            }),
        );

        expect(
            screen.getByRole('button', { name: 'Akhiri siaran' }),
        ).toBeVisible();
        expect(
            screen.queryByRole('button', { name: 'Mulai siaran' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Tandai tidak tersedia' }),
        ).not.toBeInTheDocument();
    });

    it('calls the right handler for each action', async () => {
        const onStart = vi.fn();
        const onEnd = vi.fn();
        const onUnavailable = vi.fn();
        const onDelete = vi.fn();

        renderActions(stream({ broadcast_status: 'live' }), {
            onStart,
            onEnd,
            onUnavailable,
            onDelete,
        });

        await userEvent.click(
            screen.getByRole('button', { name: 'Akhiri siaran' }),
        );
        await userEvent.click(screen.getByRole('button', { name: 'Hapus' }));

        expect(onEnd).toHaveBeenCalledTimes(1);
        expect(onDelete).toHaveBeenCalledTimes(1);
        expect(onStart).not.toHaveBeenCalled();
        expect(onUnavailable).not.toHaveBeenCalled();
    });

    it('renders no mutation actions without update permission', () => {
        renderActions(stream({ can: { update: false, delete: false } }));

        expect(
            screen.queryByRole('link', { name: 'Ubah' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Mulai siaran' }),
        ).not.toBeInTheDocument();
        expect(
            screen.queryByRole('button', { name: 'Hapus' }),
        ).not.toBeInTheDocument();
    });
});
