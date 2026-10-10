import { expect, test } from '@playwright/test';
import { resetLoginRateLimit, signIn } from './support/session';

/*
 * /jadwal-misa and the four admin pages behind it.
 *
 * This spec is where the acceptance criteria are checked in a browser rather
 * than in a response body: AC-MASS-5 (readable on a phone), AC-MASS-3 (an admin
 * change visible to a visitor), and the navigation of AC-MASS-1.
 *
 * Every test creates the data it needs through the admin UI rather than
 * seeding it, so the admin forms are exercised on the way past. The alternative
 * — seeding and only asserting the public page — would leave the forms
 * unverified in the one place where a mistake is visible to a parish secretary.
 *
 * Nothing here hard-codes a credential or a database name: both come from
 * tests/e2e/.auth.json, which `php artisan app:e2e:prepare` writes (D-29 §5).
 */

const LOKASI = 'Gereja Paroki E2E';

/** Fill the location form and save. */
async function buatLokasi(
    page: import('@playwright/test').Page,
    nama: string,
    slug: string,
): Promise<void> {
    await page.goto('/admin/lokasi/tambah');

    await page.getByLabel('Nama lokasi').fill(nama);
    await page.getByLabel('Slug').fill(slug);
    await page.getByLabel('Alamat').fill('Alamat pengujian');
    await page.getByRole('button', { name: 'Simpan lokasi' }).click();

    await expect(page).toHaveURL(/\/admin\/lokasi$/);
}

test.describe('jadwal misa publik', () => {
    test('halaman memuat kartu Misa Berikutnya dan kedua tab', async ({
        page,
    }) => {
        await page.goto('/jadwal-misa');

        await expect(
            page.getByRole('heading', { name: 'Misa Berikutnya' }),
        ).toBeVisible();

        await expect(
            page.getByRole('tab', { name: 'Misa Rutin' }),
        ).toBeVisible();
        await expect(
            page.getByRole('tab', { name: 'Misa Khusus' }),
        ).toBeVisible();

        // A link, not a button: it hands the URL to wa.me in a new tab. Only
        // copy and print are actions this page performs itself.
        await expect(
            page.getByRole('link', { name: 'Bagikan lewat WhatsApp' }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: 'Cetak jadwal' }),
        ).toBeVisible();
    });

    test('jadwal yang dibuat admin tampil di halaman publik', async ({
        page,
    }) => {
        test.slow();

        resetLoginRateLimit();
        await signIn(page);

        await buatLokasi(page, LOKASI, 'gereja-paroki-e2e');

        const row = (name: string) =>
            page.getByRole('row').filter({ hasText: name });

        // MASS-07, first half: switching a location off takes it off the public
        // page. The edit form is the only way to do it - there is no delete and
        // no toggle endpoint (D-34, D-37).
        await row(LOKASI).getByRole('link', { name: 'Ubah' }).click();
        await expect(page).toHaveURL(/\/admin\/lokasi\/\d+\/ubah$/);
        await page.getByRole('switch', { name: /aktif/i }).click();
        await page.getByRole('button', { name: 'Simpan perubahan' }).click();
        await expect(page).toHaveURL(/\/admin\/lokasi$/);

        await page.goto('/jadwal-misa');
        await expect(page.getByText(LOKASI)).toHaveCount(0);

        // Back on, so the schedule below has somewhere to live. The admin list
        // keeps deactivated locations visible - otherwise switching one off
        // would be the one edit a secretary could not undo.
        await page.goto('/admin/lokasi');
        await row(LOKASI).getByRole('link', { name: 'Ubah' }).click();
        await page.getByRole('switch', { name: /aktif/i }).click();
        await page.getByRole('button', { name: 'Simpan perubahan' }).click();
        await expect(page).toHaveURL(/\/admin\/lokasi$/);

        await page.goto('/admin/jadwal-misa/tambah');

        await page
            .getByLabel('Lokasi', { exact: true })
            .selectOption({ label: LOKASI });
        await page.getByLabel('Hari').selectOption({ label: 'Minggu' });
        await page.getByLabel('Jam mulai').fill('07:30');
        await page.getByLabel('Nama misa').fill('Misa Minggu E2E');
        await page.getByRole('button', { name: 'Simpan jadwal' }).click();

        await expect(page).toHaveURL(/\/admin\/jadwal-misa$/);

        // AC-MASS-3: the public page reads this on the very next request.
        await page.goto('/jadwal-misa');

        await expect(
            page.getByTestId('mass-schedule-card').getByText('Misa Minggu E2E'),
        ).toBeVisible();
        await expect(page.getByText('07.30 WIB').first()).toBeVisible();
    });

    test('filter lokasi dan hari mengubah daftar yang terlihat', async ({
        page,
    }) => {
        await page.goto('/jadwal-misa?hari=0');

        await expect(page.getByLabel('Hari', { exact: true })).toHaveValue('0');

        await page
            .getByLabel('Lokasi', { exact: true })
            .selectOption({ index: 1 });

        await expect(page).toHaveURL(/lokasi=\d+/);
        await expect(page.getByText(/filter aktif/i)).toBeVisible();
    });

    test('filter rusak diabaikan, bukanerror', async ({ page }) => {
        // Roadmap 5.5: a mangled link should show the schedule, not a 422 to
        // somebody who followed it.
        const response = await page.goto('/jadwal-misa?hari=99&lokasi=abc');

        expect(response?.status()).toBe(200);

        // The schedule itself, not a card: whether "Misa Berikutnya" is a
        // heading or a label depends on what earlier tests left behind, and a
        // test that breaks when a sibling adds a schedule is testing the
        // sibling.
        await expect(
            page.getByRole('tab', { name: 'Misa Rutin' }),
        ).toBeVisible();
        await expect(page.getByTestId('schedule-filter')).toBeVisible();
    });

    /**
     * AC-MASS-5. A table that overflows on a phone is the failure this catches,
     * and it is invisible in a screenshot at desktop width.
     */
    test('tidak ada scroll horizontal pada 360px', async ({ page }) => {
        await page.setViewportSize({ width: 360, height: 780 });
        await page.goto('/jadwal-misa');

        const overflow = await page.evaluate(
            () =>
                document.documentElement.scrollWidth -
                document.documentElement.clientWidth,
        );

        expect(overflow).toBeLessThanOrEqual(0);
    });

    test('navigasi publik tetap membawa Jadwal Misa dalam dua klik', async ({
        page,
    }) => {
        await page.goto('/');

        // AC-MASS-1: Beranda → menu → Jadwal Misa.
        await page
            .getByRole('navigation', { name: 'Navigasi utama' })
            .getByRole('link', { name: 'Jadwal Misa' })
            .first()
            .click();

        await expect(page).toHaveURL(/\/jadwal-misa$/);
    });
});

test.describe('jadwal misa admin', () => {
    test.beforeEach(async ({ page }) => {
        resetLoginRateLimit();
        await signIn(page);
    });

    test('ringkasan modul memuat empat pintasan dan empat statistik', async ({
        page,
    }) => {
        await page.goto('/admin/jadwal-misa');

        await expect(
            page.getByRole('heading', { name: 'Jadwal Misa', exact: true }),
        ).toBeVisible();

        for (const label of [
            'Jadwal rutin aktif',
            'Lokasi aktif',
            'Misa khusus mendatang',
            'Notice sedang tampil',
        ]) {
            await expect(page.getByText(label)).toBeVisible();
        }

        const shortcuts = page.getByRole('navigation', {
            name: 'Bagian modul Jadwal Misa',
        });

        await expect(
            shortcuts.getByRole('link', { name: 'Lokasi' }),
        ).toBeVisible();
        await expect(
            shortcuts.getByRole('link', { name: 'Misa Khusus' }),
        ).toBeVisible();
        await expect(
            shortcuts.getByRole('link', { name: 'Pemberitahuan' }),
        ).toBeVisible();
    });

    test('form lokasi memvalidasi dan menampilkan pesan bahasa Indonesia', async ({
        page,
    }) => {
        await page.goto('/admin/lokasi/tambah');
        await page.getByRole('button', { name: 'Simpan lokasi' }).click();

        // The sentence comes from lang/id/validation.php: the message is
        // ":attribute wajib diisi." and attributes.name is "Nama". The form
        // label "Nama lokasi" is not what a visitor is told.
        await expect(page.getByText('Nama wajib diisi.')).toBeVisible();
        await expect(page.getByText('Slug wajib diisi.')).toBeVisible();
    });

    test('koordinat setengah terisi ditolak', async ({ page }) => {
        // A half-filled pair is a map link pointing at open ocean.
        await page.goto('/admin/lokasi/tambah');

        await page.getByLabel('Nama lokasi').fill('Koordinat Setengah');
        await page.getByLabel('Slug').fill('koordinat-setengah');
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

    test('jadwal duplikat ditolak dengan pesan yang jelas', async ({
        page,
    }) => {
        test.slow();

        await buatLokasi(page, 'Lokasi Duplikat E2E', 'lokasi-duplikat-e2e');

        for (let attempt = 0; attempt < 2; attempt += 1) {
            await page.goto('/admin/jadwal-misa/tambah');

            await page
                .getByLabel('Lokasi')
                .selectOption({ label: 'Lokasi Duplikat E2E' });
            await page.getByLabel('Hari').selectOption({ label: 'Rabu' });
            await page.getByLabel('Jam mulai').fill('09:15');
            await page
                .getByLabel('Nama misa')
                .fill(`Misa Duplikat ${attempt + 1}`);
            await page.getByRole('button', { name: 'Simpan jadwal' }).click();
        }

        // AC-MASS-7: a sentence, not a validation failure and not a 500.
        await expect(
            page.getByText(
                'Sudah ada jadwal pada lokasi, hari, dan jam tersebut.',
            ),
        ).toBeVisible();
    });

    test('pemberitahuan disimpan dan tampil sebagai daftar', async ({
        page,
    }) => {
        await page.goto('/admin/pemberitahuan-jadwal/tambah');

        await page.getByLabel('Judul').fill('Ibadat syukur E2E');
        await page
            .getByLabel('Pesan')
            .fill('Misa syukur heeft plaatsgevonden.');

        // starts_at is required (D-34 section 3), so an empty form saves
        // nothing at all.
        await page
            .getByRole('button', { name: 'Simpan pemberitahuan' })
            .click();
        await expect(page.getByText(/wajib diisi/)).toBeVisible();
        await expect(page).toHaveURL(/\/admin\/pemberitahuan-jadwal\/tambah$/);

        await page.getByLabel('Mulai tampil').fill('2026-01-01T08:00');

        // One select, one target. Two at once is not something this form can
        // express, which is why the server-side rule that rejects it lives in
        // ScheduleNoticeTest rather than here.
        const target = page.getByLabel('Target');
        const options = await target.locator('option').all();
        expect(options.length).toBeGreaterThan(1);

        await target.selectOption(await options[1].getAttribute('value'));
        await page
            .getByRole('button', { name: 'Simpan pemberitahuan' })
            .click();

        await expect(page).toHaveURL(/\/admin\/pemberitahuan-jadwal$/);
        await expect(
            page.getByRole('cell', { name: 'Ibadat syukur E2E' }),
        ).toBeVisible();
    });

    test('halaman admin menolak tamu', async ({ page, context }) => {
        await context.clearCookies();

        await page.goto('/admin/jadwal-misa');

        await expect(page).toHaveURL(/\/admin\/login$/);
    });
});
