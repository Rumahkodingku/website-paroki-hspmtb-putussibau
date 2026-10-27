import { Link, usePage } from '@inertiajs/react';
import { Menu } from 'lucide-react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { AppearanceToggle } from '@/components/appearance-toggle';
import ParishLogo from '@/components/parish-logo';
import { PublicContainer } from '@/components/public-container';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { home } from '@/routes';
import type { IdentityDetails } from '@/types';

import { DesktopNav } from './navbar/desktop-nav';
import { DrawerNav } from './navbar/drawer-nav';
import { NavLogo } from './navbar/nav-logo';

export function ParishNavbar() {
    const [menuOpen, setMenuOpen] = useState(false);
    const [isScrolled, setIsScrolled] = useState(false);

    const { identity } = usePage<{ identity?: IdentityDetails }>().props;
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
                        <NavLogo transparent={isTransparent} />
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
                                    className="scrollbar-thin min-h-0 flex-1 scrollbar-thumb-muted-foreground/30 scrollbar-track-transparent overflow-y-auto px-4 pb-6"
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
