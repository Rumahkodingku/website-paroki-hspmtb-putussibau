import { describe, expect, it } from 'vite-plus/test';
import { screen, within } from '@testing-library/react';
import { ParishFooter } from '@/layouts/public/components/parish-footer';
import type { ContactDetails } from '@/types';
import { renderWithInertia } from '../support/inertia';

/**
 * ParishFooter.
 *
 * Two things are being pinned here, and one of them replaced a Phase 03
 * behaviour. Phase 03 deliberately rendered `[ISI: …]` placeholders for
 * contact data that had no rows, so the gap was visible. Phase 8C moved the
 * footer onto the same `contact` shared prop the /kontak page uses, which means
 * an unconfigured field is now hidden instead of shown as a placeholder
 * (KON-11/KON-10, D-67). The empty-state test below is the regression guard for
 * that decision.
 *
 * The navigation assertions are unrelated to contact and stay exactly as they
 * were: "no broken navigation" is an acceptance criterion, and the footer once
 * shipped `href="#"` for Galeri and Download.
 */

const EXPECTED_LINKS: Record<string, string> = {
    Beranda: '/',
    Profil: '/profil',
    'Jadwal Misa': '/jadwal-misa',
    'Berita & Artikel': '/berita',
    Agenda: '/agenda',
    Pelayanan: '/pelayanan',
    Komunitas: '/komunitas',
    Galeri: '/galeri',
    Dokumen: '/dokumen',
    'Halaman kontak': '/kontak',
    Sejarah: '/profil/sejarah',
    'Wilayah Pelayanan': '/profil/wilayah',
    Area: '/area',
    Pastor: '/profil/pastor',
    'Struktur Kepengurusan': '/profil/struktur',
};

const CONFIGURED_CONTACT: ContactDetails = {
    address: 'Jalan Contoh No. 1\nPutussibau',
    phone: { display: '0812-3456-7890', tel: 'tel:+6281234567890' },
    whatsapp: {
        display: '+62 812 3456 7890',
        url: 'https://wa.me/6281234567890',
    },
    email: 'sekretariat@example.test',
    officeHours: null,
    mapsEmbedUrl: null,
    mapsLink: 'https://www.google.com/maps/place/Paroki/@-0.02,111.7,17z',
    homeShowContact: true,
    social: {
        facebook: 'https://facebook.com/paroki',
        instagram: null,
        youtube: null,
        whatsappChannel: null,
    },
};

describe('ParishFooter', () => {
    it('renders every navigation link with a real href', () => {
        renderWithInertia(<ParishFooter />);

        for (const [label, href] of Object.entries(EXPECTED_LINKS)) {
            expect(
                screen.getByRole('link', { name: label }).getAttribute('href'),
            ).toBe(href);
        }
    });

    /**
     * The regression this phase exists to prevent. A literal '#' or '' resolves
     * without a request and looks like a link to a screen reader.
     */
    it('contains no dead link anywhere in the footer', () => {
        const { container } = renderWithInertia(<ParishFooter />);

        const dead = [...container.querySelectorAll('a[href]')]
            .map((anchor) => anchor.getAttribute('href'))
            .filter((href) => href === '#' || href === '' || href === null);

        expect(dead).toEqual([]);
    });

    /**
     * An unconfigured parish shows no contact placeholder and no empty anchor.
     * The footer is on every page, so a blank "Telepon:" row would be the most
     * visible empty state in the site.
     */
    it('hides contact rows when nothing is configured', () => {
        renderWithInertia(<ParishFooter />);

        expect(screen.queryByText(/\[ISI: alamat/)).toBeNull();
        expect(screen.queryByText(/\[ISI: nomor sekretariat/)).toBeNull();
        expect(screen.queryByText(/\[ISI: alamat surel/)).toBeNull();
        expect(screen.queryByText(/\[ISI: tautan peta/)).toBeNull();
        expect(screen.queryByText(/Alamat:/)).toBeNull();
        expect(screen.queryByText(/Telepon:/)).toBeNull();
        expect(screen.queryByText(/Surel:/)).toBeNull();
        expect(
            screen.queryByRole('link', { name: 'Petunjuk arah' }),
        ).toBeNull();
    });

    it('renders the configured contact details as working links', () => {
        renderWithInertia(<ParishFooter />, {
            props: { contact: CONFIGURED_CONTACT },
        });

        expect(screen.getByText(/Jalan Contoh No\. 1/)).toBeInTheDocument();

        expect(
            screen.getByRole('link', { name: /Telepon: 0812-3456-7890/ }),
        ).toHaveAttribute('href', 'tel:+6281234567890');

        expect(
            screen.getByRole('link', {
                name: /Surel: sekretariat@example\.test/,
            }),
        ).toHaveAttribute('href', 'mailto:sekretariat@example.test');

        const whatsapp = screen.getByRole('link', {
            name: /WhatsApp: \+62 812 3456 7890/,
        });

        expect(whatsapp).toHaveAttribute('href', 'https://wa.me/6281234567890');
        expect(whatsapp).toHaveAttribute('target', '_blank');
        expect(whatsapp).toHaveAttribute('rel', 'noopener noreferrer');

        expect(
            screen.getByRole('link', { name: 'Petunjuk arah' }),
        ).toHaveAttribute(
            'href',
            'https://www.google.com/maps/place/Paroki/@-0.02,111.7,17z',
        );
    });

    /**
     * A link to a social channel that has never been configured is a dead end
     * the visitor only discovers after tapping, so it is absent rather than an
     * anchor pointing nowhere.
     */
    it('does not turn an unconfigured social handle into a link', () => {
        renderWithInertia(<ParishFooter />);

        expect(screen.queryByRole('link', { name: 'Facebook' })).toBeNull();
        expect(
            within(screen.getByRole('contentinfo')).queryByText('Media Sosial'),
        ).toBeNull();
    });

    it('renders configured social links in a new tab safely', () => {
        renderWithInertia(<ParishFooter />, {
            props: { contact: CONFIGURED_CONTACT },
        });

        const facebook = screen.getByRole('link', { name: 'Facebook' });

        expect(facebook).toHaveAttribute('href', 'https://facebook.com/paroki');
        expect(facebook).toHaveAttribute('target', '_blank');
        expect(facebook).toHaveAttribute('rel', 'noopener noreferrer');
        expect(screen.queryByRole('link', { name: 'Instagram' })).toBeNull();
    });

    /**
     * The one real parish string on the page. parish_name is seeded from PRD
     * Lampiran D, so displaying it is showing approved data rather than
     * inventing it - and it arrives through the shared `seo` prop so the footer
     * never reaches for the site_settings table itself (D-23).
     */
    it('shows the seeded parish name from the shared seo prop', () => {
        renderWithInertia(<ParishFooter />, {
            props: {
                seo: {
                    appUrl: 'http://localhost',
                    siteName: 'Paroki Hati Santa Perawan Maria Tak Bernoda',
                    title: null,
                    description: null,
                    ogImage: null,
                },
            },
        });

        expect(
            screen.getAllByText('Paroki Hati Santa Perawan Maria Tak Bernoda')
                .length,
        ).toBeGreaterThan(0);
    });

    /**
     * PRD XC-P4 wants a privacy policy link. PRD 5.1 has no route for one, so
     * the label stays as text; this asserts the gap is visible rather than
     * quietly deleted, and D-31 records it.
     */
    it('shows the legal labels even though no route exists for them yet', () => {
        renderWithInertia(<ParishFooter />);

        expect(screen.getByText('Kebijakan Privasi')).toBeInTheDocument();

        expect(
            screen.queryByRole('link', { name: 'Kebijakan Privasi' }),
        ).toBeNull();
    });

    it('exposes the information-architecture group headings', () => {
        renderWithInertia(<ParishFooter />, {
            props: { contact: CONFIGURED_CONTACT },
        });

        for (const title of [
            'Menu Utama',
            'Profil Paroki',
            'Pelayanan & Komunitas',
            'Media Sosial',
            'Kontak',
        ]) {
            expect(
                within(screen.getByRole('contentinfo')).getByRole('heading', {
                    name: title,
                }),
            ).toBeInTheDocument();
        }
    });

    it('gives every footer link a 44px minimum touch target', () => {
        renderWithInertia(<ParishFooter />);

        for (const label of Object.keys(EXPECTED_LINKS)) {
            expect(
                screen.getByRole('link', { name: label }).className,
            ).toContain('min-h-11');
        }
    });
});
