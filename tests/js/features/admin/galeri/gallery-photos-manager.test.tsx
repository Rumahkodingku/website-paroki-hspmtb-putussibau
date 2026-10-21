import { describe, expect, it } from 'vitest';
import { screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { GalleryPhotosManager } from '@/features/admin/galeri/components/gallery-photos-manager';
import { TooltipProvider } from '@/components/ui/tooltip';
import type {
    GalleryAlbumDetail,
    GalleryPhotoItem,
} from '@/features/admin/galeri/types';
import { renderWithInertia } from '../../../support/inertia';

/*
 * The photo manager's two local behaviours.
 *
 * What is checked here is what moves before any request: the order is held in
 * React state, so an arrow key reorders the grid immediately, and a destructive
 * control opens a confirmation rather than acting. Both are the promises the
 * manager makes; the persistence itself is a Pest test.
 */

function photo(id: number, caption: string): GalleryPhotoItem {
    return {
        id,
        media_id: id * 10,
        caption,
        alt_text: null,
        sort_order: id - 1,
        url: `/storage/medium-${id}.webp`,
        thumb_url: `/storage/thumb-${id}.webp`,
        large_url: `/storage/large-${id}.webp`,
        width: 1600,
        height: 1200,
    };
}

function album(photos: GalleryPhotoItem[]): GalleryAlbumDetail {
    return {
        id: 7,
        title: 'Natal 2025',
        slug: 'natal-2025',
        description: null,
        event_date: '2025-12-25',
        location: null,
        cover_photo_id: photos[0]?.id ?? null,
        cover_url: null,
        is_published: false,
        published_at: null,
        updated_at: '2025-12-26T00:00:00+00:00',
        photo_count: photos.length,
        photos,
        can: { update: true, delete: true },
    };
}

const COPY = {
    manage_photos: 'Kelola Foto',
    reorder_hint: 'Urutan belum disimpan.',
    confirm_detach_title: 'Keluarkan foto ini?',
    confirm_detach_body:
        'Foto dikeluarkan dari album. Berkas gambar tetap ada di pustaka media dan tidak dihapus.',
};

function renderManager(photos: GalleryPhotoItem[]) {
    renderWithInertia(
        <TooltipProvider delayDuration={0}>
            <GalleryPhotosManager
                album={album(photos)}
                labels={{}}
                hints={{}}
                copy={COPY}
                empty={{}}
            />
        </TooltipProvider>,
        { url: '/admin/galeri/7/foto' },
    );
}

describe('GalleryPhotosManager', () => {
    it('renders the album photos in order', () => {
        renderManager([photo(1, 'Pertama'), photo(2, 'Kedua')]);

        const items = screen.getAllByRole('listitem');

        expect(within(items[0]).getByText('Pertama')).toBeInTheDocument();
        expect(within(items[1]).getByText('Kedua')).toBeInTheDocument();
    });

    it('reorders locally when the down arrow is used', async () => {
        renderManager([photo(1, 'Pertama'), photo(2, 'Kedua')]);

        await userEvent.click(
            screen.getAllByRole('button', {
                name: 'Turunkan urutan',
            })[0],
        );

        const items = screen.getAllByRole('listitem');

        expect(within(items[0]).getByText('Kedua')).toBeInTheDocument();
        expect(within(items[1]).getByText('Pertama')).toBeInTheDocument();
    });

    it('asks for confirmation before detaching a photo', async () => {
        renderManager([photo(1, 'Pertama')]);

        await userEvent.click(
            screen.getByRole('button', { name: 'Keluarkan foto' }),
        );

        expect(screen.getByText('Keluarkan foto ini?')).toBeInTheDocument();
        expect(
            screen.getByText(/Berkas gambar tetap ada di pustaka media/),
        ).toBeInTheDocument();
    });
});
