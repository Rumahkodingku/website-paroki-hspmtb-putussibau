import type { InertiaLinkProps } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { useState } from 'react';
import { AppearanceToggle } from '@/components/appearance-toggle';
import { PublicContainer } from '@/components/public-container';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { home } from '@/routes';
import { index as agenda } from '@/routes/agenda';
import { index as berita } from '@/routes/berita';
import { index as kontak } from '@/routes/kontak';
import { index as download } from '@/routes/download';
import { index as galeri } from '@/routes/galeri';
import { index as jadwalMisa } from '@/routes/jadwal-misa';
import { index as komunitas } from '@/routes/komunitas';
import { index as pelayanan } from '@/routes/pelayanan';
import { index as profil } from '@/routes/profil';

type NavEntry = {
    label: string;
    href: NonNullable<InertiaLinkProps['href']>;
    match: 'exact' | 'section';
};

const NAV_ENTRIES: NavEntry[] = [
    { label: 'Beranda', href: home(), match: 'exact' },
    { label: 'Profil', href: profil(), match: 'section' },
    { label: 'Jadwal Misa', href: jadwalMisa(), match: 'section' },
    { label: 'Berita & Artikel', href: berita(), match: 'section' },
    { label: 'Agenda', href: agenda(), match: 'section' },
    { label: 'Pelayanan', href: pelayanan(), match: 'section' },
    { label: 'Komunitas', href: komunitas(), match: 'section' },
    { label: 'Galeri', href: galeri(), match: 'section' },
    { label: 'Download', href: download(), match: 'section' },
    { label: 'Kontak', href: kontak(), match: 'section' },
];

function ParishLogo() {
    return (
        <span className="flex items-center gap-3">
            <img
                src="/logo.svg"
                alt=""
                width={40}
                height={42}
                className="size-10 shrink-0 object-contain"
            />
            {/*
                leading-tight stays on the wrapper: it is what makes these two
                lines read as one lockup inside a 64px bar. The lines themselves
                are named roles, and the full parish name is fine-print rather
                than micro-legal here because it has to stay legible - it is the
                only place a visitor is told which parish they are on.
            */}
            <span className="hidden flex-col leading-tight sm:flex">
                <Text as="span" variant="caption-strong" color="foreground">
                    HSPMTB Putussibau
                </Text>
                <Text as="span" variant="micro-legal" color="muted">
                    Hati Santa Perawan Maria Tak Bernoda
                </Text>
            </span>
        </span>
    );
}

function NavList({
    onNavigate,
    variant,
}: {
    onNavigate?: () => void;
    variant: 'bar' | 'drawer';
}) {
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    const drawer = variant === 'drawer';

    return (
        <ul
            className={
                drawer
                    ? 'flex flex-col gap-1'
                    : 'flex flex-col gap-1 md:flex-row md:items-center md:gap-6'
            }
        >
            {NAV_ENTRIES.map((entry) => {
                const active =
                    entry.match === 'exact'
                        ? isCurrentUrl(entry.href)
                        : isCurrentOrParentUrl(entry.href);

                return (
                    <li key={entry.label}>
                        {/*
                            The nav-link token lives on the Text rather than
                            on the Link, because the token is a line height of
                            1.0 and the Link is a flex row: putting it on the
                            anchor makes the label sit off the vertical centre
                            of the 44px hit target. On the inner span it centres
                            correctly and min-h-11 still owns the touch target.

                            font-medium on the active item overrides the
                            token's 400 weight, which is the same weight every
                            inactive item carries - the difference between the
                            two states has to be visible without colour, or it
                            fails for anyone who cannot see the red.
                        */}
                        <Link
                            href={entry.href}
                            onClick={onNavigate}
                            aria-current={active ? 'page' : undefined}
                            className={
                                drawer
                                    ? 'flex min-h-11 items-center rounded-md px-3 transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none'
                                    : 'relative inline-flex h-11 items-center rounded-sm transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none'
                            }
                            style={
                                active && !drawer
                                    ? {
                                          boxShadow:
                                              'inset 0 -2px 0 0 var(--primary)',
                                      }
                                    : undefined
                            }
                        >
                            <Text
                                as="span"
                                variant="nav"
                                weight={active ? 'medium' : undefined}
                                color={active ? 'danger' : undefined}
                                className={
                                    active
                                        ? undefined
                                        : 'hover:text-red-on-surface'
                                }
                            >
                                {entry.label}
                            </Text>
                        </Link>
                    </li>
                );
            })}
        </ul>
    );
}

export function ParishNavbar() {
    const [menuOpen, setMenuOpen] = useState(false);

    const closeMenu = () => setMenuOpen(false);

    return (
        /*
         * border-border, not border-divider-soft. DESIGN.md v1.1 changed
         * components.global-nav from {colors.divider-soft} to
         * {semantic.border}, which is hairline - one step more visible than the
         * divider. The footer keeps divider-soft: DESIGN.md still describes it
         * as "very subtle section and navigation separation" and its component
         * entry names no border at all.
         */
        <header className="sticky top-0 z-40 h-16 w-full border-b border-border bg-background">
            <PublicContainer className="flex h-full items-center justify-between gap-4">
                <Link
                    href={home()}
                    onClick={closeMenu}
                    className="shrink-0 rounded-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                >
                    <span className="sr-only">
                        Paroki HSPMTB Putussibau, beranda
                    </span>
                    <span aria-hidden="true">
                        <ParishLogo />
                    </span>
                </Link>

                <nav aria-label="Navigasi utama" className="hidden lg:block">
                    <NavList variant="bar" />
                </nav>

                <div className="flex items-center gap-2">
                    <AppearanceToggle />

                    <Button asChild size="lg" className="hidden lg:inline-flex">
                        <Link href={jadwalMisa()}>Jadwal Misa</Link>
                    </Button>

                    <Sheet open={menuOpen} onOpenChange={setMenuOpen}>
                        <SheetTrigger asChild>
                            <Button
                                variant="outline"
                                size="icon"
                                className="lg:hidden"
                                aria-label="Buka navigasi"
                            >
                                <Menu />
                            </Button>
                        </SheetTrigger>

                        <SheetContent
                            side="right"
                            className="flex w-80 max-w-[85vw] flex-col"
                        >
                            <SheetTitle className="px-4 pt-4 text-left">
                                <span className="sr-only">Navigasi utama</span>
                                <span aria-hidden="true">
                                    <ParishLogo />
                                </span>
                            </SheetTitle>

                            <nav
                                aria-label="Navigasi utama"
                                className="min-h-0 flex-1 overflow-y-auto px-4 pb-6"
                            >
                                <NavList
                                    variant="drawer"
                                    onNavigate={closeMenu}
                                />
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>
            </PublicContainer>
        </header>
    );
}

export default ParishNavbar;
