import { createInertiaApp } from '@inertiajs/react';
import type { ComponentType } from 'react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth';
import PublicLayout from '@/layouts/public';
import SettingsLayout from '@/layouts/settings/layout';
import { pageName } from '@/lib/page-name';
import { UploadConfig } from '@/components/upload/upload-config';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/**
 * Pages live at `features/<audience>/<feature>/pages/`, and the Inertia name is
 * derived from that path by `pageName()` — which lives in its own module so that
 * a test can import the rule without booting this file's side effects.
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

        return load().then((module) => module.default);
    },
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'public/error':
                return PublicLayout;
            case name.startsWith('public/auth/'):
                return AuthLayout;
            case name.startsWith('public/'):
                return PublicLayout;
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
                <UploadConfig>
                    {app}
                    <Toaster />
                </UploadConfig>
            </TooltipProvider>
        );
    },
    progress: {
        color: '#475467',
    },
});

initializeTheme();
