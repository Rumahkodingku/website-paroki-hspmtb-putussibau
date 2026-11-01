import { expect, test } from '@playwright/test';
import { pilih } from './support/admin-forms';
import { resetLivestreamFixtures } from './support/livestreaming';
import { resetLoginRateLimit, signIn } from './support/session';

/*
 * Livestreaming — the admin curation flow and the public surfaces.
 *
 * The spec deliberately does not click the YouTube player. The iframe is a
 * third-party request and the player itself is covered by Vitest; what matters
 * here is that the right record reaches the homepage, disappears when it ends,
 * and reappears in the archive only after the replay is marked available.
 */

const TITLE = 'E2E Livestream Minggu';
const YOUTUBE_URL = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

// The archive filters sit near the fold. A taller viewport keeps Radix's
// popper content inside the viewport so its options are clickable.
test.use({ viewport: { width: 1280, height: 1000 } });

test.describe('livestreaming', () => {
    test.beforeEach(async ({ page }) => {
        resetLivestreamFixtures();
        resetLoginRateLimit();
        await signIn(page);
    });

    test('menerbitkan, memulai, mengakhiri, dan mengarsipkan siaran', async ({
        page,
    }) => {
        await page.goto('/admin/livestreaming/tambah');
        await page.locator('#livestream-title').fill(TITLE);
        await page.locator('#livestream-youtube-url').fill(YOUTUBE_URL);
        await pilih(page, 'Kategori', 'Liturgi');
        await pilih(page, 'Lokasi', 'Gereja Paroki E2E');
        await page.getByRole('button', { name: 'Simpan', exact: true }).click();

        await expect(page).toHaveURL(/\/admin\/livestreaming\/\d+\/ubah$/);

        // Publish through the ordinary form field.
        await pilih(page, 'Status Publikasi', 'Terbit', false);
        await page.getByRole('button', { name: 'Simpan', exact: true }).click();
        await expect(page).toHaveURL(/\/admin\/livestreaming\/\d+\/ubah$/);

        // Start from the list; the confirmation dialog is part of the flow.
        await page.goto('/admin/livestreaming');
        const row = page.getByRole('row', { name: new RegExp(TITLE) });
        await row.getByRole('button', { name: 'Mulai siaran' }).click();
        await expect(page.getByRole('dialog')).toBeVisible();
        await page
            .getByRole('dialog')
            .getByRole('button', { name: 'Mulai Siaran', exact: true })
            .click();

        await expect(row.getByText('Sedang Live')).toBeVisible();

        // The homepage section appears only while the record is live.
        await page.goto('/');
        await expect(
            page.getByRole('heading', { name: 'Ikuti Misa Secara Online' }),
        ).toBeVisible();
        await expect(page.getByText('SEDANG LIVE')).toBeVisible();
        await expect(
            page
                .getByTestId('home-livestream-section')
                .getByText('Gereja Paroki E2E'),
        ).toBeVisible();

        await page.goto('/livestreaming');
        await expect(
            page.getByRole('heading', { name: 'Arsip Livestreaming' }),
        ).toBeVisible();
        await expect(
            page.getByRole('button', { name: `Putar ${TITLE}` }),
        ).toBeVisible();
        await expect(
            page.getByText('Gereja Paroki E2E').first(),
        ).toBeVisible();

        // End it.
        await page.goto('/admin/livestreaming');
        const liveRow = page.getByRole('row', { name: new RegExp(TITLE) });
        await liveRow.getByRole('button', { name: 'Akhiri siaran' }).click();
        await page
            .getByRole('dialog')
            .getByRole('button', { name: 'Akhiri Siaran', exact: true })
            .click();

        await expect(liveRow.getByText('Selesai')).toBeVisible();

        // The homepage goes back to its no-live state, with no placeholder.
        await page.goto('/');
        await expect(
            page.getByRole('heading', { name: 'Ikuti Misa Secara Online' }),
        ).toHaveCount(0);

        // A replay is not assumed; the admin marks it after the fact.
        await page.goto('/admin/livestreaming');
        await page
            .getByRole('row', { name: new RegExp(TITLE) })
            .getByRole('link', { name: 'Ubah' })
            .click();
        await page.getByLabel('Rekaman Tersedia').click();
        await page.getByRole('button', { name: 'Simpan', exact: true }).click();
        await expect(page).toHaveURL(/\/admin\/livestreaming\/\d+\/ubah$/);

        // The recording is now in the public archive and can be filtered.
        await page.goto('/livestreaming');
        await expect(page.getByRole('heading', { name: TITLE })).toBeVisible();

        await pilih(page, 'Filter kategori', 'Liturgi');
        await expect(page).toHaveURL(/kategori=liturgi/);
        await expect(page.getByRole('heading', { name: TITLE })).toBeVisible();

        await pilih(
            page,
            'Filter tahun',
            String(new Date().getFullYear()),
        );
        await expect(page).toHaveURL(/tahun=\d{4}/);
        await expect(page.getByRole('heading', { name: TITLE })).toBeVisible();

        await page
            .getByPlaceholder('Cari rekaman...')
            .fill('E2E Livestream Minggu');
        await expect(page).toHaveURL(/search=E2E/);
        await expect(page.getByRole('heading', { name: TITLE })).toBeVisible();
    });

    test('menampilkan menu Livestreaming di navbar dan footer publik', async ({
        page,
    }) => {
        await page.goto('/');

        await expect(
            page
                .getByRole('link', { name: 'Livestreaming', exact: true })
                .first(),
        ).toHaveAttribute('href', '/livestreaming');

        await expect(
            page
                .getByRole('contentinfo')
                .getByRole('link', { name: 'Livestreaming', exact: true }),
        ).toHaveAttribute('href', '/livestreaming');
    });
});
