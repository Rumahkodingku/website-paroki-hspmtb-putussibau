import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { fireEvent, render, waitFor } from '@testing-library/react';
import { MediaField } from '@/components/media-field';

/*
 * The image picker's preview.
 *
 * The bug this covers: picking a file left the preview box empty until the
 * queued job had finished, so an admin had no sign the upload had been
 * accepted and picked the same picture again. The fix is a local object URL
 * shown immediately.
 *
 * fetch is stubbed with a promise that never settles on purpose. The component
 * revokes the local preview once the server answers, so a resolved fetch would
 * make this test assert on a preview that is already gone. A pending upload is
 * exactly the window the local preview exists for.
 */

const FILE = new File(['x'], 'gereja.jpg', { type: 'image/jpeg' });

afterEach(() => {
    vi.restoreAllMocks();
});

describe('MediaField', () => {
    it('shows the picked file straight away, before the server answers', async () => {
        vi.stubGlobal(
            'URL',
            Object.assign(URL, {
                createObjectURL: vi.fn(() => 'blob:local-preview'),
                revokeObjectURL: vi.fn(),
            }),
        );

        // Never settles: the upload is still in flight.
        vi.stubGlobal(
            'fetch',
            vi.fn(() => new Promise(() => {})),
        );

        const { container } = render(
            <MediaField
                id="post-media"
                label="Gambar utama"
                altValue=""
                onAltChange={() => {}}
                altLabel="Teks alternatif"
                mediaId={null}
                onMediaChange={() => {}}
                currentUrl={null}
                fieldName="featured_image"
                emptyHint="Belum ada gambar"
            />,
        );

        const input =
            container.querySelector<HTMLInputElement>('input[type="file"]');

        expect(input).not.toBeNull();

        fireEvent.change(input as HTMLInputElement, {
            target: { files: [FILE] },
        });

        // The picker tags the box with data-test (the E2E specs read the same
        // attribute), not the data-testid Testing Library looks for by default.
        await waitFor(() =>
            expect(
                container.querySelector('[data-test="media-preview"] img'),
            ).toHaveAttribute('src', 'blob:local-preview'),
        );
    });
});
