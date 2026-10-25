import { useId, type ReactNode } from 'react';

import { Breadcrumbs } from '@/components/breadcrumbs';
import { PublicContainer } from '@/components/public-container';
import { Text } from '@/components/text';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export type PublicHeroBannerProps = {
    /** Small contextual label above the page title, e.g. "PROFIL PAROKI". */
    eyebrow?: string;
    /** Main page title. */
    title: string;
    /** Short supporting description beneath the title. */
    description?: string;
    /** Uses the shared site breadcrumb contract. Omit to hide breadcrumbs. */
    breadcrumbs?: BreadcrumbItemType[];
    /** Optional page-level actions, such as a link or button. */
    actions?: ReactNode;
    /** Optional custom content rendered below the description and before actions. */
    children?: ReactNode;
    /** Additional classes for the outer section. */
    className?: string;
    /** Additional classes for the content container. */
    contentClassName?: string;
    /** Heading level for correct document outline. Defaults to h1. */
    headingLevel?: 1 | 2;
    /** Hide the decorative SVG artwork when a page needs a quieter layout. */
    showArtwork?: boolean;
};

function PublicHeroArtwork() {
    return (
        <div
            aria-hidden="true"
            className="pointer-events-none relative mx-auto aspect-4/3 w-full max-w-104 text-primary lg:mx-0 lg:max-w-none"
        >
            <svg
                viewBox="0 0 480 360"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                className="h-full w-full overflow-visible"
                focusable="false"
            >
                <defs>
                    <linearGradient
                        id="hero-art-wash"
                        x1="88"
                        y1="34"
                        x2="384"
                        y2="326"
                        gradientUnits="userSpaceOnUse"
                    >
                        <stop stopColor="currentColor" stopOpacity="0.15" />
                        <stop
                            offset="1"
                            stopColor="currentColor"
                            stopOpacity="0.025"
                        />
                    </linearGradient>
                    <linearGradient
                        id="hero-art-ribbon"
                        x1="152"
                        y1="76"
                        x2="344"
                        y2="300"
                        gradientUnits="userSpaceOnUse"
                    >
                        <stop stopColor="currentColor" stopOpacity="0.75" />
                        <stop
                            offset="1"
                            stopColor="currentColor"
                            stopOpacity="0.18"
                        />
                    </linearGradient>
                </defs>

                <circle cx="258" cy="178" r="142" fill="url(#hero-art-wash)" />
                <circle
                    cx="258"
                    cy="178"
                    r="142"
                    stroke="currentColor"
                    strokeOpacity="0.13"
                />
                <circle
                    cx="258"
                    cy="178"
                    r="116"
                    stroke="currentColor"
                    strokeOpacity="0.11"
                    strokeDasharray="2 7"
                />
                <path
                    d="M258 16V54M258 302V340M96 178H134M382 178H420"
                    stroke="currentColor"
                    strokeOpacity="0.2"
                    strokeLinecap="round"
                />

                <path
                    d="M158 286V168C158 112.772 202.772 68 258 68C313.228 68 358 112.772 358 168V286"
                    stroke="url(#hero-art-ribbon)"
                    strokeWidth="2"
                />
                <path
                    d="M183 286V171C183 129.579 216.579 96 258 96C299.421 96 333 129.579 333 171V286"
                    stroke="currentColor"
                    strokeOpacity="0.35"
                    strokeWidth="1.5"
                />
                <path
                    d="M207 286V174C207 145.833 229.833 123 258 123C286.167 123 309 145.833 309 174V286"
                    fill="currentColor"
                    fillOpacity="0.055"
                    stroke="currentColor"
                    strokeOpacity="0.22"
                    strokeWidth="1.5"
                />

                <path
                    d="M258 145V218M241 163H275"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeOpacity="0.7"
                />
                <circle
                    cx="258"
                    cy="163"
                    r="28"
                    stroke="currentColor"
                    strokeOpacity="0.2"
                />
                <circle
                    cx="258"
                    cy="163"
                    r="4"
                    fill="currentColor"
                    fillOpacity="0.8"
                />

                <path
                    d="M108 286H408"
                    stroke="currentColor"
                    strokeOpacity="0.22"
                    strokeLinecap="round"
                />
                <path
                    d="M145 302H371"
                    stroke="currentColor"
                    strokeOpacity="0.12"
                    strokeLinecap="round"
                />
                <path
                    d="M180 318H336"
                    stroke="currentColor"
                    strokeOpacity="0.1"
                    strokeLinecap="round"
                />

                <circle
                    cx="128"
                    cy="92"
                    r="4"
                    fill="currentColor"
                    fillOpacity="0.45"
                />
                <circle
                    cx="378"
                    cy="110"
                    r="6"
                    stroke="currentColor"
                    strokeOpacity="0.4"
                />
                <circle
                    cx="365"
                    cy="264"
                    r="3"
                    fill="currentColor"
                    fillOpacity="0.55"
                />
                <path
                    d="M92 232L105 232M98.5 225.5V238.5"
                    stroke="currentColor"
                    strokeOpacity="0.35"
                    strokeLinecap="round"
                />
                <path
                    d="M390 66L401 66M395.5 60.5V71.5"
                    stroke="currentColor"
                    strokeOpacity="0.35"
                    strokeLinecap="round"
                />
            </svg>
        </div>
    );
}

/**
 * PublicHeroBanner
 *
 * Global page introduction for the public HSPMTB website. The Clean Editorial
 * layout pairs a clear text column with optional, lightweight inline SVG artwork.
 * It has no photographic/background image and no fixed height, so long Indonesian
 * titles and optional actions remain comfortable on small screens.
 */
export function PublicHeroBanner({
    eyebrow,
    title,
    description,
    breadcrumbs = [],
    actions,
    children,
    className,
    contentClassName,
    headingLevel = 1,
    showArtwork = true,
}: PublicHeroBannerProps) {
    const titleId = useId();
    const Heading: 'h1' | 'h2' = headingLevel === 1 ? 'h1' : 'h2';

    return (
        <section
            aria-labelledby={titleId}
            className={cn('bg-background', className)}
        >
            <PublicContainer
                className={cn(
                    'flex flex-col gap-6 py-10 sm:py-10',
                    contentClassName,
                )}
            >
                <div
                    className={cn(
                        'grid items-center gap-8 lg:grid-cols-[minmax(0,1.2fr)_minmax(18rem,0.8fr)] lg:gap-12',
                        !showArtwork && 'lg:grid-cols-1',
                    )}
                >
                    <div className="flex min-w-0 flex-col items-start gap-4">
                        {eyebrow && (
                            <div className="flex items-center gap-3">
                                <span
                                    aria-hidden="true"
                                    className="h-0.5 w-7 shrink-0 bg-primary"
                                />
                                <Text
                                    as="p"
                                    variant="label"
                                    className="text-[11px] font-semibold tracking-[0.12em] text-primary uppercase"
                                >
                                    {eyebrow}
                                </Text>
                            </div>
                        )}

                        <div className="flex w-full flex-col gap-3">
                            <Text
                                id={titleId}
                                as={Heading}
                                variant="display-xl"
                                className="max-w-4xl text-balance"
                            >
                                {title}
                            </Text>

                            {description && (
                                <Text
                                    as="p"
                                    variant="body"
                                    color="muted"
                                    className="max-w-3xl text-pretty"
                                >
                                    {description}
                                </Text>
                            )}
                        </div>

                        {children}

                        {actions && (
                            <div className="flex flex-wrap items-center gap-3 pt-1">
                                {actions}
                            </div>
                        )}

                        {breadcrumbs.length > 0 && (
                            <Breadcrumbs
                                breadcrumbs={breadcrumbs}
                                className="pt-1"
                            />
                        )}
                    </div>

                    {showArtwork && (
                        <div className="hidden min-w-0 lg:block">
                            <PublicHeroArtwork />
                        </div>
                    )}
                </div>
            </PublicContainer>
        </section>
    );
}

export default PublicHeroBanner;
