import { describe, expect, it } from 'vite-plus/test';
import { screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ParishNavbar } from '@/layouts/public/components/parish-navbar';
import { renderWithInertia } from '../support/inertia';

/**
 * ParishNavbar.
 *
 * The assertions here are about contract, not appearance. Which CSS class a
 * menu item carries when it is active is a design decision DESIGN.md can change
 * without breaking anything; the fact that exactly one item says aria-current is
 * a promise to a screen reader that cannot be changed silently.
 *
 * The rendered navbar is the desktop bar. In happy-dom there is no viewport, so
 * `hidden lg:block` and `lg:hidden` both resolve to the classes being present
 * rather than the elements being hidden - everything is queryable, which is what
 * makes it possible to assert on the desktop bar and on the Sheet at the same
 * time. The Sheet's own behaviour is asserted on for real, because that is
 * interaction rather than layout.
 */

const MENU = [
    { label: 'Beranda', href: '/' },
    { label: 'Profil', href: '/profil' },
    { label: 'Jadwal Misa', href: '/jadwal-misa' },
    { label: 'Berita & Artikel', href: '/berita' },
    { label: 'Agenda', href: '/agenda' },
    { label: 'Pelayanan', href: '/pelayanan' },
    { label: 'Komunitas', href: '/komunitas' },
    { label: 'Galeri', href: '/galeri' },
    { label: 'Download', href: '/download' },
    { label: 'Kontak', href: '/kontak' },
];

function desktopBar() {
    return screen.getByRole('navigation', { name: 'Navigasi utama' });
}

describe('ParishNavbar', () => {
    it('renders every main menu entry DESIGN.md lists, in order', () => {
        renderWithInertia(<ParishNavbar />);

        const links = within(desktopBar()).getAllByRole('link');

        expect(links.map((link) => link.textContent)).toEqual(
            MENU.map((entry) => entry.label),
        );
    });

    /**
     * The point of the phase. Phase 01 rendered nine of these as disabled
     * spans because the routes did not exist; a menu entry that looks finished
     * and leads nowhere is the failure roadmap section 17 warns about.
     */
    it('links every entry to a real generated URL, with no dead href', () => {
        renderWithInertia(<ParishNavbar />);

        for (const { label, href } of MENU) {
            expect(
                within(desktopBar())
                    .getByRole('link', { name: label })
                    .getAttribute('href'),
            ).toBe(href);
        }
    });

    it('marks the current page with aria-current', () => {
        renderWithInertia(<ParishNavbar />, { url: '/kontak' });

        const current = screen.getAllByRole('link', { current: 'page' });

        expect(current).toHaveLength(1);
        expect(current[0]).toHaveTextContent('Kontak');
    });

    /**
     * Section matching, not exact-path matching. Without this, opening
     * /profil/pastor drops every menu item back to inactive, so a visitor three
     * levels into a section is told they are nowhere.
     */
    it('keeps the parent section active on a sub-page', () => {
        renderWithInertia(<ParishNavbar />, { url: '/profil/pastor' });

        const current = screen.getAllByRole('link', { current: 'page' });

        expect(current).toHaveLength(1);
        expect(current[0]).toHaveTextContent('Profil');
    });

    /**
     * The inverse of the case above, and the reason Beranda is matched exactly
     * rather than by prefix. "/" is a prefix of every URL on the site, so prefix
     * matching would light it up everywhere.
     */
    it('does not mark Beranda active while another section is open', () => {
        renderWithInertia(<ParishNavbar />, { url: '/berita' });

        expect(
            screen.queryByRole('link', { name: 'Beranda', current: 'page' }),
        ).toBeNull();
    });

    it('marks Beranda active on the home page', () => {
        renderWithInertia(<ParishNavbar />, { url: '/' });

        expect(
            screen.getByRole('link', { name: 'Beranda', current: 'page' }),
        ).toBeInTheDocument();
    });

    it('exposes the Jadwal Misa shortcut PRD 5.3 asks for', () => {
        renderWithInertia(<ParishNavbar />);

        const shortcut = screen
            .getAllByRole('link', { name: 'Jadwal Misa' })
            .find((link) => link.closest('header, a')?.tagName === 'A');

        expect(shortcut?.getAttribute('href')).toBe('/jadwal-misa');
    });

    describe('mobile sheet', () => {
        it('opens from the labelled trigger', async () => {
            const user = userEvent.setup();

            renderWithInertia(<ParishNavbar />);

            expect(screen.queryByRole('dialog')).toBeNull();

            await user.click(
                screen.getByRole('button', { name: 'Buka navigasi' }),
            );

            expect(screen.getByRole('dialog')).toBeInTheDocument();
        });

        /**
         * PRD NFR-A11Y names Escape explicitly for the hamburger menu, and Radix
         * Dialog is what implements it - so this test is really pinning the
         * choice of primitive. Swapping Sheet for a hand-rolled drawer would
         * fail here.
         */
        it('closes on Escape', async () => {
            const user = userEvent.setup();

            renderWithInertia(<ParishNavbar />);

            await user.click(
                screen.getByRole('button', { name: 'Buka navigasi' }),
            );
            expect(screen.getByRole('dialog')).toBeInTheDocument();

            await user.keyboard('{Escape}');

            expect(screen.queryByRole('dialog')).toBeNull();
        });

        it('carries the same ten entries as the desktop bar', async () => {
            const user = userEvent.setup();

            renderWithInertia(<ParishNavbar />);

            await user.click(
                screen.getByRole('button', { name: 'Buka navigasi' }),
            );

            const drawer = within(screen.getByRole('dialog')).getByRole(
                'navigation',
                { name: 'Navigasi utama' },
            );

            expect(
                within(drawer)
                    .getAllByRole('link')
                    .map((link) => link.textContent),
            ).toEqual(MENU.map((entry) => entry.label));
        });

        /**
         * A Sheet driven only by its trigger cannot close itself on navigation:
         * the click navigates, the dialog is never told, and on a phone the
         * drawer stays open covering the page the visitor just chose.
         */
        it('closes after a link inside it is used', async () => {
            const user = userEvent.setup();

            renderWithInertia(<ParishNavbar />);

            await user.click(
                screen.getByRole('button', { name: 'Buka navigasi' }),
            );

            const drawer = within(screen.getByRole('dialog'));
            await user.click(drawer.getByRole('link', { name: 'Profil' }));

            expect(screen.queryByRole('dialog')).toBeNull();
        });
    });

    describe('touch targets', () => {
        /**
         * PRD 6.8 and NFR-RESP both state 44px, and DESIGN.md's responsive
         * section restates it. A navbar link shorter than that is the most common
         * way a mobile parish site fails the guideline, and it is invisible in a
         * desktop review.
         */
        it.each(MENU.map((entry) => entry.label))(
            'gives %s a link at least 44px tall',
            (label) => {
                renderWithInertia(<ParishNavbar />);

                const link = within(desktopBar()).getByRole('link', {
                    name: label,
                });

                // h-11 on the bar link is the contract; happy-dom does not
                // resolve Tailwind classes to pixels, so this asserts the class
                // that carries the height rather than a measured box.
                expect(link.className).toContain('h-11');
            },
        );

        it('sizes the sheet trigger with the 44px icon button', () => {
            renderWithInertia(<ParishNavbar />);

            expect(
                screen.getByRole('button', { name: 'Buka navigasi' }).className,
            ).toContain('size-11');
        });
    });
});
