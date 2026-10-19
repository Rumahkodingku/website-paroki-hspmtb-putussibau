import type { InertiaLinkProps } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    CalendarDays,
    CalendarRange,
    HeartHandshake,
    Newspaper,
    type LucideIcon,
} from 'lucide-react';
import { PublicSection } from '@/components/public-section';
import { Text } from '@/components/text';
import { index as agenda } from '@/routes/agenda';
import { index as berita } from '@/routes/berita';
import { index as jadwalMisa } from '@/routes/jadwal-misa';
import { index as pelayanan } from '@/routes/pelayanan';

type QuickLink = {
    label: string;
    description: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon: LucideIcon;
};

/**
 * Four destinations a visitor is most likely to want from the homepage.
 *
 * The strip is a single divided surface rather than four floating cards: the
 * links are a navigation aid, not four separate pieces of content competing
 * with the news and agenda below.
 */
export function HomeQuickLinks() {
    const links: QuickLink[] = [
        {
            label: 'Jadwal Misa',
            description: 'Jadwal rutin dan misa khusus',
            href: jadwalMisa(),
            icon: CalendarDays,
        },
        {
            label: 'Berita & Artikel',
            description: 'Kabar dan renungan paroki',
            href: berita(),
            icon: Newspaper,
        },
        {
            label: 'Agenda',
            description: 'Kegiatan mendatang',
            href: agenda(),
            icon: CalendarRange,
        },
        {
            label: 'Pelayanan',
            description: 'Sakramen dan pendampingan',
            href: pelayanan(),
            icon: HeartHandshake,
        },
    ];

    return (
        <PublicSection size="default">
            <nav aria-label="Pintasan beranda">
                <ul className="grid grid-cols-1 gap-px overflow-hidden rounded-lg border border-border bg-border sm:grid-cols-2 lg:grid-cols-4">
                    {links.map((link) => {
                        const Icon = link.icon;

                        return (
                            <li key={link.label} className="bg-card">
                                <Link
                                    href={link.href}
                                    className="group flex min-h-24 items-center gap-4 p-5 transition-colors hover:bg-canvas-subtle focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset"
                                >
                                    <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-primary-soft text-primary-on-surface">
                                        <Icon
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    </span>

                                    <span className="min-w-0 flex-1">
                                        <Text
                                            as="span"
                                            variant="heading-sm"
                                            className="block"
                                        >
                                            {link.label}
                                        </Text>
                                        <Text
                                            as="span"
                                            variant="body-sm"
                                            color="muted"
                                            className="mt-0.5 block"
                                        >
                                            {link.description}
                                        </Text>
                                    </span>

                                    <ArrowRight
                                        className="size-4 shrink-0 text-subtle transition-transform group-hover:translate-x-1 motion-reduce:transform-none motion-reduce:transition-none"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>
        </PublicSection>
    );
}

export default HomeQuickLinks;
