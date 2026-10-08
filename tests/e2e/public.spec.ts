import { expect, test } from '@playwright/test';

/**
 * The public side of the site.
 *
 * There is very little here yet on purpose: the public website is still a shell
 * with [ISI: ...] placeholders (Phase 01 has no public pages beyond `/`, see
 * docs/DECISIONS.md D-27). What is worth pinning down now is the shell itself:
 * that `/` renders through Inertia with a built asset bundle, and that an
 * unknown URL produces the Indonesian error page rather than a blank screen.
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
});
