import { expect, test, type Page } from '@playwright/test';
import { resetLoginRateLimit, signIn } from './support/session';

/*
 * Area — the area module's admin page.
 *
 * Since D-57 the module stands alone and a lingkungan may be top-level (this
 * parish has no wilayah). The two-level shape (a wilayah with lingkungan under
 * it), the top-level lingkungan, and the delete guard are what this spec checks.
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

async function buatArea(
    page: Page,
    type: 'Wilayah' | 'Lingkungan' | 'Stasi',
    nama: string,
    slug: string,
    parent?: string,
    /**
     * Extra fields, filled on the create form before it is saved.
     *
     * A callback rather than a map of values because the fields are not all text
     * inputs, and because the helper saves immediately: anything meant to be
     * filled in has to happen on the form, not on the list page it lands on
     * afterwards.
     */
    beforeSave?: (form: Page) => Promise<void>,
): Promise<void> {
    await page.goto('/admin/area/tambah');

    // The type is always chosen explicitly: the form defaults to Lingkungan
    // (D-57), so a Wilayah or Stasi would otherwise be created as one.
    await pilihCombobox(page, /Tipe Area/, type);

    if (parent !== undefined) {
        await pilihCombobox(page, /Wilayah Induk/, parent);
    }

    await page.locator('#area-name').fill(nama);

    if (beforeSave) {
        await beforeSave(page);
    }

    await page.getByRole('button', { name: 'Simpan area' }).click();

    await expect(page).toHaveURL(/\/admin\/area$/);
}

/**
 * One table, so a row is addressed by its slug rather than by its text.
 *
 * The "Wilayah Induk" cell repeats a parent's name inside the child's row, so a
 * `hasText` filter matches both — that is why each row carries a
 * `data-test="area-row-<slug>"` hook on its name cell.
 */
const rowFor = (page: Page, slug: string) =>
    page
        .getByRole('row')
        .filter({ has: page.locator(`[data-test="area-row-${slug}"]`) });

async function hapus(page: Page, slug: string): Promise<void> {
    await rowFor(page, slug).getByRole('button', { name: 'Hapus' }).click();

    await page
        .getByRole('dialog')
        .getByRole('button', { name: 'Hapus', exact: true })
        .click();

    await expect(page.getByRole('dialog')).toBeHidden();
}

test.describe('sidebar Area', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
        await page.goto('/admin');
    });

    test('is its own flat link, not under Master Data', async ({ page }) => {
        const sidebar = page.getByRole('navigation', {
            name: 'Navigasi utama',
        });

        await expect(
            sidebar.getByRole('link', {
                name: 'Area Paroki',
                exact: true,
            }),
        ).toHaveAttribute('href', '/admin/area');

        await expect(
            sidebar.getByRole('link', {
                name: 'Area Paroki',
                exact: true,
            }),
        ).toHaveCount(1);
    });
});

test.describe('hierarki area', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
    });

    test('menampilkan lingkungan top-level dan stasi, serta menjaga hapus', async ({
        page,
    }) => {
        await buatArea(
            page,
            'Stasi',
            'Stasi Hierarki E2E',
            'stasi-hierarki-e2e',
        );
        await buatArea(
            page,
            'Lingkungan',
            'Lingkungan Hierarki E2E',
            'lingkungan-hierarki-e2e',
        );

        await page.goto('/admin/area');

        // One table for all three types, not a section per type.
        await expect(page.getByRole('table')).toHaveCount(1);

        await expect(rowFor(page, 'stasi-hierarki-e2e')).toBeVisible();
        await expect(rowFor(page, 'lingkungan-hierarki-e2e')).toBeVisible();

        await hapus(page, 'stasi-hierarki-e2e');
        await expect(rowFor(page, 'stasi-hierarki-e2e')).toHaveCount(0);
    });

    test('klik header mengurutkan lewat query string, bukan reorder di klien', async ({
        page,
    }) => {
        await buatArea(page, 'Stasi', 'Zulu E2E', 'zulu-e2e');
        await buatArea(page, 'Wilayah', 'Alfa E2E', 'alfa-e2e');

        await page.goto('/admin/area');

        await page
            .getByRole('button', { name: 'Urutkan berdasarkan Nama' })
            .click();

        await expect(page).toHaveURL(/sort=name/);
        await expect(page).toHaveURL(/direction=asc/);

        const names = page
            .getByRole('row')
            .filter({ has: page.locator('[data-test^="area-row-"]') });

        await expect(names.first()).toContainText('Alfa E2E');
        await expect(names.last()).toContainText('Zulu E2E');

        // Clicking the same header again flips the direction.
        await page
            .getByRole('button', { name: 'Urutkan berdasarkan Nama' })
            .click();

        await expect(page).toHaveURL(/direction=desc/);
        await expect(names.first()).toContainText('Zulu E2E');
    });

    test('wilayah dengan anak tidak bisa dihapus sebelum anaknya', async ({
        page,
    }) => {
        await buatArea(
            page,
            'Wilayah',
            'Wilayah Hierarki E2E',
            'wilayah-hierarki-e2e',
        );
        await buatArea(
            page,
            'Lingkungan',
            'Anak Hierarki E2E',
            'anak-hierarki-e2e',
            'Wilayah Hierarki E2E',
        );

        await page.goto('/admin/area');

        // The wilayah survives a refused delete while it has a child.
        await hapus(page, 'wilayah-hierarki-e2e');
        await expect(rowFor(page, 'wilayah-hierarki-e2e')).toBeVisible();

        await hapus(page, 'anak-hierarki-e2e');
        await expect(rowFor(page, 'anak-hierarki-e2e')).toHaveCount(0);

        await hapus(page, 'wilayah-hierarki-e2e');
        await expect(rowFor(page, 'wilayah-hierarki-e2e')).toHaveCount(0);
    });
});

test.describe('direktori publik', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
    });

    test('pencarian menemukan area dan bisa dikosongkan lagi', async ({
        page,
    }) => {
        await buatArea(
            page,
            'Lingkungan',
            'Angrep Cari E2E',
            'angrep-cari-e2e',
        );

        await page.goto('/profil/area');

        // The card's whole area is one link, so its accessible name carries the
        // type label and the name together.
        const card = page.getByRole('link', {
            name: 'Lihat detail Lingkungan Angrep Cari E2E',
        });

        await expect(card).toBeVisible();

        await page.getByRole('searchbox', { name: 'Cari area' }).fill('zzz');

        await expect(
            page.getByText('Tidak ada area yang cocok dengan pencarian.'),
        ).toBeVisible();
        await expect(card).toHaveCount(0);

        await page.getByRole('button', { name: 'Reset pencarian' }).click();

        await expect(card).toBeVisible();
        await expect(
            page.getByRole('searchbox', { name: 'Cari area' }),
        ).toHaveValue('');
    });

    test('alamat dan tautan peta muncul di halaman detail (D-61)', async ({
        page,
    }) => {
        await buatArea(
            page,
            'Stasi',
            'Stasi Peta E2E',
            'stasi-peta-e2e',
            undefined,
            async (form) => {
                // The location block is a titled group of its own in the form,
                // below the two switches - and it has to be filled in here,
                // because the helper saves and lands on the list page after.
                await form
                    .locator('#area-address')
                    .fill('Gereja Stasi Peta E2E, Jalan Peta No. 2');
            },
        );

        await page.goto('/profil/area/stasi-peta-e2e');

        await expect(
            page.getByText('Gereja Stasi Peta E2E, Jalan Peta No. 2'),
        ).toBeVisible();

        // No coordinates were stored, so there is no link to assert - the block
        // would not have rendered its address without one being possible.
        await expect(
            page.getByRole('link', { name: 'Buka di Google Maps' }),
        ).toHaveCount(0);
    });
});
