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

const JADWAL_MISA_SUB_MENU = [
    ['Misa Rutin', '/admin/jadwal-misa/misa-rutin'],
    ['Misa Khusus', '/admin/jadwal-misa/misa-khusus'],
    ['Pemberitahuan', '/admin/jadwal-misa/pemberitahuan-jadwal'],
] as const;

/** Master Data took Lokasi in Phase 05, Kategori Pelayanan in Phase 06B, and
 * Kategori Komunitas in Phase 07. Wilayah & Lingkungan became its own item. */
const MASTER_DATA_SUB_MENU = [
    ['Lokasi', '/admin/master-data/lokasi'],
    ['Kategori Berita', '/admin/master-data/kategori-berita'],
    ['Kategori Agenda', '/admin/master-data/kategori-agenda'],
    ['Kategori Pelayanan', '/admin/master-data/kategori-pelayanan'],
    ['Kategori Komunitas', '/admin/master-data/kategori-komunitas'],
] as const;

/** Profil Paroki was a disabled row until Phase 6A gave it a module. */
const PROFIL_PAROKI_SUB_MENU = [
    ['Informasi Profil', '/admin/profil/informasi'],
    ['Sejarah Paroki', '/admin/profil/sejarah'],
    ['Wilayah Pelayanan', '/admin/profil/wilayah'],
    ['Pastor Paroki', '/admin/profil/pastor'],
    ['Struktur Kepengurusan', '/admin/profil/struktur'],
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

/**
 * Open every closed group, so all the sidebar's links are reachable from one
 * render.
 *
 * Async on purpose: the disclosure is React state, so a click has not been
 * applied until userEvent's promise settles. Querying in the same tick as the
 * click finds a sub-menu that has not been rendered yet, and the failure reads
 * as "the link is missing" rather than "the test did not wait".
 */
async function renderSidebarWithGroupsOpen(url = '/admin') {
    renderSidebar(url);

    for (const title of ['Jadwal Misa', 'Master Data', 'Profil Paroki']) {
        const button = screen.queryByRole('button', { name: title });

        if (button?.getAttribute('aria-expanded') === 'false') {
            await userEvent.click(button);
        }
    }
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

    it.each(JADWAL_MISA_SUB_MENU)('links %s to %s', (title, href) => {
        renderSidebar('/admin/jadwal-misa/misa-rutin');

        expect(screen.getByRole('link', { name: title })).toHaveAttribute(
            'href',
            href,
        );
    });

    it('gives every child its own URL', () => {
        renderSidebar('/admin/jadwal-misa/misa-rutin');

        const hrefs = JADWAL_MISA_SUB_MENU.map(([title]) =>
            screen.getByRole('link', { name: title }).getAttribute('href'),
        );

        // The bug this file guards produced one duplicate here and made a list
        // unreachable. Distinctness catches it whatever the exact URLs are.
        expect(new Set(hrefs).size).toBe(hrefs.length);
    });

    it('keeps the sub-menu shut when the admin is outside the module', () => {
        renderSidebar('/admin');

        for (const [title] of JADWAL_MISA_SUB_MENU) {
            expect(screen.queryByRole('link', { name: title })).toBeNull();
        }

        expect(
            screen.getByRole('button', { name: 'Jadwal Misa' }),
        ).toHaveAttribute('aria-expanded', 'false');
    });

    it('opens itself when the admin is already inside the module', () => {
        // Landing on /admin/jadwal-misa/misa-khusus and finding "Jadwal Misa"
        // shut above it reads as a broken sidebar, so the group opens from
        // where the admin is.
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
});

describe('AppSidebar — Master Data', () => {
    it('holds Lokasi, which moved here out of Jadwal Misa', async () => {
        // A room is announced in by an activity as well as by a mass, so
        // listing it under Jadwal Misa claimed an ownership it does not have.
        await renderSidebarWithGroupsOpen('/admin');

        expect(screen.getByRole('link', { name: 'Lokasi' })).toHaveAttribute(
            'href',
            '/admin/master-data/lokasi',
        );
    });

    it.each(MASTER_DATA_SUB_MENU)('links %s to %s', async (title, href) => {
        await renderSidebarWithGroupsOpen('/admin');

        expect(screen.getByRole('link', { name: title })).toHaveAttribute(
            'href',
            href,
        );
    });

    it('gives every lookup table its own URL', async () => {
        await renderSidebarWithGroupsOpen('/admin');

        const hrefs = MASTER_DATA_SUB_MENU.map(([title]) =>
            screen.getByRole('link', { name: title }).getAttribute('href'),
        );

        expect(new Set(hrefs).size).toBe(hrefs.length);
    });

    it('is a disclosure and not a link', () => {
        // There is no /admin/master-data page, and a group pointing at a URL
        // that 404s is the failure this file exists to prevent.
        renderSidebar('/admin');

        expect(screen.queryByRole('link', { name: 'Master Data' })).toBeNull();
        expect(
            screen.getByRole('button', { name: 'Master Data' }),
        ).toHaveAttribute('aria-expanded', 'false');
    });

    it('opens itself when the admin is already on one of its tables', () => {
        // Checked against the children's URLs, not the parent's: a child URL is
        // not prefixed by the parent's, so `isCurrentOrParentUrl` on the group
        // alone would never match.
        renderSidebar('/admin/master-data/kategori-agenda');

        expect(
            screen.getByRole('button', { name: 'Master Data' }),
        ).toHaveAttribute('aria-expanded', 'true');
        expect(
            screen.getByRole('link', { name: 'Kategori Agenda' }),
        ).toBeVisible();
    });

    it('stays shut when the admin is somewhere else entirely', () => {
        renderSidebar('/admin/jadwal-misa/misa-rutin');

        expect(
            screen.getByRole('button', { name: 'Master Data' }),
        ).toHaveAttribute('aria-expanded', 'false');
    });

    it('does not appear under Jadwal Misa as well', async () => {
        // Both groups were once carrying "Kategori Berita". A sidebar that
        // renders the same label twice has two links with the same name, and
        // getByRole throws on that — which is how the duplicate was found.
        await renderSidebarWithGroupsOpen('/admin');

        expect(
            screen.getAllByRole('link', { name: 'Kategori Berita' }),
        ).toHaveLength(1);
        expect(
            screen.getAllByRole('link', { name: 'Kategori Agenda' }),
        ).toHaveLength(1);
        expect(screen.getAllByRole('link', { name: 'Lokasi' })).toHaveLength(1);
    });
});

describe('AppSidebar — Profil Paroki', () => {
    it.each(PROFIL_PAROKI_SUB_MENU)('links %s to %s', async (title, href) => {
        await renderSidebarWithGroupsOpen('/admin');

        expect(screen.getByRole('link', { name: title })).toHaveAttribute(
            'href',
            href,
        );
    });

    it('gives every child its own URL', async () => {
        await renderSidebarWithGroupsOpen('/admin');

        const hrefs = PROFIL_PAROKI_SUB_MENU.map(([title]) =>
            screen.getByRole('link', { name: title }).getAttribute('href'),
        );

        expect(new Set(hrefs).size).toBe(hrefs.length);
    });

    it('is a disclosure and not a link', () => {
        // There is no overview page; the module root redirects, and a group
        // pointing at a redirect is exactly the failure this file guards.
        renderSidebar('/admin');

        expect(
            screen.queryByRole('link', { name: 'Profil Paroki' }),
        ).toBeNull();
        expect(
            screen.getByRole('button', { name: 'Profil Paroki' }),
        ).toHaveAttribute('aria-expanded', 'false');
    });

    it('opens itself when the admin is already inside the module', () => {
        renderSidebar('/admin/profil/sejarah');

        expect(
            screen.getByRole('button', { name: 'Profil Paroki' }),
        ).toHaveAttribute('aria-expanded', 'true');
        expect(
            screen.getByRole('link', { name: 'Sejarah Paroki' }),
        ).toBeVisible();
    });

    it('stays shut when the admin is somewhere else entirely', () => {
        renderSidebar('/admin/berita/daftar');

        expect(
            screen.getByRole('button', { name: 'Profil Paroki' }),
        ).toHaveAttribute('aria-expanded', 'false');
    });
});

describe('AppSidebar — Berita and Agenda', () => {
    it('links both modules straight to their lists', () => {
        // Flat links, not groups with one child. A single-child group is a link
        // with two extra clicks, and /admin/berita redirects to /daftar anyway.
        renderSidebar('/admin');

        expect(screen.getByRole('link', { name: 'Berita' })).toHaveAttribute(
            'href',
            '/admin/berita/daftar',
        );
        expect(screen.getByRole('link', { name: 'Agenda' })).toHaveAttribute(
            'href',
            '/admin/agenda/daftar',
        );
    });
});

describe('AppSidebar — Pelayanan', () => {
    it('links straight to the service list', () => {
        // Phase 06B gave Pelayanan a module. It is a flat link, not a group: the
        // list is the destination the sidebar item should open, and there is no
        // overview page to sit between them (D-54).
        renderSidebar('/admin');

        expect(screen.getByRole('link', { name: 'Pelayanan' })).toHaveAttribute(
            'href',
            '/admin/pelayanan',
        );
        expect(screen.queryByRole('button', { name: 'Pelayanan' })).toBeNull();
    });
});

describe('AppSidebar — Komunitas', () => {
    it('links straight to the community list', () => {
        // Phase 07 gave Komunitas a module. It is a flat link, not a group: the
        // list is the destination the sidebar item should open (D-55 point 5).
        renderSidebar('/admin');

        expect(screen.getByRole('link', { name: 'Komunitas' })).toHaveAttribute(
            'href',
            '/admin/komunitas',
        );
        expect(screen.queryByRole('button', { name: 'Komunitas' })).toBeNull();
    });
});

describe('AppSidebar — Area', () => {
    it('is its own flat item, not a Master Data child', () => {
        // Phase 07 moved it out of Master Data, then out of Komunitas entirely
        // (D-57): the area hierarchy stands alone.
        renderSidebar('/admin');

        expect(screen.getByRole('link', { name: 'Area' })).toHaveAttribute(
            'href',
            '/admin/area',
        );
    });

    it('does not also appear under Master Data', async () => {
        await renderSidebarWithGroupsOpen('/admin');

        expect(screen.getAllByRole('link', { name: 'Area' })).toHaveLength(1);
    });
});

describe('AppSidebar — modules with no route yet', () => {
    it('does not link them', () => {
        renderSidebar('/admin');

        // Marked rather than linked, so nothing in this sidebar points at a URL
        // that would 404. Asserted through the absence of a link rather than
        // through the marker text: the marker is micro-legal styling, and a test
        // that fails when someone rewords it stops guarding the thing it was
        // written for.
        expect(screen.queryByRole('link', { name: 'Galeri' })).toBeNull();
        expect(screen.queryByRole('link', { name: 'Kontak' })).toBeNull();
    });
});
