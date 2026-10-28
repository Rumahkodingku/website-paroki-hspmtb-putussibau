import { expect, test } from '@playwright/test';
import {
    clearContactDetails,
    seedContactDetails,
    setHomeShowContact,
} from './support/contact';
import { resetLoginRateLimit, signIn } from './support/session';

/**
 * Phase 8C — Kontak.
 *
 * The fixture is planted before each test and removed afterwards, so a spec
 * never depends on another spec's data. The values are fake on purpose; real
 * parish contact details are not test data.
 *
 * @see docs/roadmap/phase-8c-contacts.md §8.3
 */
test.describe('kontak publik', () => {
    test.beforeEach(() => {
        clearContactDetails();
    });

    test('pengunjung tanpa login dapat membuka /kontak', async ({ page }) => {
        await page.goto('/kontak');

        await expect(
            page.getByRole('heading', { name: 'Kami siap membantu Anda' }),
        ).toBeVisible();
    });

    test('data fixture tampil dan setiap tautan punya target yang benar', async ({
        page,
    }) => {
        seedContactDetails();
        await page.goto('/kontak');

        const main = page.getByRole('main');

        await expect(main.getByText(/Jalan E2E No\. 1/)).toBeVisible();

        await expect(
            main.getByRole('link', { name: 'Telepon', exact: true }),
        ).toHaveAttribute('href', 'tel:+6281234567890');

        await expect(
            main.getByRole('link', { name: 'Chat WhatsApp' }),
        ).toHaveAttribute('href', 'https://wa.me/6281234567890');

        await expect(
            main.getByRole('link', { name: 'Kirim Email' }),
        ).toHaveAttribute('href', 'mailto:kontak@example.test');

        await expect(
            main.getByRole('link', { name: 'Petunjuk Arah' }),
        ).toHaveAttribute(
            'href',
            'https://www.google.com/maps/place/Paroki+E2E/@-0.0293,111.7686,17z',
        );

        await expect(
            main.getByRole('link', { name: 'Facebook' }),
        ).toHaveAttribute('href', 'https://facebook.com/contoh');

        // The weekly schedule is formatted server-side into display lines.
        await expect(
            main.getByText(/Senin–Jumat: 08\.00–15\.00 WIB/),
        ).toBeVisible();
    });

    test('peta hanya tampil saat URL embed tersedia', async ({ page }) => {
        seedContactDetails();
        await page.goto('/kontak');

        await expect(page.getByTitle('Peta lokasi paroki')).toBeVisible();

        clearContactDetails();
        await page.goto('/kontak');

        await expect(page.getByTitle('Peta lokasi paroki')).toHaveCount(0);
    });

    test('data kosong tidak menghasilkan tombol atau tautan rusak', async ({
        page,
    }) => {
        await page.goto('/kontak');

        await expect(
            page.getByText('Informasi kontak belum tersedia'),
        ).toBeVisible();

        await expect(page.getByRole('link', { name: 'Telepon' })).toHaveCount(
            0,
        );
        await expect(
            page.getByRole('link', { name: 'Chat WhatsApp' }),
        ).toHaveCount(0);
        await expect(
            page.getByRole('link', { name: 'Kirim Email' }),
        ).toHaveCount(0);
        await expect(
            page.getByRole('link', { name: 'Petunjuk Arah' }),
        ).toHaveCount(0);
    });

    test('footer memakai data yang sama dan menuju halaman kontak', async ({
        page,
    }) => {
        seedContactDetails();
        await page.goto('/');

        const footer = page.getByRole('contentinfo');

        await expect(footer.getByText(/Jalan E2E No\. 1/)).toBeVisible();
        await expect(
            footer.getByRole('link', { name: /Telepon: 0812-3456-7890/ }),
        ).toHaveAttribute('href', 'tel:+6281234567890');

        await footer.getByRole('link', { name: 'Halaman kontak' }).click();

        await expect(page).toHaveURL(/\/kontak$/);
        await expect(
            page.getByRole('heading', { name: 'Kami siap membantu Anda' }),
        ).toBeVisible();
    });

    test('home_show_contact hanya mengatur blok beranda', async ({ page }) => {
        seedContactDetails();
        setHomeShowContact(false);

        await page.goto('/');
        await expect(
            page
                .getByRole('main')
                .getByRole('heading', { name: 'Hubungi Kami' }),
        ).toHaveCount(0);

        await page.goto('/kontak');
        await expect(
            page.getByRole('heading', { name: 'Kami siap membantu Anda' }),
        ).toBeVisible();
    });

    test('tidak ada horizontal overflow pada 360px', async ({ page }) => {
        seedContactDetails();
        await page.setViewportSize({ width: 360, height: 780 });
        await page.goto('/kontak');

        const overflow = await page.evaluate(
            () =>
                document.documentElement.scrollWidth -
                document.documentElement.clientWidth,
        );

        expect(overflow).toBeLessThanOrEqual(0);
    });

    test('perubahan pengaturan admin terlihat di halaman publik', async ({
        page,
    }) => {
        resetLoginRateLimit();
        await signIn(page);

        await page.goto('/admin/pengaturan');
        await page.getByRole('tab', { name: 'Kontak' }).click();
        await page.locator('#setting-contact_phone').fill('0812-9999-8888');

        await Promise.all([
            page.waitForResponse(
                (response) =>
                    response.url().includes('/admin/pengaturan') &&
                    response.request().method() === 'PUT',
            ),
            page.getByTestId('save-settings-button').click(),
        ]);

        await page.goto('/kontak');

        await expect(
            page
                .getByRole('main')
                .getByRole('link', { name: 'Telepon', exact: true }),
        ).toHaveAttribute('href', 'tel:+6281299998888');
    });
});
