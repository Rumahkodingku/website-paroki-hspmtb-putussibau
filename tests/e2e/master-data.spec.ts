import { expect, test } from '@playwright/test';
import { buatLokasi, pilih, pilihWaktu } from './support/admin-forms';
import { resetLoginRateLimit, signIn } from './support/session';

/*
 * Master Data — Lokasi, Kategori Berita, Kategori Agenda.
 *
 * ## Why this file exists
 *
 * Clicking "Kategori Berita" and "Kategori Agenda" in the sidebar did nothing
 * at all. No navigation, no error, no loading. Nothing in the codebase
 * explained it: the routes existed, the server answered 200 with the right
 * component, the hrefs were correct, the pages were registered, and the build
 * carried all three URLs.
 *
 * The cause was a name mismatch that no test asked about. The controller sent
 * Inertia "admin/kategori-berita/index"; the frontend had registered that file
 * as "admin/kategori-berita", because pageName() drops `index` at a feature's
 * root and keeps it one folder down. Both halves were internally consistent
 * and nothing compared them. ArchitectureTest now walks every controller's
 * render call and checks the name against the registered page list — that is
 * the test this file's bug produced.
 *
 * The lesson is in the shape of the file: every sidebar link is asserted for
 * its **href and then clicked**, and the URL is checked afterwards. Asserting
 * an href proves nothing about whether the link works, and doing exactly that
 * is what let four screens ship unreachable.
 */

test.describe('sidebar Master Data', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
        await page.goto('/admin');

        // Semua item sekarang tampil langsung (tanpa dropdown); tidak perlu membuka grup.
    });

    test('setiap anak Master Data punya href yang berbeda', async ({
        page,
    }) => {
        const sidebar = page.getByRole('navigation', {
            name: 'Navigasi utama',
        });

        const hrefs = await Promise.all(
            [
                'Lokasi',
                'Kategori Berita',
                'Kategori Agenda',
                'Kategori Layanan',
                'Kategori Komunitas',
            ].map((title) =>
                sidebar
                    .getByRole('link', { name: title, exact: true })
                    .getAttribute('href'),
            ),
        );

        // Two names for one function — `import { index as a, index as b }` —
        // produces a duplicate here and makes one list unreachable. It has
        // happened in this sidebar before.
        expect(new Set(hrefs).size).toBe(hrefs.length);
    });

    test('Lokasi hidup di grup Master Data, bukan di Jadwal Misa', async ({
        page,
    }) => {
        const sidebar = page.getByRole('navigation', {
            name: 'Navigasi utama',
        });

        for (const [title, href] of [
            ['Lokasi', '/admin/master-data/lokasi'],
            ['Kategori Berita', '/admin/master-data/kategori-berita'],
            ['Kategori Agenda', '/admin/master-data/kategori-agenda'],
            ['Kategori Layanan', '/admin/master-data/kategori-pelayanan'],
            ['Kategori Komunitas', '/admin/master-data/kategori-komunitas'],
        ] as const) {
            await expect(
                sidebar.getByRole('link', { name: title, exact: true }),
            ).toHaveAttribute('href', href);
        }

        // Hanya boleh ada satu label "Lokasi" di seluruh sidebar: kategori dan
        // lokasi masing-masing muncul di bawah satu grup saja.
        await expect(
            sidebar.getByRole('link', { name: 'Lokasi', exact: true }),
        ).toHaveCount(1);
    });

    test('klik Lokasi benar-benar membuka halamannya', async ({ page }) => {
        const sidebar = page.getByRole('navigation', {
            name: 'Navigasi utama',
        });

        await sidebar
            .getByRole('link', { name: 'Lokasi', exact: true })
            .click();

        await expect(page).toHaveURL(/\/admin\/master-data\/lokasi$/);
    });

    /**
     * The bug this file was written for. Asserted on the URL and then on
     * content only the target page renders, so a navigation that lands
     * somewhere else fails rather than passing on a redirect.
     */
    test('klik Kategori Berita benar-benar membuka halamannya', async ({
        page,
    }) => {
        await page
            .getByRole('navigation', { name: 'Navigasi utama' })
            .getByRole('link', { name: 'Kategori Berita', exact: true })
            .click();

        await expect(page).toHaveURL(/\/admin\/master-data\/kategori-berita$/);
        await expect(
            page.getByRole('heading', { name: 'Kategori Berita' }),
        ).toBeVisible();
    });

    test('klik Kategori Agenda benar-benar membuka halamannya', async ({
        page,
    }) => {
        await page
            .getByRole('navigation', { name: 'Navigasi utama' })
            .getByRole('link', { name: 'Kategori Agenda', exact: true })
            .click();

        await expect(page).toHaveURL(/\/admin\/master-data\/kategori-agenda$/);
        await expect(
            page.getByRole('heading', { name: 'Kategori Agenda' }),
        ).toBeVisible();
    });

    test('klik Kategori Pelayanan benar-benar membuka halamannya', async ({
        page,
    }) => {
        await page
            .getByRole('navigation', { name: 'Navigasi utama' })
            .getByRole('link', { name: 'Kategori Layanan', exact: true })
            .click();

        await expect(page).toHaveURL(
            /\/admin\/master-data\/kategori-pelayanan$/,
        );
        await expect(
            page.getByRole('heading', { name: 'Kategori Layanan' }),
        ).toBeVisible();
    });

    test('klik Kategori Komunitas benar-benar membuka halamannya', async ({
        page,
    }) => {
        await page
            .getByRole('navigation', { name: 'Navigasi utama' })
            .getByRole('link', { name: 'Kategori Komunitas', exact: true })
            .click();

        await expect(page).toHaveURL(
            /\/admin\/master-data\/kategori-komunitas$/,
        );
        await expect(
            page.getByRole('heading', { name: 'Kategori Komunitas' }),
        ).toBeVisible();
    });

});

test.describe('lokasi admin', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
    });

    test('form lokasi memvalidasi dan menampilkan pesan bahasa Indonesia', async ({
        page,
    }) => {
        await page.goto('/admin/master-data/lokasi/tambah');
        await page.getByRole('button', { name: 'Simpan lokasi' }).click();

        // The sentence comes from lang/id/validation.php: the message is
        // ":attribute wajib diisi." and attributes.name is "Nama". The form
        // label "Nama lokasi" is not what a visitor is told.
        await expect(
            page.getByRole('main').getByText('Nama wajib diisi.'),
        ).toBeVisible();
    });

    test('koordinat setengah terisi ditolak', async ({ page }) => {
        // A half-filled pair is a map link pointing at open ocean.
        await page.goto('/admin/master-data/lokasi/tambah');

        await page.getByLabel('Nama lokasi').fill('Koordinat Setengah');
        await page.getByLabel('Lintang').fill('-0.0293');
        await page.getByRole('button', { name: 'Simpan lokasi' }).click();

        await expect(
            page
                .getByText(
                    'Koordinat lintang dan bujur harus diisi keduanya atau dikosongkan keduanya.',
                )
                .first(),
        ).toBeVisible();
    });

    test('lokasi kosong bisa dihapus lewat dialog konfirmasi', async ({
        page,
    }) => {
        await buatLokasi(page, 'Lokasi Untuk Dihapus');

        const row = page.getByRole('row').filter({
            hasText: 'Lokasi Untuk Dihapus',
        });

        await row.getByRole('button', { name: 'Hapus' }).click();

        // The dialog has to state the consequence, not just the verb. Scoped to
        // the dialog: the name is also on the row behind it, and there is a
        // card version of the list on small screens besides.
        const dialog = page.getByRole('dialog');
        await expect(
            dialog.getByTestId('confirm-dialog-confirm'),
        ).toBeVisible();
        await expect(dialog).toContainText('Lokasi Untuk Dihapus');
        await expect(dialog).toContainText('tidak dapat dibatalkan');

        await page.getByTestId('confirm-dialog-confirm').click();

        await expect(page).toHaveURL(/\/admin\/master-data\/lokasi$/);
        await expect(page.getByText('Lokasi Untuk Dihapus')).toHaveCount(0);
    });

    test('lokasi yang punya jadwal tidak menawarkan tombol hapus', async ({
        page,
    }) => {
        // Roadmap 4.5: the button is not offered for something the server will
        // refuse, and the refusal is asserted by DeleteTest on the backend.
        await buatLokasi(page, 'Lokasi Sibuk E2E');

        await page.goto('/admin/jadwal-misa/misa-rutin/tambah');

        await pilih(page, 'Lokasi', 'Lokasi Sibuk E2E');
        await pilih(page, 'Hari', 'Selasa');
        await pilihWaktu(page, 'Jam mulai', '10:00');
        await page.getByLabel('Nama misa').fill('Misa Sibuk E2E');
        await page.getByRole('button', { name: 'Simpan jadwal' }).click();

        await expect(page).toHaveURL(/\/admin\/jadwal-misa\/misa-rutin$/);

        await page.goto('/admin/master-data/lokasi');

        const busy = page
            .getByRole('row')
            .filter({ hasText: 'Lokasi Sibuk E2E' });

        await expect(busy.getByRole('link', { name: 'Ubah' })).toBeVisible();
        await expect(busy.getByRole('button', { name: 'Hapus' })).toHaveCount(
            0,
        );
    });
});

/*
 * The URLs this module used to have.
 *
 * A bookmarked link is the difference between "the menu moved" and "the parish
 * secretary's link is broken", so each one answers 302 rather than 404. Checked
 * in the browser rather than only in a feature test because the thing that can
 * break here is the middleware stack in front of the redirect, not the redirect.
 */
test.describe('URL Master Data yang lama', () => {
    test('dialihkan ke alamat barunya, bukan 404', async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);

        for (const [from, to] of [
            ['/admin/jadwal-misa/lokasi', '/admin/master-data/lokasi'],
            ['/admin/kategori-berita', '/admin/master-data/kategori-berita'],
            ['/admin/kategori-agenda', '/admin/master-data/kategori-agenda'],
        ] as const) {
            await page.goto(from);
            await expect(page).toHaveURL(new RegExp(`${to}$`));
        }
    });
});
