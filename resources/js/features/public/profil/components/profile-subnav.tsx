import { Link, usePage } from '@inertiajs/react';
import { PublicContainer } from '@/components/public-container';
import { Text } from '@/components/text';
import { cn } from '@/lib/utils';
import { pastor, sejarah, struktur, wilayah } from '@/routes/profil';

/*
 * ProfileSubNav - DESIGN.md, "Profile Components".
 *
 * A contextual navigation for the Profil section. Remains visually secondary to
 * the content: a hairline row that scrolls horizontally on a phone rather than a
 * loud tab bar. The active item is marked by weight and a red underline, never
 * by colour alone (roadmap 6A-82).
 */

const LINKS = [
    { label: 'Sejarah', href: sejarah() },
    { label: 'Wilayah Pelayanan', href: wilayah() },
    { label: 'Pastor', href: pastor() },
    { label: 'Struktur Kepengurusan', href: struktur() },
] as const;

export function ProfileSubNav() {
    const { url } = usePage();
    const path = url.split('?')[0];

    return (
        <nav
            aria-label="Navigasi Profil Paroki"
            className="border-b border-divider-soft bg-background"
        >
            <PublicContainer>
                <ul className="flex gap-1 overflow-x-auto">
                    {LINKS.map((link) => {
                        const active = link.href.url === path;

                        return (
                            <li key={link.href.url}>
                                <Link
                                    href={link.href}
                                    aria-current={active ? 'page' : undefined}
                                    className={cn(
                                        'inline-flex min-h-11 items-center border-b-2 px-3 text-sm whitespace-nowrap transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                                        active
                                            ? 'border-primary font-medium text-foreground'
                                            : 'border-transparent text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    <Text as="span" variant="caption-strong">
                                        {link.label}
                                    </Text>
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </PublicContainer>
        </nav>
    );
}

export default ProfileSubNav;
