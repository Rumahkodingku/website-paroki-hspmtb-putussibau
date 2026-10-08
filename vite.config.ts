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
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
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
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
