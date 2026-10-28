import { expect, test, type Page } from '@playwright/test';
import { resetLoginRateLimit, signIn } from './support/session';

/*
 * Komunitas — the admin list and form, and the public directory and detail.
 *
 * The E2E database is bare (app:e2e:prepare runs with demo content off), so a
 * category and a wilayah are created first — a community cannot exist without a
 * category, and its area filter needs an area.
 *
 * The contact privacy test is the important one: XC-P1 says a contact that is
 * not approved must never reach the browser, and the only way to check that from
 * the outside is to submit a recognisable name and assert it is absent from the
 * public page.
 */

async function pilihCombobox(
    page: Page,
    name: RegExp,
    option: string,
): Promise<void> {
    const trigger = page.getByRole('combobox', { name });
    await trigger.evaluate((element) =>
        element.scrollIntoView({ block: 'center' }),
    );
    await trigger.click();
    await page.getByRole('option', { name: option, exact: true }).click();
    await expect(
        page.getByRole('option', { name: option, exact: true }),
    ).toBeHidden();
}

async function buatKategori(page: Page, nama: string): Promise<void> {
    await page.goto('/admin/master-data/kategori-komunitas/tambah');
    await page.locator('#community-category-name').fill(nama);
    await page.getByRole('button', { name: 'Simpan kategori' }).click();

    await expect(page).toHaveURL(/\/admin\/master-data\/kategori-komunitas$/);
}

test.describe('sidebar Komunitas', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
        await page.goto('/admin');
    });

    test('menautkan langsung ke daftar komunitas', async ({ page }) => {
        const sidebar = page.getByRole('navigation', {
            name: 'Navigasi utama',
        });

        await expect(
            sidebar.getByRole('link', { name: 'Komunitas', exact: true }),
        ).toHaveAttribute('href', '/admin/komunitas');
    });

    test('klik Komunitas membuka daftarnya', async ({ page }) => {
        await page
            .getByRole('navigation', { name: 'Navigasi utama' })
            .getByRole('link', { name: 'Komunitas', exact: true })
            .click();

        await expect(page).toHaveURL(/\/admin\/komunitas$/);
        await expect(
            page.getByRole('heading', { name: 'Komunitas' }),
        ).toBeVisible();
    });
});

test.describe('alur komunitas', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
    });

    test('membuat komunitas, mempublikasikannya, dan menampilkannya di publik', async ({
        page,
    }) => {
        await buatKategori(page, 'Kategorial E2E');

        await page.goto('/admin/komunitas/tambah');
        await page.locator('#community-name').fill('Komunitas E2E');
        await pilihCombobox(page, /Kategori/, 'Kategorial E2E');
        await pilihCombobox(page, /Status Komunitas/, 'Aktif');

        // The Profil tab (D-60): the editor is a contenteditable div, so it is
        // filled the way a person types rather than through fill().
        await page.getByRole('tab', { name: 'Profil Komunitas' }).click();
        await page.locator('#community-vision').click();
        await page
            .locator('#community-vision')
            .pressSequentially('Menjadi mitra bagi semua.');

        // The Media Sosial tab (D-59), filled so the public detail has a link.
        await page.getByRole('tab', { name: 'Media Sosial' }).click();
        await page
            .locator('#community-website')
            .fill('https://komunitas-e2e.test');

        // The form is tabbed, so the switch lives in another tab than the one
        // that opened. `switch` and `tab` share the word "Publikasi" but are
        // different roles, which is why both locators stay unambiguous.
        await page.getByRole('tab', { name: 'Publikasi' }).click();
        await page.getByRole('switch', { name: 'Publikasi' }).click();
        await page.getByRole('button', { name: 'Simpan komunitas' }).click();

        await expect(page).toHaveURL(/\/admin\/komunitas\/\d+\/ubah$/);

        // Public directory shows it.
        await page.goto('/komunitas');
        await expect(
            page.getByRole('heading', { name: 'Komunitas E2E' }),
        ).toBeVisible();

        // Detail page opens.
        await page
            .getByRole('link', { name: /Lihat detail komunitas Komunitas E2E/ })
            .click();
        await expect(page).toHaveURL(/\/komunitas\/komunitas-e2e$/);
        await expect(
            page.getByRole('heading', { name: 'Komunitas E2E' }),
        ).toBeVisible();

        // The vision made the trip too, as markup rather than as text.
        await expect(
            page.locator('#main').getByText('Menjadi mitra bagi semua.'),
        ).toBeVisible();

        // The link made the trip: admin form -> resource -> public section.
        await expect(
            page.getByRole('link', { name: 'Situs web' }),
        ).toHaveAttribute('href', 'https://komunitas-e2e.test');
        // The public page groups social links under the contact section, not
        // under a separate "Media Sosial" heading.
        await expect(
            page.getByRole('link', { name: 'Situs web' }),
        ).toBeVisible();
    });

    test('menyembunyikan kontak komunitas yang belum disetujui', async ({
        page,
    }) => {
        await buatKategori(page, 'Kontak E2E');

        await page.goto('/admin/komunitas/tambah');
        await page.locator('#community-name').fill('Komunitas Kontak');
        await pilihCombobox(page, /Kategori/, 'Kontak E2E');
        await pilihCombobox(page, /Status Komunitas/, 'Aktif');

        // Contact fields sit in the Bergabung & Kontak tab, not the first one.
        await page
            .getByRole('tab', { name: 'Informasi Bergabung dan Kontak' })
            .click();
        await page
            .locator('#community-contact-name')
            .fill('Kontak Rahasia E2E');
        await page.locator('#community-contact-phone').fill('628123456789');
        // show_contact stays off.
        await page.getByRole('tab', { name: 'Publikasi' }).click();
        await page.getByRole('switch', { name: 'Publikasi' }).click();
        await page.getByRole('button', { name: 'Simpan komunitas' }).click();

        await expect(page).toHaveURL(/\/admin\/komunitas\/\d+\/ubah$/);

        await page.goto('/komunitas/komunitas-kontak');
        await expect(
            page.getByRole('heading', { name: 'Komunitas Kontak' }),
        ).toBeVisible();

        // The contact section and the number never reach the page.
        await expect(page.getByText('Kontak Rahasia E2E')).toHaveCount(0);
        await expect(page.getByText('628123456789')).toHaveCount(0);
        await expect(page.getByText('Kontak Pengurus')).toHaveCount(0);
    });
});
