import { expect, test } from '@playwright/test';
import { resetLoginRateLimit, signIn } from './support/session';

test.describe('Backup & Pemulihan', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
    });

    test('membuat, mengunduh, dan menghapus backup database', async ({
        page,
    }) => {
        await page.goto('/admin/backup');

        await expect(
            page.getByRole('heading', { name: 'Backup & Pemulihan' }),
        ).toBeVisible();

        await page.getByRole('button', { name: 'Buat backup' }).click();

        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
        await dialog.getByRole('button', { name: 'Buat backup' }).click();

        await expect(page).toHaveURL(/\/admin\/backup\/\d+$/);
        await expect(page.getByText('Berhasil').first()).toBeVisible();

        const downloadPromise = page.waitForEvent('download');
        await page.getByRole('link', { name: 'Unduh' }).click();
        const download = await downloadPromise;

        expect(download.suggestedFilename()).toMatch(/\.sql\.gz$/);

        await page.goto('/admin/backup');
        const row = page.getByRole('row', { name: /Database/ }).first();
        await row.getByRole('button', { name: 'Hapus backup' }).click();

        await page
            .getByRole('dialog')
            .getByRole('button', { name: 'Hapus' })
            .click();

        await expect(page.getByText('Backup berhasil dihapus.')).toBeVisible();
        await expect(page.getByRole('dialog')).toHaveCount(0);
        await expect(
            page.getByText('Belum ada backup', { exact: true }),
        ).toBeVisible();
    });

    test('menolak unggahan yang bukan arsip backup', async ({ page }) => {
        await page.goto('/admin/backup');
        await page.getByRole('button', { name: 'Unggah' }).click();

        const dialog = page.getByRole('dialog');
        await dialog.locator('input[type="file"]').setInputFiles({
            name: 'backup.zip',
            mimeType: 'application/zip',
            buffer: Buffer.from('bukan zip'),
        });
        await dialog
            .getByRole('button', { name: 'Unggah dan validasi' })
            .click();

        await expect(
            page.getByText(/tidak didukung|tidak valid|harus berupa/).first(),
        ).toBeVisible();
    });

    test('menyimpan pengaturan backup', async ({ page }) => {
        await page.goto('/admin/backup/pengaturan');

        await expect(
            page.getByRole('heading', { name: 'Pengaturan Backup' }),
        ).toBeVisible();

        const retention = page.getByLabel('Retensi backup database');
        await retention.fill('21');
        await page.getByRole('button', { name: 'Simpan pengaturan' }).click();

        await expect(
            page.getByText('Pengaturan backup berhasil disimpan.'),
        ).toBeVisible();
    });

    test('membuka dialog persiapan pemulihan tanpa mengubah data', async ({
        page,
    }) => {
        await page.goto('/admin/backup');

        if ((await page.getByText('Belum ada backup').count()) > 0) {
            await page.getByRole('button', { name: 'Buat backup' }).click();
            await page
                .getByRole('dialog')
                .getByRole('button', { name: 'Buat backup' })
                .click();
            await expect(page).toHaveURL(/\/admin\/backup\/\d+$/);
        } else {
            await page
                .getByRole('link', { name: 'Lihat detail backup' })
                .first()
                .click();
        }

        await page
            .getByRole('button', { name: 'Siapkan Pemulihan' })
            .click();

        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
        await expect(dialog.getByText('Safety backup wajib berhasil')).toBeVisible();
        await dialog.getByRole('button', { name: 'Batal' }).click();
    });
});
