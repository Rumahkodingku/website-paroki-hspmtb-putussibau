import { expect, test } from '@playwright/test';
import { resetLoginRateLimit, signIn } from './support/session';

/*
 * The rich text editor and the image picker, in a browser.
 *
 * Two defects, both invisible to the backend tests:
 *
 * 1. Tiptap v3's useEditor stopped re-rendering on every transaction. The
 *    toolbar read editor.isActive() during render, so it computed its state
 *    once and froze — the text changed but no button ever lit up. A unit test
 *    covers the selector; this covers the click a real admin performs.
 *
 * 2. The image preview only appeared once the queued job had finished, which
 *    with the database queue and no worker never happens here. The local object
 *    URL is the fix, and `blob:` is the proof it was shown before the server
 *    answered.
 *
 * Both run against /admin/berita/tambah, which renders one editor and one
 * picker.
 */

/** A 1x1 transparent PNG, enough for the picker's preview and the upload. */
const PNG = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
    'base64',
);

test.describe('editor and picker', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
        await page.goto('/admin/berita/tambah');
    });

    test('menyalakan tombol toolbar saat format dipilih', async ({ page }) => {
        const editor = page.locator('.rich-text .ProseMirror');
        await expect(editor).toBeVisible();

        const bold = page.getByRole('button', { name: 'Tebal' });
        await expect(bold).toHaveAttribute('aria-pressed', 'false');

        await bold.click();
        await expect(bold).toHaveAttribute('aria-pressed', 'true');

        await editor.click();
        await editor.pressSequentially('Tebal');
        await expect(editor.locator('strong')).toHaveText('Tebal');
    });

    test('menampilkan pratinjau gambar segera setelah berkas dipilih', async ({
        page,
    }) => {
        const preview = page.locator('[data-test="media-preview"] img');

        await expect(preview).toHaveCount(0);

        await page.locator('input[type="file"]').setInputFiles({
            name: 'gereja.png',
            mimeType: 'image/png',
            buffer: PNG,
        });

        await expect(preview).toBeVisible();
        await expect(preview).toHaveAttribute('src', /^blob:/);
    });

    test('membuat heading lebih besar daripada teks body', async ({ page }) => {
        // The heading steps were all body-strong (17px) once, so picking H2
        // changed the tag but nothing on screen. Comparing the computed size
        // against the body text is what locks the hierarchy.
        const editor = page.locator('.rich-text .ProseMirror');
        await expect(editor).toBeVisible();

        await editor.click();
        await editor.pressSequentially('Judul bagian');

        await page.getByRole('button', { name: 'Subjudul 2' }).click();

        const heading = editor.locator('h2');
        await expect(heading).toHaveText('Judul bagian');

        const headingSize = await heading.evaluate((element) =>
            parseFloat(getComputedStyle(element).fontSize),
        );
        const bodySize = await page
            .locator('.rich-text')
            .evaluate((element) =>
                parseFloat(getComputedStyle(element).fontSize),
            );

        expect(headingSize).toBeGreaterThan(bodySize);
    });

    test('menampilkan tooltip saat tombol toolbar diarahkan', async ({
        page,
    }) => {
        const bold = page.getByRole('button', { name: 'Tebal' });
        await bold.hover();

        await expect(page.getByRole('tooltip')).toHaveText('Tebal');
    });

    test('menu tabel tetap terbuka meski tombolnya ber-tooltip', async ({
        page,
    }) => {
        // The table button composes TooltipTrigger and DropdownMenuTrigger on
        // the same element. If that composition breaks, the menu silently stops
        // opening, so the click is asserted rather than only the tooltip.
        await page.getByRole('button', { name: 'Tabel' }).click();

        await expect(
            page.getByRole('menuitem', { name: 'Sisipkan tabel' }),
        ).toBeVisible();
    });
});
