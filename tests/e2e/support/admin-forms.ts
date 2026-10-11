import { expect, type Page } from '@playwright/test';

/*
 * The two interactions the admin forms have in common, shared by more than one
 * spec file.
 *
 * They lived in mass-schedule.spec.ts until Master Data moved out of that
 * module (D-48). They are not about the mass schedule: `pilih` is how every
 * Radix ChoiceSelect is driven, and `buatLokasi` creates a location, which the
 * agenda and the mass schedule both point at. A copy in each spec would be a
 * copy that drifts, and the failure it causes — a locator that matches nothing
 * because a label was reworded in one file only — reads as a broken form.
 */

/**
 * Choose one option in a ChoiceSelect.
 *
 * The admin selects are Radix popovers, not <select> elements: the trigger is
 * a button and the options only exist while the list is open. Playwright's
 * selectOption() has nothing to act on, so every pick is open-then-click.
 *
 * The trigger is addressed by label. SelectTrigger renders a <button>, which
 * is a labelable element, so the form's <Label htmlFor> still names it.
 */
export async function pilih(
    page: Page,
    label: string,
    option: string,
): Promise<void> {
    await page.getByLabel(label, { exact: true }).click();
    await page.getByRole('option', { name: option, exact: true }).click();

    // Wait for the list to close, or the next click lands on an overlay.
    await expect(
        page.getByRole('option', { name: option, exact: true }),
    ).toBeHidden();
}

/** Fill the location form and save, ending on the Master Data location list. */
export async function buatLokasi(
    page: Page,
    nama: string,
    slug: string,
): Promise<void> {
    await page.goto('/admin/master-data/lokasi/tambah');

    await page.getByLabel('Nama lokasi').fill(nama);
    await page.getByLabel('Slug').fill(slug);
    await page.getByLabel('Alamat').fill('Alamat pengujian');
    await page.getByRole('button', { name: 'Simpan lokasi' }).click();

    await expect(page).toHaveURL(/\/admin\/master-data\/lokasi$/);
}
