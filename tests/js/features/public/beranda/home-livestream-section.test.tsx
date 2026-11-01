import { describe, expect, it } from 'vitest';
import { screen } from '@testing-library/react';
import { HomeLivestreamSection } from '@/features/public/beranda/components/home-livestream-section';
import type { PublicLivestream } from '@/components/livestream-player';
import { renderWithInertia } from '../../../support/inertia';

/*
 * The homepage live block has one job: appear when there is a live stream, and
 * disappear without leaving a hole when there is not.
 */

function stream(overrides: Partial<PublicLivestream> = {}): PublicLivestream {
    return {
        id: 1,
        title: 'Misa Minggu Pagi',
        description: 'Perayaan Ekaristi Minggu pagi.',
        video_id: 'dQw4w9WgXcQ',
        embed_url: 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
        watch_url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        thumbnail_url: null,
        category: 'liturgi',
        category_label: 'Liturgi',
        location: 'Gereja Paroki HSPMTB',
        scheduled_at: null,
        started_at: '2026-11-01T01:00:00+00:00',
        recorded_at: null,
        ...overrides,
    };
}

describe('HomeLivestreamSection', () => {
    it('renders nothing when there is no live stream', () => {
        const { container } = renderWithInertia(
            <HomeLivestreamSection stream={null} enabled={false} />,
        );

        expect(container).toBeEmptyDOMElement();
    });

    it('renders nothing when the section flag is off', () => {
        const { container } = renderWithInertia(
            <HomeLivestreamSection stream={stream()} enabled={false} />,
        );

        expect(container).toBeEmptyDOMElement();
    });

    it('renders the live player, badge and YouTube link', () => {
        renderWithInertia(<HomeLivestreamSection stream={stream()} enabled />);

        expect(
            screen.getByRole('heading', {
                name: 'Ikuti Kegiatan Paroki Secara Online',
            }),
        ).toBeVisible();
        expect(screen.getByText('SEDANG LIVE')).toBeVisible();
        expect(
            screen.getByRole('button', { name: 'Putar Misa Minggu Pagi' }),
        ).toBeVisible();
        expect(
            screen.getByRole('link', { name: /Tonton di YouTube/i }),
        ).toHaveAttribute(
            'href',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        );
        expect(screen.getByText('Gereja Paroki HSPMTB')).toBeVisible();
    });
});
