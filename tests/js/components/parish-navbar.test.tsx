import { describe, expect, it } from 'vite-plus/test';
import { screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { ParishNavbar } from '@/layouts/public/components/parish-navbar';
import { renderWithInertia } from '../support/inertia';

/**
 * ParishNavbar.
 *
 * These assertions are about contract, not appearance. Which CSS class a menu
 * item carries when it is active is a design decision DESIGN-v2 can change
 * without breaking anything; the fact that the current section is announced is a
 * promise to a screen reader that cannot be changed silently.
 *
 * The rendered navbar is the desktop bar. In happy-dom there is no viewport, so
 * `hidden lg:flex` and `lg:hidden` both resolve to the classes being present
 * rather than the elements being hidden - everything is queryable, which is what
 * makes it possible to assert on the desktop bar and on the Sheet at the same
 * time. The Sheet's own behaviour is asserted on for real.
 *
 * The desktop bar is grouped (D-73): five direct links and two disclosure
 * buttons. The mobile drawer stays a flat list of every destination.
 */

/** Every destination, as the flat mobile drawer lists them. */
const DRAWER_MENU = [
    'Beranda',
    'Jadwal Misa',
    'Berita & Artikel',
    'Agenda',
    'Kontak',
];

const DESKTOP_LINKS = [
    { label: 'Beranda', href: '/' },
    { label: 'Jadwal Misa', href: '/jadwal-misa' },
    { label: 'Berita & Artikel', href: '/berita' },
    { label: 'Agenda', href: '/agenda' },
    { label: 'Kontak', href: '/kontak' },
];

const DESKTOP_GROUPS: Record<string, { label: string; href: string }[]> = {
    Profil: [
        { label: 'Sejarah', href: '/profil/sejarah' },
        { label: 'Wilayah Pelayanan', href: '/profil/wilayah' },
        { label: 'Area', href: '/area' },
        { label: 'Pastor', href: '/profil/pastor' },
        { label: 'Struktur Kepengurusan', href: '/profil/struktur' },
    ],
    Pelayanan: [
        { label: 'Pelayanan', href: '/pelayanan' },
        { label: 'Komunitas', href: '/komunitas' },
        { label: 'Galeri', href: '/galeri' },
        { label: 'Dokumen', href: '/dokumen' },
    ],
};

function desktopBar() {
    return screen.getByRole('navigation', { name: 'Navigasi utama' });
}

describe('ParishNavbar', () => {
    describe('desktop bar', () => {
        it('renders its direct links in order', () => {
            renderWithInertia(<ParishNavbar />);

            const links = within(desktopBar()).getAllByRole('link');

            expect(links.map((link) => link.textContent)).toEqual(
                DESKTOP_LINKS.map((entry) => entry.label),
            );
        });

        it('links every direct entry to a real generated URL', () => {
            renderWithInertia(<ParishNavbar />);

            for (const { label, href } of DESKTOP_LINKS) {
                expect(
                    within(desktopBar())
                        .getByRole('link', { name: label })
                        .getAttribute('href'),
                ).toBe(href);
            }
        });

        /**
         * The two grouped destinations are disclosure buttons, not links. That
         * is what Radix NavigationMenuTrigger is, and it is why the bar has
         * seven controls instead of eleven.
         */
        it('renders the grouped entries as disclosure buttons', () => {
            renderWithInertia(<ParishNavbar />);

            const buttons = within(desktopBar()).getAllByRole('button');

            expect(buttons.map((button) => button.textContent?.trim())).toEqual(
                Object.keys(DESKTOP_GROUPS),
            );
        });

        it.each(Object.entries(DESKTOP_GROUPS))(
            'opens the %s disclosure to reveal its destinations',
            async (label, children) => {
                const user = userEvent.setup();

                renderWithInertia(<ParishNavbar />);

                await user.click(
                    within(desktopBar()).getByRole('button', { name: label }),
                );

                for (const child of children) {
                    expect(
                        within(desktopBar())
                            .getByRole('link', { name: child.label })
                            .getAttribute('href'),
                    ).toBe(child.href);
                }
            },
        );

        it('marks the current page with aria-current', () => {
            renderWithInertia(<ParishNavbar />, { url: '/kontak' });

            const current = screen.getAllByRole('link', { current: 'page' });

            expect(current).toHaveLength(1);
            expect(current[0]).toHaveTextContent('Kontak');
        });

        /**
         * Section matching, not exact-path matching. Without this, opening
         * /profil/pastor drops every menu item back to inactive, so a visitor
         * three levels into a section is told they are nowhere. A grouped
         * section announces the state on its disclosure button.
         */
        it('keeps the parent section active on a sub-page', () => {
            renderWithInertia(<ParishNavbar />, { url: '/profil/pastor' });

            const trigger = within(desktopBar()).getByRole('button', {
                name: 'Profil',
            });

            expect(trigger).toHaveAttribute('aria-current', 'page');
            expect(trigger).toHaveAttribute('data-active', 'true');
            expect(
                screen.queryAllByRole('link', { current: 'page' }),
            ).toHaveLength(0);
        });

        it('does not mark a grouped section active while another is open', () => {
            renderWithInertia(<ParishNavbar />, { url: '/berita' });

            expect(
                within(desktopBar()).getByRole('button', { name: 'Profil' }),
            ).not.toHaveAttribute('aria-current');
        });

        /**
         * The inverse of the case above, and the reason Beranda is matched
         * exactly rather than by prefix. "/" is a prefix of every URL on the
         * site, so prefix matching would light it up everywhere.
         */
        it('does not mark Beranda active while another section is open', () => {
            renderWithInertia(<ParishNavbar />, { url: '/berita' });

            expect(
                screen.queryByRole('link', {
                    name: 'Beranda',
                    current: 'page',
                }),
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

            expect(
                within(desktopBar())
                    .getByRole('link', { name: 'Jadwal Misa' })
                    .getAttribute('href'),
            ).toBe('/jadwal-misa');
        });
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

        it('carries every destination as a flat list', async () => {
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
            ).toEqual(DRAWER_MENU);
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
            await user.click(drawer.getByRole('link', { name: 'Jadwal Misa' }));

            expect(screen.queryByRole('dialog')).toBeNull();
        });
    });

    describe('touch targets', () => {
        /**
         * PRD 6.8 and NFR-RESP both state 44px, and DESIGN-v2's responsive
         * section restates it. A navbar control shorter than that is the most
         * common way a mobile parish site fails the guideline, and it is
         * invisible in a desktop review.
         */
        it.each([
            ...DESKTOP_LINKS.map((entry) => entry.label),
            ...Object.keys(DESKTOP_GROUPS),
        ])('gives the %s control a 44px height', (label) => {
            renderWithInertia(<ParishNavbar />);

            const control =
                within(desktopBar()).queryByRole('link', { name: label }) ??
                within(desktopBar()).getByRole('button', { name: label });

            // h-11 on the control is the contract; happy-dom does not resolve
            // Tailwind classes to pixels, so this asserts the class that carries
            // the height rather than a measured box.
            expect(control.className).toContain('h-11');
        });

        it('sizes the sheet trigger with the 44px icon button', () => {
            renderWithInertia(<ParishNavbar />);

            expect(
                screen.getByRole('button', { name: 'Buka navigasi' }).className,
            ).toContain('size-11');
        });
    });
});
