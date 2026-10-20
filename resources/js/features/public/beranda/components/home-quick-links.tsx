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

import { Text } from '@/components/text';
import { index as agenda } from '@/routes/agenda';
import { index as berita } from '@/routes/berita';
import { index as jadwalMisa } from '@/routes/jadwal-misa';
import { index as pelayanan } from '@/routes/pelayanan';
import PublicContainer from '@/components/public-container';

type QuickLink = {
    label: string;
    description: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon: LucideIcon;
};

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
            description: 'Kegiatan paroki mendatang',
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
        <PublicContainer className="relative z-10 -mt-14 pb-0 md:-mt-16">
            <nav aria-label="Pintasan beranda">
                <ul className="grid grid-cols-1 overflow-hidden rounded-2xl border bg-background sm:grid-cols-2 lg:grid-cols-4">
                    {links.map((link, index) => {
                        const Icon = link.icon;

                        return (
                            <li
                                key={link.label}
                                className={[
                                    'relative min-w-0',
                                    index > 0
                                        ? 'border-t border-border/70 sm:border-t-0'
                                        : '',
                                    index % 2 === 1
                                        ? 'sm:border-s sm:border-border/70'
                                        : '',
                                    index > 1
                                        ? 'lg:border-s lg:border-border/70'
                                        : '',
                                ]
                                    .filter(Boolean)
                                    .join(' ')}
                            >
                                <Link
                                    href={link.href}
                                    className="group flex min-h-28 items-center gap-4 p-5 transition-colors duration-200 hover:bg-muted/50 focus-visible:z-10 focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none focus-visible:ring-inset sm:min-h-32 sm:px-6"
                                >
                                    <span className="flex size-12 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary transition-colors duration-200 group-hover:bg-primary group-hover:text-primary-foreground sm:size-14">
                                        <Icon
                                            className="size-6"
                                            aria-hidden="true"
                                            strokeWidth={1.8}
                                        />
                                    </span>

                                    <span className="min-w-0 flex-1">
                                        <Text
                                            as="span"
                                            variant="heading-sm"
                                            className="block transition-colors duration-200 group-hover:text-primary"
                                        >
                                            {link.label}
                                        </Text>

                                        <Text
                                            as="span"
                                            variant="body-sm"
                                            color="muted"
                                            className="mt-1 block leading-relaxed"
                                        >
                                            {link.description}
                                        </Text>
                                    </span>

                                    <ArrowRight
                                        className="size-4 shrink-0 text-muted-foreground/70 transition-all duration-200 group-hover:translate-x-1 group-hover:text-primary motion-reduce:transform-none motion-reduce:transition-none"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </li>
                        );
                    })}
                </ul>
            </nav>
        </PublicContainer>
    );
}

export default HomeQuickLinks;
