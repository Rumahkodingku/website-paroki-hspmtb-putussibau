import { expect, test, type Page } from '@playwright/test';
import { resetLoginRateLimit, signIn } from './support/session';

/*
 * The rich text editor and the featured image uploader, in a browser.
 *
 * Three defects, all invisible to the backend tests:
 *
 * 1. Tiptap v3's useEditor stopped re-rendering on every transaction. The
 *    toolbar read editor.isActive() during render, so it computed its state
 *    once and froze — the text changed but no button ever lit up. A unit test
 *    covers the selector; this covers the click a real admin performs.
 *
 * 2. The image preview only appeared once the queued job had finished, which
 *    with the database queue and no worker never happens here. The preview is
 *    built in the browser, and `blob:` is the proof it was shown before the
 *    server answered anything.
 *
 * 3. The uploader shipped pointing at `/api/upload`, a route this application
 *    does not have. Nothing on the page looks broken: the dropzone, the crop
 *    dialog and the progress bar all work perfectly around a 404. Which is why
 *    "the crop dialog opens" and "the file is queued" are two assertions rather
 *    than one, and why the adapter's endpoint is pinned in
 *    tests/js/lib/media-upload-adapter.test.ts.
 *
 * Both run against /admin/berita/tambah, which renders one editor and one
 * uploader.
 */

/** A 1x1 transparent PNG, enough for the picker's preview and the upload. */
const PNG = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
    'base64',
);

/**
 * Hand a file to the featured image uploader.
 *
 * Scoped to the uploader rather than to `input[type="file"]`, which is what the
 * spec used before the swap. The rich text editor's insert-image dialog has an
 * input of its own, and a bare selector across the page stops being unambiguous
 * the moment two of them are mounted at once.
 */
async function pilihGambar(page: Page): Promise<void> {
    await page
        .locator('[data-slot="upload"] input[type="file"]')
        .setInputFiles({
            name: 'gereja.png',
            mimeType: 'image/png',
            buffer: PNG,
        });
}

/**
 * Keep the file as it is and close the crop dialog.
 *
 * Scoped to the dialog, and exact: the queue's own cancel button is labelled
 * "Cancel upload of \<name\>", and getByRole matches a substring by default, so
 * `name: 'Cancel'` on the whole page resolves to two controls.
 */
async function lewatiPemotongan(page: Page): Promise<void> {
    await page
        .getByRole('dialog')
        .getByRole('button', { name: 'Cancel', exact: true })
        .click();
}

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

    test('meminta pemotongan gambar setelah berkas dipilih', async ({
        page,
    }) => {
        // The featured image is cropped before it is sent, and the dialog is the
        // only place that is visible. Cancelling keeps the original, which is
        // also the path a parish photo takes when the framing is already right.
        await pilihGambar(page);

        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(
            page.getByRole('heading', { name: 'Potong gambar' }),
        ).toBeVisible();

        await lewatiPemotongan(page);
    });

    test('menampilkan pratinjau gambar segera setelah berkas dipilih', async ({
        page,
    }) => {
        const preview = page.locator('[data-slot="upload-item-media"] img');

        await expect(preview).toHaveCount(0);

        await pilihGambar(page);

        // Skipping the crop queues the file, and the queue renders its own
        // preview from the file in the browser. With the database queue and no
        // worker the server never finishes processing anything, so a preview
        // that appears here is one the admin's own file produced.
        await lewatiPemotongan(page);

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
