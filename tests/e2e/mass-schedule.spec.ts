import { expect, test } from '@playwright/test';
import {
    buatLokasi,
    pilih,
    pilihTanggal,
    pilihWaktu,
} from './support/admin-forms';
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

    });

    test('jadwal yang dibuat admin tampil di halaman publik', async ({
        page,
    }) => {
        test.slow();

        resetLoginRateLimit();
        await signIn(page);

        await buatLokasi(page, LOKASI);

        const row = (name: string) =>
            page.getByRole('row').filter({ hasText: name });

        // MASS-07, first half: switching a location off takes it off the public
        // page. The edit form is the only way to do it - there is no delete and
        // no toggle endpoint (D-34, D-37).
        await row(LOKASI).getByRole('link', { name: 'Ubah' }).click();
        await expect(page).toHaveURL(
            /\/admin\/master-data\/lokasi\/\d+\/ubah$/,
        );
        await page.getByRole('switch', { name: /aktif/i }).click();
        await page.getByRole('button', { name: 'Simpan perubahan' }).click();
        await expect(page).toHaveURL(/\/admin\/master-data\/lokasi$/);

        await page.goto('/jadwal-misa');
        await expect(page.getByText(LOKASI)).toHaveCount(0);

        // Back on, so the schedule below has somewhere to live. The admin list
        // keeps deactivated locations visible - otherwise switching one off
        // would be the one edit a secretary could not undo.
        await page.goto('/admin/master-data/lokasi');
        await row(LOKASI).getByRole('link', { name: 'Ubah' }).click();
        await page.getByRole('switch', { name: /aktif/i }).click();
        await page.getByRole('button', { name: 'Simpan perubahan' }).click();
        await expect(page).toHaveURL(/\/admin\/master-data\/lokasi$/);

        await page.goto('/admin/jadwal-misa/misa-rutin/tambah');

        await pilih(page, 'Lokasi', LOKASI);
        await pilih(page, 'Hari', 'Minggu');
        await pilihWaktu(page, 'Jam mulai', '07:30');
        await page.getByLabel('Nama misa').fill('Misa Minggu E2E');
        await page.getByRole('button', { name: 'Simpan jadwal' }).click();

        await expect(page).toHaveURL(/\/admin\/jadwal-misa\/misa-rutin$/);

        // AC-MASS-3: the public page reads this on the very next request.
        await page.goto('/jadwal-misa');

        await expect(
            page
                .getByTestId('mass-schedule-card')
                .getByText('Misa Minggu E2E')
                .last(),
        ).toBeVisible();
        await expect(page.getByText('07.30 WIB').last()).toBeVisible();
    });

    test('filter lokasi dan hari mengubah daftar yang terlihat', async ({
        page,
    }) => {
        await page.goto('/jadwal-misa?hari=0');

        await expect(
            page.getByLabel('Filter berdasarkan hari'),
        ).toContainText('Minggu');

        await page.getByLabel('Filter berdasarkan lokasi').click();
        await page.getByRole('option').nth(1).click();

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

    test('URL ringkasan mengalihkan ke tabel, bukan 404', async ({ page }) => {
        // The summary page is gone. The URL is not: it is the one PRD 5.2
        // names, and seven breadcrumbs in the module point at it — so a 404
        // here would be a dead end the parish's own navigation leads to.
        await page.goto('/admin/jadwal-misa');

        await expect(page).toHaveURL(/\/admin\/jadwal-misa\/misa-rutin$/);

        // It has to arrive at the table, not at a page that merely kept the URL.
        await expect(
            page.getByRole('heading', { name: 'Misa Rutin' }),
        ).toBeVisible();
        await expect(
            page.getByRole('link', { name: 'Tambah misa rutin' }),
        ).toBeVisible();
    });

    test('sub-menu sidebar membawa ketiga bagian modul', async ({ page }) => {
        // Outside the module, so the group starts shut: this test is about the
        // disclosure, not about the auto-open.
        await page.goto('/admin');

        const sidebar = page.getByRole('navigation', {
            name: 'Navigasi utama',
        });
        // Semua item sekarang tampil langsung (tanpa dropdown); tidak perlu
        // membuka grup terlebih dahulu.

        // Tiga, bukan empat. Lokasi pindah ke grup Master Data pada Fase 05:
        // sebuah ruangan dipakai kegiatan agenda, bukan hanya misa, jadi
        // listing-nya di bawah Jadwal Misa mengklaim kepemilikan yang tidak
        // dimilikinya.
        for (const [title, href] of [
            ['Misa Rutin', '/admin/jadwal-misa/misa-rutin'],
            ['Misa Khusus', '/admin/jadwal-misa/misa-khusus'],
            ['Pemberitahuan', '/admin/jadwal-misa/pemberitahuan-jadwal'],
        ] as const) {
            await expect(
                sidebar.getByRole('link', { name: title, exact: true }),
            ).toHaveAttribute('href', href);
        }

        await sidebar
            .getByRole('link', { name: 'Misa Rutin', exact: true })
            .click();

        await expect(page).toHaveURL(/\/admin\/jadwal-misa\/misa-rutin$/);

    });

    test('Berita dan Agenda adalah tautan langsung ke daftarnya', async ({
        page,
    }) => {
        await page.goto('/admin');

        const sidebar = page.getByRole('navigation', {
            name: 'Navigasi utama',
        });

        // Keduanya datar, bukan grup dengan satu anak: grup satu anak adalah
        // tautan dengan dua klik tambahan, dan /admin/berita memang hanya
        // redirect ke daftar.
        await expect(
            sidebar.getByRole('link', { name: 'Berita', exact: true }),
        ).toHaveAttribute('href', '/admin/berita/daftar');

        await expect(
            sidebar.getByRole('link', { name: 'Agenda', exact: true }),
        ).toHaveAttribute('href', '/admin/agenda/daftar');
    });

    test('klik kolom mengurutkan lewat URL, bukan di browser', async ({
        page,
    }) => {
        await buatLokasi(page, 'Lokasi Urut E2E');

        await page.goto('/admin/jadwal-misa/misa-rutin/tambah');
        await pilih(page, 'Lokasi', 'Lokasi Urut E2E');
        await pilih(page, 'Hari', 'Rabu');
        await pilihWaktu(page, 'Jam mulai', '12:00');
        await page.getByLabel('Nama misa').fill('Misa Urut Zebra');
        await page.getByRole('button', { name: 'Simpan jadwal' }).click();
        await expect(page).toHaveURL(/\/admin\/jadwal-misa\/misa-rutin$/);

        await page.goto('/admin/jadwal-misa/misa-rutin');
        await page.getByRole('button', { name: /Nama misa/ }).click();

        // The claim: sorting is a visit, so it is visible in the URL and in the
        // row order that comes back from the server.
        await expect(page).toHaveURL(/sort=name/);
        await expect(page).toHaveURL(/direction=asc/);
    });

    test('jadwal duplikat ditolak dengan pesan yang jelas', async ({
        page,
    }) => {
        test.slow();

        await buatLokasi(page, 'Lokasi Duplikat E2E');

        for (let attempt = 0; attempt < 2; attempt += 1) {
            await page.goto('/admin/jadwal-misa/misa-rutin/tambah');

            await pilih(page, 'Lokasi', 'Lokasi Duplikat E2E');
            await pilih(page, 'Hari', 'Rabu');
            await pilihWaktu(page, 'Jam mulai', '09:15');
            await page
                .getByLabel('Nama misa')
                .fill(`Misa Duplikat ${attempt + 1}`);
            await page.getByRole('button', { name: 'Simpan jadwal' }).click();
        }

        // AC-MASS-7: a sentence, not a validation failure and not a 500.
        await expect(
            page
                .getByRole('main')
                .getByText(
                    'Sudah ada jadwal pada lokasi, hari, dan jam tersebut.',
                )
                .first(),
        ).toBeVisible();
    });

    test('pemberitahuan disimpan dan tampil sebagai daftar', async ({
        page,
    }) => {
        await page.goto('/admin/jadwal-misa/pemberitahuan-jadwal/tambah');

        await page.getByLabel('Judul').fill('Ibadat syukur E2E');
        await page
            .getByLabel('Pesan')
            .fill('Misa syukur heeft plaatsgevonden.');

        // starts_at is required (D-34 section 3), so an empty form saves
        // nothing at all.
        await page
            .getByRole('button', { name: 'Simpan pemberitahuan' })
            .click();
        await expect(
            page.getByRole('main').getByText(/wajib diisi/).first(),
        ).toBeVisible();
        await expect(page).toHaveURL(
            /\/admin\/jadwal-misa\/pemberitahuan-jadwal\/tambah$/,
        );

        await pilihTanggal(page, 'Mulai tampil');

        // One select, one target. Two at once is not something this form can
        // express, which is why the server-side rule that rejects it lives in
        // ScheduleNoticeTest rather than here.
        await page.getByLabel('Target', { exact: true }).click();

        const targetOptions = page.getByRole('option');
        await expect(targetOptions).not.toHaveCount(1);

        // The first entry is the general notice, the rest name a real target.
        const firstTarget = targetOptions.nth(1);
        await firstTarget.click();
        await expect(firstTarget).toBeHidden();
        await page
            .getByRole('button', { name: 'Simpan pemberitahuan' })
            .click();

        await expect(page).toHaveURL(
            /\/admin\/jadwal-misa\/pemberitahuan-jadwal$/,
        );
        await expect(
            page.getByRole('cell', { name: 'Ibadat syukur E2E' }),
        ).toBeVisible();
    });

    test('jadwal misa rutin bisa dihapus', async ({ page }) => {
        await buatLokasi(page, 'Lokasi Jadwal E2E');

        await page.goto('/admin/jadwal-misa/misa-rutin/tambah');

        await pilih(page, 'Lokasi', 'Lokasi Jadwal E2E');
        await pilih(page, 'Hari', 'Kamis');
        await pilihWaktu(page, 'Jam mulai', '11:15');
        await page.getByLabel('Nama misa').fill('Misa Kamis E2E');
        await page.getByRole('button', { name: 'Simpan jadwal' }).click();

        await expect(page).toHaveURL(/\/admin\/jadwal-misa\/misa-rutin$/);
        await expect(
            page.getByRole('cell', { name: 'Misa Kamis E2E' }),
        ).toBeVisible();

        await page.goto('/admin/jadwal-misa/misa-rutin');

        const scheduleRow = page
            .getByRole('row')
            .filter({ hasText: 'Misa Kamis E2E' });

        await scheduleRow.getByRole('button', { name: 'Hapus' }).click();
        await page.getByTestId('confirm-dialog-confirm').click();

        await expect(page).toHaveURL(/\/admin\/jadwal-misa\/misa-rutin$/);
        await expect(
            page.getByRole('cell', { name: 'Misa Kamis E2E' }),
        ).toHaveCount(0);
    });

    test('halaman admin menolak tamu', async ({ page, context }) => {
        await context.clearCookies();

        await page.goto('/admin/jadwal-misa');

        await expect(page).toHaveURL(/\/admin\/login$/);
    });
});
