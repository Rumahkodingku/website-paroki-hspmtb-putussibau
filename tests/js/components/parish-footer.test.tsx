import { describe, expect, it } from 'vite-plus/test';
import { screen, within } from '@testing-library/react';
import { ParishFooter } from '@/layouts/public/components/parish-footer';
import { renderWithInertia } from '../support/inertia';

/**
 * ParishFooter.
 *
 * Two things are being pinned here, and one of them is a bug this phase fixed.
 *
 * Phase 01 rendered Galeri and Download as href="#" and Beranda as a literal
 * "/". All three are navigation to nowhere, and "no broken navigation" is an
 * acceptance criterion of roadmap section 10 rather than a matter of taste. The
 * first test below is the regression guard for that.
 *
 * The second is that unconfigured parish data stays visibly unconfigured.
 * contact_phone and the four social keys exist in config/site-settings.php but
 * have no rows, so rendering a link or a value for them would show the visitor
 * an empty string where an address should be. The [ISI: ...] placeholder is the
 * honest state and this test makes sure it survives being "tidied up".
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
    Download: '/download',
    'Halaman kontak': '/kontak',
    Sejarah: '/profil/sejarah',
    'Visi & Misi': '/profil/visi-misi',
    'Wilayah Pelayanan': '/profil/wilayah',
    Pastor: '/profil/pastor',
    'Struktur Kepengurusan': '/profil/struktur',
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

    it('keeps unconfigured parish data as a visible placeholder', () => {
        renderWithInertia(<ParishFooter />);

        expect(
            screen.getByText('[ISI: nomor sekretariat]'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('[ISI: alamat lengkap paroki]'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('[ISI: tautan Facebook paroki]'),
        ).toBeInTheDocument();
    });

    /**
     * A link to a social channel that has never been configured is a dead end
     * the visitor only discovers after tapping, so the placeholder is plain text
     * rather than an anchor.
     */
    it('does not turn an unconfigured social handle into a link', () => {
        renderWithInertia(<ParishFooter />);

        expect(screen.queryByRole('link', { name: /Facebook/ })).toBeNull();
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
        renderWithInertia(<ParishFooter />);

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
