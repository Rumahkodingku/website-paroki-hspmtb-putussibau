import { execFileSync } from 'node:child_process';
import { expect, test } from '@playwright/test';
import { e2eEnvironment } from './support/environment';

const admin = e2eEnvironment();

/**
 * Only used to build the expected logout landing URL: toHaveURL wants a full
 * URL, and the suite's baseURL lives in playwright.config.ts rather than in a
 * module these specs import.
 */
const baseURL = 'http://127.0.0.1:8000';

/*
 * Locators target the input elements by name rather than by label.
 *
 * getByLabel('Password') is ambiguous on this page and resolves to two
 * elements: the field, and the visibility toggle, whose aria-label is "Show
 * password". Playwright matches labels as a case-insensitive substring, so the
 * toggle matches too, and the locator throws a strict-mode violation instead of
 * filling the field. The email field is unaffected only because the toggle says
 * "password" and not "address".
 */
const emailField = (page: import('@playwright/test').Page) =>
    page.locator('input[name="email"]');

const passwordField = (page: import('@playwright/test').Page) =>
    page.locator('input[name="password"]');

const loginButton = (page: import('@playwright/test').Page) =>
    page.getByTestId('login-button');

/**
 * These specs cover the part of the application where the three middleware
 * layers, the session and the RBAC grant all have to agree: an admin page that
 * a guest should never see, and a login that has to actually produce a session.
 *
 * They exercise a real browser against a real server, so unlike the Vitest suite
 * they prove the Inertia wiring and the Blade asset tags resolve — a 500 from a
 * missing Vite manifest fails here exactly as it would for a user.
 */
test.describe('autentikasi admin', () => {
    test.beforeEach(() => {
        /*
         * Fortify throttles the login route, and the limiter lives in the cache,
         * which here is the database — state that every test in a run shares.
         * Without this, the wrong-password test below would lock out the correct
         * login in the tests after it, and repeated local runs would keep the
         * lockout in place until the limiter window expired.
         *
         * Resetting it costs no coverage: throttling itself is asserted in
         * tests/Feature/Auth/AuthenticationTest.php. What matters here is only
         * that these tests do not depend on each other's order.
         */
        execFileSync('php', ['artisan', 'cache:clear'], {
            env: { ...process.env, DB_DATABASE: admin.database },
            stdio: 'ignore',
        });
    });

    test('halaman admin mengalihkan tamu ke halaman masuk', async ({
        page,
    }) => {
        await page.goto('/admin');

        await expect(page).toHaveURL(/\/admin\/login$/);

        // The redirect must land on a real page, not on an error. Checking the
        // URL alone would pass even if the login screen threw while rendering.
        await expect(loginButton(page)).toBeVisible();
    });

    test('halaman masuk menampilkan email dan kata sandi', async ({ page }) => {
        await page.goto('/admin/login');

        await expect(emailField(page)).toBeVisible();
        await expect(passwordField(page)).toHaveAttribute('type', 'password');
        await expect(loginButton(page)).toBeVisible();
    });

    test('masuk dengan kredensial yang benar membuka dasbor', async ({
        page,
    }) => {
        await page.goto('/admin/login');

        await emailField(page).fill(admin.email);
        await passwordField(page).fill(admin.password);
        await loginButton(page).click();

        await expect(page).toHaveURL(/\/admin$/);

        // The seeded account is visible in the sidebar, which proves the shared
        // auth.user prop arrived rather than just that a redirect happened.
        await expect(page.getByTestId('sidebar-menu-button')).toContainText(
            'Super Admin E2E',
        );
    });

    test('masalah dengan kata sandi ditolak dan tidak memberi sesi', async ({
        page,
    }) => {
        await page.goto('/admin/login');

        await emailField(page).fill(admin.email);
        await passwordField(page).fill('kata-sandi-yang-salah');
        await loginButton(page).click();

        await expect(
            page.getByText(
                'Email atau kata sandi tidak cocok dengan data kami.',
            ),
        ).toBeVisible();

        await expect(page).toHaveURL(/\/admin\/login$/);

        // And the session really is absent, rather than merely unredirected.
        await page.goto('/admin');
        await expect(page).toHaveURL(/\/admin\/login$/);
    });

    test('keluar menutup sesi dan mengunci lagi halaman admin', async ({
        page,
    }) => {
        await page.goto('/admin/login');

        await emailField(page).fill(admin.email);
        await passwordField(page).fill(admin.password);
        await loginButton(page).click();
        await expect(page).toHaveURL(/\/admin$/);

        // The logout control lives in a Radix dropdown, so the trigger has to be
        // opened before the item inside it can be clicked.
        await page.getByTestId('sidebar-menu-button').click();
        await page.getByTestId('logout-button').click();

        // Landing on the public home page rather than on the login form is the
        // documented behaviour: fortify.home is /admin for signing in, but the
        // logout response redirects to the home route, which is /. See the
        // `users can logout` case in tests/Feature/Auth/AuthenticationTest.php.
        await expect(page).toHaveURL(new RegExp(`${baseURL}/$`));

        // The session is what actually matters, so check that rather than
        // trusting the landing page: an admin URL must now bounce to login.
        await page.goto('/admin');
        await expect(page).toHaveURL(/\/admin\/login$/);
    });
});
