import { defineConfig, devices } from '@playwright/test';
import { e2eEnvironment } from './tests/e2e/support/environment';

const port = 8000;
const baseURL = `http://127.0.0.1:${port}`;

const environment = e2eEnvironment();

/**
 * End-to-end configuration.
 *
 * There is no `.env` juggling here: AppE2ePrepare (app:e2e:prepare) rebuilds the
 * test database first and writes tests/e2e/.auth.json, and `npm run e2e` runs
 * the two in that order. The specs read their credentials from that file, so
 * they log into exactly the account that was seeded.
 *
 * @see docs/DECISIONS.md D-29
 */
export default defineConfig({
    testDir: './tests/e2e',

    /**
     * php artisan serve answers one request at a time. Parallel workers would
     * queue behind each other and the symptom would be random timeouts on a
     * suite that is not actually testing anything timing-related.
     */
    workers: 1,

    fullyParallel: false,

    forbidOnly: Boolean(process.env.CI),

    // No retries locally: a first-run failure should be a real failure, and a
    // pass on the second attempt would hide it rather than explain it.
    retries: process.env.CI ? 1 : 0,

    reporter: process.env.CI
        ? [['github'], ['html', { open: 'never' }]]
        : [['list']],

    use: {
        baseURL,

        /**
         * The components in this codebase mark test hooks with data-test, not
         * Playwright's default data-testid. Aligning the attribute here means the
         * existing hooks (login-button, sidebar-menu-button, logout-button) work
         * with getByTestId instead of every spec spelling out [data-test="..."].
         */
        testIdAttribute: 'data-test',

        /**
         * `php artisan serve` is not a production server and there is no
         * client-side router, so every navigation is a full page load. Without
         * this the first assertion after a click races the Inertia swap.
         */
        trace: 'on-first-retry',

        screenshot: 'only-on-failure',
    },

    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],

    webServer: {
        command: `php artisan serve --host=127.0.0.1 --port=${port}`,

        /**
         * The one setting that makes the whole suite agree with itself.
         *
         * `php artisan serve` reads its connection from the environment, and on
         * a developer machine that is the database in .env — not the one
         * AppE2ePrepare just rebuilt. Without this, login would submit against a
         * database with no such account and fail as a plain credential mismatch,
         * which points at the login form instead of at the wiring. Dotenv does
         * not overwrite a real environment variable, so this wins.
         */
        env: {
            DB_DATABASE: environment.database,

            /**
             * Force debug off even though a development machine has it on.
             *
             * The custom error pages are only rendered when config('app.debug')
             * is false: the respond() callback in bootstrap/app.php returns the
             * original response early otherwise, and Laravel's own debug page
             * takes over. Without this the 404 specs would pass while asserting
             * on that debug page — the status code is right and the wording is
             * somebody else's — so they would report the error pages as covered
             * while proving nothing about them.
             */
            APP_DEBUG: 'false',
        },

        // /up is Laravel's health endpoint. It answers without touching the
        // database or the Vite manifest, which makes it a fair readiness probe:
        // waiting on a real page instead would report the server as broken
        // whenever the frontend simply has not been built yet.
        url: `${baseURL}/up`,

        reuseExistingServer: !process.env.CI,

        timeout: 120_000,
    },
});
