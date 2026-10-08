import { expect, test } from '@playwright/test';
import { resetLoginRateLimit, signIn } from './support/session';

/**
 * The settings page is the only data-driven admin view in Phase 01, and its
 * rich text editor is the one component that is loaded lazily: Tiptap and
 * ProseMirror are a few hundred kilobytes and only one tab needs them.
 *
 * That makes it the one place where a moved file can break the page without any
 * other test noticing. A dynamic import of a path that no longer resolves does
 * not fail the build, it fails the first time somebody opens the tab that needs
 * it — so this spec opens that tab.
 */
test.describe('pengaturan situs', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
    });

    test('halaman pengaturan memuat dan menampilkan setiap grup sebagai tab', async ({
        page,
    }) => {
        await page.goto('/admin/pengaturan');

        // level 2, not level 1: the admin shell already renders an sr-only h1
        // with the page title, so asking for the heading by name alone matches
        // both and throws on a strict-mode violation.
        await expect(
            page.getByRole('heading', {
                name: 'Pengaturan Situs',
                level: 2,
            }),
        ).toBeVisible();

        // One tab per group from config/site-settings.php. Asserting a single tab
        // would still pass if the rest failed to render.
        for (const group of [
            'Identitas Paroki',
            'Kontak',
            'Media Sosial',
            'SEO Dasar',
            'Beranda',
            'Privasi',
        ]) {
            await expect(page.getByRole('tab', { name: group })).toBeVisible();
        }
    });

    test('editor rich text termuat saat tab privasi dibuka', async ({
        page,
    }) => {
        await page.goto('/admin/pengaturan');

        // Radix keeps inactive tab panels unmounted, so the editor is not in the
        // DOM until its tab is opened. That is also what makes this the only
        // assertion in the suite that exercises the lazy chunk.
        await page.getByRole('tab', { name: 'Privasi' }).click();

        await expect(page.locator('[contenteditable="true"]')).toBeVisible();

        // The toolbar ships in the same chunk as the editor, so finding it rules
        // out an editor that mounted without its controls. Its label is the
        // component's fallback: the settings page passes id, value and onChange,
        // but no label.
        await expect(
            page.getByRole('toolbar', { name: 'Alat pemformat teks' }),
        ).toBeVisible();

        await expect(page.getByRole('button', { name: 'Tebal' })).toBeVisible();
    });

    test('halaman pengaturan menolak tamu', async ({ page, context }) => {
        // Drop the session established above: the middleware layers must refuse
        // before the page renders at all.
        await context.clearCookies();

        await page.goto('/admin/pengaturan');

        await expect(page).toHaveURL(/\/admin\/login$/);
    });
});
