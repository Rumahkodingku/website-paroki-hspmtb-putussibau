import { expect, test } from '@playwright/test';
import { seedDocumentServiceLink } from './support/documents';
import { resetLoginRateLimit, signIn } from './support/session';

const PDF = Buffer.from(
    '%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n',
);

async function pilihStatus(
    page: import('@playwright/test').Page,
    label: string,
): Promise<void> {
    const status = page.getByLabel(/Status publikasi/);
    await status.click();

    const option = page.getByRole('option', { name: label, exact: true });
    await expect(option).toBeAttached();
    await option.focus();
    await page.keyboard.press('Enter');
    await expect(status).toContainText(label);
}

test.describe('Dokumen', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
    });

    test('membuat draf, menerbitkan, mengunduh, mengarsipkan, lalu menghapus', async ({
        page,
    }) => {
        const title = 'Pedoman E2E Phase 8B';

        await page.goto('/admin/dokumen/tambah');
        await page.getByLabel(/Judul dokumen/).fill(title);
        await page.getByLabel(/Kategori/).click();
        await page
            .getByRole('option', { name: 'Pedoman', exact: true })
            .click();
        await page.locator('#document-file').setInputFiles({
            name: 'pedoman-e2e.pdf',
            mimeType: 'application/pdf',
            buffer: PDF,
        });
        await page.getByRole('button', { name: 'Simpan Draf' }).click();

        await expect(page).toHaveURL(/\/admin\/dokumen\/\d+\/ubah$/);
        const id = Number(page.url().match(/dokumen\/(\d+)\/ubah/)?.[1]);

        expect(id).toBeGreaterThan(0);

        await page.goto('/dokumen');
        await expect(page.getByRole('heading', { name: title })).toHaveCount(0);

        const draftDownload = await page.request.get(`/dokumen/${id}/unduh`);
        expect(draftDownload.status()).toBe(404);

        await page.goto(`/admin/dokumen/${id}/ubah`);
        await pilihStatus(page, 'Terbit');

        const updateResponse = page.waitForResponse(
            (response) =>
                response.url().includes(`/admin/dokumen/${id}`) &&
                response.request().method() === 'POST',
        );
        await page.getByRole('button', { name: 'Simpan Perubahan' }).click();
        await updateResponse;

        await page.goto('/dokumen');
        await expect(page.getByRole('heading', { name: title })).toBeVisible();

        const downloadPromise = page.waitForEvent('download');
        await page
            .getByRole('link', { name: `Unduh dokumen ${title}` })
            .click();
        const download = await downloadPromise;

        expect(download.suggestedFilename()).toBe('pedoman-e2e-phase-8b.pdf');

        const preview = await page.request.get(`/dokumen/${id}/unduh?inline=1`);
        expect(preview.status()).toBe(200);
        expect(preview.headers()['content-type']).toContain('application/pdf');

        await page.goto(`/admin/dokumen/${id}/ubah`);
        await pilihStatus(page, 'Arsip');

        const archiveResponse = page.waitForResponse(
            (response) =>
                response.url().includes(`/admin/dokumen/${id}`) &&
                response.request().method() === 'POST',
        );
        await page.getByRole('button', { name: 'Simpan Perubahan' }).click();
        await archiveResponse;

        await page.goto('/dokumen');
        await expect(page.getByRole('heading', { name: title })).toHaveCount(0);
        expect((await page.request.get(`/dokumen/${id}/unduh`)).status()).toBe(
            404,
        );

        await page.goto('/admin/dokumen');
        const row = page.getByRole('row', { name: new RegExp(title) });
        await row.getByRole('button', { name: 'Hapus' }).click();
        await page
            .getByRole('dialog')
            .getByRole('button', { name: 'Hapus' })
            .click();

        await expect(row).toHaveCount(0);
    });

    test('menautkan dokumen ke layanan dan menampilkannya di publik', async ({
        page,
    }) => {
        seedDocumentServiceLink();

        await page.goto('/admin/pelayanan');
        const row = page.getByRole('row', {
            name: /Layanan E2E Dokumen/,
        });
        await row.getByRole('link', { name: 'Ubah' }).click();

        await page.getByRole('tab', { name: 'Dokumen Terkait' }).click();
        await page
            .getByRole('checkbox', { name: 'Dokumen E2E Layanan' })
            .check();
        await page.getByRole('button', { name: 'Simpan Perubahan' }).click();

        await page.goto('/pelayanan/layanan-e2e-dokumen');
        await expect(
            page.getByRole('heading', { name: 'Dokumen Terkait' }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', {
                name: 'Unduh dokumen Dokumen E2E Layanan',
            }),
        ).toBeVisible();
    });

    test('mengalihkan URL download lama ke Dokumen', async ({ page }) => {
        await page.goto('/download');
        await expect(page).toHaveURL(/\/dokumen$/);

        await page.goto('/admin/download');
        await expect(page).toHaveURL(/\/admin\/dokumen$/);
    });
});
