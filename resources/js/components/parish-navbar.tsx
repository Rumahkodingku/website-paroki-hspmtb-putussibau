import { Link } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { home } from '@/routes';

/*
 * ParishNavbar - DESIGN.md, components.global-nav
 *
 * Persistent white bar, 64px tall, hairline bottom border, 13px nav links. The
 * document replaces Apple's black global navigation with a quieter parish
 * surface, so this stays light and out of the way.
 *
 * Every section listed here is a placeholder. The public routes do not exist in
 * Phase 01 - PRD section 5.1 puts them in a later phase - so the entries render
 * as disabled rather than as links to nowhere. Section 17 of the roadmap is
 * explicit that a module must not look finished just because its menu entry is
 * on screen, and a live-looking link to a 404 is exactly that failure.
 */

type NavEntry = {
    label: string;
    /** Absent until the matching public route is built. */
    href?: string;
};

const NAV_ENTRIES: NavEntry[] = [
    { label: 'Beranda', href: '/' },
    { label: 'Profil' },
    { label: 'Jadwal Misa' },
    { label: 'Berita & Artikel' },
    { label: 'Agenda' },
    { label: 'Pelayanan' },
    { label: 'Komunitas' },
    { label: 'Galeri' },
    { label: 'Download' },
    { label: 'Kontak' },
];

function NavList({ onNavigate }: { onNavigate?: () => void }) {
    return (
        <ul className="flex flex-col gap-1 md:flex-row md:items-center md:gap-6">
            {NAV_ENTRIES.map((entry) => (
                <li key={entry.label}>
                    {entry.href ? (
                        <Link
                            href={entry.href}
                            onClick={onNavigate}
                            className="inline-flex h-11 items-center text-nav-link text-foreground transition-colors hover:text-primary focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                        >
                            {entry.label}
                        </Link>
                    ) : (
                        <span
                            aria-disabled="true"
                            className="inline-flex h-11 cursor-default items-center text-nav-link text-ink-muted-soft"
                        >
                            {entry.label}
                        </span>
                    )}
                </li>
            ))}
        </ul>
    );
}

function ParishWordmark() {
    return (
        <span className="flex flex-col leading-tight">
            <span className="text-caption-strong text-foreground">
                HSPMTB Putussibau
            </span>
            {/* DESIGN.md forbids inventing parish identity, so the mark is a
                labelled placeholder rather than a drawn or invented crest. */}
            <span className="text-micro-legal text-muted-foreground">
                [ISI: logo paroki]
            </span>
        </span>
    );
}

export function ParishNavbar({ children }: { children?: ReactNode }) {
    return (
        <header className="sticky top-0 z-40 h-16 w-full border-b border-divider-soft bg-background">
            <div className="mx-auto flex h-full w-full max-w-7xl items-center justify-between gap-4 px-4">
                <Link
                    href={home()}
                    className="flex items-center focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                >
                    <ParishWordmark />
                </Link>

                <nav aria-label="Navigasi utama" className="hidden lg:block">
                    <NavList />
                </nav>

                <div className="flex items-center gap-2">
                    {children}

                    <Sheet>
                        <SheetTrigger asChild>
                            {/* 44x44 minimum touch target, per the responsive
                                section of DESIGN.md. */}
                            <Button
                                variant="outline"
                                size="icon"
                                className="lg:hidden"
                                aria-label="Buka navigasi"
                            >
                                <Menu />
                            </Button>
                        </SheetTrigger>

                        <SheetContent side="right" className="w-72">
                            <SheetTitle className="px-4 pt-4 text-left">
                                <ParishWordmark />
                            </SheetTitle>

                            <nav
                                aria-label="Navigasi utama"
                                className="px-4 pb-6"
                            >
                                <NavList />
                            </nav>
                        </SheetContent>
                    </Sheet>
                </div>
            </div>
        </header>
    );
}
