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
            'Profil Paroki',
            'Media Sosial',
            'SEO Dasar',
            'Beranda',
            'Privasi',
        ]) {
            await expect(page.getByRole('tab', { name: group })).toBeVisible();
        }
    });

    test('kontrol favicon memakai pemilih media, bukan input teks', async ({
        page,
    }) => {
        await page.goto('/admin/pengaturan');
        await page.getByRole('tab', { name: 'Identitas Paroki' }).click();

        // The media picker shows its empty hint; the old text input is gone.
        await expect(page.getByText('Belum ada favicon.')).toBeVisible();
        await expect(page.locator('input#setting-favicon')).toHaveCount(0);
    });

    test('field situs web berada di tab Media Sosial, bukan Profil Paroki', async ({
        page,
    }) => {
        await page.goto('/admin/pengaturan');

        await page.getByRole('tab', { name: 'Profil Paroki' }).click();
        await expect(page.getByText('Situs web paroki')).toHaveCount(0);

        await page.getByRole('tab', { name: 'Media Sosial' }).click();
        await expect(page.getByText('Situs web paroki')).toBeVisible();
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

    test('editor jam pelayanan menyimpan jadwal mingguan', async ({ page }) => {
        await page.goto('/admin/pengaturan');
        await page.getByRole('tab', { name: 'Kontak' }).click();

        await expect(page.getByText('Jam Pelayanan Sekretariat')).toBeVisible();

        // Only the seven day switches live in this tab; every other Kontak field
        // is a text input.
        await expect(page.getByRole('switch')).toHaveCount(7);

        await page
            .getByRole('switch', { name: 'Aktifkan pelayanan Senin' })
            .click();
        await page.getByLabel('Jam buka Senin').fill('08:00');
        await page.getByLabel('Jam tutup Senin').fill('15:00');

        await Promise.all([
            page.waitForResponse(
                (response) =>
                    response.url().includes('/admin/pengaturan') &&
                    ['POST', 'PUT'].includes(response.request().method()),
            ),
            page.getByTestId('save-settings-button').click(),
        ]);

        await page.reload();
        await page.getByRole('tab', { name: 'Kontak' }).click();

        await expect(
            page.getByRole('switch', { name: 'Aktifkan pelayanan Senin' }),
        ).toHaveAttribute('aria-checked', 'true');
        await expect(page.getByLabel('Jam buka Senin')).toHaveValue('08:00');
        await expect(page.getByLabel('Jam tutup Senin')).toHaveValue('15:00');
    });

    test('halaman pengaturan menolak tamu', async ({ page, context }) => {
        // Drop the session established above: the middleware layers must refuse
        // before the page renders at all.
        await context.clearCookies();

        await page.goto('/admin/pengaturan');

        await expect(page).toHaveURL(/\/admin\/login$/);
    });
});
