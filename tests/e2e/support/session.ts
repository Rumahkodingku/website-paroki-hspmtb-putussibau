import { execFileSync } from 'node:child_process';
import { expect, type Page } from '@playwright/test';
import { e2eEnvironment } from './environment';

const environment = e2eEnvironment();

/*
 * Fields are addressed by name rather than by label.
 *
 * getByLabel('Password') is ambiguous on the login page and resolves to two
 * elements: the field, and the visibility toggle, whose aria-label is "Show
 * password". Playwright matches labels as a case-insensitive substring, so the
 * toggle matches too and the locator throws a strict-mode violation instead of
 * filling the field. The email field is unaffected only because the toggle says
 * "password" and not "address".
 */
export const emailField = (page: Page) => page.locator('input[name="email"]');

export const passwordField = (page: Page) =>
    page.locator('input[name="password"]');

export const loginButton = (page: Page) => page.getByTestId('login-button');

/**
 * Clear the login rate limiter.
 *
 * Fortify throttles the login route and the limiter lives in the cache, which
 * here is the database — state that every test in a run shares. Without this,
 * the wrong-password test would lock out the correct login in the tests after
 * it, and repeated local runs would keep the lockout in place until the window
 * expired.
 *
 * Resetting it costs no coverage: throttling itself is asserted in
 * tests/Feature/Auth/AuthenticationTest.php. What matters here is only that the
 * specs do not depend on each other's order, or on how many times the suite has
 * been run today.
 */
export function resetLoginRateLimit(): void {
    execFileSync('php', ['artisan', 'cache:clear'], {
        env: { ...process.env, DB_DATABASE: environment.database },
        stdio: 'ignore',
    });
}

/**
 * Sign in as the seeded Super Admin.
 *
 * Every spec that needs the admin area goes through here so that a change to the
 * login form is a one-file edit rather than one per spec.
 */
export async function signIn(page: Page): Promise<void> {
    await page.goto('/admin/login');

    await emailField(page).fill(environment.email);
    await passwordField(page).fill(environment.password);
    await loginButton(page).click();

    await expect(page).toHaveURL(/\/admin$/);
}
