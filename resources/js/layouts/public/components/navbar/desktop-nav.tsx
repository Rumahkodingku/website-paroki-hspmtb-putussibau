import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { navigationMenuTriggerStyle } from '@/components/ui/navigation-menu';
import {
    NavigationMenu,
    NavigationMenuContent,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    NavigationMenuTrigger,
} from '@/components/ui/navigation-menu';
import { useNavActive } from '@/layouts/public/hooks/use-nav-active';
import { DESKTOP_ENTRIES, type NavItem } from '@/layouts/public/navigation';

type DesktopNavProps = {
    transparent: boolean;
};

export function DesktopNav({ transparent }: DesktopNavProps) {
    const { isActive } = useNavActive();
    const linkColors = transparent
        ? 'text-white hover:bg-white/10 hover:text-white focus-visible:text-white'
        : 'text-foreground';
    const activeColors = transparent ? 'text-white' : 'text-primary';
    const activeMarker = transparent
        ? 'inset 0 -2px 0 0 #DCA640'
        : 'inset 0 -2px 0 0 var(--primary)';

    return (
        <NavigationMenu
            viewport={false}
            aria-label="Navigasi utama"
            className="hidden lg:flex"
        >
            <NavigationMenuList className="gap-1">
                {DESKTOP_ENTRIES.map((item: NavItem) => {
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
                                    style={
                                        active
                                            ? { boxShadow: activeMarker }
                                            : undefined
                                    }
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
                                style={
                                    active
                                        ? { boxShadow: activeMarker }
                                        : undefined
                                }
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
