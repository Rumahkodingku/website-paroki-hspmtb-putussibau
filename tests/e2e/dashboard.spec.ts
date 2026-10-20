import { expect, test } from '@playwright/test';
import { resetLoginRateLimit, signIn } from './support/session';

/*
 * The admin dashboard, in the browser.
 *
 * The Pest suite proves the numbers; this file proves the page an admin
 * actually reaches. It runs against the bare E2E database, so most panels are
 * empty by design and the empty states are part of what is asserted — a fresh
 * installation must not open on an error.
 */

test.describe('dashboard admin', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
    });

    test('menampilkan ringkasan dan panel utama', async ({ page }) => {
        await expect(page).toHaveURL(/\/admin$/);

        const main = page.getByRole('main');

        await expect(
            main.getByRole('heading', { name: /Selamat datang/ }),
        ).toBeVisible();

        for (const panel of [
            'Aksi Cepat',
            'Agenda Terdekat',
            'Berita Terbaru',
            'Perlu Perhatian',
            'Konten Terbaru',
        ]) {
            await expect(
                main.getByRole('heading', { name: panel, exact: true }),
            ).toBeVisible();
        }

        // The five summary tiles, by their labels. Scoped to the summary grid:
        // "Berita" and "Dokumen" are also type labels in Konten Terbaru once
        // the database has content.
        const summary = page.getByTestId('dashboard-summary');

        for (const card of [
            'Berita',
            'Agenda mendatang',
            'Dokumen',
            'Misa rutin aktif',
            'Pelayanan aktif',
        ]) {
            await expect(
                summary.getByText(card, { exact: true }),
            ).toBeVisible();
        }
    });

    test('database kosong tetap menampilkan empty state, bukan galat', async ({
        page,
    }) => {
        const main = page.getByRole('main');

        await expect(
            main.getByText('Belum ada agenda mendatang'),
        ).toBeVisible();
        await expect(main.getByText('Belum ada berita terbit')).toBeVisible();
        await expect(
            main.getByText('Tidak ada yang perlu ditindaklanjuti'),
        ).toBeVisible();
    });

    test('aksi cepat membuka halaman yang benar', async ({ page }) => {
        // Scoped to the quick actions: "Tulis Berita" also appears in the
        // Berita Terbaru empty state, so the description is what tells them
        // apart.
        await page.getByRole('link', { name: /Tulis Berita Artikel/ }).click();

        await expect(page).toHaveURL(/\/admin\/berita\/tambah$/);
        await expect(
            page.getByRole('heading', { name: 'Tulis Artikel', exact: true }),
        ).toBeVisible();
    });

    test('layout tidak overflow horizontal di semua viewport target', async ({
        page,
    }) => {
        for (const width of [360, 390, 768, 1024, 1440]) {
            await page.setViewportSize({ width, height: 900 });
            await page.goto('/admin');

            const overflow = await page.evaluate(
                () =>
                    document.documentElement.scrollWidth -
                    document.documentElement.clientWidth,
            );

            expect(
                overflow,
                `overflow horizontal di ${width}px`,
            ).toBeLessThanOrEqual(1);
        }
    });
});

test('tamu tidak dapat membuka dashboard', async ({ page }) => {
    await page.goto('/admin');

    await expect(page).toHaveURL(/\/admin\/login$/);
    await expect(page.getByTestId('login-button')).toBeVisible();
});
