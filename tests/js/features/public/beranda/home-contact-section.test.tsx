import { describe, expect, it } from 'vite-plus/test';
import { screen } from '@testing-library/react';
import { HomeContactSection } from '@/features/public/beranda/components/home-contact-section';
import type { ContactDetails } from '@/types';
import { renderWithInertia } from '../../../support/inertia';

/**
 * `home_show_contact` gates the homepage block and nothing else (KON-12).
 * The block is also absent when the parish has no contact data to summarise,
 * so an enabled switch cannot produce five empty labels.
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
    ...EMPTY,
    address: 'Jalan Contoh No. 1',
    phone: { display: '0812-3456-7890', tel: 'tel:+6281234567890' },
    email: 'sekretariat@example.test',
    officeHours: 'Senin–Jumat: 08.00–15.00 WIB\nSabtu: Tutup',
};

describe('HomeContactSection', () => {
    it('renders the summary when home_show_contact is true', () => {
        renderWithInertia(<HomeContactSection contact={CONFIGURED} />);

        expect(
            screen.getByRole('heading', { name: 'Hubungi Kami' }),
        ).toBeInTheDocument();
        expect(screen.getByText('Jalan Contoh No. 1')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: 'Halaman Kontak' }),
        ).toHaveAttribute('href', '/kontak');
    });

    it('renders nothing when home_show_contact is false', () => {
        const { container } = renderWithInertia(
            <HomeContactSection
                contact={{ ...CONFIGURED, homeShowContact: false }}
            />,
        );

        expect(container).toBeEmptyDOMElement();
    });

    it('renders nothing when there is no contact detail to summarise', () => {
        const { container } = renderWithInertia(
            <HomeContactSection contact={EMPTY} />,
        );

        expect(container).toBeEmptyDOMElement();
    });

    it('links the phone number with tel and email with mailto', () => {
        renderWithInertia(<HomeContactSection contact={CONFIGURED} />);

        expect(
            screen.getByRole('link', { name: /0812-3456-7890/ }),
        ).toHaveAttribute('href', 'tel:+6281234567890');

        expect(
            screen.getByRole('link', { name: /sekretariat@example\.test/ }),
        ).toHaveAttribute('href', 'mailto:sekretariat@example.test');
    });

    it('summarises the weekly office hours from the shared prop', () => {
        renderWithInertia(<HomeContactSection contact={CONFIGURED} />);

        expect(screen.getByText('Jam Layanan')).toBeInTheDocument();

        /*
         * Read textContent off the item rather than matching the whole string:
         * getByText normalises whitespace, so a schedule whose lines are
         * separated by "\n" can only be matched line by line.
         */
        const item = screen.getByText('Jam Layanan').closest('li');

        expect(item?.textContent).toContain('Senin–Jumat: 08.00–15.00 WIB');
        expect(item?.textContent).toContain('Sabtu: Tutup');
    });

    it('omits the office hours label when no schedule is configured', () => {
        renderWithInertia(
            <HomeContactSection
                contact={{ ...CONFIGURED, officeHours: null }}
            />,
        );

        expect(screen.queryByText('Jam Layanan')).toBeNull();
    });
});
