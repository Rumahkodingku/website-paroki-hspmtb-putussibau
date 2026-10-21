import { describe, expect, it } from 'vitest';
import { screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    CommunityForm,
    emptyCommunityFormData,
} from '@/features/admin/komunitas/components/community-form';
import type { CommunityFormInstance } from '@/features/admin/komunitas/components/community-form-instance';
import type { CommunityFormData } from '@/features/admin/komunitas/types';
import { renderWithInertia } from '../../../support/inertia';

/*
 * The community form's tab shell.
 *
 * What is tested here is the thing a tabbed form can get quietly wrong: where a
 * rejected submission puts the admin. A message that renders inside a tab nobody
 * is looking at is not an error report, it is a form that looks broken, so the
 * two promises made to the user are that the tab holding the problem opens
 * itself, and that the trigger says how many are waiting there.
 *
 * Errors are driven through Inertia's own form API - `setErrors`, called from the
 * `onSubmit` prop the way a rejected visit would - rather than by stubbing
 * @inertiajs/react. The alternative would describe a mock instead of the form.
 */

const LABELS: Record<string, string> = {
    name: 'Nama',
    slug: 'Slug',
    category: 'Kategori',
    status: 'Status Komunitas',
    description: 'Deskripsi',
    logo: 'Logo',
    logo_alt: 'Teks alternatif logo',
    thumbnail: 'Thumbnail',
    thumbnail_alt: 'Teks alternatif thumbnail',
    vision: 'Visi',
    mission: 'Misi',
    meeting_schedule: 'Jadwal Pertemuan',
    meeting_location: 'Lokasi Pertemuan',
    eligibility: 'Persyaratan Bergabung',
    joining_info: 'Cara Bergabung',
    contact_name: 'Nama Kontak',
    contact_phone: 'Nomor Kontak',
    show_contact: 'Tampilkan kontak di halaman publik',
    is_published: 'Publikasi',
};

const HINTS: Record<string, string> = {
    slug: 'Terbentuk otomatis dari nama.',
    logo: 'PNG atau JPG.',
    thumbnail: 'PNG atau JPG.',
    show_contact: 'Kontak hanya tampil bila opsi ini aktif.',
};

const COPY = {
    sections: {
        basic: 'Informasi Dasar',
        images: 'Logo dan Thumbnail',
        profile: 'Profil Komunitas',
        schedule: 'Jadwal dan Lokasi',
        joining: 'Informasi Bergabung dan Kontak',
        publication: 'Publikasi',
    },
    publication_live: 'Dipublikasikan',
    publication_draft: 'Draf',
};

const STATUSES = [
    { value: 'forming' as const, label: 'Dalam Pembentukan' },
    { value: 'active' as const, label: 'Aktif' },
    { value: 'inactive' as const, label: 'Nonaktif' },
];

const CATEGORIES = [{ id: 1, name: 'Kategorial', slug: 'kategorial' }];

/**
 * Render the form with a submit handler that fails the way the server does.
 *
 * The handler runs inside the test's own onSubmit, which the component already
 * hands its form instance to, so the assertion is on the component's reaction
 * rather than on a fake.
 */
function renderForm(
    errors: Record<string, string>,
    initial: Partial<CommunityFormData> = {},
) {
    const onSubmit = (form: CommunityFormInstance) => {
        // setError, not setErrors: the plural form belongs to the precognitive
        // variant of the API, and useForm() without precognition has the
        // singular one that also takes a whole error object.
        form.setError(errors);
    };

    renderWithInertia(
        <CommunityForm
            initial={{ ...emptyCommunityFormData(), ...initial }}
            labels={LABELS}
            hints={HINTS}
            copy={COPY}
            statuses={STATUSES}
            categories={CATEGORIES}
            submitLabel="Simpan komunitas"
            onSubmit={onSubmit}
            onCancel={() => undefined}
        />,
        { url: '/admin/komunitas/tambah' },
    );

    return {
        submit: () =>
            userEvent.click(
                screen.getByRole('button', { name: 'Simpan komunitas' }),
            ),
    };
}

function tab(name: string): HTMLElement {
    return screen.getByRole('tab', { name: new RegExp(`^${name}`) });
}

describe('CommunityForm — tab shell', () => {
    it('opens on Informasi Dasar and mounts only that panel', () => {
        renderForm({});

        expect(tab('Informasi Dasar')).toHaveAttribute('aria-selected', 'true');
        expect(screen.getByLabelText(/^Nama/)).toBeInTheDocument();

        // Visi belongs to the Profil tab. Radix unmounts an inactive panel, so
        // its absence is the proof the tabs really are separate.
        expect(screen.queryByLabelText(/^Visi/)).not.toBeInTheDocument();
    });

    it('moves to the tab holding the error and badges it', async () => {
        const { submit } = renderForm({
            contact_phone: 'Nomor telepon tidak dikenali.',
        });

        await submit();

        const target = tab('Informasi Bergabung dan Kontak');

        expect(target).toHaveAttribute('aria-selected', 'true');

        // The count is its own text node; the word behind it is sr-only, so it
        // is what a screen reader hears and what getByText cannot reach from
        // the badge element.
        expect(within(target).getByText('1')).toBeInTheDocument();
        expect(within(target).getByText('kesalahan')).toBeInTheDocument();
        expect(screen.getByText('Nomor telepon tidak dikenali.')).toBeVisible();
    });

    it('badges every tab that has an error, not just the one it lands on', async () => {
        const { submit } = renderForm({
            name: 'Nama wajib diisi.',
            contact_phone: 'Nomor telepon tidak dikenali.',
        });

        await submit();

        // The first error in tab order wins the jump, and both tabs say so.
        expect(tab('Informasi Dasar')).toHaveAttribute('aria-selected', 'true');
        expect(
            within(tab('Informasi Dasar')).getByText('1'),
        ).toBeInTheDocument();
        expect(
            within(tab('Informasi Bergabung dan Kontak')).getByText('1'),
        ).toBeInTheDocument();
    });

    it('leaves a tab alone when the errors arrive for one already open', async () => {
        const { submit } = renderForm({ vision: 'Visi terlalu panjang.' });

        await submit();

        expect(tab('Profil Komunitas')).toHaveAttribute(
            'aria-selected',
            'true',
        );

        // A second rejection changes the errors but not the story: the tab that
        // holds them is still the one on screen.
        await submit();

        expect(tab('Profil Komunitas')).toHaveAttribute(
            'aria-selected',
            'true',
        );
    });

    it('keeps every trigger reachable while the panels come and go', async () => {
        renderForm({});

        const names = [
            'Informasi Dasar',
            'Logo dan Thumbnail',
            'Profil Komunitas',
            'Jadwal dan Lokasi',
            'Informasi Bergabung dan Kontak',
            'Publikasi',
        ];

        // Six triggers, and clicking one swaps which panel is mounted.
        for (const name of names) {
            expect(
                screen.getByRole('tab', { name: new RegExp(`^${name}`) }),
            ).toBeInTheDocument();
        }

        await userEvent.click(tab('Publikasi'));

        expect(screen.getByRole('switch', { name: 'Publikasi' })).toBeVisible();
        expect(screen.queryByLabelText('Nama')).not.toBeInTheDocument();
    });
});
