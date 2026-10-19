import type { InertiaLinkProps } from '@inertiajs/react';
import { Link, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { useState } from 'react';
import { AppearanceToggle } from '@/components/appearance-toggle';
import ParishLogo from '@/components/parish-logo';
import { PublicContainer } from '@/components/public-container';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuTrigger,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import type { IdentityDetails } from '@/types';
import { home } from '@/routes';
import { index as agenda } from '@/routes/agenda';
import { index as berita } from '@/routes/berita';
import { index as kontak } from '@/routes/kontak';
import { index as dokumen } from '@/routes/dokumen';
import { index as galeri } from '@/routes/galeri';
import { index as jadwalMisa } from '@/routes/jadwal-misa';
import { index as komunitas } from '@/routes/komunitas';
import { index as pelayanan } from '@/routes/pelayanan';
import { index as areaIndex } from '@/routes/area';
import { pastor, sejarah, struktur, wilayah } from '@/routes/profil';

type NavHref = NonNullable<InertiaLinkProps['href']>;

type NavEntry = {
    label: string;
    href: NavHref;
    /**
     * How the current URL is matched against `href`.
     *
     * `exact`   only the same path (Beranda, because "/" prefixes everything).
     * `section` the path or anything under it (the default).
     * `prefix`  anything under the href's parent path. Profil links to
     *           /profil/sejarah, but the whole /profil/* section should stay
     *           active, so it matches on /profil.
     */
    match: 'exact' | 'section' | 'prefix';
};

/** The parent path of a section link, e.g. /profil/sejarah -> /profil. */
function sectionPrefix(href: NavHref): string {
    const path = toUrl(href).split('?')[0];

    return path.replace(/\/[^/]+\/?$/, '') || '/';
}

/**
 * The mobile drawer's destinations. Kept flat and in DESIGN-v2 §8's order, so a
 * phone visitor sees every destination in one scroll and nothing is hidden one
 * tap deeper.
 */
const DRAWER_ENTRIES: NavEntry[] = [
    { label: 'Beranda', href: home(), match: 'exact' },
    { label: 'Profil', href: sejarah(), match: 'prefix' },
    { label: 'Jadwal Misa', href: jadwalMisa(), match: 'section' },
    { label: 'Berita & Artikel', href: berita(), match: 'section' },
    { label: 'Agenda', href: agenda(), match: 'section' },
    { label: 'Pelayanan', href: pelayanan(), match: 'section' },
    { label: 'Komunitas', href: komunitas(), match: 'section' },
    { label: 'Area', href: areaIndex(), match: 'section' },
    { label: 'Galeri', href: galeri(), match: 'section' },
    { label: 'Dokumen', href: dokumen(), match: 'section' },
    { label: 'Kontak', href: kontak(), match: 'section' },
];

const PROFIL_CHILDREN: NavEntry[] = [
    { label: 'Sejarah', href: sejarah(), match: 'section' },
    { label: 'Wilayah Pelayanan', href: wilayah(), match: 'section' },
    { label: 'Area', href: areaIndex(), match: 'section' },
    { label: 'Pastor', href: pastor(), match: 'section' },
    { label: 'Struktur Kepengurusan', href: struktur(), match: 'section' },
];

const PELAYANAN_CHILDREN: NavEntry[] = [
    { label: 'Pelayanan', href: pelayanan(), match: 'section' },
    { label: 'Komunitas', href: komunitas(), match: 'section' },
    { label: 'Galeri', href: galeri(), match: 'section' },
    { label: 'Dokumen', href: dokumen(), match: 'section' },
];

type DesktopEntry =
    | { kind: 'link'; entry: NavEntry }
    | { kind: 'group'; label: string; children: NavEntry[] };

/**
 * The desktop bar, grouped so eleven destinations become seven controls with
 * room to breathe. The grouping reuses the same information architecture as the
 * footer (D-73). A group's landing page is its first child, so it stays one
 * click away inside the dropdown.
 */
const DESKTOP_ENTRIES: DesktopEntry[] = [
    { kind: 'link', entry: { label: 'Beranda', href: home(), match: 'exact' } },
    { kind: 'group', label: 'Profil', children: PROFIL_CHILDREN },
    {
        kind: 'link',
        entry: {
            label: 'Jadwal Misa',
            href: jadwalMisa(),
            match: 'section',
        },
    },
    {
        kind: 'link',
        entry: { label: 'Berita & Artikel', href: berita(), match: 'section' },
    },
    {
        kind: 'link',
        entry: { label: 'Agenda', href: agenda(), match: 'section' },
    },
    { kind: 'group', label: 'Pelayanan', children: PELAYANAN_CHILDREN },
    {
        kind: 'link',
        entry: { label: 'Kontak', href: kontak(), match: 'section' },
    },
];

function Logo() {
    const { identity } = usePage<{ identity?: IdentityDetails }>().props;

    const primary = identity?.shortName ?? identity?.name ?? 'Paroki';
    const secondary = identity?.name ?? identity?.tagline ?? null;

    return (
        <span className="flex items-center gap-3">
            <ParishLogo width={40} height={42} />

            <span className="hidden flex-col leading-tight sm:flex">
                <Text as="span" variant="label" color="foreground">
                    {primary}
                </Text>
                {secondary ? (
                    <Text as="span" variant="fine-print" color="muted">
                        {secondary}
                    </Text>
                ) : null}
            </span>
        </span>
    );
}

/**
 * DESIGN-v2 marks the active route with red text and a thin marker. The marker
 * is the non-colour cue; primary-on-surface is used for it because the brand red
 * would dissolve on the dark canvas.
 */
const ACTIVE_MARKER = {
    boxShadow: 'inset 0 -2px 0 0 var(--primary-on-surface)',
} as const;

function DesktopNav() {
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    const isActive = (entry: NavEntry): boolean =>
        entry.match === 'exact'
            ? isCurrentUrl(entry.href)
            : entry.match === 'prefix'
              ? isCurrentOrParentUrl(sectionPrefix(entry.href))
              : isCurrentOrParentUrl(entry.href);

    return (
        <NavigationMenu
            viewport={false}
            aria-label="Navigasi utama"
            className="hidden lg:flex"
        >
            <NavigationMenuList className="gap-1">
                {DESKTOP_ENTRIES.map((item) => {
                    if (item.kind === 'link') {
                        const active = isActive(item.entry);

                        return (
                            <NavigationMenuItem key={item.entry.label}>
                                <Link
                                    href={item.entry.href}
                                    aria-current={active ? 'page' : undefined}
                                    className={cn(
                                        navigationMenuTriggerStyle(),
                                        'relative',
                                        active && 'text-primary-on-surface',
                                    )}
                                    style={active ? ACTIVE_MARKER : undefined}
                                >
                                    {item.entry.label}
                                </Link>
                            </NavigationMenuItem>
                        );
                    }

                    const active = item.children.some(isActive);

                    return (
                        <NavigationMenuItem key={item.label}>
                            <NavigationMenuTrigger
                                data-active={active ? 'true' : undefined}
                                aria-current={active ? 'page' : undefined}
                                className={cn(
                                    active && 'text-primary-on-surface',
                                )}
                                style={active ? ACTIVE_MARKER : undefined}
                            >
                                {item.label}
                            </NavigationMenuTrigger>

                            <NavigationMenuContent>
                                <ul className="grid w-[16rem] gap-1">
                                    {item.children.map((child) => {
                                        const childActive = isActive(child);

                                        return (
                                            <li key={child.label}>
                                                <NavigationMenuLink
                                                    asChild
                                                    data-active={
                                                        childActive
                                                            ? 'true'
                                                            : undefined
                                                    }
                                                >
                                                    <Link
                                                        href={child.href}
                                                        prefetch
                                                        aria-current={
                                                            childActive
                                                                ? 'page'
                                                                : undefined
                                                        }
                                                        className="block"
                                                    >
                                                        {child.label}
                                                    </Link>
                                                </NavigationMenuLink>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </NavigationMenuContent>
                        </NavigationMenuItem>
                    );
                })}
            </NavigationMenuList>
        </NavigationMenu>
    );
}

/** The flat mobile drawer. */
function DrawerNav({ onNavigate }: { onNavigate?: () => void }) {
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <ul className="flex flex-col gap-1">
            {DRAWER_ENTRIES.map((entry) => {
                const active =
                    entry.match === 'exact'
                        ? isCurrentUrl(entry.href)
                        : entry.match === 'prefix'
                          ? isCurrentOrParentUrl(sectionPrefix(entry.href))
                          : isCurrentOrParentUrl(entry.href);

                return (
                    <li key={entry.label}>
                        <Link
                            href={entry.href}
                            onClick={onNavigate}
                            aria-current={active ? 'page' : undefined}
                            className="flex min-h-11 items-center rounded-md px-3 transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <Text
                                as="span"
                                variant="nav"
                                color={active ? 'danger' : undefined}
                                className={
                                    active
                                        ? undefined
                                        : 'hover:text-primary-on-surface'
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

    const { identity } = usePage<{ identity?: IdentityDetails }>().props;

    const closeMenu = () => setMenuOpen(false);

    return (
        <header className="sticky top-0 z-40 h-16 w-full border-b border-border bg-background">
            <PublicContainer className="flex h-full items-center justify-between gap-4">
                <Link
                    href={home()}
                    onClick={closeMenu}
                    className="shrink-0 rounded-md bg-transparent focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                >
                    <span className="sr-only">
                        {identity?.name ?? 'Paroki'}, beranda
                    </span>
                    <span aria-hidden="true">
                        <Logo />
                    </span>
                </Link>

                <DesktopNav />

                <div className="flex items-center gap-2">
                    <AppearanceToggle />

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
                                <DrawerNav onNavigate={closeMenu} />
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>
            </PublicContainer>
        </header>
    );
}

export default ParishNavbar;
