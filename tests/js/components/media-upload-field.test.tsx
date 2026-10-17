import { afterEach, describe, expect, it, vi } from 'vite-plus/test';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import { UploadConfigProvider } from '@uploadcn/react';
import { MediaUploadField } from '@/components/media-upload-field';
import { mediaUploadAdapter } from '@/lib/media-upload-adapter';
import { renderWithInertia } from '../support/inertia';

/*
 * The featured image field, in the state it opens in.
 *
 * The bug this covers is not a crash. The upload queue starts empty every time
 * the page loads, so a uploader that only renders the queue says "no image" on
 * an article that has one - and an admin reads that as "my picture is gone" and
 * uploads it again. The field therefore has to show what the form is carrying,
 * and it has to keep showing it while the queue is not describing it.
 *
 * Nothing here drives an upload. The uploader, the crop dialog and the browser's
 * image decoding are the library's and the browser's; a unit test that went that
 * far would be testing happy-dom's canvas. The transport - the poll, the
 * settled-only contract, the csrf header - is covered in
 * lib/media-upload-adapter.test.ts, where the edges actually are.
 */

const STORED_URL = '/storage/media/variants/3/medium.webp';

/** The provider the whole app mounts, so `useUploadContext` has something. */
function withUploader(ui: React.ReactElement) {
    return (
        <UploadConfigProvider adapter={mediaUploadAdapter}>
            {ui}
        </UploadConfigProvider>
    );
}

function renderField(props: Record<string, unknown> = {}) {
    const onMediaChange = vi.fn();
    const onAltChange = vi.fn();

    const view = renderWithInertia(
        withUploader(
            <MediaUploadField
                {...({
                    id: 'post-media',
                    label: 'Gambar utama',
                    altLabel: 'Teks alternatif gambar',
                    altValue: '',
                    onAltChange,
                    onMediaChange,
                    currentUrl: STORED_URL,
                    aspect: 16 / 9,
                    fieldName: 'featured_image',
                    ...props,
                } as React.ComponentProps<typeof MediaUploadField>)}
            />,
        ),
    );

    return { ...view, onMediaChange, onAltChange };
}

afterEach(() => {
    vi.restoreAllMocks();
});

describe('MediaUploadField', () => {
    it('shows the image the record already has', () => {
        const { container } = renderField();

        expect(
            container.querySelector('[data-test="media-current"] img'),
        ).toHaveAttribute('src', STORED_URL);
    });

    it('offers a way to replace it rather than leaving an empty dropzone', () => {
        // An article with an image opens with a queue that is empty, so the
        // dropzone alone reads as "there is no picture here".
        renderField();

        expect(
            screen.getByRole('button', { name: 'Ganti gambar' }),
        ).toBeInTheDocument();
    });

    it('says nothing about a stored image when the record has none', () => {
        const { container } = renderField({ currentUrl: null });

        expect(
            container.querySelector('[data-test="media-current"]'),
        ).toBeNull();
    });

    it('keeps the alt text out of the transport until the admin types it', () => {
        // The adapter reads the alt text when the file is sent, not when it was
        // built, so the order the admin works in does not matter.
        const { onAltChange } = renderField();

        fireEvent.change(screen.getByLabelText('Teks alternatif gambar'), {
            target: { value: 'Misa Malam Natal' },
        });

        expect(onAltChange).toHaveBeenCalledWith('Misa Malam Natal');
    });

    it('shows the hint the server sent about the alt text', () => {
        renderField({ hint: 'Dibaca layar pembaca.' });

        expect(screen.getByText('Dibaca layar pembaca.')).toBeInTheDocument();
    });

    it('names the cause when the upload limits are missing from the page', () => {
        // A silent fallback would be a second answer to config/media.php, and
        // the drift it invites is exactly what the shared prop exists to stop.
        expect(() =>
            renderWithInertia(
                withUploader(
                    <MediaUploadField
                        id="post-media"
                        label="Gambar utama"
                        altLabel="Teks alternatif gambar"
                        altValue=""
                        onAltChange={() => {}}
                        onMediaChange={() => {}}
                        currentUrl={null}
                        aspect={16 / 9}
                        fieldName="featured_image"
                    />,
                ),
                { props: { media: undefined } as never },
            ),
        ).toThrow(/Shared prop `media` is missing/);
    });
});

describe('MediaUploadField upload limits', () => {
    it('refuses what the server would refuse, before the request', async () => {
        const { container } = renderField({ currentUrl: null });

        const input =
            container.querySelector<HTMLInputElement>('input[type="file"]');

        // The server accepts image/jpeg and image/png and nothing else, so an
        // accept of image/* would let a WebP through to a 422 about the field.
        await waitFor(() =>
            expect(input?.getAttribute('accept')).toBe('image/jpeg,image/png'),
        );
    });
});

/*
 * The other variants.
 *
 * `variant` picks which block in components/upload/ is drawn, and the rules the
 * image variant is held to do not stop applying because the shape changed: the
 * saved picture is still shown, the limits still come from the shared prop, and
 * a removal is still only offered where a null means something.
 */

function renderVariant(
    props: React.ComponentProps<typeof MediaUploadField>,
): ReturnType<typeof renderWithInertia> {
    return renderWithInertia(withUploader(<MediaUploadField {...props} />));
}

describe('MediaUploadField avatar', () => {
    function renderAvatar(
        overrides: Partial<{
            removable: boolean;
            currentUrl: string | null;
        }> = {},
    ) {
        const onMediaChange = vi.fn();

        const view = renderVariant({
            id: 'member-photo',
            variant: 'avatar',
            label: 'Foto',
            altLabel: 'Teks alternatif foto',
            altValue: '',
            onAltChange: () => {},
            onMediaChange,
            currentUrl: STORED_URL,
            fieldName: 'photo_media_id',
            ...overrides,
        });

        return { ...view, onMediaChange };
    }

    it('shows the photo the record already has', () => {
        const { container } = renderAvatar();

        expect(container.querySelector('img')).toHaveAttribute(
            'src',
            STORED_URL,
        );
    });

    it('offers a way to replace it', () => {
        renderAvatar();

        expect(
            screen.getByRole('button', { name: 'Ganti foto' }),
        ).toBeInTheDocument();
    });

    it('empties the form when the saved photo is removed', () => {
        const { container, onMediaChange } = renderAvatar({ removable: true });

        fireEvent.click(screen.getByRole('button', { name: 'Hapus foto' }));

        // The column, and the picture: a removal that left the photo on screen
        // would be a form claiming to still hold what it just gave up.
        expect(onMediaChange).toHaveBeenCalledWith(null);
        expect(container.querySelector('img')).toBeNull();
    });

    it('offers no removal that would detach nothing', () => {
        // Posts and events read a null as "unchanged", so the button is opt-in
        // rather than always drawn. See docs/DECISIONS.md D-62, D-63.
        renderAvatar({ removable: false });

        expect(screen.queryByRole('button', { name: 'Hapus foto' })).toBeNull();
    });

    it('renders no alt field when the caller has no alt text to give', () => {
        renderVariant({
            id: 'member-photo',
            variant: 'avatar',
            label: 'Foto',
            onMediaChange: () => {},
            currentUrl: null,
            fieldName: 'photo_media_id',
        });

        expect(screen.queryByRole('textbox')).toBeNull();
    });
});

describe('MediaUploadField logo', () => {
    function renderLogo(overrides: Partial<{ removable: boolean }> = {}) {
        const onMediaChange = vi.fn();

        const view = renderVariant({
            id: 'community-logo',
            variant: 'logo',
            label: 'Logo komunitas',
            hint: 'JPG atau PNG.',
            name: 'Kategorial',
            altValue: '',
            onAltChange: () => {},
            altLabel: 'Teks alternatif logo',
            onMediaChange,
            currentUrl: STORED_URL,
            fieldName: 'logo',
            ...overrides,
        });

        return { ...view, onMediaChange };
    }

    it('shows the logo the record already has', () => {
        const { container } = renderLogo();

        expect(container.querySelector('img')).toHaveAttribute(
            'src',
            STORED_URL,
        );
    });

    it('names the card once, not twice', () => {
        // The block draws a titled card. Wrapping it in a Field label as well
        // would put the same word on screen twice.
        renderLogo();

        expect(screen.getAllByText('Logo komunitas')).toHaveLength(1);
    });

    it('empties the form when the saved logo is removed', () => {
        const { onMediaChange } = renderLogo({ removable: true });

        fireEvent.click(screen.getByRole('button', { name: 'Hapus logo' }));

        expect(onMediaChange).toHaveBeenCalledWith(null);
    });
});

describe('MediaUploadField variants with no backend yet', () => {
    /*
     * Gallery and the generic file row still have no endpoint: a gallery needs
     * many rows while every form column holds one, and a file row does not
     * exist. This is the test that stops those blocks being wired back in
     * early, because a file input there would be a control whose every attempt
     * fails.
     */
    it.each(['gallery', 'file'] as const)(
        'renders %s as a notice rather than a picker',
        (variant) => {
            const { container } = renderVariant({
                id: `${variant}-field`,
                variant,
                label: 'Berkas',
            });

            expect(screen.getByText('Belum tersedia')).toBeInTheDocument();
            expect(
                container.querySelector('[data-test="media-unavailable"]'),
            ).not.toBeNull();
            expect(
                container.querySelectorAll('input[type="file"]'),
            ).toHaveLength(0);
        },
    );
});

describe('MediaUploadField document', () => {
    function renderDocument() {
        const onFileChange = vi.fn();

        const view = renderVariant({
            id: 'document-file',
            variant: 'document',
            label: 'Berkas dokumen',
            hint: 'PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, atau ZIP.',
            onFileChange,
            currentFileName: 'pedoman-lama.pdf',
        });

        return { ...view, onFileChange };
    }

    it('offers a file input and shows the file the record already has', () => {
        const { container } = renderDocument();

        const input = container.querySelector('input[type="file"]');

        expect(input).not.toBeNull();
        expect(input).toHaveAttribute('accept');
        expect(input?.getAttribute('accept')).toContain('.pdf');
        expect(screen.getByText(/pedoman-lama\.pdf/)).toBeInTheDocument();
    });

    it('reports the selected file to the form', () => {
        const { container, onFileChange } = renderDocument();
        const file = new File(['isi'], 'pedoman.pdf', {
            type: 'application/pdf',
        });

        fireEvent.change(container.querySelector('input[type="file"]')!, {
            target: { files: [file] },
        });

        expect(onFileChange).toHaveBeenCalledWith(file);
    });
});

describe('MediaUploadField image without a crop', () => {
    it('does not offer to crop when no ratio is given', () => {
        /*
         * A map cropped to a ratio has had streets cut off it, and the public
         * page's object-cover was already doing that silently. Omitting `aspect`
         * is how a caller says "this is not framed content" — so neither the
         * dialog nor the sentence about it may appear.
         */
        const { container } = renderField({
            aspect: undefined,
            currentUrl: null,
        });

        const description = container.querySelector(
            '[data-slot="upload-dropzone-description"]',
        );

        expect(description?.textContent).toContain('JPEG, PNG');
        expect(description?.textContent).not.toContain('dipotong');
    });

    it('still offers the crop when a ratio is given', () => {
        const { container } = renderField({ currentUrl: null });

        const description = container.querySelector(
            '[data-slot="upload-dropzone-description"]',
        );

        expect(description?.textContent).toContain('dipotong');
    });

    it('says the field is empty when the caller has copy for it', () => {
        // "Thumbnail bersifat opsional." is real information, and a dropzone
        // alone does not carry it.
        renderField({ currentUrl: null, emptyHint: 'Belum ada gambar peta.' });

        expect(screen.getByText('Belum ada gambar peta.')).toBeInTheDocument();
    });

    it('says nothing when the caller has no copy for it', () => {
        const { container } = renderField({ currentUrl: null });

        expect(
            container.querySelector('[data-test="media-current"]'),
        ).toBeNull();
    });
});

describe('MediaUploadField logo empty copy', () => {
    it('uses the caller copy on the empty card', () => {
        renderVariant({
            id: 'profile-logo',
            variant: 'logo',
            label: 'Logo paroki',
            emptyHint: 'Belum ada logo paroki.',
            onMediaChange: () => {},
            currentUrl: null,
            fieldName: 'logo_media_id',
        });

        expect(screen.getByText('Belum ada logo paroki.')).toBeInTheDocument();
    });
});
