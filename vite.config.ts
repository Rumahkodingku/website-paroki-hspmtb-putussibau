import { fileURLToPath } from 'node:url';
import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import babel from '@rolldown/plugin-babel';
import tailwindcss from '@tailwindcss/vite';
import react, { reactCompilerPreset } from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        /*
         * Omitted while Vitest runs.
         *
         * laravel-vite-plugin refuses to load when CI is set, on the assumption
         * that a CI environment means somebody started a dev server instead of
         * building assets. Vitest builds a server internally in order to
         * transform modules and never serves HMR, so the check is a false
         * positive there — it failed the ci job the first time this suite ran in
         * GitHub Actions.
         *
         * Omitting the plugin is narrower than setting
         * LARAVEL_BYPASS_ENV_CHECK in the workflow: that would switch the guard
         * off for the production build and any future step as well. Nothing in a
         * unit test needs Laravel's asset resolution.
         */
        ...(process.env.VITEST
            ? []
            : [
                  laravel({
                      input: ['resources/css/app.css', 'resources/js/app.tsx'],
                      refresh: true,
                      fonts: [
                          bunny('Instrument Sans', {
                              weights: [400, 500, 600],
                          }),
                      ],
                  }),
              ]),
        inertia(),
        react(),
        babel({
            presets: [reactCompilerPreset()],
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ]),
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    /*
     * The `@/` alias is declared here rather than left to laravel-vite-plugin,
     * which is what actually provided it before. Two reasons.
     *
     * It is a project convention that belongs in the project's own config, not a
     * side effect of a Laravel plugin: tsconfig.json already maps `@/*` for the
     * editor and for tsc, and this is the Vite half of the same mapping. Without
     * it the alias silently disappears the moment that plugin is not loaded.
     *
     * And the plugin cannot be loaded for unit tests, because it refuses to
     * start in CI on the assumption that a CI environment means somebody launched
     * a dev server. Vitest builds a server internally to transform modules and
     * never serves HMR, so the check is a false positive there.
     */
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        // All frontend tests live under tests/, mirroring the Pest suites:
        // tests/js for Vitest, tests/e2e for Playwright.
        include: ['tests/js/**/*.{test,spec}.?(c|m)[jt]s?(x)'],
        // Vitest's own default glob is **/*.spec.*, which would happily collect
        // the Playwright specs sitting in the same tests/ tree and try to run
        // them as unit tests. The include above already cannot match them; this
        // keeps that true if someone ever widens it.
        exclude: ['**/node_modules/**', '**/vendor/**', 'tests/e2e/**'],
        // happy-dom over jsdom: it is the default for this toolchain and is
        // markedly faster, which matters because `composer ci:check` runs this
        // on every commit via pre-commit.
        environment: 'happy-dom',
        // Deliberately off. Explicit imports make it obvious that test APIs come
        // from vite-plus/test rather than from a global, and explicit
        // afterEach(cleanup) below is what keeps React state from leaking.
        globals: false,
        setupFiles: ['./tests/js/setup.ts'],
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            '*.md',
            'docs/**',
            '.codex/**',
            'skills-lock.json',
            'composer.json',
            'public/**',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
