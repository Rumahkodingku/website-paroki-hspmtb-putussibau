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
): Promise<void> {
    await page.goto('/admin/master-data/lokasi/tambah');

    await page.getByLabel('Nama lokasi').fill(nama);
    await page.getByLabel('Alamat').fill('Alamat pengujian');
    await page.getByRole('button', { name: 'Simpan lokasi' }).click();

    await expect(page).toHaveURL(/\/admin\/master-data\/lokasi$/);
}

/**
 * Set a TimePicker (a popover with two Radix selects) by its label.
 *
 * TimePicker is a button, not an input, so fill() cannot touch it. The hour and
 * minute selects live inside the popover that the button opens.
 */
export async function pilihWaktu(
    page: Page,
    label: string,
    value: string,
): Promise<void> {
    const [hour, minute] = value.split(':');

    await page.getByLabel(label, { exact: true }).click();

    const popover = page.locator('[data-slot="popover-content"]').last();
    const selects = popover.getByRole('combobox');

    await selects.nth(0).click();
    await page.getByRole('option', { name: hour, exact: true }).click();

    await selects.nth(1).click();
    await page.getByRole('option', { name: minute, exact: true }).click();

    await page.keyboard.press('Escape');
}

/**
 * Pick a date from a DatePicker popover.
 *
 * The calendar renders every visible day with a `data-day` attribute. The
 * selected day is deliberately not fixed to a known month: these forms only
 * require a valid date, and pinning one would make the test break in a future
 * month.
 */
export async function pilihTanggal(page: Page, label: string): Promise<void> {
    await page.getByLabel(label, { exact: true }).click();

    const day = page.locator('[data-slot="popover-content"] [data-day]').nth(15);
    await day.click();

    await page.keyboard.press('Escape');
}
