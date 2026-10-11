import { describe, expect, it } from 'vite-plus/test';
import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { renderWithInertia } from '../support/inertia';
import { AppSidebar } from '@/layouts/app/components/app-sidebar';
import { AppShell } from '@/layouts/app/components/app-shell';
import { TooltipProvider } from '@/components/ui/tooltip';

/*
 * The admin sidebar's navigation.
 *
 * This file exists because of a bug that compiles, type-checks and passes every
 * lint rule: `import { index as summary, index as list } from
 * '@/routes/mass-schedules'` gives two names for one function, so "Misa Rutin"
 * in the sub-menu silently pointed at the summary URL. Nothing complained. An
 * admin clicking "Misa Rutin" landed on the summary and had no way to tell
 * why.
 *
 * A sidebar is the one place a wrong href is a wrong destination rather than a
 * cosmetic mistake, and it is cheap to assert.
 *
 * It also covers the disclosure behaviour, because that is the other thing this
 * file can catch quietly: a group that opens itself when the admin is already
 * inside it, stays shut when they are not, and toggles on demand.
 */

const SUB_MENU = [
    ['Lokasi', '/admin/jadwal-misa/lokasi'],
    ['Misa Rutin', '/admin/jadwal-misa/misa-rutin'],
    ['Misa Khusus', '/admin/jadwal-misa/misa-khusus'],
    ['Pemberitahuan', '/admin/jadwal-misa/pemberitahuan-jadwal'],
] as const;

/**
 * AppSidebar is a fragment of the shell, not a page. Two providers sit above
 * it in the real tree — TooltipProvider in app.tsx and SidebarProvider inside
 * AppShell — and each throws when it is missing. Mirroring both keeps this test
 * about the navigation rather than about how the shell is composed, and the
 * duplication is deliberate: inventing a lighter wrapper here would let the
 * sidebar keep depending on a provider the app does not actually provide.
 */
function renderSidebar(url = '/admin') {
    return renderWithInertia(
        <TooltipProvider delayDuration={0}>
            <AppShell variant="sidebar">
                <AppSidebar />
            </AppShell>
        </TooltipProvider>,
        { url },
    );
}

describe('AppSidebar', () => {
    it('makes the module title a disclosure rather than a link', () => {
        renderSidebar();

        // The whole row opens and closes the sub-menu, so it is a button. That
        // leaves the summary reachable only from the breadcrumb, which is a
        // decision about navigation rather than an accident — so it is asserted
        // in both directions. Testing only the absence of the link would pass
        // against a sidebar that simply forgot to render the row.
        expect(screen.queryByRole('link', { name: 'Jadwal Misa' })).toBeNull();
        expect(
            screen.getByRole('button', { name: 'Jadwal Misa' }),
        ).toHaveAttribute('aria-expanded', 'false');
    });

    it.each(SUB_MENU)('links %s to %s', (title, href) => {
        renderSidebar('/admin/jadwal-misa/lokasi');

        expect(screen.getByRole('link', { name: title })).toHaveAttribute(
            'href',
            href,
        );
    });

    it('gives every child its own URL', () => {
        renderSidebar('/admin/jadwal-misa/lokasi');

        const hrefs = SUB_MENU.map(([title]) =>
            screen.getByRole('link', { name: title }).getAttribute('href'),
        );

        // The bug this file guards produced one duplicate here and made a list
        // unreachable. Distinctness catches it whatever the exact URLs are.
        expect(new Set(hrefs).size).toBe(hrefs.length);
    });

    it('keeps the sub-menu shut when the admin is outside the module', () => {
        renderSidebar('/admin');

        for (const [title] of SUB_MENU) {
            expect(screen.queryByRole('link', { name: title })).toBeNull();
        }

        expect(
            screen.getByRole('button', { name: 'Jadwal Misa' }),
        ).toHaveAttribute('aria-expanded', 'false');
    });

    it('opens itself when the admin is already inside the module', () => {
        // Landing on /admin/jadwal-misa/lokasi and finding "Jadwal Misa" shut above it reads
        // as a broken sidebar, so the group opens from where the admin is.
        renderSidebar('/admin/jadwal-misa/misa-khusus');

        expect(
            screen.getByRole('button', { name: 'Jadwal Misa' }),
        ).toHaveAttribute('aria-expanded', 'true');
        expect(screen.getByRole('link', { name: 'Misa Khusus' })).toBeVisible();
    });

    it('opens and closes on the chevron', async () => {
        renderSidebar('/admin');

        await userEvent.click(
            screen.getByRole('button', { name: 'Jadwal Misa' }),
        );

        expect(
            screen.getByRole('button', { name: 'Jadwal Misa' }),
        ).toBeVisible();
        expect(screen.getByRole('link', { name: 'Misa Rutin' })).toBeVisible();

        await userEvent.click(
            screen.getByRole('button', { name: 'Jadwal Misa' }),
        );

        expect(screen.queryByRole('link', { name: 'Misa Rutin' })).toBeNull();
    });

    it('does not link a module that has no route yet', () => {
        renderSidebar();

        // Newspaper and the rest are marked rather than linked, so nothing in
        // this sidebar points at a URL that would 404. Asserted through the
        // absence of a link rather than through the marker text: the marker is
        // micro-legal styling, and a test that fails when someone rewords it
        // stops guarding the thing it was written for.
        expect(screen.queryByRole('link', { name: 'Berita' })).toBeNull();
        expect(screen.queryByRole('link', { name: 'Galeri' })).toBeNull();
    });
});
