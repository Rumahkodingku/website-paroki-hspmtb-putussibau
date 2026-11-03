import { expect, test } from '@playwright/test';
import { resetLoginRateLimit, signIn } from './support/session';

test.describe('seo', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
    });

    test('admin dapat menyimpan SEO halaman statis', async ({ page }) => {
        await page.goto('/admin/seo');

        await expect(
            page.getByRole('heading', {
                name: 'SEO Halaman Statis',
                level: 2,
            }),
        ).toBeVisible();

        // All panels start closed; opening Beranda reveals its fields.
        await expect(page.getByLabel('Judul SEO').first()).toBeHidden();
        await page.getByRole('button', { name: /Beranda/ }).first().click();
        await expect(page.getByLabel('Judul SEO').first()).toBeVisible();

        await page.getByLabel('Judul SEO').first().fill('Beranda E2E');
        await page
            .getByRole('button', { name: 'Simpan SEO halaman' })
            .first()
            .click();

        await expect(
            page.getByText('SEO halaman berhasil disimpan.'),
        ).toBeVisible();

        await page.goto('/');
        await expect(page).toHaveTitle(/Beranda E2E/);
    });

    test('robots.txt dan sitemap.xml dapat diakses publik', async ({
        request,
    }) => {
        const robots = await request.get('/robots.txt');
        expect(robots.status()).toBe(200);
        expect(await robots.text()).toContain('Sitemap:');

        const sitemap = await request.get('/sitemap.xml');
        expect(sitemap.status()).toBe(200);
        expect(await sitemap.text()).toContain('<urlset');
    });
});
