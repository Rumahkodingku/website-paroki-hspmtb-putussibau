import { expect, test, type Page } from '@playwright/test';
import { loginButton } from './support/session';

/**
 * The public side of the site.
 *
 * Phase 01 had almost nothing here on purpose: the public website was a shell
 * with [ISI: ...] placeholders and one route. Phase 03 gave it the full set of
 * PRD 5.1 routes and a real navigation, so this file now has something to prove.
 *
 * Two things are deliberately NOT asserted here.
 *
 * Copy. The [ISI: ...] placeholders are going to be replaced by parish content
 * one module at a time, and a spec that quotes them would fail on that day for
 * no useful reason. What is asserted instead is structure: that a link exists,
 * points somewhere, and that the shell survives the navigation.
 *
 * Horizontal overflow at every viewport. Checked at 360px below, because that is
 * the viewport PRD NFR-RESP names and the one where a ten-item navigation and a
 * six-column footer actually fail. Checking ten widths would multiply the run
 * time to prove the same CSS.
 */
test.describe('situs publik', () => {
    test('halaman depan dapat diakses tanpa masuk', async ({ page }) => {
        const response = await page.goto('/');

        expect(response?.status()).toBe(200);
        await expect(page).toHaveTitle(/Selamat Datang/);
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    });

    test('frontend benar-benar dimuat, bukan halaman tanpa aset', async ({
        page,
    }) => {
        const failed: string[] = [];

        page.on('response', (response) => {
            if (response.status() >= 400) {
                failed.push(`${response.status()} ${response.url()}`);
            }
        });

        await page.goto('/');

        // A missing Vite manifest or a stale asset URL shows up here as a 404 on
        // a stylesheet, and the page still renders its markup, so asserting on
        // the heading alone would call a broken page healthy.
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
        expect(failed).toEqual([]);
    });

    test('URL yang tidak dikenal menampilkan halaman error 404', async ({
        page,
    }) => {
        const response = await page.goto('/halaman-yang-tidak-ada');

        expect(response?.status()).toBe(404);
        await expect(page.getByText('404')).toBeVisible();
    });

    /**
     * PRD XC-E2 asks for 404 and 500 in Bahasa Indonesia inside the public
     * layout, and until the browser suite existed that copy was only ever checked
     * as a prop reaching React — which says nothing about whether it reached the
     * screen. Asserting the wording here is what closes the gap recorded in
     * docs/DECISIONS.md D-26 §7.
     */
    test('halaman 404 menampilkan copywriting Bahasa Indonesia', async ({
        page,
    }) => {
        await page.goto('/halaman-yang-tidak-ada');

        await expect(page.getByText('Halaman Tidak Ditemukan')).toBeVisible();
        await expect(
            page.getByText(
                'Alamat yang Anda buka tidak tersedia. Mungkin halaman tersebut dipindahkan, atau memang tidak pernah ada.',
            ),
        ).toBeVisible();
    });

    test('tidak ada pendaftaran publik', async ({ page }) => {
        const response = await page.goto('/register');

        // PRD AUTH-R2 forbids public registration (see D-19). This pins that
        // down at the browser level rather than trusting the route list.
        expect(response?.status()).toBe(404);
    });

    /**
     * Roadmap section 17, first critical flow.
     *
     * The assertion that matters is the last one. Inertia's persistent layout
     * means PublicLayout stays mounted across navigations, so the navbar and
     * footer must still be there after the swap - and a layout that remounts per
     * page would still pass a test that only checked the URL.
     */
    test('menu utama di ponsel membuka, menavigasi, dan mempertahankan shell', async ({
        page,
    }) => {
        await page.setViewportSize({ width: 360, height: 780 });
        await page.goto('/');

        await page.getByRole('button', { name: 'Buka navigasi' }).click();

        const drawer = page.getByRole('dialog');
        await expect(drawer).toBeVisible();

        await drawer.getByRole('link', { name: 'Profil' }).click();

        await expect(page).toHaveURL(/\/profil$/);

        // The drawer closed rather than covering the page it just navigated to.
        await expect(page.getByRole('dialog')).toHaveCount(0);

        // The persistent shell survived the navigation.
        await expect(page.getByRole('banner')).toBeVisible();
        await expect(page.getByRole('contentinfo')).toBeVisible();
    });

    /**
     * Roadmap section 17, second critical flow, and the one Phase 01 could not
     * run: Kontak did not exist as a route, so the footer's Kontak entry was a
     * placeholder rather than a link.
     */
    test('footer menavigasi ke halaman kontak', async ({ page }) => {
        await page.goto('/');

        await page
            .getByRole('contentinfo')
            .getByRole('link', { name: 'Halaman kontak' })
            .click();

        await expect(page).toHaveURL(/\/kontak$/);
        await expect(page.getByRole('banner')).toBeVisible();
    });

    test('setiap entri navigasi utama mengarah ke halaman yang ada', async ({
        page,
    }) => {
        // Checked through the drawer, at a width where the drawer is the only
        // navigation. The desktop bar is display:none below lg, and Playwright
        // will happily click a hidden link - which would test something no
        // visitor can do.
        await page.setViewportSize({ width: 360, height: 780 });
        await page.goto('/');

        const menu = [
            'Profil',
            'Jadwal Misa',
            'Berita & Artikel',
            'Agenda',
            'Pelayanan',
            'Komunitas',
            'Galeri',
            'Download',
            'Kontak',
        ];

        // The drawer is opened once and left open. Re-opening it per item would
        // need a close in between, and a modal that has not been dismissed
        // covers its own trigger - so the second click would time out waiting for
        // a button the visitor cannot reach either.
        await page.getByRole('button', { name: 'Buka navigasi' }).click();

        const drawer = page.getByRole('dialog');
        await expect(drawer).toBeVisible();

        for (const label of menu) {
            const link = drawer.getByRole('link', { name: label, exact: true });

            await expect(link).toBeVisible();

            const href = await link.getAttribute('href');
            expect(href).toBeTruthy();

            const response = await page.request.get(href ?? '');
            expect(response.status(), `${label} -> ${href}`).toBe(200);
        }
    });

    /**
     * PRD 6.8 and NFR-RESP: 44px minimum touch target. Asserted on the measured
     * box rather than on a class, because the class is an intention and the box
     * is the fact.
     */
    test('target sentuh minimal 44x44 piksel', async ({ page }) => {
        await page.setViewportSize({ width: 360, height: 780 });
        await page.goto('/');

        const trigger = page.getByRole('button', { name: 'Buka navigasi' });
        const box = await trigger.boundingBox();

        expect(box).not.toBeNull();
        expect(box?.width ?? 0).toBeGreaterThanOrEqual(44);
        expect(box?.height ?? 0).toBeGreaterThanOrEqual(44);
    });

    /**
     * NFR-RESP: no horizontal scroll. A ten-item navigation and a six-column
     * footer are the two places this fails, and on a phone it fails as a page
     * that can be panned sideways, which is disorienting rather than obviously
     * broken - so nothing else in the suite would catch it.
     */
    test('tidak ada scroll horizontal pada 360px', async ({ page }) => {
        await page.setViewportSize({ width: 360, height: 780 });

        for (const url of [
            '/',
            '/profil',
            '/jadwal-misa',
            '/kontak',
            '/design-system',
        ]) {
            await page.goto(url);

            const overflow = await page.evaluate(
                () =>
                    document.documentElement.scrollWidth -
                    document.documentElement.clientWidth,
            );

            expect(
                overflow,
                `horizontal overflow at ${url}`,
            ).toBeLessThanOrEqual(0);
        }
    });

    /**
     * The auth pages live under the same `public/` audience prefix as the public
     * site, so app.tsx has to tell them apart. Getting that order wrong puts the
     * parish navbar on the login page - which advertises the whole site to
     * somebody who has not signed in, on the one page whose job is to be simple.
     */
    test('halaman masuk tidak memakai navigasi publik', async ({ page }) => {
        await page.goto('/admin/login');

        await expect(loginButton(page)).toBeVisible();
        await expect(
            page.getByRole('navigation', { name: 'Navigasi utama' }),
        ).toHaveCount(0);
        await expect(page.getByRole('contentinfo')).toHaveCount(0);
    });
});

/**
 * The public light/dark switch.
 *
 * Separate describe block because these specs change the visitor's stored
 * preference, and the cookie outlives a page. The beforeEach clears it so a dark
 * preference left by one case cannot leak into the next and make a light-mode
 * assertion pass for the wrong reason.
 */
test.describe('tema publik', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/');
        await page.evaluate(() => {
            localStorage.removeItem('appearance');
            document.cookie = 'appearance=; max-age=0; path=/';
        });
    });

    const toggle = (page: Page) =>
        page.getByRole('button', { name: 'Mode gelap' });

    test('beranda mulai dalam mode terang', async ({ page }) => {
        await expect(page.locator('html')).not.toHaveClass(/dark/);
        await expect(toggle(page)).toHaveAttribute('aria-pressed', 'false');
    });

    test('tombol mengubah tema dan menandai statusnya', async ({ page }) => {
        await toggle(page).click();

        await expect(page.locator('html')).toHaveClass(/dark/);
        await expect(toggle(page)).toHaveAttribute('aria-pressed', 'true');

        await toggle(page).click();

        await expect(page.locator('html')).not.toHaveClass(/dark/);
        await expect(toggle(page)).toHaveAttribute('aria-pressed', 'false');
    });

    /**
     * The part a client-side-only assertion would miss. The cookie is what
     * HandleAppearance reads and what app.blade.php turns into the class on
     * <html> before any JavaScript runs, so without it the choice would survive
     * an Inertia navigation and vanish on the next full page load.
     */
    test('pilihan bertahan setelah halaman dimuat ulang', async ({ page }) => {
        await toggle(page).click();
        await expect(page.locator('html')).toHaveClass(/dark/);

        await page.reload();

        await expect(page.locator('html')).toHaveClass(/dark/);
        await expect(toggle(page)).toHaveAttribute('aria-pressed', 'true');
    });

    test('pilihan bertahan saat berpindah halaman', async ({ page }) => {
        await toggle(page).click();

        await page
            .getByRole('contentinfo')
            .getByRole('link', { name: 'Halaman kontak' })
            .click();
        await expect(page).toHaveURL(/\/kontak$/);
        await expect(page.locator('html')).toHaveClass(/dark/);
    });

    /**
     * DESIGN.md: "Theme switching MUST be instant and MUST NOT cause layout
     * shifts." The measurable part is that nothing moves - the header stays
     * exactly where it was and the page does not jump.
     */
    test('berganti tema tidak menggeser tata letak', async ({ page }) => {
        await page.setViewportSize({ width: 360, height: 780 });
        await page.goto('/');

        const header = page.getByRole('banner');
        const before = await header.boundingBox();
        const scrollBefore = await page.evaluate(() => window.scrollY);

        await toggle(page).click();
        await expect(page.locator('html')).toHaveClass(/dark/);

        const after = await header.boundingBox();

        expect(after?.y).toBe(before?.y);
        expect(after?.height).toBe(before?.height);
        expect(await page.evaluate(() => window.scrollY)).toBe(scrollBefore);
    });

    test('tema gelap tidak menimbulkan scroll horizontal', async ({ page }) => {
        await page.setViewportSize({ width: 360, height: 780 });
        await page.goto('/');

        for (const url of ['/', '/profil', '/kontak', '/design-system']) {
            await page.goto(url);
            await toggle(page).click();

            const overflow = await page.evaluate(
                () =>
                    document.documentElement.scrollWidth -
                    document.documentElement.clientWidth,
            );

            expect(overflow, `dark overflow at ${url}`).toBeLessThanOrEqual(0);

            await toggle(page).click();
        }
    });

    /**
     * The visible proof that dark mode is not the saturated navy DESIGN.md
     * v1.1 retired. A navy canvas would still be a strongly blue pixel; a
     * charcoal one has almost no channel spread.
     */
    test('mode gelap memakai kanvas netral, bukan navy', async ({ page }) => {
        await page.goto('/');
        await toggle(page).click();
        await expect(page.locator('html')).toHaveClass(/dark/);

        const background = await page.evaluate(
            () => getComputedStyle(document.body).backgroundColor,
        );

        const channels = (background.match(/\d+/g) ?? [])
            .slice(0, 3)
            .map(Number);

        // DESIGN.md dark-canvas is #171A1F: red and green sit close together and
        // blue is only a little higher. Navy #01266D would put blue far above
        // both, and would be a default navy rather than this near-black.
        expect(channels).toHaveLength(3);
        expect(
            Math.max(...channels) - Math.min(...channels),
        ).toBeLessThanOrEqual(20);
        expect(channels[0]).toBeLessThan(60);
    });

    test('tombol temanya punya target sentuh 44x44', async ({ page }) => {
        await page.setViewportSize({ width: 360, height: 780 });
        await page.goto('/');

        const box = await toggle(page).boundingBox();

        expect(box?.width ?? 0).toBeGreaterThanOrEqual(44);
        expect(box?.height ?? 0).toBeGreaterThanOrEqual(44);
    });
});
