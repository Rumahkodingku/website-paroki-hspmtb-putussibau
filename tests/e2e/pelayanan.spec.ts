import { expect, test, type Page } from '@playwright/test';
import { resetLoginRateLimit, signIn } from './support/session';

/*
 * Pelayanan — the admin list, the tabbed service form, and the public pages.
 *
 * The sidebar item is asserted for its href and then clicked, because an href
 * that points nowhere is exactly how four screens shipped unreachable in an
 * earlier phase (see master-data.spec.ts).
 *
 * The E2E database is bare (app:e2e:prepare runs with demo content off), so a
 * category is created first — a service cannot exist without one.
 */

async function buatKategori(page: Page, nama: string): Promise<void> {
    await page.goto('/admin/master-data/kategori-pelayanan/tambah');
    await page.getByLabel('Nama Kategori').fill(nama);
    await page.getByRole('button', { name: 'Simpan kategori' }).click();

    await expect(page).toHaveURL(/\/admin\/master-data\/kategori-pelayanan$/);
}

async function buatLayanan(
    page: Page,
    nama: string,
    kategori: string,
): Promise<void> {
    await page.goto('/admin/pelayanan/tambah');
    await page.getByLabel('Nama Layanan').fill(nama);

    // The category is a Radix ChoiceSelect. Addressed by role rather than by
    // label because a required field's accessible name carries the asterisk
    // ("Kategori *"), which an exact label match does not allow.
    //
    // The trigger is centred before opening: the list renders downward, and on
    // the default 720px viewport a trigger sitting near the bottom puts the
    // options out of the viewport, where Playwright cannot click them.
    const trigger = page.getByRole('combobox', { name: /Kategori/ });
    await trigger.evaluate((element) =>
        element.scrollIntoView({ block: 'center' }),
    );
    await trigger.click();
    await page.getByRole('option', { name: kategori, exact: true }).click();
    await expect(
        page.getByRole('option', { name: kategori, exact: true }),
    ).toBeHidden();

    await page.getByRole('button', { name: 'Simpan', exact: true }).click();

    await expect(page).toHaveURL(/\/admin\/pelayanan\/\d+\/ubah$/);
}

test.describe('sidebar Pelayanan', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
        await page.goto('/admin');
    });

    test('menautkan langsung ke Daftar Pelayanan', async ({ page }) => {
        const sidebar = page.getByRole('navigation', {
            name: 'Navigasi utama',
        });

        await expect(
            sidebar.getByRole('link', { name: 'Pelayanan', exact: true }),
        ).toHaveAttribute('href', '/admin/pelayanan');
    });

    test('klik Pelayanan membuka Daftar Pelayanan', async ({ page }) => {
        await page
            .getByRole('navigation', { name: 'Navigasi utama' })
            .getByRole('link', { name: 'Pelayanan', exact: true })
            .click();

        await expect(page).toHaveURL(/\/admin\/pelayanan$/);
        await expect(
            page.getByRole('heading', { name: 'Daftar Pelayanan' }),
        ).toBeVisible();
    });
});

test.describe('layanan admin', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
    });

    test('membuat kategori, layanan, persyaratan, dan FAQ', async ({
        page,
    }) => {
        await buatKategori(page, 'Sakramen Inisiasi E2E');
        await buatLayanan(
            page,
            'Baptis Bayi E2E',
            'Sakramen Inisiasi E2E',
        );

        // Persyaratan tab.
        await page.getByRole('tab', { name: 'Persyaratan' }).click();
        await page.getByRole('button', { name: 'Tambah Persyaratan' }).click();

        const requirementDialog = page.getByRole('dialog');
        await requirementDialog
            .getByRole('textbox', { name: 'Persyaratan *' })
            .fill('Fotokopi Kartu Keluarga');
        await requirementDialog
            .getByRole('button', { name: 'Tambah', exact: true })
            .click();

        await expect(page.getByText('Fotokopi Kartu Keluarga')).toBeVisible();

        // FAQ tab.
        await page.getByRole('tab', { name: 'FAQ' }).click();
        await page.getByRole('button', { name: 'Tambah FAQ' }).click();

        const faqDialog = page.getByRole('dialog');
        await faqDialog.getByLabel('Pertanyaan').fill('Apakah ada biaya?');
        await faqDialog
            .getByLabel('Jawaban')
            .fill('Silakan hubungi sekretariat.');
        await faqDialog
            .getByRole('button', { name: 'Tambah', exact: true })
            .click();

        await expect(page.getByText('Apakah ada biaya?')).toBeVisible();
    });

    test('menyimpan layanan tanpa URL Google Form', async ({ page }) => {
        await buatKategori(page, 'Kategori Tanpa Form');
        await buatLayanan(
            page,
            'Layanan Tanpa Form',
            'Kategori Tanpa Form',
        );

        await page.getByRole('tab', { name: 'Kontak & Pendaftaran' }).click();
        await expect(
            page.getByText('Tidak menggunakan Google Form.', { exact: true }),
        ).toBeVisible();

        await page.getByRole('button', { name: 'Simpan Perubahan' }).click();

        // A URL is optional, so saving without one succeeds.
        await expect(page).toHaveURL(/\/admin\/pelayanan\/\d+\/ubah$/);
    });

    test('menerima URL Google Form valid dan menampilkan tombol uji', async ({
        page,
    }) => {
        await buatKategori(page, 'Kategori Form');
        await buatLayanan(page, 'Layanan Form', 'Kategori Form');

        await page.getByRole('tab', { name: 'Kontak & Pendaftaran' }).click();
        await page
            .getByLabel('Link Google Form')
            .fill('https://docs.google.com/forms/d/e/contoh/viewform');

        await expect(
            page.getByRole('link', { name: 'Buka Tautan' }),
        ).toBeVisible();
    });

    test('menolak URL yang bukan Google Form', async ({ page }) => {
        await buatKategori(page, 'Kategori URL Salah');
        await buatLayanan(
            page,
            'Layanan URL Salah',
            'Kategori URL Salah',
        );

        await page.getByRole('tab', { name: 'Kontak & Pendaftaran' }).click();
        await page
            .getByLabel('Link Google Form')
            .fill('https://evil.com/forms');
        await page.getByRole('button', { name: 'Simpan Perubahan' }).click();

        await expect(
            page
                .getByRole('main')
                .getByText('Tautan Google Form harus berupa alamat https')
                .first(),
        ).toBeVisible();
    });
});

test.describe('halaman publik Pelayanan', () => {
    test('pengunjung hanya melihat layanan yang aktif', async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
        await buatKategori(page, 'Kategori Publik');
        await buatLayanan(
            page,
            'Layanan Publik E2E',
            'Kategori Publik',
        );

        // buatLayanan ends on the edit page. A new service is inactive, so the
        // public list does not show it yet.
        const editUrl = page.url();

        await page.goto('/pelayanan');
        await expect(page.getByText('Layanan Publik E2E')).toHaveCount(0);

        // Activate it, then it appears publicly.
        await page.goto(editUrl);
        await page
            .getByRole('switch', { name: 'Aktifkan layanan ini' })
            .click();
        await page.getByRole('button', { name: 'Simpan Perubahan' }).click();

        await page.goto('/pelayanan');
        await expect(page.getByText('Layanan Publik E2E')).toBeVisible();
    });
});
