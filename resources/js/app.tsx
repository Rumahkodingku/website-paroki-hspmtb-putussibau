import { createInertiaApp } from '@inertiajs/react';
import type { ComponentType } from 'react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import PublicLayout from '@/layouts/public-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/**
 * Pages live at `features/<audience>/<feature>/pages/`, and the Inertia name is
 * derived from that path by `pageName()` below. Two shapes:
 *
 *   features/admin/akun/pages/profile.tsx      -> 'admin/akun/profile'
 *   features/admin/dashboard/pages/index.tsx    -> 'admin/dashboard'
 *   features/public/auth/pages/login.tsx       -> 'public/auth/login'
 *   features/public/beranda/pages/index.tsx    -> 'public/beranda'
 *
 * `index.tsx` means the feature itself rather than a page named "index", which
 * is what keeps the file path from repeating the URL for single-page features.
 *
 * This resolver is written by hand because the plugin cannot do it.
 * `@inertiajs/vite` injects its own resolver when `createInertiaApp` has neither
 * a `pages` nor a `resolve` property, and that injected version hard-codes two
 * directories — `./pages` and `./Pages`. Its `pages: '...'` option does not help
 * either: the glob it builds does accept a wildcard, but the lookup key it
 * builds is a literal string with a `*` in it, which never matches a key in the
 * glob map. So a nested layout needs this.
 *
 * The index is built once at module load, not per navigation.
 *
 * App\Support\PageChunk applies the same rule on the PHP side, for the Blade
 * template that preloads the page chunk. Both sides read the filesystem, so
 * there is no name-to-path table to keep in step.
 */
const pageModules = import.meta.glob<{ default: ComponentType }>(
    './features/*/*/pages/**/*.tsx',
);

/**
 * The Inertia name for a globbed page path.
 *
 * Returns null for a path the rule does not cover — a page file placed outside
 * the two-level shape, say — so a malformed entry is skipped rather than
 * registered under a name that looks valid and points at the wrong file.
 */
function pageName(path: string): string | null {
    // './features/admin/akun/pages/profile.tsx' -> ['admin','akun','pages','profile.tsx']
    const segments = path.replace(/^\.\/features\//, '').split('/');

    if (segments.length < 4 || segments[2] !== 'pages') {
        return null;
    }

    const [audience, feature] = segments;
    const file = (segments[segments.length - 1] ?? '').replace(/\.tsx$/, '');
    const nested = segments.slice(3, -1);

    return file === 'index'
        ? `${audience}/${feature}`
        : [audience, feature, ...nested, file].join('/');
}

const pagesByName = Object.fromEntries(
    Object.entries(pageModules).flatMap(([path, load]) => {
        const name = pageName(path);

        return name === null ? [] : [[name, load] as const];
    }),
) as Record<string, () => Promise<{ default: ComponentType }>>;

void createInertiaApp({
    resolve: async (name: string) => {
        const load = pagesByName[name];

        if (!load) {
            throw new Error(
                `Page "${name}" was not found. Pages live under ` +
                    'features/<audience>/<feature>/pages/, and the name is the ' +
                    'path below that pages directory, with index.tsx standing ' +
                    'for the feature itself.',
            );
        }

        // Returns Promise<ComponentType>, not Promise<{ default }>. Inertia's
        // ComponentResolver accepts a module object synchronously or a component
        // asynchronously, but not a promise of a module object — which is the
        // one combination that looks reasonable and does not type-check.
        return load().then((module) => module.default);
    },
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            // Guest entry point. Wrapped in PublicLayout so the parish navbar
            // and footer have a real page to render against.
            case name === 'public/beranda':
                return PublicLayout;
            // Error pages always use the public layout, including errors that
            // happen inside the admin area. Roadmap section 26 allows an admin
            // shell "if the context fits", and this sidebar does not: ten of
            // its twelve entries are placeholder links to routes that do not
            // exist yet, so it would be navigation to nowhere on the one page
            // where the visitor most needs a single obvious way out.
            case name === 'public/error':
                return PublicLayout;
            // Authentication pages live under public/auth (see docs/DECISIONS.md
            // D-08) but are rendered by Fortify, not by our routes.
            case name.startsWith('public/auth/'):
                return AuthLayout;
            case name.startsWith('admin/akun/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        // ink-muted-80 from DESIGN.md; the default was an unnamed neutral.
        color: '#475467',
    },
});

// This will set light / dark mode on load...
initializeTheme();
