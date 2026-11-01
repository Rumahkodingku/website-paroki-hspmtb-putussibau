import { afterEach, describe, expect, it, vi } from 'vitest';
import { screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { router } from '@inertiajs/react';
import { LivestreamArchiveCard } from '@/features/public/livestreaming/components/livestream-archive-card';
import LivestreamingIndex from '@/features/public/livestreaming/pages/index';
import type {
    LivestreamArchiveFilters,
    PublicLivestream,
} from '@/features/public/livestreaming/types';
import type { Paginated } from '@/types';
import { renderWithInertia } from '../../../support/inertia';

/*
 * The archive page and its card.
 *
 * The card is the whole click target, so the title stays readable without a
 * nested link. The page keeps its filters in the URL; this test checks the
 * search actually asks Inertia for a new visit rather than filtering in memory.
 */

afterEach(() => {
    vi.restoreAllMocks();
});

function stream(overrides: Partial<PublicLivestream> = {}): PublicLivestream {
    return {
        id: 1,
        title: 'Misa Paskah',
        description: 'Perayaan Paskah bersama umat.',
        video_id: 'dQw4w9WgXcQ',
        embed_url: 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
        watch_url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        thumbnail_url: null,
        category: 'liturgi',
        category_label: 'Liturgi',
        location: 'Gereja Paroki',
        scheduled_at: null,
        started_at: null,
        recorded_at: '2025-06-01T02:00:00+00:00',
        ...overrides,
    };
}

function paginated(data: PublicLivestream[]): Paginated<PublicLivestream> {
    return {
        data,
        links: { first: null, last: null, prev: null, next: null },
        meta: {
            current_page: 1,
            from: data.length > 0 ? 1 : null,
            last_page: 1,
            per_page: 12,
            to: data.length > 0 ? data.length : null,
            total: data.length,
            links: [],
        },
    };
}

const FILTERS: LivestreamArchiveFilters = {
    search: '',
    kategori: '',
    tahun: '',
};

describe('LivestreamArchiveCard', () => {
    it('shows the title, category and recording date', () => {
        renderWithInertia(
            <LivestreamArchiveCard
                stream={stream()}
                onPlay={() => undefined}
            />,
            { url: '/livestreaming' },
        );

        expect(
            screen.getByRole('heading', { name: 'Misa Paskah' }),
        ).toBeVisible();
        expect(screen.getByText('Liturgi')).toBeVisible();
        expect(screen.getByText('1 Juni 2025')).toBeVisible();
    });

    it('opens the player from the whole card', async () => {
        const onPlay = vi.fn();

        renderWithInertia(
            <LivestreamArchiveCard stream={stream()} onPlay={onPlay} />,
            { url: '/livestreaming' },
        );

        await userEvent.click(
            screen.getByRole('button', {
                name: 'Putar rekaman Misa Paskah',
            }),
        );

        expect(onPlay).toHaveBeenCalledTimes(1);
    });
});

describe('LivestreamingIndex', () => {
    it('renders the header and an empty state when there is no recording', () => {
        renderWithInertia(
            <LivestreamingIndex
                activeStream={null}
                archives={paginated([])}
                years={[]}
                categories={[]}
                filters={FILTERS}
            />,
            { url: '/livestreaming' },
        );

        expect(
            screen.getByRole('heading', {
                level: 1,
                name: 'Arsip Livestreaming',
            }),
        ).toBeVisible();
        expect(
            screen.getByText('Belum ada rekaman livestreaming.'),
        ).toBeVisible();
    });

    it('renders the active player above the grid', () => {
        renderWithInertia(
            <LivestreamingIndex
                activeStream={stream({ recorded_at: null })}
                archives={paginated([])}
                years={[]}
                categories={[]}
                filters={FILTERS}
            />,
            { url: '/livestreaming' },
        );

        expect(screen.getByText('SEDANG LIVE')).toBeVisible();
        expect(
            screen.getByRole('button', { name: 'Putar Misa Paskah' }),
        ).toBeVisible();
    });

    it('asks Inertia for a new visit when the search changes', async () => {
        const get = vi.spyOn(router, 'get').mockImplementation(() => undefined);

        renderWithInertia(
            <LivestreamingIndex
                activeStream={null}
                archives={paginated([])}
                years={[]}
                categories={[]}
                filters={FILTERS}
            />,
            { url: '/livestreaming' },
        );

        await userEvent.type(
            screen.getByPlaceholderText('Cari rekaman...'),
            'Paskah',
        );

        await waitFor(() => expect(get).toHaveBeenCalled());

        const call = get.mock.calls.at(-1);

        expect(call?.[0]).toBe('/livestreaming');
        expect(call?.[1]).toMatchObject({
            search: 'Paskah',
            kategori: '',
            tahun: '',
            page: 1,
        });
    });
});
