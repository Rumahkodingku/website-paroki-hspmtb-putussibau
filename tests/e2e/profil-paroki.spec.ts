import { expect, test } from '@playwright/test';
import { resetLoginRateLimit, signIn } from './support/session';

/*
 * Profil Paroki — the admin module and its five public pages.
 *
 * The file follows the lesson from tests/e2e/master-data.spec.ts: assert the
 * href AND then click it, and check the URL plus something only the target page
 * renders. Asserting an href proves nothing about whether the link works.
 */

const PROFIL_PAROKI_SUB_MENU = [
    ['Informasi Profil', '/admin/profil/informasi'],
    ['Sejarah Paroki', '/admin/profil/sejarah'],
    ['Wilayah Pelayanan', '/admin/profil/wilayah'],
    ['Pastor Paroki', '/admin/profil/pastor'],
    ['Struktur Kepengurusan', '/admin/profil/struktur'],
] as const;

test.describe('sidebar Profil Paroki', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
        await page.goto('/admin');

        const toggle = page
            .getByRole('navigation', { name: 'Navigasi utama' })
            .getByRole('button', { name: 'Profil Paroki', exact: true });

        if ((await toggle.getAttribute('aria-expanded')) === 'false') {
            await toggle.click();
        }
    });

    test('setiap anak Profil Paroki punya href yang berbeda', async ({
        page,
    }) => {
        const sidebar = page.getByRole('navigation', {
            name: 'Navigasi utama',
        });

        const hrefs = await Promise.all(
            PROFIL_PAROKI_SUB_MENU.map(([title]) =>
                sidebar
                    .getByRole('link', { name: title, exact: true })
                    .getAttribute('href'),
            ),
        );

        expect(new Set(hrefs).size).toBe(hrefs.length);
    });

    test('setiap anak dapat diklik dan mendarat di halamannya', async ({
        page,
    }) => {
        for (const [title, url] of PROFIL_PAROKI_SUB_MENU) {
            await page
                .getByRole('navigation', { name: 'Navigasi utama' })
                .getByRole('link', { name: title, exact: true })
                .click();

            await expect(page).toHaveURL(new RegExp(`${url}$`));
            await expect(
                page.getByRole('heading', { name: title, level: 2 }),
            ).toBeVisible();
        }
    });
});

test.describe('alur profil', () => {
    test('menyimpan informasi profil dan menampilkannya di halaman publik', async ({
        page,
    }) => {
        resetLoginRateLimit();
        await signIn(page);

        await page.goto('/admin/profil/informasi');
        await page.getByLabel('Nama paroki').fill('Paroki Uji E2E');

        const publish = page.locator('#profile-published');
        if ((await publish.getAttribute('aria-checked')) !== 'true') {
            await publish.click();
        }

        await page
            .getByRole('button', { name: 'Simpan informasi profil' })
            .click();

        // The save redirects back to the same edit page.
        await expect(page).toHaveURL(/\/admin\/profil\/informasi$/);

        // Public page shows the published name.
        await page.goto('/profil');
        await expect(
            page
                .locator('#main')
                .getByRole('heading', { name: 'Paroki Uji E2E' }),
        ).toBeVisible();
    });
});

test.describe('halaman publik Profil Paroki', () => {
    const PUBLIC_PAGES: Array<[string, string]> = [
        ['/profil', 'Ringkasan'],
        ['/profil/sejarah', 'Sejarah'],
        ['/profil/wilayah', 'Wilayah Pelayanan'],
        ['/profil/pastor', 'Pastor'],
        ['/profil/struktur', 'Struktur Kepengurusan'],
    ];

    test('pengunjung dapat menavigasi kelimanya', async ({ page }) => {
        for (const [url, activeLabel] of PUBLIC_PAGES) {
            await page.goto(url);

            const subnav = page.getByRole('navigation', {
                name: 'Navigasi Profil Paroki',
            });

            await expect(subnav).toBeVisible();
            await expect(
                subnav.getByRole('link', { name: activeLabel, exact: true }),
            ).toHaveAttribute('aria-current', 'page');
        }
    });

    test('tidak ada tautan Visi & Misi di mana pun pada bagian profil', async ({
        page,
    }) => {
        for (const [url] of PUBLIC_PAGES) {
            await page.goto(url);
            await expect(page.getByRole('link', { name: /visi/i })).toHaveCount(
                0,
            );
        }
    });
});
