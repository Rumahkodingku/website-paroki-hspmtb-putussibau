import { afterEach, describe, expect, it, vi } from 'vitest';
import { fireEvent, screen, waitFor } from '@testing-library/react';
import Kontak from '@/features/public/kontak/pages/index';
import type { ContactDetails } from '@/types';
import { renderWithInertia } from '../../../support/inertia';

/**
 * The /kontak page.
 *
 * The contract is the `contact` shared prop, so these tests vary that prop
 * rather than mocking SiteSettingsService. What matters is what a visitor can
 * act on: a configured phone becomes a tel link, an absent one becomes no card,
 * and a map appears only when the server sent a validated embed URL.
 */

const EMPTY: ContactDetails = {
    address: null,
    phone: null,
    whatsapp: null,
    email: null,
    officeHours: null,
    mapsEmbedUrl: null,
    mapsLink: null,
    homeShowContact: true,
    social: {
        facebook: null,
        instagram: null,
        youtube: null,
        whatsappChannel: null,
    },
};

const CONFIGURED: ContactDetails = {
    address: 'Jalan Contoh No. 1\nPutussibau',
    phone: { display: '0812-3456-7890', tel: 'tel:+6281234567890' },
    whatsapp: {
        display: '+62 812 3456 7890',
        url: 'https://wa.me/6281234567890',
    },
    email: 'sekretariat@example.test',
    officeHours: 'Senin–Jumat, 08.00–15.00',
    mapsEmbedUrl: 'https://www.google.com/maps?q=Paroki&z=17&output=embed',
    mapsLink: 'https://www.google.com/maps/place/Paroki/@-0.02,111.7,17z',
    homeShowContact: true,
    social: {
        facebook: 'https://facebook.com/paroki',
        instagram: null,
        youtube: null,
        whatsappChannel: null,
    },
};

function renderPage(contact: ContactDetails) {
    return renderWithInertia(<Kontak />, { props: { contact } });
}

const originalClipboard = navigator.clipboard;

afterEach(() => {
    Object.defineProperty(navigator, 'clipboard', {
        value: originalClipboard,
        configurable: true,
    });
});

describe('Kontak', () => {
    it('renders the heading and every configured detail', () => {
        renderPage(CONFIGURED);

        expect(
            screen.getByRole('heading', { name: 'Kami siap membantu Anda' }),
        ).toBeInTheDocument();
        expect(screen.getByText(/Jalan Contoh No\. 1/)).toBeInTheDocument();
        expect(screen.getByText('0812-3456-7890')).toBeInTheDocument();
        expect(
            screen.getByText('sekretariat@example.test'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Senin–Jumat, 08.00–15.00'),
        ).toBeInTheDocument();
    });

    it('builds the action links from the server-resolved values', () => {
        renderPage(CONFIGURED);

        expect(screen.getByRole('link', { name: 'Telepon' })).toHaveAttribute(
            'href',
            'tel:+6281234567890',
        );

        expect(
            screen.getByRole('link', { name: 'Chat WhatsApp' }),
        ).toHaveAttribute('href', 'https://wa.me/6281234567890');

        expect(
            screen.getByRole('link', { name: 'Kirim Email' }),
        ).toHaveAttribute('href', 'mailto:sekretariat@example.test');

        expect(
            screen.getByRole('link', { name: 'Petunjuk Arah' }),
        ).toHaveAttribute(
            'href',
            'https://www.google.com/maps/place/Paroki/@-0.02,111.7,17z',
        );
    });

    it('opens external actions in a new tab safely', () => {
        renderPage(CONFIGURED);

        for (const name of ['Chat WhatsApp', 'Petunjuk Arah']) {
            const link = screen.getByRole('link', { name });

            expect(link).toHaveAttribute('target', '_blank');
            expect(link).toHaveAttribute('rel', 'noopener noreferrer');
        }
    });

    it('shows an honest empty state when nothing is configured', () => {
        renderPage(EMPTY);

        expect(
            screen.getByText('Informasi kontak belum tersedia'),
        ).toBeInTheDocument();

        expect(screen.queryByRole('link', { name: 'Telepon' })).toBeNull();
        expect(
            screen.queryByRole('link', { name: 'Chat WhatsApp' }),
        ).toBeNull();
        expect(screen.queryByRole('link', { name: 'Kirim Email' })).toBeNull();
        expect(screen.queryByTitle('Peta lokasi paroki')).toBeNull();
        expect(screen.queryByText(/\[ISI:/)).toBeNull();
    });

    it('renders the map only when an embed URL is present', () => {
        renderPage({ ...EMPTY, mapsEmbedUrl: CONFIGURED.mapsEmbedUrl });

        const frame = screen.getByTitle('Peta lokasi paroki');

        expect(frame).toHaveAttribute(
            'src',
            'https://www.google.com/maps?q=Paroki&z=17&output=embed',
        );
        expect(frame).toHaveAttribute('loading', 'lazy');

        expect(
            screen.queryByRole('link', { name: 'Petunjuk Arah' }),
        ).toBeNull();
    });

    it('renders directions without a map when only the link exists', () => {
        renderPage({ ...EMPTY, mapsLink: CONFIGURED.mapsLink });

        expect(screen.queryByTitle('Peta lokasi paroki')).toBeNull();
        expect(
            screen.getByRole('link', { name: 'Petunjuk Arah' }),
        ).toBeInTheDocument();
    });

    it('does not render a social section when no channel is configured', () => {
        renderPage(EMPTY);

        expect(
            screen.queryByRole('heading', { name: 'Media Sosial' }),
        ).toBeNull();
        expect(screen.queryByRole('link', { name: 'Facebook' })).toBeNull();
    });

    it('renders configured social channels with an accessible name', () => {
        renderPage(CONFIGURED);

        const facebook = screen.getByRole('link', { name: 'Facebook' });

        expect(facebook).toHaveAttribute('href', 'https://facebook.com/paroki');
        expect(facebook).toHaveAttribute('target', '_blank');
        expect(facebook).toHaveAttribute('rel', 'noopener noreferrer');
    });

    it('copies the address and confirms it', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined);

        Object.defineProperty(navigator, 'clipboard', {
            value: { writeText },
            configurable: true,
        });

        renderPage({ ...EMPTY, address: 'Jalan Contoh No. 1' });

        fireEvent.click(screen.getByRole('button', { name: 'Salin alamat' }));

        await waitFor(() =>
            expect(writeText).toHaveBeenCalledWith('Jalan Contoh No. 1'),
        );

        expect(await screen.findByText('Tersalin')).toBeInTheDocument();
    });
});
