export type * from './auth';
export type * from './navigation';
export type * from './settings';
export type * from './ui';

import type { Auth } from './auth';

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
};
