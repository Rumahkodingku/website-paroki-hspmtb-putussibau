/*
 * Site-wide types only.
 *
 * A type belongs here when more than one feature needs it. Anything used by a
 * single feature lives next to that feature's components in
 * `features/<name>/types.ts`, so that deleting the feature deletes its contract
 * with it — settings.ts moved out for exactly that reason.
 */
export type * from './auth';
export type * from './navigation';
export type * from './pagination';
export type * from './ui';

import type { Auth } from './auth';

/**
 * Site-wide SEO fallbacks, read server-side from `site_settings`.
 *
 * Every field is nullable and null means "never configured", which is what the
 * Seo component needs in order to omit a tag rather than emit an empty one.
 * An empty meta description is worse than none: crawlers and link previews
 * both treat it as a description.
 */
export type SeoDefaults = {
    /** From APP_URL. Lets Seo absolutise paths without touching `window`. */
    appUrl: string;
    siteName: string | null;
    title: string | null;
    description: string | null;
    /** Relative or absolute path; Seo makes it absolute. */
    ogImage: string | null;
};

/**
 * Props HandleInertiaRequests shares with every page.
 *
 * Kept in one place so a page that needs a shared prop reaches for the same
 * definition instead of declaring its own version of it.
 *
 * `errors` and `flash` are absent on purpose. Inertia supplies `errors` through
 * its own middleware, and flash is delivered by the `flash` DOM event rather
 * than as a page prop, so neither belongs to this contract.
 *
 * @see docs/DECISIONS.md D-21
 */
export type SharedProps = {
    name: string;
    auth: Auth;
    /** App locale, always `id` on the MVP. */
    locale: string;
    /** Storage stays UTC; only this is used to render dates. */
    displayTimezone: string;
    sidebarOpen: boolean;
    seo: SeoDefaults;
};
