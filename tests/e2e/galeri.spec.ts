import { expect, test } from '@playwright/test';
import { seedGalleryAlbum } from './support/gallery';
import { resetLoginRateLimit, signIn } from './support/session';

/*
 * Galeri — the admin album CRUD and the public directory and detail.
 *
 * The E2E database has no workers, so a file uploaded in the browser never
 * finishes processing and can never become a gallery photo. Where a photo has to
 * exist, it is planted directly (support/gallery.ts); the upload path itself is
 * covered by the media unit tests and the editor spec. Everything else — the
 * draft/private rule, the lightbox, deletion with confirmation — is exercised
 * through the real interface.
 */

test.describe('sidebar Galeri', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
        await page.goto('/admin');
    });

    test('menautkan langsung ke daftar galeri', async ({ page }) => {
        const sidebar = page.getByRole('navigation', {
            name: 'Navigasi utama',
        });

        await expect(
            sidebar.getByRole('link', { name: 'Galeri', exact: true }),
        ).toHaveAttribute('href', '/admin/galeri');
    });

    test('klik Galeri membuka daftar album', async ({ page }) => {
        await page
            .getByRole('navigation', { name: 'Navigasi utama' })
            .getByRole('link', { name: 'Galeri', exact: true })
            .click();

        await expect(page).toHaveURL(/\/admin\/galeri$/);
        await expect(
            page.getByRole('heading', { name: 'Galeri' }),
        ).toBeVisible();
    });
});

test.describe('alur album', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
    });

    test('membuat album draf, menahannya dari publik, lalu menghapusnya', async ({
        page,
    }) => {
        await page.goto('/admin/galeri/tambah');
        await page.locator('#gallery-title').fill('Album Draf E2E');
        await page.locator('#gallery-slug').fill('album-draf-e2e');
        await page.locator('#gallery-event-date').fill('2025-03-01');
        await page.getByRole('button', { name: 'Simpan album' }).click();

        // A new album lands on its photo manager, ready for photos.
        await expect(page).toHaveURL(/\/admin\/galeri\/\d+\/foto$/);
        await expect(
            page.getByRole('heading', { name: 'Album Draf E2E' }),
        ).toBeVisible();

        // A draft never reaches the directory...
        await page.goto('/galeri');
        await expect(
            page.getByRole('heading', { name: 'Album Draf E2E' }),
        ).toHaveCount(0);

        // ...and its own URL 404s rather than showing it.
        const response = await page.goto('/galeri/album-draf-e2e');
        expect(response?.status()).toBe(404);

        // Delete it with the confirmation dialog.
        await page.goto('/admin/galeri');
        const row = page.getByRole('row', { name: /Album Draf E2E/ });
        await row.getByRole('button', { name: 'Hapus' }).click();
        await page
            .getByRole('dialog')
            .getByRole('button', { name: 'Hapus', exact: true })
            .click();

        await expect(page.getByText('Album Draf E2E')).toHaveCount(0);
    });

    test('menampilkan album terbit dan lightbox-nya', async ({ page }) => {
        seedGalleryAlbum();

        await page.goto('/galeri');
        await expect(
            page.getByRole('heading', { name: 'Album E2E' }),
        ).toBeVisible();

        await page.getByRole('link', { name: /Lihat album Album E2E/ }).click();
        await expect(page).toHaveURL(/\/galeri\/album-e2e$/);
        await expect(
            page.getByRole('heading', { name: 'Album E2E' }),
        ).toBeVisible();

        // Open the first photo; the caption names the button.
        await page
            .getByRole('button', { name: 'Perbesar foto: Foto Pertama' })
            .click();

        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
        await expect(dialog.getByText('Foto 1 dari 2')).toBeVisible();

        // Arrow keys move to the next photo.
        await page.keyboard.press('ArrowRight');
        await expect(dialog.getByText('Foto 2 dari 2')).toBeVisible();

        // Escape closes it.
        await page.keyboard.press('Escape');
        await expect(dialog).toBeHidden();
    });
});
