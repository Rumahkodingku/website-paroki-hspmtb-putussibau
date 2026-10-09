import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

/**
 * One crumb.
 *
 * `href` is optional because the last crumb is the current page and is rendered
 * as plain text, not a link - a page that links to itself invites a pointless
 * round trip and reads as a mistake to a screen reader. It was required while
 * only the admin area used this, because every admin trail happened to end on a
 * linkable page; the first public breadcrumb, whose last crumb is the page the
 * visitor is already on, made the mismatch visible.
 */
export type BreadcrumbItem = {
    title: string;
    href?: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
};
