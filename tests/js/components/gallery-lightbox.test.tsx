import { describe, expect, it, vi } from 'vite-plus/test';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Lightbox } from '@/features/public/galeri/components/lightbox';
import type { PublicGalleryPhoto } from '@/features/public/galeri/types';

/*
 * The album lightbox.
 *
 * Interaction and keyboard only — it holds no state of its own beyond the
 * document listener, so what there is to check is that Escape closes, the arrow
 * keys move, and the dialog announces itself.
 */

function photo(id: number, caption: string | null = null): PublicGalleryPhoto {
    return {
        id,
        caption,
        alt_text: `Foto ${id}`,
        url: `/storage/medium-${id}.webp`,
        thumb_url: `/storage/thumb-${id}.webp`,
        large_url: `/storage/large-${id}.webp`,
        width: 1600,
        height: 1200,
    };
}

const PHOTOS = [photo(1, 'Pertama'), photo(2, 'Kedua'), photo(3, 'Ketiga')];

describe('Lightbox', () => {
    it('renders nothing when no photo is open', () => {
        const { container } = render(
            <Lightbox
                photos={PHOTOS}
                index={null}
                title="Album"
                onClose={() => undefined}
                onNavigate={() => undefined}
            />,
        );

        expect(container).toBeEmptyDOMElement();
    });

    it('announces itself as a dialog and shows the counter and caption', () => {
        render(
            <Lightbox
                photos={PHOTOS}
                index={1}
                title="Album"
                onClose={() => undefined}
                onNavigate={() => undefined}
            />,
        );

        const dialog = screen.getByRole('dialog');

        expect(dialog).toHaveAttribute('aria-modal', 'true');
        expect(screen.getByText('Foto 2 dari 3')).toBeInTheDocument();
        expect(screen.getByText('Kedua')).toBeInTheDocument();
    });

    it('closes on Escape', async () => {
        const onClose = vi.fn();

        render(
            <Lightbox
                photos={PHOTOS}
                index={0}
                title="Album"
                onClose={onClose}
                onNavigate={() => undefined}
            />,
        );

        await userEvent.keyboard('{Escape}');

        expect(onClose).toHaveBeenCalled();
    });

    it('moves to the next and previous photo with the arrow keys, wrapping', async () => {
        const onNavigate = vi.fn();

        render(
            <Lightbox
                photos={PHOTOS}
                index={2}
                title="Album"
                onClose={() => undefined}
                onNavigate={onNavigate}
            />,
        );

        await userEvent.keyboard('{ArrowRight}');
        expect(onNavigate).toHaveBeenLastCalledWith(0);

        await userEvent.keyboard('{ArrowLeft}');
        expect(onNavigate).toHaveBeenLastCalledWith(1);
    });

    it('navigates with the on-screen buttons too', async () => {
        const onNavigate = vi.fn();

        render(
            <Lightbox
                photos={PHOTOS}
                index={0}
                title="Album"
                onClose={() => undefined}
                onNavigate={onNavigate}
            />,
        );

        await userEvent.click(
            screen.getByRole('button', { name: 'Foto berikutnya' }),
        );

        expect(onNavigate).toHaveBeenCalledWith(1);
    });
});
