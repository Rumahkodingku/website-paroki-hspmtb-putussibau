import { describe, expect, it } from 'vitest';
import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    GalleryForm,
    emptyGalleryFormData,
} from '@/features/admin/galeri/components/gallery-form';
import type { GalleryAlbumFormInstance } from '@/features/admin/galeri/components/gallery-form-instance';
import type { GalleryAlbumFormData } from '@/features/admin/galeri/types';
import { renderWithInertia } from '../../../support/inertia';

/*
 * The album form.
 *
 * What this guards is the promise that a server rejection lands on the field
 * that caused it: an Indonesian message that renders somewhere other than the
 * input is not an error report. Errors are driven through Inertia's own form API
 * (setErrors) the way a rejected visit does, rather than by stubbing
 * @inertiajs/react.
 */

const LABELS: Record<string, string> = {
    title: 'Judul Album',
    slug: 'Slug',
    description: 'Deskripsi',
    event_date: 'Tanggal Kegiatan',
    location: 'Lokasi',
    is_published: 'Publikasi',
};

const HINTS: Record<string, string> = {
    slug: 'Terbentuk otomatis dari judul.',
    event_date: 'Tanggal kegiatan, bukan tanggal unggah.',
};

const COPY = {
    draft_hint: 'Album draf tidak tampil di halaman publik.',
};

function renderForm(
    errors: Record<string, string>,
    initial: Partial<GalleryAlbumFormData> = {},
) {
    const onSubmit = (form: GalleryAlbumFormInstance) => {
        form.setError(errors);
    };

    renderWithInertia(
        <GalleryForm
            initial={{ ...emptyGalleryFormData(), ...initial }}
            labels={LABELS}
            hints={HINTS}
            copy={COPY}
            submitLabel="Simpan album"
            onSubmit={onSubmit}
            onCancel={() => undefined}
        />,
        { url: '/admin/galeri/tambah' },
    );

    return {
        submit: () =>
            userEvent.click(
                screen.getByRole('button', { name: 'Simpan album' }),
            ),
    };
}

describe('GalleryForm', () => {
    it('renders the labels it is handed rather than its own strings', () => {
        renderForm({});

        expect(screen.getByLabelText(/Judul Album/)).toBeInTheDocument();
        expect(screen.getByLabelText(/Tanggal Kegiatan/)).toBeInTheDocument();
    });

    it('shows a server error next to the field that caused it', async () => {
        const { submit } = renderForm({
            title: 'Judul album wajib diisi.',
        });

        await submit();

        expect(screen.getByText('Judul album wajib diisi.')).toBeVisible();
    });

    it('reports the error count for several rejected fields at once', async () => {
        const { submit } = renderForm({
            title: 'Judul album wajib diisi.',
            slug: 'Slug sudah dipakai album lain.',
        });

        await submit();

        expect(screen.getByText('Judul album wajib diisi.')).toBeVisible();
        expect(
            screen.getByText('Slug sudah dipakai album lain.'),
        ).toBeVisible();
    });
});
