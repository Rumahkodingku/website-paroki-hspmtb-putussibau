import type { InertiaLinkProps } from '@inertiajs/react';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown, Menu } from 'lucide-react';
import { useEffect, useState } from 'react';

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
     * exact   : Hanya URL yang sama.
     * section : URL dan turunannya.
     * prefix  : Seluruh section induk dari sebuah URL.
     */
    match: 'exact' | 'section' | 'prefix';
};

function sectionPrefix(href: NavHref): string {
    const path = toUrl(href).split('?')[0];

    return path.replace(/\/[^/]+\/?$/, '') || '/';
}

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
    {
        label: 'Struktur Kepengurusan',
        href: struktur(),
        match: 'section',
    },
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

const DESKTOP_ENTRIES: DesktopEntry[] = [
    {
        kind: 'link',
        entry: { label: 'Beranda', href: home(), match: 'exact' },
    },
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
        entry: {
            label: 'Berita & Artikel',
            href: berita(),
            match: 'section',
        },
    },
    {
        kind: 'link',
        entry: { label: 'Agenda', href: agenda(), match: 'section' },
    },
    {
        kind: 'group',
        label: 'Pelayanan',
        children: PELAYANAN_CHILDREN,
    },
    {
        kind: 'link',
        entry: { label: 'Kontak', href: kontak(), match: 'section' },
    },
];

function Logo({ transparent }: { transparent: boolean }) {
    const { identity } = usePage<{ identity?: IdentityDetails }>().props;

    const primary = identity?.shortName ?? identity?.name ?? 'Paroki';
    const secondary = identity?.name ?? identity?.tagline ?? null;

    return (
        <span className="flex items-center gap-3">
            <ParishLogo width={40} height={42} />

            <span className="hidden flex-col leading-tight sm:flex">
                <Text
                    as="span"
                    variant="label"
                    color="foreground"
                    weight="medium"
                    className={cn(
                        'transition-colors duration-300',
                        transparent && 'text-white!',
                    )}
                >
                    {primary}
                </Text>

                {secondary ? (
                    <Text
                        as="span"
                        variant="fine-print"
                        color="muted"
                        weight="medium"
                        className={cn(
                            'transition-colors duration-300',
                            transparent && 'text-white/75!',
                        )}
                    >
                        {secondary}
                    </Text>
                ) : null}
            </span>
        </span>
    );
}

function DesktopNav({ transparent }: { transparent: boolean }) {
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

    const isActive = (entry: NavEntry): boolean =>
        entry.match === 'exact'
            ? isCurrentUrl(entry.href)
            : entry.match === 'prefix'
              ? isCurrentOrParentUrl(sectionPrefix(entry.href))
              : isCurrentOrParentUrl(entry.href);

    const activeMarker = {
        boxShadow: transparent
            ? 'inset 0 -2px 0 0 #DCA640'
            : 'inset 0 -2px 0 0 var(--primary)',
    } as const;

    const linkColors = transparent
        ? 'text-white hover:bg-white/10 hover:text-white focus-visible:text-white'
        : 'text-foreground';

    const activeColors = transparent ? 'text-white' : 'text-primary';

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
                                        'relative font-medium transition-colors duration-300',
                                        linkColors,
                                        active && activeColors,
                                    )}
                                    style={active ? activeMarker : undefined}
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
                                    'font-medium transition-colors duration-300',
                                    linkColors,
                                    active && activeColors,
                                )}
                                style={active ? activeMarker : undefined}
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
                                                        className="block font-medium"
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

function DrawerNav({ onNavigate }: { onNavigate?: () => void }) {
    const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
    const [expandedMenus, setExpandedMenus] = useState<string[]>([]);

    const isActive = (entry: NavEntry): boolean =>
        entry.match === 'exact'
            ? isCurrentUrl(entry.href)
            : entry.match === 'prefix'
              ? isCurrentOrParentUrl(sectionPrefix(entry.href))
              : isCurrentOrParentUrl(entry.href);

    const toggleMenu = (label: string) => {
        setExpandedMenus((current) =>
            current.includes(label)
                ? current.filter((item) => item !== label)
                : [...current, label],
        );
    };

    const renderLink = (entry: NavEntry, nested = false) => {
        const active = isActive(entry);

        return (
            <Link
                key={entry.label}
                href={entry.href}
                onClick={onNavigate}
                aria-current={active ? 'page' : undefined}
                className={cn(
                    'flex min-h-11 items-center rounded-lg px-3 transition-colors',
                    'focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                    nested && 'ml-4 border-l border-border pl-4',
                    active
                        ? 'bg-primary/10 font-medium text-primary'
                        : 'text-foreground hover:bg-muted',
                )}
            >
                <Text
                    as="span"
                    variant="nav"
                    weight="medium"
                    color={active ? 'danger' : undefined}
                >
                    {entry.label}
                </Text>
            </Link>
        );
    };

    const renderGroup = (label: string, children: NavEntry[]) => {
        const active = children.some(isActive);
        const expanded = expandedMenus.includes(label) || active;

        return (
            <li key={label} className="space-y-1">
                <button
                    type="button"
                    aria-expanded={expanded}
                    onClick={() => toggleMenu(label)}
                    className={cn(
                        'flex min-h-11 w-full items-center justify-between rounded-lg px-3 text-left transition-colors',
                        'focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                        active
                            ? 'bg-primary/10 font-medium text-primary'
                            : 'text-foreground hover:bg-muted',
                    )}
                >
                    <Text
                        as="span"
                        variant="nav"
                        weight="medium"
                        color={active ? 'danger' : undefined}
                    >
                        {label}
                    </Text>

                    <ChevronDown
                        aria-hidden="true"
                        className={cn(
                            'size-4 shrink-0 transition-transform duration-200',
                            expanded && 'rotate-180',
                        )}
                    />
                </button>

                {expanded && (
                    <ul className="space-y-1">
                        {children.map((child) => (
                            <li key={child.label}>{renderLink(child, true)}</li>
                        ))}
                    </ul>
                )}
            </li>
        );
    };

    return (
        <ul className="flex flex-col gap-1">
            {renderLink(DRAWER_ENTRIES[0])}

            {renderGroup('Profil', PROFIL_CHILDREN)}

            {renderLink(DRAWER_ENTRIES[2])}
            {renderLink(DRAWER_ENTRIES[3])}
            {renderLink(DRAWER_ENTRIES[4])}

            {renderGroup('Pelayanan', PELAYANAN_CHILDREN)}

            {renderLink(DRAWER_ENTRIES[10])}
        </ul>
    );
}

export function ParishNavbar() {
    const [menuOpen, setMenuOpen] = useState(false);
    const [isScrolled, setIsScrolled] = useState(false);

    const { identity } = usePage<{
        identity?: IdentityDetails;
    }>().props;

    const { isCurrentUrl } = useCurrentUrl();

    const isHome = isCurrentUrl(home());
    const isTransparent = isHome && !isScrolled;

    const closeMenu = () => setMenuOpen(false);

    useEffect(() => {
        const handleScroll = () => {
            setIsScrolled(window.scrollY > 24);
        };

        handleScroll();

        window.addEventListener('scroll', handleScroll, {
            passive: true,
        });

        return () => {
            window.removeEventListener('scroll', handleScroll);
        };
    }, []);

    return (
        <header
            className={cn(
                'z-50 w-full transition-[background-color,box-shadow,border-color,backdrop-filter] duration-300 ease-out',
                isHome ? 'fixed top-0 right-0 left-0' : 'sticky top-0',
                isTransparent
                    ? 'border-b border-transparent bg-transparent shadow-none'
                    : 'border-b border-border/80 bg-background/95 shadow-sm backdrop-blur-md',
            )}
        >
            <PublicContainer className="flex h-16 items-center justify-between gap-4">
                <Link
                    href={home()}
                    onClick={closeMenu}
                    className="shrink-0 rounded-md bg-transparent focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                >
                    <span className="sr-only">
                        {identity?.name ?? 'Paroki'}, beranda
                    </span>

                    <span aria-hidden="true">
                        <Logo transparent={isTransparent} />
                    </span>
                </Link>

                <div className="flex flex-row gap-4">
                    <DesktopNav transparent={isTransparent} />
                    <div className="flex items-center gap-2">
                        <span
                            aria-hidden="true"
                            className={cn(
                                'mx-1 h-6 w-px transition-colors duration-300',
                                isTransparent ? 'bg-white/30' : 'bg-border',
                            )}
                        />

                        <AppearanceToggle />

                        <Sheet open={menuOpen} onOpenChange={setMenuOpen}>
                            <SheetTrigger asChild>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    className={cn(
                                        'transition-colors duration-300 lg:hidden',
                                        isTransparent &&
                                            'border-white/40 bg-white/10 text-white hover:bg-white/20 hover:text-white',
                                    )}
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
                                    <span className="sr-only">
                                        Navigasi utama
                                    </span>

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
                </div>
            </PublicContainer>
        </header>
    );
}

export default ParishNavbar;
