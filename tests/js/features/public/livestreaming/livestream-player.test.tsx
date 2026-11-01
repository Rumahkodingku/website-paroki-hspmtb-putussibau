import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    LivestreamPlayer,
    type PublicLivestream,
} from '@/components/livestream-player';

/*
 * The player is the only place an iframe enters the public page. Two promises:
 * no third-party request before the visitor asks for it, and the iframe uses
 * the server-built privacy-enhanced URL.
 */

function stream(overrides: Partial<PublicLivestream> = {}): PublicLivestream {
    return {
        id: 1,
        title: 'Misa Minggu Pagi',
        description: null,
        video_id: 'dQw4w9WgXcQ',
        embed_url: 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
        watch_url: 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        thumbnail_url: null,
        category: 'liturgi',
        category_label: 'Liturgi',
        location: null,
        scheduled_at: null,
        started_at: null,
        recorded_at: null,
        ...overrides,
    };
}

describe('LivestreamPlayer', () => {
    it('does not mount an iframe before the visitor clicks play', async () => {
        render(<LivestreamPlayer stream={stream()} />);

        expect(
            screen.queryByTitle('Video: Misa Minggu Pagi'),
        ).not.toBeInTheDocument();

        await userEvent.click(
            screen.getByRole('button', { name: 'Putar Misa Minggu Pagi' }),
        );

        const iframe = screen.getByTitle('Video: Misa Minggu Pagi');

        expect(iframe).toHaveAttribute(
            'src',
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
        );
        expect(iframe).toHaveAttribute('allowfullscreen');
    });

    it('mounts the iframe immediately when autoLoad is set', () => {
        render(<LivestreamPlayer stream={stream()} autoLoad />);

        expect(
            screen.getByTitle('Video: Misa Minggu Pagi'),
        ).toBeInTheDocument();
    });

    it('explains an unavailable video instead of rendering a broken player', async () => {
        render(
            <LivestreamPlayer
                stream={stream({
                    embed_url: null,
                    watch_url: null,
                })}
            />,
        );

        await userEvent.click(
            screen.getByRole('button', { name: 'Putar Misa Minggu Pagi' }),
        );

        expect(screen.getByText(/Video tidak dapat diputar/i)).toBeVisible();
    });
});
