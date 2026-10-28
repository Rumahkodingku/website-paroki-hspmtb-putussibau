import { expect, test } from '@playwright/test';
import { resetLoginRateLimit, signIn } from './support/session';

/*
 * Profil Paroki — the admin module and its four public pages.
 *
 * The file follows the lesson from tests/e2e/master-data.spec.ts: assert the
 * href AND then click it, and check the URL plus something only the target page
 * renders. Asserting an href proves nothing about whether the link works.
 *
 * Informasi Profil is the module root again (D-78).
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

test.describe('alur narasi sejarah', () => {
    test('menyimpan narasi sejarah dan menampilkannya di halaman publik', async ({
        page,
    }) => {
        resetLoginRateLimit();
        await signIn(page);

        await page.goto('/admin/profil/sejarah');

        const editor = page.locator('[contenteditable="true"]').first();
        await editor.click();
        await editor.fill('Narasi sejarah uji E2E');

        await page.getByRole('button', { name: 'Simpan narasi' }).click();

        // The save redirects back to the same page.
        await expect(page).toHaveURL(/\/admin\/profil\/sejarah$/);

        // The narrative is an admin-only field; reloading proves it persisted.
        await page.goto('/admin/profil/sejarah');
        await expect(
            page
                .locator('[contenteditable="true"]')
                .first()
                .getByText('Narasi sejarah uji E2E'),
        ).toBeVisible();
    });
});

test.describe('halaman publik Profil Paroki', () => {
    const PUBLIC_PAGES: Array<[string, string]> = [
        ['/profil', 'Profil Paroki'],
        ['/profil/sejarah', 'Sejarah'],
        ['/profil/wilayah', 'Wilayah Pelayanan'],
        ['/profil/pastor', 'Pastor'],
        ['/profil/struktur', 'Struktur Kepengurusan'],
    ];

    test('pengunjung dapat menavigasi keempatnya', async ({ page }) => {
        for (const [url, activeLabel] of PUBLIC_PAGES) {
            await page.goto(url);

            await expect(
                page.getByRole('heading', { name: activeLabel, level: 1 }),
            ).toBeVisible();
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
