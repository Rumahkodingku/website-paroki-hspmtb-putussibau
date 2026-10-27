import { expect, test } from '@playwright/test';
import { resetLoginRateLimit, signIn } from './support/session';

test.describe('audit log', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
    });

    test('halaman audit log memuat daftar dan modal filter', async ({ page }) => {
        await page.goto('/admin/audit-log');

        await expect(
            page.getByRole('heading', { name: 'Audit Log', level: 2 }),
        ).toBeVisible();

        await page.getByRole('button', { name: 'Filter' }).click();

        const dialog = page.getByRole('dialog', {
            name: 'Filter Audit Log',
        });

        await expect(dialog).toBeVisible();
        await expect(dialog.getByLabel('Filter pengguna')).toBeVisible();
        await expect(dialog.getByLabel('Filter modul')).toBeVisible();
        await expect(dialog.getByLabel('Filter aktivitas')).toBeVisible();
        await expect(dialog.getByLabel('Filter hasil')).toBeVisible();
        await expect(dialog.getByText('Dari tanggal')).toBeVisible();
        await expect(dialog.getByText('Sampai tanggal')).toBeVisible();
    });

    test('modal filter tertutup saat Terapkan ditekan', async ({ page }) => {
        await page.goto('/admin/audit-log');

        await page.getByRole('button', { name: 'Filter' }).click();

        const dialog = page.getByRole('dialog', {
            name: 'Filter Audit Log',
        });

        await Promise.all([
            page.waitForURL(/page=1/),
            dialog.getByRole('button', { name: 'Terapkan' }).click(),
        ]).catch(() => undefined);

        await expect(dialog).toBeHidden();
    });

    test('modal filter tertutup saat Batal ditekan', async ({ page }) => {
        await page.goto('/admin/audit-log');

        await page.getByRole('button', { name: 'Filter' }).click();

        const dialog = page.getByRole('dialog', {
            name: 'Filter Audit Log',
        });

        await dialog.getByRole('button', { name: 'Batal' }).click();

        await expect(dialog).toBeHidden();
    });

    test('klik header Waktu mengurutkan lewat query string', async ({ page }) => {
        await page.goto('/admin/audit-log');

        await page
            .getByRole('button', { name: 'Urutkan berdasarkan Waktu' })
            .click();

        await expect(page).toHaveURL(/sort=occurred_at/);
        await expect(page).toHaveURL(/direction=asc/);

        await page
            .getByRole('button', { name: 'Urutkan berdasarkan Waktu' })
            .click();

        await expect(page).toHaveURL(/direction=desc/);
    });

    test('menu Audit Log tampil untuk Super Admin', async ({ page }) => {
        await page.goto('/admin');

        await expect(
            page.getByRole('link', { name: 'Audit Log' }).first(),
        ).toBeVisible();
    });

    test('tamu tidak dapat membuka audit log', async ({ page, context }) => {
        await context.clearCookies();

        await page.goto('/admin/audit-log');

        await expect(page).toHaveURL(/\/admin\/login$/);
    });
});
